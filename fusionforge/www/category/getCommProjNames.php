<?php

/**
 *
 * getCommProjNames.php
 * 
 * File to retrieving project names in the community.
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';

if (!isset($_GET['category'])) {
	return;
}

$arrProjNames = array();

$sqlGNames = "SELECT DISTINCT group_name gn, g.group_id gid FROM groups g " .
	"JOIN trove_group_link tgl " .
	"ON g.group_id=tgl.group_id ";

// Use category id.
// Only retrieve projects not yet among featured projects.
// Use public projects only.
$sqlGNames .= "WHERE g.status='A' " .
	"AND g.group_id NOT IN " .
	"(SELECT group_id FROM featured_projects WHERE trove_cat_id=" . ((int) $_GET['category']) . ") " .
	"AND trove_cat_id=" . ((int) $_GET['category']) . " " .
	"AND NOT g.simtk_is_public=0 ";

// Use filtered term.
if (isset($_GET['term'])) {
	$sqlGNames .= "AND LOWER(group_name) like '%" . strtolower(htmlspecialchars($_GET['term'])) . "%' ";
} 

// Order the results.
$sqlNames = "SELECT gNames.gn, gNames.gid FROM (" .
	$sqlGNames . ") gNames " .
	"ORDER BY gNames.gn";

$resNames = db_query_params($sqlNames, array());
$numNames = db_numrows($resNames);
for ($cnt = 0; $cnt < $numNames; $cnt++) {
	$arrProjNames[] = db_result($resNames, $cnt, 'gn');
}

echo json_encode($arrProjNames);

?>
