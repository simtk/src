<?php

/**
 *
 * githubUtils.php
 * 
 * Utilities to retrieve GitHub information.
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

// Log GitHub access. Increment access count.
function logGitHubAccess($groupId) {

	// Update existing count.
	$sqlUpdate = "UPDATE group_github_access_history " .
		"SET repository_accesses = repository_accesses + 1 " .
		"WHERE group_id = $groupId";
	$resUpdate = db_query_params($sqlUpdate, array());
	if (!$resUpdate) {
		// Cannot update count.
		return "***ERROR***" . "Cannot update group_github_access_history table";
	}
	else if (db_affected_rows($resUpdate) < 1) {
		// Entry does not exist for the gorup yet. Insert first entry.
		$sqlInsert = "INSERT INTO group_github_access_history " .
			"(group_id, repository_accesses) VALUES " .
			"($groupId, 1)";
		$resInsert = db_query_params($sqlInsert, array());
		if (!$resInsert) {
			// Cannot insert first entry.
			return "***ERROR***" . "Cannot insert to group_github_access_history table";
		}
	}

	return null;
}
 
// Get GitHub data.
function getGitHubData($url) {

	// NOTE: Need to set user_agent; otherwise, URL access is forbidden.
	ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 6.0)');

	// Suppress warning in case the URL cannot be accessed.
	// Do not verify peer.
	$arrOptions = array("ssl"=>array("verify_peer"=>false, "verify_peer_name"=>false));  
	$response = @file_get_contents($url, false, stream_context_create($arrOptions));
	if ($response == false || trim($response) == "") {
		// Error, or data not ready from GitHub yet.
		return null;
	}

	// Decode response which is encoded with JSON.
	$theObj = json_decode($response);

	return $theObj;
}

// Get contributors and their commits info.
function getContributors($theGitHubURL, &$numContributors, &$totalCommits) {

	$numContributors = 0;
	$totalCommits = 0;

	// Retry 5 times.
	for ($cnt = 0; $cnt < 5; $cnt++) {

		// Get contributors info.
		$ret = getGitHubData($theGitHubURL . "/stats/contributors");

		if ($ret != null && is_array($ret)) {
			// NOTE: when data is not ready, non-array object is returned.
			// Hence, need to check for array.
			$numContributors = count($ret);

			// Accumulate commits from each contributor.
			for ($cntAuthors = 0; $cntAuthors < count($ret); $cntAuthors++) {
				$totalCommits += $ret[$cntAuthors]->total;
			}

			// Success.
			return true;
		}

		// Sleep 1 second before retry.
		sleep(1);
	}

	// Failed retrieval.
	return false;
}

// Get last commit activities from last year.
function getLastCommit($theGitHubURL, &$dateLastCommit) {

	$dateLastCommit = 0;

	// Retry 5 times.
	for ($cnt = 0; $cnt < 5; $cnt++) {

		// Get commit activities info.
		$ret = getGitHubData($theGitHubURL . "/stats/commit_activity");

		if ($ret != null && is_array($ret)) {

			// NOTE: Fetch data backwards, because most recent week 
			// is placed at the end.
			for ($cntWeeks = count($ret) - 1; $cntWeeks > 0; $cntWeeks--) {
				if ($ret[$cntWeeks]->total > 0) {
					$dateLastCommit = $ret[$cntWeeks]->week;

					if ($ret[$cntWeeks]->days != null &&
						is_array($ret[$cntWeeks]->days)) {

						// NOTE: Fetch the last day in week that has commits.
						// First day is Sunday.
						// Search backwards.
						for ($cntDays = 6; $cntDays > 0; $cntDays--) {
							if ($ret[$cntWeeks]->days[$cntDays] > 0) {
								// Found committed day.
								$dateLastCommit += $cntDays * 3600 * 24;
								break;
							}
						}
					}

					// Found commits for the week.
					// Done.
					break;
				}
			}

			// Success.
			return true;
		}

		// Sleep 1 second before retry.
		sleep(1);
	}

	// Failed retrieval.
	return false;
}

// Test for URL existence.
function urlExistance($strUrl) {
	$handle = curl_init($strUrl);
	$timeout = 5;
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle,  CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

	curl_setopt($handle, CURLOPT_HEADER, true);
	curl_setopt($handle, CURLOPT_NOBODY, true);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($handle, CURLOPT_AUTOREFERER, true);

	// Get the HTML or whatever is linked in URL.
	$response = curl_exec($handle);
	//echo $response;

	// Check for 404 (file not found).
	$httpCode1 = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	//echo $httpCode1;

	curl_close($handle);

	return $httpCode1;
}

// Get GitHub archive file size at given URL.
function getGitHubFileSize($strUrl) {

	$fileSize = false;

	$handle = curl_init($strUrl);
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($handle, CURLOPT_HEADER, true);
	curl_setopt($handle, CURLOPT_NOBODY, true);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

	// Get the HTML or whatever is linked in URL.
	$response = curl_exec($handle);
	//echo $response;

	// Check for 404 (file not found).
	$fileSize = curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	//echo $fileSize;

	curl_close($handle);

	return $fileSize;
}

?>
