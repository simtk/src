<?php

/**
 *
 * projCommunities.php
 * 
 * File to set up project communities.
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
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/project_utils.php';
require_once $gfwww.'project/admin/project_admin_utils.php';


$group_id = getIntFromRequest('group_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$group->clearError();

// If this was a submission, make updates
if ($submit = getStringFromRequest('submit')) {

	$categories = getStringFromRequest('categories');
	$resTroveGroupLink = $group->updateTroveGroupLink($categories, true);
	if (!$resTroveGroupLink) {
		$error_msg .= $group->getErrorMessage();
	}
	if (empty($error_msg)) {
		$feedback .= _('Project information updated');
	}
}


// Use the return value of $params, which is populated with titles, urls, and attrs.
$theParams = project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

?>

<style>
.myButton {
	height: 18px;
	width: auto;
}
</style>

<div class="project_overview_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">

<table style="max-width:645px;" width="100%" cellpadding="2" cellspacing="2">
<tr valign="top">
        <td width="50%">

<form id="myForm" action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<input type="hidden" id="valDelKeyword" name="valDelKeyword" value=""/>
<input type="hidden" id="valDelOntology" name="valDelOntology" value=""/>

<?php 

   $troveCatLinkArr = $group->getTroveGroupLink();
   $troveCatLinkPendingArr = $group->getTroveGroupLinkPending();
   $resultCommunities = getProjectCommunities();

   // Communities.
   if (db_numrows($resultCommunities) > 0) {
	echo '<h4>Your project belongs to the following communities</h4>';
	echo '<div class="table-responsive">';
	echo '<table class="table table-condensed table-bordered table-striped">';
	echo '<tr><th>Community</th><th>Status</th>';

	while ($row = db_fetch_array($resultCommunities)) {
?>
		<tr>
		<td><input type="checkbox" 
			name="categories[]" 
			value="<?php echo $row['trove_cat_id']; ?>"
			<?php
				if ((isset($troveCatLinkArr[$row['trove_cat_id']]) &&
					$troveCatLinkArr[$row['trove_cat_id']]) ||
					(isset($troveCatLinkPendingArr[$row['trove_cat_id']]) &&
					$troveCatLinkPendingArr[$row['trove_cat_id']])) {
					echo "checked"; 
				}
			?> 
			> <a href="/category/communityPage.php?cat=<?php
				echo $row['trove_cat_id'];
			?>"><?php echo $row['fullname']; ?></a>
			<!--a 
			href="#" 
			data-toggle="tooltip" 
			data-placement="right" 
			title="<?php 
				if (isset($row['simtk_intro_text']) &&
					trim($row['simtk_intro_text']) != "") {
					echo $row['simtk_intro_text']; 
				}
				else {
					echo "No description available";
				}
			?>" >?</a-->
		</td>
		<td>
			<?php
			if (isset($troveCatLinkArr[$row['trove_cat_id']]) &&
				$troveCatLinkArr[$row['trove_cat_id']]) {
				// Approved.
				echo "Approved";
			}
			else {
				// Check whether approval is pending.
				if (isset($troveCatLinkPendingArr[$row['trove_cat_id']]) &&
					$troveCatLinkPendingArr[$row['trove_cat_id']]) {
					// Pending approval
					echo "Pending approval";
				}
			}
			?>
		</td>
		</tr>
<?php
	}
	echo '</table>';
	echo '</div>';
   }
   
?>


<?php
// This function is used to render checkboxes below
function c($v) {
	if ($v) {
		return 'checked="checked"';
	} else {
		return '';
	}
}
?>

<p>
<input type="submit" class="btn-cta" name="submit" value="Update" />
</p>


</form>

		</td>
	</tr>
</table>
</div>

</div>
</div>

<?php

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
