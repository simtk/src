<?

/**
 *
 * communityAdminUtils.php
 * 
 * Utilities to support community administration.
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
 

// Update category information.
function updateCommunityInfo($communityName, $communityDesc, 
	$isAutoApprove, $catId) {

	$sqlInfo = 'UPDATE trove_cat SET ' .
		'fullname=$1, ' .
		'fullpath=$2, ' .
		'simtk_intro_text=$3, ' .
		'auto_approve_child=$4 ' .
		'WHERE trove_cat_id=$5';

	db_begin();

	$resInfo = db_query_params($sqlInfo, 
		array(
			$communityName,
			"All Topics :: Custom Categories :: " . $communityName,
			$communityDesc,
			$isAutoApprove,
			$catId
		)
	);
	if (!$resInfo || db_affected_rows($resInfo) < 1) {
		db_rollback();
		$error_msg = sprintf('Error On Update: %s', db_error());
		return $error_msg;
	}

	db_commit();

	if ($isAutoApprove != 1) {
		// Not approval. Done
		return true;
	}

	// Check whether there are projects pending approval.
	// If so, approve them, since auto-approve is now on.
	$sqlPendingApproval = "SELECT group_id from trove_group_link_pending " .
		"WHERE trove_cat_id=$1";
	$resPendingApproval = db_query_params($sqlPendingApproval, array($catId));
	while ($row = db_fetch_array($resPendingApproval)) {
		$gid = $row['group_id'];

		// Auto-approve the pending project.
		approvePendingProject($gid, $catId);
	}

	return true;
}


// Add featured project.
// Use public projects only.
function addFeaturedProject($addFeaturedProj, $catId) {

	if ($addFeaturedProj == null ||
		trim($addFeaturedProj) == "") {
		return "";
	}

	$sqlAdd = 'INSERT INTO featured_projects (group_id, trove_cat_id) ' .
		'SELECT g.group_id, $2 FROM groups g ' .
		'WHERE g.group_name=$1 AND g.group_id NOT IN ' .
		'(SELECT group_id FROM featured_projects WHERE trove_cat_id=$2) ' .
		'AND g.group_id IN ' .
		'(SELECT group_id FROM trove_group_link WHERE trove_cat_id=$2) ' .
		'AND NOT g.simtk_is_public=0';

	db_begin();

	$resAdd = db_query_params($sqlAdd, array($addFeaturedProj, $catId));
	if (!$resAdd || db_affected_rows($resAdd) < 1) {
		$error_msg = "Cannot add as featured project: " . $addFeaturedProj;
		db_rollback();

		$sqlCheck = 'SELECT g.group_id FROM groups g ' .
			'WHERE g.group_name=$1 AND g.group_id IN ' .
			'(SELECT group_id FROM featured_projects WHERE trove_cat_id=$2)';
		$resCheck = db_query_params($sqlCheck, array($addFeaturedProj, $catId));
		if (db_numrows($resCheck) > 0) {
			// Featured project already present. OK to ignore.
			return "";
		}

		return $error_msg;
	}

	db_commit();

	return "";
}

// Delete featured project.
function delFeaturedProject($delFeaturedProj, $catId) {

	if ($delFeaturedProj == null ||
		trim($delFeaturedProj) == "") {
		return "";
	}

	$sqlDel = 'DELETE FROM featured_projects ' .
		'WHERE group_id=' .
		'(SELECT g.group_id FROM groups g WHERE g.group_name=$1) ' .
		'AND trove_cat_id=$2';

	db_begin();

	$resDel = db_query_params($sqlDel, array($delFeaturedProj, $catId));
	if (!$resDel || db_affected_rows($resDel) < 1) {
		$error_msg = "Cannot delete featured project: " . $delFeaturedProj;
		db_rollback();
		return $error_msg;
	}

	db_commit();

	return "";
}

// Add administrator.
function addAdministrator($strUserId, $catId) {

	if ($strUserId == null ||
		trim($strUserId) == "") {
		return "";
	}

	$error_msg = "";

	db_begin();

	$sqlAdd = 'INSERT INTO trove_admin (trove_cat_id, user_id) ' .
		'SELECT $1, u.user_id FROM users u ' .
		'WHERE u.user_id=$2 ' .
		'AND u.user_id NOT IN ' .
		'(SELECT user_id FROM trove_admin ' .
		'WHERE trove_cat_id=$1)';
	$resAdd = db_query_params($sqlAdd, array($catId, $strUserId));
	if (!$resAdd || db_affected_rows($resAdd) < 1) {

		db_rollback();

		$sqlChkAdmins = 'SELECT user_id FROM trove_admin ' .
			'WHERE trove_cat_id=$1 ' .
			'AND user_id=$2';
		$resChkAdmins = db_query_params($sqlChkAdmins, array($catId, $strUserId));
		if (db_numrows($resChkAdmins) > 0) {
			// Admin already present. OK to ignore.
		}
		else {
			// Error adding user.
			$error_msg .= $strUserId . " ";
		}
	}
	else {
		// Insert OK.
		db_commit();
	}

	if ($error_msg != "") {
		return "Cannot add administrator: " . $error_msg;
	}

	return $error_msg;
}

// Delete administrator.
// NOTE: delAdmin is user_id.
function delAdministrator($delAdmin, $catId) {

	if ($delAdmin == null ||
		trim($delAdmin) == "") {
		return "";
	}

	$sqlDel = 'DELETE FROM trove_admin ' .
		'WHERE user_id=$1 ' .
		'AND trove_cat_id=$2';

	db_begin();

	$resDel = db_query_params($sqlDel, array($delAdmin, $catId));
	if (!$resDel || db_affected_rows($resDel) < 1) {
		$error_msg = "Cannot delete administrator: " . $delAdmin;
		db_rollback();
		return $error_msg;
	}

	db_commit();

	return "";
}


// Approve pending project.
function approvePendingProject($approvePendingProj, $catId) {

	if ($approvePendingProj == null ||
		trim($approvePendingProj) == "") {
		return "";
	}

	$sqlDel = 'DELETE FROM trove_group_link_pending ' .
		'WHERE group_id=$1 ' .
		'AND trove_cat_id=$2';

	$sqlCheck = "SELECT group_id from trove_group_link " .
		'WHERE group_id=$1 ' .
		'AND trove_cat_id=$2';

	$sqlAdd = "INSERT INTO trove_group_link " .
		"(trove_cat_id, trove_cat_version, " .
		"group_id, trove_cat_root) " .
		"VALUES ($1, $2, $3, $4)";

	db_begin();

	// Delete pending project from trove_group_link_pending table.
	$resDel = db_query_params($sqlDel, array($approvePendingProj, $catId));
	if (!$resDel || db_affected_rows($resDel) < 1) {
		// Cannot delete. Entry does not exist.
		db_rollback();
		return "";
	}

	// Check for existing duplicate entry in trove_group_link table.
	$resCheck = db_query_params($sqlCheck, array($approvePendingProj, $catId));
	if (db_numrows($resCheck) > 0) {
		// Entry present already. No need to insert.
		db_commit();
		return "";
	}

	// Insert pending project into trove_group_link table.
	$resAdd = db_query_params($sqlAdd,
		array(
			$catId,
			time(),
			$approvePendingProj,
			18
		)
	);
	if (!$resAdd || db_affected_rows($resAdd) < 1 ) {
		$error_msg = "Cannot approve the pending project: " . $approvePendingProj;
		db_rollback();
		return $error_msg;
	}

	db_commit();

	return "";
}

// Delete pending project.
function delPendingProject($delPendingProj, $catId) {

	if ($delPendingProj == null ||
		trim($delPendingProj) == "") {
		return "";
	}

	$sqlDel = 'DELETE FROM trove_group_link_pending ' .
		'WHERE group_id=$1 ' .
		'AND trove_cat_id=$2';

	db_begin();

	$resDel = db_query_params($sqlDel, array($delPendingProj, $catId));
	if (!$resDel || db_affected_rows($resDel) < 1) {
		$error_msg = "Cannot delete pending project: " . $delPendingProj;
		db_rollback();
		return $error_msg;
	}

	db_commit();

	return "";
}

?>
