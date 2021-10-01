<?php

/**
 *
 * datashare plugin managePubs.php
 *
 * admin page for managing publications.
 *
 * Copyright 2005-2021, SimTK Team
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
require_once $gfwww.'project/project_utils.php';

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
$study_id = getIntFromRequest('study_id');

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

if (!session_loggedin()) {
	exit_not_logged_in();
}

if (!forge_check_perm('datashare', $group_id, 'write')) {
	exit_permission_denied('You cannot manage citations unless you are an admin on that project.', 'home');
}

// get Datashare object
$study = new Datashare($group_id);

if (!$study || !is_object($study)) {
	exit_error('Error','Could Not Create Study Object');
}
elseif ($study->isError()) {
	exit_error($study->getErrorMessage(), 'Datashare Error');
}

$study_results = $study->getStudy($study_id);
$studyTitle = $study_results[0]->title;

$func = htmlspecialchars(getStringFromRequest('func'));
if ($func == "delete_citation") {
	$citation_id = getIntFromRequest('citation_id');
	$sure = getIntFromRequest('sure');
	$really_sure = getIntFromRequest('really_sure');

	if (!$sure || !$really_sure) {
		$error_msg = 'Citation not deleted: you did not check “I am Sure”';
	}
	else {
		db_begin();
		$res = db_query_params("DELETE FROM plugin_datashare_citation " .
			"WHERE citation_id=$1", array($citation_id));
		if (!$res || db_affected_rows($res) < 1) {
			$error_msg = "Error deleting citation";
			db_rollback();
		}
		else {
			$feedback = "Citation Deleted";
			db_commit();
		}
	}
}
else if ($func == "add_citation") {
	$authors = htmlspecialchars(getStringFromRequest('authors'));
	$title = htmlspecialchars(getStringFromRequest('title'));
	$publisher = htmlspecialchars(getStringFromRequest('publisher'));
	$citation_year = getIntFromRequest('citation_year');
	$url = htmlspecialchars(getStringFromRequest('url'));
	$doi = htmlspecialchars(getStringFromRequest('doi'));
	$cite = getIntFromRequest('cite');

	if (trim($authors) == "" ||
		trim($title) == "" ||
		trim($publisher) == "" ||
		$citation_year < 1900 ||
		$citation_year > 2099) {
		$error_msg = 'Citation not added: please complete required field';
	}
	else {
		db_begin();
		$res = db_query_params("INSERT INTO plugin_datashare_citation " .
			"(study_id, authors, title, publisher_information, doi, citation_year, url, cite) " .
			"VALUES ($1,$2,$3,$4,$5,$6,$7,$8)",
			array(
				$study_id,
				$authors,
				$title,
				$publisher,
				$doi,
				$citation_year,
				$url,
				$cite
			)
		);
		if (!$res || db_affected_rows($res) < 1) {
			db_rollback();
			$error_msg = "Error adding citation";
		}
		else {
			db_commit();
			$feedback = "Added Citation";
		}
	}
}
else if ($func == "update_citation") {
	$authors = htmlspecialchars(getStringFromRequest('authors'));
	$title = htmlspecialchars(getStringFromRequest('title'));
	$publisher = htmlspecialchars(getStringFromRequest('publisher'));
	$citation_year = getIntFromRequest('citation_year');
	$url = htmlspecialchars(getStringFromRequest('url'));
	$doi = htmlspecialchars(getStringFromRequest('doi'));
	$cite = getIntFromRequest('cite');
	$citation_id = getIntFromRequest('citation_id');

	if (trim($authors) == "" ||
		trim($title) == "" ||
		trim($publisher) == "" ||
		$citation_year < 1900 ||
		$citation_year > 2099) {
		$error_msg = 'Citation not updated: please complete required field';
	}
	else {
		db_begin();
		$res = db_query_params("UPDATE plugin_datashare_citation SET " .
			"study_id=$1," .
			"authors=$2," .
			"title=$3," .
			"publisher_information=$4," .
			"doi=$5," .
			"citation_year=$6," .
			"url=$7," .
			"cite=$8 " .
			"WHERE citation_id=$9",
			array(
				$study_id,
				$authors,
				$title,
				$publisher,
				$doi,
				$citation_year,
				$url,
				$cite,
				$citation_id
			)
		);
		if (!$res || db_affected_rows($res) < 1) {
			db_rollback();
			$error_msg = "Error updating citation";
		}
		else {
			db_commit();
			$feedback = "Updated Citation";
		}
	}
}

datashare_header(array('title'=>'Datashare'),$group_id);
echo "<div><h4>" . $studyTitle . "<p/></h4></div>";

echo "<div class=\"project_overview_main\">";
echo "<div style=\"display: table; width: 100%;\">";
echo "<div class=\"main_col\">";

$study->displayCitations($group_id, $study_id, true);

echo "</div><!--main_col-->\n";

// Add side bar to show statistics and project leads.
constructSideBar($group);

echo "</div><!--display table-->\n</div><!--project_overview_main-->\n";

datashare_footer(array());


// Retrieve the error message and names of components to flag.
function retrieveErrorMessages($error_msg, &$arrErrors) {

        // Error messages are separated using "^" as delimiter.
        $arrErrors = explode("^", $error_msg);

        // The error message is the last token.
        // Note: "^" can be not present in the string; i.e. "".
        $error_msg = $arrErrors[count($arrErrors) - 1];

        return $error_msg;
}

// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	echo '</div>';
}


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
