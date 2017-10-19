<?php

/**
 *
 * indexPhpbb.php
 * 
 * Display phpBB summary of forums.
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
 
// Display phpBB content into an iframe.

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/forum_db_utils.php';

if (isset($_GET['group_id'])) {
	$group_id = getIntFromRequest('group_id');
}
else if (isset($_GET['f'])) {
	$group_id = getIntFromRequest('f');
}
else {
	return;
}
$pluginname = 'phpBB';

// Get start page if present in parameter.
$start = 0;
if (isset($_GET['start'])) {
	$start = getIntFromRequest('start');
}

// Check permission and prompt for login if needed.
session_require_perm('project_read', $group_id);

$group = group_get_object($group_id);
if (!$group) {
	exit_error(sprintf(_('Invalid Project')), '');
}

if (!$group->usesPlugin($pluginname)) {
	exit_error(sprintf(_('First activate the %s plugin through the Project\'s Admin Interface'),
		$pluginname), '');
}

$params = array ();
$params['toptab'] = $pluginname;
$params['group'] = $group_id;
$params['title'] = 'phpBB' ;
$params['pagename'] = $pluginname;
$params['sectionvals'] = array ($group->getPublicName());

// Get moderators from forum database.
$arrModerators = getModerators($group_id);
$arrFullNames = array();
foreach ($arrModerators as $key=>$username) {
	// Get first and last names of user.
	$res = db_query_params("SELECT firstname, lastname FROM users " .
		"WHERE user_name='" . $username . "'",
		array());
	while ($row = db_fetch_array($res)) {
		$firstName = $row['firstname'];
		$lastName = $row['lastname'];
		$arrFullNames[] = $firstName . " " . $lastName;
	}
}
$strModerators = implode(", ", $arrFullNames);

// Page header.
site_project_header($params);

// Submenu title information.
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuTitle[] = 'View Forum';
$subMenuUrl[]='/plugins/phpBB/indexPhpbb.php?group_id=' . $group_id . '&pluginname=phpBB';

// Show the submenu.
echo $HTML->beginSubMenu();
echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
if (!empty($strModerators)) {
	// Has moderators.
	echo "<p>Moderators: " . $strModerators . "</p>";
}
// Link to Forum statiscis page.
echo "<a href='/project/stats/forum_stats.php?group_id=" . $group_id . "'>Forum Statistics and Usage</a>";
echo $HTML->endSubMenu();

// Store $group_id in $_COOKIE.
//
// NOTE: The Advanced Search needs this information to generate the variable 
// {THE_FORUM_ID} which is passed to the template search_body.html 
// to constrain the search to the current forum.
// Otherwise, all forums will be search.
if (isset($_GET["group_id"])) {
	$group_id = $_GET["group_id"];
}
else if (isset($_GET["f"])) {
	$group_id = $_GET["f"];
}
else {
	return;
}
if ($group_id != 0) {
	// group_id is NOT 0. Set group_id cookie (i.e. "f_curr").
	if (isset($_GET["group_id"])) {
		$group_id = $_GET["group_id"];
	}
	else if (isset($_GET["f"])) {
		$group_id = $_GET["f"];
	}
	else {
		return;
	}
	setcookie("f_curr", $group_id, time()+36000);
}
else {
	// group_id is 0. Retrieve group_id from cookie.
	$group_id = $_COOKIE["f_curr"];
}



// Cross launch into phpbb.

$strPhpbbURL = '/plugins/phpBB/' .
	'viewforumbyname.php?' .
	'fname=' . $group->getPublicName() .
	'&fid=' . $group_id;
if ($start != 0) {
	// Add start page.
	$strPhpbbURL .= '&start=' . $start;
}

// get the session user

if ($user = session_get_user()) {
	$userName = $user->getUnixName();

	$strPhpbbURL = $strPhpbbURL . 
		'&forname=' . $userName .
		'&forpass=MY_PASSWORD';
}


// NOTE: rand() is needed to avoid browser caching logged in user.
// Otherwise, even after the user has logged out, back button will
// load information of previous user.
echo '<iframe name="' . rand() . '" src="' . util_make_url($strPhpbbURL) . '" ' .
	'frameborder="0" scrolling="no" width="100%" height="700px">' .
	'</iframe>';

?>

<script src='iframeAdjust.js'></script>

<?php

// Page footer.
site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
