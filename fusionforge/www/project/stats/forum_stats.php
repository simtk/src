<?php
/**
 * Project Forum Statistics Page
 *
 * Copyright 2003 GForge, LLC
 * Copyright 2010 (c) Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012-2013, Franck Villaume - TrivialDev
 * Copyright 2016-2020, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org/
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/forum_db_utils.php';
require_once $gfwww.'project/project_utils.php';

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

if (!$group->usesPlugin("phpBB")) {
	exit_error("To view forum statistics, first activate the Forums plugin through Admin->Tools->Enable/Disable", '');
}

// Retrieve forum id, which may be different from group_id.
// Send group's public name also, which may also be different from forum name.
$theForumId = lookupForumId($group_id, $group->getPublicName());

$params['toptab']='Project Statistics';
$params['group'] = $group_id;
$params['titleurl'] = '/project/stats/index.php?group_id='.$group_id;
site_project_header($params);

echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

// Create submenu under downloads_main DIV, such that it does not
// occupy the whole width of the page (rather than using the
// submenu population in Theme.class.php)
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuAttr = array();
$subMenuTitle["Title"] = "Statistics: Forum Statistics";
$subMenuUrl[] = '/project/stats/index.php?group_id=' . $group_id;

// Show the submenu.
echo $HTML->beginSubMenu();
echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, $subMenuAttr);
echo $HTML->endSubMenu();

// Retrieve forum statistics.
$arrUser = array();
$arrPostCntAllTime = array();
$arrPostCntLastMonth = array();
$arrTopicCntAllTime = array();
$arrTopicCntLastMonth = array();
getPostStats($theForumId, $arrUser, 
	$arrPostCntAllTime, $arrPostCntLastMonth, 
	$arrTopicCntAllTime, $arrTopicCntLastMonth);

// Display forum statistics.
displayPostStats($arrUser, 
	$arrPostCntAllTime, $arrPostCntLastMonth, 
	$arrTopicCntAllTime, $arrTopicCntLastMonth);


echo "</div><!--main_col-->\n";

// Add side bar to show statistics and project leads.
constructSideBar($group);

echo "</div><!--display table-->\n</div><!--project_overview_main-->\n";

site_project_footer(array());

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


// Display forum statistics.
function displayPostStats($arrUser, 
	$arrPostCntAllTime, $arrPostCntLastMonth, 
	$arrTopicCntAllTime, $arrTopicCntLastMonth) {

	echo "<table class='my-layout-table'>";
	echo "<tbody>";
	echo "<tr>";
	echo "<td>";

	echo "<div class='table-responsive'>";
	echo "<table class='table table-condensed table-bordered'>";
	echo "<tbody>";
	echo "<tr>" .
		"<th class='info'>User name</th>" .
		"<th class='info'>Posts<br/>(total)</th>" .
		"<th class='info'>Posts<br/>(previous 30 days)</th>" .
		"<th class='info'>Topics<br/>(total)</th>" .
		"<th class='info'>Topics<br/>(previous 30 days)</th>" .
		"</tr>";

	foreach ($arrUser as $id=>$name) {
		echo "<tr>";
		echo "<td>" . $name . "</td>";

		if (isset($arrPostCntAllTime[$id])) {
			echo "<td>" . $arrPostCntAllTime[$id] . "</td>";
		}
		else {
			echo "<td>0</td>";
		}
		// User may not have posted message last month. Check for entry.
		if (isset($arrPostCntLastMonth[$id])) {
			echo "<td>" . $arrPostCntLastMonth[$id] . "</td>";
		}
		else {
			echo "<td>0</td>";
		}

		if (isset($arrTopicCntAllTime[$id])) {
			echo "<td>" . $arrTopicCntAllTime[$id] . "</td>";
		}
		else {
			echo "<td>0</td>";
		}
		// User may not have posted topic last month. Check for entry.
		if (isset($arrTopicCntLastMonth[$id])) {
			echo "<td>" . $arrTopicCntLastMonth[$id] . "</td>";
		}
		else {
			echo "<td>0</td>";
		}
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
	echo "</div>";

	echo "</td>";
	echo "</tr>";
	echo "</tbody>";
	echo "</table>";
}



