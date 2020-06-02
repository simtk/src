<?php

/**
 *
 * wizard_summary.php
 * 
 * Project administration summary wizard.
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
                $form_summary,
                $form_download_description,
                $logo_tmpfile,
                $logo_type
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
           header("Location: category.php?group_id=$group_id&wizard=1");
        }

}

project_admin_header(array('title'=>sprintf(_('Project Information for %s'), $group->getPublicName()),'group'=>$group->getID()));
?>

<table class="my-layout-table">
	<tr>
		<td>

<?php 

echo $HTML->boxTop(_('Continue Project Setup - Summary'));

/*

if (forge_get_config('use_shell')) {
?>
<p><?php echo _('Group shell (SSH) server:') ?> <strong><?php echo $group->getUnixName().'.'.forge_get_config('web_host'); ?></strong></p>
<p><?php echo _('Group directory on shell server:') ?><br/><strong><?php echo account_group_homedir($group->getUnixName()); ?></strong></p>
<p><?php echo _('Project WWW directory on shell server:') ?><br /><strong><?php echo account_group_homedir($group->getUnixName()).'/htdocs'; ?></strong></p>
<?php
	} //end of use_shell condition
*/
?>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post" enctype="multipart/form-data">

<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<?php if (getStringFromRequest('wizard')) { ?>
  <input type="hidden" name="wizard" value="1" />
<?php } ?>

<h2><?php echo _('Project Title'); ?></h2>
<p>
<input type="text" name="form_group_name" value="<?php echo $group->getPublicName(); ?>" size="40" maxlength="40" />
</p>

<h2><?php echo _('Description'); ?></h2>
<p>
<?php //echo _('HTML will be stripped from this description'); ?>
</p>
<p>
<textarea cols="80" rows="3" name="form_shortdesc">
<?php echo $group->getDescription(); ?>
</textarea>
</p>

<h2><?php echo _('Summary'); ?></h2>
<p>
<textarea cols="80" rows="3" name="form_summary">
<?php echo $group->getSummary(); ?>
</textarea>
</p>

<!--
<h2><?php echo _('Download Description'); ?></h2>
<p>
<textarea cols="80" rows="3" name="form_download_description">
<?php echo $group->getDownloadDescription(); ?>
</textarea>
</p>

<?php if (forge_get_config('use_project_tags')) { ?>
<h2><?php echo _('Project tags'); ?></h2>
<p>
<?php echo _('Add tags (use comma as separator): ') ?><br />
<input type="text" name="form_tags" size="100" value="<?php echo $group->getTags(); ?>" />
</p>
<?php
	$infos = getAllProjectTags();
	if ($infos) {
		echo '<br />';
		echo _('Or pick a tag from those used by other projects: ');
		echo '<br />';
		echo '<table width="100%"><thead><tr>';
		echo '<th>'._('Tags').'</th>';
		echo '<th>'._('Projects').'</th>';
		echo '</tr></thead><tbody>';

		$unix_name = $group->getUnixName();
		foreach ($infos as $tag => $plist) {
			$disabled = '';
			$links = array();
			foreach($plist as $project) {
				$links[] = util_make_link('/projects/'.$project['unix_group_name'].'/',$project['unix_group_name']);
				if ($project['group_id'] == $group_id) {
					$disabled = ' disabled="disabled"';
				}
			}

			echo '<tr>';
			echo '<td><input type="checkbox" name="addTags[]" value="'.$tag.'"'.$disabled.' /> ';
			if ($disabled) {
				echo '<s>'.$tag.'</s>';
			} else {
				echo $tag;
			}
			echo '</td>';
			echo '<td>'.implode(' ', $links).'</td>' ;
			echo '</tr>' ;
		}
		echo '</tbody></table>' ;
	}
} ?>

<h2><?php echo _('Trove Categorization'); ?></h2>
<p>
<a href="/project/admin/group_trove.php?group_id=<?php echo $group->getID(); ?>">[<?php echo _('Edit Trove'); ?>]</a>
</p>

<h2><?php echo _('Homepage Link') ?></h2>
<p>
<input type="text" name="form_homepage" size="100" value="<?php echo $group->getHomePage(); ?>" />
</p>
--->

<input type="hidden" name="form_homepage" size="100" value="<?php echo $group->getHomePage(); ?>" />

<h2><?php echo _('Privacy') ?></h2>
<p>
<input type="checkbox" name="private" value="1" /> Keep project private.
</p>

<h2><?php echo _('Image') ?></h2>
<p>
<input type="file" name="logofile" />
</p>

<?php
// This function is used to render checkboxes below
function c($v) {
	if ($v) {
		return 'checked="checked"';
	} else {
		return '';
	}
}
?>

<?php
if(forge_get_config('use_mail')) {
?>
<input type="hidden" name="use_mail" value="<?php echo ($group->usesMail() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_survey')) {
?>
<input type="hidden" name="use_survey" value="<?php echo ($group->usesSurvey() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_activity')) {
?>
<input type="hidden" name="use_activity" value="<?php echo ($group->usesActivity() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_forum')) {
?>
<input type="hidden" name="use_forum" value="<?php echo ($group->usesForum() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_pm')) {
?>
<input type="hidden" name="use_pm" value="<?php echo ($group->usesPM() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_scm')) {
?>
<input type="hidden" name="use_scm" value="<?php echo ($group->usesSCM() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_news')) {
?>
<input type="hidden" name="use_news" value="<?php echo ($group->usesNews() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_docman')) {
?>
<input type="hidden" name="use_docman" value="<?php echo ($group->usesDocman() ? '1' : '0'); ?>" />
<p>

<!--
<?php echo _('If you wish, you can provide default email addresses to which new submissions will be sent') ?>.<br />
<strong><?php echo _('New Document Submissions')._(':'); ?></strong><br />
<input type="email" name="new_doc_address" value="<?php echo $group->getDocEmailAddress(); ?>" size="40" maxlength="250" />
<?php echo _('(send on all updates)') ?>
<input type="checkbox" name="send_all_docs" value="1" <?php echo c($group->docEmailAll()); ?> />
</p>
-->

<?php
}

if(forge_get_config('use_ftp')) {
?>
<input type="hidden" name="use_ftp" value="<?php echo ($group->usesFTP() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_tracker')) {
?>
<input type="hidden" name="use_tracker" value="<?php echo ($group->usesTracker() ? '1' : '0'); ?>" />
<?php
}

if(forge_get_config('use_frs')) {
?>
<input type="hidden" name="use_frs" value="<?php echo ($group->usesFRS() ? '1' : '0'); ?>" />
<?php } ?>

<input type="hidden" name="use_stats" value="<?php echo ($group->usesStats() ? '1' : '0'); ?>" />

<p>
<input type="submit" name="submit" value="<?php echo _('Save and Continue') ?>" />
</p>

</form>

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
