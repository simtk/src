<?php
require_once $gfcommon.'include/Error.class.php';
class Simtk_news extends Error {

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
	function Simtk_news(&$group, $Id=false) {
		$this->Error();
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
		$res=db_query_params("SELECT * FROM plugin_simtk_news WHERE id='$Id' AND group_id='". $this->group->getID() ."'",array());
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
		$res = db_query_params("SELECT * FROM plugin_simtk_news WHERE group_id='". $this->group->getID() ."' DESC",array());
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
     *      @return boolean success.
    function updateDisplayFrontpage($id,$simtk_sidebar_display) {
       $sqlCmd="UPDATE plugin_simtk_news SET simtk_sidebar_display = '$simtk_sidebar_display' WHERE id=$id";
       //echo "$sqlCmd <br/>";
       $res=db_query_params($sqlCmd,array());
       return true;
    }
	
	/**
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
