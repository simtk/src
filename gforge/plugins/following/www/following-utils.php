<?php
/**
 * News Facility
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
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

function following_Project_Header($params,$id) {
	global $DOCUMENT_ROOT,$HTML;
	$params['toptab']='Following';
	$params['group']=$id;
        $group_id = $id;

	if ($group_id) {
		$menu_texts=array();
		$menu_links=array();

		$menu_texts[]=_('View Followings');
		$menu_links[]='/plugins/following/?type=group&pluginname=Following&id='.$group_id;
		if (session_loggedin()) {
			$project = group_get_object($params['group']);
			if ($project && is_object($project) && !$project->isError()) {
                                /*
				if (forge_check_perm ('project_admin', $group_id)) {
					$menu_texts[]=_('Administration');
					$menu_links[]='/plugins/following/admin/?group_id='.$group_id;
				}
                                */
			}
		}
		$params['submenu'] = $HTML->subMenu($menu_texts,$menu_links);
	}
	/*
		Show horizontal links
	*/
	site_project_header($params);
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


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
