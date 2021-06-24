<?php

/**
 *
 * index.php
 * 
 * File to administor GitHub access.
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
require_once $gfcommon . 'include/pre.php';
require_once $gfwww . 'project/admin/project_admin_utils.php';
require_once $gfcommon . 'include/githubUtils.php';

$groupId = getIntFromRequest('group_id');
if (!$groupId) {
	exit_no_group();
}


$groupObj = group_get_object($groupId);
if (!$groupObj || !is_object($groupObj)) {
	exit_no_group();
}
elseif ($groupObj->isError()) {
        exit_error($groupObj->getErrorMessage(), '');
}
$groupObj->clearError();

session_require_perm('project_admin', $groupId) ;

if ($submit = getStringFromRequest('update')) {
	$url = htmlspecialchars(getStringFromRequest('urlGitHub'));
	if (isset($url) && !empty($url)) {
		// Trim.
		$url = trim($url);
		if (strpos($url, "/") === 0) {
			// Remove leading "/" if any.
			$url = substr($url, 1);
		}
		if (strrpos($url, "/") === strlen($url) - 1) {
			// Remove trailing "/" if any.
			$url = substr($url, 0, strlen($url) - 1);
		}
		// Check $url for validity:
		// There should be at least one "/".
		// Format is "owner/repo".
		$idx = strpos($url, "/");
		if ($idx === false || $idx === 0 || $idx === strlen($url) - 1) {
			// "/" is not present, starts with "/", or 
			// ends with "/" after trimming.
			$error_msg .= "$url is in invalid format.";
		}
		else if (stripos(strrev($url), strrev(".git/")) === 0 ||
			stripos(strrev($url), strrev(".git")) === 0) {
			// Ends with ".git" or ".git/".
			$error_msg .= "Do not include .git suffix in URL.";
		}
		else {
			$theGitHubURL = "https://github.com/" . $url;
			if (urlExistance($theGitHubURL) != 200) {
				// GitHub repository does not exist.
				$error_msg .= "$url is an invalid URL.";
			}
			else {
				// Save. GitHub repository exists.
				$groupObj->saveGitHubAccessURL($url);
				$feedback .= "URL updated.";
			}
		}
	}
	else {
		// Save empty URL.
		$groupObj->saveGitHubAccessURL("");
		$feedback .= "URL updated.";
	}
}

project_admin_header(array('title'=>'Admin','group'=>$groupObj->getID()));
?>

<div class="project_overview_main">
	<div style="display: table; width: 100%;">
		<div class="main_col">

<table style="max-width:645px;" width="100%" cellpadding="2" cellspacing="2">
<tr valign="top">
	<td width="65%">


<form id="myForm" action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<input type="hidden" name="group_id" value="<?php echo $groupObj->getID(); ?>" />
<div class="table-responsive">
<table>
<tr>
	<th>
		<h2>Repository URL</h2>
		<p>Link a GitHub repository to this SimTK project. Only public repositories are supported.</p>
	</th>
</tr>
<tr>
	<td>
		<span>https://github.com/</span>
		<input type="text" name="urlGitHub" size="30"
			value="<?php echo $groupObj->getGitHubAccessURL(); ?>" />
	</td>
</tr>
<tr>
	<td>
		<br/>
		<input type="submit" name="update" value="Update" class="btn-cta" />
	</td>
</tr>
</table>
</div>

</form>

	</td>
</tr>
</table>
		</div>
	</div>
</div>

<?php
project_admin_footer(array());

?>

