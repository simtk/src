<?php

/**
 *
 * userPermsSetup.php
 * 
 * Set up user permissions.
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
 
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/forum_db_utils.php';

/**
* @ignore
*/
global $auth, $cache, $config, $db, $phpbb_root_path, $phpEx, $template, $user;


// Populate phpbb_user_group and phpbb_acl_users tables to set up permissions.
function userPermsSetup($forumId) {

	// Moderator permisson for group.
	$mod_permission = 10;
	// Moderator permisson for user.
	$mod_user_permission = 14;

	// Clean phpbb_acl_users table of given forum before repopulation.
	$query_clean_acl_users = "DELETE FROM phpbb_acl_users WHERE forum_id=$forumId";
	$result_clean_acl_users = queryForum($query_clean_acl_users);

	// Clean phpbb_user_group table of given forum before repopulation.
	$query_clean_user_group = "DELETE FROM phpbb_user_group " .
		"WHERE group_id IN (" .
		"SELECT group_id FROM phpbb_groups " .
		"WHERE group_name LIKE '" . $forumId . ";%')";
	$result_clean_user_group = queryForum($query_clean_user_group);

	// Get members of forum.
	$project = group_get_object($forumId);
	if ($project == false) {
		// Invalid project object.
		return;
	} 
	// NOTE: use getUsersWithID() to fetch the latest copy of members in project.
	$members = $project->getUsersWithId();
	foreach ($members as $theUser) {
		// NOTE: Username is used for cross referencing a user
		// between the gforge and phpBB databases.
		// Tables users (gforge db) and phpbb_users (phpBB db) have 
		// different user_id for a given user, but the user name is same.
		$username = $theUser->getUnixName();

		// Get gforge user_id here.
		$user_id = $theUser->getID();
		// Get role of the user from gforge db.
		$roles = RBACEngine::getInstance()->getAvailableRolesForUser($theUser);
		sortRoleList($roles);
		$role_names = array();
		foreach ($roles as $role) {
			if ($role->getHomeProject() && 
				$role->getHomeProject()->getID() == $project->getID()) {
				$role_names[] = $role->getName();
			}
		}
		$role_string = implode (', ', $role_names);

		$group_leader = 0;
		if ($role_string == "Admin") {
			// An "Admin" user.
			$group_leader = 1;
		}

		/*
		echo $username . ": " .  $role_string . "\n";
		echo "user_id: " . $user_id . "\n";
		echo "group_leader: " . $group_leader . "\n";
		*/

		// Find corresponding user_id in phpBB databse of the user.
		$userIdInForum = -1;
		$query_userid = "SELECT user_id from phpbb_users " .
			"WHERE username='" . $username . "'";
		$result_userid = queryForum($query_userid);
		while ($rowUserId = pg_fetch_array($result_userid)) {
			$userIdInForum = $rowUserId["user_id"];
		}
		if ($userIdInForum == -1) {
			// Cannot find the user_id in phpBB database.
			// Skip.
			continue;
		}

		// Find the group_id in phpBB database.
		// NOTE: The group_id in phpBB database is different from
		// that in the gforge database.
		$query_2 = "SELECT group_id from phpbb_groups " .
			"WHERE group_name like '" . $forumId . ";%'";
		$result_2 = queryForum($query_2);
		while ($row = pg_fetch_array($result_2)) {
			$target_group_id = $row["group_id"];

			// Populate phpbb_user_group table.
			// echo "target_group_id: " . $target_group_id . "\n";
			$query_add = "INSERT INTO phpbb_user_group (" .
				"user_id,group_id,group_leader,user_pending) VALUES (" .
				"$userIdInForum, " .
				"$target_group_id," .
				"$group_leader," .
				"0)";
			$result_add = queryForum($query_add);
			pg_free_result($result_add);
		}
		pg_free_result($result_2);

		// Populate phpbb_acl_users table.
		if ($group_leader == 1) {
			// Admin user.
			$query_add_3 = "INSERT INTO phpbb_acl_users (" .
				"user_id,forum_id,auth_option_id,auth_role_id,auth_setting) " .
				"VALUES (" .
				"$userIdInForum," .
				"$forumId," .
				"0," .
				"$mod_permission," .
				"0)";
		}
		else {
			// Non-admin user.
			$query_add_3 = "INSERT INTO phpbb_acl_users (" .
				"user_id,forum_id,auth_option_id,auth_role_id,auth_setting) " .
				"VALUES (" .
				"$userIdInForum," .
				"$forumId," .
				"0," .
				"$mod_user_permission," .
				"0)";
		}

		$result_add_3 = queryForum($query_add_3);
		pg_free_result($result_add_3);
	}
}

?>
