<?php
/**
 * Welcome page
 *
 * This is the page user is redirected to after first site login
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2016-2018, Henry Kwong, Tod Hing - SimTK Team
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

if (session_loggedin()) {
	// Redirect to my page if logged in.
	session_redirect('/plugins/mypage');
}

$HTML->header(array('title'=>'Welcome to ' . forge_get_config('forge_name'), 'pagename'=>''));

print '<br/><br/><p>You are now a member of SimTK, a thriving community of biomedical researchers who share software, models, data, and knowledge.  You can now:</p>';
print '<ul>';
print '<li><a href="/register/">Create</a> your own projects</li>';
print '<li><a href="/search/search.php?srch=&search=search&type_of_search=soft&sort=downloads&page=0">Explore</a> and contribute to other projects</li>';
print '<li><a href="/search/searchPeople.php?type_of_search=people&srch=scott">Connect</a> with other SimTK members</li>';
print '</ul><br/>';
print '<p>We hope you find the site valuable and would love to hear how you' . "'" . 're using SimTK.  Send your stories to us at <a href="/sendmessage.php?touser=101">feedback@simtk.org</a>.  If you have feature requests or bug reports, we want to know about those, too.  Please file an issue <a href="/tracker/?group_id=11">here</a>.<br/><br/>Enjoy the site!</p>';

print '<p>';
printf('-- the %s staff', forge_get_config('forge_name'));
print '</p>';

$HTML->footer(array());
