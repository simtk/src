<?php
/**
 * usagemap.php
 *
 * Project Usage Map Page
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
require_once $gfplugins.'reports/www/reports-utils.php';
require_once $gfplugins.'reports/include/Reports.class.php';

$group_id = getIntFromRequest('group_id');

if ( !$group_id ) {
	exit_no_group();
}

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

$sys_google_maps_key = "<insert your google API key here>";


$morescripts = '
<script type="text/javascript">
  onload=initPage;
  onresize=resizeMap;
</script>
';

?>

<style>
@-moz-document url-prefix() {
	.project_menu>li> .dropdown-menu {
		top:5px;
	}
}
</style>

<?php

echo "<link rel='stylesheet' type='text/css' href='map.css'>";
?>

<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php
	echo $sys_google_maps_key; ?>&amp;sensor=false"></script>

<?php
echo "<script type='text/javascript' src='usagemap_js.php?group_id=$group_id'></script>";

reports_header(array('title'=>'Downloads Summary','group'=>$group_id,'morehead'=>$morescripts),$group_id);


$resAll = db_query_params("select coalesce(sum(hits),0) as hits from stats_site_user_loc where group_id=$group_id",array());
if ($resAll) {
	$allHits = db_result($resAll,0,0);
	db_free_result($resAll);
} else {
	$allHits = 0;
}

$resAllCount = db_query_params("select count(*) as hits from stats_site_user_loc where group_id=$group_id",array());
if ($resAllCount) {
	$allHitCount = db_result($resAllCount,0,0);
	db_free_result($resAllCount);
} else {
	$allHitCount = 0;
}

$resStanford = db_query_params("select coalesce(sum(hits),0) as hits from stats_site_user_loc where group_id=$group_id and host_name like '%.stanford.edu'",array());
if ($resStanford) {
	$stanfordHits = db_result($resStanford,0,0);
	db_free_result($resStanford);
} else {
	$stanfordHits = 0;
}

$resStanfordCount = db_query_params("select count(*) as hits from stats_site_user_loc where group_id=$group_id and host_name like '%.stanford.edu'",array());
if ($resStanfordCount) {
	$stanfordHitCount = db_result($resStanfordCount,0,0);
	db_free_result($resStanfordCount);
} else {
	$stanfordHitCount = 0;
}

?>



<div align="center">
  <h2 id="mapHeader"><?php echo $allHits ?> Page Hits in the past 180 Days (<?php echo $allHitCount ?> Unique Visitors)<br/><small><?php echo $stanfordHits ?> Stanford Page Hits (<?php echo $stanfordHitCount ?> Unique Visitors)</small></h2>
  <div id="map"></div>
  <div id="mapFooter"><br/>
  <img align="middle" alt="" src="/images/map/marker_red.png"/> more than 1000
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <img align="middle" alt="" src="/images/map/marker_orange.png"/> 101 to 1000
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <img align="middle" alt="" src="/images/map/marker_yellow.png"/> 26 to 100
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <img align="middle" alt="" src="/images/map/marker_green.png"/> 6 to 25
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <img align="middle" alt="" src="/images/map/marker_blue.png"/> 2 to 5
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  <img align="middle" alt="" src="/images/map/marker_gray.png"/> 1
  </div>
</div>

<?php

site_project_footer( array() );

?>
