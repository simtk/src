<?php
/** 
 * edititem.php
 *
 * FusionForge Documentation Manager
 *
 * Copyright 2012-2013, Franck Villaume - TrivialDev
 * Copyright 2016-2022, SimTK Team
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

global $g; //group object
global $group_id; // id of the group
global $dm;

//echo "groupid: " . $group_id . "<br />";


$statusResult = $dm->getStatusNameList("","3");

$docid = getIntFromRequest('fileid');
$fromview = htmlspecialchars(getStringFromRequest('fromview'));
//echo "docid: " . $docid . "<br />";
$d = new Document($g, $docid);

//echo 'statusId:'.$d->getStateID() . "<br />";
//echo 'docgroupId:'.$d->getDocGroupID() . "<br />";
$dgf = new DocumentGroupFactory($g);
$dgf_groups = $dgf->getDocGroups();

if (!forge_check_perm('docman', $group_id, 'approve')) {
	$return_msg= _('Document Manager Access Denied');
	session_redirect('/docman/?group_id='.$group_id.'&warning_msg='.urlencode($return_msg));
}

?>

<div class="du_warning_msg"></div>
<script src='/frs/admin/handlerDiskUsage.js'></script>

<script>
// Handle Update button click.
function handlerSubmit(groupId) {
	// Check disk usage.
	if (!handlerDiskUsage(groupId)) {
		// Disk usage exceeded quota. Do not proceed.
		event.preventDefault();
		return;
	}
}
</script>

<?php

echo '<div id="editFile" >';
echo '<form id="editdocdata" name="editdocdata" action="?group_id='.$group_id.'&amp;action=editfile&amp;fromview=listfile"  method="post" enctype="multipart/form-data">';
echo '<table>';
echo '	<tr>';
echo '		<td><strong>'. _('Document Title')._(': ').'</strong>'. utils_requiredField() .'<br />';
echo '		<input pattern=".{5,}" title="'.sprintf(_('(at least %s characters)'), 5).'" id="title" type="text" name="title" size="40" maxlength="255" value="'.$d->getName().'"/></td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><strong>'. _('Description')._(': ').'</strong>'. utils_requiredField() .'<br />';
echo '		<input pattern=".{10,}" title="'.sprintf(_('(at least %s characters)'), 10).'" id="description" type="text" name="description" size="40" maxlength="255" value="'.$d->getDescription().'"/></td>';
echo '	</tr>';
if ($g->useDocmanSearch()) {
	echo '	<tr>';
	echo '		<td>'. _('Both fields are used by the document search engine.') .'</td>';
	echo '	</tr>';
}
echo '	<tr>';
echo '		<td><strong>'. _('Citation')._(': ').'</strong><br />';
echo '		<input pattern=".{10,}" title="'.sprintf(_('(at least %s characters)'), 10).'" id="citation" type="text" name="citation" size="40" maxlength="255" value="'.$d->getCitation().'"/></td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><strong>'. _('File')._(': ').'</strong>';
echo '			<a id="filelink"></a>';
echo '		</td>';
echo '	</tr>';
if ($g->useCreateOnline()) {
	echo '	<tr id="editonlineroweditfile" >';
	echo '		<td>'. _('Edit the contents to your desire or leave them as they are to remain unmodified.') .'<br />';
	echo '			<textarea id="defaulteditzone" name="details" rows="15" cols="70"></textarea><br />';
	echo '			<input id="defaulteditfiletype" type="hidden" name="filetype" value="text/plain" />';
	echo '			<input id="editor" type="hidden" name="editor" value="online" />';
	echo '		</td>';
	echo '	</tr>';
}
echo '	<tr>';
echo '		<td><strong>'. _('Folder that document belongs to.:') .'</strong><br />';
echo '			<select name="doc_group" id="doc_group">';
                while($row = db_fetch_array($dgf_groups)) {
                   echo '<option value="'.$row['doc_group'].'"';
				   if ($row['doc_group'] == $d->getDocGroupID()) { echo "selected";}
				   echo '>'.$row['groupname']."</option>";
                }
echo '			</select>';		
			
echo '		</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><strong>'. _('State')._(': ').'</strong><br />';
echo '			<select name="stateid" id="stateid">';
                while($row = db_fetch_array($statusResult)) {
                   echo '<option value="'.$row['stateid'].'"';
				   if ($row['stateid'] == $d->getStateID()) { echo "selected";}
				   echo '>'.$row['name']."</option>";
                }
echo '			</select>';				
echo '		</td>';
echo '	</tr>';
if ($d->isURL()) {
echo '	<tr id="fileurlroweditfile">';
echo '		<td><strong>'. _('Specify an new URL where the file will be referenced:') .'</strong>'. utils_requiredField() .'<br />';
echo '			<input id="fileurl" type="url" name="file_url" size="50" value="'.$d->getFileName().'" pattern="ftp://.+|https?://.+" />';
echo '		</td>';
echo '	</tr>';
} else {
echo '	<tr id="uploadnewroweditfile">';
echo '		<td><strong>'. _('OPTIONAL: Upload new file:') .'</strong><br />';
echo '			<input type="file" name="uploaded_data" /><br />'.sprintf(_('(max upload size: %s)'),human_readable_bytes(util_get_maxuploadfilesize()));
echo '		</td>';
echo '	</tr>';
}
echo '</table>';
echo '<br />';
echo '<input type="hidden" id="docid" name="docid" value="'.$docid.'"/>';
echo '<input type="hidden" id="fromview" name="fromview" value="'.$fromview.'"/>';
echo '<input type="submit" name="submit" class="btn-cta" ' .
	'onclick="handlerSubmit(' . $group_id . ')" ' .
	'value="'._('Update').'" />';
echo '</form>';
echo '</div>';
