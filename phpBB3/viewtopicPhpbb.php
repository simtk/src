<?php

/**
 *
 * viewtopicPhpbb.php
 * 
 * View a topic within an iframe.
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

$fid = getStringFromRequest('f');
$idx = strpos($fid, "^t=");
if ($idx != FALSE) {
	// Look for presence of topic_id embedded within the forum_id.
	// This is the case when return_to is used, because "&" cannot be
	// used to separate topic_id from forum_id but have to use a "^" delimiter.

	// forum_id.
	$theFid = substr($fid, 0, $idx);
	// topic_id.
	$tid = substr($fid, $idx + 3);
	$fid = $theFid;

	if (!is_numeric($fid) || !is_numeric($tid)) {
		// Has non-integer values.
		// Reparse the data as integer values and let it fail.
		// This is a security precaution to not admit any non-integer 
		// forum_id or topic_id values to be used.
		$fid = getIntFromRequest('f');
		$tid = getIntFromRequest('t');
	}
}
else {
	// The string "^t=" is not present.
	// Parse for integer forum_id, and topic_id.
	$fid = getIntFromRequest('f');
	$tid = getIntFromRequest('t');
}
$pid = getIntFromRequest('p');
$start = getIntFromRequest('start');
$view = getStringFromRequest('view');
// Retrieve session_id.
$sid = getStringFromRequest('sid');
$pluginname = 'phpBB';


$group = group_get_object($fid);
if (!$group) {
	exit_error(sprintf(_('Invalid Project')), '');
}

if (!$group->usesPlugin($pluginname)) {
	exit_error(sprintf(_('First activate the %s plugin through the Project\'s Admin Interface'),
		$pluginname), '');
}

// Check permission and prompt for login if needed.
session_require_perm('project_read', $fid);

$params = array ();
$params['toptab'] = $pluginname;
$params['group'] = $fid;
$params['title'] = 'phpBB' ;
$params['pagename'] = $pluginname;
$params['sectionvals'] = array ($group->getPublicName());

// Page header.
site_project_header($params);

$group_id = $fid;
// Submenu title information.
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuTitle[] = 'View Forum';
$subMenuUrl[]='/plugins/phpBB/indexPhpbb.php?group_id=' . $group_id . '&pluginname=phpBB';
// Show the submenu.
echo $HTML->beginSubMenu();
echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
echo $HTML->endSubMenu();

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
if (!empty($strModerators)) {
	// Has moderators.
	echo "<p>Moderators: " . $strModerators . "</p>";
}


// Cross launch into phpbb.

$strPhpbbURL = '/plugins/phpBB/' .
	'viewtopic.php?' .
	'f=' . $fid .
	'&t=' . $tid .
	'&start=' . $start .
	'&view=' . $view;
if (isset($pid) && $pid) {
	$strPhpbbURL .= '&p=' . $pid;
}
if (isset($sid) && $sid != "") {
	// Include session_id if present.
	$strPhpbbURL .= '&sid=' . $sid;
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
