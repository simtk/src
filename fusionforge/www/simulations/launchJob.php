<?php

/**
 *
 * launchJob.php
 * 
 * Launch the request simulation job.
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
 
// Launch the requested job.

require_once "../env.inc.php";
require_once $gfcommon.'include/pre.php';
require_once('util_simulations.php');

// Get group id.
$group_id = getIntFromPost("GroupId");
$groupObj = group_get_object($group_id);
if (!$groupObj) {
	exit_no_group();
}

// Check permission and prompt for login if needed.
if (!session_loggedin() || !($u = &session_get_user())) {
	exit_not_logged_in();
}
//session_require_perm('simulations', $group_id, 'write') ;
$isPermitted = checkSimulationPermission($groupObj, $u);
if (!$isPermitted) {
	echo "***ERROR***" . "Simulation job request is for members only!!!";
	return;
}

// Get user id
// Note: User is logged in already!!!
$userID = $u->getID();
$userName = $u->getUnixName();

// Retrieve parameters for job submitted.
$strRemoteServerName = $_POST["ServerName"];
$emailAddr = $_POST["EmailAddr"];
$jobName = $_POST["JobName"];
$modelName = $_POST["ModelName"];
$modifyScriptName = $_POST["ModifyScriptName"];
$submitScriptName = $_POST["SubmitScriptName"];
$postprocessScriptName = $_POST["PostprocessScriptName"];
$installDirName = $_POST["InstallDirName"];
$softwareName = $_POST["SoftwareName"];
$softwareVersion = $_POST["SoftwareVersion"];
$cfgName = $_POST["ConfigFileName"];
$execCheck = $_POST["ExecCheck"];
$maxRunTime = $_POST["MaxRunTime"];
$modifyModel = $_POST["ModifyModel"];

// NOTE: All newlines were with replaced <br/> before sending via POST
// because Firefox strips the character, but other browsers are fine.
// Replacing <br/> with newlines to restore the content.
$cfgText = $_POST["ConfigText"];
$cfgText = str_replace("<br/>", "\n", $cfgText);

// Submit request and get result.
$resJobRequest = requestSimulationJob(
	$strRemoteServerName,
	$userName, 
	$emailAddr,
	$jobName,
	$group_id, 
	$modelName, 
	$modifyScriptName,
	$submitScriptName,
	$postprocessScriptName,
	$installDirName,
	$softwareName, 
	$softwareVersion,
	$cfgName,
	$cfgText,
	$execCheck,
	$maxRunTime,
	$modifyModel);

echo json_encode($resJobRequest);

?>
