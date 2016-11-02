<?php
/**
 * FusionForge.class.php
 *
 * FusionForge top-level information
 *
 * Copyright 2002, GForge, LLC
 * Copyright 2009-2011, Roland Mas
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
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

require_once $gfcommon.'include/Error.class.php';
require_once $gfcommon.'include/Stats.class.php';

class FusionForge extends Error {

	var $software_name ;
	var $software_type ;
	var $software_version ;

	/**
	 *	FusionForge - FusionForge object constructor
	 */
	function FusionForge() {
		$this->Error();

		$pkg = dirname(dirname(__FILE__)).'/pkginfo.inc.php';
		if (file_exists($pkg)) {
			include $pkg;
		}

		if (isset($forge_pkg_name)) {
			$this->software_name = $forge_pkg_name;
		} else {
			$this->software_name = 'FusionForge' ;
		}

		if (isset($forge_pkg_version)) {
			$this->software_version = $forge_pkg_version;
		} else {
			$this->software_version = '5.3.1' ;
		}

		if (isset($forge_pkg_type)) {
			$this->software_type = $forge_pkg_type;
		} else {
			$this->software_type = $this->software_name;
		}

		return true;
	}

	function getNumberOfPublicHostedProjects() {
		$res = db_query_params ('SELECT group_id FROM groups WHERE status=$1',
				      array ('A'));
		if (!$res) {
			$this->setError('Unable to get hosted project count: '.db_error());
			return false;
		}
		$count = 0;
		$ra = RoleAnonymous::getInstance() ;
		while ($row = db_fetch_array($res)) {
			if ($ra->hasPermission('project_read', $row['group_id'])) {
				$count++;
			}
		}
		return $count;
	}

	function getNumberOfHostedProjects() {
/*
		$res = db_query_params ('SELECT group_id FROM groups WHERE status=$1',
					array ('A'));
		if (!$res) {
			$this->setError('Unable to get hosted project count: '.db_error());
			return false;
		}
		$count = 0;
		$ra = RoleAnonymous::getInstance() ;
		while ($row = db_fetch_array($res)) {
			if ($ra->hasPermission('project_read', $row['group_id'])) {
				$count++;
			}
		}
*/
		$res = db_query_params('SELECT count(DISTINCT g.group_id) FROM groups g ' .
			'WHERE status=$1 ' .
			"AND NOT simtk_is_system IS NULL",
			array('A'));
		if (!$res || db_numrows($res) < 1) {
			$this->setError('Unable to get group count: '.db_error());
			return false;
		}
		return $this->parseCount($res);
	}

	function getNumberOfActiveUsers() {
		$res = db_query_params ('SELECT count(*) AS count FROM users WHERE status=$1 and user_id != 100',
					array ('A'));
		if (!$res || db_numrows($res) < 1) {
			$this->setError('Unable to get user count: '.db_error());
			return false;
		}
		return $this->parseCount($res);
	}

	function getPublicProjectNames() {
		$res = db_query_params ('SELECT unix_group_name, group_id FROM groups WHERE status=$1 ORDER BY unix_group_name',
					array ('A'));
		if (!$res) {
			$this->setError('Unable to get list of public projects: '.db_error());
			return false;
		}
		$result = array();
		$ra = RoleAnonymous::getInstance() ;
		while ($row = db_fetch_array($res)) {
			if ($ra->hasPermission('project_read', $row['group_id'])) {
				$result[] = $row['unix_group_name'];
			}
		}
		return $result;
	}

	function getTotalDownloads() {
		//$res_count = db_query_params ('SELECT SUM(downloads) AS downloads FROM stats_site', array());
		$res_count = db_query_params ('SELECT SUM(frs_dlstats_grouptotal_vw.downloads) AS downloads ' .
			'FROM frs_dlstats_grouptotal_vw, groups ' .  
			'WHERE frs_dlstats_grouptotal_vw.group_id = groups.group_id AND groups.status=$1',
			array ('A'));
		if (db_numrows($res_count) > 0) {
			$row_count = db_fetch_array($res_count);
			return $row_count['downloads'];
		}
		else {
			return "error";
		}
	}


	// Get top total downloads.
	function getTopTotalDownloadProjects(&$arrProjLink,
                &$arrLogo, 
                &$arrValue,
                &$arrSummary,
		$countTop = 15) {

		$res_topdown = db_query_params ('
			SELECT groups.group_id,
			groups.group_name,
			groups.unix_group_name,
			groups.simtk_logo_file,
			groups.simtk_summary,
			frs_dlstats_grouptotal_vw.downloads
			FROM frs_dlstats_grouptotal_vw, groups
			WHERE
			frs_dlstats_grouptotal_vw.group_id=groups.group_id AND groups.status=$1
			ORDER BY downloads DESC
			', array ('A'));

		$count = 0 ;
		while (($row_topdown=db_fetch_array($res_topdown)) && ($count < $countTop)) {
			if (!forge_check_perm ('project_read', $row_topdown['group_id'])) {
				continue ;
			}

			// Get unix group name to use as index. This name is unique.
			$unixGroupName = $row_topdown['unix_group_name'];

			if ($row_topdown['downloads'] > 0) {

				// Do not show total downloads by itself.
				// Show top total downloads only if the project is
				// already present because of other trending attributes.
				if (!isset($arrValue[$unixGroupName])) {
					// Skip.
					continue;
				}

				$t_prj_link = util_make_link_g(
					$unixGroupName,
					$row_topdown['group_id'], 
					$row_topdown['group_name']);
				$t_logo = $row_topdown['simtk_logo_file'];
				$t_summary = $row_topdown['simtk_summary'];
				$t_downloads = number_format($row_topdown['downloads']);

				if (!isset($arrProjLink[$unixGroupName])) {
					// Project name has not appeared before.
					// Fill in project info.
					$arrProjLink[$unixGroupName] = $t_prj_link;
					$arrLogo[$unixGroupName] = $t_logo;
					$arrSummary[$unixGroupName] = $t_summary;
				}

/*
				if (!isset($arrValue[$unixGroupName])) {
					// Initialize array for returning values.
					$arrValue[$unixGroupName] = array();
				}
*/
				$theValues = $arrValue[$unixGroupName];
				$theValues[] = "<span class='type'>Total downloads: </span><span class='content'>" . $t_downloads . "</span>";
				$arrValue[$unixGroupName] = $theValues;

				$count++ ;
			}
		}
	}

	// Get top downloads in the past week.
	function getTopDownloadProjects(&$arrProjLink,
                &$arrLogo, 
                &$arrValue,
                &$arrSummary,
		$countTop = 15,
		$daysBack = 7) {

		$time = time();

		$time_back = $time - 86400 * $daysBack;
		$this_week = $time;

		$last_year = date('Y', $time_back);
		$last_month = date('m', $time_back);
		$last_day = date('d',$time_back);

		$this_year = date('Y', $this_week);
		$this_month = date('m', $this_week);
		$this_day = date('d', $this_week);

		//echo "time_back: $last_year$last_month:$last_day \n";
		//echo "this_week: $this_year$this_month:$this_day \n";

		$sqlTopDownloads = "SELECT groups.group_id,
			groups.group_name,
			groups.unix_group_name,
			groups.simtk_logo_file,
			groups.simtk_summary,
			sum(downloads) as downloads 
			FROM frs_dlstats_group_vw, groups
			WHERE
			((month=$last_year$last_month AND day>$last_day) OR (month>$last_year$last_month)) 
				AND frs_dlstats_group_vw.group_id=groups.group_id 
				AND groups.status=$1
			GROUP by frs_dlstats_group_vw.group_id, groups.group_id  
			ORDER BY downloads DESC";
		$res_topdown = db_query_params ($sqlTopDownloads, array ('A'));

		$count = 0 ;
		while (($row_topdown=db_fetch_array($res_topdown)) && ($count < $countTop)) {
			if (!forge_check_perm ('project_read', $row_topdown['group_id'])) {
				continue ;
			}

			// Get unix group name to use as index. This name is unique.
			$unixGroupName = $row_topdown['unix_group_name'];

			if ($row_topdown['downloads'] > 0) {
				$t_prj_link = util_make_link_g(
					$unixGroupName,
					$row_topdown['group_id'], 
					$row_topdown['group_name']);
				$t_logo = $row_topdown['simtk_logo_file'];
				$t_summary = $row_topdown['simtk_summary'];
				$t_downloads = number_format($row_topdown['downloads']);

				if (!isset($arrProjLink[$unixGroupName])) {
					// Project name has not appeared before.
					// Fill in project info.
					$arrProjLink[$unixGroupName] = $t_prj_link;
					$arrLogo[$unixGroupName] = $t_logo;
					$arrSummary[$unixGroupName] = $t_summary;
				}

				if (!isset($arrValue[$unixGroupName])) {
					// Initialize array for returning values.
					$arrValue[$unixGroupName] = array();
				}
				$theValues = $arrValue[$unixGroupName];
				$theValues[] = "<span class='type'>Downloads last week: </span><span class='content'>" . $t_downloads . "</span>";
				$arrValue[$unixGroupName] = $theValues;

				$count++ ;
			}
		}
	}

	// Get projects with most forum posts in the past week.
	function getMostForumPostsProjects(&$arrProjLink,
                &$arrLogo, 
                &$arrValue,
                &$arrSummary,
		$arrGroupIds,
		$arrNumPosts,
		$countTop = 15) {

		if (empty($arrGroupIds)) {
			// No forum posts.
			return;
		}

		// Comma-separated group ids.
		$strGroupIds = implode(",", $arrGroupIds);

		$sqlMostPosts = 'SELECT group_id, group_name, unix_group_name, ' .
			'simtk_logo_file, simtk_summary FROM groups ' .
			'WHERE group_id IN (' . $strGroupIds . ') ' .
			'AND groups.status=$1';
		$resMostPosts = db_query_params($sqlMostPosts, array('A'));

		$count = 0 ;
		while (($rowMostPosts=db_fetch_array($resMostPosts)) && ($count < $countTop)) {
			if (!forge_check_perm ('project_read', $rowMostPosts['group_id'])) {
				continue ;
			}

			// Get unix group name to use as index. This name is unique.
			$unixGroupName = $rowMostPosts['unix_group_name'];

			$t_prj_link = util_make_link_g(
				$unixGroupName,
				$rowMostPosts['group_id'], 
				$rowMostPosts['group_name']);
			$t_logo = $rowMostPosts['simtk_logo_file'];
			$t_summary = $rowMostPosts['simtk_summary'];

			if (!isset($arrProjLink[$unixGroupName])) {
				// Project name has not appeared before.
				// Fill in project info.
				$arrProjLink[$unixGroupName] = $t_prj_link;
				$arrLogo[$unixGroupName] = $t_logo;
				$arrSummary[$unixGroupName] = $t_summary;
			}

			if (!isset($arrValue[$unixGroupName])) {
				// Initialize array for returning values.
				$arrValue[$unixGroupName] = array();
			}
			$theValues = $arrValue[$unixGroupName];
			$theValues[] = "<span class='type'>Forum posts last week: </span><span class='content'>" . $arrNumPosts[$rowMostPosts['group_id']] . "</span>";
			$arrValue[$unixGroupName] = $theValues;

			$count++ ;
		}
	}

	// Get most active in the past week.
	function getMostActiveProjects(&$arrProjLink,
                &$arrLogo, 
                &$arrValue,
                &$arrSummary,
		$countTop = 15) {

		$stats = new Stats();
		$result = $stats->getMostActiveStats('week', 0);

		$count = 0;
	        $res = $row_mostactive=db_fetch_array($result);
	        while (($row_mostactive=db_fetch_array($result)) && ($count < $countTop)) {
			if (!forge_check_perm ('project_read', $row_mostactive['group_id'])) {
				continue ;
			}

			// Get unix group name to use as index. This name is unique.
			$unixGroupName = $row_mostactive['unix_group_name'];

			$t_prj_link = util_make_link_g(
				$unixGroupName,
				$row_mostactive['group_id'],
				$row_mostactive['group_name']);
			$t_logo = $row_mostactive['simtk_logo_file'];
			$t_summary = $row_mostactive['simtk_summary'];
			$t_percentile = number_format($row_mostactive['percentile'], 1);

			if (!isset($arrProjLink[$unixGroupName])) {
				// Project name has not appeared before.
				// Fill in project info.
				$arrProjLink[$unixGroupName] = $t_prj_link;
				$arrLogo[$unixGroupName] = $t_logo;
				$arrSummary[$unixGroupName] = $t_summary;
			}

			if (!isset($arrValue[$unixGroupName])) {
				// Initialize array for returning values.
				$arrValue[$unixGroupName] = array();
			}
			$theValues = $arrValue[$unixGroupName];
			$theValues[] = "<span class='type'>Active: </span><span class='content'>" . $t_percentile . "%</span>";
			$arrValue[$unixGroupName] = $theValues;

			$count++ ;
		}
	}

	// Get projects with new download files in the past week.
	function getProjectsNewDownloadFiles(&$arrProjLink,
                &$arrLogo, 
                &$arrValue,
                &$arrSummary,
		$countTop = 15,
		$daysBack = 7) {

		$time = time();
		$time_back = $time - 86400 * $daysBack;

		$sqlNewFiles = 'SELECT g.group_id, g.group_name, g.unix_group_name, ' .
			'g.simtk_logo_file, g.simtk_summary, count(*) AS cnt_files ' .
			'FROM groups g ' .
			'JOIN frs_package p ON g.group_id=p.group_id ' .
			'JOIN frs_release r ON p.package_id=r.package_id ' .
			'JOIN frs_file f ON r.release_id=f.release_id ' .
			'WHERE f.post_date>$1 ' .
			'AND g.status=$2 ' .
			'GROUP BY g.group_id, g.group_name, g.unix_group_name, ' .
			'g.simtk_logo_file, g.simtk_summary ' .
			'ORDER BY cnt_files DESC';
		$resNewFiles = db_query_params($sqlNewFiles, array($time_back, 'A'));

		$count = 0 ;
		while (($rowNewFiles=db_fetch_array($resNewFiles)) && ($count < $countTop)) {
			if (!forge_check_perm ('project_read', $rowNewFiles['group_id'])) {
				continue ;
			}

			// Get unix group name to use as index. This name is unique.
			$unixGroupName = $rowNewFiles['unix_group_name'];

			$t_prj_link = util_make_link_g(
				$unixGroupName,
				$rowNewFiles['group_id'],
				$rowNewFiles['group_name']);
			$t_logo = $rowNewFiles['simtk_logo_file'];
			$t_summary = $rowNewFiles['simtk_summary'];
			$new_files = $rowNewFiles['cnt_files'];

			if (!isset($arrProjLink[$unixGroupName])) {
				// Project name has not appeared before.
				// Fill in project info.
				$arrProjLink[$unixGroupName] = $t_prj_link;
				$arrLogo[$unixGroupName] = $t_logo;
				$arrSummary[$unixGroupName] = $t_summary;
			}

			if (!isset($arrValue[$unixGroupName])) {
				// Initialize array for returning values.
				$arrValue[$unixGroupName] = array();
			}
			$theValues = $arrValue[$unixGroupName];
			$theValues[] = "<span class='type'>New download files: </span><span class='content'>" . $new_files . "</span>";
			$arrValue[$unixGroupName] = $theValues;

			$count++ ;
		}
	}

	// Get number of followers per project.
	//
	// Note: Because the table project_follows can contain multiple
	// user entries for a given group, but only the one with the last timestamp
	// is the one currently in use, we have to use the following query
	// to eliminate earlier entries:
	//
	// 	SELECT a.* FROM project_follows a INNER JOIN (
	// 	SELECT group_id, user_name, max(time) AS max_time 
	//	FROM project_follows GROUP BY group_id, user_name) b 
	//	ON a.user_name=b.user_name AND a.time=b.max_time 
	//	AND a.group_id=b.group_id;
	//
	// Without the daysBack parameters, all followers are counted.
	// Otherwise, daysBack how far back to look for followers.
	function getNumFollowersProjects(&$arrProjLink,
                &$arrLogo, 
                &$arrValue,
                &$arrSummary,
		$countTop = 15,
		$daysBack = false) {

		$sqlDaysBack = '';
		if ($daysBack !== false) {
			// Number of days back.
			$sqlDaysBack = " AND a.time > CURRENT_DATE - integer '" . $daysBack . "'";
		}

		$sqlFollowers = 'SELECT g.group_id, g.group_name, g.unix_group_name, ' .
			'g.simtk_logo_file, g.simtk_summary, count(*) AS cnt_followers ' .
			'FROM groups g ' .
			'JOIN (' .
				'SELECT a.* FROM project_follows a ' .
				'INNER JOIN (' .
				'SELECT group_id, user_name, max(time) AS max_time ' .
				'FROM project_follows ' .
				'GROUP BY group_id, user_name) b ' .
				'ON a.group_id=b.group_id ' .
				'AND a.user_name=b.user_name ' .
				'AND a.time=b.max_time' .
				$sqlDaysBack .
			') p ON g.group_id=p.group_id ' .
			'WHERE g.status=$1 ' .
			'AND follows=true ' .
			'GROUP BY g.group_id, g.group_name, g.unix_group_name, ' .
			'g.simtk_logo_file, g.simtk_summary ' .
			'ORDER BY cnt_followers DESC';
		$resFollowers = db_query_params($sqlFollowers, array('A'));

		$count = 0 ;
		while (($rowFollowers=db_fetch_array($resFollowers)) && ($count < $countTop)) {
			if (!forge_check_perm ('project_read', $rowFollowers['group_id'])) {
				continue ;
			}

			// Get unix group name to use as index. This name is unique.
			$unixGroupName = $rowFollowers['unix_group_name'];

			if ($rowFollowers['cnt_followers'] > 0) {
				$t_prj_link = util_make_link_g(
					$unixGroupName,
					$rowFollowers['group_id'], 
					$rowFollowers['group_name']);
				$t_logo = $rowFollowers['simtk_logo_file'];
				$t_summary = $rowFollowers['simtk_summary'];
				$t_followers = $rowFollowers['cnt_followers'];
				if ($daysBack === false && $t_followers < 20) {
					// Count of total followers too small.
					// Skip.
					continue;
				}

				if (!isset($arrProjLink[$unixGroupName])) {
					// Project name has not appeared before.
					// Fill in project info.
					$arrProjLink[$unixGroupName] = $t_prj_link;
					$arrLogo[$unixGroupName] = $t_logo;
					$arrSummary[$unixGroupName] = $t_summary;
				}

				if (!isset($arrValue[$unixGroupName])) {
					// Initialize array for returning values.
					$arrValue[$unixGroupName] = array();
				}
				$theValues = $arrValue[$unixGroupName];
				if ($daysBack !== false) {
					// New followers only, specified by $daysBack.
					$theValues[] = "<span class='type'>New followers: </span><span class='content'>" . $t_followers . "</span>";
				}
				else {
					// All followers counted.
					$theValues[] = "<span class='type'>Total followers: </span><span class='content'>" . $t_followers . "</span>";
				}
				$arrValue[$unixGroupName] = $theValues;

				$count++ ;
			}
		}
	}

	// Get projects with new memebrs in the past week.
	function getProjectsNewMembers(&$arrProjLink,
                &$arrLogo, 
                &$arrValue,
                &$arrSummary,
		$countTop = 15,
		$daysBack = 7) {

		$time = time();
		$time_back = $time - 86400 * $daysBack;

		$sqlNewUsers = 'SELECT g.group_id, g.group_name, g.unix_group_name, ' .
			'g.simtk_logo_file, g.simtk_summary, count(*) AS cnt_users ' .
			'FROM groups g ' .
			'JOIN group_history gh ON g.group_id=gh.group_id ' .
			'WHERE gh.adddate>$1 ' .
			'AND g.status=$2 ' .
			"AND field_name='Added User' " .
			'GROUP BY g.group_id, g.group_name, g.unix_group_name, ' .
			'g.simtk_logo_file, g.simtk_summary ' .
			'ORDER BY cnt_users DESC';
		$resNewUsers = db_query_params($sqlNewUsers, array($time_back, 'A'));

		$count = 0 ;
		while (($rowNewUsers=db_fetch_array($resNewUsers)) && ($count < $countTop)) {
			if (!forge_check_perm ('project_read', $rowNewUsers['group_id'])) {
				continue ;
			}

			// Get unix group name to use as index. This name is unique.
			$unixGroupName = $rowNewUsers['unix_group_name'];

			$t_prj_link = util_make_link_g(
				$unixGroupName,
				$rowNewUsers['group_id'],
				$rowNewUsers['group_name']);
			$t_logo = $rowNewUsers['simtk_logo_file'];
			$t_summary = $rowNewUsers['simtk_summary'];
			$new_users = $rowNewUsers['cnt_users'];

			if (!isset($arrProjLink[$unixGroupName])) {
				// Project name has not appeared before.
				// Fill in project info.
				$arrProjLink[$unixGroupName] = $t_prj_link;
				$arrLogo[$unixGroupName] = $t_logo;
				$arrSummary[$unixGroupName] = $t_summary;
			}

			if (!isset($arrValue[$unixGroupName])) {
				// Initialize array for returning values.
				$arrValue[$unixGroupName] = array();
			}
			$theValues = $arrValue[$unixGroupName];
			$theValues[] = "<span class='type'>New members: </span><span class='content'>" . $new_users . "</span>";
			$arrValue[$unixGroupName] = $theValues;

			$count++ ;
		}
	}

	function parseCount($res) {
		$row_count = db_fetch_array($res);
		return $row_count['count'];
	}

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
