<?php

/**
 *
 * roleUtils.php
 *
 * Copyright 2005-2019, SimTK Team
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

// PHP Semaphore is disabled by default and
// requires recompilation to enable the feature.
//
// For our current usage, acquiring and releasing
// exclusive file lock on files with given IDs would suffice.

define("SEM_FILE_LOCATION", "/usr/share/gforge/semaphore/");
define("ROLE_CREATE_LOCK", "999");

// Acquire file lock to use as sem_acquire().
function mySemAcquire($theId) {
	$fp = fopen(SEM_FILE_LOCATION . $theId , 'w');
	if (!$fp) {
		// Cannot get file pointer.
		return false;
	}
	// Acquire exclusive file lock to use as semaphore with max_acquire of 1.
	if (!flock($fp, LOCK_EX)) {
		// Cannot obtain file lock.
		return false;
	}
	// Has acquired file lock.
	return $fp;
}

// Release file lock to use as sem_release().
function mySemRelease($fp) {
	// Release the lock.
	fclose($fp);
}

// Insert into pfo_role table.
// Return the associated role_id from the pfo_role table.
function insertRole($groupId, $roleName) {

	// Acquire semaphore.
	// NOTE: This process is necessary to ensure that
	// no other thread is adding to the pfo_role table.
	$semId = mySemAcquire(ROLE_CREATE_LOCK);

	// Check whether entry exists already.
	$strCheckPresence = "SELECT role_id FROM pfo_role WHERE " .
		"home_group_id=" .  $groupId . " AND " .
		"role_name='" . $roleName . "'";
	$resRoleId = db_query_params($strCheckPresence, array());
	if (!$resRoleId || db_numrows($resRoleId) < 1) {
		// Entry not present yet. Insert entry.

		// Insert home_group_id and role_name into pfo_role table.
		$strPFORoleInsert = "INSERT INTO pfo_role " .
			"(home_group_id, role_name) VALUES (" .
			$groupId . ",'" . $roleName . "')";
		//echo $strPFORoleInsert . "\n";
		$resInsert = db_query_params($strPFORoleInsert, array());
		if (!$resInsert || db_affected_rows($resInsert) < 1) {
			echo "Error: $strPFORoleInsert\n";
		}

		// Find role_id generated in pfo_role from the last insert.
		$strRoleIdSelect = "SELECT max(role_id) FROM pfo_role";
		$resRoleId = db_query_params($strRoleIdSelect, array());
		$row = db_fetch_array($resRoleId);
		$theRoleId = $row['max'];

		// Insert into pfo_role_setting table.
		//echo "pfo_role RoleId: $theRoleId \n";
		addToRoleSetting($roleName, $theRoleId, $groupId);
	}
	else {
		// Retrieve entry that existed already.
        	$row = db_fetch_array($resRoleId);
		$theRoleId = $row['role_id'];
	}

	// Release semaphore.
	mySemRelease($semId);

	return $theRoleId;
}


// Insert into pfo_role_setting table.
function addToRoleSetting($roleName, $roleId, $groupId) {

	switch ($roleName) {
	case 'Admin':
		insertRoleSetting($roleId, $groupId, "project_admin", 1);
		insertRoleSetting($roleId, $groupId, "scm", 2);
		insertRoleSetting($roleId, $groupId, "docman", 4);
		insertRoleSetting($roleId, $groupId, "forum_admin", 1);
		insertRoleSetting($roleId, $groupId, "tracker_admin", 1);
		insertRoleSetting($roleId, $groupId, "new_tracker", 15);
		insertRoleSetting($roleId, $groupId, "project_read", 1);
		insertRoleSetting($roleId, $groupId, "frs", 3);
    insertRoleSetting($roleId, $groupId, "datashare", 3);
		insertRoleSetting($roleId, $groupId, "pubs", 1);
		insertRoleSetting($roleId, $groupId, "plugin_moinmoin_access", 3);

		insertTrackersRoles($roleId, $roleName, $groupId);
		break;
	case 'Developer':
		insertRoleSetting($roleId, $groupId, "project_admin", 0);
		insertRoleSetting($roleId, $groupId, "scm", 2);
		insertRoleSetting($roleId, $groupId, "docman", 2);
		insertRoleSetting($roleId, $groupId, "forum_admin", 0);
		insertRoleSetting($roleId, $groupId, "tracker_admin", 0);
		insertRoleSetting($roleId, $groupId, "new_tracker", 11);
		insertRoleSetting($roleId, $groupId, "project_read", 1);
		insertRoleSetting($roleId, $groupId, "frs", 2);
    insertRoleSetting($roleId, $groupId, "datashare", 2);
		insertRoleSetting($roleId, $groupId, "pubs", 0);
		insertRoleSetting($roleId, $groupId, "plugin_moinmoin_access", 2);

		insertTrackersRoles($roleId, $roleName, $groupId);
		break;
	case 'Read-Only Member':
		insertRoleSetting($roleId, $groupId, "project_admin", 0);
		insertRoleSetting($roleId, $groupId, "scm", 1);
		insertRoleSetting($roleId, $groupId, "docman", 2);
		insertRoleSetting($roleId, $groupId, "forum_admin", 0);
		insertRoleSetting($roleId, $groupId, "tracker_admin", 0);
		insertRoleSetting($roleId, $groupId, "new_tracker", 1);
		insertRoleSetting($roleId, $groupId, "project_read", 1);
		insertRoleSetting($roleId, $groupId, "frs", 2);
    insertRoleSetting($roleId, $groupId, "datashare", 2);
		insertRoleSetting($roleId, $groupId, "pubs", 0);
		insertRoleSetting($roleId, $groupId, "plugin_moinmoin_access", 1);

		insertTrackersRoles($roleId, $roleName, $groupId);
		break;
	case 'Read-Write Member':
		insertRoleSetting($roleId, $groupId, "project_admin", 0);
		insertRoleSetting($roleId, $groupId, "scm", 2);
		insertRoleSetting($roleId, $groupId, "docman", 4);
		insertRoleSetting($roleId, $groupId, "forum_admin", 1);
		insertRoleSetting($roleId, $groupId, "tracker_admin", 1);
		insertRoleSetting($roleId, $groupId, "new_tracker", 15);
		insertRoleSetting($roleId, $groupId, "project_read", 1);
		insertRoleSetting($roleId, $groupId, "frs", 3);
    insertRoleSetting($roleId, $groupId, "datashare", 3);
		insertRoleSetting($roleId, $groupId, "pubs", 1);
		insertRoleSetting($roleId, $groupId, "plugin_moinmoin_access", 2);

		insertTrackersRoles($roleId, $roleName, $groupId);
		break;
	case 'Senior Developer':
		insertRoleSetting($roleId, $groupId, "project_admin", 0);
		insertRoleSetting($roleId, $groupId, "scm", 2);
		insertRoleSetting($roleId, $groupId, "docman", 4);
		insertRoleSetting($roleId, $groupId, "forum_admin", 1);
		insertRoleSetting($roleId, $groupId, "tracker_admin", 1);
		insertRoleSetting($roleId, $groupId, "new_tracker", 15);
		insertRoleSetting($roleId, $groupId, "project_read", 1);
		insertRoleSetting($roleId, $groupId, "frs", 3);
    insertRoleSetting($roleId, $groupId, "datashare", 3);
		insertRoleSetting($roleId, $groupId, "pubs", 1);
		insertRoleSetting($roleId, $groupId, "plugin_moinmoin_access", 2);

		insertTrackersRoles($roleId, $roleName, $groupId);
		break;
	default:
		echo "DEFAULT!!!: $roleName, $roleId, $groupId \n";
		insertRoleSetting($roleId, $groupId, "project_read", 1);
	}
}


// Insert into pfo_role_setting table.
function insertRoleSetting($roleId, $groupId, $sectionName, $value) {

	// Check whether anonymous role entry exists in pfo_role_setting table already.
	$strCheckPresence = "SELECT role_id FROM pfo_role_setting WHERE " .
		"role_id=" . $roleId . " AND " .
		"section_name='" . $sectionName . "' AND " .
		"ref_id=" . $groupId;
	$resIsPresent = db_query_params($strCheckPresence, array());
	if (!$resIsPresent || db_numrows($resIsPresent) < 1) {
		// Entry not present yet. Insert entry.

		// Insert group_id into pfo_role_setting table.
		//echo "pfo_role_setting: " . $roleId . ":" . $groupId . "\n";
		$strPFORoleSettingInsert = "INSERT INTO pfo_role_setting " .
			"(role_id, section_name, ref_id, perm_val) " .
			"VALUES (" .
			$roleId . ",'" . $sectionName . "'," . $groupId . "," . $value . ")";
		//echo "pfo_role_setting: $strPFORoleSettingInsert \n";
		$resInsert = db_query_params($strPFORoleSettingInsert, array());
		if (!$resInsert || db_affected_rows($resInsert) < 1) {
			echo "Error: $strPFORoleSettingInsert\n";
		}
	}
	else {
		// Entry present already. Update the value.
		$strPFORoleSettingUpdate = 'UPDATE pfo_role_setting ' .
               	       'SET perm_val=$1 ' .
			'WHERE role_id=$2 ' .
			'AND section_name=$3 ' .
			'AND ref_id=$4';
		$resUpdate = db_query_params($strPFORoleSettingUpdate,
			array($value,
				$roleId,
				$sectionName,
				$groupId
			)
		);
		if (!$resUpdate || db_affected_rows($resUpdate) < 1) {
			echo "Error: $strPFORoleSettingUpdate\n";
		}
	}
}


// Insert tracker into pfo_role_setting table.
function insertTrackersRoles($roleId, $roleName, $groupId) {

	$theVal = 0;
	switch ($roleName) {
	case "Admin":
		// Tech & manager
		$theVal = 15;
		break;
	case "Developer":
		// Technician
		$theVal = 11;
		break;
	case "Read-Only Member":
		// Read only.
		$theVal = 1;
		break;
	case "Read-Writer Member":
		// Tech & manager
		$theVal = 15;
		break;
	case "Senior Developer":
		// Tech & manager
		$theVal = 15;
		break;
	case "Anonymous":
		// Read only.
		$theVal = 9;
		break;
	}

	// Find all trackers given the group id.
	$strSelectTrackers = "SELECT group_artifact_id FROM artifact_group_list WHERE group_id=" . $groupId;

	$resTrackers = db_query_params($strSelectTrackers, array());
	if ($resTrackers) {
		while ($rowTracker = db_fetch_array($resTrackers)) {
			// NOTE: Tracker is handled differently than other modules!!!
			//
			// The ref_id expected is the tracker id (i.e. group_artifact_id),
			// rather than the group.
			//
			// A group can have multiple trackers (e.g. "Bugs", "Features").
			// Insert a tracker role for each eacher associated with the group.
			insertRoleSetting($roleId, $rowTracker['group_artifact_id'], "tracker", $theVal);
		}
	}
}

// Set up public project such that it is visible to users who are not logged in.
function setAnonymousAccessForProject($groupId) {

	// Check whether anonymous role entry exists in role_project_refs table already.
	// If not, insert group_id into role_project_refs table.
	insertRoleProjectRefs(1, $groupId);

	// Insert group_id into pfo_role_setting table.
	insertRoleSetting(1, $groupId, "project_read", 1);
	// Give wiki access to anonymous user.
	insertRoleSetting(1, $groupId, "plugin_moinmoin_access", 1);

	// Insert tracker into pfo_role_setting table.
	insertTrackersRoles(1, "Anonymous", $groupId);
}

// Unset role privileges for private project such that
// it is not visible to users who are not logged in.
function unsetAnonymousAccessForProject($groupId) {

	// Check whether anonymous role entry exists in role_project_refs table already.
	// If not, insert group_id into role_project_refs table.
	insertRoleProjectRefs(1, $groupId);

	// Insert group_id into pfo_role_setting table.
	insertRoleSetting(1, $groupId, "project_read", 0);
	// Unset wiki access.
	insertRoleSetting(1, $groupId, "plugin_moinmoin_access", 0);

	// Unset tracker access.
	insertTrackersRoles(1, "", $groupId);
}

// Insert into role_project_refs table.
function insertRoleProjectRefs($roleId, $groupId) {

	// Check whether entry exists already.
	$strCheckPresence = "SELECT role_id FROM role_project_refs WHERE " .
		"role_id=" .  $roleId . " AND " .
		"group_id=" . $groupId;
	$resIsPresent = db_query_params($strCheckPresence, array());
	if (!$resIsPresent || db_numrows($resIsPresent) < 1) {
		// Entry not present yet. Insert entry.

		// Insert into role_project_refs table.
		//echo "role_project_refs: " . $roleId . ":" . $groupId . "\n";
		$strPFOGroupRoleInsert = "INSERT INTO role_project_refs " .
			"(role_id, group_id) VALUES (" .
			$roleId . "," . $groupId . ")";

		//echo $strPFOGroupRoleInsert . "\n";
		$resInsert = db_query_params($strPFOGroupRoleInsert, array());
		if (!$resInsert || db_affected_rows($resInsert) < 1) {
			echo "Error: $strPFOGroupRoleInsert\n";
		}
	}
}


?>
