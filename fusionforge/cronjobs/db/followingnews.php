#! /usr/bin/php
<?php
/**
 * Following News backend cron script
 * 
 * Copyright 2005-2020, SimTK Team
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

require dirname(__FILE__) . '/../../www/env.inc.php';
require_once $gfcommon . 'include/pre.php';
require $gfcommon . 'include/cron_utils.php';
require_once $gfplugins . 'mypage/www/mypage-utils.php';
require_once $gfplugins . 'simtk_news/www/simtk_news_utils.php';

// Pause between messages in sec.
$SLEEP = 1;

// Number of days to try to resend matched emails.
$DAYS_TO_RETRY = 3;

// Maximum number of emails allowed to be sent per run.
$max_allowed = 300;

// Set timezone.
date_default_timezone_set('America/Los_Angeles');

$email_sent = 0;
$email_held = 0;
$email_total = 0;

$err = '';

// Subject.
$subj = "Updates from the SimTK projects you follow";

// Create footer.
$tail = "<br />==================================================================<br />" ;
$tail .= 'You received this message because you are following projects on ' . 
	forge_get_config ('forge_name') . 
	'. You may opt out or change the frequency of the news you receive by logging in to ' . 
	forge_get_config ('forge_name') . 
	' and visiting your <a href="' .
	util_make_url('/account/settings.php/') .
	'">Account Settings Page</a>.';
	
echo "begin cronjob....\n";

// Get news by days.
$arrNumDays = array(1, 7, 14);
foreach ($arrNumDays as $days) {

	$arrNewsID = array();
	$arrUser = array();
	$arrUserEmail = array();
	$arrUserName = array();

	// Get news that matched the examination period.
	// NOTE: Add DAYS_TO_RETRY such that previously 
	// unsent emails due to exceeding email_held are included.
	$arrNews = getNewsByDays($days + $DAYS_TO_RETRY, 0);
	$compare_date = strtotime("-" . ($days + $DAYS_TO_RETRY) . " days");

	echo "\n\nFollowing Email Sent for Users with Notification Frequency: " . $days . " days\n";
	echo "Including News with Post Date On or After: " . date("m/d/Y", $compare_date) . "\n";
	echo "such that held emails up to " . $DAYS_TO_RETRY . " days prior are retried\n";

	//echo "count: " . count($arrNews) . "\n";
	//var_dump($arrNews);

	// Go through each news.
	foreach ($arrNews as $news) {

		$newsId = $news['id'];
		$arrNewsID[$newsId]['user_name'] = $news['user_name'];
		$arrNewsID[$newsId]['picture_file'] = $news['picture_file'];
		$arrNewsID[$newsId]['unix_group_name'] = $news['unix_group_name'];
		$arrNewsID[$newsId]['group_name'] = $news['group_name'];
		$arrNewsID[$newsId]['summary'] = $news['summary'];
		$arrNewsID[$newsId]['group_id'] = $news['group_id'];
		$arrNewsID[$newsId]['post_date'] = $news['post_date'];

		// Get users following the project.
		$sqlSelect = "SELECT * FROM project_follows pf " .
			"JOIN users u " .
			"ON u.user_name=pf.user_name " .
			"JOIN " .
			"(SELECT user_name, group_id, max(time) last_time FROM project_follows " .
			"GROUP BY group_id, user_name) lq " .
			"ON pf.user_name=lq.user_name " .
			"AND pf.group_id=lq.group_id " .
			"AND pf.time=lq.last_time " .
			"WHERE u.status='A' " .
			"AND pf.group_id=" . $news['group_id'] . " " .
			"AND follows=true " .
			"AND notification_frequency=" . $days . " " .
			"AND (notification_date=0 OR notification_date<=" . $compare_date . ")";
		$users_following = db_query_params($sqlSelect, array());
		if ($users_following) {
			while ($row = db_fetch_array($users_following)) {
				// Assign the news id to each userid array.
				$arrUser[$row['user_id']][] = $news['id'];
				$arrUserEmail[$row['user_id']] = $row['email'];
				$arrUserName[$row['user_id']]['user_name'] = $row['user_name'];
				$arrUserName[$row['user_id']]['realname'] = $row['realname'];
			}
		}
	}

	// Go through each user.
	foreach($arrUser as $user_id=>$theArrNews) {

		$body_news = "";
		foreach ($theArrNews as $newsId) {

			// Build body of email.
			// Format HTML News Page to send out.
			$tmpPicFile = $arrNewsID[$newsId]['picture_file'];
			if ($tmpPicFile == "") {
				$tmpPicFile = "user_profile.jpg";
			}
			$body_news .= '<tr><td width="45" valign="top"> <a href="' .
				util_make_url('/users/') . $arrNewsID[$newsId]['user_name'] .
				'"><img height="40" width="40" src="' . 
				util_make_url('/userpics/') . $tmpPicFile . '"></a></td>';
			$body_news .= '<td width="500" valign="top"> <strong><a href="' .
				util_make_url('/plugins/') . 'simtk_news/news_details.php?flag=2&group_id=' .
				$arrNewsID[$newsId]['group_id'] . '&id='.$newsId . 
				'" style="color:#5e96e1;">' . $arrNewsID[$newsId]['summary'] .
				'</a></strong><br /> ' .
				'<small><a href="' . util_make_url('/projects/') .
				$arrNewsID[$newsId]['unix_group_name'] . '/">' .
				$arrNewsID[$newsId]['group_name'] . '</a> ' .
				date(_('M d, Y'), $arrNewsID[$newsId]['post_date']) .
				'</small></td>';
			$body_news .= "</tr>";
		}

		$body =  "<p><b>Hello " . $arrUserName[$user_id]['realname'] . ",</b></p><br />";
		$body .= "<p><b>Here's the latest news from the SimTK projects you are following:</b></p><br />";
		$body .= '<table cellpadding="5" cellspacing="5">';
		$body .= $body_news;
		$body .= "</table>";

		// send email
		if ($email_sent >= $max_allowed) {
			// Do not update database.
			// Wait for next iteration of cronjob to resend.

			$email_held++;
		}
		else {
			// Disabled for now.
			util_send_message($arrUserEmail[$user_id], $subj,
				$body.$tail, 
				'noreply@'.forge_get_config('web_host'),
				'', '', '', true);

			// Sleep between sending emails.
			sleep($SLEEP);

			echo "\nSend email to: " . $arrUserEmail[$user_id] . "\r\n";
			echo "News Summary: " . $arrNewsID[$newsId]['summary'] . "\r\n";
			echo "News Post Date: " . date("m/d/Y", $arrNewsID[$newsId]['post_date']) . "\r\n";

			// update notification_sent field for user
			db_begin();
			$sqlUpdate = "UPDATE users SET notification_date=" . time() . " " .
				"WHERE user_id='" . $user_id . "'";
			$result_user = db_query_params($sqlUpdate, array());
			db_commit();

			$email_sent++;
		}

		$email_total++;
	}
}  

// send message to admin  
echo "\nTotal Emails: " . $email_total . "\n";
echo "Total Emails Sent: " . $email_sent . "\n";
echo "Total Emails Held (Not sent due to threshold): " . $email_held . "\n";

if (db_error()) {
	$err .= db_error();
}
$mess = "following news mail sent: " . $email_sent;
m_exit($mess);


function m_exit($mess = '') {
	global $err;

	if (!cron_entry(31, $mess.$err)) {
		// rely on crond to report the error
		echo "cron_entry error: " . db_error() . "\n";
	}
	exit;
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
