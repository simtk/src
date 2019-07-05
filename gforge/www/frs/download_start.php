<?php
/**
 * FRS Facility
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2010 (c) FusionForge Team
 * Copyright 2013, Franck Villaume - TrivialDev
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

$no_gz_buffer=true;

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';
require_once 'download_utils.php';


// Add the logged in user to the mail list
// specified for this file.
function addMailListMembership($listName) {

	if (!session_loggedin()) {
		// User is not logged in.
	}

	if ($listName == "") {
		// Mail list not available.
	}

	// Add user to mail list.
	$theUser = session_get_user();
	$userEmail = escapeshellcmd($theUser->getEmail());
	$userName = escapeshellcmd($theUser->getUnixName());
	$cmdAddMember = "echo '$userName <$userEmail>' | /usr/lib/mailman/bin/add_members -d - -w y $listName";
	exec($cmdAddMember);
}


/* This script can work in one of the following modes:
 * - file: one specific file (given its file_id)
 * - latestzip: gets a Zip archive containing all files in the latest
 *   release of a given package (given a package_id)
 * - latestfile: the version of a file that's in the
 *   latest release of a given package (given the file name and the
 *   package_id)
 */

function alertMissingElement($strMissingElem, $elemPath="") {
	global $HTML;

	// Send email to SimTK webmaster.
	$strEmailSubject = "Downloads: Missing element.";
	$strEmailMessage = "$strMissingElem is missing from downloads.\n";
	if (trim($elemPath) != "") {
		$strEmailMessage .= "Path: $elemPath\n";
	}
	$admins = RBACEngine::getInstance()->getUsersByAllowedAction('approve_projects', -1);
	foreach ($admins as $admin) {
		$admin_email = $admin->getEmail();
		setup_gettext_for_user ($admin);
		util_send_message($admin_email, $strEmailSubject, $strEmailMessage);
		setup_gettext_from_context();
	}

	header($_SERVER["SERVER_PROTOCOL"]. " 404 Not Found");
	$HTML->header(array('title'=>'Requested Page not Found (Error 404)'));

	$msgMissingFile = "$strMissingElem is currently not available. ";
	$msgMissingFile .= "The SimTK webmaster has been alerted.";
	echo $HTML->warning_msg($msgMissingFile);

	$HTML->footer();

	exit;
}

function send_file($filename, $filepath, $agreed, $expected_use = "", $file_id = NULL, $mode = NULL) {

	if (!file_exists($filepath)) {
		//session_redirect404();
		alertMissingElement($filename, $filepath);
	}

	header('Content-disposition: attachment; filename="'.str_replace('"', '', $filename).'"');
	sysdebug_off("Content-type: application/binary");
	$length = filesize($filepath);
	header("Content-length: $length");

	// Note: ob_clean() and flush() are needed here!!!
	// Otherwise, the zip file downloaded to MAC cannot be opened.
	// See: http://stackoverflow.com/questions/23668293/php-dynamically-generated-zip-file-by-ziparchive-cant-be-opened
	// See: http://stackoverflow.com/questions/19963382/php-zip-file-download-error-when-opening
	ob_clean();
	flush();

	readfile_chunked($filepath);

	if (!$file_id) {
		return;
	}

	if (session_loggedin()) {
		$s =& session_get_user();
		$us=$s->getID();
	}
	else {
		$us=100;
	}

	$ip = getStringFromServer('REMOTE_ADDR');

	if (!empty($_SERVER['REMOTE_HOST'])) {
		$host = strtolower($_SERVER['REMOTE_HOST']);
	}
	else if (!empty($ip)) {
		$host = strtolower(getHostByAddr($ip));
	}

	$strInsert = "INSERT INTO frs_dlstats_file " .
		"(ip_address, file_id, month, day, user_id," .
		"simtk_host_name, simtk_expected_use, simtk_agreed_to_license) " .
		"VALUES ($1,$2,$3,$4,$5,$6,$7,$8)";

	if ($mode != 'latestzip' && $mode != 'release') {
		$arrInsert = array(
			$ip,
			$file_id,
			date('Ym'),
			date('d'),
			$us,
			$host,
			pg_escape_string($expected_use),
			$agreed
		);
		db_query_params($strInsert, $arrInsert);
	}
	elseif ($mode == 'release') {
		// Here $file_id is a release_id
		$release = frsrelease_get_object($file_id);
		// Get files contained in the release.
		$files = $release->getFiles();

		foreach ($files as $fileObject) {
			$arrInsert = array(
				$ip,
				$fileObject->getID(),
				date('Ym'),
				date('d'),
				$us,
				$host,
				pg_escape_string($expected_use),
				1
			);
			db_query_params($strInsert, $arrInsert);
		}
	}
	else {
		// Here $file_id is a package_id
		$frsPackage = frspackage_get_object($file_id);
		$release = $frsPackage->getNewestRelease();
		$files = $release->getFiles();

		foreach ($files as $fileObject) {
			$arrInsert = array(
				$ip,
				$fileObject->getID(),
				date('Ym'),
				date('d'),
				$us,
				$host,
				pg_escape_string($expected_use),
				1
			);
			db_query_params($strInsert, $arrInsert);
		}
	}
}


// Navigate to the URL specified in the file path.
function send_url($filename, $filepath, $file_id = NULL, $mode = NULL) {

	if (session_loggedin()) {
		$s =& session_get_user();
		$us=$s->getID();
	}
	else {
		$us=100;
	}

	$ip = getStringFromServer('REMOTE_ADDR');
	if (!empty($_SERVER['REMOTE_HOST'])) {
		$host = strtolower($_SERVER['REMOTE_HOST']);
	}
	else if (!empty($ip)) {
		$host = strtolower(getHostByAddr($ip));
	}

	$strInsert = "INSERT INTO frs_dlstats_file " .
		"(ip_address, file_id, month, day, user_id," .
		"simtk_host_name, simtk_expected_use, simtk_agreed_to_license) " .
		"VALUES ($1,$2,$3,$4,$5,$6,$7,$8)";

	$arrInsert = array(
		$ip,
		$file_id,
		date('Ym'),
		date('d'),
		$us,
		$host,
		"",
		0);
	db_query_params($strInsert, $arrInsert);

	header("Location: " . $filepath);
	return;
}


$agreed = getIntFromRequest('agreed');
$expected_use = getStringFromRequest('expected_use');
$add_to_mailing_list = getStringFromRequest('add_to_mailing_list');
$mail_list_name = getStringFromRequest('mail_list_name');


$normalized_urlprefix = normalized_urlprefix();
$pathinfo = substr_replace(getStringFromServer('REQUEST_URI'), '', 0, strlen($normalized_urlprefix)-1);
$expl_pathinfo = explode('/', $pathinfo);

$mode = $expl_pathinfo[3];

// .../download_start.php/123/foo.tar.gz (5.1 backward compatibility)
if (ctype_digit($mode)) {
	$mode = 'file';
	$expl_pathinfo = array_merge(array_slice($expl_pathinfo, 0, 3), 
		array($mode), array_slice($expl_pathinfo, 3));
}

switch ($mode) {
case 'file':
	// EXAMPLE: https://SERVER_NAME/frs/download_confirm.php/file/FILE_ID/FILE_TO_DOWNLOAD?group_id=GROUP_ID

	$file_id = $expl_pathinfo[4];

	// Allow alternate content-type rendering by hook
	$default_content_type = 'application/binary';

	$script = 'frs_download_file';
	$content_type = util_negociate_alternate_content_types($script, $default_content_type);

	if($content_type != $default_content_type) {
		$hook_params = array();
		$hook_params['accept'] = $content_type;
		$hook_params['group_id'] = $group_id;
		$hook_params['file_id'] = $file_id;
		$hook_params['return'] = '';
		$hook_params['content_type'] = '';
		plugin_hook_by_reference('content_negociated_frs_download_file', $hook_params);
		if($hook_params['content_type'] != ''){
			header('Content-type: '. $hook_params['content_type']);
			echo $hook_params['content'];
		}
		else {
			header('HTTP/1.1 406 Not Acceptable',true,406);
		}
		exit(0);
	}

	$frsFile = frsfile_get_object($file_id);
	if (!$frsFile) {
		//session_redirect404();
		alertMissingElement("File #" . $file_id);
	}

	// Try to find the file specified by 'simtk_filelocation' in frs_file,
	// which is migrated from Simtk 1.0.
	$simtkFileLocation = NULL;
	$simtkFileType = "";
	$sqlSimtkFileLocation = 'SELECT simtk_filelocation, simtk_filetype FROM frs_file WHERE file_id=$1';
	$resSimtkFileLocation = db_query_params($sqlSimtkFileLocation, array($file_id));
	if ($resSimtkFileLocation && 
		db_numrows($resSimtkFileLocation) > 0) {
		$rowSimtkFileLocation = db_fetch_array($resSimtkFileLocation);
		$simtkFileLocation = $rowSimtkFileLocation['simtk_filelocation'];
		$simtkFileType = $rowSimtkFileLocation['simtk_filetype'];
	}

	// Check the simtk_collect_data flag to determine whether logged-in should be checked.
	$simtkCollectData = $frsFile->getCollectData();
	$frsRelease = $frsFile->FRSRelease;
	$frsPackage = $frsRelease->FRSPackage;
	$frsGroup = $frsPackage->Group;
	if ($simtkFileType != "" && $simtkFileType != "URL") {
		// Perform this check only on non-URL type links.
		if ($frsPackage->isPublic()) {
			if ($simtkCollectData) {
				if (!session_loggedin()) {
					// Not logged in.
					session_require_perm('frs', $frsGroup->getID(), 'read_public');
				}
			}
		}
		else {
			session_require_perm('frs', $frsGroup->getID(), 'read_private');
		}
	}

	$filename = $frsFile->getName();

	if ($simtkFileLocation != NULL && 
		trim($simtkFileLocation) != "") {

		if ($simtkFileType != "" && $simtkFileType == "URL") {
			// NOTE: Do not use send_file if file type is URL!!!
			$filepath = $simtkFileLocation;
			// Navigate to the URL.
			send_url($filename, $filepath, $file_id);
			break;
		}
		else {
			// Found file! (for backward compatibility)
			$filepath = "/var/lib/gforge/download/" . 
				$frsGroup->getUnixName() . "/" . 
				$simtkFileLocation;
			if (!file_exists($filepath)) {
				// File is not found. Use default gforge file download.
				// NOTE: This case happens when a file from 
				// a previous release is re-used.
				$filepath = forge_get_config('upload_dir') . '/'. 
					$frsGroup->getUnixName() . '/' .
					$frsPackage->getFileName() . '/'.
					$frsRelease->getFileName() . '/' .
					$filename;
			}
		}
	}
	else {
		// Default gforge file download.
		$filepath = forge_get_config('upload_dir') . '/'. 
			$frsGroup->getUnixName() . '/' .
			$frsPackage->getFileName() . '/'.
			$frsRelease->getFileName() . '/' .
			$filename;
	}

	if ($frsFile->getShowAgreement() > 0 && 
		(!isset($agreed) || $agreed != 1)) {
		// Agreed not set.
		exit_error('Please agree to license!!');
	}

	if ($simtkCollectData > 0 && 
		(!isset($expected_use) || strlen(trim($expected_use)) <  7)) {
		// Expected use not valid.
		exit_error('Invalid expected use!');
	}

	if ($add_to_mailing_list != "" && $mail_list_name != "") {
		// Add user to mail list.
		addMailListMembership($mail_list_name);
	}

	// Send file.
	send_file($filename, $filepath, $agreed, $expected_use, $file_id);

	break;

case 'latestzip':
	// EXAMPLE: https://SERVER_NAME/frs/download_confirm.php/latestzip/PACKAGE_ID/PACKAGE_TO_DOWNLOAD?group_id=GROUP_ID

	$package_id = $expl_pathinfo[4];

	$frsPackage = frspackage_get_object($package_id);
	if (!$frsPackage || !$frsPackage->getNewestRelease()) {
		//session_redirect404();
		alertMissingElement("Package #" . $package_id);
	}
	$frsRelease = $frsPackage->getNewestRelease();
	$release_id = $frsRelease->getID();
	$frsGroup = $frsPackage->Group;

	// Check the simtk_collect_data flag for all files contained within the package
	// to determine whether any file requires logged-in to be checked.
	$simtkCollectData = getReleaseCollectData($release_id);
	if ($frsPackage->isPublic()) {
		if ($simtkCollectData) {
			if (!session_loggedin()) {
				// Not logged in.
				session_require_perm('frs', $frsGroup->getID(), 'read_public');
			}
		}
	} else {
		session_require_perm('frs', $frsGroup->getID(), 'read_private');
	}

	$filename = $frsPackage->getNewestReleaseZipName();
	$filepath = $frsPackage->getNewestReleaseZipPath();
	$dirUpload = forge_get_config('upload_dir');
	$dirGroup = $dirUpload . '/' . $frsGroup->getUnixName();
	if (!is_dir($dirGroup)) {
		@mkdir($dirGroup);
	}
	$dirRelease = $dirGroup . '/' . $frsPackage->getFileName();
	if (!is_dir($dirRelease)) {
		@mkdir($dirRelease);
	}

	if (!file_exists($filepath) && 
		class_exists('ZipArchive')) {

		// The zip file does not exist.
		// Try examining the files contained in each release and generate this zip file.
		// This process is only needed for release migrated from Simtk 1.0 because
		// the release zip file feature was not available for Simtk 1.0.
		// For Simtk 2.0, this zip file will be present.
		$release = $frsPackage->getNewestRelease();
		// Get files contained in the release.
		$files = $release->getFiles();

		$zipFile = new ZipArchive();
		if ($zipFile->open($filepath, ZIPARCHIVE::OVERWRITE) === true) {

			foreach ($files as $fileObject) {
				$tmpFileId =  $fileObject->getID();

				// Try to find the file specified by 
				// 'simtk_filelocation' in frs_file,
				// which is migrated from Simtk 1.0.
				$simtkFileLocation = NULL;
				$sqlSimtkFileLocation = "SELECT file_size, simtk_filelocation " .
					"FROM frs_file WHERE file_id=$1";
				$resSimtkFileLocation = db_query_params($sqlSimtkFileLocation, 
					array($tmpFileId));

				$tmpFilePath = null;
				if ($resSimtkFileLocation && db_numrows($resSimtkFileLocation) > 0) {
					$rowSimtkFileLocation = db_fetch_array($resSimtkFileLocation);
					$simtkFileLocation = $rowSimtkFileLocation['simtk_filelocation'];
				}

				if ($simtkFileLocation != NULL && 
					trim($simtkFileLocation) != "") {
					// Found File!
					$simtkFileSize = $rowSimtkFileLocation['file_size'];
					// Only include file that has non-zero file size.
					// e.g. URL which point to file outside are not 
					// included in this zip file.
					if ($simtkFileSize > 0) {
						// Found file! (for backward compatibility)
						$tmpFilePath = "/var/lib/gforge/download/" . 
							$frsGroup->getUnixName() . "/" . 
							$simtkFileLocation;
						if (!file_exists($tmpFilePath)) {
							// File is not found. Use default gforge file download.
							// NOTE: This case happens when a file from 
							// a previous release is re-used.
							$tmpFilePath = forge_get_config('upload_dir') . '/'.
								$frsGroup->getUnixName() . '/' .
								$frsPackage->getFileName() . '/'.
								$frsRelease->getFileName() . '/' .
								$fileObject->getName();
						}
					}
				}
				else {
					// Default gforge file download path.
					$tmpFilePath = forge_get_config('upload_dir') . '/'.
						$frsGroup->getUnixName() . '/' .
						$frsPackage->getFileName() . '/'.
						$frsRelease->getFileName() . '/' .
						$fileObject->getName();
				}

				if ($tmpFilePath !== null) {
					// Insert file into the zip file.
					$zipFile->addFile($tmpFilePath, $fileObject->getName());
				}
			}
		}
		$zipFile->close();
	}

	$simtkShowAgreement = getReleaseShowAgreement($release_id);
	if ($simtkShowAgreement > 0 &&
		(!isset($agreed) || $agreed != 1)) {
		// Agreed not set.
		exit_error('Please agree to license!!');
	}

	if ($simtkCollectData > 0 && 
		(!isset($expected_use) || strlen(trim($expected_use)) <  7)) {
		// Expected use not valid.
		exit_error('Invalid expected use!');
	}

	if ($add_to_mailing_list != "" && $mail_list_name != "") {
		// Add user to mail list.
		addMailListMembership($mail_list_name);
	}

	send_file($filename, $filepath, $agreed, $expected_use, $package_id, $mode);

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
		//session_redirect404();
		alertMissingElement($decodedFileNameFromURL);
	}
	$row = db_fetch_array($res);
	$file_id = $row['file_id'];

	// Get FRSFile object.
	$frsFile = frsfile_get_object($file_id);
	if (!$frsFile) {
		// Cannot find file.
		//session_redirect404();
		alertMissingElement("File #: " . $file_id);
	}

	$frsRelease = $frsFile->FRSRelease;
	$frsPackage = $frsRelease->FRSPackage;
	$frsGroup = $frsPackage->Group;

	// Try to find the file specified by 'simtk_filelocation' in frs_file,
	// which is migrated from Simtk 1.0.
	$simtkFileLocation = NULL;
	$simtkFileType = "";
	$sqlSimtkFileLocation = 'SELECT simtk_filelocation, simtk_filetype FROM frs_file WHERE file_id=$1';
	$resSimtkFileLocation = db_query_params($sqlSimtkFileLocation, array($file_id));
	if ($resSimtkFileLocation && db_numrows($resSimtkFileLocation) > 0) {
		$rowSimtkFileLocation = db_fetch_array($resSimtkFileLocation);
		$simtkFileLocation = $rowSimtkFileLocation['simtk_filelocation'];
		$simtkFileType = $rowSimtkFileLocation['simtk_filetype'];
	}

	// Check the simtk_collect_data flag to determine whether logged-in should be checked.
	$simtkCollectData = $frsFile->getCollectData();
	if ($simtkFileType != "" && $simtkFileType != "URL") {
		// Perform this check only on non-URL type links.
		if ($frsPackage->isPublic()) {
			if ($simtkCollectData) {
				if (!session_loggedin()) {
					// Not logged in.
					session_require_perm('frs', $frsGroup->getID(), 'read_public');
				}
			}
		}
		else {
			session_require_perm('frs', $frsGroup->getID(), 'read_private');
		}
	}

	$filename = $frsFile->getName();


	if ($simtkFileLocation != NULL && 
		trim($simtkFileLocation) != "") {

		if ($simtkFileType != "" && $simtkFileType == "URL") {
			// NOTE: Do not use send_file if file type is URL!!!
			$filepath = $simtkFileLocation;
			// Navigate to the URL.
			send_url($filename, $filepath, $file_id);
			break;
		}
		else {
			// Found file! (for backward compatibility)
			$filepath = "/var/lib/gforge/download/" . 
				$frsGroup->getUnixName() . "/" . 
				$simtkFileLocation;
			if (!file_exists($filepath)) {
				// File is not found. Use default gforge file download.
				// NOTE: This case happens when a file from 
				// a previous release is re-used.
				$filepath = forge_get_config('upload_dir') . '/'. 
					$frsGroup->getUnixName() . '/' .
					$frsPackage->getFileName() . '/'.
					$frsRelease->getFileName() . '/' .
					$filename;
			}
		}
	}
	else {
		// Default gforge file download.
		$filepath = forge_get_config('upload_dir') . '/'. 
			$frsGroup->getUnixName() . '/' .
			$frsPackage->getFileName() . '/'.
			$frsRelease->getFileName() . '/' .
			$filename;
	}

	if ($frsFile->getShowAgreement() > 0 && 
		(!isset($agreed) || $agreed != 1)) {
		// Agreed not set.
		exit_error('Please agree to license!!');
	}

	if ($simtkCollectData > 0 && 
		(!isset($expected_use) || strlen(trim($expected_use)) <  7)) {
		// Expected use not valid.
		exit_error('Invalid expected use!');
	}

	if ($add_to_mailing_list != "" && $mail_list_name != "") {
		// Add user to mail list.
		addMailListMembership($mail_list_name);
	}

	send_file($filename, $filepath, $agreed, $expected_use, $file_id);

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
		//session_redirect404();
		alertMissingElement("Release #" . $release_id);
	}
	$frsGroup = $frsPackage->Group;

	// Check the simtk_collect_data flag for all files contained within the package
	// to determine whether any file requires logged-in to be checked.
	$simtkCollectData = getReleaseCollectData($release_id);
	if ($frsPackage->isPublic()) {
		if ($simtkCollectData) {
			if (!session_loggedin()) {
				// Not logged in.
				session_require_perm('frs', $frsGroup->getID(), 'read_public');
			}
		}
	} else {
		session_require_perm('frs', $frsGroup->getID(), 'read_private');
	}

	$filename = $frsPackage->getReleaseZipName($release_id);
	$filepath = $frsPackage->getReleaseZipPath($release_id);
	$dirUpload = forge_get_config('upload_dir');
	$dirGroup = $dirUpload . '/' . $frsGroup->getUnixName();
	if (!is_dir($dirGroup)) {
		@mkdir($dirGroup);
	}
	$dirRelease = $dirGroup . '/' . $frsPackage->getFileName();
	if (!is_dir($dirRelease)) {
		@mkdir($dirRelease);
	}

	if (!file_exists($filepath) && 
		class_exists('ZipArchive')) {

		// The zip file does not exist.
		// Try examining the files contained in each release and generate this zip file.
		// This process is only needed for release migrated from Simtk 1.0 because
		// the release zip file feature was not available for Simtk 1.0.
		// For Simtk 2.0, this zip file will be present.
		$release = $frsPackage->getRelease($release_id);
		// Get files contained in the release.
		$files = $release->getFiles();

		$zipFile = new ZipArchive();
		if ($zipFile->open($filepath, ZIPARCHIVE::OVERWRITE) === true) {

			foreach ($files as $fileObject) {
				$tmpFileId =  $fileObject->getID();

				// Try to find the file specified by 
				// 'simtk_filelocation' in frs_file,
				// which is migrated from Simtk 1.0.
				$simtkFileLocation = NULL;
				$sqlSimtkFileLocation = "SELECT file_size, simtk_filelocation " .
					"FROM frs_file WHERE file_id=$1";
				$resSimtkFileLocation = db_query_params($sqlSimtkFileLocation, 
					array($tmpFileId));

				$tmpFilePath = null;
				if ($resSimtkFileLocation && db_numrows($resSimtkFileLocation) > 0) {
					$rowSimtkFileLocation = db_fetch_array($resSimtkFileLocation);
					$simtkFileLocation = $rowSimtkFileLocation['simtk_filelocation'];
				}

				if ($simtkFileLocation != NULL && 
					trim($simtkFileLocation) != "") {
					// Found File!
					$simtkFileSize = $rowSimtkFileLocation['file_size'];
					// Only include file that has non-zero file size.
					// e.g. URL which point to file outside are not 
					// included in this zip file.
					if ($simtkFileSize > 0) {
						// Found file! (for backward compatibility)
						$tmpFilePath = "/var/lib/gforge/download/" . 
							$frsGroup->getUnixName() . "/" . 
							$simtkFileLocation;
						if (!file_exists($tmpFilePath)) {
							// File is not found. Use default gforge file download.
							// NOTE: This case happens when a file from 
							// a previous release is re-used.
							$tmpFilePath = forge_get_config('upload_dir') . '/'.
								$frsGroup->getUnixName() . '/' .
								$frsPackage->getFileName() . '/'.
								$frsRelease->getFileName() . '/' .
								$fileObject->getName();
						}
					}
				}
				else {
					// Default gforge file download path.
					$tmpFilePath = forge_get_config('upload_dir') . '/'.
						$frsGroup->getUnixName() . '/' .
						$frsPackage->getFileName() . '/'.
						$frsRelease->getFileName() . '/' .
						$fileObject->getName();
				}

				if ($tmpFilePath !== null) {
					// Insert file into the zip file.
					$zipFile->addFile($tmpFilePath, $fileObject->getName());
				}
			}
		}
		$zipFile->close();
	}

	$simtkShowAgreement = getReleaseShowAgreement($release_id);
	if ($simtkShowAgreement > 0 &&
		(!isset($agreed) || $agreed != 1)) {
		// Agreed not set.
		exit_error('Please agree to license!!');
	}

	if ($simtkCollectData > 0 && 
		(!isset($expected_use) || strlen(trim($expected_use)) <  7)) {
		// Expected use not valid.
		exit_error('Invalid expected use!');
	}


	if ($add_to_mailing_list != "" && $mail_list_name != "") {
		// Add user to mail list.
		addMailListMembership($mail_list_name);
	}

	send_file($filename, $filepath, $agreed, $expected_use, $release_id, $mode);

	break;

default:
	exit_error(_('Invalid download mode'));
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
