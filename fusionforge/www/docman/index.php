<?php
/**
 * index.php
 *
 * FusionForge Documentation Manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2010-2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * Copyright 2016-2021, Tod Hing - SimTK Team
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

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'docman/DocumentManager.class.php';
require_once $gfcommon.'docman/Document.class.php';
require_once $gfcommon.'docman/DocumentFactory.class.php';
require_once $gfcommon.'docman/DocumentGroup.class.php';
require_once $gfcommon.'docman/DocumentGroupFactory.class.php';
require_once $gfcommon.'docman/include/DocumentGroupHTML.class.php';
require_once $gfcommon.'docman/include/utils.php';
require_once $gfcommon.'include/TextSanitizer.class.php'; // to make the HTML input by the user safe to store
require_once $gfcommon.'reporting/report_utils.php';
require_once $gfcommon.'reporting/ReportPerGroupDocmanDownloads.class.php';
require_once $gfwww.'include/html.php';
require_once $gfwww.'project/project_utils.php';

/* are we using docman ? */
if (!forge_get_config('use_docman'))
	exit_disabled('home');

/* get informations from request or $_POST */
$group_id = getIntFromRequest('group_id');

/* validate group */
if (!$group_id)
	exit_no_group();

$g = group_get_object($group_id);
if (!$g || !is_object($g))
	exit_no_group();

//session_require_perm('docman', $group_id, 'read');

/* is this group using docman ? */
if (!$g->usesDocman())
	exit_disabled();

if ($g->isError())
	exit_error($g->getErrorMessage(), 'docman');

$dirid = getIntFromRequest('dirid');
if (empty($dirid))
	$dirid = 0;

$childgroup_id = getIntFromRequest('childgroup_id');

/* everything sounds ok, now let do the job */
$action = getStringFromRequest('action');
switch ($action) {
	case "addfile":
	case "addsubdocgroup":
	case "deldir":
	case "delfile":
	case "editdocgroup":
	case "editfile":
	case "emptytrash":
	case "enforcereserve":
	case "forcereindexenginesearch":
	case "getfile":
	case "injectzip":
	case "lockfile":
	case "monitorfile":
	case "monitordirectory":
	case "releasefile":
	case "reservefile":
	case "trashdir":
	case "trashfile":
	case "updatecreateonline":
	case "updateenginesearch":
	case "updatewebdavinterface":
	case "validatefile": {
		// Check project read privilege.
		if (forge_check_perm('project_read', $group_id)) {
			include ($gfcommon."docman/actions/$action.php");
		}
		break;
	}
}

if (session_loggedin()) {
	$u = user_get_object(user_getid());
	if (!$u || !is_object($u)) {
		exit_error(_('Could Not Get User'));
	} elseif ($u->isError()) {
		exit_error($u->getErrorMessage(), 'my');
	}
}

html_use_storage();
//html_use_simplemenu();
html_use_jqueryui();
html_use_jquerysplitter();
use_javascript('/docman/scripts/DocManController.js');
//use_javascript('/js/sortable.js');

$title = _('Documents');

site_project_header(array('title'=>$title, 'group'=>$group_id, 'toptab'=>'docman', 'titleurl'=>'/docman/?group_id='.$group_id));

// Check project read privilege.
if (forge_check_perm('project_read', $group_id)) {

echo "\n";
echo "<div class=\"project_overview_main\">\n";
echo "<div style=\"display: table; width: 100%;\">\n"; 
echo "<div class=\"main_col\">\n";

echo '<div id="menu" >';
include ($gfcommon.'docman/views/menu.php');
echo '</div>';

echo '<div id="views">';
include ($gfcommon.'docman/views/views.php');
echo '</div>';
//echo "index.php";

echo "</div><!--main_col-->\n";

// Add side bar to show statistics and project leads.
$group = group_get_object($group_id);
constructSideBar($g);

echo "</div><!--display table-->\n</div><!--project_overview_main-->\n";

}

site_project_footer(array());

// Construct side bar
function constructSideBar($groupObj) {

	if ($groupObj == null) {
		// Group object not available.
		return;
	}

	echo '<div class="side_bar">';

	// Statistics.
	displayStatsBlock($groupObj);

	// Get project leads.
	$project_admins = $groupObj->getLeads();
	displayCarouselProjectLeads($project_admins);

	echo '</div>';
}
