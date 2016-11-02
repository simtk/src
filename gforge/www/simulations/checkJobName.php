<?php

/**
 *
 * checkJobName.php
 * 
 * Check whether job name is already present for the user.
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

$theResult = array();

// Retrieve parameters.
$theUserName = user_getname();
$theJobName = $_POST["JobName"];

// Check whether job name is already present for the user.
$sqlExistingJobName = "SELECT job_name FROM simulation_jobs_details WHERE " .
		"user_name='" . $theUserName . "' AND " .
		"job_name='" . $theJobName . "'";

$resExistingJobName = db_query_params($sqlExistingJobName, array());
$rowsExistingJobName = db_numrows($resExistingJobName);

$theResult["UserName"] = $theUserName;
$theResult["JobName"] = $theJobName;
if ($rowsExistingJobName > 0) {
	// Job name is already present.
	$theResult["isJobValid"] = false;
}
else {
	// Job name is not used yet.
	$theResult["isJobValid"] = true;
}
db_free_result($resExistingJobName);

// Encode the result.
$strRes = json_encode($theResult);
echo $strRes;

?>

