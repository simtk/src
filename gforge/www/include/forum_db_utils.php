<?php

/**
 *
 * forum_db_utils.php
 * 
 * Utility file to handle forum access.
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
 
// Execute the database query at the forum server.
function queryForum($theQuery) {

	// Note: This file is in the directory gforge/www/include.
	// Other paths are defined relative to this directory.
	require (dirname(__FILE__) . '/../../common/include/env.inc.php');
	require_once $gfcommon . "include/pre.php";

	// Retrieve phpBB database credentials from "phpBB.ini" config file.
	$forgeConfig = FusionForgeConfig::get_instance();
	$simtkHost = $forgeConfig->get_value("phpBB", "phpbb_host");
	$simtkDbName = $forgeConfig->get_value("phpBB", "phpbb_name");
	$simtkDbUser = $forgeConfig->get_value("phpBB", "phpbb_user");
	$simtkDbPassword = $forgeConfig->get_value("phpBB", "phpbb_password");

	// Connect and select database.
	$dbConn = pg_connect(
		"host=" . $simtkHost .
		" dbname=" . $simtkDbName .
		" user=" . $simtkDbUser .
		" password=" . $simtkDbPassword);
	if ($dbConn == null) {
		// Cannot connect to forum. Do not proceed.
		return false;
	}

	// Perform SQL query.
	$result = pg_query_params($dbConn, $theQuery, array()) or die('Query failed: ' . pg_last_error()); 

	// Closing connection
	pg_close($dbConn);

	return $result;
}


// Get last post which is visible by user (i.e. not deleted.)
function getLastQuery($theUser, &$forumName, &$forumId, &$topicId, &$postId, &$postTime) {

	// Query last post from phpbb_posts table.
	// Also, get other associated information with the last post.
	$strQuery = "SELECT forum_name, p.forum_id, p.topic_id, p.post_id, post_time " .
		"FROM phpbb_posts AS p " .
		"JOIN phpbb_forums AS f " .
		"ON p.forum_id=f.forum_id " .
		"JOIN phpbb_users AS u " .
		"ON p.poster_id=u.user_id " .
		"WHERE u.username='". $theUser . "' " . 
		"AND p.post_visibility=1 " .
		"ORDER BY post_time DESC LIMIT 1";

	// Query forum database.
	$res = queryForum($strQuery);
	if (pg_num_rows($res) == 0) {
		// Not found.

		// Free resultset.
		pg_free_result($res);

		return false;
	}

	while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
		$forumName = $row["forum_name"];
		$forumId = $row["forum_id"];
		$topicId = $row["topic_id"];
		$postId = $row["post_id"];
		$postTime = $row["post_time"];
	}

	// Free resultset.
	pg_free_result($res);

	return true;
}

// Get number of posts by user which is visible (i.e. not deleted.)
function getNumPosts($theUser) {

	$countPosts = 0;

	// Query post count from phpbb_posts table.
	$strQuery = "SELECT count(post_id) FROM phpbb_posts AS p " .
		"JOIN phpbb_users AS u " .
		"ON p.poster_id=u.user_id " .
		"WHERE p.post_visibility=1 AND u.username='" . $theUser . "'";

	// Query forum database.
	$res = queryForum($strQuery);
	while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
		$countPosts = $row["count"];
	}

	// Free resultset.
	pg_free_result($res);

	return $countPosts;
}

// Get number of posts by group id (i.e. the forum id) which is visible (i.e. not deleted.)
function getNumPostsByGroupId($theGroupId) {

	$countPosts = 0;

	// Query post count from phpbb_posts table.
	$strQuery = "SELECT count(post_id) FROM phpbb_posts " .
		"WHERE post_visibility=1 AND forum_id=" . $theGroupId;

	// Query forum database.
	$res = queryForum($strQuery);
	while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
		$countPosts = $row["count"];
	}

	// Free resultset.
	pg_free_result($res);

	return $countPosts;
}

// Get number of topics by group id (i.e. the forum id) which is visible (i.e. not deleted.)
function getNumTopicsByGroupId($theGroupId) {

	$countTopics = 0;

	// Query topic count from phpbb_topics table.
	$strQuery = "SELECT count(topic_id) FROM phpbb_topics " .
		"WHERE topic_visibility=1 AND forum_id=" . $theGroupId;

	// Query forum database.
	$res = queryForum($strQuery);
	while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
		$countTopics = $row["count"];
	}

	// Free resultset.
	pg_free_result($res);

	return $countTopics;
}

// Get posts by category which are visible (i.e. not deleted.)
function getCategoryPosts($numPostsToShow, $categoryId, $suppressDetails=false) {

	$strQueryGroup = "SELECT tgl.group_id FROM trove_group_link tgl " .
		"JOIN groups g " .
		"ON tgl.group_id=g.group_id " .
		"WHERE g.status='A' " .
		"AND g.simtk_is_public=1 ";

	if (isset($categoryId) && $categoryId != "") {
		// Has category id.
		$strQueryGroup .= 'AND trove_cat_id=$1';
		$resGroups = db_query_params($strQueryGroup, array($categoryId));
	}
	else {
		// No category id.
		$resGroups = db_query_params($strQueryGroup, array());
	}

	$arrGroups = array();
	while ($rowGroups = db_fetch_array($resGroups)) {
		array_push($arrGroups, $rowGroups["group_id"]);
	}
	$strGroups = implode(",", $arrGroups);
	db_free_result($resGroups);

	if (trim($strGroups) == "") {
		// No groups available. Skip.
		return;
	}

	// Query post count from phpbb_posts table.
	// Retrieve last 20 posts.
	$maxPostsRetreived = 20;
	$strQuery = "SELECT post_id, topic_id, p.forum_id, forum_name, " .
			"poster_id, username, user_avatar, " .
			"post_time, post_subject, post_text " .
		"FROM phpbb_posts AS p " .
		"JOIN phpbb_forums AS f " .
			"ON p.forum_id=f.forum_id ".
		"JOIN phpbb_users AS u " .
			"ON p.poster_id=u.user_id " .
		"WHERE p.post_visibility=1 " .
			"AND p.forum_id IN (" . $strGroups . ") " .
			"ORDER BY post_time DESC " .
			"LIMIT $maxPostsRetreived";

	// Query forum database.
	$strResult = "";
	$cnt = 0;
	$arrTopics = array();
	$resPosts = queryForum($strQuery);
	while ($row = pg_fetch_array($resPosts, null, PGSQL_ASSOC)) {
		if ($cnt >= $numPostsToShow) {
			// Done.
			break;
		}

		// Track topics displayed.
		$theTopic = $row["topic_id"];
		if (isset($arrTopics[$theTopic])) {
			// Topic has appeared before. Skip.
			continue;
		}
		$arrTopics[$theTopic] = $theTopic;

		// Retrieve post item for display.
		generate_display_post_item($row, $cnt, $strResult, $suppressDetails);
		$cnt++;
	}

	if ($cnt = 0) {
		$strResult = "No Posts Found";
	}

	// Free resultset.
	pg_free_result($resPosts);

	return $strResult;
}

// Generate UI display item per post.
function generate_display_post_item($result, $i, &$return, $suppressDetails=false) {

	$theForumId = $result["forum_id"];
	$theForumName = $result["forum_name"];

	$theUrl = "/plugins/phpBB/viewtopicPhpbb.php?" .
		"f=" . $result["forum_id"] .
		"&t=" . $result["topic_id"] .
		"&p=" . $result["post_id"];

	$theSubject = $result["post_subject"];

	$return .= '<div class="item_discussion">';

	if ($suppressDetails === false) {
		// Title.
		$return .= '<h4>' . util_make_link($theUrl, $theSubject) . '</h4>';
		$return .= "\n";
	}

	// Project name.
	$strUnixGroupName = "";
	$strQueryGroupName = "SELECT unix_group_name FROM groups " .
		"WHERE group_id=" . $theForumId;
	$resGroupName = db_query_params($strQueryGroupName, array());
	while ($rowGroupName = db_fetch_array($resGroupName)) {
		$strUnixGroupName = $rowGroupName["unix_group_name"];
	}
	db_free_result($resGroupName);
	$forumName = util_make_link_g($strUnixGroupName, $theForumId, $theForumName);

	// Date.
	$theDate = date('M j, Y', $result["post_time"]);
	if ($suppressDetails === false) {
		$return .= "<div class='discussion_data'>" . $forumName . " " . $theDate . "</div>";
		$return .= "\n";
	}

	// Post item.
        $re = '/		# Split sentences on whitespace between them.
		(?<=		# Begin positive lookbehind.
		[.!?]		# Either an end of sentence punct,
		| [.!?][\'"]	# or end of sentence punct and quote.
		)		# End positive lookbehind.
		(?<!		# Begin negative lookbehind.
		Mr\.		# Skip either "Mr."
		| Mrs\.		# or "Mrs.",
		| Ms\.		# or "Ms.",
		| Jr\.		# or "Jr.",
		| Dr\.		# or "Dr.",
		| Prof\.	# or "Prof.",
		| Sr\.		# or "Sr.",
				# or... (you get the idea).
		)		# End negative lookbehind.
		\s+		# Split on whitespace between sentences.
		/ix';

	$theText = $result["post_text"];
	$arr = preg_split($re, $theText , -1, PREG_SPLIT_NO_EMPTY);
	$summ_txt = '';
	// If the first paragraph is short, and so are following paragraphs,
	// add the next paragraph on.
	if ((strlen($arr[0]) < 50) && (isset($arr[1]) && (strlen($arr[0].$arr[1]) < 300))) {
		if ($arr[1]) {
			$summ_txt .= $arr[0] . '. ' . $arr[1];
		}
		else {
			$summ_txt .= $arr[0]; // the news has only one sentence
		}
	}
	else {
		$summ_txt .= $arr[0];
	}


	// User picture file.
	$theUserName = $result["username"];
	if ($result["user_avatar"] != "_thumb.jpg") {
		$thePictureFile = $theUserName;
	}
	else {
//		$thePictureFile = "user_default.gif";
		$thePictureFile = "user_profile.jpg";
	}
	if ($suppressDetails === false) {
		if ($summ_txt != "") {
			$return .= '<div class="discussion_photo">';
			$return .= "<a href='/users/" . $theUserName . "'>";
			$return .= "<img " .
				' alt="Image not available"' .
				' onError="this.onerror=null;this.src=' . "'" . 
				'/userpics/user_profile.jpg' . "';" . '"' .
				" src='/userpics/" . $thePictureFile ."' class='news_img' />";
			$return .= "</a>";
			$return .= '</div>';
			$return .= '<div class="discussion_phototext">';
			$return .= $summ_txt;
			$return .= '</div>';
		}
	}
	else {
		$return .= '<div class="discussion_photo">';
		$return .= "<a href='/users/" . $theUserName . "'>";
		$return .= "<img " .
			' alt="Image not available"' .
			' onError="this.onerror=null;this.src=' . "'" . 
			'/userpics/user_profile.jpg' . "';" . '"' .
			" src='/userpics/" . $thePictureFile ."' class='news_img' />";
		$return .= "</a>";
		$return .= '</div>';
		$return .= '<div class="discussion_phototext">';
		$return .= '<h4 style="margin-top:0px;margin-bottom:0px;">' . util_make_link($theUrl, $theSubject) . '</h4>';
		$return .= "<div class='discussion_data'>" . $forumName . " " . $theDate . "</div>";
		$return .= '</div>';
	}

	$return .= '<div style="clear: both"></div>';
	$return .= '</div>';
	$return .= "\n\n";
}

// Get projects with most forum posts by group id 
// (i.e. the forum id) which is visible (i.e. not deleted.)
function getMostForumPostsProjects(&$arrNumPosts, $daysBack = 7) {

	$arrGroupIds = array();
	$arrNumPosts = array();

	$time = time();
	$time_back = $time - 86400 * $daysBack;

	// Query posts count from phpbb_posts table.
	$strQuery = "SELECT forum_id, count(post_id) AS cnt_posts FROM phpbb_posts " .
		"WHERE post_visibility=1 " .
		"AND post_time>" . $time_back . " " .
		"GROUP BY forum_id " .
		"ORDER BY cnt_posts DESC";

	// Query forum database.
	$res = queryForum($strQuery);
	while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
		$forumId = $row["forum_id"];
		$arrGroupIds[] = $forumId;
		$arrNumPosts[$forumId] = $row["cnt_posts"];
	}

	// Free resultset.
	pg_free_result($res);

	return $arrGroupIds;
}


// Get the forum moderators.
function getModerators($groupId) {

	$arrUserNames = array();

	// Query moderators from phpbb_acl_users table.
	$strQuery = 'SELECT DISTINCT pau.user_id, u.username FROM phpbb_acl_users pau ' .
		'JOIN phpbb_users u ' .
		'ON pau.user_id=u.user_id ' .
		'WHERE forum_id=' . $groupId .
		'AND auth_role_id=10';

	// Query forum database.
	$res = queryForum($strQuery);
	while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
		$userName = $row["username"];
		$arrUserNames[] = $userName;
	}

	// Free resultset.
	pg_free_result($res);

	return $arrUserNames;
}

?>
