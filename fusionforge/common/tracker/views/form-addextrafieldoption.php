<?php
/**
 * Tracker Facility
 *
 * Copyright 2010 (c) FusionForge Team
 * Copyright 2014, Franck Villaume - TrivialDev
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

//
//  FORM TO ADD ELEMENTS TO EXTRA FIELD
//
	$boxid = getIntFromRequest('boxid');
	$ac = new ArtifactExtraField($ath,$boxid);
	if (!$ac || !is_object($ac)) {
		exit_error(_('Unable to create ArtifactExtraField Object'),'tracker');
	} elseif ($ac->isError()) {
		exit_error($ac->getErrorMessage(),'tracker');
	} else {
		$efearr=$ath->getExtraFieldElements($boxid);
		$title = sprintf(_('Add/Update Custom Field Elements in %s'), $ath->getName());
		$ath->adminHeader(array('title'=>$title));

		// Update page title identified by the class "project_submenu".
		echo '<script>$(".project_submenu").html("Tracker: ' . $ath->getName() . '");</script>';

		echo '<h2>'._('Custom Field Name')._(': ').$ac->getName().'</h2>';
		$rows=count($efearr);
		if ($rows > 0) {

			?>
			<form action="<?php echo 'index.php?group_id='.$group_id.'&amp;atid='.$ath->getID().'&amp;boxid='.$boxid; ?>" method="post">
			<?php
			$title_arr=array();
			$title_arr[]=_('Current / New positions');
			if ($ac->getType() == ARTIFACT_EXTRAFIELDTYPE_STATUS) {
				$title_arr[] = _('Mapping');
			}
			$title_arr[]=_('Up/Down positions');
			$title_arr[]=_('Elements Defined');
			$title_arr[]='';

			echo $GLOBALS['HTML']->listTableTop ($title_arr,false, ' ');

			for ($i=0; $i < $rows; $i++) {
				echo '<tr '. $GLOBALS['HTML']->boxGetAltRowStyle($i) .'>'.
					'<td class="align-right">'.
					($i + 1).' --&gt; <input type="text" name="order['. $efearr[$i]['element_id'] .']" value="" size="3" maxlength="3" />'.
					"</td>\n";
				if ($ac->getType() == ARTIFACT_EXTRAFIELDTYPE_STATUS) {
					echo '<td>' .
					    $ath->getStatusName($efearr[$i]['status_id']) .
					    "</td>\n";
				}
				echo '<td class="align-center">'.
					'<a href="index.php?group_id='.$group_id.'&amp;atid='.$ath->getID().'&amp;boxid='.$boxid.'&amp;id='.$efearr[$i]['element_id'].
					'&amp;updownorder_opt=1&amp;new_pos='.(($i == 0)? $i + 1 : $i).'">'.html_image('ic/btn_up.png','19','18',array('alt'=>"Up")).'</a>'.
					'<a href="index.php?group_id='.$group_id.'&amp;atid='.$ath->getID().'&amp;boxid='.$boxid.'&amp;id='.$efearr[$i]['element_id'].
					'&amp;updownorder_opt=1&amp;new_pos='.(($i == $rows - 1)? $i + 1 : $i + 2).'">'.html_image('ic/btn_down.png','19','18',array('alt'=>"Down")).'</a>'.
					'</td>'."\n".'<td>'.$efearr[$i]['element_name'].
					'</td>'."\n".'<td class="align-center">'.
					'<a href="'.getStringFromServer('PHP_SELF').'?update_opt=1&amp;id='.
					$efearr[$i]['element_id'].'&amp;boxid='.
					$boxid.'&amp;group_id='.$group_id.'&amp;atid='. $ath->getID() .'">'.
					html_image('ic/forum_edit.gif','37','15',array('alt'=>"Edit")).'</a>'.
					'</td></tr>'."\n";
			}
//			echo $GLOBALS['HTML']->listTableBottom();
			?>
			<tr class="noborder">
			<td class="align-right">
			<input type="submit" name="post_changes_order" value="Reorder" class="btn-cta" />
			</td>
			<td>
			</td>
			<td class="align-left">
			<input type="submit" name="post_changes_alphaorder" value="Alphabetical order" class="btn-cta" />
			</td>
			</tr>
			<?php echo $GLOBALS['HTML']->listTableBottom(); ?>
			</form>
			<?php

		} else {
			echo "\n<strong>"._('You have not defined any elements.')."</strong>";
		}
		?>
		<form action="<?php echo getStringFromServer('PHP_SELF').'?group_id='.$group_id.'&amp;boxid='.$boxid.'&amp;atid='.$ath->getID(); ?>" method="post">
		<input type="hidden" name="add_opt" value="y" />
		<br /><br />
		<label for="name">
			<strong><?php echo _('Add New Element')._(':'); ?></strong>
		</label>
		<input type="text" id="name" name="name" value="" size="15" maxlength="30" />
		<!--
		Show a pop-up box to choose the possible statuses that this element will map to
		-->
		<?php if ($ac->getType() == ARTIFACT_EXTRAFIELDTYPE_STATUS) { ?>
		<strong><?php echo _('Status'); ?></strong>
		<?php echo $ath->statusBox('status_id',1,false,false); ?>
		<?php } ?>
		<input type="submit" name="post_changes" value="Submit" class="btn-cta" />
		</form>
		<?php
		$ath->footer();
	}
// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
