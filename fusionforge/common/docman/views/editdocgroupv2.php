<?php

/**
 * FusionForge Documentation Manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013, Franck Villaume - TrivialDev
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
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
global $g; //group object
global $group_id; // id of the group
global $dirid; // id of doc_group
global $dgf; // document directory factory of this group
global $dgh; // document directory html

//echo "dirid: " . $dirid . "<br />";

if (!forge_check_perm('docman', $group_id, 'approve')) {
	$return_msg= _('Document Manager Access Denied');
	//session_redirect('/docman/?group_id='.$group_id.'&warning_msg='.urlencode($return_msg));
	echo "<script type='text/javascript'>window.top.location='" .
		"/account/login.php?triggered=1&return_to=" .
		urlencode('/docman/?group_id=' . $group_id . '&view=listfile') .
		"';</script>";
	exit;
}

$dgf = new DocumentGroupFactory($g);
$dgh = new DocumentGroupHTML($g);
$dgf_groups = $dgf->getDocGroups();

// plugin projects-hierarchy
$actionurl = '?group_id='.$group_id.'&amp;action=editdocgroup';
if ($childgroup_id) {
	$g = group_get_object($childgroup_id);
	$actionurl = '?group_id='.$group_id.'&amp;action=editdocgroup&amp;childgroup_id='.$childgroup_id;
}

$dg = new DocumentGroup($g, $dirid);
if ($dg->isError()) {
	//session_redirect('/docman/?group_id='.$group_id.'&error_msg='.urlencode($dg->getErrorMessage()));
	echo "<script type='text/javascript'>window.top.location='" .
		'/docman/?group_id=' . $group_id . '&view=listfile' . 
		'&error_msg=' . urlencode($dg->getErrorMessage()) .
		"';</script>";
	exit;
}

?>
<div class="docmanDivIncluded">
	<form name="editgroup" action="<?php echo $actionurl; ?>" method="post">
		<input type="hidden" name="dirid" value="<?php echo $dirid; ?>" />
		<table>
			<tr>
				<td><?php echo _('Folder Name') ?></td>
				<td><input required="required" type="text" size="50" name="groupname" value="<?php echo $dg->getName(); ?>" /></td>
				
			</tr>
			<tr>
				<td><?php echo _('Parent Folder') ?></td>
				<td>
				<?php
				/*
				echo '			<select name="parent_dirid" id="parent_dirid">';
				echo '<option value="0">None</option>';
                while($row = db_fetch_array($dgf_groups)) {
				   //echo "<pre>";
				   //var_dump();
				   //echo "</pre>";
				   if ($row['doc_group'] != $dirid && (!in_array($row['doc_group'],$dg->getSubgroup($dirid)))) {
                      echo '<option value="'.$row['doc_group'].'"';
				      if ($row['doc_group'] == $dg->getParentID()) { echo "selected";}
				         echo '>'.$row['groupname']."</option>";
				   }
                }
				
                echo '			</select>';
				*/
				$dgh->showSelectNestedGroups($dgf->getNested(), 'parent_dirid', true, $dg->getParentId(), array($dg->getID()));
                ?>
				</td>
			</tr>
		</table>
		<br />
		<?php echo '<input type="submit" name="submit" class="btn-cta" value="'._('Update').'" />'; ?>
	</form>
</div>
