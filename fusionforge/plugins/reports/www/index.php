<?php
/**
 * index.php
 *
 * reports plugin main index file for displaying usage map and other statistics.
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

$user = session_get_user(); // get the session user

/*
if (!$user || !is_object($user) || $user->isError() || !$user->isActive()) {
   exit_error("Invalid User", "Cannot Process your request for this user.");
}
*/

$group_id = getIntFromRequest('group_id');

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

// Check permission and prompt for login if needed.
session_require_perm('project_read', $group_id);

reports_header(array('title'=>_('Downloads Summary')),$group_id);

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

?>

<table class="my-layout-table">
	<tr>
		<td>

<?php 

    $reports = new Reports($group);    
    $resultsDownloads = $reports->getDownloadsSummary($cntUsers,$cntUserfiles,$cntRecords,$cntLinks,$cntFiles,$packages,$packageCounts,$packageTotals,$packageDates,$packageStatus,$packageIsPublic,$releaseNames,$releaseDates,$releaseTotals,$releaseStatus,$packageArray);
?>
    <?php //echo "cntUsers: " . number_format($cntUsers). "<br>"; ?>
	<?php //echo "cntUserFiles: " . number_format($cntUserfiles). "<br>"; ?>
	<?php //echo "cntRecords: " . number_format($cntRecords). "<br>"; ?>
	<?php //echo "cntLinks: " . number_format($cntLinks). "<br>"; ?>
	<?php //echo "cntFiles: " . number_format($cntFiles). "<br>"; 
	
	//echo "<br />releaseNames: ";
	//var_dump($releaseNames);
	//echo "<br />Results: ";
	//echo "<pre>";
	//var_dump($resultsDownloads);
	//echo "</pre>";
	?>
	
	
    <h4>Summary Statistics</h4>
    <div class="table-responsive">
    <table class="table table-condensed table-bordered">
	<tr><th class="info">Unique Users<sup>1</sup></th><th class="info">Total Downloads</th><th class="info">File Downloads</th><th class="info">Links</th><th class="info">Unique Downloads<sup>2</sup></th></tr>
	<tr><td><?php echo number_format($cntUsers); ?></td><td><?php echo number_format($cntRecords); ?></td><td><?php echo number_format($cntFiles); ?></td><td><?php echo number_format($cntLinks); ?></td><td><?php echo number_format($cntUserfiles); ?></td></tr>
	</table>
    </div>
    <br /><br />
	
	<h4>Unique Users<sup>1</sup></h4>
	<div class="table-responsive">
    <table class="table table-condensed table-bordered">
	<tr><th class="info"></th><th class="info">Total<sup>3</sup></th><th class="info">Downloads<sup>3</sup><br><small>(non-documentation)</th><th class="info">Documentation<sup>3</sup></th><th class="info">Links<sup>*</sup></th></tr>
	<?php foreach ($resultsDownloads as $results) { ?>
	        <?php if ($results['status'] == 1) { ?>
			  <?php //if (!is_array($results)) { ?>
	            <tr><td  class="active"><?php echo "<b>" . $results['name'] . "</b>"; ?></td><td  class="active"><?php echo $results['unique_users']; ?></td><td  class="active"><?php 
	if (isset($results['files'])) {
		echo $results['files']; 
	}
?></td><td class="active"><?php 
	if (isset($results['documentation'])) {
		echo $results['documentation']; 
	}
?></td><td  class="active"><?php 
	if (isset($results['links'])) {
		echo $results['links']; 
	}
?></td></tr>
	            <?php //} else { ?>
			    <?php foreach ($results as $package) { ?>
				  <?php if (isset($package['release_name'])) { ?>
		             <tr><td><?php echo $package['release_name']; ?></td><td><?php echo $package['release_totals']; ?></td><td><?php 
	if (isset($package['files'])) {
		echo $package['files']; 
	}
?></td><td><?php 
	if (isset($package['documentation'])) {
		echo $package['documentation']; 
	}
?></td><td><?php 
	if (isset($package['links'])) {
		echo $package['links']; 
	}
?></td></tr>		
	              <?php } ?>
				<?php } ?>
			<?php //} ?>	
				
	        <?php } ?>
	<?php } ?>
	</table>
	</div>
	<small>
	<sup>1</sup>Downloads where user information was not gathered count as one user. May include hidden packages.<br />
    <sup>2</sup>Includes both files and links. A file downloaded multiple times by the same user counts as one<br />
    <sup>3</sup>Number of users that downloaded a file in this category<br />
    <sup>*</sup>Number of users that clicked on a link (only tracked since June 2010)
    </small>
	<br />
	
<?php

echo $HTML->boxBottom();?>

		</td>
	</tr>
</table>

<?php

echo "</div></div></div>";

reports_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
