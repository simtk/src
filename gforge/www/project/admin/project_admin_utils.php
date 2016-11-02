<?php
/**
 * Project Admin: Module of common functions
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org/
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/*
	Standard header to be used on all /project/admin/* pages
*/
// $params, after population, is returned to enable submenu information.
function project_admin_header($params) {
	global $group_id, $feedback, $HTML;

	$params['toptab'] = 'admin';
	$params['group'] = $group_id;

	$params['titleurl'] = '/project/admin/?group_id='.$group_id;

	session_require_perm('project_admin', $group_id);

	$project = group_get_object($group_id);
	if (!$project || !is_object($project)) {
		return;
	}

	$labels = array();
	$links = array();
	$attr_r = array();

	//$labels[] = _('Project Information');
	//$attr_r[] = array('class' => 'tabtitle', 'title' => _('General information about project. description.'));
	//$links[] = '/project/admin/?group_id='.$group_id;

	$labels[] = _('Tools');
	$attr_r[] = array('class' => 'tabtitle', 'title' => _('Activate / Desactivate extensions like docman, forums, plugins.'));
	$links[] = '/project/admin/tools.php?group_id='.$group_id;

	$labels[] = _('Category/Communities');
	$attr_r[] = array('class' => 'tabtitle', 'title' => _('Category, communities, primary content.'));
	$links[] = '/project/admin/category.php?group_id='.$group_id;

	$labels[] = _('Layout');
	$attr_r[] = array('class' => 'tabtitle', 'title' => _('Layout for standard or publication type.'));
	$links[] = '/project/admin/layout.php?group_id='.$group_id;

	$labels[] = _('Settings');
	$attr_r[] = array('class' => 'tabtitle', 'title' => _('General Administrative Settings'));
	$links[] = '/project/admin/settings.php?group_id='.$group_id;

	$labels[] = _('Members');
	$attr_r[] = array('class' => 'tabtitle', 'title' => _('Permissions management. Edit / Create roles. Assign new permissions to user. Add / Remove member.'));
	$links[] = '/project/admin/users.php?group_id='.$group_id;
	
	$labels[] = _('Reports');
	$attr_r[] = array('class' => 'tabtitle', 'title' => _('Reports.'));
	$links[] = '/project/admin/statistics.php?group_id='.$group_id;

/*
	$labels[] = _('Project History');
	$attr_r[] = array('class' => 'tabtitle', 'title' => _('Show the significant change of your project.'));
	$links[] = '/project/admin/history.php?group_id='.$group_id;
	if(forge_get_config('use_people')) {
		$labels[] = _('Post Jobs');
		$attr_r[] = array('class' => 'tabtitle', 'title' => _('Hiring new people. Describe the job'));
		$links[] = '/people/createjob.php?group_id='.$group_id;
		$labels[] = _('Edit Jobs');
		$attr_r[] = array('class' => 'tabtitle', 'title' => _('Edit already created available position in your project.'));
		$links[] = '/people/?group_id='.$group_id;
	}

	if(forge_get_config('use_project_multimedia')) {
		$labels[] = _('Edit Multimedia Data');
		//TODO: set the title.
		$attr_r[] = array('class' => 'tabtitle', 'title' => '');
		$links[] = '/project/admin/editimages.php?group_id='.$group_id;
	}
	if(forge_get_config('use_project_vhost')) {
		$labels[] = _('VHOSTs');
		//TODO: set the title.
		$attr_r[] = array('class' => 'tabtitle', 'title' => '');
		$links[] = '/project/admin/vhost.php?group_id='.$group_id;
	}
	if(forge_get_config('use_project_database')) {
		$labels[] = _('Database Admin');
		//TODO: set the title.
		$attr_r[] = array('class' => 'tabtitle', 'title' => '');
		$links[] = '/project/admin/database.php?group_id='.$group_id;
	}
*/
	if ($project->usesStats()) {
		$labels[] = _('Stats');
		//TODO: set the title.
		$attr_r[] = array('class' => 'tabtitle', 'title' => '');
		$links[] = '/project/stats/?group_id='.$group_id;
	}

	$params['labels'] =& $labels;
	$params['links'] =& $links;
	$params['attr_r'] =& $attr_r;
	plugin_hook("groupadminmenu", $params);
	$params['submenu'] = $HTML->subMenu($params['labels'], $params['links'], $params['attr_r']);
	site_project_header($params);

	// Return the populated $params.
	return $params;
}

/*

	Standard footer to be used on all /project/admin/* pages

*/

function project_admin_footer($params=array()) {
	site_project_footer($params);
}

/*

	The following three functions are for group
	audit trail

	When changes like adduser/rmuser/change status
	are made to a group, a row is added to audit trail
	using group_add_history()

*/

function group_get_history ($group_id=false) {
	return db_query_params("SELECT group_history.field_name,group_history.old_value,group_history.adddate,users.user_name
FROM group_history,users
WHERE group_history.mod_by=users.user_id
AND group_id=$1 ORDER BY group_history.adddate DESC", array($group_id));
}

function group_add_history ($field_name,$old_value,$group_id) {
	$group = group_get_object($group_id);
	$group->addHistory($field_name,$old_value);
}

/*

	Nicely html-formatted output of this group's audit trail

*/

function show_grouphistory ($group_id) {
	/*
		show the group_history rows that are relevant to
		this group_id
	*/

	$result=group_get_history($group_id);
	$rows=db_numrows($result);

	if ($rows > 0) {

		echo '<p>'._('This log will show who made significant changes to your project and when').'</p>';

		$title_arr=array();
		$title_arr[]=_('Field');
		$title_arr[]=_('Old Value');
		$title_arr[]=_('Date');
		$title_arr[]=_('By');

		echo $GLOBALS['HTML']->listTableTop ($title_arr);
		for ($i=0; $i < $rows; $i++) {
			$field=db_result($result, $i, 'field_name');
			echo '
			<tr '. $GLOBALS['HTML']->boxGetAltRowStyle($i) .'><td>'.$field.'</td><td>';

			if (is_numeric(db_result($result, $i, 'old_value'))) {
				if (preg_match("/[Uu]ser/i", $field)) {
					echo user_getname(db_result($result, $i, 'old_value'));
				} else {
					echo db_result($result, $i, 'old_value');
				}
			} else {
				echo db_result($result, $i, 'old_value');
			}
			echo '</td>'.
				'<td>'.date(_('Y-m-d H:i'),db_result($result, $i, 'adddate')).'</td>'.
				'<td>'.db_result($result, $i, 'user_name').'</td></tr>';
		}

		echo $GLOBALS['HTML']->listTableBottom();

	} else {
		echo '<p>'._('No changes').'</p>';
	}
}

/*
	prdb_namespace_seek - check that a projects' potential db name hasn't
	already been used.  If it has - add a 1..20 to the end of it.  If it
	iterates through twenty times and still fails - namespace depletion -
	throw an error.

 */
function prdb_namespace_seek($namecheck) {

	$query = 'SELECT * FROM prdb_dbs WHERE dbname=$1';

	$res_dbl = db_query_params($query, array($namecheck));

	if (db_numrows($res_dbl) > 0) {
		//crap, we're going to have issues
		$curr_num = 1;

		while ((db_numrows($res_dbl) > 0) && ($curr_num < 20)) {

			$curr_num++;
			$namecheck .= $namecheck.$curr_num;

			$res_dbl = db_query_params($query, array($namecheck));
		}

		// if we reached 20, then the namespace is depleted - eject eject
		if ($curr_num == 20) {
			exit_error(_('Failed to find namespace for database'),'home');
		}

	}
	return $namecheck;

} //end prdb_namespace_seek()

function random_pwgen() {
	return (substr(strtr(base64_encode(util_randbytes(9)), '+', '.'),
		       0, 10));
}

function permissions_blurb() {
	return _('<strong>NOTE:</strong><dl><dt><strong>Project Admins (bold)</strong></dt><dd>can access this page and other project administration pages</dd><dt><strong>Release Technicians</strong></dt><dd>can make the file releases (any project admin also a release technician)</dd><dt><strong>Tool Technicians (T)</strong></dt><dd>can be assigned Bugs/Tasks/Patches</dd><dt><strong>Tool Admins (A)</strong></dt><dd>can make changes to Bugs/Tasks/Patches as well as use the /toolname/admin/ pages</dd><dt><strong>Tool No Permission (N/A)</strong></dt><dd>Developer doesn\'t have specific permission (currently equivalent to \'-\')</dd><dt><strong>Moderators</strong> (forums)</dt><dd>can delete messages from the project forums</dd><dt><strong>Editors</strong> (doc. manager)</dt><dd>can update/edit/remove documentation from the project.</dd></dl>');
}

function getPrimaryContent() {

   $res = db_query_params('
		SELECT fullname, trove_cat_id, description, simtk_intro_text
		FROM trove_cat
		WHERE parent=404 order by fullname',
			array());
   return $res;
}

function getBiologicalApplications() {

   $res = db_query_params('
		SELECT fullname, trove_cat_id, description, simtk_intro_text
		FROM trove_cat
		WHERE parent=403 order by fullname',
			array());
   return $res;
}

function getBiocomputationalFocus() {

   $res = db_query_params('
		SELECT fullname, trove_cat_id, description, simtk_intro_text
		FROM trove_cat
		WHERE parent=408 order by fullname',
			array());
   return $res;
}

// Get project communities.
// NOTE: trove_cat value of 1000 is used as parent of community pages.
function getProjectCommunities() {

   $res = db_query_params('
		SELECT fullname, trove_cat_id, description, simtk_intro_text, auto_approve_child
		FROM trove_cat
		WHERE parent=1000 order by fullname',
			array());
   return $res;
}


// Check whether using old style display orders.
// (i.e. project_lead = 1).
function checkLegacyProjectLead($groupId) {

	$countLegacy = 0;
	$resUsers = db_query_params('SELECT count(project_lead) AS count_legacy ' .
		'FROM user_group ' .
		'WHERE group_id=$1 ' .
		'AND project_lead=1',
		array($groupId));
	if ($resUsers && db_numrows($resUsers) > 0) {
		$row = db_fetch_array($resUsers);
		$countLegacy = $row['count_legacy'];
	}
	return $countLegacy;
}

// Get information for display order (from "user_group" table "project_lead" column.)
function getUserDisplayOrder($groupId) {

	$arrUsers = array();

	// First fix legacy project_lead values where project_lead 
	// values can be 1s instead of being multiples of 2.
	$countLegacy = checkLegacyProjectLead($groupId);
	if ($countLegacy > 0) {
		regenerateDisplayOrder($groupId);
	}

	$resUsers = db_query_params('SELECT user_id, project_lead FROM user_group ' .
		'WHERE group_id=$1 ' .
		'AND NOT project_lead=0 ' .
		'ORDER BY project_lead ASC',
		array($groupId));
	$numUsers = db_numrows($resUsers);

	$lastMax = 1;
	// Generate UI display order values (e.g. 1,2,3,... not multiples of 2.)
	$curMax = 0;
        for ($cnt = 0; $cnt < $numUsers; $cnt++) {
		$theUser = db_fetch_array($resUsers);

		// Note: project_lead are in multiples of 2 (i.e. 0, 2, 4, 6, ...)
		if ($theUser['project_lead'] > $lastMax) {
			$curMax++;
			$lastMax = $theUser['project_lead'];
		}
		$arrUsers[$theUser['user_id']] = $curMax;
        }

	return $arrUsers;
}


// Regenerate the display order after a user has been 
// removed or order has changed.
// Note: Also used for fixing legacy code where project_lead values 
// can be 1s instead of being multiples of 2. In this case, project_lead 
// values are either 0 or 1, but not any other values.
function regenerateDisplayOrder($groupId) {

	$arrUsers = array();

	// Get group object.
	$group = group_get_object($groupId);
	// Get current valid group members. Role is checked.
	$members = $group->getUsersWithId() ;

	// Find all users with project_lead value > 0.
	$resUsers = db_query_params('SELECT user_id FROM user_group ' .
		'WHERE group_id=$1 ' .
		'AND NOT project_lead=0 ' .
		'ORDER BY project_lead ASC',
		array($groupId));

	$numUsers = db_numrows($resUsers);
	for ($cnt = 0; $cnt < $numUsers; $cnt++) {
		// Build project_lead value using 2 times loop's index count.
		$theUser = db_fetch_array($resUsers);
		$userId = $theUser['user_id'];

		// NOTE: Check whether user is a member of the group here.
		// If user is not a member of the group,
		// remove this user from the user_group table and 
		// do not add to display order.
		if (!isset($members[$userId])) {
			db_begin();
			$strRemove = 'DELETE FROM user_group WHERE group_id=$1 AND user_id=$2';
			$arrRemove = array($groupId, $userId);
			$res = db_query_params($strRemove, $arrRemove);
			if (!$res || db_affected_rows($res) < 1) {
				db_rollback();
			}
			// Remove user from user_group table.
			db_commit();
			
			// Do not add to display order.
			continue;
		}
		// Add to display order.
		$valProjLead = ($cnt + 1) * 2;
		$arrUsers[$userId] = $valProjLead;
	}

	$strOrder = 'UPDATE user_group SET project_lead=$1 ' .
		'WHERE group_id=$2 ' .
		'AND user_id=$3';

	$valProjLead = 0;
	foreach ($arrUsers as $userId=>$oldValProjLead) {

		// Create consecutive multiples of 2 for the display order, starting at 2.
		$valProjLead += 2;

		db_begin();

		$arrOrder = array(
			$valProjLead,
			$groupId,
			$userId
		);
		$res = db_query_params($strOrder, $arrOrder);
		if (!$res || db_affected_rows($res) < 1) {
			$error_msg = sprintf(_('Error On Update: %s'), db_error());
			db_rollback();
			return false;
		}
		db_commit();
	}

	return;
}


// Process project_lead values that are greater than or equal to the
// specified value, other than the specified user.
function processRemainingDisplayOrder($valProjLead, $groupId, $userId) {

	$arrUsers = array();

	// Find remaining entries with greater project_lead values.
	$resUsers = db_query_params('SELECT user_id FROM user_group ' .
		'WHERE group_id=$2 ' .
		'AND project_lead>=$1 ' .
		'AND NOT user_id=$3 ' .
		'ORDER BY project_lead ASC',
		$arrOrder = array(
			$valProjLead,
			$groupId,
			$userId
		)
	);

	$numUsers = db_numrows($resUsers);
	for ($cnt = 0; $cnt < $numUsers; $cnt++) {
		// Build project_lead value using 2 times loop's index count.
		// starting from the specified value + 2.
		$theUser = db_fetch_array($resUsers);
		$userId = $theUser['user_id'];
		$arrUsers[$userId] = $valProjLead + ($cnt + 1) * 2;
	}

	$strOrder = 'UPDATE user_group SET project_lead=$1 ' .
		'WHERE group_id=$2 ' .
		'AND user_id=$3';

	foreach ($arrUsers as $userId=>$tmpProjectLead) {
		db_begin();

		$arrOrder = array(
			$tmpProjectLead,
			$groupId,
			$userId
		);
		$res = db_query_params($strOrder, $arrOrder);
		if (!$res || db_affected_rows($res) < 1) {
			$error_msg = sprintf(_('Error On Update: %s'), db_error());
			db_rollback();
			return false;
		}
		db_commit();
	}

	return;
}

// Update display order of user in the group.
// Note: project_lead values are in multiples of 2.
function updateOrder($groupId, $userId, $isShow, $displayOrder) {

	// Get current display order.
	$arrUsers = getUserDisplayOrder($groupId);

	if ($isShow) {
		if (!array_key_exists($userId, $arrUsers)) {
			// This is a new item.

			// Get current maximum value in display order
			// and generate next value for this new item.
			if (empty($arrUsers)) {
				$maxvalDisplayOrder = 0;
			}
			else {
				$maxvalDisplayOrder = max($arrUsers);
			}
			$nextvalProjLead = 2 * $maxvalDisplayOrder + 2;

			db_begin();

			// Try UPDATE first.
			$strOrder = 'UPDATE user_group SET project_lead=$1 ' .
				'WHERE group_id=$2 ' .
				'AND user_id=$3';
			$arrOrder = array(
				$nextvalProjLead,
				$groupId,
				$userId
			);
			$res = db_query_params($strOrder, $arrOrder);
			if (!$res || db_affected_rows($res) < 1) {

				// Try INSERT.
				$strOrder = 'INSERT INTO user_group ' .
					'(project_lead, group_id, user_id) ' .
					'VALUES ($1, $2, $3)';
				$arrOrder = array(
					$nextvalProjLead,
					$groupId,
					$userId
				);
				$res = db_query_params($strOrder, $arrOrder);
				if (!$res || db_affected_rows($res) < 1) {
					$error_msg = sprintf(_('Error On Update: %s'), db_error());
					db_rollback();
					return false;
				}
			}
			db_commit();

			return;
		}
		else {
			// This is an existing item.

			if (is_null($displayOrder) || !is_numeric($displayOrder)) {
				// Invalid non-numeric input. Change to 1000.
				$displayOrder = 1000;
			}

			// Update display order for this item
			$valProjLead = $displayOrder * 2;
			db_begin();

			$curProjLead = 0;
			$strProjLead = 'SELECT project_lead FROM user_group ' .
				'WHERE group_id=$1 ' .
				'AND user_id=$2';
			$arrProjLead = array($groupId, $userId);
			$resProjLead = db_query_params($strProjLead, $arrProjLead);
			for ($cnt = 0; $cnt < db_numrows($resProjLead); $cnt++) {
				$theRow = db_fetch_array($resProjLead);
				$curProjLead = $theRow['project_lead'];
			}

			if ($valProjLead > $curProjLead) {
				// Move to higher display order.
				// Add 2 to ensure that the new position
				// is after a currently occupied value by another user
				// i.e. after a user with the same order value.
				// Then, let regenerateDisplayOrder() re-adjust order values
				// to consecutive multiples of 2.
				// NOTE: This adjustment is only necessary when 
				// moving to a higher display order value, but NOT
				// use when moving down to a lower order display value.
				$valProjLead += 2;
			}

			//echo "$userId: EXISTING display order: " . $curProjLead;
			//echo " NEW display order: " . $valProjLead;


			$strOrder = 'UPDATE user_group SET project_lead=$1
				WHERE group_id=$2
				AND user_id=$3';
			$arrOrder = array(
				$valProjLead,
				$groupId,
				$userId
			);
			$res = db_query_params($strOrder, $arrOrder);
			if (!$res || db_affected_rows($res) < 1) {
				$error_msg = sprintf(_('Error On Update: %s'), db_error());
				db_rollback();
				return false;
			}

			db_commit();

			// Fix up display order for all other entries
			// that are currently displayed.
			processRemainingDisplayOrder($valProjLead, $groupId, $userId);

			// Regenerate the display order to be equally
			// spaced multiples of 2 (i.e. 2, 4, 6, 8, etc.)
			regenerateDisplayOrder($groupId);

			return;
		}
	}

	if (!$isShow && array_key_exists($userId, $arrUsers)) {

		// Remove an item from display order.
		db_begin();
		$strOrder = 'UPDATE user_group SET project_lead=0
			WHERE group_id=$1
			AND user_id=$2';
		$arrOrder = array(
			$groupId,
			$userId
		);

		$res = db_query_params($strOrder, $arrOrder);
		if (!$res || db_affected_rows($res) < 1) {
			$error_msg = sprintf(_('Error On Update: %s'), db_error());
			db_rollback();
			return false;
		}
		db_commit();

		// Fix up display order after removal.
		// e.g. project_lead values: "2", "4", "6", "8".
		// After removing "6", "2", "4", "8" remains.
		// Fix it up to have "2", "4", "6" instad.
		regenerateDisplayOrder($groupId);

		return;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
