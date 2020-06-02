<?php
/**
 *
 * edit.php
 *
 * This file contains the form which allows admin to edit a publication.
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

//require_once $gfplugins.'env.inc.php';
require_once '../../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once '../publications-utils.php';
require_once $gfplugins.'publications/include/Publications.class.php';
require_once $gfwww.'project/project_utils.php';

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
publications_Project_Header(array('title'=>'Publications','pagename'=>"$pluginname",'sectionvals'=>array(group_getname($group_id))),$group_id);

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

if (session_loggedin()) {

	if (!forge_check_perm('pubs', $group_id, 'project_admin')) {
		exit_permission_denied(_('You cannot submit publication for a project unless you are an admin on that project.'), 'home');
	}

	// Check form_key in submit operation.
	if (getStringFromRequest('submit')) {
            if (!form_key_is_valid(getStringFromRequest('form_key'))) {
			exit_form_double_submit('publications');
            }
	}
            $pub_id = getStringFromRequest('pub_id');
            $is_primary = 0;
		
	//	$is_primary = $is_primary == 1? 1: 0;
	
	    $pub = new Publication($group,$pub_id);
	    if (!$pub || !is_object($pub)) {
		  exit_error('Error','Could Not Create Publication');
	    } elseif ($pub->isError()) {
		  exit_error('Error',$pub->getErrorMessage());
	    }

            if (getStringFromRequest('post_changes')) {
               $publication = getHtmlTextFromRequest('publication');
               $abstract = getHtmlTextFromRequest('abstract');
               $year = getStringFromRequest('year');
               $url = getStringFromRequest('url');
               $is_primary = getStringFromRequest('is_primary');
               if ($pub->update($publication,$year,$url,$is_primary,$abstract)) {
                  $feedback = _('Publication Updated.');
//                  echo '<p class="feedback">'. $feedback . "</p>";
                  echo '<p class="warning_msg">'. $feedback . "</p>";
               }
               else {
		  exit_error('Error',"Update Publication Error");
               }

            }

         //   echo "pub: $publication";
          //  echo "year: $year";

//		 $new_pub->create($publication, $year, $url, $is_primary,$abstract);
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

	
	//publications must now be submitted from a project page -
	
	/*
		Show the submit form
	*/
	echo	'
		<form id="publicationsaddform" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="pub_id" value="'.$pub_id.'" />
		<input type="hidden" name="is_primary" value="'.$pub->data_array['is_primary'].'" />
		<input type="hidden" name="post_changes" value="y" />
		<input type="hidden" name="form_key" value="'. form_generate_key() .'" />
		
		<strong>'._('Publication')._(': ').'</strong>'.utils_requiredField().'<br />
		<p>
	        <textarea required="required" name="publication" rows="5" cols="50">'.$pub->data_array['publication'].'</textarea>
                <p>
		<strong>'._('Abstract')._(': ').'</strong><br />
		<p>
	        <textarea name="abstract" rows="5" cols="50">'.$pub->data_array['abstract'].'</textarea>
                <p>
		<strong>'._('Year')._(': ').'</strong>'.utils_requiredField().'<br />
		<input required="required" type="text" name="year" value="'.$pub->data_array['publication_year'].'" size="80" /></p>
		<p>
		<strong>'._('URL')._(': ').'</strong><br />
		<input type="text" name="url" value="'.$pub->data_array['url'].'" size="80" /></p>
		<p>';

	echo '<div><input type="submit" name="submit" class="btn-cta" value="'._('Update').'" />
		</div></form>';
		
	//echo "</div><!--main_col-->\n</div><!--display table-->\n</div><!--project_overview_main-->\n";
	
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
