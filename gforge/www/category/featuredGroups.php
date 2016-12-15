<?php

/**
 *
 * featuredGroups.php
 * 
 * File to retrieving featured projects.
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

// Get category id.
if (isset($_GET["cat"])) {
        $cat_id = intval($_GET["cat"]);
}

// Get featured projects.
$resStr = "";
$sqlGroups = "SELECT group_id from featured_projects where trove_cat_id=$1";
$resGroups = db_query_params($sqlGroups, array($cat_id));
if (!$resGroups) {
	// Cannot execute db query.
	return "***ERROR***";
}
$cntRows = db_numrows($resGroups); 
for ($i = 0; $i < $cntRows; $i++) {
	$theGroupId = db_result($resGroups, $i, 'group_id');
	$resStr = $resStr . "," . $theGroupId;
}
db_free_result($resGroups);

echo $resStr;

?>