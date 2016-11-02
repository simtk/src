<?php
/**
 *
 * news plugin submit.php
 * 
 * admin page for creating new news.
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
//require_once $gfwww.'include/note.php';
require_once 'simtk_news_utils.php';
//require_once $gfcommon.'forum/Forum.class.php';
//require_once $gfcommon.'include/TextSanitizer.class.php'; // to make the HTML input by the user safe to store
require_once $gfwww.'project/project_utils.php';

$group_id = getIntFromRequest('group_id');

// Check permission and prompt for login if needed.
session_require_perm('project_read', $group_id);


if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'news');
}

$summary = getStringFromRequest('summary');
$details = getHtmlTextFromRequest('details');
$display_local = getStringFromRequest('display_local');
if (!$display_local) {
  $display_local = "f";
}
$display_global = getStringFromRequest('display_global');
if (!$display_global) {
  $display_global = "f";
}


if (session_loggedin()) {

	if (!forge_check_perm('project_admin', $group_id)) {
		exit_permission_denied(_('You cannot submit news for a project unless you are an admin on that project.'), 'home');
	}

	if ($group_id == forge_get_config('news_group')) {
		exit_permission_denied(_('Submitting news from the news group is not allowed.'), 'home');
	}

	if (getStringFromRequest('post_changes')) {
		if (!form_key_is_valid(getStringFromRequest('form_key'))) {
			exit_form_double_submit('news');
		}

		//check to make sure both fields are there
		if ($summary && $details) {
			/*
			  create a new discussion forum without a default msg
			  if one isn't already there
			*/

			db_begin();
			//$f = new Forum($group, false, false, true);
			//if (!$f->create(preg_replace('/[^_\.0-9a-z-]/', '-', strtolower($summary)), $details, '')) {
		//		db_rollback();
		//		$error_msg = $f->getErrorMessage();
		//	} else {
				$group->normalizeAllRoles();
				//$new_id = $f->getID();
				$sql = 'INSERT INTO plugin_simtk_news (group_id, submitted_by, is_approved, post_date, forum_id, summary,details,simtk_sidebar_display,simtk_request_global)
						VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)';
				$result = db_query_params($sql, array($group_id, user_getid(), 0, time(), 0, htmlspecialchars($summary), $details, $display_local, $display_global));
				if (!$result) {
					db_rollback();
					form_release_key(getStringFromRequest('form_key'));
					$error_msg = _('Error: insert failed.');
				} else {
					db_commit();
					$feedback = _('News Added.');
				}
		//	}
		} else {
			form_release_key(getStringFromRequest('form_key'));
			$error_msg = _('Error: both subject and body are required.');
		}
	}

	//news must now be submitted from a project page -
    /*
	if (!$group_id) {
		exit_no_group();
	}
    */
	//html_use_tooltips();


	/*
		Show the submit form
	*/
	//$group = group_get_object($group_id);
	news_header(array('title'=>'News'),$group_id);
    
	echo "<div class=\"project_overview_main\">";
    echo "<div style=\"display: table; width: 75%;\">";
    echo "<div class=\"main_col\">";

	//$jsfunc = notepad_func();
	
	echo '<p><span class="required_note">Required fields outlined in blue.</span><br />';
	echo _('URLs that start with http:// or https:// are made clickable. Other HTML is not allowed.');
	echo '</p>';
	echo '
		<form id="newssubmitform" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="post_changes" value="y" />
		<input type="hidden" name="form_key" value="'. form_generate_key() .'" />
		<p>
		<strong>'._('Subject')._(': ').'</strong><br />
		<input required="required" type="text" name="summary" class="required" size="60" ';
	if (isset($summary) && $summary != "") {
		echo "value='" . $summary . "'";
	}
	echo '/></p>
		<p>
		<strong>'._('Details')._(': ').'</strong></p>';

	$theDetails = "";
	if (isset($details) && $details != "") {
		$theDetails = $details;
	}

	$params = array();
	$params['name'] = 'details';
	$params['width'] = "800";
	$params['height'] = "500";
	$params['body'] = $details;
	$params['group'] = $group_id;
	$params['content'] = '<textarea required="required" name="details" rows="5" cols="50" class="required">' . $theDetails . '</textarea>';
	plugin_hook_by_reference("text_editor",$params);

	echo $params['content'].'<br />';
	
	echo '<h3>Display Options</h3>';
	
	echo '<p>All posts automatically appear on your project News summary page. To have news displayed in other places on the SimTK site, check the appropriate box below.</p>';
	
	echo "<p><input type=\"checkbox\" name=\"display_local\" value=\"t\"";
	if (isset($display_local) && $display_local == "t") {
		echo "checked='checked'";
	}
	echo '> Display on <a href="/projects/'.$group->getUnixName().'">Project homepage</a></p>';
    
	echo "<p><input type=\"checkbox\" name=\"display_global\" value=\"t\"";
	if (isset($display_global) && $display_global == "t") {
		echo "checked='checked'";
	}
	echo "> Display on <a href='/'>SimTK homepage</a> (Needs approval by SimTK news team to appear)</p>";
                                
	echo '<div><input type="submit" name="submit" value="'._('Submit').'" class="btn-cta" /></div></form>';

	echo "</div>";
	constructSideBar($group);
	echo "</div></div>";
	
	news_footer(array());

} else {

	exit_not_logged_in();

}

// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	echo '</div>';
}


// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
