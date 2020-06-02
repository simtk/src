<?php

/**
 *
 * api plugin index.php
 *
 * Main index page.
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfplugins.'api/include/Api.class.php';
require_once $gfcommon.'include/FusionForge.class.php';
    
	$api = new Api;
	if (!$api || !is_object($api)) {
           header('Content-Type: application/json; charset=UTF-8');
		   $data = "Cannot Process your request. Error creating API Object";
		   echo json_encode($data);
		   exit;
    }
		
	$request_method=$_SERVER["REQUEST_METHOD"]; 
	switch($request_method) { 
	   case 'GET': // Retrieve 
	     if ($api->checkKey()) {
	        $api->retrieve(); 
		 }
	     break; 
	   case 'POST': // Insert 
	      // Not supported at this time
	      header("HTTP/1.0 405 Method Not Allowed");
	      break; 
	   case 'PUT': // Update 
	      // Not supported at this time
	      header("HTTP/1.0 405 Method Not Allowed");
	      break; 
	   case 'DELETE': // Delete 
	      // Not supported at this time
	      header("HTTP/1.0 405 Method Not Allowed");
	      break; 
	   default: // Invalid Request Method 
	      header("HTTP/1.0 405 Method Not Allowed"); 
	      break; 
	}
	

	

?>
