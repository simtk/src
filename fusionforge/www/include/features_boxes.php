<?php
/**
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012, Franck Villaume - TrivialDev
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org
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

// Attempt to set up the include path, to fix problems with relative includes

require_once $gfcommon.'include/FusionForge.class.php';
require_once $gfcommon.'include/tag_cloud.php';
require_once $gfcommon.'include/Stats.class.php';

function show_features_boxes() {
	global $HTML;

	plugin_hook ("features_boxes_top", array());
	$return = '<h2 class="skip">' . _('Features Boxes') . '</h2>';

	if (forge_get_config('use_project_tags')) {
		$contentTag = tag_cloud();
		if (!empty($contentTag)) {
			$return .= $HTML->boxTop(_('Tag Cloud'), 'Tag_Cloud');
			$return .= $contentTag;
			$return .= $HTML->boxBottom();
		}
	}
	$return .= $HTML->boxTop(sprintf(_('%s Statistics'), forge_get_config ('forge_name')), 'Forge_Statistics');
	$return .= show_sitestats();
	if (forge_get_config('use_frs')) {
		$return .= $HTML->boxMiddle(_('Top Project Downloads'), 'Top_Projects_Downloads');
		$return .= show_top_downloads();
	}
	if (forge_get_config('use_ratings')) {
		$return .= $HTML->boxMiddle(_('Highest Ranked Users'), 'Highest_Ranked_Users');
		$return .= show_highest_ranked_users();
	}
	$return .= $HTML->boxMiddle(_('Most Active This Week'), 'Most_Active_This_Week');
	$return .= show_highest_ranked_projects();
	$return .= $HTML->boxMiddle(_('Recently Registered Projects'), 'Recently_Registered_Projects');
	$return .= show_newest_projects();
	$return .= $HTML->boxMiddle(_('System Information'), 'System_Information');
	$ff = new FusionForge();
	$return .= sprintf(_('%s is running %s version %s'),
			   forge_get_config ('forge_name'),
			   $ff->software_name,
			   $ff->software_version);
	$return .= $HTML->boxBottom();
	plugin_hook ("features_boxes_bottom", array());
	return $return;
}

function show_top_downloads() {
	// TODO yesterday is now defined as two days ago.  Quick fix
	//      to allow download list to be cached before nightly
	//      aggregation is done. jbyers 2001.03.19
	//
	$return = '' ;

	$res_topdown = db_query_params ('
		SELECT groups.group_id,
		groups.group_name,
		groups.unix_group_name,
		frs_dlstats_grouptotal_vw.downloads
		FROM frs_dlstats_grouptotal_vw,groups
		WHERE
		frs_dlstats_grouptotal_vw.group_id=groups.group_id AND groups.status=$1
		ORDER BY downloads DESC
	',
					array ('A'));

	// print each one
	$count = 0 ;
	while (($row_topdown=db_fetch_array($res_topdown)) && ($count < 10)) {
		if (!forge_check_perm ('project_read', $row_topdown['group_id'])) {
			continue ;
		}
		if ($row_topdown['downloads'] > 0) {
			$t_downloads = number_format($row_topdown['downloads']);
			$t_prj_link = util_make_link_g ($row_topdown['unix_group_name'], $row_topdown['group_id'], $row_topdown['group_name']);

			$return .= '<tr>';
			$return .= '<td class="width-stat-col1">' . $t_downloads . '</td>';
			$return .= '<td>' . $t_prj_link . '</td>';
			$return .= '</tr>';
			$count++ ;
		}
	}
	if ( $return == "" ) {
		return _('No stats available');
	} else {
		$return = '<table>' . $return . "</table>\n";
	}
	$return .= '<div class="align-center">' . util_make_link ('/top/', _('All the ranking'), array('class' => 'dot-link')) . '</div>';

	return $return;
}

function stats_getprojects_active_public() {
	$ff = new FusionForge();
	return $ff->getNumberOfPublicHostedProjects();
}

function stats_getprojects_total() {
	$ff = new FusionForge();
	return $ff->getNumberOfHostedProjects();
}

function stats_getpageviews_total() {
	$res_count = db_query_params ('SELECT SUM(site_views) AS site, SUM(subdomain_views) AS subdomain FROM stats_site',
			array());
	if (db_numrows($res_count) > 0) {
		$row_count = db_fetch_array($res_count);
		return ($row_count['site'] + $row_count['subdomain']);
	} else {
		return "error";
	}
}

function stats_downloads_total() {
	$res_count = db_query_params ('SELECT SUM(downloads) AS downloads FROM stats_site',
			array());
	if (db_numrows($res_count) > 0) {
		$row_count = db_fetch_array($res_count);
		return $row_count['downloads'];
	} else {
		return "error";
	}
}

function show_sitestats() {

	$gforge = new FusionForge();
	//$return = '<p>';
	$return = "<span style='color:#8f3747;font-weight:bold;font-size:18px'>Browse SimTK Projects</span>";
	$return .= '<p/>';
	$return .= "<span style='color:#8f3747;font-weight:bold;font-size:14px'>Biological Applications</span>";
	$return .= '<p><a href="category/category.php?cat=309&sort=date&page=0&srch=&">Cardiovascular System</a></p>';
	$return .= '<p><a href="category/category.php?cat=421&sort=date&page=0&srch=&">Cell</a></p>';
	$return .= '<p><a href="category/category.php?cat=308&sort=date&page=0&srch=&">Myosin</a></p>';
	$return .= '<p><a href="category/category.php?cat=310&sort=date&page=0&srch=&">Neuromuscular System</a></p>';
	$return .= '<p><a href="category/category.php?cat=406&sort=date&page=0&srch=&">Protein</a></p>';
	$return .= '<p><a href="category/category.php?cat=307&sort=date&page=0&srch=&">RNA</a></p>';
	$return .= '<p><a href="category/category.php?cat=420&sort=date&page=0&srch=&">Tissue</a></p>';
	$return .= '<p/>';
	$return .= "<span style='color:#8f3747;font-weight:bold;font-size:14px'>Biocomputational Focus</span>";
	$return .= '<p><a href="category/category.php?cat=411&sort=date&page=0&srch=&">Experimental Anaylsis</a></p>';
	$return .= '<p><a href="category/category.php?cat=412&sort=date&page=0&srch=&">Image Processing</a></p>';
	$return .= '<p><a href="category/category.php?cat=426&sort=date&page=0&srch=&">Network Modeling and Analysis</a></p>';
	$return .= '<p><a href="category/category.php?cat=409&sort=date&page=0&srch=&">Physics-Based Simulation</a></p>';
	$return .= '<p><a href="category/category.php?cat=416&sort=date&page=0&srch=&">Statistical Analysis</a></p>';
	$return .= '<p><a href="category/category.php?cat=415&sort=date&page=0&srch=&">Visualization</a></p>';
	$return .= '<p/>';
	$return .= '<hr>';
	$return .= '<p>';
	$return .= _('Hosted Projects')._(': ');
	if (forge_get_config('use_project_full_list')) {
		$return .= '<a href="softwaremap/full_list.php">';
	}
	$return .= '<strong>'.number_format($gforge->getNumberOfPublicHostedProjects()).'</strong>';
	if (forge_get_config('use_project_full_list')) {
		$return .= '</a>';
	}
	$return .= "</p><p>";
	$return .= _('Registered Users')._(': ').'<strong>'.
		number_format($gforge->getNumberOfActiveUsers()).'</strong>';
	$return .= "</p>\n";
	return $return;
}

function show_newest_projects() {
	$res_newproj = db_query_params ('SELECT group_id,unix_group_name,group_name,register_time FROM groups WHERE status=$1 AND type_id=1 AND register_time > 0 ORDER BY register_time DESC', array ('A'));

	$return = '';

	$count = 0 ;
	while (($row_newproj=db_fetch_array($res_newproj)) && ($count < 10)) {
		if (!forge_check_perm ('project_read', $row_newproj['group_id'])) {
			continue ;
		}

		$count++ ;
		$t_prj_date = date(_('m/d'),$row_newproj['register_time']);
		$t_prj_link = util_make_link_g ($row_newproj['unix_group_name'],$row_newproj['group_id'],$row_newproj['group_name']);

		$return .= "<tr>";
		$return .= '<td class="width-stat-col1">' . $t_prj_date . "</td>";
		$return .= '<td>' . $t_prj_link . '</td>';
		$return .= "</tr>\n";
	}

	if ( $return == "" ) {
		return _('No stats available');
	} else {
		$return = '<table>' . $return . "</table>\n";
	}

	if (forge_get_config('use_project_full_list')) {
		$return .= '<div class="align-center">'.util_make_link ('/softwaremap/full_list.php', _('All newest projects'), array('class' => 'dot-link')).'</div>';
	}
	return $return;
}

function show_highest_ranked_users() {
	//select out the users information to show the top users on the site
	$res = db_query_params('SELECT users.user_name,users.user_id,users.realname,user_metric.metric	FROM user_metric,users WHERE users.user_id=user_metric.user_id AND user_metric.ranking < 11 AND users.status != $1 ORDER BY ranking ASC',
				array ('D'));
	if (!$res) {
		return db_error();
	} else {
		$rows = db_numrows($res);
		if ($rows < 1) {
			return  _('No stats available');
		} else {
			$return = '';
			for ($i=0; $i < $rows; $i++) {
				$return .= ($i+1).' - ('. number_format(db_result($res,$i,'metric'),4) .') '
				. util_make_link_u (db_result($res, $i, 'user_name'), db_result($res, $i, 'user_id'),db_result($res,$i,'realname'))
				.'<br />';
			}
		}
		$return .= '<div class="align-center">'.util_make_link('/top/topusers.php', _('All Users'), array('class' => 'dot-link')).'</div>';
		return $return;
	}
}

function show_highest_ranked_projects() {
	$stats = new Stats();
	$result = $stats->getMostActiveStats ('week', 0) ;
	$return = '' ;

	$count = 1 ;
	while (($row=db_fetch_array($result)) && ($count <= 20)) {
		if (!forge_check_perm ('project_read', $row['group_id'])) {
			continue ;
		}

		$t_prj_link = util_make_link_g ($row['unix_group_name'],$row['group_id'],$row['group_name']);

		$return .= "<tr>";
		$return .= '<td class="width-stat-col1">'. $count . "</td>";
		$return .= '<td>' . $t_prj_link . '</td>';
		$return .= "</tr>\n";

		$count++ ;
	}
	if ( $return == "" ) {
		return _('No stats available');
	} else {
		$return = '<table>' . $return . "</table>\n";
	}

	$return .= '<div class="align-center">' . util_make_link ('/top/mostactive.php?type=week', _('All project activities'), array('class' => 'dot-link')) . '</div>';

	return $return;
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
