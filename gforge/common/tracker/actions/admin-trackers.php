<?php
/**
 * FusionForge Tracker Listing
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright 2010, FusionForge Team
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright 2012-2014, Franck Villaume - TrivialDev
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

require_once $gfcommon.'include/roleUtils.php';

global $group;

//
//	Display existing artifact types
//
$atf = new ArtifactTypeFactoryHtml($group);
if (!$atf || !is_object($atf) || $atf->isError()) {
	exit_error(_('Could Not Get ArtifactTypeFactory'),'tracker');
}

// Only keep the Artifacts where the user has admin rights.
$arr = $atf->getArtifactTypes();
$i=0;
for ($j = 0; $j < count($arr); $j++) {
	if (forge_check_perm ('tracker', $arr[$j]->getID(), 'manager')) {
		$at_arr[$i++] =& $arr[$j];
	}
}
// If no more tracker now,
if ($i==0 && $j>0) {
	exit_permission_denied('','tracker');
}

//required params for site_project_header();
$params['group']=$group_id;
$params['toptab']='tracker';
if(isset($page_title)){
	$params['title'] = $page_title;
} else {
	$params['title'] = '';
}

$atf->header( array('title' => _('Trackers Administration')));

if (!isset($at_arr) || !$at_arr || count($at_arr) < 1) {
	echo '<p class="warning">'._('No trackers found').'</p>';
} else {

	echo '
	<p>'._('Choose a data type and you can set up prefs, categories, groups, users, and permissions').'.</p>';

	/*
		Put the result set (list of forums for this group) into a column with folders
	*/
	$tablearr=array(_('Tracker'),_('Description'));
	echo $HTML->listTableTop($tablearr);

	for ($j = 0; $j < count($at_arr); $j++) {
		echo '
		<tr '. $HTML->boxGetAltRowStyle($j) . '>
			<td><a href="'.util_make_url ('/tracker/admin/?atid='. $at_arr[$j]->getID() . '&amp;group_id='.$group_id).'">' .
				html_image("ic/tracker20w.png","20","20") . ' &nbsp;'.
				$at_arr[$j]->getName() .'</a>
			</td>
			<td>'.$at_arr[$j]->getDescription() .'
			</td>
		</tr>';
	}
	echo $HTML->listTableBottom();

	$roadmap_factory = new RoadmapFactory($group);
	$roadmaps = $roadmap_factory->getRoadmaps(true);
	if (!empty($roadmaps)) {
		echo '	<p id="roadmapadminlink">
			<a href="'.util_make_url('/tracker/admin/?group_id='.$group_id.'&admin_roadmap=1').'" >'._('Manage your roadmaps.').'</a>
			</p>';
	}
}

$atf->footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
