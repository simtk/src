<?php
/**
 * Project Admin: Update file in a release.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org/
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/utils.php';
require_once $gfcommon.'include/User.class.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfwww.'admin/admin_utils.php';

// File.
$result_pending_file = db_query_params("SELECT group_name, filename, " .
	"ff.doi_identifier AS doi_identifier, file_user_id, file_id " .
	"FROM frs_file ff " .
	"JOIN frs_release fr " .
	"ON ff.release_id = fr.release_id " .
	"JOIN frs_package fp " .
	"ON fr.package_id = fp.package_id " .
	"JOIN groups g " .
	"ON g.group_id = fp.group_id " .
	"WHERE ff.doi = 1 " .
	"AND ff.doi_identifier is null",
	array());
$result_assigned_file = db_query_params("SELECT group_name, filename, " .
	"ff.doi_identifier AS doi_identifier " .
	"FROM frs_file ff " .
	"JOIN frs_release fr " .
	"ON ff.release_id = fr.release_id " .
	"JOIN frs_package fp " .
	"ON fr.package_id = fp.package_id " .
	"JOIN groups g " .
	"ON g.group_id = fp.group_id " .
	"WHERE ff.doi = 1 ".
	"AND ff.doi_identifier <> '' ",
	array());

// Package.
$result_pending_package = db_query_params("SELECT group_name, name, " .
	"doi_identifier, package_user_id, package_id " .
	"FROM frs_package fp " .
	"JOIN groups g " .
	"ON g.group_id = fp.group_id " .
	"WHERE fp.doi = 1 " .
	"AND fp.doi_identifier is null",
	array());
$result_assigned_package = db_query_params("SELECT group_name, name, " .
	"doi_identifier " .
	"FROM frs_package fp " .
	"JOIN groups g " .
	"ON g.group_id = fp.group_id " .
	"WHERE fp.doi = 1 ".
	"AND fp.doi_identifier <> '' ",
	array());

// Update doi_identifier.
if (getStringFromRequest('submit')) {

	$file_user_id = getStringFromRequest('file_user_id');
	$file_id = getStringFromRequest('file_id');

	$package_user_id = getStringFromRequest('package_user_id');
	$package_id = getStringFromRequest('package_id');

	$doi_identifier = getStringFromRequest('doi_identifier');
	$group_name = getStringFromRequest('group_name');
	
	if (!empty($file_user_id) && !empty($file_id)) {
		// File.
		$user = user_get_object($file_user_id);
		$user_email = $user->getEmail();

		$name = getStringFromRequest('filename');

		$result = db_query_params("UPDATE frs_file " .
			"SET doi_identifier=$1 " .
			"WHERE file_id=$2", 
			array($doi_identifier, $file_id));
	}
	else if (!empty($package_user_id) && !empty($package_id)) {
		// Package.
		$user = user_get_object($package_user_id);
		$user_email = $user->getEmail();

		$name = getStringFromRequest('name');

		$result = db_query_params("UPDATE frs_package " .
			"SET doi_identifier=$1 " .
			"WHERE package_id=$2", 
			array($doi_identifier, $package_id));
	}

	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= sprintf(("Error On DOI Update: %s"), db_error());
	}
	else {
		$feedback .= "DOI Updated";
		
		if ($file_user_id || $package_user_id) {
			$message = "The DOI has been assigned for the following:\n\n" . 
				"Project: " . $group_name . 
				"\nFilename: " . $name . 
				"\nDOI Identifier: " . $doi_identifier;
			$message .= "\n\nThe information for the DOI citation were based upon the information in your project.  Go to https://doi.org/" . 
				$doi_identifier . 
				"\nto see your resource." .
				"\n\nPlease also verify the accuracy of your resource’s description at https://search.datacite.org/works?query=" . 
				$doi_identifier . 
				" (or go to https://search.datacite.org and enter the DOI in the search box).  If you would like to add ORCIDs or funding institutions, or if any of the information needs to be updated, please email us at webmaster@simtk.org.";

			util_send_message($user_email, "DOI Assigned", $message);
		}
		
		// refresh query results
		$result_pending_file = db_query_params("SELECT group_name, filename, " .
			"ff.doi_identifier AS doi_identifier, file_user_id, file_id " .
			"FROM frs_file ff " .
			"JOIN frs_release fr " .
			"ON ff.release_id = fr.release_id " .
			"JOIN frs_package fp " .
			"ON fr.package_id = fp.package_id " .
			"JOIN groups g " .
			"ON g.group_id = fp.group_id " .
			"WHERE ff.doi = 1 " .
			"AND ff.doi_identifier is null",
			array());
		$result_assigned_file = db_query_params("SELECT group_name, filename, " .
			"ff.doi_identifier AS doi_identifier " .
			"FROM frs_file ff " .
			"JOIN frs_release fr " .
			"ON ff.release_id = fr.release_id " .
			"JOIN frs_package fp " .
			"ON fr.package_id = fp.package_id " .
			"JOIN groups g " .
			"ON g.group_id = fp.group_id " .
			"WHERE ff.doi = 1 ".
			"AND ff.doi_identifier <> '' ",
			array());
		$result_pending_package = db_query_params("SELECT group_name, name, " .
			"doi_identifier, package_user_id, package_id " .
			"FROM frs_package fp " .
			"JOIN groups g " .
			"ON g.group_id = fp.group_id " .
			"WHERE fp.doi = 1 " .
			"AND fp.doi_identifier is null",
			array());
		$result_assigned_package = db_query_params("SELECT group_name, name, " .
			"doi_identifier " .
			"FROM frs_package fp " .
			"JOIN groups g " .
			"ON g.group_id = fp.group_id " .
			"WHERE fp.doi = 1 ".
			"AND fp.doi_identifier <> '' ",
			array());
	}
}

site_admin_header(array('title'=>_('Site Admin')));
?>

<style>
td {
	padding-bottom:5px;
	vertical-align:top;
}
</style>


<h2>Admin DOI</h2>
<h4>Note: Enter the identifier only in DOI Identifier, not the full URL.</h4>

<h3>DOI Pending</h3>

<table class="table">

<tr><th>Project Name</th><th>Submitter</th><th>Display Name</th><th>DOI Identifier</th><th></th></tr>
	
<?php

// File.
while ($row = db_fetch_array($result_pending_file)) {
	echo "<form action='downloads-doi.php' method='post'>" .
		"<input type='hidden' name='file_id' value='" . $row['file_id'] . "'>" .
		"<input type='hidden' name='file_user_id' value='" . $row['file_user_id'] . "'>" .
		"<input type='hidden' name='filename' value='" . $row['filename'] . "'>" .
		"<input type='hidden' name='group_name' value='" . $row['group_name'] . "'>" .
		"<tr>" .
		"<td>". $row['group_name'] . "</td>";
	echo "<td>";
	if ($row['file_user_id']) {
		$user = user_get_object($row['file_user_id']);
		if ($user) {
			$real_name = $user->getRealName();
			echo $real_name;
		}
	}
	echo "</td>";
	echo "<td>" . $row['filename'] . "</td>" .
		"<td><input type='text' name='doi_identifier'></td>" .
		"<td><input type='submit' name='submit' id='submit' value='Update' class='btn-cta' /></td>" .
		"</tr>" .
		"</form>";
}

// Package.
while ($row = db_fetch_array($result_pending_package)) {
	echo "<form action='downloads-doi.php' method='post'>" .
		"<input type='hidden' name='package_id' value='" . $row['package_id'] . "'>" .
		"<input type='hidden' name='package_user_id' value='" . $row['package_user_id'] . "'>" .
		"<input type='hidden' name='name' value='" . $row['name'] . "'>" .
		"<input type='hidden' name='group_name' value='" . $row['group_name'] . "'>" .
		"<tr>" .
		"<td>". $row['group_name'] . "</td>";
	echo "<td>";
	if ($row['package_user_id']) {
		$user = user_get_object($row['package_user_id']);
		if ($user) {
			$real_name = $user->getRealName();
			echo $real_name;
		}
	}
	echo "</td>";
	echo "<td>" . $row['name'] . "</td>" .
		"<td><input type='text' name='doi_identifier'></td>" .
		"<td><input type='submit' name='submit' id='submit' value='Update' class='btn-cta' /></td>" .
		"</tr>" .
		"</form>";
}

?>

</table>

<h3>DOI Assigned</h3>
<table class="table">
<tr><th>Project Name</th><th>Display Name</th><th>DOI Identifier</th></tr>
<tr>

<?php

// File.
while ($row = db_fetch_array($result_assigned_file)) {
	echo "<tr>" .
		"<td>" . $row['group_name'] . "</td>" .
		"<td>" . $row['filename'] . "</td>" .
		"<td>" . $row['doi_identifier'] . "</td>" .
		"</tr>";
}

// Package.
while ($row = db_fetch_array($result_assigned_package)) {
	echo "<tr>" .
		"<td>" . $row['group_name'] . "</td>" .
		"<td>" . $row['name'] . "</td>" .
		"<td>" . $row['doi_identifier'] . "</td>" .
		"</tr>";
}

?>

</table>

<?php

site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
