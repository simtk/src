<?php

/**
 *
 * following-utils.php
 * 
 * Copyright 2005-2019, SimTK Team
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
