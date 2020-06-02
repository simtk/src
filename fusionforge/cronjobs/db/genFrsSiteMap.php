<?php

/**
 *
 * genFrsSiteMap.php
 * 
 * Generate FRS sitemap.
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
 
require dirname(__FILE__).'/../../common/include/env.inc.php';
require_once $gfcommon.'include/pre.php';

// Get latest release dates of active projects with public releases.
$query = "SELECT fp.group_id AS group_id, " .
	"max(fr.release_date) AS release_date " .
	"FROM frs_package fp " .
	"JOIN frs_release fr " .
	"ON fp.package_id=fr.package_id " .
	"JOIN groups g " .
	"ON g.group_id=fp.group_id " .
	"WHERE g.status='A' " .
	"AND fr.status_id=1 " .
	"GROUP BY fp.group_id " .
	"ORDER BY fp.group_id";

$result = db_query_params($query, array());
if (!$result) {
	// Problem with query.
	exit;
}

// Get server name.
$serverName = forge_get_config('web_host');

// Header.
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

while ($row = db_fetch_array($result)) {
	$groupId = $row["group_id"];
	$relDate = $row["release_date"];
	$strRelDate = date('Y-m-d', $relDate);

	echo "<url>\n";
	echo "<loc>https://" . $serverName . "/frs/?group_id=" . $groupId . "</loc>\n";
	echo "<lastmod>" . $strRelDate . "</lastmod>\n";
	echo "</url>\n";
}

db_free_result($result);

// Footer.
echo "</urlset>\n";

?>

