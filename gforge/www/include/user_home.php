<?php
/**
 * Developer Info Page
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010, FusionForge Team
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012, Franck Villaume - TrivialDev
 * Copyright © 2012
 *	Thorsten Glaser <t.glaser@tarent.de>
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/**
 * Assumes $user object for displayed user is present
 */

$title = $user->getRealName();
$HTML->header(array('title'=>$title));

define('NUM_CHARS_IN_TITLE_TO_SHOW', 30);
#define('NUM_PROJECTS_TO_SHOW', 9999);

?>

<style>

.textHeader3 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 14px;
	color: #8F3747;
	font-style: italic;
	margin-top: 0px;
	margin-bottom: 0px;
}
.textBody3 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 14px;
	margin-top: 0px;
	margin-bottom: 0px;
}

</style>

<div class='news_communities_trending_projects'>

<?php

// Get user projects info.
$projects = $user->getGroups();
sortProjectList($projects);
$roles = RBACEngine::getInstance()->getAvailableRolesForUser($user);
sortRoleList($roles);

// Count number of projects that user is a member of. 
$cntUserProjects = 0;
foreach ($projects as $p) {
	if (!forge_check_perm('project_read', $p->getID())) {
		continue;
	}
	$cntUserProjects++;
}

// Display user profile information.
echo myUserProfile($user);

// End of two_third_col
?>
	</div>
	<div class='one_third_col'>
	<div class="project_representation">
	<div class="wrapper_text">
<?php
	// Member since.
	echo '<span>' .
		'Member since ' . date('M j, Y', $user->getAddDate()) .
		'</span><br/><br/>';

	if ($cntUserProjects > 0) {
		$theSuffix = "";
		if ($cntUserProjects > 1) {
			$theSuffix = "s";
		}
		echo '<span>' .
			'Member of ' . $cntUserProjects . " project" . $theSuffix .
			'</span><br/>';
	}


require_once $gfwww.'include/forum_db_utils.php';
require_once $gfwww.'project/project_utils.php';

// Get total posts by user.
$countPosts = getNumPosts($user->getUnixName());
if ($countPosts > 0) {
//	echo "<span style='color:#a7a7a7;font-size:12px;'>" .
	echo "<span>" . "<a style='color:#5e96e1;' " .
		"href='/plugins/phpBB/searchPhpbb.php?" .
		"author=" . $user->getUnixName() .
		"'>Total forum posts: </a>" .
		$countPosts . 
		"</span>";
}

// Get last post by user, if any.
$status = getLastQuery($user->getUnixName(), $forumName, $forumId, $topicId, $postId, $postTime);
if ($status !== false) {
	// Check for read permssion on the group.
	// If no 'project_read' permission, do not show link.
	if (!forge_check_perm('project_read', $forumId)) {
		echo "<br/>Last forum post: " .
			"<span>" .
			date('M j, Y', $postTime) . 
			"</span><br/>";
	}
	else {
		echo "<br/>" .
			"<a style='color:#5e96e1;' " .
			"href='/plugins/phpBB/viewtopicPhpbb.php?" .
			"f=" . $forumId .
			"&t=" . $topicId .
			"&p=" . $postId . "'>" .
			"Last forum post: " .
			"</a>" .
			"<span>" .
			date('M j, Y', $postTime) . 
			"</span><br/>";
	}
}

?>

	</div>
	</div>
	</div>
</div>

<?php

// see if there were any groups
if ($cntUserProjects > 0) {

	$theSuffix = ':';
	if ($cntUserProjects > 1) {
		$theSuffix = 's:';
	}

	echo "<div class='related_group'>";
	echo "<h2>Projects</h2>";
	echo "<h4>" .
		$user->getRealName() . " is a member of the following project" . $theSuffix .
		"</h4>";

	// Build array of projects that user belongs to.
	$cnt = 0;
	$arrProjects = array();
	foreach ($projects as $p) {
		if (!forge_check_perm('project_read', $p->getID())) {
			continue;
		}

		$role_names = array() ;
		foreach ($roles as $r) {
			if ($r instanceof RoleExplicit && 
				$r->getHomeProject() != NULL && 
				$r->getHomeProject()->getID() == $p->getID()) {

				$role_names[] = $r->getName();
			}
		}

		if (trim($p->getStatus()) != "A") {
			// Not an active project. Skip.
			continue;
		}
		$arrProjects[$cnt]['group_name'] = $p->getPublicName();
		$arrProjects[$cnt]['simtk_logo_file'] = $p->getLogoFile();
		$arrProjects[$cnt]['unix_group_name'] = $p->getUnixName();
		$arrProjects[$cnt]['role_names'] = htmlspecialchars(implode(', ', $role_names));

		$cnt++;
	}

	// Show projects.
	$strProjects = displayProjects($arrProjects);
	echo $strProjects;

	// Projects div.
	echo "</div>\n";

} // end if groups


// Get projects folllowed.
$strProjectFollowed = getFollowingProjects($user, $cntProjects);
if (isset($strProjectFollowed) && trim($strProjectFollowed) != "") {
	echo "<div class='related_group'>";
	echo "<h2>Followed projects</h2>";
	$theSuffix = '';
	if ($cntProjects > 1) {
		$theSuffix = 's';
	}
	echo "<h4>" .
		$user->getRealName() . " is following the project" . $theSuffix . " below:" .
		"</h4>";

	echo $strProjectFollowed . "\n";
	echo "</div>";
}

echo $HTML->boxBottom();

$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:


// Generate tags to display user profile information.
function myUserProfile($user) {

	$strRet = '';
	$user_id = $user->getID();
	$user_mailsha1 = $user->getSha1Email();

	// Top div.
	// vertical line.
	//$strRet .= '<div class="two_third_col" style="border-right:thin solid #EEE;">';
	$strRet .= '<div class="two_third_col">';
	$strRet .= '<div class="project_representation">';

	// Image.
	$strRet .= '<div class="wrapper_img_lg">';

	// User picture file.
	$picture_file = $user->getPictureFile();
	if (trim($picture_file) == "") {
//		$picture_file = "user_default.gif";
		$picture_file = "user_profile.jpg";
	}
	$strRet .= '<img alt="Image not available"' .
		' onError="this.onerror=null;this.src=' . "'" . '/userpics/user_profile.jpg' . "';" . '"' .
		' src="/userpics/' . $picture_file . '"' .
		' />';
	$strRet .= '</div>';

	$strRet .= '<div class="wrapper_text">';

	$strRet .= '<h1>' . $user->getRealName() . '</h1><br/>';

	// Note: Use absolute path from websites to create the following links.
	// University website.
	$websiteUniv = $user->getUniversityWebsite();
	if (isset($websiteUniv) && trim($websiteUniv) != "") {
		if (stripos($websiteUniv, "http://") === false &&
			stripos($websiteUniv, "https://") === false) {
			$websiteUniv = "http://" . $websiteUniv;
		}
		$strRet .= '<span>' . 
			util_make_link(
				$websiteUniv,
				$user->getUniversityName(),
				false,
				true) .
			'</span><br/>';
	}
	else if (trim($user->getUniversityName()) != "") {
		// Show university name if website is not present.
		$strRet .= '<span>' . $user->getUniversityName() . '</span><br/>';
	}
	// Lab website.
	$websiteLab = $user->getLabWebsite();
	if (isset($websiteLab) && trim($websiteLab) != "") {
		if (stripos($websiteLab, "http://") === false &&
			stripos($websiteLab, "https://") === false) {
			$websiteLab = "http://" . $websiteLab;
		}
		$strRet .= '<span>' . 
			util_make_link(
				$websiteLab,
				$user->getLabName(),
				false,
				true) .
			'</span><br/>';
	}
	else if (trim($user->getLabName()) != "") {
		// Show lab name if website is not present.
		$strRet .= '<span>' . $user->getLabName() . '</span><br/>';
	}
	// Personal website.
	$websitePersonal = $user->getPersonalWebsite();
	if (isset($websitePersonal) && trim($websitePersonal) != "") {
		if (stripos($websitePersonal, "http://") === false &&
			stripos($websitePersonal, "https://") === false) {
				$websitePersonal = "http://" . $websitePersonal;
		}
		$strRet .= '<span>' . 
			util_make_link(
				$websitePersonal,
				"Personal website",
				false,
				true) .
			'</span><br/>';
	}

	// Contact by email.
	$strRet .= '<br/><strong><span property="sioc:email_sha1" content="'. $user_mailsha1 .'">' .
		util_make_link(
			'/sendmessage.php?touser=' . $user_id, 
//			'Contact ' . $user->getRealName()) . 
			'<img src="/images/email103.png"/>&nbsp;Contact') . 
		'</span></strong><br/><br/>';

	// Close wrapper_text div.
	$strRet .= '</div>';

	// Close project_representation div.
	$strRet .= '</div>';

	// Second div, if present.
	// Simtk interest.
	$simtkInterest = $user->getSimTKInterest();
	$otherInterest = $user->getOtherInterest();
	if ((isset($simtkInterest) && trim($simtkInterest) != "") ||
		isset($otherInterest) && trim($otherInterest) != "") {
		$strRet .= '<div>';
		// horizontal line.
		//$strRet .= '<hr></hr>';
	}
	if (isset($simtkInterest) && trim($simtkInterest) != "") {
		$strRet .= '<h2>Interests</h2>';
		$strRet .= '<span>' . $simtkInterest . '</span><br/>';
	}

	// Other interest.
	if (isset($otherInterest) && trim($otherInterest) != "") {
		$strRet .= '<br/><span class="textHeader3">Other Interests: </span>';
		$strRet .= '<span>' . $otherInterest . '</span><br/>';
	}
	if ((isset($simtkInterest) && trim($simtkInterest) != "") ||
		isset($otherInterest) && trim($otherInterest) != "") {
		$strRet .= '</div>';
	}

	// horizontal line.
	//$strRet .= '<hr></hr>';

	return $strRet;
}


// Get projects that the user follows.
function getFollowingProjects($user, &$cntProjects) {

	$retStr = '';

	// Retrieve info on projects followed.
	$strQuery = "SELECT p.group_id, g.group_name, g.unix_group_name, g.simtk_logo_file, g.status " .
		"FROM project_follows AS p " .
		"JOIN groups AS g ON p.group_id=g.group_id " .
		"WHERE p.user_name='" . 
		$user->getUnixName() . "' AND " .
		"follows=true AND public=true " .
		"ORDER BY g.group_name";
	$result = db_query_params($strQuery, array());
	if ($result === false) {
		return $retStr;
	}
	$rows = db_numrows($result);
	if ($rows <= 0) {
		return $retStr;
	}

	// Build array of followed projects.
	$cntProjects = 0;
	$arrProjects = array();
	for ($cnt = 0; $cnt < $rows; $cnt++) {
		$status = db_result($result, $cnt, 'status');
		if (trim($status) != "A") {
			// Not an active project. Skip.
			continue;
		}
		$cntProjects++;

		$group_id = db_result($result, $cnt, 'group_id');
		$group_name = db_result($result, $cnt, 'group_name');
		$unix_group_name = db_result($result, $cnt, 'unix_group_name');
		$simtk_logo_file = db_result($result, $cnt, 'simtk_logo_file');
		//echo $group_id . " " . $group_name . " " . $unix_group_name . " " . $simtk_logo_file;

		//$project_link = util_make_link_g($unix_group_name, $group_id, $group_name);
		//$retStr .= '<br/>' . $project_link; 

		$arrProjects[$cnt]['group_name'] = $group_name;
		$arrProjects[$cnt]['simtk_logo_file'] = $simtk_logo_file;
		$arrProjects[$cnt]['unix_group_name'] = $unix_group_name;
	}

	// Display projects followed in a carousel.
	$retStr =  displayProjects($arrProjects);

	return $retStr;
}



// Display project icons and info.
function displayProjects($arrProjects) {

	// Need a clear here; otherwise, e.g. when the number of projects
	// in the "Projects" section is 1, the "Followed projects" title
	// will show up on the same line as the first project int the
	// "Projects" section. 
	echo "<div style='clear: both;'></div>";

	$retStr = '';
	$numRecs = count($arrProjects);
	if ($numRecs == 0) {
		// No records available.
		return $retStr;
	}

	if ($numRecs <= NUM_PROJECTS_TO_SHOW) {

		// "See all" not needed.

		for ($cnt = 0; $cnt < $numRecs; $cnt++) {
			$retStr .= genProjectRecDisplay($arrProjects[$cnt]);
		}
	}
	else {

		// "See all" needed.

		for ($cnt = 0; $cnt < NUM_PROJECTS_TO_SHOW; $cnt++) {
			// The related items shown before "See all".
			$retStr .= genProjectRecDisplay($arrProjects[$cnt]);
		}

		$retStr .= "<div class='related_link'>";
		$retStr .= '<h2><a href="#" onclick="' . 
				'$(' . "'.related_more').show();" . 
				'$(' . "'.related_link').hide();" .
				'return false;' . 
			'">See all</a></h2>';
		$retStr .= "</div>";

		$retStr .= "<div class='related_more' style='display:none'>";
		// The rest of the related items.
		for ($cnt = NUM_PROJECTS_TO_SHOW; $cnt < $numRecs; $cnt++) {
			$retStr .= genProjectRecDisplay($arrProjects[$cnt]);
		}
		$retStr .= "</div><!-- related_more -->";
	}

	return $retStr;
}

