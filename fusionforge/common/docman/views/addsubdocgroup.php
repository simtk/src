<?php
/**
 * addsubdocgroup.php
 *
 * FusionForge Documentation Manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013, Franck Villaume - TrivialDev
 * Copyright 2016-2022, Tod Hing - SimTK Team
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

if (!forge_check_perm('docman', $group_id, 'approve')) {
	$return_msg= _('Document Manager Access Denied');
	session_redirect('/docman/?group_id='.$group_id.'&warning_msg='.urlencode($return_msg));
}

// plugin projects-hierarchy
$actionurl = '?group_id='.$group_id.'&amp;action=addsubdocgroup&amp;dirid='.$dirid;
if (isset($childgroup_id) && $childgroup_id) {
	$g = group_get_object($childgroup_id);
	$actionurl .= '&amp;childgroup_id='.$childgroup_id;
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
?>
<script type="text/javascript">//<![CDATA[
function doItAddSubGroup() {
	// Check disk usage.
	if (!handlerDiskUsage(<?php echo $group_id; ?>)) {
		// Disable input fields.
		$(".theFieldSet").attr("disabled", "disabled");
	}

	document.getElementById('addsubgroup').submit();
	document.getElementById('submitaddsubgroup').disabled = true;
}
//]]></script>
<?php
echo '<div class="docmanDivIncluded">';
echo '<form id="addsubgroup" name="addsubgroup" method="post" action="'.$actionurl.'">';
echo '<fieldset class="theFieldSet">';
if ($dirid) {
	$folderMessage = _('Name of the document subfolder to create');
	echo $folderMessage._(': ');
} else {
	$folderMessage = _('Name of the document folder to create');
	echo $folderMessage._(': ');
}
echo '<input required="required" type="text" name="groupname" size="40" maxlength="255" placeholder="'.$folderMessage.'" />';

if ($dirid) {
       // $folderMessage = _('Name of the document subfolder to create');
       // echo $folderMessage._(': ');
       // $dgh->showSelectNestedGroups($dfg->getNested(), 'doc_group', false, $dirid);
} else {
	$folderMessage = _('Create as subfolder in');
	echo '<br /><br />'.$folderMessage._(': ');
        $dgh->showSelectNestedGroups($dgf->getNested(), 'doc_group', true, $dirid);
}

echo '<input id="submitaddsubgroup" type="button" value="'. _('Create') .'" onclick="javascript:doItAddSubGroup()" />';
echo '</fieldset>';
echo '</form>';
echo '</div>';
