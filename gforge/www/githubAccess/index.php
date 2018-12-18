<?php

/**
 *
 * index.php
 * 
 * Page displays GitHub information.
 *
 * Copyright 2005-2017, SimTK Team
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
require_once $gfcommon . 'include/pre.php';
require_once $gfwww . 'project/project_utils.php';
require_once $gfcommon . '/include/githubUtils.php';

$group_id = getIntFromRequest("group_id");

//session_require_perm('scm', $group_id, 'read') ;

$groupObj = group_get_object($group_id);
if (!$groupObj || !is_object($groupObj)) {
	exit_no_group();
}
elseif ($groupObj->isError()) {
	exit_error($groupObj->getErrorMessage(), '');
}
$groupObj->clearError();

if (!$groupObj->usesGitHub()) {
	$error_msg = "GitHub Source Repository is not in use.";
}
else {
	// Using GitHub access. Get URL.
	$url = $groupObj->getGitHubAccessURL();
	if (!isset($url) || empty($url)) {
		$error_msg = "GitHub Source Repository access is not set up.";
	}
	else {
		$theGitHubURL = "https://api.github.com/repos/" . $url;
		// Get GitHub statistics on project.
		$status = getContributors($theGitHubURL, $numContributors, $totalCommits);
		$status = getLastCommit($theGitHubURL, $dateLastCommit);
	}
}

$params['toptab'] = 'githubAccess';
$params['group'] = $group_id;
$params['submenu'] = $HTML->subMenu(array(), array(), array());
site_project_header($params);

?>

<div class="project_overview_main">
<div style="display: table; width: 100%;">
<div class="main_col">
<table class="fullwidth">
<tr valign="top">

<?php
	if (isset($url) && !empty($url)) {
?>

	<td width="65%">
		<p><a href="/githubAccess/loadGitHubAccessURL.php?group_id=<?php 
			echo $group_id; 
		?>">Access the GitHub Repository</a> of this project's code.</p>
	</td>

	<td width="35%">
		<p><strong>Repository History</strong></p>
<?php 
		if (isset($totalCommits)) {
			echo "Total number of commits: $totalCommits";
			if (isset($numContributors)) {
				echo "<br/>Number of contributors: $numContributors";
			}
		}
		else {
			echo "0 commits.";
		}

		if (isset($dateLastCommit) && $dateLastCommit > 0) {
			$theDateTime = new DateTime("@$dateLastCommit");
			echo "<br/>Last commit: " . $theDateTime->format('M j, Y');
		}
		else {
			echo "<br/>Last commit: Longer than 1 year";
		}
?>
	</td>

<?php
}
?>

</tr>
</table>
</div>
</div>
</div>


<?

site_project_footer(array());

?>
