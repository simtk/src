<?php

/**
 *
 * followersmap.php
 * 
 * Generate map of project followers.
 * 
 * Copyright 2005-2018, SimTK Team
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
require_once $gfcommon . 'include/pre.php';
require_once $gfplugins . 'following/www/following-utils.php';
require_once $gfwww . 'project/project_utils.php';

$group_id = getIntFromRequest('group_id');

// Get group information.
if (!$group_id) {
	exit_no_group();
}
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
}
else if ($group->isError()) {
	exit_error($group->getErrorMessage(), 'admin');
}

$following = new Following($group);

$sys_google_maps_key = "<insert your google API key here>";

?>

<link rel='stylesheet' type='text/css' href='followers.css'>
<script src='//maps.googleapis.com/maps/api/js?key=<?php
	echo $sys_google_maps_key;
?>'>
</script>
<script type='text/javascript' src='followersmap_js.php?group_id=<?php echo $group_id; ?>'></script>

<?php

site_project_header(array('title'=>'Project Followers', 'group'=>$group_id, 'toptab'=>'following'));

?>

<div class="project_overview_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">
			<div class='divMapContainer'>
<?php

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

		echo "<a href='follow-info.php?group_id=$group_id'>What does it mean to follow a project?</a>";

		echo "<span style='float: right;'><b>Display followers in:</b>&nbsp;";
		echo "<select onchange='location=this.value;'>";
		echo "<option id='optList' value='/plugins/following/index.php?group_id=" . $group_id . "'>List</option>";
		echo "<option id='optMap' value='/plugins/following/followersmap.php?group_id=" . $group_id . "'>Map</option>";
		echo "</select>";
		echo "</span><br/><br/>";

		echo "<span>Click location to see followers there</span>";
		echo "<div id='map'></div>";

?>

		<div id="mapFooter">
			<img align="middle" alt="" src="/images/map/marker_red.png"/>
			<span>more than 1000</span>
			<img align="middle" alt="" src="/images/map/marker_orange.png"/>
			<span>101 to 1000</span>
			<img align="middle" alt="" src="/images/map/marker_yellow.png"/>
			<span>26 to 100</span>
			<img align="middle" alt="" src="/images/map/marker_green.png"/>
			<span>6 to 25</span>
			<img align="middle" alt="" src="/images/map/marker_blue.png"/>
			<span>2 to 5</span>
			<img align="middle" alt="" src="/images/map/marker_gray.png"/>
			<span>1</span>
		</div>

		<div style='clear:both'></div>

		<div class="projectFollowers">
		</div>

<?php

	}
}

?>

			</div> <!-- divMapContainer -->
		</div> <!-- main_col -->

<?php

		// "side_bar".
		constructSideBar($group);

?>

	</div> <!-- display: table; width: 100% -->
</div> <!-- project_overview_main -->

<script>
$(document).ready(function () {
	// Select the Map option by default here.
	// Otherwise, on reload, the option may not get selected in Chrome.
	$('#optMap').attr('selected', 'selected');
})
</script>

<?php

site_project_footer(array());

?>

