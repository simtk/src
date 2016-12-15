<?php
/**
 * Change user's settings page
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010 (c) Franck Villaume
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/User.class.php';
require_once $gfplugins.'mypage/www/mypage-utils.php';
require_once $gfcommon.'include/Role.class.php';


session_require_login () ;

// get user
$user = session_get_user(); // get the session user
$username = $user->getUnixName();
//get the user object based on the user_name in the URL
$user = user_get_object_by_name($username);

if (!$user || !is_object($user) || $user->isError() || !$user->isActive()) {
  exit_error(_('That user does not exist.'));
}
		
if (getStringFromRequest('submit_notif')) {
	   $notification_freq = getIntFromRequest('notification_freq');
	   $user->updateFollowingNotification($notification_freq);
	   $feedback = "Email Notification Frequency updated.";
}
	
if (getStringFromRequest('unfollow')) {
	   $unfollow_group_id = getIntFromRequest('group_id');
	   removeFollowing($unfollow_group_id,$username);
	   $feedback = "Project Follow Deleted.";
}
	
if (getStringFromRequest('follow')) {
	   $follow_group_id = getIntFromRequest('group_id');
	   $public = getStringFromRequest('public');
	   if (addFollowing($username,$public,$follow_group_id)) {
	     $feedback = "Project Follow Added.";
	   }
}
	
// check projects user is following
	
$cntProjectsFollowing = 0;
$projectsFollowing = getProjectsFollowing($user,$cntProjectsFollowing);	

$title = _('Settings');
$HTML->header(array('title'=>$title));

echo '<h2 class="underlined">Account</h2>';

echo '<ul>';
echo '<li><a href="change_pw.php">Update password</a></li>';
echo '<li><a href="change_email.php">Update email</a></li>';
echo '</ul>';

echo '<h2 class="underlined">Following</h2>';


$notification = $user->getFollowingNotification();
if ($notification) {
  $frequency = db_result($notification, 0, 'notification_frequency');
}

// get all projects
$allprojects = getProjects();	
?>
<script>
$(function() {
    $('#notification_freq').change(function() {
        this.form.submit();
    });
});
</script>

	    
		<form id="followingform" action="<?php getStringFromServer('PHP_SELF'); ?>" method="post">
		    <b>Email Notification Frequency:</b>
	        <select name="notification_freq" id="notification_freq">
			<option value="0" <?php if ($frequency == 0) { echo "selected"; } ?>>Do not send email</option>
			<option value="1" <?php if ($frequency == 1) { echo "selected"; } ?>>Daily</option>
			<option value="7" <?php if ($frequency == 7) { echo "selected"; } ?>>Weekly</option>
			<option value="14" <?php if ($frequency == 14) { echo "selected"; } ?>>Every two weeks</option>
			</select>
            <input type="hidden" name="submit_notif" value="1" />
		</form>
		
		<br />
	    <b>Follow a Project:</b> 
		<div class="form_simtk">
		<form id="followingprojectform" action="<?php getStringFromServer('PHP_SELF'); ?>" method="post">
		
		<p>
	        <select name="group_id">
			<?php			
			$ra = RoleAnonymous::getInstance();
			$max_rows = db_numrows($allprojects);
			$j = 0;
	        for ($i = 0; $i < db_numrows($allprojects); $i++) {
	           $proj_id = db_result($allprojects, $i, 'group_id');
			   if ($ra->hasPermission('project_read', $proj_id)) {
			      $j++;
		          $group_name = db_result($allprojects, $i, 'group_name');
			      $group_name = (strlen($group_name) > 50) ? substr($group_name,0,50).'...' : $group_name;
		          echo "<option value='$proj_id'>$group_name</option>";
			   }
	        }
			
			?>
			</select>
            <p><input type="radio" name="public" value="true" checked>Public Follow
			<input type="radio" name="public" value="false">Private Follow  <a href="#" data-toggle="popover" data-placement="right"  data-trigger="hover" title="Following Projects" data-content="There are two types of project followers, public or private. Public follower: Others will see your name and profile in the list of project followers. By becoming a public follower, other members of SimTK may message you regarding your interest and involvement in the project. Private follower: Your name and profile will NOT be displayed in the list of followers.">(Public vs Private?)</a>
	    </p>
		<input type="hidden" name="follow" value="1">
		<input type="submit" name="followsubmit" class="btn-cta" value="Follow" />
		</p></form>
		</div>
		

		
	<?php
	//echo "max: " . $max_rows . "<br />";
	//echo "actual: " . $j . "<br />";
	
	echo '<br /><b>Followed Projects:</b>';
	if ($cntProjectsFollowing > 0) {
	   // index $i should be set above
	   foreach ($projectsFollowing as $proj) {
		  //
			echo '<div class="myproject_representation">'."\n";
			if (!empty($proj['simtk_logo_file'])) {
				echo '<div class="myproject_img">' .
					'<a href="/projects/' . $proj['unix_group_name'] . '">' . 
					'<img src="/logos/'.$proj['simtk_logo_file'].'">' .
					'</a>' .
					'</div>'."\n";
			}
			else {
				echo '<div class="myproject_img">' .
					'<a href="/projects/' . $proj['unix_group_name'] . '">' . 
					'<img src="/logos/_thumb">' .
					'</a>' .
					'</div>'."\n";
			}
			echo '<div class="wrapper_text"><h4><a href="/projects/' . 
				$proj['unix_group_name'] . 
				'" style="color:#5e96e1;">' . 
				$proj['group_name'] . 
				'</a>';
			echo ' <a class="btn btn-cta" role="button" href="'.getStringFromServer('PHP_SELF').'?unfollow=1&group_id='.$proj['group_id'].'">Unfollow</a>';
			echo '</h4></div>';
			echo '</div>'."\n"; // myproject_representation
		
       }
	} else {
	  echo "<p>You are not following any projects</p>";
	}

	
	
	?>
	
	


<?php
site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End: