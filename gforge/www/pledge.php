<?php

/**
 *
 * pledge.php
 * 
 * File to display pledge.
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

<br/>
<h2 style="color: #f75236;">Our Pledge and Your Responsibility</h2>
<p><span style="color: #f75236;">Our pledge</span> When you visit or use Simtk.org, you maintain control over your personal data. When you set up an account, we do not use the information you provide for purposes other than sending you communication. We do not share this information with outside parties. We will use only non-identifying and aggregate information to better design our Web site and to gather internal usage statistics. For example, we may disclose the number of people that visited a site or downloaded information from a certain area on our Web site, but we do not use any of the information to track the usage pattern of a specific user without permission.</p>
<p><span style="color: #f75236;">Your responsibility</span> Any contribution to Simtk.org must be appropriate for sharing with our user community. In particular, if your contribution includes any patient data, all such data must de-identified in accordance with U.S. confidentiality and security laws and requirements, and your disclosure of such data must be properly authorized and in compliance with all applicable laws and regulations.</p>

<br/><br/>

<?php
$HTML->footer(array());
?>
