<?php
/**
 *
 * datashare plugin class
 * 
 * The class which contains all methods for the datashare plugin.
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

require_once $gfcommon.'include/Error.class.php';


class Datashare extends Error {

	const MAX_STUDIES = 3;

	/**
	 * Associative array of data from db.
	 *
	 * @var	 array   $data_array.
	 */
	var $data_array;

	/**
	 * The group object.
	 *
	 * @var	 object  $group.
	 */
	var $group;


	/**
	 *  Constructor.
	 *
	 *	@param	object	The group object to which this study is associated.
	 *	@return	boolean	success.
	 */
	function Datashare(&$group, $study_id=false) {
		$this->Error();

		if (!$group || !is_object($group)) {
			//$this->setNotValidGroupObjectError();
			return false;

		}

		if ($group->isError()) {
			$this->setError('Datashare: '.$group->getErrorMessage());
			return false;

		}

		$this->group =& $group;


		if ($study_id) {
			if (!$this->getStudy($study_id)) {
				return false;
			}
		}

		return true;

    }




	/**
	 *  getStudy() - get all rows for this study from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function getStudy($study_id) {

		$res = db_query_params("SELECT * FROM plugin_datashare WHERE study_id = $study_id",array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

                $results = array();

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultDS();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}

	/**
	 *  getStudy() - get all rows for this study from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function getStudyByGroup($group_id) {

		$res = db_query_params("SELECT * FROM plugin_datashare " .
			"WHERE group_id = $group_id " .
			"AND (active=0 OR active=1 OR active=-2 OR active=-3) " .
			"ORDER BY title",
			array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

                $results = array();

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultDS();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}
	

	/**
	 *	@return	boolean	success
	*/
	function insertStudy($group_id, $title, $description, $is_private, $template) {

		if (!session_loggedin() || !($user = &session_get_user())) {
			exit_not_logged_in();
		}
		if (!forge_check_perm("datashare", $group_id, 'write')) {
			exit_permission_denied("You cannot add a new study for a project unless you are an admin on that project.");
		}

		$userId = $user->getID();
		$realName = $user->getRealName();
		$userName = $user->getUnixName();

		// Check parameter validity.
		if (!$title || trim($title) == "") {
			$this->setError(_('title^'.'You must enter a title'));
			return false;
		}
		if (!$description || trim($description) == "") {
			$this->setError(_('description^'.'You must enter a description'));
			return false;
		}

		$result = $this->getStudyByGroup($group_id);
		$rowcount=count($result);
		if ($rowcount < self::MAX_STUDIES) {

			db_begin();

			$token = rand(10000,999999);
			// insert new row
			$res=db_query_params('INSERT INTO plugin_datashare ' .
				'(group_id, title, description, is_private, ' .
				'token, active, template_id, user_id, date_created) ' .
				'VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
				array($group_id, 
					htmlspecialchars($title), 
					htmlspecialchars($description), 
					$is_private, 
					$token, 
					0, 
					$template,
					$userId,
					time()));
			if (!$res || db_affected_rows($res) < 1) {
				$this->setError(_('Error creating new study'));
				return false;
			}

			db_commit();

			$message = "New study requested for approval.\n\n" . 
				"Study Title: " . $title . "\n" . 
				"Description: " . $description . "\n" . 
				"Group ID: " . $group_id . "\n" . 
				"Submitter: " . $realName . " ($userName)\n\n" .
				"Please visit the following URL to approve or reject this study:\n" .
				util_make_url("/admin/datashareStudies.php");
			$admins = RBACEngine::getInstance()->getUsersByAllowedAction ('approve_projects', -1);

			if (count($admins) < 1) {
				$this->setError(_("There is no administrator to send the mail to."));
				return false;
			}

			foreach ($admins as $admin) {
				$admin_email = $admin->getEmail();
				setup_gettext_for_user ($admin);
				util_send_message($admin_email,
					sprintf('New %s Study Submitted', 
						forge_get_config('forge_name')), 
					$message);
				setup_gettext_from_context();
			}

			return true;
		}
		else {
			// maximum studies allowed reached
			$this->setError(_('Maximum studies reached.'));
			return false;
		}
	}
	
	function updateStudy($study_id,$title,$description,$is_private) {
	
	    // Check parameter validity.
        if (!$title || trim($title) == "") {
           $this->setError(_('title^'.'You must enter a title'));
           return false;
        }
		if (!$description || trim($description) == "") {
           $this->setError(_('description^'.'You must enter a description'));
           return false;
        }
		
	    //$sqlCmd="UPDATE plugin_datashare SET title = $1, description = $2, is_private = $3 WHERE study_id= $study_id";
        db_begin(); 
        $res=db_query_params('UPDATE plugin_datashare SET title=$1, description=$2, is_private=$3 WHERE study_id=$4',
		array(htmlspecialchars($title),htmlspecialchars($description),$is_private,$study_id));
        db_commit();
        if (!$res || db_affected_rows($res) < 1) {
		   $this->setError(_('Error updating study'));
           return false;
        }

		return true;
	}
	
	/**
	 *  getTemplate() - get all rows for templates from the database.
	 *
	 *	@return	arrray  The array of Templates 
	 */
	function getTemplate() {

		$res = db_query_params("SELECT * FROM plugin_datashare_template",array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

                $results = array();

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultDS();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}
	
	/* call API to set up files on remote datasharing server */
    function initializeStudy($study_id) {
	
       // call API to create database table for new study.	
	
	}
	
	function &getGroup() {

		return $this->group;

	}

	function getID() {

		return $this->data_array['pub_id'];

	}	

}





class DALQueryResultDS {


  private $_results = array();

  public function __construct(){}

  public function __set($var,$val){
    $this->_results[$var] = $val;
  }


  public function __get($var){

    if (isset($this->_results[$var])){
      return $this->_results[$var];
    }
    else{
      return null;
    }

  }

}



