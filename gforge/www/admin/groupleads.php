<?php
/**
 * List of all groups in the system.
 *
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright 2013, Franck Villaume - TrivialDev
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
require_once $gfwww.'admin/admin_utils.php';

site_admin_header(array('title'=>_('Project List')));

$headers = array( _('Project Name'), _('Project Leads'));

echo $HTML->listTableTop($headers);

//echo $HTML->listTableTop();

//$res = db_query_params('SELECT count(group_id) as num_leads from groups, user_group where groups_group_id = user_group.group_id and project_lead > 0',array());

$res = db_query_params('SELECT group_name, count(user_group.group_id) as num_leads from groups, user_group where groups.group_id = user_group.group_id and project_lead > 0 group by group_name, user_group.group_id',array());

$num_rows = db_numrows( $res );
//echo "rows: " . $num_rows;

while ($grp = db_fetch_array($res)) {

	echo '<tr>';
	echo '<td>'.$grp['group_name'].'</td>';
	echo '<td>'.$grp['group_id'].'</td>';
	echo '<td>'.$grp['unix_group_name'].'</td>';
	echo '<td>'.$grp['num_leads'].'</td>';
	echo '</tr>';
}

echo $HTML->listTableBottom();

site_admin_footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
