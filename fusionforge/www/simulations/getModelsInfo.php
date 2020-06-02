<?php

/**
 *
 * getModelsInfo.php
 * 
 * Get information of simulation model.
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
$softwareVersion = getStringFromPost("SoftwareVersion");

$modelNames = array();
$modelCfgNames = array();
$modelModifyScriptNames = array();
$modelSubmitScriptNames = array();
$modelPostprocessScriptNames = array();
$modelExecChecks = array();
$modelMaxRunTimes = array();
$installDirNames = array();
$sql = "SELECT model_name, cfg_name_1, script_name_modify, " .
	"script_name_submit, script_name_postprocess, " .
	"install_dir, exec_check, max_runtime " .
	"FROM simulation_specifications " .
	"WHERE group_id=$1 " . 
	"AND software_name=$2 " .
	"AND software_version=$3";

$result = db_query_params($sql, 
	array($groupId, $softwareName, $softwareVersion));
$rows = db_numrows($result); 
for ($i = 0; $i < $rows; $i++) {
	$tmpModelName = db_result($result, $i, 'model_name');
	// Track model names.
	$modelNames[$tmpModelName] = $tmpModelName;

	// Track model config file names.
	if (!isset($modelCfgNames[$tmpModelName])) {
		// Not present yet. Add an array.
		$modelCfgNames[$tmpModelName] = array();
	}
	// Insert entry into the array for the given model.
	$modelCfgNames[$tmpModelName][] = db_result($result, $i, 'cfg_name_1');

	// Track model modify script file names.
	$modelModifyScriptNames[$tmpModelName] = db_result($result, $i, 'script_name_modify');

	// Track model submit script file names.
	$modelSubmitScriptNames[$tmpModelName] = db_result($result, $i, 'script_name_submit');

	// Track model postprocess script file names.
	$modelPostprocessScriptNames[$tmpModelName] = db_result($result, $i, 'script_name_postprocess');

	// Track model execution string checks.
	$modelExecChecks[$tmpModelName] = db_result($result, $i, 'exec_check');

	// Track model max runtime
	$modelMaxRunTimes[$tmpModelName] = db_result($result, $i, 'max_runtime');

	// Track model installation directories.
	$installDirNames[$tmpModelName] = db_result($result, $i, 'install_dir');
}
db_free_result($result);

// Sort model names.
uksort($modelNames, 'strcasecmp');

$res = array();
$res["modelNames"] = $modelNames;
$res["modelCfgNames"] = $modelCfgNames;
$res["modelModifyScriptNames"] = $modelModifyScriptNames;
$res["modelSubmitScriptNames"] = $modelSubmitScriptNames;
$res["modelPostprocessScriptNames"] = $modelPostprocessScriptNames;
$res["modelExecChecks"] = $modelExecChecks;
$res["modelMaxRunTimes"] = $modelMaxRunTimes;
$res["InstallDirNames"] = $installDirNames;

echo json_encode($res);

?>
