<?php
/**
 * FRS HTML Utilities
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright 2013, Franck Villaume - TrivialDev
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

/*
	Standard header to be used on all /project/admin/* pages
*/

function frs_admin_header($params) {
	global $group_id;

	/*
		Are they logged in?
	*/
	if (!session_loggedin()) {
		exit_not_logged_in();
	}

	$project = group_get_object($group_id);
	if (!$project || !is_object($project)) {
		return;
	}

	session_require_perm('frs', $group_id, 'write');

	frs_header($params);
}

function frs_admin_footer() {
	site_project_footer(array());
}

function frs_header($params) {
	global $group_id,$HTML;

	/*
		Does this site use FRS?
	*/
	if (!forge_get_config('use_frs')) {
		exit_disabled('home');
	}

	$project = group_get_object($group_id);
	if (!$project || !is_object($project)) {
		exit_no_group();
	}

	$params['toptab'] = 'frs';
	$params['group'] = $group_id;

	if (forge_check_perm('frs', $group_id, 'write')) {
		$params['submenu'] = $HTML->subMenu(
			array(
				_('View Downloads'),
				_('Reporting'),
				_('Administration')
				),
			array(
				'/frs/?group_id='.$group_id,
				'/frs/reporting/downloads.php?group_id='.$group_id,
				'/frs/admin/?group_id='.$group_id
				),
			array(
				NULL,
				NULL,
				NULL
				)
		);
	}
	else {
		$params['submenu'] = $HTML->subMenu(
			array(),
			array(),
			array()
		);
	}

	site_project_header($params);
}

function frs_footer() {
	site_project_footer(array());
}

/*
	The following functions are for the FRS (File Release System)
*/

/*
	pop-up box of public / private frs statuses
*/

function frs_show_public_popup($name='is_public', $checked_val="xzxz") {
	/*
		return a pop-up select box of statuses
	*/
	$FRS_PUBLIC_RES = array('private', 'public');
	return html_build_select_box_from_array($FRS_PUBLIC_RES, $name, $checked_val, false);
}

/*
	pop-up box of supported frs statuses
*/

function frs_show_status_popup($name='status_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of statuses
	*/
	global $FRS_STATUS_RES;
	if (!isset($FRS_STATUS_RES)) {
		$FRS_STATUS_RES=db_query_params('SELECT * FROM frs_status',
			array());
	}
	return html_build_select_box($FRS_STATUS_RES, $name, $checked_val, false);
}

/*
	pop-up box of supported frs filetypes
*/

function frs_show_filetype_popup ($name='type_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of the available filetypes
	*/
	global $FRS_FILETYPE_RES;
	if (!isset($FRS_FILETYPE_RES)) {
		$FRS_FILETYPE_RES=db_query_params('SELECT * FROM frs_filetype ' .
			'WHERE type_id>=9995 ' . 
			'ORDER BY type_id',
			array());
	}
	return html_build_select_box($FRS_FILETYPE_RES, $name, $checked_val, false);
}

/*
	pop-up box of supported frs processor options
*/

function frs_show_processor_popup($name='processor_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of the available processors
	*/
	global $FRS_PROCESSOR_RES;
	if (!isset($FRS_PROCESSOR_RES)) {
		$FRS_PROCESSOR_RES=db_query_params ('SELECT * FROM frs_processor ' .
			'WHERE processor_id>=8000 AND processor_id<=9999 ' .
			'ORDER BY processor_id',
			array());
	}
	return html_build_select_box ($FRS_PROCESSOR_RES, $name, $checked_val, false);
}

/*
	pop-up box of packages:releases for this group
*/

function frs_show_release_popup ($group_id, $name='release_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of releases for the project
	*/
	global $FRS_RELEASE_RES;

	if (!$group_id) {
		return _('Error: group id required');
	}
	if (!isset($FRS_RELEASE_RES)) {
		$FRS_RELEASE_RES = db_query_params("SELECT frs_release.release_id,(frs_package.name || ' : ' || frs_release.name) FROM frs_release,frs_package
WHERE frs_package.group_id=$1
AND frs_release.package_id=frs_package.package_id",
						   array($group_id));
		echo db_error();
	}
	return html_build_select_box($FRS_RELEASE_RES,$name,$checked_val,false);
}

/*
	pop-up box of packages for this group
*/

function frs_show_package_popup ($group_id, $name='package_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of packages for this project
	*/
	global $FRS_PACKAGE_RES;
	if (!$group_id) {
		return _('Error: group id required');
	}
	if (!isset($FRS_PACKAGE_RES)) {
		$FRS_PACKAGE_RES=db_query_params ('SELECT package_id,name
			FROM frs_package WHERE group_id=$1',
		array($group_id));
		echo db_error();
	}
	return html_build_select_box ($FRS_PACKAGE_RES,$name,$checked_val,false);
}

/*
	pop-up box of mailing list for this group
*/

function frs_show_mailinglist_popup ($group_id, $name='group_list_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of packages for this group
	*/
	if (!$group_id) {
		return _('Error: group id required');
	}
	$strQuery = "SELECT unix_group_name from groups where group_id=$group_id";
	$res = db_query_params($strQuery, array());
	if (db_numrows($res) == 0) {
		// No value available.
		return false;
	}
	$unix_group_name = db_result($res, 0, 'unix_group_name');

	$strQuery = "SELECT group_list_id, list_name FROM mail_group_list " .
		"WHERE group_id=$group_id AND list_name!='$unix_group_name" . "-commits' " .
		"ORDER BY list_name";
	$resMailingLists = db_query_params($strQuery, array());

	return html_build_select_box ($resMailingLists,$name,$checked_val,false);
}

function frs_add_file_from_form($release, $type_id, $processor_id, $release_date, 
	$userfile, $ftp_filename, $manual_filename, 
	$collect_info, $use_mail_list, $group_list_id, 
	$show_notes, $show_agreement,
	$file_desc="", $disp_name="", $doi=0, $user_id=-1, $url="",
	$githubArchiveUrl="", $refreshArchive=0) {

	$group_unix_name = $release->getFRSPackage()->getGroup()->getUnixName() ;
	$incoming = forge_get_config('groupdir_prefix')."/$group_unix_name/incoming" ;

	$filechecks = false ;

	// Check the filesize here against project-based filesize limit and prevent from
	// proceeding if limit is exceeded.
	//
	// NOTE: The PHP INI parameters post_max_size and upload_max_filesize can be
	// specified in .htaccess but they cannot be changed on the fly.
	// Hence, instead, let .htaccess contain a maximum value, and let the frs_quota
	// specify a configurable filesize limit per project. If value is found in frs_quota,
	// that value is used; otherwise, use a lower default value.
	// $userfile['size'] contains the filesize to be uploaded.
	$theGroupId = $release->getFRSPackage()->getGroup()->getID();
	$fileSizeLimit = getUploadFileSizeLimit($theGroupId);
	if ($userfile['size'] > $fileSizeLimit) {
		return 'The uploaded file size (' . $userfile['size'] . ') exceeds the maximum file size (' . $fileSizeLimit . '). Contact the site admin to upload this big file, or use an alternate upload method (if available).';
	}

	if ($userfile && is_uploaded_file($userfile['tmp_name']) && util_is_valid_filename($userfile['name'])) {
		$infile = $userfile['tmp_name'] ;
		$fname = $userfile['name'] ;
		$move = true ;
		$filechecks = true ;
	} elseif ($userfile && $userfile['error'] != UPLOAD_ERR_OK && $userfile['error'] != UPLOAD_ERR_NO_FILE) {
		switch ($userfile['error']) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return 'The uploaded file size (' . $userfile['size'] . ') exceeds the maximum file size (' . $fileSizeLimit . '). Contact the site admin to upload this big file, or use an alternate upload method (if available).';
			break;
			case UPLOAD_ERR_PARTIAL:
				return _('The uploaded file was only partially uploaded.') ;
			break;
			default:
				return _('Unknown file upload error.') ;
			break;
		}
	} elseif (forge_get_config('use_ftp_uploads') && $ftp_filename && util_is_valid_filename($ftp_filename) && is_file($upload_dir.'/'.$ftp_filename)) {
		$infile = $upload_dir.'/'.$ftp_filename;
		$fname = $ftp_filename ;
		$move = false ;
		$filechecks = true ;
	} elseif (forge_get_config('use_manual_uploads') && $manual_filename && util_is_valid_filename($manual_filename) && is_file($incoming.'/'.$manual_filename)) {
		$infile = $incoming.'/'.$manual_filename ;
		$fname = $manual_filename ;
		$move = false ;
		$filechecks = true ;
	}

	if ($url == "" && $githubArchiveUrl == "") {
		// Selected a file.
		if ($userfile && $userfile['error'] == UPLOAD_ERR_NO_FILE) {
			return _('Must select a file.') ;
		}

		if (trim($disp_name) != "") {

			// User specified a non-empty "Display Name" for file name.

			// Try to get file extension.
			$strExt = "";
			if (isset($fname)) {
				// Get last occurence of "."
				$idxDot = strrpos($fname, ".");
				if ($idxDot !== false) {
					$strExt = substr($fname, $idxDot);
				}
			}

			// Use "Display Name".
			$fname = $disp_name;
			if (strpos($fname, ".") === false) {
				// Extension not present. Append from uploaded file.
				$fname .= $strExt;
			}
		}

		if ($filechecks) {
			if (strlen($fname) < 3)
				return _('Name is too short. It must be at least 3 characters.');
			if (!$move) {
				$tmp = tempnam ('', '') ;
				copy ($infile, $tmp) ;
				$infile = $tmp ;
			}
			$frsf = new FRSFile($release);
			if (!$frsf || !is_object($frsf)) {
				return _('Could Not Get FRSFile');
			}
			elseif ($frsf->isError()) {
				return $frsf->getErrorMessage();
			}
			else {
				if (!$frsf->create($fname, $infile, $type_id, 
					$processor_id, $release_date, 
					$collect_info, $use_mail_list, $group_list_id, 
					$show_notes, $show_agreement,
					$file_desc, $doi, $user_id, $url)) {
					return $frsf->getErrorMessage();
				}
				return true ;
			}
		}
		else {
			return _('Unknown file upload error.') ;
		}
	}
	else if ($githubArchiveUrl == "") {
		// URL.
		$fname = $disp_name;
		$frsf = new FRSFile($release);
		if (!$frsf || !is_object($frsf)) {
			return _('Could Not Get FRSFile');
		}
		elseif ($frsf->isError()) {
			return $frsf->getErrorMessage();
		}
		else {
			// Note: For URL, the file location should be set to null.
			if (!$frsf->create($fname, null, $type_id, 
				$processor_id, $release_date, 
				$collect_info, $use_mail_list, $group_list_id, 
				$show_notes, $show_agreement,
				$file_desc, $doi, $user_id, $url)) {
				return $frsf->getErrorMessage();
			}
			return true ;
		}
	}
	else {
		// GitHub archive URL.
		$fname = $disp_name;
		$frsf = new FRSFile($release);
		if (!$frsf || !is_object($frsf)) {
			return _('Could Not Get FRSFile');
		}
		elseif ($frsf->isError()) {
			return $frsf->getErrorMessage();
		}
		else {
			if (!$frsf->create($fname, null, $type_id,
				$processor_id, $release_date,
				$collect_info, $use_mail_list, $group_list_id,
				$show_notes, $show_agreement,
				$file_desc, $doi, $user_id, $url, 
				$githubArchiveUrl, $refreshArchive)) {
				return $frsf->getErrorMessage();
			}
			return true ;
		}
	}
}

/* filter utils.php:&ls() output for additional constraints from FRS */
function frs_filterfiles($in) {
	$out = array();
	for ($i = 0; $i < count($in); $i++) {
		if (strlen($in[$i]) < 3)
			continue;
		$out[] = $in[$i];
	}
	return $out;
}


/* list of files for download in project overview download pulldown menu */
function frs_package_use_agreement($group_id) {

   
   $res_agreement = db_query_params('SELECT DISTINCT ON (name) name, simtk_custom_agreement, use_agreement, package_id  
        FROM frs_package,frs_use_agreement
        WHERE frs_package.simtk_use_agreement = frs_use_agreement.use_agreement_id AND frs_package.group_id=$1
        AND frs_package.status_id=1
        AND frs_package.is_public=1
        AND use_agreement_id <> 0
        ORDER BY name, simtk_custom_agreement, use_agreement, package_id', array($group_id));

   $numrows = db_numrows($res_agreement);
   if ($numrows > 0) {

      echo '<span class="small grey">License: ';
      $i = 1;
      while ($row = db_fetch_array($res_agreement)) {
          echo '<a href="#" id="package' . $row['package_id'] . '" data-content="' . $row['simtk_custom_agreement'] . '" title="' . $row['use_agreement'] . ' Use Agreement" rel="popover">' . $row['name'] . "</a>";
          if ($i < $numrows) {
            // insert comma
            echo ", ";
          }
          $i++;
      echo '<script>$("#package' . $row['package_id'] . '").popover({ ' . "title: 'Use Agreement', html: 'true', trigger: 'focus' });</script>";
      }
      echo "</span>";
   }


   //echo '<a href="#" id="blob" class="btn large primary" rel="popover">hover for popover</a>';
   //echo '<script>$("#blob").popover({ ' . "title: 'test', content: 'stuff', html: 'true' });</script>";
}



/* list of files for download in project overview download pulldown menu */
function frs_download_files_pulldown($cur_group,$group_id) {

   /*
   $cur_group = group_get_object($group_id);

   if (!$cur_group) {
        echo "error get object";
        exit;
        return false;
   }
   */

   $menu = array();
 
   //
   //      Members of projects can see all packages
   //      Non-members can only see public packages
   //
   /*
   if (session_loggedin()) {
        if (user_ismember($group_id) || forge_check_global_perm('forge_admin')) {
                $pub_sql='';
        } else {
                $pub_sql=' AND is_public=1 ';
        }
   } else {
        $pub_sql=' AND is_public=1 ';
   }
   */

   $res_package = db_query_params('SELECT * 
        FROM frs_package,frs_use_agreement
        WHERE frs_package.simtk_use_agreement = frs_use_agreement.use_agreement_id AND frs_package.group_id=$1
        AND frs_package.status_id=1
        AND frs_package.is_public=1
        ORDER BY name', array($group_id));
   $num_packages = db_numrows( $res_package );

   //echo "num packages: " . $num_packages;
   if ( $num_packages < 1) {
     return $num_packages;
   } else {
     // Iterate through packages
        $menu_num = 0;
        for ( $p = 0; $p < $num_packages; $p++ ) {

          $package_id = db_result($res_package, $p, 'package_id');

          $frsPackage = new FRSPackage($cur_group, $package_id);

          $package_name = db_result($res_package, $p, 'name');

          $package_use_agreement = db_result($res_package, $p, 'use_agreement');
          $use_agreement = "";
          if ($package_use_agreement != "None") {
             $use_agreement = "(" . $package_use_agreement . " License)";
          }
          
          // get the releases of the package
          $res_release = db_query_params ('SELECT * FROM frs_release WHERE package_id=$1
                AND status_id=1 ORDER BY release_date DESC, name ASC', array ($package_id));
                $num_releases = db_numrows( $res_release );
          
          if ( $res_release && $num_releases > 0 ) {
             
             /*  
             if (class_exists('ZipArchive')) {
                // display link to latest-release-as-zip
                //$menu[$p]["url"] = util_make_link ('/frs/download.php/latestzip/'.$frsPackage->getID().'/'.$frsPackage->getNewestReleaseZipName();
                $menu[$p]["url"] = "/frs/download.php/latestzip/".$frsPackage->getID()."/".$frsPackage->getNewestReleaseZipName();
                $menu[$p]["name"] = $frsPackage->getNewestReleaseZipName();
                //echo "url: " . $menu[$p]["url"] . "<br />";
                //echo "menu: " . $menu[$p]["name"] . "<br />";
                //$frsPackage->getNewestReleaseZipName();
                }
             */

             // get the first release which should be the latest by date
             $package_release = db_fetch_array( $res_release );

             $package_release_id = $package_release['release_id'];

             // get the files in this release....
             $res_file = db_query_params("SELECT frs_file.filename AS filename,
                                          frs_file.file_id AS file_id,
                                          frs_file.simtk_filetype AS simtk_filetype,
                                          frs_filetype.name AS type, frs_filetype.type_id AS type_id, frs_processor.name AS name
                                          FROM frs_filetype,frs_file,frs_processor
                                          WHERE release_id=$1
                                          AND frs_filetype.type_id=frs_file.type_id AND frs_processor.processor_id = frs_file.processor_id
                                          ORDER BY filename", array($package_release_id));

             $num_files = db_numrows( $res_file );
             // not iterate through files and add to the menu array
             if ( $res_file && $num_files > 0 ) {
                // now iterate and show the files in this release....
                for ( $f = 0; $f < $num_files; $f++ ) {
                   $file_release = db_fetch_array( $res_file );
                   // check release type ......$tmp_col6 = $file_release['type'];

                   // NOTE: Has to check whether file is a URL. If so, do not include.
                   if ($file_release['type_id'] != 9997 && 
			$file_release['type_id'] != 9994 &&
			$file_release['simtk_filetype'] != "URL") { 
                      $menu[$menu_num]["url"] = "/frs/download_confirm.php/file/" .
			$file_release['file_id'] . "/" .
			$file_release['filename'] . "?" .
			"group_id=" . $group_id;
		      $menu[$menu_num]["name"] = "<b>" . $package_name . ":</b> " . $file_release['filename'];
                      $menu_num++;
                   } // filetype
                   //print_r ($file_release);
                }
             }
          } // releases more than 0

        } // Iterate through packages for loop
        return $menu;

   }

}

// Retrieve upload filesize limit of given project.
function getUploadFileSizeLimit($theGroupId) {
	$fileSizeLimit = false;

	// Get 'default_upload_max_filesize' from defaults.ini.
	$defaultUploadMaxFilesize = forge_get_config('default_upload_max_filesize');
	if ($defaultUploadMaxFilesize === false || trim($defaultUploadMaxFilesize) == "") {
		// Parameter not found. Set to 4M bytes.
		$defaultUploadMaxFilesize = 4 * 1024 * 1024;
	}

	$strQuery = "SELECT post_max_size FROM frs_quota " .
		"WHERE group_id=" . $theGroupId;
	$resFilesizeLimit = db_query_params($strQuery, array());
	$numrows = db_numrows($resFilesizeLimit);
	if ($numrows > 0) {
		while ($row = db_fetch_array($resFilesizeLimit)) {
			$fileSizeLimit = $row['post_max_size'];
		}
	}
	if ($fileSizeLimit !== false && $fileSizeLimit != -1) {
		// Found upload filesize for project.
		$fileSizeLimit = trim($fileSizeLimit);
		$last = strtolower($fileSizeLimit[strlen($fileSizeLimit) - 1]);
		switch ($last) {
		case 'g':
			$fileSizeLimit *= 1024;
		case 'm':
			$fileSizeLimit *= 1024;
		case 'k':
			$fileSizeLimit *= 1024;
		}
		return $fileSizeLimit;
	}
	else {
		// Use default upload max filesize.
		return $defaultUploadMaxFilesize;
	}
}


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
