<?php
/**
 *
 * Project Admin: Create a package
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * http://fusionforge.org/
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
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
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
	exit_error($group->getErrorMessage(), 'frs');
}

session_require_perm('frs', $group_id, 'write');

// Relatively simple form to create a package
// only admin can add a package
if (getStringFromRequest('submit')) {
	// Create package info.
	$packageName = trim(getStringFromRequest('package_name'));
	$packageDesc = trim(getStringFromRequest('package_desc'));
	$packageNotes = trim(getStringFromRequest('package_notes'));
	$packageCustomAgreement = trim(getStringFromRequest('license_preview'));
	$packageUseAgreement = getIntFromRequest('use_agreement');
	$packageIsPublic = getIntFromRequest('is_public');
	$packageStatus = getIntFromRequest('status_id');
	$packageOpenLatest = getIntFromRequest('openlatest');
	$packageShowDownloadButton = getIntFromRequest('showDownloadButton');
	$packageLogo = getUploadedFile('simtk_logo_file');
	$packageLogoFileName = "";
	$packageLogoFileType = "";

	// Check for uploaded logo
	$exec_changes = true;
	if ($packageUseAgreement !== 0 &&
		stripos($packageCustomAgreement,
		"[Insert Year(s)], [Insert organization or names of copyright holder(s)]") !== FALSE) {
		$exec_changes = false;
		$warning_msg = "Please insert organization or names of copyright holder(s) in license agreement";
	}
	if ($packageLogo["tmp_name"]) {
		if (!is_uploaded_file($packageLogo['tmp_name'])) {
			exit_error(_('Attempted File Upload Attack'),'frs');
		}
		$theLogo = fread(fopen($packageLogo['tmp_name'], 'r'), $packageLogo['size']);
		if ($theLogo === false) {
			$exec_changes = false;
		}
		else {
			// Has file input.
			$packageLogoFileName = $packageLogo['tmp_name'];
			$packageLogoFileType = $packageLogo['type'];
		}
	}
	if ($exec_changes === true && $packageName) {
		// Create a new package with specified parameters.
		$frsp = new FRSPackage($group);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'), 'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		if (!$frsp->create($packageName, 
			$packageStatus,
			$packageIsPublic,
			$packageDesc,
			$packageLogoFileName,
			$packageLogoFileType,
			$packageNotes,
			$packageCustomAgreement,
			$packageUseAgreement,
			$packageOpenLatest,
			$packageShowDownloadButton)) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		else {
			$feedback .= _('Added Package');

			session_redirect('/frs/admin/createrelease.php?group_id=' . $group_id .
				'&package_id=' . $frsp->getID());
		}
	}
}

frs_admin_header(array('title'=>'Create a package', 'group'=>$group_id));

// Note: Need to use enctype="multipart/form-data" in the form below in order for
// file input to work.

?>

<style>
td {
	padding-top:5px;
	vertical-align:top;
}
</style>

<script src='/frs/download.js'></script>
<link rel='stylesheet' href='/frs/download.css' type='text/css' />
<script src='/frs/admin/preload_license.js'></script>
<script src='/frs/admin/license.js'></script>

<script>
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
	});
</script>

<div><h3>Create a package</h3></div>

<fieldset>
<form enctype="multipart/form-data" action="/frs/admin/createpackage.php" method="POST">

<table>
<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
<input type="hidden" name="func" value="add_package" />

<tr>
<td>
	<strong>Name:</strong>
</td>
<td>
	<input type="text" name="package_name" value="" size="20" maxlength="30" required="required" pattern=".{3,}" title="At least 3 characters" />
</td>
</tr>

<tr>
<td>
	<strong><?php echo _('Publicly Viewable')._(':'); ?></strong>
</td>
<td>
	<input type="radio" name="is_public" value="1" checked="checked" ><label>Public</label></input>
	<input type="radio" name="is_public" value="0" ><label>Private</label></input>
</td>
</tr>

<tr>
<td>
	<strong>Status:</strong>
</td>
<td>
	<input type="radio" name="status_id" value="1" checked="checked" ><label>Active</label></input>
	<input type="radio" name="status_id" value="3" ><label>Hidden</label></input>
</td>
</tr>

<tr>
<td>
	<strong>Logo:</strong>
</td>
<td>
	<div class="module_your_profile_picture">
		<div class="submodule_picture">
			<div class="picture_wrapper"><img id="logo_preview" src="" alt="package logo"/></div>
			<input type="file" name="simtk_logo_file" id="simtk_logo_file" size="30" onchange="previewImage('simtk_logo_file', 'logo_preview');"/>
		</div>
	</div>
</td>
</tr>

<tr>
<td>
</td>
<td>
	<input type="checkbox" name="openlatest" value="1" />
	<strong> Open the latest release in this package</strong>
</td>
</tr>

<tr>
<td>
</td>
<td>
	<input type="checkbox" name="showDownloadButton" value="1" checked />
	<strong> Show the Download Package button</strong>
</td>
</tr>

<tr>
<td>
	<strong>Description:</strong><br/>
</td>
<td>
	<textarea class='' style='margin-top:5px;' rows='6' cols='50' name='package_desc'></textarea>
</td>
</tr>

<tr>
<td>
	<strong>Download notes:</strong><br/>(Optional)
	<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Display some notes to users before they download a file in this package. This feature can be activated for a file when it is added to the package.">?</a></span>
</td>
<td>
	<textarea class='' style='margin-top:5px;' rows='6' cols='50' name='package_notes' title='Notes displayed to users before they download a file in this package.'></textarea>
</td>
</tr>

<tr>
<td>
	<strong>License agreement:</strong><br/>(Optional)
	<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="You can display a download agreement to users before they download any file in this package. The option to show this agreement can be removed for a given file when that file is added to the package.<br/><br/>

For code, we recommend that you add a license agreement as a comment header in every source file. This is in addition to or instead of this download agreement. A summary of each license is provided, but you should consult the license itself for the exact terms that apply.">?</a></span>
</td>
<td>
	<ul>
		<strong><label>Open Source Licenses</label></strong>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="
* Allows others to use your data/documentation/software for any purpose, commercial or non-commercial, make modifications, and redistribute it.<br/>
* Includes a disclaimer of warranty.<br/>
* Users are obligated to include your license terms if they redistribute. See <a href='http://www.opensource.org' target='_blank'>http://www.opensource.org</a> and <a href='http://en.wikipedia.org/wiki/Comparison_of_free_software_licenses' target='_blank'>Wikipedia</a> for more information.<br/>
* SimTK provides some common open-source licenses to choose from.  Many other options exist. See <a href='https://spdx.org/licenses/' target='_blank'>list of other open-source licenses</a>.<br/><br/>

These licenses differ in the additional obligations they place on the users.
">?</a></span>

	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="2" checked="checked" ><label>MIT</label>
		</input>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Default license. No additional obligations.">?</a></span>
	</li>

	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="3" ><label>LGPL</label>
		</input>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Modifications that are redistributed must include the modified source under the same terms.">?</a></span>
	</li>

	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="4" ><label>GPL</label>
		</input>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Any distributed work that includes all or part of GPL-licensed material must itself be offered under GPL, meaning that all the source code is available.">?</a></span>
	</li>

	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="6" ><label>CC BY 4.0</label>
		</input>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Others can share and adapt the file(s) for any purpose, even commercially, but they must give proper attribution.  Similar to MIT license but applies to works beyond just software and related documentation.  Also, it provides more terms and conditions.">?</a></span>
	</li>
	
	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="7" ><label>Apache 2.0</label>
		</input>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Similar to MIT license. Some major differences: 1) Apache 2.0 offers more explicit patent protection and 2) it also requires listing all modifications to original software.">?</a></span>
	</li>
	
		<strong><label>Other licenses</label></strong>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content='Many other licenses can be used. See <a href="http://en.wikipedia.org/wiki/Comparison_of_free_software_licenses" target="_blank">Wikipedia</a> and <a href="http://creativecommons.org/licenses" target="_blank">Creative Commons</a>. For complex licenses, we recommend that you enter a URL for the license, e.g., "The [project name] license agreement can be read here: http://XXX."'>?</a></span>

	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="5" ><label>Creative Commons Attribution-Non-Commercial</label>
		</input>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Similar to open-source licenses except your work can only be used for non-commercial purposes.">?</a></span>
	</li>

	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="1" ><label>Custom</label>
		</input>
		<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Specify a custom license.">?</a></span>
	</li>

	<li>
		<input type="radio" name="use_agreement" class="use_agreement" value="0" ><label>None</label>
		</input>
	</li>
	</ul>
</td>
</tr>

<tr>
<td>
</td>
<td style="width:500px;">
	<span class="edit_notice" style="color:#f75236;">Update first line of license with (1) copyright year and (2) organization or copyright holder</span>
</td>
</tr>

<tr>
<td>
</td>
<td>
	<textarea class='license_preview' style='margin-top:5px;' rows='10' cols='50' name='license_preview' title='Preview license'></textarea>
</td>
</tr>

<tr>
<td>
	<input type="submit" name="submit" value="<?php echo 'Create This Package' ?>" class="btn-cta" />
</td>
<td></td>
</tr>

</table>

</form>
</fieldset>

<?php

frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
