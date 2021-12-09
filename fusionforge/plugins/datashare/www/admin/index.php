<?php
/**
 *
 * index.php
 *
 * Main admin index page for DataShare study.
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
require_once $gfplugins.'datashare/include/Datashare.class.php';

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

$error_msg = "";
if (session_loggedin()) {
	if (!forge_check_perm ('datashare', $group_id, 'write')) {
		$error_msg = "Access Denied: You cannot access the datashare admin section for a project unless you are an admin on that project";
	}
	else {
		$userperm = $group->getPermission();//we'll check if the user belongs to the group (optional)
		if ( !$userperm->IsMember()) {
			$error_msg = "Access Denied: You are not a member of this project";
		}
		else {
			// get current studies
			$study = new Datashare($group_id);
			if (!$study || !is_object($study)) {
				$error_msg = "Could Not Create Study Object";
			}
			elseif ($study->isError()) {
				$error_msg = $study->getErrorMessage();
			}
			else {
				// OK. No error.
			}
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

<script>

$(document).ready(function() {
	// Handle popover show and hide.
	$(".myPopOver").hover(function() {
		$(this).find(".popoverLic").popover("show");
	});
	$(".myPopOver").mouseleave(function() {
		$(this).find(".popoverLic").popover("hide");
	});
});
</script>

<?php

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n";
echo "<div class=\"main_col\">\n";

if (session_loggedin()) {
	$study_result = $study->getStudyByGroup($group_id);
	$numstudies = 0;
	if ($study_result) {
		$numstudies = count($study_result);
	}
	if ($numstudies < Datashare::MAX_STUDIES) {
		echo "<a class='btn-blue' href='add.php?group_id=$group_id'>Add Study</a> " .
			"<a style='text-align: right; float: right;' href='https://" .
			$datashareServer .
			"/apps/import/metadata.php' target='_blank'>" .
			"Learn more about adding metadata to your dataset</a><br /><br />";
	}
	if ($study_result) {
		echo '<table class="table">';
		echo "<tr><th>Study</th><th>Status</th><th>Description</th><th>Actions</th><th></th></tr>";
		foreach ($study_result as $result) {
			if ($result->active == 1) {
				$status = "Active";
			}
			else {
				$status = "Pending Activation";
			}
			echo "<form name=\"form$result->study_id\" action=\"../view.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"id\" value=\"$group_id\">";
			echo "<input type=\"hidden\" name=\"pluginname\" value=\"datashare\">";
			echo "<input type=\"hidden\" name=\"studyid\" value=\"$result->study_id\">";
			echo "</form>";
			echo "<tr><td>";
			if ($result->active == 1) {
				echo "<a href=\"#\" onclick=\"document.forms['form$result->study_id'].submit();\" class='btn-blue'>$result->title</a>";
			}
			else {
				echo $result->title;
			}
			echo "</td>";
			echo "<td>$status</td>";
			echo "<td>" . htmlspecialchars_decode($result->description);
			if ($study->isDOI($result->study_id)) {
				$strCancelStudyDoiLink = "cancelStudyDoi.php?" .
					"group_id=" . $group_id .
					"&study_id=" . $result->study_id;
				if (empty($study->getDOI($result->study_id))) {
					echo '<div><i>doi: pending</i>&nbsp;' .
						'<a style="background-color:#81a5d4; color:#ffffff; font-size:14px;" ' .
						'title="Click to cancel DOI request" href="' .
						$strCancelStudyDoiLink .
						'">&nbsp;X&nbsp;</a></div>';
				}
				else {
					echo '<div><i>doi:' . $study->getDOI($result->study_id). '</i></div>';
					echo '<span class="myPopOver"><a href="javascript://" ' .
						'class="popoverLic" data-html="true" ' .
						'data-toggle="popover" data-placement="right" title="DOI" ' .
						'data-content="Note: DOI assigned so resource cannot be edited or removed.">Note: DOI assigned</a></span>';
				}
			}
			echo "</td>";
			echo "<td>";

			if ($result->active == 1) {
				echo "<a class='btn-blue' " .
					"href='stats.php?" .
					"group_id=$group_id&" .
					"study_id=$result->study_id&" .
					"typeid=1'>" .
					"View Statistics</a>";

				if ($study->isDOI($result->study_id)) {
					// DOI requested.
					if (empty($study->getDOI($result->study_id))) {
						// DOI not assigned yet.
						echo "<br/><a class='btn-blue' " .
							"style='margin-top:2px;' " .
							"href='edit.php?" .
							"group_id=$group_id&" .
							"study_id=$result->study_id'>" .
							"Edit</a>";
						echo "<br/><a class='btn-blue' " .
							"style='margin-top:2px;' " .
							"href='managePubs.php?" .
							"group_id=$group_id&" .
							"study_id=$result->study_id'>" .
							"Manage Citations</a>";
					}
					else {
						// DOI assigned already.
						// Can no longer edit study.
					}
				}
				else {
					// DOI not requested.
					echo "<br/><a class='btn-blue' " .
						"style='margin-top:2px;' " .
						"href='edit.php?" .
						"group_id=$group_id&" .
						"study_id=$result->study_id'>" .
						"Edit</a>";
					echo "<br/><a class='btn-blue' " .
						"style='margin-top:2px;' " .
						"href='managePubs.php?" .
						"group_id=$group_id&" .
						"study_id=$result->study_id'>" .
						"Manage Citations</a>";
				}

				if ($study->isDOI($result->study_id)) {
					// DOI requested.
				}
				else {
					// DOI not requested.
					echo "<br/><a class='btn-blue' " .
						"style='margin-top:2px;' " .
						"href='/frs/admin/obtainPackageDoi.php?" .
						"group_id=$group_id&" .
						"study_id=$result->study_id'>" .
						"Obtain DOI</a></td>";
				}
			}

			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	else {
		echo "No current studies exist for this project<br /><br />";
	}

	if ($numstudies >= Datashare::MAX_STUDIES) {
		echo "<b>Note: maximum number of studies reached</b><br /><br />";
	}
}

echo "</div><!--main_col-->\n</div><!--display table-->\n</div><!--project_overview_main-->\n";

datashare_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
