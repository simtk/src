<?php

/**
 *
 * header.php
 * 
 * File to handle header.
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
 
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SimTK: Welcome</title>
<link rel="icon" type="image/png" href="/images/icon.png">
<link rel="shortcut icon" type="image/png" href="/images/icon.png">
<link rel="alternate" title="SimTK - Project News Highlights RSS" href="/export/rss_sfnews.php" type="application/rss+xml">
<link rel="alternate" title="SimTK - Project News Highlights RSS 2.0" href="/export/rss20_news.php" type="application/rss+xml">
<link rel="alternate" title="SimTK - New Projects RSS" href="/export/rss_sfprojects.php" type="application/rss+xml">
<link rel="alternate" title="SimTK - New Activity RSS" href="/export/rss20_activity.php?group_id=0" type="application/rss+xml"><link rel="search" title="SimTK" href="/export/search_plugin.php" type="application/opensearchdescription+xml">

<script type="text/javascript" src="/js/common.js"></script>
<script type="text/javascript" src="/javascript/jquery/jquery.min.js"></script>
<script type="text/javascript" src="/scripts/jquery-storage/jquery.Storage.js"></script>
<script type="text/javascript">//<![CDATA[
		jQuery(window).load(function(){
			jQuery(".quicknews").hide();
			setTimeout("jQuery('.feedback').hide('slow')", 5000);
			setInterval(function() {
					setTimeout("jQuery('.feedback').hide('slow')", 5000);
				}, 5000);
		});
                $(function(){
                    // hack to remove documents from the SELECT
                    $(".search_select").find('[value=alldocs]').remove(); 
                });
		//]]>
</script>
<script type="text/javascript" src="/js/jquery-1.11.2.min.js"></script>
<script type="text/javascript" src="/themes/simtk/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/themes/simtk/js/help_ruler.js"></script>
<script type="text/javascript" src="/themes/simtk/js/dropdowns-enhancement.js"></script>
<script type="text/javascript" src="/themes/simtk/js/jquery.customSelect.min.js"></script>
<script type="text/javascript" src="/themes/simtk/js/jquery.popupWindow.js"></script>

<link rel="stylesheet" type="text/css" href="/themes/css/fusionforge.css">
<link rel="stylesheet" type="text/css" href="/themes/simtk/css/theme.css">
<link rel="stylesheet" type="text/css" href="/themes/simtk/css/carousel.css">
<meta name="Forge-Identification" content="FusionForge:5.3.1-1">
</head>

<body>
<div class="the_header">
<div class="cont_header">

<nav class="navbar navbar-simtk" role="navigation">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" href="/"><img src="/themes/simtk/images/header/logo.png" alt="FusionForge Home"/></a></div>
	
	<!-- HAVE TWO ADDITIONAL CLASSES: intend and action --->
	<div id="navbar" class="navbar-collapse collapse">
	    <!-- Decreased width of Search Box and moved to this location --->
		<ul class="nav navbar-nav navbar-right">
<div class="the_search_box">

<form id='searchBox' action='/search/search.php' method='get'>
	<div class='search_box_inputs'>
		<input type='text' id='searchBox-words' 
			placeholder='Search for' name='srch' 
			value='' required='required' />
		<input type='submit' name='Search' value='Search' />
	</div>
	<span class='search_box_select'>
		<input type='radio' name='type_of_search' value='soft' checked='checked' />&nbsp;<label>Projects</label>&nbsp;&nbsp; <input type='radio' name='type_of_search' value='people'  />&nbsp;<label>People</label>
	</span>
</form>

</div>


<li class="dropdown">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Projects<span class="caret"></span></a>
	<ul class="dropdown-menu" style="min-width:330px;" role="menu">
		<li class="dropdown-submenu">
			<a href="#" data-toggle="dropdown">Project categories</a>
			<ul class="dropdown-menu">
				<li class="dropdown-header">Biological applications</li>
				<li class="intend"><a href="/category/category.php?cat=309&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Cardiovascular system</a></li>
				<li class="intend"><a href="/category/category.php?cat=421&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Cell</a></li>
				<li class="intend"><a href="/category/category.php?cat=308&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Myosin</a></li>
				<li class="intend"><a href="/category/category.php?cat=310&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Neuromuscular system</a></li>
				<li class="intend"><a href="/category/category.php?cat=406&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Protein</a></li>
				<li class="intend"><a href="/category/category.php?cat=307&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">RNA</a></li>
				<li class="intend"><a href="/category/category.php?cat=420&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Tissue</a></li>
				<li class="dropdown-header">Biocomputational focus</li>
				<li class="intend"><a href="/category/category.php?cat=411&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Experimental analysis</a></li>
				<li class="intend"><a href="/category/category.php?cat=412&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Image processing</a></li>
				<li class="intend"><a href="/category/category.php?cat=426&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Network modeling and analysis</a></li>
				<li class="intend"><a href="/category/category.php?cat=409&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Physics-based simulation</a></li>
				<li class="intend"><a href="/category/category.php?cat=416&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Statistical analysis</a></li>
				<li class="intend"><a href="/category/category.php?cat=415&amp;sort=date&amp;page=0&amp;srch=&amp;" tabindex="-1">Visualization</a></li>
			</ul>
		</li>
		<li><a class="action" href="/register">Create a new project</a></li>

	</ul>
</li>
<li class="dropdown">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">About<span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu">
		<li class="intend"><a href="/whatIsSimtk.php">What is SimTK?</a></li>
		<li class="intend"><a href='/features.php'>Features</a></li>
		<li class="intend"><a href='/faq.php'>FAQ</a></li>
		<li class="intend"><a href='/sendmessage.php?touser=101'>Contact</a></li>
	</ul>
</li>

<li><a href="/account/register.php">Sign Up</a></li> <li><a href="/account/login.php?return_to=%2F">Log In</a></li>		</ul>
	</div>
</nav>

</div>
</div>
<div class="the_body">
<div class="cont_body">

<?php
	$strFileAnnouncement = "/usr/share/gforge/www/announcement.html";
	if (file_exists($strFileAnnouncement)) {
		// Announcement file exists.
		$handle = fopen($strFileAnnouncement, "rb");
		if ($handle !== false) {
			// Opened successfully. Get content.
			$strAnnouncement = stream_get_contents($handle);
			fclose($handle);

			// Display announcement.
			echo $strAnnouncement;
		}
	}
?>

<div class="row_body">
<div class="maindiv">

