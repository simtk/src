<?php

/**
 *
 * wizard.php
 * 
 * Project administration wizard.
 *
 * Copyright 2005-2019, SimTK Team
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
 
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfcommon.'include/GroupJoinRequest.class.php';

$group_id = getIntFromRequest('group_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$group->clearError();

// If this was a submission, make updates
if (getStringFromRequest('submit')) {
	$form_group_name = getStringFromRequest('form_group_name');
	$form_shortdesc = getStringFromRequest('form_shortdesc');
	$form_summary = getStringFromRequest('form_summary');
	$form_download_description = getStringFromRequest('form_download_description');
	$form_homepage = getStringFromRequest('form_homepage');
	$logo_image_id = getIntFromRequest('logo_image_id');
	$use_mail = getStringFromRequest('use_mail');
	$use_survey = getStringFromRequest('use_survey');
	$use_forum = getStringFromRequest('use_forum');
	$use_pm = getStringFromRequest('use_pm');
	$use_scm = getStringFromRequest('use_scm');
	$use_news = getStringFromRequest('use_news');
	$use_docman = getStringFromRequest('use_docman');
	$use_ftp = getStringFromRequest('use_ftp');
	$use_tracker = getStringFromRequest('use_tracker');
	$use_frs = getStringFromRequest('use_frs');
	$use_stats = getStringFromRequest('use_stats');
	$use_activity = getStringFromRequest('use_activity');
	$tags = getStringFromRequest('form_tags');
	$addTags = getArrayFromRequest('addTags');
	$new_doc_address = getStringFromRequest('new_doc_address');
	$send_all_docs = getStringFromRequest('send_all_docs');
//        $logofile = getUploadedFile('logofile');

	if (trim($tags) != "") {
		$tags .= ",";
	}
	$tags .= implode(",", $addTags);

        //handle logofile
        $logo_tmpfile = $_FILES['logofile']['tmp_name'];
        $logo_type = $_FILES['logofile']['type'];

	$res = $group->update(
		session_get_user(),
		$form_group_name,
		$form_homepage,
		$form_shortdesc,
		$use_mail,
		$use_survey,
		$use_forum,
		$use_pm,
		1,
		$use_scm,
		$use_news,
		$use_docman,
		$new_doc_address,
		$send_all_docs,
		100,
		$use_ftp,
		$use_tracker,
		$use_frs,
		$use_stats,
		$tags,
		$use_activity,
		0,
                $logo_tmpfile,
                $logo_type,
                $form_summary,
                $form_download_description
	);

        //
/*
         if (!is_uploaded_file($uploaded_data['tmp_name'])) {
                $return_msg = _('Invalid file name.');
                        session_redirect($baseurl.'&error_msg='.urlencode($return_msg));
                }

                if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $uploaded_data_type = finfo_file($finfo, $uploaded_data['tmp_name']);
                        if( $uploaded_data_type === 'application/msword') {
                                $ext = pathinfo($uploaded_data['name'], PATHINFO_EXTENSION);
                                if ( $ext === 'ppt' ) {
                                        $uploaded_data_type = 'application/vnd.ms-powerpoint';
                                } elseif ( $ext === 'xls' ) {
                                        $uploaded_data_type = 'application/vnd.ms-excel';
                                }
                        }
                } else {
                        $uploaded_data_type = $uploaded_data['type'];
                }
                if ($uploaded_data_type == 'application/octet-stream' && $uploaded_data_type != $uploaded_data['type']) {
                        $uploaded_data_type = $uploaded_data['type'];
                }
                $data = $uploaded_data['tmp_name'];
                $file_url = '';
                $uploaded_data_name = $uploaded_data['name'];   
*/

	//100 $logo_image_id

	if (!$res) {
		$error_msg .= $group->getErrorMessage();
	} else {
		$feedback .= _('Project information updated');
	}

        if (getStringFromRequest('wizard')) {
           header("Location: wizard_end.php?group_id=$group_id&wizard=1");
        }


}

project_admin_header(array('title'=>sprintf(_('Admin for %s'), $group->getPublicName()),'group'=>$group->getID()));
?>

<table class="my-layout-table">
	<tr>
		<td>

<?php 

echo $HTML->boxTop(_('Project Setup is now Complete.'));
?>


<?php
plugin_hook('hierarchy_views', array($group_id, 'admin'));

echo $HTML->boxBottom();?>

		</td>
	</tr>
</table>

<?php

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
