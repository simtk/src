<?php
/**
 * Project Members Information
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'project/project_utils.php';

$group_id = getIntFromGet("group_id");
$form_grp = getIntFromGet("form_grp");
if (!$group_id && $form_grp) {
	$group_id = $form_grp;
}

// Get selected sort criteria.
$mySelect = getStringFromRequest("mySelect");
if (!isset($mySelect) || trim($mySelect) == "") {
	// Default. Sort by roles.
	$mySelect = "sortRole";
}

// Check permission and prompt for login if needed.
session_require_perm('project_read', $group_id);

site_project_header(array('title'=>_('Project Member List'),'group'=>$group_id,'toptab'=>'memberlist'));


// beginning of the user descripion block
$project = group_get_object($group_id);
$project_stdzd_uri = util_make_url_g ($project->getUnixName(), $group_id);
$usergroup_stdzd_uri = $project_stdzd_uri.'members/';
print '<div about="'. $usergroup_stdzd_uri .'" typeof="sioc:UserGroup">';
print '<span rel="http://www.w3.org/2002/07/owl#sameAs" resource=""></span>';
print '<span rev="sioc:has_usergroup" resource="'. $project_stdzd_uri . '"></span>';
print '</div>';

$title_arr=array();
$title_arr[]=_('Member');
$title_arr[]=_('Role');

echo '<div class="project_overview_main">';
echo '<div style="display: table; width: 100%;">';
echo '<div class="main_col">';

// Create submenu under downloads_main DIV, such that it does not
// occupy the whole width of the page (rather than using the
// submenu population in Theme.class.php)
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuAttr = array();
$subMenuTitle["Title"] = "Team: Members";
$subMenuTitle[] = 'View Members';
$subMenuUrl[] = '/project/memberlist.php?group_id=' . $group_id;
if (session_loggedin()) {
	if ($project && is_object($project) && !$project->isError()) {
		if (forge_check_perm ('project_admin', $group_id, 'write')) {
			// Check permission before adding administrative menu items.
			$subMenuTitle[] = _('Administration');
			$subMenuUrl[] = '/project/usersAdmin.php?group_id=' . $group_id;
		}
	}
}
// Show the submenu.
echo $HTML->beginSubMenu();
echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, $subMenuAttr);
echo $HTML->endSubMenu();


echo "<div class='teamsort'>";
echo "<label for='select'>Sort by:&nbsp;</label>";
echo "<form method='post' action='/project/memberlist.php?group_id=" . $group_id . "'>";
echo "<select class='mySelect' name='mySelect' id='mySelect' onchange='this.form.submit()'>";
echo "<option value='sortName'";
if ($mySelect == 'sortName') {
	echo "selected";
}
echo ">Name</option>";
echo "<option value='sortRole' ";
if ($mySelect == 'sortRole') {
	echo "selected";
}
echo ">Role</option>";
echo "</select>";
echo "</form>";
echo "</div>";

$arrInfo = array();

// Get project members.
$members = $project->getUsers() ;
foreach ($members as $user) {
	// Get role string.
	$roles = RBACEngine::getInstance()->getAvailableRolesForUser($user);
	sortRoleList($roles);
	$role_names = array();
	foreach ($roles as $role) {
		if ($role->getHomeProject() && 
			$role->getHomeProject()->getID() == $project->getID()) {
			$role_names[] = $role->getName() ;
		}
	}
	$role_string = implode(', ', $role_names);

	// Generate entry for multisort.
	$arrInfo[] = array('Role'=>$role_string, 'Name'=>$user->getRealName(), 'User'=>$user);
}

$arrSort = array();
foreach ($arrInfo as $idx=>$val) {
	$arrSort['Role'][$idx] = $val['Role'];
	$arrSort['Name'][$idx] = $val['Name'];
}

if ($mySelect == 'sortRole') {
	// Sort by role first, then by name.
	array_multisort($arrSort['Role'], SORT_ASC, $arrSort['Name'], SORT_ASC, $arrInfo);
}
else {
	// Sort by name first, then by role.
	array_multisort($arrSort['Name'], SORT_ASC, $arrSort['Role'], SORT_ASC, $arrInfo);
}

foreach ($arrInfo as $idx=>$val) {
	// Get user info.
	$user = $val['User'];
	$userName = $val['Name'];
	$userRole = $val['Role'];
	$userImageFile = "/userpics/" . $user->getUnixName();

	// Check for existence of image file first.
	if (!file_exists($fusionforge_basedir . "/www" . $userImageFile)) {
		$userImageFile = "/userpics/user_profile.jpg";
	}

	$member_uri = util_make_url_u($user->getUnixName(), $user->getID());

	echo "<div class='team-col1'>";
	echo "<div class='team_member_lg'>";
	echo "<a href='/users/" . 
		$user->getUnixName() . 
		"'><img onerror='this.onerror=null;this.src=" . 
		'"' . 
		"/userpics/user_profile.jpg" . 
		'"' . 
		";' src='" . $userImageFile .
		"' alt='Image not available'></img></a>";
	echo "<div style='clear:both'></div>";
	echo "</div>";
	echo "</div>";
	echo "<div class='team-col2'>";
	echo "<div class='team-name'><a href='/users/" . $user->getUnixName() . "'>" . $userName . "</a></div>";
	echo "<div class='team-role'>" . $userRole . "</div>";
	echo "</div>";
	echo "<div style='clear:both'></div>";
}

// end of community member description block
echo '<div style="clear: both;"></div>';
echo "</div>";

// Display side bar.
constructSideBar($project);

echo '</div>';
echo '</div>';

site_project_footer(array());

// Construct side bar
function constructSideBar($groupObj) {
	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);
	echo '</div>';
}


