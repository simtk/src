<?php

/**
 *
 * index_std.php
 * 
 * File to display front page.
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
 
require_once $gfcommon.'include/FusionForge.class.php';
require_once $gfcommon.'include/tag_cloud.php';
require_once $gfcommon.'include/Stats.class.php';
require_once $gfwww.'include/forum_db_utils.php';
?>

<style>
.project_representation>.wrapper_text>h4>a {
	font-size: 20px;
}
.project_representation>.wrapper_text>.type {
	color: rgb(94,150,225);
	font-size: 14px;
}
.project_representation>.wrapper_text>.content {
	color: rgb(167,167,167);;
	font-size: 14px;
}
</style>

<div class='row'>
    <div class='home_page_header'>
        <div class='left_container'>
            <div class='home_page_descr'>
                <p>Enabling groundbreaking biomedical research via open access to high-quality
                    simulation tools, accurate models, and the people behind them. </p>
            </div>
        </div>
        <div class='right_container'>
            <div class='home_page_info'>
<?php 
	$ff = new FusionForge();
	echo $ff->getNumberOfHostedProjects();
?>
		<a href="/search/search.php?srch=&search=search&type_of_search=soft&sort=downloads&page=0&">projects</a><br/>
<?php

	echo $ff->getTotalDownloads();
//	echo '&nbsp;<a href="#">downloads</a><br/>';
	echo '&nbsp;downloads<br/>';
	echo $ff->getNumberOfActiveUsers();
	echo '&nbsp;members<br/>';
//	echo '&nbsp;<a href="#">members</a><br/>';
?>
		<div class="btn-ctabox"><a class="btn-cta" href='account/register.php'>Join Us</a></div>
            </div>
        </div>
    </div>
</div>

<div class='projects_slideshow'>
	<div class='projects_slide'>
		<div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
	<!-- Wrapper for slides -->
			<div class="carousel-inner" role="listbox">
				<div class="item active">
					<img src="images/featuredProjects/dummy1.jpg" alt="Dummy1">
					<div class="carousel-caption">
						<a href="/projects/dummy1"><H2>Dummy1</H2></a>
						<p>Dummy1... <a href="/projects/dummy1">Learn more</a>
						</p>
					</div>
				</div>
				<div class="item">
					<img src="images/featuredProjects/dummy2.jpg" alt="Dummy2">
					<div class="carousel-caption">
						<a href="/projects/dummy2"><H2>Dummy2</H2></a>
						<p>Dummy2... <a href="/projects/dummy2">Learn more</a>
						</p>
					</div>
				</div>
			</div>
			<!-- Controls -->
			<a class="left carousel-control" href="#carousel-example-generic" role="button" data-slide="prev">
				<div class="simtk_carousel_arrow left" aria-hidden="true"></div>
				<span class="sr-only">Previous</span>
			</a>
			<a class="right carousel-control" href="#carousel-example-generic" role="button" data-slide="next">
				<div class="simtk_carousel_arrow right" aria-hidden="true"></div>
				<span class="sr-only">Next</span>
			</a>
		</div>
	</div>
</div>

<?php
	$arrProjLink = array();
	$arrLogo = array();
	$arrSummary = array();
	$arrValue = array();

	// Get top downloads in past week.
        $ff->getTopDownloadProjects($arrProjLink, $arrLogo, $arrValue, $arrSummary, 15);

/*
	// Get most active stats.
        $ff->getMostActiveProjects($arrProjLink, $arrLogo, $arrValue, $arrSummary, 15);
*/

	// Get projects with recent forum posts.
	$arrGroupIds = getMostForumPostsProjects($arrNumPosts);
        $ff->getMostForumPostsProjects($arrProjLink, $arrLogo, $arrValue, $arrSummary, 
		$arrGroupIds, $arrNumPosts, 15);

	// Get projects with new download files.
        $ff->getProjectsNewDownloadFiles($arrProjLink, $arrLogo, $arrValue, $arrSummary, 15);

	// Get projects with most total followers.
        $ff->getNumFollowersProjects($arrProjLink, $arrLogo, $arrValue, $arrSummary, 15);

	// Get projects with most new followers (in the last week).
        $ff->getNumFollowersProjects($arrProjLink, $arrLogo, $arrValue, $arrSummary, 15, 7);

	// Get projects with most new members (in the last week).
        $ff->getProjectsNewMembers($arrProjLink, $arrLogo, $arrValue, $arrSummary, 15);

	// Get top total downloads.
        $ff->getTopTotalDownloadProjects($arrProjLink, $arrLogo, $arrValue, $arrSummary, 15);

?>

<div class='news_and_trending_projects'>
	<div class='two_third_col'>
		<h2>Trending Projects</h2>

<?php
	// Randomize keys and pick 15 only from the associative array.
	// Get the keys.
	$keys = array_keys($arrProjLink);
	// Randomize keys.
	shuffle($keys);
	$cntKeys = 0;
	foreach ($keys as $unixGroupName) {
		$cntKeys++;
		if ($cntKeys > 15) {
			// Done.
			break;
		}
?>

		<div class="project_representation">
			<div class="wrapper_img">
				<a href="/projects/<?php echo $unixGroupName; ?>">
					<img onError="this.onerror=null;this.src='/logos/_thumb';"
					alt="Image not available" 
					src="/logos/<?php 
if (!empty($arrLogo[$unixGroupName])) {
	echo $arrLogo[$unixGroupName]; 
}
else {
	// No logo specified. Use a default.
	echo "_thumb"; 
}
?>"/>
				</a>
			</div>
			<div class="wrapper_text">
				<h4><?php echo $arrProjLink[$unixGroupName]; ?></h4>
				<?php echo wordwrap($arrSummary[$unixGroupName], 50, "<br/>\n", true); ?><br/>

<?php
		$theValues = $arrValue[$unixGroupName];
		for ($cnt = 0; $cnt < count($theValues); $cnt++) {
?>
			<?php echo $theValues[$cnt]; ?><br/>
<?php
		}
?>
		
			</div>
		</div>
<?php
	}
?>

	</div>
	<div class='one_third_col'>
        <div class='module_category'>
            <h2>By Category</h2>
            <div class='item_category'>
                <span class='category_header'>Biological applications</span>
                <ul>
                    <li><a href="/category/category.php?cat=309&sort=date&page=0&srch=&">Cardiovascular system</a></li>
                    <li><a href="/category/category.php?cat=421&sort=date&page=0&srch=&">Cell</a></li>
                    <li><a href="/category/category.php?cat=308&sort=date&page=0&srch=&">Myosin</a></li>
                    <li><a href="/category/category.php?cat=310&sort=date&page=0&srch=&">Neuromuscular system</a></li>
                    <li><a href="/category/category.php?cat=406&sort=date&page=0&srch=&">Protein</a></li>
                    <li><a href="/category/category.php?cat=307&sort=date&page=0&srch=&">RNA</a></li>
                    <li><a href="/category/category.php?cat=420&sort=date&page=0&srch=&">Tissue</a></li>
                </ul>
            </div>
            <div class='item_category'>
                <span class='category_header'>Biocomputational focus</span>
                <ul>
                    <li><a href="/category/category.php?cat=411&sort=date&page=0&srch=&">Experimental analysis</a></li>
                    <li><a href="/category/category.php?cat=412&sort=date&page=0&srch=&">Image processing</a></li>
                    <li><a href="/category/category.php?cat=426&sort=date&page=0&srch=&">Network modeling and analysis</a></li>
                    <li><a href="/category/category.php?cat=409&sort=date&page=0&srch=&">Physics-based simulation</a></li>
                    <li><a href="/category/category.php?cat=416&sort=date&page=0&srch=&">Statistical analysis</a></li>
                    <li><a href="/category/category.php?cat=415&sort=date&page=0&srch=&">Visualization</a></li>
                </ul>
            </div>
        </div>

<?php
$arrCommunities = array();
$resCommunities = db_query_params('SELECT trove_cat_id, fullname FROM trove_cat ' .
        'WHERE parent=1000 ' .
        'ORDER BY trove_cat_id',
        array());
$cntCommunities = db_numrows($resCommunities);
if ($cntCommunities > 0) {
?>
        <div class='module_category'>
		<h2>Communities</h2>
		<div class='item_category'>
			<ul>
<?php
}
while ($theRow = db_fetch_array($resCommunities)) {
	$trove_cat_id = $theRow['trove_cat_id'];
	$fullname = $theRow['fullname'];
	echo '<li><a href="/category/communityPage.php?cat=' .
		$trove_cat_id .
		'&sort=date&page=0&srch=&">' .
		$fullname .
		'</a></li>';
}
echo '<li><a href="/communities.php">See all communities...</a></li>';

if ($cntCommunities > 0) {
?>
			<ul>
		</div>
	</div>
<?php
}
?>

	<div class='module_home_news'>
		<h2>Jobs</h2>
		<div style="clear: both;"></div>

		<div class="item_home_news">
			<h4><a href="/opportunities.php">See all job openings...</a></h4>
		</div>
		<div style="clear: both;"></div>
	</div>
	        <div class='module_home_news'>
			<h2 id="news">News</h2>
<?php
//	echo news_show_latest(0,10,true,false,false,-1, true);
//	echo news_show_latest(0,10,true,false,false,0,false);
	echo news_show_latest(0,5,true,false,false,0,false,true);
?>
		</div>
	</div>
</div>

<?php
