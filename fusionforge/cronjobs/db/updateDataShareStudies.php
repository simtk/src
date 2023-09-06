<?php

/**
 *
 * updateDataShareStudies.php
 * 
 * Cronjob to update data share studies.
 * 
 * Copyright 2005-2024, SimTK Team
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

require dirname(__FILE__).'/../../common/include/env.inc.php';
require_once $gfcommon.'include/pre.php';

// Find approved and activated studies.
$strQueryStudies = "SELECT study_id, " .
	"subject_prefix " .
	"FROM plugin_datashare pd " .
	"WHERE active=1";
$resStudies = db_query_params($strQueryStudies, array());
if (!$resStudies) {
	error_log("db query error on plugin_datashare: $strQueryStudies \n");
	exit;
}
if (db_numrows($resStudies) < 1) {
	// No data yet. Done.
	exit;
}
while ($rowStudies = db_fetch_array($resStudies)) {
	// Get study info.
	$study_id = $rowStudies["study_id"];
	$subject_prefix = $rowStudies["subject_prefix"];

	// Update study in Data Share server.
	$statusUpdate = updateStudy($study_id, $subject_prefix);
	if ($statusUpdate === true) {
		// Study updated.
		echo "study $study_id has been updated.\n\n";
	}
}

// Update a study.
// The local script in SimTK server executes study creation commands remotely
// at the DataShare server.
function updateStudy($studyId, $subject_prefix) {

	if (!is_numeric($studyId)) {
		error_log("Invalid study id: $studyId \n");
		return false;
	}
	if (!preg_match('/^[-A-Za-z0-9_.]+\z/', $subject_prefix)) {
		error_log("Invalid subject prefix: $subject_prefix \n");
		return false;
	}
	$studyId = (int) $studyId;

	$arrRes = array();

	// Update study in Data Share server.
	exec("/usr/share/gforge/cronjobs/db/updateDataShareStudy $studyId $subject_prefix", 
		$arrRes, 
		$status);
	if ($status != 0) {

		// Error updating study.
		$msgErr = "Error updating study at remote server: study id is $studyId\n";
		// Collect output messages when executing study update commands.
		foreach ($arrRes as $strOut) {
			$msgErr .= $strOut . "\n";
		}
		echo $msgErr;

		return false;
	}

	return true;
}

?>

