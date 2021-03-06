<?php
/**
 * Tracker Front Page
 *
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012,2014, Franck Villaume - TrivialDev
 * Copyright 2016-2021, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org/
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

global $group;
global $HTML;

$atf = new ArtifactTypeFactoryHtml($group);
if (!$atf || !is_object($atf) || $atf->isError()) {
	exit_error(_('Could Not Get ArtifactTypeFactory'),'tracker');
}

$at_arr = $atf->getArtifactTypes();
if ($at_arr === false) {
	exit_permission_denied('tracker');
}

use_javascript('/js/sortable.js');

$atf->header();

if (!$at_arr || count($at_arr) < 1) {
	echo $HTML->information(_('No trackers have been set up, or you cannot view them.'));
	echo '<p>';
	echo sprintf(_('The Admin for this project will have to set up data types using the %1$s admin page %2$s'), '<a href="'.util_make_url ('/tracker/admin/?group_id='.$group->getID()).'">', '</a>');
	echo "</p>";
} else {
	plugin_hook ("blocks", "tracker index");
	echo '<p>'._('Choose a tracker and you can browse/edit/add items to it.').'</p>';
	/*
		Put the result set (list of trackers for this group) into a column with folders
	*/
	$tablearr = array(_('Tracker'),_('Description'),_('Open'),_('Total'));
	echo $HTML->listTableTop($tablearr, false, 'full sortable_table_tracker', 'sortable_table_tracker');

	for ($j = 0; $j < count($at_arr); $j++) {
		if (!is_object($at_arr[$j])) {
			//just skip it
		} elseif ($at_arr[$j]->isError()) {
			echo $at_arr[$j]->getErrorMessage();
		} else {
			if ($at_arr[$j]->getName() == "") {
				// Data not available or access not permitted. Skip.
				continue;
			}

			$atid = $at_arr[$j]->getID();
			$ath = new ArtifactTypeHtml($group, $atid);
			// Check if tracker access is allowed.
			if ($ath && is_object($ath) && !$ath->isError() && !$ath->isPermitted()) {
				// Access not permitted.
				continue;
			}

			$cells = array();
			$cells[][] = util_make_link('/tracker/?atid='.$at_arr[$j]->getID().'&group_id='.$group->getID().'&func=browse',
							html_image("ic/tracker20w.png","20","20").' '.$at_arr[$j]->getName());
			$cells[][] = $at_arr[$j]->getDescription();
			$cells[] = array((int) $at_arr[$j]->getOpenCount(), 'class' => 'align-center');
			$cells[] = array((int) $at_arr[$j]->getTotalCount(), 'class' => 'align-center');
			echo $HTML->multiTableRow(array('class' => $HTML->boxGetAltRowStyle($j, true)), $cells);
		}
	}
	echo $HTML->listTableBottom();
}
$atf->footer();

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
