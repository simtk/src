<?php
/**
 * FusionForge Documentation Manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013, Franck Villaume - TrivialDev
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
global $g; // group object
global $group_id; // id of the group
global $dirid; // id of the doc_group


if (isset($childgroup_id) && $childgroup_id) {
	$g = group_get_object($childgroup_id);
}

$dgf = new DocumentGroupFactory($g);
if ($dgf->isError()) {
   echo "error dgf";
   exit_error($dgf->getErrorMessage(), 'docman');
}

$dgh = new DocumentGroupHTML($g);
if ($dgh->isError()) {
   echo "error dgh";
   exit_error($dgh->getErrorMessage(), 'docman');
}


if ($dirid) {
       // $folderMessage = _('Name of the document subfolder to create');
       // echo $folderMessage._(': ');
       // $dgh->showSelectNestedGroups($dfg->getNested(), 'doc_group', false, $dirid);
} else {
	$folderMessage = _('Create as subfolder in');
	echo '<br /><br />'.$folderMessage._(': ');
        $dgh->showSelectNestedGroups($dgf->getNested(), 'doc_group', true, $dirid);
}

