<?php
/**
 * Project Admin: Update file in a release.
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
$file_id = getIntFromRequest('file_id');
$func = getStringFromRequest('func');

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

// Get file.
$frsf = new FRSFile($frsr, $file_id);
if (!$frsf || !is_object($frsf)) {
	exit_error(_('Could Not Get FRSFile'),'frs');
}
elseif ($frsf->isError()) {
	exit_error($frsf->getErrorMessage(),'frs');
}

$upload_dir = forge_get_config('ftp_upload_dir') . "/" . $group->getUnixName();

// Update file in release.
if (getStringFromRequest('submit') && $func=="edit_file" && $file_id) {
	// Build a Unix time value from the supplied Y-m-d value
	$release_date = getStringFromRequest('release_date');
	$release_date = strtotime($release_date);

	$userfile = getUploadedFile('userfile');
	$type_id = getIntFromRequest('type_id');
	$processor_id = getIntFromRequest('processor_id');
	$file_desc = getStringFromRequest('file_desc');
	$docType = getIntFromRequest('docType');
	$url = trim(getStringFromRequest('url'));
	$disp_name = trim(getStringFromRequest('disp_name'));
	$collect_info = getIntFromRequest('collect_info');
	$use_mail_list = getIntFromRequest('use_mail_list');
	$group_list_id = getIntFromRequest('group_list_id');
	$show_notes = getIntFromRequest('show_notes');
	$show_agreement = getIntFromRequest('show_agreement');
    $doi = getIntFromRequest('doi');
	
	if (empty($doi)) {
	   $doi = 0;
	}
	$doi_confirm = 0;
	
	// get user
    $user = session_get_user(); // get the session user
    $user_id = $user->getID();
	
	if ($docType == 2) {
		// URL

		if ($url == "") {
                        $error_msg .= 'Please enter a URL.';
                }
		else {
			if ($disp_name == "") {
				// Display Name is not present. Set to URL as default.
				$disp_name = $url;
			}
			$ret = $frsf->update($type_id, $processor_id, $release_date, $release_id, 
				$collect_info, $use_mail_list, $group_list_id, $userfile, 
				$show_notes, $show_agreement,
				$file_desc, $disp_name, $doi, $user_id, $url);
			if ($ret === false) {
				// Return the error message.
				$error_msg = $frsf->getErrorMessage();
			}
		}
	}
	else {
		// Selected a file.

		$ret = $frsf->update($type_id, $processor_id, $release_date, $release_id,
			$collect_info, $use_mail_list, $group_list_id, $userfile, 
			$show_notes, $show_agreement,
			$file_desc, $disp_name, $doi, $user_id);
		if ($ret === false) {
			// Return the error message.
			$error_msg = $frsf->getErrorMessage();
		}
	}

	if ($ret === true) {
	    if ($doi) {
           // set doi for release
		   $frsr->setDoi($doi);
		   // set doi for package
		   $frsp->setDoi($doi);
		   $doi_confirm = 1;
		   $message = "\n"
				. _('Please visit the following URL to assign DOI')._(': '). "\n"
				. util_make_url('/admin/downloads-doi.php');
		   util_send_message("webmaster@simtk.org", sprintf(_('DOI for %s File Requested'), $disp_name), $message);
		   $feedback = _('Your file has been uploaded and your DOI will be emailed within 72 hours. ');
		} else {
		   $feedback = 'File/Link Updated';
        }
		
		// Refresh file object.
		$frsf = new FRSFile($frsr, $file_id);
		if (!$frsf || !is_object($frsf)) {
			exit_error(_('Could Not Get FRSFile'),'frs');
		}
		elseif ($frsf->isError()) {
			exit_error($frsf->getErrorMessage(),'frs');
		}
	}
	else {
		$error_msg .= $ret ;
	}
}

frs_admin_header(array('title'=>'Update File','group'=>$group_id));
?>

<script>
	$(document).ready(function() {
		if ($('#docLink').is(":checked")) {
			// Disable inputs for File upload.
			$('.upFile').prop("disabled", true);
			$('[name="group_list_id"]').prop("disabled", true);
			$('#doi').attr('checked', false);
		    $('#doi_info').hide();
		    $('#doi').prop("disabled", true);
		}
		else {
			// Enable inputs for File upload.
			$('.upFile').prop("disabled", false);
			$('[name="group_list_id"]').prop("disabled", false);
			$('#doi').prop("disabled", false);
		}
		$('#docFile').click(function() {
			// Show the Display Name warning.
			$('#warnDispName').show("slow");
			$('#labelDispName').html("<strong>Rename File<br/>for display & download:<br/>(optional)</strong>");

			// Disable inputs for File upload.
			$('.upFile').prop("disabled", false);
			$('[name="group_list_id"]').prop("disabled", false);
			$('#doi').prop("disabled", false);
		});
		$('#docLink').click(function() {
			// Hide the Display Name warning.
			$('#warnDispName').hide("slow");
			$('#labelDispName').html("<strong>Display Name:</strong>");

			// Enable inputs for File upload.
			$('.upFile').prop("disabled", true);
			$('#doi').attr('checked', false);
		    $('#doi_info').hide();
		    $('#doi').prop("disabled", true);
			$('[name="group_list_id"]').prop("disabled", true);
		});

		$('#collect_info').click(function() {
			var theValue = $('#collect_info').is(":checked");
			if (theValue == 0) {
				$('#use_mail_list').prop('checked', 0);
				$('#use_mail_list').prop('disabled', true);
				$('[name="group_list_id"]').prop("disabled", true);
			}
			else {
				$('#use_mail_list').prop('disabled', false);
				$('[name="group_list_id"]').prop("disabled", false);
			}
		});
		if (!$('#collect_info').is(":checked")) {
			$('#use_mail_list').prop('checked', 0);
			$('#use_mail_list').prop('disabled', true);
			$('[name="group_list_id"]').prop("disabled", true);
		}
		$('#doi').change(function(){
            if(this.checked)
               //$('#doi_info').fadeIn('slow');
			   $('#doi_info').show();
		    else
		       $('#doi_info').hide();
        });
		$("#submit").click(function(){
	       if ($('#doi').is(":checked")) {
              if (!confirm("I confirm that I would like to have this file, its release, and the package it belongs to made permanent. Please issue a DOI.")){
                event.preventDefault();
              }
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

<?php if (($doi) && ($doi_confirm)) { ?>

  <?php echo "File: " . $disp_name . "<br />"; ?>
  <?php echo "(Release: " . $frsr->getName() . ")<br /><br />"; ?>
  <p>This file is being assigned a DOI and can no longer be edited or deleted.</p>
  <script type="text/javascript">    
    $(document).ready(function() {
     window.history.forward(1);
	});
  </script>
		
<?php } else { ?>

<div><h4>Update: <?php echo $frsf->getName(); ?><p/>(RELEASE: <?php echo $frsr->getName(); ?>)</h4></div>

<form id="addfile" enctype="multipart/form-data" action="editfile.php" method="post">

<input type="hidden" name="func" value="edit_file" />
<input type="hidden" name="package_id" value="<?php echo $package_id; ?>" />
<input type="hidden" name="release_id" value="<?php echo $release_id; ?>" />
<input type="hidden" name="file_id" value="<?php echo $file_id; ?>" />
<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />

<table>

<tr>
	<td colspan="2"><h2>Select File/Link</h2></td>
</tr>

<tr>
	<td style="width:20%;"><input type="radio" id="docFile" name="docType" value="1" <?php if ($frsf->isURL() === false) echo "checked='checked'"; ?> ><label for="docFile"><strong>Upload a File</strong></label></input></td>
	<td>
	<table id="tableFile">

	<tr>
		<td>New File:
		<?php echo '&nbsp;(max upload size: ' . 
			human_readable_bytes(util_get_maxuploadfilesize()) . 
			')'; ?>
		</td>
	</tr>
	<tr>
		<td style="padding-left:30px;">
		<input class="upFile" type="file" id="newfile" name="userfile"  size="30" />
		</td>
	</tr>
	<tr>
		<td>Download Options:</td>
	</tr>

	<tr>
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" id="collect_info" name="collect_info" value="1" <?php if ($frsf->getCollectData() === "1") echo "checked='checked'"; ?> />&nbsp;Collect user information (requires user login).</td>
	</td>
	<td></td>
	</tr>
	<tr>
		<td style="padding-left:30px;">
			-->Please keep above <strong>checked</strong> for non documentation files.
		</td>
	</tr>

<?php
// Only show agreement if the package has one; i.e. the value is not 0 ("None".)
if ($frsp->getUseAgreement() != 0) {
?>
	<tr>
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" name="show_agreement" value="1" <?php if ($frsf->getShowAgreement() === "1") echo "checked='checked'"; ?> />&nbsp;Show download agreement</td>
	<td></td>
	</tr>
<?php
}
?>
	<tr>
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" name="show_notes" value="1" <?php if ($frsf->getShowNotes() === "1") echo "checked='checked'"; ?> />&nbsp;Show download notes</td>
	<td></td>
	</tr>

	<tr>
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" id="use_mail_list" name="use_mail_list" value="1" <?php if ($frsf->getUseMailList() === "1") echo "checked='checked'"; ?> />&nbsp;Ask user to join mailing list:&nbsp;
	<?php print frs_show_mailinglist_popup($group_id, 'group_list_id', $frsf->getGroupListId()); ?></td>
	</tr>
	<tr>
		<td style="padding-left:30px;">
			-->"Collect user information" must be checked to use mailing lists.
		</td>
	</tr>

	</table>
	</td>
</tr>

<tr style="height:15px;">
<td></td>
<td></td>
</tr>

<tr>
	<td><input type="radio" id="docLink" name="docType" value="2" <?php if ($frsf->isURL() === true) echo "checked='checked'"; ?> ><label for="docLink"><strong>Create a Link</strong></label></input></td>
	<td>
	<table>
		<tr>
			<td>Link URL:&nbsp;&nbsp;</td>
			<td><input type="text" id="linkurl" name="url" value="<?php echo $frsf->getURL(); ?>"/></td>
		</tr>
	</table>
	</td>
</tr>

<tr>
	<td colspan="2"><h2>Provide File/Link Details</h2></td>
</tr>

<tr>
	<td>
	<div id="labelDispName">
<?php
	if ($frsf->isURL() === false) {
		// File.
?>
		<strong>Rename File<br/>for display & download:<br/>(optional)</strong>
<?php	}
	else {
		// URL
?>
		<strong>Display Name:</strong>
<?php
	}
?>
	</div>
	</td>
	<td><input type="text" id="disp_name" name="disp_name" value="<?php echo $frsf->getName(); ?>"/>
	<div id="warnDispName"
<?php
	if ($frsf->isURL() === true) {
		// URL.
		// Initialize DIV for "File", but do not display the DIV,
		// hence allowing DIV Show/Hide toggle chosen with radio button.
?>
		style="display: none;"
<?php
	}
?>
	>
	<strong>Note:</strong> Include file extension so file can be launched after download.
	<br/><strong>Restriction:</strong> File name must be at least 3 letters long and are limited to letters, numbers, spaces, dashes, underscores, and periods.
	</div>
	</td>
</tr>
<tr>
	<td><strong>Description:</strong></td>
	<td><textarea class='' style='margin-top:5px;' rows='5' cols='60' name='file_desc'><?php echo $frsf->getDesc(); ?></textarea></td>
</tr>
<tr>
	<td><strong>Type:</strong></td>
	<td><?php print frs_show_filetype_popup('type_id', $frsf->getTypeID()); ?></td>
</tr>
<tr>
	<td></td>
	<td><strong>Note: </strong>A file or URL of type documentation will appear under a separate "Documentation" heading in the download section.</td>
</tr>
<tr>
	<td><strong>Platform:</strong></td>
	<td><?php print frs_show_processor_popup('processor_id', $frsf->getProcessorID()); ?></td>
</tr>
<tr>
	<td><strong>Release Date:</strong></td>
	<td><input type="text" name="release_date" value="<?php echo date('Y-m-d', $frsf->getReleaseTime()); ?>" size="10" maxlength="10" /></td>
</tr>
<tr>
	<td><strong>DOI:</strong></td>
	<td><input type="checkbox" name="doi" id="doi" value="1" />&nbsp;Obtain a DOI for file
	<div id="doi_info" style="display:none">
        <font color="#ff0000">Warning: You will not be able to remove or edit this file after the DOI has been issued.  You will not be able to delete the release or the package it belongs to either.</font>
    </div>
	</td>
</tr>
<tr>
	<td><input type="submit" name="submit" id="submit" value="<?php echo _('Update/Refresh') ?> " class="btn-cta" /></td>
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
