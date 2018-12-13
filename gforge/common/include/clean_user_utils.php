<?php

/**
 *
 * clean_user_utils.php
 * 
 * Utilities for cleaning user entry from database.
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

// Retrieve database tables and columns associated with user names or ids from file.
function getUserAssociatedTablesAndColumns(&$arrAssociationsGforge, &$arrAssociationsPhpbb) {

	// gforge tables.

	// phpbb tables.

	return true;
}

// Check whether the user has references in use.
function checkUserReferences($dbconnPhpbb,
	$userName,
	$uidGforge,
	$uidPhpbb,
	$arrAssociationsGforge,
	$arrAssociationsPhpbb,
	$arrWikiUser,
	&$msg) {

	return true;
}

// Clean the user entries from gforge and phpbb databases.
function cleanUserEntries($dbconnPhpbb) {

	return false;
}

// Look up Wiki user names from the wiki_users table.
function getWikiUsersFromDB() {
	$arrWikiUser = array();

	return $arrWikiUser;
}

// Get phpBB user_id given the user name.
function getPhpbbUserIdWithUsername($dbconnPhpbb, $userName) {

	return false;
}

// Get db connection of Phpbb.
function getDbconnPhpbb() {

	global $error_msg, $feedback, $warning_msg;

	// Note: This file is in the directory gforge/www/include.
	// Other paths are defined relative to this directory.
	require (dirname(__FILE__) . '/../../common/include/env.inc.php');
	require_once $gfcommon . "include/FusionForge.class.php";
	require_once $gfcommon . "include/pre.php";

	// Retrieve phpBB database credentials from "phpBB.ini" config file.
	$forgeConfig = FusionForgeConfig::get_instance();
	$simtkHost = $forgeConfig->get_value("phpBB", "phpbb_host");
	$simtkDbName = $forgeConfig->get_value("phpBB", "phpbb_name");
	$simtkDbUser = $forgeConfig->get_value("phpBB", "phpbb_user");
	$simtkDbPassword = $forgeConfig->get_value("phpBB", "phpbb_password");

	// Connect and select database.
	$dbconnPhpbb = pg_connect(
		"host=" . $simtkHost .
		" dbname=" . $simtkDbName .
		" user=" . $simtkDbUser .
		" password=" . $simtkDbPassword);
	if ($dbconnPhpbb == null) {
		// Cannot connect to forum. Do not proceed.
		return false;
	}

	return $dbconnPhpbb;
}

// Close db connection of Phpbb.
function closeDbconnPhpbb($dbconnPhpbb) {
	// Closing connection
	pg_close($dbconnPhpbb);
}

?>
