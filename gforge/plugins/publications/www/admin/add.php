<?php
/**
 *
 * add.php
 *
 * This file contains the form which allows user to add a new publication.
 * 
 * Copyright 2005-2018, SimTK Team
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
error_reporting(-1);
ini_set('display_errors', 'On');

//require_once $gfplugins.'env.inc.php';
require_once '../../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfplugins.'publications/www/publications-utils.php';
require_once $gfplugins.'publications/include/Publications.class.php';
require_once $gfwww.'project/project_utils.php';

//require_once $gfwww.'news/news_utils.php';

//require_once $gfcommon.'include/TextSanitizer.class.php'; // to make the HTML input by the user safe to store

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'publications');
	//$error_msg .= $group->getErrorMessage();
}

$pluginname="publications";
publications_Project_Header(array('title'=>'Publications','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";
	
if (session_loggedin()) {

	if (!forge_check_perm('pubs', $group_id, 'project_admin') && !forge_check_perm('project_read', $group_id)) {
		exit_permission_denied(_('You cannot submit news for a project unless you are an admin on that project.'), 'home');
	}

    $new_pub = new Publication($group);
	if (!$new_pub || !is_object($new_pub)) {
	   exit_error('Error','Could Not Create Publication');
	} elseif ($new_pub->isError()) {
	   exit_error('Error',$new_pub->getErrorMessage());
	}
	$primary_exist = $new_pub->getPrimary();
	
	//handle form submit
	if (getStringFromRequest('post_changes')) {
       if (!form_key_is_valid(getStringFromRequest('form_key'))) {
	      exit_form_double_submit('publications');
	   }
       $publication = getHtmlTextFromRequest('publication');
       $abstract = getHtmlTextFromRequest('abstract');
       $year = getStringFromRequest('year');
       $url = getStringFromRequest('url');
       $is_primary = getIntFromRequest('is_primary');
    
	   if ($new_pub->create($publication, $year, $url, $is_primary, $abstract)) {
	      $feedback = _('Publication Added');
          //echo '<p class="warning_msg">' . $feedback . '</p>';
		  echo "<div class='warning_msg' style='padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;'><div style='float:left;'>" . $feedback . "</div><div style='float:right;' onclick=\"$('.warning_msg').hide('slow');\">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div><div style='clear: both;'></div></div>";
		  
       } else {
          //$feedback = $new_pub->getErrorMessage();
		  //echo '<p class="warning_msg">Error: ' . $new_pub->getErrorMessage() . '</p>';
		  echo "<div class='warning_msg' style='padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;'><div style='float:left;'>Error: " . $new_pub->getErrorMessage() . "</div><div style='float:right;' onclick=\"$('.warning_msg').hide('slow');\">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div><div style='clear: both;'></div></div>";
		  
       }
	} 
	
	/*
		Show the submit form
	*/
	
	//echo $HTML->beginSubMenu();
	//echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
	//echo $HTML->endSubMenu();
			
	echo	'
		<form id="publicationsaddform" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="post_changes" value="y" />
		<input type="hidden" name="form_key" value="'. form_generate_key() .'" />
		
		<strong>'._('Publication')._(': ').'</strong>'.utils_requiredField().'<br />
		<p>
	        <textarea required="required" name="publication" rows="5" cols="50"></textarea>
                <p>
		<strong>'._('Abstract')._(': ').'</strong><br />
		<p>
	        <textarea name="abstract" rows="5" cols="50"></textarea>
                <p>
		<strong>'._('Year')._(': ').'</strong>'.utils_requiredField().'<br />
		<input required="required" type="text" name="year" value="" size="80" /></p>
		<p>
		<strong>'._('URL')._(': ').'</strong><br />
		<input type="text" name="url" value="" size="80" /></p>
		<p>';

	
	if (!$primary_exist) {
	    echo '<input type="checkbox" name="is_primary" value="1" checked> <b>Primary Citation</b> (shown on Overview page of publication projects)';
	} else {
	    echo '<input type="hidden" name="is_primary" value="0">';
	}
	
	echo '<div><input type="submit" name="submit" class="btn-cta" value="'._('Add').'" />
		</div></form>';
		
	echo "</div>";
    constructSideBar($group);
    echo "</div></div>";
	
    site_project_footer(array());	

} else {

	exit_not_logged_in();

}



// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		echo "group object not available";
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
