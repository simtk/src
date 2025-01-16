<?php

/**
 *
 * clearUserCache.php
 * 
 * Clear the user cache to get updated permissions.
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

// Select user.
if (!isset($theUserName)) {
	echo "user name not set\n";
	exit;
}

$strUsersQuery = "SELECT user_id, user_name, status FROM users " .
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
	$status = $row["status"];

	//echo($username . "<br/>\n");
	//echo($status . "<br/>\n");

	$query_2 = "SELECT user_id, username FROM phpbb_users WHERE username = $1";
	//echo ($query_2 . "<br/>\n");

	$result_2 = pg_query_params($myconn, $query_2, array($username));
	if ($row_2 = pg_fetch_array($result_2)) {

		// The user is present in phpbb_users.
		$phpbb_username = $row_2["username"];
		$phpbb_userid = $row_2["user_id"];

		//echo ($username . ":" . $userid . ":" . $phpbb_username . ":" . $phpbb_userid . "<br/>\n");

		// Clear stored permissions for user.
		$auth->acl_clear_prefetch($phpbb_userid);
	}

	pg_free_result($result_2);
}

db_free_result($result);

?>


