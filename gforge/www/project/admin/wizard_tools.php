<?php

/**
 *
 * wizard_tools.php
 * 
 * Project administiration wizard tools.
 *
 * Copyright 2005-2016, SimTK Team
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
require_once $gfwww.'project/admin/project_admin_utils.php';

$group_id = getIntFromRequest('group_id');
session_require_perm('project_admin', $group_id);
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_error(_('Error creating group'), 'admin');
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'admin');
}

// If this was a submission, make updates
if (getStringFromRequest('submit')) {
	$form_group_name = getStringFromRequest('form_group_name');
	$form_shortdesc = getStringFromRequest('form_shortdesc');
	$form_homepage = getStringFromRequest('form_homepage');
	$form_summary = getStringFromRequest('form_summary');
	$form_downloaddescription = getStringFromRequest('form_downloaddescription');
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
	$new_doc_address = getStringFromRequest('new_doc_address');
	$send_all_docs = getStringFromRequest('send_all_docs');

        $logo_tempfile = "";
        $logo_type = "";

	$res = $group->update(
		session_get_user(),
		$form_group_name,
		$form_homepage,
		$form_shortdesc,
                $form_summary,
                $form_downloaddescription,
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
                $logo_tempfile,
                $logo_type,
		$group->isPublic()
	);

	if (!$res) {
		$error_msg = $group->getErrorMessage();
		$group->clearError();
	} else {
		// This is done so plugins can enable/disable themselves from the project
		$hookParams['group'] = $group_id;
		if (!plugin_hook("groupisactivecheckboxpost", $hookParams)) {
			if ($group->isError()) {
				$error_msg = $group->getErrorMessage();
				$group->clearError();
			} else {
				$error_msg = _('At least one plugin does not initialize correctly');
			}
		}
	}

	if (empty($error_msg)) {
		$feedback = _('Project information updated');
	}
        if (getStringFromRequest('wizard')) {
           header("Location: wizard_summary.php?group_id=$group_id&wizard=1");
        }

}

project_admin_header(array('title'=>sprintf(_('Tools for %s'), $group->getPublicName()),
						   'group'=>$group->getID()));

echo '<table class="fullwidth">';
echo '<tr class="top">';
echo '<td class="halfwidth">';

echo $HTML->boxTop(_('Continue Project Setup - Tools').'');
?>


<p>This section allows you to enable or disable tools that are available for your project.
</p>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<input type="hidden" name="wizard" value="1" />
<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<input type="hidden" name="form_group_name" value="<?php echo $group->getPublicName(); ?>" />
<input type="hidden" name="form_shortdesc" value="<?php echo $group->getDescription(); ?>" />
<input type="hidden" name="form_summary" value="<?php echo $group->getSummary(); ?>" />
<input type="hidden" name="form_downloaddescription" value="<?php echo $group->getDownloadDescription(); ?>" />
<input type="hidden" name="form_tags" size="100" value="<?php echo $group->getTags(); ?>" />
<input type="hidden" name="form_homepage" size="100" value="<?php echo $group->getHomePage(); ?>" />

<?php

// This function is used to render checkboxes below
function c($v) {
	if ($v) {
		return 'checked="checked"';
	} else {
		return '';
	}
}

/*
	Show the options that this project is using
*/

?>

<table>
<?php
if(forge_get_config('use_activity')) {
?>
<tr>
<td>
<input type="checkbox" name="use_activity" value="1" <?php echo c($group->usesActivity()); ?> />
</td>
<td>
<strong><?php echo _('Use Project Activity') ?></strong>
</td>
</tr>
<?php
}

if(forge_get_config('use_forum')) {
?>
<tr>
<td>
<input type="checkbox" name="use_forum" value="1" <?php echo c($group->usesForum()); ?> />
</td>
<td>
<strong><?php echo _('Use Forums') ?></strong>
</td>
</tr>
<?php
}

/*
if(forge_get_config('use_tracker')) {
?>
<tr>
<td>
<input type="checkbox" name="use_tracker" value="1" <?php echo c($group->usesTracker()); ?> />
</td>
<td>
<strong><?php echo _('Use Trackers') ?></strong>
</td>
</tr>
<?php
}
*/

if(forge_get_config('use_mail')) {
?>
<tr>
<td>
<input type="checkbox" name="use_mail" value="1" <?php echo c($group->usesMail()); ?> />
</td>
<td>
<strong><?php echo _('Use Mailing Lists') ?></strong>
</td>
</tr>
<?php
}

if(forge_get_config('use_pm')) {
?>
<tr>
<td>
<input type="checkbox" name="use_pm" value="1" <?php echo c($group->usesPM()); ?> />
</td>
<td>
<strong><?php echo _('Use Tasks') ?></strong>
</td>
</tr>
<?php
}

if(forge_get_config('use_docman')) {
?>
<tr>
<td>
<input type="checkbox" name="use_docman" value="1" <?php echo c($group->usesDocman()); ?> />
</td>
<td>
<strong><?php echo _('Use Documents') ?></strong>
</td>
</tr>
<?php
}

/*
if(forge_get_config('use_survey')) {
?>
<tr>
<td>
<input type="checkbox" name="use_survey" value="1" <?php echo c($group->usesSurvey()); ?> />
</td>
<td>
<strong><?php echo _('Use Surveys') ?></strong>
</td>
</tr>
<?php
}
*/

if(forge_get_config('use_news')) {
?>
<tr>
<td>
<input type="checkbox" name="use_news" value="1" <?php echo c($group->usesNews()); ?> />
</td>
<td>
<strong><?php echo _('Use News') ?> </strong>
</td>
</tr>
<?php
}

if(forge_get_config('use_scm')) {
?>
<tr>
<td>
<input type="checkbox" name="use_scm" value="1" <?php echo c($group->usesSCM()); ?> />
</td>
<td>
<strong><?php echo _('Use Source Code') ?></strong>
</td>
</tr>
<?php
}

if(forge_get_config('use_frs')) {
?>
<tr>
<td>
<input type="checkbox" name="use_frs" value="1" <?php echo c($group->usesFRS()); ?> />
</td>
<td>
<strong><?php echo _('Use File Release System') ?></strong>
</td>
</tr>
<?php
}

/*
if(forge_get_config('use_ftp')) {
?>
<tr>
<td>
<input type="checkbox" name="use_ftp" value="1" <?php echo c($group->usesFTP()); ?> />
</td>
<td>
<strong><?php echo _('Use FTP') ?></strong>
</td>
</tr>
<?php } */ ?>
<tr>
<td>
<input type="checkbox" name="use_stats" value="1" <?php echo c($group->usesStats()); ?> />
</td>
<td>
<strong><?php echo _('Use Statistics') ?></strong>
</td>
</tr>

<?php
$hookParams['group']=$group_id;
plugin_hook("groupisactivecheckbox",$hookParams);
?>

</table>

<input type="hidden" name="new_doc_address" value="<?php echo $group->getDocEmailAddress(); ?>" />
<input type="hidden" name="send_all_docs" value="1" <?php echo c($group->docEmailAll()); ?> />

<input type="submit" name="submit" value="<?php echo _('Save and Continue') ?>" />
</form>

<br />

<?php
echo $HTML->boxBottom();
echo '</td>';

/*
echo '<td>';
echo $HTML->boxTop(_('Tool Admin'));

if($group->usesForum()) { ?>
	<p><a href="/forum/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Forums Admin') ?></a></p>
<?php }
if($group->usesTracker()) { ?>
	<p><a href="/tracker/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Trackers Administration') ?></a></p>
<?php }
if($group->usesMail()) { ?>
	<p><a href="/mail/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Mailing Lists Admin') ?></a></p>
<?php }
if($group->usesPM()) { ?>
	<p><a href="/pm/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Tasks Administration') ?></a></p>
<?php }
if($group->usesDocman()) { ?>
	<p><a href="/docman/?group_id=<?php echo $group->getID(); ?>&amp;view=admin"><?php echo _('Documents Admin') ?></a></p>
<?php }
if($group->usesSurvey()) { ?>
	<p><a href="/survey/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Survey Admin') ?></a></p>
<?php }
if($group->usesNews()) { ?>
	<p><a href="/news/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('News Admin') ?></a></p>
<?php }
if($group->usesSCM()) { ?>
	<p><a href="/scm/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Source Code Admin') ?></a></p>
<?php }
if($group->usesFRS()) { ?>
	<p><a href="/frs/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('File Release System Admin') ?></a></p>
<?php }

$hook_params = array();
$hook_params['group_id'] = $group_id;
plugin_hook("project_admin_plugins", $hook_params);

echo $HTML->boxBottom();

echo '</td>';
*/

echo '</tr>';
echo '</table>';

project_admin_footer(array());
