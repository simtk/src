<?php

/**
 *
 * news plugin index.php
 * 
 * Main news index file for displaying news belonging to project.
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
require_once $gfwww.'project/project_utils.php';


	$user = session_get_user(); // get the session user

/*
	if (!$user || !is_object($user) || $user->isError() || !$user->isActive()) {
		exit_error("Invalid User", "Cannot Process your request for this user.");
	}
*/

$group_id = getIntFromRequest('group_id');
$limit = getIntFromRequest('limit');
$offset = getIntFromRequest('offset');

// Check permission and prompt for login if needed.
session_require_perm('project_read', $group_id);

news_header(array('title'=>_('News')),$group_id);

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

$params['titleurl']='/plugins/simtk_news/?group_id='.$group_id;
$params['toptab']='news';
$params['group']=$group_id;

// Create submenu under project_overview_main DIV, such that it does not
// occupy the whole width of the page (rather than using the
// submenu population in Theme.class.php)
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuTitle[] = _('View News');
$subMenuUrl[]='/plugins/simtk_news/?group_id=' . $group_id;
if (session_loggedin()) {
	$group = group_get_object($group_id);
	if ($group && is_object($group) && !$group->isError()) {
		if (forge_check_perm ('project_admin', $group_id)) {
			// Check permission before adding administrative menu items.
			$subMenuTitle[]='Add News';
			$subMenuUrl[]='/plugins/simtk_news/submit.php?group_id=' . $group_id;
			$subMenuTitle[]=_('Administration');
			$subMenuUrl[]='/plugins/simtk_news/admin/?group_id=' . $group_id;
		}
	}
}

// Show the submenu.
//echo $HTML->beginSubMenu();
//echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
//echo $HTML->endSubMenu();

/*
        Put the result set (list of forums for this group) into a column with folders
*/
if ( !$group_id || $group_id < 0 || !is_numeric($group_id) ) {
        $group_id = 0;
}
if ( !$offset || $offset < 0 || !is_numeric($offset) ) {
        $offset = 0;
}
if ( !$limit || $limit < 0 || $limit > 50 || !is_numeric($limit) ) {
        $limit = 50;
}

if ($group_id && ($group_id != forge_get_config('news_group'))) {
        $result = db_query_params ('SELECT * FROM plugin_simtk_news WHERE group_id=$1 AND is_approved <> 4 ORDER BY post_date DESC',
                                   array ($group_id),
                                   $limit+1,
                                   $offset);
} else {
        $result = db_query_params ('SELECT * FROM plugin_simtk_news WHERE is_approved=1 ORDER BY post_date DESC',
                                   array ());
}

$rows=db_numrows($result);
$more=0;
if ($rows>$limit) {
        $rows=$limit;
        $more=1;
}

if ($rows < 1) {
        if ($group_id) {
                echo '<p class="information">'.sprintf(_('No News Found for %s'),group_getname($group_id)).'</p>';
        } else {
                echo '<p class="information">'._('No News Found').'</p>';
        }
        echo db_error();
} else {
        //echo "<p><a href=''>RSS Feed</a></p>";
        echo news_show_latest($group_id,50,true,false,false,-1,false);
}

echo "</div><!--main_col-->\n";

// Add side bar to show statistics and project leads.
$group = group_get_object($group_id);
constructSideBar($group);

echo "</div><!--display table-->\n</div><!--project_overview_main-->\n";

news_footer(array());


// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	echo '</div>';
}

