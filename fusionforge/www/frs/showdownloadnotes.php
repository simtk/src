<?php

/**
 *
 * showdownloadnotes.php
 * 
 * Display download notes.
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once 'frs_data_util.php';

$group_id = getIntFromRequest('group_id');

$theGroupInfo = getFrsGroupInfo($group_id);
$is_public = $theGroupInfo['is_public'];
$notes = $theGroupInfo['download_notes'];
$preformatted = $theGroupInfo['preformatted'];

//  Members of projects can see all packages
//  Non-members can only see public packages
if(!$is_public) {
	if (!session_loggedin() || (!user_ismember($group_id) &&
	    !forge_check_global_perm('forge_admin'))) {
		exit_permission_denied();
	}
}

frs_header(array('title'=>'Instructions and Details', 'group'=>$group_id));

// Show preformatted or plain notes/changes
if ($preformatted == "1") {
	$opening = '<pre>';
	$closing = '</pre>';
}
else {
	$opening = '<p>';
	$closing = '</p>';
}

if ($notes) {
	echo $HTML->boxTop(_('Instructions and Details'));
	echo "$opening" . $notes . "$closing";
	echo $HTML->boxBottom();
}

frs_footer();
