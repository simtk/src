<?php

/**
 *
 * settings.php
 * 
 * File to layout setting of project.
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
 
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfcommon.'include/GroupJoinRequest.class.php';
require_once $gfplugins.'simtk_news/include/Simtk_news.class.php';


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
	
	$form_layout = getStringFromRequest('form_layout');
	$private = getStringFromRequest('private');
	 
	// if private is 0, then booleanparam will be 0, otherwise set booleanparam to 1
	// private = 0 means project is not public.
	$feedback_more = "";
	if (isset($private) && $private == "0") {
	  //echo "private: " . $private . "<br />";
	  $private_param = 0;
	  // disable global news
	  $simtk_news = new Simtk_news($group);  
	  if ($simtk_news->globalDisplayExist($group_id)) {
	    $feedback_more = " (Notice: News displayed on the SimTK Site have been removed)"; 
	  }
	  $simtk_news->updateDisplayGlobalGroupID($group_id,0);
	  $simtk_news->updateRequestGlobalGroupID($group_id,0);
	} else {
	  $private_param = 1;
	}
	$res = $group->updateSettings(session_get_user(),$form_layout,$private_param);

	if (!$res) {
		$error_msg .= $group->getErrorMessage();
	} else {
		$feedback .= _('Project Settings Updated');
		$feedback .= $feedback_more;
	}
    if (getStringFromRequest('wizard')) {
      header("Location: wizard.php?group_id=$group_id&wizard=1");
    }
	
}

// get current layout for project, important to make this call after the updateLayout call.
$layout = $group->getLayout();

project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

?>

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
         echo $HTML->boxTop(_('<h3>Continue Project Setup - Settings</h3>'));
       } else {
//         echo $HTML->boxTop(_('<h3>Settings</h3>'));
       }

?>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post" enctype="multipart/form-data">

<div class="form_simtk">

<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<?php if (getStringFromRequest('wizard')) { ?>
  <input type="hidden" name="wizard" value="1" />
<?php } ?>

Layout of the Project's Overview

<p>
<input type="radio" name="form_layout" value="0" <?php if ($layout == 0) { echo " checked";} ?> /> Standard <a href="#" data-toggle="popover" data-placement="right" data-trigger="hover" title="Standard Project" data-content="A Standard Project will display the description of the project at the top of the project home page">?</a><br />
<input type="radio" name="form_layout" value="1" <?php if ($layout == 1) { echo " checked";} ?> /> Publication <a href="#" data-toggle="popover" data-placement="right"  data-trigger="hover" title="Publication Project" data-content="A Publication Project will display the primary publication at the top of the project home page.  A primary publication must exist and the download description section must be completed.">?</a>
</p>

Privacy <a href="#" data-toggle="popover" data-placement="right" data-trigger="hover" title="Privacy" data-content="Checking the box below prevents access to all subsections of your project (team, documents, source control, downloads, etc.). Only your overview page remains publicly viewable. The privacy of download packages, documents, and source control, can be independently controlled. This is the preferred way to manage the privacy of your project.">?</a>

<p>
<?php //echo "isPublic: " . $group->isPublic() . "<br />"; ?>

<input type="checkbox" name="private" value="0" <?php if (!$group->isPublic()) { echo 'checked="checked"'; } ?>/> Keep project private.
</p>

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

<p>
<input type="submit" class="btn-cta" name="submit" value="<?php echo _('Update') ?>" />
</p>

<?php } ?>

</div>

</form>

<?php

echo $HTML->boxBottom();?>

		</td>
	</tr>
</table>

<?php

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
