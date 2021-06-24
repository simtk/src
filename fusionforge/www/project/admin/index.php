<?php
/**
 * Project Admin Main Page
 *
 * This page contains administrative information for the project as well
 * as allows to manage it. This page should be accessible to all project
 * members, but only admins may perform most functions.
 *
 * Copyright 2004 GForge, LLC - Tim Perdue
 * Copyright 2010, Franck Villaume - Capgemini
 * Copyright 2010-2011, Alain Peyrat - Alcatel-Lucent
 * Copyright 2016-2021 Tod Hing, Henry Kwong - SimTK Team
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
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfcommon.'include/GroupJoinRequest.class.php';
require_once $gfplugins.'simtk_news/include/Simtk_news.class.php';
require_once $gfplugins.'following/include/Following.class.php';

$group_id = getIntFromRequest('group_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$group->clearError();

// If this was a submission, make updates
if (getStringFromRequest('submit')) {
	$form_group_name = getStringFromRequest('form_group_name');
	$form_shortdesc = getStringFromRequest('form_shortdesc');
	$form_summary = htmlspecialchars(getStringFromRequest('form_summary'));
	$logo_tmpfile = getStringFromRequest('logofilename');
	$logo_type = htmlspecialchars(getStringFromRequest('logofiletype'));
	$private = getStringFromRequest('private');
        
	// if private is 0, then booleanparam will be 0, otherwise set booleanparam to 1
	// private = 0 means project is not public.
	$feedback_more = "";	
	if (isset($private) && $private == "0") {
		//echo "private: " . $private . "<br />";
		$private_param = 0;
		// Disable global news.
		$simtk_news = new Simtk_news($group);
		if ($simtk_news->globalDisplayExist($group_id)) {
			$feedback_more = " (Notice: News displayed on the SimTK Site have been removed)";
		}
		$simtk_news->updateDisplayGlobalGroupID($group_id,0);
		$simtk_news->updateRequestGlobalGroupID($group_id,0);

		// Remove followers who are not member of the private project.
		$following = new Following($group);
		// Get all followers.
		$allFollowers = $following->getFollowing($group_id);
		if ($allFollowers !== false) {
			// Has followers.
			// Get all members of project.
			$theUsers = $group->getUsers();
			// Check each follower.
			foreach ($allFollowers as $followers_list) {
				// Get follower's user name.
				$nameFollower = strtolower($followers_list->user_name);

				// Check whether follower is member of project.
				$isMember = false;
				foreach ($theUsers as $user) {
					if ($user->getUnixName() == $nameFollower) {
						// User is a member of project. Done.
						$isMember = true;
						break;
					}
				}
				if (!$isMember) {
					// Follower is not a member of the private project.
					// Remove from followers.
					$following->unfollow($group_id, $followers_list->user_name);
				}
			}
		}
	}
	else {
		$private_param = 1;
	}
	
	$res = $group->updateInformation(
		session_get_user(),
		$form_group_name,
		$form_shortdesc,
                $form_summary,
                $logo_tmpfile,
                $logo_type,
				$private_param
	);

	if (!$res) {
		$error_msg .= $group->getErrorMessage();
	} else {
		$feedback .= _('Project information updated');
		$feedback .= $feedback_more;
	}
       if (getStringFromRequest('wizard')) {
           header("Location: tools.php?group_id=$group_id&wizard=1");
        }
}

project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

?>

<link rel='stylesheet' href='/account/register.css' type='text/css' />
<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
<link rel="stylesheet" href="/js/jquery.fileupload.css">
<script src='/js/jquery-ui-1.10.1.custom.min.js'></script>
<!-- The Load Image plugin for previewing images and image resizing -->
<script src="/js/load-image.all.min.js"></script>
<!-- The basic File Upload plugin -->
<script src="/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="/js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="/js/jquery.fileupload-image.js"></script>
<!-- The File Upload validation plugin -->
<script src="/js/jquery.fileupload-validate.js"></script>
<script src='logoUploadHandler.js'></script>

<style>
#fileuploadErrMsg {
	color: #f75236;
	font-size: 12px;
	padding-top: 5px;
}
#fileuploadErrMsg>img {
	float: left;
	padding: 0px;
	margin: 0px;
	width: 16px;
	height: 16px;
}
#fileuploadErrMsg>span {
	padding-left: 5px;
}
</style>

<script type="text/javascript">
function ShowPopup(hoveritem)
{
	hp = document.getElementById("titlepopup");

	// Set position of hover-over popup
	hp.style.top = hoveritem.offsetTop + 18;
	hp.style.left = hoveritem.offsetLeft + 20;

	// Set popup to visible
	hp.style.visibility = "Visible";
}

function HidePopup()
{
	hp = document.getElementById("titlepopup");
	hp.style.visibility = "Hidden";
}
</script>

<table class="my-layout-table">
	<tr>
		<td>

<?php 

       if (getStringFromRequest('wizard')) {
         echo $HTML->boxTop(_('<h3>Continue Project Setup - Information</h3>'));
       } else {
//         echo $HTML->boxTop(_('<h3>Project Information</h3>'));
       }

?>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post" enctype="multipart/form-data">
<div class="row">
<div class="col-sm-10">

<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<?php if (getStringFromRequest('wizard')) { ?>
  <input type="hidden" name="wizard" value="1" />
<?php } ?>

<h2><?php echo _('Project Title'); ?></h2>
<p><b>Restrictions: 3-80 characters</b>
</p>

<p>
<input type="text" name="form_group_name" value="<?php echo $group->getPublicName(); ?>" size="60" maxlength="80" /> 
</p>

<h2><?php echo _('Summary'); ?></h2>
<p>Your project summary appears on your project overview page and in the search results. <b>Restrictions:  10-255 characters</b></p>

<p>
<textarea cols="80" rows="3" name="form_summary">
<?php echo $group->getSummary(); ?>
</textarea>
</p>

<h2><?php echo _('Description'); ?></h2>
<p>The long description of your project allows you to provide more details about it.
</p>

<p>
<textarea cols="80" rows="20" name="form_shortdesc">
<?php echo $group->getDescription(); ?>
</textarea>
</p>

<h2><?php echo _('Logo') ?></h2>

<p><b>Restrictions: Less than 2 MB. Must be PNG, JPG, or GIF image format. For best results, image should be perfect square.</b></p>

    <div class="submodule_picture">
        <div id="fileDataDiv"></div>
		<div class="logo_wrapper"><?php

	if (trim($group->getLogoFile() != "")) {
		// Has logo file.
		// Force image to refresh; otherwise, the cached image 
	    // would be used which may be old.
		echo '<img src="/logos/'.$group->getLogoFile() . '_thumb?dummy_value=' . rand() . '" />';
	}
	else {
		// Show a default logo file.
		//echo "_thumb";
	}

    ?></div> <!-- logo wrapper -->
		
		<p>
        <div class="drag_and_drop_wrapper">
			<div class="div_drag_and_drop" id="div_drag_and_drop"><p>To add your image, drag and drop a file into this box or select a file from your computer.</p>
				<span class="btn btn-success fileinput-button">
					<i class="glyphicon"></i>
					<span>Browse...</span>
					<input type="file" name="files[]" id="fileupload" />
				</span>
			</div> <!-- div_drag_and_drop -->
		</div> <!-- drag_and_drop_wrapper -->
		</p>
    </div> <!-- submodule_picture -->
    <div id="fileuploadErrMsg"></div><br/>

<br />
<h2><?php echo _('Privacy'); ?></h2>

<p>
<input type="checkbox" name="private" value="0" <?php if (!$group->isPublic()) { echo 'checked="checked"'; } ?>/> Make entire project private - only title is publicly viewable
</p>

<p><b>This option is discouraged. Instead, we suggest independently limiting access to individual sections of the project.</b>
</p>

<h2>Social Media (Beta)</h2>
<p>To add social media links to your SimTK project, <a href="/sendmessage.php?touser=101&subject=<?php
        echo urlencode("Add project's social media pages to " . $group->getPublicName() . ".");
?>">contact the SimTK Webmaster</a>.</p>

<?php
// This function is used to render checkboxes below
function c($v) {
	if ($v) {
		return 'checked="checked"';
	} else {
		return '';
	}
}
?>

<?php
if (getStringFromRequest('wizard')) { ?>

   <p>
   <input type="submit" name="submit" value="<?php echo _('Save and Continue') ?>" />
   </p>

  <?php      }
else { ?>

<br />
<p>
<input type="submit" class="btn-cta" name="submit" value="<?php echo _('Update') ?>" />
</p>

<?php } ?>

</div> <!-- col-sm-10 -->
</div> <!-- row-->
</form>

<?php
plugin_hook('hierarchy_views', array($group_id, 'admin'));

echo $HTML->boxBottom();?>

		</td>
	</tr>
</table>

<?php

echo "</div><!--main_col-->\n</div><!--display table-->\n</div><!--project_overview_main-->\n";

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
