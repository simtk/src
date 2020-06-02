<?php

/**
 *
 * refreshGitHubArchives.php
 * 
 * Refresh GitHub archives.
 * 
 * Copyright 2005-2020, SimTK Team
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
 
require_once (dirname(__FILE__).'/../../www/env.inc.php');
require_once $gfcommon . 'include/pre.php';
require_once $gfcommon . 'include/utilsGitHubSave.php';

// Get number of days since epoch for "now".
$now = time();

$arrPackages = array();

// Find GitHub archive files that need refreshing (i.e. "refresh_archive>0").
$strQuery = "SELECT file_id, filename, refresh_archive, " .
	"simtk_filelocation, release_time, file_size " .
	"FROM frs_file " .
	"WHERE simtk_filetype='GitHubArchive' " .
	"AND refresh_archive>0";
//echo $strQuery . "\n";
$res = db_query_params($strQuery, array());
if (!$res || db_numrows($res) <= 0) {
	// No files to refresh. Done.
	exit();
}

while ($row = db_fetch_array($res)) {
	$fileId = $row["file_id"];
	$fileName = $row["filename"];
	$refreshArchive = $row["refresh_archive"];
	$url = $row["simtk_filelocation"];
	$relTime = $row["release_time"];
	$fSize = $row["file_size"];

	// Get number of days since epoch for last release time of file.

	echo "$fileId : $fileName : $refreshArchive : $url : $relTime : $fSize \n";

	// Check whether it is time to refresh the file.
	if ($now - $relTime >= $refreshArchive * 86400) {
		// Time to refresh the file.
		$fileSize = refreshFile($fileId, $url, $packId);

		if ($fileSize !== false && $fileSize != -1) {
			// Update release time and file size for the file in frs_file table.
			updateReleasedFileInfo($fileId, $fileSize);

			// Remember this package.
			$arrPackages[$packId] = $packId;
		}
	}
}

// Iterate through all affected packages that have
// updated files and generate new zip file for each package.
foreach(array_keys($arrPackages) as $packId) {
	// Update package zip file.
	updatePackageZipFile($packId);
}

?>

