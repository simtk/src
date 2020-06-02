<?php

/**
 *
 * rolesUI.php
 * 
 * File for setting up roles.
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
 
echo '<table width="100%"><thead><tr>';
echo '<th>'._('Role Name').'</th>';
echo '<th style="text-align:right">'._('Action').'</th>';
echo '</tr></thead><tbody>';

$roles = $group->getRoles() ;
sortRoleList($roles, $group, 'composite') ;

foreach ($roles as $theRole) {

	// Check whether logged-in user is 'forge_admin'.
	if (!session_loggedin() || !forge_check_global_perm('forge_admin')) {
		if ($theRole->getDisplayableName($group) == "Anonymous/not logged in (global role)") {
			// Skip Anoymous/not logged in role permission edit.
			continue;
		}
	}

	echo '<tr><td colspan="2">
	<form action="/project/admin/roleedit.php?group_id='. $group_id .'" method="post">
	<div style="float:left;">
		'.$theRole->getDisplayableName($group).'
	</div><div style="float:right;padding:2px;">
		<input type="hidden" name="role_id" value="'.$theRole->getID().'" />
		<input style="width:160px;" type="submit" name="edit" ';
	if ($theRole->getDisplayableName($group) == "Admin") {
		echo 'value="View Permissions" class="btn-blue" />';
	}
	else {
		echo 'value="Edit Permissions" class="btn-blue" />';
	}
	echo '</div>
	</form>';

	if ($theRole->getHomeProject() != NULL && $theRole->getHomeProject()->getId() == $group_id) {
		echo '<form action="/project/admin/roledelete.php?group_id='. $group_id .'" method="post">
        <div style="float:right;padding:2px;">
		<input type="hidden" name="role_id" value="'.$theRole->getID().'" />
		<input type="submit" name="delete" ';
	if ($theRole->getDisplayableName($group) == "Admin") {
		echo 'disabled ';
	}
	echo 'value="'._("Delete role").'" class="btn-blue" />
	</div>
	</form>';
	}

	echo '</td></tr>';
}

echo '<tr><td colspan="2">';
echo '<form action="/project/admin/roleedit.php?group_id='. $group_id .'" method="post">
	<div style="float:left;">
		<input type="text" name="role_name" size="10" value="" required="required" />
	</div><div style="float:right;padding:2px;">
		<input type="submit" name="add" value="' . _("Create Role") . '" class="btn-blue" />
	</div>';
echo '</form>';
echo '</td></tr>';


// Check whether logged-in user is 'forge_admin'.
if (session_loggedin() && forge_check_global_perm('forge_admin')) {
	if (count($used_external_roles)) {
		echo '<tr><td colspan="100%">&nbsp;</td></tr>';
		echo '<tr><th colspan="100%">Currently used external roles</th></tr>';
		foreach ($used_external_roles as $theRole) {
			echo '<tr><td colspan="2">
			<form action="'.getStringFromServer('PHP_SELF').'" method="post">
				<input type="hidden" name="submitConfirm" value="y" />
				<input type="hidden" name="role_id" value="'.$theRole->getID().'" />
				<input type="hidden" name="group_id" value="'.$group_id.'" />
				<div style="float:left;">' . $theRole->getDisplayableName($group) . 
				'</div><div style="float:right;padding:2px;">
					<input type="submit" name="unlinkrole" value="' . _("Unlink Role") . 
					'" class="btn-blue" />
				</div>
			</form>
			</td></tr>';
		}
	}

	if (count($unused_external_roles)) {
		echo '<tr><td colspan="100%">&nbsp;</td></tr>';
		echo '<tr><th colspan="100%">Available external roles</th></tr>';

		$ids = array();
		$names = array();
		foreach ($unused_external_roles as $theRole) {
			$ids[] = $theRole->getID();
			$names[] = $theRole->getDisplayableName($group);
		}
		echo '<tr><td colspan="2">
		<form action="'.getStringFromServer('PHP_SELF').'" method="post">
			<input type="hidden" name="submitConfirm" value="y" />
			<input type="hidden" name="group_id" value="'.$group_id.'" />
			<div style="float:left;">';
			echo html_build_select_box_from_arrays($ids,$names,'role_id','',false,'',false,'');
			echo '</div><div style="float:right;padding:2px;">
				<input type="submit" name="linkrole" value="' . _("Link external role") .
					'" class="btn-blue" />
			</div>
		</form>
		</td></tr>';
	}
}

echo $HTML->boxBottom();
?>
</td>
</tr>

</table>

