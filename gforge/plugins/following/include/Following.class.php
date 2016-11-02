<?php
/**
 *
 * following plugin class
 * 
 * The class which contains all methods for the following plugin.
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

require_once $gfcommon.'include/Error.class.php';



class Following extends Error {
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
	 *	@param	object	The group object to which this publication is associated.
	 *	@return	boolean	success.
	 */
	function Following(&$group, $pubId=false) {
		$this->Error();

		if (!$group || !is_object($group)) {
			$this->setNotValidGroupObjectError();
			return false;

		}

		if ($group->isError()) {
			$this->setError('Following: '.$group->getErrorMessage());
			return false;

		}

		$this->group =& $group;


		if ($pubId) {
			if (!$this->fetchData($pubId)) {
				return false;
			}
		}

		return true;

    }



	/**
	 *  fetchData() - re-fetch the data for this following from the database.
	 *
	 *  @param  int	 The publication id.
	 *	@return	boolean	success
	 */
	function fetchData($pubId) {

		$res=db_query_params("SELECT * FROM project_follows,users WHERE users.user_name = project_follows.user_name and users.user_name = 'A' and follow = true AND group_id='". $this->group->getID() ."'",array());

		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('following: fetch error'));
			return false;
		}
		$this->data_array =& db_fetch_array($res);
		db_free_result($res);

		return true;

	}

	/**
	 *  getFollowing() - get all rows for this following from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function getFollowing($group_id) {

		$res = db_query_params("SELECT * FROM project_follows,users WHERE users.user_name = project_follows.user_name and users.status = 'A' and group_id= ". $group_id ." AND follows = true",array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

                $results = array();

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultFollow();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}

	
	/**
	 *  isFollowing() - get all rows for this following from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function isFollowing($group_id,$user_name) {

		$res = db_query_params("SELECT * FROM project_follows WHERE group_id='". $group_id ."' AND follows = true AND user_name = '" . $user_name . "'",array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

        return true;

	}

	

    /**
	 *  getFollowing() - get all rows for this following from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function getPrivateFollowing($group_id) {

		$res = db_query_params("SELECT * FROM project_follows,users WHERE users.user_name = project_follows.user_name and users.status = 'A' and group_id='". $group_id ."' AND follows = true AND public = false",array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

                $results = array();

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultFollow();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}


	/**
	 *  getFollowing() - get all rows for this following from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function getPrivateFollowingCount($group_id) {

		$res = db_query_params("SELECT * FROM project_follows,users WHERE users.user_name = project_follows.user_name and users.status = 'A' and group_id='". $group_id ."' AND follows = true AND public = false",array());

		if (!$res || db_numrows($res) < 1) {
			return 0;
		}

		return db_numrows($res);
	}


	/**
	 *  getFollowing() - get all rows for this following from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function getPublicFollowingCount($group_id) {

		$res = db_query_params("SELECT * FROM project_follows,users WHERE users.user_name = project_follows.user_name and users.status = 'A' and group_id='". $group_id ."' AND follows = true AND public = true",array());

		if (!$res || db_numrows($res) < 1) {
			return 0;
		}

		return db_numrows($res);
	}


	/**
	/**
	 *  getFollowing() - get all rows for this following from the database.
	 *
	 *	@return	arrray  The array of Followings 
	 */
	function getPublicFollowing($group_id) {

		$res = db_query_params("SELECT * FROM project_follows,users WHERE users.user_name = project_follows.user_name and users.status = 'A' and group_id='". $group_id ."' AND follows = true AND public = true",array());

		if (!$res || db_numrows($res) < 1) {
			return false;
		}

                $results = array();

                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResultFollow();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }

                   $results[] = $result;

                }

		db_free_result($res);

		return $results;

	}


	/**
	 *	@return	boolean	success.

	 */

	function unfollow($group_id,$user_name) {

        $sqlCmd="DELETE from project_follows WHERE group_id=".$group_id . " AND user_name = '" . $user_name . "'";

		db_begin();

                $res=db_query_params($sqlCmd,array());

                if (!$res || db_affected_rows($res) < 1) {
                        $this->setOnUpdateError(db_error());
                        return false;
                }

		db_commit();

		return true;
	}


	/**
	 *	@return	boolean	success.

	 */

	function follow($user_name,$public,$group_id) {

		$perm =& $this->group->getPermission( session_get_user() );

		// removed check for !$perm->isMember() - Tod Hing 05-19-15
		if (!$perm || !is_object($perm)) {
			$this->setPermissionDeniedError();
			return false;

		}

                $sqlCmd="UPDATE project_follows SET follows = true, public = " . $public .  
                         " WHERE group_id=".$group_id . " AND user_name = '" . $user_name . "'";

		db_begin();

                $res=db_query_params($sqlCmd,array());


                if (!$res || db_affected_rows($res) < 1) {
                  // insert new row
                  $sql = "INSERT INTO project_follows (group_id, user_name, follows, public) VALUES ($group_id, '$user_name', true,$public);";
                  $res=db_query_params($sql,array());


                }

		db_commit();

		return true;
	}






	function &getGroup() {

		return $this->group;

	}



	function getID() {

		return $this->data_array['pub_id'];

	}

	

}





class DALQueryResultFollow {


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



