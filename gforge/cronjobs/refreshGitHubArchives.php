<?php

/**
 *
 * refreshGitHubArchives.php
 * 
 * Refresh GitHub archives.
 * 
 * Copyright 2005-2017, SimTK Team
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
require_once $gfcommon . 'frs/FRSPackage.class.php';

// Get number of days since epoch for "now".
$now = time();
$nowDay = floor($now / 86400);
//echo "NOW: $now $nowDay \n";

$arrPackages = array();

$strQuery = "SELECT ff.file_id fid, ff.filename fname, ff.refresh_archive, " .
	"ff.simtk_filelocation url, ff.release_time rtime, " .
	"fr.name rname, fp.name pname, fp.package_id pid, g.unix_group_name gname " .
	"FROM frs_file ff " .
	"JOIN frs_release fr ON ff.release_id=fr.release_id " .
	"JOIN frs_package fp ON fp.package_id=fr.package_id " .
	"JOIN groups g ON g.group_id=fp.group_id " .
	"WHERE ff.simtk_filetype='GitHubArchive' " .
	"AND refresh_archive>0";
$res = db_query_params($strQuery, array());
if ($res && db_numrows($res) > 0) {
	while ($row = db_fetch_array($res)) {
		$fileId = $row["fid"];
		$relTime = $row["rtime"];
		$fileName = $row["fname"];
		$relName = $row["rname"];
		$packName = $row["pname"];
		$packId = $row["pid"];
		$groupName = $row["gname"];
		$url = $row["url"];
		$refreshArchive = $row["refresh_archive"];

		// Get number of days since epoch for last release time of file.
		$relDay = floor($relTime / 86400);

		//echo "$fileId : $fileName : $relName : $packName : $packId : $groupName : $relTime : $relDay : $url : $refreshArchive \n";

		// Check whether it is time to refresh the file.
		if ($nowDay - $relDay >= $refreshArchive) {
			// Time to refresh the file.
			$fileSize = refreshFile($fileName, $relName, $packName, $groupName, $url);

			if ($fileSize !== false && $fileSize != -1) {
				// Update release time and file zie for the file in frs_file table.
				updateReleasedFileInfo($fileId, $fileSize);

				// Remember this package.
				$arrPackages[$packId] = $packId;
			}
		}
	}

	// Iterate through all affected packages that have
	// updated files to generate new zip file for the packages.
	foreach(array_keys($arrPackages) as $packId) {
		// Update package zip file.
		updatePackageZipFile($packId);
	}
}

// Refresh file in the downloads direcotryusing the given URL.
function refreshFile($fileName, $relName, $packName, $groupName, $url) {

	$fileSize = -1;

	// Get GitHub archive file content and save.
	$content = @file_get_contents($url);
	$fullPathName = forge_get_config('upload_dir') . '/' .
		$groupName . '/' .
		$packName . '/' .
		$relName . '/' .
		$fileName;
	//echo "Full pathname: $fullPathName \n";
	$fp = @fopen($fullPathName, "w+");
	if ($fp === false) {
		echo "Cannot save file: " . $fullPathName;
		return false;
	}
	fwrite($fp, $content);
	fclose($fp);

	// Get file size.
	$fileSize = filesize($fullPathName);

	return $fileSize;
}

// Update info of the released file in frs_file table.
function updateReleasedFileInfo($fileId, $fileSize) {

	$strUpdate = 'UPDATE frs_file SET ' .
		'release_time=$1, ' .
		'file_size=$2 ' .
		'WHERE file_id=$3';
	$res = db_query_params($strUpdate, array(time(), $fileSize, $fileId));
	if (!$res || db_affected_rows($res) < 1) {
		echo "Error on update: " . db_error();
		return false;
	}

	return true;
}

// Update package zip file with new set of files contained.
function updatePackageZipFile($packId) {

	//echo "Package Id: $packId \n";
	$objPackage = frspackage_get_object($packId);
	if ($objPackage != null) {
		// Create new zip file for package.
		$objPackage->createNewestReleaseFilesAsZip();
	}
}

?>

