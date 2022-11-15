<?php
/**
 * Delete citation in a DataShare study.
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

if (!forge_check_perm('datashare', $group_id, 'write')) {
	exit_permission_denied('You cannot delete a citation unless you are an admin on that project.', 'home');
}

// get Datashare object
$study = new Datashare($group_id);
if (!$study || !is_object($study)) {
	exit_error('Error','Could Not Create Study Object');
}
elseif ($study->isError()) {
	exit_error($study->getErrorMessage(), 'Datashare Error');
}

// Get citation.
$strQuery = 'SELECT title FROM plugin_datashare_citation ' .
	'WHERE citation_id=$1;';
$res = db_query_params($strQuery, array($citation_id));
$rows = db_numrows($res);
if ($rows != 1) {
	// Cannot find citation.
	exit_error('Cannot find citation', 'datashare');
}


datashare_header(array('title'=>'Datashare'), $group_id);

echo '<hr/>';
echo "<div class=\"project_overview_main\">";
echo "<div style=\"display: table; width: 100%;\">";
echo "<div class=\"main_col\">";

echo '<div><h3>' . db_result($res, 0, 'title') . '</h3></div>';
echo '<form action="/plugins/datashare/admin/managePubs.php?' .
	'group_id=' . $group_id .
	'" method="post">';
echo '<input type="hidden" name="func" value="delete_citation" />';
echo '<input type="hidden" name="study_id" value="' . $study_id . '" />';
echo '<input type="hidden" name="citation_id" value="' . $citation_id . '" />';
echo 'You are about to permanently and irretrievably delete this citation!';
echo '<p>';
echo '<input type="checkbox" name="sure" value="1" />&nbsp;I am Sure<br/>';
echo '<input type="checkbox" name="really_sure" value="1" />&nbsp;I am Really Sure<br/>';
echo '<br/><input type="submit" name="submit" value="Delete Citation" class="btn-cta" />';
echo '</p>';
echo '</form>';

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
