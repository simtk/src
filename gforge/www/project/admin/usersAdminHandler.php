<?php

/**
 *
 * usersAdminHandler.php
 * 
 * File to handle users administration.
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
 
require_once $gfplugins.'phpBB/www/userPermsSetup.php';

$group_id = getIntFromRequest('group_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

// Add hook to replace users managements by a plugin.
$html_code = array();
if (plugin_hook_listeners("project_admin_users") > 0) {
	$hook_params = array () ;
	$hook_params['group_id'] = $group_id ;
	plugin_hook ("project_admin_users", $hook_params);
}

// Check whether a user is a member of the project.
function isUserPresent($userObjs, $theUserId) {

	foreach ($userObjs as $userObj) {
		if ($userObj->getID() == $theUserId) {
			// User is a member.
			return true;
		}
	}

	// User is not a member.
	return false;
}

function cache_external_roles () {
	global $used_external_roles, $unused_external_roles, $group, $group_id;

	$unused_external_roles = array () ;
	foreach (RBACEngine::getInstance()->getPublicRoles() as $r) {
		$grs = $r->getLinkedProjects () ;
		$seen = false ;
		foreach ($grs as $g) {
			if ($g->getID() == $group_id) {
				$seen = true ;
				break ;
			}
		}
		if (!$seen) {
			$unused_external_roles[] = $r ;
		}
	}
	$used_external_roles = array () ;
	foreach ($group->getRoles() as $r) {
		if ($r->getHomeProject() == NULL
		    || $r->getHomeProject()->getID() != $group_id) {
			$used_external_roles[] = $r ;
		}
	}

	sortRoleList ($used_external_roles, $group, 'composite') ;
	sortRoleList ($unused_external_roles, $group, 'composite') ;

}

cache_external_roles () ;

$theUserName = "";
if (getStringFromRequest('submitConfirm')) {
	if (getStringFromRequest('adduser') || getStringFromRequest('addrole')) {

		$cur_role_id = getIntFromRequest('cur_role_id');
		$cur_role_name = getStringFromRequest('cur_role_name');

		// Get all current user objects of this project.
		$userObjs = $group->getMembers();

		/* Add user to this project */
		$form_unix_name = getStringFromRequest('form_unix_name');
		$user_object = user_get_object_by_name($form_unix_name);

		if ($user_object === false) {
			$warning_msg .= _('No Matching Users Found');
		} else if (getStringFromRequest('adduser') && isUserPresent($userObjs, $user_object->getID())) {

			// User is already a member of the project.
			$user_name = $user_object->getRealName();
			$theUserName = $user_object->getUnixName();
			$warning_msg .= $user_name . ' is a member already';
		} else {
			// User is already a member of the project.
			$user_id = $user_object->getID();
			$user_name = $user_object->getRealName();
			$theUserName = $user_object->getUnixName();

			if ($cur_role_id != 0) {
				$role = RBACEngine::getInstance()->getRoleById($cur_role_id) ;
				if ($role->getHomeProject() == NULL) {
					session_require_global_perm ('forge_admin') ;
				} else {
					session_require_perm ('project_admin', $role->getHomeProject()->getID()) ;
				}
				if (!$role->removeUser (user_get_object ($user_id))) {
					$error_msg = $role->getErrorMessage() ;
				}
			}

			$role_id = getIntFromRequest('role_id');
			if (!$role_id) {
				$warning_msg .= _('Role not selected');
			} else {
				$user_id = $user_object->getID();
				if (!$group->addUser($form_unix_name,$role_id)) {
					$error_msg = $group->getErrorMessage();
				} else {
					if (getStringFromRequest('adduser')) {
						$feedback = "Member added successfully";
					}
					else {
						$feedback = $user_name . " changed role from '" . $cur_role_name . "' successfully";
					}
					//if the user have requested to join this group
					//we should remove him from the request list
					//since it has already been added
					$gjr=new GroupJoinRequest($group,$user_id);
					if ($gjr || is_object($gjr) || !$gjr->isError()) {
						$gjr->delete(true);
					}
				}
			}
		}
	} elseif (getStringFromRequest('rmrole')) {
		/* Remove a member from this project */
		$user_id = getIntFromRequest('user_id');
		$user_object = user_get_object($user_id);
		$user_name = null;
		if ($user_object !== false) {
			$user_name = $user_object->getRealName();
			$theUserName = $user_object->getUnixName();
		}

		$role_id = getIntFromRequest('role_id');
		$role = RBACEngine::getInstance()->getRoleById($role_id) ;
		if ($role->getHomeProject() == NULL) {
			session_require_global_perm ('forge_admin') ;
		} else {
			session_require_perm ('project_admin', $role->getHomeProject()->getID()) ;
		}
		if (!$role->removeUser (user_get_object ($user_id))) {
			$error_msg = $role->getErrorMessage() ;
		} else {
			// Update display order, since a role has been removed.
			regenerateDisplayOrder($group_id);

			// Get list of users in current group (user id is key of array).
			$members = $group->getUsersWithId();
			if (array_key_exists($user_id, $members)) {
				// User is still present (i.e. has remaining role).
				$feedback = _("Role removed successfully");
			}
			else {
				// User with last role has been removed.
				// Hence, the user is no longer in the group.
				if ($user_name != null) {
					$feedback = $user_name . " removed successfully";
				}
				else {
					$feedback = _("User and role removed successfully");
				}
			}
		}
	} elseif (getStringFromRequest('updateuser')) {
		/* Adjust Member Role */
		$user_id = getIntFromRequest('user_id');
		$user_object = user_get_object($user_id);
		$user_name = null;
		if ($user_object !== false) {
			$user_name = $user_object->getRealName();
			$theUserName = $user_object->getUnixName();
		}

		$role_id = getIntFromRequest('role_id');
		if (! $role_id) {
			$error_msg = _("Role not selected");
		}
		else {
			if (!$group->updateUser($user_id,$role_id)) {
				$error_msg = $group->getErrorMessage();
			} else {
				$feedback = _("Member updated successfully");
			}
		}
	} elseif (getStringFromRequest('acceptpending')) {
		/* Add user to this project */
		$role_id = getIntFromRequest('role_id');
		if (!$role_id) {
			$warning_msg .= _("Role not selected");
		} else {
			$form_userid = getIntFromRequest('form_userid');
			$form_unix_name = getStringFromRequest('form_unix_name');
			if (!$group->addUser($form_unix_name,$role_id)) {
				$error_msg = $group->getErrorMessage();
			} else {
				$gjr=new GroupJoinRequest($group,$form_userid);
				if (!$gjr || !is_object($gjr) || $gjr->isError()) {
					$error_msg = _('Error Getting GroupJoinRequest');
				} else {
					$gjr->delete(true);
				}
				$feedback = _("Member added successfully");
			}
		}
	} elseif (getStringFromRequest('rejectpending')) {
		/* Reject adding user to this project */
		$form_userid = getIntFromRequest('form_userid');
		$gjr=new GroupJoinRequest($group,$form_userid);
		if (!$gjr || !is_object($gjr) || $gjr->isError()) {
			$error_msg .= _('Error Getting GroupJoinRequest');
		} else {
			if (!$gjr->reject()) {
				$error_msg = $gjr->getErrorMessage();
			} else {
				$feedback .= _('Rejected');
			}
		}
	} elseif (getStringFromRequest('linkrole')) {
		/* link a role to this project */
		$role_id = getIntFromRequest('role_id');
		foreach ($unused_external_roles as $r) {
			if ($r->getID() == $role_id) {
				if (!$r->linkProject($group)) {
					$error_msg = $r->getErrorMessage();
				}
				else {
					if ($role_id == 1) {
						// Anonymous role.
						// Also have to grant anonymous access to project again.
						setAnonymousAccessForProject($group->getID());
					}
					else if ($role_id=2) {
						// Change project_read value the default value of 0 to 1.
						// Get Logged in role.
						$theLoggedInRole = RoleLoggedIn::getInstance();
						$theLoggedInRole->setSetting('project_read',
							$group->getID(), 1);
					}
					$feedback = _("Role linked successfully");
					$group->addHistory(_('Linked Role'), $r->getName());
					cache_external_roles () ;
				}
			}
		}
	} elseif (getStringFromRequest('unlinkrole')) {
		/* unlink a role from this project */
		$role_id = getIntFromRequest('role_id');
		foreach ($used_external_roles as $r) {
			if ($r->getID() == $role_id) {
				if (!$r->unLinkProject($group)) {
					$error_msg = $r->getErrorMessage();
				} else {
					$feedback = _("Role unlinked successfully");
					$group->addHistory(_('Unlinked Role'), $r->getName());
					cache_external_roles () ;
				}
			}
		}
	} elseif (getStringFromRequest('updateOrder')) {
		// Update order of display in project overview.
		$userId = getStringFromRequest('user_id');
		$user_object = user_get_object($user_id);
		$user_name = null;
		if ($user_object !== false) {
			$user_name = $user_object->getRealName();
			$theUserName = $user_object->getUnixName();
		}

		$isShow = getStringFromRequest('checkboxShow');
		$displayOrder = getStringFromRequest('displayOrder');

		// Update user display order in the group.
		updateOrder($group_id, $userId, $isShow, $displayOrder);

		$feedback = "Display order updated";
	}

	// Update users permission in forum.
	userPermsSetup($group_id);

	// Clear phpBB cache for given user.
	if ($theUserName != "") {
		$urlClearUsercache = "https://".
			getStringFromServer('HTTP_HOST') .
			"/plugins/phpBB/clearUserCache.php?" .
			"userName=" . $theUserName;

		// Invoke URL access to add the user to phpbb_users.
		$resStr = file_get_contents($urlClearUsercache);
	}
}

$group->clearError();

?>

