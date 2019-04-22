<?php
/** * Api.class.php *
* api plugin Class which contains methods for api.
*
* Copyright 2005-2017, SimTK Team
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
*  * SimTK is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*  * You should have received a copy of the GNU General Public
* License along with SimTK. If not, see
* <http://www.gnu.org/licenses/>.
*/
class Api {

function checkKey() {

   $api_key = $_REQUEST['key'];
   if (!$api_key || empty($api_key)) {
      $data = array("message" => "Cannot Process your request. No API Key specified");
      $this->json($data);
      return false;
   } else if (!$this->getApiKey($api_key)) {
      $data = array("message" => "Cannot Process your request. API Key not valid");
      $this->json($data);
	  return false;
   }

   return true;
}

function getApiKey($api_key) {
   // Validate the API Key
   $res = db_query_params("SELECT * FROM plugin_api WHERE api_key='". $api_key ."' AND status >= 1",array());
   if (!$res || db_numrows($res) < 1) {
      return false;
   }
   return true;
}

function json($data) {
   header('Content-Type: application/json; charset=UTF-8');
   echo json_encode($data);
}

function retrieve() {

   $user_id = 0;
   $group_id = 0;

   $user_id = $_REQUEST['userid'];
   $group_id = $_REQUEST['groupid'];
   $tool = $_REQUEST['tool'];
   $action = $_REQUEST['action'];

   switch ($action) {
      case 100:
         $gforge = new FusionForge();
         $info = array(
                 'name' => $gforge->software_name,
                 'version' => $gforge->software_version,
                 'forge_name' => forge_get_config('forge_name'),
                 'php_version' => PHP_VERSION,
				 );

         $this->json($info);
         break;
      case 1:
         if (!empty($user_id) && !empty($group_id) && !empty($tool)) {
            $this->getUserPermission($user_id,$group_id,$tool);				}
         else {
            $data = array("message" => "Cannot Process your request. One or more empty or invalid parameters");
            $this->json($data);				}
            break;
      case 11:
         $this->getUser($user_id);
         break;
      case 12:
         $this->getProjectLeads($group_id);
         break;
      case 13:
         $this->getProjectUsers($group_id);
         break;
	    case 14:
	     $add_date = 0;
         $add_date = $_REQUEST['add_date'];
         $this->verifyUserValid($user_id,$add_date);
         break;
      case 15:
            $this->isMember($user_id,$group_id);
            break;
      case 16:
               $this->getProject($group_id);
               break;
      case 20:
         $studyid = 0;
		     $token = 0;
         $studyid = $_REQUEST['studyid'];
         $token = $_REQUEST['token'];
         $this->verifyStudyValid($studyid,$token);
         break;
      /*			 // hangs on this case
      case 2:
         $this->getActiveUsers();
         break;		     */
      case 3:
         $projects = group_get_active_projects();
         $this->json($projects);
         break;
      default:
         $data = array("message" => "Cannot Process your request. Action does not exist");
         $this->json($data);
	} // switch
}

function getProject($group_id) {
   $group = group_get_object($group_id);
   $data_arr = array();

   $data_item=array(
                 "is_public" => $group->isPublic()
   );

   //$data_arr[] = $data_item;

   $this->json($data_item);

}

// function retrieve
function getUserPermission($user_id,$group_id,$tool) {
   $res = db_query_params("SELECT * FROM pfo_role_setting,pfo_user_role WHERE pfo_role_setting.role_id = pfo_user_role.role_id and user_id = ".$user_id. " and section_name ='". $tool ."' AND ref_id = ".$group_id ,array());

   if (!$res || db_numrows($res) < 1) {
      return false;
   }

   $data = array("perm_val" => db_result($res,0,'perm_val'));
   $this->json($data);
}


function getProjectLeads($group_id) {
   $group = group_get_object($group_id);
   $data = $group->getLeads();
   $data_arr = array();
   foreach ($data as $data_obj) {
      $data_item=array(
                 "user_id" => $data_obj->data_array['user_id'],
                 "user_name" => $data_obj->data_array['user_name'],
                 "email" => $data_obj->data_array['email'],
                 "realname" => $data_obj->data_array['realname']
                 );

      $data_arr[] = $data_item;
   }

   $this->json($data_arr);

}

function getProjectUsers($group_id) {

   $group = group_get_object($group_id);
   $data = $group->getMembers();
   $data_arr = array();
   foreach ($data as $data_obj) {
      $data_item=array(
                 "user_id" => $data_obj->data_array['user_id'],
                 "user_name" => $data_obj->data_array['user_name'],
                 "email" => $data_obj->data_array['email'],
                 "realname" => $data_obj->data_array['realname']
       );

       $data_arr[] = $data_item;
   }

   $this->json($data_arr);
}

function isMember($user_id, $group_id) {

   $group = group_get_object($group_id);
   $data = $group->getMembers();
   $data_item=array("is_member" => 0);
   foreach ($data as $data_obj) {
     if ($data_obj->data_array['user_id'] == $user_id) {
       $data_item=array("is_member" => 1);
     }
   }
   $this->json($data_item);
}

function getActiveUsers() {

   $data = user_get_active_users();
   $data_arr = array();
   foreach ($data as $data_obj) {
      $data_item=array(
                 "user_id" => $data_obj->data_array['user_id'],
                 "user_name" => $data_obj->data_array['user_name'],
                 "email" => $data_obj->data_array['email'],
                 "realname" => $data_obj->data_array['realname']
      );

    $data_arr[] = $data_item;

    }

$this->json($data_arr);
}

function getUser($user_id) {

   $data = user_get_object($user_id);

   $data_item=array(
              "user_id" => $data->data_array['user_id'],
              "user_name" => $data->data_array['user_name'],
              "email" => $data->data_array['email'],
              "realname" => $data->data_array['realname'],
              "add_date" => $data->data_array['add_date']
    );

    $this->json($data_item);

}

function verifyUserValid($user_id,$add_date) {

   $data = user_get_object($user_id);

   if ($add_date == $data->data_array['add_date']) {
      $data_item=array("valid_user" => 1);
   } else {
      $data_item=array("valid_user" => 0);
   }

   $this->json($data_item);

}

function verifyStudyValid($study_id,$token) {

   $res = db_query_params("SELECT * FROM plugin_datashare, groups WHERE groups.group_id = plugin_datashare.group_id and study_id = $study_id and token = '$token'",array());

   if (!$res || db_numrows($res) < 1) {
      $data = array("study_valid" => 0);
   } else {
      $data = array("study_valid" => 1);
      $data =array(
	            "study_valid" => 1,
              "group_id" => db_result($res,0,'group_id'),
              "group_name" => db_result($res,0,'group_name'),
              "study_name" => db_result($res,0,'title'),
              "template_id" => db_result($res,0,'template_id'),
              "is_private" => db_result($res,0,'is_private'),
              "active" => db_result($res,0,'active'),
              "token" => db_result($res,0,'token')
   );
   }
   $this->json($data);

}

function getStudy($group_id,$studyid) {

   $study = new Datashare($group_id);

   if (!$study || !is_object($study)) {
	  exit_error('Error','Could Not Create Study Object');
   } elseif ($study->isError()) {
      exit_error($study->getErrorMessage(), 'Datashare Error');
   }

   $study_result = $study->getStudy($studyid);

   $data_item=array(
              "group_id" => $study_result->group_id,
              "template_id" => $study_result->template_id,
              "is_private" => $study_result->is_private,
              "active" => $study_result->active,
              "token" => $study_result->token
   );

   $this->json($data_item);
}

} // class API
