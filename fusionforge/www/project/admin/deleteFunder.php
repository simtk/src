<?php
/**
 * Delete Funder information to a SimTK project. This page is
 * only visible to admins of a SimTK project.
 *
 * Copyright 2005-2023, SimTK Team
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
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfwww.'project/project_utils.php';

$group_id = getIntFromRequest('group_id');
$funder_id = getIntFromRequest('funder_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

if (!session_loggedin()) {
	exit_not_logged_in();
}

$group->clearError();


project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

// Get funder info.
$strQuery = 'SELECT award_title FROM group_funderinfo ' .
	'WHERE funder_id=$1 and group_id=$2;';
$res = db_query_params($strQuery, array($funder_id, $group_id));
$rows = db_numrows($res);
if ($rows != 1) {
	// Cannot find funder.
	exit_error('Cannot find Funder information', 'datashare');
}

echo '<div><h3>' . db_result($res, 0, 'award_title') . '</h3></div>';
echo '<form action="/project/admin/manageFunders.php?' .
	'group_id=' . $group_id .
	'" method="post">';
echo '<input type="hidden" name="func" value="delete_funder" />';
echo '<input type="hidden" name="funder_id" value="' . $funder_id . '" />';
echo 'You are about to permanently and irretrievably delete this Funder!';
echo '<p>';
echo '<input type="checkbox" name="sure" value="1" />&nbsp;I am Sure<br/>';
echo '<input type="checkbox" name="really_sure" value="1" />&nbsp;I am Really Sure<br/>';
echo '<br/><input type="submit" name="submit" value="Delete Funder" class="btn-cta" />';
echo '</p>';
echo '</form>';




echo "</div><!--main_col-->\n";

constructSideBar($group);

echo "</div><!--display table-->\n</div><!--project_overview_main-->\n";



project_admin_footer(array());


// Retrieve the error message and names of components to flag.
function retrieveErrorMessages($error_msg, &$arrErrors) {

    // Error messages are separated using "^" as delimiter.
    $arrErrors = explode("^", $error_msg);

    // The error message is the last token.
    // Note: "^" can be not present in the string; i.e. "".
    $error_msg = $arrErrors[count($arrErrors) - 1];

    return $error_msg;
}

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
