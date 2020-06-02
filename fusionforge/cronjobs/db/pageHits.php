<?php

/**
* @ignore
*/

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

/*
// Inclusive
$startDay = 20130201;
$endDay = 20130227;
echo "START DAY: " . $startDay . " END DAY: " . $endDay . "<br/><br/>";
*/
// Start and end days for data in simtk_henry_040913.
//$startDay = 20120414;
//$endDay = 20121029;

//$endDay = 20150703;
//$endDay = 20151204;

$yesterday = date("Ymd", time() - 24 * 60 * 60);
echo "YESTERDAY: $yesterday" . "<br/>\n";


// Attempt a connection.
$dbConn = pg_connect("host=$dbServer dbname=$dbName user=$dbUser");
if (!$dbConn) {
	die("Connection failed: " . pg_last_error());
}


// Days encountered: start and end.
$firstDaysRecorded = array();
$lastDaysRecorded = array();
$pageHitsRecorded = array();
$pageHitsMonthRecorded = array();


// Get lowest and highest days recorded.
$strActivityTimesQuery = "SELECT max(last_updated) FROM activity_group_times";
$res = pg_query($dbConn, $strActivityTimesQuery);
$highestDay = pg_fetch_result($res, 0);

$strActivityTimesQuery = "SELECT min(first_updated) FROM activity_group_times";
$res = pg_query($dbConn, $strActivityTimesQuery);
$lowestDay = pg_fetch_result($res, 0);

echo "LAST END DAY (Day highest): " . $highestDay . " Day lowest: " . $lowestDay . "<br/><br/>\n";


// Retrieve start and end days of activity for each group accumulated so far.
$strActivityTimesQuery = "SELECT group_id, last_updated, first_updated FROM activity_group_times";
$res = pg_query($dbConn, $strActivityTimesQuery);
while ($row = pg_fetch_array($res)) {
	$groupId = $row["group_id"];
	$lastUpdated = $row["last_updated"];
	$firstUpdated = $row["first_updated"];

	$firstDaysRecorded[$groupId] = $firstUpdated;
	$lastDaysRecorded[$groupId] = $lastUpdated;
}
// Free result.
pg_free_result($res);

echo "GROUPS recorded: " . count($lastDaysRecorded) .  "<br/>\n";
/*
// Sort by group_id.
ksort($lastDaysRecorded);
foreach ($lastDaysRecorded as $groupId=>$day) {
	echo $groupId . ":" . $firstDaysRecorded[$groupId]. " " . $day . "<br/>\n";
}
*/
echo "<br/>\n";


// Retrieve page hits for each group accumulated so far.
$strPageHits = "SELECT group_id, ip_addr, hits FROM activity_group_stats";
$res = pg_query($dbConn, $strPageHits);
while ($row = pg_fetch_array($res)) {
	$groupId = $row["group_id"];
	$ipAddr = $row["ip_addr"];
	$hits = $row["hits"];

	$pageHitsRecorded[$groupId][$ipAddr] = $hits;
}
// Free result.
pg_free_result($res);

//echo "Recorded HITS:<br/>";
foreach ($pageHitsRecorded as $groupId=>$row) {
	foreach ($row as $ipAddr=>$count) {
		//echo $groupId . ":" . $ipAddr . ":" . $count . "<br/>";
	}
}


// Retrieve page hits by month for each group accumulated so far.
$strPageHitsMonth = "SELECT group_id, month, hits FROM activity_group_stats_month";
$res = pg_query($dbConn, $strPageHitsMonth);
while ($row = pg_fetch_array($res)) {
	$groupId = $row["group_id"];
	$month = $row["month"];
	$hits = $row["hits"];

	$pageHitsMonthRecorded[$groupId][$month] = $hits;
}
// Free result.
pg_free_result($res);

//echo "Recorded HITS by month:<br/>";
foreach ($pageHitsMonthRecorded as $groupId=>$row) {
	foreach ($row as $month=>$count) {
		//echo $groupId . ":" . $month . ":" . $count . "<br/>";
	}
}



// Retrieve info from activity_log table.
$firstDaysToUpdate = array();
$lastDaysToUpdate = array();
$pageHitsToUpdate = array();
$pageHitsMonthToUpdate = array();
$strStatsQuery = "SELECT group_id, simtk_ip_addr, day FROM activity_log WHERE type=0 AND group_id != 0 AND simtk_ip_addr IS NOT NULL";
if (isset($startDay)) {
	// Start day is specified.
	$strStatsQuery = $strStatsQuery . " AND day >= " . $startDay;
}
else {
	if (isset($highestDay)) {
		// Use last highest day.
		$strStatsQuery = $strStatsQuery . " AND day > " . $highestDay;
	}
	else {
		// No start day specified; i.e. from the beginning.
	}
}
if (isset($endDay)) {
	// End day is specified.
	$strStatsQuery = $strStatsQuery . " AND day <= " . $endDay;
}
else {
	// Use yesterday.
	$strStatsQuery = $strStatsQuery . " AND day <= " . $yesterday;
}
echo "Retrieving from activity_log...<br/>\n";
echo $strStatsQuery . "<br/>\n";
$res = pg_query($dbConn, $strStatsQuery);
while ($row = pg_fetch_array($res)) {
	
	// Track whether page hit has been counted for this entry.
	$isCounted = false;

	$groupId = $row["group_id"];
	$ipAddr = $row["simtk_ip_addr"];
	$day = $row["day"];

	// Day is in the format of 20130204; hence, dividing the day by 100 produces the year-month combination of 201302.
	$month = (int) ($day / 100);

	if (!isset($firstDaysRecorded[$groupId]) ||
		$day < $firstDaysRecorded[$groupId]) {

		// Track earliest day not recorded yet.
		if (!isset($firstDaysToUpdate[$groupId]) || 
			$day < $firstDaysToUpdate[$groupId]) {

			$firstDaysToUpdate[$groupId] = $day;
		}

		// Has not encountered page hit on this day before. Update the count.
		if (!isset($pageHitsToUpdate[$groupId][$ipAddr])) {
			$pageHitsToUpdate[$groupId][$ipAddr] = 0;
		}
		$pageHitsToUpdate[$groupId][$ipAddr]++;
		$isCounted = true;

		// Update hit in month.
		if (!isset($pageHitsMonthToUpdate[$groupId][$month])) {
			$pageHitsMonthToUpdate[$groupId][$month] = 0;
		}
		$pageHitsMonthToUpdate[$groupId][$month]++;
	}

	if (!isset($lastDaysRecorded[$groupId]) ||
		$day > $lastDaysRecorded[$groupId]) {

		// Track latest day not recorded yet.
		if (!isset($lastDaysToUpdate[$groupId]) || 
			$day > $lastDaysToUpdate[$groupId]) {
			$lastDaysToUpdate[$groupId] = $day;
		}

		if ($isCounted === false) {
			// Do not double-count; count may have been updated from firstDays.
			if (!isset($pageHitsToUpdate[$groupId][$ipAddr])) {
				$pageHitsToUpdate[$groupId][$ipAddr] = 0;
			}
			$pageHitsToUpdate[$groupId][$ipAddr]++;

			if (!isset($pageHitsMonthToUpdate[$groupId][$month])) {
				$pageHitsMonthToUpdate[$groupId][$month] = 0;
			}
			$pageHitsMonthToUpdate[$groupId][$month]++;
		}
	}
}
// Free result.
pg_free_result($res);


// Update activity_group_times table.
echo "First DAYS groups to update: " . count($firstDaysToUpdate) . "<br/>\n";
foreach ($firstDaysToUpdate as $groupId=>$day) {

	if (!isset($firstDaysRecorded[$groupId]) && !isset($lastDaysRecorded[$groupId])) {
		// Insert
		$strInsertFirstDay = "INSERT INTO activity_group_times (group_id, first_updated) " .
			"VALUES (" . $groupId . ", " . $day . ")";
		//echo $strInsertFirstDay . "<br/>";

		$status = pg_query($dbConn, $strInsertFirstDay);
	}
	else {
		// Update.
		$strUpdateFirstDay = "UPDATE activity_group_times SET first_updated=". $day . " WHERE group_id=" . $groupId;
		//echo $strUpdateFirstDay . "<br/>";
		$status = pg_query($dbConn, $strUpdateFirstDay);
		if (!$status) {
			die("Error in SQL: " . $pg_last_error()); 
		}
	}

	//echo $groupId . ":" . $day . "<br/>";
}
echo "Last DAYS groups to update: " . count($lastDaysToUpdate) . "<br/>\n";
foreach ($lastDaysToUpdate as $groupId=>$day) {

	if (!isset($firstDaysRecorded[$groupId]) && !isset($lastDaysRecorded[$groupId]) && 
		!isset($firstDaysToUpdate[$groupId])) {
		// Insert
		$strInsertLastDay = "INSERT INTO activity_group_times (group_id, last_updated) " .
			"VALUES (" . $groupId . ", " . $day . ")";
		//echo $strInsertLastDay . "<br/>";
		$status = pg_query($dbConn, $strInsertLastDay);
	}
	else {
		// Update.
		$strUpdateLastDay = "UPDATE activity_group_times SET last_updated=". $day . " WHERE group_id=" . $groupId;
		//echo $strUpdateLastDay . "<br/>";
		$status = pg_query($dbConn, $strUpdateLastDay);
		if (!$status) {
			die("Error in SQL: " . $pg_last_error()); 
		}
	}

	//echo $groupId . ":" . $day . "<br/>";
}
//echo "<br/>";


echo "PAGE HITS groups to update: " . count($pageHitsToUpdate) . "<br/>\n";
foreach ($pageHitsToUpdate as $groupId=>$row) {
	foreach ($row as $ipAddr=>$count) {
		//echo $groupId . ":" . $ipAddr . ":" . $count . "<br/>";

		if (!isset($pageHitsRecorded[$groupId][$ipAddr])) {
			// Insert.
			$strInsertPageHits = "INSERT INTO activity_group_stats (group_id, ip_addr, hits) " .
				"VALUES (" . $groupId . ",'" . $ipAddr . "'," . $count . ")";
			//echo $strInsertPageHits . "<br/>";
			$status = pg_query($dbConn, $strInsertPageHits);
		}
		else {
			// Update.
			$updatedPageHits = $pageHitsRecorded[$groupId][$ipAddr] + $count;
			$strUpdatePageHits = "UPDATE activity_group_stats SET hits=". $updatedPageHits . 
				" WHERE group_id=" . $groupId . " AND ip_addr='" . $ipAddr . "'";
			//echo $strUpdatePageHits . "<br/>";
			$status = pg_query($dbConn, $strUpdatePageHits);
			if (!$status) {
				die("Error in SQL: " . $pg_last_error()); 
			}
		}
	}
}


echo "PAGE HITS by months groups to update: " . count($pageHitsMonthToUpdate) . "<br/>\n";
foreach ($pageHitsMonthToUpdate as $groupId=>$row) {
	foreach ($row as $month=>$count) {
		//echo $groupId . ":" . $month . ":" . $count . "<br/>";

		if (!isset($pageHitsMonthRecorded[$groupId][$month])) {
			// Insert.
			$strInsertPageHitsMonth = "INSERT INTO activity_group_stats_month (group_id, month, hits) " .
				"VALUES (" . $groupId . ",'" . $month . "'," . $count . ")";
			//echo $strInsertPageHitsMonth . "<br/>";
			$status = pg_query($dbConn, $strInsertPageHitsMonth);
		}
		else {
			// Update.
			$updatedPageHitsMonth = $pageHitsMonthRecorded[$groupId][$month] + $count;
			$strUpdatePageHitsMonth = "UPDATE activity_group_stats_month SET hits=". $updatedPageHitsMonth . 
				" WHERE group_id=" . $groupId . " AND month='" . $month . "'";
			//echo $strUpdatePageHitsMonth . "<br/>";
			$status = pg_query($dbConn, $strUpdatePageHitsMonth);
			if (!$status) {
				die("Error in SQL: " . $pg_last_error()); 
			}
		}
	}
}

?>
