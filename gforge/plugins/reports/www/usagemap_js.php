<?php
/**
 *
 * reports plugin usagemap_js.php
 * 
 * File which contains markers and labels for usage map
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
 
// Initial db and session library, opens session
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/utils.php';

header("Content-Type: text/javascript");

?>

var map;
var openInfoWindow;

function initialize() {

  /*
   var myWidth = 0, myHeight = 0;

  var mapEl=document.getElementById('map');
  var pageHeader=document.getElementById('pageHeader');
  var pageFooter=document.getElementById('pageFooter');
  var projectHeader=document.getElementById('projectHeader');
  var mapHeader=document.getElementById('mapHeader');
  var mapFooter=document.getElementById('mapFooter');
  var projectNavigation=document.getElementById('projectNavigation');

  myWidth = pageHeader.clientWidth;

  if( typeof( window.innerWidth ) == 'number' ) {
    //Non-IE
    myHeight = window.innerHeight;
  } else if( document.documentElement &&
      ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    //IE 6+ in 'standards compliant mode'
    myHeight = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    //IE 4 compatible
    myHeight = document.body.clientHeight;
  }

  //subtract for spacing

  myHeight = myHeight - pageHeader.clientHeight - mapHeader.clientHeight - mapFooter.clientHeight - pageFooter.clientHeight <?php if (isset($group_id)) echo " - projectHeader.clientHeight" ?> - 30;
  if (myHeight < 240) {
    myHeight = 240;
  }

  myWidth = myWidth <?php if (isset($group_id)) echo "- projectNavigation.clientWidth" ?> - 30;
  if (myWidth < 320) {
    myWidth = 320;
  }

  mapEl.style.height = myHeight + "px";
  mapEl.style.width = myWidth + "px";
  */
  
  var mapOptions = {
       center: new google.maps.LatLng(30, 0), 
	   zoom: 2, 
	   mapTypeId: google.maps.MapTypeId.ROADMAP
  };
  map = new google.maps.Map(document.getElementById("map"), mapOptions);
  //google.maps.event.addDomListener(window,"resize",map.onResize);
  addPoints();
}

google.maps.event.addDomListener(window, 'load', initialize);


// Creates a marker whose info window displays the given text
function createMarker(lat, long, title, info, icon) {
  markerOpts = {
    position: new google.maps.LatLng(lat, long),
    map: map,
    icon: icon,
    title: title
  };
  var marker = new google.maps.Marker(markerOpts);

  var infoWindow = new google.maps.InfoWindow({content: info});

  // Show this marker's index in the info window when it is clicked
  google.maps.event.addListener(marker, "click", function() {
    if (openInfoWindow)
      openInfoWindow.close();
    infoWindow.open(map, marker);
    openInfoWindow = infoWindow;
  });

  return marker;
}

// Adds the points to the map
function addPoints() {

  grayImage="/images/map/marker_gray.png";
  blueImage="/images/map/marker_blue.png";
  greenImage="/images/map/marker_green.png";
  yellowImage="/images/map/marker_yellow.png";
  orangeImage="/images/map/marker_orange.png";
  redImage="/images/map/marker_red.png";
  
  <?php
	$sql = "SELECT SUM(hits) as hits,city,state,country,longitude,latitude FROM stats_site_user_loc";
	if (isset($group_id)) {
		$sql .= " WHERE group_id='$group_id'";
	} else if (isset($topic_id)) {
		$sql .= " stats, trove_group_link tgl WHERE tgl.trove_cat_id='$topic_id' and tgl.group_id=stats.group_id";
	}
	
	$sql .= " GROUP BY city,state,country,longitude,latitude ORDER BY hits";
	
	$res = db_query_params($sql,array());
	
	if (!$res || db_error()) {
		error_log(db_error());
		exit;
	}
	
	while ($row = db_fetch_array($res)) {
	  if ($row["longitude"] != "" || $row["latitude"] != "") {
		$text = "<div style='text-align:left'>";
		$text .= locationSummary($row);
		$text .= "<\/div>";

		if ($row["hits"] == 1) {
			echo "createMarker(".$row["latitude"].", ".$row["longitude"].", \"".$row["city"]."\", \"".$text."\", grayImage);\n";
		} else if ($row["hits"] <= 5) {
			echo "createMarker(".$row["latitude"].", ".$row["longitude"].", \"".$row["city"]."\", \"".$text."\", blueImage);\n";
		} else if ($row["hits"] <= 25) {
			echo "createMarker(".$row["latitude"].", ".$row["longitude"].", \"".$row["city"]."\", \"".$text."\", greenImage);\n";
		} else if ($row["hits"] <= 100) {
			echo "createMarker(".$row["latitude"].", ".$row["longitude"].", \"".$row["city"]."\", \"".$text."\", yellowImage);\n";
		} else if ($row["hits"] <= 1000) {
			echo "createMarker(".$row["latitude"].", ".$row["longitude"].", \"".$row["city"]."\", \"".$text."\", orangeImage);\n";
		} else {
			echo "createMarker(".$row["latitude"].", ".$row["longitude"].", \"".$row["city"]."\", \"".$text."\", redImage);\n";
		}
	  }
    }
	
	db_free_result($res);
	
  ?>
  
}

<?php

function locationSummary($row) {
	$text = "<table cellspacing='0' cellpadding='0' style='width:100px'>";
	$addr = util_string_for_js($row["city"]);
	if (($row["country"] == "United States" || $row["country"] == "Canada") && $row["state"] != "") {
		$addr .= (empty($addr) ? "" : ", ").util_string_for_js($row["state"]);
	}
	$text .= rowData("<h5>".$addr."<\\/h5>");
	$text .= rowData(util_string_for_js($row["country"]));
	$text .= rowData("Hits: ".$row["hits"]);
	$text .= "<\\/table>";
	return $text;
}

function rowData($text) {
	if (isset($text) && $text != "") {
		return "<tr><td>".$text."<\\/td><\\/tr>";
	} else {
		return "";
	}
}

?>