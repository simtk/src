<?php
/**
 * Project Admin: Delete a citation
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'frs/FRSPackage.class.php';

$group_id = getIntFromRequest('group_id');
$package_id = getIntFromRequest('package_id');
$citation_id = getIntFromRequest('citation_id');
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

if (!$citation_id) {
	// Cannot find citation.
	exit_error('Cannot find citation', 'frs');
}

$group = group_get_object($group_id);

/*
	Relatively simple form to delete a citation from package.
*/

frs_admin_header(array('title'=>'Delete Citation','group'=>$group_id));
$strFrsQuery = 'SELECT citation FROM frs_citation fc ' .
	'WHERE citation_id=$1;';
$res = db_query_params($strFrsQuery, array($citation_id));
$rows = db_numrows($res);
if ($rows != 1) {
	// Cannot find citation.
	exit_error('Cannot find citation', 'frs');
}

echo '<hr />';
echo '<div><h3>' . db_result($res, 0, 'citation') . '</h3></div>';
	echo '
	<form action="/frs/admin/?group_id='.$group_id.'" method="post">
	<input type="hidden" name="func" value="delete_citation" />
	<input type="hidden" name="package_id" value="'. $package_id .'" />
	<input type="hidden" name="citation_id" value="'. $citation_id .'" />
	'._('You are about to permanently and irretrievably delete this citation!').'
	<p>
	<input type="checkbox" name="sure" value="1" />'._('I am Sure').'<br />
	<input type="checkbox" name="really_sure" value="1" />'._('I am Really Sure').'<br />
	<input type="submit" name="submit" value="'._('Delete').'" class="btn-cta" />
	</p>
	</form>';

frs_admin_footer();
