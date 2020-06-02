<?php
/**
 * Project Admin: Delete a file from release.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * http://fusionforge.org/
 * Copyright 2016-2020, Henry Kwong, Tod Hing - SimTK Team
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
$package_id = getIntFromRequest('package_id');
$release_id = getIntFromRequest('release_id');
$file_id = getIntFromRequest('file_id');
if (!$group_id) {
	exit_no_group();
}

$project = group_get_object($group_id);
if (!$project || !is_object($project)) {
    exit_no_group();
}
elseif ($project->isError()) {
	exit_error($project->getErrorMessage(),'frs');
}

session_require_perm ('frs', $group_id, 'write') ;


// Get package.
$frsp = new FRSPackage($project, $package_id);
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

/*
	Relatively simple form to delete a file from release.
*/

frs_admin_header(array('title'=>'Delete File','group'=>$group_id));

echo '<hr />';
echo '<div><h3>' . $frsf->getName() . '</h3></div>';
	echo '
	<form action="/frs/admin/?group_id='.$group_id.'" method="post">
	<input type="hidden" name="func" value="delete_file" />
	<input type="hidden" name="package_id" value="'. $package_id .'" />
	<input type="hidden" name="release_id" value="'. $release_id .'" />
	<input type="hidden" name="file_id" value="'. $file_id .'" />
	<p>You are about to permanently and irretrievably delete this file from the release!</p>
	<input type="checkbox" name="sure" value="1" />&nbsp;'._('I am Sure').'<br />
	<input type="checkbox" name="really_sure" value="1" />&nbsp;'._('I am Really Sure').'<br/>
	<input style="margin-top:17px;margin-bottom:5px;" type="submit" name="submitAndNotify" value="Delete & Notify Followers" class="btn-cta" />
	<input style="margin-top:2px;margin-bottom:5px;" type="submit" name="submitNoNotify" value="Delete & Do Not Notify Followers" class="btn-cta" />
	</p>
	</form>';

frs_admin_footer();
