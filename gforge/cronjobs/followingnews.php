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

$all_users=db_query_params ('Select * FROM users where notification_frequency > 0',array());

$subj = "Updates from the SimTK projects you follow";

$max = 0;
$num_sent = 0;
$users_notif = 0;
$today = date("Y-m-d");
echo "begin cronjob....\n";
while ($users = db_fetch_array ($all_users)) {
    $users_notif++;
    // if notification set - this check is performed in query above.
    // if > 0, then get list of projects user belongs to
    //echo "user: " . $users['user_name'] . "\n";

    //$compare_date = new DateTime(date("Y-m-d H:i", strtotime( '-' . $users['notification_frequency'] . ' days' ) ));
    //$compare_date = new DateTime(date("Y-m-d"));
	$compare_date = strtotime("-".$users['notification_frequency']. " days");
	//echo "compare date: " . $compare_date . "\n";
	//echo "compare date format: " . date("Y-m-d", $compare_date) . "\n";
	
	$send_email = 0;
        // if notification_date is 0 then automatically send email. This will be users initial setting.
	if (empty($users['notification_date'])) {
	  // default to notification_frequency minus today's date
	  $send_email = 1;	  
	} else {
	  // check to see if it's time to send email
	  //if today's date minus frequency equal to last date sent, then send email	
      //echo "today-frequency: " . date("Y-m-d",$compare_date) . "\n";
      //echo "notification date: " . date("Y-m-d",$users['notification_date']) . "\n";	  
	  if (date("Y-m-d",$compare_date) == date("Y-m-d",$users['notification_date'])){
	    $send_email = 1;
	  }
	}

   if ($send_email) {
	echo "\n---------------";	
    echo "User: " . $users['user_name'] . "\n";
    echo "Compare date: " . $compare_date . "\n";
    // add projects user is member of
    $i = 0;
    $cntUserProjects = 0;
    //get the user object based on the user_name in the URL
    $user = user_get_object_by_name($users['user_name']);
	$userProjects = getUserProjects($user,$cntUserProjects);
	$arrNewsByProjects = array();

	// get list of news, project member of and following projects
    if ($cntUserProjects > 0) {	   
	   foreach ($userProjects as $proj) {
		  // get news for each project
		  
		  $arrNews = getNewsByProject($proj['group_id'],0);
		  //echo "groupid: " . $proj['group_id'] . "\n";
		  foreach ($arrNews as $news) {
		    $arrNewsByProjects[$i++] = $news;
		  }
       }
	}
	
	// check projects user is following
	$cntProjectsFollowing = 0;
    $projectsFollowing = getProjectsFollowing($user,$cntProjectsFollowing);
	
    if ($cntProjectsFollowing > 0) {
	   // index $i should be set above
	   foreach ($projectsFollowing as $proj) {
		  // get news for each project
		  $arrNews = getNewsByProject($proj['group_id'],0);
		  foreach ($arrNews as $news) {
		    $arrNewsByProjects[$i++] = $news;
		  }
       }
	}
	
	// get news date, compare to today's date
	$arrNewsSend = array();
	$i = 0;
        $body_news = "";	
	foreach ($arrNewsByProjects as $news) {
	   $news_date = date("Y-m-d H:i" ,$news['post_date']);
	   //echo "news date: " . strtotime($news_date) . "\n";
	   // if notification freq less than diff then include news to send
	   if ($compare_date < strtotime($news_date)) {
	      // Format HTML News Page to send out
		  $tmpPicFile = $news['picture_file'];
		  if ($tmpPicFile == "") {
			$tmpPicFile = "user_profile.jpg";
		  }
		  $body_news .= '<tr><td width="45" valign="top"> <a href="'.util_make_url('/users/') . $news['user_name'] . '"><img height="40" width="40" src="'.util_make_url('/userpics/') . $tmpPicFile .'"></a></td>';
		  $body_news .= '<td width="500" valign="top"> <strong><a href="'.util_make_url('/plugins/') . 'simtk_news/news_details.php?flag=2&group_id=' . 
						$news['group_id'] . 
						'&id='.$news['id'] . 
						'" style="color:#5e96e1;">' . 
						$news['summary'] . 
					'</a></strong><br />
					<small><a href="'.util_make_url('/projects/') . 
						$news['unix_group_name'] . 
						'/">' . 
						$news['group_name'] . 
						'</a> ' . 
						date(_('M d, Y'),$news['post_date']) . 
					'</small></td>';
		  $body_news .= "</tr>";
		  // store information in an array for later use
		  $arrNewsSend[$i++] = $news;
	   }
	}
        if ($arrNewsSend) {
	   $body = "<p><b>Here's the latest news from the SimTK projects you are following:</b></p><br />";
	   $body .= '<table cellpadding="5" cellspacing="5">';
           $body .= $body_news;
	   $body .= "</table>";
        }
	
	// get related projects
	$allTroveCat = array();
	foreach ($projectsFollowing as $proj) {
		  // get news for each project
		  $troveCategories = getTroveCategories($proj['group_id']);
		  //print_r($troveCategories);
		  $allTroveCat = array_merge($troveCategories,$allTroveCat);
    }
	
	// get most common category
	$c = array_count_values($allTroveCat);
	if (!empty($c)) {
	   $commonCat = array_search(max($c),$c);
	   $cntProjectsByCat = 0;
	   echo "Common Category: " . $commonCat . "\n";
	   $resultsProjectsCat = getProjectsByCat($cntProjectsByCat,$commonCat,$compare_date);
	   // add content to body
	   if ($resultsProjectsCat) {
	      $max = db_numrows($resultsProjectsCat);
	      if ($max > 0) {
	         $body .= "<br /><br /><p><b>Here are related SimTK projects recently created:</b></p><br />";
	         $body .= "<table>";
	      }
	      for ($i = 0; $i < $max; $i++) {
	         $tempLogo = db_result($resultsProjectsCat, $i, 'simtk_logo_file');
	         if (!empty($tempLogo)) {
		        $body .= '<tr><td width="45" valign="top"><img height="40" width="40" src="'.util_make_url('/logos/') . db_result($resultsProjectsCat, $i, 'simtk_logo_file') .'"></td>';
		     } else {
		        $body .= '<tr><td width="45" valign="top"><img height="40" width="40" src="'.util_make_url('/logos/_thumb') .'"></td>';
		     }
		  
		  $body .= '<td width="500" valign="top"> <strong><a href="'.util_make_url('/projects/') . db_result($resultsProjectsCat, $i, 'unix_group_name') . 
						'" style="color:#5e96e1;">' . db_result($resultsProjectsCat, $i, 'group_name') . '</a></strong><br /><small>' . 
						db_result($resultsProjectsCat, $i, 'simtk_summary') . '</small></td>';
		  $body .= "</tr>";
	   }
	   if ($max > 0) {
	      $body .= "</table>";
	   }
	 } // if empty $c
	}
	
	// create footer
	$tail = "<br />==================================================================<br />" ;
	$tail .= 'You received this message because you are following projects on ' . forge_get_config ('forge_name') . '. You may opt out or change the frequency of the news you receive by logging in to ' . forge_get_config ('forge_name') . ' and visiting your <a href="'.util_make_url('/account/settings.php/').'">Account Settings Page</a>.';
		
	// send news.
	if ($arrNewsSend || $max > 0) {
       util_send_message($user->getEmail(),$subj, $body.$tail,'noreply@'.forge_get_config('web_host'),'','','',true);
	   echo "Email Sent to User: " . $user->getEmail() . "\n";
	   $num_sent++;
	}

	// update notification_sent field for user - regardless if any new news items or not
	db_begin();
	$sql = 'update users set notification_date = ' . time() . ' where user_id = ' . $users['user_id'];
	$result_user = db_query_params($sql, array());				
	db_commit();
					
	sleep($SLEEP);
	
   } // if send email
}  // while
  
  
  
// send message to admin  
echo "Emails sent: " . $num_sent . "\n";
echo "Num Users with notification on: " . $users_notif . "\n";

if (db_error()) {
	$err .= $sql.db_error();
}
$mess = "following news mail sent";
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
