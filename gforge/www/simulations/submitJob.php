<?php

/**
 *
 * submitJob.php
 * 
 * UI for submitting a new simulation job.
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
 
require_once "../env.inc.php";
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'project/project_utils.php';
require_once 'util_simulations.php';

$group_id = getIntFromRequest('group_id');
$groupObj = group_get_object($group_id);
if (!$groupObj) {
	exit_no_group();
}

// Check permission and prompt for login if needed.
if (!session_loggedin() || !($u = &session_get_user())) {
	exit_not_logged_in();
}


// Get description.
$simu_description = getSimulationDescription($group_id);

// Get user id
// Note: User is logged in already!!!
$userID = $u->getID();
$userEmail =  $u->getEmail();
$userName = $u->getUnixName();

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

$isPermitted = checkSimulationPermission($groupObj, $u);
if (!$isPermitted) {
	echo "The simulation is for members only!!!";
	echo "</div></div></div>";
	site_project_footer(array());
	return;
}

//session_require_perm('simulations', $group_id, 'read_public');
// Do not check quota for forge_admin.
if (!forge_check_global_perm('forge_admin')) {

	// Simulation quota in seconds.
	// NOTE: -1 means quota not to be checked.
	$quota = getSimulationQuota($userName, $group_id);

	// Get usage in the past year.
	$since = time() - (365 * 24 * 60 * 60);
	$usage = getSimulationUsage($userName, $group_id, $since);
	if ($quota != -1 && $usage >= $quota) {
		// Quota excceded. Do not proceed.
		echo "<h4>Quota exceeded!</h4>" .
			"<h4>Allocated: $quota seconds</h4>" .
			"<h4>Used: $usage seconds</h4>" .
			"<div class='clearer'></div>";
		echo "</div></div></div>";
		site_project_footer(array());
		return;
	}
}

?>

<style>
textarea {
	width:280px;
	height:250px;
	resize:none;
	overflow:auto;
}

td {
	padding-left: 10px;
	padding-right: 10px;
	padding-top: 5px;
	padding-bottom: 5px;
}

</style>

<script>
// Global variables.
var arrServerInfo = [];
var arrModelInfo = [];
var isJobNameValid = true;

// Adapted from http://stackoverflow.com/questions/1056728/where-can-i-find-documentation-on-formatting-a-date-in-javascript
//author: meizz
Date.prototype.format = function(format) {
	var o = {
		"M+" : this.getMonth()+1, //month
		"d+" : this.getDate(),    //day
		"h+" : this.getHours(),   //hour
		"m+" : this.getMinutes(), //minute
		"s+" : this.getSeconds(), //second
		"q+" : Math.floor((this.getMonth()+3)/3),  //quarter
		"S" : this.getMilliseconds() //millisecond
	}

	if (/(y+)/.test(format))
		format = format.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));

	for (var k in o)
		if (new RegExp("("+ k +")").test(format))
			format = format.replace(RegExp.$1, RegExp.$1.length==1 ? o[k] : ("00"+ o[k]).substr((""+ o[k]).length));

	return format;
}

$(function() {
	// Populate Job Name with current date/time by default.
	$("#jobname").val(new Date().format("yyyy-MM-dd hh:mm:ss"));

	// Pre-select the "No" radio button for "Modify Model".
	$("#radioNo").prop("checked", "checked");

	// Retrieve server info once at load time.
	getServers();

	// Handle server name selection change.
	$("#selServerName").change(function() {
		updateSoftwareNamesVersions();
	});

	// Handle software selection change.
	$("#selSoftware").change(function() {
		getModels();
	});

	// Handle model name selection change.
	$("#selModelName").change(function() {
		updateConfigFileNames();
	});

	// Handle config file name selection change.
	$("#selConfigFileName").change(function() {
		getConfigText();
	});

	// Hide the config file name selection and config text area.
	$("#labelModelConfigFile").css("display", "none");
	$("#contentModelConfigFile").css("display", "none");
	$("#labelConfigText").css("display", "none");
	$("#contentConfigText").css("display", "none");

	// Handle config text readonly selection change.
	$("input:radio").change(function() {
		var selection = $(this).val();
		if (selection == "Yes") {
			// Show the config file name selection and config text area.

			$("#labelModelConfigFile").css("display", "table-cell");
			$("#contentModelConfigFile").css("display", "table-cell");
			$("#labelConfigText").css("display", "table-cell");
			$("#contentConfigText").css("display", "table-cell");
			$(".configText").removeAttr("disabled");
			$("#selConfigFileName").removeAttr("disabled");
		}
		else {
			// Hide the config file name selection and config text area.

			$("#labelModelConfigFile").css("display", "none");
			$("#contentModelConfigFile").css("display", "none");
			$("#labelConfigText").css("display", "none");
			$("#contentConfigText").css("display", "none");

			$(".configText").attr("disabled", "true");
			$("#selConfigFileName").attr("disabled", "disabled");
		}
	});

	// Handle job submission.
	$("#submitButton").button().click(function(event) {

		event.preventDefault();

		var emailAddr = $("#notificationemail").val();
		if (!validateEmailAddress(emailAddr)) {
			// Invalid email address.
			// Show warning dialog.
			theMessage = 'Please enter a valid email address!';
			showWarningDialog("DialogWarnEmail", "Invalid Email Address", theMessage);
			return;
		}

		var jobName = $("#jobname").val();
		if (!validateJobName(jobName) || isJobNameValid == false) {
			// Invalid job name.
			return;
		}

		// Submit job.
		mySubmit();
	});

});


// Display warning dialog with given title and message.
function showWarningDialog(theDialogName, theTitle, theMessage) {

	$('<div id="' + theDialogName + '" title="' + theTitle + '"></div>').
		html('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0"></span>' + theMessage + '</p>').
		dialog({
			resizable: false,
			height:175,
			modal: true,
			buttons: {
				OK: function() {
					$(this).dialog("close");
				},
			},
		});
}


// Retrieve results returned from AJAX call.
function getResults(res) {
	var arrRes = [];
	$.each(res, function(key, value) {
		arrRes[key] = value;
		if ($.isArray(value)) {
			$.each(value, function(key1, value1) {
				arrRes[key1] = value1;
			});
		}
	});

	return arrRes;
}


// Submit job.
function mySubmit() {
	// Get the server name selection.
	//var selServerName = $("#selServerName").val();
	var selServerAlias = $("#selServerName").val();
	var theServerAliases = arrServerInfo["serverAliases"];
	var selServerName = theServerAliases[selServerAlias];

	// Note: Software name is separated from software version by " ".
	var selSoftware = $("#selSoftware").val();
	var softwareInfo = selSoftware.split(" ");
	// Get Software Name.
	var softwareName = softwareInfo[0];
	// Get Software Version.
	var softwareVersion = softwareInfo[1];

	// Get the model name selection.
	var selModelName = $("#selModelName").val();

	// Get modify script file name associated with the model.
	var arrModifyScriptNames = arrModelInfo["modelModifyScriptNames"];
	var modifyScriptName = arrModifyScriptNames[selModelName];

	// Get submit script file name associated with the model.
	var arrSubmitScriptNames = arrModelInfo["modelSubmitScriptNames"];
	var submitScriptName = arrSubmitScriptNames[selModelName];

	// Get postprocess script file name associated with the model.
	var arrPostprocessScriptNames = arrModelInfo["modelPostprocessScriptNames"];
	var postprocessScriptName = arrPostprocessScriptNames[selModelName];

	// Get installation directory associated with the model.
	var arrInstallDirNames = arrModelInfo["InstallDirNames"];
	var installDirName = arrInstallDirNames[selModelName];

	// Get configuration file Name.
	var selConfigFileName = $("#selConfigFileName").val();

	// Get current configuration text from UI.
	var theConfigText = $(".configText").val();

	// Get email address.
	var emailAddr = $("#notificationemail").val();

	// Get job name.
	var jobName = $("#jobname").val();

	// Get current time.
	var now = (new Date()).getTime();

	// Parameters for job submission.
	var theData = new Array();
	theData.push({name: "ServerName", value: selServerName});
	theData.push({name: "SoftwareName", value: softwareName});
	theData.push({name: "SoftwareVersion", value: softwareVersion});
	theData.push({name: "ModelName", value: selModelName});
	theData.push({name: "EmailAddr", value: emailAddr});
	theData.push({name: "JobName", value: jobName});
	theData.push({name: "ModifyScriptName", value: modifyScriptName});
	theData.push({name: "SubmitScriptName", value: submitScriptName});
	theData.push({name: "PostprocessScriptName", value: postprocessScriptName});
	theData.push({name: "InstallDirName", value: installDirName});
	theData.push({name: "JobTimeStamp", value: now});

	var selConfig = $("input:radio:checked").val();
	if (selConfig == "Yes") {

		var strTimestampAndConfigFileName = now + "_" + selConfigFileName;

		// Submit config file name and text if the model should be modified.
		//theData.push({name: "ConfigFileName", value: selConfigFileName});
		theData.push({name: "ConfigFileName", value: strTimestampAndConfigFileName});
		theData.push({name: "ConfigText", value: theConfigText});
	}

	// Note: Append group id for the next page.
	$('#jobSubmit').attr("action", "requestedJob.php?group_id=" + <?php echo $group_id ?>);

	// Note: key is the numeric index. value is a javascript object with name and value properties.
	// Hence, to pass to the form, retrieve the value.name and value.value to insert into the hidden input.
	$.each(theData, function(key, value) {
		$('#jobSubmit').append('<input type="hidden" name="' + value.name + '" value="' + value.value + '"/>');
	});

	$('#jobSubmit').submit();

}

// Suppress backspace. Otherwise, when backspace is clicked, the page nagivates back in history.
document.onkeydown = function(e) {
	stopDefaultBackspaceBehaviour(e);
}
document.onkeypress = function(e) {
	stopDefaultBackspaceBehaviour(e);
}
function stopDefaultBackspaceBehaviour(event) {
	var event = event || window.event;
	if (event.keyCode == 8) {
		var elements = "HTML, BODY, TABLE, TBODY, TR, TD, DIV, SELECT";
		var d = event.srcElement || event.target;
		var regex = new RegExp(d.tagName.toUpperCase());
		if (regex.test(elements)) {
			event.preventDefault ? event.preventDefault() : event.returnValue = false;
		}
	}
}


// Validate job name.
function validateJobName(theJobName) {

        // max length
        if (theJobName.length >= 40) {
		// Show warning dialog.
		theMessage = 'Please enter a job name of less than 40 characters!';
		showWarningDialog("DialogWarnJobName", "Invalid Job Name", theMessage);
                return false;
        }

	if (theJobName.trim().length == 0) {
		// Show warning dialog.
		theMessage = "Please enter a job name!";
		showWarningDialog("DialogWarnJobName", "Invalid Job Name", theMessage);
                return false;
	}

	var theRegex = /^[0-9a-zA-Z :\-/]+$/;
	var valid = theRegex.test(theJobName);
	if (!valid) {

		// Show warning dialog.
		theMessage = 'Please use alphanumeric characters! (- / space are OK)';
		showWarningDialog("DialogWarnJobName", "Invalid Job Name", theMessage);

		return false;
	}

	// Get user name.
	var jobName = $("#jobname").val();
	var theData = new Array();
	theData.push({name: "JobName", value: jobName});

	$.ajax({
		type: "POST",
		data: theData,
		dataType: "json",
		url: "/simulations/checkJobName.php",
		async: false,
	}).done(function(res) {
		// Received job information.
		var theResult = getResults(res);
		//alert("Result: " + theResult["isJobValid"]);
		//alert("Result: " + theResult["JobName"]);
		if (theResult["isJobValid"] == false) {
			isJobNameValid = false;

			// Show warning dialog.
			theMessage = 'Please enter a different job name. There is already a job with this name!';
			showWarningDialog("DialogWarnJobName", "Invalid Job Name", theMessage);
		}
		else {
			isJobNameValid = true;
		}
	}).fail(function() {
		isJobNameValid = false;

		alert("Failed to check job name!!!");
		return false;
	});

	return true;
}

// Validate email address.
function validateEmailAddress(theEmailAddr) {
	var emailRegex = new RegExp(/^([\w\.\-]+)@([\w\-]+)((\.(\w){2,3})+)$/i);
	var valid = emailRegex.test(theEmailAddr);
	if (!valid) {
		return false;
	}
	else {
		return true;
	}
}



// Retreive servers info.
// Note: Use synchronous AJAX to ensure data have been retrieved before proceeding.
function getServers() {

	// Retrieve servers information.
	var theData = {
		"GroupId": <?php echo $group_id; ?>,
	};

	$.ajax({
		type: "POST",
		data: theData,
		async: false,
		dataType: "json",
		url: "/simulations/getServersInfo.php",
	}).done(function(res) {

		// Received servers info.
		arrServerInfo = getResults(res);

		// Update models UI.
		updateServersInfo();
	}).fail(function() {
		alert("Failed to get servers info!!!");
	});

}


// Retreive models info.
// Note: Use synchronous AJAX to ensure data have been retrieved before proceeding.
function getModels() {

	// Retrieve models based on software name and version selected.
	var selSoftware = $("#selSoftware").val();

	// Note: Software name is separated from software version by " ".
	var softwareInfo = selSoftware.split(" ");
	var softwareName = softwareInfo[0];
	var softwareVersion = softwareInfo[1];

	// Retrieve models information, given the software name and software version..
	var theData = {
		"GroupId": <?php echo $group_id; ?>,
		"SoftwareName": softwareName,
		"SoftwareVersion": softwareVersion,
	};

	$.ajax({
		type: "POST",
		data: theData,
		async: false,
		dataType: "json",
		url: "/simulations/getModelsInfo.php",
	}).done(function(res) {

		// Received models info.
		arrModelInfo = getResults(res);

		// Update models UI.
		updateModelsInfo();
	}).fail(function() {
		alert("Failed to get models info!!!");
	});

}



// Retrieve the config file info.
// Note: Use synchronous AJAX to ensure data have been retrieved before proceeding.
function getConfigText() {

	// Selected configuration file.
	var selConfigFileName = $("#selConfigFileName").val();

	// Selected software; strips space.
	var selSoftwareName = $("#selSoftware").val();
	selSoftwareName = selSoftwareName.replace(/\s/g, '');

	// Get config texts information.
	var theData = {
		"GroupId": <?php echo $group_id; ?>,
		"SoftwareName": selSoftwareName,
		"ConfigFileName": selConfigFileName,
	};
	$.ajax({
		type: "POST",
		data: theData,
		async: false,
		dataType: "json",
		url: "/simulations/getCfgText.php",
	}).done(function(res) {

		// Received config text info.
		var arrConfigTextInfo = getResults(res);

		var strCfgText = arrConfigTextInfo["cfgText"];
		var idxErr = strCfgText.indexOf("***ERROR***");
		if (idxErr != -1) {
			var strErr = strCfgText.substring(11);
			alert("Error getting configuration: " + strErr);
			$(".configText").val("");
		}
		else {
			// Update configuration text of selected configuration file.
			$(".configText").val(strCfgText);
		}
	}).fail(function(jqXHR, textStatus) {
		alert("Failure to get configuration info: " + textStatus);
	});
}


// Update servers info.
function updateServersInfo() {

	// All server names.
	//var theServerNames = arrServerInfo["serverNames"];
	var theServerAliases = arrServerInfo["serverAliases"];

	// Update select options using server names received..
	var optionsServerNames = '';
	$.each(theServerAliases, function(key, value) {
		optionsServerNames += "<option>" +
			key +
			"</option>\n";
	});
	$("#selServerName").html(optionsServerNames);

	// Update the associated software name/version info.
	updateSoftwareNamesVersions();
}


// Update model names info.
function updateModelsInfo() {

	// All model names.
	var theModelNames = arrModelInfo["modelNames"];

	// Update select options using model names received..
	var optionsModelNames = '';
	$.each(theModelNames, function(key, value) {
		optionsModelNames += "<option>" +
			value +
			"</option>\n";
	});
	$("#selModelName").html(optionsModelNames);

	// Update the associated configuration file info.
	updateConfigFileNames();
}


// Update software names and versions info.
function updateSoftwareNamesVersions() {

	// All software names.
	var arrSoftwareNames = arrServerInfo["softwareNames"];
	// All software versions.
	var arrSoftwareVersions = arrServerInfo["softwareVersions"];

	// Get the server name selection.
	//var selServerName = $("#selServerName").val();
	var selServerAlias = $("#selServerName").val();
	var theServerAliases = arrServerInfo["serverAliases"];
	var selServerName = theServerAliases[selServerAlias];

	// Get software names and verions associated with the server.
	var theSoftwareNames = arrSoftwareNames[selServerName];
	var theSoftwareVersions = arrSoftwareVersions[selServerName];

	// Sort the software.
	var theOptions = new Array();
	$.each(theSoftwareNames, function(key, value) {
		theOptions.push(value + " " + theSoftwareVersions[key]);
	});
	theOptions.sort();

	// Update select options using softare names and versions received.
	var optionsSoftware = '';
	//$.each(theSoftwareNames, function(key, value) {
	$.each(theOptions, function(key, value) {
		optionsSoftware += "<option>" +
			value +
			"</option>\n";
	});
	$("#selSoftware").html(optionsSoftware);


	// Retreive models info.
	getModels();
}


// Update model configuration file names and configuration text.
function updateConfigFileNames() {

	// All config file names.
	var arrCfgNames = arrModelInfo["modelCfgNames"];

	// Get the model name selection.
	var selModelName = $("#selModelName").val();

	// Get config file names associated with the model.
	var theCfgNames = arrCfgNames[selModelName];

	// Update select options using config files names received.
	var optionsConfigFiles = '';
	$.each(theCfgNames, function(key, value) {
		optionsConfigFiles += "<option>" +
			value +
			"</option>\n";
	});
	$("#selConfigFileName").html(optionsConfigFiles);

	var selection = $("input:radio:checked").val();
	if (selection == "Yes") {
		$("#selConfigFileName").removeAttr("disabled");
	}
	else {
		$("#selConfigFileName").attr("disabled", "disabled");
	}

	// Retrieve config file text.
	getConfigText();
}

</script>

<fieldset>
<form method='post' id='jobSubmit'>

<table>

<tr>
<td>
<strong>Job description:
</td>
<td>
<?php echo $simu_description; ?>
</td>
</tr>

<tr>
<td>
<strong>Select server:</strong>
</td>
<td>
<select id='selServerName'>
</select>
</td>
</tr>

<tr>
<td>
<strong>Select software:</strong>
</td>
<td>
<select id='selSoftware'>
</select>
</td>
</tr>

<tr>
<td>
<strong>Select model:</strong>
</td>
<td>
<select id='selModelName'>
</select>
</td>
</tr>

<tr>
<td>
<strong>Modify model:</strong>
</td>
<td>
	<input type='radio' id='radioYes' class='modifymodel' name='modifymodel' value='Yes' ><label>Yes</label></input>
	<input type='radio' id='radioNo' class='modifymodel' name='modifymodel' value='No' checked='checked'><label>No</label></input>
</td>
</tr>

<tr>
<td id='labelModelConfigFile'>
<strong>Model Configuration File</strong>
</td>
<td id='contentModelConfigFile'>
<select id='selConfigFileName'>
</select>
</td>
</tr>

<tr>
<td id='labelConfigText'><br/></td>
<td id='contentConfigText'>
	<textarea class='configText' disabled='disabled'></textarea>
</td>
</tr>

<tr>
<td>
<strong>Notification email:</strong>
</td>
<td> <input id='notificationemail' type='text' value="<?php echo $userEmail; ?>" />
</td>
</tr>

<tr>
<td>
<strong>Job name:</strong>
</td>
<td> <input id='jobname' type='text' value="" />
</td>
</tr>

<tr>
<td>
	<p><button id='submitButton' class="btn-cta">Submit</button></p>
</td>
</tr>

</table>
</form>
</fieldset>

<div class="clearer"></div>

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

site_project_footer(array());

?>
