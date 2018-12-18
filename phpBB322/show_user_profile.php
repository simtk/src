<?php

/**
 *
 * show_user_profile.php
 * 
 * Display user profile.
 * 
 * Copyright 2005-2018, SimTK Team
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
 
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/forum_db_utils.php';

$userName = null;
$userId = $_REQUEST["userId"];
if (isset($userId) && $userId != null && $userId != "") {
	// For security reasons, use phpbb_users user_id instead username.
	// NOTE: this parameter is visible in the URL.
	$query = "SELECT username from phpbb_users where user_id=" . $userId;
	$res = queryForum($query);
	if (!$res) {
		return;
	}
	while ($row = pg_fetch_array($res)) {
		$userName = $row["username"];
	}
	pg_free_result($res);
}
else {
	// Last resort.
	// Try getting user name from $_REQUEST.
	$userName = $_REQUEST["userName"];
}

// Display the SimTK user profile.
// Note: Since phpBB is in a plugin, need to use the top window to show the profile.
// Note: Need to use replace() method to not alter the history.
echo "<script>window.top.location.replace('/users/" . $userName . "');</script>";

exit;

?>

