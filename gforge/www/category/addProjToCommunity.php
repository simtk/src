<?php

/**
 *
 * addProjToCommunity.php
 * 
 * Select project(s) to request to be added to communities.
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/project_utils.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfplugins.'mypage/www/mypage-utils.php';

if (!session_loggedin()) {
	exit_not_logged_in();
}
// Get user.
$user = session_get_user();

$cat_id = getIntFromRequest('cat');
if (!isset($cat_id)) {
	exit_error("Community not available", 'admin');
	return;
}

// Validate of community specified.
// NOTE: trove_cat value of 1000 is used as parent.
$resultCommunities = db_query_params('SELECT trove_cat_id FROM trove_cat ' .
	'WHERE parent=1000 ' .
	'AND trove_cat_id=$1',
	array($cat_id));
if (db_numrows($resultCommunities) <= 0) {
	// Proect community is invalid.
	exit_error("Invalid community", 'admin');
	return;
}


// Get projects that the user belongs to.
$arrPublicProjs = array();
$cntUserProjects = 0;
$userProjects = getUserProjects($user, $cntUserProjects);
for ($cntProj = 0; $cntProj < $cntUserProjects; $cntProj++) {

	// Find projects which user is an "Admin".
	if (strpos($userProjects[$cntProj]["role_names"], "Admin") === false) {
		// User is not Admin of project.
		continue;
	}

	$groupId = $userProjects[$cntProj]["group_id"];
	$groupObj = group_get_object($groupId);
	if (!$groupObj->isPublic()) {
		// Ignore private projects.
		continue;
	}

	// Collect public projects in the specified community.
	$arrPublicProjs[] = $groupObj;
}
$numPublicProjs = count($arrPublicProjs);
if ($numPublicProjs <= 0) {
	exit_error("No available public project to add to community");
	return;
}


// If this was a submission, make updates.
if ($submit = getStringFromRequest('submit')) {

	// These are selected groups to participate in the chosen community
	// i.e. $cat_id.
	$selGroups = getStringFromRequest('groups');
	if (!is_array($selGroups) || count($selGroups) <= 0) {
		// No group selected. Create an empty array.
		$selGroups = array();
	}

	// Get all known communities.
	$allCommunities = array();
	$resultCommunities = getProjectCommunities();
	if (db_numrows($resultCommunities) > 0) {
		while ($row = db_fetch_array($resultCommunities)) {
			$allCommunities[] = $row['trove_cat_id'];
		}
	}

	// Iterate through all public projects that user is "Admin"
	for ($cntProj = 0; $cntProj < $numPublicProjs; $cntProj++) {
		$groupObj = $arrPublicProjs[$cntProj];

		$selCommunities = array();

		// Iterate through all known communities 
		// to generate a list of selected communities for the given project.
		$troveCatLinkArr = $groupObj->getTroveGroupLink();
		$troveCatLinkPendingArr = $groupObj->getTroveGroupLinkPending();
		for ($cntComm = 0 ; $cntComm < count($allCommunities); $cntComm++) {
			$theCommunity = $allCommunities[$cntComm];

			// Get community approved and pending approval for the project.
			if ((isset($troveCatLinkArr[$theCommunity]) &&
				$troveCatLinkArr[$theCommunity]) ||
				(isset($troveCatLinkPendingArr[$theCommunity]) &&
				$troveCatLinkPendingArr[$theCommunity])) {

				// Community approved or pending approval.
				// NOTE:
				// If community is present in the array, it means that 
				// this group is approved or is pending approval 
				// to be in the community.
				// 
				// If community is not present in the array, it means that 
				// this group is currently not participating in the community.
				$selCommunities[] = $theCommunity;
			}
		}

		//print_r($selCommunities);

		// Get the group id.
		$groupId = $groupObj->getID();
		if (in_array($groupId, $selGroups)) {
			// Selected group id (i.e. checkbox is checked.)
			if (!in_array($cat_id, $selCommunities)) {
				// Not present amongst the communities. Add the community.
				$selCommunities[] = $cat_id;
			}
			else {
				// Already in the communities. No action needed.
			}
		}
		else {
			// Did not select group id (i.e. checkbox is not checked.)
			if (in_array($cat_id, $selCommunities)) {
				// Present in the communities. Remove the community.
				$theIdx = array_search($cat_id, $selCommunities);
				$selCommunities = array_splice($selCommunities, $theIdx + 1, 1);
			}
			else {
				// Already not in communities. No action needed.
			}
		}
		//print_r($selCommunities);

		$resTroveGroupLink = $groupObj->updateTroveGroupLink($selCommunities, true);
		if (!$resTroveGroupLink) {
			if (empty($error_msg)) {
				$error_msg = $groupObj->getErrorMessage();
			}
			else {
				$error_msg .= "\n" . $groupObj->getErrorMessage();
			}
                }
	}
	if (empty($error_msg)) {
		$feedback .= _('Community information updated');
	}
}


$HTML->header(array('title'=>'Community','pagename'=>''));

// Force IE NOT to go into "Compatibility" mode.
header("X-UA-Compatible: IE=Edge");


// Get community information.
$sql = "SELECT trove_cat_id, shortname, fullname FROM trove_cat " .
	"WHERE trove_cat_id=$1";
$result = db_query_params($sql, array($cat_id));
$rows = db_numrows($result);
for ($i = 0; $i < $rows; $i++) {
	$cat_id = db_result($result,$i,'trove_cat_id');
	$fullname = db_result($result,$i,'fullname');
	$shortname = db_result($result,$i,'shortname');
}
db_free_result($result);

echo "<h2>" . $fullname . " Community: Add projects</h2><br/>";

?>

<script>
// NOTE: Chrome does not refresh checkbox even though the "checked" attribute is present.
// Hence, used a hidden div to keep the "is_checked"/"not_checked" text value and then
// refresh the checkbox property accordingly.
$(document).ready(function() {
	// The class myHiddenDiv is used for tracking the "checked" information.
	$(".myHiddenDiv").each(function() {
		if ($(this).attr("id").indexOf("hidden") != -1) {
			// Get target id stored after "hidden".
			var theTargetId = $(this).attr("id").substring(6);
			// Generate target checkbox id.
			var theCheckBoxId = "myCheckBox" + theTargetId;
			if ($(this).text().trim() == "is_checked") {
				// Check checkbox.
				$("#" + theCheckBoxId).prop("checked", true);
			}
			else {
				// Uncheck checkbox.
				$("#" + theCheckBoxId).prop("checked", false);
			}
		}
	});
});
</script>

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

<?php

// Do not show table for projects if none is available.
if ($numPublicProjs > 0) {
	echo '<form id="myForm" action="' . getStringFromServer('PHP_SELF') . '" method="post">';
	echo '<input type="hidden" name="cat" value="' . $cat_id . '" />';

	echo '<div class="table-responsive">';
	echo '<table class="table table-condensed table-bordered table-striped">';
	echo '<tr><th>Project</th><th>Status</th>';

	for ($cntProj = 0; $cntProj < $numPublicProjs; $cntProj++) {

		$groupObj = $arrPublicProjs[$cntProj];
		$fullGroupName = $groupObj->getPublicName();
		$groupName = $groupObj->getUnixName();

		// Find project status in the specified community.
		$troveCatLinkArr = $groupObj->getTroveGroupLink();
		$troveCatLinkPendingArr = $groupObj->getTroveGroupLinkPending();

?>
	<tr>
	<td>
		<input type="checkbox" 
			id="myCheckBox<?php echo $groupObj->getID(); ?>"
			name="groups[]" 
			value="<?php echo $groupObj->getID(); ?>"
			<?php
				if ((isset($troveCatLinkArr[$cat_id]) &&
					$troveCatLinkArr[$cat_id]) ||
					(isset($troveCatLinkPendingArr[$cat_id]) &&
					$troveCatLinkPendingArr[$cat_id])) {
					echo "checked"; 
				}
			?> 
		> <a href="/projects/<?php echo $groupName; ?>"><?php echo $fullGroupName; ?></a>
		<div style="display:none;" 
			id="hidden<?php echo $groupObj->getID(); ?>"
			class="myHiddenDiv" >
			<?php
				if ((isset($troveCatLinkArr[$cat_id]) &&
					$troveCatLinkArr[$cat_id]) ||
					(isset($troveCatLinkPendingArr[$cat_id]) &&
					$troveCatLinkPendingArr[$cat_id])) {
					echo "is_checked"; 
				}
				else {
					echo "not_checked"; 
				}
			?> 
		</div>
	</td>
	<td>
		<?php
			// Status.
			if (isset($troveCatLinkArr[$cat_id]) &&
				$troveCatLinkArr[$cat_id]) {
				// Approved.
				echo "Approved";
			}
			else {
				// Check whether approval is pending.
				if (isset($troveCatLinkPendingArr[$cat_id]) &&
					$troveCatLinkPendingArr[$cat_id]) {
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

	echo '<p><input type="submit" class="btn-cta" name="submit" value="Update" /></p>';
	echo '</form>';
}
   
?>

		</td>
	</tr>
</table>
</div>

</div>
</div>

<?php

$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

