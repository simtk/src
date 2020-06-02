<?php

/**
 *
 * populateWikiUsers.php
 * 
 * Populate the wiki_users table from Wiki user files.
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

require_once (dirname(__FILE__).'/../../www/env.inc.php');
require_once $gfcommon . 'include/pre.php';
require_once $gfcommon . 'include/clean_user_utils.php';

// Clean wiki_users first.
$res = db_query_params("DELETE FROM wiki_users", array());
if (!$res) {
	echo "Cannot delete from wiki_users\n";
	return;
}
db_free_result($res);

// Get wiki users from Wiki user files.
$arrWikiUser = getWikiUsers();

// Update wiki_users table.
foreach ($arrWikiUser as $userName) {
	$res = db_query_params("INSERT INTO wiki_users (user_name) " .
		"SELECT $1 WHERE NOT EXISTS " .
		"(SELECT 1 FROM wiki_users WHERE user_name=$2)",
		array($userName, $userName));
	if (!$res) {
		echo "Cannot popupate wiki_users: $userName \n";
	}
	db_free_result($res);
}

?>
