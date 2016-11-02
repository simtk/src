<?php
/**
 *
 * mypage plugin index.php
 * 
 * Main index page for mypage which displays my projects, my news, my trending projects.
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
require_once $gfcommon.'include/User.class.php';
require_once $gfplugins.'mypage/www/mypage-utils.php';
require_once $gfplugins.'simtk_news/www/simtk_news_utils.php';


//require_once $gfplugins.'mypage/include/Mypage.class.php';

//$group_id = getStringFromRequest('group_id');
//session_require_perm ('project_read', $group_id) ;

define('NUM_PROJECTS_TO_SHOW', 10);

$title = _('My Page');

//$request =& HTTPRequest::instance();
//$request->set('group_id', $group_id);

if (session_loggedin()) {
    /*
	$group = group_get_object($group_id);
	if (!$group || !is_object($group)) {
		exit_no_group();
	} elseif ($group->isError()) {
		exit_error($group->getErrorMessage(), 'home');
	}
    */
        // get user
        $user = session_get_user(); // get the session user
        $username = $user->getUnixName();
		//get the user object based on the user_name in the URL
        $user = user_get_object_by_name($username);

        if (!$user || !is_object($user) || $user->isError() || !$user->isActive()) {
          exit_error(_('That user does not exist.'));
        }
        
	$HTML->header(array('title'=>$title));
}
else {
	// User is not logged in. Display log in page.
	exit_not_logged_in();
}
		 
html_use_jqueryui();


//site_project_header(array('title'=>$title, 'group'=>$group_id, 'toptab'=>'mypage'));

?>

<div class="project_overview_main">
    <div style="display: table; width: 100%;"> 
        <div class="main_col">

<?php

	$type = getStringFromRequest('type');
	$pluginname = getStringFromRequest('pluginname');
	
	
	// DO THE STUFF FOR THE MY PaGE HERE

	
	// add projects user is member of
	$i = 0;
	$cntUserProjects = 0;
	$userProjects = getUserProjects($user,$cntUserProjects);
	$arrNewsByProjects = array();
	$randomTrending = rand(1, $cntUserProjects);  // for trending
	
	$iTrending = 1;
    if ($cntUserProjects > 0) {	   
	   foreach ($userProjects as $proj) {
		  // get news for each project
		  if ($iTrending++ == $randomTrending) {
		    $group_idTrending = $proj['group_id'];
		  }
		  
		  $arrNews = getNewsByProject($proj['group_id']);
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
		  $arrNews = getNewsByProject($proj['group_id']);
		  foreach ($arrNews as $news) {
		    $arrNewsByProjects[$i++] = $news;
		  }
       }
	}
	
	
	echo '<div class="three_cols">';
	
	echo "<div class='two_third_col'>";
	
	echo '<h2 class="underlined">News</h2>';

	if (!empty($arrNewsByProjects)) {
		
		// sort News by date
		usort($arrNewsByProjects, function($a, $b) {
           return ($a['post_date'] > $b['post_date']) ? -1 : 1;
        });
		
        // display news
		$display_max = 10;
		$display_num = 1;
		foreach ($arrNewsByProjects as $news) {
			//echo $news['summary'] . "<br>";
			$tmpPicFile = $news['picture_file'];
			if ($tmpPicFile == "") {
				$tmpPicFile = "user_profile.jpg";
			}
			echo '<p>
			<div class="news_representation">
				<div class="news_img">
					<a href="/users/' . $news['user_name'] . '">
						<img ' .
						' onError="this.onerror=null;this.src=' . "'" . '/userpics/user_profile.jpg' . "';" . '"' .
						' alt="Image not available"' .
						' src="/userpics/' . $tmpPicFile .'">
					</a>
				</div><!--news_img-->
				<div class="wrapper_text">
					<h3><a href="/plugins/simtk_news/news_details.php?flag=2&group_id=' . 
						$news['group_id'] . 
						'&id='.$news['id'] . 
						'" style="color:#5e96e1;">' . 
						$news['summary'] . 
					'</a></h3>
					<small><a href="/projects/' . 
						$news['unix_group_name'] . 
						'/">' . 
						$news['group_name'] . 
						'</a> ' . 
						date(_('M d, Y'),$news['post_date']) . 
					'</small>
					<p>' . html_entity_decode(util_make_clickable_links(util_whitelist_tags($news['details']))) . '</p>
				</div><!-- class="wrapper_text" -->
			</div><!-- class="news_representation" -->
			</p>';
		   $display_num++;
		   if ($display_num > $display_max) {
		     break;
		   }
		}

	}
	else {
		echo "You have not submitted any news";
	}
	
	echo '</div>'."\n";
	
	echo "<div class='one_third_col'>";
	
	echo '<div class="myproject_box">';
	echo '<h2 class="underlined">My Projects</h2>';

	$cntUserProjects = 0;
	$userProjects = getUserProjects($user,$cntUserProjects,1);

	if ($cntUserProjects > 0) {
		$theSuffix = ".";
		if ($cntUserProjects > 1) {
			$theSuffix = "s.";
		}
		
		$toShow = NUM_PROJECTS_TO_SHOW;
		$numProjs = count($userProjects);
		if ($numProjs < $toShow) {
			// Have less projects than threshold. Set to threshold.
			$toShow = $numProjs;
		}
		// Display up to threshold.
		for ($cnt = 0; $cnt < $toShow; $cnt++) {
			$proj = $userProjects[$cnt];

			echo '<div class="myproject_representation">'."\n";
			if (!empty($proj['simtk_logo_file'])) {
				echo '<div class="myproject_img">' .
					'<a href="/projects/' . $proj['unix_group_name'] . '">' . 
					'<img src="/logos/'.$proj['simtk_logo_file'].'">' .
					'</a>' .
					'</div>'."\n";
			}
			else {
				echo '<div class="myproject_img">' .
					'<a href="/projects/' . $proj['unix_group_name'] . '">' . 
					'<img src="/logos/_thumb">' .
					'</a>' .
					'</div>'."\n";
			}
			echo '<div class="wrapper_text"><h4><a href="/projects/' . 
				$proj['unix_group_name'] . 
				'" style="color:#5e96e1;">' . 
				$proj['group_name'] . 
				'</a></h4></div>'."\n";
			echo '</div>'."\n"; // myproject_representation
		}

		if ($numProjs > $toShow) {
			// Has more projects to show than threshold. Start the extras section.
			// Add "See all"/"Show fewer".
			echo '<div class="related_link_trigger"><a href="#" onclick=$(".related_more").show();$(".related_link_trigger").hide();$(".related_link_close").show();return false;>See all</a></div>';
			echo '<div class="related_more" style="display:none">';
		}
		// Rest of the projects.
		for ($cnt = $toShow; $cnt < $numProjs; $cnt++) {
			$proj = $userProjects[$cnt];

			echo '<div class="myproject_representation">'."\n";
			if (!empty($proj['simtk_logo_file'])) {
				echo '<div class="myproject_img">' .
					'<a href="/projects/' . $proj['unix_group_name'] . '">' . 
					'<img src="/logos/'.$proj['simtk_logo_file'].'">' .
					'</a>' .
					'</div>'."\n";
			}
			else {
				echo '<div class="myproject_img">' .
					'<a href="/projects/' . $proj['unix_group_name'] . '">' . 
					'<img src="/logos/_thumb">' .
					'</a>' .
					'</div>'."\n";
			}
			echo '<div class="wrapper_text"><h4><a href="/projects/' . 
				$proj['unix_group_name'] . 
				'" style="color:#5e96e1;">' . 
				$proj['group_name'] . 
				'</a></h4></div>'."\n";
			echo '</div>'."\n"; // myproject_representation
		}

		if ($numProjs > $toShow) {
			// Has more projects to show than threshold. Finish the extras section.
			echo '</div><!-- related_more -->';
			echo '<div class="related_link_close" style="display:none">';
			echo '<a href="#" onclick=$(".related_more").hide();$(".related_link_trigger").show();$(".related_link_close").hide();return false;>Show fewer</a>';
			echo '</div><!-- related_link_close -->';
		}
	}
	else {
		echo "You are not a member of any projects";
	}

	echo '</div>'."\n"; 
	echo '<br/>';

	echo '<div>'."\n";
	echo '<div><a href="/register/" class="btn-blue share_text_button">New Project</a>';
	echo '</div>'."\n";
    
	
	echo '<div>'."\n";
	echo '<br><h2 class="underlined">Trending</h2>';

	/*
	$cat_id = getTroveCat($group_idTrending);
	//echo "group: " . $group_idTrending . "<br>";
	//echo "catid: " . $cat_id . "<br>";
	// random display from criteria below
	
	$cntTrendingProjects = 0;
	$limit = 2;
	$trendingProjects = getFollowingByCat($cntTrendingProjects,$cat_id,$limit);
	
	
    if ($cntTrendingProjects > 0) {
		
		foreach ($trendingProjects as $trending) {
		  //echo $proj['group_name'] . " (" . $proj['role_names'] . ")<br>";
		  //echo '<div><img src="/logos/'.$proj['unix_group_name'].'_thumb"></div> <h5><a href="/projects/' . $proj['unix_group_name'] . '" style="color:#5e96e1;">' . $proj['group_name'] . '</a></h5></div>';
          echo '<div class="trending_representation">';
		  echo '<div class="trending_img"><img src="/logos/'.$trending['unix_group_name'].'_thumb"></div>';
		  echo '<div class="wrapper_text"><h4><a href="/projects/' . $trending['unix_group_name'] . '" style="color:#5e96e1;">' . $trending['group_name'] . '</a></h4>';
		  echo '<small>' . number_format($trending['followers']) . ' total followers</small></div>';
          echo '</div>'."\n"; // class
		}
    }
    */
	
	$finalTrending = array();
	// TotalUsers
	$cntTrendingProjects = 0;
	$trendingProjects = getTopDownloads($cntTrendingProjects,2);
	//var_dump($trendingProjects);
	$res = finalTrending($finalTrending,$trendingProjects,"downloads");
	
	$cntTrendingProjects = 0;
	$trendingProjects = getTopDownloadsPastWeek($cntTrendingProjects,1);
	//var_dump($trendingProjects);
	$res = finalTrending($finalTrending,$trendingProjects,"downloads_past_week");
	
	$cntTrendingProjects = 0;
	$cat_id = 0;
	$trendingProjects = getTotalUsersProject($cntTrendingProjects,$cat_id,2);
	//var_dump($trendingProjects);
	$res = finalTrending($finalTrending,$trendingProjects,"members");
	
	$cntTrendingProjects = 0;
	$cat_id = 0;
	$trendingProjects = getFollowingByCat($cntTrendingProjects,$cat_id,4);
	//var_dump($trendingProjects);
	$res = finalTrending($finalTrending,$trendingProjects,"followers");
	
	//var_dump($finalTrending);
	
    if (!empty($finalTrending)) {
		
		foreach ($finalTrending as $trending) {
		     echo '<div class="trending_representation">';
		     if (!empty($trending['simtk_logo_file'])) {
		        echo '<div class="trending_img">' .
				'<a href="/projects/' . $trending['unix_group_name'] . '">' . 
				'<img src="/logos/'.$trending['simtk_logo_file'].'">' .
				'</a>' . 
				'</div>';
		     } else {
			    echo '<div class="trending_img">' .
				'<a href="/projects/' . $trending['unix_group_name'] . '">' . 
				'<img src="/logos/_thumb">' .
				'</a>' . 
				'</div>';
		     }
			 
			 echo '<div class="wrapper_text"><h4><a href="/projects/' . $trending['unix_group_name'] . '" style="color:#5e96e1;">' . $trending['group_name'] . '</a><br />';
		     echo '<small>' . number_format($trending['attribute_num']) . ' ' . $trending['attribute_type'] .'</small>';
             echo '</h4></div></div>'."\n"; // class
		  
		}
    }
	
	
	/*
	// TotalUsers
	$cntTrendingProjects = 0;
	//$trendingProjects = getTotalUserProject($cntTrendingProjects,$cat_id,$limit);
	$trendingProjects = getTopDownloads($cntTrendingProjects);
	
	$start = rand(1,15);
	$istart = 1;
    if ($cntTrendingProjects > 0) {
		
		foreach ($trendingProjects as $trending) {
		  //echo $proj['group_name'] . " (" . $proj['role_names'] . ")<br>";
		  //echo '<div><img src="/logos/'.$proj['unix_group_name'].'_thumb"></div> <h5><a href="/projects/' . $proj['unix_group_name'] . '" style="color:#5e96e1;">' . $proj['group_name'] . '</a></h5></div>';
          if ($istart++ == $start) {
		     echo '<div class="trending_representation">';
		     echo '<div class="trending_img"><img src="/logos/'.$trending['unix_group_name'].'_thumb"></div>';
		     echo '<div class="wrapper_text"><h4><a href="/projects/' . $trending['unix_group_name'] . '" style="color:#5e96e1;">' . $trending['group_name'] . '</a></h4>';
		     echo '<small>' . number_format($trending['downloads']) . ' total downloads</small></div>';
             echo '</div>'."\n"; // class
			 break;
		  }
		}
    }
     
	// TotalUsers
	$cntTrendingProjects = 0;
	//$trendingProjects = getTotalUserProject($cntTrendingProjects,$cat_id,$limit);
	$trendingProjects = getTopDownloads($cntTrendingProjects);
	
	$start = rand(1,5);
	$istart = 1;
    if ($cntTrendingProjects > 0) {
		
		foreach ($trendingProjects as $trending) {
		  //echo $proj['group_name'] . " (" . $proj['role_names'] . ")<br>";
		  //echo '<div><img src="/logos/'.$proj['unix_group_name'].'_thumb"></div> <h5><a href="/projects/' . $proj['unix_group_name'] . '" style="color:#5e96e1;">' . $proj['group_name'] . '</a></h5></div>';
          if ($istart++ == $start) {
		     echo '<div class="trending_representation">';
		     echo '<div class="trending_img"><img src="/logos/'.$trending['unix_group_name'].'_thumb"></div>';
		     echo '<div class="wrapper_text"><h4><a href="/projects/' . $trending['unix_group_name'] . '" style="color:#5e96e1;">' . $trending['group_name'] . '</a></h4>';
		     //echo '<small>' . number_format($start) . ' new followers this week</small></div>';
             echo '</div></div>'."\n"; // class
			 break;
		  }
		}
    }
	
	// TotalUsers
	$cntTrendingProjects = 0;
	//$trendingProjects = getTotalUserProject($cntTrendingProjects,$cat_id,$limit);
	$trendingProjects = getTopDownloads($cntTrendingProjects);
	
	$start = rand(1,6);
	$istart = 1;
    if ($cntTrendingProjects > 0) {
		
		foreach ($trendingProjects as $trending) {
		  //echo $proj['group_name'] . " (" . $proj['role_names'] . ")<br>";
		  //echo '<div><img src="/logos/'.$proj['unix_group_name'].'_thumb"></div> <h5><a href="/projects/' . $proj['unix_group_name'] . '" style="color:#5e96e1;">' . $proj['group_name'] . '</a></h5></div>';
          if ($istart++ == $start) {
		     echo '<div class="trending_representation">';
		     echo '<div class="trending_img"><img src="/logos/'.$trending['unix_group_name'].'_thumb"></div>';
		     echo '<div class="wrapper_text"><h4><a href="/projects/' . $trending['unix_group_name'] . '" style="color:#5e96e1;">' . $trending['group_name'] . '</a></h4>';
		     //echo '<small>' . number_format($start) . ' new members this week</small></div>';
             echo '</div></div>'."\n"; // class
			 break;
		  }
		}
    }
	*/
	
    echo '</div>'."\n";
	
	echo '</div>'."\n"; // 3 columns
?>

        </div>
    </div>
</div>



<?php

site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
