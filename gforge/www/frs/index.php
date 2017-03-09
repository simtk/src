<?php
/**
 * Project File Information/Download Page
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2002-2004 (c) GForge Team
 * Copyright 2010 (c) FusionForge Team
 * Copyright 2013, Franck Villaume - TrivialDev
 * http://fusionforge.org/
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
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
require_once $gfcommon.'frs/include/frs_utils.php';
require_once $gfcommon.'frs/FRSPackage.class.php';

$group_id = getIntFromRequest('group_id');
$package_id = getIntFromRequest('package_id');
$release_id = getIntFromRequest('release_id');

// Allow alternate content-type rendering by hook
$default_content_type = 'text/html';

$script = 'frs_index';
$content_type = util_negociate_alternate_content_types($script, $default_content_type);

if ($content_type != $default_content_type) {
	$hook_params = array();
	$hook_params['accept'] = $content_type;
	$hook_params['group_id'] = $group_id;
	$hook_params['release_id'] = $release_id;
	$hook_params['return'] = '';
	$hook_params['content_type'] = '';
	plugin_hook_by_reference('content_negociated_frs_index', $hook_params);
	if ($hook_params['content_type'] != ''){
		header('Content-type: ' . $hook_params['content_type']);
		echo $hook_params['content'];
	}
	else {
		header('HTTP/1.1 406 Not Acceptable', true, 406);
	}

	exit(0);
}

$cur_group_obj = group_get_object($group_id);
if (!$cur_group_obj) {
	exit_no_group();
}

// Check permission and prompt for login if needed.
session_require_perm('project_read', $group_id);

//
//	Members of projects can see all packages
//	Non-members can only see public packages
//
if (session_loggedin()) {
	if (user_ismember($group_id) || forge_check_global_perm('forge_admin')) {
		$pub_sql='';
	}
	else {
		$pub_sql=' AND is_public=1 ';
	}
}
else {
	$pub_sql=' AND is_public=1 ';
}

frs_header(array('title'=>_('Downloads'), 'group'=>$group_id));
plugin_hook("blocks", "files index");

require_once 'frs_front.php';

?>

<script src='/frs/showNotReadyDivs.js'></script>

<?php
frs_footer();
