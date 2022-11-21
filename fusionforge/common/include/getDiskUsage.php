<?php

/**
 *
 * getDiskUsage.php
 * 
 * Copyright 2005-2022, SimTK Team
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

require (dirname(__FILE__) . '/../../common/include/env.inc.php');
require_once $gfcommon . 'include/pre.php';
require_once $gfplugins . 'datashare/include/Datashare.class.php';

// Set MAX_TOTAL_BYTES to 150GB.
defined('MAX_TOTAL_BYTES') or define('MAX_TOTAL_BYTES', 150*1024);

// Get Data Share disk usage of group.
function getDataShareDiskUsageByGroup($groupId,
	&$totalBytes,
	&$lastModifiedTime) {

	$totalBytes = 0;
	$lastModifiedTime = false;

	$datashare = new Datashare($groupId);
	if (!$datashare || !is_object($datashare)) {
		// Invalid DataShare object.
		return;
	}
	// Get Data Share disk usage information of given group.
	$datashare->getDiskUsageByGroup($groupId, $totalBytes, $lastModifiedTime);
}

// Get disk usage in docman of group.
function getDocmanDiskUsageByGroup($groupId, 
	$pathTop, 
	&$totalBytes, 
	&$lastModifiedTime) {

	$totalBytes = 0;
	$lastModifiedTime = false;

	// Retrieve docid of files associated with the group given the group id.
	$res = db_query_params("SELECT docid, filesize FROM doc_data " .
		"WHERE group_id=$1",
		array($groupId));
	$rows = db_numrows($res);
	for ($cnt = 0; $cnt < $rows; $cnt++) {
		$docId = db_result($res, $cnt, "docid");

		// Get pathname of each document, given the docid.
		$pathName = getDocPath($docId);
		$fullPathName = $pathTop . "/" . $pathName;

		if (file_exists($fullPathName)) {
			// Get size of file.
			$totalBytes += filesize($fullPathName);

			// Get last modified time.
			$mtime = filemtime($fullPathName);
			if (!$lastModifiedTime || $mtime > $lastModifiedTime) {
				$lastModifiedTime = $mtime;
			}
		}
	}
}

// Get disk usage of group, given the top directory (for FRS and SVN).
function getDiskUsageByGroup($groupId, 
	$pathTop, 
	&$totalBytes, 
	&$lastModifiedTime) {

	// Get unix group name given the group id.
	$res = db_query_params("SELECT unix_group_name FROM groups " .
		"WHERE group_id=$1",
		array($groupId));
	$rows = db_numrows($res);
	for ($cnt = 0; $cnt < $rows; $cnt++) {
		$groupName = db_result($res, $cnt, "unix_group_name");
		$fullPathName = $pathTop . "/" . $groupName;

		// Get information of files under directory associated with the group..
		getDirInfo($fullPathName, $totalBytes, $lastModifiedTime);
	}
}


// Get information of files under a directory.
function getDirInfo($fullPathName, &$totalBytes, &$lastModifiedTime){

	$totalBytes = 0;
	$lastModifiedTime = false;

	// Recursively find all files under the directory to get total size.
	if (is_dir($fullPathName) && file_exists($fullPathName)) {
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPathName,
			FilesystemIterator::SKIP_DOTS)) as $theObj) {

			// Get size of file.
			$theSize = $theObj->getSize();
			$totalBytes += $theSize;

			// Get last modified time.
			$mtime = $theObj->getMTime();
			if (!$lastModifiedTime || $mtime > $lastModifiedTime) {
				$lastModifiedTime = $mtime;
			}
		}
	}
}

// Get pathname of file given the docid.
function getDocPath($docId) {

	// Retrieve representation of docid in hex.
	$docId = dechex($docId);
	$dirName = substr($docId, strlen($docId) - 2);
	$fileName = substr($docId, 0, strlen($docId) - 2);
	if (!$fileName) {
		$fileName = '0';
	}

	// Path of file.
	$pathName = $dirName . "/" . $fileName;

	return $pathName;
}


// Get total disk usage by group.
function getTotalDiskUsageByGroup($groupId, 
	&$svnTotalBytes,
	&$frsTotalBytes,
	&$docTotalBytes,
	&$dsTotalBytes,
	&$svnLastModifiedTime,
	&$frsLastModifiedTime,
	&$docLastModifiedTime,
	&$dsLastModifiedTime) {

	// SVN
	$pathTopSvn = forge_get_config('repos_path', 'scmsvn');
	getDiskUsageByGroup($groupId, $pathTopSvn, $svnTotalBytes, $svnLastModifiedTime);

	// Downloads.
	$pathTopFrs = forge_get_config("upload_dir");
	getDiskUsageByGroup($groupId, $pathTopFrs, $frsTotalBytes, $frsLastModifiedTime);

	// Docman
	getDocmanDiskUsageByGroup($groupId, "/var/lib/gforge/docman", 
		$docTotalBytes, $docLastModifiedTime);

	// Data Share studies.
	getDataShareDiskUsageByGroup($groupId, $dsTotalBytes, $dsLastModifiedTime);
}


// Check if total disk usage has exceeded allowance.
function checkTotalDiskUsageByGroup($groupId, &$totalBytes, &$allowedBytes) {

	// Get disk usage information.
	getTotalDiskUsageByGroup($groupId, 
		$svnTotalBytes, 
		$frsTotalBytes, 
		$docTotalBytes, 
		$dsTotalBytes, 
		$svnLastModifiedTime, 
		$frsLastModifiedTime, 
		$docLastModifiedTime, 
		$dsLastModifiedTime);

	// Total usage in bytes converted to MB.
	$totalBytes = $svnTotalBytes + $frsTotalBytes + $docTotalBytes + $dsTotalBytes;
	$allowedBytes = getDiskQuotaByGroup($groupId) * 1024 * 1024;

	if ($totalBytes > $allowedBytes) {
		// Disk usage exceeded.
		return false;
	}
	else {
		// OK.
		return true;
	}
}


// Get formatted stats information of disk usage and last modified time of the given group.
function statsTotalDiskUSageByGroup($groupId) {

	// Get disk usage information.
	getTotalDiskUsageByGroup($groupId, 
		$svnTotalBytes, 
		$frsTotalBytes, 
		$docTotalBytes, 
		$dsTotalBytes, 
		$svnLastModifiedTime, 
		$frsLastModifiedTime, 
		$docLastModifiedTime, 
		$dsLastModifiedTime);

	// Total usage in bytes converted to MB.
	$totalBytes = $svnTotalBytes + $frsTotalBytes + $docTotalBytes + $dsTotalBytes;
	$totalBytes = ceil($totalBytes/1024/1024);
	echo $groupId . " | ";
	echo $totalBytes . " | ";

	if ($svnLastModifiedTime) {
		echo ceil($svnTotalBytes/1024/1024) . " | ";
		echo date("Y-m-d", $svnLastModifiedTime) . " | ";
	}
	else {
		echo " | ";
		echo " | ";
	}
	if ($frsLastModifiedTime) {
		echo ceil($frsTotalBytes/1024/1024) . " | ";
		echo date("Y-m-d", $frsLastModifiedTime) . " | ";
	}
	else {
		echo " | ";
		echo " | ";
	}
	if ($docLastModifiedTime) {
		echo ceil($docTotalBytes/1024/1024) . " | ";
		echo date("Y-m-d", $docLastModifiedTime) . " | ";
	}
	else {
		echo " | ";
		echo " | ";
	}
	if ($dsLastModifiedTime) {
		echo ceil($dsTotalBytes/1024/1024) . " | ";
		echo date("Y-m-d", $dsLastModifiedTime) . " | ";
	}
	else {
		echo " | ";
		echo " | ";
	}
	echo "\n";
}

// Retrieve diskspace quota in MB of given project.
function getDiskQuotaByGroup($theGroupId) {
	$maxBytes = false;

	$defaultDiskQuota = MAX_TOTAL_BYTES;

	$strQuery = "SELECT max_bytes FROM project_diskquota " .
		"WHERE group_id=$1";
	$resDiskQuota = db_query_params($strQuery, array($theGroupId));
	$numrows = db_numrows($resDiskQuota);
	if ($numrows > 0) {
		while ($row = db_fetch_array($resDiskQuota)) {
			$maxBytes = $row['max_bytes'];
		}
	}
	if ($maxBytes !== false && $maxBytes != -1) {
		// Found diskspace quota for project.
		$maxBytes = trim($maxBytes);
		$last = strtolower($maxBytes[strlen($maxBytes) - 1]);
		switch ($last) {
		case 'g':
			$maxBytes *= 1024;
			break;
		case 'm':
			$maxBytes *= 1;
			break;
		case 'k':
			$maxBytes /= 1024;
			break;
		default:
			$maxBytes /= (1024*1024);
			break;
		}
		return $maxBytes;
	}
	else {
		// Use default diskspace quota.
		return $defaultDiskQuota;
	}
}

?>

