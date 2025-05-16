<?php
/**
 *
 * reports plugin Class
 * 
 * Copyright 2005-2024, SimTK Team
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
 
require_once $gfcommon.'include/FFError.class.php';

class Reports extends FFError {

	/**
	 * Associative array of data from db.
	 *
	 * @var	 array   $data_array.
	 */
	var $data_array;

	/**
	 * The group object.
	 *
	 * @var	 object  $group.
	 */
	var $group;

	/**
	 *  Constructor.
	 *
	 *	@param	object	The group object to which this report is associated.
	 *	@return	boolean	success.
	 */
	function __construct(&$group) {

		parent::__construct();

		if (!$group || !is_object($group)) {
			$this->setNotValidGroupObjectError();
			return false;
		}

		if ($group->isError()) {
			$this->setError('Reports:: '.$group->getErrorMessage());
			return false;
		}

		$this->group =& $group;

		return true;

    }

	function getDownloadsSummary(&$cntUsers,&$cntUserFiles,&$cntRecords,&$cntLinks,&$cntFiles,&$packages,&$packageCounts,&$packageTotals,&$packageDates,&$packageStatus,&$packageIsPublic,&$releaseNames,&$releaseDates,&$releaseTotals,&$releaseStatus,&$packageArray,$fromYear=false,$fromMonth=false,$fromDay=false) {

		$users = array();
		$userfiles = array();
		$strQuery = "SELECT user_id, f.file_id AS file_id, " .
			"type_id, a.release_id, package_id, simtk_filetype " .
			"FROM frs_dlstats_file d " .
			"JOIN frs_file f " .
			"ON f.file_id=d.file_id " .
			"JOIN frs_frpg_view a " .
			"ON a.file_id=d.file_id " .
			"WHERE group_id=$1 ";
		if ($fromYear != false && $fromMonth != false && $fromDay != false) {
			$strQuery .= "AND ((month=$fromYear$fromMonth AND day>=$fromDay) 
				OR (month>$fromYear$fromMonth)) ";
		}
		$res = db_query_params($strQuery, array ($this->group->getID()));
		
		//if ( $this->project->getType() == 2 )
		//{
		//	$sql .= " OR package_id IN ( SELECT filelocation FROM frs_file f JOIN frs_frpg_view a ON f.file_id = a.file_id WHERE filetype = 'URL' AND group_id = $this->getID() )";
		//}
         
		
		if ( $res )
		{
		    $numFiles = 0;
			$numLinks = 0;
			$numRecords = db_numrows( $res );
			while ( $results = db_fetch_array( $res ) )
			{
				$file = $results[ 'file_id' ];
				$release = $results[ 'release_id' ];
				$package = $results[ 'package_id' ];
				$user = $results[ 'user_id' ];
				if (!isset($users[ $user ])) {
					$users[ $user ] = 0;
				}
				$users[ $user ]++;
				if (!isset($userfiles[ $user . "x" . $file ])) {
					$userfiles[ $user . "x" . $file ] = 0;
				}
				$userfiles[ $user . "x" . $file ]++;
				if ( $results[ 'simtk_filetype' ] == "URL" )
					$numLinks++;
				else
					$numFiles++;
			}
			$cntRecords = $numRecords;
			$cntFiles = $numFiles;
			$cntLinks = $numLinks;
			$cntUsers = count( array_keys( $users ) );
			$cntUserFiles = count( array_keys( $userfiles ) );
			
			
			
		}
		// This query is for the releases within the package.  Modified the order by release_id desc to show latest releases first - Tod Hing 12015-16
		if ($fromYear != false && $fromMonth != false && $fromDay != false) {
			$resReleases = db_query_params("SELECT p.package_id, a.release_id,
				CASE WHEN simtk_filetype = 'URL' THEN 2 ELSE CASE WHEN type_id = 9997 THEN 1 ELSE 0 END END AS category, count( distinct user_id ) AS users
				FROM frs_frpg_view a 
				LEFT JOIN frs_dlstats_file d ON a.file_id = d.file_id 
				LEFT JOIN frs_file f ON f.file_id = a.file_id
				LEFT JOIN frs_package p ON a.package_id = p.package_id
				WHERE a.group_id = $1 AND p.simtk_group_link IS NULL
					AND ((month=$fromYear$fromMonth AND day>=$fromDay) 
					OR (month>$fromYear$fromMonth)) 
				GROUP BY category, a.release_id, p.package_id 
				ORDER BY p.package_id, release_id desc, category",array($this->group->getID()));
		}
		else {
			$resReleases = db_query_params("SELECT p.package_id, a.release_id,
				CASE WHEN simtk_filetype = 'URL' THEN 2 ELSE CASE WHEN type_id = 9997 THEN 1 ELSE 0 END END AS category, count( distinct user_id ) AS users
				FROM frs_frpg_view a 
				LEFT JOIN frs_dlstats_file d ON a.file_id = d.file_id 
				LEFT JOIN frs_file f ON f.file_id = a.file_id
				LEFT JOIN frs_package p ON a.package_id = p.package_id
				WHERE a.group_id = $1 AND p.simtk_group_link IS NULL
				GROUP BY category, a.release_id, p.package_id 
				ORDER BY p.package_id, release_id desc, category",array($this->group->getID()));
		}

		$packages = array();
		$packageCounts = array();
		$packageTotals = array();
		$packageDates = array();
		$packageStatus = array();
		$packageIsPublic = array();
		$releaseNames = array();
		$releaseDates = array();
		$releaseTotals = array();
		$releaseStatus = array();
		if ( $resReleases )
		{
			for ( $i=0; $i<db_numrows( $resReleases ); $i++ )
			{
				$package = db_result( $resReleases, $i, 'package_id' );
				$release = db_result( $resReleases, $i, 'release_id' );
				$doc = db_result( $resReleases, $i, 'category' );
				$packages[ $package ][ $release ][ $doc ] = db_result( $resReleases, $i, 'users' );
				//echo "user: " . $packages[ $package ][ $release ][ $doc ] . "<br>";
			}
			
		}

		
		
		if ($fromYear != false && $fromMonth != false && $fromDay != false) {
			$resPackages = db_query_params ("SELECT package_id,
				CASE WHEN simtk_filetype = 'URL' THEN 2 ELSE CASE WHEN type_id = 9997 THEN 1 ELSE 0 END END AS category,
				count( distinct user_id ) AS users
				FROM frs_frpg_view a 
				LEFT JOIN frs_dlstats_file d ON a.file_id = d.file_id 
				LEFT JOIN frs_file f ON f.file_id = a.file_id 
				WHERE package_id IN ( " . implode( ",", array_keys( $packages ) )
				. ")
					AND ((month=$fromYear$fromMonth AND day>=$fromDay) 
					OR (month>$fromYear$fromMonth)) 
				GROUP BY category, package_id 
				ORDER BY package_id, category",array());
		}
		else {
			$resPackages = db_query_params ("SELECT package_id,
				CASE WHEN simtk_filetype = 'URL' THEN 2 ELSE CASE WHEN type_id = 9997 THEN 1 ELSE 0 END END AS category,
				count( distinct user_id ) AS users
				FROM frs_frpg_view a 
				LEFT JOIN frs_dlstats_file d ON a.file_id = d.file_id 
				LEFT JOIN frs_file f ON f.file_id = a.file_id 
				WHERE package_id IN ( " . implode( ",", array_keys( $packages ) )
				. ") GROUP BY category, package_id 
				ORDER BY package_id, category",array());
		}
		
		if ( $resPackages )
		{
			while ( $results = db_fetch_array( $resPackages ) )
			{
				$package = $results[ 'package_id' ];
				$doc = $results[ 'category' ];
				$packageCounts[ $package ][ $doc ] = $results[ 'users' ];
			}
			db_free_result( $resPackages );
		}

		
		
		if ($fromYear != false && $fromMonth != false && $fromDay != false) {
			$resReleaseTotal = db_query_params ("SELECT p.package_id AS package_id, r.release_id, count( distinct user_id ) AS users
				FROM frs_file f 
				JOIN frs_dlstats_file d ON f.file_id = d.file_id 
				JOIN frs_release r ON f.release_id = r.release_id 
				JOIN frs_package p ON r.package_id = p.package_id 
				WHERE p.package_id IN ( " . implode( ",", array_keys( $packages ) )
				. ")
					AND ((month=$fromYear$fromMonth AND day>=$fromDay) 
					OR (month>$fromYear$fromMonth)) 
				GROUP BY p.package_id, r.release_id
				ORDER BY package_id, release_id",array());
		}
		else {
			$resReleaseTotal = db_query_params ("SELECT p.package_id AS package_id, r.release_id, count( distinct user_id ) AS users
				FROM frs_file f 
				JOIN frs_dlstats_file d ON f.file_id = d.file_id 
				JOIN frs_release r ON f.release_id = r.release_id 
				JOIN frs_package p ON r.package_id = p.package_id 
				WHERE p.package_id IN ( " . implode( ",", array_keys( $packages ) )
				. ") GROUP BY p.package_id, r.release_id
				ORDER BY package_id, release_id",array());
		}
		
		if ( $resReleaseTotal )
		{
			while ( $results = db_fetch_array( $resReleaseTotal ) )
			{
				$rel = $results[ 'release_id' ];
				$releaseTotals[ $rel ] = $results[ 'users' ];
			}
			db_free_result( $resReleaseTotal );
		}
		
		
		$releases = db_query_params ("SELECT p.package_id, r.release_id, r.status_id AS status, r.name, release_date
			FROM frs_release r
			JOIN frs_package p ON r.package_id = p.package_id
			JOIN frs_file f ON f.release_id = r.release_id
			WHERE p.package_id IN ( " . implode( ",", array_keys( $packages ) ) . ")",array());
		
		if ( $releases )
		{
			while ( $results = db_fetch_array( $releases ) )
			{
				$rel = $results[ 'release_id' ];
				$releaseNames[ $rel ] = $results[ 'name' ];
				$releaseDates[ $rel ] = $results[ 'release_date' ];
				$releaseStatus[ $rel ] = $results[ 'status' ];
				if ( !$packages[ $results[ 'package_id' ] ][ $rel ] )
				{
					$packages[ $results[ 'package_id' ] ][ $rel ] = array();
					$releaseTotals[ $rel ] = 0;
				}
			}
		}
		
		if ($fromYear != false && $fromMonth != false && $fromDay != false) {
			$resPackageTotal = db_query_params ("SELECT p.package_id AS package_id, count( distinct user_id ) AS users
				FROM frs_package p 
				JOIN frs_release r ON r.package_id = p.package_id 
				JOIN frs_file f ON f.release_id = r.release_id 
				JOIN frs_dlstats_file d ON f.file_id = d.file_id
				WHERE p.package_id IN ( " . implode( ",", array_keys( $packages ) )
				. ")
					AND ((month=$fromYear$fromMonth AND day>=$fromDay) 
					OR (month>$fromYear$fromMonth)) 
				GROUP BY p.package_id
				ORDER BY package_id",array());
		}
		else {
			$resPackageTotal = db_query_params ("SELECT p.package_id AS package_id, count( distinct user_id ) AS users
				FROM frs_package p 
				JOIN frs_release r ON r.package_id = p.package_id 
				JOIN frs_file f ON f.release_id = r.release_id 
				JOIN frs_dlstats_file d ON f.file_id = d.file_id
				WHERE p.package_id IN ( " . implode( ",", array_keys( $packages ) )
				. ") GROUP BY p.package_id
				ORDER BY package_id",array());
		}
		
		if ( $resPackageTotal )
		{
			while ( $results = db_fetch_array( $resPackageTotal ) )
			{
				$package = $results[ 'package_id' ];
				$packageTotals[ $package ] = $results[ 'users' ];
			}
		}
		
		$linkRes = db_query_params ("SELECT DISTINCT r.release_id, r.name
			FROM frs_release r 
			JOIN frs_file f ON r.release_id = f.release_id 
			JOIN frs_package p ON r.package_id = p.package_id
			WHERE p.package_id IN ( " . implode( ",", array_keys( $packages ) ) . ")" . " AND r.status_id = 1 AND p.status_id = 1 AND p.is_public = 1",array());
		
		//if ( $public )
		//{
		//	$sql .= " AND r.status_id = 1 AND p.status_id = 1 AND p.is_public = 1";
		//}
		
		$linkReleases = array();
		$binReleases = array();
		if ( $linkRes )
		{
			while ( $row = db_fetch_array( $linkRes ) )
			{
				if ( isset($row[ 'simtk_filetype' ]) && $row[ 'simtk_filetype' ] == "URL" )
					$linkReleases[ $row[ 'release_id' ] ] = $row[ 'name' ];
				else
					$binReleases[ $row[ 'release_id' ] ] = $row[ 'name' ];
			}
		}
		
		//echo implode( ",", array_keys( $packages ));
		
		$packageList = db_query_params ("SELECT p.package_id, p.name, p.status_id AS status, p.is_public,
			MAX( release_date ) AS release_date
			FROM frs_package p
			JOIN frs_release r ON p.package_id = r.package_id
			JOIN frs_file f ON f.release_id = r.release_id
			WHERE p.package_id IN ( " . implode( ",", array_keys( $packages ) )
			. ") GROUP BY p.package_id, p.name, p.status_id, p.is_public",array());
		
        $packageArray = array();
		
		if ( $packageList )
		{
			//$xmlData .= "<package_list>";
			while( $results = db_fetch_array( $packageList ) )
			{
				$package = $results[ 'package_id' ];
				//echo "package: " . $package . "<br>";
				$packageArray[$package]['package_id'] = $results[ 'package_id' ];
				$packageArray[$package]['release_date'] = $results[ 'release_date' ];
				$packageArray[$package]['name'] = $results[ 'name' ];
				$packageArray[$package]['status'] = $results[ 'status' ];
				$packageArray[$package]['is_public'] = $results[ 'is_public' ];
				$packageArray[$package]['unique_users'] = isset($packageTotals[ $package ]) && $packageTotals[ $package ] ? $packageTotals[ $package ] : "0";
				
				//$xmlData .= "<id><![CDATA[" . $package . "]]></id>";
				//$xmlData .= "<release_date><![CDATA[" . $results[ 'release_date' ] . "]]></release_date>";
				//$xmlData .= "<name><![CDATA[" . unescape( $results[ 'name' ] ) . "]]></name>";
				//$xmlData .= "<status><![CDATA[" . $results[ 'status' ] . "]]></status>";
				//$xmlData .= "<is_public><![CDATA[" . $results[ 'is_public' ] . "]]></is_public>";
				//$xmlData .= "<unique_users><![CDATA[" . ( $packageTotals[ $package ] ? $packageTotals[ $package ] : "0" ) . "]]></unique_users>";
				
				
				
				if ( $packageCounts[ $package ] )
				{
					foreach ( array_keys( $packageCounts[ $package ] ) as $doc )
					{
						if ( $doc == 0 ) {
							$tag = "files";
							$packageArray[$package]['files'] = $packageCounts[ $package ][ $doc ];
						}
						else if ( $doc == 1 ) {
							$tag = "documentation";
							$packageArray[$package]['documentation'] = $packageCounts[ $package ][ $doc ];
						}
						else if ( $doc == 2 ) {
							$tag = "links";
							$packageArray[$package]['links'] = $packageCounts[ $package ][ $doc ];
						}
						//$xmlData .= "<$tag><![CDATA[" . $packageCounts[ $package ][ $doc ] . "]]></$tag>";
					}
				}
				//$xmlData .= "<release_list>";
				foreach ( array_keys( $packages[ $package ] ) as $rel )
				{
					//$xmlData .= "<release>";
					//$xmlData .= "<id><![CDATA[" . $rel . "]]></id>";
					//$xmlData .= "<release_date><![CDATA[" . $releaseDates[ $rel ] . "]]></release_date>";
					//$xmlData .= "<name><![CDATA[" . unescape( $releaseNames[ $rel ] ) . "]]></name>";
					//$xmlData .= "<status><![CDATA[" . $releaseStatus[ $rel ] . "]]></status>";
					//if ( in_array( $rel, array_keys( $linkReleases ) ) )
					//	$xmlData .= "<has_links />";
					//if ( in_array( $rel, array_keys( $binReleases ) ) )
					//	$xmlData .= "<has_binaries />";
					//$xmlData .= "<unique_users><![CDATA[" . $releaseTotals[ $rel ] . "]]></unique_users>";
					$packageArray[$package][$rel]['release_id'] = $rel;
					$packageArray[$package][$rel]['release_name'] = $releaseNames[ $rel ];
					if (isset($releaseTotals[ $rel ])) {
						$packageArray[$package][$rel]['release_totals'] = $releaseTotals[ $rel ];
					}
					else {
						$packageArray[$package][$rel]['release_totals'] = 0;
					}
					foreach ( array_keys( $packages[ $package ][ $rel ] ) as $doc )
					{
						if ( $doc == 0 ) {
							$tag = "files";
							$packageArray[$package][$rel]['files'] = $packages[ $package ][ $rel ][ $doc ];
						}
						else if ( $doc == 1 ) {
							$tag = "documentation";
							$packageArray[$package][$rel]['documentation'] = $packages[ $package ][ $rel ][ $doc ];
						}
						else if ( $doc == 2 ) {
							$tag = "links";
							$packageArray[$package][$rel]['links'] = $packages[ $package ][ $rel ][ $doc ];
						
						}
						//$xmlData .= "<$tag><![CDATA[" . $packages[ $package ][ $rel ][ $doc ] . "]]></$tag>";
					}
					//$xmlData .= "</release>";
				}
				//$xmlData .= "</release_list>";
				//$xmlData .= "</package>";
				
			}
			//db_free_result( $resPackages );
			//$xmlData .= "</package_list>";
			//echo "p: " . var_dump($packageArray);
		}
	
/*	
		
		$res = db_query_params ("SELECT filelocation FROM frs_file f
			JOIN frs_frpg_view a ON f.file_id = a.file_id
			JOIN frs_package p ON p.package_id = a.package_id
			WHERE p.group_link IS NOT NULL AND f.filetype = 'URL' AND a.group_id = " . $this->getID()
			. " ORDER BY p.name",array());
		
		if ( $res && db_numrows( $res ) > 0 )
		{
			$package_ids = array();
			while ( $row = db_fetch_array( $res ) )
			{
				array_push( $package_ids, $row[ 'filelocation' ] );
			}
			db_free_result( $res );
			
			$child_groups = array();
			$child_packages = array();
			$res = db_query_params ("SELECT a.group_id, p.is_public, p.status_id, name, unix_group_name, count( distinct user_id ) AS users
				FROM frs_frpg_view a
				LEFT JOIN frs_dlstats_file d ON d.file_id = a.file_id
				LEFT JOIN frs_package p ON p.group_link = a.group_id
				LEFT JOIN groups g ON a.group_id = g.group_id
				WHERE a.package_id IN ( " . implode( ",", $package_ids ) . " )
				AND p.group_id = " . $this->getID() . "
				GROUP BY a.group_id, unix_group_name, p.is_public, p.status_id, name",array());
			
			while ( $res && $row = db_fetch_array( $res ) )
			{
				$gid = $row[ 'group_id' ];
				$child_groups[ $gid ] = array();
				$child_groups[ $gid ][ 'name' ] = $row[ 'name' ];
				$child_groups[ $gid ][ 'all' ] = $row[ 'users' ];
				$child_groups[ $gid ][ 'unix_name' ] = $row[ 'unix_group_name' ];
				$child_groups[ $gid ][ 'status' ] = $row[ 'status_id' ];
				$child_groups[ $gid ][ 'public' ] = $row[ 'is_public' ];
			}
			db_free_result( $res );
			
			$res = db_query_params ("SELECT a.group_id, a.package_id, p2.is_public as child_public, p2.status_id as child_status, p.status_id, filename, count( DISTINCT user_id ) AS users
				FROM frs_frpg_view a
				LEFT JOIN frs_dlstats_file d ON a.file_id = d.file_id
				LEFT JOIN frs_package p ON a.group_id = p.group_link
				LEFT JOIN frs_file f ON filelocation = a.package_id 
				LEFT JOIN frs_frpg_view a2 ON a2.file_id = f.file_id
				LEFT JOIN frs_package p2 ON a.package_id = p2.package_id
				WHERE a.package_id IN ( " . implode( ",", $package_ids ) . " )
				AND a2.group_id = " . $this->getID() .
				" GROUP BY a.group_id, a.package_id, p2.is_public, p2.status_id, p.status_id, filename",array());
			
			while( $res && $row = db_fetch_array( $res ) )
			{
				$gid = $row[ 'group_id' ];
				$pid = $row[ 'package_id' ];
				if ( !$child_packages[ $gid ] )
					$child_packages[ $gid ] = array();
				$child_packages[ $gid ][ $pid ] = array();
				$child_packages[ $gid ][ $pid ][ 'name' ] = $row[ 'filename' ];
				$child_packages[ $gid ][ $pid ][ 'all' ] = $row[ 'users' ];
				$child_packages[ $gid ][ $pid ][ 'status' ] = $row[ 'status_id' ];
				$child_packages[ $gid ][ $pid ][ 'child_public' ] = $row[ 'child_public' ];
				$child_packages[ $gid ][ $pid ][ 'child_status' ] = $row[ 'child_status' ];
			}
			db_free_result( $res );

			$res = db_query_params ("SELECT a.group_id, count( distinct user_id ) AS users,
				CASE WHEN filetype = 'URL' THEN 'links' ELSE CASE WHEN type_id = 2000 THEN 'documentation' ELSE 'files' END END AS category
				FROM frs_frpg_view a
				LEFT JOIN frs_dlstats_file d ON d.file_id = a.file_id
				LEFT JOIN frs_file f ON f.file_id = a.file_id
				WHERE a.package_id IN ( " . implode( ",", $package_ids ) . " )
				GROUP BY a.group_id, category",array());
			
			while( $res && $row = db_fetch_array( $res ) )
			{
				$gid = $row[ 'group_id' ];
				$child_groups[ $gid ][ $row[ 'category' ] ] = $row[ 'users' ];
			}
			db_free_result( $res );

			$res = db_query_params ("SELECT a.group_id, a.package_id, count( DISTINCT user_id ) AS users,
				CASE WHEN filetype = 'URL' THEN 'links' ELSE CASE WHEN type_id = 2000 THEN 'documentation' ELSE 'files' END END AS category,
				max( CASE WHEN filetype != 'URL' THEN 1 ELSE 0 END ) AS has_files,
				max( CASE WHEN filetype = 'URL' THEN 1 ELSE 0 END ) AS has_links
				FROM frs_frpg_view a
				LEFT JOIN frs_dlstats_file d ON a.file_id = d.file_id
				LEFT JOIN frs_package p ON a.group_id = p.group_link
				LEFT JOIN frs_file f ON f.file_id = a.file_id
				WHERE a.package_id IN ( " . implode( ",", $package_ids ) . " )
				GROUP BY a.group_id, a.package_id, category",array());
			
			while( $res && $row = db_fetch_array( $res ) )
			{
				$gid = $row[ 'group_id' ];
				$pid = $row[ 'package_id' ];
				$child_packages[ $gid ][ $pid ][ $row[ 'category' ] ] = $row[ 'users' ];
				$child_packages[ $gid ][ $pid ][ 'has_files' ] = $row[ 'has_files' ];
				$child_packages[ $gid ][ $pid ][ 'has_links' ] = $row[ 'has_links' ];
			}
			
			$xmlData .= "<member_package_list>";
			foreach ( array_keys( $child_groups ) as $gid )
			{
				$xmlData .= "<package>";
				$xmlData .= "<id><![CDATA[" . $gid . "]]></id>";
				$xmlData .= "<name><![CDATA[" . unescape( $child_groups[ $gid ][ 'name' ] ) . "]]></name>";
				$xmlData .= "<unix_name><![CDATA[" . $child_groups[ $gid ][ 'unix_name' ] . "]]></unix_name>";
				$xmlData .= "<release_date>1</release_date>";
				$xmlData .= "<status><![CDATA[" . $child_groups[ $gid ][ 'status' ] . "]]></status>";
				$xmlData .= "<is_public><![CDATA[" . $child_groups[ $gid ][ 'public' ] . "]]></is_public>";
				$xmlData .= "<unique_users><![CDATA[" . $child_groups[ $gid ][ 'all' ] . "]]></unique_users>";
				foreach ( array( "files", "documentation", "links" ) as $tag )
				{
					if ( $child_groups[ $gid ][ $tag ] > -1 )
						$xmlData .= "<$tag><![CDATA[" . $child_groups[ $gid ][ $tag ] . "]]></$tag>";
				}
				$xmlData .= "<release_list>";
				foreach( array_keys( $child_packages[ $gid ] ) as $pid )
				{
					$xmlData .= "<release>";
					$xmlData .= "<id><![CDATA[" . $pid . "]]></id>";
					$xmlData .= "<name><![CDATA[" . unescape( $child_packages[ $gid ][ $pid ][ 'name' ] ) . "]]></name>";
					$xmlData .= "<release_date>1</release_date>";
					$xmlData .= "<status><![CDATA[" . $child_packages[ $gid ][ $pid ][ 'status' ] . "]]></status>";
					$xmlData .= "<child_public><![CDATA[" . $child_packages[ $gid ][ $pid ][ 'child_public' ] . "]]></child_public>";
					$xmlData .= "<child_status><![CDATA[" . $child_packages[ $gid ][ $pid ][ 'child_status' ] . "]]></child_status>";
					$xmlData .= "<unique_users><![CDATA[" . $child_packages[ $gid ][ $pid ][ 'all' ] . "]]></unique_users>";
					foreach ( array( "files", "documentation", "links" ) as $tag )
					{
						if ( $child_packages[ $gid ][ $pid ][ $tag ] > -1 )
							$xmlData .= "<$tag><![CDATA[" . $child_packages[ $gid ][ $pid ][ $tag ] . "]]></$tag>";
					}
					if ( $child_packages[ $gid ][ $pid ][ 'has_links' ] )
						$xmlData .= "<has_links />";
					if ( $child_packages[ $gid ][ $pid ][ 'has_files' ] )
						$xmlData .= "<has_binaries />";
					$xmlData .= "</release>";
				}
				$xmlData .= "</release_list>";
				$xmlData .= "</package>";
			}
			$xmlData .= "</member_package_list>";
			db_free_result( $res );
		*/	
        return $packageArray;
	
	}

	
       
	/**
     *      @return boolean success.
     */
	function &getGroup() {
		return $this->group;
	}


}

class DALQueryResult {

  private $_results = array();

  public function __construct(){}

  public function __set($var,$val){
    $this->_results[$var] = $val;
  }

  public function __get($var){
    if (isset($this->_results[$var])){
      return $this->_results[$var];
    }
    else {
      return null;
    }
  }
  
}



