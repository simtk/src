<?php
/**
 * FusionForge Project Home
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010, FusionForge Team
 * Copyright (C) 2011-2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013, Franck Villaume - TrivialDev
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
require_once $gfcommon.'include/User.class.php';

$group_id = getStringFromRequest('group_id');

$title = _('Project Home');

$request =& HTTPRequest::instance();
$request->set('group_id', $group_id);

$params['submenu'] = '';

// Getting information for following does not require to log in.
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(), 'home');
}
$following = new Following($group);
        
$followtype = getIntFromRequest('followtype');
if ($followtype == 1 || $followtype == 2) {
	// Following project requires user to be logged in.
	session_require_login();
}

if (session_loggedin()) {
        // get user
        $user = session_get_user(); // get the session user
        $user_name = $user->getUnixName();
        //get the user object based on the user_name in the URL
		/*
        $user = user_get_object_by_name($user_name);

        if (!$user || !is_object($user) || $user->isError() || !$user->isActive()) {
          exit_error(_('That user does not exist.'));
        }
		*/
		
        if (!$following || !is_object($following)) {
           exit_error('Error','Could Not Create Following');
        } elseif ($following->isError()) {
           exit_error('Error',$following->getErrorMessage());
        }
 
        $navigation = new Navigation();

        $title = _('Following for ').$group->getPublicName();
}

html_use_jqueryui();


site_project_header(array('title'=>$title, 'group'=>$group_id, 'toptab'=>'following'));

?>

<div class="project_overview_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">

<?php

	$type = getStringFromRequest('type');
	$unfollow = getIntFromRequest('unfollow');
	$pluginname = getStringFromRequest('pluginname');

	// DO THE STUFF FOR THE PROJECT PART HERE

	if ($unfollow == 1) {
		$following->unfollow($group_id,$user_name);
		$homepage = "/projects/" . $group->getUnixName() . "/";
		header("Location: $homepage");
		exit;
	}
	else if ($followtype == 1) {
		// Public following.
		$following->follow($user_name, "true", $group_id,$user_name);
		$homepage = "/projects/" . $group->getUnixName() . "/";
		header("Location: $homepage");
		exit;
	}
	else if ($followtype == 2) {
		// Private following.
		$following->follow($user_name, "false", $group_id,$user_name);
		$homepage = "/projects/" . $group->getUnixName() . "/";
		header("Location: $homepage");
		exit;
	}

	// Check permissions before showing further contents.
	if (forge_check_perm('project_read', $group_id)) {
		$result = $following->getFollowing($group_id);
		if ($result === false) {
			// Cannot fetch information.
			echo '<p class="warning_msg">Followers information is not available.</p>';
		}
		else {
			// get public count
			$public_following_count = $following->getPublicFollowingCount($group_id);
			// get private count
			$private_following_count = $following->getPrivateFollowingCount($group_id);

			echo "<h3>$public_following_count public followers and $private_following_count private followers</h3>";
			echo "<p><a href='follow-info.php?group_id=$group_id'>What does it mean to follow a project?</a></p>";

			if ($public_following_count > 0) {
				// Display users.
				$public_result = $following->getPublicFollowing($group_id);

				echo '<div class="container">';
				foreach ($public_result as $public_result_list) {
					$user = user_get_object_by_name($public_result_list->user_name);
					if (!$user || 
						!is_object($user) || 
						$user->isError() || 
						!$user->isActive()) {
						continue;
					}

					$picture_file = $user->getPictureFile();
					if (empty($picture_file)) {
						$picture_file = "user_profile.jpg";
					}
					echo '<div class="row">';
					echo '<div class="col-md-2">';
					echo '<div class="following_member">';
					echo '<a href="/users/' . 
						$public_result_list->user_name . '">' .
						'<img ' .
						' onError="this.onerror=null;this.src=' . 
						"'" . '/userpics/user_profile.jpg' . "';" . '"' .
						' src="/userpics/' . $picture_file . '"' .
						' alt="Image not available" /></a>';
					echo "</div> <!-- /.following_member -->";
					echo "</div>";
					echo '<div class="col-md-10">';

					echo '<br /><a href="/users/' . 
						$public_result_list->user_name . 
						'">' . $user->getRealName() . 
						'</a><p>';
					$interest = $user->getSimTKInterest();
					if (!empty($interest)) {
						echo $interest . "<br />";
					}
					$university = $user->getUniversityName();
					if (isset($university)) {
						echo 'Institution: ' . $university;
					}
					echo "</p></div>";
					echo "</div>";
				} // foreach

				echo "</div>";
			} // if public_following_count
		} // if has followers
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
