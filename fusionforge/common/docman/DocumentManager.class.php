<?php
/**
 * DocumentManager.class.php
 *
 * FusionForge document manager
 *
 * Copyright 2011-2014,2016, Franck Villaume - TrivialDev
 * Copyright (C) 2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013, French Ministry of National Education
 * Copyright 2016-2019, Tod Hing - SimTK Team
 * http://fusionforge.org
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
require_once $gfcommon.'include/User.class.php';
require_once $gfcommon.'docman/DocumentGroup.class.php';

class DocumentManager extends FFError {

	/**
	 * Associative array of data from db.
	 *
	 * @var	 array	$data_array.
	 */
	var $data_array;

	/**
	 * The Group object.
	 *
	 * @var	object	$Group.
	 */
	var $Group;

	/**
	 * Constructor.
	 *
	 * @param	$Group
	 * @internal	param	\The $object Group object to which this document is associated.
	 * @return	\DocumentManager
	 */
	function __construct(&$Group) {
		parent::__construct();
		if (!$Group || !is_object($Group)) {
			$this->setError(_('No Valid Group Object'));
			return;
		}
		if ($Group->isError()) {
			$this->setError('DocumentManager:: '. $Group->getErrorMessage());
			return;
		}
		$this->Group =& $Group;
	}

	/**
	 * getGroup - get the Group object this Document is associated with.
	 *
	 * @return	Object	The Group object.
	 */
	function &getGroup() {
		return $this->Group;
	}

	/**
	 * getTrashID - the trash doc_group id for this DocumentManager.
	 *
	 * @return	integer	The trash doc_group id.
	 */
	function getTrashID() {
		if (isset($this->data_array['trashid']))
			return $this->data_array['trashid'];

		$res = db_query_params('SELECT doc_group from doc_groups
					WHERE groupname = $1
					AND group_id = $2
					AND stateid = $3',
					array('.trash', $this->Group->getID(), '2'));
		if (db_numrows($res) == 1) {
			$arr = db_fetch_array($res);
			$this->data_array['trashid'] = $arr['doc_group'];
			return $this->data_array['trashid'];
		} else {
			$dg = new DocumentGroup($this->Group);
			$dg->create('.trash');
			$dg->setStateID('2');
			return $dg->getID();
		}
	}

	/**
	 * cleanTrash - delete all items in trash for this DocumentManager
	 *
	 * @return	boolean	true on success
	 */
	function cleanTrash() {
		$trashId = $this->getTrashID();
		if ($trashId !== -1) {
			db_begin();
			$result = db_query_params('select docid FROM doc_data WHERE stateid=$1 and group_id=$2', array('2', $this->Group->getID()));
			$emptyFile = db_query_params('DELETE FROM doc_data WHERE stateid=$1 and group_id=$2', array('2', $this->Group->getID()));
			if (!$emptyFile) {
				db_rollback();
				return false;
			}
			$emptyDir = db_query_params('DELETE FROM doc_groups WHERE stateid=$1 and group_id=$2 and groupname !=$3', array('2', $this->Group->getID(), '.trash'));
			if (!$emptyDir) {
				db_rollback();
				return false;
			}
			while ($arr = db_fetch_array($result)) {
				DocumentStorage::instance()->delete($arr['docid'])->commit();
			}
			db_commit();
			return true;
		}
		return false;
	}

	/**
	 * isTrashEmpty - check if the trash is empty
	 * @return	boolean	success or not
	 */
	function isTrashEmpty() {
		$res = db_query_params('select ( select count(*) from doc_groups where group_id = $1 and stateid = 2 and groupname !=$2 )
					+ ( select count(*) from docdata_vw where group_id = $3 and stateid = 2 ) as c',
					array($this->Group->getID(), '.trash', $this->Group->getID()));
		if (!$res) {
			return false;
		}
		return (db_result($res, 0, 'c') == 0);
	}

	/**
	 *  getTree - display recursively the content of the doc_group. Only doc_groups within doc_groups.
	 *
	 * @param	int	$selecteddir	the selected directory
	 * @param	string	$linkmenu	the type of link in the menu
	 * @param	int	$docGroupId	the doc_group to start: default 0
	 */
	function getTree($selecteddir, $linkmenu, $docGroupId = 0) {
		global $g, $u, $dm; // the master group of all the groups .... anyway.
		$dg = new DocumentGroup($this->Group);
		switch ($linkmenu) {
			case "listtrashfile": {
				$stateId = 2;
				break;
			}
			default: {
				$stateId = 1;
				break;
			}
		}

		$subGroupIdArr = $dg->getSubgroup($docGroupId, $stateId);
		
		$cntSubGroup = sizeof($subGroupIdArr);
		if ($cntSubGroup) {
			echo "<!-- START Package --->"."\n";
			echo '<div class="download_package" style="background:none">'."\n";
			foreach ($subGroupIdArr as $subGroupIdValue) {
				$localDg = new DocumentGroup($this->Group, $subGroupIdValue);
				$liclass = 'docman_li_treecontent';
				if ($selecteddir == $localDg->getID()) {
					$liclass = 'docman_li_treecontent_selected';
				}
				if ($this->Group->getID() != $g->getID()) {
					$link = '/docman/?group_id='.$g->getID().'&amp;view='.$linkmenu.'&amp;dirid='.$localDg->getID().'&amp;childgroup_id='.$this->Group->getID();
				}
				else {
					$link = '/docman/?group_id='.$this->Group->getID().'&amp;view='.$linkmenu.'&amp;dirid='.$localDg->getID();
				}
				$nbDocsLabel = '';
				$nbDocs = $localDg->getNumberOfDocuments($stateId);
				if ($stateId == 1 && forge_check_perm('docman', $this->Group->getID(), 'approve')) {
					$nbDocsPending = $localDg->getNumberOfDocuments(3);
					$nbDocsHidden = $localDg->getNumberOfDocuments(4);
					$nbDocsPrivate = $localDg->getNumberOfDocuments(5);
				}

				if ($stateId == 2 && forge_check_perm('docman', $this->Group->getID(), 'approve')) {
					$nbDocsTrashed = $localDg->getNumberOfDocuments(2);
				}

				if ($nbDocs && (!isset($nbDocsPending) || $nbDocsPending == 0) && (!isset($nbDocsHidden) || $nbDocsHidden == 0) && (!isset($nbDocsPrivate) || $nbDocsPrivate) && (!isset($nbDocsTrashed) || $nbDocsTrashed)) {
					$nbDocsLabel = '<span title="'._('Number of documents in this folder').'" >('.$nbDocs.')</span>';
				}
				if (isset($nbDocsPending) && isset($nbDocsHidden) && isset($nbDocsPrivate)) {
					$nbDocsLabel = '<span title="'._('Number of documents in this folder per status. active/pending/hidden/private').'" >('.$nbDocs.'/'.$nbDocsPending.'/'.$nbDocsHidden.'/'.$nbDocsPrivate.')</span>';
				}
				if (isset($nbDocsTrashed)) {
					$nbDocsLabel = '<span title="'._('Number of deleted documents in this folder').'" >('.$nbDocsTrashed.')</span>';
				}
				if ($localDg->getName() != '.trash') {
					$lititle = '';
					if ($localDg->getCreated_by()) {
						$user = user_get_object($localDg->getCreated_by());
						$lititle .= _('Created by')._(': ').$user->getRealName();
					}
					if ($localDg->getLastModifyDate()) {
						if ($lititle) {
							$lititle .= _('; ');
						}
						$lititle .= _('Last Modified')._(': ').relative_date($localDg->getLastModifyDate());
					}
					
					$localDf = new DocumentFactory($this->Group);
					
					$localDf->setDocGroupID($localDg->getID());
					$dirid = $localDg->getID();
					
					$localDf->setStateID('1');
					$d_arr_active =& $localDf->getDocuments();				
					if ($d_arr_active != NULL)
						$localdocs = $d_arr_active;

					$localDf->setStateID('4');
					$d_arr_hidden =& $localDf->getDocuments();
					if (isset($localdocs) && $localdocs != NULL && $d_arr_hidden != NULL) {
						$localdocs = array_merge($localdocs, $d_arr_hidden);
					}
					elseif ($d_arr_hidden != NULL) {
						$localdocs = $d_arr_hidden;
					}

					$localDf->setStateID('5');
					$d_arr_private =& $localDf->getDocuments();
					if (isset($localdocs) && $localdocs != NULL && $d_arr_private != NULL) {
						$localdocs = array_merge($localdocs, $d_arr_private);
					}
					elseif ($d_arr_private != NULL) {
						$localdocs = $d_arr_private;
					}

					$nested_docs = array();

					$baseredirecturl = '/docman/?group_id='.$this->Group->getID();
					$redirecturl = $baseredirecturl.'&view='.$linkmenu.'&dirid='.$dirid;
					$actionlistfileurl = '?group_id='.$this->Group->getID().'&amp;view='.$linkmenu.'&amp;dirid='.$dirid;

					if (isset($localdocs)) {
						foreach ($localdocs as $doc) {
							$nested_docs[$doc->getDocGroupID()][] = $doc;
						}
					}
					$numFiles = $localDg->getNumberOfDocuments(1); 

					// Check whether there are documents to show for the project.
					//
					// NOTE: If "Uncategorized Submissions" is the only subgroup 
					// available to show and if it is empty too (i.e. there is not 
					// any other document available in the project), display the message
					// about no documents are present.
					// Also, skip and do not display the rest of this panel.
					if ($cntSubGroup === 1 &&
						$localDg->getName() == "Uncategorized Submissions" &&
						$numFiles == 0) {
						echo '<div class="content' . $localDg->getID() . 
							'">This project has no documents.</div>';
						continue;
					}

					echo "<!-- START Panel --->"."\n";

					echo '<div>'."\n";
					echo '<h2 class="panel-title"><a href="#" class="expander toggle" data-expander-target=".content'.$localDg->getID().'">'. $localDg->getName() . '</a></h2>'."\n";				

					echo '<div class="document_icons">'."\n";
					if (forge_check_perm('docman', $this->Group->getID(), 'approve')) {
						echo ' <a href="?view=editdocgroupv2&dirid='.$dirid.'&group_id='.$this->Group->getID().'" title="'._('Edit this folder').'" >'. html_image('docman/configure-directory.png',22,22,array('alt'=>'edit')). '</a> ';
						echo ' <a href="'.$actionlistfileurl.'&amp;action=trashdir" id="docman-trashdirectory" title="'._('Move this folder and his content to trash').'" >'. html_image('docman/trash-empty.png',22,22,array('alt'=>'trashdir')). '</a> ';
					}

					if ($numFiles) {
						echo '<a href="/docman/view.php/'.$this->Group->getID().'/zip/full/'.$localDg->getID().'" title="'. _('Download this folder as a ZIP') . '" >' . html_image('docman/download-directory-zip.png',22,22,array('alt'=>'downloadaszip')). '</a>';
					}

					if (session_loggedin()) {
						if ($localDg->isMonitoredBy($u->getID())) {
							$option = 'remove';
							$titleMonitor = _('Stop monitoring this folder');
						}
						else {
							$option = 'add';
							$titleMonitor = _('Start monitoring this folder');
						}
						echo '<a class="tabtitle-ne" href="'.$actionlistfileurl.'&amp;action=monitordirectory&amp;option='.$option.'&amp;directoryid='.$localDg->getID().'" title="'.$titleMonitor.'" >'.html_image('docman/monitor-'.$option.'document.png',22,22,array('alt'=>$titleMonitor)). '</a>';
					}

					if (forge_check_perm('docman', $localDg->getID(), 'approve')) {
						echo '<div id="editdocgroup" style="display:none;">';
						echo '<h4 class="docman_h4">'. _('Edit this folder') .'</h4>';
						//include ($gfcommon.'docman/views/editdocgroup.php');
						echo '</div>';
					}
					if (forge_check_perm('docman', $localDg->getID(), 'submit')) {
						echo '<div id="additem" style="display:none">';
						//include ($gfcommon.'docman/views/additem.php');
						echo '</div>';
					}

					echo '</div>'."\n";
					echo '<div style="clear:both"></div>'."\n";
					echo '<div class="download_border"></div>'."\n";

					if (!$numFiles && (!$dg->getSubgroup($subGroupIdValue, $stateId))) {
						echo '<div class="content'.$localDg->getID().'">This folder is empty.';
					}
					else {
						echo '<div class="content'.$localDg->getID().'">';
					}

					if (isset($nested_docs[$dirid])) {
						foreach ($nested_docs[$dirid] as $d) {
							echo '<div class="content'.$dirid.'">';

							switch ($d->getFileType()) {
								case "URL": {
									$docurl = $d->getFileName();
									$docurltitle = $d->getName();
									break;
								}
								default: {
									$docurl = util_make_uri('/docman/view.php/'.$d->Group->getID().'/'.$d->getID().'/'.urlencode($d->getFileName()));
									$docurltitle = $d->getName();
								}
							}

							$admininfo = "";
							if (forge_check_perm('docman', $this->Group->getID(), 'approve')) {
								if ($d->getFileType() == "URL") {
									$admininfo = " (URL)";
								}
								else {
									$admininfo = " (Doc)";
								}
								if ($d->getStateID() == 4) {
									$admininfo .= " (Hidden)";
								}
								if ($d->getStateID() == 5) {
									$admininfo .= " (Private)";
								}
							}

							echo '<!-- START Item --->'."\n";
							echo '<div class="document_link"><a href="'.$docurl.'">'.$docurltitle.$admininfo;
							echo '</a></div>'."\n";

							echo '<div class="document_icons2">'."\n";

							if (forge_check_perm('docman', $d->Group->getID(), 'approve')) {
								$editfileaction = '?action=editfile&amp;view=listfile&amp;dirid='.$d->getDocGroupID();
								if (isset($GLOBALS['childgroup_id']) && $GLOBALS['childgroup_id']) {
									$editfileaction .= '&amp;childgroup_id='.$GLOBALS['childgroup_id'];
								}
								$editfileaction .= '&amp;group_id='.$GLOBALS['group_id'];
								$editfileaction = '?group_id='.$this->Group->getID().'&amp;view=edititem&amp;dirid='.$dirid;
								echo ' <a href="'.$actionlistfileurl.'&amp;action=trashfile&amp;fileid='.$d->getID().'" title="'. _('Move this document to trash') .'" >'.html_image('docman/trash-empty.png',22,22,array('alt'=>_('Move this document to trash'))). '</a> ';
								echo ' <a href="'.$editfileaction.'&amp;fileid='.$d->getID().'" title="'. _('Edit this document') .'" >'.html_image('docman/edit-file.png',22,22,array('alt'=>_('Edit this document'))). '</a> ';
							}

							if (session_loggedin()) {
								if ($d->isMonitoredBy($u->getID())) {
									$option = 'remove';
									$titleMonitor = _('Stop monitoring this document');
								}
								else {
									$option = 'add';
									$titleMonitor = _('Start monitoring this document');
								}
								echo '<a href="'.$actionlistfileurl.'&amp;action=monitorfile&amp;option='.$option.'&amp;fileid='.$d->getID().'" title="'.$titleMonitor.'" >'.html_image('docman/monitor-'.$option.'document.png',22,22,array('alt'=>$titleMonitor)). '</a>';
							}

							echo '</div>'."\n";
							echo '<div style="clear:both"></div>'."\n";

							if ( $d->getUpdated() ) {
								echo '<div class="document_details">' . date(_('M n, Y'), $d->getUpdated()) . "</div>"."\n";
							}
							else {
								echo '<div class="document_details">' . date(_('M n, Y'), $d->getCreated()) . "</div>"."\n";
							}
							echo '<div class="document_details">Author: ' . make_user_link($d->getCreatorUserName(), $d->getCreatorRealName()) . "</div>"."\n";
							echo '<div class="download_text">' . $d->getDescription() . '</div>'."\n";
							echo '<!-- END Item --->'."\n";

							echo '</div><br />'."\n";
						} // end of foreach
					} // end of if (isset($nested_docs[$dirid]))...
				}
				else {
					echo '<li id="leaf-'.$subGroupIdValue.'" class="'.$liclass.'">'.util_make_link($link, $localDg->getName()).$nbDocsLabel;
				}
				if ($dg->getSubgroup($subGroupIdValue, $stateId)) {
					$this->getTree($selecteddir, $linkmenu, $subGroupIdValue);
				}
				echo '</div>'."\n";
				echo '</div>'."\n";
				echo '<!-- END Panel --->'."\n";
			}
			echo "</div>"."\n";
			echo "<!-- END Package --->"."\n";
		}
		else {
			// No documents are present.
			echo '<div class="content">This project has no documents.</div>';
		}
	}

	/**
	 * getStatusNameList - get all status for documents
	 *
	 * @param	string	$format		format of the return values. json returns : { name: id, }. Default is DB object.
	 * @param	string	$removedval	skipped status id
	 * @return resource|string
	 */
	function getStatusNameList($format = '', $removedval = '') {
                //echo "val: " . $removedval;
		if (!empty($removedval)) {
			$stateQuery = db_query_params('select * from doc_states where stateid not in ($1) order by stateid', array($removedval));
                        // debugging
                        //echo "q: " . $stateQuery;
		} else {
			$stateQuery = db_query_params('select * from doc_states order by stateid', array());
		}
		switch ($format) {
			case 'json': {
				$returnString = '{';
				while ($stateArr = db_fetch_array($stateQuery)) {
					$returnString .= util_html_secure($stateArr['name']).': \''.$stateArr['stateid'].'\',';
				}
				$returnString .= '}';
				return $returnString;
				break;
			}
			default: {
				return $stateQuery;
			}
		}
	}

	/**
	 * getDocGroupList - Returns as a string used in javascript the list of available folders
	 *
	 * @param	$nested_groups
	 * @param	string		must be json which is wrong, this function does not return any json object
	 * @param	bool		allow the "None" which is the "/"
	 * @param	int		the selected folder id
	 * @param	array		folders id to not display
	 * @return	string
	 */
	function getDocGroupList($nested_groups, $format = '', $allow_none = true, $selected_id = 0, $dont_display = array()) {
		$id_array = array();
		$text_array = array();
		$this->buildArrays($nested_groups, $id_array, $text_array, $dont_display);
		$rows = count($id_array);
		switch ($format) {
			case "json": {
				$returnString = '{';
				for ($i=0; $i<$rows; $i++) {
					$returnString .= '\''.util_html_secure(addslashes($text_array[$i])).'\':'.$id_array[$i].',';
				}
				$returnString .= '}';
				break;
			}
		}
		return $returnString;
	}

	/**
	 * buildArrays - Build the arrays to call html_build_select_box_from_arrays()
	 *
	 * @param	array	Array of groups.
	 * @param	array	Reference to the array of ids that will be build
	 * @param	array	Reference to the array of group names
	 * @param	array	Array of IDs of groups that should not be displayed
	 * @param	int	The ID of the parent whose childs are being showed (0 for root groups)
	 * @param	int	The current level
	 */
	function buildArrays($group_arr, &$id_array, &$text_array, &$dont_display, $parent = 0, $level = 0) {
		if (!is_array($group_arr) || !array_key_exists("$parent", $group_arr)) return;

		$child_count = count($group_arr["$parent"]);
		for ($i = 0; $i < $child_count; $i++) {
			$doc_group =& $group_arr["$parent"][$i];

			// Should we display this element?
			if (in_array($doc_group->getID(), $dont_display)) continue;

			$margin = str_repeat("--", $level);

			$id_array[] = $doc_group->getID();
			$text_array[] = $margin.$doc_group->getName();

			// Show childs (if any)
			$this->buildArrays($group_arr, $id_array, $text_array, $dont_display, $doc_group->getID(), $level+1);
		}
	}

	/**
	 * getActivity - return the number of searched actions per sections between two dates
	 *
	 * @param	array	Sections to search for activity
	 * @param	int	the start date time format time()
	 * @param	int	the end date time format time()
	 * @return	array	number per section of activities found between begin and end values
	 */
	function getActivity($sections, $begin, $end) {
		$qpa = db_construct_qpa(false);
		for ($i = 0; $i < count($sections); $i++) {
			$union = 0;
			if (count($sections) >= 1 && $i != count($sections) -1) {
				$union = 1;
			}
			$qpa = db_construct_qpa($qpa, 'SELECT count(*) FROM activity_vw WHERE activity_date BETWEEN $1 AND $2
			AND group_id = $3 AND section = $4 ',
			array($begin,
				$end,
				$this->getGroup()->getID(),
				$sections[$i]));
			if ($union) {
				$qpa = db_construct_qpa($qpa, ' UNION ALL ', array());
			}
		}
		$res = db_query_qpa($qpa);
		$results = array();
		$j = 0;
		while ($arr = db_fetch_array($res)) {
			$results[$sections[$j]] = $arr['0'];
			$j++;
		}
		return $results;
	}
}
