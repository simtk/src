<?php
/**
 * view.php
 *
 * FusionForge Documentation Manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2010-2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012,2014, Franck Villaume - TrivialDev
 * Copyright 2016-2023, SimTK Team
 * http://fusionforge.org
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

$no_gz_buffer = true;

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'docman/Document.class.php';
require_once $gfcommon.'docman/DocumentFactory.class.php';
require_once $gfcommon.'docman/DocumentGroupFactory.class.php';
require_once $gfcommon.'docman/include/utils.php';
require_once $gfcommon.'docman/DocumentStorage.class.php';

$sysdebug_enable = false;

$arr = explode('/', getStringFromServer('REQUEST_URI'));
$group_id = (int) $arr[3];
$docid = isset($arr[4])? $arr[4]: '';

$g = group_get_object($group_id);
if (!$g || !is_object($g)) {
	exit_no_group();
} elseif ($g->isError()) {
	/*
	if ($g->isPermissionDeniedError()) {
		exit_permission_denied();
	}
	*/
	exit_error($g->getErrorMessage(), 'docman');
}
// Check project read privilege.
if (!forge_check_perm('project_read', $group_id)) {
	exit_permission_denied();
}

if (is_numeric($docid)) {
	//session_require_perm('docman', $group_id, 'read');
	$docname = urldecode($arr[5]);

	$d = new Document($g, $docid);

	if (!$d || !is_object($d)) {
		exit_error(_('Document is not available.'), 'docman');
	} elseif ($d->isError()) {
		exit_error($d->getErrorMessage(), 'docman');
	}

	$docStorage = DocumentStorage::instance();
	$fullPath = $docStorage->get_storage($docid);
	if (!file_exists($fullPath)) {
                // File does not exist. Deleted already. Done.
		exit_error("Document does not exist: " . $docid);
        }

	/**
	 * except for active (1), we need more right access than just read
	 */
	switch ($d->getStateID()) {
		case "2":
		case "3":
		case "4":
		case "5": {
			session_require_perm('docman', $group_id, 'approve');
			break;
		}
	}

	/**
	 * If the served document has wrong relative links, then
	 * theses links may redirect to the same document with another
	 * name, this way a search engine may loop and stress the
	 * server.
	 */
	if ($d->getFileName() != $docname) {
		session_redirect('/docman/view.php/'.$group_id.'/'.$docid.'/'.urlencode($d->getFileName()));
	}

	header('Content-disposition: attachment; filename="'.str_replace('"', '', $d->getFileName()) . '"');
	header("Content-type: ".$d->getFileType());
	header("Content-Transfer-Encoding: binary");
	ob_end_clean();

	$file_path = $d->getFilePath();
	$length = 0;
	if (file_exists($file_path)) {
		$length = filesize($file_path);
	}
	header("Content-length: $length");

	readfile_chunked($file_path);

} elseif ($docid === 'backup') {
	if (extension_loaded('zip')) {
		session_require_perm('docman', $group_id, 'admin');

		$df = new DocumentFactory($g);
		if ($df->isError())
			exit_error($df->getErrorMessage(), 'docman');

		$dgf = new DocumentGroupFactory($g);
		if ($dgf->isError())
			exit_error($dgf->getErrorMessage(), 'docman');

		$nested_groups = $dgf->getNested();

		if ( $nested_groups != NULL ) {
			$filename = 'docman-'.$g->getUnixName().'-'.$docid.'.zip';
			$file = forge_get_config('data_path').'/docman/'.$filename;

			$zip = new ZipArchive;
			if ( !$zip->open($file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
				@unlink($file);
				exit_error(_('Unable to open ZIP archive for backup'), 'docman');
			}

			if ( !docman_fill_zip($zip, $nested_groups, $df)) {
				if ($zip->numFiles > 0) {
					// Close zip to unlock previously added files.
					$zip->close();
				}
				@unlink($file);
				exit_error(_('Unable to fill ZIP archive for backup'), 'docman');
			}

			if ($zip->numFiles > 0) {
				if (!$zip->close()) {
					@unlink($file);
					exit_error(_('Unable to close ZIP archive for backup'), 'docman');
				}
			}

			header('Content-disposition: attachment; filename="'.$filename.'"');
			// please do not set the Content-type: it breaks IE support.
			if (!preg_match('/trident/i', $_SERVER['HTTP_USER_AGENT'])) {
				header('Content-type: application/zip');
			}
			header("Content-Transfer-Encoding: binary");
			ob_end_clean();

			if(!readfile_chunked($file)) {
				@unlink($file);
				$error_msg = _('Unable to download backup file');
				session_redirect('/docman/?group_id='.$group_id.'&view=admin&error_msg='.urlencode($error_msg));
			}
			@unlink($file);
		} else {
			$warning_msg = _('No documents to backup.');
			session_redirect('/docman/?group_id='.$group_id.'&view=admin&warning_msg='.urlencode($warning_msg));
		}
	} else {
		$warning_msg = _('ZIP extension is missing: no backup function');
		session_redirect('/docman/?group_id='.$group_id.'&view=admin&warning_msg='.urlencode($warning_msg));
	}
} elseif ($docid === 'webdav') {
	if (forge_get_config('use_webdav') && $g->useWebDav()) {
		require_once $gfcommon.'docman/include/webdav.php';
		$_SERVER['SCRIPT_NAME'] = '';
		/* we need the group id for check authentification. */
		$_SERVER["AUTH_TYPE"] = $group_id;
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="Webdav Access" (For anonymous access : click enter)');
			header('HTTP/1.0 401 Unauthorized');
			echo _('Webdav Access Canceled by user');
			die();
		}
		$server = new HTTP_WebDAV_Server_Docman;
		$server->ServeRequest();
	} else {
		$warning_msg = _('No Webdav interface enabled.');
		session_redirect('/docman/?group_id='.$group_id.'&warning_msg='.urlencode($warning_msg));
	}
} elseif ($docid === 'zip') {
	//session_require_perm('docman', $group_id, 'read');
	if (extension_loaded('zip')) {
		if ( $arr[5] === 'full' ) {
			$dirid = $arr[6];

			$dg = new DocumentGroup($g, $dirid);
			if ($dg->isError())
				exit_error($dg->getErrorMessage(), 'docman');

			$df = new DocumentFactory($g);
			if ($df->isError())
				exit_error($df->getErrorMessage(), 'docman');

			$dgf = new DocumentGroupFactory($g);
			if ($dgf->isError())
				exit_error($dgf->getErrorMessage(), 'docman');

			$nested_groups = $dgf->getNested();

			if ($dg->hasDocuments($nested_groups, $df)) {
				$filename = 'docman-'.$g->getUnixName().'-'.$dg->getID().'.zip';
				$file = forge_get_config('data_path').'/docman/'.$filename;
				@unlink($file);
				$zip = new ZipArchive;
				if ( !$zip->open($file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
					@unlink($file);
					exit_error(_('Unable to open ZIP archive for download as ZIP'), 'docman');
				}

				// ugly workaround to get the files at doc_group_id level
				$df->setDocGroupID($dg->getID());
				$docs = $df->getDocuments(1);	// no caching
				if (is_array($docs) && count($docs) > 0) {	// this group has documents
					foreach ($docs as $doc) {
						if ($doc->isURL()) {
							continue;
						}
						if (!file_exists($doc->getFilePath())) {
							// Skip; file does not exist.
							error_log("PHP Warning:  " .
								"docman: File does not exist (" .
								$g->getUnixName() . ":" . 
								$doc->getFilePath() . ":" . 
								$doc->getFileName() . ")");
							continue;
						}
						// Use addFile() to avoid memory exhaustion problem.
						//if (!$zip->addFromString(iconv("UTF-8", "ASCII//TRANSLIT", $doc->getFileName()), $doc->getFileData()))

						if (!$zip->addFile($doc->getFilePath(), $doc->getFileName())) {
							if ($zip->numFiles > 0) {
								// Close zip to unlock previously added files.
								$zip->close();
							}
							@unlink($file);
							error_log("PHP Warning:  " .
								"docman: Unable to add file (" .
								$g->getUnixName() . ":" . 
								$doc->getFileName() . ")");
							exit_error("Unable to add file (" .
								$g->getUnixName() . ":" . 
								$doc->getFileName() . ")", 
								'docman');
						}
						$zip->setCompressionName($doc->getFilePath(), ZipArchive::CM_STORE);
					}
				}
				if (!docman_fill_zip($zip, $nested_groups, $df, $dg->getID())) {
					if ($zip->numFiles > 0) {
						// Close zip to unlock previously added files.
						$zip->close();
					}
					@unlink($file);
					error_log("PHP Warning:  " .
						"docman: Unable to fill ZIP file (" .
						$g->getUnixName() . ")");
					exit_error(_('Unable to fill ZIP archive for download as ZIP'), 'docman');
				}

				if ($zip->numFiles > 0) {
					if (!$zip->close()) {
						@unlink($file);
						error_log("PHP Warning:  " .
							"docman: Unable to close ZIP archive file (" .
							$file . ")");
						exit_error("Unable to close ZIP archive for download as ZIP", 'docman');
					}
				}

				header('Content-disposition: attachment; filename="'.$filename.'"');
				// please do not set the Content-type: it breaks IE support.
				if (!preg_match('/trident/i', $_SERVER['HTTP_USER_AGENT'])) {
					header('Content-type: application/zip');
				}
				header("Content-Transfer-Encoding: binary");
				ob_end_clean();

				if(!readfile_chunked($file)) {
					@unlink($file);
					$error_msg = _('Unable to download ZIP archive');
					session_redirect('/docman/?group_id='.$group_id.'&view=admin&error_msg='.urlencode($error_msg));
				}
				@unlink($file);
			} else {
				$warning_msg = _('This documents folder is empty.');
				session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&warning_msg='.urlencode($warning_msg));
			}
		} elseif ( $arr[5] === 'selected' ) {
			$dirid = $arr[6];
			$arr_fileid = explode(',',$arr[7]);
			$filename = 'docman-'.$g->getUnixName().'-selected-'.time().'.zip';
			$file = forge_get_config('data_path').'/docman/'.$filename;
			@unlink($file);
			$zip = new ZipArchive;
			if (!$zip->open($file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
				@unlink($file);
				exit_error(_('Unable to open ZIP archive for download as ZIP'), 'docman');
			}

			foreach($arr_fileid as $docid) {
				if (!empty($docid)) {
					$d = new Document($g, $docid);
					if (!$d || !is_object($d)) {
						//@unlink($file);
						//exit_error(_('Document is not available.'), 'docman');
						continue;
					}
					elseif ($d->isError()) {
						//@unlink($file);
						//exit_error($d->getErrorMessage(), 'docman');
						continue;
					}
					if ($d->isURL()) {
						continue;
					}
					if (!file_exists($d->getFilePath())) {
						// Skip; file does not exist.
						error_log("PHP Warning:  " .
							"docman: File does not exist (" .
							$g->getUnixName() . ":" . 
							$d->getFileName() . ")");
						continue;
					}
					// Use addFile() to avoid memory exhaustion problem.
					//if (!$zip->addFromString(iconv("UTF-8", "ASCII//TRANSLIT", $d->getFileName()), $d->getFileData())) {
					if (!$zip->addFile($d->getFilePath(), $d->getFileName())) {
						if ($zip->numFiles > 0) {
							// Close zip to unlock previously added files.
							$zip->close();
						}
						@unlink($file);
						error_log("PHP Warning:  " .
							"DocMan: Unable to add file (" .
							$g->getUnixName() . ":" . 
							$d->getFileName() . ")");
						exit_error("Unable to add file (" .
							$g->getUnixName() . ":" . 
							$d->getFileName() . ")", 
							'docman');
					}
					$zip->setCompressionName($d->getFilePath(), ZipArchive::CM_STORE);
				}
				else {
					if ($zip->numFiles > 0) {
						$zip->close();
					}
					@unlink($file);
					$warning_msg = _('No action to perform');
					session_redirect('/docman/?group_id='.$group_id.'&view=listfile&dirid='.$dirid.'&warning_msg='.urlencode($warning_msg));
				}
			}
			if ($zip->numFiles > 0) {
				if (!$zip->close()) {
					@unlink($file);
					exit_error(_('Unable to close ZIP archive for download as ZIP'), 'docman');
				}
			}

			header('Content-disposition: attachment; filename="'.$filename.'"');
			// please do not set the Content-type: it breaks IE support.
			if (!preg_match('/trident/i', $_SERVER['HTTP_USER_AGENT'])) {
				header('Content-type: application/zip');
			}
			header("Content-Transfer-Encoding: binary");
			ob_end_clean();

			if(!readfile_chunked($file)) {
				@unlink($file);
				$error_msg = _('Unable to download ZIP archive');
				session_redirect('/docman/?group_id='.$group_id.'&view=admin&error_msg='.urlencode($error_msg));
			}
			@unlink($file);
		} else {
			exit_error(_('No document to display - invalid or inactive document number.'), 'docman');
		}
	} else {
		exit_error(_('PHP ZIP extension is missing.'), 'docman');
	}
} else {
	exit_error(_('No document to display - invalid or inactive document number.'), 'docman');
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
