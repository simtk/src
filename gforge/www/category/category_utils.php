<?php

/**
 *
 * category_utils.php
 *
 * Utility supporting category display.
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
require_once $gfwww.'news/news_utils.php';
require_once $gfwww.'include/forum_db_utils.php';

require_once $gfcommon.'include/FusionForge.class.php';
require_once $gfcommon.'include/tag_cloud.php';
require_once $gfcommon.'include/Stats.class.php';

function show_category_news($numNewsToShow, $categoryId, $suppressDetails=false) {
	echo news_show_latest(0,$numNewsToShow,true,false,false,0,false,true,$categoryId,$suppressDetails);
}

function show_category_forum_posts($numPostsToShow, $categoryId, $suppressDetails=false) {
	$strDiscussions = getCategoryPosts($numPostsToShow, $categoryId, $suppressDetails);
	if (trim($strDiscussions) == "") {
		$strDiscussions = "No Discussions Found";
	}

	echo $strDiscussions;
}


// Display publications in category page.
function show_category_publications($numPublicationsToShow, $categoryId) {

        $re = '/		# Split sentences on whitespace between them.
		(?<=		# Begin positive lookbehind.
		[.!?]		# Either an end of sentence punct,
		| [.!?][\'"]	# or end of sentence punct and quote.
		)		# End positive lookbehind.
		(?<!		# Begin negative lookbehind.
		Mr\.		# Skip either "Mr."
		| Mrs\.		# or "Mrs.",
		| Ms\.		# or "Ms.",
		| Jr\.		# or "Jr.",
		| Dr\.		# or "Dr.",
		| Prof\.	# or "Prof.",
		| Sr\.		# or "Sr.",
				# or... (you get the idea).
		)		# End negative lookbehind.
		\s+		# Split on whitespace between sentences.
		/ix';

	$sqlQueryPub = "SELECT g.group_name, g.group_id, g.unix_group_name, g.type_id, " .
			"p.pub_id, p.publication, p.publication_year, " .
			"p.url, p.is_primary, p.abstract " .
		"FROM plugin_publications p " .
		"JOIN groups g ON p.group_id=g.group_id " .
		"JOIN trove_group_link tgl " .
		"ON p.group_id=tgl.group_id " .
		"WHERE g.simtk_is_public=1 ";
	if (isset($categoryId) && $categoryId != "") {
		// Has category id.
		$sqlQueryPub = $sqlQueryPub . "AND tgl.trove_cat_id=$1 ";
	}
	$sqlQueryPub = $sqlQueryPub . 
		"AND g.status='A' " .
		"AND is_primary=1 " .
		"ORDER BY publication_year DESC " .
		"LIMIT $numPublicationsToShow";

	if (isset($categoryId) && $categoryId != "") {
		// Has category id.
		$result = db_query_params($sqlQueryPub, array($categoryId));
	}
	else {
		// No category id.
		$result = db_query_params($sqlQueryPub, array());
	}

	$strResult = "";
	while ($row = db_fetch_array($result)) {
		$strResult .= "<div class='item_publications'>";

		$theGroupId = $row['group_id'];

		$theUrl = "/plugins/publications/index.php?" .
			"type=group" .
			"&id=" . $theGroupId .
			"&pluginname=publications#";

		$thePublication = $row["publication"];


		$groupName = util_make_link_g(
			$row['unix_group_name'],
			$row['group_id'],
			$row['group_name']);

		$strPubLink = '<a href="' . $theUrl . '">';
		$strPubLink .= $thePublication;
		$strPubLink .= '</a>';

		$strResult .= "<p>" . $strPubLink . "</p>\n";

		$strResult .= "</div>";
	}

	if (trim($strResult) == "") {
		$strResult = "No Publications Found";
	}

	echo $strResult;
}


?>
