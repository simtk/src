<?php

/**
 *
 * site_stats_geo.php
 * 
 * File to generate site statistics with geoip data.
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
 
require('geoip/geoipcity.inc');

$startTime = time();
//echo "START TIME: $startTime\n";

// Go back a month
$how_far_back = (time() - 180*86400);
$month_ago = date('Ymd',$how_far_back);

//echo "$how_far_back\n";
//echo "$month_ago\n";

// Attempt a connection.
$dbConn = pg_connect("host=$DB_SERVER dbname=$DB_NAME user=$DB_USER");
if (!$dbConn) {
	die("Connection failed: " . pg_last_error());
}

$gi = geoip_open("/usr/share/gforge/cronjobs/geoip/GeoIPCity.dat", GEOIP_STANDARD);

// Geographic location of users
$err = "Stats_site_user_loc " . date('Ymd H:i:s',time()) . "\n";

$sql = "DELETE FROM  stats_site_user_loc";
$status = pg_query($dbConn, $sql);
if (!$status) {
	die("Error in SQL: " . $pg_last_error());
}

$query = "SELECT group_id, simtk_ip_addr, simtk_host_name FROM activity_log WHERE type=0 AND day>=$month_ago AND simtk_ip_addr IS NOT NULL";
//echo "$query\n";
$hits = array();
$res = pg_query($dbConn, $query);
while ($row = pg_fetch_array($res)) {
	
	$ipAddr = $row["simtk_ip_addr"];
	$hostName = $row["simtk_host_name"];	
	$groupId = $row["group_id"];
	
	$name = "";

	if (!isset($hits[$ipAddr][$hostName][$groupId])) {
		$hits[$ipAddr][$hostName][$groupId] = 0;
	}
	$hits[$ipAddr][$hostName][$groupId]++;
}

pg_free_result($res);


$inter = time();
$mid = $inter - $startTime;
echo "TIME SELECT only: $mid\n";

$cntInserts = 0;
foreach ($hits as $ipAddr=>$arr1) {

	// Get location data.
	$giResult = geoip_record_by_addr($gi, $ipAddr);
	if ($giResult != null) {
		$city = utf8_encode(addslashes($giResult->city)); 
		$city = str_replace("\\", "", $city);
		$city = str_replace("'", "", $city);
		$state = utf8_encode(addslashes($giResult->region)); 
		$country = addslashes($giResult->country_name); 
		$country = str_replace("\\", "", $country);
		$country = str_replace("'", "", $country);
		$pcode = addslashes($giResult->postal_code); 	

		$longitude = $giResult->longitude; 	
		$latitude = $giResult->latitude; 	
	}
	else {
		// Cannot get location data for this ip address.
		$city = "";
		$state = "";
		$country = "";
		$pcode = "";

		$longitude = "";
		$latitude = "";
	}

	foreach ($arr1 as $hostName=>$arr2) {
		foreach ($arr2 as $groupId=>$hits) {
//			echo "$ipAddr:$hostName:$groupId: $hits\n";

			$cntInserts++;

			$sql = "INSERT INTO stats_site_user_loc"
				." (group_id,ip_addr,host_name,name,address,city,state,country,pcode,latitude,longitude,hits)"
				." VALUES ("
				."'".$groupId
				."','".$ipAddr
				."','".$hostName
				."','".$name
				."','" //address
				."','".$city
				."','".$state
				."','".$country
				."','".$pcode
				."','".$latitude
				."','".$longitude
				."',".$hits
				.")";
			$rel = pg_query($dbConn, $sql);
			if (!$rel) {
				echo "***ERROR: " . pg_last_error() . "\n";
			}

		}
	}
}

$endTime = time();
//echo "END TIME: $endTime\n";
$duration = $endTime - $startTime;
echo "Duration: $duration secs; number of INSERTS: $cntInserts\n";

?>

