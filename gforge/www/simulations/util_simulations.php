<?php

/**
 *
 * util_simulations.php
 * 
 * Utilities for handling simulations.
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
require_once('sshCommUtils.php');

define("TIMEOFFSET_DELETION", 8 * 24 * 3600);


// Save model configuration file.
function saveModelConfigFile($theFullPathName, $theCfgText) {

	// If file exists, overwrite existing contents.
	// If not, create new file and store the contents.
	$fp = @fopen($theFullPathName, "w+");
	if ($fp === false) {
		// Cannot open file for saving.
		return "***ERROR***" . " Cannot save configuration file: " . $theFullPathName;
	}
	fwrite($fp, $theCfgText);
	fclose($fp);

	return null;
}


// Request simulation job.
function requestSimulationJob($theRemoteServerName,
	$theUserName, 
	$theEmailAddr,
	$theJobName,
	$theGroupId, 
	$theModelName, 
	$theModifyScriptName,
	$theSubmitScriptName,
	$thePostprocessScriptName,
	$theInstallDirName,
	$theSoftwareName, 
	$theSoftwareVersion,
	$theJobTimeStamp,
	$theCfgName,
	$theCfgText) {

	if (!isset($theUserName) || $theUserName == "") {
		// Problem with remote server. Do not proceed.
		return "***ERROR***" . " No user name. Please log in.";
	}

	if (isJobRequested($theRemoteServerName, $theUserName, $theGroupId, $theJobTimeStamp)) {
		// There is already an entry with the same job id. Do not proceed.
		return "***ERROR***" . "This simulation job has already been requested for " .
			$theUserName . " (Group $theGroupId)";
	}

	$theFullCfgName = "";
	$theFullCfgPathName = "";
	if (isset($theCfgName) && $theCfgName != "") {
		// Generate full config file name and pathname if config file is specified.
		$theFullCfgName = $theGroupId . "_" . $theUserName . "_" . $theCfgName;
		$theFullCfgPathName = "./configData/" . $theFullCfgName;

		// Save configuration to file if specified..
		$status = saveModelConfigFile($theFullCfgPathName, $theCfgText);
		if ($status != null) {
			// Error. Do not proceed.
			return $status;
		}
	}

	// Record simulation job details.
	$status = recordSimulationJob($theRemoteServerName, $theUserName, $theEmailAddr, $theJobName, $theGroupId, $theModelName, 
		$theModifyScriptName, $theSubmitScriptName, $thePostprocessScriptName, $theInstallDirName, $theFullCfgName,
		$theSoftwareName, $theSoftwareVersion, $theJobTimeStamp, $theFullCfgPathName);
	if ($status != null) {
		// Problem with remote server. Do not proceed.
		return "***ERROR***" . " Problem with remote server: " . $theRemoteServerName;
	}

	// Run the simulation job.
	$status = runSimulationJob($theRemoteServerName, $theUserName, $theGroupId, $theJobTimeStamp,
		$theModelName, $theModifyScriptName, $theSubmitScriptName, $thePostprocessScriptName, $theInstallDirName,
		$theFullCfgName, $theFullCfgPathName, $theSoftwareName, $theSoftwareVersion, $theEmailAddr, $theJobName);

	return $status;
}


// Check whether the simulation job has already been requested.
function isJobRequested($theRemoteServerName, $theUserName, $theGroupId, $theJobTimeStamp) {

	$sqlExistingJob = "SELECT job_id FROM simulation_jobs_details WHERE " .
		"server_name='" . $theRemoteServerName . "' AND " .
		"user_name='" . $theUserName . "' AND " .
		"group_id=" . $theGroupId . " AND " .
		"job_timestamp='" . $theJobTimeStamp . "'";

	$resExistingJob = db_query_params($sqlExistingJob, array());
	$rowsExistingJob = db_numrows($resExistingJob);
	if ($rowsExistingJob > 0) {
		// Job entry is already present in the remote server.
		db_free_result($resExistingJob);
		return true;
	}
	db_free_result($resExistingJob);

	return false;
}


// Look up next simulation job from the simulation_job_details table.
function lookupNextSimulationJob($theRemoteServerName,
	&$theUserName, 
	&$theGroupId, 
	&$theJobTimeStamp,
	&$theModelName, 
	&$theModifyScriptName,
	&$theSubmitScriptName,
	&$thePostprocessScriptName,
	&$theInstallDirName,
	&$theFullCfgName,
	&$theFullCfgPathName,
	&$theSoftwareName, 
	&$theSoftwareVersion,
	&$theEmailAddr,
	&$theJobName) {

	// Retrieve details on next simulation job.
	$sqlNextJob = "SELECT user_name, group_id, job_timestamp, " .
		"model_name, script_name_modify, script_name_submit, script_name_postprocess, install_dir, " .
		"cfg_name_1, cfg_pathname_1, software_name, software_version, email, job_name " .
		"FROM simulation_jobs_details WHERE " .
		"server_name='" . $theRemoteServerName . "' AND duration='-1' " .
		"ORDER BY job_id";

	$resNextJob = db_query_params($sqlNextJob, array());
	$rowsNextJob = db_numrows($resNextJob);
	if ($rowsNextJob == 0) {
		// No more jobs.
		db_free_result($resNextJob);
		return false;
	}

	// Found next job. Retrieve job details.
	if ($rowsNextJob > 0) {
		// Take the lowest row returned.
		$theUserName = db_result($resNextJob, 0, 'user_name');
		$theGroupId = db_result($resNextJob, 0, 'group_id');
		$theJobTimeStamp = db_result($resNextJob, 0, 'job_timestamp');
		$theModelName = db_result($resNextJob, 0, 'model_name');
		$theModifyScriptName = db_result($resNextJob, 0, 'script_name_modify');
		$theSubmitScriptName = db_result($resNextJob, 0, 'script_name_submit');
		$thePostprocessScriptName = db_result($resNextJob, 0, 'script_name_postprocess');
		$theInstallDirName = db_result($resNextJob, 0, 'install_dir');
		$theFullCfgName = db_result($resNextJob, 0, 'cfg_name_1');
		$theFullCfgPathName = db_result($resNextJob, 0, 'cfg_pathname_1');
		$theSoftwareName = db_result($resNextJob, 0, 'software_name');
		$theSoftwareVersion = db_result($resNextJob, 0, 'software_version');
		$theEmailAddr = db_result($resNextJob, 0, 'email');
		$theJobName = db_result($resNextJob, 0, 'job_name');
	}
	db_free_result($resNextJob);

	return true;
}


// Run simulation job.
function runSimulationJob($theRemoteServerName,
	$theUserName, 
	$theGroupId, 
	$theJobTimeStamp,
	$theModelName, 
	$theModifyScriptName,
	$theSubmitScriptName,
	$thePostprocessScriptName,
	$theInstallDirName,
	$theFullCfgName,
	$theFullCfgPathName,
	$theSoftwareName, 
	$theSoftwareVersion,
	$theEmailAddr,
	$theJobName) {

	// Reserve remote server for simulation.
	$statusAvail = reserveRemoteServer($theRemoteServerName, $theUserName, 
		$theGroupId, $theJobTimeStamp, $theJobStartedTimeStamp, $theSoftwareName);
	if ($statusAvail === false) {
		// A script is already running. Do not proceed.
		return "***INFO***" .  "Simulation job has been submitted. " .
			"There is currently a job running at $theRemoteServerName.";
	}
	else if ($statusAvail !== true) {
		// Has error. Do not proceed.
		return $statusAvail;
	}

	// OK. Reserved remote server.


	// Send email.
	$theJobTimeStampVerbose = date('Y-m-d H:i:s', intval(substr($theJobTimeStamp, 0, -3)));
	$theJobStartedTimeStampVerbose = date('Y-m-d H:i:s', intval(substr($theJobStartedTimeStamp, 0, -3)));
	$strJobStarted = "'$theRemoteServerName': Job \"$theJobName\" from " .  
		"'$theUserName' (Group $theGroupId) started at $theJobStartedTimeStampVerbose. " .
		"Job submitted at $theJobTimeStampVerbose.";
	if (isset($_SERVER['SERVER_NAME'])) {
		sendEmail($theEmailAddr, "Job Started", $strJobStarted, "nobody@" . $_SERVER['SERVER_NAME']);
	}
	else {
		// Parse configuration to get web_host.
		$theWebHost = false;
		if (file_exists("/etc/gforge/config.ini.d/debian-install.ini")) {
			// The file debian-install.ini is present.
			$arrConfig = parse_ini_file("/etc/gforge/config.ini.d/debian-install.ini");
			// Check for each parameter's presence.
			if (isset($arrConfig["web_host"])) {
				$theWebHost = $arrConfig["web_host"];
				sendEmail($theEmailAddr, "Job Started", $strJobStarted, 
					"nobody@" . $theWebHost);
			}
		}
	}
	// Record job started.
	$status = recordRemoteServerJobStart($theRemoteServerName, $theUserName, $theGroupId, $theJobTimeStamp);


	// Retrieve authentication info for login to remote server.
	$status = getRemoteUserAuthentication($theRemoteServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod);
	if ($status != null) {
		// Cannot get remote server authentication.
		return "***ERROR***" . "Cannot get remote server authentication: " . $theRemoteServerName;
	}

	// Get remote server ssh access.
	$theSsh = getRemoteServerSshAccess($theRemoteServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod, 
		$strRemoteServerHomeDir);

	// Get remote server sftp access.
	$theSftp = getRemoteServerSftpAccess($theRemoteServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod, 
		$strRemoteServerHomeDir);

	if ($theSsh === false || $theSftp === false) {
		// Cannot get Remote Server SSH or SFTP access.
		return "***ERROR***" . "Cannot get Remote Server SSH or SFTP access: " . $theRemoteServerName;
	}

	// Send configuration file if the config file is specified.
	if (isset($theFullCfgName) && $theFullCfgName != "") {
		sftpPut($theSftp, 
			$strRemoteServerHomeDir . "/" . $theFullCfgName, 
			$theFullCfgPathName);
	}

	// Generate job identifier.
	$jobId = $theUserName . "_" . $theGroupId . "_" . $theJobTimeStamp;


	// Generate full simulation wrapper file name and pathname.
	$fullWrapperName = "SimulationWrapper_" . $jobId;
	$fullWrapperPathName = "./configData/" . $fullWrapperName;

	// Look up path to software, given software name and version.
	$theSoftwarePath = lookupSoftwarePath($theSoftwareName, $theSoftwareVersion);

	// Build simulation wrapper file.
	$status = buildSimulationWrapper($fullWrapperPathName, $fullWrapperName, $jobId, 
		$theInstallDirName, $theModelName, $theSubmitScriptName, $thePostprocessScriptName,
		$theModifyScriptName, $theFullCfgName, $theSoftwarePath);
	if ($status != null) {
		// Error. Do not proceed.
		return $status;
	}

	// Send simulation wrapper file to remote server.
	sftpPut($theSftp, 
		$strRemoteServerHomeDir . "/" . $fullWrapperName, 
		$fullWrapperPathName);

	// Clean up simulation wrapper file from local server.
	unlink($fullWrapperPathName);

/*
	// Clean up configuration file from local server.
	if (isset($theFullCfgName) && $theFullCfgName != "") {
		unlink($theFullCfgPathName);
	}
*/

	// Execute simulation using simulation wrapper.
	executeSimulation($theSsh, $jobId, $fullWrapperName, $theFullCfgName);

/*
	// Clean up by disconnecting.
	$theSsh->disconnect();
	$theSftp->disconnect();
*/

	return "Simulation job request has been successfully submitted.";
}


// Record simulation job info.
function recordSimulationJob($theRemoteServerName,
	$theUserName, 
	$theEmailAddr,
	$theJobName,
	$theGroupId, 
	$theModelName, 
	$theModifyScriptName,
	$theSubmitScriptName,
	$thePostprocessScriptName,
	$theInstallDirName,
	$theFullCfgName,
	$theSoftwareName, 
	$theSoftwareVersion,
	$theJobTimeStamp,
	$theFullCfgPathName) {

	// Send email.
	$theJobTimeStampVerbose = date('Y-m-d H:i:s', intval(substr($theJobTimeStamp, 0, -3)));
	$strJobSubmitted = "'$theRemoteServerName': Job \"$theJobName\" from " .  
		"'$theUserName' (Group $theGroupId) submitted at $theJobTimeStampVerbose.\n";
	if (isset($_SERVER['SERVER_NAME'])) {
		sendEmail($theEmailAddr, "Job Submitted", $strJobSubmitted, "nobody@" . $_SERVER['SERVER_NAME']);
	}
	else {
		// Parse configuration to get web_host.
		$theWebHost = false;
		if (file_exists("/etc/gforge/config.ini.d/debian-install.ini")) {
			// The file debian-install.ini is present.
			$arrConfig = parse_ini_file("/etc/gforge/config.ini.d/debian-install.ini");
			// Check for each parameter's presence.
			if (isset($arrConfig["web_host"])) {
				$theWebHost = $arrConfig["web_host"];
				sendEmail($theEmailAddr, "Job Submitted", $strJobSubmitted,
					"nobody@" . $theWebHost);
			}
		}
	}

	// Insert job request into simulation_jobs_details table.
	// Note: status of 1 means job submitted.
	$sqlJobDetails = "INSERT INTO simulation_jobs_details " .
		"(status, server_name, user_name, email, job_name, group_id, model_name, " .
		"script_name_modify, script_name_submit, script_name_postprocess, install_dir, " .
		"cfg_name_1, cfg_pathname_1, software_name, software_version, job_timestamp, last_updated) " .
		"VALUES (" .
		"1, " .
		"'" . $theRemoteServerName . "', " . 
		"'" . $theUserName . "', " . 
		"'" . $theEmailAddr . "', " . 
		"'" . $theJobName . "', " . 
		$theGroupId . ", " . 
		"'" . $theModelName . "', " . 
		"'" . $theModifyScriptName . "', " . 
		"'" . $theSubmitScriptName . "', " . 
		"'" . $thePostprocessScriptName . "', " . 
		"'" . $theInstallDirName . "', " . 
		"'" . $theFullCfgName . "', " . 
		"'" . $theFullCfgPathName . "', " . 
		"'" . $theSoftwareName . "', " . 
		"'" . $theSoftwareVersion . "', " . 
		"'" . $theJobTimeStamp . "', " . 
		"'" . time() . "' " . 
		")";
	$resJobDetails = db_query_params($sqlJobDetails, array());
	if (!$resJobDetails) {
		// Cannot insert into job table
		return "***ERROR***" . "Cannot insert into simulation_jobs_details table: " . $sqlJobDetails;
	}

	return null;
}



// Look up path to software, given the software name and software version.
function lookupSoftwarePath($theSoftwareName, $theSoftwareVersion) {

	// Retrieve software path
	$sql = "SELECT software_path FROM simulation_software WHERE " .
		"software_name='" . $theSoftwareName . "' and " .
		"software_version='" . $theSoftwareVersion . "'";

	$theSoftwarePath = "";

	$result = db_query_params($sql, array());
	$rows = db_numrows($result);
	for ($i = 0; $i < $rows; $i++) {
		$theSoftwarePath = db_result($result, $i, 'software_path');
	}
	db_free_result($result);

	return $theSoftwarePath;
}

// Retrieve authentication info of remote server from database.
function getRemoteUserAuthentication($theRemoteServerName, 
	&$theRemoteUserName, &$theRemotePassword, &$theRemoteAuthMethod) {

	// Retrieve user name and password.
	$sql = "SELECT user_name, user_password, auth_method FROM simulation_servers_auth WHERE " .
		"server_name='" . $theRemoteServerName . "'";

	$theRemoteUserName = "";
	$theRemotePassword = "";
	$theRemoteAuthMethod = -1;
	$result = db_query_params($sql, array());
	$rows = db_numrows($result);
	for ($i = 0; $i < $rows; $i++) {
		$theRemoteUserName = db_result($result, $i, 'user_name');
		$theRemotePassword = db_result($result, $i, 'user_password');
		$theRemoteAuthMethod = db_result($result, $i, 'auth_method');
	}
	db_free_result($result);

	if ($theRemoteUserName == "" || $theRemotePassword == "" || $theRemoteAuthMethod == -1) {
		// Cannot get user name/password.
		return "***ERROR***" . "Cannot get authentication information for " . $theRemoteServerName;
	}

	return null;
}


// Check if simulation jobs at remote servers are completed.
// If completed, update time spent and make remote server available again.
function checkRemoteServerJobCompletions($showOutput = true) {

	$sqlInProgress = "SELECT server_name, user_name, group_id, job_timestamp, job_started_timestamp " .
		"FROM simulation_requests WHERE in_use=1";

	$theServerName = "";
	$theUserName = "";
	$theGroupId = -1;
	$theJobTimeStamp = "";
	$theJobStartedTimeStamp = "";

	$resInProgress = db_query_params($sqlInProgress, array());
	$rowsInProgress = db_numrows($resInProgress);
	if ($rowsInProgress == 0) {
		// All rows have in_use=0.
		//echo date("F j, Y, g:i a") . ": No simulation jobs in progress.\n";
	}

	for ($i = 0; $i < $rowsInProgress; $i++) {

		$theServerName = db_result($resInProgress, $i, 'server_name');
		$theUserName = db_result($resInProgress, $i, 'user_name');
		$theGroupId = db_result($resInProgress, $i, 'group_id');
		$theJobTimeStamp = db_result($resInProgress, $i, 'job_timestamp');
		$theJobStartedTimeStamp = db_result($resInProgress, $i, 'job_started_timestamp');

		// Check if job has been completed at remote server. Get time spent.

		$duration = checkRemoteServerJobCompletion($theServerName, 
			$theUserName, $theGroupId, $theJobTimeStamp);

		// Only use seconds (i.e. not milliseconds). Otherwise, intval() will truncate the integer.
		$theJobTimeStampVerbose = date('Y-m-d H:i:s', intval(substr($theJobTimeStamp, 0, -3)));
		$theJobStartedTimeStampVerbose = date('Y-m-d H:i:s', intval(substr($theJobStartedTimeStamp, 0, -3)));

		if ($duration === false) {
			// Error. Do not proceed.
		}
		else if ($duration == -1) {
			if ($showOutput) {
				echo date("F j, Y, g:i a") .  
				": '" .  $theServerName . "' is not done with job from " .
				"'$theUserName' (Group $theGroupId).\n" . 
				"Job started at $theJobStartedTimeStampVerbose. Job submitted at $theJobTimeStampVerbose.\n";
			}
		}
		else {
			// Done. Duration is returned. Update database.
			if ($showOutput) {
				echo date("F j, Y, g:i a") . 
				": '" . $theServerName . "' is done with job from " .
				"'$theUserName' (Group $theGroupId).\n" .
				"Job started at $theJobStartedTimeStampVerbose. Job submitted at $theJobTimeStampVerbose.\n" .
				"Time spent: " . $duration . " secs.\n";
			}

			// Retrieve results file.
			$status = getRemoteServerResultSummary($theServerName, 
				$theUserName, $theGroupId, $theJobTimeStamp,
				$theSummaryTxt);

			// Record duration and result summary of simulation job.
			$status = recordRemoteServerJobCompletion($theServerName, 
				$theUserName, $theGroupId, $theJobTimeStamp, 
				$duration, $theSummaryTxt);


			// Done. Make remote server available.
			$status = doneRemoteServer($theServerName);

			// Send email on job completion.
			$status = emailJobCompletion($theServerName, $theUserName, $theGroupId, 
				$theJobTimeStamp, $theJobStartedTimeStampVerbose, $duration);
		}
	}
	db_free_result($resInProgress);


	// Look up all idle remote servers (i.e. no simulation jobs in progress.)
	// Note: Each simulatoin server has exactly one row in the table.
	$sqlIdle = "SELECT server_name FROM simulation_requests WHERE in_use=0";
	$theServerName = "";
	$resIdle = db_query_params($sqlIdle, array());
	$rowsIdle = db_numrows($resIdle);
	for ($i = 0; $i < $rowsIdle; $i++) {

		// Idle remote server.
		$theServerName = db_result($resIdle, $i, 'server_name');

		// Look up next job.
		if (lookupNextSimulationJob($theServerName,
			$nextUserName, $nextGroupId, $nextJobTimeStamp,
			$nextModelName, $nextModifyScriptName, $nextSubmitScriptName, $nextPostprocessScriptName,
			$nextInstallDirName, $nextFullCfgName, $nextFullCfgPathName,
			$nextSoftwareName, $nextSoftwareVersion,
			$nextEmailAddr, $nextJobName)) {

			// Run the simulation job.
			runSimulationJob($theServerName, $nextUserName, $nextGroupId, $nextJobTimeStamp,
				$nextModelName, $nextModifyScriptName, $nextSubmitScriptName, $nextPostprocessScriptName, 
				$nextInstallDirName, $nextFullCfgName, $nextFullCfgPathName, 
				$nextSoftwareName, $nextSoftwareVersion,
				$nextEmailAddr, $nextJobName);
		}
	}
	db_free_result($resIdle);
}



// Retrieve result summary file from remote server.
function getRemoteServerResultSummary($theServerName, 
	$theUserName, $theGroupId, $theJobTimeStamp,
	&$theSummaryTxt) {

	$theSummaryTxt = false;

	// Retrieve authentication info for login to remote server.
	$status = getRemoteUserAuthentication($theServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod);
	if ($status != null) {
		// Cannot retrieve authentication information.
		return $status;
	}

	// Get remote server sftp access.
	$theSftp = getRemoteServerSftpAccess($theServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod, 
		$strRemoteServerHomeDir);
	if ($theSftp === false) {
		// Cannot get Remote Server SFTP access.
		return "***ERROR***" .  "Cannot access remote server: " . $theServerName;
	}

	// Generate job identifier.
	$jobId = $theUserName . "_" . $theGroupId . "_" . $theJobTimeStamp;

	$strRemotePathResultFile = "$jobId/post_process" . ".txt";
	$strLocalPathResultFile = "results/$jobId" . "_post_process.txt";

	// Retrieve result file.
	$status = sftpGet($theSftp, 
		$strRemoteServerHomeDir . "/" . $strRemotePathResultFile, 
		$strLocalPathResultFile);
	if ($status === false) {
		// Error getting result file.
		return false;
	}

	// Open file for reading.
	$theSummaryTxt = file_get_contents($strLocalPathResultFile);


	$strRemotePathResultFile = "$jobId/femur_kinematics.txt";
	$strLocalPathResultFile = "results/$jobId" . "_femur_kinematics.txt";
	$status = sftpGet($theSftp, 
		$strRemoteServerHomeDir . "/" . $strRemotePathResultFile, 
		$strLocalPathResultFile);

	$strRemotePathResultFile = "$jobId/femur_kinetics.txt";
	$strLocalPathResultFile = "results/$jobId" . "_femur_kinetics.txt";
	$status = sftpGet($theSftp, 
		$strRemoteServerHomeDir . "/" . $strRemotePathResultFile, 
		$strLocalPathResultFile);

	$strRemotePathResultFile = "$jobId/tibia_kinematics.txt";
	$strLocalPathResultFile = "results/$jobId" . "_tibia_kinematics.txt";
	$status = sftpGet($theSftp, 
		$strRemoteServerHomeDir . "/" . $strRemotePathResultFile, 
		$strLocalPathResultFile);

	$strRemotePathResultFile = "$jobId/tibia_kinetics.txt";
	$strLocalPathResultFile = "results/$jobId" . "_tibia_kinetics.txt";
	$status = sftpGet($theSftp, 
		$strRemoteServerHomeDir . "/" . $strRemotePathResultFile, 
		$strLocalPathResultFile);

	return true;
}


// Retrieve results (".tar.gz") file from remote server.
function getRemoteServerResults($theServerName, $theUserName, $theGroupId, $theJobTimeStamp) {

	// Retrieve authentication info for login to remote server.
	$status = getRemoteUserAuthentication($theServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod);
	if ($status != null) {
		// Cannot retrieve authentication information.
		return $status;
	}

	// Get remote server sftp access.
	$theSftp = getRemoteServerSftpAccess($theServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod, 
		$strRemoteServerHomeDir);
	if ($theSftp === false) {
		// Cannot get Remote Server SFTP access.
		return "***ERROR***" .  "Cannot access remote server: " . $theServerName;
	}

	// Generate job identifier.
	$jobId = $theUserName . "_" . $theGroupId . "_" . $theJobTimeStamp;

	$strRemotePathResultFile = "$jobId/$jobId" . ".tar.gz";
	$strLocalPathResultFile = "results/$jobId" . ".tar.gz";

	// Retrieve result file.
	$status = sftpGet($theSftp, 
		$strRemoteServerHomeDir . "/" . $strRemotePathResultFile, 
		$strLocalPathResultFile);
	if ($status === false) {
		// Error getting result file.
		return false;
	}

	return true;
}



// Check whether simulatoin job has been completed at the remote server.
function checkRemoteServerJobCompletion($theRemoteServerName, 
	$theUserName, $theGroupId, $theJobTimeStamp) {

	// Retrieve authentication info for login to remote server.
	$status = getRemoteUserAuthentication($theRemoteServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod);
	if ($status != null) {
		// Error. Do not proceed.
		return false;
	}

	// Get remote server ssh access.
	$theSsh = getRemoteServerSshAccess($theRemoteServerName, 
		$strRemoteUserName, $strRemotePassword, $intRemoteAuthMethod, 
		$strRemoteServerHomeDir);
	if ($theSsh === false) {
		// Error. Do not proceed.
		return false;
	}

	// Check whether simulation job has been completed.
	$status =  checkJobCompletion($theSsh, $theUserName, $theGroupId, $theJobTimeStamp);

/*
	// Clean up by disconnecting.
	$theSsh->disconnect();
*/

	return $status;
}


// Send email on job completion to user.
function emailJobCompletion($theServerName, $theUserName, $theGroupId, $theJobTimeStamp, 
	$theJobStartedTimeStampVerbose, $duration) {

	// Retrieve email associated with the simulation job. 
	$sqlEmail = "SELECT email, job_name FROM simulation_jobs_details WHERE " .
		"server_name='" . $theServerName . "' AND " .
		"user_name='" . $theUserName . "' AND " .
		"group_id=" . $theGroupId . " AND " .
		"job_timestamp='" . $theJobTimeStamp . "'";

	$theEmailAddr = "";
	$theJobName = "";

	$resEmail = db_query_params($sqlEmail, array());
	$rowsEmail = db_numrows($resEmail);
	for ($i = 0; $i < $rowsEmail; $i++) {
		$theEmailAddr = db_result($resEmail, $i, 'email');
		$theJobName = db_result($resEmail, $i, 'job_name');
	}
	db_free_result($resEmail);

	if ($theEmailAddr == "") {
		// Cannot get email.
		return "***ERROR***" . "Cannot get email from simulation_jobs_details table";
	}

	// Send email.
	$theJobTimeStampVerbose = date('Y-m-d H:i:s', intval(substr($theJobTimeStamp, 0, -3)));
	$strJobCompleted = date("F j, Y, g:i a") .  " '" .  $theServerName . 
		"'\nJob \"$theJobName\" from " .  "'$theUserName' (Group $theGroupId) completed.\n" . 
		"Job started at $theJobStartedTimeStampVerbose. Job submitted at $theJobTimeStampVerbose.\n" .
		"Duration: " . $duration . " seconds\n";
	if (isset($_SERVER['SERVER_NAME'])) {
		sendEmail($theEmailAddr, "Job Completed", $strJobCompleted, "nobody@" . $_SERVER['SERVER_NAME']);
	}
	else {
		// Parse configuration to get web_host.
		$theWebHost = false;
		if (file_exists("/etc/gforge/config.ini.d/debian-install.ini")) {
			// The file debian-install.ini is present.
			$arrConfig = parse_ini_file("/etc/gforge/config.ini.d/debian-install.ini");
			// Check for each parameter's presence.
			if (isset($arrConfig["web_host"])) {
				$theWebHost = $arrConfig["web_host"];
				sendEmail($theEmailAddr, "Job Completed", $strJobCompleted,
					"nobody@" . $theWebHost);
			}
		}
	}

	return null;
}


// Check whether simulatoin job has been completed.
function checkJobCompletion($theSsh, $theUserName, $theGroupId, $theJobTimeStamp) {

	// Generate job identifier.
	$jobId = $theUserName . "_" . $theGroupId . "_" . $theJobTimeStamp;
	$jobIdStart = $jobId . "_start";
	$jobIdDone = $jobId . "_done";

	// Get job start timestamp.
	$strTimeStart =  "cat $jobId/" . $jobIdStart;
	$resTimeStart =  sshExec($theSsh, $strTimeStart);

	// Get job completion timestamp.
	$strTimeDone =  "cat $jobId/" . $jobIdDone;
	$resTimeDone =  sshExec($theSsh, $strTimeDone);

	$resTimeStart = trim($resTimeStart);
	$resTimeDone = trim($resTimeDone);
	if ($resTimeDone != "" && 
		$resTimeStart != "" &&
		is_numeric($resTimeDone) &&
		is_numeric($resTimeStart)) {
		// Get duration for simulation.
		return (int) $resTimeDone - (int) $resTimeStart;
	}

	// Job not completed yet.
	return -1;
}

// Record simulation job start.
function recordRemoteServerJobStart($theRemoteServerName, $theUserName, $theGroupId, $theJobTimeStamp) {

	// Note: status=2 means job started.
	$sqlUpdate = "UPDATE simulation_jobs_details SET " .
		"status=2, " .
		"last_updated='" . time() . "' " .
		"WHERE (" . 
		"server_name='" . $theRemoteServerName . "' AND " .
		"user_name='" . $theUserName . "' AND " . 
		"group_id=" . $theGroupId . " AND " . 
		"job_timestamp='" . $theJobTimeStamp . "' " . 
		")";
	$resUpdate = db_query_params($sqlUpdate, array());
	if (!$resUpdate) {
		// Cannot update status.
		return "***ERROR***" . "Cannot update simulation_jobs_details table";
	}

	return null;
}

// Record simulation job completion.
function recordRemoteServerJobCompletion($theRemoteServerName, 
	$theUserName, $theGroupId, $theJobTimeStamp, 
	$duration, $theSummaryTxt) {

	// Note: status=3 means job completed.
	$sqlDuration = "UPDATE simulation_jobs_details SET " .
		"status=3, " .
		"last_updated='" . time() . "', " .
		"duration='" . $duration . "' ";

	if ($theSummaryTxt !== false) {
		// Has result summary. Add to job details.
		$sqlDuration = $sqlDuration . 
			", result_summary='" . $theSummaryTxt . "' ";
	}

	$sqlDuration = $sqlDuration . 
		"WHERE (" . 
		"server_name='" . $theRemoteServerName . "' AND " .
		"user_name='" . $theUserName . "' AND " . 
		"group_id=" . $theGroupId . " AND " . 
		"job_timestamp='" . $theJobTimeStamp . "' " . 
		")";
	$resDuration = db_query_params($sqlDuration, array());
	if (!$resDuration) {
		// Cannot update duration.
		return "***ERROR***" . "Cannot update simulation_jobs_details table";
	}

	return null;
}


// Reserve simulation server by setting in_use to 1.
function reserveRemoteServer($theRemoteServerName, $theUserName, 
	$theGroupId, $theJobTimeStamp, &$theJobStartedTimeStamp, $theSoftwareName) {

	$theJobStartedTimeStamp = time() . '000';
	$sqlInProgress = "UPDATE simulation_requests SET " .
		"in_use=1, " .
		"user_name='" . $theUserName . "', " . 
		"group_id=" . $theGroupId . ", " . 
		"job_timestamp='" . $theJobTimeStamp . "', " . 
		"job_started_timestamp='" . $theJobStartedTimeStamp . "', " . 
		"software_name='" . $theSoftwareName . "' " .
		"WHERE (" . 
		"server_name='" . $theRemoteServerName . "' AND " .
		"in_use=0 " .
		")";

	$resInProgress = db_query_params($sqlInProgress, array());
	if (!$resInProgress) {
		// Cannot update simulation_requests table.
		return "***ERROR***" . "Cannot update simulation_requests table: Reserve";
	}

	if (db_affected_rows($resInProgress) < 1) {
		// Cannot reserve remote server. Already in use.
		return false;
	}
	else {
		// Reserved remote server.
		return true;
	}
}


// Reset in_use flag for simulation server to make server available.
function doneRemoteServer($theRemoteServerName) {

	$sqlInProgress = "UPDATE simulation_requests SET " .
		"in_use=0, " .
		"user_name='', " . 
		"group_id=-1, " . 
		"job_timestamp='', " . 
		"job_started_timestamp='', " . 
		"software_name='' " .
		"WHERE " . 
		"server_name='" . $theRemoteServerName . "' ";
	$resInProgress = db_query_params($sqlInProgress, array());
	if (!$resInProgress) {
		// Cannot update simulation_requests table.
		return "***ERROR***" . "Cannot update simulation_requests table: Done";
	}

	return null;
}


// Build simulation wrapper file for execution in remote server.
function buildSimulationWrapper($theFullWrapperPathName,
	$theWrapperName,
	$theJobId,
	$theInstallDirName,
	$theModelName,
	$theSubmitScriptName,
	$thePostprocessScriptName,
	$theModifyScriptName,
	$theFullCfgName,
	$theSoftwarePath) {


	// If file exists, overwrite existing contents.
	// If not, create new file and store the contents.
	$fp = @fopen($theFullWrapperPathName, "w+");
	if ($fp === false) {
		// Cannot open file for saving.
		return "***ERROR***" . " Cannot save wrapper file: " . $theFullWrapperPathName;
	}

	fwrite($fp, "date +%s > $theJobId/$theJobId" . "_start" .  "\n");
	fwrite($fp, "cp $theInstallDirName/$theModelName $theJobId/" . "\n");

	// Generate string for script execution.
	$strExecScript = "STR_ERR=`cd $theJobId;" .
		"( ../$theInstallDirName/$theSubmitScriptName ";

	if (isset($theSoftwarePath) && $theSoftwarePath != "") {
		$strExecScript = $strExecScript . 
			"../$theInstallDirName/$theSoftwarePath ";
	}

	// Add model.
	$strExecScript = $strExecScript . "$theModelName ";

	if (isset($thePostprocessScriptName) && $thePostprocessScriptName != "") {
		$strExecScript = $strExecScript . 
			"../$theInstallDirName/$thePostprocessScriptName ";
	}
	if (isset($theFullCfgName) && $theFullCfgName != "") {
		// Modify configuration file.
		$strExecScript = $strExecScript . 
			"../$theInstallDirName/$theModifyScriptName $theFullCfgName " ;
	}
	$strExecScript = $strExecScript .  
		">" . $theJobId . ".stdout ) " .
		">&" . $theJobId . ".stderr" .  "`";

	fwrite($fp, $strExecScript . "\n");
	fwrite($fp, "if [ \$? -ne 0 ]; then echo \${STR_ERR} > $theJobId/$theJobId" . "_error; fi" . "\n");
	fwrite($fp, "date +%s > $theJobId/$theJobId" . "_done" .  "\n");
	fwrite($fp, "tar cf $theJobId/$theJobId" . ".tar --exclude=*.tar $theJobId/*" .  "\n");
	fwrite($fp, "gzip $theJobId/$theJobId" . ".tar" .  "\n");
	//fwrite($fp, "rm $theJobId/$theWrapperName" . "\n");
	fclose($fp);

	return null;
}


// Execute simulation job on remote server.
function executeSimulation($theSsh, $theJobId, $theFullWrapperName, $theFullCfgName) {

	$strResMkdir =  sshExec($theSsh, 'mkdir ./' . $theJobId);
	$strResChmod =  sshExec($theSsh, 'chmod a+x ' . $theFullWrapperName);
	$strResMv =  sshExec($theSsh, 'mv ' . $theFullWrapperName . ' ./' . $theJobId);
	if (isset($theFullCfgName) && $theFullCfgName != "") {
		// Modify configuration file.
		$strResMvCfg =  sshExec($theSsh, 'mv ' . $theFullCfgName . ' ./' . $theJobId);
	}
//	$strResExec =  sshExec($theSsh, './' . $theJobId . '/' . $theFullWrapperName . " &");
	$strResExec =  sshExec($theSsh, './' . $theJobId . '/' . $theFullWrapperName . " >> /dev/null 2>&1 &");
}


// Send back result via JSON.
function sendResult($theResult, $theStatus) {

/*
	// Set the job status.
	$theResult["JobStatus"] = $theStatus;

	// Encode the result array.
	$strRes = json_encode($theResult);
	echo $strRes;
*/
	echo $theResult["JobResultOutput"] . "<BR/>\n";
}

// Send email to user.
function sendEmail($theEmailAddr, $theTitle, $theMsgBody, $theSenderEmailAddr) {

	// Email user.
	mail($theEmailAddr, $theTitle, $theMsgBody, "From: " . $theSenderEmailAddr . "\n");
}


// Get simulation license.
function getSimulationLicense($groupId) {

	$license = "";

	$sql = "SELECT license_agreement FROM simulation_job " .
		"WHERE group_id=$1 ";
	$result = db_query_params($sql, array($groupId));
	$rows = db_numrows($result);
	for ($i = 0; $i < $rows; $i++) {
		$license = db_result($result, $i, 'license_agreement');
	}
	db_free_result($result);

	return $license;
}

// Get simulation description.
function getSimulationDescription($groupId) {

	$desc = "";

	$sql = "SELECT description FROM simulation_job " .
		"WHERE group_id=$1 ";
	$result = db_query_params($sql, array($groupId));
	$rows = db_numrows($result);
	for ($i = 0; $i < $rows; $i++) {
		$desc = db_result($result, $i, 'description');
	}
	db_free_result($result);

	return $desc;
}

// Get simulation quota.
function getSimulationQuota($userName, $groupId) {

	$quota = 0;
	$sql = "SELECT quota FROM simulation_quota " .
		"WHERE group_id=$1 " .
		"AND user_name=$2 ";
	$result = db_query_params($sql, array($groupId, $userName));
	$rows = db_numrows($result);
	for ($i = 0; $i < $rows; $i++) {
		$quota = db_result($result, $i, 'quota');
	}
	db_free_result($result);

	return $quota;
}

// Get simulation usage.
function getSimulationUsage($userName, $groupId, $since=0) {

	$usage = 0;
	$sql = "SELECT duration FROM simulation_jobs_details " .
		"WHERE group_id=$1 " .
		"AND user_name=$2 ";
	if ($since != 0) {
		$sql .= "AND CAST(coalesce(last_updated, '0') AS integer) > $since";
	}
	$result = db_query_params($sql, array($groupId, $userName));
	$rows = db_numrows($result);
	for ($i = 0; $i < $rows; $i++) {
		$strDuration = db_result($result, $i, 'duration');
		$usage += intval($strDuration);
	}
	db_free_result($result);

	return $usage;
}

// Clean simulation archive older than TIMEOFFSET_DELETION.
function cleanSimulationArchives() {
	// Go back TIMEOFFSET_DELETION.
	$timeCheck = time() - TIMEOFFSET_DELETION;

	$dirPath = "results/*" . ".tar.gz";
	foreach (glob($dirPath) as $theFileName) {
		if (filemtime($theFileName) <= $timeCheck) {
			unlink($theFileName);
		}
	}
}


?>
