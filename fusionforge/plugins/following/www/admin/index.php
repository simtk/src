<?php

/**
 *
 * following indx.php
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

error_reporting(-1);
ini_set('display_errors', 'On');

require_once $gfplugins.'env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once '../publications-utils.php';
require_once $gfplugins.'publications/include/Publications.class.php';


$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'publications');
}

$pluginname="publications";
publications_Project_Header(array('title'=>$pluginname . ' Project Plugin!','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);

if (session_loggedin()) {

	if (!forge_check_perm('project_admin', $group_id)) {
		exit_permission_denied(_('You cannot submit news for a project unless you are an admin on that project.'), 'home');
	}


			$userperm = $group->getPermission();//we'll check if the user belongs to the group (optional)
			if ( !$userperm->IsMember()) {
				exit_error("Access Denied", "You are not a member of this project");
			}

                        // check if deleting
                        $action = getStringFromRequest('action');
                        $pub_id = getIntFromRequest('pub_id');

                        $pub = new Publication($group);
                        if (!$pub || !is_object($pub)) {
                          exit_error('Error','Could Not Create Publication');
                        } elseif ($pub->isError()) {
                          exit_error('Error',$pub->getErrorMessage());
                        }
                        if (isset($action)) {
                          if ($action == "delete") {
                            //echo "</br>action: " . $action;
                            if ($pub->delete($pub_id)) {
                              $feedback = _('Publication Deleted.');
//                              echo '<p class="feedback">'. $feedback . "</p>";
                              echo '<p class="warning_msg">'. $feedback . "</p>";
                            }
                            else {
                              exit_error('Error',"Delete Error");
                            }
                          }
                          elseif ($action == "setprimary") {
                            //echo "</br>action: " . $action;
                            if ($pub->setAsOnlyPrimary($pub_id)) {
                              $feedback = _('Primary Set.');
//                              echo '<p class="feedback">'. $feedback . "</p>";
                              echo '<p class="warning_msg">'. $feedback . "</p>";
                            }
                            else {
                              exit_error('Error',"Primary Set Error");
                            }
                          }
                          elseif ($action == "unsetprimary") {
                            //echo "</br>action: " . $action;
                            if ($pub->setNotPrimary($pub_id)) {
                              $feedback = _('Primary Unset.');
//                              echo '<p class="feedback">'. $feedback . "</p>";
                              echo '<p class="warning_msg">'. $feedback . "</p>";
                            }
                            else {
                              exit_error('Error',"Primary Unset Error");
                            }
                          }
                        }
                        $result = $pub->getPublications();


                        foreach ($result as $result_list) {
						  if ($result_list->is_primary == 1) { 
						     echo "<h3>Primary</h3>";
						  }
                          echo '<p>' . $result_list->publication;
                          echo '&nbsp;&nbsp; (' . $result_list->publication_year . ')';
                          echo "&nbsp;&nbsp; [<a href='edit.php?group_id=$group_id&pub_id=$result_list->pub_id'>Edit</a>] ";
                          echo "&nbsp;&nbsp; [<a href='index.php?group_id=$group_id&action=delete&pub_id=$result_list->pub_id'>Delete</a>]";
                          if ($result_list->is_primary == 1) {
                            echo "&nbsp;&nbsp; [<a href='index.php?group_id=$group_id&action=unsetprimary&pub_id=$result_list->pub_id'>Undo Primary</a>] </p>";
                          } 
                          else {
                            echo "&nbsp;&nbsp; [<a href='index.php?group_id=$group_id&action=setprimary&pub_id=$result_list->pub_id'>Set Primary</a>] </p>";

                          }
                        }


}



//	site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
