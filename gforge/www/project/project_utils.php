<?php

/**
 *
 * project_utils.php
 * 
 * Utility file to handle project display.
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
 
// This is the max number of characters per line tested 
// empirically on various platforms and browsers that
// would not cause text to overflow to next line.
define('NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE', 19);

define('NUM_PROJECTS_TO_SHOW', 8);
#define('NUM_PROJECTS_TO_SHOW', 9999);

require_once $gfwww.'include/forum_db_utils.php';
require_once $gfcommon.'mail/MailingListFactory.class.php';

require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'tracker/Artifact.class.php';
require_once $gfcommon.'tracker/ArtifactType.class.php';
require_once $gfcommon.'tracker/include/ArtifactTypeHtml.class.php';
require_once $gfcommon.'tracker/include/ArtifactHtml.class.php';
require_once $gfcommon.'tracker/ArtifactTypeFactory.class.php';
require_once $gfcommon.'tracker/include/ArtifactTypeFactoryHtml.class.php';

// Display 2 blocks of statistics.
function displayStatsBlock($groupObj) {

	if ($groupObj == null) {
		// Empty group object.
		return;
	}
	// Get group id.
	$group_id = $groupObj->getId();

	// Last project update time.
	// Use shorter format.
	$lastUpdated = $groupObj->getLastUpdate($group_id);
	//$lastUpdated = date('M d, Y', strtotime($lastUpdated));
	// Total downloads.
	$totalDownloads = number_format($groupObj->getTotalDownloads());
	// Forum posts.
	$forumPosts = getNumPostsByGroupId($group_id);

	// First block.
	echo '<div class="statbox">';

	// Check whether project uses downloads.
	if ($groupObj->usesFRS()) {
		// Total downloads.
		echo '<a style="color:#5e96e1;font-size:24px;font-weight:400;" href="/plugins/reports/index.php?type=group&group_id=' . $group_id . '&reports=reports">' . $totalDownloads . '</a>';
		echo '<p style="font-size:13px;line-height:17px;color:#a7a7a7;margin-top:-5px">downloads</p>';
	}
	
	if ($groupObj->usesPlugin("phpBB")) {
		// Uses forums.
		// Forum posts.
		echo '<a style="color:#5e96e1;font-size:24px;font-weight:400;" href="/plugins/phpBB/indexPhpbb.php?group_id=' . $group_id . '&pluginname=phpBB">' . $forumPosts . '</a>';
		echo '<p style="font-size:13px;line-height:17px;color:#a7a7a7;margin-top:-5px;">forum posts</p>';
	}

	$following = new Following($groupObj);
	if (!$following || !is_object($following)) {
	}
	elseif ($following->isError()) {
	}
	else {
		$result = $following->getFollowing($group_id);
		if (!$result) {
			$total_followers = 0;
		}
		else {
			// get public count
			$public_following_count = $following->getPublicFollowingCount($group_id);

			// get private count
			$private_following_count = $following->getPrivateFollowingCount($group_id);
			$total_followers = $public_following_count + $private_following_count;
		}
		if ($total_followers > 0) {
			echo '<a style="color:#5e96e1;font-size:24px;font-weight:400;" href="/plugins/following/index.php?group_id=' . $group_id . '">' . $total_followers . '</a>';
			echo '<p style="font-size:13px;line-height:17px;color:#a7a7a7;margin-top:-5px;">followers</p>';
		}
	}

	// Project last updated.
	if ($lastUpdated) {
		echo '<div style="font-size:13px;line-height:17px;color:#a7a7a7; margin-bottom:8px">Last updated<br/>' . $lastUpdated . '</div>';
	}

	// More statistics.
	// Linked to report page.
	echo '<div>';
	echo '<a style="font-size:13px;line-height:17px;color:#5e96e1;" href="/plugins/reports/index.php?type=group&group_id=' . $group_id . '&reports=reports">Project Statistics</a>';
	echo '</div>';

	echo "<div style='clear: both;'></div>";
	echo '</div>';

	// Second block.
	echo '<div style="margin:20px 10px 10px 0;min-height:106px;">';

	// Mailing list.
	if ($groupObj->usesMail()) {
	   $mlFactory = new MailingListFactory($groupObj);
	   $mlArray = $mlFactory->getMailingLists();
	   if (count($mlArray) != 0) {
	      echo '<div class="share_text" style="margin-bottom:6px;"><a class="btn-blue share_text_button" href="/mail/index.php?group_id=' . $group_id . '" style="width:158px;">Join Mailing Lists</a></div>';
       }
	}
	
	if ($groupObj->isPublic() && $groupObj->usesTracker()) {
		// Public project and uses tracker..
		// Display button for submitting to Suggest Ideas tracker.
		$arrAtNames = array();
		// Locate  all trackers in the group specified.
		$resAtNames = db_query_params('SELECT * FROM artifact_group_list_vw ' .
			'WHERE group_id=$1 ' .
			'ORDER BY group_artifact_id ASC',
			array($group_id));
		if ($resAtNames) {
			while ($row = db_fetch_array($resAtNames)) {

				$atid = $row['group_artifact_id'];
				$atname = $row['name'];

				if ($atname == "Suggested Ideas") {
					// Found it.
					// Add button for Suggest Idea to go to tracker.
					echo '<div class="share_text">' .
						'<a class="btn-blue share_text_button" ' .
						'href="/tracker?' .
						'atid=' . $atid . '&' .
						'group_id=' . $group_id . '&' .
						'func=add' .
						'" style="width:158px;">Suggest Idea</a></div>';
					break;
				}
			}
		}
	}

	echo "<div style='clear: both;'></div>";
	echo '</div>';
}

function displayCarouselProjectLeads($project_admins, $max_leads_per_slide=3) {

           $num_leads = count($project_admins);
           if ($num_leads <= $max_leads_per_slide) {
             // no carousel needed
             foreach ($project_admins as $admin_obj) {
                //print_r($admin_obj);
                //var_dump($admin_obj);
                //var_dump($admin_obj->data_array);
               
                echo '<div class="team_member">';
                echo '<a href="/users/' . $admin_obj->data_array['user_name'] . '"><img src="/userpics/';
                if (!empty($admin_obj->data_array['picture_file'])) { echo $admin_obj->data_array['picture_file'] . ""; } else { echo "user_profile.jpg"; } 
                echo '" ' .
			' onError="this.onerror=null;this.src=' . "'" . '/userpics/user_profile.jpg' . "';" . '"' .
			' alt="Image not available" /></a>';
                echo "<br/>";
                echo '<a href="/users/' . $admin_obj->data_array['user_name'] . '">' . $admin_obj->data_array['realname'] . '</a>';
                echo "</div> <!-- /.team_member -->";

             } // foreach
           } else {
             // carousel needed since more than 1 slide needed
             // calculate number of slides
             $slides = getNumberSlides($num_leads, $max_leads_per_slide);
             //echo "slides: " . $slides . "<br />";
             //echo "leads: " . $num_leads . "<br />";


             echo '<div id="myCarousel" class="carousel slide" data-ride="carousel" data-interval="false">';
             echo '<div class="carousel-inner" role="listbox">';

             $i = 0;
             $j = 0;
             foreach ($project_admins as $admin_obj) {
               if ($i == 0) {
                 echo '<div class="item active">';
               } else if ($i == $max_leads_per_slide) {
                 echo '<div class="item">';
                 $i = 0;
               }
               $i++;
               $j++;

               echo '<div class="team_member">';
               echo '<a href="/users/' . $admin_obj->data_array['user_name'] . '"><img src="/userpics/';
               if (!empty($admin_obj->data_array['picture_file'])) { echo $admin_obj->data_array['picture_file'] . ""; } else { echo "user_profile.jpg"; } 
                echo '" ' .
			' onError="this.onerror=null;this.src=' . "'" . '/userpics/user_profile.jpg' . "';" . '"' .
			' alt="Image not available" /></a>';
                echo "<br/>";
                echo '<a href="/users/' . $admin_obj->data_array['user_name'] . '">' . $admin_obj->data_array['realname'] . '</a>';
                echo "</div> <!-- team_member -->";


              if ($i == $max_leads_per_slide || $j == $num_leads) {
               echo "</div> <!-- item-lead -->";
              }

              } // foreach

               echo "</div> <!-- carousel-inner -->";
                // Indicators 
               echo "<br /><br /><br />";
               echo '<ol class="carousel-indicators">';
               echo '<li data-target="#myCarousel" data-slide-to="0" class="active"></li> ';

               for ($slide_i = 1; $slide_i < $slides; $slide_i++) {
                    echo '<li data-target="#myCarousel" data-slide-to="' . $slide_i . '"></li> ';
               } 
               echo "</ol>";
               echo "</div> <!-- carousel slide -->";

           }




}

// Generate section of community (if present).
function genCommunityInfo($groupObj) {

	$retStr = "";
	$arrCommunities = array();

	if ($groupObj == null) {
		return $retStr;
	}

	// Look up category/group info.
	$troveCatLinkArr = $groupObj->getTroveGroupLink();

	// Get project communities.
	// NOTE: trove_cat value of 1000 is used as parent.
	$resultCommunities = db_query_params('SELECT fullname, trove_cat_id, ' .
		'description, simtk_intro_text FROM trove_cat ' .
		'WHERE parent=1000 ' .
		'ORDER BY fullname',
		array());
	if (db_numrows($resultCommunities) <= 0) {
		// No project communities found.
		return $retStr;
	}
	// Look up communities that this project belongs to.
	while ($row = db_fetch_array($resultCommunities)) {
		if (isset($troveCatLinkArr[$row['trove_cat_id']]) &&
			$troveCatLinkArr[$row['trove_cat_id']]) {
			// Found a community that this project belongs to.
			// Get description of community.
			$arrCommunities[$row['trove_cat_id']] = $row['fullname'];
		}
	}

/*
	// Disabled for now to not show Communities section in Project Overview page.
	if (count($arrCommunities) > 0) {
		// Has community.
		$retStr = '<h2>Communities</h2>';
		foreach ($arrCommunities as $cat_id=>$fullName) {
			// Create link.
			$retStr .= '<h4><a href="' .
				'/category/communityPage.php?cat=' . $cat_id .
				'&sort=date&page=0&srch=&' .
				'">' . $fullName . '</a></h4>';
		}
	}
*/

	return $retStr;
}

function displayRecommendedProjects($groupObj, $rec_projects, $max_recs_per_slide=3) {

	$numRecs = count($rec_projects);

	if ($numRecs <= 0) {
		return;
	}

	$resStr = '';

	$resStr .= '<div class="related_group">';
	$resStr .= genCommunityInfo($groupObj);
	$resStr .= "<h2>People also viewed</h2>";

	if ($numRecs > 0 && $numRecs <= NUM_PROJECTS_TO_SHOW) {

		// "See all" not needed.

		foreach ($rec_projects as $rec_project) {
			$resStr .= genProjectRecDisplay($rec_project);
		}
	}
	else {

		// "See all" needed.

		for ($cnt = 0; $cnt < NUM_PROJECTS_TO_SHOW; $cnt++) {
			$resStr .= genProjectRecDisplay($rec_projects[$cnt]);
		}

		// "See all".
		$resStr .= "<div class='related_link'>";
		$resStr .= '<h2><a href="#" onclick="' . 
				'$(' . "'.related_more').show();" . 
				'$(' . "'.related_link').hide();" .
				'return false;' . 
			'">See all</a></h2>';
		$resStr .= "</div>";

		$resStr .= "<div class='related_more' style='display:none'>";
		// The rest of the recommended items.
		for ($cnt = NUM_PROJECTS_TO_SHOW; $cnt < $numRecs; $cnt++) {
			$resStr .= genProjectRecDisplay($rec_projects[$cnt]);
		}
		$resStr .= "</div><!-- related_more -->";
	}

	$resStr .= "</div> <!-- related_group -->";

	// Display string.
	echo $resStr;
}


// Generate display string for project record.
function genProjectRecDisplay($projRec) {

	$res = '';
	$res .= '<div class="related_item">';

	if (!empty($projRec['simtk_logo_file'])) {
		$res .= "<div class='related_thumb'>";
		$res .= "<a href='/projects/" . $projRec['unix_group_name'] . "'>" .
			"<img " .
			' onError="this.onerror=null;this.src=' . "'" . '/logos/_thumb' . "';" . '"' .
			" alt='Image not available'" .
			" src='/logos/" . $projRec['simtk_logo_file'] . 
			"'></img></a>";
		$res .= "</div>";
	}
	else {
		$res .= "<div class='related_thumb'>";
		$res .= "<a href='/projects/" . $projRec['unix_group_name'] . "'>" .
			"<img " .
			' onError="this.onerror=null;this.src=' . "'" . '/logos/_thumb' . "';" . '"' .
			" alt='Image not available'" .
			" src='/logos/_thumb'></img></a>";
		$res .= "</div>";
	}
	$res .= "<div class='related_text'>";

	// Create a 3-line version of the group name,
	// truncating if the name is too long.
	$theGroupName = $projRec['group_name'];
	$theGroupName = genDisplayGroupName($theGroupName);
	$res .= "<a href='/projects/" . 
		$projRec['unix_group_name'] . "/'>" . 
		$theGroupName . 
		"</a>";
	// related_text.
	$res .= "</div>";
	// related_item
	$res .= "</div>";

	return $res;
}

function displayRelatedProjects($related_projects, $max_related=6) {

	if (db_numrows($related_projects) > 0) {
		echo "<h2>Related Projects</h2>";
		echo "<p>The project owner recommends the following other projects:</p>";
		//print_r ($related_projects);
		while ($project = db_fetch_array($related_projects)) {
			echo '<h4><a href="/projects/'. $project['unix_group_name'] . '">' . $project['group_name'] . '</a>';
		}
	} 
}


// Generate a 3-line version of the group name for display,
// truncating if the name is too long, appended with "...".
function genDisplayGroupName($inGroupName) {

	$resStr = "";

	// Add "<br/>\n" to separate each line in the group name.
	$splitStr = wordWrap($inGroupName, NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE, "<br/>\n");

	// Split into array of strings for each line.
	// Keep only up to 3 lines.
	$arrStr = explode("<br/>\n", $splitStr);
	$numLines = count($arrStr);
	$isTruncated = false;
	$lastLine = "";
	for ($cnt = 0; $cnt < min($numLines, 3); $cnt++) {
		$theLine = $arrStr[$cnt];

		// Check length of this line.
		if (strlen($theLine) >= NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE) {
			// Has a long line.

			if ($cnt < 2) {
				// Merge with next line. 
				// Do not insert "<br/>\n" to end of this line.
				$resStr .= $theLine . " ";
			}
			else {
				// Do not include this third line because it is long
				// and may not fit.
				$isTruncated = true;
				// Keep this last line for further processing later.
				$lastLine = $theLine;
			}
		}
		else {
			// Insert "<br/>\n" to end of this line.
			$resStr .= $theLine . "<br/>\n";
		}
	}

	// Check if there is string in the next position of the array.
	if (isset($arrStr[$cnt]) || $isTruncated) {
		// There is more in the group name string.
		// Add "..." to the display.

		// NOTE: If there is a long string on line 3, this last string
		// will not have "<br/>\n" appended at the end; hence, the display
		// display will stop at the end of the second line.
		// This third line should not be shown because there is not enough
		// room to hold the long string.

		// Find last "<br/>\n".
		$idx = strrpos($resStr, "<br/>\n");
		if ($idx !== FALSE) {
			$resStr = substr($resStr, 0, $idx);
		}

		if ($isTruncated) {
			// There is a last line which has been truncated and 
			// it has not yet been added to the third line.
			// If there are word breaks in this last line, try to 
			// add part of the line by looking at word breaks.
			// Add 1 character to account of " ".
			$remainStr = wordWrap($lastLine, NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE, "<br/>\n", false);

			$idx = strrpos($remainStr, "<br/>\n");
			if ($idx !== FALSE) {
				$endStr = substr($remainStr, 0, $idx);
				$resStr .= " " . $endStr;
			}
			else {
				// <= NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE.
				$resStr .= " " . $lastLine;
			}
		}

		$resStr .= "...<br/>\n";
	}

	return $resStr;

/*
	// OLD

	// Generate Line 1.
	$line1 = substr($inGroupName, 0, NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE);
	$lengthLine1 = strlen($line1);
	$lastIdx = getLastDelimiter($line1);
	if ($lastIdx !== FALSE) {
		// Shorten line 1 to the last index.
		$line1 = substr($line1, 0, $lastIdx + 1);
		$lengthLine1 = strlen($line1);
	}
	// Check remaining characters.
	$remaining = substr($inGroupName, $lengthLine1);
	if (($lengthLine1 + strlen($remaining)) <= NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE) {
		// Done. Title fits in 1 line.
		return $line1 . $remaining;
	}

	// Generate Line 2.
	$line2 = substr($inGroupName, $lengthLine1, NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE);
	$lengthLine2 = strlen($line2);
	$lastIdx = getLastDelimiter($line2);
	if ($lastIdx !== FALSE) {
		// Shorten line 2 to the last index.
		$line2 = substr($line2, 0, $lastIdx + 1);
		$lengthLine2 = strlen($line2);
	}
	// Check remaining characters.
	$remaining = substr($inGroupName, $lengthLine1 + $lengthLine2);
	if (($lengthLine2 + strlen($remaining)) <= NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE) {
		// Done. Title fits in 2 lines.
		return $line1 . $line2 . $remaining;
	}

	// Generate Line 3.
	// Leave 3 characters for "..."
	$line3 = substr($inGroupName, $lengthLine1 + $lengthLine2, 
		NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE - 3);
	$lengthLine3 = strlen($line3);
	$lastIdx = getLastDelimiter($line3);
	if ($lastIdx !== FALSE) {
		// Shorten line 3 to BEFORE the last index.
		$line3 = substr($line3, 0, $lastIdx);
		$lengthLine3 = strlen($line3);
	}
	// Check remaining characters.
	$remaining = substr($inGroupName, $lengthLine1 + $lengthLine2 + $lengthLine3);
	if (($lengthLine3 + strlen($remaining)) <= NUM_CHARS_IN_TITLE_TO_SHOW_PER_LINE) {
		// Done. Title fits in 3 lines.
		return $line1 . $line2 . $line3 . $remaining;
	}

	// Title is longer than 3 lines. Add "..."
	return $line1 . $line2 . $line3 . "...";
*/

}


// Get position of the last delimiter.
function getLastDelimiter($inLine) {
	// Default: check for space.
	$lastIndex = strrpos($inLine, " ", 0);
	$lastHyphen = strrpos($inLine, "-", 0);
	if ($lastHyphen > $lastIndex) {
		// Use position of "-" instead.
		$lastIndex = $lastHyphen;
	}

	return $lastIndex;
}

