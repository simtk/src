<?php

/**
 *
 * utilsGitHubSave.php
 * 
 * Utilities for saving GitHub archive file.
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
 
require_once $gfcommon . 'frs/FRSFile.class.php';
require_once $gfcommon . 'frs/FRSPackage.class.php';
require_once $gfcommon . 'include/githubUtils.php';

defined('MAX_GITHUB_FILESIZE') or define('MAX_GITHUB_FILESIZE', 250 * 1024 * 1024);

// Refresh file in the downloads direcotry using the given URL.
function refreshFile($fileId, $url, &$packId) {

	$frsFile = frsfile_get_object($fileId);
	if (!$frsFile) {
		// Cannot get FRSFile object.
		return false;
	}

	// Check GitHub archive file size at the given URL.
	$tmpFileSize = getGitHubFileSize($url);
	if ($tmpFileSize === false) {
		notifyGitHubRefresh($fileId, $url, "Cannot get GitHub archive file size.");
		return false;
	}
	if ($tmpFileSize > MAX_GITHUB_FILESIZE) {
		notifyGitHubRefresh($fileId, $url, "GitHub archive file is too large: $tmpFileSize.");
		return false;
	}

	$fileSize = -1;

	// Get GitHub archive file content and save.
	//$content = @file_get_contents($url);
	$context = array(
		"ssl"=>array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);

	// Generate full path name.
	$groupName = $frsFile->FRSRelease->FRSPackage->Group->getUnixName();
	$packName = $frsFile->FRSRelease->FRSPackage->getFileName();
	$relName = $frsFile->FRSRelease->getFileName();
	$fileName = $frsFile->getName();
	$packId = $frsFile->FRSRelease->FRSPackage->getID();
	$fullPathName = forge_get_config('upload_dir') . '/' .
		$groupName . '/' .
		$packName . '/' .
		$relName . '/' .
		$fileName;
	//echo "Full pathname: $fullPathName \n";

	// Save file.
	// NOTE: file_get_contents() uses too much memory.
	// Replaced with the method copy().
	/*
	$content = file_get_contents($url, false, stream_context_create($context));
	// Save file.
	$fp = @fopen($fullPathName, "w+");
	if ($fp === false) {
		//echo "Cannot open file: " . $fullPathName . "\n";
		notifyGitHubRefresh($fileId, $url, "Cannot open GitHub archive file.");
		return false;
	}
	fwrite($fp, $content);
	fclose($fp);
	*/
	if (!@copy($url, $fullPathName, stream_context_create($context))) {
		//echo "Cannot save file: " . $fullPathName . "\n";
		notifyGitHubRefresh($fileId, $url, "Cannot save GitHub archive file.");
		return false;
	}

	// Get file size after has been saved.
	$fileSize = filesize($fullPathName);

	return $fileSize;
}

// Send notification message on GitHub file refresh.
function notifyGitHubRefresh($fileId, $url, $message) {

	// Get FRSFile object.
	$frsFile = frsfile_get_object($fileId);
	if (!$frsFile) {
		// Cannot get FrsFile object.
		return;
	}
	
	$strMsg = $message . "\n" . $url . "\n";

	// Get project admins.
	$groupObj = $frsFile->FRSRelease->FRSPackage->Group;
	$projAdmins = $groupObj->getAdmins();
	foreach ($projAdmins as $adminObj) {
		// Send email to project admin.
		$adminEmail = $adminObj->data_array["email"];
		util_send_message($adminEmail, "Problem in GitHub archive file retrieval.", $strMsg);
	}
	// Send email to webmaster.
	util_send_message("webmaster@simtk.org", "Problem in GitHub archive file retrieval.", $strMsg);
}


// Update info of the released file in frs_file table.
function updateReleasedFileInfo($fileId, $fileSize) {

	// Save release time and file size.
	$strUpdate = 'UPDATE frs_file SET ' .
		'release_time=$1, ' .
		'file_size=$2 ' .
		'WHERE file_id=$3';
	$res = db_query_params($strUpdate, 
		array(time(), $fileSize, $fileId));
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
