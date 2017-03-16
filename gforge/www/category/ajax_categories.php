<?php

/**
 *
 * ajax_categories.php
 * 
 * ajax code to handle categories.
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
require_once $gfcommon.'include/escapingUtils.php';

// Get category id.
if (isset($_GET["cat"])) {
	$cat_id = intval($_GET["cat"]);
}

echo "[";
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

if (isset($_GET["all_groups"]) && $_GET["all_groups"] == 1) {
	// Includes both private and public projects.
	$sql .= " WHERE status = 'A' " .
		"AND NOT simtk_is_system IS NULL ";
}
else {
	// public projects only by default.
	$sql .= " WHERE simtk_is_public = 1 AND status = 'A' " . 
		"AND NOT simtk_is_system IS NULL ";
}


if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$sql .= "AND trove_cat_id=$1 ";
}

$sql .= "ORDER BY g.group_id";


if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$db_res = db_query_params($sql, array(pg_escape_string($cat_id)));
}
else {
	// No category id.
	$db_res = db_query_params($sql, array());
}

$db_count = db_numrows($db_res);
// Really sad we need to do this, but our Postgres is so out of date 
// that it doesn't have string_agg or even array_agg, which would have 
// been a one-line fix
$sql = "SELECT t1.group_id, t2.trove_cat_id, c.fullname FROM trove_group_link t1 
	JOIN trove_group_link t2 ON t1.group_id=t2.group_id 
	JOIN trove_cat c ON t2.trove_cat_id=c.trove_cat_id ";

if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$sql .= "WHERE t1.trove_cat_id=$1 ";
}


$sql .= "ORDER BY t1.group_id, t2.trove_cat_id";

if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$trove_res = db_query_params($sql, array(pg_escape_string($cat_id)));
}
else {
	// No category id.
	$trove_res = db_query_params($sql, array());
}

$trove_count = db_numrows($trove_res);
$trove_inc = 0;

// Get "keywords" for categories.
$sql = "SELECT keyword, project_id FROM project_keywords ";

if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$sql .= "WHERE project_id IN (SELECT group_id FROM trove_group_link WHERE trove_cat_id=$1) ";
}

$sql .= "ORDER BY project_id";

if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$keywords_res = db_query_params($sql, array(pg_escape_string($cat_id)));
}
else {
	// No category id.
	$keywords_res = db_query_params($sql, array());
}

$keywords_count = db_numrows($keywords_res);
$keywords = array();
for ($i = 0; $i < $keywords_count; $i++) {
	$keyword_row = pg_fetch_object($keywords_res, $i);
	if (!isset($keywords[$keyword_row->project_id])) {
		$keywords[$keyword_row->project_id] = $keyword_row->keyword;
	}
	else {
		$keywords[$keyword_row->project_id] = $keywords[$keyword_row->project_id] . "," .
			$keyword_row->keyword;
	}
}

// Get "Ontology" terms for categories.
$sql = "SELECT bro_resource, project_id FROM project_bro_resources ";

if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$sql .= "WHERE project_id in (SELECT group_id FROM trove_group_link WHERE trove_cat_id=$1) ";
}

$sql .= "ORDER BY project_id";

if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$ontology_res = db_query_params($sql, array(pg_escape_string($cat_id)));
}
else {
	// No category id.
	$ontology_res = db_query_params($sql, array());
}

$ontology_count = db_numrows($ontology_res);
$ontologies = array();
for ($i = 0; $i < $ontology_count; $i++) {
	$ontology_row = pg_fetch_object($ontology_res, $i);
	if (!isset($ontologies[$ontology_row->project_id])) {
		$ontologies[$ontology_row->project_id] = $ontology_row->bro_resource;
	}
	else {
		$ontologies[$ontology_row->project_id] = $ontologies[$ontology_row->project_id] . "," .
			$ontology_row->bro_resource;
	}
}

// Look up all project members per group.
// NOTE: Customized single SELECT query used to fetch user realname and group id,
// rather than going through the RBACEngine, which is more efficient and faster.
$allProjMembers = array();
$sql = "SELECT u.realname, home_group_id AS group_id  " .
	"FROM pfo_user_role pur " .
	"JOIN pfo_role pr " .
	"ON pur.role_id=pr.role_id " .
	"JOIN users u " .
	"ON pur.user_id=u.user_id ";
if (isset($cat_id) && trim($cat_id) != "" && $cat_id > 0) {
	// Has category id.
	$sql .= "WHERE group_id in " .
		"(SELECT group_id FROM trove_group_link WHERE trove_cat_id=$1) ";
	$user_roles_res = db_query_params($sql, array(pg_escape_string($cat_id)));
}
else {
	$user_roles_res = db_query_params($sql, array());
}
$user_roles_count = db_numrows($user_roles_res);
for ($i = 0; $i < $user_roles_count; $i++) {
	$user_roles_row = pg_fetch_object($user_roles_res, $i);

	$theGroupId = $user_roles_row->group_id;
	$theRealName = $user_roles_row->realname;
	// Put realnames into array of groups for fetching later.
	if (!isset($allProjMembers[$theGroupId])) {
		$allProjMembers[$theGroupId] = $theRealName;
	}
	else {
		$allProjMembers[$theGroupId] = $allProjMembers[$theGroupId] . "," .
			$theRealName;
	}
}

$arrProjects = array();
for ($i = 0; $i < $db_count; $i++) {
	$project = pg_fetch_object($db_res, $i);

	if (isset($arrProjects[$project->group_id])) {
		// Duplicate. Skip.
		continue;
	}
	$arrProjects[$project->group_id] = $project->group_id;

	if ($i > 0)
		echo ",";
	echo "{";
	if (!isset($project->dls_downloads)) {
		$projectDownloads = "0";
	}
	else {
		$projectDownloads = $project->dls_downloads;
	}
	echo json_kv("group_id", $project->group_id) . ",";
	echo json_kv("unix_group_name", $project->unix_group_name) . ",";
	echo json_kv("modified", $project->modified) . ",";
	echo json_kv("downloads", $projectDownloads) . ",";
	echo json_kv("group_name", $project->group_name). ",";
	echo json_kv("logo_file", $project->simtk_logo_file) . ",";
	echo json_kv("short_description", $project->simtk_summary) . ",";
	echo json_kv("long_description", $project->simtk_short_description) . ",";
	echo json_kv("has_downloads", $project->has_public_package > 0) . ",";
	// Check before filling in; keywords may not be present.
	if (isset($keywords[$project->group_id])) {
		echo json_kv("keywords", $keywords[$project->group_id]) . ",";
	}
	else {
		echo json_kv("keywords", "") . ",";
	}
	// Check before filling in; ontologies may not be present.
	if (isset($ontologies[$project->group_id])) {
		echo json_kv("ontologies", $ontologies[$project->group_id]) . ",";
	}
	else {
		echo json_kv("ontologies", "") . ",";
	}
	// Check before filling in; allProjMembers may not be present.
	if (isset($allProjMembers[$project->group_id])) {
		echo json_kv("projMembers", $allProjMembers[$project->group_id]) . ",";
	}
	else {
		echo json_kv("projMembers", "") . ",";
	}
	echo '"trove_cats":[';
	$is_toolkit = false;
	$is_model = false;
	$is_data = false;
	$is_application = false;
	$trove_cats = "";
	for ($trove_inc; 
		$trove_inc < $trove_count && db_result($trove_res, $trove_inc, 0) <= $project->group_id; 
		$trove_inc++) {
		if (db_result($trove_res, $trove_inc, 0) == $project->group_id)
			$trove_cats .= "{" . json_kv("id", db_result($trove_res, $trove_inc, 1)) . "," . json_kv("fullname", db_result($trove_res, $trove_inc, 2)) . "},";
		$trove_id = db_result($trove_res, $trove_inc, 1);
		if ($trove_id == 312 || $trove_id == 313 || $trove_id == 402)
			$is_toolkit = true;
		if ($trove_id == 318)
			$is_model = true;
		if ($trove_id == 306)
			$is_application = true;
		if ($trove_id == 400)
			$is_data = true;
	}
	echo substr($trove_cats, 0, -1);
	echo '],';
	echo json_kv("is_toolkit", $is_toolkit) . ",";
	echo json_kv("is_model", $is_model) . ",";
	echo json_kv("is_application", $is_application) . ",";
	echo json_kv("is_data", $is_data);
	echo "}";
}

db_free_result($db_res);
db_free_result($trove_res);
db_free_result($keywords_res);
db_free_result($ontology_res);

echo "]";

function json_kv($key, $val) {
	$out = '"' . json_escape($key) . '":';
	if (is_int($val))
		$out .= $val;
	if (is_bool($val))
		$out .= $val ? "true" : "false";
	else
		$out .= '"' . json_escape($val) . '"';
	return $out;
}

function json_escape($str) {
	return str_replace('"', '\"', str_replace("\t", '\t', str_replace("\n", '\n', str_replace("\r", "", str_replace('\\', '\\\\', $str)))));
}

?>
