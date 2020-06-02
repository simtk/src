<?php

/**
 *
 * download.php
 * 
 * For redirecting to download confirmation page.
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
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';

// Get file_id passed in from request.
$file_id = getIntFromRequest('file_id');

// Get FRSFile object.
$frsFile = frsfile_get_object($file_id);
if (!isset($frsFile) || !$frsFile) {
	// File not found.
	session_redirect404();
}

// Get FRSRelease.
$frsRelease = $frsFile->FRSRelease;
// Get FRSPackage.
$frsPackage = $frsRelease->FRSPackage;
// Get Group.
$frsGroup = $frsPackage->Group;

// Get filename.
$filename = $frsFile->getName();
// Get group id.
$groupId = $frsGroup->getID();

// Redirect to download_confirm.php.
header("Location: " .
	"/frs/download_confirm.php/file/" . 
	$file_id . "/" .
	$filename . 
	"?group_id=" .
	$groupId);
?>
