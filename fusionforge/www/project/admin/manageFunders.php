<?php
/**
 * Manage Funder information for a SimTK project. This page is
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


// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

session_require_perm ('project_admin', $group_id);

$group->clearError();

$func = htmlspecialchars(getStringFromRequest('func'));
if($func == "delete_funder"){
    $funder_id = getIntFromRequest('funder_id');
    $sure = getIntFromRequest('sure');
	$really_sure = getIntFromRequest('really_sure');

	if (!$sure || !$really_sure) {
		$error_msg = 'Funder Information not deleted: you did not check "I am Sure" and "I am Really Sure"';
	}else{
        $res = db_query_params("SELECT funder_id from group_funderinfo where funder_id=$1 and group_id=$2", array($funder_id, $group_id));
        if (!$res) {
			$error_msg = "Funder Infromation not deleted: cannot read table";
		}
		else if (db_numrows($res) < 1) {
			$error_msg = "Funder Information not deleted: the funder does not exist";
		}else{
            db_begin();
            $res = db_query_params("DELETE FROM group_funderinfo where funder_id=$1 and group_id=$2",array($funder_id, $group_id));
            if (!$res || db_affected_rows($res) < 1) {
				$error_msg = "Error deleting Funder Information";
				db_rollback();
			}
			else {
				$feedback = "Funder Information Deleted";
				db_commit();
			}
        }
    }
}


project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

echo '<a class="btn-blue" ' .
				'href="/project/admin/addFunder.php?' .
				'group_id=' . $group_id .
				'">Add Funder Information</a><br/>';

$funders = $group->getFundersInfo();

echo '<div style="margin:25px 0 10px 0">';
foreach ($funders as $funder){
    echo '<div style="margin: 24px 0;  display:grid; grid-template-columns: .7fr 1fr">';
    echo '<div>'. htmlspecialchars($funder['funder_name']) .', ' . htmlspecialchars($funder['award_number']) . '<br> ' . htmlspecialchars($funder['award_title']) .'</div>';
    echo '<div style="display:inline;">';
    echo '<a class="btn-blue" ' .
						'href="/project/admin/updateFunder.php?' .
						'group_id=' . $group_id .
                        '&funder_id=' . $funder['funder_id'] .
						'">Update</a>';
	echo '&nbsp;';
					echo '<a class="btn-blue" ' .
						'href="/project/admin/deleteFunder.php?' .
						'group_id=' . $group_id .
                        '&funder_id=' . $funder['funder_id'] .
						'">Delete</a>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';



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
