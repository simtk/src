<?php
/**
 * Update Funder information for a SimTK project. This page is
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

session_require_perm ('project_admin', $group_id) ;
$group->clearError();


$func = htmlspecialchars(getStringFromRequest('func'));
if ($func == "update_funder") {
    $funder_name = htmlspecialchars(trim(getStringFromRequest('funder_name')));
    $award_title = htmlspecialchars(trim(getStringFromRequest('award_title')));
    $award_number = htmlspecialchars(trim(getStringFromRequest('award_number')));
    $funder_description = htmlspecialchars(trim(getStringFromRequest('funder_description')));

    if($funder_name == "" ){
        $error_msg = "Funder information not updated. Please fill in the required fields.";
    }else{
        $res = db_query_params("SELECT funder_id from group_funderinfo WHERE " .
        "funder_name=$1 AND " .
        "award_title=$2 AND " .
        "award_number=$3 AND " .
        "funder_description=$4 AND " .
        "group_id=$5 AND " .
        "funder_id=$6;",
        array(
            $funder_name,
            $award_title,
            $award_number,
            $funder_description,
            $group_id,
            $funder_id
        ));
        if (!$res) {
			$error_msg = "Funder Information not updated: cannot read table";
		}else if(db_numrows($res) >= 1){
            $error_msg = "Funder Information already updated";
            session_redirect('/project/admin/manageFunders.php' .
				'?group_id=' . $group_id);
        }else{
            db_begin();
            $res = db_query_params("UPDATE group_funderinfo SET " .
            "funder_name=$1," .
            "award_title=$2,". 
            "award_number=$3,".
            "funder_description=$4 " . 
            "WHERE funder_id=$5 AND group_id=$6;",
            array(
                $funder_name,
                $award_title,
                $award_number,
                $funder_description,
                $funder_id,
                $group_id
            ));
            if(!$res || db_affected_rows($res) < 1){
                db_rollback();
                $error_msg = "Error updating Funder Information entry.";
            }else{
                db_commit();
                $feedback = "Funder Information Updated";
                session_redirect('/project/admin/manageFunders.php' .
					'?group_id=' . $group_id);
            }

        }
    }
    
}

project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";


$funder_info = db_query_params('select * from group_funderinfo where funder_id=$1 and group_id=$2',array($funder_id, $group_id));
?>

<div><h3>Update Funder Information</h3></div>
<form enctype="multipart/form-data" action="/project/admin/updateFunder.php" method="POST">
<input type="hidden" name="func" value="update_funder" />
<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
<input type="hidden" name="funder_id" value="<?php echo htmlspecialchars(db_result($funder_info,0,'funder_id')); ?>" />
<span class="required_note">Required fields outlined in blue.</span>
<br/><br/>
<style>
table>tbody>tr>td {
	padding-top: 5px;
}
</style>
<table>
<tr>
	<td><strong>Funder Name:&nbsp</strong></td>
	<td><textarea class="required" rows='1' cols='60' name='funder_name'><?php echo htmlspecialchars(db_result($funder_info,0,'funder_name')) ?></textarea></td>
</tr>
<tr>
	<td><strong>Award Number:&nbsp;</strong></td>
	<td><textarea rows='1' cols='60' name='award_number'><?php echo htmlspecialchars(db_result($funder_info,0,'award_number')) ?></textarea></td>
</tr>
<tr>
	<td><strong>Award Title:&nbsp;</strong></td>
	<td><textarea rows='1' cols='60' name='award_title'><?php echo htmlspecialchars(db_result($funder_info,0,'award_title')) ?></textarea></td>
</tr>
<tr>
	<td><strong>Additional Text to Acknowledge Funding:&nbsp;</strong></td>
	<td><textarea rows='4' cols='60' name='funder_description'><?php echo htmlspecialchars(db_result($funder_info,0,'funder_description')) ?></textarea></td>
</tr>
<tr>
    <br>
	<td><input style="margin-top:20px;" type="submit" name="submit" value="Update Funder" class="btn-cta" /></td>
</tr>
</table>
</form>
<?php

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
