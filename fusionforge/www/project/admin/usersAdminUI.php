<?php

/**
 *
 * usersAdminUI.php
 * 
 * File to handle user administration UI.
 *
 * Copyright 2005-2020, SimTK Team
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
 
require_once $gfwww.'project/project_utils.php';

?>


<script type="text/javascript" src="/themes/simtk/js/simple-expand.js"></script>
<script type="text/javascript">
        $(function() {
                $('.expander').simpleexpand();

		// Remove redundant submenu, if present.
		$('.maindiv>.submenu').remove();
		// Update selection of the "Member" administration.
		$('.dropdown>.btnDropdown').html('Members<span class="arrow_icon"><span>');

		// Tooltip for role selection
		$('.setrole>select').attr("title", "Click to select and change role");

		// Role selection change.
		$('.setrole>select').change(function() {
			// Submit the selected role.
			this.form.submit();
		});

		// "Show in sidebar" checkbox change.
		$('td>.checkboxShow').change(function() {
			this.form.submit();
		});

		// "Show in sidebar" order selection change.
		$('td>.order>select').change(function() {
			this.form.submit();
		});

		// Find number of "Admin" roles.
		var cntAdmin = 0;
		$(".setrole>select option:selected").each(function() {
			if ($(this).text() == "Admin") {
				cntAdmin++;
			}
		});
		if (cntAdmin <= 1) {
			// Only 1 user with "Admin" role left.
			$(".setrole>select option:selected").each(function() {
				// Look up included classnames for unix_name of this user.
				// NOTE: This name is stored in its grandparent component:
				// DIV that has the class "setrole".
				if ($(this).text() == "Admin") {
					var theClassName = $(this).parent().parent().attr("class");
					// Remove the "setrole" classname.
					var theUnixName = theClassName.replace("setrole", "").trim();
					// Hide the user removal button by referencing with
					// the username as classname.
					$("input." + theUnixName).each(function() {
						$(this).hide();
					});
					// Show leading space before username.
					$(".leadSpace_" + theUnixName).show();
					// Disable associated role selection.
					$(this).parent().attr("disabled", "disabled");
					$(this).parent().attr("title", "");
					$(this).parent().hide();
					$(this).parent().parent().append("<span><b>Admin</b></span><span class='required_note'>&nbsp;(At least one Admin is required)&nbsp;</span>");
				}
			});
		}
        });
</script>

<div class="project_overview_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">

<?php

// Show the submenu under the "main_col" DIV.
// Note: Otherwise, the sidebar would occur below the submenu area rather than on the same row..
if (isset($subMenuTitle) && isset($subMenuUrl) && isset($subMenuAttr)) {
	echo $HTML->beginSubMenu();
	echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, $subMenuAttr);
	echo $HTML->endSubMenu();
}

?>

<table style="max-width:645px;" width="100%" cellpadding="2" cellspacing="2">
<tr valign="top">
	<td width="50%">
<?php
		// Pending requests
		$reqs =& get_group_join_requests($group);
		if (count($reqs) > 0) {
			echo $HTML->boxTop(_("Pending Membership Requests"));
			for ($i=0; $i<count($reqs); $i++) {
				$user =& user_get_object($reqs[$i]->getUserId());
				if (!$user || !is_object($user)) {
					echo "Invalid User";
				}
?>
		<form action="<?php echo getStringFromServer('PHP_SELF') . 
			'?group_id=' . 
			$group_id; ?>" method="post">
		<input type="hidden" name="submitConfirm" value="y" />
		<input type="hidden" name="form_userid" value="<?php echo $user->getId(); ?>" />
		<input type="hidden" name="form_unix_name" value="<?php 
			echo $user->getUnixName(); ?>" />
		<table width="100%">
			<tr>
				<td style="white-space: nowrap;"><a href="/users/<?php 
					echo $user->getUnixName(); ?>"><?php 
					echo $user->getRealName(); ?></a>
				</td>
				<td style="white-space: nowrap; text-align: right;">
					<?php echo role_box($group_id,'role_id'); ?>
					<input type="submit" name="acceptpending" value="<?php 
						echo _("Accept") ?>" />
					<input type="submit" name="rejectpending" value="<?php 
						echo _("Reject") ?>" />
				</td>
			</tr>
		</table>
		</form>
<?php
			} // for
		} // if

		if (isset($html_code['add_user'])) {
			echo $html_code['add_user'];
		}
		else {

?>
		<div class="download_package" style="background-image:none;margin-bottom:10px;max-width:645px;">
			<div class="nomobile">
				<div id="panel1.1">
					<h2><a style="color:#f75236;font-size:29px;" id="expander" class="expander toggle collapsed" href="#">Edit/Define Roles</a></h2>
					<div id='theRoles' class='content' style="display: block;">
<?php
						// Include Roles UI construction PHP file.. 
						require_once $gfwww.'project/admin/rolesUI.php';
?>
					</div>
				</div>
			</div>
		</div>

<?php

		echo "<div style='color:#f75236;font-size:29px;'>" . $HTML->boxMiddle(_("Add Members")) . "</div>";
?>
		<form action="<?php echo getStringFromServer('PHP_SELF') . 
			'?group_id=' . 
			$group_id; ?>" method="post">
		<div style="float:right;">
			<input type="submit" name="adduser" value="<?php 
				echo _("Add member") ?>" class="btn-blue" />
		</div>
		<table>
		<tr>
			<input type="hidden" name="submitConfirm" value="y" />
			<td>
				<div style="float:left;"><input type="text" name="form_unix_name" value="" required="required" /></div>
			</td>
			<td>
				<?php echo role_box($group_id,'role_id'); ?>
			</td>
		</tr>
		</table>
		</form>
		<div style="clear:both;"/>
		<div style="float:right;padding:2px;">
			<a class="btn-blue" href="/project/admin/massadd.php?group_id=<?php 
				echo $group_id; ?>"><?php 
				echo _("Add users from list"); ?></a>
		</div>
		<div style="clear:both;"/>

		</div>

<?php
		}

		echo "<div style='color:#f75236;font-size:29px;'>" . 
			$HTML->boxMiddle(_("Current Project Members")) . 
			"</div>";

		// Show the members of this project

		$members = $group->getUsersWithId() ;

		echo '<table width="100%"><thead><tr>';
		echo '<th>'._('User Name').'</th>';
		echo '<th style="text-align:right">'._('Role').'</th>';
		echo '</tr></thead><tbody>';

		// Get display order of users.
		$arrUsers = getUserDisplayOrder($group_id);

		$i = 1;
		foreach ($arrUsers as $id=>$theOrder) {
			if (!isset($members[$id])) {
				continue;
			}
			$user = $members[$id];
			genUserEntry($HTML, $group, $group_id, $user, $i++, $arrUsers);
		}
		foreach ($members as $id=>$user) {
			if (array_key_exists($id, $arrUsers)) {
				// Skip. This users has been shown already.
				continue;
			}
			genUserEntry($HTML, $group, $group_id, $user, $i++, $arrUsers);
		}

		echo '</tbody></table>';

		echo $HTML->boxBottom();
?>
		</td>
	</tr>
</table>
</div>
<?php
		constructSideBar($group);
?>

</div>
</div>

<?php

project_admin_footer(array());


// Construct display order "select" and its menu items.
function display_order_box($name, $selected=1, $numMembers=10) {

	$ids = array();
	$names = array();

	for ($cnt = 1; $cnt <= $numMembers; $cnt++) {
		$ids[] = $cnt;
		$names[] = $cnt;
	}

	return html_build_select_box_from_arrays($ids, 	$names, 
		$name, $selected, false, '', false);
}

// Construct side bar
function constructSideBar($groupObj) {
	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	echo '</div>';
}

// Generate user UI entry.
function genUserEntry($HTML, $group, $group_id, $user, $cnt, $arrUsers) {

	$roles = array () ;
	foreach (RBACEngine::getInstance()->getAvailableRolesForUser ($user) as $role) {
		if ($role->getHomeProject() && 
			$role->getHomeProject()->getID() == $group->getID()) {
			$roles[] = $role ;
		}
	}

	sortRoleList ($roles) ;

	$seen = false ;
	foreach ($roles as $role) {
		echo '<tr '. $HTML->boxGetAltRowStyle($cnt) . '>' ;
		if (!$seen) {
			$displayName = $user->getRealName();
			if (empty($displayName)) {
				$displayName = $user->getUnixName();
			}

			// rowspan: selected roles + role selection + update display order.
			echo '<td style="white-space: nowrap;" rowspan="3">';

			echo '<form action="'.getStringFromServer('PHP_SELF').'" method="post">';
			echo '<input type="hidden" name="submitConfirm" value="y" />';
			echo '<input type="hidden" name="user_id" value="'.$user->getID().'" />';
			echo '<input type="hidden" name="group_id" value="'. $group_id .'" />';
			echo '<input type="hidden" name="role_id" value="'.$role->getID().'" />';
			// Add the user_name to this "rmrole" input component as classname.
			echo '<input style="background-color:#81a5d4; color:#ffffff; font-size:14px;" ' .
				'class="rmrole ' . $user->getUnixName() . 
				'" type="submit" name="rmrole" value="X" ' .
				'title="Click to remove from project" />';
			echo '<span class="leadSpace_' . $user->getUnixName() . 
				'" style="display:none;">&nbsp</span>';
			echo '<a href="/users/' . $user->getUnixName() . '"> ' . 
				$displayName . '</a>';
			echo '</td>';
			echo '</form>';
			$seen = true ;
		}

		echo '</tr>';
	}

	echo '<tr '. $HTML->boxGetAltRowStyle($cnt) . '>';
	echo '<form action="'.getStringFromServer('PHP_SELF').'" method="post">';

	echo '<td>';
	echo '<input type="hidden" name="submitConfirm" value="y" />';
	// Passed "addrole" as a hidden parameter.
	echo '<input type="hidden" name="addrole" value="y" />';
	echo '<input type="hidden" name="form_unix_name" value="'.$user->getUnixName().'" />';
	echo '<input type="hidden" name="group_id" value="'. $group_id .'" />';
	foreach ($roles as $role) {
		echo '<input type="hidden" name="cur_role_id" value="'.$role->getID().'" />';
		echo '<input type="hidden" name="cur_role_name" value="'.$role->getName().'" />';
	}
	// Add the user_name to this "setrole" DIV component as classname.
	echo '<div class="setrole ' . $user->getUnixName() .
		'" style="float:right;padding-top:3px;padding-bottom:3px;">' . 
		role_box($group_id,'role_id',$role->getID()) . 
		'</div>';
	echo '</td>';

	echo '</form></tr>';

	$checked = "";
	if (isset($arrUsers[$user->getID()]) && 
		// Has display order; check checkedbox.
		$arrUsers[$user->getID()] != "0") {
		$checked = "checked";
	}

	echo '<tr '. $HTML->boxGetAltRowStyle($cnt) . '>';
	echo '<td>';
	echo '<div style="float:right;padding-bottom:3px;">';
	echo '<form action="'.getStringFromServer('PHP_SELF').'" method="post">';
	// Passed "updateOrder" as a hidden parameter.
	echo '<input type="hidden" name="updateOrder" value="y" />';
	echo '<input type="hidden" name="submitConfirm" value="y" />';
	echo '<input type="hidden" name="form_unix_name" value="'.$user->getUnixName().'" />';
	echo '<input type="hidden" name="group_id" value="'. $group_id .'" />';
	echo '<input type="hidden" name="user_id" value="'. $user->getID() . '" />';

	echo '<table>';
	echo '<tr>';

	echo '<td>';
	echo '<div>Show in sidebar:&nbsp;</div>';
	echo '</td>';

	echo '<td>';
	echo '<input type="checkbox" title="Click to show user in sidebar" class="checkboxShow" name="checkboxShow" ' . $checked . ' />';
	echo '</td>';

	if ($checked != "") {
		echo '<td>';
		echo '<div>&nbsp;&nbsp;Order:&nbsp;</div>';
		echo '</td>';
		echo '<td>';
		echo '<div class="order" style="float:left;">' . 
			display_order_box('displayOrder', 
				$arrUsers[$user->getID()], 
				count($arrUsers)) . 
			'</div>';
		echo '</td>';
	}

	echo '</tr>';
	echo '</table>';

	echo '</div></td>';
	echo '</form>';
	echo '</tr>';
}


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

