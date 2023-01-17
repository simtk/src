<?php

/**
 * Copyright 2005-2023, SimTK Team
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

require_once "DocumentStorage.class.php";

// Clean up storage of all deleted docman documents.
function cleanupAllDeletedDocmanDocuments($groupId=false) {

	// Select documents.
	if ($groupId) {
		$sqlDocData = "SELECT docid FROM doc_data WHERE group_id=$1";
		$resDocData = db_query_params($sqlDocData, array($groupId));
	}
	else {
		$sqlDocData = "SELECT docid FROM doc_data";
		$resDocData = db_query_params($sqlDocData, array());
	}
	if (!$resDocData) {
		//echo "No doc_data\n";
		return;
	}
	while ($row = db_fetch_array($resDocData)) {
		$docId = $row["docid"];

		// Clean up storage.
		cleanupDeletedDocmanDocument($docId);
	}
	db_free_result($resDocData);
}


// Clean up storage of deleted docman document.
function cleanupDeletedDocmanDocument($docId) {

	// Get Docman Storage instance.
	$docStorage = DocumentStorage::instance();
	$fullPath = $docStorage->get_storage($docId);
	if (!file_exists($fullPath)) {
		// File does not exist. Deleted already. Done.
		return;
	}

	$sqlDocData = "SELECT stateid, doc_group FROM doc_data " .
		"WHERE docid=$1";
	$resDocData = db_query_params($sqlDocData, array($docId));
	if (!$resDocData) {
		//echo "No doc_data\n";
		return;
	}
	while ($row = db_fetch_array($resDocData)) {
		$docStateId= $row["stateid"];
		$docGroup= $row["doc_group"];

		// NOTE: A deleted docman document has stateid of 2.
		if ($docStateId == 2) {
			// Document deleted. File exists. Remove file from storage.
			//echo "Delete file: $docId : $fullPath \n";
			unlink($fullPath);
		}
		else {
			// Traverse and check parent doc_group.
			// Need to check because its parent doc_group 
			// may have been deleted.
			$isDelete = checkParentDocGroup($docGroup);
			if ($isDelete) {
				// Parent doc_group has been deleted. Delete file.
				// File exists. Remove file from storage.
				//echo "Delete file in doc_group: $docId : $fullPath \n";
				unlink($fullPath);
			}
		}
	}

	db_free_result($resDocData);
}

// Check parent doc_group to see if parent doc_group has been deleted.
function checkParentDocgroup($docGroup) {

	// Get doc_group.
	$sqlDocGroups = "SELECT stateid, parent_doc_group, groupname FROM doc_groups " . 
		"WHERE doc_group=$1";
	$resDocGroups = db_query_params($sqlDocGroups, array($docGroup));
	if (!$resDocGroups) {
		//echo "No doc_groups\n";
		exit;
	}

	while ($row = db_fetch_array($resDocGroups)) {
		$stateid = $row["stateid"];
		$parentDocGroup = $row["parent_doc_group"];
		$groupname = $row["groupname"];

		// NOTE: Deleted docman doc_group has stateid of 2.
		if ($stateid == 2) {
			// This doc_group has been deleted.
			// Done.
			db_free_result($resDocGroups);
			return true;
		}
		else {
			// Check parent doc_group.
			$parentStateId = checkParentDocgroup($parentDocGroup);
			if ($parentStateId) {
				// Parent doc_group has been deleted.
				// Done.
				db_free_result($resDocGroups);
				return true;
			}
			if (!$parentDocGroup || 
				$parentDocGroup == null ||
				trim($parentDocGroup) == "") {
				// No more parent doc_group.
				// Done.
				db_free_result($resDocGroups);
				return false;
			}
		}
	}

	db_free_result($resDocGroups);
	return false;
}

?>



