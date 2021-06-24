<?php
/**
 *
 * Project Admin: Create a release
 *
 * Copyright 1999-2001 (c) VA Linux Systems , Darrell Brogdon
 * Copyright 2002 (c) GForge, LLC
 * Copyright 2010 (c), FusionForge Team
 * Copyright (C) 2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2014, Franck Villaume - TrivialDev
 * http://fusionforge.org/
 * Copyright 2016-2021, Henry Kwong, Tod Hing - SimTK Team
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
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error('Error', $group->getErrorMessage(), 'frs');
}

// check the permissions and see if this user is a release manager.
// If so, he can create a release
session_require_perm('frs', $group_id, 'write');

$package_id = getIntFromRequest('package_id');
if (!$package_id) {
	session_redirect('/frs/admin/?group_id='.$group_id);
}

// Get package.
$frsp = new FRSPackage($group, $package_id);
if (!$frsp || !is_object($frsp)) {
	exit_error(_('Could Not Get FRS Package'),'frs');
}
elseif ($frsp->isError()) {
	exit_error($frsp->getErrorMessage(),'frs');
}


$upload_dir = forge_get_config('ftp_upload_dir') . "/" . $group->getUnixName();

$copy_release = getStringFromRequest('copy_release');
$submitAndNotify = getStringFromRequest('submitAndNotify');
$submitNoNotify = getStringFromRequest('submitNoNotify');
if ($submitAndNotify || $submitNoNotify) {
	$release_name = trim(getStringFromRequest('release_name'));
	$release_date = getStringFromRequest('release_date');
	$release_date = strtotime($release_date);
	$release_status = getIntFromRequest('status_id');
	$uploaded_notes = getUploadedFile('uploaded_notes');
	$uploaded_changes = getUploadedFile('uploaded_changes');
	$release_notes = getStringFromRequest('release_notes');
	$release_changes = getStringFromRequest('release_changes');
	$preformatted = getStringFromRequest('preformatted');
	$release_desc = getStringFromRequest('release_desc');
	$exec_changes = true;
	if ($submitAndNotify) {
		$emailChange = 1;
	}
	else {
		$emailChange = 0;
	}

	// Check for uploaded release notes
	if ($uploaded_notes["tmp_name"]) {
		if (!is_uploaded_file($uploaded_notes['tmp_name'])) {
			exit_error(_('Attempted File Upload Attack'), 'frs');
		}
		if ($uploaded_notes['type'] !== 'text/plain') {
			$error_msg .= _('Release Notes Are not in Text') . '<br/>';
			$exec_changes = false;
		}
		else {
			$notes = fread(fopen($uploaded_notes['tmp_name'], 'r'), $uploaded_notes['size']);
			if (strlen($notes) < 20) {
				$error_msg .= _('Release Notes Are Too Small') . '<br/>';
				$exec_changes = false;
			}
		}
	}
	else {
		$notes = $release_notes;
	}

	// Check for uploaded change logs
	if ($uploaded_changes['tmp_name']) {
		if (!is_uploaded_file($uploaded_changes['tmp_name'])) {
			exit_error(_('Attempted File Upload Attack'), 'frs');
		}
		if ($uploaded_changes['type'] !== 'text/plain') {
			$error_msg .= _('Change Log Is not in Text') . '<br/>';
			$exec_changes = false;
		}
		else {
			$changes = fread(fopen($uploaded_changes['tmp_name'], 'r'), $uploaded_changes['size']);
			if (strlen($changes) < 20) {
				$error_msg .= _('Change Log Is Too Small') . '<br/>';
				$exec_changes = false;
			}
		}
	} else {
		$changes = $release_changes;
	}

	$warning_msg = '' ;
	if (!$release_name) {
		$warning_msg .= _('Must define a release name.');
	}
	elseif (!$package_id) {
		$warning_msg .= _('Must select a package.');

	}
	elseif ($exec_changes == true) {
		// Get package.
		$frsp = new FRSPackage($group, $package_id);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'), 'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		else {
			// Create a new FRSRelease in the db.
			$frsr = new FRSRelease($frsp);
			if (!$frsr || !is_object($frsr)) {
				exit_error(_('Could Not Get FRS Release'), 'frs');
			}
			elseif ($frsr->isError()) {
				exit_error($frsr->getErrorMessage(), 'frs');
			}
			else {
				db_begin();
				if (!$frsr->create($release_name, $notes, $changes, 
					$preformatted, $release_date, 
					$release_status, $release_desc, $emailChange)) {
					db_rollback();
					exit_error($frsr->getErrorMessage(), 'frs');
				}
				else if ($copy_release == "1") {
					// Get the previous release and copy files over from it.
					$prevReleaseId = getIntFromRequest('prev_rel_id');
					if (!isset($prevReleaseId) || $prevReleaseId == -1) {
						db_rollback();
						exit_error('No previous release to copy from', 'frs');
					}
					$prevr = new FRSRelease($frsp, $prevReleaseId);
					foreach ($prevr->getFiles() as $file) {
						if (!$file->copyToRelease($frsr, true)) {
							db_rollback();
							exit_error($file->getErrorMessage(), 'frs');
						}
					} 
				}

				db_commit();

				$feedback .= 'Added Release';

				if ($copy_release == "1") {
					session_redirect('/frs/admin/?group_id=' . $group_id . 
						'&package_id=' . $package_id .
						'&release_id=' . $frsr->getID());
				}
				else {
					session_redirect('/frs/admin/addfile.php?group_id=' . $group_id .
						'&package_id=' . $package_id .
						'&release_id=' . $frsr->getID());
				}
			}
		}
	}
}


// Get latest release associated with this package
$prevReleaseId = -1;
$strFrsQuery = "SELECT release_id FROM frs_release " .
	"WHERE package_id=$1 " .
	"ORDER BY release_date DESC LIMIT 1";
$res = db_query_params($strFrsQuery, array($frsp->getID()));
if ($res && $row = db_fetch_array($res)) {
	// Found a previous release for this package. Allow it to be used as a template.
	$prevReleaseId = $row['release_id'];
}


frs_admin_header(array('title'=>'Create Release','group'=>$group_id));

?>

<style>
td {
	padding-top:5px;
	vertical-align:top;
}
</style>

<div><h3>Add Release to Package <?php echo $frsp->getName(); ?></h3></div>

<form enctype="multipart/form-data" action="/frs/admin/createrelease.php" method="POST">

<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
<input type="hidden" name="func" value="create_release" />
<input type="hidden" name="package_id" value="<?php echo $package_id; ?>" />
<input type="hidden" name="prev_rel_id" value="<?php echo $prevReleaseId ?>" />

<span class="required_note">Required fields outlined in blue.</span>

<table>
<tr>
	<td><strong>Package Name:&nbsp;</strong></td>
	<td><?php echo $frsp->getName(); ?></td>
</tr>
<tr>
	<td><strong>Release Date:</strong></td>
	<td><input type="text" name="release_date" value="<?php echo date('Y-m-d H:i'); ?>" size="16" maxlength="16" /></td>
</tr>
<tr>
	<td><strong>Release Name:</strong></td>
	<td><input type="text" class="required" required="required" name="release_name" value="" pattern=".{3,}" title="<?php echo  _('At least 3 characters') ?>" /></td>
</tr>
<tr>
	<td><strong><?php echo _('Status')._(':'); ?></strong></td>
	<td>
		<input type="radio" name="status_id" value="1" checked="checked" ><label>Active</label></input>
		<input type="radio" name="status_id" value="3" ><label>Hidden</label></input>
	</td>
</tr>
<tr>
	<td><strong>Description:</strong></td>
	<td><textarea class='' style='margin-top:5px;' rows='6' cols='50' name='release_desc'></textarea></td>
</tr>
</table>

<div class="download_package" style="margin-bottom:0px;background-image:none;">
Edit the Release Notes or Change Log for this release of this package. These changes will apply to all files attached to this release. You can either upload the release notes and change log individually, or paste them in together below.
</div>

<br/>
<table>
<tr>
	<td colspan="2">
		<strong><?php echo _('Upload Release Notes')._(':'); ?></strong>
		<?php echo('('._('max upload size: '.
//			human_readable_bytes(util_get_maxuploadfilesize())).')') 
			human_readable_bytes(getUploadFileSizeLimit($group_id))).')') 
		?>
	</td>
</tr>
<tr>
	<td>Paste the Notes or import a <strong>TEXT file</strong></td>
	<td><input type="file" name="uploaded_notes" size="30" /></td>
</tr>
<tr>
	<td colspan="2"><textarea name="release_notes" rows="10" cols="60"></textarea></td>
</tr>

<tr>
	<td colspan="2">
		<strong><?php echo _('Upload Change Log')._(':'); ?></strong>
		<?php echo('('._('max upload size: '.
//			human_readable_bytes(util_get_maxuploadfilesize())).')') 
			human_readable_bytes(getUploadFileSizeLimit($group_id))).')') 
		?>
	</td>
</tr>
<tr>
	<td>Paste the Log or import a <strong>TEXT file</strong></td>
	<td><input type="file" name="uploaded_changes" size="30" /></td>
</tr>
<tr>
	<td colspan="2"><textarea name="release_changes" rows="10" cols="60"></textarea></td>
</tr>

<tr>
	<td colspan="2">
		<input type="checkbox" name="preformatted" value="1" /> <?php echo _('Preserve my pre-formatted text') ?>

<tr style="height:10px;">
	<td style="border-bottom:1px solid #ccc;"></td>
	<td style="border-bottom:1px solid #ccc;"></td>
</tr>


<?php
	if ($prevReleaseId != -1) {
		// There is a previous release for this package present.
?>
<tr>
	<td colspan="2" style="padding-top:10px;">
		<input type="checkbox" name="copy_release" value="1" ?><strong>&nbsp;Copy file structure from most recent previous release</strong>
	</td>
</tr>
<?php
	}
?>

<tr>
	<td colspan="2" style="padding-top:15px;">
		<input type="submit" name="submitAndNotify" id="submitAndNotify" value="Add & Notify Followers" class="btn-cta" /> &nbsp;<input type="submit" name="submitNoNotify" id="submitNoNotify" value="Add & Do Not Notify Followers" class="btn-cta" />
	</td>
</tr>

</table>

</form>


<?php

frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
