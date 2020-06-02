<?php
/**
 *
 * Project Registration: Project Information.
 *
 * This page is used to request data required for project registration:
 *	 o Project Public Name
 *	 o Project Registration Purpose
 *	 o Project License
 *	 o Project Public Description
 *	 o Project Unix Name
 * All these data are more or less strictly validated.
 *
 * This is last page in registration sequence. Its successful subsmission
 * leads to creation of new group with Pending status, suitable for approval.
 *
 * Portions Copyright 1999-2001 (c) VA Linux Systems
 * Portions Copyright 2002-2004 (c) GForge Team
 * Portions Copyright 2002-2009 (c) Roland Mas
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012, Jean-Christophe Masson - French National Education Department
 * Copyright 2013-2014, Franck Villaume - TrivialDev
 * Copyright 2016-2019, Tod Hing, Henry Kwong - SimTK Team
 * http://fusionforge.org/
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'scm/SCMFactory.class.php';

global $HTML;

//
//	Test if restricted project registration
//
if (forge_get_config('project_registration_restricted')) {
	session_require_global_perm ('approve_projects', '',
				     sprintf (_('Project registration is restricted on %s, and only administrators can create new projects.'),
					      forge_get_config ('forge_name')));
} elseif (!session_loggedin()) {
	exit_not_logged_in();
}

$template_projects = group_get_template_projects() ;
sortProjectList ($template_projects) ;
$full_name = trim(getStringFromRequest('full_name'));
$purpose = trim(getStringFromRequest('purpose'));
$description = trim(getStringFromRequest('description'));
$unix_name = trim(strtolower(getStringFromRequest('unix_name')));
$scm = getStringFromRequest('scm');
$private = getStringFromRequest('private');
$built_from_template = getIntFromRequest('built_from_template');
//$summary = trim(getStringFromRequest('summary'));
//$download_description = trim(getStringFromRequest('download_description'));
$summary = substr($description, 0, 255);
$download_description = "";

$index = 1;

if (getStringFromRequest('submit')) {
	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
		exit_form_double_submit('my');
	}

	if (!$scm) {
		$scm = 'noscm' ;
	}

	$template_project = group_get_object($built_from_template);
	if ($template_project
	    && !$template_project->isError()
	    && $template_project->isTemplate()) {
		// Valid template selected, nothing to do
	} elseif (forge_get_config('allow_project_without_template')) {
		// Empty projects allowed
		$built_from_template = 0 ;
	} elseif (count($template_projects) == 0) {
		// No empty projects allowed, but no template available
		$built_from_template = 0 ;
	} else {
		// No empty projects allowed, picking the first available template
		$built_from_template = $template_projects[0]->getID() ;
	}

	$scm_host = '';
	if (forge_get_config('use_scm')) {
		$plugin = false ;
		if (forge_get_config('use_scm') && $scm && $scm != 'noscm') {
			$plugin = plugin_get_object($scm);
			if ($plugin) {
				$scm_host = $plugin->getDefaultServer();
			}
		}
		if (! $scm_host) {
			$scm_host = forge_get_config('scm_host');
		}
	}
/*
	if ( !$purpose && forge_get_config ('project_auto_approval') ) {
		$purpose = 'No purpose given, autoapprove was on';
	}
*/
		$purpose = 'No purpose given, autoapprove was on';

	$send_mail = ! forge_get_config ('project_auto_approval') ;

	$logo_tmpfile = "";
	$logo_type = "";
	if (isset($_FILES['logofile']['tmp_name'])) {
		$logo_tmpfile = $_FILES['logofile']['tmp_name'];
	}
	if (isset($_FILES['logofile']['type'])) {
		$logo_type = $_FILES['logofile']['type'];
	}

	// Set up flag for public/private project. 
	if (isset($private) && $private == "0") {
		// Private project.
		$private_param = 0;
	}
	else {
		// Public project.
		$private_param = 1;
	}

	$group = new Group();
	$u = session_get_user();
	$res = $group->create(
		$u,
		$full_name,
		$unix_name,
		$description,
		$purpose,
		'shell1',
		$scm_host,
		$private_param,
		$send_mail,
		$built_from_template,
                $summary,
                $download_description,
                $logo_tmpfile,
                $logo_type
	);
	if ($res && forge_get_config('use_scm') && $plugin) {
		$group->setUseSCM (true) ;
		$res = $group->setPluginUse ($scm, true);
	} else {
		$group->setUseSCM (false) ;
	}

	if (!$res) {
		form_release_key(getStringFromRequest("form_key"));
		$error_msg .= $group->getErrorMessage();
	} else {
		site_user_header(array('title'=>_('Registration complete')));

		if ( !forge_get_config('project_auto_approval') && !forge_check_global_perm('approve_projects')) {
			echo '<p>';
			printf(_('Your project has been submitted to the %s administrators. Within 72 hours, you will receive notification of their decision and further instructions.'), forge_get_config ('forge_name'));
			echo '</p>';
			echo '<p>';
			printf(_('Thank you for choosing %s.'), forge_get_config ('forge_name'));
			echo '</p>';
		} elseif ($group->isError()) {
			echo '<p class="error">' . $group->getErrorMessage() . '</p>';
		} else {
			printf(_('Approving Project: %s'), $group->getUnixName());
			echo '<br />';

			if (forge_get_config('project_auto_approval')) {
				$u = user_get_object_by_name(forge_get_config('project_auto_approval_user'));
			}

			if (!$group->approve($u)) {
				printf('<p class="error">' . _('Approval Error: %s'), $group->getErrorMessage() . '</p>');
			}
			else {
				// Set privacy after approval.
				// NOTE: Privacy should be set after project approval.
				$res = $group->updatePrivacy(session_get_user(), $private_param);
				if (!$res) {
					$error_msg .= $group->getErrorMessage();
					printf('<p class="error">' . 'Privacy Error: %s', $group->getErrorMessage() . '</p>');
				}

				echo '<p>';
				echo _('Your project has been automatically approved. You should receive an email containing further information shortly.');
				echo '</p>';
				echo '<p>';
				printf(_('Thank you for choosing %s.'), forge_get_config ('forge_name'));
				echo '</p>';
			}
		}

		site_footer(array());
		exit();
	}
} elseif (getStringFromRequest('i_disagree')) {
	session_redirect("/");
}

site_user_header(array('title'=>_('Register Project')));
//require $gfwww.'/include/header.php';

if (isset($group) && $group->isError()) {
   echo '<script>';
   echo '$(document).ready(function() {';
   if ($group->getErrorMessage() == 'Project title is too short') {
      echo '$("input[name=\'full_name\']").css("border-color", "red");';	  
   } elseif (trim($group->getErrorMessage()) == 'Describe your project in more detail.') {
      echo '$("textarea[name=\'description\']").css("border-color", "red");';
   } elseif ($group->getErrorMessage() == 'Invalid project identifier.') {
      echo '$("input[name=\'unix_name\']").css("border-color", "red");';
   } elseif ($group->getErrorMessage() == 'Project identifier is already taken.') {
      echo '$("input[name=\'unix_name\']").css("border-color", "red");';
   }
   echo '});';
   echo '</script>';
}
?>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post" enctype="multipart/form-data">
<h1>Create SimTK project</h1>
<p>
<?php echo 'A project is a set of webpages used to develop and share software, models, and other data. Tailor it to highlight the downloads you provide or the publication results that you are enabling others to reproduce. <span class="required_note">Required fields outlined in blue.</span>' ?>
</p>

<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>"/>
<h2><?php echo 'Project title' ?></h2>
<p>
<?php echo '<strong>Restrictions:  3-80 characters.</strong>' ?>
</p>
<p>
<input class="required" size="80" maxlength="80" type="text" name="full_name" placeholder="<?php echo _('Project Title'); ?>" />
</p>

<?php
/*
// Don't display Project purpose if auto approval is on, because it won't be used.
if ( !forge_get_config ('project_auto_approval') ) {
	echo '<h2>'.'Project purpose and summarization'.'</h2>';
	echo '<p>';
	printf(_('Please provide detailed, accurate description of your project and what %1$s resources and in which way you plan to use. This description will be the basis for the approval or rejection of your project\'s hosting on %1$s, and later, to ensure that you are using the services in the intended way. This description will not be used as a public description of your project. It must be written in English. From 10 to 1500 characters.'), forge_get_config ('forge_name'));
	echo '</p>';
	echo '<textarea required="required" name="purpose" cols="70" rows="10" placeholder="'. _('Project Purpose And Summarization').'" >';
	echo htmlspecialchars($purpose);
	echo '</textarea>';
}
*/
?>

<h2><?php echo 'Project description' ?></h2>
<p>
<?php echo 'Provide a detailed description of your project so SimTK webmasters can determine suitability of your project for the site.'; ?>
</p>

<textarea class="required" name="description" cols="80" rows="10" placeholder="<?php echo _('Project Public Description'); ?>" >
</textarea>

<h2><?php echo 'Short project identifier' ?></h2>
<?php echo "The identifier is part of the URL for your project.<p/><strong>Restrictions: 3-15 characters; lower-case; only characters, numbers, dashes (-), and underscores (_).</strong>" ?>
<p>
<br />
<input class="required" type="text" maxlength="15" size="15" name="unix_name" placeholder="<?php echo _('Short Name'); ?>" />
</p>

<h2><?php echo 'Privacy' ?></h2>
<p>
<input type="checkbox" name="private" value="0" /> Make entire project private - only title is publicly viewable
</p>
<p>
<strong>This option is discouraged. Instead, we suggest independently limiting access to individual sections of the project.</strong>
</p>

<input type="hidden" name="scm" value"noscm">
<?php

/*
$SCMFactory = new SCMFactory();
$scm_plugins=$SCMFactory->getSCMs();
if (forge_get_config('use_scm') && count($scm_plugins) > 0) {
	echo '<h2>'.'Source code'.'</h2>';
	echo '<p>' . _('You can choose among different SCM for your project, but just one (or none at all). Please select the SCM system you want to use.')."</p>\n";
	echo '<table><tbody><tr><td><strong>'._('SCM Repository')._(':').'</strong></td>';
	if (!$scm) {
		echo '<td><input type="radio" name="scm" value="noscm" checked="checked" />'._('No SCM').'</td>';
	} else {
		echo '<td><input type="radio" name="scm" value="noscm" />'._('No SCM').'</td>';
	}
	foreach($scm_plugins as $plugin) {
		$myPlugin= plugin_get_object($plugin);
		echo '<td><input type="radio" name="scm" ';
		echo 'value="'.$myPlugin->name.'"';
		if ($scm && strcmp($scm, $myPlugin->name) == 0) {
			echo ' checked="checked"';
		}
		echo ' />'.$myPlugin->text.'</td>';
	}
	echo '</tr></tbody></table>'."\n";
}

echo '<h2>'.'Project template'. '</h2>';

if (count ($template_projects) > 1) {
	$tpv_arr = array () ;
	$tpn_arr = array () ;
	echo '<p>';
	if (forge_get_config('allow_project_without_template')) {
		printf(_('You can either start from an empty project, or pick a project that will act as a template for yours.  Your project will initially have the same configuration as the template (same roles and permissions, same trackers, same set of enabled plugins, and so on).')) ;
		$tpv_arr[] = 0 ;
		$tpn_arr[] = _('Start from empty project') ;
	} else {
		printf(_('Please pick a project that will act as a template for yours.  Your project will initially have the same configuration as the template (same roles and permissions, same trackers, same set of enabled plugins, and so on).')) ;
	}
	echo '</p>' ;
	foreach ($template_projects as $tp) {
		$tpv_arr[] = $tp->getID() ;
		$tpn_arr[] = $tp->getPublicName() ;
	}
	echo html_build_select_box_from_arrays ($tpv_arr, $tpn_arr, 'built_from_template', $built_from_template,
						false, '', false, '') ;
} elseif (count ($template_projects) == 1) {
	echo '<p>';
	if (forge_get_config('allow_project_without_template')) {
		printf(_('You can either start from an empty project, or use the %s project as a template for yours.  Your project will initially have the same configuration as the template (same roles and permissions, same trackers, same set of enabled plugins, and so on).'),
		       $template_projects[0]->getPublicName()) ;
		echo '</p>' ;
		$tpv_arr = array () ;
		$tpn_arr = array () ;
		$tpv_arr[] = 0 ;
		$tpn_arr[] = _('Start from empty project') ;
		$tpv_arr[] = $template_projects[0]->getID() ;
		$tpn_arr[] = $template_projects[0]->getPublicName() ;
		echo html_build_select_box_from_arrays ($tpv_arr, $tpn_arr, 'built_from_template', $template_projects[0]->getID(),
							false, '', false, '') ;
	} else {
		printf(_('Your project will initially have the same configuration as the %s project (same roles and permissions, same trackers, same set of enabled plugins, and so on).'),
		       $template_projects[0]->getPublicName()) ;
		echo '<input type="hidden" name="built_from_template" value="'.$template_projects[0]->getID().'" />' ;
		echo '</p>' ;
	}
} else {
	echo '<p>';
	printf(_('Since no template project is available, your project will start empty.')) ;
	echo '<input type="hidden" name="built_from_template" value="0" />' ;
	echo '</p>';
}

*/


?>

<hr>
<input type="submit" name="submit" value="<?php echo 'Create project' ?>" class="btn-cta" />

</form>

<?php

site_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
