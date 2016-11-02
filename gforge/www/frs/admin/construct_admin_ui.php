<?php

/**
 *
 * construct_admin_ui.php
 * 
 * Construct UI for downloads administration.
 *
 * Copyright 2005-2016, SimTK Team
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
function constructHeaderUI($groupId, $package_id, $release_id) {

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
</script>

<link rel="stylesheet" type="text/css" href="/themes/simtk/css/theme.css">
<link rel="stylesheet" type="text/css" href="/themes/simtk/css/carousel.css">

<style>
.btn-blue {
	margin-top:1px;
	margin-bottom:1px;
}
</style>

<div class="downloads_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">

<?php

}

// Close header div.
function closeHeaderUI($groupObj) {

	// Close "main_col" div.
	echo "</div>";

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

	echo '<div class="side_bar">';

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	// Close "side_bar" div.
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

	echo "<div class='download_package'>";

	echo "<div class='project_representation'>";

	if (trim($packLogo) != "") {
		echo "<div class='wrapper_img'>";
		echo "<img " .
			' onError="this.onerror=null;this.src=' . "'" . '/logos/_thumb' . "';" . '"' .
			" alt='Image not available' " .
			" src='/logos-frs/" . $packLogo . "' />";
		echo "</div>";
	}
	echo "<div class='wrapper_text'>";
	echo "<div class='download_title'>" . $packName;
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
	echo "<div class='download_description'>" . $packDesc . "</div>";
	echo "</div>"; // wrapper_text
	echo "</div>"; // project_representation

	// Update package.
	$strEditPackageLink = "/frs/admin/editpackage.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId;
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strEditPackageLink . '">' .
		'Update Package</a></span>&nbsp';

	// Delete package.
	$strDeletePackageLink = "/frs/admin/deletepackage.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId;
	if (!$packDoi) {
	   echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strDeletePackageLink . '">' .
		'Delete Package</a></span>&nbsp';
    }
	
	// Add release.
	$strAddReleaseLink = "/frs/admin/createrelease.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId;
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strAddReleaseLink . '">' .
		'Add Release</a></span>&nbsp';

	// Add citation.
	$strAddCitationLink = "/frs/admin/createcitation.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId;
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strAddCitationLink . '">' .
		'Add Citation</a></span>&nbsp';
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

	$packId = $packageInfo["package_id"];
	$arrCitations = $packageInfo["citations"];
	$numCitations = count($arrCitations);

	if ($numCitations > 0) {
		echo '<div class="download_citation">';

		echo '<table>';
		if ($packageInfo["countCitations"] > 0) {
			echo '<th><div class="download_subtitle">PLEASE CITE THESE PAPERS</div></th>';
			for ($cntCites = 0; $cntCites < $numCitations; $cntCites++) {
				$citeInfo = $arrCitations[$cntCites];
				if ($citeInfo["cite"] == "1") {
					echo '<tr>';
					echo '<td>' . 
						$citeInfo["citation"] . 
						' (' . $citeInfo["citation_year"] . ') ';
					if ($citeInfo["url"] != false &&
						$citeInfo["url"] != "") {
						echo '<span class="download_extra">' .
							'<a href="' . $citeInfo["url"] . '">View</a>' . 
							'</span>';
					}
					echo '</td>';

					$citationId = $citeInfo["citation_id"];
					$strUpdateCitationLink = "/frs/admin/editcitation.php?" .
						"group_id=" . $groupId . 
						"&package_id=" . $packId . 
						"&citation_id=" . $citationId;
					$strDeleteCitationLink = "/frs/admin/deletecitation.php?" .
						"group_id=" . $groupId . 
						"&package_id=" . $packId . 
						"&citation_id=" . $citationId;

					echo '<td><span class="download_btn">' .
						'<a class="btn-blue" href="' . $strUpdateCitationLink . '">' .
						'Update</a></span></td>';
					echo '<td><span class="download_btn">' .
						'<a class="btn-blue" href="' . $strDeleteCitationLink . '">' .
						'Delete</a></span></td>';

					echo '</tr>';
				}
			}
		}
		if ($packageInfo["countNonCitations"] > 0) {
			echo '<th><div class="download_subtitle">ADDITIONAL PAPERS</div></th>';
			for ($cntCites = 0; $cntCites < $numCitations; $cntCites++) {
				$citeInfo = $arrCitations[$cntCites];
				if ($citeInfo["cite"] != "1") {
					echo '<tr>';
					echo '<td>' . 
						$citeInfo["citation"] . 
						' (' . $citeInfo["citation_year"] . ') ';
					if ($citeInfo["url"] != false &&
						$citeInfo["url"] != "") {
						echo '<span class="download_extra">' .
							'<a href="' . $citeInfo["url"] . '">View</a>' . 
							'</span>';
					}
					echo '</td>';

					$citationId = $citeInfo["citation_id"];
					$strUpdateCitationLink = "/frs/admin/editcitation.php?" .
						"group_id=" . $groupId . 
						"&package_id=" . $packId . 
						"&citation_id=" . $citationId;
					$strDeleteCitationLink = "/frs/admin/deletecitation.php?" .
						"group_id=" . $groupId . 
						"&package_id=" . $packId . 
						"&citation_id=" . $citationId;

					echo '<td><span class="download_btn">' .
						'<a class="btn-blue" href="' . $strUpdateCitationLink . '">' .
						'Update</a></span></td>';
					echo '<td><span class="download_btn">' .
						'<a class="btn-blue" href="' . $strDeleteCitationLink . '">' .
						'Delete</a></span></td>';

					echo '</tr>';
				}
			}
		}
		echo '</table>';

		echo '</div>';
	}
}

// Construct UI for the given release.
function constructReleaseUI($HTML, $groupId, $groupObj, 
	$packageInfo, $releaseInfo, $isLatestRelease) {

	$packId = $packageInfo["package_id"];
	$packName = $packageInfo["name"];
	$frsPackage = new FRSPackage($groupObj, $packId);

	$relId = $releaseInfo["release_id"];
	$relName = $releaseInfo["name"];
	$relNotes = $releaseInfo["notes"];
	$relChanges = $releaseInfo["changes"];
	$relDesc = $releaseInfo["description"];
	$relDate = $releaseInfo['release_date'];
	$relDoi = $releaseInfo['doi'];

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
	echo '<div class="download_date ' . 
		$downloadDatePackageClassName . ' ' .
		$downloadDateReleaseClassName . '">' . 
		date('M d, Y', $relDate) . '</div>';

	// Add horizontal row of space before release.
	echo '<div>&nbsp;</div>';
	if ($isLatestRelease) {
		echo '<div class="download_subtitle">RELEASE: ' . $relName . '</div>';
	}
	else {
		echo '<div class="download_subtitle2">RELEASE: ' . $relName . '</div>';
	}

	// Generate links to edit/delete/arrange specified release.
	$strEditReleaseLink = "/frs/admin/editrelease.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId . 
		"&release_id=" . $relId;
	$strDeleteReleaseLink = "/frs/admin/deleterelease.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId . 
		"&release_id=" . $relId;
	$strAddFileLink = "/frs/admin/addfile.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId . 
		"&release_id=" . $relId;
	$strArrangeReleaseLink = "/frs/admin/arrangerelease.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId . 
		"&release_id=" . $relId;

	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strEditReleaseLink . '">' .
		'Update Release</a></span>&nbsp;';
	if (!$relDoi) {
	   echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strDeleteReleaseLink . '">' .
		'Delete Release</a></span>&nbsp;';
    }
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strAddFileLink . '">' .
		'Add File</a></span>&nbsp';
	echo '<span class="download_btn">' .
		'<a class="btn-blue" href="' . $strArrangeReleaseLink . '">' .
		'Arrange Release</a></span>&nbsp';
}

// Close the release UI construction.
function closeReleaseUI($groupId, $packageInfo, $releaseInfo, 
	$isLatestRelease, &$lastHeaderMajorIdx, &$lastHeaderMinorIdx,
	$releaseHasFiles, $curIdxRel) {

	$packageId = $packageInfo["package_id"];
	$relId = $releaseInfo["release_id"];
	$cntReleases = count($packageInfo["releases"]);

	// Close files header.
	if ($releaseHasFiles) {
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
	echo '<div class="content tinted previousReleasesPanel">';
}

// Generate div for hidden releases.
function constructHiddenReleasesHeader(&$lastHeaderMajorIdx, &$lastHeaderMinorIdx, $packageId) {

	// Update major header index. Reset minor header index.
	$lastHeaderMajorIdx = $lastHeaderMajorIdx + 1;
	$lastHeaderMinorIdx = 0;
	$panelName = "panel" . $lastHeaderMajorIdx;

	echo '<div id="' . $panelName . '">';
	echo '<h2><a class="expander toggle hiddenReleases' . $packageId . ' href="#">Hidden Releases</a></h2>';
	echo '<div class="download_border"></div>';
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
	<h2><a class="expander toggle" href="#">Download Links</a></h2>
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

	echo "<table>";
}

// Close Files header.
function closeFilesHeader() {

	// Close files divs.

	// Close table.
	echo "</table>";
	// Close "content" div.
	echo "</div>";
	// Close '<div id="$panelName">.
	echo "</div>";
}

// Construct UI for the given file.
function constructFileUI($groupId, 
	$packageInfo, $releaseInfo, $fileInfo) {

	$relId = $releaseInfo["release_id"];
	$packId = $packageInfo["package_id"];

	$fileId = $fileInfo['file_id'];
	$fileName = $fileInfo['filename'];
	$fileTime = $fileInfo['release_time'];
	$fileSize = $fileInfo['file_size'];
	$fileDownloads = $fileInfo['downloads'];
	$fileProcessor = $fileInfo['processor'];
	$fileFileType = $fileInfo['filetype'];
	$filenameHeader = $fileInfo['filename_header'];
	$fileDescription = $fileInfo['description'];
	$simtkFileType = $fileInfo['simtk_filetype'];
	$fileLocation = $fileInfo['filelocation'];
	$not_doc = $fileInfo['not_doc'];
	$doi = $fileInfo['doi'];
	$doi_identifier = $fileInfo['doi_identifier'];

	$tmp_col1 = util_make_link('/frs/download.php/file/' . $fileId . '/' . $fileName, $fileName);
	$tmp_col2 = date(_('Y-m-d H:i'), $fileTime);
	$tmp_col3 = human_readable_bytes($fileSize);
	$tmp_col4 = ($fileDownloads ? number_format($fileDownloads, 0) : '0');
	$tmp_col5 = $fileProcessor;
	$tmp_col6 = $fileFileType;
	$tmp_col7 = util_make_link('/frs/download.php/latestfile/' . $packId . '/' . $fileName, 
		_('Latest version'));

	// Generate label for file size (in bytes, kB, or mB).
	$strFileSize = $fileSize;
	if (intval($fileSize/1024) >= 1) {
		$strFileSize = intval($fileSize/1024) . " kB";
	}
	if (intval($fileSize/1024/1024) >= 1) {
		$strFileSize = intval($fileSize/1024/1024) . " mB";
	}

	if (trim($filenameHeader) != "") {
		// Header for file.
		echo '<th><div class="download_subtitle2">' . $filenameHeader . '</div></th>';
	}


	echo "<tr>";
	$strUpdateFileLink = "/frs/admin/editfile.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId .
		"&release_id=" . $relId .
		"&file_id=" . $fileId;
	$strDeleteFileLink = "/frs/admin/deletefile.php?" .
		"group_id=" . $groupId . 
		"&package_id=" . $packId .
		"&release_id=" . $relId .
		"&file_id=" . $fileId;
	if ($not_doc == "t") {
		// Download Links.
		if ($simtkFileType == "URL") {
			echo '<td><div class="download_link">' . 
				'<a href="/frs/download.php/file/' . 
				$fileId . '/' . $fileName . 
				'?group_id=' . $groupId . 
				'">';
			echo $fileName . ' (URL)</a></div></td>';
		}
		else {
			echo '<td><div class="download_link">' . 
				'<a href="/frs/download_confirm.php/file/' . 
				$fileId . '/' . $fileName . 
				'?group_id=' . $groupId . 
				'">';
			echo $fileName . '</a></div></td>';
			
		}
	}
	else {
		// Documenation Links.
		if ($simtkFileType == "URL") {
			echo '<td><div class="download_link">' . 
				'<a href="/frs/download.php/file/' . 
				$fileId . '/' . $fileName . 
				'?group_id=' . $groupId . 
				'">';
			echo $fileName . ' (URL)</a></div></td>';
		}
		else {
			echo '<td><div class="download_link">' . 
				'<a href="/frs/download_confirm.php/file/' . 
				$fileId . '/' . $fileName . 
				'?group_id=' . $groupId . 
				'">';
			echo $fileName . '</a></div></td>';
		}
	}
	if (!$doi) {
	   echo '<td><span class="download_btn">' .
		'<a class="btn-blue" href="' . $strUpdateFileLink . '">' .
		'Update</a></span></td>';
	
	   echo '<td><span class="download_btn">' .
		'<a class="btn-blue" href="' . $strDeleteFileLink . '">' .
		'Delete</a></span></td>';
	} else {
	   if (empty($doi_identifier)) {
		      $doi_identifier = " pending";
	   }
	   echo "<td colspan=2><span>doi:" . $doi_identifier . "</span></td>";
	}
	echo "</tr>";
}


?>
