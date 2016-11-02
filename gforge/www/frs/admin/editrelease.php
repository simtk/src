<?php
/**
 * Project Admin: Update Release in Package
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * http://fusionforge.org/
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
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
$package_id = getIntFromRequest('package_id');
$release_id = getIntFromRequest('release_id');
if (!$group_id) {
	exit_no_group();
}
if (!$package_id || !$release_id) {
	session_redirect('/frs/admin/?group_id='.$group_id);
}

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'frs');
}
session_require_perm ('frs', $group_id, 'write') ;

// Get package.
$frsp = new FRSPackage($group, $package_id);
if (!$frsp || !is_object($frsp)) {
	exit_error(_('Could Not Get FRS Package'),'frs');
}
elseif ($frsp->isError()) {
	exit_error($frsp->getErrorMessage(),'frs');
}

// Get release.
$frsr = new FRSRelease($frsp, $release_id);
if (!$frsr || !is_object($frsr)) {
	exit_error(_('Could Not Get FRS Release'),'frs');
}
elseif ($frsr->isError()) {
	exit_error($frsr->getErrorMessage(),'frs');
}

$upload_dir = forge_get_config('ftp_upload_dir') . "/" . $group->getUnixName();


// Update release info
if (getStringFromRequest('submit')) {
	$release_name = trim(getStringFromRequest('release_name'));
	$release_date = getStringFromRequest('release_date');
	$release_date = strtotime($release_date);
	$status_id = getIntFromRequest('status_id');
	$uploaded_notes = getUploadedFile('uploaded_notes');
	$uploaded_changes = getUploadedFile('uploaded_changes');
	$release_notes = getStringFromRequest('release_notes');
	$release_changes = getStringFromRequest('release_changes');
	$preformatted = getStringFromRequest('preformatted');
	$release_desc = getStringFromRequest('release_desc');
	$exec_changes = true;

	// Check for uploaded release notes
	if ($uploaded_notes["tmp_name"]) {
		if (!is_uploaded_file($uploaded_notes['tmp_name'])) {
			exit_error(_('Attempted File Upload Attack'), 'frs');
		}
		if ($uploaded_notes['type'] !== 'text/plain') {
			$error_msg .= _('Release Notes Are not in Text') . '<br/>';
			$exec_changes = false;
		} else {
			$notes = fread(fopen($uploaded_notes['tmp_name'], 'r'), $uploaded_notes['size']);
			if (strlen($notes) < 20) {
				$error_msg .= _('Release Notes Are Too Small') . '<br/>';
				$exec_changes = false;
			}
		}
	} else {
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
		} else {
			$changes = fread(fopen($uploaded_changes['tmp_name'], 'r'), $uploaded_changes['size']);
			if (strlen($changes) < 20) {
				$error_msg .= _('Change Log Is Too Small') . '<br/>';
				$exec_changes = false;
			}
		}
	} else {
		$changes = $release_changes;
	}

	// If we haven't encountered any problems so far then save the changes
	if ($exec_changes == true) {
		if (!$frsr->update($status_id, $release_name, $notes, $changes, $preformatted,
			$release_date, $release_desc)) {
			exit_error($frsr->getErrorMessage(),'frs');
		}
		else {
			$feedback .= "Updated Release.";

			// Refresh release object.
			$frsr = new FRSRelease($frsp, $release_id);
			if (!$frsr || !is_object($frsr)) {
				exit_error(_('Could Not Get FRS Release'),'frs');
			}
			elseif ($frsr->isError()) {
				exit_error($frsr->getErrorMessage(),'frs');
			}
		}
	}
}

frs_admin_header(array('title'=>'Update Release','group'=>$group_id));
?>

<style>
td {
	padding-top:5px;
	vertical-align:top;
}
</style>

<div><h4>Update Release <?php echo $frsr->getName(); ?> in Package <?php echo $frsp->getName(); ?></h4></div>

<form enctype="multipart/form-data" method="post" action="<?php echo getStringFromServer('PHP_SELF')."?group_id=$group_id&amp;release_id=$release_id&amp;package_id=$package_id"; ?>">

<span class="required_note">Required fields outlined in blue.</span>

<table>
<tr>
	<td><strong>Release Date:</strong></td>
	<td><input type="text" name="release_date" value="<?php echo date('Y-m-d H:i',$frsr->getReleaseDate()) ?>" size="16" maxlength="16" /></td>
</tr>
<tr>
	<td><strong>Release Name:&nbsp;</strong></td>
	<td><input type="text" class="required" name="release_name" value="<?php echo $frsr->getName(); ?>" required="required" pattern=".{3,}" title="<?php echo _('At least 3 characters') ?>"/></td>
</tr>
<tr>
	<td><strong><?php echo _('Status')._(':'); ?></strong></td>
	<td>
		<input type="radio" name="status_id" value="1" <?php if ($frsr->getStatus() === "1") echo "checked='checked'"; ?> ><label>Active</label></input>
<input type="radio" name="status_id" value="3" <?php if ($frsr->getStatus() !== "1") echo "checked='checked'"; ?> ><label>Hidden</label></input>
	</td>
</tr>
<tr>
	<td><strong>Description:</strong><br/></td>
	<td><textarea class='' style='margin-top:5px;' rows='6' cols='50' name='release_desc'><?php echo $frsr->getDesc(); ?></textarea></td>
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
		<?php echo('('._('max upload size: '.human_readable_bytes(util_get_maxuploadfilesize())).')') ?>
	</td>
</tr>
<tr>
	<td>Paste the Notes or import a <strong>TEXT file</strong></td>
	<td><input type="file" name="uploaded_notes" size="30" /></td>
</tr>
<tr>
	<td colspan="2"><textarea name="release_notes" rows="10" cols="60"><?php echo $frsr->getNotes(); ?></textarea></td>
</tr>

<tr>
	<td colspan="2">
		<strong><?php echo _('Upload Change Log')._(':'); ?></strong>
		<?php echo('('._('max upload size: '.human_readable_bytes(util_get_maxuploadfilesize())).')') ?>
	</td>
</tr>
<tr>
	<td>Paste the Log or import a <strong>TEXT file</strong></td>
	<td><input type="file" name="uploaded_changes" size="30" /></td>
</tr>
<tr>
	<td colspan="2"><textarea name="release_changes" rows="10" cols="60"><?php echo $frsr->getChanges(); ?></textarea></td>
</tr>

<tr>
	<td colspan="2">
		<input type="checkbox" name="preformatted" value="1" <?php echo (($frsr->getPreformatted())?'checked="checked"':''); ?> /> <?php echo _('Preserve my pre-formatted text') ?>
</tr>

<tr style="height:10px;">
	<td style="border-bottom:1px solid #ccc;"></td>
	<td style="border-bottom:1px solid #ccc;"></td>
</tr>

<tr>
	<td style="padding-top:10px;">
		<p><input type="submit" name="submit" value="<?php echo _('Submit/Refresh') ?>" class="btn-cta" /></p>
	</td>
</table>
</form>

<?php

frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
