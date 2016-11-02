<?php

/**
 *
 * getServersInfo.php
 * 
 * Get information on simulations supported by the servers.
 * 
 * Copyright 2005-2016, SimTK Team
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
 
require_once "../env.inc.php";
require_once $gfcommon.'include/pre.php';

$groupId = $_POST["GroupId"];

// Get names of servers, software names, and versions.
$serverNames = array();
$serverAliases = array();
$softwareNames = array();
$softareVersions = array();
$sql = "SELECT server_name, software_name, software_version, server_alias FROM simulation_servers WHERE group_id=" . $groupId;
$result = db_query_params($sql, array());
$rows = db_numrows($result); 
for ($i = 0; $i < $rows; $i++) {
	$tmpServerName = db_result($result, $i, 'server_name');
	$serverNames[$tmpServerName] = $tmpServerName;

	$tmpServerAlias = db_result($result, $i, 'server_alias');
	$serverAliases[$tmpServerAlias] = $tmpServerName;

	if (!isset($softwareNames[$tmpServerName])) {
		// Not present yet. Add an array.
		$softwareNames[$tmpServerName] = array();
	}
	// Insert entry into the array for the given server.
	$softwareNames[$tmpServerName][] = db_result($result, $i, 'software_name');

	if (!isset($softwareVersions[$tmpServerName])) {
		// Not present yet. Add an array.
		$softwareVersions[$tmpServerName] = array();
	}
	// Insert entry into the array for the given server.
	$softwareVersions[$tmpServerName][] = db_result($result, $i, 'software_version');
}
db_free_result($result);

// Sort server names.
uksort($serverNames, 'strcasecmp');

$res = array();
$res["serverNames"] = $serverNames;
$res["serverAliases"] = $serverAliases;
$res["softwareNames"] = $softwareNames;
$res["softwareVersions"] = $softwareVersions;

echo json_encode($res);

?>
