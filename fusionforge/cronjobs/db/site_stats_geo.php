<?php

if (file_exists("/etc/gforge/config.ini.d/post-install-secrets.ini")) {
	// The file post-install-secrets.ini is present.
	$arrDbConfig = parse_ini_file("/etc/gforge/config.ini.d/post-install-secrets.ini");

	// Check for each parameter's presence.
	if (isset($arrDbConfig["database_host"])) {
		$dbServer = $arrDbConfig["database_host"];
	}
	if (isset($arrDbConfig["database_name"])) {
		$dbName = $arrDbConfig["database_name"];
	}
	if (isset($arrDbConfig["database_user"])) {
		$dbUser = $arrDbConfig["database_user"];
	}
}
if (!isset($dbServer) || !isset($dbName) || !isset($dbUser)) {
	die("Database configuration information not available");
}

require('geoip/geoipcity.inc');

$startTime = time();
//echo "START TIME: $startTime\n";

// Go back a month
$how_far_back = (time() - 180*86400);
$month_ago = date('Ymd',$how_far_back);

//echo "$how_far_back\n";
//echo "$month_ago\n";

// Attempt a connection.
$dbConn = pg_connect("host=$dbServer dbname=$dbName user=$dbUser");
if (!$dbConn) {
	die("Connection failed: " . pg_last_error());
}

$gi = geoip_open("/usr/share/gforge/cronjobs/db/geoip/GeoIPCity.dat", GEOIP_STANDARD);

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

