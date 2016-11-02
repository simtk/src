<?php
/**
 * listfile.php
 *
 * FusionForge Documentation Manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright (C) 2010 Alcatel-Lucent
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * Copyright 2016, Tod Hing - SimTK Team

 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
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

/* please do not add require here : use www/docman/index.php to add require */
/* global variables used */
global $group_id; // id of the group
global $dirid; // id of doc_group
global $HTML; // Layout object
global $u; // User object
global $g; // the Group object
global $dm; // the docman manager

$linkmenu = 'listfile';
$baseredirecturl = '/docman/?group_id='.$group_id;
$redirecturl = $baseredirecturl.'&view='.$linkmenu.'&dirid='.$dirid;
$actionlistfileurl = '?group_id='.$group_id.'&amp;view='.$linkmenu.'&amp;dirid='.$dirid;
/*
if (!forge_check_perm('docman', $group_id, 'read')) {
	$return_msg= _('Document Manager Access Denied');
	session_redirect($baseredirecturl.'&warning_msg='.urlencode($return_msg));
}
*/

echo '<div id="leftdiv">';
include ($gfcommon.'docman/views/tree.php');
echo '</div>';


include ($gfcommon.'docman/views/help.php');

