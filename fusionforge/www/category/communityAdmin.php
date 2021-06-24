<?php

/**
 *
 * communityAdmin.php
 * 
 * File to handle community administration.
 * 
 * Copyright 2005-2021, SimTK Team
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
require_once $gfwww.'include/trove.php';
require_once $gfwww.'category/communityAdminUtils.php';

$cat_id = getIntFromRequest("cat");

// Check permission for community page administration.
$cntUsers = 0;
if (session_get_user()) {
	// Get user id if logged in.
	$user_id = session_get_user()->getID();

	$sqlUsers = "SELECT user_id FROM trove_admin " .
		"WHERE trove_admin.trove_cat_id=$1 " .
		"AND user_id=$2";
	$resUsers = db_query_params($sqlUsers, array($cat_id, $user_id));
	$cntUsers = db_numrows($resUsers);

	db_free_result($resUsers);
}
if (!forge_check_global_perm('forge_admin') && $cntUsers <= 0) {
	exit_permission_denied('Permission denied.', '');
}


// Delete featured project.
$delFeaturedProj = htmlspecialchars(getStringFromRequest('valDelFeaturedProj'));
if (trim($delFeaturedProj) != "") {
	$strMsg = delFeaturedProject($delFeaturedProj, $cat_id);
	if ($strMsg !== NULL && $strMsg != "") {
		// Error deleting featured project.
		$error_msg = $strMsg;
	}
}

// Add featured project.
$addFeaturedProj = htmlspecialchars(getStringFromRequest('addFeaturedProj'));
if ($addFeaturedProj != "") {
	// Add featured project.
	$strMsg = addFeaturedProject($addFeaturedProj, $cat_id);
	if ($strMsg !== NULL && $strMsg != "") {
		// Error adding featured project.
		$error_msg = $strMsg;
	}
}


// Delete administrator.
// NOTE: delAdmin is not present in FireFox. Use hidden parameter valDelAdmin.
$strUserId = htmlspecialchars(getStringFromRequest('valDelAdmin'));
if (trim($strUserId) != "") {
	$strMsg = delAdministrator($strUserId, $cat_id);
	if ($strMsg !== NULL && $strMsg != "") {
		// Error deleting administrator.
		$error_msg = $strMsg;
	}
}

// Add administrator.
$strUserId = htmlspecialchars(getStringFromRequest('valAddAdmin'));
if ($strUserId != "") {
	// Add administrator.
	$strMsg = addAdministrator($strUserId, $cat_id);
	if ($strMsg !== NULL && $strMsg != "") {
		// Error adding administrator.
		$error_msg = $strMsg;
	}
}


// Delete pending project
$delPendingProj = htmlspecialchars(getStringFromRequest('delPendingProj'));
if (trim($delPendingProj) != "") {
	$strMsg = delPendingProject($delPendingProj, $cat_id);
	if ($strMsg !== NULL && $strMsg != "") {
		// Error deleting pending project.
		$error_msg = $strMsg;
	}
}

// Approve pending project
$approvePendingProj = htmlspecialchars(getStringFromRequest('approvePendingProj'));
if (trim($approvePendingProj) != "") {
	$strMsg = approvePendingProject($approvePendingProj, $cat_id);
	if ($strMsg !== NULL && $strMsg != "") {
		// Error approving pending project.
		$error_msg = $strMsg;
	}
}


// Update community information.
if (getStringFromRequest('submit')) {
	$communityName = htmlspecialchars(trim(getStringFromRequest('communityName')));
	$communityDesc = htmlspecialchars(trim(getStringFromRequest('communityDescription')));
	$autoApprove = trim(getStringFromRequest('autoApprove'));

	if ($communityName != "") {
		if ($autoApprove == 1) {
			$isAutoApprove = 1;
		}
		else {
			$isAutoApprove = 0;
		}

		// Save name and description.
		$status = updateCommunityInfo($communityName, $communityDesc, 
			$isAutoApprove, $cat_id);
		if ($status === true) {
			$feedback = "Updated community information.";
		}
		else {
			$error_msg = $status;
		}
	}
} 


// Retrieve community information.
$fullName = "";
$simtkIntroText = "";
$isAutoApprove = 1;
$sqlInfo = "SELECT fullname, simtk_intro_text, auto_approve_child " .
	"FROM trove_cat " .
	"WHERE trove_cat_id=$1";
$resInfo = db_query_params($sqlInfo, array($cat_id));
$cntInfo = db_numrows($resInfo);
while ($row = db_fetch_array($resInfo)) {
	$fullName = $row['fullname'];
	$simtkIntroText = $row['simtk_intro_text'];
	$isAutoApprove = $row['auto_approve_child'];
}
db_free_result($resInfo);


// Look up featured projects.
$sqlGNames = "SELECT DISTINCT group_name gn, unix_group_name FROM groups g " .
	"JOIN featured_projects fp " .
	"ON g.group_id=fp.group_id " .
	"WHERE fp.trove_cat_id=$1" .
$sqlFeaturedProjs = "SELECT * FROM (" .
        $sqlGNames . ") gNames " .
        "ORDER BY gNames.gn";
$resFeaturedProjs = db_query_params($sqlFeaturedProjs, array($cat_id));


// Look up administrators.
$sqlUNames = "SELECT realname, user_name, u.user_id as uid FROM users u " .
	"JOIN trove_admin ta " .
	"ON u.user_id=ta.user_id " .
	"WHERE ta.trove_cat_id=$1" .
$sqlAdmins = "SELECT * FROM (" .
        $sqlUNames . ") uNames " .
        "ORDER BY uNames.realname";
$resAdmins = db_query_params($sqlAdmins, array($cat_id));

// Look up projects with pending join request.
$sqlPendingProj = "SELECT tglp.group_id as gid, " .
	"unix_group_name, " .
	"group_name " .
	"FROM trove_group_link_pending tglp " .
	"JOIN groups g " .
	"ON tglp.group_id=g.group_id " .
	"WHERE tglp.trove_cat_id=$1" .
	"ORDER BY group_name";
$resPendingProj = db_query_params($sqlPendingProj, array($cat_id));


$HTML->header(array());

?>

<style>
td {
	padding-top: 10px;
	padding-right: 2px;
}
.myButton {
	height: 18px;
	width: auto;
}
.featuredHead {
	vertical-align: top;
}
.adminHead {
	vertical-align: top;
}
.projPendingHead {
	vertical-align: top;
}
.tdTable {
	padding-top: 0px;
}
</style>

<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

<script>
$(function() {
	$( "#addFeaturedProj" ).autocomplete({
		source: "getCommProjNames.php?category=<?php echo $cat_id; ?>",
		minLength: 1,
	});
	$("#addAdmin").autocomplete({
		source: "getCommProjAdmins.php?category=<?php echo $cat_id; ?>",
		minLength: 1,
		select: function(event, ui) {
			// Retrieve user_id of selected user for adding as administrator.
			var strUserId = ui.item.userid;
			$("#valAddAdmin").val(strUserId);
		},
	});
});
</script>

<h2>Community Admin
<br/>
<br/>
<a class="btn-blue share_text_button" href="/category/communityPage.php?cat=<?php echo $cat_id; ?>" >Go to Community Page</a>
</h2>

<fieldset>
<form enctype="multipart/form-data" method="POST">

<input type="hidden" id="valDelFeaturedProj" name="valDelFeaturedProj" value=""/>
<input type="hidden" id="valDelAdmin" name="valDelAdmin" value=""/>
<input type="hidden" id="valAddAdmin" name="valAddAdmin" value=""/>

<div class="project_overview_main">
        <div style="display: table; width: 100%;">
                <div class="main_col">

<table style="max-width:645px;" width="100%" cellpadding="2" cellspacing="2">

<tr>
	<td><strong>Name:</strong></td>
	<td><input class="required" type="text" size="44" name="communityName" value="<?php echo $fullName; ?>"/></td>
</tr>

<tr>
	<td><strong>Description:&nbsp;</strong></td>
	<td><textarea style="margin-top:5px;" rows="6" cols="50" name="communityDescription"><?php echo $simtkIntroText; ?></textarea></td>
</tr>

<tr class="adminHead">
	<td><span><strong>Administrators:&nbsp;</strong></span></td>

	<td class="tdTable">
	<table>
<?php
	$numAdmins = db_numrows($resAdmins);
	if ($numAdmins <= 0) {
		echo "<tr><td>No administrators</td></tr>";
	}
	for ($cnt = 0; $cnt < $numAdmins; $cnt++) {
?>
	<tr>
		<td>
		<span><a href="/users/<?php 
			echo db_result($resAdmins, $cnt, 'user_name'); ?>" ><?php 
			echo db_result($resAdmins, $cnt, 'realname'); ?></a></span> 
		<input class="myButton" 
			type="image" 
			name="delAdmin" 
			onclick="$('#valDelAdmin').val('<?php 
				echo db_result($resAdmins, $cnt, 'uid'); ?>');" 
			value="<?php echo db_result($resAdmins, $cnt, 'uid'); ?>" 
			src="/themes/simtk/images/list-remove.png" 
			title="Remove administrator"
			alt="Delete Admin">
		</td>
	</tr>
<?php
	}
?>

	<tr>
		<td>
		<input type="text" 
			id="addAdmin" 
			name="addAdmin" 
			title="Enter name of administrator"
			placeholder="Enter member full name"
			size="44" 
			maxlength="80" /> 
		<input class="myButton" 
			type="image" 
			src="/themes/simtk/images/list-add.png" 
			title="Add administrator"
			alt="Add Admin">
		</td>
	</tr>

	</table>
	</td>

</tr>
</table>

<br/><br/> 

<div style="max-width:645px;">
<h2 class="underlined">Manage Community Projects</h2>
</div>
<table>
<tr>
	<td class="onequarterwidth"><strong>Auto-Approve Projects to Join Community:&nbsp;</strong></td>
	<td><input type="checkbox"
		name="autoApprove"
		title="Check this box and click Update button to auto-approve project join request"
		value="1"
		<?php
			if (isset($isAutoApprove) && $isAutoApprove == 1) {
				echo "checked";
			}
		?>
		>
	</td>
</tr>

<tr class="featuredHead">
	<td><span><strong>Featured Projects:&nbsp;</strong></span></td>

	<td class="tdTable">
	<table>
<?php
	$numFeaturedProjs = db_numrows($resFeaturedProjs);
	if ($numFeaturedProjs <= 0) {
		echo "<tr><td>No featured projects</td></tr>";
	}
	for ($cnt = 0; $cnt < $numFeaturedProjs; $cnt++) {
?>
	<tr>
		<td>
		<span><a href="/projects/<?php 
			echo db_result($resFeaturedProjs, $cnt, 'unix_group_name'); ?>" ><?php
			echo db_result($resFeaturedProjs, $cnt, 'gn'); ?></a></span>
		<input class="myButton" 
			type="image" 
			name="delFeaturedProj" 
			onclick="$('#valDelFeaturedProj').val('<?php 
				echo db_result($resFeaturedProjs, $cnt, 'gn'); ?>');" 
			value="<?php echo db_result($resFeaturedProjs, $cnt, 'gn'); ?>" 
			src="/themes/simtk/images/list-remove.png" 
			title="Remove featured project"
			alt="Delete Project">
		</td>
	</tr>
<?php
	}
?>

	<tr>
		<td>
		<input type="text" 
			id="addFeaturedProj" 
			name="addFeaturedProj" 
			title="Enter name of featured project"
			placeholder="Enter project title"
			size="36" 
			maxlength="80" /> 
		<input class="myButton" 
			type="image" 
			src="/themes/simtk/images/list-add.png" 
			title="Add featured project"
			alt="Add Project">
		</td>
	</tr>

	</table>
	</td>

</tr>


<?php
	$numPendingProj = db_numrows($resPendingProj);
	if ($numPendingProj > 0) {
?>

<tr class="projPendingHead">
	<td><span><strong>Projects Requesting to Join Community:&nbsp;</strong></span></td>

	<td class="tdTable">
	<table>
<?php
		for ($cnt = 0; $cnt < $numPendingProj; $cnt++) {
?>
	<tr>
		<td>
		<span><a href="/projects/<?php 
			echo db_result($resPendingProj, $cnt, 'unix_group_name'); ?>" ><?php 
			echo db_result($resPendingProj, $cnt, 'group_name'); ?></a></span> 
		</td>
		<td>
		<button class="btn-blue" 
			type="submit" 
			name="delPendingProj" 
			value="<?php echo db_result($resPendingProj, $cnt, 'gid'); ?>" 
			title="Reject community join request">Reject</button>
		</td>
		<td>
		<button class="btn-blue"
			type="submit" 
			name="approvePendingProj" 
			value="<?php echo db_result($resPendingProj, $cnt, 'gid'); ?>" 
			title="Approve community join request">Approve</button>
		</td>
	</tr>
<?php
		}
?>
	</table>
	</td>
</tr>

<?php
	}
?>
</table>

<br/><br/>

<div style="max-width:645px;">
<h2 class="underlined">Manage Related Communities</h2>
<a href="/sendmessage.php?touser=101&subject=<?php
	echo urlencode("Adding a related community to " . $fullName . "."); 
?>">Contact SimTK Webmaster</a> to add a community.
</div>

<br/><br/>
<input type="submit" name="submit" value="Update" class="btn-cta" />


</form>
</fieldset>

		</div>
	</div>
</div>

<?php

$HTML->footer(array());

?>
