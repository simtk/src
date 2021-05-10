<?php

/**
 *
 * createDataShareStudies.php
 * 
 * Cronjob to create data share studies.
 * 
 * Copyright 2005-2021, SimTK Team
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

// Find approved studies waiting to be created.
$strQueryStudies = "SELECT study_id, " .
	"pd.group_id as group_id, " .
	"pd.title as title, " .
	"pd.description as description, " .
	"subject_prefix, " .
	"active, " .
	"realname, user_name, email, group_name " .
	"FROM plugin_datashare pd " .
	"JOIN users u " .
	"ON pd.user_id=u.user_id " .
	"JOIN groups g " .
	"ON pd.group_id=g.group_id " .
	"WHERE active=-2 OR active=-4";
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
	$active = $rowStudies["active"];
	$study_id = $rowStudies["study_id"];
	$group_id = $rowStudies["group_id"];
	$study_title = $rowStudies["title"];
	$description = $rowStudies["description"];
	$subject_prefix = $rowStudies["subject_prefix"];
	$realName = $rowStudies["realname"];
	$userName = $rowStudies["user_name"];
	$email = $rowStudies["email"];
	$groupName = $rowStudies["group_name"];

	if ($active == -2) {
		// Create approved study in Data Share server.
		$statusCreate = createStudy($study_id, $subject_prefix);

		if ($statusCreate === true) {
			// Study approved and created. Email user.
			$message = "The new Data Share study you requested on SimTK has been created.\n\n" .
				"Study Title: " . $study_title . "\n" .
				"Description: " . $description . "\n" .
				"Project Name: " . $groupName . "\n" .
				"Top Level Folders Prefix: " . $subject_prefix . "\n" .
				"Submitter: " . $realName . " ($userName)\n\n" .
				"Please visit this study at the following URL:\n" .
				util_make_url("plugins/datashare?group_id=$group_id");
			util_send_message($email,
				sprintf('New %s Study created', forge_get_config('forge_name')),
				$message);
		}
	}
	else if ($active == -4) {
		// Update study in Data Share server.
		$statusUpdate = updateStudy($study_id, $subject_prefix);

		if ($statusUpdate === true) {
			// Study updated. Email user.
			$message = "The Data Share study on SimTK has been updated.\n\n" .
				"Study Title: " . $study_title . "\n" .
				"Description: " . $description . "\n" .
				"Project Name: " . $groupName . "\n" .
				"Top Level Folders Prefix: " . $subject_prefix . "\n" .
				"Submitter: " . $realName . " ($userName)\n\n" .
				"Please visit this study at the following URL:\n" .
				util_make_url("plugins/datashare?group_id=$group_id");
			util_send_message($email,
				sprintf('%s Study updated', forge_get_config('forge_name')),
				$message);
		}
	}
}

 
// Create a study.
// The local script in SimTK server executes study creation commands remotely
// at the DataShare server.
function createStudy($studyId, $subject_prefix) {

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

	// Create study in Data Share server.
	exec("/usr/share/gforge/cronjobs/db/createDataShareStudy $studyId $subject_prefix", 
		$arrRes, 
		$status);
	if ($status != 0) {

		// Error creating study.
		$msgErr = "Error creating study at remote server: study id is $studyId\n";
		// Collect output messages when executing study creation commands.
		foreach ($arrRes as $strOut) {
			$msgErr .= $strOut . "\n";
		}
		error_log($msgErr);

		// Update plugin_datashare db table.
		$resStudies = db_query_params("UPDATE plugin_datashare set active=-3 " .
			"WHERE study_id=$1",
			array($studyId));
		if (!$resStudies) {
			error_log("Cannot update plugin_datashare for problem creating study: $studyId \n");
		}

		$admins = RBACEngine::getInstance()->getUsersByAllowedAction('approve_projects', -1);
		foreach ($admins as $admin) {
			$admin_email = $admin->getEmail();
			setup_gettext_for_user ($admin);
			util_send_message($admin_email,
				"Error creating Data Share study",
				$msgErr);
			setup_gettext_from_context();
		}

		return false;
	}
	else {
		// Successfully created study. Update plugin_datashare to set active to 1.
		$resStudies = db_query_params("UPDATE plugin_datashare set active=1 " .
			"WHERE study_id=$1",
			array($studyId));

		if (!$resStudies) {
			error_log("Cannot update plugin_datashare after successfully creating study: $studyId \n");
			return false;
		}
	}

	return true;
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
	// NOTE: The script "createDataShareStudy" handles both study creation and update.
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
		error_log($msgErr);

		// Update plugin_datashare db table.
		$resStudies = db_query_params("UPDATE plugin_datashare set active=-3 " .
			"WHERE study_id=$1",
			array($studyId));
		if (!$resStudies) {
			error_log("Cannot update plugin_datashare for problem updating study: $studyId \n");
		}

		$admins = RBACEngine::getInstance()->getUsersByAllowedAction('approve_projects', -1);
		foreach ($admins as $admin) {
			$admin_email = $admin->getEmail();
			setup_gettext_for_user ($admin);
			util_send_message($admin_email,
				"Error updating Data Share study",
				$msgErr);
			setup_gettext_from_context();
		}

		return false;
	}
	else {
		// Successfully updated study. Update plugin_datashare to set active to 1.
		$resStudies = db_query_params("UPDATE plugin_datashare set active=1 " .
			"WHERE study_id=$1",
			array($studyId));

		if (!$resStudies) {
			error_log("Cannot update plugin_datashare after successfully updating study: $studyId \n");
			return false;
		}
	}

	return true;
}

?>

