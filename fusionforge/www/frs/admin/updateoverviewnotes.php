<?php
/**
 * Project Admin: Update download overview and notes.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * http://fusionforge.org/
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once 'frs_admin_data_util.php';

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'frs');
}

// Get the download overview and notes info.
$theGroupInfo = getFrsGroupInfo($group_id);

session_require_perm ('frs', $group_id, 'write') ;

// Update release info
if (getStringFromRequest('submit')) {
	$notes = getStringFromRequest('notes');
	$uploaded_notes = getUploadedFile('uploaded_notes');
	$preformatted = getStringFromRequest('preformatted');
	$overview = getStringFromRequest('overview');
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
		$notes = $notes;
	}

	// If we haven't encountered any problems so far then save the changes
	if ($exec_changes == true) {
		if (!$group->updateOverviewNotes(session_get_user(), $notes, $preformatted, $overview)) {
			exit_error($group->getErrorMessage(), 'frs');
		}
		else {
			$feedback .= "Updated overview and notes.";

			// Get the download overview and notes info.
			$theGroupInfo = getFrsGroupInfo($group_id);
		}
	}
}

frs_admin_header(array('title'=>'Update Overview/Notes','group'=>$group_id));
?>


<form enctype="multipart/form-data" method="post" action="<?php echo "updateoverviewnotes.php?group_id=$group_id"; ?>">

<h3>Downloads Page Overview</h3>
<div class="download_package" style="margin-bottom:20px;">
Help people navigate your Downloads page by describing how you organized it. This description will appear at the top of your project's Downloads page followed by a link to the Downloads General Instructions, if provided.
<h4>Samples:</h4>
<ol>
<li>1) Data and software are provided below to generate blood flow simulations through a geometric model that's constructed from MRI data. Download the packages (and follow the associated directions for them) in the order listed below.</li>
<li>2) The first package contains a script for downloading and viewing the molecular trajectories. The remaining packages each contain one of the starting PDB structures for the trajectories.</li>
</ol>
</div>
<table>
<th>Your downloads overview:</th>
<tr>
	<td colspan="2"><textarea class='' style='margin-top:5px;' rows='5' cols='60' name='overview'><?php echo $theGroupInfo["download_overview"]; ?></textarea></td>
</tr>
</table>


<h3>Downloads General Instructions</h3>
<div class="download_package" style="margin-bottom:20px;">
Provide general directions on using the downloads provided. These are especially useful for publication projects or projects with multiple packages. This information will appear via a link at the top of your project's Downloads page. You can upload a file or copy and paste the text below.
<h4>Example:</h4>
<ol>
<li>1. Download and install software application from SimVascular package.</li>
<li>2. Download MRI data from MRA DataSet 1 package.</li>
<li>3. Read in MRI data into SimVascular and construct the geometric model.</li>
</ol>
</div>
<table>
<th>Your general instructions:</th>
</tr>
<tr>
	<td>Paste the text or import a <strong>TEXT file<strong></td>
	<td><input type="file" name="uploaded_notes" size="30" /></td>
</tr>
<tr>
	<td colspan="2">
		<textarea name="notes" rows="10" cols="60"><?php echo $theGroupInfo["download_notes"]; ?></textarea>
	</td>
</tr>
<tr>
	<td colspan="2">
		<input type="checkbox" name="preformatted" value="1" <?php echo (($theGroupInfo['preformatted'] == '1')?'checked="checked"':''); ?> /> <?php echo _('Preserve my pre-formatted text') ?>
	</td>
</tr>
<tr>
	<td>
		<input type="submit" name="submit" value="<?php echo _('Submit/Refresh') ?>" class="btn-cta" />
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
