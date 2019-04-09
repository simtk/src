<?php

/**
 *
 * populateProjectsLastUpdated.php
 * 
 * Populate group_history table with dates that projects were last updated.
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
 
require dirname(__FILE__) . '/../www/env.inc.php';
require_once $gfcommon . 'include/pre.php';

// Get all active projects.
$strAllActiveProjects = "SELECT group_id from groups " .
	"WHERE status='A'";
$resProjs = db_query_params($strAllActiveProjects, array());
$rowsProjs = db_numrows($resProjs);
for ($cnt = 0; $cnt < $rowsProjs; $cnt++) {

	// Active project.
	$theGroupId = db_result($resProjs, $cnt, 'group_id');

	// Get last updated date.
	$theLastUpdated = getLastUpdate($theGroupId);

	if ($theLastUpdated !== false) {
		// Update/insert the date to group_history table.
		updateLastUpdate($theGroupId, $theLastUpdated);
	}
}
db_free_result($resProjs);


// Update group_history table with the date that project was last updated.
function updateLastUpdate($groupId, $lastUpdated) {

	// Insert date if not exists.
	$strInsert = "INSERT INTO group_history " .
		"(group_id, field_name, mod_by, adddate) " .
		"SELECT $1, 'Last Updated', 102, $2 " .
		"WHERE NOT EXISTS " .
		"(SELECT 1 FROM group_history " .
		"WHERE group_id=$1 " .
		"AND field_name='Last Updated') ";
	$resInsert = db_query_params($strInsert, 
		array($groupId, $lastUpdated));
	if (!$resInsert || db_affected_rows($resInsert) < 1) {

		// Update date if not exists.
		$strUpdate = "UPDATE group_history SET " .
			"mod_by=102, " .
			"adddate=$2 " .
			"WHERE group_id=$1 " .
			"AND field_name='Last Updated' " .
			"AND adddate<$2 ";
		$resUpdate = db_query_params($strUpdate, 
			array($groupId, $lastUpdated));
		if (!$resUpdate || db_affected_rows($resUpdate) < 1) {

			// The date is present already.
/*
			$tmpDateTime = new DateTime("@$lastUpdated");
			$tmpDateTime->setTimezone(new DateTimeZone("America/Los_Angeles"));
			$theDateTime = $tmpDateTime->format('M j, Y');
			echo "Entry exists; not updated: $groupId $theDateTime \n";
*/
		}
	}
}

// Get last updated date of specified project.
function getLastUpdate($groupId) {

	// Get a group object given group id.
	$groupObj = group_get_object($groupId);
	if (!$groupObj || !is_object($groupObj)) {
		echo "Cannot get group object: " . $groupId  . "\n";
		return false;
	}
	elseif ($groupObj->isError()) {
		if ($groupObj->isPermissionDeniedError()) {
			echo "Permission denied getting group object: " . $groupId . "\n";
			return false;
		}
		echo $groupObj->getErrorMessage() . ": " . $groupId . "\n";
		return false;
	}

	// Look up history to get last updated date.

	$strQuery = "SELECT adddate FROM (";

	// Check group_history table first.
	$strQuery .= "(" .
		"SELECT gh.adddate AS adddate " .
		"FROM group_history gh " .
		"WHERE group_id=" . $groupObj->getID() .
		") ";

	if ($groupObj->usesFRS()) {
		// Downloads.
		$strQuery .= "UNION " .
			"(" .
			"SELECT ff.post_date AS adddate " .
			"FROM frs_file ff " .
			"JOIN frs_release fr " .
			"ON fr.release_id=ff.release_id " .
			"JOIN frs_package fp " .
			"ON fp.package_id=fr.package_id " .
			"WHERE group_id=" . $groupObj->getID() .
			") ";
	}

	if ($groupObj->usesDocman()) {
		// Create document.
		$strQuery .= "UNION " .
			"(" .
			"SELECT createdate AS adddate " .
			"FROM doc_data " .
			"WHERE group_id=" . $groupObj->getID() .
			") ";
		// Update document.
		$strQuery .= "UNION " .
			"(" .
			"SELECT updatedate AS adddate " .
			"FROM doc_data " .
			"WHERE group_id=" . $groupObj->getID() .
			") ";
	}

	// NOTE: Need to check news usage with usesPlugin("simtk_news")
	// but not $groupObj->usesNews().
	if ($groupObj->usesPlugin("simtk_news")) {
		// News.
		$strQuery .= "UNION " .
			"(" .
			"SELECT post_date AS adddate " .
			"FROM plugin_simtk_news " .
			"WHERE group_id=" . $groupObj->getID() . " " .
			"AND is_approved!=4 " .
			") ";
	}

	if ($groupObj->usesSCM()) {
		// CVS last commit time.
		$res = db_query_params("SELECT month, day FROM stats_cvs_group " .
			"WHERE group_id=" . $groupObj->getID() . " " .
			"AND commits>0 " .
			"AND month!=0 " .
			"AND day!= 0 " .
			"ORDER BY month DESC, day DESC LIMIT 1 " ,
			array());
		$rows = db_numrows($res);
		if ($rows > 0) {
			// NOTE: month is in the format 201808 
			// where 2018 is year and 08 is month.
			$theYearMonth = db_result($res, 0, 'month');
			$theYear = substr($theYearMonth, 0, 4);
			$theMonth = substr($theYearMonth, 4);
			$theDay = db_result($res, 0, 'day');

			// Generate last commit timestamp.
			$lastCommitTime = mktime(0, 0, 0, $theMonth, $theDay, $theYear);

			$strQuery .= "UNION " .
				"(" .
				"SELECT " . $lastCommitTime . " AS adddate " .
				") ";
		}
	}
	else if ($groupObj->usesGitHub()) {
		// GitHub last commit time.
		$url = $groupObj->getGitHubAccessURL();
		if (isset($url) && !empty($url)) {
			// Base GitHub URL.
			$theGitHubURL = "https://api.github.com/repos/" . $url;

			// Get GitHub statistics on project.
			$status = getLastCommit($theGitHubURL, $dateLastCommit);
			if (isset($dateLastCommit) && $dateLastCommit > 0) {
				$theDateTime = new DateTime("@$dateLastCommit");
				$lastCommitTime = $theDateTime->getTimestamp();
				$strQuery .= "UNION " .
					"(" .
					"SELECT " . $lastCommitTime . " AS adddate " .
					") ";
			}
		}
	}

	// Wiki updates.
	$strQuery .= "UNION " .
		"(" .
		"SELECT last_update AS adddate " .
		"FROM wiki_updates " .
		"WHERE group_id=" . $groupObj->getID() .
		") ";

	// Sort with latest first and select the latest.
	// NOTE: Do not include adddate that is null in search..
	$strQuery .= ") subq " .
		"WHERE adddate is not null " .
		"ORDER BY adddate DESC LIMIT 1";

	// Get the date.
	$lastUpdated = false;
	$res = db_query_params($strQuery, array());
	$rows = db_numrows($res);
	if ($rows > 0) {
		$lastUpdated = db_result($res, 0, 'adddate');
	}
	db_free_result($res);

	return $lastUpdated;
}

?>
