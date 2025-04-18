<?php

/**
 *
 * Copyright 2005-2025, SimTK Team
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

require dirname(__FILE__).'/../../common/include/env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon . 'include/getDiskUsage.php';

// Get database connection.
$dbConn = getDbConn();

$arrProjs = array();

// Get projects with downloads.
getProjectsWithDownloads($dbConn, $arrProjs);

// Get projects with datashare.
getProjectsWithDatashare($dbConn, $arrProjs);

echo "Projects with public non-empty downloads or non-empty datashare: " . 
	count($arrProjs) . "\n";

// Try getting a database connection given the credentials.
function getDbConn() {

	if (file_exists("/etc/gforge/config.ini.d/post-install-secrets.ini")) {
		// The file post-install-secrets.ini is present.
		$arrDbConfig = parse_ini_file("/etc/gforge/config.ini.d/post-install-secrets.ini");

		// Check for each parameter's presence.
		if (isset($arrDbConfig["database_host"])) {
			$dbServer = $arrDbConfig["database_host"];
		}
		if (isset($arrDbConfig["database_name"])) {
			$dbName = $arrDbConfig["database_name"];
		}
		if (isset($arrDbConfig["database_user"])) {
			$dbUser = $arrDbConfig["database_user"];
		}
	}
	if (!isset($dbServer) || !isset($dbName) || !isset($dbUser)) {
		die("Database configuration information not available");
	}

	// Attempt a connection.
	$dbConn = pg_connect("host=$dbServer dbname=$dbName user=$dbUser");
	if (!$dbConn) {
		die("Connection failed: " . pg_last_error());
	}

	return $dbConn;
}


// Find projects with public downloads.
function getProjectsWithDownloads($dbConn, &$arrProjs) {

	$cntProjs = 0;

	$query = "SELECT group_id, unix_group_name, count(file_id) AS num_files
		FROM
		(SELECT g.group_id,
			unix_group_name,
			file_id,
        		CASE WHEN status = 'A'::bpchar 
				AND simtk_is_public=1 
				AND g.simtk_is_system=0 
				AND p.status_id=1 
				AND p.is_public=1 
				AND r.status_id=1 
				AND f.type_id<>2000
				AND release_time>0
			THEN 1
			ELSE 0 END 
			AS is_public_download_file
			FROM groups g
			JOIN frs_package p
			ON g.group_id=p.group_id
			JOIN frs_release r
			ON p.package_id=r.package_id
			JOIN frs_file f
			ON r.release_id=f.release_id
		) AS pub
		WHERE is_public_download_file=1 
		GROUP BY group_id, unix_group_name";

	$res = pg_query($dbConn, $query);
	while ($row = pg_fetch_array($res)) {
		$groupId = $row["group_id"];
		$groupName = $row["unix_group_name"];
		$numFiles = $row["num_files"];

		// Get disk usage of public downloads by group.
		getTotalDiskUsageByGroup($groupId,
			$svnTotalBytes,
			$frsTotalBytes,
			$docTotalBytes,
			$dsTotalBytes,
			$svnLastModifiedTime,
			$frsLastModifiedTime,
			$docLastModifiedTime,
			$dsLastModifiedTime);

		if ($frsTotalBytes != 0) {
			//echo "$groupName Download: $frsTotalBytes bytes\n";
			$arrProjs[$groupId] = $groupId;
			$cntProjs++;
		}
		else {
			//echo "$groupName Download: empty\n";
		}
	}

	echo "Projects with public non-empty downloads: " . $cntProjs . "\n";
}

// Find projects with public datashare.
function getProjectsWithDatashare($dbConn, &$arrProjs) {

	$cntProjs = 0;

	$query = "SELECT group_id, unix_group_name, count(study_id) AS num_studies 
		FROM
		(SELECT g.group_id,
			unix_group_name,
			study_id
			FROM groups g
			JOIN plugin_datashare ds
			ON g.group_id=ds.group_id
			WHERE status='A'
			AND simtk_is_public=1
			AND ds.active=1
			AND ds.is_private=0
		) pub_ds
		GROUP BY group_id, unix_group_name";

	$res = pg_query($dbConn, $query);
	while ($row = pg_fetch_array($res)) {
		$groupId = $row["group_id"];
		$groupName = $row["unix_group_name"];
		$numStudies = $row["num_studies"];

		// Get disk usage of public share by group.
		getTotalDiskUsageByGroup($groupId,
			$svnTotalBytes,
			$frsTotalBytes,
			$docTotalBytes,
			$dsTotalBytes,
			$svnLastModifiedTime,
			$frsLastModifiedTime,
			$docLastModifiedTime,
			$dsLastModifiedTime);

		if ($dsTotalBytes != 0) {
			//echo "$groupName Datashare: $dsTotalBytes bytes\n";
			$arrProjs[$groupId] = $groupId;
			$cntProjs++;
		}
		else {
			//echo "$groupName Datashare: empty\n";
		}
	}

	echo "Projects with public non-empty datashare: " . $cntProjs . "\n";
}

