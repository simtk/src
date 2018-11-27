<?php
/**
 * Project Admin: Add citation to a package
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * http://fusionforge.org/
 * Copyright 2016-2018, Henry Kwong, Tod Hing - SimTK Team
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
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';


$group_id = getIntFromRequest('group_id');
$package_id = getIntFromRequest('package_id');
if (!$group_id) {
	exit_no_group();
}
if (!$package_id) {
	exit_error(_('Could Not Get FRS Package'), 'frs');
}

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'frs');
}
session_require_perm ('frs', $group_id, 'write') ;

// Get package.
$frsp = new FRSPackage($group, $package_id);
if (!$frsp || !is_object($frsp)) {
	exit_error(_('Could Not Get FRS Package'), 'frs');
}
elseif ($frsp->isError()) {
	exit_error($frsp->getErrorMessage(), 'frs');
}

// Add citation to the package
if (getStringFromRequest('submit')) {
	$citation = getStringFromRequest('citation');
	$citation_year = getIntFromRequest('citation_year');
	$url = getStringFromRequest('url');
	$cite = getStringFromRequest('cite');

	if (isset($cite) && $cite == 1) {
		$ret = $frsp->addCitation($citation, $citation_year, $url, $cite);
	}
	else {
		$ret = $frsp->addCitation($citation, $citation_year, $url);
	}

	if ($ret === true) {
		$feedback = "Added Citation";
	}
	else {
		$error_msg .= $frsp->getErrorMessage();
	}
}

frs_admin_header(array('title'=>'Add Citation','group'=>$group_id));
?>

<div><h3>Add Citation to <?php echo $frsp->getName(); ?></h3></div>

<form enctype="multipart/form-data" action="/frs/admin/createcitation.php" method="POST">

<input type="hidden" name="func" value="add_citation" />
<input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
<input type="hidden" name="package_id" value="<?php echo $package_id; ?>" />

<span class="required_note">Required fields outlined in blue.</span>
<br/><br/>

<style>
table>tbody>tr>td {
	padding-top: 5px;
}
</style>

<table>
<tr>
	<td><strong>Package Name:</strong></td>
	<td><?php echo $frsp->getName(); ?></td>
</tr>
<tr>
	<td><strong>Citation:</strong></td>
	<td><textarea class="required" rows='5' cols='60' name='citation'></textarea></td>
</tr>
<tr>
	<td><strong>Year:</strong></td>
	<td><input class="required" type="text" name="citation_year" value=""/></td>
</tr>
<tr>
	<td><strong>URL:</strong></td>
	<td><input type="text" name="url" value="" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="checkbox" name="cite" value="1" /> Categorize under "please cite these papers"
</tr>
<tr>
	<td><input type="submit" name="submit" value="Add This Citation" class="btn-cta" /></td>
</tr>
</table>
</form>

<?php

frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
