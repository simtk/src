<?php

/**
 *
 * faq.php
 * 
 * File to display frequently asked questions.
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

<h2>FAQ</h2>
<br/>
<br/>

<b>Do I have to register with SimTK to utilize its resources?</b>
<br/>
<br/>

<div>You must be a member of SimTK in order to create a project and upload files. Some projects require that you be a SimTK member in order to download their resources. This enables them to determine the number of individuals utilizing their software and data, thus easily demonstrating the value of their resources to funding agencies. Other projects allow anyone, SimTK members and non-members, to access their resources.</div>
<br/>
<br/>

<b>What copyright or license applies to the resources I provide through my project?</b>
<br/>
<br/>

<div>When you upload your software or other files to SimTK, you choose what license or copyright is assigned to it. SimTK provides some commonly used licenses (like MIT, GPL, Creative Commons Attribution-Non-Commercial). You can also add your own custom license or not have any license at all for the resource you're sharing. To have all users agree to the license when they download your resource, you need to remember to check the box next to "Show download agreement."</div>
<br/>
<br/>

<b>Can projects be modified or even deleted once they were created?</b>
<br/>
<br/>

<div>Projects can be modified after creation. However, they cannot currently be deleted.</div>
<br/>
<br/>

<b>Is a project immediately public after clicking the "Create Project" button?</b>
<br/>
<br/>

<div>When you click the "Create Project" button, there is a checkbox that allows you to determine if the project is public or private. You can change this after you create the project. We recommend that individuals create public projects and instead opt to make certain sections of their project private. This enables others to learn the most about your project and encourages collaboration.</div>
<br/>
<br/>

Have a question that's not answered here? Check out our <a href="/plugins/phpBB/indexPhpbb.php?group_id=11&pluginname=phpBB">discussion forum page</a>.

<?php
$HTML->footer(array());
?>
