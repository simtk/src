<?php
/**
 * FusionForge Documentation Manager
 *
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright 2012-2014, Franck Villaume - TrivialDev
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

/* please do not add require here : use www/docman/index.php to add require */
/* global variables used */
global $group_id; // id of the group
global $g; // the group object
global $dirid; // id of doc_group
global $HTML; // Layout object
global $nested_pending_docs;
global $nested_groups;
global $redirecturl; // built url from listfile.php (handle the hierarchy)
global $actionlistfileurl; // built action url from listfile.php (handle the hierarchy)

//echo "dirid: " . $dirid . "<br />";

$df = new DocumentFactory($g);
$df->setStateID('3');
$d_pending_arr =& $df->getDocuments();

if (!forge_check_perm('docman', $g->getID(), 'approve')) {
	$return_msg= _('Document Manager Access Denied');
	session_redirect($redirecturl.'&warning_msg='.urlencode($return_msg));
}

echo '<h4>'._('Pending files').'</h4>';

if (empty($d_pending_arr)) {
	echo '<p class="information">'._('No pending documents.').'</p>';
} else {

		echo "<!-- START Package --->"."\n";
        echo '<div class="download_package" style="background:none">'."\n";
		foreach ($d_pending_arr as $d) {
			
			switch ($d->getFileType()) {
				case "URL": {
					$docurl = $d->getFileName();
					$docurltitle = $d->getName();
					break;
				}
				default: {
					$docurl = util_make_uri('/docman/view.php/'.$g->getID().'/'.$d->getID().'/'.urlencode($d->getFileName()));
					$docurltitle =  $d->getName();
				}
			}
			echo '<div class="document_link"><a href="'.$docurl.'" title="'.$docurltitle.'" >'.$docurltitle;
			echo '</a></div>';
			
			echo '<div class="document_icons2">'."\n";
			
            			
			if (forge_check_perm('docman', $group_id, 'approve')) {
			   $editfileaction = '?group_id='.$group_id.'&amp;view=edititem&amp;fromview=admin&amp;dirid='.$d->getDocGroupID();	              
			   //echo ' <a href="'.$actionlistfileurl.'&amp;action=trashfile&amp;fileid='.$d->getID().'" title="'. _('Move this document to trash') .'" >'.html_image('docman/trash-empty.png',22,22,array('alt'=>_('Move this document to trash'))). '</a> ';
               echo ' <a href="'.$editfileaction.'&amp;fileid='.$d->getID().'" title="'. _('Edit this document') .'" >'.html_image('docman/edit-file.png',22,22,array('alt'=>_('Edit this document'))). '</a> ';
					     
			}
			
			
			echo '</div>'."\n";
		    echo '<div style="clear:both"></div>'."\n";
					   
			if ( $d->getUpdated() ) {
			   echo '<div class="document_details">' . date(_('M n, Y'), $d->getUpdated()) . "</div>"."\n";
		    } else {
			   echo '<div class="document_details">' . date(_('M n, Y'), $d->getCreated()) . "</div>"."\n";
		    }
		    echo '<div class="document_details">Author: ' . make_user_link($d->getCreatorUserName(), $d->getCreatorRealName()) . "</div>"."\n";
			echo '<div class="download_text">' . $d->getDescription() . '</div>'."\n";
					   
			echo '<!-- END Item --->'."\n";
			echo "<br />";
					   
			/*
			$editfileaction = '?action=editfile&amp;fromview=listfile&amp;dirid='.$d->getDocGroupID();
			if (isset($GLOBALS['childgroup_id']) && $GLOBALS['childgroup_id']) {
				$editfileaction .= '&amp;childgroup_id='.$GLOBALS['childgroup_id'];
			}
			$editfileaction .= '&amp;group_id='.$GLOBALS['group_id'];
			echo '<a class="tabtitle-ne" href="#" onclick="javascript:controllerListPending.toggleEditFileView({action:\''.$editfileaction.'\', lockIntervalDelay: 60000, childGroupId: '.util_ifsetor($childgroup_id, 0).' ,id:'.$d->getID().', groupId:'.$d->Group->getID().', docgroupId:'.$d->getDocGroupID().', statusId:'.$d->getStateID().', statusDict:'.$dm->getStatusNameList('json').', docgroupDict:'.$dm->getDocGroupList($nested_groups, 'json').', title:\''.htmlspecialchars($d->getName()).'\', filename:\''.$d->getFilename().'\', description:\''.htmlspecialchars($d->getDescription()).'\', isURL:\''.$d->isURL().'\', isText:\''.$d->isText().'\', useCreateOnline:'.$d->Group->useCreateOnline().', docManURL:\''.util_make_uri("docman").'\'})" title="'. _('Edit this document') .'" >'.html_image('docman/edit-file.png', 22, 22, array('alt'=>_('Edit this document'))). '</a>';
			echo '<a class="tabtitle" href="#" onclick="window.location.href=\''.$actionlistfileurl.'&action=validatefile&fileid='.$d->getID().'\'" title="'. _('Activate in this folder') . '" >' . html_image('docman/validate.png', 22, 22, array('alt'=>'Activate in this folder')). '</a>';
			*/
			
		}
		echo "</div>"."\n";
        echo "<!-- END Package --->"."\n";
}
