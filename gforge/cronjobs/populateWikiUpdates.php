<?php

/**
 *
 * populateWikiUpdates.php
 * 
 * Populate the wiki_updates tables from Wiki edit-log files.
 *
 * Copyright 2005-2018, SimTK Team
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

require dirname(__FILE__) . '/../www/env.inc.php';
require_once $gfcommon . 'include/pre.php';

// Clean wiki_updates first.
$res = db_query_params("DELETE FROM wiki_updates", array());
if (!$res) {
	echo "Cannot delete from wiki_updates\n";
	return;
}
db_free_result($res);

// Get all Wiki edit-log files.
foreach (glob("/var/lib/gforge/plugins/moinmoin/wikidata/*/data/edit-log") as $filenameWikiEditLog) {

	// Get last edit time of Wiki.
	$lastUpdateTime = filemtime($filenameWikiEditLog);

	$idxEnd = strrpos($filenameWikiEditLog, "/data/edit-log");
	$strTmp = substr($filenameWikiEditLog, 0, $idxEnd);
	$idxStart = strrpos($strTmp, "/");
	$strGroupName = substr($strTmp, $idxStart + 1);

	$res = db_query_params("INSERT INTO wiki_updates " .
		"(group_id, last_update) " .
		"SELECT group_id, " . $lastUpdateTime . " FROM groups " .
		"WHERE unix_group_name='" . $strGroupName . "' ",
		array());
	if (!$res) {
		echo "Cannot popupate wiki_updates: $strGroupName \n";
	}
	db_free_result($res);
}

?>
