<?php
/**
 * Project Admin Users Page
 *
 * Copyright 2004 GForge, LLC
 * Copyright 2006 federicot
 * Copyright © 2011
 *	Thorsten Glaser <t.glaser@tarent.de>
 * Copyright 2011, Roland Mas
 * Copyright 2014, Stéphane-Eymeric Bredthauer
 * Copyright 2014, Franck Villaume - TrivialDev
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
 * All rights reserved.
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
 *-
 * This page contains administrative information for the project as well
 * as allows to manage it. This page should be accessible to all project
 * members, but only admins may perform most functions.
 */

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfwww.'include/role_utils.php';
require_once $gfcommon.'include/account.php';
require_once $gfcommon.'include/GroupJoinRequest.class.php';
require_once $gfwww.'project/admin/usersAdminHandler.php';

// Use the return value of $params, which is populated with titles, urls, and attrs.
$theParams = project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

// Submenu display has already been disabled in Theme.class.php for "Project Management/Members".
// Fill in the parameters here and show the submenu below "main_col" DIV.
// Get titles, urls, and attrs after they have been set in project_admin_header().
$subMenuTitle = $theParams['labels'];
$subMenuUrl = $theParams['links'];
$subMenuAttr = $theParams['attr_r'];

require_once $gfwww.'project/admin/usersAdminUI.php';

?>

