<?php

/**
 *
 * getCommProjAdmins.php
 * 
 * File to retrieving project administrators in the community.
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';

$category = getIntFromRequest("category");
if ($category == 0) {
	return;
}

if (!isset($_GET['term']) || strlen($_GET['term']) <= 2) {
	// NOTE: Do not return any names if search string is less than 3 characters.
	// There should be at least 3 characters in a real name, so this
	// requirement would not leave out any real names, yet can help with
	// autocompleteness.
	// Otherwise, if there are many users, the array returned may be too large.
	// Return empty array.
	echo json_encode(array());
	return;
}

// Look up matching users from projects in category.
$arrUsers = lookupUsersFromCateogry($_GET['term'], $category);
if (count($arrUsers) < 1) {
	// No users found from projects in category.
	// Try looking up from all users.
	$arrUsers = lookupAllUsers($_GET['term'], $category);
}

// Case-insensitve sort array.
asort($arrUsers, SORT_NATURAL | SORT_FLAG_CASE);

// Fill in with user_id and user description.
$arrResult = array();
foreach ($arrUsers as $userId=>$desc) {
	$arrResult[] = array(
		'userid'=>$userId,
		'label'=>$desc
	);
}

// JSON-encode and send results back.
$strResult = json_encode($arrResult);
echo $strResult;


// Look up user from projects in category.
function lookupUsersFromCateogry($term, $catId) {
	$arrUsers = array();

	$sqlGroups = "SELECT DISTINCT g.group_id gid FROM groups g " .
		"JOIN trove_group_link tgl " .
		"ON g.group_id=tgl.group_id ";

	$sqlGroups .= "WHERE trove_cat_id=$1 " .
		"AND NOT g.simtk_is_public=0 ";
	$resGroups = db_query_params($sqlGroups, array($catId));
	$numGroups = db_numrows($resGroups);

	// Look up users in group.
	for ($cnt = 0; $cnt < $numGroups; $cnt++) {
		// Get group_id.
		$gid = db_result($resGroups, $cnt, 'gid');
		// Get group object.
		$groupObj = group_get_object($gid);
		// Get users in group.
		$userObjs = $groupObj->getUsersWithId();

		foreach ($userObjs as $user_id=>$userObj) {
			// Get user_id as string.
			$strUserId = (string) $user_id;
			// Get realname of user.
			$strRealName = $userObj->getRealName();

			if (stripos($strRealName, $term) !== false) {
				// Has match to search term.
				if (!array_key_exists($strUserId, $arrUsers)) {
					// Skip duplicates.

					// Add user_name to user description.
					$userName = "";
					$sqlUser = "SELECT user_name, university_name " .
						"FROM users " .
						"WHERE user_id=$1 " .
						"AND status='A' " .
						"AND user_id NOT IN " .
						"(SELECT user_id from trove_admin " .
						"WHERE trove_cat_id=$2)";
					$resUser = db_query_params($sqlUser,
						array($user_id, $catId));
					$numUsers = db_numrows($resUser);
					for ($cntUser = 0; $cntUser < $numUsers; $cntUser++) {
						// Get user_name.
						$userName = db_result($resUser, 
							$cntUser, 
							'user_name');
						// Get university_name.
						$univName = db_result($resUser, 
							$cntUser, 
							'university_name');
						if (trim($univName) != "") {
							$strUserDesc = "$strRealName ($univName)";
						}
						else {
							$strUserDesc = $strRealName;
						}

						$arrUsers[$strUserId] = $strUserDesc;
					}
				}
			}
		}
	}

	return $arrUsers;
}

// Look up from all users.
function lookupAllUsers($term, $catId) {
	$arrUsers = array();

	$sqlUser = "SELECT user_id, realname, university_name FROM users " .
		"WHERE LOWER(realname) LIKE '%" . strtolower($term) . "%' " .
		"AND status='A' " .
		"AND user_id NOT IN " .
		"(SELECT user_id from trove_admin WHERE trove_cat_id=$1)";
	$resUser = db_query_params($sqlUser, array($catId));
	for ($cntUser = 0; $cntUser < db_numrows($resUser); $cntUser++) {
		// Get user_id.
		$strUserId = db_result($resUser, $cntUser, 'user_id');
		// Get realname.
		$strRealName = db_result($resUser, $cntUser, 'realname');
		// Get university_name.
		$strUnivName = db_result($resUser, $cntUser, 'university_name');
		if (trim($strUnivName) != "") {
			$strUserDesc = "$strRealName ($strUnivName)";
		}
		else {
			$strUserDesc = $strRealName;
		}

		$arrUsers[$strUserId] = $strUserDesc;
	}

	return $arrUsers;
}

?>
