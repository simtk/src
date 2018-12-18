<?php

/**
 *
 * searchPhpbb.php
 * 
 * Display phpBB search results.
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
 
// Display phpBB search results into an iframe.

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';

// Display page header.
$HTML->header(array());

if (isset($_GET['author'])) {
	$author = getStringFromRequest('author');
	if (!preg_match('/^[a-z0-9][-a-z0-9_\.]+\z/', $author)) {
                return;
        }
}
else {
	return;
}

// Cross launch into phpbb.

$strPhpbbURL = "/plugins/phpBB/search.php?author=" . $author;


// NOTE: rand() is needed to avoid browser caching logged in user.
// Otherwise, even after the user has logged out, back button will
// load information of previous user.
echo '<iframe name="' . rand() . '" src="' . util_make_url($strPhpbbURL) . '" ' .
	'frameborder="0" scrolling="no" width="100%" height="700px">' .
	'</iframe>';

?>

<script src='iframeAdjust.js'></script>

<?php

// Display page footer.
$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
