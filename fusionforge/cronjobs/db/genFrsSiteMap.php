<?php

/**
 *
 * genFrsSiteMap.php
 * 
 * Generate FRS sitemap.
 * 
 * Copyright 2005-2021, SimTK Team
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

// Get latest download release dates of active projects with public releases.
$query_frs = "SELECT fp.group_id AS group_id, " .
	"max(fr.release_date) AS release_date " .
	"FROM frs_package fp " .
	"JOIN frs_release fr " .
	"ON fp.package_id=fr.package_id " .
	"JOIN groups g " .
	"ON g.group_id=fp.group_id " .
	"WHERE g.status='A' " .
	"AND fr.status_id=1 " .
	"GROUP BY fp.group_id";
$result_frs = db_query_params($query_frs, array());
if (!$result_frs) {
	// Problem with query.
	exit;
}

// Get creation date of public, active DataShare studies.
$query_datashare = "SELECT group_id, date_created AS release_date FROM plugin_datashare " .
	"WHERE is_private = 0 " .
	"AND active=1";
$result_datashare = db_query_params($query_datashare, array());
if (!$result_datashare) {
	// Problem with query.
	exit;
}

$arrRes = array();

// Get groups from downloads.
while ($row = db_fetch_array($result_frs)) {
	$groupId = $row["group_id"];
	$relDate = $row["release_date"];
	$arrRes[$groupId] = $relDate;
}

// Get groups from DataShare.
while ($row = db_fetch_array($result_datashare)) {
	$groupId = $row["group_id"];
	$relDate = $row["release_date"];

	if (!isset($arrRes[$groupId])) {
		// Insert DataShare entry.
		$arrRes[$groupId] = $relDate;
	}
	else {
		if ($arrRes[$groupId] < $relDate) {
			// Use newer date from DataShare.
			$arrRes[$groupId] = $relDate;
		}
	}
}

// Sort result by group id.
ksort($arrRes);

// Get server name.
$serverName = forge_get_config('web_host');

// Header.
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($arrRes as $groupId=>$relDate) {
	$strRelDate = date('Y-m-d', $relDate);

	echo "<url>\n";
	echo "<loc>https://" . $serverName . "/frs/?group_id=" . $groupId . "</loc>\n";
	echo "<lastmod>" . $strRelDate . "</lastmod>\n";
	echo "</url>\n";
}

db_free_result($result_frs);
db_free_result($result_datashare);

// Footer.
echo "</urlset>\n";

?>

