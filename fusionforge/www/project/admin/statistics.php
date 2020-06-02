<?php

/**
 *
 * statistics.php
 * 
 * File to show project statistics.
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
require_once $gfwww.'project/admin/project_admin_utils.php';

$group_id = getIntFromRequest('group_id');

session_require_perm ('project_admin', $group_id) ;

// get current information
$group = group_get_object($group_id);
if (!$group || !is_object($group)) {
	exit_no_group();
} elseif ($group->isError()) {
	exit_error($group->getErrorMessage(),'admin');
}

// First check whether Downloads is enabled.
if (!$group->usesFRS()) {
	exit_error("Downloads has been disabled.");
}

$group->clearError();


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

<div class="project_overview_main">
    <div style="display: table; width: 100%;"> 
        <div class="main_col">
		
<table class="my-layout-table">
	<tr>
		<td>

<?php
	$numrows = 0;
	$limit = 100;
	$offset = 0;
	if (isset($_GET['offset']) && $_GET['offset']) {
		$offset = $_GET['offset'];
	}
	if (isset($GET['all']) && $_GET['all']) {
		$limit = 0;
	}
	$resultsDownloads = $group->getDownloadsTracking($offset,$numrows,$limit);
?>

<h4>Downloads Tracking</h4>
<p>

<?php
	//check if the group has the Reports plugin active
	if ($group->usesPlugin ( "reports" )) { 
		echo "View <a href='/plugins/reports/?group_id=$group_id'>summary statistics</a> of all downloads.";
	}
?>

<br/>
Last 100 downloads are shown.
<br/>
Download all records as a <a href="/frs/getDownloadStatsAsCsv.php?group_id=<?php echo $group_id; ?>">CSV</a> file.
</p>

<div class="table-responsive">
	<table class="table table-condensed table-striped table-bordered">
	<tr><th class="info">Package</th><th class="info">Release</th><th class="info">File/Link</th><th class="info">User</th><th class="info">Institution</th><th class="info">Lab</th><th class="info">Expected Use</th><th class="info">Date</th><th class="info">Agreed to License</th></tr>

<?php
	foreach ($resultsDownloads as $results) {
		$userprofile = "<a href='/users/" . 
			$results['user_name'] . 
			"'>" .
			$results['firstname'] . 
			" " . 
			$results['lastname'] . 
			"</a>";
?>

		<tr>
		<td>

<?php
		echo $results['package_name'];
?>
		</td>
		<td>
<?php
		echo $results['release_name'];
?>
		</td>
		<td>
<?php
		echo $results['filename'];
?>
		</td>
		<td>
<?php
		echo $results['firstname'] != 'Nobody' ? $userprofile : "Not logged.";
?>
		</td>
		<td>
<?php
		echo $results['university_name']; ?>
		</td>
		<td>
<?php
		echo $results['lab_name'];
?>
		</td>
		<td>
<?php
		echo $results['expected_use'];
?>
		</td>
		<td>
<?php
		echo $results['date'];
?>
		</td>
		<td>
<?php
		echo $results['agreed_to_license'] ? "Yes" : "No";
?>
		</td>
		</tr>
<?php
	}
?>

	</table>
</div>
<br/>

<?php
	if ($limit == 100 && $limit <= $numrows) {
		echo "<a href='statistics.php?group_id=" . 
			$group->getID() . 
			"&offset=" . 
			$offset . 
			"'>More....</a>";
	}

	echo $HTML->boxBottom();
?>

		</td>
	</tr>
</table>

</div>
</div>
</div>

<?php

project_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
