<?php
/**
 *
 * publications plugin publications-utils.php
 *
 * Utility page for display header information for pubs
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

function publications_Project_Header($params,$group_id) {
	global $DOCUMENT_ROOT,$HTML;

	$params['titleurl']='/plugins/publications/?pluginname=publications&id='.$group_id;
	$params['toptab']='publications';
    $params['group']=$group_id;
	
	if ($group_id) {
		$menu_texts=array();
		$menu_links=array();

		$menu_texts[]=_('View Publications');
		$menu_links[]='/plugins/publications/?pluginname=publications&id='.$group_id;
		
		if (session_loggedin()) {
		      
			    if ($setting1 = forge_check_perm ('pubs', $group_id, 'project_admin') || $setting2 = forge_check_perm ('project_read', $group_id)) {
				//if (forge_check_perm ('project_read', $group_id)) {
					$menu_texts[]=_('Add Publications');
					$menu_links[]='/plugins/publications/admin/add.php?group_id=' . $group_id;
				}
				
				if ($setting = forge_check_perm ('pubs', $group_id, 'project_admin')) {
				    $menu_texts[]=_('Administration');
					$menu_links[]='/plugins/publications/admin/?group_id='.$group_id;
				}
				
		}
		
		/*
		Show horizontal links
	    */
		//site_project_header($params);
		
		//site_project_header(array('title'=>$params['title'], 'group'=>$group_id, 'toptab'=>'publications', 'titleurl'=>$titleurl));
        site_project_header($params);

		echo $HTML->beginSubMenu();
		echo $HTML->printSubMenu($menu_texts, $menu_links, array());
		echo $HTML->endSubMenu();

		
		
		
	}
	
	
	
}

/*
function news_footer($params) {
	GLOBAL $HTML;
	$HTML->footer($params);
}
*/


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
