<?php

/**
 *
 * popupmenuSetup.php
 *
 * Contains functions to build project menu with links and titles.
 *
 * Copyright 2005-2019, SimTK Team
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

// Retrieve popup menu items given the menu title.
function sectionPopupMenuItems($sectionName, $groupId, &$menuTitles, &$menuUrls, &$menuDispNames) {

	// Get group object using group id.
	$groupObj = group_get_object($groupId);

	if ($sectionName == "Downloads") {
		/*
		if (session_loggedin() &&
			$groupObj && is_object($groupObj) && !$groupObj->isError() &&
			forge_check_perm ('frs', $groupId, 'write')) {
			// Check permission before adding administrative menu items.
			$menuTitles[] = 'View downloads';
			$menuUrls[] = '/frs?group_id=' . $groupId;
			$menuTitles[] = _('Administration');
			$menuUrls[] = '/frs/admin/?group_id=' . $groupId;
		}
		*/
		if ($groupObj->usesPlugin("datashare")) {
			$menuTitles[] = 'Downloads';
			$menuUrls[] = '/frs?group_id=' . $groupId;
			$menuTitles[] = 'Data Share';
			$menuUrls[] = '/plugins/datashare/index.php?type=group&id=' . $groupId;
		}
		else {
			if (session_loggedin() &&
				$groupObj && is_object($groupObj) && !$groupObj->isError() &&
				forge_check_perm ('frs', $groupId, 'write')) {
				// Check permission before adding administrative menu items.
				$menuTitles[] = 'View downloads';
				$menuUrls[] = '/frs?group_id=' . $groupId;
				$menuTitles[] = _('Administration');
				$menuUrls[] = '/frs/admin/?group_id=' . $groupId;
			}
		}
	} else if ($sectionName == "Data Share") {
		if ($groupObj->usesPlugin("datashare")) {
			   $menuTitles[] = 'Data Share';
			   $menuUrls[] = '/plugins/datashare/index.php?type=group&id=' . $groupId;
		}
	}
	else if ($sectionName == "Admin") {
		if (session_loggedin() &&
			$groupObj && is_object($groupObj) && !$groupObj->isError() &&
			forge_check_perm ('project_admin', $groupId, 'write')) {
			// Check permission before adding administrative menu items.
			$menuTitles[] = 'Project info';
			$menuUrls[] = '/project/admin/?group_id=' . $groupId;
			$menuTitles[] = 'Team members';
			$menuUrls[] = '/project/admin/users.php?group_id=' . $groupId;
			$menuTitles[] = 'Tools';
			$menuUrls[] = '/project/admin/tools.php?group_id=' . $groupId;
			$menuTitles[] = 'Categories';
			$menuUrls[] = '/project/admin/category.php?group_id=' . $groupId;
			$menuTitles[] = 'Communities';
			$menuUrls[] = '/project/admin/projCommunities.php?group_id=' . $groupId;
			$menuTitles[] = 'Main page layout';
			$menuUrls[] = '/project/admin/layout.php?group_id=' . $groupId;
		}
	}
	else if ($sectionName == "News") {
		if (session_loggedin() &&
			$groupObj && is_object($groupObj) && !$groupObj->isError() &&
			forge_check_perm ('project_admin', $groupId)) {
			// Check permission before adding administrative menu items.
			$menuTitles[]='View news';
			$menuUrls[]='/plugins/simtk_news/index.php?group_id=' . $groupId;
			$menuTitles[]='Add news';
			$menuUrls[]='/plugins/simtk_news/submit.php?group_id=' . $groupId;
			$menuTitles[]=_('Administration');
			$menuUrls[]='/plugins/simtk_news/admin/?group_id=' . $groupId;
		}
	}
	else if ($sectionName == "Issues") {
		$arrTrackersInfo = getTrackersInfo($groupId);
		$menuTitles[] = 'View trackers';
		$menuUrls[]  = '/tracker?group_id=' . $groupId;
		if (session_loggedin()) {
			foreach ($arrTrackersInfo as $name=>$arrInfo) {
				$atid = $arrInfo['atid'];
				if (forge_check_perm('tracker', $atid, 'read')) {
					$menuTitles[] = $name;
					$menuUrls[]  = '/tracker/?atid=' . $atid . '&group_id=' . $groupId . '&func=browse';
					$menuDispNames[$name] = abbrName($name);
				}
			}
			$perm = $groupObj->getPermission();
			if ($perm && is_object($perm) && !$perm->isError() && $perm->isPMAdmin()) {
				$menuTitles[] = 'Create new tracker';
				$menuUrls[]  = '/tracker/admin/?group_id=' . $groupId;
				$menuTitles[] = 'Administration';
				$menuUrls[]  = '/tracker/admin/?show_tracker=1&group_id=' . $groupId;
			}
		}
		else {
			foreach ($arrTrackersInfo as $name=>$arrInfo) {
				if ($arrInfo['is_public'] != 1) {
					// Not public; skip.
					continue;
				}
				$atid = $arrInfo['atid'];
				if (forge_check_perm('tracker', $atid, 'read')) {
					$menuTitles[] = $name;
					$menuUrls[]  = '/tracker/?atid=' . $atid . '&group_id=' . $groupId . '&func=browse';
					$menuDispNames[$name] = abbrName($name);
				}
			}
		}
	}
	else if ($sectionName == "Source Code") {
		if (session_loggedin() && forge_check_perm('project_admin', $groupId)) {

			if (is_object($groupObj) && !$groupObj->isError()) {
				if ($groupObj->usesGitHub()) {
					// GitHub.
					$url = $groupObj->getGitHubAccessURL();
					if (!empty($url)) {
						$menuTitles[] = 'Summary';
						$menuUrls[] = '/githubAccess?group_id=' . $groupId;
						$menuTitles[] = _('Browse source code');
						$menuUrls[] = "/githubAccess/loadGitHubAccessURL.php?group_id=" . $groupId;
					}
					$menuTitles[] = _('Administration');
					$menuUrls[] = '/githubAccess/admin?group_id=' . $groupId;
				}
				else {
					// Subversion.
					$menuTitles[] = 'Summary';
					$menuUrls[] = '/scm?group_id=' . $groupId;
					$menuTitles[] = _('Browse source code');
					$menuUrls[] = '/svn/' . $groupObj->getUnixName() . '/';
					$menuTitles[] = _('Administration');
					$menuUrls[] = '/scm/admin?group_id=' . $groupId;
				}
			}
		} else if (!session_loggedin()) {

			if ($groupObj && is_object($groupObj) && !$groupObj->isError() && $groupObj->enableAnonSCM()) {
				if ($groupObj->usesGitHub()) {
					// GitHub.
					$url = $groupObj->getGitHubAccessURL();
					if (!empty($url)) {
						$menuTitles[] = 'Summary';
						$menuUrls[] = '/githubAccess?group_id=' . $groupId;
						$menuTitles[] = _('Browse source code');
						$menuUrls[] = "/githubAccess/loadGitHubAccessURL.php?group_id=" . $groupId;
					}
				}
				else {
					// Subversion.
					$menuTitles[] = 'Summary';
					$menuUrls[] = '/scm?group_id=' . $groupId;
					$menuTitles[] = _('Browse source code');
					$menuUrls[] = '/svn/' . $groupObj->getUnixName() . '/';
				}
			}
		} else if (session_loggedin() && forge_check_perm('scm', $groupId, 'read')) {

			if (is_object($groupObj) && !$groupObj->isError()) {
				if ($groupObj->usesGitHub()) {
					// GitHub.
					$url = $groupObj->getGitHubAccessURL();
					if (!empty($url)) {
						$menuTitles[] = 'Summary';
						$menuUrls[] = '/githubAccess?group_id=' . $groupId;
						$menuTitles[] = _('Browse source code');
						$menuUrls[] = "/githubAccess/loadGitHubAccessURL.php?group_id=" . $groupId;
					}
				}
				else {
					// Subversion.
					$menuTitles[] = 'Summary';
					$menuUrls[] = '/scm?group_id=' . $groupId;
					$menuTitles[] = _('Browse source code');
					$menuUrls[] = '/svn/' . $groupObj->getUnixName() . '/';
				}
			}
		}
	}
	else if ($sectionName == "Documents") {
		if (session_loggedin()) {
			$menuTitles[] = 'View/Edit documents';
			$menuUrls[] = '/docman?group_id=' . $groupId;
			if (forge_check_perm('docman', $groupId, 'submit')) {
				$menuTitles[] = _('Add new item');
				$menuUrls[] = '/docman?group_id=' . $groupId . '&amp;view=additem';
			}
			/*
			if (forge_check_perm('docman', $groupId, 'approve')) {
				$dm = new DocumentManager($groupObj);
				if (!$dm->isTrashEmpty()) {
					$menuTitles[] = _('Trash');
					$menuUrls[] = '/docman?group_id=' . $groupId .
						'&amp;view=listtrashfile';
				}
			}

			if (forge_check_perm('docman', $groupId, 'admin')) {
				//$menuTitles[] = _('Reporting');
				//$menuUrls[] = '/docman?group_id=' . $groupId . '&amp;view=reporting';
				$menuTitles[] = _('Administration');
				$menuUrls[] = '/docman?group_id=' . $groupId . '&amp;view=admin';
			}
			*/
		}
	}


	else if ($sectionName == "About") {
		if ($groupObj && is_object($groupObj) && !$groupObj->isError()) {
		    $menuTitles[] = 'Project summary';
		    $menuUrls[] = '/projects/' . $groupObj->getUnixName();

		    $menuTitles[] = 'Project statistics';
		    $menuUrls[] = '/plugins/reports/index.php?type=group&reports=reports&group_id=' . $groupId;

			if ($groupObj->usesPlugin("publications")) {
			   $menuTitles[] = 'Publications';
			   $menuUrls[] = '/plugins/publications/index.php?type=group&id=' . $groupId .
				'&pluginname=publications';
			}

			// Check permission before adding administrative menu items.
			$menuTitles[] = 'Team members';
			$menuUrls[] = '/project/memberlist.php?group_id=' . $groupId;

		}
	}
	else if ($sectionName == "Simulations") {
		if (session_loggedin() && ($u = &session_get_user()) &&
			$groupObj && is_object($groupObj) && !$groupObj->isError()) {
			$menuTitles[] = 'View simulations';
			$menuUrls[] = '/simulations/viewJobs.php?group_id=' . $groupId;
			$menuTitles[] = 'Submit job';
			$menuUrls[] = '/simulations/submitJob.php?group_id=' . $groupId;
			if (forge_check_perm ('project_admin', $groupId, 'write')) {
				$menuTitles[] = 'Administration';
				$menuUrls[] = '/simulations/simulationsAdmin.php?group_id=' . $groupId;
			}
		}
	}


/*
	// Commented out. Do not show User preferences (i.e. User control panel).
	else if ($sectionName == "Forums") {
		if (session_loggedin()) {
			$menuTitles[] = 'View forum';
			$menuUrls[] = '/plugins/phpBB/indexPhpbb.php?group_id=' . $groupId;
			$menuTitles[] = 'User preferences';
			$menuUrls[] = '/plugins/phpBB/ucpPhpbb.php?group_id=' . $groupId;
		}
	}
*/
}


// Retrieve submenu items given the submenu title.
function sectionSubMenuItems($sectionName, $theSubMenuTitle, $groupId,
	&$submenuTitles, &$submenuUrls, &$submenuDispNames) {

	// Get group object using group id.
	$groupObj = group_get_object($groupId);

	if ($sectionName == "Issues") {
		$arrTrackersInfo = getTrackersInfo($groupId);
		if (!isset($arrTrackersInfo[$theSubMenuTitle])) {
			// Note: Not all popup menu items have submenus!
			return;
		}
		$arrInfo = $arrTrackersInfo[$theSubMenuTitle];
		if ($arrInfo == null) {
			// Note: Not all popup menu items have submenus!
			return;
		}
		$atid = $arrInfo['atid'];
		if (session_loggedin()) {
			if (forge_check_perm('tracker', $atid, 'read')) {
				$submenuTitles[] = $theSubMenuTitle . " tracker";
				$submenuUrls[]  = '/tracker/?atid=' . $atid . '&group_id=' . $groupId . '&func=browse';
				$submenuDispNames[$theSubMenuTitle . " tracker"] =
					abbrName($theSubMenuTitle) . " tracker";
			}
			if (forge_check_perm('tracker', $atid, 'submit')) {
				$submenuTitles[] = "Submit new";
				$submenuUrls[]  = '/tracker/?atid=' . $atid . '&group_id=' . $groupId . '&func=add';
			}
			$ath = new ArtifactTypeHtml($groupObj, $atid);
			if ($ath && is_object($ath) && !$ath->isError()) {
				if ($ath->isMonitoring()) {
					$submenuTitles[] = "Stop Follow";
					$submenuUrls[]  = '/tracker/?group_id=' . $groupId . '&atid='. $atid . '&func=monitor&stop=1';
				}
				else {
					$submenuTitles[] = "Follow";
					$submenuUrls[]  = '/tracker/?group_id=' . $groupId . '&atid='. $atid . '&func=monitor&start=1';
				}
			}
			if (forge_check_perm('tracker', $atid, 'manager')) {
				$submenuTitles[] = "Administration";
				$submenuUrls[]  = '/tracker/admin/?atid=' . $atid . '&group_id=' . $groupId;
			}
		}
		else {
			if ($arrInfo['is_public'] == 1) {
				if (forge_check_perm('tracker', $atid, 'read')) {
					$submenuTitles[] = $theSubMenuTitle . " tracker";
					$submenuUrls[]  = '/tracker/?atid=' . $atid . '&group_id=' . $groupId . '&func=browse';
					$submenuDispNames[$theSubMenuTitle . " tracker"] =
						abbrName($theSubMenuTitle) . " tracker";
				}
			}
			if ($arrInfo['allow_anon'] == 1) {
				$submenuTitles[] = "Submit new";
				$submenuUrls[]  = '/tracker/?atid=' . $atid . '&group_id=' . $groupId . '&func=add';
			}
			$ath = new ArtifactTypeHtml($groupObj, $atid);
			if ($ath && is_object($ath) && !$ath->isError()) {
				if ($ath->isMonitoring()) {
					$submenuTitles[] = "Stop Follow";
					$submenuUrls[]  = '/tracker/?group_id=' . $groupId . '&atid='. $atid . '&func=monitor&stop=1';
				}
				else {
					$submenuTitles[] = "Follow";
					$submenuUrls[]  = '/tracker/?group_id=' . $groupId . '&atid='. $atid . '&func=monitor&start=1';
				}
			}
		}
	} else if ($sectionName == "About" && $theSubMenuTitle == "Publications") {



			   /*
			   if (forge_check_perm('pubs', $groupId, 'project_admin') ||
				  forge_check_perm('project_read', $groupId)) {
				  $submenuTitles[] = 'Add publications';
				  $submenuUrls[] = '/plugins/publications/admin/add.php?group_id=' . $groupId;
			   }
			   */

			   if (forge_check_perm ('pubs', $groupId, 'project_admin')) {
			      $submenuTitles[] = 'View publications';
			      $submenuUrls[] = '/plugins/publications/index.php?type=group&id=' . $groupId . '&pluginname=publications';
			      $submenuTitles[] = 'Add publications';
				  $submenuUrls[] = '/plugins/publications/admin/add.php?group_id=' . $groupId;
				  $submenuTitles[] = _('Administration');
				  $submenuUrls[] = '/plugins/publications/admin/?group_id=' . $groupId;
			   }


	} else if ($sectionName == "About" && $theSubMenuTitle == "Project statistics") {

	       $submenuTitles[] = 'Downloads Summary';
			   $submenuUrls[] = '/plugins/reports/index.php?type=group&reports=reports&group_id=' . $groupId;

			   if (forge_check_perm('project_admin', $groupId)) {
			      $submenuTitles[] = 'Downloads Details';
			      $submenuUrls[] = '/project/admin/statistics.php?group_id=' . $groupId;
			   }
			   /*
			   if (session_loggedin() && $groupObj && is_object($groupObj) && !$groupObj->isError() && forge_check_perm ('frs', $groupId, 'write')) {
			      // Check permission before adding administrative menu items.
			      $submenuTitles[] = 'Download Stat Plots';
			      $submenuUrls[] = '/frs/reporting/downloads.php?group_id=' . $groupId;
		       }
			   */
			   // Available for public view.
			   $submenuTitles[] = 'Project Activity Plots';
			   $submenuUrls[] = '/project/stats/index.php?group_id=' . $groupId;

			   $submenuTitles[] = 'Geography of Use';
			   $submenuUrls[] = '/plugins/reports/usagemap.php?group_id=' . $groupId;

		     // Display Forum Statistics submenu if Forum plugin is enabled.
		     if ($groupObj->usesPlugin("phpBB")) {
			      $submenuTitles[] = 'Forum Statistics';
			      $submenuUrls[] = '/project/stats/forum_stats.php?group_id=' . $groupId;
		     }

    } else if ($sectionName == "About" && $theSubMenuTitle == "Members") {

			   if (forge_check_perm ('project_admin', $groupId)) {
			     // Check permission before adding administrative menu items.
				 $submenuTitles[] = 'View Members';
			     $submenuUrls[] = '/project/memberlist.php?group_id=' . $groupId;
			     $submenuTitles[] = 'Administration';
			     $submenuUrls[] = '/project/admin/users.php?group_id=' . $groupId;
		       }
				 } else if ($sectionName == "Downloads" && $theSubMenuTitle == "Downloads") {

		 	           $submenuTitles[] = 'View';
		 			   $submenuUrls[] = '/frs?group_id=' . $groupId;
		 	           if (session_loggedin() &&
		 			     $groupObj && is_object($groupObj) && !$groupObj->isError() &&
		 			     forge_check_perm ('frs', $groupId, 'write')) {
		 			     // Check permission before adding administrative menu items.
		 			     $submenuTitles[] = _('Administration');
		 			     $submenuUrls[] = '/frs/admin/?group_id=' . $groupId;
		 		       }

		 	} else if ($sectionName == "Downloads" && $theSubMenuTitle == "Data Share") {


		 			     $submenuTitles[] = 'View';
		 			     $submenuUrls[] = '/plugins/datashare/?group_id=' . $groupId;
		 				 if (session_loggedin() &&
		 			     $groupObj && is_object($groupObj) && !$groupObj->isError() &&
		 			     forge_check_perm ('datashare', $groupId, 'write')) {
		 			        $submenuTitles[] = _('Administration');
		 			        $submenuUrls[] = '/plugins/datashare/admin/?group_id=' . $groupId;
		 				 }
		 	} else if ($sectionName == "Data Share" && $theSubMenuTitle == "Data Share") {


		 		 			     $submenuTitles[] = 'View';
		 		 			     $submenuUrls[] = '/plugins/datashare/?group_id=' . $groupId;
		 		 				 if (session_loggedin() &&
		 		 			     $groupObj && is_object($groupObj) && !$groupObj->isError() &&
		 		 			     forge_check_perm ('datashare', $groupId, 'write')) {
		 		 			        $submenuTitles[] = _('Administration');
		 		 			        $submenuUrls[] = '/plugins/datashare/admin/?group_id=' . $groupId;
		 		 				 }
	/*
	} else if ($sectionName == "Admin" && $theSubMenuTitle == "Reports") {

	           $submenuTitles[] = 'Downloads Summary';
			   $submenuUrls[] = '/plugins/reports/index.php?type=group&reports=reports&group_id=' . $groupId;


			   $submenuTitles[] = 'Downloads Details';
			   $submenuUrls[] = '/project/admin/statistics.php?group_id=' . $groupId;


			   $submenuTitles[] = 'Geography of Use';
			   $submenuUrls[] = '/plugins/reports/usagemap.php?group_id=' . $groupId;
	*/
	} else if ($sectionName == "Admin" && $theSubMenuTitle == "Tools") {
		$submenuTitles[] = 'Enable/Disable';
		$submenuUrls[] = '/project/admin/tools.php?group_id=' . $groupId;
		if ($groupObj->usesFRS()) {
			$submenuTitles[] = 'Downloads';
			$submenuUrls[] = '/frs/admin/?group_id=' . $groupId;
		}
		if ($groupObj->usesDocman()) {
			$submenuTitles[] = 'Documents';
			$submenuUrls[] = '/docman?group_id=' . $groupId . '&amp;view=admin';
		}
		if ($groupObj->usesSCM()) {
			$submenuTitles[] = 'Source Code';
			$submenuUrls[] = '/scm/admin?group_id=' . $groupId;
		}
		if ($groupObj->usesTracker()) {
			$submenuTitles[] = 'Issues';
			$submenuUrls[]  = '/tracker/admin/?show_tracker=1&group_id=' . $groupId;
		}
		if ($groupObj->usesPlugin("simtk_news")) {
			$submenuTitles[] = 'News';
			$submenuUrls[]='/plugins/simtk_news/admin/?group_id=' . $groupId;
		}
		if ($groupObj->usesMail()) {
			$submenuTitles[] = 'Mailing lists';
			$submenuUrls[] = '/mail/admin/index.php?group_id=' . $groupId;
		}
		if ($groupObj->usesPlugin("datashare")) {
			$submenuTitles[] = 'Data Share';
			$submenuUrls[] = '/plugins/datashare/admin/?group_id=' . $groupId;
		}
	}
}

// Get GitHub access info from group_github_access table.
function getGitHubRepositoryAccessInfo($group_id) {

	$arrGitHubAccessInfo = array();

	$strQuery = 'SELECT main_url ' .
		'FROM group_github_access ' .
		'WHERE group_id=$1';
	$resGitHubAccessInfo = db_query_params($strQuery, array($group_id));
	while ($theRow = db_fetch_array($resGitHubAccessInfo)) {
		$arrGitHubAccessInfo['main_url'] = $theRow['main_url'];
	}

	return $arrGitHubAccessInfo;
}

// Get trackers info from artifact_group_list table.
function getTrackersInfo($group_id) {

	$arrTrackersInfo = array();

	$strQuery = 'SELECT group_artifact_id, name, simtk_is_public, simtk_allow_anon ' .
		'FROM artifact_group_list WHERE group_id=$1';
	$resTracker = db_query_params($strQuery, array($group_id));
	while ($theRow = db_fetch_array($resTracker)) {
		$arrInfo = array();
		$name = $theRow['name'];
		$arrInfo['atid'] = $theRow['group_artifact_id'];
		$arrInfo['is_public'] = $theRow['simtk_is_public'];
		$arrInfo['allow_anon'] = $theRow['simtk_allow_anon'];
		$arrTrackersInfo[$name] = $arrInfo;
	}

	return $arrTrackersInfo;
}

/*
 * Abbreviate name to number of characters specified.
 * Show first X characters, followed by "...."
 */
function abbrName($inName) {

	// Use first 24 leading characters.
	defined("COUNT_LEADING_CHARACTERS_NAME") or define("COUNT_LEADING_CHARACTERS_NAME", 16);

	$trimmedName = $inName;
	if (strlen($inName) <= COUNT_LEADING_CHARACTERS_NAME) {
		// Ignore and return.
		// Not abbreviated, $abbr value too short, or string is shorter than $abbr.
		return $trimmedName;
	}

	$trimmedName = substr($inName, 0, COUNT_LEADING_CHARACTERS_NAME) . "....";

        return $trimmedName;
}

// Check simulation permission.
// NOTE: permission=0 means only only group members can access.
// permssion=1 means all logged in users can access.
function checkSimulationPermission($theGroupObj, $theUserObj=false) {

	// Fetch simulation permission for group and user.
	$simu_permission = _getSimulationPermission($theGroupObj->getID());
	if ($simu_permission === false) {
		// Simulations not set up for this group.
		return false;
	}

	// Group has simulation permissions.

	if (forge_check_global_perm('forge_admin')) {
		// forge_admin always has permission.
		return true;
	}

	if ($simu_permission == 1) {
		// All logged-in members allowed.
		return true;
	}

	// Access to members of project only.

	if ($theUserObj === false) {
		// User object not provided.
		return false;
	}

	// Check for whether user is member of project.
	// Get user id.
	$theUserID = $theUserObj->getID();
	$memberObjs = $theGroupObj->getMembers();
	foreach ($memberObjs as $memberObj) {
		if ($memberObj->getID() == $theUserID) {
			// User is a member.
			return true;
		}
	}

	// Not a member of project.
	return false;
}

// Get simulation permission.
// NOTE: permission=0 means only only group members can access.
// permssion=1 means all logged in users can access.
function _getSimulationPermission($groupId) {

	// No simulation permission is set up for the group.
	$perm = false;

	$sql = "SELECT permission FROM simulation_job " .
		"WHERE group_id=$1 ";
	$result = db_query_params($sql, array($groupId));
	$rows = db_numrows($result);
	for ($i = 0; $i < $rows; $i++) {
		// Found simulation permissions.
		// Members of group or any logged-in users.
		$perm = db_result($result, $i, 'permission');
	}
	db_free_result($result);

	return $perm;
}


?>
