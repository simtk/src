<?php
/**
 * FusionForge file release system
 *
 * Copyright 2002, Tim Perdue/GForge, LLC
 * Copyright 2009, Roland Mas
 * Copyright 2012-2013, Franck Villaume - TrivialDev
 * Copyright (C) 2012 Alain Peyrat - Alcatel-Lucent
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

require_once $gfcommon.'include/Error.class.php';

/**
 * Factory method which creates a FRSFile from an file id
 *
 * @param	int		$file_id	The file id
 * @param	array|bool	$data		The result array, if it's passed in
 * @return	object		FRSFile object
 */
function &frsfile_get_object($file_id, $data=false) {
	global $FRSFILE_OBJ;
	if (!isset($FRSFILE_OBJ['_'.$file_id.'_'])) {
		if ($data) {
					//the db result handle was passed in
		} else {
			$res = db_query_params ('SELECT * FROM frs_file WHERE file_id=$1',
						array ($file_id)) ;
			if (db_numrows($res)<1 ) {
				$FRSFILE_OBJ['_'.$file_id.'_']=false;
				return false;
			}
			$data = db_fetch_array($res);
		}
		$FRSRelease = frsrelease_get_object($data['release_id']);
		$FRSFILE_OBJ['_'.$file_id.'_']= new FRSFile($FRSRelease,$data['file_id'],$data);
	}
	return $FRSFILE_OBJ['_'.$file_id.'_'];
}

class FRSFile extends Error {

	/**
	 * Associative array of data from db.
	 *
	 * @var	array	$data_array.
	 */
	var $data_array;

	/**
	 * The FRSRelease.
	 *
	 * @var	object	FRSRelease.
	 */
	var $FRSRelease;

	/**
	* Constructor.
	*
	* @param	object		$FRSRelease	The FRSRelease object to which this file is associated.
	* @param	int|bool	$file_id	The file_id.
	* @param	array|bool	$arr		The associative array of data.
	* @return	\FRSFile
	*/
	function __construct(&$FRSRelease, $file_id=false, $arr=false) {
		$this->Error();
		if (!$FRSRelease || !is_object($FRSRelease)) {
			$this->setError(_('Invalid FRS Release Object'));
			return;
		}
		if ($FRSRelease->isError()) {
			$this->setError('FRSFile: '.$FRSRelease->getErrorMessage());
			return;
		}
		$this->FRSRelease =& $FRSRelease;

		if ($file_id) {
			if (!$arr || !is_array($arr)) {
				if (!$this->fetchData($file_id)) {
					return;
				}
			} else {
				$this->data_array =& $arr;
				if ($this->data_array['release_id'] != $this->FRSRelease->getID()) {
					$this->setError('FRSRelease_id in db result does not match FRSRelease Object');
					$this->data_array=null;
					return;
				}
			}
		}
	}

	/**
	 * create - create a new file in this FRSFileRelease/FRSPackage.
	 *
	 * @param	string		$name		The name of this file.
	 * @param	string		$file_location	The location of this file in the local file system.
	 * @param	int		$type_id	The type_id of this file from the frs-file-types table.
	 * @param	int		$processor_id	The processor_id of this file from the frs-processor-types table.
	 * @param	int|bool	$release_time	The release_date of this file in unix time (seconds).
	 * @param	string		$file_desc	The description of this file.
	 * @param	string		$mime_type	The mime type of the file (default: application/octet-stream)
	 * @param	bool		$is_remote	True if file is an URL and not an uploaded file (default: false)
	 * @return	bool		success.
	 */
	function create($name, $file_location, $type_id, $processor_id, $release_time=false, 
		$collect_info, $use_mail_list, $group_list_id, 
		$show_notes, $show_agreement,
		$file_desc="", $doi, $user_id, $url="") {

		if (strlen($name) < 3) {
			$this->setError(_('Name is too short. It must be at least 3 characters.'));
			return false;
		}
		if ($file_location != null && !util_is_valid_filename($name)) {
			$this->setError(_('Filename can only be alphanumeric and “-”, “_”, “+”, “.”, “~” characters.'));
			return false;
		}

		if ($url == "") {
			//
			//	Can't really use is_uploaded_file() or move_uploaded_file()
			//	since we want this to be generalized code
			//	This is potentially exploitable if you do not validate
			//	before calling this function
			//
			if (!is_file($file_location) || !file_exists($file_location)) {
				$this->setError(_('FRSFile Appears to be invalid'));
				return false;
			}
		}

		if (!forge_check_perm ('frs', $this->FRSRelease->FRSPackage->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		//
		//	Filename must be unique in this release
		//
		$resfile = db_query_params ('SELECT filename FROM frs_file WHERE filename=$1 AND release_id=$2',
					    array ($name,
						   $this->FRSRelease->getId())) ;
		if (!$resfile || db_numrows($resfile) > 0) {
			$this->setError(_('That filename already exists in this project space').' '.db_error());
			return false;
		}

		$path_name = forge_get_config('upload_dir').'/'.$this->FRSRelease->FRSPackage->Group->getUnixName();
		if (!is_dir($path_name)) {
			mkdir($path_name, 0755, true);
		} else {
			if ( fileperms($path_name) != 0x4755 ) {
				chmod($path_name, 0755);
			}
		}
		$path_name = $path_name.'/'.$this->FRSRelease->FRSPackage->getFileName();
		if (!is_dir($path_name)) {
			mkdir($path_name, 0755);
		} else {
			if ( fileperms($path_name) != 0x4755 ) {
				chmod($path_name, 0755);
			}
		}
		$path_name = $path_name.'/'.$this->FRSRelease->getFileName();
		if (!is_dir($path_name)) {
			mkdir($path_name, 0755);
		} else {
			if ( fileperms($path_name) != 0x4755 ) {
				chmod($path_name, 0755);
			}
		}

		$newfilelocation = forge_get_config('upload_dir').'/'.
			$this->FRSRelease->FRSPackage->Group->getUnixName().'/'.
			$this->FRSRelease->FRSPackage->getFileName().'/'.
			$this->FRSRelease->getFileName().'/';

		if ($url == "") {
			$ret = rename($file_location, $newfilelocation.$name);
			if (!$ret) {
				$this->setError(_('File cannot be moved to the permanent location')._(': ').$newfilelocation.$name);
				return false;
			}
			$file_size=filesize("$newfilelocation$name");
		}
		else {
			// URL.
			$file_size = 0;
		}

		if (!$release_time) {
			$release_time=time();
		}

		// Do not enter group_list_id if not using mailing list.
		if ($use_mail_list == 0) {
			$group_list_id = 0;
		}

		db_begin();

		if ($url == "") {
			$strInsert = 'INSERT INTO frs_file (' .
				'release_id, filename, release_time, type_id, ' .
				'processor_id, file_size, post_date, simtk_description, ' .
				'simtk_collect_data, simtk_use_mail_list, ' .
				'simtk_group_list_id, simtk_show_notes, simtk_show_agreement, doi, file_user_id ' .
				') VALUES ' .
				'($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15)';
			$arrInsert = array(
				$this->FRSRelease->getId(),
				$name,
				$release_time,
				$type_id,
				$processor_id,
				$file_size,
				time(),
				$file_desc,
				$collect_info, 
				$use_mail_list, 
				$group_list_id,
				$show_notes,
				$show_agreement,
				$doi,
				$user_id);
		}
		else {
			// URL.
			$strInsert = 'INSERT INTO frs_file (' .
				'release_id, filename, release_time, type_id, ' .
				'processor_id, file_size, post_date, simtk_description, ' .
				'simtk_filetype, simtk_filelocation, ' .
				'simtk_collect_data, simtk_use_mail_list, ' .
				'simtk_group_list_id, simtk_show_notes, simtk_show_agreement, doi, file_user_id ' .
				') VALUES ' .
				'($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17)';
			$arrInsert = array(
				$this->FRSRelease->getId(),
				$name,
				$release_time,
				$type_id,
				$processor_id,
				$file_size,
				time(),
				$file_desc,
				"URL",
				$url,
				$collect_info, 
				$use_mail_list, 
				$group_list_id,
				$show_notes,
				$show_agreement,
				$doi,
				$user_id);
		}
		$result = db_query_params($strInsert, $arrInsert);
		if (!$result) {
			$this->setError(_('Error Adding Release: ').db_error());
			db_rollback();
			return false;
		}
		$this->file_id=db_insertid($result,'frs_file','file_id');
		if (!$this->fetchData($this->file_id)) {
			db_rollback();
			return false;
		} else {
			db_commit();
			$this->FRSRelease->FRSPackage->createNewestReleaseFilesAsZip();
		}

		// File released.
		// Send notice to users monitoring this package.
		$this->FRSRelease->sendNotice('UPDATE_RELEASE');

		return true;
	}

	/**
	 * fetchData - re-fetch the data for this FRSFile from the database.
	 *
	 * @param	int	$file_id	The file_id.
	 * @return	boolean	success.
	 */
	function fetchData($file_id) {
		$res = db_query_params('SELECT * FROM frs_file_vw as ffv ' .
			'JOIN (SELECT file_id, simtk_description, ' .
			'simtk_collect_data, simtk_use_mail_list, simtk_group_list_id, ' .
			'simtk_filetype, simtk_filelocation, simtk_show_notes, simtk_show_agreement, ' .
			'simtk_rank, simtk_filename_header, doi, doi_identifier, file_user_id ' .
			'FROM frs_file) AS ff ' .
			'ON ffv.file_id=ff.file_id ' .
			'WHERE ffv.file_id=$1 AND release_id=$2',
			array(
				$file_id,
				$this->FRSRelease->getID()
			)
		);
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('Invalid file_id'));
			return false;
		}
		$this->data_array = db_fetch_array($res);
		db_free_result($res);
		return true;
	}

	/**
	 * getFRSRelease - get the FRSRelease object this file is associated with.
	 *
	 * @return	object	The FRSRelease object.
	 */
	function &getFRSRelease() {
		return $this->FRSRelease;
	}

	/**
	 * getID - get this file_id.
	 *
	 * @return	int	The id of this file.
	 */
	function getID() {
		return $this->data_array['file_id'];
	}

	/**
	 * getName - get the name of this file.
	 *
	 * @return	string	The name of this file.
	 */
	function getName() {
		return $this->data_array['filename'];
	}

	/**
	 * getSize - get the size of this file.
	 *
	 * @return	int	The size.
	 */
	function getSize() {
		return $this->data_array['file_size'];
	}

	/**
	 * getTypeID - the filetype id.
	 *
	 * @return	int	the filetype id.
	 */
	function getTypeID() {
		return $this->data_array['type_id'];
	}

	/**
	 * getTypeName - the filetype name.
	 *
	 * @return	string	The filetype name.
	 */
	function getFileType() {
		return $this->data_array['simtk_filetype'];
	}

	function getFrsFileFileType() {
		return $this->data_array['simtk_filetype'];
	}

	/**
	 * getProcessorID - the processor id.
	 *
	 * @return	int	the processor id.
	 */
	function getProcessorID() {
		return $this->data_array['processor_id'];
	}

	/**
	 * getProcessor - the processor name.
	 *
	 * @return	string	The processor name.
	 */
	function getProcessor() {
		return $this->data_array['processor'];
	}

	/**
	 * getDownloads - the number of downloads.
	 *
	 * @return	int	The number of downloads.
	 */
	function getDownloads() {
		return $this->data_array['downloads'];
	}

	/**
	 * getReleaseTime - get the releasetime of this file.
	 *
	 * @return	int	The release time in unix time.
	 */
	function getReleaseTime() {
		return $this->data_array['release_time'];
	}

	/**
	 * getPostDate - get the post date of this file.
	 *
	 * @return	int	The post date in unix time.
	 */
	function getPostDate() {
		return $this->data_array['post_date'];
	}

	/**
	 * getDesc - get the description of this file.
	 *
	 * @return	string	The description
	 */
	function getDesc() {
		return $this->data_array['simtk_description'];
	}

	/**
	 *  getLocalFilename - get the name used to store
	 *                     this file in local filesystem.
	 *
	 *  @return string  The name of this file.
	 */
	function getLocalFilename() {
		if (empty($this->data_array['simtk_filelocation'])) {
			return $this->data_array['filename'];
		}
		else {
			return $this->data_array['simtk_filelocation'];
		}
	}

	/**
	 * getCollectData - Collect data for this file?
	 *
	 * @return	int	Collect data for this file?
	 */
	function getCollectData() {
		return $this->data_array['simtk_collect_data'];
	}

	/**
	 * getRank - File rank
	 *
	 * @return	int	FRSFile file rank
	 */
	function getRank() {
		return $this->data_array['simtk_rank'];
	}

	/**
	 * getFilenameHeader - Filename header
	 *
	 * @return	string	FRSFile filename header
	 */
	function getFilenameHeader() {
		return $this->data_array['simtk_filename_header'];
	}

	/**
	 * getShowAgreement - Show download agreement for this file?
	 *
	 * @return	int	Show download agreement for this file?
	 */
	function getShowAgreement() {
		return $this->data_array['simtk_show_agreement'];
	}

	/**
	 * getShowNotes - Show package download notes for this file?
	 *
	 * @return	int	Show download notes for this file?
	 */
	function getShowNotes() {
		return $this->data_array['simtk_show_notes'];
	}

	/**
	 * getUseMailList - Use mail list for this file?
	 *
	 * @return	string	Use mail list for this file?
	 */
	function getUseMailList() {
		return $this->data_array['simtk_use_mail_list'];
	}

	/**
	 * getGroupListId - Get mail list group id for this file.
	 *
	 * @return	string	Use mail list for this file?
	 */
	function getGroupListId() {
		return $this->data_array['simtk_group_list_id'];
	}

	/**
	 * isURL - Is this file a URL?
	 *
	 * @return	true	File is URL; false otherwise.
	 */
	function isURL() {
		if ($this->data_array['simtk_filetype'] == "URL") {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * getURL - Get the URL if this file is a URL.
	 *
	 * @return	string	The URL if this file is a URL; "" otherwise.
	 */
	function getURL() {
		if ($this->data_array['simtk_filetype'] == "URL") {
			return $this->data_array['simtk_filelocation'];
		}
		else {
			return "";
		}
	}

	function getDOI() {
	   return $this->data_array['doi'];
	}
	
	function isDOI() {
	   if ($this->data_array['doi']) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * delete - Delete this file from the database and file system.
	 *
	 * @return	boolean	success.
	 */
	function delete($isDeleteRelease=false) {
		if (!forge_check_perm ('frs', $this->FRSRelease->FRSPackage->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		$fileNewDirStructure = forge_get_config('upload_dir').'/'.
			$this->FRSRelease->FRSPackage->Group->getUnixName() . '/' .
			$this->FRSRelease->FRSPackage->getName().'/'.
			$this->FRSRelease->getName().'/'.
			$this->getLocalFilename();

		$fileOldDirStructure = forge_get_config('upload_dir').'/'.
			$this->FRSRelease->FRSPackage->Group->getUnixName() . '/' .
			$this->getLocalFilename();

		if (file_exists($fileOldDirStructure)) {
			// File migrated from v1.0. Delete using simtk_filelocation.
			unlink($fileOldDirStructure);
		}
		else if (file_exists($fileNewDirStructure)) {
			// File in v2.0. Delete using filename.
			unlink($fileNewDirStructure);
		}

		$result = db_query_params ('DELETE FROM frs_file WHERE file_id=$1',
					   array ($this->getID())) ;
		if (!$result || db_affected_rows($result) < 1) {
			$this->setError("frsDeleteFile()::2 ".db_error());
			return false;
		} else {
			db_query_params ('DELETE FROM frs_dlstats_file WHERE file_id=$1',
						array ($this->getID())) ;
			db_query_params ('DELETE FROM frs_dlstats_filetotal_agg WHERE file_id=$1',
						array ($this->getID())) ;
			$this->FRSRelease->FRSPackage->createNewestReleaseFilesAsZip();
		}

		if (!$isDeleteRelease) {
			// File deleted, but not deleting release.
			// Send notice to users monitoring this package.
			// (Otherwise, release deletion has sendNotice() invocation already.
			// Hence, no need to sendNotice().)
			$this->FRSRelease->sendNotice('UPDATE_RELEASE');
		}

		return true;
	}

	/**
	 * update - update an existing file in this FRSFileRelease/FRSPackage.
	 *
	 * @param	int		$type_id	The type_id of this file from the frs-file-types table.
	 * @param	int		$processor_id	The processor_id of this file from the frs-processor-types table.
	 * @param	int		$release_time	The release_date of this file in unix time (seconds).
	 * @param	int|bool	$release_id	The release_id of the release this file belongs to (if not set, defaults to the release id of this file).
	 * @param	string		$file_desc	The description of this file.
	 * @return	boolean		success.
	 */
	function update($type_id, $processor_id, $release_time, $release_id=false, 
		$collect_info, $use_mail_list, $group_list_id, $userfile, 
		$show_notes, $show_agreement,
		$file_desc='', $disp_name="", $doi, $user_id, $url="") {

		if (!forge_check_perm ('frs', $this->FRSRelease->FRSPackage->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		// For file not changed.
		$file_size = -1;

		if ($userfile && is_uploaded_file($userfile['tmp_name']) && 
			util_is_valid_filename($userfile['name'])) {

			$file_location = $userfile['tmp_name'];
			$userfile_name = $userfile['name'] ;
		}
		elseif ($userfile && $userfile['error'] != UPLOAD_ERR_OK &&
			$userfile['error'] != UPLOAD_ERR_NO_FILE) {

			switch ($userfile['error']) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return 'The uploaded file exceeds the maximum file size. ' .
					'Contact to the site admin to upload this big file, ' .
					'or use an alternate upload method (if available).';
			case UPLOAD_ERR_PARTIAL:
				return 'The uploaded file was only partially uploaded.';
			default:
				return 'Unknown file upload error.';
			}
		}

		if ($url == "") {
			// Selected a file.
			if ($userfile && $userfile['error'] != UPLOAD_ERR_NO_FILE &&
				$userfile_name != "") {

				// Replacement userfile selected.
				if (!is_file($file_location) || !file_exists($file_location)) {
					$this->setError(_('FRSFile Appears to be invalid'));
					return false;
				}
				if ($disp_name == "") {
					// Set display name to be the file name if not specified.
					// Otherwise, use existing display name.
					$disp_name = $userfile_name;
				}
			}
			// Set simtk_file_type.
			$simtk_file_type = "";

			// Check Display Name.
			// NOTE: File may be changed from URL type to FILE.
			// Hence, need to verify validity of the display name here.
			if (strlen($disp_name) < 3) {
				$this->setError(_('Name is too short. It must be at least 3 characters.'));
				return false;
			}
			if (!util_is_valid_filename($disp_name)) {
				$this->setError(_('Filename can only be alphanumeric and “-”, “_”, “+”, “.”, “~” characters.'));
				return false;
			}

		}
		else {
			// Set simtk_file_type.
			$simtk_file_type = "URL";
			// Set file_size.
			$file_size = 0;
		}

		if ($disp_name != $this->getName()) {
			// Display name must be unique in this release.
			$resfile = db_query_params('SELECT filename FROM frs_file ' .
				'WHERE filename=$1 AND release_id=$2',
				array ($disp_name, $this->FRSRelease->getId()));
			if (!$resfile || db_numrows($resfile) > 0) {
				$this->setError(_('That filename already exists in this project space') .
					' ' . db_error());
				return false;
			}
		}

		$path_name = forge_get_config('upload_dir') . '/' .
			$this->FRSRelease->FRSPackage->Group->getUnixName();
		if (!is_dir($path_name)) {
			mkdir($path_name, 0755, true);
		}
		else {
			if (fileperms($path_name) != 0x4755 ) {
				chmod($path_name, 0755);
			}
		}
		$path_name = $path_name . '/' .
			$this->FRSRelease->FRSPackage->getFileName();
		if (!is_dir($path_name)) {
			mkdir($path_name, 0755);
		}
		else {
			if (fileperms($path_name) != 0x4755 ) {
				chmod($path_name, 0755);
			}
		}
		$path_name = $path_name . '/' .
			$this->FRSRelease->getFileName();
		if (!is_dir($path_name)) {
			mkdir($path_name, 0755);
		}
		else {
			if (fileperms($path_name) != 0x4755 ) {
				chmod($path_name, 0755);
			}
		}

		$newfilelocation = forge_get_config('upload_dir') . '/' .
			$this->FRSRelease->FRSPackage->Group->getUnixName() . '/' .
			$this->FRSRelease->FRSPackage->getFileName() . '/' .
			$this->FRSRelease->getFileName() . '/';

		if ($simtk_file_type != "URL") {
			if (isset($userfile_name) && $userfile_name != "") {
				// Selected a file.
				// Move file to final location.
				$ret = rename($file_location, $newfilelocation.$disp_name);
				if (!$ret) {
					$this->setError(_('File cannot be moved to the permanent location') .
						_(': ') . $newfilelocation.$disp_name);
					return false;
				}
				// Set file_size.
				$file_size = filesize("$newfilelocation$disp_name");
			}
			else {
				// File not selected.
				if ($disp_name != $this->getName()) {
					// The display name has changed.
					$old_file_location = forge_get_config('upload_dir') . '/' .
						$this->FRSRelease->FRSPackage->Group->getUnixName() . '/' .
						$this->FRSRelease->FRSPackage->getFileName() . '/' .
						$this->FRSRelease->getFileName().'/'.
						$this->data_array['filename'];
					$new_file_location = forge_get_config('upload_dir') . '/' .
						$this->FRSRelease->FRSPackage->Group->getUnixName() . '/' .
						$this->FRSRelease->FRSPackage->getFileName() . '/' .
						$this->FRSRelease->getFileName().'/'.
						$disp_name;

					// NOTE: Check for SimTK 2.0 file presence first
					// before applying the renaming operation.
					// If a released file is in the SimTK 1.0 directory structure
					// and it is renamed, there is no need to move (rename) the file.
					if (file_exists($old_file_location)) {
						$ret = rename($old_file_location, $new_file_location);
					}
				}
			}
		}

		if (!$release_time) {
			$release_time=time();
		}

		// Sanity checks
		if ($release_id) {
			// Check that the new FRSRelease id exists
			if ($FRSRelease=frsrelease_get_object($release_id)) {
				// Check that the new FRSRelease id belongs to the group of this FRSFile.
				if ($FRSRelease->FRSPackage->Group->getID() != 
					$this->FRSRelease->FRSPackage->Group->getID()) {

					$this->setError(_('No Valid Group Object'));
					return false;
				}
			}
			else {
				$this->setError(_('Invalid FRS Release Object'));
				return false;
			}
		}
		else {
			// If release_id is not set, default to the release id of this file.
			$release_id = $this->FRSRelease->getID();
		}

		// Do not enter group_list_id if not using mailing list.
		if ($use_mail_list == 0) {
			$group_list_id = 0;
		}

		// Update database
		db_begin();

		if ($file_size != -1) {
			$strUpdate = 'UPDATE frs_file SET ' .
				'type_id=$1, ' .
				'processor_id=$2, ' .
				'release_time=$3, ' .
				'post_date=$4, ' .
				'release_id=$5, ' .
				'simtk_description=$6, ' .
				'filename=$7, ' .
				'file_size=$8, ' .
				'simtk_filetype=$9, ' .
				'simtk_filelocation=$10, ' .
				'simtk_collect_data=$11, ' .
				'simtk_use_mail_list=$12, ' .
				'simtk_group_list_id=$13, ' .
				'simtk_show_notes=$14, ' .
				'simtk_show_agreement=$15, ' .
				'doi=$16, ' .
				'file_user_id=$17 ' .
				'WHERE file_id=$18';
			$arrUpdate = array(
				$type_id,
				$processor_id,
				$release_time,
				time(),
				$release_id,
				$file_desc,
				$disp_name,
				$file_size,
				$simtk_file_type,
				$url,
				$collect_info, 
				$use_mail_list, 
				$group_list_id,
				$show_notes,
				$show_agreement,
				$doi,
				$user_id,
				$this->getID()
			);
		}
		else {
			// File not uploaded. Do not update simtk_filelocation.
			// Otherwise, file location will be lost.
			$strUpdate = 'UPDATE frs_file SET ' .
				'type_id=$1, ' .
				'processor_id=$2, ' .
				'release_time=$3, ' .
				'post_date=$4, ' .
				'release_id=$5, ' .
				'simtk_description=$6, ' .
				'filename=$7, ' .
				'simtk_filetype=$8, ' .
				'simtk_collect_data=$9, ' .
				'simtk_use_mail_list=$10, ' .
				'simtk_group_list_id=$11, ' .
				'simtk_show_notes=$12, ' .
				'simtk_show_agreement=$13, ' .
				'doi=$14, ' .
				'file_user_id=$15 ' .
				'WHERE file_id=$16';
			$arrUpdate = array(
				$type_id,
				$processor_id,
				$release_time,
				time(),
				$release_id,
				$file_desc,
				$disp_name,
				$simtk_file_type,
				$collect_info, 
				$use_mail_list, 
				$group_list_id,
				$show_notes,
				$show_agreement,
				$doi,
				$user_id,
				$this->getID()
			);
		}

		$res = db_query_params($strUpdate, $arrUpdate);

		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('Error On Update: %s'), db_error()));
			db_rollback();
			return false;
		}

		// Move physically file if needed
		if ($release_id != $this->FRSRelease->getID()) {
			$old_file_location = forge_get_config('upload_dir') . '/' .
				$this->FRSRelease->FRSPackage->Group->getUnixName() . '/' .
				$this->FRSRelease->FRSPackage->getFileName() . '/' .
				$this->FRSRelease->getFileName() . '/' .
				$this->data_array['filename'];
			$new_file_location = forge_get_config('upload_dir') . '/' .
				$FRSRelease->FRSPackage->Group->getUnixName() . '/' .
				$FRSRelease->FRSPackage->getFileName() . '/' .
				$FRSRelease->getFileName().'/'.
				$this->data_array['filename'];
			if (file_exists($new_file_location)) {
				$this->setError(_('That filename already exists in this project space'));

				db_rollback();

				return false;
			}
			$ret = rename($old_file_location, $new_file_location);
			if (!$ret) {
				$this->setError(_('File cannot be moved to the permanent location')
					. _(': ') . $new_file_location);

				db_rollback();

				return false;
			}
		}

		db_commit();

		// File updated.
		// Send notice to users monitoring this package.
		$this->FRSRelease->sendNotice('UPDATE_RELEASE');

		$this->FRSRelease->FRSPackage->createNewestReleaseFilesAsZip();

		return true;
	}


	/*
	 *  copyToRelease - makes a copy of the designated file in another release
	 *
	 *  @param  int  The database ID of the release to receive a copy of this file
	 *  @param  newFile  true if the function should copy the file as a new file in the filesystem, false if the new file should point to the same file as the old one in the flesystem
	 */
	function copyToRelease($newRelease, $newFile) {
		if (!$this->getID()) {
			$this->setError("No release ID given to copyToRelease()");
			return false;
		}

		if (!forge_check_perm ('frs', $this->FRSRelease->FRSPackage->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		// Used as default (e.g. for URL.)
		$newFilename = $this->getLocalFilename();
		if ($newFile && !$this->isURL()) {
			// File directory from v1.0.
			$filedir = forge_get_config('upload_dir') . '/' . 
				$this->FRSRelease->FRSPackage->Group->getUnixName() . '/';

			// New file directory in v2.0 of this release.
			$fromFileDir = forge_get_config('upload_dir') . '/' . 
				$this->FRSRelease->FRSPackage->Group->getUnixName() . '/' .
				$this->FRSRelease->FRSPackage->getName() . '/' .
				$this->FRSRelease->getName() . '/';

			// New file directory in v.2.0 of release to copy to.
			$toFileDir = forge_get_config('upload_dir') . '/' . 
				$newRelease->FRSPackage->Group->getUnixName() . '/' .
				$newRelease->FRSPackage->getName() . '/' .
				$newRelease->getName() . '/';

			if (file_exists($filedir . $this->getLocalFilename())) {
				// Migrated file from v1.0; simtk_filelocation is used here.
				// Get a unique UUID for the filesystem
				while ($newFilename == "" || file_exists($filedir . $newFilename)) {
					// NOTE: Need to "api-get install uuid-runtime" in Debian first.
					$newFilename = exec('uuidgen');
				}
				system("cp $filedir" . $this->getLocalFilename() . " $filedir$newFilename");
			}
			else if (file_exists($fromFileDir . $this->getLocalFilename())) { 
				// Try file directory in v2.0; filename is used here.
				system("cp $fromFileDir" . $this->getLocalFilename() . 
					" $toFileDir" . $this->getLocalFilename());

				// Note: $newFilename stays as "" here, which will be populated into
				// simtk_filelocation of the release to copy to, indicating to
				// use filename, but not simtk_filelocation for the release to copy to.
			}
		}

		db_begin();

		$res = db_query_params(
			"INSERT INTO frs_file (
				filename,
				release_id,
				type_id,
				processor_id,
				release_time,
				file_size,
				post_date,
				simtk_description,
				simtk_show_agreement,
				simtk_collect_data,
				simtk_filetype,
				simtk_show_notes,
				simtk_filelocation,
				simtk_rank,
				simtk_filename_header,
				simtk_group_list_id,
				simtk_use_mail_list
			) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17)",
			array(
				$this->getName(),
				$newRelease->getID(),
				$this->getTypeID(),
				$this->getProcessorID(),
				$this->getReleaseTime(),
				$this->getSize(),
				$this->getPostDate(),
				$this->getDesc(),
				$this->getShowAgreement(),
				$this->getCollectData(),
				$this->getFrsFileFileType(),
				$this->getShowNotes(),
				$newFilename,
				$this->getRank(),
				$this->getFilenameHeader(),
				$this->getGroupListId(),
				$this->getUseMailList()
			)
		);

		if ( !$res || db_affected_rows( $res ) != 1 ) {
			$this->setError("FRSFile::copyToRelease() error on database insert: " . db_error());
			return false;
		}

		db_commit();

		return true;
	}
	
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
