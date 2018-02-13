<?php

/**
 *
 * getCfgText.php
 * 
 * Get configuration file content of simulation job.
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

$groupId = getIntFromPost("GroupId");
$softwareName = getStringFromPost("SoftwareName");
$cfgName = getStringFromPost("ConfigFileName");

if (!preg_match('/^[0-9a-zA-Z_.\-]+$/', $softwareName)) {
	// Invalid software name.
	$cfgText = "***ERROR***" . "Invalid software name";
}
else if (!preg_match('/^[0-9a-zA-Z_.\-]+$/', $cfgName)) {
	// Invalid config file name.
	$cfgText = "***ERROR***" . "Invalid config file name";
}
else {
	// Try getting file content using group id and config file name.
	$fullPathName = "./configData/" . $groupId . "_" . $softwareName . "_" . $cfgName;
	$cfgText = @file_get_contents($fullPathName);
	if ($cfgText === false) {
		// Cannot get file content. Send back error string.
		$cfgText = "***ERROR***" . "Cannot get contents of $fullPathName";
	}
}

$res = array();
$res["cfgText"] = $cfgText;
echo json_encode($res);

?>
