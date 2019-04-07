#! /usr/bin/php
<?php
/**
 * Following News backend cron script
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

require dirname(__FILE__).'/../www/env.inc.php';
require_once $gfcommon.'include/pre.php';
require $gfcommon.'include/cron_utils.php';
require_once $gfplugins.'mypage/www/mypage-utils.php';
require_once $gfplugins.'simtk_news/www/simtk_news_utils.php';

$err='';

if (!cron_create_lock(__FILE__)) {
	$err = "Following News already running...exiting";
	if (!cron_entry(31,$err)) {
		// rely on crond to report the error
		echo "cron_entry error: ".db_error()."\n";
	}
	exit();
}

// Pause between messages, sec
$SLEEP = 1;

/*
parse_str($argv[2], $_GET);
$initial = 'h';
$initial0 = 'i';
//parse_str($_SERVER['QUERY_STRING'], $_GET);
if (isset($_GET['initial'])) {
  $initial = $_GET['initial'];
}
if (isset($_GET['initial0'])) {
  $initial0 = $_GET['initial0'];
}
$all_users=db_query_params ("Select * FROM users where notification_frequency > 0 and LOWER(lastname) like '".$initial."%' or LOWER(lastname) like '".$initial0."%'",array());
//$all_users=db_query_params ("Select * FROM users where notification_frequency > 0 and LOWER(lastname) like '".$initial."%'",array());
*/

$subj = "Updates from the SimTK projects you follow";

$max = 0;
$num_sent = 0;
$users_notif = 0;
$body = "";
$today = date("Y-m-d");
// create footer
$tail = "<br />==================================================================<br />" ;
$tail .= 'You received this message because you are following projects on ' . forge_get_config ('forge_name') . '. You may opt out or change the frequency of the news you receive by logging in to ' . forge_get_config ('forge_name') . ' and visiting your <a href="'.util_make_url('/account/settings.php/').'">Account Settings Page</a>.';
	
echo "begin cronjob....\n";

// get news by days

$numdays = array(1,7,14);

foreach ($numdays as $days) {
  $arrNews = array();
  $arrNewsID = array();
  $arrUser = array();
  $arrUserEmail = array();
  $arrUserName = array();
  $arrNews = getNewsByDays($days,0);
  $compare_date = strtotime("-".$days. " days");

  echo "\n\nFollowing Email Sent for Users with Notification Frequency: " . $days . " days\n";
  echo "Include News with Post Date On or After: " . date("m/d/Y",$compare_date) . "\n";

  //echo "count: " . count($arrNews) . "\n";
  //var_dump($arrNews);


  if ($arrNews) {

   foreach ($arrNews as $news) {
      $arrNewsID[$news['id']]['user_name'] = $news['user_name'];
	  $arrNewsID[$news['id']]['picture_file'] = $news['picture_file'];
	  $arrNewsID[$news['id']]['unix_group_name'] = $news['unix_group_name'];
	  $arrNewsID[$news['id']]['group_name'] = $news['group_name'];
	  $arrNewsID[$news['id']]['summary'] = $news['summary'];
	  $arrNewsID[$news['id']]['group_id'] = $news['group_id'];
	  $arrNewsID[$news['id']]['post_date'] = $news['post_date'];
	  
      // get users following the project
	  $users_following = db_query_params("SELECT * FROM project_follows,users WHERE users.user_name = project_follows.user_name and users.status = 'A' and group_id= ". $news['group_id'] ." AND follows = true AND notification_frequency = ". $days ." AND (notification_date = 0 OR notification_date <= ". $compare_date . ")",array());
      if ($users_following) {
	     while ($row = db_fetch_array($users_following)) {
		     // assign the news id to each userid array
             $arrUser[$row['user_id']][] = $news['id'];
			 $arrUserEmail[$row['user_id']] = $row['email'];
			 $arrUserName[$row['user_id']] = $row['user_name'];
         }
	  }	 
	}

   // loop through each userid
   if ($arrUser) {
      foreach($arrUser as $user_id => $value) {
	     echo "\nSend email to: " . $arrUserEmail[$user_id] . "\r\n";
		 $body = "";
		 $body_news = "";
         foreach ($value as $key2 => $id) {
            echo "News Summary: ". $arrNewsID[$id]['summary'] ."\r\n";
			echo "News Post Date: ". date("m/d/Y",$arrNewsID[$id]['post_date']) ."\r\n";
			// build body of email
			// Format HTML News Page to send out
		    $tmpPicFile = $arrNewsID[$id]['picture_file'];
		    if ($tmpPicFile == "") {
			  $tmpPicFile = "user_profile.jpg";
		    }
		    $body_news .= '<tr><td width="45" valign="top"> <a href="'.util_make_url('/users/') . $arrNewsID[$id]['user_name'] . '"><img height="40" width="40" src="'.util_make_url('/userpics/') . $tmpPicFile .'"></a></td>';
		    $body_news .= '<td width="500" valign="top"> <strong><a href="'.util_make_url('/plugins/') . 'simtk_news/news_details.php?flag=2&group_id=' . 
						$arrNewsID[$id]['group_id'] . 
						'&id='.$id . 
						'" style="color:#5e96e1;">' . 
						$arrNewsID[$id]['summary'] . 
					'</a></strong><br />
					<small><a href="'.util_make_url('/projects/') . 
						$arrNewsID[$id]['unix_group_name'] . 
						'/">' . 
						$arrNewsID[$id]['group_name'] . 
						'</a> ' . 
						date(_('M d, Y'),$arrNewsID[$id]['post_date']) . 
					'</small></td>';
		  $body_news .= "</tr>";
			
			
         }
		 $body =  "<p>Hello " . $arrUserName[$user_id] . "</p><br />";
		 $body .= "<p><b>Here's the latest news from the SimTK projects you are following:</b></p><br />";
	     $body .= '<table cellpadding="5" cellspacing="5">';
         $body .= $body_news;
	     $body .= "</table>";
	     // send email
		 if ($arrUserEmail[$user_id] == "tod_hing@yahoo.com" || $arrUserEmail[$user_id] == "joyku@stanford.edu" || $arrUserEmail[$user_id] == "hykwong@stanford.edu") {
            util_send_message($arrUserEmail[$user_id],$subj, $body.$tail,'noreply@'.forge_get_config('web_host'),'','','',true);
	     }
		  
		 // update notification_sent field for user
	     db_begin();
	     $sql = "update users set notification_date = " . time() . " where user_id = '" . $user_id . "'";
	     $result_user = db_query_params($sql, array());				
	     db_commit();
      } // foreach arrUser
   } // if arrUser
   

  }
  
}  
// send message to admin  
//echo "Emails sent (Status: Monitor Only): " . $num_sent . "\n";
//echo "Num Users with notification on: " . $users_notif . "\n";

if (db_error()) {
	$err .= $sql.db_error();
}
$mess = "following news mail sent: " . $num_sent;
m_exit($mess);


function m_exit($mess = '') {
	global $err;

	if (!cron_remove_lock(__FILE__)) {
		$err .= "Could not remove lock\n";
	}
	if (!cron_entry(31,$mess.$err)) {
		// rely on crond to report the error
		echo "cron_entry error: ".db_error()."\n";
	}
	exit;
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
