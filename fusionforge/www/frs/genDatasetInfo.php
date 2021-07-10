<?php

/**
 *
 * genDatasetInfo.php
 * 
 * Generate the script that describes dataset in download packages.
 *
 * Copyright 2005-2021, SimTK Team
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
 
require_once "frs_data_util.php";
require_once "construct_ui.php";
require_once $gfplugins.'datashare/include/Datashare.class.php';

if ($group_id == -1 || 
	$cur_group_obj == null ||
	($pub_sql != "" && $pub_sql != ' AND is_public=1 ')) {
	return;
}

// Retrieve download overview and notes info for the given group.
$theGroupInfo = getFrsGroupInfo($group_id);

// Retrieve packages, releases, and files info for the given group.
$thePackages = getFrsPackagesInfo($group_id, $pub_sql);

// Dataset description.
$strDataset = genDatasetDesc($cur_group_obj, $thePackages, $lastRelDate, $lastRelIds);
echo $strDataset;


// Generate the Dataset description.
function genDatasetDesc($groupObj, $thePackages, &$lastRelDate, &$lastRelIds) {

	// Keep latest release id for each package.
	$lastRelIds = array();
	$lastRelDate = -1;
	$hasFiles = false;

	$arrStrPackage = array();

	// Iterate over the packages.
	foreach ($thePackages as $idxPack=>$packageInfo) {

		$isPublic = $packageInfo["is_public"];
		if ($isPublic != "1") {
			// Ignore non-public package.
			continue;
		}

		// Get the releases in this package.
		$theReleases = $packageInfo["releases"];

		// Note: The latest release goes first.
		$foundRelease = false;
		foreach ($theReleases as $idxRel=>$releaseInfo) {

			if ($foundRelease === true) {
				// Found a release already. Skip.
				continue;
			}

			$relStatus = $releaseInfo["status_id"];
			if ($relStatus != 1) {
				// Ignore non-active release. Skip to next release.
				continue;
			}

			// Check whether there are file(s) in the release.
			$theFiles = $releaseInfo["files"];
			$cntFilesInRelease = count($theFiles);
			if ($cntFilesInRelease <= 0) {
				// This release does not contain any file.
				// Skip to next release. 
				continue;
			}

			// Release has file(s).

			if ($hasFiles === false) {
				// Found file(s).
				$hasFiles = true;
			}

			// Get the latest release id.
			if (!isset($lastRelIds[$idxPack])) {
				// Note: Since the latest release goes first, once the latest
				// release id has been set for the package, does not have to
				// fill in again for the package.
				$lastRelIds[$idxPack] = $releaseInfo["release_id"];
			}

			// Get release date.
			$relDate = $releaseInfo["release_date"];
			if ($relDate > $lastRelDate) {
				// Update the latest release date.
				$lastRelDate = $relDate;
			}

			// Generate the dataset description for the package.
			$strPackage = genPackageDatasetDesc($groupObj, 
				$packageInfo, $releaseInfo);
			$arrStrPackage[] = $strPackage;

			$foundRelease = true;
		}
	}


	// Generate dataset header.
	$strHeader = genDatasetHeader($groupObj, $lastRelDate, $lastRelIds, $thePackages);

	// Generate dataset trailer.
	$strTrailer = genDatasetTrailer($groupObj);

	// Generate dataset packages description.
	$strDistribution = genDatasetDistribution($arrStrPackage);

	if ($strHeader !== false) {
		// There is at least one pacakge with file available, or 
		// there is at least one public, active DataShare study available.
		// Return result for Google Dataset search.
		return $strHeader . $strDistribution . $strTrailer;
	}
	else {
		// Download package or dataset not available.
		// Do not include header for Google Dataset search.
		return "";
	}
}


// Generate the dataset header.
function genDatasetHeader($groupObj, $lastRelDate, $lastRelIds, $thePackages) {

	// Get creation date of public, active DataShare studies.
	$query_datashare = "SELECT date_created AS release_date FROM plugin_datashare " .
		"WHERE is_private = 0 " .
		"AND active=1 " .
		"AND group_id=$1";
	$result_datashare = db_query_params($query_datashare, array($groupObj->getID()));
	if ($result_datashare) {
		while ($row = db_fetch_array($result_datashare)) {
			$relDate = $row["release_date"];

			if ($lastRelDate == -1) {
				// No release date encountered yet.
				// Use this date from DataShare study.
				$lastRelDate = $relDate;
			}
			else {
				if ($lastRelDate < $relDate) {
					// Use the newer DataShare date.
					$lastRelDate = $relDate;
				}
			}
		}
	}

	if ($lastRelDate == -1) {
		// No release date found.
		// Package or DataShare study is not available.
		// Done. Do not include header for Google Dataset search.
		return false;
	}


	// There is at least one pacakge with file available, or 
	// there is at least one public, active DataShare study available.

	$serverName = getServerName();

	// SCRIPT tag.
	$strHeader = '<script type="application/ld+json"> {';

	$strHeader .= '"@context": "https://schema.org/",';
	$strHeader .= '"@type": "Dataset",';

	$strHeader .= '"includedInDataCatalog": {' .
		'"@type": ["DataCatalog"], ' .
		'"url": "https://simtk.org", ' .
		'"name": "SimTK"},';

	$strHeader .= '"name": "' . htmlspecialchars($groupObj->getPublicName(), ENT_QUOTES) . '",';

	$theDesc = $groupObj->getDescription();

	// Remove up to 20 iframe tags.
	for ($cnt = 0; $cnt < 20; $cnt++) {
		// Convert "iframe" tag to "a" tag and get "href".
	 	$strCleaned = cleanTags($theDesc, "iframe", "src");
		if ($strCleaned !== false) {
			$theDesc = $strCleaned;
		}
		// Convert "object" tag to "a" tag and get "href".
	 	$strCleaned = cleanTags($theDesc, "object", "src");
		if ($strCleaned !== false) {
			$theDesc = $strCleaned;
		}
	}

	$numPackages = count($thePackages);
	$theDesc .= "<br/><br/>" . "This project includes the following software/data packages: <br/>";
	$theDesc .= "<ul>";
	foreach ($thePackages as $idxPack=>$packageInfo) {

		if (!isset($lastRelIds[$idxPack])) {
			// No public package with active release that has files. 
			continue;
		}

		$packageName = $packageInfo["name"];
		$packageURL = 'https://' . $serverName .
			'/frs?group_id=' . $groupObj->getID();
		if (isset($lastRelIds[$idxPack])) {
			// Add latest release id of package.
 			$packageURL .= '#' . $lastRelIds[$idxPack];
		}
		$packageDesc = $packageInfo["description"];
		if (trim($packageDesc) != "") {
			$strPackage = '<a href="' . $packageURL . '">' . $packageName . '</a>' .
				': ' . $packageDesc;
		}
		else {
			$strPackage = '<a href="' . $packageURL . '">' . $packageName . '</a>';
		}

		$theDesc .= "<li>" . $strPackage . "</li>";
	}

	// Get Data Share studies.
	if ($groupObj->usesPlugin("datashare")) {
		$group_id = $groupObj->getID();
		$study = new Datashare($group_id);
		if ($study && is_object($study)) {
			$study_result = $study->getStudyByGroup($group_id);

			// Only show public studies.
			if ($study_result) {
				foreach ($study_result as $result) {
					if ($result->active == 1 &&
						$result->is_private < 2) {
						$studyTitle = $result->title;
						$studyURL = 'https://' . $serverName .
							'/plugins/datashare?group_id=' . 
							$groupObj->getID();
						$studyDescription = $result->description;
						$theDesc .= "<li>" . 
							'<a href="' . $studyURL . '">' . 
							$studyTitle . '</a>' . 
							": " . $studyDescription . "</li>";
					}
				}
			}
		}
	}
	$theDesc .= "</ul>";

	$strDesc = htmlspecialchars($theDesc, ENT_QUOTES);
	if (strlen($strDesc) >= 5000) {
		// Description is too long. Generate a shorter version.


		// Use summary instead of description.
		$theDesc = $groupObj->getSummary();

		// Remove up to 20 iframe tags.
		for ($cnt = 0; $cnt < 20; $cnt++) {
			// Convert "iframe" tag to "a" tag and get "href".
		 	$strCleaned = cleanTags($theDesc, "iframe", "src");
			if ($strCleaned !== false) {
				$theDesc = $strCleaned;
			}
			// Convert "object" tag to "a" tag and get "href".
		 	$strCleaned = cleanTags($theDesc, "object", "src");
			if ($strCleaned !== false) {
				$theDesc = $strCleaned;
			}
		}

		$numPackages = count($thePackages);
		$theDesc .= "<br/><br/>" . "This project includes the following software/data packages: <br/>";
		$theDesc .= "<ul>";
		foreach ($thePackages as $idxPack=>$packageInfo) {

			if (!isset($lastRelIds[$idxPack])) {
				// No public package with active release that has files. 
				continue;
			}

			$packageName = $packageInfo["name"];
			$packageURL = 'https://' . $serverName .
				'/frs?group_id=' . $groupObj->getID();
			if (isset($lastRelIds[$idxPack])) {
				// Add latest release id of package.
 				$packageURL .= '#' . $lastRelIds[$idxPack];
			}
			$packageDesc = $packageInfo["description"];

			// Do not include description.
			$strPackage = '<a href="' . $packageURL . '">' . $packageName . '</a>';
	
			$theDesc .= "<li>" . $strPackage . "</li>";
		}

		// Get Data Share studies.
		if ($groupObj->usesPlugin("datashare")) {
			$group_id = $groupObj->getID();
			$study = new Datashare($group_id);
			if ($study && is_object($study)) {
				$study_result = $study->getStudyByGroup($group_id);

				// Only show public studies.
				if ($study_result) {
					foreach ($study_result as $result) {
						if ($result->active == 1 &&
							$result->is_private < 2) {
							$studyTitle = $result->title;
							$studyURL = 'https://' . $serverName .
								'/plugins/datashare?group_id=' . 
								$groupObj->getID();
							$studyDescription = $result->description;
							$theDesc .= "<li>" . 
								'<a href="' . $studyURL . '">' . 
								$studyTitle . '</a>' . "</li>";
						}
					}
				}
			}
		}
		$theDesc .= "</ul>";

		$strDesc = htmlspecialchars($theDesc, ENT_QUOTES);
	}
	$strHeader .= '"description": "' .  $strDesc . '",';

	$strHeader .= '"url": "https://' . $serverName . '/projects/' . $groupObj->getUnixName() . '",';

	$projectLogo = trim($groupObj->getLogoFile());
	if ($projectLogo != "") {
		$strHeader .= '"thumbnailUrl": "https://' . $serverName . '/logos/' . $projectLogo . '",';
	}

	// Latest release modified date from package(s).
	$strHeader .= '"dateModified": "' . date('M d, Y', $lastRelDate) . '",';

	// Use project leads as creator.
	$strHeader .= '"creator": [';
	$projectLeads = $groupObj->getLeads();
	$numLeads = count($projectLeads);
	if ($numLeads == 0) {
		// No project leads. Get admins instead.
		$projectLeads = $groupObj->getAdmins();
		$numLeads = count($projectLeads);
	}
	for ($cnt = 0; $cnt < $numLeads; $cnt++) {
		$leadInfo = $projectLeads[$cnt];
		$firstName = $leadInfo->data_array['firstname'];
		$lastName = $leadInfo->data_array['lastname'];
		$universityName = trim($leadInfo->data_array['university_name']);
		$strHeader .= '{';
		$strHeader .= '"@type": "Person",';
		$strHeader .= '"name": "' . $firstName . ' ' . $lastName . '",';
		if ($universityName != "") {
			$strHeader .= '"affiliation": "' . $universityName . '",';
		}
		$strHeader .= '"givenName": "' . $firstName . '",';
		$strHeader .= '"familyName": "' . $lastName . '"';
		$strHeader .= '}';

		if ($cnt < $numLeads - 1) {
			$strHeader .= ",";
		}
	}
	// End of Creator.
	$strHeader .= '],';

	// Publisher
	// Use organizations of project leads as publishers.
	$cntPublishers = 0;
	$arrUnivName = array();
	$arrUnivUrl = array();
	$strHeader .= '"publisher": [';
	for ($cnt = 0; $cnt < $numLeads; $cnt++) {
		$leadInfo = $projectLeads[$cnt];
		$universityName = trim($leadInfo->data_array['university_name']);
		$universityUrl = trim($leadInfo->data_array['university_website']);
		if ($universityName != "" || $universityUrl != "") {

			if ($universityName != "" && 
				isset($arrUnivName[strtolower($universityName)])) {
				// Organization name included already.
				// Skip.
				continue;
			}
			if ($universityUrl != "" && 
				isset($arrUnivUrl[strtolower($universityUrl)])) {
				// Organization website included already.
				// Skip.
				continue;
			}

			if ($cntPublishers > 0) {
				// Has entry already.
				// Add comma before adding next entry.
				$strHeader .= ",";
			}

			$strHeader .= '{';
			if ($universityName != "") {
				$strHeader .= '"name": "' . $universityName . '",';
				$arrUnivName[strtolower($universityName)] = $universityName;
			}
			if ($universityUrl != "") {
				$strHeader .= '"url": "' . $universityUrl . '",';
				$arrUnivUrl[strtolower($universityUrl)] = $universityUrl;
			}
			$strHeader .= '"@type": "Organization"';
			$strHeader .= '}';

			$cntPublishers++;
		}
	}
	// End of Publishder.
	$strHeader .= '],';

	return $strHeader;
}


// Generate the dataset trailer.
function genDatasetTrailer($groupObj) {

	// End of SCRIPT tag.
	$strTrailer = '} </script>';

	return $strTrailer;
}


// Generate dataset distribution.
function genDatasetDistribution($arrStrPackage) {

	$strDistribution = '"distribution": [';

	$numPackages = count($arrStrPackage);
	for ($cnt = 0; $cnt < $numPackages; $cnt++) {
		$strDistribution .= $arrStrPackage[$cnt];
		if ($cnt < $numPackages - 1) {
			$strDistribution .= ",";
		}
	}

	$strDistribution .= ']';

	return $strDistribution;
}

// Generate the dataset description for given package.
function genPackageDatasetDesc($groupObj, $packageInfo, $releaseInfo) {

	$serverName = getServerName();

	// Summation of file sizes in this release in the package.
	$sumFileSize = 0;

	$strPackage = '{"@type": "DataDownload",';

	$packageName = $packageInfo["name"];
	$strPackage .= '"name": "' . htmlspecialchars($packageName, ENT_QUOTES) . '",';

	$packageDesc = $packageInfo["description"];
	$strPackageDesc = htmlspecialchars($packageDesc, ENT_QUOTES);
	if (strlen($strPackageDesc) >= 5000) {
		// Package description is too long. Use package name instead.
		$strPackageDesc = htmlspecialchars($packageName, ENT_QUOTES);
	}
	$strPackage .= '"description": "' . $strPackageDesc . '",';

	// License
	$packId = $packageInfo["package_id"];
	$strLicenseLink = genLicenseLink($packId, $strLic);
	if (trim($strLic) != "") {
		// Only include license if it is present.
		$strLic = "<b>" . $packageName . "</b><br/>" . $strLic . "<br/><br/>";
		$strLic = htmlspecialchars($strLic, ENT_QUOTES);
		$strPackage .= '"license": "' . $strLic . '",';
	}

	// Go through each file in the release.
	$theFiles = $releaseInfo["files"];
	$arrFileDoi = array();
	$arrFileType = array();
	foreach ($theFiles as $idxFile=>$fileInfo) {

		// File size.
		$strFileSize = $fileInfo["file_size"];
		$fileSize = intval($strFileSize);
		$sumFileSize += $fileSize;

		if ($fileSize > 0) {
			// Non-empty file.

			// File type.
			$strFileType = $fileInfo["simtk_filetype"];
			if (trim($strFileType) != "") {
				$arrFileType[$strFileType] = $strFileType;
			}
			else {
				// Does not have "simtk_filetype" info; try "filetype".
				$strFileType = $fileInfo["filetype"];
				if (trim($strFileType) != "") {
					$arrFileType[$strFileType] = $strFileType;
				}
				else {
					$arrFileType[$strFileType] = "Unknown";
				}
			}
		}

		// DOI.
		$fileDoi = $fileInfo["doi"];
		if ($fileDoi && isset($fileInfo["doi_identifier"])) {
			$fileDoiIdentifier = $fileInfo["doi_identifier"];
			if (!empty($fileDoiIdentifier)) {
				// File has requested DOI and has file DOI.
				$arrFileDoi[] = '"https://doi.org/' . $fileDoiIdentifier . '"';
			}
		}
	}

	// Note: The encodingFormat is needed.
	if (count($arrFileType) > 0) {
		$cnt = 0;
		$strFileTypes = "";
		foreach ($arrFileType as $idxFileType=>$theFileType) {
			if ($cnt > 0) {
				$strFileTypes .= ",";
			}
			$strFileTypes .= $theFileType;
			$cnt++;
		}
		$strPackage .= '"encodingFormat": "' . $strFileTypes . '",';
	}

	// DOI.
	// NOTE: If package has DOI, use the package DOI.
	// If not, check if there are DOI for file(s).
	$packDoi = $packageInfo["doi"];
	if ($packDoi && isset($packageInfo["doi_identifier"])) {
		$packDoiIdentifier = $packageInfo["doi_identifier"];
		if (!empty($packDoiIdentifier)) {
			// Package has requested DOI and has package DOI.
			// Show the package DOI.
			$strPackage .= '"@id": "https://doi.org/' . $packDoiIdentifier . '",';
		}
	}
	else {
		// Check if there are file DOI(s).
		// NOTE: Cannot accomodate more than one file DOI in a package!!!
		// Show ONLY the first file DOI.
		$numFileDoi = count($arrFileDoi);
		for ($cnt = 0; $cnt < $numFileDoi; $cnt++) {
			if ($cnt == 0) {
				$strPackage .= '"@id": ' . $arrFileDoi[$cnt] . ',';
			}
		}
	}


	// Citation.
	$arrCitations = $packageInfo["citations"];
	$numCitations = count($arrCitations);
	if ($numCitations > 0 && $packageInfo["countCitations"] > 0) {

		// Has citation(s).
	 	$strPackage .= '"citation": [';

		$cnt = 0;
		for ($cntCites = 0; $cntCites < $numCitations; $cntCites++) {
			$citeInfo = $arrCitations[$cntCites];
			if ($citeInfo["cite"]) {

				if ($cnt > 0) {
					$strPackage .= ",";
				}

				$strPackage .= '{"@type": "ScholarlyArticle"';

				// NOTE: If package has DOI, use the package DOI.
				// If not, check if there are DOI for file(s).
				$packDoi = $packageInfo["doi"];
				if ($packDoi && isset($packageInfo["doi_identifier"])) {
					$packDoiIdentifier = $packageInfo["doi_identifier"];
					if (!empty($packDoiIdentifier)) {
						// Package has requested DOI and has package DOI.
						// Show the package DOI.
						$strPackage .= '"identifier": "https://doi.org/' . $packDoiIdentifier . '",';
					}
				}
				else {
					// Check if there are file DOI(s).
					// NOTE: Cannot accomodate more than one file DOI in a package!!!
					// Show ONLY the first file DOI.
					$numFileDoi = count($arrFileDoi);
					for ($cnt = 0; $cnt < $numFileDoi; $cnt++) {
						if ($cnt == 0) {
							$strPackage .= '"identifier": ' . $arrFileDoi[$cnt] . ',';
						}
					}
				}

				// NOTE: Author(s) and publisher are not separately listed in db.
				// Fill in the headline only from "citation" column.
				$strPackage .= ', "headline": "' . $citeInfo["citation"] . '"';
				$strPackage .= ', "datePublished": "' . $citeInfo["citation_year"] . '"';
				if ($citeInfo["url"] != false && $citeInfo["url"] != "") {
					$strPackage .= ', "url": "' . $citeInfo["url"] . '"';
					// NOTE: Image is not available. Put in same value as URL.
					// Otherwise, warning appears.
					$strPackage .= ', "image": "' . $citeInfo["url"] . '"';
				}
				else {
					// NOTE: No image available. Put in a dummy image.
					// Otherwise, warning appears.
					$filePackageLogo = $packageInfo["logo"];
					if (trim($filePackageLogo) != "") {
						$strPackage .= ', "image": "https://' . $serverName . 
							'/logos-frs/' . $filePackageLogo . '"';
					}
					else {
						$strPackage .= ', "image": "https://' . $serverName . 
							'/logos/_thumb"';
					}
				}

				$strPackage .= '}';

				$cnt++;
			}
		}

		// End of Citation.
		$strPackage .= '],';
	}


	$uploadDate = $releaseInfo["release_date"];
	$strPackage .= '"uploadDate": "' . date('M d, Y', $uploadDate) . '",';

	$relName = $releaseInfo["name"];
	$strPackage .= '"version": "' . htmlspecialchars($relName, ENT_QUOTES) . '",';

	// Summation of file sizes.
	$strSumFileSize = $sumFileSize;
	if (intval($sumFileSize/1024) >= 1) {
		$strSumFileSize = intval($sumFileSize/1024) . " KB";
	}
	if (intval($sumFileSize/1024/1024) >= 1) {
		$strSumFileSize = intval($sumFileSize/1024/1024) . " MB";
	}
	$strPackage .= '"contentSize": "' . $strSumFileSize . '",';

	$serverName = getServerName();
	$strPackage .= '"contentURL": "' . 'https://' . $serverName .
		'/frs?group_id=' . $groupObj->getID() .
		'#' . $releaseInfo["release_id"] .
		'"';

	$strPackage .= '}';

	return $strPackage;
}


// Get server name.
function getServerName() {
	$theServer = false;
	if (isset($_SERVER['SERVER_NAME'])) {
		$theServer = $_SERVER['SERVER_NAME'];
	}
	else {
		// Parse configuration to get web_host.
		if (file_exists("/etc/gforge/config.ini.d/post-install.ini")) {
			// The file post-install.ini is present.
			$arrConfig = parse_ini_file("/etc/gforge/config.ini.d/post-install.ini");
			// Check for each parameter's presence.
			if (isset($arrConfig["web_host"])) {
				$theServer = $arrConfig["web_host"];
			}
		}
	}

	return $theServer;
}

// Clean URL tags in the string content.
function cleanTags($strContent, $tag, $targetName) {

	// Restore to tag format from encoded format.
	$strContent = html_entity_decode($strContent);

	// Find opening tag.
	$idxStart = stripos($strContent, "<" . $tag . " ");
	if ($idxStart === false) {
		// Does not have tag.
		return false;
	}

	// Part before opening tag.
	$strPre = substr($strContent, 0, $idxStart);

	// Part starting with opening tag.
	$strMain = substr($strContent, $idxStart);

	// Find closing tag.
	$idxEnd = stripos($strMain, "</" . $tag . ">");
	if ($idxEnd === false) {
		// Does not have closing tag.
		return false;
	}

	// Has tag content.
	// Convert to <a> tag.
	$strTaggedSection = substr($strMain, 0, $idxEnd + strlen("</" . $tag . ">"));
	$idxSingleQuote = stripos($strTaggedSection, $targetName . "='");
	$idxDoubleQuote = stripos($strTaggedSection, $targetName . '="');
	if ($idxSingleQuote === false && $idxDoubleQuote === false) {
		// Cannot find source in tag. Ignore.
		return false;
	}
	else if ($idxSingleQuote !== false) {
		// Target uses single quote.
		// Locate source.
		$strFront = substr($strTaggedSection, 0, $idxSingleQuote + strlen($targetName . "='"));
		$strTail = substr($strTaggedSection, $idxSingleQuote + strlen($targetName . "='"));
		// Find matching ending single quote.
		$idxEndQuote = stripos($strTail, "'");
		// Get href.
		$strHref = substr($strTail, 0, $idxEndQuote);
	}
	else if ($idxDoubleQuote !== false) {
		// Target uses double quote.
		$strFront = substr($strTaggedSection, 0, $idxDoubleQuote + strlen($targetName . '="'));
		$strTail = substr($strTaggedSection, $idxDoubleQuote + strlen($targetName . '="'));
		// Find matching ending double quote.
		$idxEndQuote = stripos($strTail, '"');
		// Get href.
		$strHref = substr($strTail, 0, $idxEndQuote);
	}
	else {
		// Should not have both single and double quote.
		return false;
	}

	// Add prefix.
	if (stripos($strHref, "//") === 0) {
		$strHref = "https:" . $strHref;
	}
	else if (stripos($strHref, "www.") === 0) {
		$strHref = "https://" . $strHref;
	}
	else if (stripos($strHref, "youtube.") === 0) {
		$strHref = "https://www." . $strHref;
	}
	// Convert YouTube usage to "embed" in order to show YouTube video..
	$strHref = str_replace("https://www.youtube.com/v/", "https://www.youtube.com/embed/", $strHref);

	$strA = '<a href="' . $strHref . '">' . $strHref . '</a>';

	// Part after closing tag.
	$strTrail = substr($strMain, $idxEnd + strlen("</" . $tag . ">"));

	$strContent = $strPre . $strA . $strTrail;

	// Re-encode string.
	return $strContent;
}

?>
