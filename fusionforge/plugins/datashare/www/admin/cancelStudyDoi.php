<?php

/**
 * Datashare Admin: Cancel a DOI request on study.
 *
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once '../../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once '../datashare-utils.php';
require_once $gfplugins.'datashare/include/Datashare.class.php';

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
	exit_error($group->getErrorMessage(),'datashare');
}

if (session_loggedin()) {
	if (!forge_check_perm ('datashare', $group_id, 'write')) {
		exit_error("Access Denied: You cannot access the datashare admin section for a project unless you are an admin on that project", 'datashare');
	}

	$userperm = $group->getPermission();//we'll check if the user belongs to the group (optional)
	if (!$userperm->IsMember()) {
		exit_error("Access Denied", "You are not a member of this project");
	}

	// get Datashare study object.
	$study = new Datashare($group_id);
	if (!$study || !is_object($study)) {
		exit_error('Error','Could Not Get Study Object');
	}
	elseif ($study->isError()) {
		exit_error($study->getErrorMessage(), 'Datashare Error');
	}

	if (getStringFromRequest('submit')) {
		$func = getStringFromRequest('func');
		$sure = getStringFromRequest('sure');
		$really_sure = getStringFromRequest('really_sure');
		if ($func == 'cancel_study_doi' && $study_id) {
			if ($sure && $really_sure) {
				if (!$study->cancelDOI($study_id)) {
					exit_error($frsf->getErrorMessage(), 'datashare');
				}
				else {
					$feedback .= 'DOI Request Canceled.';
					session_redirect('/plugins/datashare/admin?group_id=' . $group_id);
				}
			}
			else {
				$error_msg = 'DOI request not canceled: you did not check “I am Sure”';
			}
		}
	}
}

datashare_header(array('title'=>'Datashare','pagename'=>"datashare",'sectionvals'=>array(group_getname($group_id))),$group_id);


// Cancel a DOI request.

echo '<hr />';
echo '<div><h3>' . $study->getStudy($study_id)[0]->title . '</h3></div>';
echo '<form action="cancelStudyDoi.php?group_id='.$group_id.'" method="post">
	<input type="hidden" name="func" value="cancel_study_doi" />
	<input type="hidden" name="study_id" value="'. $study_id .'" />
	<p>You are about to cancel the DOI request for this study!</p>
	<input type="checkbox" name="sure" value="1" />&nbsp;'._('I am Sure').'<br />
	<input type="checkbox" name="really_sure" value="1" />&nbsp;'._('I am Really Sure').'<br /><br />
	<input type="submit" name="submit" value="Cancel DOI Request" class="btn-cta" />
	</form>';

datashare_footer(array());

?>
