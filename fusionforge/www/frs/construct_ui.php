<?php

/**
 *
 * construct_ui.php
 * 
 * Construct UI for downloads display.
 *
 * Copyright 2005-2020, SimTK Team
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
 
require_once $gfwww.'project/project_utils.php';

// Construct header portion of UI.
function constructHeaderUI($HTML, $groupObj, $theGroupInfo, $groupId, $package_id, $release_id) {

?>

<script type="text/javascript" src="/themes/simtk/js/simple-expand.js"></script>
<script type="text/javascript">

$(function() {
	$('.expander').simpleexpand();
	// Find "Downloads" header and make it a hyperlink to this "Downloads" page.
	$(".maindiv>h2:contains('Downloads')").html(
		"<a href='/frs/?group_id=" + 
		<?php echo $groupId; ?> +
		"'>Downloads</a>");
});

$(document).ready(function() {
	// Handle popover show and hide.
	$(".myPopOver").hover(function() {
		$(this).find(".popoverLic").popover("show");
	});
	$(".myPopOver").mouseleave(function() {
		$(this).find(".popoverLic").popover("hide");
	});

	// Scroll to package id anchor if present.
	// NOTE: This script is necessary because Chrome and Edge browsers 
	// sometimes do not scroll to anchor.
	// Get URL and locate package id anchor.
	var theUrl = window.location.href;
	var idxPackId = theUrl.lastIndexOf("#pack_");
	if (idxPackId != -1) {
		// Package id anchor is present.
		var packId = theUrl.substr(idxPackId + 6).trim();
		// Check for numeric package id.
		if ($.isNumeric(packId)) {
			$("html, body").animate({
				scrollTop: $("#pack_" + packId).offset().top
				}, "slow");
		}
	}
});

</script>

<link rel="stylesheet" type="text/css" href="/themes/simtk/css/theme.css">
<link rel="stylesheet" type="text/css" href="/themes/simtk/css/carousel.css">

<div class="downloads_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">

<?php

	// Create submenu under downloads_main DIV, such that it does not
	// occupy the whole width of the page (rather than using the 
	// submenu population in Theme.class.php)
	$subMenuTitle = array();
	$subMenuUrl = array();
	$subMenuTitle[] = 'View Downloads';
	$subMenuUrl[] = '/frs/?group_id=' . $groupId;
	if (session_loggedin()) {
		if ($groupObj && is_object($groupObj) && !$groupObj->isError()) {
			if (forge_check_perm ('frs', $groupId, 'write')) {
				// Check permission before adding administrative menu items.
				$subMenuTitle[] = _('Reporting');
				$subMenuUrl[] = '/frs/reporting/downloads.php?group_id=' . $groupId;
				$subMenuTitle[] = _('Administration');
				$subMenuUrl[] = '/frs/admin/?group_id=' . $groupId;

			}
		}
	}
	// Show the submenu.
	echo $HTML->beginSubMenu();
	echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
	echo $HTML->endSubMenu();

	constructDownloadOverviewAndNotes($theGroupInfo, $groupId);
}


// Construct section for download overview and notes, if present.
function constructDownloadOverviewAndNotes($theGroupInfo, $groupId) {

	if (trim($theGroupInfo["download_overview"]) != "") {
		// Show download overview.
		echo '<div class="download_package" style="background-image:none;margin-bottom:20px;">';
		echo $theGroupInfo["download_overview"];
		if (trim($theGroupInfo["download_notes"]) != "") {
/*
			// Show download notes.
			echo '<div class="download_package" style="background-image:none;margin-bottom:20px;">';
			echo '<label>Notes:</label>&nbsp;';
			if ($theGroupInfo["preformatted"] == "1") {
				// Preformatted text.
				echo "<pre>";
			}
			echo '<p>' . $theGroupInfo["download_notes"] . '</p>';
			if ($theGroupInfo["preformatted"] == "1") {
				// Preformatted text.
				echo "</pre>";
			}
			echo '</div>';
*/
			echo '<br/><span class="download_extra">' . 
				'<a href="/frs/showdownloadnotes.php?group_id=' . $groupId . 
				'">Downloads instructions and details</a>' . '</span>';
		}
		echo '</div>';
	}
}

// Close header div.
function closeHeaderUI($groupObj) {

	// Close "main_col" div.
	echo "</div>";

	// "side_bar".
	constructSideBar($groupObj);

	// Close "display: table; width: 100%" div.
	echo '</div>';
	// Close "downloads_main" div.
	echo '</div>';

}


// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div style="padding-top:20px;" class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

        echo '</div>';
}

// Construct UI for the given package.
function constructPackageUI($HTML, $groupId, $groupObj, $packageInfo,
	&$lastHeaderMajorIdx, &$lastHeaderMinorIdx) {

	// Update major header index. Reset minor header index.
	$lastHeaderMajorIdx = $lastHeaderMajorIdx + 1;
	$lastHeaderMinorIdx = 0;

	$packId = $packageInfo["package_id"];
	$packName = $packageInfo["name"];
	$packDesc = $packageInfo["description"];
	$packLogo = $packageInfo["logo"];
	$packDoi = $packageInfo["doi"];

	// Set up FRSPackage for package.
	$frsPackage = new FRSPackage($groupObj, $packId);

	$package_name_protected = $HTML->toSlug($packName);
	echo '<div class="download_package">';

	// Construct div for monitoring package.
	$strFollows = constructMonitored($groupId, $groupObj, $packId);

	echo "<div class='project_representation'>";

	if (trim($packLogo) != "") {
		echo "<div class='wrapper_img'>";
		echo '<img onError="this.onerror=null;this.src=' . "'" . '/logos/_thumb' . "';" . '"' .
			" alt='Image not available'" .
			" src='/logos-frs/" . $packLogo . "'/>";
		echo "</div>";
	}
	echo "<div class='wrapper_text'>";
	echo "<a href='#pack_" . $packId . "'>";
	echo "<div id='pack_" . $packId . "' class='download_title'>" . $packName;
	if ($packageInfo["is_public"] != "1") {
		// Private.
		if ($packageInfo["status_id"] != "1") {
			// Hidden.
			echo "&nbsp;(Private/Hidden)";
		}
		else {
			echo "&nbsp;(Private)";
		}
	}
	else {
		// Public
		if ($packageInfo["status_id"] != "1") {
			// Hidden.
			echo "&nbsp;(Hidden)";
		}
		else {
			// Public/Not-hidden.
		}
	}
	echo "</div>"; // download_title
	echo "</a>";

	if (isset($packageInfo["doi_identifier"])) {
		$packDoiIdentifier = $packageInfo["doi_identifier"];
	}
	if ($packDoi) {
		// Package has requested DOI.
		if (empty($packDoiIdentifier)) {
			$packDoiIdentifier = " pending";
		}
		echo '<div class="download_details" style="margin-left:0px;">doi:' . $packDoiIdentifier . '</div><br/>';
	}

	echo "<div class='download_description'>" . $packDesc . "</div>";
	echo "</div>"; // wrapper_text
	echo "</div>"; // project_representation

}


// Construct div for monitoring package.
function constructMonitored($groupId, $groupObj, $packId) {

	echo '<div class="download_follow">';

	$frsPackage = new FRSPackage($groupObj, $packId);
	if ($frsPackage->isMonitoring()) {
		$strUrl = '/frs/monitor.php?filemodule_id=' . $packId .
			'&amp;group_id=' . $groupId .'&amp;stop=1';
		echo '<a href="' .  $strUrl . '" title="Click to stop receiving email about package update.">Unfollow</a>';
	}
	else {
		$strUrl = '/frs/monitor.php?filemodule_id='. $packId .
			'&amp;group_id=' . $groupId .'&amp;start=1';
		echo '<a href="' .  $strUrl . '" title="Click to receive email about package update.">Follow</a>';
	}
	echo '</div>';
}


// Close the package UI construction.
function closePackageUI($packageInfo) {
	$cntReleases = count($packageInfo["releases"]);
	if ($cntReleases > 1) {
		// There is at least 1 previous release or hidden release.
		// Close the DIVs first.

		// Close "content tinted" div.
		echo '</div>';
		// Close '<div id="$panelName">.
		echo '</div>';
	}

	// Close "download_package" div.
	echo '</div>';
}


// Show citations of given package.
function listCitations($groupId, $packageInfo) {

	$arrCitations = $packageInfo["citations"];
	$numCitations = count($arrCitations);
	if ($numCitations > 0) {
		echo '<div class="download_citation">';

		if ($packageInfo["countCitations"] > 0) {
			echo '<div class="download_subtitle">PLEASE CITE THESE PAPERS</div>';
			for ($cntCites = 0; $cntCites < $numCitations; $cntCites++) {
				$citeInfo = $arrCitations[$cntCites];
				if ($citeInfo["cite"]) {
					echo '<div style="clear:both"></div>';
					echo '<p>' . 
						$citeInfo["citation"] . 
						' (' . $citeInfo["citation_year"] . ') ';
					if ($citeInfo["url"] != false &&
						$citeInfo["url"] != "") {
						echo '<span class="download_extra">' .
							'<a href="' . $citeInfo["url"] . '">View</a>' . 
							'</span>';
					}
					echo '</p>';
				}
			}
		}
		if ($packageInfo["countNonCitations"] > 0) {
			echo '<div class="download_subtitle">ADDITIONAL PAPERS</div>';
			for ($cntCites = 0; $cntCites < $numCitations; $cntCites++) {
				$citeInfo = $arrCitations[$cntCites];
				if (!$citeInfo["cite"]) {
					echo '<div style="clear:both"></div>';
					echo '<p>' . 
						$citeInfo["citation"] . 
						' (' . $citeInfo["citation_year"] . ') ';
					if ($citeInfo["url"] != false &&
						$citeInfo["url"] != "") {
						echo '<span class="download_extra">' .
							'<a href="' . $citeInfo["url"] . '">View</a>' . 
							'</span>';
					}
					echo '</p>';
				}
			}
		}
		echo '</div>';
	}
}

// Check whether the release contains URLs only.
function checkURLsOnly($releaseInfo) {

	$theFiles = $releaseInfo["files"];
	foreach ($theFiles as $idxFile=>$fileInfo) {
		$simtkFileType = $fileInfo['simtk_filetype'];
		if ($simtkFileType != "URL") {
			// A non-URL file found! Done.
			return false;
		}
	}

	return true;
}

// Construct UI for the given release.
function constructReleaseUI($HTML, $groupId, $groupObj, 
	$release_id, $packageInfo, $releaseInfo, $isLatestRelease) {

	$packId = $packageInfo["package_id"];
	$packName = $packageInfo["name"];
	$frsPackage = new FRSPackage($groupObj, $packId);

	$relId = $releaseInfo["release_id"];
	$relName = $releaseInfo["name"];
	$relNotes = $releaseInfo["notes"];
	$relChanges = $releaseInfo["changes"];
	$relDesc = $releaseInfo["description"];
	$relDate = $releaseInfo["release_date"];
	$relDoi = $releaseInfo["doi"];
	if (isset($releaseInfo["doi_identifier"])) {
		$relDoiIdentifier = $releaseInfo["doi_identifier"];
	}
	if ($relDoi) {
		if (empty($relDoiIdentifier)) {
			$relDoiIdentifier = " pending";
		}
		echo '<div class="download_details" style="margin-left:0px;">doi:' . $relDoiIdentifier . '</div><br/>';
	}

	// Check if the release contains URLs only.
	$isURLsOnly = checkURLsOnly($releaseInfo);

	// Use package id and release id to identify panel.
	// Create both class names for package id only and package with release id.
	// such that panel can be selected by package id or package_id . "_" . "release_id".
	$downloadDatePackageClassName = "";
	if ($isLatestRelease === true) {
		// Include package id for lastest release for selection.
		$downloadDatePackageClassName = "download_date" . 
			$packageInfo["package_id"];
	}
	$downloadDateReleaseClassName = "download_date" . 
		$packageInfo["package_id"] . "_" . 
		$releaseInfo["release_id"];

	// Output string in format of "DEC 12,2015".

	if ($isLatestRelease) {
		echo '<div class="download_subtitle">' . $relName . '</div>';
	}
	else {
		echo '<div class="download_subtitle2">' . $relName . '</div><br/>';
	}
	
	echo '<div class="download_date ' . 
		$downloadDatePackageClassName . ' ' .
		$downloadDateReleaseClassName . '">' . 
		date('M d, Y', $relDate) . '</div>';

	echo '<div style="clear:both"></div>';
	echo '<p>' . $relDesc;
	// Show notes hyperlink if notes or changelog is present.
	if (trim($relNotes) != "" || trim($relChanges) != "") {
		echo '<span class="download_extra">' .
			'<a href="/frs/shownotes.php?release_id=' . $relId . '">Notes</a>' .
			'</span>';
	}
	// Display license.
	echo "&nbsp;&nbsp;" . genLicenseLink($packId, $strLic);
	echo '</p>';

	// Download Package button is only shown for the latest release.
	if ($packageInfo["show_download_button"] == 1 && class_exists('ZipArchive')) {
		// Only show "Download Package button if release has non-URL file(s).
		if ($isURLsOnly === false) {
			if ($isLatestRelease) {
				// Generate link to latest-release-as-zip.
				$strDownloadLink = '/frs/download_confirm.php/latestzip/' . 
					$packId . '/' . 
					$frsPackage->getNewestReleaseZipName() . 
					'?group_id=' . $groupId;
			}
			else {
				// Generate link to specified release.
				$strDownloadLink = '/frs/download_confirm.php/release/' . $relId . 
					'?group_id=' . $groupId;
			}

			echo '<div class="download_btn">' .
				'<a class="btn-blue" href="' . $strDownloadLink . '">' .
				'Download Package</a></div>';
		}
	}
}

// Close the release UI construction.
function closeReleaseUI($groupId, $release_id, $packageInfo, $releaseInfo, 
	$isLatestRelease, &$lastHeaderMajorIdx, &$lastHeaderMinorIdx,
	$releaseHasFiles, $curIdxRel) {

	$packageId = $packageInfo["package_id"];
	$relId = $releaseInfo["release_id"];
/*
	if ($release_id && $release_id != $relId) {
		// Selected a release, but not the current one. Skip.
		return;
	}
*/

	if ($releaseHasFiles) {
		// Close files header.
		closeFilesHeader();
	}

	// Get status of current release (i.e. visible or hidden).
	$curReleaseStatus = $releaseInfo["status_id"];

	// Get next release, if available.
	$nextReleaseStatus = false;
	$nextIdxRel = $curIdxRel + 1;
	$cntReleases = count($packageInfo["releases"]);
	if ($nextIdxRel < $cntReleases) {
		// Get info of next release.
		$nextReleaseInfo = $packageInfo["releases"][$nextIdxRel];
		// Get status of next release (i.e. visible or hidden).
		$nextReleaseStatus = $nextReleaseInfo["status_id"];
	}

	if ($curIdxRel == 0) {
		// Release with index of 0 is the latest release.

		// List citations, if any.
		listCitations($groupId, $packageInfo);

		if ($nextReleaseStatus === false) {
			// No other releases. No action needed.
		}
		else if ($nextReleaseStatus == 1) {
			// There are previous releases. Construct header.
			constructPreviousReleasesHeader($lastHeaderMajorIdx, $lastHeaderMinorIdx, $packageId);
		}
		else if ($nextReleaseStatus != 1) {
			// There are hidden releases. Construct header.
			constructHiddenReleasesHeader($lastHeaderMajorIdx, $lastHeaderMinorIdx, $packageId);
		}
	}
	else {
		if ($nextReleaseStatus === false) {
			// No other releases. No action needed here.
			// NOTE: At package closing, the DIVs will be closed.
		}
		else if ($curReleaseStatus == 1 && 
			$nextReleaseStatus != 1) {

			// Done with visible release. There are hidden releases.
			// Start the panel for hidden releases.
			// Close the DIVs for current visible releases first.

			// Close "content tinted" div.
			echo '</div>';
			// Close '<div id="$panelName">.
			echo '</div>';

			// Construct header.
			constructHiddenReleasesHeader($lastHeaderMajorIdx, $lastHeaderMinorIdx, $packageId);
		}
	}

	echo '<br/>';
}

// Generate div for previous releases.
function constructPreviousReleasesHeader(&$lastHeaderMajorIdx, &$lastHeaderMinorIdx, $packageId) {

	// Update major header index. Reset minor header index.
	$lastHeaderMajorIdx = $lastHeaderMajorIdx + 1;
	$lastHeaderMinorIdx = 0;
	$panelName = "panel" . $lastHeaderMajorIdx;

	echo '<div id="' . $panelName . '">';
	echo '<h2><a class="expander toggle previousReleases' . $packageId . '" href="#">Previous Releases</a></h2>';
	echo '<div class="download_border"></div>';
	// Container panel for previous releases.
	echo '<div class="content tinted previousReleasesPanel">';
}

// Generate div for hidden releases.
function constructHiddenReleasesHeader(&$lastHeaderMajorIdx, &$lastHeaderMinorIdx, $packageId) {

	// Update major header index. Reset minor header index.
	$lastHeaderMajorIdx = $lastHeaderMajorIdx + 1;
	$lastHeaderMinorIdx = 0;
	$panelName = "panel" . $lastHeaderMajorIdx;

	echo '<div id="' . $panelName . '">';
	echo '<h2><a class="expander toggle hiddenReleases' . $packageId . '" href="#">Hidden Releases</a></h2>';
	echo '<div class="download_border"></div>';
	// Container panel for hidden releases.
	echo '<div class="content tinted hiddenReleasesPanel">';
}


// Construct header for files section.
function constructFilesHeader($theFileType, 
	&$lastHeaderMajorIdx, &$lastHeaderMinorIdx,
	$packageInfo, $releaseInfo, $isLatestRelease) {

	// Increment header index.
	$lastHeaderMinorIdx = $lastHeaderMinorIdx + 1;

	// Use package id and release id to identify panel.
	// Create both class names for package id only and package with release id.
	// such that panel can be selected by package id or package_id . "_" . "release_id".
	$panelPackageClassName = "";
	if ($isLatestRelease === true) {
		// Include package id for lastest release for selection.
		$panelPackageClassName = "panel" . $packageInfo["package_id"];
	}
	$panelReleaseClassName = "panel" . 
		$packageInfo["package_id"] . "_" . 
		$releaseInfo["release_id"];

	if ($theFileType === "t") {
		// Download links; i.e not documentation.
		echo '<div class="' . 
			$panelReleaseClassName . ' ' . 
			$panelPackageClassName . '">';
?>
	<h2><a class="expander toggle" id="<?php echo $releaseInfo["release_id"]; ?>" href="#">Download Links</a></h2>
	<div class="download_border"></div>
		<div class="content">
<?php

	}
	else {
		// Documentation links.
		echo '<div class="' . 
			$panelReleaseClassName . ' ' . 
			$panelPackageClassName . '">';
?>
	<h2><a class="expander toggle" href="#">Documentation Links</a></h2>
	<div class="download_border"></div>
		<div class="content">
<?php

	}
}

// Close Files header.
function closeFilesHeader() {
	// Close files divs.

	// Close "content" div.
	echo "</div>";
	// Close '<div id="$panelName">.
	echo "</div>";
}

// Construct UI for the given file.
function constructFileUI($groupId, $release_id, 
	$packageInfo, $releaseInfo, $fileInfo) {

	$relId = $releaseInfo["release_id"];
/*
	if ($release_id && $release_id != $relId) {
		// Selected a release, but not the current one. Skip.
		return;
	}
*/

	$packId = $packageInfo["package_id"];

	$fileId = $fileInfo["file_id"];
	$fileName = $fileInfo["filename"];
	$fileTime = $fileInfo["release_time"];
	$fileSize = $fileInfo["file_size"];
	$fileDownloads = $fileInfo["downloads"];
	$fileProcessor = $fileInfo["processor"];
	$fileFileType = $fileInfo["filetype"];
	$filenameHeader = $fileInfo["filename_header"];
	$fileDescription = $fileInfo["description"];
	$simtkFileType = $fileInfo["simtk_filetype"];
	$fileLocation = $fileInfo["filelocation"];
	$not_doc = $fileInfo["not_doc"];
	$fileDoi = $fileInfo["doi"];
	if (isset($fileInfo["doi_identifier"])) {
		$fileDoiIdentifier = $fileInfo["doi_identifier"];
	}

	// Generate label for file size (in bytes, KB, or MB).
	$strFileSize = $fileSize;
	if (intval($fileSize/1024) >= 1) {
		$strFileSize = intval($fileSize/1024) . " KB";
	}
	if (intval($fileSize/1024/1024) >= 1) {
		$strFileSize = intval($fileSize/1024/1024) . " MB";
	}

	if (trim($filenameHeader) != "") {
		// Header for file.
		echo '<div class="download_subtitle2">' . $filenameHeader . '</div><br/>';
	}


	if ($not_doc == "t") {
		// Download Links.
		
		if ($simtkFileType == "URL") {
			echo '<div class="download_link">' . 
				'<a href="/frs/download_start.php/file/' . 
				$fileId . '/' . $fileName . 
				'?group_id=' . $groupId .
				'">';
			echo $fileName . ' (URL)</a></div>';
		}
		else {
			echo '<div style="clear:both"></div>';
			if ($simtkFileType == "GitHubArchive" && $fileSize == 0) {
				// File downloading. Not ready yet.
				echo '<div class="download_link grey">';
				echo $fileName . '</div>';
			}
			else {
				echo '<div class="download_link">' . 
					'<a href="/frs/download_confirm.php/file/' . 
					$fileId . '/' . $fileName . 
					'?group_id=' . $groupId .
					'">';
				echo $fileName . '</a></div>';
			}
		}
		
		echo '<div class="download_date">' . date('M d, Y', $fileTime) . '</div>';
		echo '<div class="download_size">' . $strFileSize . '</div>';
		echo '<div class="download_details">' . $fileProcessor . '</div>';
		echo '<div class="download_details">' . $fileFileType . '</div>';
		if ($fileDoi) {
			if (empty($fileDoiIdentifier)) {
				$fileDoiIdentifier = " pending";
			}
			echo '<div class="download_details">doi:' . $fileDoiIdentifier . '</div><br/>';
		}
		echo '<div class="download_text">' . $fileDescription . '</div>';

		if ($simtkFileType == "GitHubArchive" && $fileSize == 0) {
			// File downloading. Not ready yet.
			echo '<div class="download_size not_ready" ' .
				'id="notready_' . $packId . "_" . $relId . "_" . $fileId .
				'"><i>Updating from GitHub...</i></div>';
		}
	}
	else {
		// Documentation Links.
		if ($simtkFileType == "URL") {
			echo '<div style="clear:both"></div>';
			echo '<div class="download_link">' . 
				'<a href="/frs/download_start.php/file/' . 
				$fileId . '/' . $fileName . 
				'?group_id=' . $groupId .
				'">';
			echo $fileName . ' (URL)</a></div>';
			
		}
		else {
			echo '<div style="clear:both"></div>';
			if ($simtkFileType == "GitHubArchive" && $fileSize == 0) {
				// File downloading. Not ready yet.
				echo '<div class="download_link grey">';
				echo $fileName . '</div>';
			}
			else {
				echo '<div class="download_link">' . 
					'<a href="/frs/download_confirm.php/file/' . 
					$fileId . '/' . $fileName . 
					'?group_id=' . $groupId .
					'">';
				echo $fileName . '</a></div>';
			}
		}
		echo '<div class="download_date">' . date('M d, Y', $fileTime) . '</div>';
		echo '<div style="clear:both"></div>';
		if ($fileDoi) {
			if (empty($fileDoiIdentifier)) {
				$fileDoiIdentifier = " pending";
			}
			echo '<div class="download_size">doi:' . $fileDoiIdentifier . '</div><br/>';
		}
		echo '<div class="download_text">' . $fileDescription . '</div>';
		if ($simtkFileType == "GitHubArchive" && $fileSize == 0) {
			// File downloading. Not ready yet.
			echo '<div class="download_size not_ready" ' .
				'id="notready_' . $packId . "_" . $relId . "_" . $fileId .
				'"><i>Updating from GitHub...</i></div>';
		}
	}
	echo '<br/>';

}


// Generate string for display license.
function genLicenseLink($packId, &$strLic) {

	$strLicense = $strLic = "";

	// Get license.
	$res_agreement = db_query_params("SELECT simtk_custom_agreement, use_agreement " .
		"FROM frs_package fp " .
		"JOIN frs_use_agreement fua " .
		"ON fp.simtk_use_agreement=fua.use_agreement_id " .
		"WHERE fp.package_id=$1 " .
		"AND fua.use_agreement_id <> 0",
		array($packId));
	$numrows = db_numrows($res_agreement);
	if ($numrows > 0) {
		while ($row = db_fetch_array($res_agreement)) {
			$strLic = $row['simtk_custom_agreement'];
			$strUseAgreement = $row['use_agreement'];
		}
	}

	if (trim($strLic) != "") {
		// Generate popup string.
		// NOTE: Has to use "javscript://" to avoid 
		// automatically scrolling to top upon clicking.
		$strLicense = '<span class="myPopOver"><a href="javascript://" ' .
			'class="popoverLic" data-html="true" ' .
			'data-toggle="popover" data-placement="right" ' .
			'data-content="' . $strLic . 
			'" title="' . $strUseAgreement . ' Use Agreement" ' .
			'>' . 'View License' . '</a></span>';
	}

	return $strLicense;
}


?>
