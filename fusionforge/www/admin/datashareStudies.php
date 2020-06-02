<?php

/**
 *
 * datashareStudies.php
 * 
 * Data Share study administration
 * 
 * Copyright 2005-2020, SimTK Team
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
require_once $gfcommon.'include/utils.php';
require_once $gfcommon.'include/User.class.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfwww.'admin/admin_utils.php';

session_require_global_perm('forge_admin');

// Get action submitted.
$action = null;
if ($arrAction = getStringFromRequest('submit')) {
	if (isset($arrAction["Approve"])) {
		$action = "Approve";
	}
	else if (isset($arrAction["Reject"])) {
		$action = "Reject";
	}

	$studyId = getStringFromRequest('study_id');
	$studyTitle = getStringFromRequest('study_title');
	$userId = getStringFromRequest('user_id');
	$userName = getStringFromRequest('user_name');
	$groupId = getStringFromRequest('group_id');

	if ($action == "Reject") {

		// Update entry in plugin_datashare table to reject the study.
		// Set "active" to value of -1 but do not delete the row,
		// such that there is record of the requested study.
		$result = db_query_params("UPDATE plugin_datashare " .
			"SET active=-1 " .
			"WHERE study_id=$1", 
			array($studyId));
		if (db_affected_rows($result) < 1) {
			$feedback = sprintf(("Error On Study Rejection: %s"), db_error());
		}
		else {
			$feedback = "Rejected study: " . $studyTitle;
		}
	}
	else if ($action == "Approve") {

		// Update entry in plugin_datashare table for approval of the study.
		// Set "active" to value of -2.
		// Cronjob will set up study in the Data Share server.
		// Afterward, "active" will be set to the value of 1.
		$result = db_query_params("UPDATE plugin_datashare " .
			"SET active=-2 " .
			"WHERE study_id=$1", 
			array($studyId));
		if (db_affected_rows($result) < 1) {
			$feedback = sprintf(("Error On Study Approval: %s"), db_error());
		}
		else {
			$feedback = "The study " . $studyTitle . " has been approved.<br/>" .
				"It will be available in a few minutes.";
		}
	}
}

// Pending studies.
$result_pending_study = db_query_params("SELECT study_id, " .
	"pd.group_id as group_id, " .
	"unix_group_name, " .
	"pd.title as title, " .
	"is_private, date_created, token, realname, user_name, " .
	"pd.user_id as user_id " .
	"FROM plugin_datashare pd " .
	"JOIN groups g " .
	"ON g.group_id=pd.group_id " .
	"JOIN users u " .
	"ON u.user_id=pd.user_id " .
	"WHERE pd.active=0",
	array());

site_admin_header(array('title'=>_('Site Admin')));

?>

<style>
td {
	padding-bottom:5px;
	vertical-align:top;
}
</style>


<h2>Data Share: Admin</h2>

<?php

$numPending = db_numrows($result_pending_study);
if ($numPending > 0) {
	echo "<h3>Study Pending Activation</h3>";
	echo "<table class='table'>";
	echo "<tr><th>Study Title</th><th>Submitter</th><th>Request Date</th><th></th></tr>";
}
else {
	echo "No pending studies exist.<br/><br/>";
}

while ($row = db_fetch_array($result_pending_study)) {

	echo "<form action='datashareStudies.php' method='post'>" .
		"<input type='hidden' name='study_id' value='" . $row['study_id'] . "'>" .
		"<input type='hidden' name='study_title' value='" . $row['title'] . "'>" .
		"<input type='hidden' name='user_id' value='" . $row['user_id'] . "'>" .
		"<input type='hidden' name='user_name' value='" . $row['user_name'] . "'>" .
		"<input type='hidden' name='group_id' value='" . $row['group_id'] . "'>";
	echo "<tr>";
	echo "<td>";
	echo "<a href='" . 
		"/plugins/datashare?group_id=" . $row['group_id'] . 
		"'>" . $row['title'] . "</a>";
	echo "</td>";
	echo "<td>";
	if ($row['user_id'] && $row['realname'] && $row['user_name']) {
		$realName = $row['realname'];
		$userName = $row['user_name'];
		$userInfo = "<a href='/users/" . $userName . "'>" . $realName . "</a>";
		echo $userInfo;
	}
	echo "</td>";

	$dateCreatedVerbose = false;
	$dateCreated = $row['date_created'];
	if ($dateCreated != false) {
		$dateCreatedVerbose = date('Y-m-d H:i', $dateCreated);
	}
	echo "<td>" . $dateCreatedVerbose . "</td>" .
		"<td>" .
			"<input type='submit' name='submit[Approve]' id='approve' value='Approve' class='btn-blue' />" .
			"&nbsp;" .
			"<input type='submit' name='submit[Reject]' id='reject' value='Reject' class='btn-blue' />" .
		"</td>";
	echo "</tr>";
	echo "</form>";
}

if ($numPending > 0) {
	echo "</table>";
}

?>

<?php

site_project_footer(array());


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
