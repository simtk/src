<?php
require_once $gfcommon.'include/Error.class.php';
class Reports extends Error {
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
	 *	@param	object	The group object to which this report is associated.
	 *	@return	boolean	success.
	 */
	function Reports(&$group) {
		$this->Error();
		if (!$group || !is_object($group)) {
			$this->setNotValidGroupObjectError();
			return false;
		}
		if ($group->isError()) {
			$this->setError('Reports:: '.$group->getErrorMessage());
			return false;
		}
		$this->group =& $group;
		return true;
    }
	
	/**
	function &getGroup() {
		return $this->group;
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
    else {
      return null;
    }
  }
