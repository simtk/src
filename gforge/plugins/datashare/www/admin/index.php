<?php
/**
 *
 * index.php
 *
 * Main admin index page for creating new study.
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

$pluginname="datashare";
datashare_header(array('title'=>'Datashare','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n";
echo "<div class=\"main_col\">\n";

if (session_loggedin()) {

	//if (!forge_check_perm('pubs', $group_id, 'project_admin')) {
	if (!forge_check_perm ('datashare', $group_id, 'write')) {
		//exit_permission_denied(_('You cannot access the datashare admin section for a project unless you are an admin on that project.'), 'home');
		exit_error("Access Denied: You cannot access the datashare admin section for a project unless you are an admin on that project", 'datashare');
	}


			$userperm = $group->getPermission();//we'll check if the user belongs to the group (optional)
			if ( !$userperm->IsMember()) {
				exit_error("Access Denied", "You are not a member of this project");
			}


						// get current studies
						$study = new Datashare($group_id);

						if (!$study || !is_object($study)) {
	                       exit_error('Error','Could Not Create Study Object');
                        } elseif ($study->isError()) {
	                       exit_error($study->getErrorMessage(), 'Datashare Error');
                        }

						$study_result = $study->getStudyByGroup($group_id);
						$numstudies = 0;
						if ($study_result) {
                           $numstudies = count($study_result);
                        }
                        if ($numstudies < 3) {
                           echo "<a class='btn-blue' href='add.php?group_id=$group_id'>Add Study</a> <a style='text-align: right; float: right;' href='https://<datashare server>/apps/import/metadata.php' target='_blank'>Learn more about adding metadata to your dataset</a><br /><br />";
                        }
						if ($study_result) {

						  echo '<table class="table">';
						  echo "<tr><th>Study</th><th>Status</th><th>Description</th><th></th></tr>";
						  foreach ($study_result as $result) {
						      if ($result->active) {
							   $status = "Active";
							 } else {
							   $status = "Pending Activation";
							 }
						     echo "<tr><td>$result->title</td><td>$status</td><td>$result->description</td><td><a href='edit.php?group_id=$group_id&study_id=$result->study_id'>Edit</a></td></tr>";
						  }
						  echo "</table>";
						} else {
						  echo "No current studies exist for this project<br /><br />";
						}

						if ($numstudies >= 3) {
                           echo "<b>Note: maximum number of studies reached</b><br /><br />";
                        }

}

echo "</div><!--main_col-->\n</div><!--display table-->\n</div><!--project_overview_main-->\n";

datashare_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
