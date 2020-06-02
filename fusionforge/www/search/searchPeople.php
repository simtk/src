<?php

/**
 *
 * searchPeople.php
 * 
 * File to display search results of people.
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
 
require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/FusionForge.class.php';

// Allow alternate content-type rendering by hook
$default_content_type = 'text/html';

global $rows;

$srch = "";
if (isset($_GET["srch"])) {
	// Get search string.
	$srch = $_GET["srch"];
	$theRegex = '/[^a-z0-9_ :\-"\']/i';
	$notValid = preg_match($theRegex, $srch);
	if ($notValid) {
		// Ignore input.
		session_redirect("/search/searchPeople.php?type_of_search=people&srch=___ERROR___");
	}
}

$HTML->header(array('title'=>'Search','pagename'=>''));

// Force IE NOT to go into "Compatibility" mode.
//header("X-UA-Compatible: IE=Edge");

?>


<script src='/search/SimtkPeopleFilters.js'></script>
<script src='/category/FilterSearch.js'></script>
<link rel='stylesheet' href='../category/category.css' type='text/css' />

<script type="text/javascript" src="/themes/simtk/js/simple-expand.js"></script>
<link rel="stylesheet" type="text/css" href="/themes/simtk/css/theme.css">

<style>
.people_representation>.wrapper_text>h4>a {
	font-size: 24px;
}
.people_representation>.wrapper_text>.type {
	color: rgb(94,150,225);
	font-size: 14px;
}
.people_representation>.wrapper_text>.content {
	color: rgb(167,167,167);
	font-size: 14px;
}
.CountPeople {
	color: #f75236;
	font-size: 40px;
}
</style>

<div class="sidebarleft">
<div class="category-shell">

<div id="myHeaderContainer" class="category-container">
	<div class="clearer"></div>
</div><!--myHeaderContainer-->

<div id="myPeopleContainer" class="category-container">

	<div class="category-header">
		<div class="searchresult-text">
<?php
if ($srch == "___ERROR___") {
?>
			<h2>Invalid text to search for</h2>
<?php
}
else {
?>
			<h2><span class="searchresults">People search results: </span><?php echo $srch ?></h2>
<?php
}
?>
		</div>
		<div class="clearer"></div>
	</div><!-- category-header-->

	<div class="category-left">

		<div id="searchstats" class="searchstats">
			<span class="CountPeople"></span><br/>
			<span class="SearchMsg">people matched<br/>your search criteria.<br/><br/>
				<span style="color:#f75236;font-size:12px;font-weight:bold;">Find other people you know using the search bar above.</span></span>
		</div><!--searchstats-->
	</div>

	<div class="category-center">
	<script>
		SimtkPeopleFilters.setup($('#myPeopleContainer'));
	</script>

	</div><!--category-center-->

	<div class="clearer"></div>
</div><!--category-container-->

<div class="clearer"></div>

</div><!--category-shell-->

</div><!--sidebarleft-->



<?php

$HTML->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
