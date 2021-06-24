<?php

/**
 *
 * activatePhpbbForum.php
 * 
 * Activate a phpBB forum.
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
 
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/forum_db_utils.php';
require_once 'userPermsSetup.php';

/**
* @ignore
*/
global $auth, $cache, $config, $db, $phpbb_root_path, $phpEx, $template, $user; 

function activatePhpbbForum($forumId, $auth) {

	// Set default values.
	$default_poster	= pg_escape_string("SimTK Admin");
	$default_poster_id = 2;

	// New forum get created within 'Projects' group (id 1).
	$parent_id = 1;

	// Set defaults for new forum.
	$forum_type = 1;
	$forum_flags = 48;
	$forum_parents = 'a:1:{i:1;a:2:{i:0;s:8:"Projects";i:1;i:1;}}';
	$forum_parents_e = pg_escape_string($forum_parents);

	// Extract data for proper group.
	$result = false;
	if ($forumId !== false && $forumId != null && $forumId != "") {
		$query  = "SELECT * FROM groups WHERE group_id=$1";
		$result = db_query_params($query, array($forumId));
	}
	if (!$result) {
		return false;
	}
	while ($row = db_fetch_array($result)) {
		$forum_id = $row["group_id"];
		$group_id = (int) '';
		$forum_name = pg_escape_string($row["group_name"]);
		$group_name = pg_escape_string(substr($row["group_id"] . ";" . $row["group_name"], 0, 58));
		$description = pg_escape_string($row["simtk_summary"]);
		$group_type = (int) '2';

		// Set empty/default values for variables.
		$group_attributes = array(
			'group_rank'=>(int)'',
			'group_colour'=>'',
//			'group_avatar'=>'',
//			'group_avatar_type'=>(int)'',
//			'group_avatar_width'=>(int)'',
//			'group_avatar_height'=>(int)'',
			'group_receive_pm'=>(int)'',
			'group_legend'=> (int)'',
			'group_message_limit'=>(int)'',
			'group_max_recipients'=>(int)'',
		);
		$query_sub_1 = "SELECT forum_id,left_id,right_id FROM phpbb_forums " .
			"ORDER BY forum_id DESC LIMIT 1";
		$result_sub_1 = queryForum($query_sub_1);
		if (!$result_sub_1) {
			return false;
		}
		while ($row = pg_fetch_array($result_sub_1)) {
			$left_id = $row["left_id"] + 2;
			$right_id = $row["right_id"] + 2;
		}
		pg_free_result($result_sub_1);

		// Create new forum with data from Gforge group.
		$query_sub_1 = "INSERT INTO phpbb_forums " .
			"(forum_id,forum_name,forum_desc,parent_id," .
			"left_id,right_id,forum_type,forum_flags," .
			"forum_parents,display_on_index) " .
			"VALUES (" .
			"$forum_id," .
			"'" . $forum_name . "'," .
			"'" . $description . "'," .
			"$parent_id,$left_id,$right_id,$forum_type,$forum_flags," .
			"'" . $forum_parents_e . "'" .
			",0)";
		$result_sub_1 = queryForum($query_sub_1);
		pg_free_result($result_sub_1);

		group_create($group_id, $group_type, $group_name, $description, $group_attributes);

		//copy_forum_permissions(2, $forum_id, true);
	}
	db_free_result($result);

	$final_right = $right_id + 1;
	$query = "UPDATE phpbb_forums SET right_id = " . $final_right . " WHERE forum_id = 1";
	$result	= queryForum($query);
	pg_free_result($result);

	// Grab the highest group_id to advance the group sequence.
	$query = "SELECT group_id FROM phpbb_groups ORDER BY group_id DESC LIMIT 1";
	$result = queryForum($query);
	if (!$result) {
		return false;
	}
	while ($row = pg_fetch_array($result)) {
		$tmp_group_id = $row["group_id"];
		$group_id_seq = $tmp_group_id + 1;
	}
	pg_free_result($result);


	$query = "ALTER SEQUENCE phpbb_groups_seq RESTART WITH $group_id_seq";
	$result	= queryForum($query);
	pg_free_result($result);

	$forum_id_seq = $forum_id + 1;
	$query = "ALTER SEQUENCE phpbb_forums_seq RESTART WITH $forum_id_seq";
	$result	= queryForum($query);
	pg_free_result($result);


	// Create topics.

	// Grab value of topics sequence.
	$query = "SELECT nextval('phpbb_topics_seq');";
	$result	= queryForum($query);
	$row = pg_fetch_row($result);
	$topic_id = $row[0] + 1;

	// Create topic.
	$first_posted_name = $default_poster;
	$last_posted_name = $default_poster;
	$topic_title = pg_escape_string($forum_name . " Public Forum");
	$topic_time = microtime(TRUE);
	$topic_active_date = microtime(TRUE);
	$topic_poster = $default_poster_id;

	$query = "INSERT INTO phpbb_topics " .
		"(topic_id,forum_id,topic_title,topic_time,topic_last_post_time," .
		"topic_poster,topic_first_poster_name,topic_last_poster_name) VALUES (" .
		"$topic_id,$forum_id,'$topic_title',$topic_time,$topic_active_date," .
		"$topic_poster,'$first_posted_name','$last_posted_name')";
	$result = queryForum($query);
	pg_free_result($result);

	// Advance sequence.
	$topic_id_seq = $topic_id + 1;
	$query = "ALTER SEQUENCE phpbb_topics_seq RESTART WITH $topic_id_seq";
	$result = queryForum($query);
	pg_free_result($result);


	// Create post.
	$post_title = $forum_name . " Public Forum";
	$post_time = microtime(TRUE);
	$post_subject = $forum_name . " Public Forum";
	$post_body = "Welcome to the " . $forum_name . 
		" public forum. Feel free to browse or search " .
		"the topics for helpful information, or post a topic of your own.";
	$poster_id = $topic_poster;
	$poster_name = pg_escape_string("SimTK.org Admin");

	$query = "INSERT INTO phpbb_posts (" .
		"topic_id,forum_id,poster_id,post_time," .
		"post_username,post_subject,post_text) VALUES (" .
		"$topic_id," .
		"$forum_id," .
		"$poster_id," .
		"$post_time," .
		"'" . $poster_name . "'," .
		"'" . $post_subject . "'," .
		"'" . $post_body . "'" .
		")";
	$result = queryForum($query);
	pg_free_result($result);

	// Build topic counts in forums.
	$query = "SELECT forum_id FROM phpbb_forums where forum_id = " . $forum_id;
	$result = queryForum($query);
	if (!$result) {
		return false;
	}
	while ($row = pg_fetch_array($result)) {
		$forum_id = $row["forum_id"];

		$query_2_last = "SELECT * FROM phpbb_posts " .
			"WHERE forum_id = $forum_id ORDER BY post_time";
		$result_2_last = queryForum($query_2_last);
		$topic_count = pg_num_rows($result_2_last);
		if (!$result_2_last) { 
			return false;
		}
		while ($row = pg_fetch_array($result_2_last)) {
			$post_time = $row["post_time"];
			$post_id = $row["post_id"];
			$post_subject = pg_escape_string($row["post_subject"]);
			$poster_name = pg_escape_string("SimTK.org Admin");
			$poster_id = $row["poster_id"];

			$query_2_last_n = "SELECT * FROM phpbb_users " .
				"WHERE user_id = $poster_id";
			$result_2_last_n = queryForum($query_2_last_n);
			while ($row_2_last_n = pg_fetch_array($result_2_last_n)) {
				$poster_name = $row_2_last_n["user_yim"];
				if ($poster_name == "")  {
					$poster_name = pg_escape_string("SimTK.org Admin");
				}
			}
			pg_free_result($result_2_last_n);
		}
		pg_free_result($result_2_last);


		$query_4 = "UPDATE phpbb_forums SET " .
			"forum_last_poster_id = $poster_id, " .
			"forum_last_post_id = $post_id, " .
			"forum_last_post_time = $post_time, " .
			"forum_last_post_subject = '" . $post_subject . "', " .
			"forum_last_poster_name = '" . $poster_name . "' " .
			"WHERE forum_id = $forum_id";
		$result_4 = queryForum($query_4);
		pg_free_result($result_4);

		$query_5 = "UPDATE phpbb_topics SET " .
			"topic_posts_approved = 1 " .
			"WHERE forum_id = $forum_id";
		$result_5 = queryForum($query_5);
		pg_free_result($result_5);
	}
	pg_free_result($result);


	// Build topic counts in forums.
	$query = "SELECT forum_id FROM phpbb_forums where forum_id = " . $forum_id;
	$result = queryForum($query);
	if (!$result) {
		return false;
	}
	while ($row = pg_fetch_array($result)) {
		$forum_id = $row["forum_id"];

		$query_2_last = "SELECT * FROM phpbb_topics WHERE forum_id = $forum_id";
		$result_2_last = queryForum($query_2_last);
		$topic_count = pg_num_rows($result_2_last);
		pg_free_result($result_2_last);

		$query_4 = "UPDATE phpbb_forums SET " .
			"forum_topics = $topic_count " .
			"WHERE forum_id = $forum_id";
		$result_4 = queryForum($query_4);
		pg_free_result($result_4);
	}
	pg_free_result($result);


	// Build post counts in forums.
	$query = "SELECT forum_id FROM phpbb_forums where forum_id = " . $forum_id;
	$result = queryForum($query);
	if (!$result) {
		return false;
	}
	while ($row = pg_fetch_array($result)) {
		$forum_id = $row["forum_id"];

		$query_2_last = "SELECT * FROM phpbb_posts WHERE forum_id = $forum_id";
		$result_2_last = queryForum($query_2_last);
		$post_count = pg_num_rows($result_2_last);
		pg_free_result($result_2_last);

		$query_4 = "UPDATE phpbb_forums SET " .
			"forum_posts = $post_count " .
			"WHERE forum_id = $forum_id";
		$result_4 = queryForum($query_4);
		pg_free_result($result_4);
	}
	pg_free_result($result);


	// Build group memberships.
	// NOTE: Should NOT use user_group table. Use role-based permissions!!!

	// List members
	$project = group_get_object($forumId);
        $members = $project->getUsers();
        $i=0;
        foreach ($members as $user) {
                $username = $user->getUnixName();
                $user_id = $user->getID();
                $roles = RBACEngine::getInstance()->getAvailableRolesForUser($user);
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
			$group_leader = 1;
		}

		// Find user_id in simtk db given the username.
		$userIdInForum = -1;
		$query_userid = "SELECT user_id from phpbb_users " .
			"WHERE username='" . $username . "'";
		$result_userid = queryForum($query_userid);
		while ($rowUserId = pg_fetch_array($result_userid)) {
			$userIdInForum = $rowUserId["user_id"];
		}
		if ($userIdInForum == -1) {
			// Cannot find user_id.
			continue;
		}
	}

	// Create permissions for group.
	$query = "SELECT forum_id FROM phpbb_forums WHERE forum_id = " . $forumId;
	$result = queryForum($query);
	if (!$result) {
		return false;
	}

	// Iterate over result set.
	while ($row = pg_fetch_array($result)) {
		$regular_permission = 21; // standard access plus polls
		$guest_permission = 17; // non-registered get read only
		$mod_permission	= 10; // moderator permisson for group
		$mod_user_permission = 14; // moderator permisson for user

		$forum_id = $row["forum_id"];

		// Grab if forum is private or public.
		$query_1 = "SELECT simtk_is_public from groups WHERE group_id=$1";
		$result_1 = db_query_params($query_1, array($forumId));
		$public_forum = 1;
		while ($row = pg_fetch_array($result_1)) {
			if ($row["simtk_is_public"] === 0) {
				$public_forum = 0;
			}
		}
		db_free_result($result_1);

		// Grab the right group to assign permissions to. Group and forum ids in phpbb
		// do not match up. The forum id is prepended to the group_name during the creation
		// process to help match them up. That's what we do here.
		$query_2 = "SELECT group_id from phpbb_groups " .
			"WHERE group_name like '" . $forumId . ";%'";
		$result_2 = queryForum($query_2);
		while ($row = pg_fetch_array($result_2)) {

			$target_group_id = $row["group_id"];

			$query_add = "INSERT INTO phpbb_acl_groups (" .
				"group_id,forum_id,auth_option_id,auth_role_id,auth_setting) VALUES (" . 
				"$target_group_id," . 
				"$forumId," .
				"0," .
				"$regular_permission," .
				"0)";

			$query_add_2 = "INSERT INTO phpbb_acl_groups (" .
				"group_id,forum_id,auth_option_id,auth_role_id,auth_setting) VALUES (" .
				"5," . 
				"$forumId," .
				"0," . 
				"$mod_permission," .
				"0)";
			$result_add = queryForum($query_add);
			$result_add_2 = queryForum($query_add_2);

			// Set up users permission in forum.
			userPermsSetup($forumId);

			if ($public_forum == 1) {
				$query_add_2 = "INSERT INTO phpbb_acl_groups (" .
					"group_id,forum_id,auth_option_id," .
					"auth_role_id,auth_setting) VALUES (" .
					"2," .
					"$forumId," .
					"0," .
					"$regular_permission," .
					"0)";
				$query_add_3 = "INSERT INTO phpbb_acl_groups (" .
					"group_id,forum_id,auth_option_id," .
					"auth_role_id,auth_setting) VALUES (" .
					"1," .
					"$forumId," .
					"0," .
					"$guest_permission," .
					"0)";
				$query_add_4 = "INSERT INTO phpbb_acl_groups (" .
					"group_id,forum_id,auth_option_id," .
					"auth_role_id,auth_setting) VALUES (" .
					"6," .
					"$forumId," .
					"0," .
					"$guest_permission," .
					"0)";

				$result_add_2 = queryForum($query_add_2);
				$result_add_3 = queryForum($query_add_3);
				$result_add_4 = queryForum($query_add_4);
			}
		}
		pg_free_result($result_2);
	}
	pg_free_result($result);

	$auth->acl_clear_prefetch();

	// Success!!!
	return true;
}

?>

