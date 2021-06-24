<?php
/**
 *
 * category.php
 *
 * Admin file to manage keywords, ontology and categories for projects.
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
require_once $gfwww.'include/role_utils.php';
require_once $gfwww.'project/admin/project_admin_utils.php';
require_once $gfcommon.'include/GroupJoinRequest.class.php';

// Use jqeury-ui.
html_use_jqueryui();

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

$error_msg = "";

// Delete ontology.
if ($delOntology = htmlspecialchars(getStringFromRequest('valDelOntology'))) {
	$resOntology = $group->deleteOntology($delOntology);
	if (!$resOntology) {
		$theMsg = $group->getErrorMessage();
		if ($theMsg != "No Error") {
			$error_msg .= $theMsg;
		}
	}
}

// Delete keyword.
if ($delKeyword = htmlspecialchars(getStringFromRequest('valDelKeyword'))) {
	$resKeyword = $group->deleteKeyword($delKeyword);
	if (!$resKeyword) {
		$theMsg = $group->getErrorMessage();
		if ($theMsg != "No Error") {
			$error_msg .= $theMsg;
		}
	}
}
	
// Add keyword or ontology.
$keywords = getStringFromRequest('keywords');
$ontology = getStringFromRequest('ontology');
if (!empty($ontology) || !empty($keywords)) {
	$res = $group->updateCategory(session_get_user(), $ontology, $keywords);
	if (!$res) {
		$theMsg = $group->getErrorMessage();
		if ($theMsg != "No Error") {
			$error_msg .= $theMsg;
		}
	}
}

// If this was a submission, make updates
if ($submit = getStringFromRequest('submit')) {

	$categories = htmlspecialchars(getStringFromRequest('categories'));
	$resTroveGroupLink = $group->updateTroveGroupLink($categories);
	if (!$resTroveGroupLink) {
		$theMsg = $group->getErrorMessage();
		if ($theMsg != "No Error") {
			$error_msg .= $theMsg;
		}
	}
	if (empty($error_msg)) {
		$feedback .= _('Project information updated');
	}

	if (getStringFromRequest('wizard')) {
		header("Location: category.php?group_id=$group_id&wizard=1");
	}
}

/*
if ($remove = getStringFromRequest('remove')) {

    
    if ($remove == "ontology") {
	  $ontology = htmlspecialchars(getStringFromRequest('ontology'));
	  $res = $group->deleteOntology($ontology);
	} else if ($remove == "keyword") {
	  $keyword = htmlspecialchars(getStringFromRequest('keyword'));
	  $res = $group->deleteKeyword($keyword);
	}

	if (!$res) {
		$error_msg .= $group->getErrorMessage();
	} else {
		$feedback .= _('Project information updated');
	}

}
*/

// Do this after submit.
$keywordsArray = array();
$sql = "SELECT DISTINCT keyword FROM project_keywords where project_id = $1" . 
	" order by keyword";
$resKeywords = db_query_params($sql, array($group->getID()));
$numRowsKeywords = db_numrows($resKeywords);
for ($i=0; $i<$numRowsKeywords; $i++) {
	$keywordsArray[] = db_result($resKeywords, $i, 'keyword');
}

$ontologyArray = array();
$sql = "SELECT DISTINCT bro_resource FROM project_bro_resources WHERE project_id = $1" . 
	" ORDER BY bro_resource ASC";
$resOntology = db_query_params($sql, array($group->getID()));
$numRowsOntology = db_numrows($resOntology);
for ($i=0; $i<$numRowsOntology; $i++) {
	$ontologyArray[] = db_result($resOntology, $i, 'bro_resource');
}

project_admin_header(array('title'=>'Admin','group'=>$group->getID()));

?>

<style>
.myButton {
	height: 18px;
	width: auto;
}
</style>

<script>

$(function() {
	$( "#keywords" ).autocomplete({
		source: "getkeywordsajax.php",
		minLength: 1,
	});
});

$(function() {
	$( "#ontology" ).autocomplete({
		source: "getontologyajax.php",
		minLength: 1,
	});
});
  
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
  
<table class="my-layout-table">
<tr>
<td>

<?php 

	if (getStringFromRequest('wizard')) {
		echo $HTML->boxTop(_('<h3>Continue Project Setup - Category/Communities</h3>'));
	}
	else {
		//echo $HTML->boxTop(_('<h3>Category/Communities</h3>'));
	}
?>

<form id="myForm" action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<input type="hidden" name="group_id" value="<?php echo $group->getID(); ?>" />
<input type="hidden" id="valDelKeyword" name="valDelKeyword" value=""/>
<input type="hidden" id="valDelOntology" name="valDelOntology" value=""/>

<?php
if (getStringFromRequest('wizard')) {
?>

<input type="hidden" name="wizard" value="1" />

<?php
}
?>

<h2><?php echo _('Keywords'); ?></h2>
<p>

<?php

if ($numRowsKeywords <= 0) {
	echo "<b>No keywords</b><br />";
}
else {
	for ($i=0; $i<$numRowsKeywords; $i++) {
		echo db_result($resKeywords, $i, 'keyword') .
			" <input class='myButton' " .
			"type='image' " .
			"name='delKeyword' " .
			"onclick='$(\"#valDelKeyword\").val(\"" .
			db_result($resKeywords, $i, 'keyword') .
			"\");' " .
			"src='/themes/simtk/images/list-remove.png' " .
			"alt='Delete Keyword'><br/>";
	}
}
	
?>

</p>

<p>
<input type="text" id="keywords" name="keywords" size="30" maxlength="80" /> 
<input class="myButton" type="image" src="/themes/simtk/images/list-add.png" alt="Add Keyword">
</p>

<h2><?php echo _('Ontology'); ?></h2>
<p>

<?php

if ($numRowsOntology <= 0) {
	echo "<b>No ontology terms</b><br />";
}
else {
	for ($i=0; $i<$numRowsOntology; $i++) {
		echo db_result($resOntology, $i, 'bro_resource') .
			" <input class='myButton' " .
			"type='image' " .
			"name='delOntology' " .
			"onclick='$(\"#valDelOntology\").val(\"" .
			db_result($resOntology, $i, 'bro_resource') .
			"\");' " .
			"src='/themes/simtk/images/list-remove.png' " .
			"alt='Delete Ontology'><br />";
	}
}

?>

<p>
<input type="text" id="ontology" name="ontology" size="30" maxlength="80" /> 
<input class="myButton" type="image" src="/themes/simtk/images/list-add.png" alt="Add Ontology">
</p>

<?php

$troveCatLinkArr = $group->getTroveGroupLink();
$troveCatLinkPendingArr = $group->getTroveGroupLinkPending();
$resultPrimary = getPrimaryContent();
$resultBioApp = getBiologicalApplications();
$resultBioFocus = getBiocomputationalFocus();

echo "<h2>Primary Content of Your SimTK Project</h2>";

while ($row = db_fetch_array($resultPrimary)) {
	echo '<input type="checkbox" name="categories[]" value="' . $row['trove_cat_id'] . '"';
	if ((isset($troveCatLinkArr[$row['trove_cat_id']]) &&
		$troveCatLinkArr[$row['trove_cat_id']])) {
		echo "checked";
	}

	echo '> ' . $row['fullname'] .
		' <span class="myPopOver">' .
		'<a href="javascript://" class="popoverLic" data-html="true" ' .
		'data-toggle="popover" data-placement="right" data-content="' .
		$row['simtk_intro_text'] . '">?</a></span><br/>';
}

echo "<h2>Biological Applications for Your Project</h2>";

while ($row = db_fetch_array($resultBioApp)) {
	echo '<input type="checkbox" name="categories[]" value="' . 
		$row['trove_cat_id'] . '"';
	if ((isset($troveCatLinkArr[$row['trove_cat_id']]) &&
		$troveCatLinkArr[$row['trove_cat_id']])) {
		echo "checked";
	}

	echo '> ' . $row['fullname'] .
		' <span class="myPopOver">' .
		'<a href="javascript://" class="popoverLic" data-html="true" ' .
		'data-toggle="popover" data-placement="right" data-content="' .
		$row['simtk_intro_text'] . '">?</a></span><br/>';
}

echo "<h2>Biocomputational Focus of Your Project</h2>";

while ($row = db_fetch_array($resultBioFocus)) {
	echo '<input type="checkbox" name="categories[]" value="' . 
		$row['trove_cat_id'] . '"';
	if ((isset($troveCatLinkArr[$row['trove_cat_id']]) &&
		$troveCatLinkArr[$row['trove_cat_id']])) {
		echo "checked";
	}

	echo '> ' . $row['fullname'] .
		' <span class="myPopOver">' .
		'<a href="javascript://" class="popoverLic" data-html="true" ' .
		'data-toggle="popover" data-placement="right" data-content="' .
		$row['simtk_intro_text'] . '">?</a></span><br/>';
}

// This function is used to render checkboxes below
function c($v) {
	if ($v) {
		return 'checked="checked"';
	}
	else {
		return '';
	}
}

if (getStringFromRequest('wizard')) {

?>

<p>
<input type="submit" name="submit" value="<?php echo _('Save and Continue') ?>" />
</p>

<?php
}
else {
?>

<br/>
<p>
<input type="submit" class="btn-cta" name="submit" value="<?php echo _('Update') ?>" />
</p>

<?php
}
?>

</form>

<?php
plugin_hook('hierarchy_views', array($group_id, 'admin'));
echo $HTML->boxBottom();
?>

</td>
</tr>
</table>

<?php

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
