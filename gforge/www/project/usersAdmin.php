<?php

/**
 *
 * usersAdmin.php
 * 
 * File to handle administrative users configuration.
 *
 * Copyright 2005-2016, SimTK Team
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfwww.'include/role_utils.php';
require_once $gfcommon.'include/account.php';
require_once $gfcommon.'include/GroupJoinRequest.class.php';
require_once $gfwww.'project/admin/usersAdminHandler.php';

site_project_header(array('title'=>_('Project Member List'),'group'=>$group_id,'toptab'=>'memberlist'));

// Create submenu under "main_cols" DIV, such that it does not
// occupy the whole width of the page (rather than using the
// submenu population in Theme.class.php)
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuAttr = array();
$subMenuTitle["Title"] = "Team";
$subMenuTitle[] = 'View Members';
$subMenuUrl[] = '/project/memberlist.php?group_id=' . $group_id;
if (session_loggedin()) {
	if ($group && is_object($group) && !$group->isError()) {
		if (forge_check_perm ('project_admin', $group_id, 'write')) {
			// Check permission before adding administrative menu items.
			$subMenuTitle[] = _('Administration');
			$subMenuUrl[] = '/project/usersAdmin.php?group_id=' . $group_id;
		}
	}
}

require_once $gfwww.'project/admin/usersAdminUI.php';

?>
