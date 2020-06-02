<?php
/**
 * Project Admin: Arrange Releases of a Package
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
require_once $gfcommon.'frs/FRSRelease.class.php';
require_once $gfcommon.'frs/FRSFile.class.php';
require_once $gfcommon.'frs/include/frs_utils.php';

$group_id = getIntFromRequest('group_id');
$package_id = getIntFromRequest('package_id');
$release_id = getIntFromRequest('release_id');
$header_order = getIntFromRequest('header_order');

if (!$group_id) {
	exit_no_group();
}
if (!$package_id || !$release_id) {
	session_redirect('/frs/admin/?group_id='.$group_id);
}

$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'frs');
}
session_require_perm ('frs', $group_id, 'write') ;

// Get package.
$frsp = new FRSPackage($group, $package_id);
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

$upload_dir = forge_get_config('ftp_upload_dir') . "/" . $group->getUnixName();


// Arrange release info
if (getStringFromRequest('submit')) {

	$header_order = getStringFromRequest('header_order');
	$arrFiles = explode(",", $header_order);
	for ($cnt = 0; $cnt < count($arrFiles); $cnt++) {
		$idx = stripos($arrFiles[$cnt], "=");
		if ($idx === false) {
			// Token not found.
			continue;
		}
		$fileId = substr($arrFiles[$cnt], 0, $idx);
		$rank = substr($arrFiles[$cnt], $idx + 1);
		$header = getStringFromRequest($fileId);

		if (!$frsr->arrangeFiles($fileId, $rank, $header)) {
			exit_error($frsr->getErrorMessage(),'frs');
		}
	}
	$feedback .= "Arranged Release.";
}

frs_admin_header(array('title'=>'Arrange Releases','group'=>$group_id));
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
			var fileId = $(el).attr("id");
			data += fileId + "=" + $(el).index() + ",";
		});

		$("#header_order").val(data.slice(0, -1));

		$(".sortable").sortable({
			stop: function(event, ui) {
				// Get order of elements in each sortable
				// identified by file ids.
				var data = "";
				$(".sortable li").each(function(i, el) {
					var fileId = $(el).attr("id");
					data += fileId + "=" + $(el).index() + ",";
				});

				$("#header_order").val(data.slice(0, -1));
				
			}
		});
		$(".sortable").disableSelection();
	});
</script>
 

<?php

// Get a list of files associated with this release
$strFrsQuery = "SELECT *, " .
	"(fft.name != 'Documentation') AS not_doc " .
	"FROM frs_file ff " .
	"JOIN frs_filetype fft ON ff.type_id=fft.type_id " .
	"WHERE release_id=$1 " .
	"ORDER BY not_doc desc, simtk_rank";
$res = db_query_params($strFrsQuery, array($release_id));
$rows = db_numrows($res);
if ($rows > 0) {
?>

<div><h3>Arrange files in Release <?php echo $frsr->getName(); ?> of Package <?php echo $frsp->getName(); ?> by clicking and moving blue panels.</h3></div>

<form action="/frs/admin/arrangerelease.php" method="post">

<div>
<?php
	echo "<input type='hidden' name='func' value='arrange_package'/>";
	echo "<input type='hidden' name='group_id' value='" . $group_id . "'/>";
	echo "<input type='hidden' name='package_id' value='" . $package_id . "'/>";
	echo "<input type='hidden' name='release_id' value='" . $release_id . "'/>";
	echo "<input type='hidden' id='header_order' name='header_order' value='" . $header_order . "'/>";

	// Find all files in the release.
	// Files are already ordered by type and rank.
	$typeId = -1;
	for ($cnt = 0; $cnt < $rows; $cnt++) {
		$theTypeId = db_result($res, $cnt, 'not_doc');
		if ($theTypeId != $typeId) {
			// A new type is found.
			if ($typeId != -1) {
				// End previous sortable section.
				echo '</ul>';
			}

			// Get name for the file type (e.g. "Documentation". )
			if (db_result($res, $cnt, 'name') != "Documentation") {
				echo '<h4>Download Links</h4>';
			}
			else {
				echo '<h4>Documentation Links</h4>';
			}
			echo '<ul class="sortable">';
			$typeId = $theTypeId;
		}
			
?>
		<li id='<?php echo db_result($res,$cnt,'file_id'); ?>' 
			class="ui-state-default">
			<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
<?php 
		// Get header.
		$header = db_result($res, $cnt, 'simtk_filename_header');
		echo "<label for='header'>Optional Header:</label>&nbsp;";
		echo "<input name='" . db_result($res,$cnt,'file_id') . 
			"' type='text' value='" . $header . 
			"' size='35' maxlength='50' />"; 
		echo "</label>";

		// File name.
		echo "<label>" . db_result($res, $cnt, 'filename');
?>
		</li>
<?php
	}
	if ($typeId != -1) {
		// End sortable section.
		echo '</ul>';
	}
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
