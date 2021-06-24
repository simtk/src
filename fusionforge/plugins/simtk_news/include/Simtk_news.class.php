<?php/** * Simtk_news.class.php * * news plugin Class - contains methods for retrieving news & displaying global, displaying locally. *  * Copyright 2005-2016, SimTK Team * * This file is part of the SimTK web portal originating from         * Simbios, the NIH National Center for Physics-Based                * Simulation of Biological Structures at Stanford University,       * funded under the NIH Roadmap for Medical Research, grant           * U54 GM072970, with continued maintenance and enhancement * funded under NIH grants R01 GM107340 & R01 GM104139, and  * the U.S. Army Medical Research & Material Command award  * W81XWH-15-1-0232R01. *  * SimTK is free software; you can redistribute it and/or modify * it under the terms of the GNU General Public License as  * published by the Free Software Foundation, either version 3 of * the License, or (at your option) any later version. *  * SimTK is distributed in the hope that it will be useful, but * WITHOUT ANY WARRANTY; without even the implied warranty of * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the * GNU General Public License for more details.  *  * You should have received a copy of the GNU General Public  * License along with SimTK. If not, see   * <http://www.gnu.org/licenses/>. */ 
//require_once $gfcommon.'include/Error.class.php';
require_once $gfcommon.'include/FFError.class.php';
class Simtk_news extends FFError {

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
	//function Simtk_news(&$group, $Id=false) {
	function __construct(&$group, $Id=false) {
		//$this->Error();
		parent::__construct();
		if (!$group || !is_object($group)) {
			$this->setNotValidGroupObjectError();
			return false;
		}
		if ($group->isError()) {
			$this->setError('Simtk_news:: '.$group->getErrorMessage());
			return false;
		}
		$this->group =& $group;

		if ($Id) {
			if (!$this->fetchData($Id)) {
				return false;
			}
		}
		return true;
    }

	/**
	 *  fetchData() - re-fetch the data for this publication from the database.
	 *
	 *  @param  int	 The publication id.
	 *	@return	boolean	success
	 */
	function fetchData($Id) {
		$res=db_query_params("SELECT * FROM plugin_simtk_news WHERE id=$1 AND group_id=$2",array($Id, $this->group->getID()));
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('simtk_news','invalid_pub_id'));
			return false;
		}
		$this->data_array =& db_fetch_array($res);
		db_free_result($res);
		return true;
	}
	
	/**
	 *  getSimtkNews() - get all rows of news from the database.
	 *
	 *	@return	arrray  The array of Publications
	 */
	function getSimtkNews() {
		$res = db_query_params("SELECT * FROM plugin_simtk_news WHERE group_id=$1 DESC",array($this->group->getID()));
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('simtk news','error_no_rows'));
			return false;
		}
                $results = array();  
                while ($row = db_fetch_array($res)) {
                   $result = new DALQueryResult();

                   foreach ($row as $k=>$v){
                      $result->$k = $v;
                   }
                   $results[] = $result;
                }
		db_free_result($res);
		return $results;
	}
    /**
     *      @return boolean success.     */
    function updateDisplayFrontpage($id,$simtk_sidebar_display) {
       $sqlCmd="UPDATE plugin_simtk_news SET simtk_sidebar_display=$1 WHERE id=$2";
       //echo "$sqlCmd <br/>";
       $res=db_query_params($sqlCmd,array($simtk_sidebar_display, $id));       if (!$res || db_affected_rows($res) < 1) {          //      $this->setOnUpdateError(db_error());          return false;       }
       return true;
    }	function updateDisplayGlobal($id,$simtk_global_display) {       $sqlCmd="UPDATE plugin_simtk_news SET is_approved=$1 WHERE id=$2";       $res=db_query_params($sqlCmd,array($simtk_global_display, $id));       if (!$res || db_affected_rows($res) < 1) {          return false;       }				       return true;    }	
		function updateDisplayGlobalGroupID($group_id,$simtk_global_display) {       $sqlCmd="UPDATE plugin_simtk_news SET is_approved=$1 WHERE group_id=$2";       $res=db_query_params($sqlCmd,array($simtk_global_display, $group_id));       if (!$res || db_affected_rows($res) < 1) {          return false;       }				       return true;    }			function globalDisplayExist($group_id) {	   $sqlCmd="SELECT * from plugin_simtk_news where is_approved = 1 and group_id=$group_id";       $res=db_query_params($sqlCmd,array());       if (!$res || db_affected_rows($res) < 1) {          return false;       }				       return true;	}			/**     *      @return boolean success.	 */    function updateRequestGlobal($id,$simtk_request_global) {	   if ($simtk_request_global) {	      $simtkRequestGlobal = "TRUE";	   } else {	      $simtkRequestGlobal = "FALSE";	   }       $sqlCmd="UPDATE plugin_simtk_news SET simtk_request_global = '$simtkRequestGlobal' WHERE id=$id";       $res=db_query_params($sqlCmd,array());       if (!$res || db_affected_rows($res) < 1) {          return false;       }                       return true;    }			/**     *      @return boolean success.	 */    function updateRequestGlobalGroupID($group_id,$simtk_request_global) {	   if ($simtk_request_global) {	      $simtkRequestGlobal = "TRUE";	   } else {	      $simtkRequestGlobal = "FALSE";	   }       $sqlCmd="UPDATE plugin_simtk_news SET simtk_request_global = '$simtkRequestGlobal' WHERE group_id=$group_id";       $res=db_query_params($sqlCmd,array());       if (!$res || db_affected_rows($res) < 1) {          return false;       }                       return true;    }	
	/**	 * sendDisplayNotificationEmail - Send display global notification email.	 *	 * This function sends out a notification email to the	 * SourceForge admin user when a news project requests to be display globally	 *	 * @return	boolean	success.	 * @access	public	 */	function sendDisplayNotificationEmail() {		$admins = RBACEngine::getInstance()->getUsersByAllowedAction ('approve_projects', -1);				if (count($admins) < 1) {			$this->setError(_("There is no administrator to send the mail to."));			return false;		}		foreach ($admins as $admin) {			$admin_email = $admin->getEmail();			setup_gettext_for_user ($admin);						$message = "\n"					. _('Please visit the following URL to approve or reject the global news request')._(': '). "\n"					. util_make_url('/admin/pending-simtk-news.php');			util_send_message($admin_email, sprintf(_('Global News for %s Project Submitted'), forge_get_config('forge_name')), $message);			setup_gettext_from_context();		}				return true;	}
	function &getGroup() {
		return $this->group;
	}

	function getID() {
		return $this->data_array['pub_id'];
	}
}
class DALQueryResult {

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

