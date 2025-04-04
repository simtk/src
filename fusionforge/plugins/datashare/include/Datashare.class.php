<?php
/**
 *
 * datashare plugin class
 * 
 * The class which contains all methods for the datashare plugin.
 *
 * Copyright 2005-2025, SimTK Team
 *
 * This file is part of the SimTK web portal originating from        
 * Simbios, the NIH National Center for Physics-Based               
 * Simulation of Biological Structures at Stanford University,      
 * funded under the NIH Roadmap for Medical Research, grant          
 * U54 GM072970, with continued maintenance and enhancement
 * funded under NIH grants R01 GM107340 & R01 GM104139, and 
 * the U.S. Army Medical Research & Material Command award 
 * W81XWH-15-1-0232R01.
 * 
 * SimTK is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 * 
 * SimTK is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details. 
 * 
 * You should have received a copy of the GNU General Public 
 * License along with SimTK. If not, see  
 * <http://www.gnu.org/licenses/>.
 */ 

require_once $gfcommon.'include/FFError.class.php';

class Datashare extends FFError {

	const MAX_STUDIES = 3;

	/**
	 * Associative array of data from db.
	 *
	 * @var	 array   $data_array.
	 */
	var $data_array;

	/**
	 * The group object.
	 *
	 * @var	 object  $group.
	 */
	var $group;

	/**
	 *  Constructor.
	 *
	 *	@param	object	The group object to which this study is associated.
	 *	@return	boolean	success.
	 */
	function __construct(&$group, $study_id=false) {
		parent::__construct();

		if (!$group || !is_object($group)) {
			//$this->setNotValidGroupObjectError();
			return false;

		}

		if ($group->isError()) {
			$this->setError('Datashare: '.$group->getErrorMessage());
			return false;

		}

		$this->group =& $group;
	
		if ($study_id) {
			if (!$this->getStudy($study_id)) {
				return false;
			}
		}

		return true;
	}

	// Get doi of study.
	function getDOI($studyId) {
		$doi_identifier = false;
		$res = db_query_params('SELECT doi_identifier FROM plugin_datashare ' .
			'WHERE study_id=$1',
			array($studyId)
		);
		if (!$res || db_numrows($res) > 0) {
			while ($arr = db_fetch_array($res)) {
				$doi_identifier = $arr['doi_identifier'];
			}
		}
		return $doi_identifier;
	}

	// Get doi status of study.
	function isDOI($studyId) {
		$doi = false;
		$res = db_query_params('SELECT doi FROM plugin_datashare ' .
			'WHERE study_id=$1',
			array($studyId)
		);
		if (!$res || db_numrows($res) > 0) {
			while ($arr = db_fetch_array($res)) {
				$doi = $arr['doi'];
			}
		}
		return $doi;
	}

	// Cancel DOI request for specified study.
	function cancelDoi($studyId) {
		db_begin();

		$res = db_query_params('UPDATE plugin_datashare SET ' .
			'doi_requester=0, ' .
			'doi=0 ' .
			'WHERE study_id=$1 ' .
			'AND doi_identifier IS NULL ' .
			'AND doi=1',
			array(
				$studyId
			)
		);
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(_('Error requesting Doi'));
			db_rollback();
			return false;
		}

		db_commit();

		return true;
	}

	// DOI requested for specified study.
	function setDoi($studyId, $userId, $doi=1) {
		db_begin();

		$res = db_query_params('UPDATE plugin_datashare SET ' .
			'doi_requester=$1, ' .
			'doi=$2 ' .
			'WHERE study_id=$3',
			array(
				$userId,
				$doi,
				$studyId
			)
		);
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(_('Error requesting Doi'));
			db_rollback();
			return false;
		}
		db_commit();

		return true;
	}

	/**
	 *  getStudy() - get all rows for this study from the database.
	 *
	 *	@return	arrray  The data array of this study.
	 */
	function getStudy($study_id) {

		$study_id = (int) $study_id;
		$res = db_query_params("SELECT * FROM plugin_datashare " .
			"WHERE study_id = $1",
			array($study_id));

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

		$results = array();

		while ($row = db_fetch_array($res)) {
			$result = new DALQueryResultDS();

			foreach ($row as $k=>$v){
				$result->$k = $v;
			}

			$results[] = $result;
		}

		db_free_result($res);

		return $results;
	}

	/**
	 *  getCitations() - get citations of this study.
	 *
	 */
	function getCitations($study_id) {

		$study_id = (int) $study_id;
		$res = db_query_params("SELECT * FROM plugin_datashare_citation " .
			"WHERE study_id = $1",
			array($study_id));

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

		$results = array();

		while ($row = db_fetch_array($res)) {
			$result = new DALQueryResultDS();

			foreach ($row as $k=>$v){
				$result->$k = $v;
			}

			$results[] = $result;
		}

		db_free_result($res);

		return $results;
	}


	// Display citations, if any.
	function displayCitations($group_id, $study_id, $isAdmin=false) {

		if ($isAdmin) {
			// Check permissions.
			if (!session_loggedin() || !($user = &session_get_user())) {
				exit_not_logged_in();
			}
			if (!forge_check_perm("datashare", $group_id, 'write')) {
				exit_permission_denied("You cannot manage citations unless you are an admin on that project.");
			}

			echo '<a class="btn-blue" ' .
				'href="/plugins/datashare/admin/addCitation.php?' .
				'group_id=' . $group_id .
				'&study_id=' . $study_id .
				'">Add Citation</a><br/>';
		}

		$citations = $this->getCitations($study_id);
		if ($citations == false) {
			// No citations found.
			if ($isAdmin) {
				echo "<br/>This study has no citations.";
			}

			return;
		}

		$arrCite = array();
		$arrNonCite = array();
		$numCitations = count($citations);
		for ($cnt = 0; $cnt < $numCitations; $cnt++) {
			if ($citations[$cnt]->cite) {
				$arrCite[] = $citations[$cnt];
			}
			else {
				$arrNonCite[] = $citations[$cnt];
			}
		}

		$numCites = count($arrCite);
		if ($numCites > 0) {
			echo '<div style="width:95%">';

			if ($isAdmin) {
				echo '<div class="download_citation">';
				echo '<div class="download_subtitle">CATEGORY: "PLEASE CITE THESE PAPERS"</div>';
			}
			else {
				// Indent display.
				echo '<div style="margin-left:30px;" class="download_citation">';
				echo '<div class="download_subtitle">PLEASE CITE THESE PAPERS</div>';
			}
			echo '<div style="clear:both"></div>';

			for ($cnt = 0; $cnt < $numCites; $cnt++) {
				if (!$isAdmin) {
					// Indent display.
					echo '<p>';
				}
				echo htmlspecialchars($arrCite[$cnt]->authors) . " " . 
					htmlspecialchars($arrCite[$cnt]->title) . " " .
					htmlspecialchars($arrCite[$cnt]->publisher_information) . " ";
				if (trim($arrCite[$cnt]->doi) != "") {
					echo '<span class="download_extra">';
					echo '<a target="_blank" href="' . 
						'http://doi.org/' . 
						htmlspecialchars($arrCite[$cnt]->doi) . 
						'">doi: ' . 
						htmlspecialchars($arrCite[$cnt]->doi) .
						'</a> ';
					echo '</span>';
				}
				echo "(" . $arrCite[$cnt]->citation_year . ") ";
				if (trim($arrCite[$cnt]->url) != "") {
					echo '<span class="download_extra">';
					echo '<a target="_blank" href="' . 
						htmlspecialchars($arrCite[$cnt]->url) . 
						'">View</a>';
					echo '</span>';
				}
				if (!$isAdmin) {
					echo '</p>';
				}
				
				if ($isAdmin) {
					echo '<div style="display:inline;">';
					echo '&nbsp;';
					echo '<a class="btn-blue" ' .
						'href="/plugins/datashare/admin/updateCitation.php?' .
						'group_id=' . $group_id .
						'&study_id=' . $study_id .
						'&citation_id=' . $arrCite[$cnt]->citation_id .
						'">Update</a>';
					echo '&nbsp;';
					echo '<a class="btn-blue" ' .
						'href="/plugins/datashare/admin/deleteCitation.php?' .
						'group_id=' . $group_id .
						'&study_id=' . $study_id .
						'&citation_id=' . $arrCite[$cnt]->citation_id .
						'">Delete</a>';
					echo '</div>';
					echo '<br/>';
					echo '<br/>';
				}
			}

			echo "</div>";
			echo "</div>";
		}

		$numNonCite = count($arrNonCite);
		if ($numNonCite > 0) {
			echo '<div style="width:95%">';

			if ($isAdmin) {
				echo '<div class="download_citation">';
				echo '<div class="download_subtitle">CATEGORY: "ADDITIONAL PAPERS"</div>';
			}
			else {
				// Indent display.
				echo '<div style="margin-left:30px;" class="download_citation">';
				echo '<div class="download_subtitle">ADDITIONAL PAPERS</div>';
			}
			echo '<div style="clear:both"></div>';

			for ($cnt = 0; $cnt < $numNonCite; $cnt++) {
				if (!$isAdmin) {
					// Indent display.
					echo '<p>';
				}
				echo htmlspecialchars($arrNonCite[$cnt]->authors) . " " . 
					htmlspecialchars($arrNonCite[$cnt]->title) . " " .
					htmlspecialchars($arrNonCite[$cnt]->publisher_information) . " ";
				if (trim($arrNonCite[$cnt]->doi) != "") {
					echo '<span class="download_extra">';
					echo '<a target="_blank" href="' . 
						'http://doi.org/' . 
						htmlspecialchars($arrNonCite[$cnt]->doi) . 
						'">doi: ' . 
						htmlspecialchars($arrNonCite[$cnt]->doi) .
						'</a> ';
					echo '</span>';
				}
				echo "(" . $arrNonCite[$cnt]->citation_year . ") ";
				if (trim($arrNonCite[$cnt]->url) != "") {
					echo '<span class="download_extra">';
					echo '<a target="_blank" href="' . 
						htmlspecialchars($arrNonCite[$cnt]->url) . 
						'">View</a>';
					echo '</span>';
				}
				if (!$isAdmin) {
					echo '</p>';
				}

				if ($isAdmin) {
					echo '<div style="display:inline;">';
					echo '&nbsp;';
					echo '<a class="btn-blue" ' .
						'href="/plugins/datashare/admin/updateCitation.php?' .
						'group_id=' . $group_id .
						'&study_id=' . $study_id .
						'&citation_id=' . $arrNonCite[$cnt]->citation_id .
						'">Update</a>';
					echo '&nbsp;';
					echo '<a class="btn-blue" ' .
						'href="/plugins/datashare/admin/deleteCitation.php?' .
						'group_id=' . $group_id .
						'&study_id=' . $study_id .
						'&citation_id=' . $arrNonCite[$cnt]->citation_id .
						'">Delete</a>';
					echo '</div>';
					echo '<br/>';
					echo '<br/>';
				}
			}

			echo "</div>";
			echo "</div>";
		}
	}

	/**
	 *  getStudyByGroup() - get all rows associated with stuides of this group from the database.
	 *
	 *	@return	arrray  The data array of this study.
	 */
	function getStudyByGroup($group_id) {

		$group_id = (int) $group_id;
		$res = db_query_params("SELECT * FROM plugin_datashare " .
			"WHERE group_id = $1 " .
			"AND (active=0 OR active=1 OR active=-2 OR active=-3 OR active=-4) " .
			"ORDER BY title",
			array($group_id));

                $results = array();
		if (!$res || db_numrows($res) < 1) {
			return $results;
		}

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultDS();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}


	// Get disk usage information by group.
	function getDiskUsageByGroup($group_id, &$totalBytesInGroup, &$lastModifiedTimeInGroup) {

		$totalBytesInGroup = 0;
		$lastModifiedTimeInGroup = false;

		$res = db_query_params("SELECT total_bytes, last_modified " .
			"FROM plugin_datashare_diskusage " .
			"WHERE group_id=$1",
			array($group_id)
		);
		if (!$res || db_numrows($res) > 0) {
			while ($arr = db_fetch_array($res)) {
				$totalBytes = $arr['total_bytes'];
				$lastModifiedTime = $arr['last_modified'];

				$totalBytesInGroup += $totalBytes;
				if (!$lastModifiedTimeInGroup || 
					$lastModifiedTime > $lastModifiedTimeInGroup) {
					$lastModifiedTimeInGroup = $lastModifiedTime;
				}
			}
		}

		db_free_result($res);
	}

	// Save disk usage information by group as retrieved from Data Share server
	function saveDiskUsageByGroup($group_id) {

		// Specify with dirname(__FILE__).
		// Otherwise, the directory look up may fail if invoked from other locations.
		include dirname(__FILE__) . "/../www/admin/server.php";

		if (file_exists("/etc/gforge/config.ini.d/datashare.ini")) {
			// The file datashare.ini is present.
			$arrDatashareConfig = parse_ini_file("/etc/gforge/config.ini.d/datashare.ini");

			// Check for each parameter's presence.
			if (isset($arrDatashareConfig["datashare_server"])) {
				$datashareServer = $arrDatashareConfig["datashare_server"];
			}
		}
		if (!isset($datashareServer)) {
			// Cannot get Data Share server.
			return;
		}

		$totalBytesInGroup = 0;
		$lastModifiedTimeInGroup = false;

		$study_result = $this->getStudyByGroup($group_id);
		if ($study_result) {
			$context = array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);
			// Get each study id associated with the group.
			foreach ($study_result as $result) {
				$theStudyId = $result->study_id;

				// Retrieve disk usage information from Data Share server.
				$url = "https://$datashareServer/reports/getDataShareDiskUsage.php?" .
					"apikey=$api_key&" .
					"studyid=" . $theStudyId;
				$response_json = file_get_contents($url, 
					false, 
					stream_context_create($context));
				$response = json_decode($response_json);

				$totalBytesInGroup = $response->total_bytes;
				if (!$lastModifiedTimeInGroup || 
					($response->last_modified && 
					$response->last_modified > $lastModifiedTimeInGroup)) {
					$lastModifiedTimeInGroup = $response->last_modified;
				}

				// Save disk usage info to db.
				if ($totalBytesInGroup && $lastModifiedTimeInGroup) {
					// Try update first.
					$query = "UPDATE plugin_datashare_diskusage SET " .
						"total_bytes=$1, " .
						"last_modified=$2, " .
						"group_id=$3, " .
						"user_id=$4 " .
						"WHERE study_id=$5";
					$res = db_query_params($query,
						array(
							$totalBytesInGroup,
							$lastModifiedTimeInGroup,
							$group_id,
							0,
							$theStudyId
						)
					);
					if (!$res || db_affected_rows($res) < 1) {
						// Insert row.
						$query = "INSERT INTO plugin_datashare_diskusage " .
							"(study_id, group_id, total_bytes, last_modified, user_id) " .
							"VALUES ($1,$2,$3,$4,$5)";
						$res = db_query_params($query,
							array(
								$theStudyId,
								$group_id,
								$totalBytesInGroup,
								$lastModifiedTimeInGroup,
								0
							)
						);
						if (!$res) {
							// Cannot insert row.
						}
					}
				}
			}
		}
	}
	
	/**
	 *	@return	boolean	success
	*/
	function insertStudy($group_id, $title, $description, $is_private, $template, 
		$subject_prefix="subject", $useAgreement=0, $customAgreement="") {

		$group_id = (int) $group_id;

		if (!session_loggedin() || !($user = &session_get_user())) {
			exit_not_logged_in();
		}
		if (!forge_check_perm("datashare", $group_id, 'write')) {
			exit_permission_denied("You cannot add a new study for a project unless you are an admin on that project.");
		}

		$userId = $user->getID();
		$realName = $user->getRealName();
		$userName = $user->getUnixName();
		$groupName = group_getname($group_id);

		// Check parameter validity.
		if (!$title || trim($title) == "") {
			$this->setError(_('title^'.'You must enter a title'));
			return false;
		}
		if (strlen($title) > 80) {
			$this->setError(_('title^'.'Title is too long'));
			return false;
		}
		if (preg_match('/^[a-z0-9 _-]+$/i', $title) == false ) {
			$this->setError(_('title^'.'Invalid title name'));
			return false;
		}
		if (!$description || trim($description) == "") {
			$this->setError(_('description^'.'You must enter a description'));
			return false;
		}
		if (!$subject_prefix || trim($subject_prefix) == "") {
			$this->setError('subject^' . 'You must enter a top level folder prefix');
			return false;
		}
		$subject_prefix = trim($subject_prefix);
		if (strlen($subject_prefix) > 80) {
			$this->setError(_('title^'.'Title is too long'));
			return false;
		}
		if (ctype_alpha($subject_prefix) === false) {
			// Alphabetic values only.
			$this->setError('subject^' . 
				'You can only use alphabetic characters for top level folder prefix');
			return false;
		}
		if ($useAgreement == 0) {
			// Use agreement is "None". Do not fill in custom agreement.
			$customAgreement = '';
		}

		$result = $this->getStudyByGroup($group_id);
		$rowcount = count($result);
		if ($rowcount < self::MAX_STUDIES) {

			db_begin();

			$token = rand(10000,999999);
			// insert new row
			$res=db_query_params('INSERT INTO plugin_datashare ' .
				'(group_id, title, description, is_private, ' .
				'token, active, template_id, user_id, date_created, ' .
				'subject_prefix, simtk_use_agreement, simtk_custom_agreement) ' .
				'VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)',
				array($group_id, 
					htmlspecialchars($title), 
					htmlspecialchars($description), 
					$is_private, 
					$token, 
					0, 
					$template,
					$userId,
					time(),
					$subject_prefix,
					$useAgreement,
					htmlspecialchars($customAgreement)
				)
			);
			if (!$res || db_affected_rows($res) < 1) {
				$this->setError(_('Error creating new study'));
				db_rollback();
				return false;
			}

			db_commit();

			$message = "New study requested for approval.\n\n" . 
				"Study Title: " . $title . "\n" . 
				"Description: " . $description . "\n" . 
				"Project Name: " . $groupName . "\n" .
				"Submitter: " . $realName . " ($userName)\n\n" .
				"Please visit the following URL to approve or reject this study:\n" .
				util_make_url("/admin/datashareStudies.php");
			$admins = RBACEngine::getInstance()->getUsersByAllowedAction ('approve_projects', -1);

			if (count($admins) < 1) {
				$this->setError(_("There is no administrator to send the mail to."));
				return false;
			}

			foreach ($admins as $admin) {
				$admin_email = $admin->getEmail();
				setup_gettext_for_user ($admin);
				util_send_message($admin_email, 'DATA SHARE APPROVAL', $message);
				setup_gettext_from_context();
			}

			return true;
		}
		else {
			// maximum studies allowed reached
			$this->setError(_('Maximum studies reached.'));
			return false;
		}
	}
	
	function updateStudy($study_id, $title, $description, $is_private, 
		$subject_prefix="subject", $useAgreement=0, $customAgreement="") {

		// Check parameter validity.
		if (!$title || trim($title) == "") {
			$this->setError(_('title^'.'You must enter a title'));
			return false;
		}
		if (strlen($title) > 80) {
			$this->setError(_('title^'.'Title is too long'));
			return false;
		}
		if (preg_match('/^[a-z0-9 _-]+$/i', $title) == false) {
			$this->setError(_('title^'.'Invalid title name'));
			return false;
		}
		if (!$description || trim($description) == "") {
			$this->setError(_('description^'.'You must enter a description'));
			return false;
		}
		if (!$subject_prefix || trim($subject_prefix) == "") {
			$this->setError('subject^' . 'You must enter a top level folder prefix');
			return false;
		}
		$subject_prefix = trim($subject_prefix);
		if (strlen($subject_prefix) > 80) {
			$this->setError(_('title^'.'Title is too long'));
			return false;
		}
		if (ctype_alpha($subject_prefix) === false) {
			// Alphabetic values only.
			$this->setError('subject^' . 
				'You can only use alphabetic characters for top level folder prefix');
			return false;
		}

		// Get current study information.
		$isSubjPrefixUpdate = false;
		$study_results = $this->getStudy($study_id);
		if ($study_results != FALSE &&
			count($study_results) > 0) {
			$curSubjPrefix = $study_results[0]->subject_prefix;
			if ($curSubjPrefix != $subject_prefix) {
				$isSubjPrefixUpdate = true;
			}
		}

		if ($useAgreement == 0) {
			// Use agreement is "None". Do not fill in custom agreement.
			$customAgreement = '';
		}

		db_begin();
		if ($isSubjPrefixUpdate === false) {
			$res=db_query_params('UPDATE plugin_datashare SET ' .
				'title=$1, description=$2, is_private=$3, ' .
				'simtk_use_agreement=$4, simtk_custom_agreement=$5 ' .
				'WHERE study_id=$6',
				array(
					htmlspecialchars($title),
					htmlspecialchars($description),
					$is_private,
					$useAgreement,
					htmlspecialchars($customAgreement),
					$study_id
				)
			);
		}
		else {
			// Subject prefix has update.
			$res=db_query_params('UPDATE plugin_datashare SET ' .
				'title=$1, description=$2, is_private=$3, subject_prefix=$4, active=$5, ' .
				'simtk_use_agreement=$6, simtk_custom_agreement=$7 ' .
				'WHERE study_id=$8',
				array(
					htmlspecialchars($title),
					htmlspecialchars($description),
					$is_private,
					$subject_prefix,
					-4,
					$useAgreement,
					htmlspecialchars($customAgreement),
					$study_id
				)
			);
		}
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(_('Error updating study'));
			db_rollback();
			return false;
		}
		db_commit();

		return true;
	}
	
	/**
	 *  getTemplate() - get all rows for templates from the database.
	 *
	 *	@return	arrray  The array of Templates 
	 */
	function getTemplate() {

		$res = db_query_params("SELECT * FROM plugin_datashare_template",array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

                $results = array();

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultDS();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}
	
	/* call API to set up files on remote datasharing server */
	function initializeStudy($study_id) {
		// call API to create database table for new study.	
	}

	function &getGroup() {
		return $this->group;
	}

	function getID() {
		return $this->data_array['pub_id'];
	}	
}

class DALQueryResultDS {

	private $_results = array();

	public function __construct(){}

	public function __set($var,$val){
		$this->_results[$var] = $val;
	}

	public function __get($var){
		if (isset($this->_results[$var])){
			return $this->_results[$var];
		}
		else {
			return null;
		}
	}
}



