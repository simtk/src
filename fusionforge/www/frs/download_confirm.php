<?php

/**
 *
 * download_confirm.php
 * 
 * File that handles download of file with confirmation.
 *
 * Copyright 2005-2021, SimTK Team
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
 
$no_gz_buffer=true;

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';
require_once 'download_utils.php';

// Mail list for this file.
$listName = "";

$normalized_urlprefix = normalized_urlprefix();
$pathinfo = substr_replace(getStringFromServer('REQUEST_URI'), '', 0, strlen($normalized_urlprefix)-1);
$expl_pathinfo = explode('/', $pathinfo);

// $mode is 'file', 'latestzip', 'latestfile', or 'release'.
$mode = $expl_pathinfo[3];

// .../download_confirm.php/123/foo.tar.gz (5.1 backward compatibility)
if (ctype_digit($mode)) {
	$mode = 'file';
	$expl_pathinfo = array_merge(array_slice($expl_pathinfo, 0, 3), 
		array($mode), array_slice($expl_pathinfo, 3));
}

switch ($mode) {
case 'file':
	// EXAMPLE: https://SERVER_NAME/frs/download_confirm.php/file/FILE_ID/FILE_TO_DOWNLOAD?group_id=GROUP_ID

	// Get FRSFile object.
	$file_id = $expl_pathinfo[4];
	$frsFile = frsfile_get_object($file_id);
	if (!$frsFile) {
		// Cannot find file.
		session_redirect404();
	}

	$frsRelease = $frsFile->FRSRelease;
	$frsPackage = $frsRelease->FRSPackage;

	// Check the simtk_collect_data flag to determine whether logged-in should be checked.
	$simtkCollectData = $frsFile->getCollectData();
	if ($frsPackage->isPublic()) {
		if ($simtkCollectData > 0) {
			if (!session_loggedin()) {
				// Not logged in.
				session_require_perm('frs', $frsPackage->Group->getID(), 'read_public');
			}
		}
	}
	else {
		session_require_perm('frs', $frsPackage->Group->getID(), 'read_private');
	}

	// Generate the page top.
	generatePageTop($group_id, $expl_pathinfo);

	// DIV containing the user fields.
	echo "<div id='divUserInputs'>";

	$showDownloadNotes = $frsFile->getShowNotes();
	if ($showDownloadNotes > 0) {
		genDownloadNotesUI($frsPackage);
	}

	if ($simtkCollectData > 0) {
		// Get text of previous expected use.
		$prevExpectedUse =  getFileExpectedUse($file_id);

		// Collect data UI.
		genExpectedUseUI($prevExpectedUse);
	}

	// Check whether user is a member of the 
	// specified mail group list for this file.
	$groupListId = $frsFile->getGroupListId();
	$promptMembership = checkMailListMembership($groupListId, $listName);
	if ($promptMembership) {
		// Prompt for mail list membership.
		genMailListUI($listName);
	}

	if ($frsFile->getShowAgreement() > 0) {
		// File shows agreement.
		genAgreedSubmitButton($frsPackage);
	}
	else {
		genSubmitButton();
	}

	break;

case 'latestzip':
	// EXAMPLE: https://SERVER_NAME/frs/download_confirm.php/latestzip/PACKAGE_ID/PACKAGE_TO_DOWNLOAD?group_id=GROUP_ID
	$package_id = $expl_pathinfo[4];

	$frsPackage = frspackage_get_object($package_id);
	if (!$frsPackage || !$frsPackage->getNewestRelease()) {
		session_redirect404();
	}
	$frsRelease = $frsPackage->getNewestRelease();
	$release_id = $frsRelease->getID();

	// Check the simtk_collect_data flag for all files contained within the package
	// to determine whether any file requires logged-in to be checked
	$simtkCollectData = getReleaseCollectData($release_id);
	if ($frsPackage->isPublic()) {
		if ($simtkCollectData > 0) {
			if (!session_loggedin()) {
				// Not logged in.
				session_require_perm('frs', $frsPackage->Group->getID(), 'read_public');
			}
		}
	} else {
		session_require_perm('frs', $frsPackage->Group->getID(), 'read_private');
	}

	// Generate the page top.
	generatePageTop($group_id, $expl_pathinfo);

	// DIV containing the user fields.
	echo "<div id='divUserInputs'>";

	if ($simtkCollectData > 0) {
		// Get text of previous expected use.
		$prevExpectedUse =  getReleaseExpectedUse($release_id);

		// Collect data UI.
		genExpectedUseUI($prevExpectedUse);
	}

	// Check whether user is a member of the 
	// specified mail group list for this file.
	$groupListId = getReleaseGroupListId($release_id);
	$promptMembership = checkMailListMembership($groupListId, $listName);
	if ($promptMembership) {
		// Prompt for mail list membership.
		genMailListUI($listName);
	}

	$simtkShowAgreement = getReleaseShowAgreement($release_id);
	if ($simtkShowAgreement > 0) {
		// At least one file shows agreement.
		genAgreedSubmitButton($frsPackage);
	}
	else {
		genSubmitButton();
	}

	break;

case 'latestfile':
	// EXAMPLE: https://SERVER_NAME/frs/download_confirm.php/latestfile/PACKAGE_ID/FILE_TO_DOWNLOAD?group_id=GROUP_ID
	$package_id = $expl_pathinfo[4];
	$tmpStr = $expl_pathinfo[5];
	// Remove "?" and subsequence characters.
	$idx = stripos($tmpStr, "?");
	if ($idx !== false) {
		$tmpFileName = substr($tmpStr, 0, $idx);
	}
	else {
		$tmpFileName = $tmpStr;
	}

	// Note: Needs to use urldecode() here!!!
	// Otherwise, a space in the file name as shown in the UI becomes %20 in $tmpFileName,
	// causing the file not to be found.
	// urldecode() converts %20 back to space, hence fixes this problem.
	$decodedFileNameFromURL = urldecode($tmpFileName);
	$sqlFileId = 'SELECT f.file_id FROM frs_file f, frs_release r, frs_package p ' .
		'WHERE f.release_id = r.release_id ' .
		'AND r.package_id = p.package_id ' .
		'AND p.package_id = $1 ' .
		'AND f.filename = $2 ' .
		'ORDER BY f.release_id DESC';
	$res = db_query_params($sqlFileId, array($package_id, $decodedFileNameFromURL));
	if (!$res || db_numrows($res) < 1) {
		session_redirect404();
	}
	$row = db_fetch_array($res);
	$file_id = $row['file_id'];

	// Get FRSFile object.
	$frsFile = frsfile_get_object($file_id);
	if (!$frsFile) {
		// Cannot find file.
		session_redirect404();
	}

	$frsRelease = $frsFile->FRSRelease;
	$frsPackage = $frsRelease->FRSPackage;

	// Check the simtk_collect_data flag to determine whether logged-in should be checked.
	$simtkCollectData = $frsFile->getCollectData();
	if ($frsPackage->isPublic()) {
		if ($simtkCollectData > 0) {
			if (!session_loggedin()) {
				// Not logged in.
				session_require_perm('frs', $frsPackage->Group->getID(), 'read_public');
			}
		}
	}
	else {
		session_require_perm('frs', $frsPackage->Group->getID(), 'read_private');
	}

	// Generate the page top.
	generatePageTop($group_id, $expl_pathinfo);

	if ($simtkCollectData > 0) {
		// Get text of previous expected use.
		$prevExpectedUse =  getFileExpectedUse($file_id);

		// Collect data UI.
		genExpectedUseUI($prevExpectedUse);
	}

	// Check whether user is a member of the 
	// specified mail group list for this file.
	$groupListId = $frsFile->getGroupListId();
	$promptMembership = checkMailListMembership($groupListId, $listName);
	if ($promptMembership) {
		// Prompt for mail list membership.
		genMailListUI($listName);
	}

	$simtkShowAgreement = $frsFile->getShowAgreement();
	if ($simtkShowAgreement > 0) {
		// At least one file shows agreement.
		genAgreedSubmitButton($frsPackage);
	}
	else {
		genSubmitButton();
	}

	break;

case 'release':
	// EXAMPLE: https://SERVER_NAME/frs/download_confirm.php/release/RELEASE_ID?group_id=GROUP_ID
	$tmpStr = $expl_pathinfo[4];
	// Remove "?" and subsequence characters.
	$idx = stripos($tmpStr, "?");
	if ($idx !== false) {
		$release_id = substr($tmpStr, 0, $idx);
	}
	else {
		$release_id = $tmpStr;
	}

	$frsRelease = frsrelease_get_object($release_id);
	$frsPackage = $frsRelease->FRSPackage;
	if (!$frsPackage) {
		session_redirect404();
	}

	// Check the simtk_collect_data flag for all files contained within the package
	// to determine whether any file requires logged-in to be checked.
	$simtkCollectData = getReleaseCollectData($release_id);
	if ($frsPackage->isPublic()) {
		if ($simtkCollectData > 0) {
			if (!session_loggedin()) {
				// Not logged in.
				session_require_perm('frs', $frsPackage->Group->getID(), 'read_public');
			}
		}
	} else {
		session_require_perm('frs', $frsPackage->Group->getID(), 'read_private');
	}

	// Generate the page top.
	generatePageTop($group_id, $expl_pathinfo);

	if ($simtkCollectData > 0) {
		// Get text of previous expected use.
		$prevExpectedUse =  getReleaseExpectedUse($release_id);

		// Collect data UI.
		genExpectedUseUI($prevExpectedUse);
	}

	// Check whether user is a member of the 
	// specified mail group list for this file.
	$groupListId = getReleaseGroupListId($release_id);
	$promptMembership = checkMailListMembership($groupListId, $listName);
	if ($promptMembership) {
		// Prompt for mail list membership.
		genMailListUI($listName);
	}

	$simtkShowAgreement = getReleaseShowAgreement($release_id);
	if ($simtkShowAgreement > 0) {
		// At least one file shows agreement.
		genAgreedSubmitButton($frsPackage);
	}
	else {
		genSubmitButton();
	}


	break;

default:
	exit_error(_('Invalid download mode'));
}

// Generate page bottom.
generatePageBottom();

// Get user id.
// Anonymous user.
$userId = 100;
if (session_loggedin()) {
	// User is logged in.
	$usrObj =& session_get_user();
	$userId = $usrObj->getID();
}

?>

<script type="text/javascript" src="/themes/simtk/js/jquery.customSelect.min.js"></script>
<script type="text/javascript" src="/frs/utilsDownloadProgress.js"></script>

<script>
$(document).ready(function() {
	// Handle submit action.
	$("#mySubmit").click(function() {

		if ($(".expected_use").length) {
			// Expected use textarea is present.
			var valExpectedUse = $(".expected_use").val();
			if ($.trim(valExpectedUse).length == 0 ||
				valExpectedUse.length < 7) {
				// Ignore submission. Expected use is not filled in.
				$(".expected_use")[0].scrollIntoView(false);
				return;
			}
		}

		// Hide the license div, if present, once submitted.
		$(".divLicense").hide();

		// Get timestamp associated with the download.
		var theTimeStamp = $("input[name='timestamp']").val();
		var theRemoteAddr = "<?php echo $_SERVER["REMOTE_ADDR"]; ?>";
		var theUserId = "<?php echo $userId; ?>";

		// Token file to keep track of download.
		var tokenDownloadProgress = "download_" +
			theRemoteAddr + "." +
                        theUserId + "." +
                        theTimeStamp;

		// Start tracking of download progress.
		trackDownloadProgress("msgDownload", 
			"myBrowse",
			"mySubmit",
			"divUserInputs",
			tokenDownloadProgress);
	});

	// Return to download page.
	$("#myBrowse").click(function() {
		event.preventDefault();
		window.location.href = "/frs/?group_id=" + <?php echo $group_id; ?>;
	});
});
</script>

<?php

frs_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
