<?php

/*
 * MoinMoinWiki plugin
 *
 * Copyright 2009-2011, Roland Mas
 * Copyright 2006, Daniel Perez
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
 *
 */

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';

$group_id = getIntFromRequest('group_id');
$pluginname = 'moinmoin' ;

$group = group_get_object($group_id);
if (!$group) {
	exit_error ("Invalid Project", "Invalid Project");
}

// Check permission and prompt for login if needed.
session_require_perm('project_read', $group_id);

if (!$group->usesPlugin ($pluginname)) {
	exit_error ("Error", "First activate the $pluginname plugin through the Project's Admin Interface");
}

$params = array () ;
$params['toptab']      = $pluginname;
$params['group']       = $group_id;
$params['title']       = 'MoinMoinWiki' ;
$params['pagename']    = $pluginname;
$params['sectionvals'] = array ($group->getPublicName());

if (file_exists ('/var/lib/gforge/plugins/moinmoin/wikidata/'.$group->getUnixName().'.py')) {
/*
	echo '<iframe src="' .
		util_make_url('/plugins/moinmoin/' . 
		$group->getUnixName() . 
		'/FrontPage') . 
		'" frameborder="0" width=100% height=700></iframe>' ;
*/

	$serverURL = "https://" . $_SERVER['SERVER_NAME'];
	$destURL = $serverURL . "/plugins/moinmoin/" . $group->getUnixName() . "/FrontPage";

	// Show contents from Wiki (not using iframe). Using this method:
	// Double-scrolling of iframe is avoided.
	// Upong clicking a link within Wiki page, the link shows up in the address bar of browser.
	// The user logged-in state is recognized within Wiki, such that logged in user with the
	// permissions can edit the Wiki page, while anonymous users has the immutable page which
	// is not allowed to be edited.
	header("Location: " . "/plugins/moinmoin/" . $group->getUnixName() . "/FrontPage");
}

site_project_header($params);

// Submenu title information.
$subMenuTitle = array();
$subMenuUrl = array();
$subMenuTitle[] = 'View Wiki';
$subMenuUrl[]='/plugins/moinmoin/frame.php?group_id=' . $group_id;
// Show the submenu.
echo $HTML->beginSubMenu();
echo $HTML->printSubMenu($subMenuTitle, $subMenuUrl, array());
echo $HTML->endSubMenu();

if (!file_exists ('/var/lib/gforge/plugins/moinmoin/wikidata/'.$group->getUnixName().'.py')) {
	print '<h2>'._('Wiki not created yet, please wait for a few minutes.').'</h2>';
}

site_project_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
