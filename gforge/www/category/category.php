<?php

/**
 *
 * category.php
 * 
 * File to handle categories.
 *
 * Copyright 2005-2016, SimTK Team
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

require_once 'category_utils.php';

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

$HTML->header(array('title'=>'Project category','pagename'=>''));

global $cat_id, $rows;
$cat_id = getIntFromRequest("cat");

$featured_cat_id = 407;

/*
// The parameter "cat" can be a comma separated string upon user selecting different categories.
// The originally selected cateogry remains at the beginning.
// Only pick the first value after exploding this array to get the cateogry. Otherwise, the
// following db selection fails! HK.
$catIds = explode(',', $cat_id);
if (count($catIds) > 0) {
        $cat_id = $catIds[0];
}
*/

$sql = "SELECT trove_cat_id,
		shortname,
		fullname,
		description,
                simtk_intro_text,
                simtk_make_diff_intro,
		count_subproj
		FROM trove_cat
		WHERE trove_cat_id=$1";
$result = db_query_params($sql, array($cat_id));
$rows = db_numrows($result); 
for ($i = 0; $i < $rows; $i++) {
	$cat_id = db_result($result,$i,'trove_cat_id');
	$fullname = db_result($result,$i,'fullname');
	$shortname = db_result($result,$i,'shortname');
	$description = db_result($result,$i,'description');
	$introduction = db_result($result,$i,'simtk_intro_text');
	$make_diff_intro = db_result($result,$i,'simtk_make_diff_intro');
	$count_subproj = db_result($result,$i,'count_subproj');

	//echo "trove_cat_id: " . $cat_id . " ";
}
db_free_result($result);

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


// Get cateogry id and full name given the specified parent id.
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


// Get featured projects.
function getFeaturedProjects($inCatId) {
	$resStr = "";
	$sqlGroups = "SELECT group_id from featured_projects where trove_cat_id=$1";
	$resGroups = db_query_params($sqlGroups, array($inCatId));
	if (!$resGroups) {
		// Cannot execute db query.
		return "***ERROR***";
	}
	$cntRows = db_numrows($resGroups); 
	for ($i = 0; $i < $cntRows; $i++) {
		$theGroupId = db_result($resGroups, $i, 'group_id');
		$resStr = $resStr . "," . $theGroupId;
	}
	db_free_result($resGroups);

	return $resStr;
}


// Count projects that has downloads. HK.
// Note: Should use LEFT JOIN here too, like ajax_categories.php. HK.
$cntHasDownloads = 0;
$sql = "SELECT *,
	CASE WHEN g.group_id IN (SELECT group_id FROM frs_file ff LEFT JOIN frs_release fr ON ff.release_id = fr.release_id LEFT JOIN frs_package fp ON fp.package_id = fr.package_id WHERE fp.is_public=1 AND fp.status_id=1 AND fr.status_id=1) THEN 1 ELSE 0 END AS has_public_package,
	0 as is_model
	FROM trove_group_link AS t JOIN (SELECT group_id group_id, unix_group_name, group_name, simtk_logo_file, simtk_summary, simtk_short_description, status, simtk_is_public, simtk_is_system FROM groups) AS g ON t.group_id = g.group_id JOIN (SELECT group_id, MAX(adddate) AS modified FROM group_history GROUP BY group_id) AS gh ON t.group_id = gh.group_id LEFT JOIN (SELECT group_id as dls_group_id, downloads as dls_downloads from frs_dlstats_grouptotal_vw) as dls ON dls_group_id = t.group_id 
	WHERE simtk_is_public = 1 AND status = 'A' AND trove_cat_id=$1 ORDER BY g.group_id";
$db_res = db_query_params($sql, array(pg_escape_string($cat_id)));
$db_count = db_numrows($db_res);
for ($i = 0; $i < $db_count; $i++) {
	$project = pg_fetch_object($db_res, $i);
	if ($project->has_public_package > 0) {
		$cntHasDownloads++;
	}
}

//echo "DOWNLOADS: $cntHasDownloads <br/>";

// Force IE NOT to go into "Compatibility" mode.
header("X-UA-Compatible: IE=Edge");

?>


<script src='/category/category.js'></script>

<link rel="stylesheet" href='/js/jquery-ui-1.10.1.custom.min.css' />
<link rel="stylesheet" type="text/css" href="/themes/simtk/css/theme.css">

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

<link rel='stylesheet' href='category.css' type='text/css' />

<div class='news_communities_trending_projects'>
<div class='two_third_col'>
<div class='module_home_categories'>
		
	<div class="categories_intro">
		<div class="project_submenu"><?php echo $fullname; ?></div>
		<div style="clear:both"></div>
		<p><?php echo $introduction;?></p>
	</div><!-- /categories_intro -->

	<div class="featured_projs" style="Display: none;">
	<h2 class='underlined'>Featured projects</h2>

	<div class="categories_featured" id="categories_featured">
	</div><!-- categories_featured -->
	</div><!-- featured_projs -->

	<h2 class='underlined'>Recently updated projects</h2>

	<div class="categories_home" id="categories_home">
	</div><!-- categories_home -->
			
</div><!-- module_home_categories -->
</div><!-- two_third_col -->


<div class='one_third_col'>
<div class="statbox2area">
	<div class="statbox2">
		<div class="statbox2-left">
			<div class="statbox2-number"><a href="#"></a></div>
			<div class="statbox2-text">total<br>projects</div>
		</div><!-- statbox2-left -->
		<div class="statbox2-right">
			<div class="statbox2-number"><a href="#"><?php echo "$cntHasDownloads"; ?></a></div>
			<div class="statbox2-text">projects with<br>downloads</div>
		</div><!-- statbox2-right -->
		<div style="clear:both"></div>
		
		<div class="share_text">
			<a class="btn-blue share_text_button" href="/search/search.php?cat=<?php
				echo $cat_id;
?>" style="font-size:15px;">Search projects in this category</a>
		</div><!-- share_text -->
	</div><!-- statbox2 -->
	
	<div style="clear: both;"></div>
</div><!-- statbox2area -->

<div class='module_newsarea'>
	<h2 class='underlined'>News</h2>

	<div class="item_newsarea">
		<?php echo show_category_news(3, $cat_id, true);?>
	</div><!-- /item_newsarea -->

</div><!-- module_newsarea -->

<div class='module_publications'>
	<h2 class='underlined'>Publications</h2>

	<?php echo show_category_publications(3, $cat_id); ?>

</div><!-- module_publications -->

<div class='module_discussion'>
	<h2 class='underlined'>Discussion</h2>

	<?php echo show_category_forum_posts(3, $cat_id, true);?>

</div><!-- module_discussion -->

</div><!-- one_third_col -->
</div><!-- news_communities_trending_projects -->

<?php

$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
