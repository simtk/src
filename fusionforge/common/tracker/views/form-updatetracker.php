<?php
/*
 * Tracker Facility
 *
 * Copyright 2010 (c) FusionForge Team
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
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

$name = getStringFromRequest('name', $ath->getName());
$description = getStringFromRequest('description', $ath->getDescription());
$email_address = getStringFromRequest('email_address', $ath->getEmailAddress());
$email_all = getStringFromRequest('email_all', $ath->emailAll());
$simtk_allow_anon = getStringFromRequest('simtk_allow_anon', $ath->getSimtkAllowAnon());
$due_period = getStringFromRequest('due_period', $ath->getDuePeriod() / 86400);
$status_timeout = getStringFromRequest('status_timeout', $ath->getStatusTimeout() / 86400);
$submit_instructions = getStringFromRequest('submit_instructions', $ath->getSubmitInstructions());
$browse_instructions = getStringFromRequest('browse_instructions', $ath->getBrowseInstructions());

//
//	FORM TO UPDATE ARTIFACT TYPES
//
$ath->adminHeader(array('title'=>sprintf(_('Update settings for %s'), $ath->getName())));

// Update page title identified by the class "project_submenu".
echo '<script>$(".project_submenu").html("Tracker: ' . $ath->getName() . '");</script>';

		?>
		<form action="<?php echo getStringFromServer('PHP_SELF').'?group_id='.$group_id.'&amp;atid='.$ath->getID(); ?>" method="post">
		<p>
		<input type="hidden" name="update_type" value="y" />
		<?php echo _('<strong>Name:</strong>'); ?><br />
		<?php if ($ath->getDataType()) {
			echo $ath->getName();
		} else {
			?>
			<input type="text" name="name" value="<?php echo $ath->getName(); ?>" />
			<?php
		}
		?>
		</p>
		<p>
		<strong><?php echo _('Description')._(':'); ?></strong><br />
		<?php if ($ath->getDataType()) {
			echo $ath->getDescription();
		} else {
			?>
			<input type="text" name="description" value="<?php echo $ath->getDescription(); ?>" size="50" />
			<?php
		}
		?>
		</p>
		<p>
		<input type="checkbox" name="simtk_allow_anon" value="1" <?php echo (($simtk_allow_anon)?'checked="checked"':''); ?> /> <strong><?php echo 'Allow non-logged-in postings' ?></strong></p>
		<p>
		<strong><?php echo _('Send email on new submission to address')._(':'); ?></strong><br />
		<input type="text" name="email_address" value="<?php echo $email_address; ?>" /></p>
		<p>
		<input type="checkbox" name="email_all" value="1" <?php echo (($email_all)?'checked="checked"':''); ?> /> <strong><?php echo _('Send email on all changes') ?></strong></p>
		<p>
		<strong><?php echo _('Days till considered overdue')._(':'); ?></strong><br />
		<input type="text" name="due_period" value="<?php echo $due_period; ?>" /></p>
		<p>
		<strong><?php echo _('Days till pending tracker items time out')._(':'); ?></strong><br />
		<input type="text" name="status_timeout"  value="<?php echo $status_timeout; ?>" /></p>
		<p>
		<strong><?php echo _('Free form text for the “Submit New” page')._(':'); ?></strong><br />
		<textarea name="submit_instructions" rows="10" cols="55"><?php echo $submit_instructions; ?></textarea></p>
		<p>
		<strong><?php echo _('Free form text for the Browse page')._(':'); ?></strong><br />
		<textarea name="browse_instructions" rows="10" cols="55"><?php echo $browse_instructions; ?></textarea></p>
		<p>
		<input type="submit" name="post_changes" value="Submit" class="btn-cta" /></p>
		</form>
		<?php

		$ath->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
