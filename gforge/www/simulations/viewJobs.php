<?php

/**
 *
 * viewJobs.php
 * 
 * UI for viewing all simulation jobs.
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

$group_id = getIntFromRequest('group_id');
$groupObj = group_get_object($group_id);
if (!$groupObj) {
	exit_no_group();
}
$fullGroupName = $groupObj->getPublicName();

// Check permission and prompt for login if needed.
if (!session_loggedin() || !($u = &session_get_user())) {
	exit_not_logged_in();
}

// Get user id
// Note: User is logged in already!!!
$userID = $u->getID();
$userName = $u->getUnixName();
$realName = $u->getRealName();
$sql = "SELECT email FROM users WHERE user_id=" . $userID;
$result = db_query_params($sql, array());
$rows = db_numrows($result);
for ($i = 0; $i < $rows; $i++) {
	$userEmail = db_result($result, $i, 'email');
}
db_free_result($result);

$job_launched = getIntFromRequest('job_launched');
if ($job_launched == 1) {
	// Display feedback message after job launch.
	$feedback = "You have submitted a job and will receive email updates on the status of the job.";
}

$job_cancelled = getIntFromRequest('job_cancelled');
if ($job_cancelled == 1) {
	// Display feedback message after job cancelled.
	$feedback = "You have cancelled a job and will receive email updates on the status of the job.";
}

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
	echo "Cannot view simulations for $realName in $fullGroupName.<br/>The simulations are for members only!!!";
	echo "</div></div></div>";
	site_project_footer(array());
	return;
}

?>


<link rel="stylesheet" href="/js/jquery-ui-1.10.1.custom.min.css"/>
<script src="/js/jquery-1.11.2.min.js"></script>
<script src="/js/jquery-ui-1.10.1.custom.min.js"></script>

<script>

$(function() {
	$("#theJobs").html("Loading...");

	// Load the jobs table.
	loadSimulationResults(<?php echo $group_id; ?>);
});


// Display simulation jobs results.
function loadSimulationResults(groupId) {

	$.get('/simulations/viewSubmittedJobs.php', function(data) {

		// Table header.
		strRes = "<tr><th>Job Name</th><th>Status</th><th>Job details</th><th>Results</th></tr>";

		// Decode the JSON output data.
		var res = $.parseJSON(data);

		var cntItems = 0;
		$.each(res, function(theGroup, item) {
			if (theGroup != groupId) {
				// Results from a different group. Ignore and continue.
				return true;
			}
			cntItems++;

			$.each(item, function(jobName, itemInfo) {
				strRes += "<tr>";

				strRes += "<td>";
				strRes += "<div class='jobInfo' name='" + 
					jobName + "'>" + 
					jobName + "</div>";
				strRes += "</td>";

				strRes += "<td>";
				var jobStatus = itemInfo['status'];
				if (jobStatus != "Completed" && 
					jobStatus != "Cancelled" &&
					jobStatus != "Cancelling") {

					// Set up link for job cancellation.
					strRes += "<a " +
						"title='Click to cancel job' " +
						"class='rmrole' " +
						"style='background-color:#81a5d4; color:#ffffff; font-size:14px; white-space: nowrap;'" +
						"href='cancelJob.php?" +
						"JobName=" + jobName + "&" + 
						"GroupId=" + groupId +
						"'>&nbsp;X&nbsp;</a>&nbsp;";
					strRes += jobStatus;
				}
				else {
					strRes += jobStatus;
				}
				strRes += "</td>";

				if (itemInfo['status'] == "Completed" ||
					itemInfo['status'] == "Cancelled" ||
					itemInfo['status'] == "Cancelling") {
					// Get reference to model; strip space.
					var modelName = itemInfo["model_name"];
					var softwareName = itemInfo["software_name"];
					softwareName = softwareName.replace(/\s/g, '');
					var softwareVersion = itemInfo["software_version"];
					softwareVersion = softwareVersion.replace(/\s/g, '');
					var modelReference = groupId + "_" + 
						softwareName + "_" + 
						softwareVersion + "_" + 
						modelName;

					strRes += "<td>";
					strRes += "<i>Model:</i> " + modelName +
						" <span><a href='" + 
						"configData/" + modelReference + 
						"' title='Download model'>" +
						"<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a>" +
						"</span><br/>";

					// Show config file if used.
					if (itemInfo["cfg_pathname"] !== undefined &&
						itemInfo["cfg_pathname"] != "") {
						strRes += "<i>Config File:</i> <span><a href='" + 
							itemInfo["cfg_pathname"] + 
							"' title='Download config file'>";
						strRes += "<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a>";
						strRes += "</span><br/>";
					}
					else {
						strRes += "<i>Config File:</i> None<br/>";
					}

					strRes += "<i>Software:</i> " + itemInfo["software_name"] + "<br/>";
					strRes += "<i>Version:</i> " + itemInfo["software_version"] + "<br/>";
					strRes += "<i>Server:</i> " + itemInfo["server_name"];
					strRes += "<br/><i>Duration:</i> ";
					if (itemInfo["duration"] != -1) {
						strRes += itemInfo["duration"] + " secs";
					}

					strRes += "<br/><i>Last updated:</i> ";
					lastUpdated = itemInfo["last_updated"];
					lastUpdatedVerbose = new Date(lastUpdated * 1000).format("MM/dd/yyyy hh:mm:ss");
					strRes += lastUpdatedVerbose;
					strRes += "</td>";

					strRes += "<td><span>";

					var theSummaryText = "";
					var theSummary = itemInfo["summary"].trim();
					if (theSummary != "" && theSummary.split('\n').length <= 1) {
						// Display summary content if content is one line only.
						theSummaryText = theSummary;
						strRes += "<i>Summary:</i> " + theSummaryText + "<br/><br/>";
					}
					else {
						var userName = "<?php echo $userName; ?>";
						var prefix = userName + "_" + 
							groupId + "_" +
							itemInfo["job_timestamp"] + "_";

						var summaryFile = itemInfo['pathSummaryFile'];
						if (summaryFile !== undefined) {

							// NOTE: 5 output text files from OpenKnee simulation:
							// post_process.txt, 
							// femur_kinematics.txt, femur_kinetics.txt,
							// tibia_kinematics.txt, tibia_kinetics.txt
							//
							// These returned files are prefixed by:
							// user_name, group_id, and job_timestamp.
							// Strip this prefix to get the filenames.

							// "post_process.txt"
							var file1 = summaryFile;
							var idx = file1.indexOf(prefix);
							if (idx != -1) {
								var title = file1.substr(idx + prefix.length);
								strRes += title + " ";
							}
							strRes += "<a href='" + 
								"/simulations/" + file1 + 
								"' title='Download file'>";
							strRes += "<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a><br/><br/>";

/*
							// "femur_kinematics.txt"
							var file2 = summaryFile.replace("post_process.txt", 
								"femur_kinematics.txt");
							var idx = file2.indexOf(prefix);
							if (idx != -1) {
								var title = file2.substr(idx + prefix.length);
								strRes += title + " ";
							}
							strRes += "<a href='" + 
								"/simulations/" + file2 + 
								"' title='Download file'>";
							strRes += "<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a><br/>";

							// "femur_kinetics.txt"
							var file3 = summaryFile.replace("post_process.txt", 
								"femur_kinetics.txt");
							var idx = file3.indexOf(prefix);
							if (idx != -1) {
								var title = file3.substr(idx + prefix.length);
								strRes += title + " ";
							}
							strRes += "<a href='" + 
								"/simulations/" + file3 + 
								"' title='Download file'>";
							strRes += "<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a><br/>";

							// "tibia_kinematics.txt"
							var file4 = summaryFile.replace("post_process.txt", 
								"tibia_kinematics.txt");
							var idx = file4.indexOf(prefix);
							if (idx != -1) {
								var title = file4.substr(idx + prefix.length);
								strRes += title + " ";
							}
							strRes += "<a href='" + 
								"/simulations/" + file4 + 
								"' title='Download file'>";
							strRes += "<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a><br/>";

							// "tibia_kinetics.txt"
							var file5 = summaryFile.replace("post_process.txt", 
								"tibia_kinetics.txt");
							var idx = file5.indexOf(prefix);
							if (idx != -1) {
								var title = file5.substr(idx + prefix.length);
								strRes += title + " ";
							}
							strRes += "<a href='" + 
								"/simulations/" + file5 + 
								"' title='Download file'>";
							strRes += "<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a><br/>";
*/
						}
					}

					strRes += "</span>";

					var rawFile = itemInfo['pathRawFile'];
					if (rawFile !== undefined &&
						itemInfo['time_to_delete'] !== undefined) {

						// Raw file is available.

						// NOTE: getTime() is in milliseconds.
						var timeToDelete = itemInfo['time_to_delete'] * 1000;
						var timeNow = (new Date()).getTime();
						var timeDel = new Date(timeToDelete);
						var strTimeDel = "<span style='color:#f75236;'>" +
							timeDel.toDateString().substring(4,7) + 
							" " + timeDel.getDate() + 
							", " + timeDel.getFullYear() +
							"</span>";
						if (timeToDelete > timeNow) {
							strRes += "<span>Raw data ";
							strRes += "<a href='/simulations/" + rawFile + 
								"' title='Download raw output as a ZIP'>";
							strRes += "<img src='/themes/simtk/images/docman/download-directory-zip.png' alt='downloadaszip' style='margin:0;float:none;width:22px;height:22px;'></a><br/>";
							strRes += "<div style='font-size:11px;'>Raw data will be deleted on " + strTimeDel + ".</div>";
							strRes += "</span>";
						}
					}
					strRes += "</td>";
				}
				else {
					strRes += "<td></td><td></td>";
				}

				strRes += "</tr>";
			});

			$("#theJobs").html(strRes);
		});

		if (cntItems <= 0) {
			$("#theJobs").html("No jobs available.");
		}
	});
}

// For formatting date in Javascript using "yyyy-MM-dd hh:mm:ss"
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

</script>

<div id="jobsInfo">
	<div class='table-responsive'>
		<table id='theJobs' class='table'>
		</table>
	</div>
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

site_project_footer(array());

?>
