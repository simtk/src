<?php
/**
 * tools.php
 *
 * Project Admin page to edit tools information
 *
 * Portions Copyright 1999-2001 (c) VA Linux Systems
 * The rest Copyright 2002-2004 (c) GForge Team
 * Copyright 2010, Franck Villaume - Capgemini
 * Copyright 2016, Tod Hing - SimTK Team
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

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfwww.'scm/include/scm_utils.php';
require_once $gfcommon.'scm/SCMFactory.class.php';

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

    $SCMFactory = new SCMFactory();
	$scm_plugins = $SCMFactory->getSCMs();
	
	
	$use_mail = getStringFromRequest('use_mail');
	$use_forum = getStringFromRequest('use_forum');
	$use_scm = getStringFromRequest('use_scm');
	$use_news = getStringFromRequest('use_news');
	$use_docman = getStringFromRequest('use_docman');
	$use_frs = getStringFromRequest('use_frs');
	$use_stats = getStringFromRequest('use_stats');
	$use_activity = getStringFromRequest('use_activity');
	$use_tracker = getStringFromRequest('use_tracker');

	$res = $group->updateTools(
		session_get_user(),
		$use_mail,
		$use_forum,
		$use_scm,
		$use_news,
		$use_docman,
		$use_frs,
		$use_stats,
		$use_tracker,
		$use_activity
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

		$strAppend = "";
		$use_moinmoin = getStringFromRequest('use_moinmoin');
		if ($use_moinmoin == 1) {
			if ($use_scm == false) {
				$strAppend = ' Wiki takes up to 15 minutes to create.';
			}
			else {
				$strAppend = ' Wiki and Code Repository take up to 15 minutes to create.';
			}
		}
		else {
			if ($use_scm == 1) {
				$strAppend = ' Code Repository takes up to 15 minutes to create.';
			}
		}
		if ($use_scm == 1) {
			$group->setPluginUse("scmsvn", 1);
			$group->setSCMBox("simtk.org");
			$hook_params = array();
			$hook_params['group_id'] = $group_id;
			$hook_params['scmsvn'] = [];
			//var_dump($hook_params);
			plugin_hook("scm_admin_update", $hook_params);
		}

		$feedback = 'Project information updated.' . $strAppend;
	}
        if (getStringFromRequest('wizard')) {
		header("Location: category.php?group_id=$group_id&wizard=1");
        }

}

project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

if (getStringFromRequest('wizard')) {
   //echo $HTML->boxTop(_('<h3>Continue Project Setup - Tools</h3>'));
   echo '<h3>Continue Project Setup - Tools</h3>';
} else {
   //echo $HTML->boxTop(_('<h3>Tools</h3>').'');
//   echo '<h3>Tools</h3>';
}
	   
echo '<table class="fullwidth">';
echo '<tr class="top">';
echo '<td class="onethirdwidth">';

?>

<p>Disable and enable tools used for your project below:</p>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<?php if (getStringFromRequest('wizard')) { ?>
  <input type="hidden" name="wizard" value="1" />
<?php } ?>

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

<style>
input[type="checkbox"] {
	margin-right: 3px;
}
</style>

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
<strong><?php echo _('Use Code Repository') ?></strong>
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
<strong><?php echo _('Use Downloads') ?></strong>
</td>
</tr>
<?php
} 

if(forge_get_config('use_tracker')) {
?>
<tr>
<td>
<input type="checkbox" name="use_tracker" value="1" <?php echo c($group->usesTracker()); ?> />
</td>
<td>
<strong><?php echo _('Use Issue Tracker (Features & Bugs)') ?></strong>
</td>
</tr>
<?php
} ?>

<!---
<tr>
<td>
<input type="checkbox" name="use_stats" value="1" <?php echo c($group->usesStats()); ?> />
</td>
<td>
<strong><?php echo _('Use Statistics') ?></strong>
</td>
</tr>
--->

<?php
$hookParams['group']=$group_id;
plugin_hook("groupisactivecheckbox",$hookParams);
?>

</table>


<?php
if (getStringFromRequest('wizard')) { ?>

   <p>
   <input type="submit" name="submit" value="<?php echo _('Save and Continue') ?>" />
   </p>

  <?php      }
else { ?>

<br />
<p>
<input type="submit" class="btn-cta" name="submit" value="<?php echo _('Update') ?>" />
</p>

<?php } ?>

</form>

<br />

<?php
echo $HTML->boxBottom();
echo '</td>';

if (!getStringFromRequest('wizard')) { 

echo '<td>';
echo $HTML->boxTop('');

/*
if($group->usesForum()) { ?>
	<p><a href="/forum/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Forums Admin') ?></a></p>
<?php }

if($group->usesMail()) { ?>
	<p><a href="/mail/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Mailing Lists Admin') ?></a></p>
<?php }
if($group->usesDocman()) { ?>
	<p><a href="/docman/?group_id=<?php echo $group->getID(); ?>&amp;view=admin"><?php echo _('Documents Admin') ?></a></p>
<?php }
if($group->usesNews()) { ?>
	<p><a href="/news/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('News Admin') ?></a></p>
<?php }
if($group->usesSCM()) { ?>
	<p><a href="/scm/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Code Repository Admin') ?></a></p>
<?php }
if($group->usesFRS()) { ?>
	<p><a href="/frs/admin/?group_id=<?php echo $group->getID(); ?>"><?php echo _('Downloads Admin') ?></a></p>
<?php }

$hook_params = array();
$hook_params['group_id'] = $group_id;
plugin_hook("project_admin_plugins", $hook_params);
*/
echo $HTML->boxBottom();

echo '</td>';

} // if not wizard

echo '</tr>';
echo '</table>';

project_admin_footer(array());
