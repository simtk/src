<?php

/**
 *
 * biositemap_NCBC_Simbios.php
 * 
 * File to RDF file.
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
 
require dirname(__FILE__) . '/../../../www/env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once("ProjectXml.class.php");
require_once("transform.php");

$res = db_query_params("SELECT DISTINCT g.group_id FROM groups g
	JOIN frs_package fp ON (g.group_id = fp.group_id)
	JOIN frs_release fr ON (fp.package_id = fr.package_id)
	RIGHT JOIN stats_cvs_group scg ON (g.group_id = scg.group_id)
	WHERE g.simtk_is_public = 1 
	AND g.status = 'A' 
	AND ((g.use_frs = 1 AND fp.is_public = 1 AND fp.status_id = 1 AND fr.status_id = 1) 
	OR (g.use_scm = 1 AND commits > 0))",
	array());

$projects = array();
if ($res && db_numrows($res) > 0) {
	while ($row = db_fetch_array($res)) {
		$groupId = $row[0];
		array_push($projects, new ProjectXml($row[0]));
	}
}

$bioXml = "<data><projects>";
foreach ($projects as $project) {
	// Kind of a hackish way of getting HTML out of the description field, but that's XSL for you
	$xmlData = $project->getXmlDataInternal();
	$xmlPieces = explode("long_description>", $xmlData);
	$xmlPieces[ 1 ] = "<![CDATA[" . (strip_tags(unescape(substr($xmlPieces[1], 9, -5)))) . "]]></";
	$xmlData = implode("long_description>", $xmlPieces);
	$bioXml .= $xmlData . "\n";
}
$bioXml .= "</projects></data>";

echo transform($bioXml, "biositemap.xsl");

?>
