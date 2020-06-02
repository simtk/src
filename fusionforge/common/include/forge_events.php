<?php

/**
 *
 * forge_events.php
 *
 * Copyright 2016-2019, Henry Kwong, Tod Hing - SimTK Team
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

abstract class ForgeEvent extends Plugin {

	function __construct() {
		parent::__construct() ;
		$this->name = "event" ;
		$this->text = "event" ;
		$this->_addHook('group_approve');
		$this->_addHook('scm_admin_update');
		$this->_addHook('site_admin_option_hook');
	}

	abstract function trigger_job($name);

	function group_approve($params) {
		return $this->trigger_job('create_scm_repos');
	}

	function scm_admin_update($params) {
		return $this->trigger_job('create_scm_repos');
	}

	function site_admin_option_hook($params) {
		$action = getStringFromRequest('action');
		echo '<li><a name="jobs"></a>'.util_make_link('/admin/?action=listjobs#jobs', _('Jobs'))."\n";
		if ($action == 'listjobs') {
			echo '<ul>';
			echo '<li>'.util_make_link('/admin/?action=runjobs&job=create_scm_repos#jobs', _('Create SCM Repositories')).'</li>'."\n";
			echo '<li>'.util_make_link('/admin/?action=runjobs&job=scm_update#jobs', _('Upgrade Forge Software')).'</li>'."\n";
			echo '</ul>';
		}
		echo '</li>';
		if ($action == 'runjobs') {
			$job = getStringFromRequest('job');
			$job = util_ensure_value_in_set($job, array('create_scm_repos', 'scm_update'));
			$this->trigger_job($job);
		}
		echo '<li><a name="version"></a>'.util_make_link('/admin/?action=version#version', _('Version'))."\n";
		if ($action == 'version') {
			echo '<pre>';
			if (is_dir("/opt/acosforge/.svn")) {
				system("cd /opt/acosforge; svn info --config-dir /tmp 2>&1");
			}
			if (is_dir("/opt/acosforge/.git")) {
				system("cd /opt/acosforge; git svn info 2>&1");
			}
			echo '</pre>'."\n";
		}
		echo '</li>';
	}
}

class PgForgeEvent extends ForgeEvent {
	function trigger_job($name) {
		return db_query_params("NOTIFY $name", array());
	}
}

register_plugin (new PgForgeEvent) ;

$pm = plugin_manager_get_object() ;
$pm->SetupHooks () ;
