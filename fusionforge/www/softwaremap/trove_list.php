<?php
/**
 * FusionForge Trove Software Map
 *
 * Copyright 1999-2001, VA Linux Systems, Inc.
 * Copyright 2009, Roland Mas
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright 2013-2014, Franck Villaume - TrivialDev
 * Copyright 2005-2025, SimTK Team
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/trove.php';

if (!forge_get_config('use_trove')) {
	exit_disabled('home');
}

global $HTML;

// Allow alternate content-type rendering by hook
$default_content_type = 'text/html';

$script = 'trove_list';
$content_type = util_negociate_alternate_content_types($script, $default_content_type);

if($content_type != $default_content_type) {
	$hook_params = array();
	$hook_params['accept'] = $content_type;
	$hook_params['return'] = '';
	$hook_params['content_type'] = '';
	plugin_hook_by_reference('content_negociated_trove_list', $hook_params);
	if($hook_params['content_type'] != ''){
		header('Content-type: '. $hook_params['content_type']);
		echo $hook_params['content'];
	}
	else {
		header('HTTP/1.1 406 Not Acceptable',true,406);
	}
	exit(0);
}

$HTML->header(array('title'=>_('Software Map'),'pagename'=>'softwaremap'));
$HTML->printSoftwareMapLinks();

// assign default. 18 is 'topic'
$form_cat = getIntFromRequest('form_cat', forge_get_config('default_trove_cat'));

// default first page.
$page = getIntFromRequest('page', 1);

// 'c' for by categories
$cat = getStringFromRequest('cat', 'c');


// get info about current folder
$res_trove_cat = db_query_params('
	SELECT *
	FROM trove_cat
	WHERE trove_cat_id=$1 ORDER BY fullname',
			array($form_cat));

if (db_numrows($res_trove_cat) < 1) {
	exit_error(_('That Trove category does not exist')._(': ').db_error(),'trove');
}

echo html_ao('div', array('id' => 'project-tree', 'class' => 'underline-link'));
echo html_e('h2', array(), _('Project Tree'));

plugin_hook('display_hierarchy_submenu');

if ( $cat === 'c' ) {
	$row_trove_cat = db_fetch_array($res_trove_cat);

	// #####################################
	// this section limits search and requeries if there are discrim elements

	$discrim = getStringFromRequest('discrim');
	$discrim_url = '';
	$discrim_desc = '';

	$qpa_alias = db_construct_qpa();
	$qpa_and = db_construct_qpa();

	if ($discrim) {
		$discrim_url_b = array();

		// commas are ANDs
		$expl_discrim = explode(',', $discrim);

		if (sizeof($expl_discrim) > 6) {
			array_splice($expl_discrim, 6);
		}

		// one per argument
		for ($i = 0; $i < sizeof($expl_discrim); $i++) {
			// make sure these are all ints, no url trickery
			$expl_discrim[$i] = intval($expl_discrim[$i]);

			// need one aliased table for everything
			$qpa_alias = db_construct_qpa($qpa_alias,', trove_agg trove_agg_'.$i);

			// need additional AND entries for aliased tables
			$qpa_and = db_construct_qpa($qpa_and,
					     sprintf(' AND trove_agg_%d.trove_cat_id=$%d AND trove_agg_%d.group_id=trove_agg.group_id ', $i, $i+1, $i),
					     array($expl_discrim[$i]));

			$expl_discrim_b = array();
			for ($j = 0; $j < sizeof($expl_discrim); $j++) {
				if ($i != $j) {
					$expl_discrim_b[] = $expl_discrim[$j];
				}
			}
			$discrim_url_b[$i] = '&discrim=' . implode(',', $expl_discrim_b);

		}
		$discrim_url = '&discrim=' . implode(',', $expl_discrim);

		// build text for top of page on what viewier is seeing
		$discrim_desc = _('Now limiting view to projects in the following categories:');

		for ($i = 0; $i < sizeof($expl_discrim); $i++) {
			$discrim_desc .= '<br /> &nbsp; &nbsp; &nbsp; '
				.trove_getfullpath($expl_discrim[$i])
				.util_make_link('/softwaremap/trove_list.php?cat=c&form_cat='.$form_cat .$discrim_url_b[$i],' ['._('Remove This Filter').']');
		}
		$discrim_desc .= "<hr />\n";
	}

	// #######################################

	if (!empty($discrim_desc))
		echo html_e('p', array(), $discrim_desc);

	// ######## two column table for key on right
	// first print all parent cats and current cat (breadcrumb)
	echo $HTML->listTableTop();
	print '<tr class="top">' . "\n";
	print '<td id="project-tree-col1">' . "\n";

	$folders = explode(" :: ",$row_trove_cat['fullpath']);
	$folders_ids = explode(" :: ",$row_trove_cat['fullpath_ids']);
	$folders_len = count($folders);

	print "<p>";
	print html_image("category.png",'32','33',array('alt'=>""));
	print "&nbsp;";

	for ($i = 0; $i < $folders_len; $i++) {
		if (!isset($folders_ids[$i]) || !isset($folders[$i])) {
			continue;
		}
		// no anchor for current cat
		if ($folders_ids[$i] != $form_cat) {
			print util_make_link('/softwaremap/trove_list.php?cat=c&form_cat=' .$folders_ids[$i].$discrim_url,
					      $folders[$i]
				);
			print "&nbsp; &gt; &nbsp;";
		} else {
			print '<strong>'.$folders[$i].'</strong>';
		}
	}
	print "</p>";

	// print subcategories
	$res_sub = db_query_params('
		SELECT trove_cat.trove_cat_id AS trove_cat_id,
			trove_cat.fullname AS fullname,
			trove_treesums.subprojects AS subprojects
		FROM trove_cat LEFT JOIN trove_treesums USING (trove_cat_id)
		WHERE (
			trove_treesums.limit_1=0
			OR trove_treesums.limit_1 IS NULL
		) AND trove_cat.parent=$1
		ORDER BY fullname',
			array($form_cat));
	echo db_error();

	$elementsLi = array();
	while ($row_sub = db_fetch_array($res_sub)) {
		$realprojects = ($row_sub['subprojects']) ? $row_sub['subprojects'] : 0;
		$plural = ($row_sub['subprojects'] > 1) ? $row_sub['subprojects'] : 0;
		$content = util_make_link('/softwaremap/trove_list.php?cat=c&form_cat='.$row_sub['trove_cat_id'].$discrim_url, $row_sub['fullname']);
		$content .= '&nbsp;'.html_e('em', array(), '('.sprintf(ngettext('%s project', '%s projects', $plural), $realprojects).')');
		$elementsLi[] = array('content' => $content);
	}
	echo $HTML->html_list($elementsLi);

	// ########### right column: root level
	print "</td>\n";
	print '<td id="project-tree-col2">';
	// here we print list of root level categories, and use open folder for current
	$res_rootcat = db_query_params('
		SELECT trove_cat_id,fullname
	FROM trove_cat
	WHERE parent=0
	AND trove_cat_id!=0
	ORDER BY fullname',
			array ());
	echo db_error();

	echo html_e('p', array(), _('Browse By')._(':'));

	$elementsLi = array();
	while ($row_rootcat = db_fetch_array($res_rootcat)) {
		// print open folder if current, otherwise closed
		// also make anchor if not current
		if (($row_rootcat['trove_cat_id'] == $row_trove_cat['root_parent'])
			|| ($row_rootcat['trove_cat_id'] == $row_trove_cat['trove_cat_id'])) {
			$elementsLi[] = array('content' => $row_rootcat['fullname'], 'attrs' => array('class' => 'current-cat'));
		} else {
			$elementsLi[] = array('content' => util_make_link('/softwaremap/trove_list.php?cat=c&form_cat=' .$row_rootcat['trove_cat_id'].$discrim_url, $row_rootcat['fullname']));
		}
	}
	echo $HTML->html_list($elementsLi, array('id' => 'project-tree-branches'));
	print "</td>\n</tr>\n";
	echo $HTML->listTableBottom();
	echo html_e('hr');

	// one listing for each project
	$qpa = db_construct_qpa();
	$qpa = db_construct_qpa($qpa, 'SELECT * FROM trove_agg');
	$qpa = db_join_qpa($qpa, $qpa_alias);
	$qpa = db_construct_qpa($qpa, ' WHERE trove_agg.trove_cat_id=$1', array($form_cat));
	$qpa = db_join_qpa($qpa, $qpa_and);
	$qpa = db_construct_qpa($qpa, ' ORDER BY trove_agg.trove_cat_id ASC, trove_agg.ranking ASC');
	$res_grp = db_query_qpa($qpa, $TROVE_HARDQUERYLIMIT, 0, 'SYS_DB_TROVE');

	$projects = array();
	while ($row_grp = db_fetch_array($res_grp)) {
		if (!forge_check_perm ('project_read', $row_grp['group_id'])) {
			continue ;
		}
		$projects[] = $row_grp;
	}
	$querytotalcount = count($projects);

	// #################################################################
	// limit/offset display

	// store this as a var so it can be printed later as well
	$html_limit = '';
	if ($querytotalcount == $TROVE_HARDQUERYLIMIT) {
		$html_limit .= sprintf(_('More than <strong>%d</strong> projects in result set.'), $querytotalcount);

	}
	$html_limit .= sprintf(ngettext('<strong>%d</strong> project in result set.', '<strong>%d</strong> projects in result set.', $querytotalcount), $querytotalcount);
	if ($querytotalcount > $TROVE_BROWSELIMIT) {
		$html_limit .= html_trove_limit_navigation_box($_SERVER['PHP_SELF'].'?cat=c&form_cat='.$form_cat, $querytotalcount, $TROVE_BROWSELIMIT, $page, sprintf(_('Displaying %1$s per page. Projects sorted by activity ranking.'), $TROVE_BROWSELIMIT));
	}
	echo $html_limit.html_e('hr');

	// #################################################################
	// print actual project listings
	for ($i_proj=0;$i_proj<$querytotalcount;$i_proj++) {
		$row_grp = $projects[$i_proj];

		// check to see if row is in page range
		if (($i_proj >= (($page-1)*$TROVE_BROWSELIMIT)) && ($i_proj < ($page*$TROVE_BROWSELIMIT))) {
			$viewthisrow = 1;
		} else {
			$viewthisrow = 0;
		}

		if ($row_grp && $viewthisrow) {
			print '<table class="fullwidth"><tr class="top"><td colspan="2">';
			print util_make_link_g ($row_grp['unix_group_name'],
						$row_grp['group_id'],
						"<strong>".htmlspecialchars($row_grp['group_name'])."</strong> ");
			if ($row_grp['short_description']) {
				print "- " . htmlspecialchars($row_grp['short_description']);
			}

			print '<br />&nbsp;';
			// extra description
			print "</td></tr>\n<tr class=\"top\"><td>";
			// list all trove categories
			print trove_getcatlisting($row_grp['group_id'],1,0,1);
			print "</td>\n";
			print '<td class="align-right">'; // now the right side of the display
			if (group_get_object($row_grp['group_id'])->usesStats()) {
				print _('Activity Percentile')._(': ').'<strong>'. number_format($row_grp['percentile'],2) .'</strong>';
				print '<br />';
				sprintf(_('Activity Ranking: <strong>%d</strong>'), number_format($row_grp['ranking'],2));
			}
			print '<br />'._('Registered'). _(': ') . '<strong>'.date(_('Y-m-d H:i'),$row_grp['register_time']).'</strong>';
			print "</td></tr></table>\n<hr />\n";
		} // end if for row and range chacking
	}

	// print bottom navigation if there are more projects to display
	if ($querytotalcount > $TROVE_BROWSELIMIT) {
		print $html_limit;
	}
} elseif( $cat === 'h') {
	plugin_hook('display_hierarchy');
}

// print '<p><FONT size="-1">This listing was produced by the following query: '
//	.$query_projlist.'</FONT>';
echo html_ac(html_ap() -1);

$HTML->footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
