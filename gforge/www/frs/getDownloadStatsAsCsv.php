<?php
    
/**
 *
 * getDownloadStatsAsCsv.php
 * 
 * Get file downloads statistics.
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


if ( $_GET['group_id'] )
   $group_id = $_GET['group_id'];
			
if (!isset($group_id) || !is_numeric($group_id)) {
    exit_error("GROUP PROBLEM","MISSING GROUP ID");
}



$project =& group_get_object($group_id);
if (!$project || !is_object($project)) {
    exit_error("GROUP PROBLEM","PROBLEM CREATING GROUP OBJECT");
}

$user =& session_get_user();
if (!$user || !is_object($user)) {
    exit_error("USER PROBLEM", "PERMISSION DENIED");
}

$perm =& $project->getPermission($user);
if (!is_object($perm) || !$perm->isReleaseTechnician()) {
    exit_error("PERMISSION PROBLEM", "PERMISSION DENIED");
}



$downloadFilename="download_stats.csv";

$res = db_query_params ("SELECT u.lab_name, u.university_name, u.firstname,
               u.lastname,
               u.user_name,
			   ff.file_id,
               ff.filename,
               fr.name as release_name,
               fp.name as package_name,
               fdf.*
          FROM frs_dlstats_file AS fdf,
               frs_file AS ff,
               frs_release AS fr,
               frs_package AS fp,
               users AS u
          WHERE fdf.file_id=ff.file_id
            AND ff.release_id=fr.release_id
            AND fr.package_id=fp.package_id
            AND fp.group_id='".$group_id."'
            AND fdf.user_id=u.user_id
          ORDER BY fdf.\"month\" DESC,
                   fdf.\"day\" DESC,
                   UPPER(fp.name),
                   UPPER(fr.name),
                   UPPER(ff.filename),
                   UPPER(u.lastname),
                   UPPER(u.firstname)", array());

if ($res) {
   $downloadText="Year, Month, Day, Package Name, Release Name, File Name, User Name, Lab, University, Agreed To License, Expected Use\n";
   $numRows = db_numrows($res);
   for ($i = 0; $i < $numRows; $i++) {
      $newLine='';

      $yearMonth = db_result($res, $i, "month");
//      $date = substr($month,0,4)."-".substr($month,4)."-".str_pad($day,2,"0",STR_PAD_LEFT);

      $newLine.=substr($yearMonth,0,4).", ";
      $newLine.=substr($yearMonth,4).", ";
      $newLine.=str_pad(db_result($res, $i, "day"),2,"0",STR_PAD_LEFT).", ";

      $newLine.=db_result($res, $i, "package_name").", ";
      $newLine.=db_result($res, $i, "release_name").", ";
      $newLine.=db_result($res, $i, "filename").", ";
	  if (db_result($res, $i, "firstname") == 'Nobody') {
	    $newLine.= "Not logged.";
	  }
	  else {
	    $newLine.=db_result($res, $i, "firstname")." ";
        $newLine.=db_result($res, $i, "lastname").", ";
	  }    
	  $newLine.=db_result($res, $i, "lab_name").", ";
	  $newLine.=db_result($res, $i, "university_name").", ";
      $newLine.=db_result($res, $i, "agreed_to_license").", ";


      $expected_use=db_result($res, $i, "expected_use");
      $expected_use=preg_replace('/,/', ' ', $expected_use);
      $expected_use=preg_replace('/\n/', ' ', $expected_use);
      $expected_use=preg_replace('/\r/', ' ', $expected_use);

      $newLine.=$expected_use;
      $newLine.=" \n";

      $downloadText.=$newLine;
   }
   db_free_result($resDownloads);
} else {
   $downloadText="ERROR: ";
}

header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment'); 
header('Content-Disposition: filename='.$downloadFilename);
header('Content-Length: '.strlen($downloadText));
header('Cache-Control: no-cache');
flush(); 

echo $downloadText;
flush(); 


?>
