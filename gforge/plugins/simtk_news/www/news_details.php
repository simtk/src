<?php

/**
 *
 * news plugin news_details.php
 * 
 * Utility file for news details
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
require_once 'simtk_news_utils.php';
//require_once $gfplugins.'simtk_news/include/Simtk_news.class.php';


	$user = session_get_user(); // get the session user

/*
	if (!$user || !is_object($user) || $user->isError() || !$user->isActive()) {
		exit_error("Invalid User", "Cannot Process your request for this user.");
	}
*/

$group_id = getIntFromRequest('group_id');
$id = getIntFromRequest('id');

news_header(array('title'=>_('News')),$group_id);

echo "<div class=\"project_overview_main\">";
echo "<div style=\"display: table; width: 100%;\">";
echo "<div class=\"main_col\">";

// Add link for news page to return to.
$flag = getIntFromRequest('flag');
if ($flag == 1) {
	echo "<a href='/#news'>Return to SimTK home page</a><br/>";
}
else if ($flag == 0) {
	echo "<a href='/plugins/simtk_news/index.php?" .
		"group_id=" . $group_id .
		"&pluginname=simtk_news'>Return to project news page</a><br/>";
}
else if ($flag == 2) {
	echo "<a href='/plugins/mypage'>Return to My Page</a><br/>";
}
else if ($flag == 3) {
	echo "<a href='/projects/" .
		group_getunixname($group_id) .
		"#news'>Return to project home page</a><br/>";
}
else if ($flag == 4) {
	$catId = getIntFromRequest('cat');
	echo "<a href='/category/category.php?" .
		"cat=" . $catId .
		"'>Return to cateogry page</a><br/>";
}

$result = db_query_params ('SELECT * FROM users,plugin_simtk_news WHERE users.user_id = plugin_simtk_news.submitted_by and id=$1', array ($id));

$rows=db_numrows($result);


if ($rows < 1) {
        if ($group_id) {
                echo '<p class="information">'.sprintf(_('No News Found for %s'),group_getname($group_id)).'</p>';
        } else {
                echo '<p class="information">'._('No News Found').'</p>';
        }
        echo db_error();
} else {
        //echo news_show_latest($group_id,10,true,false,false,-1,false);

  echo "<br />" . date(_('M d, Y'),db_result($result,0,'post_date')) . "<br />";
  echo "<h3>" . db_result($result,0,'summary') . "</h3>";
  echo "<p>By " . db_result($result,0,'realname') . "</p>";
  //echo "<p>" . html_entity_decode(nl2br(db_result($result,0,'details'))) . "</p>";
  echo "<p>" . html_entity_decode(nl2br(util_make_clickable_links(util_whitelist_tags(db_result($result,0,'details'))))) . "</p>";

}

echo "</div></div></div>";

news_footer(array());

