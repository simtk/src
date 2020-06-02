<?php

/**
 *
 * getCommunities.php
 * 
 * ajax code to get communities to add to a community
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/escapingUtils.php';

// Get category id.
$cat_id = getIntFromRequest("cat");

echo "[";

if ($cat_id != 0) {

	// Retrieve community information.
	$sql = "SELECT trove_cat_id, fullname, simtk_intro_text FROM trove_cat " .
		"WHERE trove_cat_id " .
		"IN (" . 
		"SELECT linked_trove_cat_id FROM trove_cat_link WHERE trove_cat_id=$1" .
		")";
	$db_res = db_query_params($sql, array($cat_id));
	$db_count = db_numrows($db_res);
	for ($i = 0; $i < $db_count; $i++) {

		if ($i > 0) {
			// Add separator for JSON-encoded object.
			echo ",";
		}

		$trove_cat_id = db_result($db_res, $i, "trove_cat_id");
		$fullname = db_result($db_res, $i, "fullname");
		$description = db_result($db_res, $i, "simtk_intro_text");

		echo "{";
		echo json_kv("logo_file", "") . ",";
		echo json_kv("link", "/category/communityPage.php?cat=" . "1005" . "&sort=date&page=0&srch=&") . ",";
		echo json_kv("community_name", $fullname) . ",";
		echo json_kv("description", $description);
		echo "}";
	}
}

echo "]";

function json_kv($key, $val) {
	$out = '"' . json_escape($key) . '":';
	if (is_int($val))
		$out .= $val;
	if (is_bool($val))
		$out .= $val ? "true" : "false";
	else
		$out .= '"' . json_escape($val) . '"';
	return $out;
}

function json_escape($str) {
	return str_replace('"', '\"', str_replace("\t", '\t', str_replace("\n", '\n', str_replace("\r", "", str_replace('\\', '\\\\', $str)))));
}

?>

