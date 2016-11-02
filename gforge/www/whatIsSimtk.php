<?php

/**
 *
 * whatIsSimtk.php
 * 
 * File to display what is SimTK.
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
$HTML->header(array());
?>

<h2>What is SimTK?</h2>
<br/>
SimTK is a <b>free project-hosting platform</b> for the <b>biomedical computation community</b> that:
 
<div class="whatissimtk">
<ul>
<li>Enables you to easily share your software, data, and models</li>
<li>Tracks the impact of the resources you share</li>
<li>Provides the infrastructure so you can support and grow a community around your projects</li>
<li>Connects you and your project to thousands of researchers working at the intersection of biology, medicine, and computations</li>
</ul>
Individuals have created SimTK projects to meet publisher and funding agencies’ software and data sharing requirements, run scientific challenges, create a collection of their community’s resources, and much more.
<br/><br/>
 
<b>Explore projects</b> using the search bar above
<br/><br/>
 
<a href="/account/register.php">Join SimTK</a> <b>and create your own projects</b>
</div>

<?php
$HTML->footer(array());
?>
