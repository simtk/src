<?php

/**
 *
 * search.php
 * 
 * File to display search results of projects.
 * 
 * Copyright 2005-2020, SimTK Team
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/trove.php';
require_once $gfcommon.'include/FusionForge.class.php';

require_once '../category/category_utils.php';

if (!forge_get_config('use_trove')) {
	exit_disabled('home');
}

// Allow alternate content-type rendering by hook
$default_content_type = 'text/html';

$script = 'trove_list';
$content_type = util_negociate_alternate_content_types($script, $default_content_type);

if($content_type != $default_content_type) {
	$hook_params = array();
	$hook_params['accept'] = $content_type;
	$hook_params['return'] = '';
	$hook_params['content_type'] = '';
	plugin_hook_by_reference('content_negociated_trove_list', $hook_params);
	if($hook_params['content_type'] != ''){
		header('Content-type: '. $hook_params['content_type']);
		echo $hook_params['content'];
	}
	else {
		header('HTTP/1.1 406 Not Acceptable',true,406);
	}
	exit(0);
}

global $cat_id, $rows;
$cat_id = getIntFromRequest("cat");
$strSort = getStringFromRequest("sort");
$strSort = strtolower($strSort);
if ($strSort != "date" && 
	$strSort != "title" &&
	$strSort != "downloads" &&
	$strSort != "relevance") {
	$strSort = "relevance";
}

$srch = "";
if (isset($_GET["type_of_search"])) {
	// Get search type.
	$typeSearch = $_GET["type_of_search"];
	$theRegex = '/[^a-z_]/i';
	$notValid = preg_match($theRegex, $typeSearch);
	if ($notValid) {
		$typeSearch = SEARCH__TYPE_IS_SOFTWARE;
	}
}
if (isset($_GET["srch"])) {
	// Get search string.
	$srch = $_GET["srch"];
	$theRegex = '/[^a-z0-9_ :\-"\']/i';
	$notValid = preg_match($theRegex, $srch);
	if ($notValid) {
		// Ignore input.
		session_redirect("/search/search.php?type_of_search=$typeSearch&srch=___ERROR___");
	}
}
if (isset($typeSearch) && $typeSearch == "people") {
	// People search.
	session_redirect("/search/searchPeople.php?type_of_search=$typeSearch&srch=$srch");
}
/*
else {
	// Project search.
	// Display ',' instead of ' '.
	//$srch = str_replace(' ', ' OR ', $srch);
}
*/

$HTML->header(array('title'=>'Search','pagename'=>''));


/*
// The parameter "cat" can be a comma separated string upon user selecting different categories.
// The originally selected cateogry remains at the beginning.
// Only pick the first value after exploding this array to get the cateogry. Otherwise, the
// following db selection fails! HK.
if (isset($cat_id) && trim($cat_id) != "") {
	// Has category id.
	$catIds = explode(',', $cat_id);
	if (count($catIds) > 0) {
		$cat_id = $catIds[0];
	}
}
*/


// Get cat_id and fullname for each category in each group.
$grpICatIds = array();
$grpIFullNames = array();
getCategoryInfo(404, $grpICatIds, $grpIFullNames, $grpIParentFullName);
foreach ($grpICatIds as $i=>$v) {
	//echo "<h4>GROUP I $grpIParentFullName $v: $grpIFullNames[$i] </h4>";
}

$grpIICatIds = array();
$grpIIFullNames = array();
getCategoryInfo(408, $grpIICatIds, $grpIIFullNames, $grpIIParentFullName);
foreach ($grpIICatIds as $i=>$v) {
	//echo "<h4>GROUP II $grpIIParentFullName $v: $grpIIFullNames[$i] </h4>";
}

$grpIIICatIds = array();
$grpIIIFullNames = array();
getCategoryInfo(403, $grpIIICatIds, $grpIIIFullNames, $grpIIIParentFullName);
foreach ($grpIIICatIds as $i=>$v) {
	//echo "<h4>GROUP III $grpIIIParentFullName $v: $grpIIIFullNames[$i] </h4>";
}


// Get category id and full name given the specified parent id.
function getCategoryInfo($parentId, &$grpCatIds, &$grpFullNames, &$parentFullName) {

	$catInfo = array();

	$sql = "SELECT trove_cat_id, fullname FROM trove_cat where parent=$1 order by fullname";
	$result = db_query_params($sql, array($parentId));
	$rows = db_numrows($result); 
	for ($i = 0; $i < $rows; $i++) {
		$grpCatIds[] = db_result($result, $i, 'trove_cat_id');
		$grpFullNames[] = db_result($result, $i, 'fullname');
	}
	db_free_result($result);

	$sql = "SELECT fullname FROM trove_cat where trove_cat_id=$1";
	$result = db_query_params($sql, array($parentId));
	$rows = db_numrows($result); 
	for ($i = 0; $i < $rows; $i++) {
		$parentFullName = db_result($result, $i, 'fullname');
	}
	db_free_result($result);
}


// Count projects that has downloads. HK.
// Note: Should use LEFT JOIN here too, like ajax_categories.php. HK.
$sql = "SELECT *,
	CASE WHEN g.group_id IN (
		SELECT group_id FROM frs_file ff 
		LEFT JOIN frs_release fr ON ff.release_id=fr.release_id 
		LEFT JOIN frs_package fp ON fp.package_id=fr.package_id 
		WHERE fp.is_public=1 
		AND fp.status_id=1 
		AND fr.status_id=1
	) THEN 1 
	ELSE 0 
	END AS has_public_package,
	0 as is_model
	FROM trove_group_link AS t 
	RIGHT JOIN (SELECT group_id group_id, unix_group_name, group_name, simtk_logo_file, simtk_summary, simtk_short_description, status, simtk_is_public, simtk_is_system FROM groups) AS g ON t.group_id=g.group_id 
	LEFT JOIN (SELECT group_id, MAX(adddate) AS modified FROM group_history GROUP BY group_id) AS gh ON g.group_id=gh.group_id
	LEFT JOIN (SELECT group_id as dls_group_id, downloads as dls_downloads from frs_dlstats_grouptotal_vw) as dls ON dls_group_id=g.group_id";

//if (isset($_GET["all_groups"]) && $_GET["all_groups"] == 1) {
	// Include both private and public projects by default.
	$sql .= " WHERE status = 'A' " .
		"AND NOT simtk_is_system IS NULL ";
//}
//else {
	// Include public projects only.
	//$sql .= " WHERE simtk_is_public = 1 AND status = 'A' " . 
		//"AND NOT simtk_is_system IS NULL ";
//}

if ($cat_id != 0) {
	// Has category id.
	$sql .= "AND trove_cat_id=$1 ";
}

$sql .= "ORDER BY g.group_id";

if ($cat_id != 0) {
	// Has category id.
	$db_res = db_query_params($sql, array(pg_escape_string($cat_id)));
}
else {
	// No category id.
	$db_res = db_query_params($sql, array());
}

$cntHasDownloads = 0;
$cntProjects = 0;
$arrDownloads = array();
$arrProjects = array();
$db_count = db_numrows($db_res);
for ($i = 0; $i < $db_count; $i++) {
	$project = pg_fetch_object($db_res, $i);
	if ($project->has_public_package > 0) {
		if (!isset($arrDownloads[$project->group_id])) {
			$arrDownloads[$project->group_id] = $project->group_id;
		}
	}
	if (!isset($arrProjects[$project->group_id])) {
		$arrProjects[$project->group_id] = $project->group_id;
	}
}
$cntHasDownloads = count($arrDownloads);
$cntProjects = count($arrProjects);

$ff = new FusionForge();

//echo "DOWNLOADS: $cntHasDownloads <br/>";

// Force IE NOT to go into "Compatibility" mode.
//header("X-UA-Compatible: IE=Edge");

?>


<script src='/category/jquery.history.js'></script>
<script src='/category/FilterSearch.js'></script>
<script src='/category/SimtkFilters.js'></script>

<link rel='stylesheet' href='../category/category.css' type='text/css' />
<script type="text/javascript" src="/themes/simtk/js/simple-expand.js"></script>
<link rel="stylesheet" type="text/css" href="/themes/simtk/css/theme.css">
<script type="text/javascript">
	$(function() {
		$('.expander').simpleexpand();
	});
</script>

<style>
.project_representation>.wrapper_text>h4>a {
	font-size: 20px;
}
.project_representation>.wrapper_text>.type {
	color: rgb(94,150,225);
	font-size: 14px;
}
.project_representation>.wrapper_text>.content {
	color: rgb(167,167,167);
	font-size: 14px;
}
</style>

<div class="sidebarleft">

<div class="category-shell">

<div id="myHeaderContainer" class="category-container">

<div class="clearer"></div>

</div><!--myHeaderContainer-->

<div id="theSearch" style="display:none">
<div class='search-bar'>
	<form>
		<div class="search-label">Narrow search results<input type='button' class='search-go'/></div>
		<input id='titleFilter' type='text' class='search-term' />
	</form></div>
</div> <!-- theSearch-->

<div id="myCategoryContainer" class="category-container">

<div id="searchstats-mobile" class="searchstats">
	SimTK is home to:<br>
	<span class="CountProjects"><?php echo $cntProjects; ?></span> projects<br>
	<?php echo $ff->getNumberOfActiveUsers() ?> people
</div><!--searchstats-mobile-->

<div class="category-header">

<div class="searchresult-text">
<?php
if ($srch == "___ERROR___") {
?>
<h2>Invalid text to search for</h2>
<?php
}
else {
?>
<h2><span class="searchresults">Project search results: </span><?php echo $srch ?></h2>
<?php
}
?>
</div>

<div class="searchsort">
	<form action="#">
		<label for="select">Sort by:&nbsp;</label>
		<select class="mySelect">
<?php
// NOTE: SELECT always has the first option selected on load.
// Hence, it is necessary to put the option to be selected on load first.

if ($strSort == "date") {
?>
			<option value="Date">Date updated</option>
<?php
}

// For search pages (i.e. not category nor community pages), add "Most relevant" option.
if ($cat_id == 0) {
?>
			<option value="Relevance">Most relevant</option>
<?php
}
?>
			<option value="Downloads">Most downloads</option>
<?php
if ($strSort != "date") {
?>
			<option value="Date">Date updated</option>
<?php
}
?>
			<option value="Title">Title</option>
		</select>
	</form>
</div><!-- searchsort-->

<div class="clearer"></div>
</div> <!-- category-header-->

<div class="category-left">

	<div id="searchstats" class="searchstats">
		SimTK is home to:<br>
		<span class="CountProjects"></span> projects<br>
		<?php echo $ff->getNumberOfActiveUsers() ?> people
	</div><!--searchstats-->
	
	<div class="download_package">



<!-- START desktop filters -->
<div class="nomobile">
<div id="panel1.1">
<h2><a id="expander" class="expander toggle expanded" href="#"><?php echo $grpIParentFullName ?></a></h2>

<div id='theCategories1' class='content' style="display: block;">
<table>
	<tr><td><span class='filter-item'>
		<input type='checkbox' class='no-filter' />
		<span class='lblCategory categoryAll' id='categoryAll' >All (<span></span>)</span>
	</span></td></tr>

<?php
foreach ($grpICatIds as $idx=>$tmpCatId) {
	if ($tmpCatId == 313 || $tmpCatId == 405) {
		// Skip "SimTK Components" and "Public Downloads".
		continue;
	}
	if ($tmpCatId != $cat_id) {
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' class='filter-by' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
	else {
		// Show page category as a disabled checkbox.
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' disabled='disabled' checked='checked' class='filter-by myDisabled' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
}
?>

</table>
</div> <!-- theCategories1-->
</div> <!-- panel1.1-->

<div id="panel1.2">
<h2><a id="expander" class="expander toggle expanded" href="#"><?php echo $grpIIParentFullName ?></a></h2>

<div id='theCategories2' class='content' style="display: block;">
<table>
	<tr><td><span class='filter-item'>
		<input type='checkbox' class='no-filter' />
		<span class='lblCategory categoryAll' id='categoryAll' >All (<span></span>)</span>
	</span></td></tr>

<?php
foreach ($grpIICatIds as $idx=>$tmpCatId) {
	if ($tmpCatId != $cat_id) {
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' class='filter-by' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
	else {
		// Show page category as a disabled checkbox.
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' disabled='disabled' checked='checked' class='filter-by myDisabled' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
}
?>

</table>
</div> <!-- theCategories2-->
</div> <!-- panel1.2-->

<div id="panel1.3">
<h2><a id="expander" class="expander toggle expanded" href="#"><?php echo $grpIIIParentFullName ?></a></h2>

<div id='theCategories3' class='content' style="display: block;">
<table>
	<tr><td><span class='filter-item'>
		<input type='checkbox' class='no-filter' />
		<span class='lblCategory categoryAll' id='categoryAll' >All (<span></span>)</span>
	</span></td></tr>

<?php

foreach ($grpIIICatIds as $idx=>$tmpCatId) {
	if ($tmpCatId != $cat_id) {
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' class='filter-by' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
	else {
		// Show page category as a disabled checkbox.
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' disabled='disabled' checked='checked' class='filter-by myDisabled' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
}
?>

</table>
</div><!-- theCategories3-->
</div><!-- panel1.3-->
</div><!-- nomobile-->
<!-- END desktop filters -->



<!-- START mobile filters -->
<div class="mobileonly">
<div id="panel1.1">
<h2><a id="expander" class="expander toggle" href="#"><?php echo $grpIParentFullName ?></a></h2>

<div id='theCategories1' class='content' style="display: block;">
<table>
	<tr><td><span class='filter-item'>
		<input type='checkbox' class='no-filter' />
		<span class='lblCategory categoryAll' id='categoryAll' >All (<span></span>)</span>
	</span></td></tr>

<?php
foreach ($grpICatIds as $idx=>$tmpCatId) {
	if ($tmpCatId == 313 || $tmpCatId == 405) {
		// Skip "SimTK Components" and "Public Downloads".
		continue;
	}
	if ($tmpCatId != $cat_id) {
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' class='filter-by' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
	else {
		// Show page category as a disabled checkbox.
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' disabled='disabled' checked='checked' class='filter-by myDisabled' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
}
?>

</table>
</div> <!-- theCategories1-->
</div> <!-- panel1.1-->

<div id="panel1.2">
<h2><a id="expander" class="expander toggle" href="#"><?php echo $grpIIParentFullName ?></a></h2>

<div id='theCategories2' class='content' style="display: block;">
<table>
	<tr><td><span class='filter-item'>
		<input type='checkbox' class='no-filter' />
		<span class='lblCategory categoryAll' id='categoryAll' >All (<span></span>)</span>
	</span></td></tr>

<?php
foreach ($grpIICatIds as $idx=>$tmpCatId) {
	if ($tmpCatId != $cat_id) {
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' class='filter-by' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
	else {
		// Show page category as a disabled checkbox.
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' disabled='disabled' checked='checked' class='filter-by myDisabled' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
}
?>

</table>
</div> <!-- theCategories2-->
</div> <!-- panel1.2-->

<div id="panel1.3">
<h2><a id="expander" class="expander toggle" href="#"><?php echo $grpIIIParentFullName ?></a></h2>

<div id='theCategories3' class='content' style="display: block;">
<table>
	<tr><td><span class='filter-item'>
		<input type='checkbox' class='no-filter' />
		<span class='lblCategory categoryAll' id='categoryAll' >All (<span></span>)</span>
	</span></td></tr>

<?php

foreach ($grpIIICatIds as $idx=>$tmpCatId) {
	if ($tmpCatId != $cat_id) {
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' class='filter-by' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
	else {
		// Show page category as a disabled checkbox.
		echo "<tr><td><span class='filter-item'>" .
			"<input type='checkbox' disabled='disabled' checked='checked' class='filter-by myDisabled' value='" . $tmpCatId . "' /> " .
			"<span class='lblCategory $tmpCatId' id='" . $tmpCatId . "'>" . $grpIIIFullNames[$idx] . " (<span>0</span>)</span>" .
			"</span></td></tr>\n";
	}
}
?>

</table>
</div><!-- theCategories3-->
</div><!-- panel1.3-->
</div><!-- nomobile-->
<!-- END mobile filters -->



</div> <!-- download_package-->

</div><!--category-left-->


<div class="category-center">

<script>
<?php
if ($cat_id != 0) {
	// Cateogry search: Only public projects.
?>
	SimtkFilters.setup($('#myCategoryContainer'), true, false);
<?php
}
else {
	//if (isset($_GET["all_groups"]) && $_GET["all_groups"] == 1) {
		// Include both private and public projects by default.
?>
		SimtkFilters.setup($('#myCategoryContainer'), false, true);

<?php
	//}
	//else {
		// Include public projects only.
?>
		//SimtkFilters.setup($('#myCategoryContainer'), false, false);
<?php
	//}
}
?>
</script>

</div><!--category-center-->

<div class="clearer"></div>

</div><!--category-container-->

<div class="clearer"></div>

</div><!--category-shell-->

</div><!--sidebarleft-->

<?php

$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
