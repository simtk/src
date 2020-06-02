<?php
/**
 *
 * index.php
 *
 * Main admin index page for creating new study.
 *
 * Copyright 2005-2020, SimTK Team
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

require_once '../../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once '../datashare-utils.php';
require_once $gfplugins . 'datashare/include/Datashare.class.php';

// Override and use the configuration parameters as specified in
// the file datashare.ini if the file is present and has the relevant parameters.
if (file_exists("/etc/gforge/config.ini.d/datashare.ini")) {
	// The file datashare.ini is present.
	$arrDatashareConfig = parse_ini_file("/etc/gforge/config.ini.d/datashare.ini");

	// Check for each parameter's presence.
	if (isset($arrDatashareConfig["datashare_server"])) {
		$datashareServer = $arrDatashareConfig["datashare_server"];
	}
}
if (!isset($datashareServer)) {
	exit_error("Cannot get datashare server");
}


$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'datashare');
}

$study_id = getIntFromRequest('study_id');
$typeid = getIntFromRequest('typeid');
$all_checked = "";
$not_all_checked = "";
if (isset($_REQUEST['all'])) {
	$all = $_REQUEST['all'];
	if ($all) {
		$all_checked = "checked";
	}
	else {
		$not_all_checked = "checked";
	}
}
else {
	$all = 1;
	$all_checked = "checked";
}

$error_msg = "";
if (session_loggedin()) {
	if (!forge_check_perm ('datashare', $group_id, 'write')) {
		$error_msg = "Access Denied: You cannot access the datashare admin section for a project unless you are an admin on that project";
	}
	else {
		$userperm = $group->getPermission(); //we'll check if the user belongs to the group (optional)
		if (!$userperm->IsMember()) {
			$error_msg = "Access Denied: You are not a member of this project";
		}
		else {
			// OK. No error.
		}
	}
	if ($error_msg != "") {
		$pluginname="datashare";
		datashare_header(array('title'=>'Datashare','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);
		datashare_footer(array());
		return;
	}
}
else {
	$error_msg = "Access Denied: You cannot access the datashare admin section for a project unless you are an admin on that project";
	$pluginname="datashare";
	datashare_header(array('title'=>'Datashare','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);
	datashare_footer(array());
	return;
}


$pluginname="datashare";
datashare_header(array('title'=>'Datashare','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);

?>

<link href = "https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel = "stylesheet">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
	$(function() {
		$("#datepickerfrom").datepicker();
	});
	$(function() {
		$("#datepickerto").datepicker();
	});
</script>


<?php

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n";
echo "<div class=\"main_col\">\n";

if (session_loggedin()) {
	echo "<a class=\"btn-blue\" href=\"stats.php?group_id=$group_id&study_id=$study_id&typeid=1\" style=\"width:190px;\">Query History Report</a>";
	echo "&nbsp;<a class=\"btn-blue\" href=\"stats.php?group_id=$group_id&study_id=$study_id&typeid=2\" style=\"width:230px;\">Downloads History Report</a>";
	echo "<br /><br />";

	echo "<form action=\"stats.php\">";
	echo "<div class=\"form_simtk\">";
	echo "<input type=\"hidden\" name=\"group_id\" value=\"$group_id\">";
	echo "<input type=\"hidden\" name=\"study_id\" value=\"$study_id\">";
	echo "<input type=\"hidden\" name=\"typeid\" value=\"$typeid\">";
	echo "<label><input type=\"radio\" name=\"all\" value=\"1\" $all_checked> All Data</label><br />";
	echo "<label><input type=\"radio\" name=\"all\" value=\"0\" $not_all_checked> Date Range&nbsp;&nbsp; FROM: <input type=\"text\" id=\"datepickerfrom\" name=\"datefrom\">";
	echo " TO: <input type=\"text\" id=\"datepickerto\" name=\"dateto\"></label>";
	echo " <input type=\"submit\" class=\"btn-cta btn-sm\" name=\"submit\">";
	echo "</div>";
	echo "</form>";
	echo "<br />";

	if ($typeid == 1) {
		echo "<h4>Query History Report</h4>";
	}
	else {
		echo "<h4>Downloads History Report</h4>";
	}

	$date_error = 0;
	include 'server.php';
	if (!empty($_REQUEST['datefrom']) && !empty($_REQUEST['dateto']) && !$all) {
		$datefrom = $_REQUEST['datefrom'];
		$dateto = $_REQUEST['dateto'];

		// Check dates.
		if (date("Ymd", strtotime($datefrom)) > date("Ymd", strtotime($dateto))) {
			// Past end date.
			$date_error = 1;
		}
		else {
			// ok to use dates.
			$url = "https://$datashareServer/reports/getStats.php?apikey=$api_key&studyid=$study_id&typeid=$typeid&datefrom=$datefrom&dateto=$dateto";
			echo "Selected: <b>Date Range $datefrom to $dateto</b>";
		}
	}
	elseif (!$all && (empty($_REQUEST['datafrom']) ||
		empty($_REQUEST['dateto']))) {
		// Empty date present.
		$date_error = 2;
	}
	else {
		// All data.
		$url = "https://$datashareServer/reports/getStats.php?apikey=$api_key&studyid=$study_id&typeid=$typeid&date_all=1";
		echo "Selected: <b>All Data</b>";
	}

	echo "<br />";
	if ($date_error == 1) {
		echo "The <b>From</b> Date can't be past the <b>To</b> date, From: $datefrom  To: $dateto";
	}
	elseif ($date_error == 2) {
		echo "Both the <b>From</b> date and <b>To</b> date fields must be entered";
	}
	else {
		// Cache user objects.
		$arrUsers = array();

		//echo "url = $url<br /><br />";
		//$response_json = file_get_contents($url);
		$context = array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
		$response_json = file_get_contents($url, false, stream_context_create($context));
		$response = json_decode($response_json);
		//var_dump($response) . "<br />";

		echo "<table class=\"table\">";
		echo "<tr><th>Date</th><th>User</th><th>Size</th><th>Query</th></tr>";
		if ($response) {
			foreach($response as $obj) {
				$info = urldecode($obj->info);
				$params = $obj->params_list;
				$filtersUser = $obj->filters_user;
				$filtersAdmin = $obj->filters_admin;
				$userId = $obj->userid;
				$bytes = $obj->bytes;
				if ($bytes == -1) {
					$bytes = "Unknown";
				}

				// Look up user object.
				$userObj = false;
				if (isset($arrUsers[$userId])) {
					// Found in cache.
					$userObj = $arrUsers[$userId];
				}
				else {
					$userObj = user_get_object($userId);
					if (!$userObj || !is_object($userObj)) {
						// Cannot look up user object given user id.
					}
					else {
						// Cache the user object.
						$arrUsers[$userId] = $userObj;
					}
				}
				$userName = false;
				if ($userObj !== false) {
					// Look up user name.
					$userName = $userObj->getUnixName();
					$realName = $userObj->getRealName();
				}

				// Get directories included.
				$query = $params;
				if (trim($filtersUser) != "" ||
					trim($filtersAdmin) != "") {
					$query .= "<br/>Directories:<br/>";
				}
				if (trim($filtersUser) != "") {
					$query .= $filtersUser;
				}
				if (trim($filtersAdmin) != "") {
					if (trim($filtersUser) != "") {
						$query .= ",";
					}
					$query .= $filtersAdmin;
				}

				echo "<tr>";
				echo "<td nowrap>" . date("Y-m-d", strtotime($obj->dateentered)) . "</td>";
				if ($userName === false) {
					// Cannot find unix username. Cannot link to user profile.
					echo "<td>" . $obj->firstname . " " . $obj->lastname . "</td>";
				}
				else {
					// Link to user profile.
					echo "<td><a href='/users/" . 
						$userName .  "'>" .
						$realName .
						"</a></td>";
				}
				echo "<td>" . $bytes . "</td>";
				echo "<td>" . $query . "</td>";
				echo "</tr>";
			}
		}
		else {
			echo "<tr><td colspan=5>No Data Found</td></tr>";
		}

		echo "</table>";
	}
}

echo "</div><!--main_col-->";
echo "</div><!--display table-->";
echo "</div><!--project_overview_main-->";

datashare_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
