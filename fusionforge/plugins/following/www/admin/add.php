<?php

/**
 *
 * following add.php
 *
 * Copyright 2005-2019, SimTK Team
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


require_once $gfplugins.'env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once '../publications-utils.php';
require_once $gfplugins.'publications/include/Publications.class.php';

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'publications');
}

$pluginname="publications";
publications_Project_Header(array('title'=>$pluginname . ' Project Plugin!','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);

if (session_loggedin()) {

	if (!forge_check_perm('project_admin', $group_id)) {
		exit_permission_denied(_('You cannot submit news for a project unless you are an admin on that project.'), 'home');
	}


	
	
	//handle form submit
	if (getStringFromRequest('post_changes')) 
        {

         	if (!form_key_is_valid(getStringFromRequest('form_key'))) {
			exit_form_double_submit('publications');
		}
            $publication = getHtmlTextFromRequest('publication');
            $abstract = getHtmlTextFromRequest('abstract');
            $year = getStringFromRequest('year');
            $url = getStringFromRequest('url');
            $is_primary = 0;
		
	//	$is_primary = $is_primary == 1? 1: 0;
	
	    $new_pub = new Publication($group);
	    if (!$new_pub || !is_object($new_pub)) {
		  exit_error('Error','Could Not Create Publication');
	    } elseif ($new_pub->isError()) {
		  exit_error('Error',$new_pub->getErrorMessage());
	    }

         //   echo "pub: $publication";
          //  echo "year: $year";

	    if ($new_pub->create($publication, $year, $url, $is_primary,$abstract)) {
	      $feedback = _('Publication Added');
//              echo '<p class="feedback">' . $feedback;
              echo '<p class="warning_msg">' . $feedback;
            } 
            else {
              $feedback = $new_pub->getErrorMessage();
            }
/*
	    try
	    {
		  if ($new_pub->create($publication, $year, $url, $is_primary,$abstract)) {
			$feedback = $Language->getText('publications','add_success');
                        $g->addHistory('Added Publication',' ');  // log audit  Tod Hing 6-7-2014
			//clear values for the next add
			unset($publication);
			unset($year);
			unset($url);
			unset($is_primary);
			unset($abstract);
		  } else {
			$feedback = $new_pub->getErrorMessage();
                        echo "create error";
		  }
	    }
	    catch ( RuntimeException $ex )
	    {
		if ( preg_match('/^Invalid URL: /', $ex->getMessage() ) )
		{
			$url = preg_replace( "/^Invalid URL: /", "", $ex->getMessage() );
			$feedback = "The URL you provided could not be verified. 
				<div style='color:#4d4d4d; margin-top: 1em;'>Please check the link: <a href='$url' target='_blank'>$url</a>.</div>
				<div style='color:#4d4d4d; margin-top: 1em; font-size:11px;'>If it works--it's possible the target web site does not allow automated 
				verification:<br/>
				1) Paste the (possibly corrected) URL from the <em>navigation bar</em> into the URL box<br/>
				2) Select \"Add\" at the bottom of the page<br/><br/>
				Note: if you leave the URL box empty your entry will be saved without a URL.</div>";
			unset( $url );
                        echo "invalid url  error";
		}
		else
                        echo "invalid error";
			exit_error( 'Error', $ex->getMessage() );
	    } //catch
	    catch ( Exception $ex )
	    {
                echo "invalid error";
		exit_error( 'Error', $ex->getMessage() );
	    } //catch
*/

	} //handle form submit
	
	//publications must now be submitted from a project page -
	if (!$group_id) {
		exit_no_group();
	}
	
	/*
		Show the submit form
	*/
	$group = group_get_object($group_id);
//	news_header(array('title'=>_('Add Publication for Project: ').' '.$group->getPublicName()));

	//$jsfunc = notepad_func();
	echo	'
		<form id="publicationsaddform" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="post_changes" value="y" />
		<input type="hidden" name="form_key" value="'. form_generate_key() .'" />
		<p><strong>'._('Add a new publication for  project')._(': ').$group->getPublicName().'</strong></p>
		<strong>'._('Publication')._(': ').'</strong>'.utils_requiredField().'<br />
		<p>
	        <textarea required="required" name="publication" rows="5" cols="50">'.$details.'</textarea>
                <p>
		<strong>'._('Abstract')._(': ').'</strong><br />
		<p>
	        <textarea name="abstract" rows="5" cols="50">'.$details.'</textarea>
                <p>
		<strong>'._('Year')._(': ').'</strong>'.utils_requiredField().'<br />
		<input required="required" type="text" name="year" value="'.$summary.'" size="80" /></p>
		<p>
		<strong>'._('URL')._(': ').'</strong><br />
		<input type="text" name="url" value="'.$summary.'" size="80" /></p>
		<p>';

	echo '<div><input type="submit" name="submit" value="'._('Add').'" />
		</div></form>';
//	news_footer(array());

} else {

	exit_not_logged_in();

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
