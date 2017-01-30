<?php

/**
 *
 * requestedJob.php
 * 
 * UI to display requested simulation job and prompt for agreement to license.
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
 
// Prompt for agreeing to license and then launch requested job.

require_once "../env.inc.php";
require_once $gfcommon.'include/pre.php';
require_once('util_simulations.php');
require_once $gfwww.'project/project_utils.php';

$group_id = getIntFromRequest('group_id');
$groupObj = group_get_object($group_id);
if (!$groupObj) {
	exit_no_group();
}

// Check permission and prompt for login if needed.
if (!session_loggedin() || !($u = &session_get_user())) {
	exit_not_logged_in();
}

// Get user id
// Note: User is logged in already!!!
$userID = $u->getID();

html_use_jqueryui();
site_project_header(array('title'=>'Simulations', 
	'h1' => '', 
	'group'=>$group_id, 
	'toptab' => 'home' ));


?>

<div class="downloads_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">

<?php

// Create submenu under downloads_main DIV, such that it does not
// occupy the whole width of the page (rather than using the
// submenu population in Theme.class.php)
$subMenuTitle = array();
$subMenuUrl = array();
echo $HTML->beginSubMenu();
echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
echo $HTML->endSubMenu();

//session_require_perm('simulations', $group_id, 'read_public');
$isPermitted = checkSimulationPermission($groupObj, $u);
if (!$isPermitted) {
	echo "The simulation is for members only!!!";
	echo "</div></div></div>";
	site_project_footer(array());
	return;
}


// Retrieve parameters for job submitted.
$strRemoteServerName = $_POST["ServerName"];
$softwareName = $_POST["SoftwareName"];
$softwareVersion = $_POST["SoftwareVersion"];
$modelName = $_POST["ModelName"];
if (isset($_POST["ConfigFileName"])) {
	$cfgName = $_POST["ConfigFileName"];
}
else {
	$cfgName = "";
}
if (isset($_POST["ConfigText"])) {
	$cfgText = $_POST["ConfigText"];
}
else {
	$cfgText = "";
}
$emailAddr = $_POST["EmailAddr"];
$jobName = $_POST["JobName"];
$modifyScriptName = $_POST["ModifyScriptName"];
$submitScriptName = $_POST["SubmitScriptName"];
$postprocessScriptName = $_POST["PostprocessScriptName"];
$installDirName = $_POST["InstallDirName"];
$maxRunTime = $_POST["MaxRunTime"];
$modifyModel = $_POST["ModifyModel"];

// Get license agreement.
$license = getSimulationLicense($group_id);

?>


<link rel="stylesheet" href="/js/jquery-ui-1.10.1.custom.min.css"/>
<script src="/js/jquery-1.11.2.min.js"></script>
<script src="/js/jquery-ui-1.10.1.custom.min.js"></script>


<script>

function cancelHandler() {
	window.location.href= "/simulations/submitJob.php?group_id=" + <?php echo $group_id ?>;
}

function submitHandler() {

	var theData = new Array();
	theData.push({name: "ServerName", value: <?php echo json_encode($strRemoteServerName); ?>});
	theData.push({name: "EmailAddr", value: <?php echo json_encode($emailAddr); ?>});
	theData.push({name: "JobName", value: <?php echo json_encode($jobName); ?>});
	theData.push({name: "GroupId", value: <?php echo json_encode($group_id); ?>});
	theData.push({name: "ModelName", value: <?php echo json_encode($modelName); ?>});
	theData.push({name: "ModifyScriptName", value: <?php echo json_encode($modifyScriptName); ?>});
	theData.push({name: "SubmitScriptName", value: <?php echo json_encode($submitScriptName); ?>});
	theData.push({name: "PostprocessScriptName", value: <?php echo json_encode($postprocessScriptName); ?>});
	theData.push({name: "InstallDirName", value: <?php echo json_encode($installDirName); ?>});
	theData.push({name: "SoftwareName", value: <?php echo json_encode($softwareName); ?>});
	theData.push({name: "SoftwareVersion", value: <?php echo json_encode($softwareVersion); ?>});
	theData.push({name: "ConfigFileName", value: <?php echo json_encode($cfgName); ?>});
	theData.push({name: "ConfigText", value: <?php echo json_encode($cfgText); ?>});
	theData.push({name: "MaxRunTime", value: <?php echo json_encode($maxRunTime); ?>});
	theData.push({name: "ModifyModel", value: <?php echo json_encode($modifyModel); ?>});

	// Submit job request and get result.
	$.ajax({
		type: "POST",
		data: theData,
		dataType: "json",
		url: "/simulations/launchJob.php",
		async: false,
	}).done(function(resJobRequest) {
		if (resJobRequest.indexOf("***ERROR***") != -1) {
			var theError = resJobRequest.substring(11);
			alert(theError);
		}
		else {
			// Redirect to view jobs page to display jobs.
			window.location.href = "/simulations/viewJobs.php?group_id=" + <?php echo $group_id; ?> + 
				"&job_launched=1";;
		}
	}).fail(function() {
		alert("Failed to launch requested job");
	});

}

</script>


<div style="padding:10px;">
<div id='license'>
<div><strong>This simulation is provided for you to run under the following license:</strong></div><br/>
<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 5px 0;"></span>
<?php echo $license; ?>
</div>
<br/>

<hr/>
<div><strong>By clicking on the “I accept” button, you agree to be bound by the terms and conditions of this license agreement.</strong></div><br/>
<button id='acceptButton' onclick='submitHandler()' class='btn-cta' >I accept</button>
<button id='cancelButton' onclick='cancelHandler()' class='btn-cta' >I disagree</button>

</div>

</div> <!-- close main_col DIV -->
<?php

// "side_bar".
constructSideBar($groupObj);

// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div style="padding-top:20px;" class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	echo '</div>';
}

?>
</div> <!-- close "display: table; width: 100%" DIV -->
</div> <!-- close downloads_main DIV --> 


<?php

$HTML->footer(array());

?>
