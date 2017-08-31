<?php

/**
 *
 * cancelJob.php
 * 
 * Cancel simulation job for the user.
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
require_once 'util_simulations.php';

// Retrieve parameters.
$theUserName = user_getname();
$theJobName = $_GET["JobName"];
$theGroupId = $_GET["GroupId"];

// Look up simulation job for the user.
$sqlJob = "SELECT server_name, job_timestamp FROM simulation_jobs_details " .
	"WHERE user_name='" . $theUserName . "' AND " .
	"group_id=" . $theGroupId . " AND " .
	"job_name='" . $theJobName . "'";

$resJob = db_query_params($sqlJob, array());
$rowsJob = db_numrows($resJob);

$theResult["Status"] = false;
if ($rowsJob > 0) {
	$theJobTimeStamp = db_result($resJob, 0, 'job_timestamp');
	$theServerName = db_result($resJob, 0, 'server_name');
	$theResult["JobTimeStamp"] = $theJobTimeStamp;
	$theResult["ServerName"] = $theServerName;

	// Cancel simulation job.
	$status = cancelRemoteServerJob($theServerName, $theUserName, $theGroupId, $theJobTimeStamp);
	$theResult["Status"] = $status;
}
db_free_result($resJob);

// Encode the result.
$strRes = json_encode($theResult);

// Redirect page back to viewJobs.php.
session_redirect("/simulations/viewJobs.php?group_id=$theGroupId&job_cancelled=1");

?>

