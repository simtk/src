<?php

/**
 *
 * download_utils.php
 * 
 * Utiliy to handle file downloads.
 *
 * Copyright 2005-2025, SimTK Team
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
 
DEFINE('INPUT_EXPECTED_USE', '<div style="font-weight: normal;">' .
	'Please describe how you expect to use this software:' .
	'</div>' . 
	'<div>' .
	'<textarea class="expected_use" name="expected_use" cols="50" rows="5">');

DEFINE('INPUT_MAIL_LIST', '<p style="font-weight: bold; margin-bottom: 1.2em;">' .
	'<input type="checkbox" name="add_to_mailing_list" checked="">&nbsp;' .
	'I would like to find out about new releases and other ' .
	'important announcements regarding this release. ');

DEFINE('MSG_DOWNLOAD', '<div class="msgDownload"></div>');

DEFINE('INPUT_TIMESTAMP', '<input type="hidden" name="timestamp" value="' . microtime(true) . '" />');

DEFINE('INPUT_AGREED_SUBMIT', 
	'<input type="hidden" name="agreed" value="1" />' . '<br/>' .
	'<input id="mySubmit" type="submit" name="submit" ' .
	'value="I Agree & Download Now" class="btn-cta" />' . '&nbsp;' .
	'<input id="myBrowse" type="submit" name="browse" value="Return to Download" class="btn-cta" />');

DEFINE('INPUT_SUBMIT', 
	'<input type="hidden" name="agreed" value="0" />' . '<br/>' .
	'<input id="mySubmit" type="submit" name="submit" ' .
	'value="Download Now" class="btn-cta" />' . '&nbsp;' .
	'<input id="myBrowse" type="submit" name="browse" value="Return to Download" class="btn-cta" />');

// Check whether the logged in user is member of the mail list
// specified for this file.
function checkMailListMembership($groupListId, &$retListName) {

	if (!session_loggedin()) {
		// User is not logged in.
		return false;
	}

	if ($groupListId == false || $groupListId == "") {
		// No mail list set up for this file.
		return false;
	}

	$sqlListName = "SELECT list_name FROM mail_group_list " .
		"WHERE group_list_id=$1";
	$res = db_query_params($sqlListName, array($groupListId));
	$retListName = db_result($res, 0, 'list_name');
	if ($retListName == "") {
		// Cannot get mail list name.
		return false;
	}

	// Get mail list members.
	$cmdListMembers = "/usr/lib/mailman/bin/list_members $retListName";
	exec($cmdListMembers, $listMembers);

	$theUser = session_get_user();
	if ($theUser->getID() == 100) {
		return false;
	}
	$userEmail = strtolower($theUser->getEmail());
	for ($cnt = 0; $cnt < count($listMembers); $cnt++ ) {
		if (strtolower($listMembers[$cnt]) == $userEmail) {
			// Already a member of this mail list.
			return false;
		}
	}

	// Not member of this list. Prompt user for membership.
	return true;
}

// If the user has downloaded this release before, get the previous expected use.
function getFileExpectedUse($fileId) {

	$previousExpectedUse = "";

	if (!session_loggedin()) {
		// User is not logged in.
		return "";
	}

	$theUser = session_get_user();
	if ($theUser->getID() == 100) {
		return "";
	}

	$sqlQuery = "SELECT simtk_expected_use FROM frs_dlstats_file s " .
		"WHERE user_id=$1 AND " .
		"simtk_expected_use != '' AND " .
		"file_id IN " .
		"(SELECT file_id FROM frs_file f WHERE release_id=" .
		"(SELECT release_id FROM frs_file f WHERE file_id=$2)) " .
		"ORDER BY month DESC, day DESC";
	$res = db_query_params($sqlQuery, array($theUser->getID(), $fileId));
	if ($res && db_numrows( $res ) > 0 ) {
		$previousExpectedUse = db_result($res, 0, 'simtk_expected_use');
	}

	return $previousExpectedUse;
}

// If the user has downloaded this release before, get the previous expected use.
function getReleaseExpectedUse($releaseId) {

	$previousExpectedUse = "";

	if (!session_loggedin()) {
		// User is not logged in.
		return "";
	}

	$theUser = session_get_user();
	if ($theUser->getID() == 100) {
		return "";
	}

	$sqlQuery = "SELECT simtk_expected_use FROM frs_dlstats_file s " .
		"WHERE user_id=$1 AND " .
		"simtk_expected_use != '' AND " .
		"file_id IN " .
		"(SELECT file_id FROM frs_file f WHERE release_id=$2) " .
		"ORDER BY month DESC, day DESC LIMIT 1";
	$res = db_query_params($sqlQuery, array($theUser->getID(), $releaseId));
	if ($res && db_numrows( $res ) > 0 ) {
		$previousExpectedUse = db_result($res, 0, 'simtk_expected_use');
	}

	return $previousExpectedUse;
}

// Get group_list_id for release.
function getReleaseGroupListId($releaseId) {

	$groupListId = "";

	if (!session_loggedin()) {
		// User is not logged in.
		return "";
	}

	$theUser = session_get_user();
	if ($theUser->getID() == 100) {
		return "";
	}

	$sqlQuery = "SELECT simtk_group_list_id FROM frs_file f " .
		"WHERE simtk_group_list_id!=0 AND " .
		"file_id IN " .
		"(SELECT file_id FROM frs_file f WHERE release_id=$1) " .
		"ORDER BY post_date DESC LIMIT 1";
	$res = db_query_params($sqlQuery, array($releaseId));
	if ($res && db_numrows( $res ) > 0 ) {
		$groupListId = db_result($res, 0, 'simtk_group_list_id');
	}

	return $groupListId;
}

// Get simtk_collect_data for the release.
// Counts files within the release that has simtk_collect_data=1.
function getReleaseCollectData($releaseId) {

	$count_simtk_collect_data = 0;
	$strQuery = 'SELECT count(*) as count_simtk_collect_data FROM frs_file ' .
		'WHERE release_id=$1 ' .
		'AND simtk_collect_data=1';
	$res = db_query_params($strQuery, array($releaseId));
	if ($res && db_numrows($res) > 0) {
		$row = db_fetch_array($res);
		// Value returned will be 0 if none of the files requires this check.
		// Otherwise, the number of files requiring the check (a positive value)
		// will be returned.
		$count_simtk_collect_data = $row['count_simtk_collect_data'];
	}

	return $count_simtk_collect_data;
}

// Get simtk_show_agreement for the release.
// Counts files within the release that has simtk_show_agreement=1.
function getReleaseShowAgreement($releaseId) {

	$count_simtk_show_agreement = 0;
	$strQuery = 'SELECT count(*) as count_simtk_show_agreement FROM frs_file ' .
		'WHERE release_id=$1 ' .
		'AND simtk_show_agreement=1';
	$res = db_query_params($strQuery, array($releaseId));
	if ($res && db_numrows($res) > 0) {
		$row = db_fetch_array($res);
		// Value returned will be 0 if none of the files requires this check.
		// Otherwise, the number of files requiring the check (a positive value)
		// will be returned.
		$count_simtk_show_agreement = $row['count_simtk_show_agreement'];
	}

	return $count_simtk_show_agreement;
}

// Page top.
function generatePageTop($group_id, $expl_pathinfo) {

	frs_header(array('title'=>_('Downloads'), 'group'=>$group_id));
	plugin_hook("blocks", "files index");

	echo '<script src="/js/jquery-1.11.2.min.js"></script>';
	echo '<script src="/frs/download_utils.js"></script>';
	echo '<link rel="stylesheet" type="text/css" href="/themes/simtk/css/theme.css">';
	echo '<link rel="stylesheet" type="text/css" href="/themes/simtk/css/carousel.css">';
	echo '<div class="downloads_main">';
	echo '<div style="display: table; width: 100%;">';
	echo '<div class="main_col">';

	echo MSG_DOWNLOAD;

	// Generate form to collect user input before download.
	// Add back "file", "file id", "file name" and "group id"
	// to the URL to invoke with "download_start.php".
	echo '<form class="download" method="post" action="/frs/download_start.php/' .
		$expl_pathinfo[3] . '/' .
		$expl_pathinfo[4];
	if (isset($expl_pathinfo[5])) {
		// Note: $expl_pathinfo[5] may not be present.
		echo '/' . $expl_pathinfo[5];
	}
	if (isset($expl_pathinfo[5]) &&
		stripos($expl_pathinfo[5], "?group_id=") === FALSE) {
		echo '?group_id=' . $group_id;
	}
	echo '">';
}


// Page bottom.
function generatePageBottom() {
	echo '</form>';
	echo '</div> <!-- main_col -->';
	echo '</div> <!-- display: table; width: 100% -->';
	echo '</div> <!-- downloads_main -->';
}

// Generate UI to collect data.
function genExpectedUseUI($prevExpectedUse) {
	echo INPUT_EXPECTED_USE . $prevExpectedUse . '</textarea></div>';
}

// Generate UI for mail list.
function genMailListUI($listName) {
	echo INPUT_MAIL_LIST;
	echo 'Please add me to the ' . $listName . ' mailing list.</p>';
	echo '<input type="hidden" name="mail_list_name" value="' . $listName . '" />';
}

// Generate UI for showing download notes.
function genDownloadNotesUI($frsPackage) {
	if (trim($frsPackage->getDownloadNotes()) != "") {
		echo '<div class="download_package" style="background-image:none;margin-bottom:20px;">';
		echo '<label>Download Notes:</label>';
		echo '<div style="font-weight: normal;">' . 
			$frsPackage->getDownloadNotes() . 
			'</div>';
		echo '</div>';
	}
}

// Generate UI for use agreement and Agreed & submit button.
function genAgreedSubmitButton($frsPackage) {
	// Display download agreement.
	// Note: the custom agreement is the saved agreement.
	// Note: Check to ensure use agreement is not 0 ("None") first.
	if ($frsPackage->getUseAgreement() != 0) {
		echo "<div class='divLicense'>";
		echo "<div style='margin-top:5px;'><strong>License Agreement:</strong></div>";
		echo "<Textarea disabled rows='10' cols='50' >" . 
			html_entity_decode($frsPackage->getCustomAgreement()) . 
			"</Textarea><br/>";
		echo "</div>";
	}

	echo INPUT_TIMESTAMP;

	// DIV containing the user fields.
	echo "</div>";

	echo INPUT_AGREED_SUBMIT;
}

// Generate UI for submit button.
function genSubmitButton() {
	echo INPUT_TIMESTAMP;

	// DIV containing the user fields.
	echo "</div>";

	echo INPUT_SUBMIT;
}

// Send file in chunks.
function sendFileChunked($fileName, $fileSize, $tokenDownloadProgress=false) {

	// Report download progress every 20MB.
	$lastCounterChange = 0;
	$thresholdReport = 20*(1024*1024);

	// 1MB chunks
	$chunksize = 1*(1024*1024);

	$buffer = '';
	$byteCounter = 0;

	$handle = fopen($fileName, 'rb');
	if ($handle === false) {
		return false;
	}

	ob_start();
	while (!feof($handle)) {
		$buffer = fread($handle, $chunksize);
		echo $buffer;
		ob_flush();
		flush();
		$byteCounter += strlen($buffer);

		if ($tokenDownloadProgress !== false) {
			// Token file is used for tracking download progress.
			if ($byteCounter - $lastCounterChange > $thresholdReport) {
				// Get download tokens directory.
				$dirTokens = "/opt/tmp/tokens/";
				if (is_dir($dirTokens))  {
					$fp = fopen($dirTokens . $tokenDownloadProgress, 
						"w+");
					fwrite($fp, ((int) ($byteCounter * 100 / $fileSize)) . "%\n");
					fclose($fp);
				}

				// Move tracking counter to last reported value.
				$lastCounterChange = $byteCounter;
			}
		}
	}
	if ($tokenDownloadProgress !== false) {
		// Get download tokens directory.
		$dirTokens = "/opt/tmp/tokens/";
		if (is_dir($dirTokens))  {
			$fp = fopen($dirTokens . $tokenDownloadProgress, 
				"w+");
			fwrite($fp, "done\n");
			fclose($fp);
		}
	}
	ob_end_flush();

	$status = fclose($handle);
	if ($status) {
		// Return number of bytes delivered like readfile() does.
		return $byteCounter;
	}

	return $status;
}

?>



