<?php

/**
 *
 * purge_phpbb_forums_email_track.php
 * 
 * Clean up phpbb forum post email send tracking one day old.
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
 
/**
* @ignore
*/
global $auth, $cache, $config, $db, $phpbb_root_path, $phpEx, $template, $user; 

define('IN_PHPBB', true);

// Note: This file is in the directory gforge/cronjobs.
// Other paths are defined relative to this directory.
require (dirname(__FILE__) . '/../common/include/env.inc.php');
require_once $gfcommon . "include/pre.php";

// Get root path for phpBB plugin.
$phpbbDirWWW = $gfcommon . "../plugins/phpBB/www/";
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $phpbbDirWWW;

// Generate $phpEx.
$phpEx = substr(strrchr(__FILE__, '.'), 1);

// Retrieve phpBB database credentials from "phpBB.ini" config file.
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

$onedayago = time() - 86400;

$strPhpbbForumsEmailTrackPurge = "DELETE FROM phpbb_forums_email_track " .
	"WHERE time<" . $onedayago;
$phpbb_res = pg_query_params($myconn, $strPhpbbForumsEmailTrackPurge, array());

echo "Purged phpbb_forums_email_track";

?>


