<?php

/**
 *
 * ajax_people.php
 * 
 * ajax code to handle search of people.
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/escapingUtils.php';

$arrPeople = array();

$strSrch = "";
if (isset($_GET["srch"])) {
	$strSrch = trim($_GET["srch"]);
	if ($strSrch == "" || strlen($strSrch) < 3) {
		// Not enough characters to search.
		$strRes = json_encode($arrPeople);
		echo $strRes;
		return;
	}
}

$hasSpace = false;
if (strpos($strSrch, ' ') !== false) {
	// Has space within search string.
	$hasSpace = true;
}

if ($hasSpace === false) {
	// No space in search string.
	// Try match to leading characters in firstname or lastname.
	$sqlPeople = "SELECT user_name, firstname, lastname, realname, " .
		"picture_file, picture_type, lab_name, lab_website, " .
		"lab_name, lab_website, university_name, university_website, personal_website, " .
		"interest_simtk, interest_other " .
		"FROM users " .
		"WHERE status='A' AND " .
		"(firstname ilike $1 || '%' OR " .
		"lastname ilike $1 || '%') " .
		"ORDER BY firstname, lastname"; 
	$resPeople = db_query_params($sqlPeople, array($strSrch));
}
else {
	// Has space in string.
	// Try "as-is" match to leading characters in firstname, lastname.
	// Also, break up the string with space. Use first substring to match
	// with leading characters in firstname. Use last substring to match to
	// leading characters in lastname.
	$arrSubSrch = explode(" ", $strSrch);
	$strFirst = $arrSubSrch[0];
	$strLast = $arrSubSrch[count($arrSubSrch) - 1];
	$sqlPeople = "SELECT user_name, firstname, lastname, realname, " .
		"picture_file, picture_type, lab_name, lab_website, " .
		"lab_name, lab_website, university_name, university_website, personal_website, " .
		"interest_simtk, interest_other " .
		"FROM users " .
		"WHERE status='A' AND " .
		"(firstname ilike $1 || '%' OR " .
		"lastname ilike $1 || '%' OR " .
		"(firstname ilike $2 || '%' AND lastname ilike $3 || '%')) " .
		"ORDER BY firstname, lastname"; 
	$resPeople = db_query_params($sqlPeople, array($strSrch, $strFirst, $strLast));
}

$numPeople = db_numrows($resPeople);
for ($cnt = 0; $cnt < $numPeople; $cnt++) {
	$people = pg_fetch_object($resPeople, $cnt);
	$arrPeople[] = $people;
}
$strRes = json_encode($arrPeople);
echo $strRes;

db_free_result($resPeople);

?>

