<?php

/**
 *
 * sync_user.php
 * 
 * Synchronize FusionForge user with phpBB.
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
 
/**
* @ignore
*/
global $auth, $cache, $config, $db, $phpbb_root_path, $phpEx, $template, $user; 

define('IN_PHPBB', true);

//require (dirname(__FILE__) . "/../../../common/include/env.inc.php");
//require_once $gfcommon . "include/pre.php";
require "/usr/share/fusionforge/common/include/env.inc.php";
require_once "/usr/share/fusionforge/common/include/pre.php";

// Get root path for phpBB plugin.
//$phpbbDirWWW = $gfcommon . "../plugins/phpBB/www/";
//$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $phpbbDirWWW;
$phpbbDirWWW = "/usr/share/fusionforge/plugins/phpBB/www/";
$phpbb_root_path = $phpbbDirWWW;

// Generate $phpEx.
$phpEx = substr(strrchr(__FILE__, '.'), 1);

// Get userName from GET request.
if (isset($_GET["userName"])) {
	$theUserName = $_GET["userName"];
}
//echo "phpbb_root_path: " . $phpbb_root_path . "<br/>\n";
//echo "userName: " . $theUserName . "<br/>\n";

include($phpbb_root_path . 'common.php');
include($phpbb_root_path . 'includes/functions_display.php');
include($phpbb_root_path . 'includes/functions_user.php');
include($phpbb_root_path . 'includes/functions_admin.php');

$user->session_begin(); 
$auth->acl($user->data);    
$user->setup(); 

if (!isset($theUserName)) {
	echo "user name not set\n";
	exit;
}

// Select user.
$strUsersQuery = "SELECT user_id, user_name, email, realname, status, university_name, add_date " .
	"FROM users " .
	"WHERE user_name=$1";
$result = db_query_params($strUsersQuery, array($theUserName));
if (!$result) {
     die("Error in SQL query");
}

// Retrieve credentials for phpBB database from "phpBB.ini" config file.
$forgeConfig = FusionForgeConfig::get_instance();
$simtkHost = $forgeConfig->get_value("phpBB", "phpbb_host");
$simtkDbName = $forgeConfig->get_value("phpBB", "phpbb_name");
$simtkDbUser = $forgeConfig->get_value("phpBB", "phpbb_user");
$simtkDbPassword = $forgeConfig->get_value("phpBB", "phpbb_password");

// Connect to phpBB database.
$myconn = pg_connect(
	"host=" . $simtkHost . 
	" dbname=" . $simtkDbName .
	" user=" . $simtkDbUser .
	" password=" . $simtkDbPassword);

while ($row = db_fetch_array($result)) {

	$userid = $row["user_id"];
	$username = $row["user_name"];
	$email = $row["email"];
	$realname = pg_escape_string($row["realname"]);
	$status = $row["status"];
	$from = substr(pg_escape_string($row["university_name"]), 0, 99);

	//echo($username . "<br/>\n");
	//echo($email . "<br/>\n");
	//echo($realname . "<br/>\n");
	//echo($status . "<br/>\n");
	//echo($from . "<br/>\n");

	$query_2 = "SELECT user_id, username FROM phpbb_users WHERE username = '" . $username . "'";
	//echo ($query_2 . "<br/>\n");

	$result_2 = pg_query_params($myconn, $query_2, array());
	if ($row_2 = pg_fetch_array($result_2)) {

		// The user is present in phpbb_users.
		$phpbb_username = $row_2["username"];
		$phpbb_userid = $row_2["user_id"];

		//echo ($username . ":" . $userid . ":" . $phpbb_username . ":" . $phpbb_userid . "<br/>\n");

		if ($status == "S" || $status == "D") {

			// Because the user is not active, add this user to 
			// phpbb_banlist to ban user from posting.

			// Check whether user is already in the phpbb_banlist.
			$query_3 = "SELECT ban_userid FROM phpbb_banlist WHERE ban_userid=" . $phpbb_userid;
			$result_3 = pg_query_params($myconn, $query_3, array());
			if ($row_3 = pg_fetch_array($result_3) == FALSE) {
				// Not present. Add user to phpbb_banlist.
				$res = user_ban('user', $phpbb_username, FALSE, FALSE, FALSE, "BAN", "BANNED"); 
				echo("BAN RESULT: " . $phpbb_username . ":" . $res . "<br/>\n");
			}
			else {
				// User is already in phpbb_banlist. Skip.
			}

			pg_free_result($result_3);       
		}
		else if ($status == "A") {

			// User is active.

			// Check whether user is in the phpbb_banlist.
			$query_3 = "SELECT ban_userid FROM phpbb_banlist WHERE ban_userid=" . $phpbb_userid;
			$result_3 = pg_query($myconn, $query_3);
			if ($row_3 = pg_fetch_array($result_3) == FALSE) {
				// Not present in phpbb_banlist. OK.
				//echo("User is already active: " . $phpbb_username . "<br/>\n");
			}
			else {
				// User is in phpbb_banlist.

				// Because the user is active, remove from phpbb_banlist.
				$query_4 = "DELETE FROM phpbb_banlist WHERE ban_userid=" . $phpbb_userid;
				$result_4 = pg_query($myconn, $query_4);

				// free memory
				pg_free_result($result_4);
			}

			pg_free_result($result_3);       

			// Update credentials for given user.
			// NOTE: Perform this operation because the user's 
			// FusionForge credentials may have been updated.
			$strPasswordHash = phpbb_hash("MY_PASSWORD");
			$strPhpbbUsersUpdate = 'UPDATE phpbb_users SET ' .
				'user_password=$2, ' .
				'user_yim=$3, ' .
				'user_email=$4 ' .
				'WHERE username=$1';
			$phpbb_res = pg_query_params($myconn, 
				$strPhpbbUsersUpdate, 
				array(
					$username,
					$strPasswordHash,
					$realname,
					$email
				)
			);
		}

	}
	else if ($status == "A") {
		// User is not in phpbb_users and user is active. Add to phpbb_users.
		echo("$username not in phpbb_users!<br/>\n");

		// Default maximum width and height constraints.
		$scaledWidth = 75;
		$scaledHeight = 75;

		// Fetch picture file name and extension if present.
		$pictureFileName = getPictureFileName($username);
		if ($pictureFileName === false) {
			// No valid picture file. Use default.
			$pictureFileName = "_thumb.jpg";
		}
		else {
			// Generate picture path string.
			$picture_dir = $phpbb_root_path . "images/avatars/gallery/";
			$picture_path_full = $picture_dir . $pictureFileName;

			// Get scaled constraints.
			getScaledPictureDimensions($picture_path_full, $scaledWidth, $scaledHeight);
		}

		$user_row = array(
			'username' => $username,
			'user_password' => phpbb_hash("MY_PASSWORD"),
			'user_email' => $email,
			'user_yim' => $realname,
			'user_from' => $from,
			'group_id' => 2, // by default, the REGISTERED user group is id 2
			'user_type' => USER_NORMAL,
			'user_avatar' => $pictureFileName,
			'user_avatar_type' => 3,
//			'user_avatar' => '_thumb.jpg',
//			'user_avatar_width' => 75,
//			'user_avatar_height' => 75,
			'user_avatar_width' => $scaledWidth,
			'user_avatar_height' => $scaledHeight,
			'user_regdate' => time(),
		);

		// Add to phpbb_users.
		$user_id = user_add($user_row);

		print_r($user_row);
	}

	pg_free_result($result_2);
}

db_free_result($result);


// Generate scaled picture file width and height, maitaining the picture aspect ratio.
function getScaledPictureDimensions($picturePathFull, &$theScaledWidth, &$theScaledHeight) {

	if (list($picWidth, $picHeight, $picType, $picAttr) = @getimagesize($picturePathFull)) {

		$ratioH = ((float) $theScaledHeight) / ((float) $picHeight);
		$ratioW = ((float) $theScaledWidth) / ((float) $picWidth);
		// Use the dimension that is constraining.
		$theRatio = min($ratioH, $ratioW);

		// New dimensions.
		$theScaledWidth = intval($theRatio * $picWidth);
		$theScaledHeight = intval($theRatio * $picHeight); 
	}
}

// Get picture file name and extension from users table.
// Note: The file extension is jpg, png, gif, or bmp.
// Return false if picture file is not present or extension is invalid.
function getPictureFileName($theUserName) {

	// Note: Picture file name is always set to the user name.
	// Hence, only picture type should be retrieved.

	$sqlPictureFile = "SELECT picture_type FROM users WHERE user_name='$theUserName'";
	$resPictureFile = db_query_params($sqlPictureFile, array());
	if ($rowPictureFile = db_fetch_array($resPictureFile)) {

		// Found picture file.
		$thePictureType = $rowPictureFile["picture_type"];

		// Convert to jpg, png, gif, or bmp file extension.
		$thePictureFileExtension = validatePictureFileImageType($thePictureType);
		if ($thePictureFileExtension === false) {
			// Picture file type not present (or is "") or invalid.
			db_free_result($resPictureFile);
			return false;
		}
	}
	else {
		// Picture file not present.
		db_free_result($resPictureFile);
		return false;
	}

	db_free_result($resPictureFile);

	$theFileName = $theUserName . "_thumb.$thePictureFileExtension";
	return $theFileName;
}


// Get picture type: jpg, png, gif, or bmp are valid.
// Return false for invalid picture types.
function validatePictureFileImageType($inPicFileType) {

	$thePicFileType = false;
	if (strripos($inPicFileType, "jpg") !== false ||
		strripos($inPicFileType, "jpeg") !== false ||
		strripos($inPicFileType, "pjpeg") !== false) {
		$thePicFileType = "jpg";
	}
	else if (strripos($inPicFileType, "png") !== false ||
		strripos($inPicFileType, "x-png") !== false) {
		$thePicFileType = "png";
	}
	else if (strripos($inPicFileType, "gif") !== false) {
		$thePicFileType = "gif";
	}
	else if (strripos($inPicFileType, "bmp") !== false ||
		strripos($inPicFileType, "x-bmp") !== false) {
		$thePicFileType = "bmp";
	}
	else {
		// Invalid picture type.
		return false;
	}

	// Valid picture type: JPG, PNG, GIF, or BMP.
	return $thePicFileType;
}


?>


