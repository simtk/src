<?php
/**
 * Tracker Facility
 *
 * Copyright 2010 (c) FusionForge Team
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
//  FORM TO ADD CANNED RESPONSES
//
$title = sprintf(_('Manage Canned Responses to %s'), $ath->getName());
$ath->adminHeader(array('title'=>$title, 'modal'=>1));

// Update page title identified by the class "project_submenu".
echo '<script>$(".project_submenu").html("Tracker: ' . $ath->getName() . '");</script>';

		/*
			List of existing canned responses
		*/
		$result=$ath->getCannedResponses();
		$rows=db_numrows($result);

		if ($result && $rows > 0) {
			//code to show existing responses and link to update page
			echo '<h2>'._('Existing Responses').'</h2>';
			$title_arr=array();
			$title_arr[]=_('Id');
			$title_arr[]=_('Title');
			$title_arr[]=_('Operation');

			echo $GLOBALS['HTML']->listTableTop ($title_arr);

			for ($i=0; $i < $rows; $i++) {
				echo '<tr '. $GLOBALS['HTML']->boxGetAltRowStyle($i) .'>'.
					'<td>'.db_result($result, $i, 'id').'</td>'.
					'<td><a href="'.getStringFromServer('PHP_SELF').'?update_canned=1&amp;id='.
						db_result($result, $i, 'id').'&amp;group_id='.$group_id.'&amp;atid='. $ath->getID() .'">'.
						db_result($result, $i, 'title').'</a></td>
					<td><a href="'.getStringFromServer('PHP_SELF').'?delete_canned=1&amp;id='.
						db_result($result, $i, 'id').'&amp;group_id='.$group_id.'&amp;atid='. $ath->getID() .'">'.
						_('Delete').'</a></td></tr>';
			}

			echo $GLOBALS['HTML']->listTableBottom();

		} else {
			echo '<p class="information">'._('No Canned Responses set up in this Project').'</p>';
		}

		echo '<h2>'._('Add New Canned Response').'</h2>';
		?>
		<p><?php echo _('Creating useful generic messages can save you a lot of time when handling common artifact requests.') ?></p>
		<form action="<?php echo getStringFromServer('PHP_SELF').'?group_id='.$group_id.'&amp;atid='.$ath->getID(); ?>" method="post">
		<input type="hidden" name="add_canned" value="y" />
		<fieldset>
		<table>
		<tr>
		<td>
		<label for="title">
		<strong><?php echo _('Title') . _(':') ?></strong><?php echo utils_requiredField(); ?><br />
		</label>
		</td>
		<td>
		<input id="title" type="text" name="title" class="required" required="required" value="" size="50" maxlength="80" />
		</td>
		</tr>
		<tr>
		<td>
		<label for="body">
		<strong><?php echo _('Message Body') . _(':') ?></strong><?php echo utils_requiredField(); ?><br />
		</label>
		</td>
		<td style="padding-top:5px;" >
		<textarea id="body" name="body" class="required" required="required" rows="15" cols="50"></textarea></p>
		</td>
		</tr>
		<tr>
		<td>
		<input type="submit" name="post_changes" value="Submit" class="btn-cta" /></p>
		</td>
		</tr>
		</table>
		</fieldset>
		</form>
		<?php

		$ath->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
