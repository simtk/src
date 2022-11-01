<?php
/**
 * Update citation in a DataShare study.
 *
 * Copyright 2005-2022, SimTK Team
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
$citation_id = getIntFromRequest('citation_id');

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

if (!forge_check_perm ('datashare', $group_id, 'write')) {
	exit_permission_denied('You cannot update a citation unless you are an admin on that project.', 'home');
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


// Get citation.
$strQuery = 'SELECT * FROM plugin_datashare_citation ' .
	'WHERE citation_id=$1;';
$res = db_query_params($strQuery, array($citation_id));
$rows = db_numrows($res);
if ($rows != 1) {
	// Cannot find citation.
	exit_error('Cannot find citation', 'datashare');
}

$func = htmlspecialchars(getStringFromRequest('func'));
if ($func == "update_citation") {
	$authors = htmlspecialchars(trim(getStringFromRequest('authors')));
	$title = htmlspecialchars(trim(getStringFromRequest('title')));
	$publisher = htmlspecialchars(trim(getStringFromRequest('publisher')));
	$citation_year = getIntFromRequest('citation_year');
	$url = htmlspecialchars(trim(getStringFromRequest('url')));
	$url = strtolower($url);
	$doi = htmlspecialchars(trim(getStringFromRequest('doi')));
	$cite = getIntFromRequest('cite');
	$citation_id = getIntFromRequest('citation_id');

	if ($authors == "" ||
		$title == "" ||
		$publisher == "" ||
		$citation_year < 1900 ||
		$citation_year > 2099) {
		$error_msg = 'Citation not updated: please complete required field';
	}
	else if ($url != "" && 
		(!filter_var($url, FILTER_VALIDATE_URL) ||
		(strpos($url, "http://") !== 0 && strpos($url, "https://") !== 0))) {
		// Check for valid format of URL and http/https prefix.
		$error_msg = 'Citation not updated: invalid URL. Restoring to original values';
	}
	else {
		// Check for presence of data already.
		$res = db_query_params("SELECT citation_id FROM plugin_datashare_citation WHERE " .
			"study_id=$1 AND " .
			"authors=$2 AND " .
			"title=$3 AND " .
			"publisher_information=$4 AND " .
			"doi=$5 AND " .
			"citation_year=$6 AND " .
			"url=$7 AND " .
			"cite=$8 AND " .
			"citation_id=$9",
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
		if (!$res) {
			$error_msg = "Citation not updated: cannot read table";
		}
		else if (db_numrows($res) >= 1) {
			$error_msg = "Citation updated already";
			session_redirect('/plugins/datashare/admin/managePubs.php' .
				'?group_id=' . $group_id .
				'&study_id=' . $study_id);
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
				session_redirect('/plugins/datashare/admin/managePubs.php' .
					'?group_id=' . $group_id .
					'&study_id=' . $study_id);
			}
		}
	}
}

datashare_header(array('title'=>'Datashare'), $group_id);

echo "<div class=\"project_overview_main\">";
echo "<div style=\"display: table; width: 100%;\">";
echo "<div class=\"main_col\">";

?>

<div><h3><?php echo $study_results[0]->title; ?></h3></div>

<form enctype="multipart/form-data" action="/plugins/datashare/admin/updateCitation.php" method="POST">

<input type="hidden" name="func" value="update_citation" />
<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
<input type="hidden" name="study_id" value="<?php echo $study_id; ?>" />
<input type="hidden" name="citation_id" value="<?php echo $citation_id; ?>" />

<span class="required_note">Required fields outlined in blue.</span>
<br/><br/>

<style>
table>tbody>tr>td {
	padding-top: 5px;
}
</style>

<table>
<tr>
	<td><strong>Authors:&nbsp;</strong></td>
	<td><textarea class="required" rows='2' cols='60' name='authors'><?php
		echo htmlspecialchars(db_result($res, 0, 'authors'));
	?></textarea></td>
</tr>
<tr>
	<td><strong>Title:&nbsp;</strong></td>
	<td><textarea class="required" rows='5' cols='60' name='title'><?php
		echo htmlspecialchars(db_result($res, 0, 'title'));
	?></textarea></td>
</tr>
<tr>
	<td><strong>Publisher Information:&nbsp;</strong></td>
	<td><textarea class="required" rows='2' cols='60' name='publisher'><?php
		echo htmlspecialchars(db_result($res, 0, 'publisher_information'));
	?></textarea></td>
</tr>
<tr>
	<td><strong>Year:&nbsp;</strong></td>
	<td><input class="required" name="citation_year" type="number" min="1900" max="2099" step="1" value="<?php
		echo db_result($res, 0, 'citation_year');
	?>" /></td>
</tr>
<tr>
	<td><strong>DOI:&nbsp;</strong></td>
	<td>https://doi.org/<input type="text" name="doi" value="<?php
		echo htmlspecialchars(db_result($res, 0, 'doi'));
	?>" /></td>
</tr>
<tr>
	<td><strong>URL:&nbsp;</strong></td>
	<td><textarea rows='1' cols='60' name='url'><?php
		echo htmlspecialchars(db_result($res, 0, 'url'));
	?></textarea></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="checkbox" name="cite" value="1" <?php
		$cite = db_result($res, 0, 'cite');
		if ($cite == 1) {
			echo "checked";
		}
	?>/> Categorize under "please cite these papers"
</tr>
<tr>
	<td><input type="submit" name="submit" value="Update Citation" class="btn-cta" /></td>
</tr>
</table>
</form>


<?php

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
