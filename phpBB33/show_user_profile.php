<?php

/**
 *
 * show_user_profile.php
 * 
 * Display user profile.
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
 
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/forum_db_utils.php';

$userName = null;
$userId = $_REQUEST["userId"];
if (isset($userId) && $userId != null && $userId != "") {
	// For security reasons, use phpbb_users user_id instead username.
	// NOTE: this parameter is visible in the URL.
	$userId = (int) $userId;
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

// Check whether user is active first.
if (checkUser($userName) !== false) {
	// User is active.
	// Go to user profile page.
	echo "<script>window.top.location.replace('/users/" . $userName . "');</script>";
}
else {
	if (!isset($_SERVER['HTTP_REFERER'])) {
		exit;
	}
	// User is not active.
	// Show alert message and send back to referring page.
	$strURL = $_SERVER['HTTP_REFERER'];
	if (strpos($strURL, "viewforumbyname.php") !== false) {
		// This referer is the front page of phpBB.
		// Replace with "indexPhpbb.php" to include SimTK header with iFrame.
		$strURL = str_replace("viewforumbyname.php", "indexPhpbb.php", $strURL);

		// Split URL by "&".
		// Keep only "fid=" parameter.
		$arrTokens = explode("&", $strURL);
		$strHead = $arrTokens[0];
		$strToken = "";
		for ($cntToken = 1; $cntToken < count($arrTokens); $cntToken++) {
			if (strpos($arrTokens[$cntToken], "fid=") === 0) {
				// Found parameter to replace "fid" with "group_id".
				$strToken = str_replace("fid=", "group_id=", $arrTokens[$cntToken]);
				break;
			}
		}
		if ($strToken != "") {
			// Has the token that contains group_id.
			$strURL = $strHead . "&" . $strToken;
		}
		// Just in case the "fid=" appears before the first "&".
		$strURL = str_replace("fid=", "group_id=", $strURL);
	}
	// Alert message.
	echo "<script>alert('This user no longer has an active SimTK account');</script>";
	// Go to referer page.
	echo "<script>window.top.location.replace('" . $strURL . "');</script>";
}

exit;


// Check whether user is active by user name.
function checkUser($userName) {

	$theCnt = 0;
	$query  = "SELECT count(*) FROM users " .
		"WHERE user_name=$1 " .
		"AND status='A'";
	$result = db_query_params($query, array($userName));
	if (!$result) {
		// Problem with query.
		return false;
	}
	if ($row = db_fetch_array($result)) {
		$theCnt = $row["count"];
	}
	db_free_result($result);

	if ($theCnt > 0) {
		// User is active.
		return true;
	}
	else {
		// User is not active.
		return false;
	}
}

?>

