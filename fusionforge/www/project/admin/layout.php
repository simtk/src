<?php
/**
 * layout.php
 * 
 * Admin page for controlling project main page layout such as publication vs standard, etc.
 * 
 * Copyright 2005-2023, SimTK Team
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

$group_id = getIntFromRequest('group_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$group->clearError();


// If this was a submission, make updates
if (getStringFromRequest('submit')) {
	
	$display_news = getStringFromRequest('form_display_news');
	$display_downloads = getStringFromRequest('form_display_downloads');
	$display_related = getStringFromRequest('form_display_related');
	$display_download_pulldown = getStringFromRequest('form_display_download_pulldown');
	$form_download_description = getStringFromRequest('form_download');
	$form_layout = getStringFromRequest('form_layout');
	$display_funderinfo = getStringFromRequest('display_funderinfo');

	$res = $group->updateLayout(session_get_user(), $display_news, $display_related, $display_downloads, $display_download_pulldown, $form_download_description, $form_layout, $display_funderinfo); 

	if (!$res) {
		$error_msg .= $group->getErrorMessage();
	} else {
		$feedback .= _('Project information updated');
	}
        if (getStringFromRequest('wizard')) {
           header("Location: settings.php?group_id=$group_id&wizard=1");
        }
}

// get current values
$display_news = $group->getDisplayNews();
$display_downloads = $group->getDisplayDownloads();
$display_related = $group->getDisplayRelated();
$display_download_pulldown = $group->getDisplayDownloadPulldown();
$layout = $group->getLayout();
$display_funderinfo = $group->getDisplayFunderInfo();

project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

if (getStringFromRequest('wizard')) {
	echo $HTML->boxTop(_('<h3>Continue Project Setup - Layout</h3>'));
}
else {
	//echo $HTML->boxTop(_('<h3>Layout</h3>'));
}

?>

<script>

$(document).ready(function() {
	// Handle popover show and hide.
	$(".myPopOver").hover(function() {
		$(this).find(".popoverLic").popover("show");
	});
	$(".myPopOver").mouseleave(function() {
		$(this).find(".popoverLic").popover("hide");
	});
});

</script>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post" enctype="multipart/form-data">

<div class="form_simtk">

<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<?php if (getStringFromRequest('wizard')) { ?>
  <input type="hidden" name="wizard" value="1" />
<?php } ?>

Layout of the Project's Overview

<p>
<input type="radio" name="form_layout" value="0" <?php if ($layout == 0) { echo " checked";} ?> /> Standard <span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" title="Standard Project" data-content="A Standard Project will display the description of the project at the top of the project home page">?</a></span><br/>
<input type="radio" name="form_layout" value="1" <?php if ($layout == 1) { echo " checked";} ?> /> Publication <span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right"  title="Publication Project" data-content="A Publication Project will display the primary publication at the top of the project home page.  A primary publication must exist and the download description section must be completed.">?</a>
</p>

Display Funder Information [<a href="/project/admin/manageFunders.php?group_id=<?php echo $group->getID(); ?>">Add/Update Funder Information</a>]

<p>
	<input type="radio" name="display_funderinfo" value="0" <?php if ($display_funderinfo == 0) { echo " checked";} ?> /> No <br />

	<input type="radio" name="display_funderinfo" value="1" <?php if ($display_funderinfo == 1) { echo " checked";} ?> /> Yes <br />
</p>

<?php if ($group->usesFRS()) { ?>

Display Download Pulldown Menu [<a href="/frs/admin/?group_id=<?php echo $group->getID(); ?>">Select Download Items for Pulldown Menu</a>]

<p>
<input type='radio' name="form_display_download_pulldown" value="0" <?php if ($display_download_pulldown == 0) { echo " checked";} ?> /> No<br />
<input type='radio' name="form_display_download_pulldown" value="1" <?php if ($display_download_pulldown == 1) { echo " checked";} ?> /> Yes<br />
</p>

<?php } else { ?>

  <input type="hidden" name="form_display_download_pulldown" value="0">

<?php } ?>

<?php if ($group->usesPlugin("simtk_news")) { ?>

Display News Section [<a href="/plugins/simtk_news/admin/?group_id=<?php echo $group->getID(); ?>">Select News Items for Project Overview</a>]

<p>
<input type="radio" name="form_display_news" value="0" <?php if ($display_news == 0) { echo " checked";} ?> /> No<br />
<input type="radio" name="form_display_news" value="1" <?php if ($display_news == 1) { echo " checked";} ?> /> Yes<br />
</p>

<?php } else { ?>

  <input type="hidden" name="form_display_news" value="0">

<?php } ?>

<?php if ($group->usesFRS()) { ?>

Display Downloads Section 

<p>
<input type="radio" name="form_display_downloads" value="0" <?php if ($display_downloads == 0) { echo " checked";} ?> /> No<br />
<input type="radio" name="form_display_downloads" value="1" <?php if ($display_downloads == 1) { echo " checked";} ?> /> Yes<br />
</p>

<div style="margin-left: 25px;">
Download Description

<p>
<textarea cols="80" rows="3" name="form_download">
<?php echo $group->getDownloadDescription(); ?>
</textarea>
</p>
</div>

<?php } else { ?>

  <input type="hidden" name="form_display_downloads" value="0">

<?php } ?>

Display Related Projects Section [<a href="/project/admin/related_projects.php?group_id=<?php echo $group->getID(); ?>">Select Related Projects for Project Overview</a>]

<p>
<input type="radio" name="form_display_related" value="0" <?php if ($display_related == 0) { echo " checked";} ?> /> No<br />
<input type="radio" name="form_display_related" value="1" <?php if ($display_related == 1) { echo " checked";} ?> /> Yes<br />
</p>


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
plugin_hook('hierarchy_views', array($group_id, 'admin'));

echo $HTML->boxBottom();?>

<?php

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
