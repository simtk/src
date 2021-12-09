<?php

/**
 *
 * uploadProgress.php
 * 
 * Send upload progress.
 *
 * Copyright 2005-2021, SimTK Team
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

// Get session status.
$statusSession = session_status();
if ($statusSession !== PHP_SESSION_DISABLED) {
	// Session enabled.
	if ($statusSession !== PHP_SESSION_ACTIVE) {
		// Start session to get the $_SESSION variable.
		session_start();
	}
}

$theToken = false;

// Examine each POST parameter.
foreach ($_POST as $key=>$val) {
	// Get JSON-decoded data from the key.
	$theData = json_decode($key, true);
	if ($theData !== null && is_array($theData)) {
		// Has JSON data and data is array.
		if (isset($theData["token"])) {
			// Found the token.
			$theToken = $theData["token"];
			$theToken = intval($theToken);
		}
	}
}
if ($theToken === false) {
	// Token does not exist.
	echo -1;
	return;
}

$key = ini_get("session.upload_progress.prefix") . $theToken;
if (isset($_SESSION[$key])) {
	// Found upload_progress.
	echo json_encode($_SESSION[$key]);
}
else {
	// Upload progress info does not exist; it has been cleared already.
	echo -1;
}

?>

