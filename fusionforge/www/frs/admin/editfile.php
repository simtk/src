<?php
/**
 * Project Admin: Update file in a release.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * http://fusionforge.org/
 * Copyright 2016-2022, Henry Kwong, Tod Hing - SimTK Team
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
require_once $gfcommon . 'include/githubUtils.php';

defined('MAX_GITHUB_FILESIZE') or define('MAX_GITHUB_FILESIZE', 250 * 1024 * 1024);

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
$submitAndNotify = getStringFromRequest('submitAndNotify');
$submitNoNotify = getStringFromRequest('submitNoNotify');
if (($submitAndNotify || $submitNoNotify) && 
	$func=="edit_file" && $file_id) {
	// Build a Unix time value from the supplied Y-m-d value
	$release_date = getStringFromRequest('release_date');
	$release_date = strtotime($release_date);

	$userfile = getUploadedFile('userfile');
	$type_id = getIntFromRequest('type_id');
	$processor_id = getIntFromRequest('processor_id');
	$file_desc = htmlspecialchars(getStringFromRequest('file_desc'));
	$docType = getIntFromRequest('docType');
	$url = htmlspecialchars(trim(getStringFromRequest('url')));
	$githubArchiveUrl = htmlspecialchars(trim(getStringFromRequest('githubArchiveUrl')));
	$refreshArchive = getIntFromRequest('refreshArchive');
	$disp_name = htmlspecialchars(trim(getStringFromRequest('disp_name')));
	$collect_info = getIntFromRequest('collect_info');
	$use_mail_list = getIntFromRequest('use_mail_list');
	$group_list_id = getIntFromRequest('group_list_id');
	$show_notes = getIntFromRequest('show_notes');
	$show_agreement = getIntFromRequest('show_agreement');
	$doi = getIntFromRequest('doi');
	if ($submitAndNotify) {
		$emailChange = 1;
	}
	else {
		$emailChange = 0;
	}
	
	if (empty($doi)) {
	   $doi = 0;
	}
	$doi_confirm = 0;
	
	// get user
	$user = session_get_user(); // get the session user
	$user_id = $user->getID();

	$ret = false;
	$error_msg = "";
	$isDocTypeGitHubArchive = false;
	if ($docType == 2) {
		// URL

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
			$ret = $frsf->update($type_id, $processor_id, $release_date, $release_id, 
				$collect_info, $use_mail_list, $group_list_id, $userfile, 
				$show_notes, $show_agreement,
				$file_desc, $disp_name, $doi, $user_id, $url, "", 0, $emailChange);
			if ($ret === false) {
				// Return the error message.
				$error_msg = $frsf->getErrorMessage();
			}
		}
	}
	else if ($docType == 3) {
		// GitHub Archive URL.

		$isDocTypeGitHubArchive = true;

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
			$ret = $frsf->update($type_id, $processor_id, $release_date, $release_id, 
				$collect_info, $use_mail_list, $group_list_id, $userfile, 
				$show_notes, $show_agreement,
				$file_desc, $disp_name, $doi, $user_id, $url,
				$githubArchiveUrl, $refreshArchive, $emailChange);
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
			$file_desc, $disp_name, $doi, $user_id, "", 
			"", 0, $emailChange);
		if ($ret === false) {
			// Return the error message.
			$error_msg = $frsf->getErrorMessage();
		}
	}

	if ($ret === true) {
		if ($doi) {
			$doi_confirm = 1;
			$real_name = $user->getRealName();
			$message = "\nPlease visit the following URL to assign DOI: \n" . 
				util_make_url('/admin/downloads-doi.php');
			util_send_message("webmaster@simtk.org", 
				sprintf('DOI for %s file requested by %s', $disp_name, $real_name), 
				$message);

			$feedback = 'Your file has been uploaded and your DOI will be emailed within 72 hours. ';
		}
		else {
			if ($isDocTypeGitHubArchive === true) {
				$feedback = 'GitHub files updated.';
			}
			else {
				$feedback = 'File/Link Updated.';
			}
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
		if ($ret !== false) {
			$error_msg .= $ret ;
		}
	}
}

frs_admin_header(array('title'=>'Update File','group'=>$group_id));

?>

<div class="du_warning_msg"></div>
<script src='/frs/admin/handlerDiskUsage.js'></script>

<script>
	$(document).ready(function() {
		// Add div for display of percent completion.
		$(".project_menu_row").after('<div id="percentCompleted"></div>');

		// Handle popover show and hide.
		$(".myPopOver").hover(function() {
			$(this).find(".popoverLic").popover("show");
		});

		$(".myPopOver").mouseleave(function() {
			$(this).find(".popoverLic").popover("hide");
		});

		if ($('#docLink').is(":checked")) {
			// Disable inputs for File upload.
			$('.upFile').prop("disabled", true);
			$('[name="group_list_id"]').prop("disabled", true);
			$('#doi').prop('checked', false);
			$('#doi_info').hide();
			$('#doi').prop("disabled", true);
			$('.downloadFileOptions').hide("slow");
		}
		else {
			// Enable inputs for File upload.
			$('.upFile').prop("disabled", false);
			$('[name="group_list_id"]').prop("disabled", false);
			$('#doi').prop("disabled", false);
			$('.downloadFileOptions').show("slow");
		}

		if ($('#docLink').is(":checked") || 
			$('#githubLink').is(":checked")) {
			$('.cellDoi').hide("slow");
		}
		else {
			$('.cellDoi').show("slow");
		}

		$('#docFile').click(function() {
			// Show the Display Name warning.
			$('#warnDispName').show("slow");
			$('#labelDispName').html("<strong>Rename File<br/>for display & download:<br/>(optional)</strong>");

			// Enable inputs for File upload.
			$('.downloadFileOptions').show("slow");
			$('.cellDoi').show("slow");
			$('.upFile').prop("disabled", false);
			$('[name="group_list_id"]').prop("disabled", false);
			$('#doi').prop("disabled", false);
<?php
if ($frsf->getShowAgreement() === "1") {
	echo '
		$("#showAgreement").prop("checked", true);
	';
}
if ($frsf->getCollectData() === "1") {
	echo '
		$("#collectInfo").prop("checked", true);
	';
}
if ($frsf->getUseMailList() === "1") {
	echo '
		$("#useMailList").prop("checked", true);
	';
}
if ($frsf->getShowNotes() === "1") {
	echo '
		$("#showNotes").prop("checked", true);
	';
}
?>
		});

		$('#docLink').click(function() {
			// Hide the Display Name warning.
			$('#warnDispName').hide("slow");
			$('#labelDispName').html("<strong>Display Name:</strong>");

			// Disable inputs for File upload.
			$('.upFile').prop("disabled", true);
			$('#doi').prop('checked', false);
			$('#doi_info').hide();
			$('#doi').prop("disabled", true);
			$('[name="group_list_id"]').prop("disabled", true);
			$('.upFile').prop('checked', false);
			$('.downloadFileOptions').hide("slow");
			$('.cellDoi').hide("slow");
		});

		$('#githubLink').click(function() {
			// Show the Display Name warning.
			$('#warnDispName').show("slow");
			$('#labelDispName').html("<strong>Rename File<br/>for display & download:<br/>(optional)</strong>");

			// Enable inputs for File upload.
			$('.downloadFileOptions').show("slow");
			$('.cellDoi').hide("slow");
			$('.upFile').prop("disabled", false);
			$('[name="group_list_id"]').prop("disabled", false);
			$('#doi').prop('checked', false);
			$('#doi_info').hide();
			$('#doi').prop("disabled", true);
<?php
if ($frsf->getShowAgreement() === "1") {
	echo '
		$("#showAgreement").prop("checked", true);
	';
}
if ($frsf->getCollectData() === "1") {
	echo '
		$("#collectInfo").prop("checked", true);
	';
}
if ($frsf->getUseMailList() === "1") {
	echo '
		$("#useMailList").prop("checked", true);
	';
}
if ($frsf->getShowNotes() === "1") {
	echo '
		$("#showNotes").prop("checked", true);
	';
}
?>
		});

		$('#collectInfo').click(function() {
			var theValue = $('#collectInfo').is(":checked");
			if (theValue == 0) {
				$('#useMailList').prop('checked', 0);
				$('#useMailList').prop('disabled', true);
				$('[name="group_list_id"]').prop("disabled", true);
			}
			else {
				$('#useMailList').prop('disabled', false);
				$('[name="group_list_id"]').prop("disabled", false);
			}
		});

		if (!$('#collectInfo').is(":checked")) {
			$('#useMailList').prop('checked', 0);
			$('#useMailList').prop('disabled', true);
			$('[name="group_list_id"]').prop("disabled", true);
		}

		$('#doi').change(function() {
			if (this.checked)
				//$('#doi_info').fadeIn('slow');
				$('#doi_info').show();
			else
				$('#doi_info').hide();
		});

		$("#submitAndNotify").click(function() {

			// Check disk usage.
			if (!handlerDiskUsage(<?php echo $group_id; ?>)) {
				// Disk usage exceeded quota. Do not proceed.
				event.preventDefault();
				return;
			}

			if ($('#doi').is(":checked")) {
				if (!confirm("I confirm that I would like to have this file, its release, and the package it belongs to made permanent. Please issue a DOI.")) {
					event.preventDefault();
				}
			}
			$(this).prop("value", "Updating File...");

			// Query for upload progress.
			$(".warning_msg").hide();
			if ($('#docFile').is(":checked")) {
				$("#percentCompleted").html('<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;"><div style="float:left;">' + '<b>Uploading file. Please wait: Do not navigate away from this page until the upload is complete.</b>' + '</div><div style="float:right;" onclick="$(\'.warning_msg\').hide(\'slow\');">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div><div style="clear: both;"></div></div>');
				$("#percentCompleted")[0].scrollIntoView(false);

				// Monitor upload progress.
				// Use timestamp as token.
				getUploadProgress($("#uploadProgress").val());
			}
			else if ($('#githubLink').is(":checked")) {
				$("#percentCompleted").html('<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;"><div style="float:left;">' + '<b>Retrieving from GitHub. Please wait: Do not navigate away from this page until the upload is complete.</b>' + '</div><div style="float:right;" onclick="$(\'.warning_msg\').hide(\'slow\');">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div><div style="clear: both;"></div></div>');
				$("#percentCompleted")[0].scrollIntoView(false);
			}
		});

		$("#submitNoNotify").click(function() {

			// Check disk usage.
			if (!handlerDiskUsage(<?php echo $group_id; ?>)) {
				// Disk usage exceeded quota. Do not proceed.
				event.preventDefault();
				return;
			}

			if ($('#doi').is(":checked")) {
				if (!confirm("I confirm that I would like to have this file, its release, and the package it belongs to made permanent. Please issue a DOI.")) {
					event.preventDefault();
				}
			}
			$(this).prop("value", "Updating File...");

			// Query for upload progress.
			$(".warning_msg").hide();
			if ($('#docFile').is(":checked")) {
				$("#percentCompleted").html('<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;"><div style="float:left;">' + '<b>Uploading file. Please wait: Do not navigate away from this page until the upload is complete.</b>' + '</div><div style="float:right;" onclick="$(\'.warning_msg\').hide(\'slow\');">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div><div style="clear: both;"></div></div>');
				$("#percentCompleted")[0].scrollIntoView(false);

				// Monitor upload progress.
				// Use timestamp as token.
				getUploadProgress($("#uploadProgress").val());
			}
			else if ($('#githubLink').is(":checked")) {
				$("#percentCompleted").html('<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;"><div style="float:left;">' + '<b>Retrieving files from GitHub. Please wait: Do not navigate away from this page until the retrieval is complete.</b>' + '</div><div style="float:right;" onclick="$(\'.warning_msg\').hide(\'slow\');">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div><div style="clear: both;"></div></div>');
				$("#percentCompleted")[0].scrollIntoView(false);
			}
		});
	});

	// Get upload progress value.
	function getUploadProgress(theToken) {

		// Send token.
		var params = {"token": theToken};

		// Query every 2 second.
		var timer = setInterval(function() {
			$.ajax({
				type: 'POST',
				data: JSON.stringify(params),
				url: 'uploadProgress.php',
				success: function(msg) {
					if (msg === 'null' || msg == -1) {
						// Cannot get upload progress value; done.
						clearInterval(timer);
					}
					else {
						// Received upload progress value; get percentage.
						var progress = JSON.parse(msg);
						var procBytes = progress['bytes_processed'];
						var totBytes = progress['content_length'];
						if ($.isNumeric(procBytes) && $.isNumeric(totBytes)) {
							// Valid values.
							var percent = Math.floor(procBytes * 100 / totBytes);
							if (percent >= 100) {
								// Done.
								clearInterval(timer);
							}
							$("#percentCompleted").html('<div class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;"><div style="float:left;">' + '<b>Uploading file (' + percent + '%' + '). Please wait: Do not navigate away from this page until the upload is complete.</b>' + '</div><div style="float:right;" onclick="$(\'.warning_msg\').hide(\'slow\');">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div><div style="clear: both;"></div></div>');
						}
						else {
							// Error.
							console.log("Error parsing progress");
							clearInterval(timer);
						}
					}
				},
				error: function(err) {
					console.log("Error retrieving progress");
				}
			});
		}, 2000);
	}

</script>
  
<style>
td {
	padding-bottom:5px;
	vertical-align:top;
}
.popover>div.arrow {
	opacity: 0;
}
</style>

<?php if (isset($doi) && ($doi) && isset($doi_confirm) && ($doi_confirm)) { ?>

  <?php echo "File: " . $disp_name . "<br />"; ?>
  <?php echo "(Release: " . $frsr->getName() . ")<br /><br />"; ?>
  <p>This file is being assigned a DOI and can no longer be edited or deleted.</p>

<?php } else { ?>

<div><h4>Update: <?php echo $frsf->getName(); ?><p/>(RELEASE: <?php echo $frsr->getName(); ?>)</h4></div>

<form id="addfile" enctype="multipart/form-data" action="editfile.php" method="post">

<input type="hidden" id="uploadProgress" name="<?php echo ini_get("session.upload_progress.name"); ?>" value="<?php echo time(); ?>"/>
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
	<td style="min-width:200px;width:25%;padding-top:2px;"><input type="radio" id="docFile" 
		name="docType" value="1" <?php 
		if ($frsf->isURL() === false) {
			echo "checked='checked'"; 
		}
		?> ><label for="docFile"><strong>Upload a File</strong></label></input>
	</td>
	<td>
	<table id="tableFile">

	<tr>
		<td>New File:
		<?php echo '&nbsp;(max upload size: ' . 
			human_readable_bytes(getUploadFileSizeLimit($group_id)) . 
			')'; ?>
		</td>
	</tr>
	<tr>
		<td style="padding-left:30px;">
		<input class="upFile" type="file" id="newfile" name="userfile"  size="30" />
		</td>
	</tr>

	</table>
	</td>
</tr>

<tr>
	<td style="min-width:200px;padding-top:2px;"><input type="radio" id="docLink" 
		name="docType" value="2" <?php 
		if ($frsf->isURL() === true) {
			echo "checked='checked'"; 
		}
		?> ><label for="docLink"><strong>Create a Link</strong></label></input>
	</td>
	<td>
	<table>
		<tr>
			<td>Link URL:&nbsp; &nbsp;<input type="text" 
				style="margin-left:3px;"
				id="linkurl" 
				size="45"
				name="url" 
				value="<?php 
				echo $frsf->getURL(); 
				?>"/>
			</td>
		</tr>
	</table>
	</td>
</tr>

<tr>
	<td style="min-width:200px;padding-top:2px;"><input type="radio" id="githubLink" name="docType" 
		value="3" <?php 
		if ($frsf->isGitHubArchive() === true) {
			echo "checked='checked'";
		}
		?> ><label for="githubLink"><strong>Add All GitHub Files</strong></label></input>
	</td>
	<td>
	<table>
		<tr>
			<td>Link URL:<span class="myPopOver">
				<a href="javascript://" class="popoverLic" 
				data-html="true" data-toggle="popover" data-placement="top"
				data-content="To add a zip file of the most current files in the repository, list the URL with '/archive/master.zip' appended to the GitHub repository name (e.g., 
<?php

if ($group->usesGitHub()) {
	$url = $group->getGitHubAccessURL();
	if (trim($url) != "") {
		echo "https://github.com/" . $url . "/archive/master.zip";
	}
	else {
		echo "https://github.com/simtk/src/archive/master.zip";
	}
}
else {
	echo "https://github.com/simtk/src/archive/master.zip";
}

?>).  You can also specify one particular file by listing the URL to that file (e.g., https://github.com/simtk/src/archive/v3.0.5.tar.gz).">&nbsp;?&nbsp;</a></span><input type="text" 
				id="githubArchiveUrl" 
				name="githubArchiveUrl" 
				size="45"
				value="<?php 
					$theGitHubURL = $frsf->getGitHubArchiveURL(); 
					if ($theGitHubURL == "") {
						// Initialize to default GitHub archive URL.
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
						$theGitHubURL = "http://github.com/" . $url . "/archive/master.zip";
					}
					echo $theGitHubURL;
				?>"/>
			</td>
		</tr>
		<tr>
			<td><b>Restriction:</b> Public repository only</td>
		</tr>
		<tr>
			<td>Refresh frequency:&nbsp;&nbsp;
				<input type="radio" 
					name="refreshArchive" 
					value="0" <?php
					if ($frsf->getGitHubRefreshArchiveFreq() == 0) {
						echo "checked";
					}
					?>
					><label for="">None</label>
				</input>&nbsp;&nbsp;
				<input type="radio" 
					name="refreshArchive" 
					value="1" <?php
					if ($frsf->getGitHubRefreshArchiveFreq() == 1) {
						echo "checked";
					}
					?>
					><label for="">Daily</label>
				</input>&nbsp;&nbsp;
				<input type="radio" 
					name="refreshArchive" 
					value="7" <?php
					if ($frsf->getGitHubRefreshArchiveFreq() == 7) {
						echo "checked";
					}
					?>
					><label for="">Weekly</label>
				</input>&nbsp;&nbsp;
			</td>
		</tr>
	</table>
	</td>
</tr>

<tr>
	<td colspan="2"><div class="downloadFileOptions"><h2>Select Download File Options</h2></div>
	</td>
</tr>

<tr>
	<td colspan="2"><div class="downloadFileOptions"><input class="upFile" type="checkbox" id="collectInfo" name="collect_info" value="1" <?php if ($frsf->getCollectData() === "1") echo "checked='checked'"; ?> />&nbsp;&nbsp;Collect user information for download statistics (requires user login)</div>
	</td>
</tr>

<?php
// Only show agreement if the package has one; i.e. the value is not 0 ("None".)
if ($frsp->getUseAgreement() != 0) {
?>
<tr>
	<td colspan="2"><div class="downloadFileOptions"><input class="upFile" id="showAgreement" type="checkbox" name="show_agreement" value="1" <?php if ($frsf->getShowAgreement() === "1") echo "checked='checked'"; ?> />&nbsp;&nbsp;Show download agreement</div>
	</td>
</tr>
<?php
}
?>
<tr>
	<td colspan="2"><div class="downloadFileOptions"><input class="upFile" id="showNotes" type="checkbox" name="show_notes" value="1" <?php if ($frsf->getShowNotes() === "1") echo "checked='checked'"; ?> />&nbsp;&nbsp;Show download notes</div>
	</td>
</tr>

<?php
$strMailingListPopup = frs_show_mailinglist_popup($group_id, 'group_list_id', $frsf->getGroupListId());
if ($strMailingListPopup != false && trim($strMailingListPopup) != "") {
	echo '
<tr>
	<td colspan="2"><div class="downloadFileOptions"><input class="upFile" type="checkbox" id="useMailList" name="use_mail_list" value="1" ';
	if ($frsf->getUseMailList() === "1") {
		echo "checked='checked'";
	}
 	echo ' />&nbsp;';

	echo "Ask user to join mailing list:&nbsp;";
	echo $strMailingListPopup;
	echo '
	<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="top" data-content=' . "'" . '"Collect user information" must be checked to use mailing lists' . "'" . '>&nbsp;?&nbsp;</a></span> 
	</div>
	</td>
</tr>
	';
}
?>

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
	<td><input type="text" name="release_date" value="<?php echo date('Y-m-d'); ?>" size="10" maxlength="10" /> (Last Release Date: <?php echo date('Y-m-d', $frsf->getReleaseTime()); ?>)
	</td>
</tr>
<tr>
	<td><span class="cellDoi"><strong>DOI:</strong></span></td>
	<td><span class="cellDoi"><input type="checkbox" name="doi" id="doi" value="1" />&nbsp;Obtain a DOI for file
	<div id="doi_info" style="display:none">
        <font color="#ff0000">Warning: You will not be able to remove or edit this file after the DOI has been issued.  You will not be able to delete the release or the package it belongs to either.</font>
    </div></span>
	</td>
</tr>
<tr>
	<td colspan="2" style="padding-top:15px;">
		<input type="submit" 
			name="submitAndNotify" 
			id="submitAndNotify" 
			value="Update & Notify Followers" 
			onclick="$('.warning_msg').hide('slow')"
			class="btn-cta" /> &nbsp;<input type="submit" 
			name="submitNoNotify" 
			id="submitNoNotify" 
			value="Update & Do Not Notify Followers" 
			onclick="$('.warning_msg').hide('slow')"
			class="btn-cta" />
	</td>
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
