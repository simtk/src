<?php

/**
 *
 * frs_admin_front.php
 * 
 * Front page of file downloads administration.
 *
 * Copyright 2005-2019, SimTK Team
 *
 * This file is part of the SimTK web portal originating from        
 * Simbios, the NIH National Center for Physics-Based               
 * Simulation of Biological Structures at Stanford University,      
 * funded under the NIH Roadmap for Medical Research, grant          
 * U54 GM072970, with continued maintenance and enhancement
 * funded under NIH grants R01 GM107340 & R01 GM104139, and 
 * the U.S. Army Medical Research & Material Command award 
 * W81XWH-15-1-0232R01.
 * 
 * SimTK is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 * 
 * SimTK is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details. 
 * 
 * You should have received a copy of the GNU General Public 
 * License along with SimTK. If not, see  
 * <http://www.gnu.org/licenses/>.
 */ 
 
// Variables used: $HTML, $GLOBALS['HTML'], $group_id, $cur_group_obj, $pub_sql

require_once "frs_admin_data_util.php";
require_once "construct_admin_ui.php";

// Retrieve packages, releases, and files info for the given group.
$thePackages = getFrsPackagesInfo($group_id, $pub_sql);

//debugPackages($thePackages);

echo "<div style='margin-left:40px;';>";

// Construct the UI.
constructUI($HTML, $group_id, $cur_group_obj, $thePackages, $package_id, $release_id);

// Close "margin-left" div.
echo "</div>";


// Construct the UI.
function constructUI($HTML, $groupId, $groupObj, $thePackages, $package_id, $release_id) {

	// Keep track of last header index for numbering the div sequentially.
	$lastHeaderMajorIdx = 0;
	$lastHeaderMinorIdx = 0;

	$strCreatePackageLink = "/frs/admin/createpackage.php?" .
		"group_id=" . $groupId;
	$strArrangePackageLink = "/frs/admin/arrangepackage.php?" .
		"group_id=" . $groupId;
	$strUpdateOverviewAndNotesLink = "/frs/admin/updateoverviewnotes.php?" .
		"group_id=" . $groupId;
	echo "<div style='margin-bottom:5px;'>";
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strCreatePackageLink . '">' .
		'Create New Package</a></span>&nbsp;';
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strArrangePackageLink . '">' .
		'Arrange Package</a></span>&nbsp;';
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strUpdateOverviewAndNotesLink . '">' .
		'Update Overview and Notes</a></span>&nbsp;';
	echo "</div>";

	// Header for the UI.
	constructHeaderUI($groupId, $package_id, $release_id);

	// Add script for opening the release panel in given package.
	// Find the packages and open latest latest release if specified.
	foreach ($thePackages as $idxPack=>$packageInfo) {
		// Get the package id.
		$thisPackageId = $packageInfo["package_id"];

		if ($package_id != null && $release_id != null && $package_id == $thisPackageId) {
			// Package and release ids are specified. Open the release in the package.
			genReleasePanelOpenScript($package_id, $release_id);
		}
		else  {
			if ($packageInfo["openlatest"] == 1) {
				// Open latest release in package.
				genReleasePanelOpenScript($thisPackageId, null);
			}
		}
	}

	// Iterate over the packages.
	foreach ($thePackages as $idxPack=>$packageInfo) {

		// Get the releases in this package.
		$theReleases = $packageInfo["releases"];

/*
		$numFiles = getNumOfFilesInLatestRelease($theReleases);
		if ($numFiles <= 0) {
			// No files in latest release; skip.
			continue;
		}
*/

		// Construct UI for given package.
		constructPackageUI($HTML, $groupId, $groupObj, $packageInfo, 
			$lastHeaderMajorIdx, $lastHeaderMinorIdx);

		// Note: The latest release goes first.
		// Only show citations on the latest release.
		$isLatestRelease = true;
		foreach ($theReleases as $idxRel=>$releaseInfo) {

			// Construct UI for given release.
			constructReleaseUI($HTML, $groupId, $groupObj,
				$packageInfo, $releaseInfo, $isLatestRelease);

			// Construct UI for each file.
			$theFiles = $releaseInfo["files"];
			$theFileType = null;
			$releaseHasFiles = false;
			foreach ($theFiles as $idxFile=>$fileInfo) {
				$releaseHasFiles = true;
				$tmpFileType = $fileInfo["not_doc"];
				if ($theFileType !== $tmpFileType) {

					if ($theFileType !== null) {
						// Close previous header.
						// No need if header was not present yet; 
						// i.e. $theFileType === null.
						closeFilesHeader();
					}

					$theFileType = $tmpFileType;
					constructFilesHeader($theFileType, 
						$lastHeaderMajorIdx, $lastHeaderMinorIdx,
						$packageInfo, $releaseInfo, $isLatestRelease);
				}
				constructFileUI($groupId, 
					$packageInfo, $releaseInfo, $fileInfo);
			}

			// Close the release UI construction.
			closeReleaseUI($groupId, $packageInfo, $releaseInfo, 
				$isLatestRelease, $lastHeaderMajorIdx, $lastHeaderMinorIdx,
				$releaseHasFiles, $idxRel);
			$isLatestRelease = false;
		}

		// Close the package UI construction.
		closePackageUI($packageInfo);
	}

	closeHeaderUI($groupObj);

	if (count($thePackages) < 1) {
		// There are no packages available.
		// echo "<h1>" . _('No File Packages') . "</h1>";
		echo "<div class='warning'>" . 
			_('There are no file packages defined for this project.') . 
			"</div>";
		return;
	}
}


// Generate script for opening release panel in the given package.
function genReleasePanelOpenScript($packageId, $releaseId) {

	if ($packageId != null && $releaseId != null) {

		// Both package_id and release_id are specified.
		// Expand the specific release panel.
		echo "<script>";
		echo "$(function() {";

			// Expand previous release first if needed.
			echo 'if ($(".panel' . $packageId . '_' . $releaseId . 
				'").parent().hasClass("previousReleasesPanel")) {';
				echo '$(".previousReleases' . $packageId . '").click();';
			echo '}';

			// Expand hidden release first if needed.
			echo 'if ($(".panel' . $packageId . '_' . $releaseId .
				'").parent().hasClass("hiddenReleasesPanel")) {';
				echo '$(".hiddenReleases' . $packageId . '").click();';
			echo '}';

			// Expand release
			echo '$(".panel' . $packageId . '_' . $releaseId .
				'>h2>.expander").click();';

			// Both offset() and non-zero delay is necessary.
			// Otherwise, the page stays at the top.
			echo '$("html, body").animate({scrollTop: $(".download_date' . 
				$packageId . '_' . $releaseId . 
				'").offset().top}, 500);';

		echo "});";
		echo "</script>";
	}
	else if ($packageId != false) {

		// Only package_id is specified.
		// Expand the releases panel within the package.
		echo "<script>";
		echo "$(function() {";

			// Expand previous release first if needed.
			echo 'if ($(".panel' . $packageId .
				'").parent().hasClass("previousReleasesPanel")) {';
				echo '$(".previousReleases' . $packageId . '").click();';
			echo '}';

			// Expand hidden release first if needed.
			echo 'if ($(".panel' . $packageId .
				'").parent().hasClass("hiddenReleasesPanel")) {';
				echo '$(".hiddenReleases' . $packageId . '").click();';
			echo '}';

			// Expand release
			echo '$(".panel' . $packageId . '>h2>.expander").click();';

/*
			// Both offset() and non-zero delay is necessary.
			// Otherwise, the page stays at the top.
			echo '$("html, body").animate({scrollTop: $(".download_date' .  
				$packageId .
				'").offset().top}, 500);';
*/

		echo "});";
		echo "</script>";
	}
	else {
		// No expansion of panel(s).
	}
}


?>

