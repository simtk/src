<?php

require_once("common/include/Group.class.php");
require_once("common/xml/XmlObject.class.php");


class ProjectXml extends XmlObject {
	
	var $groupId, $project;

	function ProjectXml($groupId, $extraXmlData="") {
		$this->groupId = $groupId;
		$this->project =& group_get_object($groupId);
		$this->extraXmlData = $extraXmlData;
	}

	function getXmlDataInternal() {
		$groupId = $this->groupId;
		$project = $this->project;

		$resGroup = db_query_params("SELECT * FROM groups WHERE group_id=$1 AND is_system=0", array($groupId));
		if (!$resGroup) {
			return false;
		}
		if (db_numrows($resGroup) == 0) {
			return false;
		}

		$fieldNames = db_fieldnames($resGroup);
		$xmlData = "<project>";

		$server_name = (!empty($_SERVER['HTTP_HOST'])) ? strtolower($_SERVER['HTTP_HOST']) : ((!empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : getenv('SERVER_NAME'));
		$xmlData .= "<host>";
		$xmlData .= $server_name;
		$xmlData .= "</host>";

		$xmlData .= db_row_to_xml($resGroup, 0, $fieldNames);

		$desc = util_whitelist_tags( db_result($resGroup, 0, "long_description"), "<video><object><param><embed><img>" );
		$desc = util_make_links($desc, false);
		$desc = util_add_linebreaks($desc, false);
		$desc = util_strip_insecure_tags( $desc );
		
		$xmlData .= "<long_description_with_links_and_breaks>";
		$xmlData .= escapeOnce( $desc );
		$xmlData .= "</long_description_with_links_and_breaks>";

		$desc = util_whitelist_tags( db_result($resGroup, 0, "download_description") );
		$desc = util_make_links($desc, true); 
		$desc = util_add_linebreaks($desc, true);
		$desc = util_strip_insecure_tags( $desc );
		
		$xmlData .= "<download_description_with_links_and_breaks>";
		$xmlData .= escapeOnce( util_add_linebreaks( util_strip_insecure_tags( util_whitelist_tags( unescape( $desc ) ) ), true ) );
		$xmlData .= "</download_description_with_links_and_breaks>";
		
		$xmlData .= "<short_description_with_breaks>";
		$xmlData .= escapeOnce( util_strip_insecure_tags( util_add_linebreaks( util_whitelist_tags( db_result($resGroup, 0, "short_description"), true) ) ) );
		$xmlData .= "</short_description_with_breaks>";
		
		$xmlData .= "<goal_with_breaks>";
		$xmlData .= escapeOnce( util_strip_insecure_tags( util_add_linebreaks( util_whitelist_tags( db_result($resGroup, 0, "goal"), true) ) ) );
		$xmlData .= "</goal_with_breaks>";
		
		$xmlData .= "<audience_with_breaks>";
		$xmlData .= escapeOnce( util_strip_insecure_tags( util_add_linebreaks( util_whitelist_tags( db_result($resGroup, 0, "audience"), true) ) ) );
		$xmlData .= "</audience_with_breaks>";
		
		$xmlData .= "<download_overview_with_breaks>";
		$xmlData .= escapeOnce( util_strip_insecure_tags( util_add_linebreaks( util_whitelist_tags( db_result($resGroup, 0, "download_overview"), "<object><embed><param><video>"), true ) ) );
		$xmlData .= "</download_overview_with_breaks>";
		
		$resRecommended = db_query_params("SELECT * FROM recommended_projects_norms WHERE group_id=$1", array($groupId));
		$numRows = db_numrows($resRecommended);
		$r = array();
		if ($numRows <= 5) {
			for ($i = 0; $i < $numRows; $i++) {
				$r[] = db_result($resRecommended, $i, 'dst_group');	
			}
		} else {
			$usedIndices = array();

			while (count($r) < 5) {
				for ($i = 0; $i < $numRows; $i++) {
					// Randomly pick projects according to index $i
					// Geometric distribution
					$p = 0.05;
					$prob = pow(1 - $p, $i) * $p;
					$randNum = rand() / getrandmax();
					if ($randNum < $prob && !in_array($i, $usedIndices)) {
						$r[] = db_result($resRecommended, $i, 'dst_group');	
						$usedIndices[] = $i; 
					}
				}
			}
		}
		
		$xmlData .= "<recommended_projects>";
		$xmlData .= $resRecommended;
		foreach ($r as $dst_group) {
			$resDstProj = db_query_params("SELECT group_id, group_name, logo_file, logo_type, unix_group_name FROM groups WHERE group_id=$1", array($dst_group));
			$xmlData .= "<proj>";
			$xmlData .= "<group_id>";
			$xmlData .= db_result($resDstProj, 0, 'group_id');
			$xmlData .= "</group_id>";
			$xmlData .= "<name>";
			$proj_name = escapeOnce(db_result($resDstProj, 0, 'group_name'));
			if (strlen($proj_name) > 60) {
				$proj_name = substr($proj_name, 0, 40) . "...";
			}
			$xmlData .= $proj_name;
			$xmlData .= "</name>";
			$xmlData .= "<logo_file>";
			$xmlData .= db_result($resDstProj, 0, 'logo_file');
			$xmlData .= "</logo_file>";
			$xmlData .= "<unix_group_name>";
			$xmlData .= db_result($resDstProj, 0, 'unix_group_name');
			$xmlData .= "</unix_group_name>";
			$xmlData .= "</proj>";
		}
		$xmlData .= "</recommended_projects>";

		// ################## Followers
		$privateFollowers = db_query_params(
			"SELECT user_name FROM project_follows
			WHERE group_id=$1
			AND follows=true
			AND public=false", array($groupId)
		);
		if ($privateFollowers) {
			$xmlData .= db_result_to_xml($privateFollowers, "private_follower");
			db_free_result($privateFollowers);
		}

		$resFollowers = db_query_params(
			"SELECT users.user_id,
				users.user_name,
				users.realname,
				users.interest_simtk,
				users.picture_file,
				users.personal_website,
				users.university_name,
				users.lab_name
			FROM users,project_follows
			WHERE project_follows.user_name=users.user_name
			AND project_follows.group_id=$1
			AND project_follows.follows=true
			AND project_follows.public=true
			AND users.status='A'
			ORDER BY UPPER(users.lastname)", array($groupId)
		);

		if ($resFollowers) {
			$xmlData .= db_result_to_xml($resFollowers, "follower");
			db_free_result($resFollowers);
		}

		// ################## Members

		$resMembers = db_query_params(
			"SELECT users.user_id,
				users.user_name,
				users.realname,
				trim(trailing from user_group.admin_flags) as admin_flags,
				users.interest_simtk,
				picture_file,
				project_lead,
				user_group_id,
				university_name,
				lab_name
			FROM users,user_group
			WHERE user_group.user_id=users.user_id
			AND user_group.group_id=$1
			AND users.status='A'
			ORDER BY user_group_id,UPPER(realname)", array($groupId)
		);

		if ($resMembers) {
			$xmlData .= db_result_to_xml($resMembers, "member");
			db_free_result($resMembers);
		}
		

//				  if ($project->usesPublications()) {
//						  $resPubs = db_query(
//								  "SELECT publications.publication,
//										  publications.publication_year,
//										  publications.url,
//										  publications.is_primary
//								  FROM publications
//								  WHERE group_id='$groupId'
//								  AND is_primary=1;"
//						  );
//
//						  if ($resPubs) {
//								  $publicationFieldNames = db_fieldnames($resPubs);
//								  $publicationNumRows = db_numrows($resPubs);
//								  $xmlData .= "<primary_publication_list>";
//								  for ($i = 0; $i < $publicationNumRows; $i++) {
//										  $xmlData .= "<publication>";
//										  $xmlData .= db_row_to_xml($resPubs, $i, $publicationFieldNames);
//										  $xmlData .= "</publication>";
//								  }
//								  $xmlData .= "</primary_publication_list>";
//								  db_free_result($resPubs);
//						  }
//				  }
//				if ($project->usesPublications()) {

					  $resPubs = db_query_params(
							  "SELECT * from publications
							  WHERE group_id=$1
							  ORDER BY is_primary DESC, publication_year DESC, UPPER(publication) DESC", array($groupId)
					  );

					  if ($resPubs) {
							  $xmlData .= db_result_to_xml($resPubs, "publication");
							  $publicationFieldNames = db_fieldnames($resPubs);
							  $publicationNumRows = db_numrows($resPubs);
							  $xmlData .= "<primary_publication_list>";
							  for ($i = 0; $i < $publicationNumRows; $i++) {
									  if (db_result($resPubs, $i, 'is_primary')) {
											  $xmlData .= "<publication>";
											  $xmlData .= db_row_to_xml($resPubs, $i,
																			  $publicationFieldNames);
											  $xmlData .= "</publication>";
									  }
							  }
							  $xmlData .= "</primary_publication_list>";
							  db_free_result($resPubs);
					  }

//				}
//
// RJR xxx
				if ($project->usesFRS()) {
					$sql = "SELECT frs_package.package_id,frs_package.name AS package_name, frs_package.is_public, group_link IS NULL AS linked, 
								  frs_release.name AS release_name,frs_release.release_id AS release_id,frs_release.release_date AS release_date
						  FROM frs_package,frs_release
						  WHERE frs_package.package_id=frs_release.package_id 
						  AND (frs_package.group_id=$1) 
						  AND frs_package.status_id=1 
						  AND frs_release.status_id=1 
						  ORDER BY linked, frs_package.name,frs_package.package_id,frs_release.release_date DESC";
					$resFiles = db_query_params( $sql, array($this->groupId) );
	
					if ($resFiles) {
						$frsFieldNames = db_fieldnames($resFiles);
		
						$frsNumRows = db_numrows($resFiles);
						$xmlData .= "<file_list>";
						for ($i = 0; $i < $frsNumRows; $i++) {
							if ($i > 0 && db_result($resFiles, $i , "package_id") == db_result($resFiles, $i-1, "package_id")) {
							  continue;
							}
		
							$xmlData .= "<file>";
		
							//exclude fields we will handle manually
							$xmlData .= db_row_to_xml($resFiles, $i, $frsFieldNames, array("release_date"));
		
							//format the release date
							$relDate = getdate(db_result($resFiles, $i, "release_date"));
							$xmlData .= "<release_date>";
							$xmlData .= $relDate["month"] . ' ' . $relDate["mday"] . ', ' . $relDate["year"];
							$xmlData .= "</release_date>";
							$xmlData .= "<is_public>" . db_result( $resFiles, $i, "is_public" ) . "</is_public>";
								  
							$xmlData .= "</file>";
						}
						$resPlatforms = db_query_params( 
							"SELECT DISTINCT fp.name AS platform FROM frs_processor fp
							JOIN frs_file ff ON (fp.processor_id = ff.processor_id)
							JOIN frs_release fr ON (ff.release_id = fr.release_id)
							JOIN frs_package f ON (fr.package_id = f.package_id)
							WHERE type_id != 2000 AND group_id = $1", array($this->groupId));
						$xmlData .= "<platform_list>";
						if ($resPlatforms)
						{
							$numPlatforms = db_numrows( $resPlatforms );
							for ($j=0; $j < $numPlatforms; $j++)
							{
								$xmlData .= "<platform>".db_result($resPlatforms, $j, 'platform')."</platform>";
							}
						}
						$xmlData .= "</platform_list>";
						// End modification
							  
						$xmlData .= "</file_list>";
						db_free_result($resFiles);
					}
				}
				if ($project->usesSCM()) {
					$sqlCmd="SELECT COALESCE(SUM(commits),0) AS commits FROM stats_cvs_group WHERE group_id=$1";
					$resSCM = db_query_params($sqlCmd, array($this->groupId));
					if ($resSCM) {
						$scmFieldNames = db_fieldnames($resSCM);
						$scmNumRows = db_numrows($resSCM);
						$xmlData .= "<scm_list>";
						if ($scmNumRows==0) {
							$xmlData .= "<scm>0</scm>";
						} else {
							$xmlData .= "<scm>";
							$xmlData .= db_row_to_xml($resSCM, 0, $scmFieldNames);
							$xmlData .= "</scm>";
						}
					$xmlData .= "</scm_list>";
					db_free_result($resSCM);
				}
			}

		$xmlData .= "<keyword_list>";
		foreach ($project->getKeywords() as $keyword)
		{
			$xmlData .= "<keyword>" . htmlspecialchars( $keyword, ENT_QUOTES ) . "</keyword>";
		}
		$xmlData .= "</keyword_list>";

		$xmlData .= "<ontology>";
		foreach ($project->getOntology() as $term)
		{
			$xmlData .= "<resource>" . htmlspecialchars( $term, ENT_QUOTES ) . "</resource>";
		}
		$xmlData .= "</ontology>";

		$xmlData .= $this->getProjectXmlDataInternal();

		$xmlData .= $this->extraXmlData;

		$related_projects = $project->getRelatedProjects();
		if ( $related_projects )
		{
			$xmlData .= "<related_project_list>";
			$pos = 0;
			$related_ids = $project->getRelatedProjectIds( false );
			foreach( $related_ids as $id )
			{
				$rp = $related_projects[ $id ];
				$xmlData .= "<project>";
				$xmlData .= "<id>" . $rp->getID() . "</id>";
				$xmlData .= "<public_name><![CDATA[" . $rp->getPublicName() . "]]></public_name>";
				$xmlData .= "<unix_name><![CDATA[" . $rp->getUnixName() . "]]></unix_name>";
				$xmlData .= "<position>$pos</position>";
				$xmlData .= $rp->isPublic() ? "<is_public/>" : "";
				$xmlData .= "</project>";
				$pos++;
			}
			$xmlData .= "</related_project_list>";
		}
		
		$member_projects = $project->getMemberProjects();
		if ( $member_projects )
		{
			$xmlData .= "<member_project_list>";
			$pos = 0;
			$member_ids = $project->getRelatedProjectIds( true );
			foreach( $member_ids as $id )
			{
				$mp = $member_projects[ $id ];
				$xmlData .= "<project>";
				$xmlData .= "<id>" . $mp->getID() . "</id>";
				$xmlData .= "<public_name><![CDATA[" . $mp->getPublicName() . "]]></public_name>";
				$xmlData .= "<unix_name><![CDATA[" . $mp->getUnixName() . "]]></unix_name>";
				$xmlData .= $mp->isPublic() ? "<is_public/>" : "";
				$xmlData .= "<position>$pos</position>";
				$xmlData .= "</project>";
				$pos++;
			}
			$xmlData .= "</member_project_list>";
		}
		
		$related_links = $project->getRelatedLinks();
		if ( $related_links )
		{
			$xmlData .= "<related_link_list>";
			foreach ( $related_links as $link )
			{
				$xmlData .= "<link>";
				$xmlData .= "<id>" . $link[ 'id' ] . "</id>";
				$xmlData .= "<title><![CDATA[" . $link[ 'title' ] . "]]></title>";
				$xmlData .= "<url><![CDATA[" . $link[ 'url' ] . "]]></url>";
				$xmlData .= "<position>" . $link[ 'position' ] . "</position>";
				$xmlData .= "</link>";
			}
			$xmlData .= "</related_link_list>";
		}
		
		$xmlData .= "<current_time>" . date( "Y-m-d H:i:s" ) . "</current_time>";

		$xmlData .= "</project>";
		
		// echo $xmlData;exit;
		return $xmlData;

	}

	function getProjectXmlDataInternal() {}

	/* getBiositemapXml - Get XML to describe one project in a Biositemaps RDF
	 *
	 * @param	resource  An XMLWriter object that will be used to construct the output
	 *
	 * @return	string	  XML describing the given project in Biositemap syntax
	 */
	function getBiositemapXml( $xmlw )
	{
		$groupId = $this->groupId;
		$project = $this->project;

		$resGroup = db_query_params("SELECT * FROM groups WHERE group_id=$1 AND is_system=0", array($groupId));
		if (!$resGroup) {
			return false;
		}
		if (db_numrows($resGroup) == 0) {
			return false;
		}

		$fieldNames = db_fieldnames($resGroup);
		
		$xmlw->startElement( "desc:Resource_Description" );
		$xmlw->writeAttribute( "rdf:ID", $project->getName() );
		$xmlw->endElement(); # End desc:Resource_Description
		$xmlw->startElement( "rdf:Organization" );
		$xmlw->writeAttribute( "rdf:datatype", "http://www.w3.org/2001/XMLSchema#string" );
		$xmlw->endElement(); # End rdf:Organization
	}
}

?>
