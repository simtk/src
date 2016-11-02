<?php
/**
 * Tracker Facility
 *
 * Copyright 2010 (c) FusionForge Team
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
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

//
//  FORM TO DELETE POP-UP CHOICES FOR A BOX
//
	/*
		Allow deletion of a Choice for a Pop-up Box
	*/
	$boxid = getIntFromRequest('boxid');
	$ac = new ArtifactExtraField($ath,$boxid);
	if (!$ac || !is_object($ac)) {
		exit_error(_('Unable to create ArtifactExtraField Object'),'tracker');
	} elseif ($ac->isError()) {
		exit_error($ac->getErrorMessage(),'tracker');
	} else {
		$id = getStringFromRequest('id');
		$ao = new ArtifactExtraFieldElement($ac,$id);
		if (!$ao || !is_object($ao)) {
			exit_error(_('Unable to create ArtifactExtraFieldElement Object'),'tracker');
		} elseif ($ao->isError()) {
			exit_error($ao->getErrorMessage(),'tracker');
		} else {

			$ath->adminHeader(array('title'=>sprintf(_("Delete a custom field element in: %s"),
				$ath->getName())));

			// Update page title identified by the class "project_submenu".
			echo '<script>$(".project_submenu").html("Tracker: ' . $ath->getName() . '");</script>';

			?>

			<table class="centered">
			<tr>
			<td>
			<fieldset>
			<legend><?php echo _('Confirm Delete') ?></legend>
			<form action="<?php echo getStringFromServer('PHP_SELF').'?group_id='.$group_id.'&amp;atid='.$ath->getID(); ?>" method="post">
			<input type="hidden" name="delete_opt" value="y" />
			<input type="hidden" name="id" value="<?php echo $ao->getID(); ?>" />
			<input type="hidden" name="boxid" value="<?php echo $boxid; ?>" />

			<p>
			<?php echo _('You are about to permanently and irretrievably delete the <strong>' . $ao->getName() . '</strong> element!'); ?>
			</p>
			<p>
			<input id="sure" type="checkbox" name="sure" value="1" />
			<label for="sure">
			<?php echo _("I am Sure") ?>
			</label>
			<input id="really_sure" type="checkbox" name="really_sure" value="1" />
			<label for="really_sure">
			<?php echo _("I am Really Sure") ?>
			</label>
			</p>
			<p>
			<input type="submit" name="post_changes" value="Delete" class="btn-cta" /></p>
			</form>
			</fieldset>
			</td>
			</tr>
			</table>
			<?php
			$ath->footer(array());
		}
	}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
