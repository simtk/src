<?php

/**
 *
 * follow-info.php
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
 
require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';

require_once $gfwww.'news/news_utils.php';
require_once $gfwww.'include/trove.php';
require_once $gfwww.'include/project_summary.php';
require_once $gfcommon.'include/tag_cloud.php';
require_once $gfcommon.'include/HTTPRequest.class.php';
require_once $gfcommon.'widget/WidgetLayoutManager.class.php';

require_once $gfplugins.'following/www/following-utils.php';
require_once $gfplugins.'following/include/Following.class.php';
require_once $gfcommon.'include/User.class.php';

$title = _('Following');
$HTML->header(array('title'=>$title));
html_use_jqueryui();


//site_project_header(array('title'=>$title, 'group'=>$group_id, 'toptab'=>'following'));

?>

<div class="project_overview_main">
    <div style="display: table; width: 100%;"> 
        <div class="main_col">

		<h3>Following Projects</h3>
		
		<p>To encourage communication and knowledge transfer about projects on SimTK, members can follow any number of projects. By following a project, you will receive updates and news relevant to the project. There are two types of project followers, public or private:</p>
		
		
		<ul>
		<li><p><b>Public follower:</b> Others will see your name and profile in the list of project followers. By becoming a public follower, other members of SimTK may message you regarding your interest and involvement in the project.</p></li>
		<li><p><b>Private follower:</b> Your name and profile will NOT be displayed in the list of followers. The number of private followers is displayed at the top of the follower list.</p></li>
		</ul>
		
		<p>To see the list of public followers on a project, click on the number next to the “Follow: SimTK” label.</p>

		<br />
		<h4>How to Follow</h4>
		
		<p>In order to follow a project, you must be a <a href="/account/register.php">member</a> of SimTK. Use the drop-down menu next to the “Follow: SimTK” label in the top right of every project page to follow the project as a public or private follower or to unfollow the project.</p>
		
        </div>
    </div>
</div>



<?php

site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
