<?php

/**
 *
 * features.php
 * 
 * File to display SimTK features.
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
 
require_once 'env.inc.php';
require_once $gfcommon.'include/pre.php';
$HTML->header(array());
?>

<style>
.features ul li {
	margin-left: 20px;
	list-style-type: disc;
}
.features ol li {
	margin-left: 20px;
	list-style-type: decimal;
}
</style>

<h2>Features</h2>
<br/>
 
<div class="features">
<ul>
<li><b>Cloud-based storage:</b>  Access files from your laptop or your mobile device from anywhere you have an Internet connection.</li><br/>
<li><b>Cloud-based execution of code (beta):</b> Let others run your code in the cloud. They can reproduce your results or use a web-based form to update your code and generate new results. Click on the “Simulations” link in the <a href="/projects/openknee">OpenKnee project</a> to see how this works.</li><br/>
<li><b>Customized privacy options:</b>  You choose what to share and with whom.</li><br/>
<li><b>Automated statistics:</b>  Track visits to your project webpages and file downloads.</li><br/>
<li><b>A plethora of tools for sharing and communicating with users:</b>  Post downloads and documents to share. Plus each project can create a repository with version control, a wiki, mailing lists, discussion forums, and issue trackers.</li><br/>
<li><b>Integration of GitHub:</b> You can connect your public GitHub repository to your SimTK project. Automatically download files from your GitHub repository to disseminate through your SimTK project.</li><br/>
<li><b>Automated backups:</b>  SimTK has multiple layers of backup, so your data is safe and secure.</li><br/>
<li><b>Assignment of DOIs to your files:</b>  Comply with publishers’ data-sharing policies by just checking a box and requesting a permanent identifier (a DOI) for any publicly shared resource on SimTK.</li><br/>
<li><b>Communities created around projects:</b> Create a collection of projects and automatically keep abreast of the latest news, publications, and discussions happening within that community. Check out the <a href="/category/communityPage.php?cat=1001&sort=date&page=0&srch=&">OpenSim community</a>.</li><br/>
<li><b>Project recommendations:</b>  SimTK automatically links your projects with other projects on the site through the “People also viewed” feature, increasing the visibility of your work.</li><br/>
</ul>
<br/><br/>
</div>

<?php
$HTML->footer(array());
?>
