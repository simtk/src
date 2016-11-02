<?php
/**
 * FusionForge Project Home
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010, FusionForge Team
 * Copyright (C) 2011-2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2013, Franck Villaume - TrivialDev
 * http://fusionforge.org
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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
		
		<p>To see the list of public followers on a project, click on the number next to the Follow button.</p>

		<br />
		<h4>How to Follow</h4>
		
		<p>In order to follow a project, you must be a <a href="/account/register.php">member</a> of SimTK. Clicking on the "Follow" button in the top right of every project page will give you the option to follow the project as a public or private follower. If you are already following a project, the button allows you to unfollow the project.</p>
		
        </div>
    </div>
</div>



<?php

site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
