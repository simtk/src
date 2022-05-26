<?php
/**
 *
 * Project Admin: Edit Packages
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
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
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$package_id = getIntFromRequest('package_id');
$release_id = getIntFromRequest('release_id');
$file_id = getIntFromRequest('file_id');
$citation_id = getIntFromRequest('citation_id');

$cur_group_obj = group_get_object($group_id);
if (!$cur_group_obj || !is_object($cur_group_obj)) {
    exit_no_group();
}
elseif ($cur_group_obj->isError()) {
	exit_error($cur_group_obj->getErrorMessage(), 'frs');
}

session_require_perm('frs', $group_id, 'write');

// Members of projects can see all packages.
// Non-members can only see public packages.
if (session_loggedin()) {
	if (user_ismember($group_id) || forge_check_global_perm('forge_admin')) {
		$pub_sql='';
	}
	else {
		$pub_sql=' AND is_public=1 ';
	}
}
else {
	$pub_sql=' AND is_public=1 ';
}


// Relatively simple form to edit/add packages of releases
// only admin can modify packages (vs modifying releases of packages)
$submitAndNotify = getStringFromRequest('submitAndNotify');
$submitNoNotify = getStringFromRequest('submitNoNotify');
if (getStringFromRequest('submit') || $submitAndNotify || $submitNoNotify) {
	$func = getStringFromRequest('func');
	$package_id = getIntFromRequest('package_id');
	$package_name = htmlspecialchars(trim(getStringFromRequest('package_name')));
	$package_desc = htmlspecialchars(trim(getStringFromRequest('package_desc')));
	$is_public = getIntFromRequest('is_public');
	$status_id = getIntFromRequest('status_id');

	// make updates to the database
	if ($func == 'add_package' && $package_name) {

		// Create a new package.
		$frsp = new FRSPackage($cur_group_obj);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'), 'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		if (!$frsp->create($package_name, $is_public)) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		else {
			$feedback .= _('Added Package');
		}

	}
	elseif ($func == 'delete_package' && $package_id) {

		// Delete a package.
		$frsp = new FRSPackage($cur_group_obj, $package_id);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'), 'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}

		$sure = getIntFromRequest("sure");
		$really_sure = getIntFromRequest("really_sure");
		if (!$frsp->delete($sure, $really_sure)) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		else {
			$feedback .= _('Deleted');
		}

	}
	elseif ($func == 'update_package' && $package_id && $package_name) {
		$frsp = new FRSPackage($cur_group_obj, $package_id);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'), 'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		if (!$frsp->update($package_name, $status_id, $is_public, $package_desc)) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}
		else {
			$feedback .= _('Updated Package');
		}
	}
	elseif ($func == 'delete_citation' && $package_id && $citation_id) {

		// Delete a citation.
		$frsp = new FRSPackage($cur_group_obj, $package_id);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'), 'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}

		$sure = getIntFromRequest("sure");
		$really_sure = getIntFromRequest("really_sure");
		if ($sure && $really_sure) {
			if (!$frsp->deleteCitation($citation_id)) {
				exit_error($frsf->getErrorMessage(), 'frs');
			}
			else {
				$feedback .= 'Citation Deleted.';
			}
		}
		else {
			$error_msg .= 'Citation not deleted: you did not check "I am Sure" and "I am Really Sure"';
		}

	}
	elseif ($func=='delete_release' && $release_id) {
		$sure = getStringFromRequest('sure');
		$really_sure = getStringFromRequest('really_sure');
		if ($submitNoNotify) {
			$emailChange = 0;
		}
		else {
			$emailChange = 1;
		}

		// Get package.
		$frsp = new FRSPackage($cur_group_obj, $package_id);
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

		if (!$frsr->delete($sure, $really_sure, $emailChange)) {
			exit_error($frsr->getErrorMessage(), 'frs');
		}
		else {
			$feedback .= _('Deleted');

			// Release deleted already. Clear the release id.
			$release_id = null;
		}
	}
	elseif ($func=='delete_file' && $file_id) {
		$sure = getStringFromRequest('sure');
		$really_sure = getStringFromRequest('really_sure');
		if ($submitNoNotify) {
			$emailChange = 0;
		}
		else {
			$emailChange = 1;
		}
	

		//  Get package.
		$frsp = new FRSPackage($cur_group_obj, $package_id);
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

		// Get file.
		$frsf = new FRSFile($frsr, $file_id);
		if (!$frsf || !is_object($frsf)) {
			exit_error(_('Could Not Get FRSFile'), 'frs');
		}
		elseif ($frsf->isError()) {
			exit_error($frsf->getErrorMessage(), 'frs');
		}

		if ($sure && $really_sure) {
			if (!$frsf->delete(false, $emailChange)) {
				exit_error($frsf->getErrorMessage(), 'frs');
			}
			else {
				$feedback .= _('File Deleted');
			}
		}
		else {
			$error_msg .= 'File not deleted: you did not check "I am Sure" and "I am Really Sure"';
		}
	}
	elseif ($func=='cancel_doi' && $file_id) {
		$sure = getStringFromRequest('sure');
		$really_sure = getStringFromRequest('really_sure');

		//  Get package.
		$frsp = new FRSPackage($cur_group_obj, $package_id);
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

		// Get file.
		$frsf = new FRSFile($frsr, $file_id);
		if (!$frsf || !is_object($frsf)) {
			exit_error(_('Could Not Get FRSFile'), 'frs');
		}
		elseif ($frsf->isError()) {
			exit_error($frsf->getErrorMessage(), 'frs');
		}

		if ($sure && $really_sure) {
			if (!$frsf->cancelDOI()) {
				exit_error($frsf->getErrorMessage(), 'frs');
			}
			else {
				$feedback .= 'DOI request canceled';
			}
		}
		else {
			$error_msg .= 'DOI request not canceled: you did not check "I am Sure" and "I am Really Sure"';
		}
	}
	elseif ($func=='cancel_package_doi' && $package_id) {
		$sure = getStringFromRequest('sure');
		$really_sure = getStringFromRequest('really_sure');

		//  Get package.
		$frsp = new FRSPackage($cur_group_obj, $package_id);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'), 'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(), 'frs');
		}

		if ($sure && $really_sure) {
			if (!$frsp->cancelDOI()) {
				exit_error($frsp->getErrorMessage(), 'frs');
			}
			else {
				$feedback .= 'DOI request canceled';
			}
		}
		else {
			$error_msg .= 'DOI request not canceled: you did not check "I am Sure" and "I am Really Sure"';
		}
	}
}

frs_admin_header(array('title'=>_('Release Edit/File Releases'), 'group'=>$group_id));

$strQueryPackages = 'SELECT status_id, package_id, name AS package_name, is_public ' .
	'FROM frs_package ' .
	'WHERE group_id=$1' .
	'ORDER BY status_id';
$res = db_query_params($strQueryPackages, array($group_id));
$rows = db_numrows($res);

?>

<div class="du_warning_msg"></div>

<?php
require_once 'frs_admin_front.php';

?>

<script src='/frs/showNotReadyDivs.js'></script>
<script src='/frs/admin/handlerDiskUsage.js'></script>

<script>
$(document).ready(function() {
	// Display diskage usage warning message, if any.
	if (!handlerDiskUsage(<?php echo ((int)$group_id); ?>)) {
		// Disable input fields.
		$(".theFieldSet").prop("disabled", true);
		$(".theFieldSet").css("background-color", "#a7c0e1");
		$(".theFieldSet").removeAttr("href");
	}
});
</script>

<?php

frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
