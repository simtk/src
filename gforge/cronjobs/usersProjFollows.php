<?php

/**
 *
 * usersProjFollows.php
 * 
 * Look up university's geo coordinates of users following projects.
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

// Get database connection.
$dbConn = getDbConn();

// Find all users who are following projects.
$usersProjectFolows = lookupUsersFromProjectFollows($dbConn);
foreach ($usersProjectFolows as $userName) {
	// Look up geocode of user's university.
	getGeoInfo($dbConn, $userName, $universityWebsite, $universityName, $lat, $lng);

	// If found, update geocode and university information.
	if ($lat != false && $lng != false) {
		updateGeoInfo($dbConn, $userName, 
			$universityWebsite, $universityName, 
			$lat, $lng);
	}
}

// Get database connection.
function getDbConn() {

	$dbHost = $DB_SERVER;

	// Attempt a connection.
	$dbConn = pg_connect("host=$dbHost dbname=$DB_NAME user=$DB_USER");
	if (!$dbConn) {
		die("Connection failed: " . pg_last_error());
	}

	return $dbConn;
}

// Look up all users from the "project_follows" table.
function lookupUsersFromProjectFollows($dbConn) {

	$usersProjectFolows = array();

	$query = "SELECT DISTINCT user_name FROM project_follows";
	$res = pg_query($dbConn, $query);
	while ($row = pg_fetch_array($res)) {
		$userName = $row["user_name"];
		$usersProjectFolows[] = $userName;
	}

	return $usersProjectFolows;
}


// Update geocode information of university given user name.
function updateGeoInfo($dbConn, $userName, 
	$universityWebsite, $universityName, 
	$lat, $lng) {

	$userName = pg_escape_string($userName);
	$universityName = pg_escape_string($universityName);
	$universityWebsite = pg_escape_string($universityWebsite);

	$query = "UPDATE users_geoloc SET " .
		"university_name='" . $universityName . "'," .
		"university_website='" . $universityWebsite . "'," .
		"longitude='" . $lng . "'," .
		"latitude='" . $lat . "' " . 
		"WHERE user_name='" . $userName . "'";
	$res = pg_query($dbConn, $query);
	if (!$res || pg_affected_rows($res) < 1) {
		//echo "Cannot update row: $userName: $universityName $universityWebsite $lng : $lat \n";
		$query = "INSERT INTO users_geoloc " .
			"(user_name, university_name, university_website, longitude, latitude) " .
			"VALUES (" .
			"'" . $userName . "'," .
			"'" . $universityName . "'," .
			"'" . $universityWebsite . "'," .
			"'" . $lng . "'," .
			"'" . $lat . "')";
		$res = @pg_query($dbConn, $query);
		if (!$res) {
			echo "Cannot insert row: $userName: $universityName $universityWebsite $lng : $lat \n";
		}
	}
}

// Get geocode information of university given user name.
function getGeoInfo($dbConn, $userName, 
	&$universityWebsite, &$universityName, 
	&$lat, &$lng) {

	$lat = false;
	$lng = false;

	// Get associated university name and university website.
	getUniversityInfo($dbConn, $userName, $universityWebsite, $universityName);
	// If university website is available, try it first.
	if ($universityWebsite !== false && $universityWebsite != "") {
		fetchGeoCoordinates($universityWebsite, $lat, $lng);
		if ($lat === false || $lng === false) {
			// Geocode information not found.
			// Try university name.
			fetchGeoCoordinates($universityName, $lat, $lng);
		}
	}
	else if ($universityName !== false && $universityName != "") {
		// Try university name.
		fetchGeoCoordinates($universityName, $lat, $lng);
	}
}

// Get university website and name given the user name.
function getUniversityInfo($dbConn, $userName, &$universityWebsite, &$universityName) {

	$universityWebsite = false;
	$universityName = false;

	$userName = pg_escape_string($userName);

	// Find entries that require INSERT or UPDATE of geocode:
	// user_name does not exist in the "users_geoloc" table
	// university_name has changed, or
	// university_website has chnaged.
	$query = "SELECT u.university_name u_name, " .
		"u.university_website u_site, " .
		"g.university_name g_name, " .
		"g.university_website g_site " .
		"FROM users u " .
		"LEFT JOIN users_geoloc g " .
		"ON u.user_name=g.user_name " .
		"WHERE (u.user_name='" . $userName . "' " .
		"AND u.university_name!='None' " .
		"AND (g.user_name IS NULL " .
		"OR u.university_name!=g.university_name " .
		"OR u.university_website!=g.university_website)" .
		")";
	$res = pg_query($dbConn, $query);
	while ($row = pg_fetch_array($res)) {
		$universityWebsite = $row["u_site"];
		$universityName = $row["u_name"];
	}
}

// Get latitude and longitude of address.
// Address can be a name or URL.
// Return values of false if not found.
function fetchGeoCoordinates($theAddr, &$lat, &$lng) {

	$lat = false;
	$lng = false;
	$theAddr = trim($theAddr);

	// Base Google API URL.
	$baseMapURL = "https://maps.googleapis.com/maps/api/geocode/json?";

	$baseMapURL .= "key=" . $API_KEY;

	// Address to query.
	$strURL = $baseMapURL . "&address=" . urlencode($theAddr);

	// Retrieve information using curl.
	$myCurl = curl_init();
	curl_setopt($myCurl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($myCurl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($myCurl, CURLOPT_URL, $strURL);
	$resGeo = curl_exec($myCurl);
	curl_close($myCurl);

	// Decode the JSON string.
	$objGeo = json_decode($resGeo);
	//print_r($objGeo);

	if ($objGeo->status == "OK") {
		if (count($objGeo->results) > 0) {
			// Use the first geocode result if present.
			$lat = $objGeo->results[0]->geometry->location->lat;
			$lng = $objGeo->results[0]->geometry->location->lng;
		}
	}
}

?>
