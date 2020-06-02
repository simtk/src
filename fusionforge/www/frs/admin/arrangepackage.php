<?php
/**
 * Project Admin: Arrange Packages in a group.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2012-2014, Franck Villaume - TrivialDev
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';

$group_id = getIntFromRequest('group_id');
$header_order = getIntFromRequest('header_order');

if (!$group_id) {
	exit_no_group();
}

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'frs');
}
session_require_perm ('frs', $group_id, 'write') ;

// Arrange packages.
if (getStringFromRequest('submit')) {

	$header_order = getStringFromRequest('header_order');
	$arrPackages = explode(",", $header_order);
	for ($cnt = 0; $cnt < count($arrPackages); $cnt++) {
		$idx = stripos($arrPackages[$cnt], "=");
		if ($idx === false) {
			// Token not found.
			continue;
		}
		$packId = substr($arrPackages[$cnt], 0, $idx);
		$rank = substr($arrPackages[$cnt], $idx + 1);

		// Get package.
		$frsp = new FRSPackage($group, $packId);
		if (!$frsp || !is_object($frsp)) {
			exit_error(_('Could Not Get FRS Package'),'frs');
		}
		elseif ($frsp->isError()) {
			exit_error($frsp->getErrorMessage(),'frs');
		}

		// Update rank on package.
		if (!$frsp->updateRank($rank)) {
			exit_error($frsr->getErrorMessage(), 'frs');
		}
	}
	$feedback .= "Arranged packages.";
}

frs_admin_header(array('title'=>'Arrange Packages','group'=>$group_id));
?>

<link rel="stylesheet" href='/js/jquery-ui-1.10.1.custom.min.css' />
<script src='/js/jquery-ui-1.10.1.custom.min.js'></script>
<style>
.sortable {
	list-style-type: none;
	margin: 0;
	padding: 0;
	width: 60%;
}
.sortable li {
}
.sortable li span {
	position: absolute;
	margin-left: 0em;
}
.sortable li label {
	margin-left: 1.0em;
}
</style>

<script>
	$(function() {
		// Initialization.
		var data = "";
		$(".sortable li").each(function(i, el) {
			var packId = $(el).attr("id");
			data += packId + "=" + $(el).index() + ",";
		});

		$("#header_order").val(data.slice(0, -1));

		$(".sortable").sortable({
			stop: function(event, ui) {
				// Get order of elements in each sortable
				// identified by package ids.
				var data = "";
				$(".sortable li").each(function(i, el) {
					var packId = $(el).attr("id");
					data += packId + "=" + $(el).index() + ",";
				});

				$("#header_order").val(data.slice(0, -1));
				
			}
		});
		$(".sortable").disableSelection();
	});
</script>
 

<?php

// Get a list of packages in this group.
$strFrsQuery = "SELECT * FROM frs_package " .
	"WHERE group_id=$1 " .
	"ORDER BY simtk_rank, status_id, name";
$res = db_query_params($strFrsQuery, array($group_id));
$rows = db_numrows($res);
if ($rows > 0) {
?>

<div><h3>Arrange packages by clicking and moving blue panels.</h3></div>

<form action="/frs/admin/arrangepackage.php" method="post">

<div>
<?php
	echo "<input type='hidden' name='func' value='arrange_package'/>";
	echo "<input type='hidden' name='group_id' value='" . $group_id . "'/>";
	echo "<input type='hidden' id='header_order' name='header_order' value='" . $header_order . "'/>";

	echo '<ul class="sortable">';

	// Find all packages in this group.
	// Packages are already ordered by rank and name.
	for ($cnt = 0; $cnt < $rows; $cnt++) {
		echo "<li id='" . 
			db_result($res, $cnt, 'package_id') .
			"' class='ui-state-default'>";
		echo "<span class='ui-icon ui-icon-arrowthick-2-n-s'></span>";
		// File name.
		echo "<label>" . db_result($res, $cnt, 'name');

		if (db_result($res, $cnt, 'is_public') != "1") {
			// Private.
			if (db_result($res, $cnt, 'status_id') != "1") {
				// Hidden.
				echo "&nbsp;(Private/Hidden)";
			}
			else {
				echo "&nbsp;(Private)";
			}
		}
		else {
			// Public
			if (db_result($res, $cnt, 'status_id') != "1") {
				// Hidden.
				echo "&nbsp;(Hidden)";
			}
			else {
				// Public/Not-hidden.
			}
		}
		echo "<label/></li>";
	}

	// End sortable section.
	echo '</ul>';
?>
<input style='margin-top:5px;' type='submit' name='submit' value='Update' class="btn-cta" />
</div>
</form>
<?php
}


frs_admin_footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
