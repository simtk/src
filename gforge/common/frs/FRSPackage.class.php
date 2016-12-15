<?php
/**
 * FusionForge file release system
 *
 * Copyright 2002, Tim Perdue/GForge, LLC
 * Copyright 2009, Roland Mas
 * Copyright (C) 2011-2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright 2012-2013, Franck Villaume - TrivialDev
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
require_once $gfcommon.'include/image.php';
require_once $gfcommon.'frs/FRSRelease.class.php';

/**
 * @param Group $Group
 * @return array
 */
function get_frs_packages($Group) {
	$ps = array();
	$res = db_query_params ('SELECT * FROM frs_package WHERE group_id=$1',
				array ($Group->getID())) ;
	if (db_numrows($res) > 0) {
		while($arr = db_fetch_array($res)) {
			$ps[]=new FRSPackage($Group, $arr['package_id'], $arr);
		}
	}
	return $ps;
}

/**
 * Gets a FRSPackage object from the given package id
 *
 * @param	array	$package_id	the DB handle if passed in (optional)
 * @param	bool	$data
 * @return	object	the FRSPackage object
 */
function frspackage_get_object($package_id, $data=false) {
	global $FRSPACKAGE_OBJ;
	if (!isset($FRSPACKAGE_OBJ['_'.$package_id.'_'])) {
		if ($data) {
			//the db result handle was passed in
		} else {
			$res = db_query_params ('SELECT * FROM frs_package WHERE package_id=$1',
						array ($package_id)) ;
			if (db_numrows($res)<1) {
				return false;
			}
			$data = db_fetch_array($res);
		}
		$Group = group_get_object($data['group_id']);
		$FRSPACKAGE_OBJ['_'.$package_id.'_']= new FRSPackage($Group,$data['package_id'],$data);
	}
	return $FRSPACKAGE_OBJ['_'.$package_id.'_'];
}

class FRSPackage extends Error {

	/**
	 * Associative array of data from db.
	 *
	 * @var	array	$data_array.
	 */
	var $data_array;
	var $package_releases;

	/**
	 * The Group object.
	 *
	 * @var	object	$Group.
	 */
	var $Group;

	/**
	 * Constructor.
	 *
	 * @param	$Group
	 * @param	bool	$package_id
	 * @param	bool	$arr
	 * @internal	param	\The $object Group object to which this FRSPackage is associated.
	 * @internal	param	\The $int package_id.
	 * @internal	param	\The $array associative array of data.
	 * @return	\FRSPackage
	 */
	function __construct(&$Group, $package_id = false, $arr = false) {
		$this->Error();
		if (!$Group || !is_object($Group)) {
			$this->setError(_('No Valid Group Object'));
			return;
		}
		if ($Group->isError()) {
			$this->setError('FRSPackage: '.$Group->getErrorMessage());
			return;
		}
		$this->Group =& $Group;

		if ($package_id) {
			if (!$arr || !is_array($arr)) {
				if (!$this->fetchData($package_id)) {
					return;
				}
			} else {
				$this->data_array =& $arr;
				if ($this->data_array['group_id'] != $this->Group->getID()) {
					$this->setError(_('group_id in db result does not match Group Object'));
					$this->data_array = null;
					return;
				}
//
//	Add an is_public check here
//
			}
		}
	}

	/**
	 * create - create a new FRSPackage in the database.
	 *
	 * @param	$name
	 * @param	int	$is_public
	 * @internal	param	\The $string name of this package.
	 * @internal	param	\Whether $boolean it's public or not. 1=public 0=private.
	 * @return	boolean	success.
	 */
	function create($name, $status=1, $is_public = 1, $package_desc='',
		$packageLogoFilePath='', $packageLogoFileType='', $packageNotes='', 
		$packageCustomAgreement='', $packageUseAgreement=0,
		$packageOpenLatest=0, $packageShowDownloadButton=0) {

		if (strlen($name) < 3) {
			$this->setError(_('FRSPackage Name Must Be At Least 3 Characters'));
			return false;
		}
		if (!util_is_valid_filename($name)) {
			$this->setError(_('Package Name can only be alphanumeric'));
		}
		if (!forge_check_perm ('frs', $this->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		$res = db_query_params(
			'SELECT * FROM frs_package WHERE group_id=$1 AND name=$2', 
			array(
				$this->Group->getID(), 
				htmlspecialchars($name)
			)
		);
		if (db_numrows($res)) {
			$this->setError(_('Error Adding Package: Name Already Exists'));
			return false;
		}

		// Put the logo someplace permanent.
		$the_logo_file_type = "";
                if ($packageLogoFilePath && !empty($packageLogoFilePath)) {

			// Get filename, stripping directories in front.
			$idx = strripos($packageLogoFilePath, "/");
			$packageLogoFileName = $packageLogoFilePath;
			if ($idx !== false) {
				$packageLogoFileName = substr($packageLogoFileName, $idx + 1);
			}
			$abs_logo_file = "/usr/share/gforge/www/logos-frs/" . $packageLogoFileName;

			// Validate picture file type.
			$the_logo_file_type = "";
			if ($packageLogoFileType != '') {
				$the_logo_file_type = $this->validatePictureFileImageType($packageLogoFileType);
				if ($the_logo_file_type === false) {
					$this->setError('ERROR: Invalid logo file type');
					return false;
				}
			}

			if (!imageUploaded($packageLogoFilePath, $abs_logo_file, false)) {
				$this->setError('ERROR: Could not save logo file');
				return false;
			}
		}

		if ($packageUseAgreement == 0) {
			// Use agreement is "None". Do not fill in custom agreement.
			$packageCustomAgreement = '';
		}

		db_begin();

		if ($packageLogoFilePath == '') {
			$strCreateFrs = 'INSERT INTO frs_package 
				(group_id, 
				name, 
				status_id, 
				is_public, 
				simtk_description, 
				simtk_download_notes,
				simtk_custom_agreement,
				simtk_use_agreement,
				simtk_openlatest,
				simtk_show_download_button)
				VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10)';

			$arrCreateFrs = array(
				$this->Group->getId(),
				htmlspecialchars($name),
				$status,
				$is_public,
				htmlspecialchars($package_desc),
				htmlspecialchars($packageNotes),
				htmlspecialchars($packageCustomAgreement),
				$packageUseAgreement,
				$packageOpenLatest,
				$packageShowDownloadButton
			);
		}
		else {
			$strCreateFrs = 'INSERT INTO frs_package (
				group_id,
				name, 
				status_id, 
				is_public, 
				simtk_description, 
				simtk_logo_file, 
				simtk_logo_type,
				simtk_download_notes,
				simtk_openlatest,
				simtk_show_download_button) 
				VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10)';

			$arrCreateFrs = array(
				$this->Group->getId(),
				htmlspecialchars($name),
				$status,
				$is_public,
				htmlspecialchars($package_desc),
				$packageLogoFileName,
				$the_logo_file_type,
				htmlspecialchars($packageNotes),
				$packageOpenLatest,
				$packageShowDownloadButton
			);
		}

		$res = db_query_params($strCreateFrs, $arrCreateFrs);

		if (!$res) {
			$this->setError(_('Error Adding Package: ').db_error());
			db_rollback();
			return false;
		}
		$this->package_id=db_insertid($res,'frs_package','package_id');
		if (!$this->fetchData($this->package_id)) {
			db_rollback();
			return false;
		} else {

			//make groupdir if it doesn't exist
			$groupdir = forge_get_config('upload_dir').'/'.$this->Group->getUnixName();
			if (!is_dir($groupdir)) {
				@mkdir($groupdir);
			}

			$newdirlocation = $groupdir.'/'.$this->getFileName();
			if (!is_dir($newdirlocation)) {
				@mkdir($newdirlocation);
			}

			// this 2 should normally silently fail (because it's called with the apache user) but if it's root calling the create() method, then the owner and group for the directory should be changed
			@chown($newdirlocation, forge_get_config('apache_user'));
			@chgrp($newdirlocation, forge_get_config('apache_group'));
			db_commit();
			return true;
		}
	}

	/**
	 * fetchData - re-fetch the data for this Package from the database.
	 *
	 * @param	int	$package_id	The package_id.
	 * @return	boolean	success.
	 */
	function fetchData($package_id) {
		$res = db_query_params ('SELECT * FROM frs_package WHERE package_id=$1 AND group_id=$2',
					array ($package_id,
					       $this->Group->getID())) ;
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('Invalid package_id'));
			return false;
		}
		$this->data_array = db_fetch_array($res);
		db_free_result($res);
		return true;
	}

	/**
	 * getGroup - get the Group object this FRSPackage is associated with.
	 *
	 * @return	object	The Group object.
	 */
	function &getGroup() {
		return $this->Group;
	}

	/**
	 * getID - get this package_id.
	 *
	 * @return	int	The id of this package.
	 */
	function getID() {
		return $this->data_array['package_id'];
	}

	/**
	 * getName - get the name of this package.
	 *
	 * @return	string	The name of this package.
	 */
	function getName() {
		return $this->data_array['name'];
	}

	/**
	 * getDesc - get the description of this package.
	 *
	 * @return	string	The description of this package.
	 */
	function getDesc() {
		return $this->data_array['simtk_description'];
	}

	/**
	 * getDownloadNotes - get the download notes of this package.
	 *
	 * @return	string	The download notes of this package.
	 */
	function getDownloadNotes() {
		return $this->data_array['simtk_download_notes'];
	}

	/**
	 * getUseAgreement - get the use agreement of this package.
	 *
	 * @return	string	The use agreement of this package.
	 */
	function getUseAgreement() {
		return $this->data_array['simtk_use_agreement'];
	}

	/**
	 * getCustomAgreement - get the custom agreement of this package.
	 *
	 * @return	string	The custom agreement of this package.
	 */
	function getCustomAgreement() {
		return $this->data_array['simtk_custom_agreement'];
	}

	/**
	 * getLogoFile - get the logo file of this package.
	 *
	 * @return	string	The logo file of this package.
	 */
	function getLogoFile() {
		return $this->data_array['simtk_logo_file'];
	}

	/**
	 * isOpenLatest - open latest release in this package?
	 *
	 * @return	int	open latest release in this package.
	 */
	function isOpenLatest() {
		return $this->data_array['simtk_openlatest'];
	}

	/**
	 * isShowDownloadButton - show download button for this package?
	 *
	 * @return	int	show download button for this package.
	 */
	function isShowDownloadButton() {
		return $this->data_array['simtk_show_download_button'];
	}

	/**
	 * getFileName - get the filename of this package.
	 *
	 * @return	string	The name of this package.
	 */
	function getFileName() {
		return util_secure_filename($this->data_array['name']);
	}

	/**
	 * getStatus - get the status of this package.
	 *
	 * @return	int	The status.
	 */
	function getStatus() {
		return $this->data_array['status_id'];
	}

	/**
	 * isPublic - whether non-group-members can view.
	 *
	 * @return	boolean	is_public.
	 */
	function isPublic() {
		return $this->data_array['is_public'];
	}

	/**
	 * setMonitor - Add the current user to the list of people monitoring this package.
	 *
	 * @return	boolean	success.
	 */
	function setMonitor() {
		if (!session_loggedin()) {
			$this->setError(_('You can only monitor if you are logged in.'));
			return false;
		}
		$result = db_query_params ('SELECT * FROM filemodule_monitor WHERE user_id=$1 AND filemodule_id=$2',
					   array (user_getid(),
						  $this->getID())) ;

		if (!$result || db_numrows($result) < 1) {
			/*
				User is not already monitoring thread, so
				insert a row so monitoring can begin
			*/
			$result = db_query_params ('INSERT INTO filemodule_monitor (filemodule_id,user_id) VALUES ($1,$2)',
						   array ($this->getID(),
							  user_getid()));

			if (!$result) {
				$this->setError(_('Unable To Add Monitor')._(': ').db_error());
				return false;
			}

		}
		return true;
	}

	/**
	 * stopMonitor - Remove the current user from the list of people monitoring this package.
	 *
	 * @return	boolean	success.
	 */
	function stopMonitor() {
		if (!session_loggedin()) {
			$this->setError(_('You can only monitor if you are logged in.'));
			return false;
		}
		return db_query_params ('DELETE FROM filemodule_monitor WHERE user_id=$1 AND filemodule_id=$2',
					array (user_getid(),
					       $this->getID())) ;
	}

	/**
	 * getMonitorCount - Get the count of people monitoring this package
	 *
	 * @return	int	the count
	 */
	function getMonitorCount() {
		$res = db_result(db_query_params ('select count(*) as count from filemodule_monitor where filemodule_id=$1',
						  array ($this->getID())), 0, 0);
		if ($res < 0) {
			$this->setError(_('Error On querying monitor count: ').db_error());
			return false;
		}
		return $res;
	}

	/**
	 * isMonitoring - Is the current user in the list of people monitoring this package.
	 *
	 * @return	boolean	is_monitoring.
	 */
	function isMonitoring() {
		if (!session_loggedin()) {
			return false;
		}

		$result = db_query_params ('SELECT * FROM filemodule_monitor WHERE user_id=$1 AND filemodule_id=$2',
					   array (user_getid(),
						  $this->getID())) ;

		if (!$result || db_numrows($result) < 1) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * getMonitorIDs - Return an array of user_id's of the list of people monitoring this package.
	 *
	 * @return	array	The array of user_id's.
	 */
	function &getMonitorIDs() {
		$res = db_query_params ('SELECT user_id FROM filemodule_monitor WHERE filemodule_id=$1',
					array ($this->getID())) ;
		return util_result_column_to_array($res);
	}

	/**
	 * update - update an FRSPackage in the database.
	 *
	 * @param	string	$name		The name of this package.
	 * @param	int	$status		The status_id of this package from frs_status table.
	 * @param	int	$is_public	public or private : 1 or 0
	 * @return	boolean success.
	 */
	function update($name, $status, $is_public = 1, $package_desc='',
		$packageLogoFilePath='', $packageLogoFileType='', $packageNotes='',
		$packageCustomAgreement='', $packageUseAgreement=0,
		$packageOpenLatest=0, $packageShowDownloadButton=0) {

		if (strlen($name) < 3) {
			$this->setError(_('FRSPackage Name Must Be At Least 3 Characters'));
			return false;
		}

		if (!forge_check_perm('frs', $this->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}
		if ($this->getName() != htmlspecialchars($name)) {
			$res = db_query_params(
				'SELECT * FROM frs_package WHERE group_id=$1 AND name=$2',
				array(
					$this->Group->getID(),
					htmlspecialchars($name)
				)
			);
			if (db_numrows($res)) {
				$this->setError(_('Error Updating Package: Name Already Exists'));
				return false;
			}
		}


		// Put the logo someplace permanent.
		$the_logo_file_type = "";
                if ($packageLogoFilePath && !empty($packageLogoFilePath)) {

			// Get filename, stripping directories in front.
			$idx = strripos($packageLogoFilePath, "/");
			$packageLogoFileName = $packageLogoFilePath;
			if ($idx !== false) {
				$packageLogoFileName = substr($packageLogoFileName, $idx + 1);
			}
			$abs_logo_file = "/usr/share/gforge/www/logos-frs/" . $packageLogoFileName;

			// Validate picture file type.
			$the_logo_file_type = "";
			if ($packageLogoFileType != '') {
				$the_logo_file_type = $this->validatePictureFileImageType($packageLogoFileType);
				if ($the_logo_file_type === false) {
					$this->setError('ERROR: Invalid logo file type');
					return false;
				}
			}

			if (!imageUploaded($packageLogoFilePath, $abs_logo_file, false)) {
				$this->setError('ERROR: Could not save logo file');
				return false;
			}
		}

		if ($packageUseAgreement == 0) {
			// Use agreement is "None". Do not fill in custom agreement.
			$packageCustomAgreement = '';
		}

		db_begin();

		if ($packageLogoFilePath == '') {
			$strUpdateFrs = 'UPDATE frs_package SET 
				name=$1, 
				status_id=$2, 
				is_public=$3, 
				simtk_description=$6, 
				simtk_download_notes=$7,
				simtk_custom_agreement=$8,
				simtk_use_agreement=$9,
				simtk_openlatest=$10,
				simtk_show_download_button=$11
			WHERE group_id=$4 
			AND package_id=$5';

			$arrUpdateFrs = array(
				htmlspecialchars($name),
				$status,
				$is_public,
				$this->Group->getID(),
				$this->getID(),
				htmlspecialchars($package_desc),
				htmlspecialchars($packageNotes),
				htmlspecialchars($packageCustomAgreement),
				$packageUseAgreement,
				$packageOpenLatest,
				$packageShowDownloadButton
			);
		}
		else {
			$strUpdateFrs = 'UPDATE frs_package SET 
				name=$1, 
				status_id=$2, 
				is_public=$3, 
				simtk_description=$6,
				simtk_logo_file=$7,
				simtk_logo_type=$8,
				simtk_download_notes=$9,
				simtk_custom_agreement=$10,
				simtk_use_agreement=$11,
				simtk_openlatest=$12,
				simtk_show_download_button=$13
			WHERE group_id=$4 
			AND package_id=$5';

			$arrUpdateFrs = array(
				htmlspecialchars($name),
				$status,
				$is_public,
				$this->Group->getID(),
				$this->getID(),
				htmlspecialchars($package_desc),
				$packageLogoFileName,
				$the_logo_file_type,
				htmlspecialchars($packageNotes),
				htmlspecialchars($packageCustomAgreement),
				$packageUseAgreement,
				$packageOpenLatest,
				$packageShowDownloadButton
			);
		}

		$res = db_query_params($strUpdateFrs, $arrUpdateFrs);
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('Error On Update: %s'), db_error()));
			db_rollback();
			return false;
		}

		$olddirname = $this->getFileName();
		if(!$this->fetchData($this->getID())){
			$this->setError(_("Error Updating Package: Couldn't fetch data"));
			db_rollback();
			return false;
		}
		$newdirname = $this->getFileName();
		$olddirlocation = forge_get_config('upload_dir').'/'.$this->Group->getUnixName().'/'.$olddirname;
		// Migrated from Simtk 1.0, but directory does not exist yet.
		// Need to mkdir().
		if (!is_dir($olddirlocation)) {
			@mkdir($olddirlocation);
		}

		$newdirlocation = forge_get_config('upload_dir').'/'.$this->Group->getUnixName().'/'.$newdirname;

		if(($olddirname!=$newdirname)){
			if(is_dir($newdirlocation)){
				$this->setError(_('Error Updating Package: Directory Already Exists'));
				db_rollback();
				return false;
			} else {
				if(!@rename($olddirlocation,$newdirlocation)) {
					$this->setError(_("Error Updating Package: Couldn't rename dir"));
					db_rollback();
					return false;
				}
			}
		}
		db_commit();
		$this->createNewestReleaseFilesAsZip();
		return true;
	}

	/**
	 * getReleases - gets Release objects for all the releases in this package.
	 *
	 * @return	array	Array of FRSRelease Objects.
	 */
	function &getReleases() {
		if (!is_array($this->package_releases) || count($this->package_releases) < 1) {
			$this->package_releases=array();
			$res = db_query_params('SELECT * FROM frs_release WHERE package_id=$1 ORDER BY release_date DESC',
						array ($this->getID())) ;
			while ($arr = db_fetch_array($res)) {
				$this->package_releases[]=$this->newFRSRelease($arr['release_id'],$arr);
			}
		}
		return $this->package_releases;
	}

	/**
	 * newFRSRelease - generates a FRSRelease (allows overloading by subclasses)
	 *
	 * @param	string		FRS release identifier
	 * @param	array		fetched data from the DB
	 * @return	FRSRelease	new FRSFile object.
	 */
	protected function newFRSRelease($release_id, $data) {
		return new FRSRelease($this,$release_id, $data);
	}

	/**
	 * delete - delete this package and all its related data.
	 *
	 * @param	bool	I'm Sure.
	 * @param	bool	I'm REALLY sure.
	 * @return	bool	true/false;
	 */
	function delete($sure, $really_sure) {
		if (!$sure || !$really_sure) {
			$this->setMissingParamsError(_('Please tick all checkboxes.'));
			return false;
		}
		if (!forge_check_perm ('frs', $this->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}
		$r =& $this->getReleases();
		for ($i=0; $i<count($r); $i++) {
			if (!is_object($r[$i]) || $r[$i]->isError() || !$r[$i]->delete($sure, $really_sure)) {
				$this->setError(_('Release Error: ').$r[$i]->getName().':'.$r[$i]->getErrorMessage());
				return false;
			}
		}
		$dir=forge_get_config('upload_dir').'/'.
			$this->Group->getUnixName() . '/' .
			$this->getFileName().'/';

		// double-check we're not trying to remove root dir
		if (util_is_root_dir($dir)) {
			$this->setError(_('Package delete error: trying to delete root dir'));
			return false;
		}
		$this->deleteNewestReleaseFilesAsZip();

		if (is_dir($dir))
			rmdir($dir);

		db_query_params ('DELETE FROM frs_package WHERE package_id=$1 AND group_id=$2',
				 array ($this->getID(),
					$this->Group->getID())) ;
		return true;
	}

	/**
	 * Function that selects the newest release.
	 * The newest release is the release with the highest ID
	 *
	 * @return	object	FRSRelease
	 */

	function getNewestRelease() {
		// Exclude hidden releases.
		$result = db_query_params(
			'SELECT MAX(release_id) AS release_id FROM frs_release ' .
			'WHERE status_id != 3 ' .
			'AND package_id=$1',
			array ($this->getID())) ;

		if ($result && db_numrows($result) == 1) {
			$row = db_fetch_array($result);
			return frsrelease_get_object($row['release_id']);
		} else {
			$this->setError(_('No valid max release id'));
			return false;
		}
	}

	public function getNewestReleaseZipName() {
		return $this->getFileName()."-latest.zip";
	}

	public function getNewestReleaseZipPath () {
		return forge_get_config('upload_dir') . '/' .
			$this->Group->getUnixName() . '/' .
			$this->getFileName() . '/' .
			$this->getNewestReleaseZipName();
	}

	/**
	 * Function that selects the specified release.
	 *
	 * @return	object	FRSRelease
	 */
	function getRelease($releaseId) {
		return frsrelease_get_object($releaseId);
	}

	// Generate zip name for the specified release.
	public function getReleaseZipName($releaseId) {
		return $this->getFileName() . "-" . $releaseId . ".zip";
	}

	// Generate path name for the specified release.
	public function getReleaseZipPath($releaseId) {
		return forge_get_config('upload_dir') . '/' .
			$this->Group->getUnixName() . '/' .
			$this->getFileName() . '/' .
			$this->getReleaseZipName($releaseId);
	}

	public function createNewestReleaseFilesAsZip(){
		$release = $this->getNewestRelease();
		$cntEmptyFiles = 0;
		if ($release && class_exists('ZipArchive')) {
			$zip = new ZipArchive();
			$zipPath = $this->getNewestReleaseZipPath();
			$filesPath = forge_get_config('upload_dir').'/'.$this->Group->getUnixName().'/'.$this->getFileName().'/'.$release->getFileName();

			if ($zip->open($zipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)!==true) {
				exit_error(_('Cannot open the file archive.').' '.$zipPath.'.');
			}

			$files = $release->getFiles();

			foreach ($files as $f) {
				if ($f->isURL()) {
					// Ignore URLs.
					continue;
				}
				$filePath = $filesPath.'/'.$f->getName();
				if (!file_exists($filePath)) {
					$cntEmptyFiles++;
				}
				$zip->addFile($filePath,$f->getName());
			}

			$zip->close();
		}

		if ($cntEmptyFiles > 0) {
			// The newest release should not use a zip file
			// either because there are empty files present
			// or because it is from SimTK 1.0 which does not use this directory structure.
			$this->deleteNewestReleaseFilesAsZip();
		}
	}

	public function deleteNewestReleaseFilesAsZip() {
		if (file_exists($this->getNewestReleaseZipPath()))
			unlink($this->getNewestReleaseZipPath());
		return true;
	}


	// Get picture type: jpg, png, gif, or bmp are valid.
	// Return false for invalid picture types.
	function validatePictureFileImageType($inPicFileType) {

		$thePicFileType = false;
		if (strripos($inPicFileType, "jpg") !== false ||
			strripos($inPicFileType, "jpeg") !== false ||
			strripos($inPicFileType, "pjpeg") !== false) {
			$thePicFileType = "jpg";
		}
		else if (strripos($inPicFileType, "png") !== false ||
			strripos($inPicFileType, "x-png") !== false) {
			$thePicFileType = "png";
		}
		else if (strripos($inPicFileType, "gif") !== false) {
			$thePicFileType = "gif";
		}
		else if (strripos($inPicFileType, "bmp") !== false ||
			strripos($inPicFileType, "x-bmp") !== false) {
			$thePicFileType = "bmp";
		}
		else if (strripos($inPicFileType, "application/octet-stream") !== false) {
			$thePicFileType = "";
		}
		else {
			// Invalid picture type.
			return false;
		}

		// Valid picture type: JPG, PNG, GIF, or BMP.
		return $thePicFileType;
	}


	/**
	 * addCitation - add citation to a FRSPackage in the database.
	 *
	 * @param	string	citation	Citation to add to this package..
	 * @param	string	citation_year	Citation year.
	 * @param	string	url		URL.
	 * @param	int	cite		Show citation under "Please cite these papers".
	 * @return	boolean success.
	 */
	function addCitation($citation, $citation_year, $url, $cite=0) {

		if (!is_numeric($citation_year) || $citation_year < 1970 || $citation_year > 2099) {
			$this->setError('Invalid year.');
			return false;
		}

		if (!forge_check_perm('frs', $this->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		db_begin();

		$strCitation = 'INSERT INTO frs_citation ' .
			'(citation, citation_year, url, cite, package_id) VALUES ' .
			'($1,$2,$3,$4,$5)';
		$arrCitation = array(
			htmlspecialchars($citation),
			$citation_year,
			$url,
			$cite,
			$this->getID()
		);

		$res = db_query_params($strCitation, $arrCitation);
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('Error On Update: %s'), db_error()));
			db_rollback();
			return false;
		}

		db_commit();
		return true;
	}

	/**
	 * updateCitation - update citation in a FRSPackage in the database.
	 *
	 * @param	int	citation_id	The citation id.
	 * @param	string	citation	Citation to add to this package..
	 * @param	string	citation_year	Citation year.
	 * @param	string	url		URL.
	 * @param	int	cite		Show citation under "Please cite these papers".
	 * @return	boolean success.
	 */
	function updateCitation($citation_id, $citation, $citation_year, $url, $cite=0) {

		if (!is_numeric($citation_year) || $citation_year < 1970 || $citation_year > 2099) {
			$this->setError('Invalid year.');
			return false;
		}

		if (!forge_check_perm('frs', $this->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		db_begin();

		$strCitation = 'UPDATE frs_citation SET 
			citation=$1, 
			citation_year=$2, 
			url=$3, 
			cite=$4 
			WHERE citation_id=$5 
			AND package_id=$6';
		$arrCitation = array(
			htmlspecialchars($citation),
			$citation_year,
			$url,
			$cite,
			$citation_id,
			$this->getID()
		);

		$res = db_query_params($strCitation, $arrCitation);
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('Error On Update: %s'), db_error()));
			db_rollback();
			return false;
		}

		db_commit();
		return true;
	}

	/**
	 * deleteCitation - delete citation from this package.
	 *
	 * @param	int	citation_id	The citation id.
	 * @return	bool	true/false;
	 */
	function deleteCitation($citation_id) {

		if (!forge_check_perm ('frs', $this->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		$res = db_query_params('DELETE FROM frs_citation WHERE package_id=$1 AND citation_id=$2',
			array(
				$this->getID(), 
				$citation_id
			)
		);
		if (!$res || db_affected_rows($res) < 1) {
			// Cannot delete citation.
			return false;
		}

		return true;
	}

	/**
	 * updateRank - update rank in a FRSPackage in the database.
	 *
	 * @param	int	rank		The rank.
	 * @return	boolean success.
	 */
	function updateRank($rank=1) {

		if (!forge_check_perm('frs', $this->Group->getID(), 'write')) {
			$this->setPermissionDeniedError();
			return false;
		}

		db_begin();

		$strRank = 'UPDATE frs_package SET 
			simtk_rank=$1
			WHERE package_id=$2';
		$arrRank = array(
			$rank,
			$this->getID()
		);

		$res = db_query_params($strRank, $arrRank);
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('Error On Update: %s'), db_error()));
			db_rollback();
			return false;
		}

		db_commit();
		return true;
	}

	function setDoi($doi=1) {
	   
	   db_begin();

		$res = db_query_params('UPDATE frs_package SET 
			doi=$1
			WHERE package_id=$2',
		    array(
			  $doi,
			  $this->getID()
			)
		);

		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('Error On Update: %s'), db_error()));
			db_rollback();
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
