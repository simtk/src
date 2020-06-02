<?php

/**
 *
 * getOntology.php
 * 
 * Retrieve ontology data.
 *
 * Copyright 2005-2017, SimTK Team
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
 
require (dirname(__FILE__).'/../../www/env.inc.php');
require_once $gfcommon.'include/pre.php';
require $gfcommon.'include/cron_utils.php';

// Get ontology.
$cnt = 0;
echo "bro_resource,project_id\n";
$strOntologyQuery = "SELECT bro_resource, project_id FROM project_bro_resources";
$res = db_query_params($strOntologyQuery, array());
while ($row = db_fetch_array($res)) {
	$groupId = $row["project_id"];
	$bro_resource = $row["bro_resource"];
	echo trim($bro_resource) . "," . $groupId . "\n";
	$cnt++;
}
echo "($cnt rows)\n";

// Free result.
db_free_result($res);

?>
