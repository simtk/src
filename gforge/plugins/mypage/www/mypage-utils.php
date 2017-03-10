<?php
/**
 *
 * mypage plugin mypage-utils.php
 * 
 * Utility page which contains functions for retrieving user projects, followed projects.
 *
 * Copyright 2005-2016, SimTK Team
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

function mypage_Project_Header($params,$id) {
	global $DOCUMENT_ROOT,$HTML;
	$params['toptab']='My Page';
	$params['group']=$id;
        $group_id = $id;

	if ($group_id) {
		$menu_texts=array();
		$menu_links=array();

		$menu_texts[]=_('View My Page');
		$menu_links[]='/plugins/mypage/?type=group&pluginname=mypage&id='.$group_id;
		if (session_loggedin()) {
			$project = group_get_object($params['group']);
			if ($project && is_object($project) && !$project->isError()) {
                                /*
				if (forge_check_perm ('project_admin', $group_id)) {
					$menu_texts[]=_('Administration');
					$menu_links[]='/plugins/mypage/admin/?group_id='.$group_id;
				}
                                */
			}
		}
		$params['submenu'] = $HTML->subMenu($menu_texts,$menu_links);
	}
	/*
		Show horizontal links
	*/
	site_project_header($params);
}

function getProjects() {
    $result = db_query_params("SELECT group_id,group_name FROM groups WHERE status = 'A' order by group_name",array());
	$cntProjects = db_numrows($result);
	//echo "cnt: " . $cntProjects;
	/*
	// only return projects which are public
	for ($i = 0; $i < db_numrows($result); $i++) {
		
	}
	*/
	
	return $result;
}


function getUserProjects($user,&$cntUserProjects,$include_following=0) {

   $arrProjects = array();
   $cntUserProjects = 0;
   
   // Get user projects info.
   $projects = $user->getGroups();
   if (!$projects) {
     return NULL;
   }
   
   sortProjectList($projects);
   $roles = RBACEngine::getInstance()->getAvailableRolesForUser($user);
   sortRoleList($roles);
  
   // Count number of projects that user is a member of. 
   
   foreach ($projects as $p) {
      if (!forge_check_perm('project_read', $p->getID())) {
		continue;
	  }
	  $cntUserProjects++;
   }

   // see if there were any groups
   if ($cntUserProjects > 0) {

	  

	// Build array of projects that user belongs to.
	$cnt = 0;
	foreach ($projects as $p) {
		if (!forge_check_perm('project_read', $p->getID())) {
		    $cntUserProjects--;
			continue;
		}



		$role_names = array() ;
		foreach ($roles as $r) {
			if ($r instanceof RoleExplicit && 
				$r->getHomeProject() != NULL && 
				$r->getHomeProject()->getID() == $p->getID()) {

				$role_names[] = $r->getName();
			}
		}



		if (trim($p->getStatus()) != "A") {
			// Not an active project. Skip.
			$cntUserProjects--;
			continue;
		}
		
		// if following project then skip
		if (!$include_following && isFollowingProject($user,$p->getID())) {
		    $cntUserProjects--;
			continue;
		}
		
		
		$arrProjects[$cnt]['group_name'] = $p->getPublicName();
		$arrProjects[$cnt]['simtk_logo_file'] = $p->getLogoFile();
		$arrProjects[$cnt]['unix_group_name'] = $p->getUnixName();
		$arrProjects[$cnt]['role_names'] = htmlspecialchars(implode(', ', $role_names));
		$arrProjects[$cnt]['group_id'] = $p->getID();

		$cnt++;

	} // for
  } // if
  return $arrProjects;
  
}


function getProjectsFollowing($user,&$cntProjectsFollowing) {

    $result = db_query_params("SELECT groups.group_id, group_name, simtk_logo_file, unix_group_name FROM project_follows, groups WHERE groups.group_id = project_follows.group_id and project_follows.follows = true and user_name='". $user->getUnixName() . "'",array());
    //echo "name: " . $user->getUnixName() . "<br>";
	$cntProjectsFollowing = db_numrows($result);
	// Build array of projects that user follows.
	$arrProjectsFollowing = array();
	for ($i = 0; $i < db_numrows($result); $i++) {
		$arrProjectsFollowing[$i]['group_name'] = db_result($result, $i, 'group_name');
		$arrProjectsFollowing[$i]['simtk_logo_file'] = db_result($result, $i, 'simtk_logo_file');
		$arrProjectsFollowing[$i]['group_id'] = db_result($result, $i, 'group_id');
		$arrProjectsFollowing[$i]['unix_group_name'] = db_result($result, $i, 'unix_group_name');
	}
	
	return $arrProjectsFollowing;

}

function removeFollowing($group_id,$user_name) {

        $sqlCmd="DELETE from project_follows WHERE group_id=".$group_id . " AND user_name = '" . $user_name . "'";
        //echo "sql: " . $sqlCmd;
		db_begin();

        $res=db_query_params($sqlCmd,array());

        if (!$res || db_affected_rows($res) < 1) {
		   db_rollback();
           return false;
        }

		db_commit();

		return true;
	}

function addFollowing($user_name,$public,$group_id) {

        //$sqlCmd="UPDATE project_follows SET follows = true, public = " . $public . " WHERE group_id=".$group_id . " AND user_name = '" . $user_name . "'";
        //echo "sql: " . $sqlCmd . "<br />";
		if (isFollowing($user_name,$group_id) < 1) {
		   db_begin();   
           // insert new row
           $sql = "INSERT INTO project_follows (group_id, user_name, follows, public) VALUES ($group_id, '$user_name', true, $public)";
		   //echo "sql: " . $sql;
           $res=db_query_params($sql,array());
		   if (!$res || db_affected_rows($res) < 1) {
				db_rollback();
				return false;
		   }
		   db_commit();
		   return true;
		}
		return false;

		
}

function isFollowing($user_name,$group_id) {
    $result = db_query_params("SELECT groups.group_id FROM project_follows, groups WHERE groups.group_id = " . $group_id . " and groups.group_id = project_follows.group_id and project_follows.follows = true and user_name='". $user_name . "'",array());
	$cntFollowingProject = db_numrows($result);
    return $cntFollowingProject;
}
	
function isFollowingProject($user,$group_id) {
    $result = db_query_params("SELECT groups.group_id FROM project_follows, groups WHERE groups.group_id = " . $group_id . " and groups.group_id = project_follows.group_id and project_follows.follows = true and user_name='". $user->getUnixName() . "'",array());
	$cntFollowingProject = db_numrows($result);
    return $cntFollowingProject;
}

function getFollowingByCat(&$cntFollowingByCat, $trove_cat_id, $limit = 15) {
    //$result = db_query_params("SELECT count(*) AS count, groups.group_id, group_name, simtk_logo_file, unix_group_name FROM groups, project_follows, trove_group_link WHERE groups.group_id = trove_group_link.group_id and groups.group_id = project_follows.group_id and trove_cat_id = $trove_cat_id and follows = true GROUP BY groups.group_id order by count desc limit " . $limit,array());
	$result = db_query_params("SELECT count(*) AS count, groups.group_id, group_name, simtk_logo_file, unix_group_name FROM groups, project_follows WHERE groups.group_id = project_follows.group_id and follows = true GROUP BY groups.group_id order by count desc limit " . $limit,array());
	
	$cntFollowingByCat = db_numrows($result);
	// Build array of projects that user follows.
	$arrFollowingByCat = array();
	for ($i = 0; $i < db_numrows($result); $i++) {
	    $id = db_result($result, $i, 'group_id');
		$arrFollowingByCat[$id]['group_name'] = db_result($result, $i, 'group_name');
		$arrFollowingByCat[$id]['simtk_logo_file'] = db_result($result, $i, 'simtk_logo_file');
		$arrFollowingByCat[$id]['group_id'] = db_result($result, $i, 'group_id');
		$arrFollowingByCat[$id]['unix_group_name'] = db_result($result, $i, 'unix_group_name');
		$arrFollowingByCat[$id]['followers'] = db_result($result, $i, 'count');
		$arrFollowingByCat[$id]['attribute_num'] = db_result($result, $i, 'count');
		$arrFollowingByCat[$id]['attribute_type'] = "following";
	}
	
	return $arrFollowingByCat;

}

function getTotalUsersProject(&$cntUsersProject, $trove_cat_id, $limit = 15) {
		$result = db_query_params ('SELECT count(*) AS count, groups.group_id, group_name, simtk_logo_file, unix_group_name FROM groups,user_group WHERE groups.group_id = user_group.group_id group by groups.group_id order by count desc limit ' . $limit,array ());
		$cntUsersProject = db_numrows($result);
		// Build array of projects that user follows.
	    $arrTotalUsers = array();
	    for ($i = 0; $i < db_numrows($result); $i++) {
		   $id = db_result($result, $i, 'group_id');
		   $arrTotalUsers[$id]['group_name'] = db_result($result, $i, 'group_name');
		   $arrTotalUsers[$id]['simtk_logo_file'] = db_result($result, $i, 'simtk_logo_file');
		   $arrTotalUsers[$id]['group_id'] = db_result($result, $i, 'group_id');
		   $arrTotalUsers[$id]['unix_group_name'] = db_result($result, $i, 'unix_group_name');
		   $arrTotalUsers[$id]['members'] = db_result($result, $i, 'count');
		   $arrTotalUsers[$id]['attribute_num'] = db_result($result, $i, 'count');
		   $arrTotalUsers[$id]['attribute_type'] = "members";
	}
	
	return $arrTotalUsers;
	
}

function getTroveCat($group_id) {
    $result = db_query_params ("SELECT trove_cat_id FROM trove_group_link where group_id = $group_id",array ());
    $cntTroveCat = db_numrows($result);
	return (db_result($result, $i, 'trove_cat_id'));
}

function getTroveCategories($group_id) {
    $result = db_query_params ("SELECT trove_cat_id FROM trove_group_link where group_id = $group_id",array ());
    $cntTroveCat = db_numrows($result);
	// Build array of projects that user follows.
	$arrTroveCat = array();
	for ($i = 0; $i < db_numrows($result); $i++) {
		$arrTroveCat[$i] = db_result($result, $i, 'trove_cat_id');
	}
	
	return $arrTroveCat;
	
}

function getProjectsByCat(&$cntProjectsByCat, $trove_cat_id, $date = 0) {
    if (!$date) {
       $result = db_query_params ("SELECT group_id FROM trove_group_link where trove_cat_id = $trove_cat_id",array ());
	} else {
	   $result = db_query_params ("SELECT groups.group_id,register_time,unix_group_name,simtk_logo_file,simtk_summary,group_name FROM groups,trove_group_link where groups.group_id = trove_group_link.group_id and trove_cat_id = $trove_cat_id and register_time > $date",array ());
	}
    $cntProjectsByCat = db_numrows($result);
	return $result;
}


function getTopDownloads(&$cntTopDownloads,$limit=15) {
        $result = db_query_params ("
			SELECT groups.group_id,
			groups.group_name,
			groups.unix_group_name,
			groups.simtk_logo_file,
			groups.simtk_summary,
			frs_dlstats_grouptotal_vw.downloads
			FROM frs_dlstats_grouptotal_vw, groups
			WHERE
			frs_dlstats_grouptotal_vw.group_id=groups.group_id AND groups.status=$1
			ORDER BY downloads DESC limit $limit
			", array ('A'));
		
		$cntTopDownloads = db_numrows($result);
		// Build array of projects that user follows.
	    $arrTopDownloads = array();
	    for ($i = 0; $i < db_numrows($result); $i++) {
		   $id = db_result($result, $i, 'group_id');
		   $arrTopDownloads[$id]['group_name'] = db_result($result, $i, 'group_name');
		   $arrTopDownloads[$id]['simtk_logo_file'] = db_result($result, $i, 'simtk_logo_file');
		   $arrTopDownloads[$id]['group_id'] = db_result($result, $i, 'group_id');
		   $arrTopDownloads[$id]['unix_group_name'] = db_result($result, $i, 'unix_group_name');
		   $arrTopDownloads[$id]['downloads'] = db_result($result, $i, 'downloads');
		   $arrTopDownloads[$id]['attribute_num'] = db_result($result, $i, 'downloads');
		   $arrTopDownloads[$id]['attribute_type'] = "downloads";
	}
	
	return $arrTopDownloads;
}
	
function getTopDownloadsPastWeek(&$cntTopDownloads,$limit=15) {

        $time = time();

		$last_week = $time - 86400 * 7;
		$this_week = $time;

		$last_year = date('Y', $last_week);
		$last_month = date('m', $last_week);
		$last_day = date('d',$last_week);

		$this_year = date('Y', $this_week);
		$this_month = date('m', $this_week);
		$this_day = date('d', $this_week);
		
		//echo "year: " . $last_year . "<br>";
		//echo "month: " . $last_month . "<br>";
		//echo "day: " . $last_day . "<br>";
		
        $result = db_query_params ("
			SELECT groups.group_id,
			groups.group_name,
			groups.unix_group_name,
			groups.simtk_logo_file,
			groups.simtk_summary,
			frs_dlstats_group_vw.downloads
			FROM frs_dlstats_group_vw, groups WHERE
			((month=$last_year$last_month AND day>$last_day) OR (month>$last_year$last_month)) AND		
			frs_dlstats_group_vw.group_id=groups.group_id AND groups.status=$1
			ORDER BY downloads DESC limit $limit
			", array ('A'));
		
		$cntTopDownloads = db_numrows($result);
		// Build array of projects that user follows.
	    $arrTopDownloads = array();
	    for ($i = 0; $i < db_numrows($result); $i++) {
		   $id = db_result($result, $i, 'group_id');
		   $arrTopDownloads[$id]['group_name'] = db_result($result, $i, 'group_name');
		   $arrTopDownloads[$id]['simtk_logo_file'] = db_result($result, $i, 'simtk_logo_file');
		   $arrTopDownloads[$id]['group_id'] = db_result($result, $i, 'group_id');
		   $arrTopDownloads[$id]['unix_group_name'] = db_result($result, $i, 'unix_group_name');
		   $arrTopDownloads[$id]['downloads_past_week'] = db_result($result, $i, 'downloads');
		   $arrTopDownloads[$id]['attribute_num'] = db_result($result, $i, 'downloads');
		   $arrTopDownloads[$id]['attribute_type'] = "downloads past week";
	}
	
	return $arrTopDownloads;
}
	
	
function getUserNews($user,&$cntUserNews) {

    //echo "id: " . $user->getID() . "<br>";
	
    $result = db_query_params('SELECT * FROM plugin_simtk_news WHERE submitted_by=$1',
						   array($user->getID()));
    $cntUserNews = db_numrows($result);
	// Build array of projects that user belongs to.
	$cnt = 0;
	$arrNews = array();
	for ($i = 0; $i < db_numrows($result); $i++) {
		$arrNews[$i]['summary'] = db_result($result, $i, 'summary');
		$arrNews[$i]['details'] = db_result($result, $i, 'details');
		$arrNews[$i]['id'] = db_result($result, $i, 'id');
		$arrNews[$i]['post_date'] = db_result($result, $i, 'post_date');
		echo "date: " . $arrNews[$i]['post_date'] . "<br>";
	}
	
	return $arrNews;
}

function finalTrending(&$finalTrending,$prelimTrending,$attribute) {
   
   foreach ($prelimTrending as $trending) {
      $id = $trending['group_id'];
      $finalTrending[$id]['group_name'] = $trending['group_name'];
	  $finalTrending[$id]['simtk_logo_file'] = $trending['simtk_logo_file'];
	  $finalTrending[$id]['group_id'] = $trending['group_id'];
	  $finalTrending[$id]['unix_group_name'] = $trending['unix_group_name'];
	  $finalTrending[$id][$attribute] = $trending[$attribute];
	  $finalTrending[$id]['attribute_num'] = $trending['attribute_num'];
	  $finalTrending[$id]['attribute_type'] = $trending['attribute_type'];
   
   }
   return true;
}



/*
function news_footer($params) {
	GLOBAL $HTML;
	$HTML->footer($params);
}
*/   


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
