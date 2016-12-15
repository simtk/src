<?php
/**
require_once $gfcommon.'include/Error.class.php';


class Publication extends Error {

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
	function Publication(&$group, $pubId=false) {
		$this->Error();
		if (!$group || !is_object($group)) {
			$this->setNotValidGroupObjectError();
			return false;
		}
		if ($group->isError()) {
			$this->setError('Publication:: '.$group->getErrorMessage());
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
	 *  fetchData() - re-fetch the data for this publication from the database.
	 *
	 *  @param  int	 The publication id.
	 *	@return	boolean	success
	 */
	function fetchData($pubId) {
		$res=db_query_params("SELECT * FROM plugin_publications WHERE pub_id='$pubId' AND group_id='". $this->group->getID() ."'",array());
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('publications','invalid_pub_id'));
			return false;
		}
		$this->data_array =& db_fetch_array($res);
		db_free_result($res);
		return true;
	}
	
	/**
	 *  getPublications() - get all rows for this publication from the database.
	 *
	 *	@return	arrray  The array of Publications
	 */
	function getPublications() {
		$res = db_query_params("SELECT * FROM plugin_publications WHERE group_id='". $this->group->getID() ."' ORDER BY is_primary DESC",array());
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('publications','error_no_rows'));
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
	 *	setAsOnlyPimary - sets this pub as primary, and *unsets* all other in group
	 *
	 *  @param  int	 The publication id.
	 *	@return	boolean	success.
	 */
	function setAsOnlyPrimary($pubId) {
                $sqlCmd="UPDATE plugin_publications SET is_primary=0
                         WHERE group_id=".$this->group->getID();
                //echo "$sqlCmd <br/>";
                $res=db_query_params($sqlCmd,array());

                if (!$res || db_affected_rows($res) < 1) {
                   //     $this->setOnUpdateError(db_error());
                        return false;
                }

                $sqlCmd="UPDATE plugin_publications SET is_primary=1
                         WHERE pub_id=".$pubId;
                //echo "$sqlCmd <br/>";
                $res=db_query_params($sqlCmd,array());
                if (!$res || db_affected_rows($res) < 1) {
                    //    $this->setOnUpdateError(db_error());
                        return false;
                }
		return true;
	}


        /**
         *      setNotPrimary - makes this pub NOT primary.
         *
         *  @param  int  The publication id.
         *      @return boolean success.
         */
        function setNotPrimary($pubId) {
                $sqlCmd="UPDATE plugin_publications SET is_primary=0 WHERE pub_id=".$pubId;
                $res=db_query_params($sqlCmd,array());
                  //      $this->setOnUpdateError(db_error());
                  return false;
                }
		  return true;
        }


	/**
	 *	create - use this function to create a new entry in the database.
	 *
	 *	@param	string	The publication text.
	 *	@param	string	The year of the publication.
	 *	@param	string	A URL to the publication.
	 *	@return	boolean	success.
	 */
	function create($publication, $year, $url, $is_primary, $abstract) {
		global $Language;
          
		if (!$this->validate($publication, $year)) {
			return false;
		}
		$perm =& $this->group->getPermission( session_get_user() );

		if (!$perm || !is_object($perm) || !$perm->isMember()) {
			$this->setPermissionDeniedError();
			return false;
		}
		if ( $url && !eregi( "^(http|https|ftp)://", $url ) ) { $url = "http://" . $url; }
		$site = @fopen( $url, "r" );
		if ( $site )
			fclose( $site );
		else if ( $url )
		{
			if ( $_SESSION[ 'url_override_pub_0' ] != $url )
			{
				$_SESSION[ 'url_override_pub_0' ] = $url;
				throw( new RuntimeException( "Invalid URL: " . $url ) );
			//	$this->setError( $Language->getText( 'publications', 'error_bad_url', array( $url ) ) );
				return false;
			}
		}
		$sql='INSERT INTO plugin_publications (group_id,publication,publication_year,url,is_primary,abstract)
		VALUES ($1, $2, $3, $4, $5, $6)';
		db_begin();
		
		$result=db_query_params($sql,array($this->group->getID(),$publication,$year,$url,$is_primary,$abstract));
		if (!$result) {
			$this->setError('Error Adding Publication: '.db_error());
            //echo "error: " . db_error();
			db_rollback();
			return false;
		}

/*
		$pubId=db_insertid($result,'publications','pub_id');
		if (!$this->fetchData($pubId)) {
			db_rollback();
			return false;
		}
*/		
		db_commit();
		unset( $_SESSION[ 'url_override_pub_0' ] );
		return true;
	}



	/**
	 *	update - use this function to update an existing entry in the database.
	 *
	 *	@param	string	The publication text.
	 *	@param	string	The year of the publication.
	 *	@param	string	A URL to the publication.
	 *	@return	boolean	success.
	 */
	function update($publication, $year, $url, $is_primary, $abstract) {

		if (!$this->validate($publication, $year)) {
			return false;
		}
		
		$perm =& $this->group->getPermission( session_get_user() );
/*
		if (!$perm || !is_object($perm) || !$perm->isPubsEditor()) {
			$this->setPermissionDeniedError();
			return false;
		}
*/
		if ( $url && !eregi( "^(http|https|ftp)://", $url ) ) { $url = "http://" . $url; }
		$site = @fopen( $url, "r" );
		if ( $site )
			fclose( $site );
		else if ( $url )
		{
			if ( $_SESSION[ 'url_override_pub_' . $this->getID() ] != $url )
			{
				$_SESSION[ 'url_override_pub_' . $this->getID() ] = $url;
				throw( new RuntimeException( "Invalid URL: " . $url ) );
		//		$this->setError( $Language->getText( 'publications', 'error_bad_url', array( $url ) ) );
				return false;
			}
		}

		db_begin();
		$res=db_query_params("UPDATE plugin_publications SET
			publication='". $publication ."',
			publication_year='".$year."',
			url='". $url ."',
			is_primary='$is_primary',
			abstract='". $abstract ."'
			WHERE group_id=".$this->group->getID()."
			AND pub_id=".$this->getID(),array());

		if (!$res || db_affected_rows($res) < 1) {
//			$this->setOnUpdateError(db_error());
                        echo "</br>rows less 1";
                        echo db_error();
                        echo "</br>groupid: " . $this->group->getID();
                        echo "</br>pubid: " . $this->getID();
                        echo "</br>pubid: " . $this->data_array['pub_id'];
                        echo "</br>year: " . $this->data_array['publication_year'];
       
			db_rollback();
			return false;
		}
		
		if (!$this->fetchData($this->getID())) {
                        echo "fetch id error";
			db_rollback();
			return false;
		}
		
		db_commit();
		return true;
	}
	
    /**
     *  delete - delete this publication.
     *
     *  @param  bool    I'm Sure.
     *  @param  bool    I'm REALLY sure.
     *  @return   bool true/false;
     */
    function delete($pub_id) {
		$perm =& $this->group->getPermission( session_get_user() );
/*
		if (!$perm || !is_object($perm) || !$perm->isPubsEditor()) {
			$this->setPermissionDeniedError();
			return false;
		}
    	
*/
		db_query_params("DELETE FROM plugin_publications WHERE pub_id='".$pub_id."'
			AND group_id='".$this->group->getID()."'",array());

		return true;
    }
	
	function &getGroup() {
		return $this->group;
	}

	function getID() {
		return $this->data_array['pub_id'];
	}
	
	function getPublication() {
		return $this->data_array['publication'];
	}
	
	function getYear() {
		return $this->data_array['publication_year'];
	}
	
	function getUrl() {
		return $this->data_array['url'];
	}
	
	function getIsPrimary() {
		return $this->data_array['is_primary'];
	}

	function getAbstract() {
		return $this->data_array['abstract'];
	}
	
	function validate($publication,$year) {
		global $Language;

		if (strlen($publication) < 10) {
			$this->setError(_('Publication Must Be Minimum Length of 10.'));
			return false;
		}

		if (empty($year)) {
			$this->setError(_('publications','error_no_year'));
			return false;
		}
		
		if (!is_numeric($year)) {
			$this->setError(_('publications','error_invalid_year'));
			return false;
		}
		
		return true;
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
