<?php
/**
 *
 * datashare plugin add.php
 *
 * admin page for creating new study.
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

require_once $gfplugins.'env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once '../datashare-utils.php';
require_once $gfplugins.'datashare/include/Datashare.class.php';
require_once $gfwww.'project/project_utils.php';


$group_id = getIntFromRequest('group_id');

if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'datashare');
}

$title = getStringFromRequest('title');
$description = getHtmlTextFromRequest('description');
$is_private = getStringFromRequest('is_private');
$template = getIntFromRequest('template');
if (!$is_private) {
  $is_private = 0;
}
if (!$template) {
  $template = 1;
}

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
    } elseif ($study->isError()) {
	   exit_error($study->getErrorMessage(), 'Datashare Error');
    }

	$templates = $study->getTemplate();


	if (getStringFromRequest('post_changes')) {
		if (!form_key_is_valid(getStringFromRequest('form_key'))) {
			exit_form_double_submit('datashare');
		}

	    // add new study



		if ($insert_result = $study->insertStudy($group_id,$title,$description,$is_private,$template)) {
		   $feedback = _('Study Added');
	    } else {
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
	<script>
        // Update flag input components after document has been loaded completely.
        $(document).ready(function() {
	<?php
    // Flag components that have errors.
    if (isset($arrErrors)) {
       for ($cnt = 0; $cnt < count($arrErrors) - 1; $cnt++) {
          $tagName = $arrErrors[$cnt];
          // Generate the css associated with component to be flagged.
		  if ($tagName == 'description') {
             echo '$("textarea[name=\'description\']").css("border-color", "red");';
		  } else {
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
		<strong>'._('Title')._(': ').'</strong><br />
		<input type="text" name="title" class="required" size="60" ';

	echo '/></p>
		<p>
		<strong>'._('Description')._(': ').'</strong></p>';

	echo '<textarea name="description" rows="5" cols="50" class="required"></textarea>';
	echo '<br /><br />';


	echo '<p><strong>'._('Data Directory Structure Template')._(': ').'</strong> &nbsp; <a href="https://<datashare server>/apps/import/metadata.php#using" target="_blank">Learn more</a></p>';

	echo '<p><span class="required_note">Note: This selection cannot be changed once a data study has been created</span><br />';

	echo '<p>Using Subject-Based Template</p>';
	/*
	echo '<select name="template">';
	foreach ($templates as $template) {
	   echo "<option value='".$template->template_id."'>".$template->title."</option>";
	}
	echo '</select>';
	echo '<br /><br />';
	*/
	echo "<input type='hidden' name='template' value='1'>";

	echo '<br /><p><strong>'._('Publicly Viewable')._(': ').'</strong></p>';

	echo "<p><input type=\"radio\" name=\"is_private\" value=\"0\" checked> Public - anyone can view and download the data, regardless of whether they are a SimTK member or not";
	echo "<p><input type=\"radio\" name=\"is_private\" value=\"1\"> Registered User - only SimTK members who are logged into SimTK can view and download the data.";
	echo "<p><input type=\"radio\" name=\"is_private\" value=\"2\"> Private - only designated members of the project can view and download the data.";

	echo '<div><input type="submit" name="submit" value="'._('Submit').'" class="btn-cta" /></div></div></form>';

	echo "</div>";
	constructSideBar($group);
	echo "</div></div>";

	datashare_footer(array());

} else {

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
