<?php

/**
 *
 * communities.php
 * 
 * All Communities page.
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
 
require_once 'env.inc.php';
require_once $gfcommon.'include/pre.php';
$HTML->header(array());

$arrCommunities = array();
$resCommunities = db_query_params('SELECT trove_cat_id, fullname, simtk_intro_text FROM trove_cat ' .
	'WHERE parent=1000 ' .
	'ORDER BY trove_cat_id',
	array());
?>

<h2>Communities</h2>
<br/>
<div class="btn-ctabox"><a class="btn-cta" href="/sendmessage.php?recipient=admin&subject=Community%20Request">Request a community</a></div>

<br/>

<div class="news_communities_trending_projects">
<div class="two_third_col">
<div class="categories_home">

<?php
while ($theRow = db_fetch_array($resCommunities)) {

	$trove_cat_id = $theRow['trove_cat_id'];
	$fullname = $theRow['fullname'];
	$descr = $theRow['simtk_intro_text'];
?>

	<div class="item_home_categories">
		<div class="categories_text">
			<h4>
				<a href="/category/communityPage.php?cat=<?php
					echo $trove_cat_id; ?>&sort=date&page=0&srch=&" class="title"><?php
					echo $fullname; ?>
				</a>
			</h4>
			<p><?php echo $descr; ?>
			</p>
		</div>
		<div style="clear: both;"></div>
	</div>

<?php
}
?>

</div>
</div>
</div>

<?php
$HTML->footer(array());
?>

