<?php

/**
 *
 * feedback.php
 * 
 * File to handle feedback.
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
 
require_once 'env.inc.php';
require_once $gfcommon.'include/pre.php';

// Get group_id if present.
$group_id = false;
if (isset($_GET["group_id"])) {
	$group_id = $_GET["group_id"];
}
// Display header.
$HTML->header(array());
if ($group_id !== false && trim($group_id) !== "") {
	// Has group_id. Look up group object.
	$groupObj = group_get_object($group_id);
	// Get project leads.
	$projectLeads = $groupObj->getLeads();

	// Check if forum is used in project.
	$useForum = false;
	$navigation = new Navigation();
	$menu = $navigation->getSimtkProjectMenu($group_id);
	$menu_max = count($menu['titles'], 0);
	for ($i=0; $i < $menu_max; $i++) {
		$menuTitle = $menu['titles'][$i];
		if ($menuTitle == "Forums") {
			// Project uses forum.
			$useForum = true;
			break;
		}
	}

	// Check permission first.
	if (forge_check_perm('project_read', $group_id)) {
		echo "<h2>Feedback on " . $groupObj->getPublicName() . "</h2>";
		if ($useForum === true) {
			// Uses forum.
			echo 'For questions related to "' . 
				$groupObj->getPublicName() . 
				'", we recommend posting to their ' .
				'<a href="/plugins/phpBB/indexPhpbb.php?group_id=' . $group_id .
				'&pluginname=phpBB">discussion forum</a>. ';
	
			if (count($projectLeads) > 0) {
				// Has project lead(s).
				// Note: include group_id in parameter list.
				echo 'For questions not addressed in the forum, you can contact the ' .
					'<a href="/sendmessage.php?touser=' .
					$projectLeads[0]->getID() .
					'&group_id=' . $group_id . '">project administrators</a>.'; 
			}
		}
		else {
			// Does not use forum.
			// Note: include group_id in parameter list.
			if (count($projectLeads) > 0) {
				// Has project lead(s).
				echo 'For questions related to "' .
					$groupObj->getPublicName() .
					'", contact the ' .
					'<a href="/sendmessage.php?touser=' .
					$projectLeads[0]->getID() .
					'&group_id=' . $group_id . '">project administrators</a>.'; 
			}
		}
	}
	else {
		// No permission.
		echo "<h2>Feedback on " . $groupObj->getPublicName() . "</h2>";
		echo "This ia a private project. Only members who are logged into SimTK can provide feedback.";
	}
}

?>

<h2>Feedback on SimTK</h2>
If you have a question about a specific project hosted on the SimTK website (e.g., <a href="/projects/opensim">OpenSim</a>, <a href="/projects/simvascular">SimVascular</a>, <a href="/projects/openmm">OpenMM</a>), please contact the administrators of the specific project.
<br/><br/>
For general questions about the SimTK website, 
visit our <a href="/faq.php">FAQ page</a>. 
We also encourage you to post to and browse our 
<a href="/plugins/phpBB/indexPhpbb.php?group_id=11&pluginname=phpBB">discussion forum</a>.
<br/><br/>

To report suggestions or bugs on the SimTK website, 
you can file a <a href="/tracker?atid=1960&group_id=11&func=add">new issue</a>.
<br/><br/>

For any other concerns, contact the <a href="/sendmessage.php?touser=101">SimTK webmaster</a>.
<br/><br/>

Thanks in advance for your feedback and interest in SimTK.
<br/><br/>

<?php
$HTML->footer(array());
?>
