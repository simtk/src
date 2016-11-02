<?php
/**
 *
 * news plugin admin/news_admin_utils.php
 * 
 * Admin utility file for admin index page.
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

function show_news_approve_form($qpa_pending, $qpa_rejected, $qpa_approved) {
	/*
		Show list of waiting news items
	*/

	// function to show single news item
	// factored out because called 3 time below
	function show_news_item($row, $i, $approved, $selectable) {
		global $HTML;

		echo '<tr '. $HTML->boxGetAltRowStyle($i) . '><td>';
		if ($selectable) {
			echo '<input type="checkbox" '
			.'name="news_id[]" value="'
			.$row['id'].'" />';
		}
		echo date(_('Y-m-d'), $row['post_date']).'</td>
		<td width="45%">';
		echo '
		<a href="'.getStringFromServer('PHP_SELF').'?approve=1&amp;id='.$row['id'].'">'.$row['summary'].'</a>
		</td>

		<td class="onethirdwidth">'
		.util_make_link_g ($row['unix_group_name'], $row['group_id'], $row['group_name'].' ('.$row['unix_group_name'].')')
		.'</td>
		</tr>'
		;
	}

	$title_arr = array(
		_('Date'),
		_('Subject'),
		_('Project')
	);

	$ra = RoleAnonymous::getInstance();

	$result = db_query_qpa($qpa_pending);
	$items = array();
	while ($row_item = db_fetch_array($result)) {
		if ($ra->hasPermission('project_read', $row_item['group_id'])) {
			$items[] = $row_item;
		}
	}
	$rows = count($items);

	if ($rows < 1) {
		echo '
			<h2>'._('No pending items found.').'</h2>';
	} else {
		echo '<form action="'. getStringFromServer('PHP_SELF') .'" method="post">';
		echo '<input type="hidden" name="mass_reject" value="1" />';
		echo '<input type="hidden" name="post_changes" value="y" />';
		echo '<h2>'.sprintf(_('These items need to be approved (total: %d)'), $rows).'</h2>';
		echo $GLOBALS['HTML']->listTableTop($title_arr);
		for ($i=0; $i < $rows; $i++) {
			show_news_item($items[$i], $i, false,true);
		}
		echo $GLOBALS['HTML']->listTableBottom();
		echo '<br /><input type="submit" name="submit" value="'._('Reject Selected').'" class="btn-cta" />';
		echo '</form>';
	}


	/*
		Show list of rejected news items for this week
	*/

	$result = db_query_qpa($qpa_rejected);
	$items = array();
	while ($row_item = db_fetch_array($result)) {
		if ($ra->hasPermission('project_read', $row_item['group_id'])) {
			$items[] = $row_item;
		}
	}
	$rows = count($items);

	if ($rows < 1) {
		echo '
			<h2>'._('No rejected items found for this week.').'</h2>';
	} else {
		echo '<h2>'.sprintf(_('These items were rejected this past week or were not intended for front page (total: %d).'), $rows).'</h2>';
		echo $GLOBALS['HTML']->listTableTop($title_arr);
		for ($i=0; $i<$rows; $i++) {
			show_news_item($items[$i], $i, false, false);
		}
		echo $GLOBALS['HTML']->listTableBottom();
	}

	/*
		Show list of approved news items for this week
	*/

	$result = db_query_qpa($qpa_approved);
	$items = array();
	while ($row_item = db_fetch_array($result)) {
		if ($ra->hasPermission('project_read', $row_item['group_id'])) {
			$items[] = $row_item;
		}
	}
	$rows = count($items);
	if ($rows < 1) {
		echo '
			<h2>'._('No approved items found for this week.').'</h2>';
	} else {
		echo '<h2>'.sprintf(_('These items were approved this past week (total: %d).'), $rows).'</h2>';
		echo $GLOBALS['HTML']->listTableTop($title_arr);
		for ($i=0; $i < $rows; $i++) {
			show_news_item($items[$i], $i, false, false);
		}
		echo $GLOBALS['HTML']->listTableBottom();
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
