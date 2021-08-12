<?php

/**
 *
 * frs_admin_data_util.php
 * 
 * Process file downloads administration database data.
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
 
// Get the number of files in the latest release.
function getNumOfFilesInLatestRelease($theReleases) {
	// Find the number of files in the latest release.
	// Note: The latest release goes first.
	$numFiles = 0;
	foreach ($theReleases as $idxRel=>$releaseInfo) {
		// Only look at the first one, because it is the latest release.
		$theFiles = $releaseInfo["files"];
		$numFiles = count($theFiles);
		return $numFiles;
	}

	return $numFiles;
}

// Gather the download overview and notes information from the groups table.
function getFrsGroupInfo($groupId) {
	$arrGroup = array();
	$sqlGroup = "SELECT simtk_is_public, simtk_download_notes, " .
		"simtk_preformatted_download_notes, simtk_download_overview FROM groups " .
		"WHERE group_id=$1";
	$resGroup = db_query_params($sqlGroup, array($groupId));
	if ($resGroup && db_numrows($resGroup) > 0) {
		$row = db_fetch_array($resGroup);
		$arrGroup["is_public"] = $row['simtk_is_public'];
		$arrGroup["download_notes"] = $row['simtk_download_notes'];
		$arrGroup["preformatted"] = $row['simtk_preformatted_download_notes'];
		$arrGroup["download_overview"] = $row['simtk_download_overview'];
	}

	return $arrGroup;
}

// Retrieve packages, releases, and files info for the given group.
// Put data retrieve in array indexed by package id.
function getFrsPackagesInfo($groupId, $pubSql) {

	$arrPackages = array();

	// Public packages first, then private packages, then hidden packages.
	$sqlPackage = "SELECT * FROM frs_package " .
		"WHERE group_id=$1 " .
		"ORDER BY status_id, is_public DESC, name";
	$resPackage = db_query_params($sqlPackage, array($groupId));
	$numPackages = db_numrows($resPackage);

	// Iterate through packages.
	for ($cntPack = 0; $cntPack < $numPackages; $cntPack++) {

                $thePackage = db_fetch_array($resPackage);
		$packageId = $thePackage['package_id'];
		$packageName = $thePackage['name'];
		$packageDesc = $thePackage['simtk_description'];
		$packageDownloadNotes = $thePackage['simtk_download_notes'];
		$packageLogo = $thePackage['simtk_logo_file'];
		$packageStatusId = $thePackage['status_id'];
		$packageIsPublic = $thePackage['is_public'];
		$packageOpenLatest = $thePackage['simtk_openlatest'];
		$packageDoi = $thePackage['doi'];
		if (isset($thePackage['doi_identifier'])) {
			$packageDoiIdentifier = $thePackage['doi_identifier'];
		}

		// Get the releases of the package.
		$sqlRelease = "SELECT * FROM frs_release " .
			"WHERE package_id=$1 " .
			"ORDER BY status_id, release_date DESC, name ASC";
		$resRelease = db_query_params($sqlRelease, array($packageId));
		$numReleases = db_numrows($resRelease);

		// Note: This array is not associative, because we need to
		// preserve the order of the releases.
		$arrReleases = array();

		// Iterate through releases.
		for ($cntRel = 0; $cntRel < $numReleases; $cntRel++) {

                	$theRelease = db_fetch_array($resRelease);
               		$releaseId = $theRelease['release_id'];
               		$releaseName = $theRelease['name'];
			$releaseNotes = $theRelease['notes'];
			$releaseChanges = $theRelease['changes'];
			$releaseDesc = $theRelease['simtk_description'];
               		$releaseDate = $theRelease['release_date'];
			$releaseStatus = $theRelease['status_id'];
			$releaseDoi = $theRelease['doi'];
			if (isset($theRelease['doi_identifier'])) {
				$releaseDoiIdentifier = $theRelease['doi_identifier'];
			}

			// Get the files of the release.
			$sqlFile = "SELECT ff.filename AS filename, " .
				"ff.file_size AS file_size, " .
				"ff.file_id AS file_id, " .
				"ff.release_time AS release_time, " .
				"ff.post_date AS post_date, " .
				"ffv.filetype AS filetype, " .
				"ff.simtk_rank AS rank, " .
				"ff.simtk_filename_header AS filename_header, " .
				"ff.simtk_description AS description, " .
				"ff.simtk_filetype AS simtk_filetype, " .
				"ff.simtk_filelocation AS filelocation, " .
				"ff.doi AS doi, " .
				"ff.doi_identifier AS doi_identifier, " .
				"(ffv.filetype != 'Documentation') AS not_doc, " .
				"ffv.processor AS processor, " .
				"ffv.downloads AS downloads " .
				"FROM frs_file AS ff " .
				"JOIN frs_file_vw AS ffv " .
				"ON ff.file_id=ffv.file_id " .
				"WHERE ff.release_id=$1 " .
				"ORDER BY not_doc desc, rank, filename_header desc";

			$resFile = db_query_params($sqlFile, array($releaseId));
			$numFiles = db_numrows($resFile);


			$arrFiles = array();
			// Iterate through files.
			for ($cntFile = 0; $cntFile < $numFiles; $cntFile++) {

				$fileInfo = array();

				$theFile = db_fetch_array($resFile);

				$fileInfo['file_id'] = $theFile['file_id'];
				$fileInfo['filename'] = $theFile['filename'];
				$fileInfo['release_time'] = $theFile['release_time'];
				$fileInfo['post_date'] = $theFile['post_date'];
				$fileInfo['file_size'] = $theFile['file_size'];
				$fileInfo['downloads'] = $theFile['downloads'];
				$fileInfo['processor'] = $theFile['processor'];
				$fileInfo['filetype'] = $theFile['filetype'];
				$fileInfo['filename_header'] = $theFile['filename_header'];
				$fileInfo['description'] = $theFile['description'];
				$fileInfo['simtk_filetype'] = $theFile['simtk_filetype'];
				$fileInfo['filelocation'] = $theFile['filelocation'];
				$fileInfo['not_doc'] = $theFile['not_doc'];
				$fileInfo['doi'] = $theFile['doi'];
				if (isset($theFile['doi_identifier'])) {
					$fileInfo['doi_identifier'] = $theFile['doi_identifier'];
				}

				$arrFiles[] = $fileInfo;
			}

			// Release info has release id and files.
			$releaseInfo = array();
			$releaseInfo["release_id"] = $releaseId;
			$releaseInfo["name"] = $releaseName;
			$releaseInfo["notes"] = $releaseNotes;
			$releaseInfo["changes"] = $releaseChanges;
			$releaseInfo["description"] = $releaseDesc;
			$releaseInfo["release_date"] = $releaseDate;
			$releaseInfo["status_id"] = $releaseStatus;
			$releaseInfo["doi"] = $releaseDoi;
			if (isset($releaseDoiIdentifier)) {
				$releaseInfo["doi_identifier"] = $releaseDoiIdentifier;
			}
			$releaseInfo["files"] = $arrFiles;
			// Preserve release info order when keeping 
			// in array of releases, lastest first.
			$arrReleases[] = $releaseInfo;
		}


		// Get the citations of the package.
		$sqlCitation = "SELECT * FROM frs_citation WHERE package_id=$1";
		$resCitation = db_query_params($sqlCitation, array($packageId));
		$numCitations = db_numrows($resCitation);

		// Note: This array is not associative, because we need to
		// preserve the order of the citation.
		$arrCitations = array();

		// Iterate through citations.
		$cntCites = 0;
		$cntNonCites = 0;
		for ($cntCite = 0; $cntCite < $numCitations; $cntCite++) {

			$citeInfo = array();

                	$theCitation = db_fetch_array($resCitation);
			$citeInfo['citation_id'] = $theCitation['citation_id'];
			$citeInfo['citation'] = $theCitation['citation'];
			$citeInfo['citation_year'] = $theCitation['citation_year'];
			$citeInfo['url'] = $theCitation['url'];
			$citeInfo['cite'] = $theCitation['cite'];

			// Count the number citations and non-citations.
			if ($theCitation['cite']) {
				$cntCites++;
			}
			else {
				$cntNonCites++;
			}

			$arrCitations[] = $citeInfo;
		}

		$packageInfo = array();
		$packageInfo["package_id"] = $packageId;
		$packageInfo["name"] = $packageName;
		$packageInfo["description"] = $packageDesc;
		$packageInfo["download_notes"] = $packageDownloadNotes;
		$packageInfo["logo"] = $packageLogo;
		$packageInfo["status_id"] = $packageStatusId;
		$packageInfo["is_public"] = $packageIsPublic;
		$packageInfo["openlatest"] = $packageOpenLatest;
		$packageInfo["doi"] = $packageDoi;
		if (isset($packageDoiIdentifier)) {
			$packageInfo["doi_identifier"] = $packageDoiIdentifier;
		}
		$packageInfo["releases"] = $arrReleases;
		$packageInfo["citations"] = $arrCitations;
		$packageInfo["countCitations"] = $cntCites;
		$packageInfo["countNonCitations"] = $cntNonCites;		
		$arrPackages[] = $packageInfo;
	}

	return $arrPackages;
}

// DEBUG.
function debugPackages($thePackages) {
	foreach ($thePackages as $idxPack=>$packageInfo) {

		$packId = $packageInfo["package_id"];
		$theReleases = $packageInfo["releases"];

		echo "<br/>*** package_id:" . $packId . "<br/>\n";
		foreach ($theReleases as $idxRel=>$releaseInfo) {

			$relId = $releaseInfo["release_id"];
			$theFiles = $releaseInfo["files"];

			echo "release_id (package_id=" . $packId . "):" . $relId . "<br/>\n";
			foreach ($theFiles as $idxFile=>$fileInfo) {

				foreach ($fileInfo as $key=>$val) {
					echo "$key:$val<br/>\n";
				}
			}
		}
	}

}

?>
