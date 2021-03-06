<?php
/**
 * FusionForge trackers
 *
 * Copyright 1999-2001, VA Linux Systems, Inc.
 * Copyright 2002-2004, GForge, LLC
 * Copyright 2009, Roland Mas
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012, Thorsten “mirabilos” Glaser <t.glaser@tarent.de>
 * Copyright 2014, Franck Villaume - TrivialDev
 * Copyright 2016-2021, Henry Kwong, Tod Hing - SimTK Team
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once $gfcommon.'include/FFError.class.php';
require_once $gfcommon.'tracker/ArtifactExtraFieldElement.class.php';
require_once $gfcommon.'tracker/ArtifactStorage.class.php';

// Check whether logged-in user has access to the private tracker.
function checkPrivateTrackerPermissions($groupId) {

	if (forge_check_global_perm('forge_admin')) {
		// forge_admin always has permission.
		return true;
	}

	$theGroupObj = group_get_object($groupId);
	if (!$theGroupObj) {
		// Cannot get group object.
		return false;
	}

	if (!session_loggedin() ||
		!($theUserObj = &session_get_user())) {
		// Not logged in or cannot get user object.
		return false;
	}

	$theUserID = $theUserObj->getID();
	$memberObjs = $theGroupObj->getMembers();
	foreach ($memberObjs as $memberObj) {
		if ($memberObj->getID() == $theUserID) {
			// OK. User is a member of group.
			return true;
		}
	}

	// Not a member of project.
	return false;
}


/**
 * Gets an ArtifactType object from the artifact type id
 *
 * @param	int		$artType_id	The ArtifactType id
 * @param	resource|bool	$res		The DB handle if passed in (optional)
 * @return	ArtifactType	The ArtifactType object
 */
function &artifactType_get_object($artType_id, $res = false) {
	global $ARTIFACTTYPE_OBJ;
	if (!isset($ARTIFACTTYPE_OBJ["_".$artType_id."_"])) {
		if ($res) {
			//the db result handle was passed in
		}
		else {
/*
			$res = db_query_params('SELECT * FROM artifact_group_list_vw WHERE group_artifact_id=$1', array($artType_id));
*/
			// Get columns from artifact_group_list instead of from artifact_group_list_vw table.
			$strSql = 'SELECT * FROM artifact_group_list agl ' .
				'LEFT JOIN artifact_counts_agg aca ' .
				'USING (group_artifact_id) ' .
				'WHERE group_artifact_id=$1';

			$res = db_query_params($strSql, array($artType_id));
		}
		if (!$res || db_numrows($res) < 1) {
			$ARTIFACTTYPE_OBJ["_".$artType_id."_"] = false;
		}
		else {
			$data = db_fetch_array($res);
			$groupId = $data["group_id"];
			$Group = group_get_object($groupId);
			$ARTIFACTTYPE_OBJ["_".$artType_id."_"] = new ArtifactType($Group, $data["group_artifact_id"], $data);

			// Check if user is member of project.
			// If not, only get trackers that are public.
			if (!checkPrivateTrackerPermissions($groupId)) {
				$strSql = 'SELECT * FROM artifact_group_list agl ' .
					'LEFT JOIN artifact_counts_agg aca ' .
					'USING (group_artifact_id) ' .
					'WHERE group_artifact_id=$1 ' .
					'AND simtk_is_public=1';

				$res = db_query_params($strSql, array($artType_id));
				if (!$res || db_numrows($res) < 1) {
					$ARTIFACTTYPE_OBJ["_".$artType_id."_"] = false;
				}
				else {
					$data = db_fetch_array($res);
					$groupId = $data["group_id"];
					$Group = group_get_object($groupId);
					$ARTIFACTTYPE_OBJ["_".$artType_id."_"] = new ArtifactType($Group, $data["group_artifact_id"], $data);
				}
			}
		}
	}
	return $ARTIFACTTYPE_OBJ["_".$artType_id."_"];
}

function artifacttype_get_groupid($artifact_type_id) {
	global $ARTIFACTTYPE_OBJ;
	if (isset($ARTIFACTTYPE_OBJ["_".$artifact_type_id."_"])) {
		return $ARTIFACTTYPE_OBJ["_".$artifact_type_id."_"]->Group->getID();
	}

	$res = db_query_params('SELECT group_id FROM artifact_group_list WHERE group_artifact_id=$1',
		array($artifact_type_id));
	if (!$res || db_numrows($res) < 1) {
		return false;
	}
	$arr = db_fetch_array($res);
	return $arr['group_id'];
}

class ArtifactType extends FFError {

	/**
	 * The Group object.
	 *
	 * @var	object	$Group.
	 */
	var $Group;

	/**
	 * extra_fields 3d array - the IDs and Names of the extra fields
	 *
	 * @var	array	extra_fields;
	 */
	var $extra_fields = array();

	/**
	 * extra_field[extra_field_id] array - the IDs and Names of elements on the extra fields
	 *
	 * @var	array	extra_field
	 */
	var $extra_field;

	/**
	 * Technicians db resource ID.
	 *
	 * @var	int	$technicians_res.
	 */
	var $technicians_res;

	/**
	 * Submitters db resource ID.
	 *
	 * @var	int	$submitters_res.
	 */
	var $submitters_res;

	/**
	 * Status db resource ID.
	 *
	 * @var	int	$status_res.
	 */
	var $status_res;

	/**
	 * Canned responses resource ID.
	 *
	 * @var	int	$canned_responses_res.
	 */
	var $canned_responses_res;

	/**
	 * Array of artifact data.
	 *
	 * @var	array	$data_array.
	 */
	var $data_array;

	/**
	 * Array of element names so they only have to be fetched once from db.
	 *
	 * @var	array	 $data_array.
	 */
	var $element_name;

	/**
	 * Array of element status so they only have to be fetched once from db.
	 *
	 * @var	array	$data_array.
	 */
	var $element_status;

	/**
	 * cached return value of getVoters
	 * @var	int|bool	$voters
	 */
	var $voters = false;

	/**
	 * ArtifactType - constructor.
	 *
	 * @param	Group		$Group			The Group object.
	 * @param	int|bool	$artifact_type_id	The id # assigned to this artifact type in the db.
	 * @param	array|bool	$arr			The associative array of data.
	 */
	function __construct($Group, $artifact_type_id = false, $arr = false) {
		//$this->Error();
		parent::__construct();
		if (!$Group || !is_object($Group)) {
			$this->setError(_('No Valid Group Object'));
			return;
		}
		if ($Group->isError()) {
			$this->setError('ArtifactType: '.$Group->getErrorMessage());
			return;
		}
		$this->Group = $Group;
		if ($artifact_type_id) {
			if (!$arr || !is_array($arr)) {
				if (!$this->fetchData($artifact_type_id)) {
					return;
				}
			} else {
				$this->data_array =& $arr;
				if ($this->data_array['group_id'] != $this->Group->getID()) {
					$this->setError('Group_id in db result does not match Group Object');
					$this->data_array = null;
					return;
				}
			}
			//
			//  Make sure they can even access this object
			//
			if (!forge_check_perm ('tracker', $this->getID(), 'read')) {
				$this->setPermissionDeniedError();
				$this->data_array = null;
				return;
			}
		}
	}

	/**
	 * create - use this to create a new ArtifactType in the database.
	 *
	 * @param	string	$name			The type name.
	 * @param	string	$description		The type description.
	 * @param	bool	$email_all		(1) true (0) false - whether to email on all updates.
	 * @param	string	$email_address		The address to send new entries and updates to.
	 * @param	int	$due_period		Days before this item is considered overdue.
	 * @param	bool	$use_resolution		(1) true (0) false - whether the resolution box should be shown.
	 * @param	string	$submit_instructions	Free-form string that project admins can place on the submit page.
	 * @param	string	$browse_instructions	Free-form string that project admins can place on the browse page.
	 * @param	int	$datatype		(1) bug tracker, (2) Support Tracker, (3) Patch Tracker, (4) features (0) other.
	 * @return	int	id on success, false on failure.
	 */
	function create($name, $description, $email_all, $email_address,
		$due_period, $use_resolution, $submit_instructions, $browse_instructions, 
		$datatype = 0,
		$simtk_is_public = 1,
		$simtk_allow_anon = 1) {

		if (!forge_check_perm('tracker_admin', $this->Group->getID())) {
			$this->setPermissionDeniedError();
			return false;
		}

		if (!$name || !$description || !$due_period) {
			$this->setError(_('ArtifactType: Name, Description, Due Period, and Status Timeout are required'));
			return false;
		}

		// NOTE: Cannot contain "(" or ")" in name. Otherwise, jquery error would result.
		if (strpos($name, "(") !== false ||
			strpos($name, ")") !== false) {
			$this->setError("Name cannot contain parentheses.");
			return false;
		}

		if ($email_address) {
			$invalid_emails = validate_emails($email_address);
			if (count($invalid_emails) > 0) {
				$this->SetError(_('E-mail address(es) appeared invalid')._(': ').implode(',', $invalid_emails));
				return false;
			}
		}

		$use_resolution = ((!$use_resolution) ? 0 : $use_resolution);
		$simtk_is_public = ((!$simtk_is_public) ? 0 : 1);
		$simtk_allow_anon = ((!$simtk_allow_anon) ? 0 : 1);
		$email_all = ((!$email_all) ? 0 : $email_all);

		db_begin();

		$res = db_query_params('INSERT INTO
			artifact_group_list
			(group_id,
			name,
			description,
			email_all_updates,
			email_address,
			due_period,
			status_timeout,
			submit_instructions,
			browse_instructions,
			datatype,
			simtk_is_public,
			simtk_allow_anon,
			simtk_use_resolution)
			VALUES
			($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13)',
					array($this->Group->getID(),
							htmlspecialchars($name),
							htmlspecialchars($description),
							$email_all,
							$email_address,
							$due_period*(60*60*24),
							1209600,
							htmlspecialchars($submit_instructions),
							htmlspecialchars($browse_instructions),
							$datatype,
							$simtk_is_public,
							$simtk_allow_anon,
							$use_resolution));

		$id = db_insertid($res, 'artifact_group_list', 'group_artifact_id');

		if (!$res || !$id) {
			$this->setError('ArtifactType: '.db_error());
			db_rollback();
			return false;
		} else {
			if (!$this->fetchData($id)) {
				db_rollback();
				return false;
			} else {
				$this->Group->normalizeAllRoles();
				db_commit();
				return $id;
			}
		}
	}

	/**
	 * fetchData - re-fetch the data for this ArtifactType from the database.
	 *
	 * @param	int	$artifact_type_id	The artifact type ID.
	 * @return	boolean	success.
	 */
	function fetchData($artifact_type_id) {
		$this->voters = false;
/*
		$res = db_query_params('SELECT * FROM artifact_group_list_vw
			WHERE group_artifact_id=$1
			AND group_id=$2',
			array($artifact_type_id,
				$this->Group->getID()));
*/
		// Get columns from artifact_group_list instead of from artifact_group_list_vw table.
		$strSql = 'SELECT * FROM artifact_group_list agl ' .
			'LEFT JOIN artifact_counts_agg aca ' .
			'USING (group_artifact_id) ' .
			'WHERE group_artifact_id=$1 ' .
			'AND group_id=$2';

		$res = db_query_params($strSql,
			array($artifact_type_id, $this->Group->getID()));
		if (!$res || db_numrows($res) < 1) {
			// Do not setError() here.
			// Otherwise, the page exits with undesirable side effects.
			//$this->setError('ArtifactType: Invalid ArtifactTypeID');
			return false;
		}
		$this->data_array = db_fetch_array($res);

		// Check if user is member of project.
		// If not, only get trackers that are public.
		if (!checkPrivateTrackerPermissions($this->Group->getID())) {

			// Free result first.
			db_free_result($res);

			$strSql = 'SELECT * FROM artifact_group_list agl ' .
				'LEFT JOIN artifact_counts_agg aca ' .
				'USING (group_artifact_id) ' .
				'WHERE group_artifact_id=$1 ' .
				'AND group_id=$2 ' .
				'AND simtk_is_public=1';

			$res = db_query_params($strSql,
				array($artifact_type_id, $this->Group->getID()));
			if (!$res || db_numrows($res) < 1) {
				// Do not setError() here.
				// Otherwise, the page exits with undesirable side effects.
				//$this->setError('ArtifactType: Invalid ArtifactTypeID');
				return false;
			}
			$this->data_array = db_fetch_array($res);
		}
		db_free_result($res);
		return true;
	}

	/**
	 * getGroup - get the Group object this ArtifactType is associated with.
	 *
	 * @return	Object	The Group object.
	 */
	function &getGroup() {
		return $this->Group;
	}

	/**
	 * getID - get this ArtifactTypeID.
	 *
	 * @return	int	The group_artifact_id #.
	 */
	function getID() {
		return $this->data_array['group_artifact_id'];
	}

	/**
	 * getOpenCount - get the count of open tracker items in this tracker type.
	 *
	 * @return	int	The count.
	 */
	function getOpenCount() {
		return $this->data_array['open_count'];
	}

	/**
	 * getTotalCount - get the total number of tracker items in this tracker type.
	 *
	 * @return	int	The total count.
	 */
	function getTotalCount() {
		return $this->data_array['count'];
	}

	/**
	 * getSubmitInstructions - get the free-form string strings.
	 *
	 * @return	string	instructions.
	 */
	function getSubmitInstructions() {
		return $this->data_array['submit_instructions'];
	}

	/**
	 * getBrowseInstructions - get the free-form string strings.
	 *
	 * @return	string	instructions.
	 */
	function getBrowseInstructions() {
		return $this->data_array['browse_instructions'];
	}

	/**
	 * emailAll - determine if we're supposed to email on every event.
	 *
	 * @return	boolean	email_all.
	 */
	function emailAll() {
		return $this->data_array['email_all_updates'];
	}

	/**
	 * getSimtkAllowAnon - determine if we're supposed to allow anonymous user posting.
	 *
	 * @return	boolean	simtk_allow_anon.
	 */
	function getSimtkAllowAnon() {
		return $this->data_array['simtk_allow_anon'];
	}

	/**
	 * getSimtkIsPublic - determine if we're supposed to allow non-members to access tracker.
	 *
	 * @return	boolean	simtk_is_public.
	 */
	function getSimtkIsPublic() {
		return $this->data_array['simtk_is_public'];
	}

	// Check whether access to the tracker is permitted.
	function isPermitted() {

		if ($this->getSimtkIsPublic()) {
			// Public tracker.
			return true;
		}

		// Tracker is private. User must be logged in.

		if (!session_loggedin() ||
			!($theUserObj = &session_get_user())) {
			// Not logged in or cannot get user object.
			return false;
		}

		if (forge_check_global_perm('forge_admin')) {
			// forge_admin always has permission.
			return true;
		}

		$theGroupObj = $this->getGroup();
		if ($theGroupObj == false) {
			// Cannot get group object.
			return false;
		}

		$theUserID = $theUserObj->getID();
		$memberObjs = $theGroupObj->getMembers();
		foreach ($memberObjs as $memberObj) {
			if ($memberObj->getID() == $theUserID) {
				// OK. User is a member of group.
				return true;
			}
		}

		// Not a member of group.
		return false;
	}

	/**
	 * emailAddress - defined email address to send events to.
	 *
	 * @return	string	email.
	 */
	function getEmailAddress() {
		return $this->data_array['email_address'];
	}

	/**
	 * getName - the name of this ArtifactType.
	 *
	 * @return	string	name.
	 */
	function getName() {
		return $this->data_array['name'];
	}

	/**
	 * getFormattedName - formatted name of this ArtifactType
	 *
	 * @return	string	formatted name
	 */
	function getFormattedName() {
		$name = preg_replace('/[^[:alnum:]]/', '', $this->getName());
		$name = strtolower($name);
		return $name;
	}

	/**
	 * getUnixName - returns the name used by email gateway
	 *
	 * @return	string	unix name
	 */
	function getUnixName() {
		return strtolower($this->Group->getUnixName()).'-'.$this->getFormattedName();
	}

	/**
	 * getReturnEmailAddress() - return the return email address for notification emails
	 *
	 * @return	string	return email address
	 */
	function getReturnEmailAddress() {

		$address = '';
		if (forge_get_config('use_gateways')) {
			$address .= strtolower($this->getUnixName());
		} else {
			$address .= 'noreply';
		}
		$address .= '@'.forge_get_config('web_host');
		return $address;
	}

	/**
	 * getDescription - the description of this ArtifactType.
	 *
	 * @return	string	description.
	 */
	function getDescription() {
		return $this->data_array['description'];
	}

	/**
	 * getDuePeriod - how many seconds until it's considered overdue.
	 *
	 * @return	int	seconds.
	 */
	function getDuePeriod() {
		return $this->data_array['due_period'];
	}

	/**
	 * getStatusTimeout - how many seconds until an item is stale.
	 *
	 * @return	int	seconds.
	 */
	function getStatusTimeout() {
		return $this->data_array['status_timeout'];
	}

	/**
	 * getCustomStatusField - return the extra_field_id of the field containing the custom status.
	 *
	 * @return	int	extra_field_id.
	 */
	function getCustomStatusField() {
		return $this->data_array['custom_status_field'];
	}

	/**
	 * setCustomStatusField - set the extra_field_id of the field containing the custom status.
	 *
	 * @param	int	$extra_field_id	The extra field id.
	 * @return	boolean	success.
	 */
	function setCustomStatusField($extra_field_id) {
		$res = db_query_params('UPDATE artifact_group_list SET custom_status_field=$1
			WHERE group_artifact_id=$2',
					array ($extra_field_id,
					       $this->getID()));
		return $res;
	}

	/**
	 * usesCustomStatuses - boolean
	 *
	 * @return	boolean	use_custom_statues.
	 */
	function usesCustomStatuses() {
		return $this->getCustomStatusField();
	}

	/**
	 * remapStatus - pass the extra_fields array and return the status_id, either open/closed
	 *
	 * @param	int	$status_id	The status_id
	 * @param	array	$extra_fields	Complex array of extra_field_data
	 * @return	int	status_id.
	 */
	function remapStatus($status_id, $extra_fields) {
		if ($this->usesCustomStatuses()) {
			//get the selected element for the extra_field_status element
			$csfield = $this->getCustomStatusField();
			if (array_key_exists($csfield, $extra_fields)) {
				$element_id = $extra_fields[$csfield];

				//convert that element_id into the status_id
				$res = db_query_params('SELECT status_id FROM artifact_extra_field_elements WHERE element_id=$1',
					array($element_id));
				if (!$res) {
					$this->setError('Error Remapping Status: '.db_error());
					return false;
				}
				$status_id = db_result($res, 0, 'status_id');
			} else {
				// custom status was not passed... use the first status from the database
				$res = db_query_params('SELECT status_id FROM artifact_extra_field_elements WHERE extra_field_id=$1 ORDER BY element_id ASC LIMIT 1 OFFSET 0',
					array($csfield));
				if (db_numrows($res) == 0) { // No values available
					$this->setError('Error Remapping Status');
					return false;
				}
				$status_id = db_result($res, 0, 'status_id');
			}

			if ($status_id < 1 || $status_id > 4) {
				echo "INVALID STATUS REMAP: $status_id FROM SELECTED ELEMENT: $element_id";
				return false;
			}
			return $status_id;
		} else {
			return $status_id;
		}
	}

	/**
	 * getDataType - flag that is generally unused but can mark the difference between bugs, patches, etc.
	 *
	 * @return	int	The type (1) bug (2) support (3) patch (4) feature (0) other.
	 */
	function getDataType() {
		return $this->data_array['datatype'];
	}

	/**
	 * setMonitor - user can monitor this artifact.
	 *
	 * @return	bool	false - always false - always use the getErrorMessage() for feedback
	 */
	function setMonitor($user_id = -1) {
		if ($user_id == -1) {
			if (!session_loggedin()) {
				$this->setError(_('You can only monitor if you are logged in.'));
				return false;
			}
			else {
				// Check if tracker access is allowed.
				if (!$this->isPermitted()) {
					$this->setError("Permission denied. This project's administrator will have to grant you permission.");
					return false;
				}
			}
			$user_id = user_getid();
		}

		$res = db_query_params('SELECT * FROM artifact_type_monitor WHERE group_artifact_id=$1 AND user_id=$2',
			array($this->getID(),
				$user_id));
		if (!$res || db_numrows($res) < 1) {
			//not yet monitoring
			$res = db_query_params('INSERT INTO artifact_type_monitor (group_artifact_id,user_id) VALUES ($1,$2)',
				array($this->getID(),
					$user_id));
			if (!$res) {
				$this->setError(db_error());
				return false;
			} else {
				$this->setError(_('Monitoring Started'));
				return false;
			}
		} else {
			//already monitoring - remove their monitor
			db_query_params('DELETE FROM artifact_type_monitor
				WHERE group_artifact_id=$1
				AND user_id=$2',
				array($this->getID(),
					$user_id));
			$this->setError(_('Monitoring Stopped'));
			return false;
		}
	}

	function isMonitoring() {
		// Check if user is logged and tracker access is allowed.
		if (!session_loggedin() || !$this->isPermitted()) {
			return false;
		}
		$result = db_query_params('SELECT count(*) AS count FROM artifact_type_monitor
			WHERE user_id=$1 AND group_artifact_id=$2',
					   array(user_getid(),
						  $this->getID()));
		$row_count = db_fetch_array($result);
		return $result && $row_count['count'] > 0;
	}

	/**
	 * getMonitorIds - array of id of users monitoring this Artifact.
	 *
	 * @return	array	array of id of users monitoring this Artifact.
	 */
	function &getMonitorIds() {
		$res = db_query_params('SELECT user_id	FROM artifact_type_monitor WHERE group_artifact_id=$1',
					array($this->getID()));
		return util_result_column_to_array($res);
	}

	/**
	 * getExtraFields - List of possible user built extra fields
	 * set up for this artifact type.
	 *
	 * @param	array	$types
	 * @return	array	arrays of data;
	 */
	function getExtraFields($types = array()) {
		if (count($types)) {
			$filter = implode(',', $types);
			$types = explode(',', $filter);
		} else {
			$filter = '';
		}
		if (!isset($this->extra_fields[$filter])) {
			$this->extra_fields[$filter] = array();
			if (count($types)) {
				$res = db_query_params('SELECT *
				FROM artifact_extra_field_list
				WHERE group_artifact_id=$1
				AND field_type = ANY ($2)
				ORDER BY field_type ASC',
							array($this->getID(),
									db_int_array_to_any_clause($types)));
			} else {
				$res = db_query_params('SELECT *
				FROM artifact_extra_field_list
				WHERE group_artifact_id=$1
				ORDER BY field_type ASC',
					array($this->getID()));
			}
			while ($arr = db_fetch_array($res)) {
				$this->extra_fields[$filter][$arr['extra_field_id']] = $arr;
			}
		}

		return $this->extra_fields[$filter];
	}

	/**
	 * cloneFieldsFrom - clone all the fields and elements from another tracker
	 *
	 * @param	int	$clone_tracker_id
	 * @return	boolean	true/false on success
	 */
	function cloneFieldsFrom($clone_tracker_id) {

		$g = group_get_object(forge_get_config('template_group'));
		if (!$g || !is_object($g)) {
			$this->setError('Could Not Get Template Group');
			return false;
		} elseif ($g->isError()) {
			$this->setError('Template Group Error '.$g->getErrorMessage());
			return false;
		}
		$at = new ArtifactType($g,$clone_tracker_id);
		if (!$at || !is_object($at)) {
			$this->setError('Could Not Get Tracker To Clone');
			return false;
		} elseif ($at->isError()) {
			$this->setError('Clone Tracker Error '.$at->getErrorMessage());
			return false;
		}
		$efs = $at->getExtraFields();

		// get current getExtraFields if any
		$current_efs = $this->getExtraFields();

		//
		//	Iterate list of extra fields
		//
		db_begin();
		foreach ($efs as $ef) {
			//new field in this tracker
			$nef = new ArtifactExtraField($this);
			foreach ($current_efs as $current_ef) {
				if ($current_ef['field_name'] == $ef['field_name']) {
					// we delete the current extra field and use the template one...
					$current_ef_todelete = new ArtifactExtraField($this, $current_ef);
					$current_ef_todelete->delete(true,true);
				}
			}
			if (!$nef->create(util_unconvert_htmlspecialchars($ef['field_name']), $ef['field_type'], $ef['attribute1'], $ef['attribute2'], $ef['is_required'], $ef['alias'])) {
				$this->setError('Error Creating New Extra Field: '.$nef->getErrorMessage());
				db_rollback();
				return false;
			}
			//
			//	Iterate the elements
			//
			$resel = db_query_params('SELECT * FROM artifact_extra_field_elements WHERE extra_field_id=$1',
						array($ef['extra_field_id']));
			while ($el = db_fetch_array($resel)) {
				//new element
				$nel = new ArtifactExtraFieldElement($nef);
				if (!$nel->create(util_unconvert_htmlspecialchars($el['element_name']), $el['status_id'])) {
					db_rollback();
					$this->setError('Error Creating New Extra Field Element: '.$nel->getErrorMessage());
					return false;
				}
			}
		}
		db_commit();
		return true;

	}

	/**
	 * getExtraFieldName - Get a box name using the box ID
	 *
	 * @param	int	$extra_field_id	id of an extra field.
	 * @return	string	name of extra field.
	 */
	function getExtraFieldName($extra_field_id) {
		$arr = $this->getExtraFields();
		return $arr[$extra_field_id]['field_name'];
	}

	/**
	 * getExtraFieldElements - List of possible admin configured
	 * extra field elements. This function is used to
	 * present the boxes and choices on the main Add/Update page.
	 *
	 * @param	int	$id	id of the extra field
	 * @return	array of elements for this extra field.
	 */
	function getExtraFieldElements($id) {
//TODO validate $id
		if (!$id) {
			return false;
		}
		if (!isset($this->extra_field[$id])) {
			$this->extra_field[$id] = array();
			$res = db_query_params('SELECT element_id, element_name, status_id
				FROM artifact_extra_field_elements
				WHERE extra_field_id = $1
				ORDER BY element_pos ASC, element_id ASC',
				array($id));
			$i = 0;
			while ($arr = db_fetch_array($res)) {
				$this->extra_field[$id][$i++] = $arr;
			}
//			if (count($this->extra_field[$id]) == 0) {
//				return;
//			}
		}

		return $this->extra_field[$id];
	}

	/**
	 * getElementName - get the name of a particular element.
	 *
	 * @param	$choice_id
	 * @return	string		The name.
	 */
	function getElementName($choice_id) {
		if (!$choice_id) {
			return '';
		}
		if (is_array($choice_id)) {
			$choice_id = implode(',', array_map('intval', $choice_id));
		} else {
			$choice_id = intval($choice_id);
		}
		if ($choice_id == 100) {
			return 'None';
		}
		if (!isset($this->element_name[$choice_id])) {
			$res = db_query_params('SELECT element_id,extra_field_id,element_name
				FROM artifact_extra_field_elements
				WHERE element_id = ANY ($1)',
						array(db_int_array_to_any_clause(explode(',', $choice_id))));
			if (db_numrows($res) > 1) {
				$arr = util_result_column_to_array($res, 2);
				$this->element_name[$choice_id] = implode(',', $arr);
			} else {
				$this->element_name[$choice_id] = db_result($res, 0, 'element_name');
			}
		}
		return $this->element_name[$choice_id];
	}

	/**
	 * getElementStatusID - get the status of a particular element.
	 *
	 * @param	int|array	$choice_id
	 * @return	int		The status
	 */
	function getElementStatusID($choice_id) {
		if (!$choice_id) {
			return 0;
		}
		if (is_array($choice_id)) {
			$choice_id = implode(',',$choice_id);
		}
		if ($choice_id == 100) {
			return 0;
		}
		if (!$this->element_status[$choice_id]) {
			$res = db_query_params('SELECT element_id,extra_field_id,status_id
				FROM artifact_extra_field_elements
				WHERE element_id = ANY ($1)',
						array(db_int_array_to_any_clause(explode(',', $choice_id))));
			if (db_numrows($res) > 1) {
				$arr = util_result_column_to_array($res, 2);
				$this->element_status[$choice_id] = implode(',', $arr);
			} else {
				$this->element_status[$choice_id] = db_result($res, 0, 'status_id');
			}
		}
		return $this->element_status[$choice_id];
	}

	/**
	 * delete - delete this tracker and all its related data.
	 *
	 * @param	bool	$sure		I'm Sure.
	 * @param	bool	$really_sure	I'm REALLY sure.
	 * @return	bool	true/false;
	 */
	function delete($sure, $really_sure) {
		if (!$sure || !$really_sure) {
			$this->setMissingParamsError(_('Please tick all checkboxes.'));
			return false;
		}
		if (!forge_check_perm ('tracker_admin', $this->Group->getID())) {
			$this->setPermissionDeniedError();
			return false;
		}
		db_begin();
		db_query_params('DELETE FROM artifact_extra_field_data
			WHERE EXISTS (SELECT artifact_id FROM artifact
			WHERE group_artifact_id=$1
			AND artifact.artifact_id=artifact_extra_field_data.artifact_id)',
				array($this->getID()));
//echo '0.1'.db_error();
		db_query_params('DELETE FROM artifact_extra_field_elements
			WHERE EXISTS (SELECT extra_field_id FROM artifact_extra_field_list
			WHERE group_artifact_id=$1
			AND artifact_extra_field_list.extra_field_id = artifact_extra_field_elements.extra_field_id)',
				 array ($this->getID()));
//echo '0.2'.db_error();
		db_query_params('DELETE FROM artifact_extra_field_list
			WHERE group_artifact_id=$1',
			array ($this->getID()));
//echo '0.3'.db_error();
		db_query_params('DELETE FROM artifact_canned_responses
			WHERE group_artifact_id=$1',
				 array ($this->getID()));
//echo '1'.db_error();
		db_query_params('DELETE FROM artifact_counts_agg
			WHERE group_artifact_id=$1',
				 array ($this->getID()));
//echo '5'.db_error();

		ArtifactStorage::instance()->deleteFromQuery('SELECT id FROM artifact_file
			WHERE EXISTS (SELECT artifact_id FROM artifact
			WHERE group_artifact_id=$1
			AND artifact.artifact_id=artifact_file.artifact_id)',
				array($this->getID()));

		db_query_params('DELETE FROM artifact_file
			WHERE EXISTS (SELECT artifact_id FROM artifact
			WHERE group_artifact_id=$1
			AND artifact.artifact_id=artifact_file.artifact_id)',
				array($this->getID()));
//echo '6'.db_error();
		db_query_params('DELETE FROM artifact_message
			WHERE EXISTS (SELECT artifact_id FROM artifact
			WHERE group_artifact_id=$1
			AND artifact.artifact_id=artifact_message.artifact_id)',
				array($this->getID()));
//echo '7'.db_error();
		db_query_params('DELETE FROM artifact_history
			WHERE EXISTS (SELECT artifact_id FROM artifact
			WHERE group_artifact_id=$1
			AND artifact.artifact_id=artifact_history.artifact_id)',
				array($this->getID()));
//echo '8'.db_error();
		db_query_params('DELETE FROM artifact_monitor
			WHERE EXISTS (SELECT artifact_id FROM artifact
			WHERE group_artifact_id=$1
			AND artifact.artifact_id=artifact_monitor.artifact_id)',
				array($this->getID()));
//echo '9'.db_error();
		db_query_params('DELETE FROM artifact
			WHERE group_artifact_id=$1',
				array($this->getID()));
//echo '4'.db_error();
		db_query_params('DELETE FROM artifact_group_list
			WHERE group_artifact_id=$1',
				array($this->getID()));
//echo '11'.db_error();

		db_commit();
		ArtifactStorage::instance()->commit();

		$this->Group->normalizeAllRoles();

		return true;
	}

	/**
	 * getSubmitters - returns a result set of submitters.
	 *
	 * @return	resource	database result set.
	 */
	function getSubmitters() {
		if (!isset($this->submitters_res)) {
			$this->submitters_res = db_query_params('SELECT DISTINCT submitted_by, submitted_realname
				FROM artifact_vw
				WHERE group_artifact_id=$1
				ORDER BY submitted_realname',
				array($this->getID()));
		}
		return $this->submitters_res;
	}

	/**
	 * getCannedResponses - returns a result set of canned responses.
	 *
	 * @return	resource	database result set.
	 */
	function getCannedResponses() {
		if (!isset($this->cannedresponses_res)) {
			$this->cannedresponses_res = db_query_params('SELECT id,title
				FROM artifact_canned_responses
				WHERE group_artifact_id=$1',
								      array($this->getID()));
		}
		return $this->cannedresponses_res;
	}

	/**
	 * getStatuses - returns a result set of statuses.
	 *
	 * These statuses are either the default open/closed or any number of
	 * custom statuses that are stored in the extra fields. On insert/update
	 * to an artifact the status_id is remapped from the extra_field_element_id to
	 * the standard open/closed id.
	 *
	 * @return	resource	database result set.
	 */
	function getStatuses() {
		if (!isset($this->status_res)) {
			$this->status_res = db_query_params('SELECT * FROM artifact_status', array());
		}
		return $this->status_res;
	}

	/**
	 * getStatusName - returns the name of this status.
	 *
	 * @param	int	$id	The status ID.
	 * @return	string	name.
	 */
	function getStatusName($id) {
		$result = db_query_params('select status_name from artifact_status WHERE id=$1',
						array($id));
		if ($result && db_numrows($result) > 0) {
			return db_result($result, 0, 'status_name');
		} else {
			return 'Error: Not Found';
		}
	}

	/**
	 * update - use this to update this ArtifactType in the database.
	 *
	 * @param	string	$name			The item name.
	 * @param	string	$description		The item description.
	 * @param	bool	$email_all		(1) true (0) false - whether to email on all updates.
	 * @param	string	$email_address		The address to send new entries and updates to.
	 * @param	int	$due_period		Days before this item is considered overdue.
	 * @param	int	$status_timeout		 Days before stale items time out.
	 * @param	bool	$use_resolution		(1) true (0) false - whether the resolution box should be shown.
	 * @param	string	$submit_instructions	Free-form string that project admins can place on the submit page.
	 * @param	string	$browse_instructions	Free-form string that project admins can place on the browse page.
	 * @return	bool	true on success, false on failure.
	 */
	function update($name, $description, $email_all, $email_address,
		$due_period, $status_timeout, $use_resolution, 
		$submit_instructions, $browse_instructions,
		$simtk_allow_anon = 1) {

		if (!forge_check_perm ('tracker_admin', $this->Group->getID())) {
			$this->setPermissionDeniedError();
			return false;
		}

		if ($this->getDataType()) {
			$name=$this->getName();
			$description=$this->getDescription();
		}

		if (!$name || !$description || !$due_period || !$status_timeout) {
			$this->setError(_('ArtifactType: Name, Description, Due Period, and Status Timeout are required'));
			return false;
		}

		// NOTE: Cannot contain "(" or ")" in name. Otherwise, jquery error would result.
		if (strpos($name, "(") !== false ||
			strpos($name, ")") !== false) {
			$this->setError("Name cannot contain parentheses.");
			return false;
		}

		$result = db_query_params('SELECT count(*) AS count FROM artifact_group_list WHERE group_id=$1 AND name=$2 AND group_artifact_id!=$3',
			array($this->Group->getID(), $name, $this->getID()));
		if (!$result) {
			$this->setError('ArtifactType::Update(): '.db_error());
			return false;
		}
		if (db_result($result, 0, 'count')) {
			$this->setError(_('Tracker name already used'));
			return false;
		}

		if ($email_address) {
			$invalid_emails = validate_emails($email_address);
			if (count($invalid_emails) > 0) {
				$this->SetError(_('E-mail address(es) appeared invalid')._(': ').implode(',', $invalid_emails));
				return false;
			}
		}

		$email_all = ((!$email_all) ? 0 : $email_all);
		$simtk_allow_anon = ((!$simtk_allow_anon) ? 0 : $simtk_allow_anon);
		$use_resolution = ((!$use_resolution) ? 0 : $use_resolution);

		$res = db_query_params('UPDATE artifact_group_list SET
			name=$1,
			description=$2,
			email_all_updates=$3,
			email_address=$4,
			due_period=$5,
			status_timeout=$6,
			submit_instructions=$7,
			browse_instructions=$8,
			simtk_allow_anon=$9,
			simtk_use_resolution=$10
			WHERE group_artifact_id=$11 AND group_id=$12',
					 array (
						 htmlspecialchars($name),
						 htmlspecialchars($description),
						 $email_all,
						 $email_address,
						 $due_period * (60*60*24),
						 $status_timeout * (60*60*24),
						 htmlspecialchars($submit_instructions),
						 htmlspecialchars($browse_instructions),
						 $simtk_allow_anon,
						 $use_resolution,
						 $this->getID(),
						 $this->Group->getID()));

		if (!$res || db_affected_rows($res) < 1) {
			$this->setError('ArtifactType::Update(): '.db_error());
			return false;
		} else {
			$this->fetchData($this->getID());
			return true;
		}
	}

	/**
	 * getBrowseList - get the free-form string strings.
	 *
	 * @return	string	instructions.
	 */
	function getBrowseList() {
		$list = $this->data_array['browse_list'];

		// remove status_id in the browse list if a custom status exists
		if (count($this->getExtraFields(array(ARTIFACT_EXTRAFIELDTYPE_STATUS))) > 0) {
			$arr = explode(',', $list);
			$idx = array_search('status_id', $arr);
			if ($idx !== False) {
				array_splice($arr, $idx, 1);
			}
			return join(',', $arr);
		}

		return $list;
	}

	/**
	 * setCustomStatusField - set the extra_field_id of the field containing the custom status.
	 *
	 * @param	int	$list	The extra field id.
	 * @return	boolean	success.
	 */
	function setBrowseList($list) {
		$res = db_query_params('UPDATE artifact_group_list
		    SET browse_list=$1
			WHERE group_artifact_id=$2',
			array($list,
				$this->getID()));
		$this->fetchData($this->getID());
		return $res;
	}

	/**
	 * canVote - check whether the current user can vote on
	 *		items in this tracker
	 *
	 * @return	bool	true if they can
	 */
	function canVote() {
		return forge_check_perm('tracker', $this->getID(), 'vote');
	}

	/**
	 * getVoters - get IDs of users that may vote on
	 *		items in this tracker
	 *
	 * @return	array	list of user IDs
	 */
	function getVoters() {
		if ($this->voters !== false) {
			return $this->voters;
		}

		$this->voters = array();
		if (($engine = RBACEngine::getInstance())
			&& ($voters = $engine->getUsersByAllowedAction('tracker', $this->getID(), 'vote'))
			&& (count($voters) > 0)) {
			foreach ($voters as $voter) {
				$voter_id = $voter->getID();
				$this->voters[$voter_id] = $voter_id;
			}
		}
		return $this->voters;
	}

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
