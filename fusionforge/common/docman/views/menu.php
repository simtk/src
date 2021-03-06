<?php
/**
 * FusionForge Documentation Manager
 *
 * Copyright 1999-2001, VA Linux Systems
 * Copyright 2000, Quentin Cregan/SourceForge
 * Copyright 2002-2004, GForge Team
 * Copyright 2010-2011, Franck Villaume - Capgemini
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
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

/* please do not add require here : use www/docman/index.php to add require */
/* global variables used */
global $HTML; // html object
global $d_arr; // document array
global $group_id; // id of group

/* allow anyone to read?
if (!forge_check_perm('docman', $group_id, 'read')) {
	$return_msg= _('Document Manager Access Denied');
	session_redirect('/docman/?group_id='.$group_id.'&warning_msg='.urlencode($return_msg));
}
*/

/* create the submenu following role, rules and content */
$menu_text = array();
$menu_links = array();
$menu_attr = array();

if (forge_check_perm('docman', $group_id, 'approve') || forge_check_perm('docman', $group_id, 'admin')) {
  $menu_text[] = _('View/Edit Documents');
}
else {
  $menu_text[] = _('View Documents');
}
$menu_links[] = '/docman/?group_id='.$group_id;
$menu_attr[] = array('title' => _('View documents and folders in 2 panels. Left a folder tree, right a list of files of selected folder.'), 'id' => 'listFileDocmanMenu', 'class' => 'tabtitle-nw');

if (forge_check_perm('docman', $group_id, 'submit')) {
	$menu_text[] = _('Add new item');
	$menu_links[] = '/docman/?group_id='.$group_id.'&amp;view=additem';
	$menu_attr[] = array('title' => _('Add a new item such as file, create directory, inject a ZIP at root level.'), 'id' => 'addItemDocmanMenu', 'class' => 'tabtitle');
}

/*
if ($g->useDocmanSearch()) {
	$menu_text[] = _('Search');
	$menu_links[] = '/docman/?group_id='.$group_id.'&amp;view=search';
	$menu_attr[] = array('title' => _('Search documents in this project using keywords.'), 'id' => 'searchDocmanMenu', 'class' => 'tabtitle');
}
*/

if (forge_check_perm('docman', $group_id, 'approve')) {
    $dm = new DocumentManager($g);
    if (!$dm->isTrashEmpty()) {
        $menu_text[] = _('Trash');
        $menu_links[] = '/docman/?group_id='.$group_id.'&amp;view=listtrashfile';
        $menu_attr[] = array('title' => _('Recover or delete permanently files with deleted status.'), 'id' => 'trashDocmanMenu', 'class' => 'tabtitle');
    }
}

if (forge_check_perm('docman', $group_id, 'admin')) {
	$menu_text[] = _('Reporting');
	$menu_links[] = '/docman/?group_id='.$group_id.'&amp;view=reporting';
	$menu_attr[] = array('title' => _('Docman module reporting.'), 'id' => 'reportDocmanMenu', 'class' => 'tabtitle');
	$menu_text[] = _('Administration');
	$menu_links[] = '/docman/?group_id='.$group_id.'&amp;view=admin';
	$menu_attr[] = array('title' => _('Docman module administration.'), 'id' => 'adminDocmanMenu', 'class' => 'tabtitle');
}

if (count($menu_text)) {
	echo $HTML->subMenu($menu_text, $menu_links, $menu_attr);
}

plugin_hook("blocks", "doc index");
