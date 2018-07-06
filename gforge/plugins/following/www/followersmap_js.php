<?php

/**
 *
 * followersmap_js.php
 * 
 * File which contains markers and labels for followers map
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
 
// Initial db and session library. Open session.
require_once '../../env.inc.php';
require_once $gfcommon . 'include/pre.php';
require_once $gfcommon . 'include/utils.php';

header("Content-Type: text/javascript");

?>

var map;

// Keep track of last opened InfoWindow.
var openInfoWindow;

function initialize() {

	var mapOptions = {
		center: new google.maps.LatLng(30, 0),
		zoom: 1.21,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	map = new google.maps.Map(document.getElementById("map"), mapOptions);

	// Show project followers.
	addPoints();
}

google.maps.event.addDomListener(window, 'load', initialize);

// Create marker that launches an InfoWindow.
function createMarker(lat, long, summary, infoFollowers, icon) {

	markerOpts = {
		position: new google.maps.LatLng(lat, long),
		map: map,
		icon: icon,
	};
	var marker = new google.maps.Marker(markerOpts);

	var infoWindow = new google.maps.InfoWindow({content: summary});

	// Add marker click listener to launch the InfoWindow.
	google.maps.event.addListener(marker, "click", function() {

		// Close InfoWindow if opened.
		if (openInfoWindow) {
			openInfoWindow.close();
		}

		// Open new InfoWindow.
		infoWindow.open(map, marker);
		// Remember new InfoWindow.
		openInfoWindow = infoWindow;

		// Update project followers information.
		$(".projectFollowers").html(infoFollowers);

		google.maps.event.addListener(infoWindow, 'closeclick', function(){
			// InfoWindow closed. Clear followers info.
			$(".projectFollowers").html("");
		});
	});

	return marker;
}

// Add points to map.
function addPoints() {

	// Define icons.
	grayImage="/images/map/marker_gray.png";
	blueImage="/images/map/marker_blue.png";
	greenImage="/images/map/marker_green.png";
	yellowImage="/images/map/marker_yellow.png";
	orangeImage="/images/map/marker_orange.png";
	redImage="/images/map/marker_red.png";

<?php
	// Look up project followers.
	$strSql = "SELECT g.user_name AS follower, " .
		"g.university_name AS univ_name, " .
		"g.university_website AS univ_website, " .
		"longitude, " .
		"latitude " .
		"FROM users_geoloc g " .
		"JOIN project_follows p " .
		"ON g.user_name=p.user_name " .
		"JOIN " .
		"(SELECT user_name, group_id, max(time) last_time FROM project_follows " .
		"GROUP BY group_id, user_name) lq " .
		"ON p.user_name=lq.user_name " .
		"AND p.group_id=lq.group_id " .
		"AND p.time=lq.last_time " .
		"JOIN users u " .
		"ON g.user_name=u.user_name " .
		"WHERE u.status='A' " .
		"AND public=true " .
		"AND follows=true " .
		"AND p.group_id=$1 ";
	$res = db_query_params($strSql, array($group_id));
	if (!$res || db_error()) {
		error_log(db_error());
		exit;
	}

	// Build followers information grouped by the location "longitude:latitude".
	$arrLocation = array();
	while ($row = db_fetch_array($res)) {

		if ($row["longitude"] != "" || $row["latitude"] != "") {

			$idx = $row["longitude"] . ":" . $row["latitude"];
			if (!isset($arrLocation[$idx])) {
				$infoLocation = array();

				$infoLocation["longitude"] = $row["longitude"];
				$infoLocation["latitude"] = $row["latitude"];
				$infoLocation["univ_name"] = $row["univ_name"];
				$infoLocation["univ_website"] = $row["univ_website"];
				$infoLocation["followers"] = array();

				$arrLocation[$idx] = $infoLocation;
			}
			$arrFollowers = $arrLocation[$idx]["followers"];
			$arrFollowers[] = $row["follower"];
			$arrLocation[$idx]["followers"] = $arrFollowers;
		}
	}

	// Iterate through each location to create markers and 
	// the display of the associated project followers information.
	foreach ($arrLocation as $key=>$location) {

		// Set up PHP variables to use in createMarker().
		$cntFollowers = count($location["followers"]);
		$latitude = $location["latitude"];
		$longitude = $location["longitude"];
		$univName = $location["univ_name"];

		// Popup dialog description per location.
		$divSummary = "<div class='divLocation'>" .
			genLocationSummary($cntFollowers, $univName) .
			"</div>";

		// Followers information per location.
		$divFollowers = genLocationFollowers($univName, $location["followers"]);

		// Icon to use.
		if ($cntFollowers == 1) {
			$icon = "grayImage";
		}
		else if ($cntFollowers <= 5) {
			$icon = "blueImage";
		}
		else if ($cntFollowers <= 25) {
			$icon = "greenImage";
		}
		else if ($cntFollowers <= 100) {
			$icon = "yellowImage";
		}
		else if ($cntFollowers <= 1000) {
			$icon = "orangeImage";
		}
		else {
			$icon = "redImage";
		}

?>
		// Create marker on map.
		createMarker(
			<?php echo $latitude; ?>,
			<?php echo $longitude; ?>,
			<?php echo json_encode($divSummary); ?>,
			<?php echo json_encode($divFollowers); ?>,
			<?php echo $icon; ?>);

<?php
	} // foreach

	db_free_result($res);
?>
  
}

<?php

// Generate string for location summary dialog.
function genLocationSummary($numFollowers, $univName) {
	return "<table cellspacing='0' cellpadding='0' class='tableFollowers'>" .
		"<tr><td><span class='hdrFollowers'>Followers: " . $numFollowers . "</span></td></tr>" .
		"<tr><td><span class='univFollowers'>" . $univName . "</span></td></tr>" .
		"</table>";
}

// Generate string for followers section.
function genLocationFollowers($univName, $followers) {
	$divFollowers = "<div class='related_group'>";
	$divFollowers .= "<h2>" . $univName . "</h2>";
	foreach ($followers as $userName) {
		$divFollowers .= genInfoFollower($userName);
	}
	$divFollowers .= "</div>";

	return $divFollowers;
}

// Generate string for follower information.
function genInfoFollower($userName) {

	$divFollower = "";

	$strSql = "SELECT realname, picture_file FROM users " .
		"WHERE user_name=$1";
	$res = db_query_params($strSql, array($userName));
	if (!$res || db_error()) {
		return $divFollower;;
	}
	while ($row = db_fetch_array($res)) {
		$realName = $row["realname"];
		$pictFile = $row["picture_file"];
	}

	// NOTE: Need to put the "team_member" DIV inside the "related_thumb" DIV
	// such that the user picture appears in a circle and 
	// the "related_text" appears inline horizontally.
	$divFollower .= "<div class='related_item'>";

	$divFollower .= "<div class='related_thumb'>";
	$divFollower .= "<div class='team_member'>";

	$divFollower .= "<a href='/users/" . $userName . "'>";
	$divFollower .= "<img src='/userpics/";
	if (!empty($pictFile)) {
		if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/userpics/$pictFile")) {
			$divFollower .= $pictFile . "' ";
		}
		else {
			// Cannot find user image file. Use default image.
			$divFollower .= "user_profile.jpg' ";
		}
	}
	else {
		// User image not specified.
		$divFollower .= "user_profile.jpg' ";
	}
	$divFollower .= "/></a>";

	$divFollower .= "</div> <!-- team_member -->";
	$divFollower .= "</div> <!-- related_thumb -->";
	$divFollower .= "<div class='related_text'><a href='/users/" . $userName . 
		"'><div class='followerName'>" . $realName . "</div></a></div>";

	$divFollower .= "</div> <!-- related_item -->";

	db_free_result($res);

	return $divFollower;
}

