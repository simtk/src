<?php

/**
 *
 * instructions.php
 * 
 * File to show instructions for Subversion.
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
require_once $gfwww.'scm/include/scm_utils.php';

$group_id = getIntFromRequest("group_id");
scm_header(array('title'=>_('SCM Repository'),'group'=>$group_id));
?>
<p>
Subversion is a free version control system used for coordinating source code between many contributors and housing periodic snapshots of a project's progress. It features an easy-to-use interface, wide platform support, and robust features. To make use of Subversion, you'll need to install a Subversion client on your computer. A list of clients can be found here encompassing several operating systems; however, our instructions will assume the use of the command-line client (available from source or as binaries from many distributors).
</p>

<h3>Checking out a project</h3>

<p>
Once you've installed a Subversion client, open a command line. To check out a Subversion repository, simply enter:
</p>
<p>
<b>svn checkout https://simtk.org/svn/website</b>
</p>
<p>
Or, if the repository is not accessible via anonymous browsing, but you are a project member with access to it:
</p>
<p>
<b>svn checkout --username user https://simtk.org/svn/website</b>
</p>
<p>
In the above example, user should be your simtk.org username. You will be prompted for a password before you can proceed.
</p>

<h3>Getting updates from a project</h3>

<p>
If you want to update a project that you've checked out with files that others have checked in recently, you don't have to check out your entire project all over again. You can simply update it with:
</p>
<p>
<b>svn update</b>
</p>

<h3>Seeing the status of your project files</h3>

<p>
If you've made changes to a project and you want to see a summary of files with your changes, you can get a list of changes with:
</p>

<p>
<b>svn status</b>
</p>

<h3>Submitting changes to a project</h3>

<p>
If you've added or removed any files from a project, first make sure that you've told Subversion to add or remove them from the repository using
</p>
<p>
<b>svn add filename</b>
</p>

and<br />
<p>
<b>svn remove filename</b>
</p>
<p>
Once you're ready to submit changes to a project, make sure you're in the top directory of that project and check them in with:
</p>
<p>
<b>svn commit -m message</b>
</p>
<p>
Note that Subversion requires a message with each checkin; this is meant to be a summary of changes included with this checkin for tracking purposes.
</p>

<h3>Getting help</h3>

<p>
For more help on using Subversion, you can either refer to the online book or simply type into your command line:
</p>
<p>
<b>svn help</b>
</p>
<?php
scm_footer();
