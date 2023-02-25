<?php
/**
 * View Funder information for a SimTK Project.
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/project_utils.php';

$group_id = getIntFromRequest('group_id');

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$group->clearError();

site_project_header(array('title'=>_('Project Funder List'),'group'=>$group_id,'toptab'=>'Funder List'));

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";


$funders = $group->getFundersInfo();
$funders_count = 0;
if($funders) {
    $funders_count = count($funders);
}
echo '<div>';
echo '<h2>Funder Information </h2>';
if($funders_count > 0){
    echo '<div style="margin: 24px 0;">';
    echo 'This project is funded by ' . $funders[0]['funder_name'] .
    ' ' . $funders[0]['award_number'] . ' ' . $funders[0]['award_title'];
    for($i = 1; $i < $funders_count; $i++){
        echo ", " .  $funders[$i]['funder_name'] .
        ' ' . $funders[$i]['award_number'] . ' ' . $funders[$i]['award_title'];
    }
    echo '</div>';

    echo '<div>';
    foreach($funders as $funder){
        if($funder['funder_description']){
            echo '<p> ' . $funder['funder_description'] . '</p>';
        }
    }
    echo '</div>';

}else{
    echo '<p>No funder information has been added for this project.';
}
echo '</div>';

echo "</div><!--main_col-->\n";

constructSideBar($group);

echo "</div><!--display table-->\n</div><!--project_overview_main-->\n";



site_project_footer(array());


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
