<?php

/**
 *
 * datashare plugin add.php
 *
 * admin page for creating new study.
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

$title = getStringFromRequest('title');
$description = getStringFromRequest('description');
$is_private = getStringFromRequest('is_private');
$template = getIntFromRequest('template');
if (!$is_private) {
	$is_private = 0;
}
if (!$template) {
	$template = 1;
}
$subject_prefix = getStringFromRequest('subject_prefix');
if (trim($subject_prefix) == "") {
	$subject_prefix = "subject";
}
$useAgreement = getIntFromRequest('use_agreement');
$customAgreement = trim(getStringFromRequest('license_preview'));

if (session_loggedin()) {

	/*
	if (!forge_check_perm('project_admin', $group_id)) {
		exit_permission_denied(_('You cannot add a new study for a project unless you are an admin on that project.'), 'home');
	}
	*/
	//if (!forge_check_perm('pubs', $group_id, 'project_admin')) {

	if (!forge_check_perm ('datashare', $group_id, 'write')) {
		exit_permission_denied(_('You cannot add a new study for a project unless you are an admin on that project.'), 'home');
	}

	// get Datashare object
	$study = new Datashare($group_id);

	if (!$study || !is_object($study)) {
		exit_error('Error','Could Not Create Study Object');
	}
	elseif ($study->isError()) {
		exit_error($study->getErrorMessage(), 'Datashare Error');
	}

	$exec_changes = true;
	if ($useAgreement !== 0) {
		if (stripos($customAgreement, "[Insert Year(s)], [Insert organization or names of copyright holder(s)]") !== FALSE) {
			$exec_changes = false;
			$warning_msg = "Please update copyright years and organization or names of copyright holder(s) in license agreement";
		}
		else if (stripos($customAgreement, "[Insert Year(s)]") !== FALSE) {
			$exec_changes = false;
			$warning_msg = "Please update copyright year(s) in license agreement";
		}
		else if (stripos($customAgreement, "[Insert organization or names of copyright holder(s)]") !== FALSE) {
			$exec_changes = false;
			$warning_msg = "Please update organization or names of copyright holder(s) in license agreement";
		}
	}

	if (getStringFromRequest('post_changes') && $exec_changes) {
		if (!form_key_is_valid(getStringFromRequest('form_key'))) {
			exit_form_double_submit('datashare');
		}

		// add new study
		if ($insert_result = $study->insertStudy($group_id, $title, $description, 
			$is_private, $template, 
			$subject_prefix, $useAgreement, $customAgreement)) {
			$feedback = 'Study Added';
		}
		else {
			form_release_key(getStringFromRequest('form_key'));
			$error_msg = $study->getErrorMessage();
			// Extract name(s) of input components which should be flagged.
			$error_msg = retrieveErrorMessages($error_msg, $arrErrors);
		}
	}

	/*
		Show the submit form
	*/
	//$group = group_get_object($group_id);
	datashare_header(array('title'=>'Datashare'),$group_id);

	echo "<div class=\"project_overview_main\">";
	echo "<div style=\"display: table; width: 100%;\">";
	echo "<div class=\"main_col\">";

?>


<script src='/frs/download.js'></script>
<link rel='stylesheet' href='/frs/download.css' type='text/css' />
<script src='/frs/admin/preload_license.js'></script>
<script src='/frs/admin/license.js'></script>

<script>
        // Update flag input components after document has been loaded completely.
        $(document).ready(function() {
		$(".use_agreement").click(function() {
			if ($(this).val() == 0) {
				$(".license_preview").hide();
			}
			else {
				$(".license_preview").show();
			}
		});

		// Handle popover show and hide.
		$(".myPopOver").hover(function() {
			$(this).find(".popoverLic").popover("show");
		});
		$(".myPopOver").mouseleave(function() {
			$(this).find(".popoverLic").popover("hide");
		});

<?php
	// Flag components that have errors.
	if (isset($arrErrors)) {
		for ($cnt = 0; $cnt < count($arrErrors) - 1; $cnt++) {
			$tagName = $arrErrors[$cnt];
			// Generate the css associated with component to be flagged.
			if ($tagName == 'description') {
				echo '$("textarea[name=\'description\']").css("border-color", "red");';
			}
			else {
				echo '$("input[name=\'' . $tagName . '\']").css("border-color", "red");';
			}
		}
	}
?>
	 });

</script>

<?php

	echo '<p><span class="required_note">Required fields outlined in blue</span><br />';
	echo '</p>';
	echo '
		<form id="addstudyform" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<div class="form_simtk">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="post_changes" value="y" />
		<input type="hidden" name="form_key" value="'. form_generate_key() .'" />
		<p>
		<strong>'._('Title')._(': ').'</strong><br/>
		Restrictions: Up to 80 alphanumeric characters, spaces, dashes (-), and underscores (_).
		<input type="text" name="title" class="required" size="60" ';

	echo '/></p>
		<p>
		<strong>'._('Description')._(': ').'</strong></p>';

	echo '<textarea name="description" rows="5" cols="60" class="required"></textarea>';
	echo '<br /><br />';


	echo '<p><strong>'._('Data Directory Structure Template')._(': ').'</strong> &nbsp; <a href="https://' .
		$datashareServer .
		'/apps/import/metadata.php#using" target="_blank">Learn more</a></p>';

	//echo '<p><span class="required_note">Note: This selection cannot be changed once a data study has been created</span><br />';

	echo '<p>Using Top Folder Template (default)</p>';

	echo "<input type='hidden' name='template' value='1'>";

	echo '<p>';
	echo '<div style="margin-left:20px;width:600px;">';
	echo '<strong>Top Level Folder Prefix:</strong> ';
	echo '<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="If your top level folders are named subject01, subject02, etc., then you specify subject as the prefix.">?</a></span>';
	echo '<br/>';

	echo 'Restrictions: Up to 80 alphabetic characters.';
	echo '<input type="text" name="subject_prefix" class="required" size="58" placeholder="subject" /></p>';
	echo '</div>';

	echo '<br /><p><strong>'._('Publicly Viewable')._(': ').'</strong></p>';

	echo '<p><input type="radio" name="is_private" value="0" checked> Public </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Anyone can view and download the data, regardless of whether they are a SimTK member or not.">?</a><span>';
	echo '<p><input type="radio" name="is_private" value="1"> Registered User </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Only SimTK members who are logged into SimTK can view and download the data.">?</a></span>';
	echo '<p><input type="radio" name="is_private" value="2"> Private </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Only designated members of the project can view and download the data.">?</a></span>';

?>

<br/>
<br/>

<strong>License agreement:</strong>
<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="You can display a download agreement to users before they download any file in this study.<br/><br/>

For code, we recommend that you add a license agreement as a comment header in every source file. This is in addition to or instead of this download agreement. A summary of each license is provided, but you should consult the license itself for the exact terms that apply.">?</a></span>

<br/>

<div style="margin-left:20px;">

<strong><label>Open Source Licenses</label></strong>
<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="
* Allows others to use your data/documentation/software for any purpose, commercial or non-commercial, make modifications, and redistribute it.<br/>
* Includes a disclaimer of warranty.<br/>
* Users are obligated to include your license terms if they redistribute. See <a href='http://www.opensource.org' target='_blank'>http://www.opensource.org</a> and <a href='http://en.wikipedia.org/wiki/Comparison_of_free_software_licenses' target='_blank'>Wikipedia</a> for more information.<br/>
* SimTK provides some common open-source licenses to choose from.  Many other options exist. See <a href='https://spdx.org/licenses/' target='_blank'>list of other open-source licenses</a>.<br/><br/>

These licenses differ in the additional obligations they place on the users.
">?</a></span>

<p>
<input type="radio" name="use_agreement" class="use_agreement" value="2" checked="checked"> MIT </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Default license. No additional obligations.">?</a></span>
</p>
<p>
<input type="radio" name="use_agreement" class="use_agreement" value="3"> LGPL </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Modifications that are redistributed must include the modified source under the same terms.">?</a></span>
</p>
<p>
<input type="radio" name="use_agreement" class="use_agreement" value="4"> GPL </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Any distributed work that includes all or part of GPL-licensed material must itself be offered under GPL, meaning that all the source code is available.">?</a></span>
</p>
<p>
<input type="radio" name="use_agreement" class="use_agreement" value="6"> Creative Commons Attribution 4.0 </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Others can share and adapt the file(s) for any purpose, even commercially, but they must give proper attribution.  Similar to MIT license but applies to works beyond just software and related documentation.  Also, it provides more terms and conditions.">?</a></span>
</p>
<p>
<input type="radio" name="use_agreement" class="use_agreement" value="7"> Apache 2.0 </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Similar to MIT license. Some major differences: 1) Apache 2.0 offers more explicit patent protection and 2) it also requires listing all modifications to original software.">?</a></span>
</p>

<strong><label>Other licenses</label></strong>
<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content='Many other licenses can be used. See <a href="http://en.wikipedia.org/wiki/Comparison_of_free_software_licenses" target="_blank">Wikipedia</a> and <a href="http://creativecommons.org/licenses" target="_blank">Creative Commons</a>. For complex licenses, we recommend that you enter a URL for the license, e.g., "The [project name] license agreement can be read here: http://XXX."'>?</a></span>

<p>
<input type="radio" name="use_agreement" class="use_agreement" value="8"> Creative Commons Attribution-Non-Commercial 4.0 </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Similar to open-source licenses except your work can only be used for non-commercial purposes.">?</a></span>
</p>
<p>
<input type="radio" name="use_agreement" class="use_agreement" value="5"> Creative Commons Attribution-Non-Commercial 3.0 </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Similar to open-source licenses except your work can only be used for non-commercial purposes.">?</a></span>
</p>
<p>
<input type="radio" name="use_agreement" class="use_agreement" value="1"> Custom </input><span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Specify a custom license.">?</a></span>
</p>
<p>
<input type="radio" name="use_agreement" class="use_agreement" value="0"> None </input>
</p>

<br/>

<span class="edit_notice" style="color:#f75236;">Update first line of license with (1) copyright year and (2) organization or copyright holder</span>

<br/>

<input type="hidden" class="custom_license" value="" /> <textarea class="license_preview" style="margin-top:5px;" rows="10" cols="50" name="license_preview" title="Preview license">
</textarea>

</div>

<br/>
<br/>

<?php
	echo '<div><input type="submit" name="submit" value="'._('Submit').'" class="btn-cta" /></div></div></form>';

	echo "</div>";
	constructSideBar($group);
	echo "</div></div>";

	datashare_footer(array());
}
else {
	exit_not_logged_in();
}

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
