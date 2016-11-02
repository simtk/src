<?php

/**
 *
 * util_submitted_jobs.php
 * 
 * Utilities for handling submitted jobs.
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
 
require_once('util_simulations.php');

// Retrieve user's submitted jobs.
function getSubmittedJobs($theUserName, &$theJobs, &$theGroupNames) {

	$theJobs = array();
	$theGroupNames = array();
	$theJobRawDataFile = null;

	// Retrieve details on all simulation jobs of given user.
	$sqlUserJobs = "SELECT status, d.server_name, email, d.group_id, " .
		"model_name, d.software_name, d.software_version, " .
		"job_timestamp, duration, cfg_pathname_1, " .
		"job_name, last_updated, server_alias " .
		"FROM simulation_jobs_details AS d " .
		"JOIN simulation_servers s " .
		"ON d.group_id=s.group_id " .
		"AND d.server_name=s.server_name " .
		"WHERE user_name='" . $theUserName . "' " .
		"ORDER BY d.group_id, status, job_timestamp DESC";
	$resUserJobs = db_query_params($sqlUserJobs, array());

	// Retrieve job details.
	$rowsUserJobs = db_numrows($resUserJobs);

	$arrProblematicServers = array();
	for ($i = 0; $i < $rowsUserJobs; $i++) {
		$theGroupId = db_result($resUserJobs, $i, 'group_id');

		// Array for storing the job information.
		$jobInfo = array();

		$jobStatus =  db_result($resUserJobs, $i, 'status');
		if ($jobStatus == 1) {
			$strJobStatus = "Submitted";
		}
		else if ($jobStatus == 2) {
			$strJobStatus = "Started";
		}
		else if ($jobStatus == 3) {
			$strJobStatus = "Completed";
		}
		$jobInfo['status'] = $strJobStatus;
		$jobInfo['server_name']  = db_result($resUserJobs, $i, 'server_alias');
		$jobInfo['email']  = db_result($resUserJobs, $i, 'email');
		$jobInfo['model_name']  = db_result($resUserJobs, $i, 'model_name');
		$jobInfo['software_name']  = db_result($resUserJobs, $i, 'software_name');
		$jobInfo['software_version']  = db_result($resUserJobs, $i, 'software_version');
		$jobInfo['job_timestamp']  = db_result($resUserJobs, $i, 'job_timestamp');
		$jobInfo['duration']  = db_result($resUserJobs, $i, 'duration');
		$jobInfo['last_updated']  = db_result($resUserJobs, $i, 'last_updated');

		$cfgPathname  = db_result($resUserJobs, $i, 'cfg_pathname_1');
		if ($cfgPathname != "" && file_exists($cfgPathname)) {
			$jobInfo['cfg_pathname'] = $cfgPathname;
		}

		// Get the server name.
		$theServerName = $jobInfo['server_name'];

		// Retrieve the job name.
		$theJobName = db_result($resUserJobs, $i, 'job_name');
		if (trim($theJobName) == "") {
			$theJobName = date('Y-m-d H:i:s', intval(substr($jobInfo['job_timestamp'], 0, -3)));
		}

		if ($strJobStatus == "Completed") {
			// Get summary data.
			getSubmittedJobSummary($theUserName, $theJobName, $jobSummaryData);

			// Keep summary if content has one line only.
			$theSummaryText = "";
			$theSummary = trim($jobSummaryData['summary']);
			$arrSummary = explode("\n", $theSummary);
			if (count($arrSummary) <= 1) {
				$theSummaryText = $theSummary;
			}
			$jobInfo['summary'] = $theSummaryText;

			$lastUpdated = $jobInfo['last_updated'];

			// Add offset to job time as time to delete.
			$timeToDelete = $lastUpdated + TIMEOFFSET_DELETION;
			$jobInfo['time_to_delete'] = $timeToDelete;

			// NOTE: Check whether time has past the deletion time 
			// before fetching raw data file.
			// Servers for old raw data files may no longer be accessible.
			$timeNow = time();
			if ($timeToDelete > $timeNow) {
				// Get raw data file
				// Check whether server has past problem in access first.
				if (array_key_exists($theServerName, $arrProblematicServers) === false) {
					// No access problem yet.
					$status = getSubmittedJobRawData($theUserName, $theJobName, 
						true, $theJobRawDataFile);
					if ($status !== true) {
						// Problem accessing server. Remember this server.
						// Do not try the server again.
						$arrProblematicServers[$theServerName] = $theServerName;
					}
				}
			}

			// Generate job identifier.
			$theJobTimeStamp = $jobInfo['job_timestamp'];
			$jobId = $theUserName . "_" . $theGroupId . "_" . $theJobTimeStamp;
			// Filename of summary.
			$theJobSummaryDataFile = "results/" . $jobId . "_post_process.txt";
			if (file_exists($theJobSummaryDataFile)) {
				// Check for existence of summary file.
				$jobInfo['pathSummaryFile'] = $theJobSummaryDataFile;
			}

			// Check for existence of raw data file.
			if ($theJobRawDataFile !== null &&
				file_exists($theJobRawDataFile)) {
				$jobInfo['pathRawFile'] = $theJobRawDataFile;
			}
		}

		if (!isset($theJobs[$theGroupId])) {
			// The array of job infos of the group_id is not yet present. Create it.
			$jobInfosByGroupId = array();
			$theJobs[$theGroupId] = $jobInfosByGroupId;
		}

		// Retrieve the array of job infos given the group_id.
		$jobInfosByGroupId = $theJobs[$theGroupId];

		// Add job info of given job name.
		$jobInfosByGroupId[$theJobName] = $jobInfo;

		// Store the updated array.
		$theJobs[$theGroupId] = $jobInfosByGroupId;

		// Retrieve group name given the group id.
		if (!isset($theGroupNames[$theGroupId])) {
//			$sqlGroupName = "SELECT unix_group_name FROM groups WHERE group_id=$theGroupId";
			$sqlGroupName = "SELECT group_name FROM groups WHERE group_id=$theGroupId";
			$resGroupName = db_query_params($sqlGroupName, array());
			$rowsGroupName = db_numrows($resGroupName);
			for ($cntGrps = 0; $cntGrps < $rowsGroupName; $cntGrps++) {
//				$theGroupName = db_result($resGroupName, $cntGrps, 'unix_group_name');
				$theGroupName = db_result($resGroupName, $cntGrps, 'group_name');
			}
			$theGroupNames[$theGroupId] = $theGroupName;
			db_free_result($resGroupName);
		}
	}
	db_free_result($resUserJobs);
}


// Retrieve user's submitted job info.
function getSubmittedJobData($theUserName, $theJobName, &$theJobInfo) {

	$theJobInfo = array();

	// Retrieve details on the simulation job of given user.
	$sqlUserJob = "SELECT status, server_name, email, group_id, " .
		"job_timestamp, duration, cfg_pathname_1, last_updated " .
		"FROM simulation_jobs_details WHERE " .
		"user_name='" . $theUserName . "' AND " .
		"job_name='" . $theJobName . "'";
	$resUserJob = db_query_params($sqlUserJob, array());

	// Retrieve job details.
	$rowsUserJob = db_numrows($resUserJob);

	for ($i = 0; $i < $rowsUserJob; $i++) {

		$jobStatus =  db_result($resUserJob, $i, 'status');
		if ($jobStatus == 1) {
			$strJobStatus = "Submitted";
		}
		else if ($jobStatus == 2) {
			$strJobStatus = "Started";
		}
		else if ($jobStatus == 3) {
			$strJobStatus = "Completed";
		}
		$theJobInfo['status'] = $strJobStatus;
		$theJobInfo['server_name']  = db_result($resUserJob, $i, 'server_name');
		$theJobInfo['email']  = db_result($resUserJob, $i, 'email');
		$theJobInfo['job_timestamp']  = db_result($resUserJob, $i, 'job_timestamp');
		$theJobInfo['duration']  = db_result($resUserJob, $i, 'duration');
		$theJobInfo['last_updated']  = db_result($resUserJob, $i, 'last_updated');
		$theJobInfo['group_id']  = db_result($resUserJob, $i, 'group_id');

		$cfgPathname  = db_result($resUserJob, $i, 'cfg_pathname_1');
		if ($cfgPathname != "" && file_exists($cfgPathname)) {
			$theJobInfo['cfg_pathname'] = $cfgPathname;
		}
	}
	db_free_result($resUserJob);
}

// Retrieve user's submitted job summary.
function getSubmittedJobSummary($theUserName, $theJobName, &$theJobSummary) {

	$theJobSummary = array();

	// Retrieve summary on the simulation job of given user.
	$sqlUserJob = "SELECT result_summary " .
		"FROM simulation_jobs_details WHERE " .
		"user_name='" . $theUserName . "' AND " .
		"job_name='" . $theJobName . "'";
	$resUserJob = db_query_params($sqlUserJob, array());

	// Retrieve job summary.
	$rowsUserJob = db_numrows($resUserJob);

	for ($i = 0; $i < $rowsUserJob; $i++) {
		$theJobSummary['summary'] = db_result($resUserJob, $i, 'result_summary');
	}
	db_free_result($resUserJob);
}

// Retrieve user's submitted job's raw data.
function getSubmittedJobRawData($theUserName, $theJobName, $isFetchFile, &$theJobRawData) {

	$theJobRawData = false;

	// Retrieve raw data on the simulation job of given user.
	$sqlUserJob = "SELECT server_name, group_id, job_timestamp " .
		"FROM simulation_jobs_details WHERE " .
		"user_name='" . $theUserName . "' AND " .
		"job_name='" . $theJobName . "'";
	$resUserJob = db_query_params($sqlUserJob, array());

	// Retrieve job details.
	$rowsUserJob = db_numrows($resUserJob);
	if ($rowsUserJob == 0) {
		// Data not available.
		return;
	}
	for ($i = 0; $i < $rowsUserJob; $i++) {
		$theServerName = db_result($resUserJob, $i, 'server_name');
		$theGroupId = db_result($resUserJob, $i, 'group_id');
		$theJobTimeStamp = db_result($resUserJob, $i, 'job_timestamp');
	}
	db_free_result($resUserJob);


	// Generate job identifier.
	$jobId = $theUserName . "_" . $theGroupId . "_" . $theJobTimeStamp;

	// Filename of raw data tar.gz file.
	$filenameJobRawData = "results/" . $jobId . ".tar.gz";

	$theJobRawData = $filenameJobRawData;
	if ($isFetchFile === false) {
		// Done! Get file name only. Does not fetch the file.
		return;
	}

	// Fetch file.

	// Test whether file exists. If so, no need to fetch again.
	$isFileExists = file_exists($filenameJobRawData);
	if ($isFileExists === false) {
		// File is not yet present. Fetch file.

		// Get the tar.gz file from remote server that has all raw data for the given job.
		$status = getRemoteServerResults($theServerName, $theUserName, 
			$theGroupId, $theJobTimeStamp);
		if ($status !== true) {
			// Cannot retrieve file.
			$theJobRawData = false;

			if (strpos($status, "***ERROR***Cannot access remote server") !== false) {
				// Problem accessing server. Return false status.
				return false;
			}
		}
	}
	else {
		// File cached. No need to retrieve.
	}

	return true;
}

// JSON-encode job details and send back data.
function sendEncodedJobDetails($theJobs, $theGroupNames) {

	// Encode result with JSON.
	$encodedJobs = json_encode($theJobs);

	// Send back data.
	echo $encodedJobs;
}

?>

