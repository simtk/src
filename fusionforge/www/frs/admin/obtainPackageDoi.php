<?php
/**
 * Project Admin: Obtain a package/study DOI.
 *
 * Copyright 2016-2020, Henry Kwong, Tod Hing - SimTK Team
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfplugins.'datashare/www/datashare-utils.php';
require_once $gfplugins.'datashare/include/Datashare.class.php';

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}


$func = getStringFromRequest('func');
$study_id = getIntFromRequest('study_id');
$package_id = getIntFromRequest('package_id');

if ($func == "obtain_package_doi") {
	if (!$package_id) {
		session_redirect('/frs/admin/?group_id='.$group_id);
	}
	if ($group->isError()) {
		exit_error($group->getErrorMessage(), 'frs');
	}
}
else if ($func == "obtain_study_doi") {
	if (!$study_id) {
		session_redirect('/plugins/datashare/admin/?group_id='.$group_id);
	}
	if ($group->isError()) {
		exit_error($group->getErrorMessage(), 'datashare');
	}
}
else {
}

$theEntityType = false;
$theEntityName = false;
if (isset($package_id) && $package_id != 0) {
	// Downloads.
	session_require_perm ('frs', $group_id, 'write') ;

	// Get package.
	$frsp = new FRSPackage($group, $package_id);
	if (!$frsp || !is_object($frsp)) {
		exit_error(_('Could Not Get FRS Package'),'frs');
	}
	elseif ($frsp->isError()) {
		exit_error($frsp->getErrorMessage(),'frs');
	}
	$theEntityType = "package";
	$theEntityName = $frsp->getName();
}
else if (isset($study_id) && $study_id != 0) {
	// DataShare
	if (!session_loggedin()) {
		exit_error('User not logged in', '');
	}
	if (!forge_check_perm ('datashare', $group_id, 'write')) {
		exit_error("Access Denied: You cannot access the datashare admin section for a project unless you are an admin on that project", 'datashare');
	}
	// We check if the user belongs to the group.
	$userperm = $group->getPermission();
	if ( !$userperm->IsMember()) {
		exit_error("Access Denied", "You are not a member of this project");
	}
	$study = new Datashare($group_id);
	if (!$study || !is_object($study)) {
		exit_error('Error','Study has not been created');
	}
	elseif ($study->isError()) {
		exit_error($study->getErrorMessage(), 'Datashare Error');
	}
	$study_results = $study->getStudy($study_id);
	if ($study_results && isset($study_results[0])) {
		$theEntityName = $study_results[0]->title;
		$theEntityType = "study";
	}
}


// Obtain package DOI.
if (getStringFromRequest('submit')) {
	if ($func=="obtain_package_doi" && $package_id != 0) {
		$doi = getIntFromRequest('doi');
		if (empty($doi)) {
			$doi = 0;
		}
		$doi_confirm = 0;
	
		// get user
		$user = session_get_user(); // get the session user
		$user_id = $user->getID();
	
		if ($doi) {
			$ret = $frsp->setDoi($user_id);
			if ($ret === false) {
				// Return the error message.
				$error_msg = $frsp->getErrorMessage();
			}

			$doi_confirm = 1;
			$real_name = $user->getRealName();
			$message = "\nPlease visit the following URL to assign DOI: \n" . 
				util_make_url('/admin/downloads-doi.php');
			util_send_message("webmaster@simtk.org", 
				sprintf('DOI for %s package requested by %s', $theEntityName, $real_name), 
				$message);

			$feedback = 'Your DOI for the package will be emailed within 72 hours. ';
		}
	}
	else if ($func=="obtain_study_doi" && $study_id != 0) {
		$doi = getIntFromRequest('doi');
		if (empty($doi)) {
			$doi = 0;
		}
		$doi_confirm = 0;
	
		// get user
		$user = session_get_user(); // get the session user
		$user_id = $user->getID();
	
		if ($doi) {
			$ret = $study->setDoi($study_id, $user_id);
			if ($ret === false) {
				// Return the error message.
				$error_msg = "Cannot request DOI for $theEntityName study";
			}

			$doi_confirm = 1;
			$real_name = $user->getRealName();
			$message = "\nPlease visit the following URL to assign DOI: \n" . 
				util_make_url('/admin/downloads-doi.php');
			util_send_message("webmaster@simtk.org", 
				sprintf('DOI for %s study requested by %s', $theEntityName, $real_name), 
				$message);

			$feedback = 'Your DOI for the study will be emailed within 72 hours. ';
		}
	}
}

if (isset($package_id) && $package_id != 0) {
	frs_admin_header(array('title'=>'Obtain Package DOI','group'=>$group_id));
}
if (isset($study_id) && $study_id != 0) {
	datashare_header(array('title'=>'Obtain Study DOI'), $group_id);
}

?>

<script>
	$(document).ready(function() {
		$('#doi').change(function() {
			if (this.checked)
				//$('#doi_info').fadeIn('slow');
				$('#doi_info').show();
			else
				$('#doi_info').hide();
		});
		$("#submit").click(function() {
			if ($('#doi').is(":checked")) {

<?php if (isset($package_id) && $package_id != 0) { ?>

				if (!confirm("I confirm that I would like to have this package made permanent. Please issue a DOI.")) {
					event.preventDefault();
				}

<?php } else if (isset($study_id) && $study_id != 0) { ?>

				if (!confirm("I confirm that I would like to have this study made permanent. Please issue a DOI.")) {
					event.preventDefault();
				}

<?php } ?>
			}
		});
	});

</script>
<script type="text/javascript">
	$(document).ready(function() {
		window.history.forward(1);
	});
</script>
  
<style>
td {
	padding-bottom:5px;
	vertical-align:top;
}
</style>

<?php if ((isset($doi) && ($doi) && isset($doi_confirm) && ($doi_confirm)) ||
	($theEntityType == "study" && $study->isDOI($study_id)) ||
	($theEntityType == "package" && $frsp->isDOI())) { ?>

<p>This <?php echo $theEntityType; ?> is being assigned a DOI and can no longer be edited or deleted.</p>
<script type="text/javascript">
	$(document).ready(function() {
		window.history.forward(1);
	});
</script>

<?php } else { ?>

<div><h4><?php echo $theEntityName; ?><p/></h4></div>

<form id="obtainPackageDoi" enctype="multipart/form-data" action="obtainPackageDoi.php" method="post">

<?php if (isset($package_id) && $package_id != 0) { ?>

<input type="hidden" name="func" value="obtain_package_doi" />
<input type="hidden" name="package_id" value="<?php echo $package_id; ?>" />

<?php } else if (isset($study_id) && $study_id != 0) { ?>

<input type="hidden" name="func" value="obtain_study_doi" />
<input type="hidden" name="study_id" value="<?php echo $study_id; ?>" />

<?php } ?>

<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />

<table>

<tr>
	<td><input type="checkbox" name="doi" id="doi" value="1" />&nbsp;Obtain a DOI for <?php echo $theEntityType; ?>
	<div id="doi_info" style="display:none">

<?php if (isset($package_id) && $package_id != 0) { ?>

		<font color="#ff0000">Warning: After the DOI has been issued, You will not be able to:
		<ul>
		<li>remove or edit this package,</li>
		<li>remove or edit releases within the package,</li>
		<li>remove or update any files asscoiated with this package. If the package includes a link to a GitHub file, that file will no longer be updated.</li>
		</ul>
		</font>

<?php } else if (isset($study_id) && $study_id != 0) { ?>

		<font color="#ff0000">Warning: You will not be able to remove or edit this study after the DOI has been issued.  You will not be able to import, edit, or remove files it contains either.</font>

<?php } ?>

	</div>
	</td>
</tr>

<?php
	// Check if type is study and the study is private.
	if (isset($study_id) && $study_id != 0) {
		$study_results = $study->getStudy($study_id);
		if ($study_results && isset($study_results[0])) {
			$isPrivate = $study_results[0]->is_private;
			if ($isPrivate == 2) {
				// The study is private.
?>

<tr>
	<td>
	<b>NOTE:</b> This study is private. <a href="/plugins/datashare/admin/edit.php?group_id=<?php
		echo $group_id; ?>&study_id=<?php
		echo $study_id; ?>">Click here</a> to edit study.
	</td>
</tr>

<?php
			}
		}
	}
?>

<tr>
	<td><br/><input type="submit" name="submit" id="submit" value="Submit" class="btn-cta" /></td>
</tr>
</table>

</form>

<?php
} // end of else

frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
