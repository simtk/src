<?php

/**
 *
 * viewPhpbb.php
 * 
 * View phpBB forum within an iframe.
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
 
// Display phpBB content into an iframe.

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';

$fid = getIntFromRequest('f');
// Find group id given the forum id.
// The forum id may be from a subforum and may need to find
// the group that the forum is associated with by looking up
// the forum parent hierarchy.
$group_id = lookupGroupIdFromForumId($fid, $subforumName);

$tid = getIntFromRequest('t');
$pid = getIntFromRequest('p');
$pluginname = 'phpBB';

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

// Page header.
site_project_header($params);

// Cross launch into phpbb.

$strPhpbbURL = '/plugins/phpBB/' .
	'viewtopic.php?' .
	'f=' . $fid .
	'&t=' . $tid .
	'&p=' . $pid;;

// get the session user

if ($user = session_get_user()) {
	$userName = $user->getUnixName();

	$strPhpbbURL = $strPhpbbURL . 
		'&forname=' . $userName .
		'&forpass=MY_PASSWORD';
}

echo '<iframe src="' . util_make_url($strPhpbbURL) . '" ' .
	'frameborder="0" width=100% height=700>' .
	'</iframe>';


// Page footer.
site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
