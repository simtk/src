<?php
/**
 * Project Admin: Add file to a release.
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
require_once $gfwww . 'githubAccess/githubUtils.php';

define("MAX_GITHUB_FILESIZE", 150 * 1024 * 1024);

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
	exit_error($group->getErrorMessage(), 'frs');
}
session_require_perm ('frs', $group_id, 'write') ;

// Get package.
$frsp = new FRSPackage($group, $package_id);
if (!$frsp || !is_object($frsp)) {
	exit_error(_('Could Not Get FRS Package'), 'frs');
}
elseif ($frsp->isError()) {
	exit_error($frsp->getErrorMessage(), 'frs');
}

// Get release.
$frsr = new FRSRelease($frsp, $release_id);
if (!$frsr || !is_object($frsr)) {
	exit_error(_('Could Not Get FRS Release'), 'frs');
}
elseif ($frsr->isError()) {
	exit_error($frsr->getErrorMessage(), 'frs');
}

$upload_dir = forge_get_config('ftp_upload_dir') . "/" . $group->getUnixName();

?>
<script src='/js/jquery-1.11.2.min.js'></script>
<?php

// Add file to the release
if (getStringFromRequest('submit')) {
	$userfile = getUploadedFile('userfile');
	if (isset($userfile['name'])) {
		// Check for parameter presence before using.
		$userfile_name = $userfile['name'];
	}
	$type_id = getIntFromRequest('type_id');
	$release_date = getStringFromRequest('release_date');
	// Build a Unix time value from the supplied Y-m-d value
	$release_date = strtotime($release_date);
	$processor_id = getIntFromRequest('processor_id');
	$file_desc = getStringFromRequest('file_desc');
	$docType = getIntFromRequest('docType');
	$url = trim(getStringFromRequest('url'));
	$githubArchiveUrl = trim(getStringFromRequest('githubArchiveUrl'));
	$refreshArchive = getIntFromRequest('refreshArchive');
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
		
	$msgReleased = "You can now " .
		"<a href='javascript:sendnews();'>create a project news item</a>" .
		" to announce its release.";

	if ($docType == 2) {
		// URL.

		// Ensure that the GitHub archive URL is not used.
		$githubArchiveUrl = "";

		if ($url == "") {
			$error_msg .= 'Please enter a URL.';
		}
		else {
			if ($disp_name == "") {
				// Display Name is not present. Set to URL as default.
				$disp_name = $url;
			}
			$ret = frs_add_file_from_form($frsr, $type_id, $processor_id,
				$release_date, $userfile, false, false,
				$collect_info, $use_mail_list, $group_list_id,
				$show_notes, $show_agreement,
				$file_desc, $disp_name, $doi, $user_id, $url);
			if ($ret === true) {
				$feedback = '***NOSTRIPTAGS***URL is set up. ' . $msgReleased;
			}
			else {
				$error_msg .= $ret;
			}
		}
	}
	else if ($docType == 3) {
		// GitHub Archive URL.

		// Ensure that URL is not used.
		$url = "";

		if ($githubArchiveUrl == "") {
                        $error_msg .= 'Please enter a URL.';
                }
		else if (urlExistance($githubArchiveUrl) != 200) {
			// Do not proceed if GitHub archive URL is not valid.
                        $error_msg .= 'Please enter a valid URL.';
		}
		else if (($tmpFileSize = getGitHubFileSize($githubArchiveUrl)) === false ||
			$tmpFileSize > MAX_GITHUB_FILESIZE) {
			// GitHub archive file at URL is too large.
                        $error_msg .= 'Your GitHub file is too big ' .
				'(' . floor($tmpFileSize / 1024 / 1024) . 'MB)' .
				' to be added to SimTK';
		}
                else {
                        if ($disp_name == "") {
                                // Display Name is not present.
				// Set to last part of URL after "/" as default.
				$idx = strrpos($githubArchiveUrl, "/");
				if ($idx !== FALSE) {
					// Start from last "/".
					$disp_name = substr($githubArchiveUrl, $idx + 1);
				}
				else {
                                	$disp_name = $githubArchiveUrl;
				}
                        }
			// NOTE: The GitHub archive is retrieved from the link specified
			// and then treated as a file for download.
			// This archive file can be refreshed with the specified frequency.
                        $ret = frs_add_file_from_form($frsr, $type_id, $processor_id,
                                $release_date, $userfile, false, false,
                                $collect_info, $use_mail_list, $group_list_id,
                                $show_notes, $show_agreement,
                                $file_desc, $disp_name, $doi, $user_id, $url,
				$githubArchiveUrl, $refreshArchive);
                        if ($ret === true) {
                                $feedback = '***NOSTRIPTAGS***GitHub files are added and will be available in a few minutes.<br/>' . $msgReleased;
                        }
                        else {
                                $error_msg .= $ret;
                        }
                }
        }
	else {
		// Selected a file.

		if ($userfile_name == "") {
			$error_msg .= 'Please choose a file.';
		}
		else {
			$ret = frs_add_file_from_form($frsr, $type_id, $processor_id, 
				$release_date, $userfile, false, false, 
				$collect_info, $use_mail_list, $group_list_id, 
				$show_notes, $show_agreement,
				$file_desc, $disp_name, $doi, $user_id);
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
			       util_send_message("webmaster@simtk.org", sprintf(_('DOI for %s file Requested'), $disp_name), $message);
				   $feedback = _('Your file has been uploaded and your DOI will be emailed within 72 hours. ');
				} else {				
				   $feedback = '***NOSTRIPTAGS***File Released. ' . $msgReleased;
				}
?>
<script>
$(function() {
	// Show message for news creation.
	$(".feedback").after("<div style='text-align:center';><?php echo $msgReleased; ?></div>");
});
</script>
<?php
			}
			else {
				$error_msg .= $ret;
			}
		}
	}
}

frs_admin_header(array('title'=>'Add File','group'=>$group_id));

?>
<script>
function sendnews() {
	// News creation.
	groupId = <?php echo $group_id; ?>;
	groupName = "<?php echo $group->getPublicName(); ?>";
	groupNameU = "<?php echo $group->getUnixName(); ?>";
	rel_name = "<?php echo $frsr->getName(); ?>";
	pack_name = "<?php echo $frsp->getName(); ?>";

	// Note: The following variables may be undefined if back button is used.
	// Hence, check with isset() first before usage.
	file_name =
"<?php 
if (isset($disp_name)) {
	if ($disp_name == "")
		// Empty display name.
		if (isset($userfile_name)) {
			// Use filename.
			echo $userfile_name; 
		}
		else if (isset($url)) {
			// Should never be here.
			// For URL, the $disp_name is always set upon getting here.
			echo $url; 
		}
		else {
			// Should never be here.
			echo "";
		}
	else {
		// Has display name.
		echo $disp_name;
	}
}
?>";
	file_desc = "<?php if (isset($file_desc)) echo $file_desc; ?>";
	file_type = <?php if (isset($docType)) echo $docType; else echo 1; ?>;

	if (file_type == 2) {
		fType = "link";
	}
	else {
		fType = "file";
	}

	site = location.protocol + "//" + location.host
	full_sum = "New " + fType + " added: " + file_name;
	dlURL = site + "/frs?group_id=" + groupId;
	full_det = "A new " + fType + ", " + file_name + 
		", has been added to release " + rel_name + 
		" of " + pack_name + 
		". Visit " + dlURL + " to view the new " + 
		fType + ".";
	URL = site + "/plugins/simtk_news/submit.php?" +
		"group_id=" + groupId + 
		"&summary=" + full_sum + 
		"&details=" + full_det + 
		"&display_local=t";

	window.open(URL,"_new");
}

$(document).ready(function() {
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
	$('#githubLink').click(function() {
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
	$('#doi').change(function() {
		if (this.checked)
			//$('#doi_info').fadeIn('slow');
			$('#doi_info').show();
		else
			$('#doi_info').hide();
	});
	$("#submit").click(function() {
		if ($('#doi').is(":checked")) {
			if (!confirm("I confirm that I would like to have this file, its release, and the package it belongs to made permanent. Please issue a DOI.")) {
				event.preventDefault();
			}
		}
	});
   
});

</script>

<style>
td {
	padding-bottom:5px;
	vertical-align:top;
}
</style>


<div><h3>Add File/URL to Release <?php echo $frsr->getName(); ?></h3></div>

<form id="addfile" enctype="multipart/form-data" method="post" action="<?php echo getStringFromServer('PHP_SELF')."?group_id=$group_id&amp;release_id=$release_id&amp;package_id=$package_id"; ?>">
<input type="hidden" name="release_name" value="<?php echo $frsr->getName(); ?>" />

<table>

<tr>
	<td colspan="2"><h2>Select File/Link</h2></td>
</tr>

<tr>
	<td style="width:25%;padding-top:2px;"><input type="radio" id="docFile" name="docType" value="1" checked="checked" ><label for="docFile"><strong>Upload a File</strong></label></input>
	</td>
	<td>
	<table>
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
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" id="collect_info" name="collect_info" value="1" checked="checked" />&nbsp;Collect user information (requires user login).</td>
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
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" name="show_agreement" value="1" checked="checked" />&nbsp;Show download agreement</td>
	<td></td>
	</tr>
<?php
}
?>
	<tr>
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" name="show_notes" value="1" />&nbsp;Show download notes</td>
	<td></td>
	</tr>

	<tr>
	<td style="padding-left:30px;"><input class="upFile" type="checkbox" id="use_mail_list" name="use_mail_list" value="1" checked="checked" />&nbsp;Ask user to join mailing list:&nbsp;
	<?php print frs_show_mailinglist_popup($group_id, 'group_list_id'); ?></td>
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
	<td style="padding-top:2px;"><input type="radio" id="docLink" 
		name="docType" value="2" ><label 
		for="docLink"><strong>Create a Link</strong></label></input>
	</td>
	<td>
	<table>
	<tr>
		<td>Link URL:&nbsp;&nbsp;<input type="text" 
			id="linkurl" 
			size="45" 
			name="url" 
			value=""/>
		</td>
	</tr>
	</table>
	</td>
</tr>

<?php
if ($group->usesGitHub()) {
?>

<tr>
	<td style="padding-top:2px;"><input type="radio" id="githubLink" 
		name="docType" value="3" ><label 
		for="githubLink"><strong>Add All GitHub Files</strong></label></input>
	</td>
	<td>
	<table>
	<tr>
		<td>Link URL:&nbsp;&nbsp;<input type="text" 
			id="githubArchiveUrl" 
			name="githubArchiveUrl" 
			size="45" value="<?php
			$url = $group->getGitHubAccessURL();
			// Trim.
			$url = trim($url);
			if (strpos($url, "/") === 0) {
				// Remove leading "/" if any.
				$url = substr($url, 1);
			}
			if (strrpos($url, "/") === strlen($url) - 1) {
				// Remove trailing "/" if any.
				$url = substr($url, 0, strlen($url) - 1);
			}
			echo "http://github.com/" . $url . "/archive/master.zip";
		?>"/>
		</td>
	</tr>
	<tr>
		<td>(Default: Files from current release)
		</td>
	</tr>
	<tr>
		<td>Refresh frequency:&nbsp;&nbsp;
			<input type="radio" 
				name="refreshArchive" 
				value="0" checked><label for="">None</label>
			</input>&nbsp;&nbsp;
			<input type="radio" 
				name="refreshArchive" 
				value="1"><label for="">Daily</label>
			</input>&nbsp;&nbsp;
			<input type="radio" 
				name="refreshArchive" 
				value="7"><label for="">Weekly</label>
			</input>&nbsp;&nbsp;
		</td>
	</tr>
	</table>
	</td>
</tr>

<?php
}
?>

<tr>
	<td colspan="2"><h2>Provide File/Link Details</h2></td>
</tr>
<tr>
	<td>
	<div id="labelDispName">
	<strong>Rename File<br/>for display & download:<br/>(optional)</strong>
	</div>
	</td>
	<td><input type="text" id="disp_name" name="disp_name" value=""/>
	<div id="warnDispName">
	<strong>Note:</strong> Include file extension so file can be launched after download.
	<br/><strong>Restriction:</strong> File name must be at least 3 letters long and are limited to letters, numbers, spaces, dashes, underscores, and periods.
	</div>
	</td>
</tr>
<tr>
	<td><strong>Description:</strong></td>
	<td><textarea class='' style='margin-top:5px;' rows='5' cols='60' name='file_desc'></textarea></td>
</tr>
<tr>
	<td><strong>Type:</strong></td>
	<td><?php print frs_show_filetype_popup('type_id'); ?></td>
</tr>
<tr>
	<td></td>
	<td><strong>Note: </strong>A file or URL of type documentation will appear under a separate "Documentation" heading in the download section.</td>
</tr>
<tr>
	<td><strong>Platform:</strong></td>
	<td><?php print frs_show_processor_popup('processor_id'); ?></td>
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
	<td><input type="submit" id="submit" name="submit" value="<?php echo 'Add This File' ?>" class="btn-cta" /></td>
</tr>
</table>
</form>

<?php


frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
