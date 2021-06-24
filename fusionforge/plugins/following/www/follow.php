<?php

/**
 *
 * follow.php
 * 
 * Copyright 2005-2021, SimTK Team
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

require_once $gfwww.'news/news_utils.php';
require_once $gfwww.'include/trove.php';
require_once $gfwww.'include/project_summary.php';
require_once $gfcommon.'include/tag_cloud.php';
require_once $gfcommon.'include/HTTPRequest.class.php';
require_once $gfcommon.'widget/WidgetLayoutManager.class.php';

require_once $gfplugins.'following/www/following-utils.php';
require_once $gfplugins.'following/include/Following.class.php';

$group_id = htmlspecialchars(getStringFromRequest('group_id'));

//session_require_perm ('project_read', $group_id) ;

$title = _('Project Home');

$request =& HTTPRequest::instance();
$request->set('group_id', $group_id);

$params['submenu'] = '';

if (session_loggedin()) {
	$group = group_get_object($group_id);
	if (!$group || !is_object($group)) {
		exit_no_group();
	} elseif ($group->isError()) {
		exit_error($group->getErrorMessage(), 'home');
	}
        
        // get user
        $user = session_get_user(); // get the session user
        $user_name = $user->getUnixName();

        $following = new Following($group);
        if (!$following || !is_object($following)) {
           exit_error('Error','Could Not Create Following');
        } elseif ($following->isError()) {
           exit_error('Error',$following->getErrorMessage());
        }
 
        $navigation = new Navigation();

	// Display with the preferred layout/theme of the user (if logged-in)
	$perm =& $group->getPermission();
	if ($perm && is_object($perm) && $perm->isAdmin()) {
		$sql = "SELECT l.*
				FROM layouts AS l INNER JOIN owner_layouts AS o ON(l.id = o.layout_id)
				WHERE o.owner_type = $1
				AND o.owner_id = $2
				AND o.is_default = 1
				";
		$res = db_query_params($sql,array('g', $group_id));
		if($res && db_numrows($res)<1) {
			$lm = new WidgetLayoutManager();
			$lm->createDefaultLayoutForProject($group_id,1);
			$res = db_query_params($sql,array('g', $group_id));
		}
		$id = db_result($res, 0 , 'id');
		$params['submenu'] = $HTML->subMenu(
			array(_("Add widgets"),
				_("Customize Layout")),
			array('/widgets/widgets.php?owner=g'. $group_id .'&amp;layout_id='. $id,
				'/widgets/widgets.php?owner=g'. $group_id .'&amp;layout_id='. $id.'&amp;update=layout'),
			array(array('class' => 'tabtitle-nw', 'title' => _('Select new widgets to display on the project home page.')),
				array('class' => 'tabtitle', 'title' => _('Modify the layout: one column, multiple columns or build your own layout.'))));
	}
        $title = _('Following for ').$group->getPublicName();
}

html_use_jqueryui();

site_project_header(array('title'=>$title, 'group'=>$group_id, 'toptab'=>'following'));

?>

<div class="project_overview_main">
    <div style="display: table; width: 100%;"> 
        <div class="main_col">






<?php



if (session_loggedin()) {


	//handle form submit
	if (getStringFromRequest('submit')) 
        {

            if (!form_key_is_valid(getStringFromRequest('form_key'))) {
	           exit_form_double_submit('following');
            }
            $followertype = getHtmlTextFromRequest('followertype');
	

            if ($followertype == "private") {
              $public = "false";
            }
            else {
              $public = "true";
            }
 
	    if ($following->follow($user_name,$public,$group_id)) {
            //header("Location: index.php?group_id=$group_id");
		echo "<script type='text/javascript'>window.top.location='" .
			"index.php?group_id=$group_id" .
			"';</script>";
			exit;
        } 
        else {
           $feedback = $new_pub->getErrorMessage();
		   echo "Error: " . $feedback . "<br>";
        }

	} //handle form submit

        /*	
		Show the submit form
	*/
    echo "<h3>Would you like to follow this project privately or publically?</h3><br />";
	
	echo	'
		<form id="followingprivate" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="submit" value="y" />
		<input type="hidden" name="followertype" value="private" />
		<input type="hidden" name="form_key" value="'. form_generate_key() .'" />
		<p>';

	echo '<div><input class="btn btn-blue" type="submit" name="submit" value="'._('Private Follower').'" />
		</div></form>';

	echo	'
		<form id="followingpublic" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="submit" value="y" />
		<input type="hidden" name="followertype" value="public" />
		<input type="hidden" name="form_key" value="'. form_generate_key() .'" />
		<p>';

	echo '<div><input class="btn btn-blue" type="submit" name="submit" value="'._('Public Follower').'" />
		</div></form>';
	
	echo '<br /><p><a href="follow-info.php" target="_blank">What is the difference?</p>';

} else {

	exit_not_logged_in();

}




?>

        </div>
    </div>
</div>



<?php

site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
