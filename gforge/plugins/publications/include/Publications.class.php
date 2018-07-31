<?php
/**
 * Publications.class.php
 *
 * publications plugin Class which contains methods for adding, deleting and editing pubs.
 * 
 * Copyright 2005-2018, SimTK Team
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
			$this->setError(_('publications'.'invalid_pub_id'));
			return false;
		}
		$this->data_array = db_fetch_array($res);
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
			$this->setError(_('publications'.'error_no_rows'));
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
	
	function getPrimary() {
	    $res = db_query_params("SELECT * FROM plugin_publications WHERE group_id='". $this->group->getID() ."' AND is_primary = 1",array());
		if (!$res || db_numrows($res) < 1) {
			return false;
		} 
		/* Return result array */
	        return db_fetch_array($res);
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
		if (!$res || db_affected_rows($res) < 1) {
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
/*
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
			if (isset($_SESSION)) {
				if ( $_SESSION[ 'url_override_pub_0' ] != $url )
				{
					$_SESSION[ 'url_override_pub_0' ] = $url;
					throw( new RuntimeException( "Invalid URL: " . $url ) );
					//$this->setError( $Language->getText( 'publications', 'error_bad_url', array( $url ) ) );
					return false;
				}
			}
		}
*/

		// Add http if protocol not present.
		if ($url && !preg_match("/^(http|https|ftp):\/\//i", $url)) {
			$url = "http://" . $url;
		}
		// Try to access site.
		$site = @fopen($url, "r");
		if ($site) {
			// Can access site. Close.
			fclose($site);
		}
		else if ($url) {
			// Cannot access site using fopen.
			// Test the URL, access using curl, and check HTTP status code.
			if (!$this->validateURL($url)) {
				// Invalid URL.
				$this->setError("Cannot reach specified URL($url)");
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
		
/*
		$perm =& $this->group->getPermission( session_get_user() );
		if (!$perm || !is_object($perm) || !$perm->isPubsEditor()) {
			$this->setPermissionDeniedError();
			return false;
		}
		if ( $url && !eregi( "^(http|https|ftp)://", $url ) ) { $url = "http://" . $url; }
		$site = @fopen( $url, "r" );
		if ( $site )
			fclose( $site );
		else if ( $url )
		{
			if (isset($_SESSION)) {
				if ( $_SESSION[ 'url_override_pub_' . $this->getID() ] != $url )
				{
					$_SESSION[ 'url_override_pub_' . $this->getID() ] = $url;
					throw( new RuntimeException( "Invalid URL: " . $url ) );
					//$this->setError( $Language->getText( 'publications', 'error_bad_url', array( $url ) ) );
					return false;
				}
			}
		}
*/

		// Add http if protocol not present.
		if ($url && !preg_match("/^(http|https|ftp):\/\//i", $url)) {
			$url = "http://" . $url;
		}
		// Try to access site.
		$site = @fopen($url, "r");
		if ($site) {
			// Can access site. Close.
			fclose($site);
		}
		else if ($url) {
			// Cannot access site using fopen.
			// Test the URL, access using curl, and check HTTP status code.
			if (!$this->validateURL($url)) {
				// Invalid URL.
				$this->setError("Cannot reach specified URL($url)");
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
			//$this->setOnUpdateError(db_error());
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
/*
		$perm =& $this->group->getPermission( session_get_user() );
		if (!$perm || !is_object($perm) || !$perm->isPubsEditor()) {
			$this->setPermissionDeniedError();
			return false;
		}
    	
*/
		db_query_params("DELETE FROM plugin_publications WHERE pub_id='".$pub_id."' AND group_id='".$this->group->getID()."'",array());

		return true;
	}

	// Validate URL.
	function validateURL($strUrl) {

		// Check URL.
		$pattern = '|[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
		if (preg_match($pattern, $strUrl) == 0) {
			return false;
		}

		$ext = "";
		preg_match('/[^?]*/', $strUrl, $matches);
		$match = $matches[0];
		$pattern = preg_split('/\./', $match, -1, PREG_SPLIT_OFFSET_CAPTURE);
		if (count($pattern) > 1) {
			$namepart = $pattern[count($pattern)-1][0];
			preg_match('/[^?]*/', $namepart, $matches);
			$ext = $matches[0];
		}

		$starttime = date("s",time());
		$httpCode = $this->urlExistance($strUrl);
		$endtime = date("s",time());

		if (($endtime - $starttime) >= 5) {
			return false;
		}
		else if ($httpCode == 200) {
			if (strlen(trim($ext)) > 0) {
				return true;
			}
			else {
				return false;
			}
		}
		else if ($httpCode == 302) {
			if (strlen(trim($ext)) > 0) {
				return true;
			}
			else {
				return false;
			}
		}
		else if ($httpCode == 403) {
			// Request valid; server refused.
			return true;
		}

		return false;
	}

	// Retrieve from URL.
	function urlExistance($strUrl) {
		$handle = curl_init($strUrl);
		$timeout = 5;
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle,  CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLOPT_NOBODY, true);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

		// Get the HTML or whatever is linked in $strUrl.
		$response = curl_exec($handle);

		// Check for 404 (file not found).
		$httpCode1 = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		curl_close($handle);

		return $httpCode1;
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
			$this->setError(_('publications'.'error_no_year'));
			return false;
		}
		
		if (!is_numeric($year)) {
			$this->setError(_('publications'.'error_invalid_year'));
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


