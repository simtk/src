<?php
/**
 * scmhookPlugin Class
 * Copyright 2011, Franck Villaume - Capgemini
 * Copyright (C) 2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012-2013, Franck Villaume - TrivialDev
 * Copyright 2012, Benoit Debaenst - TrivialDev
 * Copyright 2016-2025, SimTK Team
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

class scmhookPlugin extends Plugin {
	var $text;

	function __construct() {
		parent::__construct();
		$this->name = 'scmhook';
		$this->text = 'Scmhook'; // To show in the tabs, use...
		$this->_addHook('groupmenu');	// To put into the project tabs
		$this->_addHook('groupisactivecheckbox'); // The "use ..." checkbox in editgroupinfo
		$this->_addHook('groupisactivecheckboxpost'); //
		$this->_addHook('scm_admin_page');
		$this->_addHook('scm_admin_update');
		$this->_addHook('artifact_extra_detail');
		$this->_addHook('task_extra_detail');
	}

	function CallHook($hookname, &$params) {
		switch ($hookname) {
			case 'scm_admin_page': {
				$group_id = $params['group_id'];
				$scm_plugin = $params['scm_plugin'];
				$group = group_get_object($group_id);
				if ($group->usesPlugin($this->name) && $scm_plugin) {
					$this->displayScmHook($group_id, $scm_plugin);
				}
				break;
			}
			case 'scm_admin_update': {
				$this->update($params);
				break;
			}
			case 'artifact_extra_detail': {
				$group_id = $params['group_id'];
				$group = group_get_object($group_id);
				if ($group->usesPlugin($this->name)) {
					$this->artifact_extra_detail($params);
				}
				break;
			}
			case 'task_extra_detail': {
				$group_id = $params['group_id'];
				$group = group_get_object($group_id);
				if ($group->usesPlugin($this->name)) {
					$this->task_extra_detail($params);
				}
				break;
			}
		}
		return true;
	}

	function exists($group_id) {
		$res = db_query_params('SELECT id_group FROM plugin_scmhook WHERE id_group = $1', array($group_id));
		if (!$res)
			return false;

		if (db_numrows($res))
			return true;

		return false;
	}

	function add($group_id) {
		if (!$this->exists($group_id)) {
			$res = db_query_params('INSERT INTO plugin_scmhook (id_group) VALUES ($1)', array($group_id));
			if (!$res)
				return false;

		}
		return true;
	}

	function remove($group_id) {
		if ($this->exists($group_id)) {
			$res = db_query_params('DELETE FROM plugin_scmhook where id_group = $1', array($group_id));
			if (!$res)
				return false;

		}
		return true;
	}

	function update($params) {
		$group_id = $params['group_id'];
		$hooksString = '';
		foreach($params as $key => $value) {
			if ($key == strstr($key, 'scm')) {
				$hookname = preg_replace('/scm[a-z][a-z]+_/','',$key);
				if ($key != $hookname) {	//handle the case of scm_enable_anonymous
					if (strlen($hooksString)) {
						$hooksString .= '|'.$hookname;
					} else {
						$hooksString .= $hookname;
					}
				}
			}
		}
		$res = db_query_params('UPDATE plugin_scmhook set hooks = $1, need_update = 1 where id_group = $2',
					array($hooksString, $group_id));

		if (!$res)
			return false;

		return true;
	}

	function displayScmHook($group_id, $scm) {
		global $HTML;
		use_javascript('/js/sortable.js');
		echo $HTML->getJavascripts();
		$hooksAvailable = $this->getAvailableHooks($group_id);
		$statusDeploy = $this->getStatusDeploy($group_id);
		$hooksEnabled = $this->getEnabledHooks($group_id);
		if (count($hooksAvailable)) {
			echo '<div id="scmhook">';
			if ($statusDeploy)
				echo '<p class="warning">'._('Hooks management update process waiting ...').'</p>';

			echo '<h2>'._('Enable Repository Hooks').'</h2>';
			switch ($scm) {
				case "scmsvn": {
					$this->displayScmSvnHook($hooksAvailable, $statusDeploy, $hooksEnabled);
					break;
				}
				case "scmhg": {
					$this->displayScmHgHook($hooksAvailable, $statusDeploy, $hooksEnabled);
					break;
				}
				case "scmgit": {
					$this->displayScmGitHook($hooksAvailable, $statusDeploy, $hooksEnabled);
					break;
				}
				default: {
					echo '<div class="warning">'._('SCM Type not supported yet by scmhook').'</div>';
					break;
				}
			}
			echo '</div><p />';
		} else {
			echo '<p class="information">'._('No hooks available').'</p>';
		}
	}

	function getStatusDeploy($group_id) {
		$res = db_query_params('SELECT need_update FROM plugin_scmhook WHERE id_group = $1', array($group_id));
		if (!$res)
			return 1;

		$row = db_fetch_array($res);
		return $row['need_update'];
	}

	function getAvailableHooks($group_id) {
		$listScm = $this->getListLibraryScm();
		$group = group_get_object($group_id);
		for ($i = 0; $i < count($listScm); $i++) {
			if ($group->usesPlugin($listScm[$i])) {
				return $this->getListLibraryHook($listScm[$i]);
			}
		}
		return array();
	}

	function getEnabledHooks($group_id) {
		$res = db_query_params('SELECT hooks FROM plugin_scmhook WHERE id_group = $1', array($group_id));
		if (!$res)
			return false;

		$row = db_fetch_array($res);
		if (count($row)) {
			return explode('|', $row['hooks']);
		}
		return array();
	}

	function getListLibraryScm() {
		return array_values(array_diff(scandir(dirname(__FILE__).'/../library/'), Array('.', '..', '.svn')));
	}

	function getListLibraryHook($scm) {
		$listHooks = array_values(array_diff(scandir(dirname(__FILE__).'/../library/'.$scm), array('.', '..', '.svn', 'skel', 'hooks', 'depends', 'cronjobs')));
		$validHooks = array();
		foreach ($listHooks as $hook) {
			if (!stristr($hook,'~')) {
				include_once dirname(__FILE__).'/../library/'.$scm.'/'.$hook;
				$hookClassName = preg_replace('/.class.php/','', $hook);
				$hookObject = new $hookClassName;
				$validHooks[] = $hookObject;
			}
		}
		return $validHooks;
	}

	function artifact_extra_detail($params) {
		$hooksAvailable = $this->getAvailableHooks($params['group_id']);
		$hooksEnabled = $this->getEnabledHooks($params['group_id']);
		foreach ($hooksAvailable as $hookAvailable) {
			if (in_array($hookAvailable->getClassname(), $hooksEnabled)) {
				if (method_exists($hookAvailable,'artifact_extra_detail')) {
					$hookAvailable->artifact_extra_detail($params);
				}
			}
		}
	}

	function task_extra_detail($params) {
		$hooksAvailable = $this->getAvailableHooks($params['group_id']);
		$hooksEnabled = $this->getEnabledHooks($params['group_id']);
		foreach ($hooksAvailable as $hookAvailable) {
			if (in_array($hookAvailable->getClassname(), $hooksEnabled)) {
				if (method_exists($hookAvailable,'task_extra_detail')) {
					$hookAvailable->task_extra_detail($params);
				}
			}
		}
	}

	/**
	 * override default groupisactivecheckboxpost function for init value in db
	 */
	function groupisactivecheckboxpost(&$params) {
		// this code actually activates/deactivates the plugin after the form was submitted in the project edit public info page
		$group = group_get_object($params['group']);
		$flag = strtolower('use_'.$this->name);
		if (getIntFromRequest($flag) == 1) {
			$group->setPluginUse($this->name);
			$this->add($group->getID());
		} else {
			$group->setPluginUse($this->name, false);
			$this->remove($group->getID());
		}
		return true;
	}

	function displayScmSvnHook($hooksAvailable, $statusDeploy, $hooksEnabled) {
		global $HTML;
		$hooksPreCommit = array();
		$hooksPreRevPropChange = array();
		$hooksPostCommit = array();
		foreach ($hooksAvailable as $hook) {
			switch ($hook->getHookType()) {
				case "pre-commit": {
					$hooksPreCommit[] = $hook;
					break;
				}
				case "pre-revprop-change": {
					$hooksPreRevPropChange[] = $hook;
					break;
				}
				case "post-commit": {
					$hooksPostCommit[] = $hook;
					break;
				}
				default: {
					//byebye hook.... we do not know you...
					break;
				}
			}
		}
		if (count($hooksPreCommit)) {
			echo '<h3>'._('pre-commit Hooks').'</h3>';
			$tabletop = array('', _('Hook Name'), _('Description'));
			$classth = array('unsortable', '', '');
			echo $HTML->listTableTop($tabletop, false, 'sortable_scmhook_precommit', 'sortable', $classth);
			foreach ($hooksPreCommit as $hookPreCommit) {
				$isdisabled = 0;
				if (! empty($hookPreCommit->onlyGlobalAdmin) && ! Permission::isGlobalAdmin()) {
					echo '<tr style="display: none;" ><td>';
				}
				else {
					echo '<tr><td>';
				}
				echo '<input type="checkbox" ';
				echo 'name="'.$hookPreCommit->getLabel().'_'.$hookPreCommit->getClassname().'" ';
				if (in_array($hookPreCommit->getClassname(), $hooksEnabled))
					echo ' checked="checked"';

				if ($statusDeploy) {
					$isdisabled = 1;
					echo ' disabled="disabled"';
				}
				if (!$isdisabled && !$hookPreCommit->isAvailable())
					echo ' disabled="disabled"';

				echo ' />';
				if (in_array($hookPreCommit->getClassname(), $hooksEnabled) && $statusDeploy) {
					echo '<input type="hidden" ';
					echo 'name="'.$hookPreCommit->getLabel().'_'.$hookPreCommit->getClassname().'" ';
					echo 'value="on" />';
				}
				echo '</td><td';
				if (!$hookPreCommit->isAvailable())
					echo ' class="tabtitle-w" title="'.$hookPreCommit->getDisabledMessage().'"';

				echo ' >';
				echo $hookPreCommit->getName();
				echo '</td><td>';
				echo $hookPreCommit->getDescription();
				echo '</td></tr>';
			}
			echo $HTML->listTableBottom();
		}
		if (count($hooksPreRevPropChange)) {
			echo '<h3>'._('pre-revprop-change Hooks').'</h3>';
			$tabletop = array('', _('Hook Name'), _('Description'));
			$classth = array('unsortable', '', '');
			echo $HTML->listTableTop($tabletop, false, 'sortable_scmhook_precommit', 'sortable', $classth);
			foreach ($hooksPreRevPropChange as $hook) {
				$isdisabled = 0;
				if (! empty($hook->onlyGlobalAdmin) && ! Permission::isGlobalAdmin()) {
					echo '<tr style="display: none;" ><td>';
				}
				else {
					echo '<tr><td>';
				}
				echo '<input type="checkbox" ';
				echo 'name="'.$hook->getLabel().'_'.$hook->getClassname().'" ';
				if (in_array($hook->getClassname(), $hooksEnabled))
					echo ' checked="checked"';

				if ($statusDeploy) {
					$isdisabled = 1;
					echo ' disabled="disabled"';
				}
				if (!$isdisabled && !$hook->isAvailable())
					echo ' disabled="disabled"';

				echo ' />';
				if (in_array($hook->getClassname(), $hooksEnabled) && $statusDeploy) {
					echo '<input type="hidden" ';
					echo 'name="'.$hook->getLabel().'_'.$hook->getClassname().'" ';
					echo 'value="on" />';
				}
				echo '</td><td';
				if (!$hook->isAvailable())
					echo ' class="tabtitle-w" title="'.$hook->getDisabledMessage().'"';

				echo ' >';
				echo $hook->getName();
				echo '</td><td>';
				echo $hook->getDescription();
				echo '</td></tr>';
			}
			echo $HTML->listTableBottom();
		}
		if (count($hooksPostCommit)) {
			echo '<h3>'._('post-commit Hooks').'</h3>';
			$tabletop = array('', _('Hook Name'), _('Description'));
			$classth = array('unsortable', '', '');
			echo $HTML->listTableTop($tabletop, false, 'sortable_scmhook_postcommit', 'sortable', $classth);
			foreach ($hooksPostCommit as $hookPostCommit) {
				$isdisabled = 0;
				if (! empty($hookPostCommit->onlyGlobalAdmin) && ! Permission::isGlobalAdmin()) {
					echo '<tr style="display: none;" ><td>';
				}
				else {
					echo '<tr><td>';
				}
				echo '<input type="checkbox" ';
				echo 'name="'.$hookPostCommit->getLabel().'_'.$hookPostCommit->getClassname().'" ';
				if (in_array($hookPostCommit->getClassname(), $hooksEnabled))
					echo ' checked="checked"';

				if ($statusDeploy) {
					$isdisabled = 1;
					echo ' disabled="disabled"';
				}

				if (!$isdisabled && !$hookPostCommit->isAvailable())
					echo ' disabled="disabled"';

				echo ' />';
				if (in_array($hookPostCommit->getClassname(), $hooksEnabled) && $statusDeploy) {
					echo '<input type="hidden" ';
					echo 'name="'.$hookPostCommit->getLabel().'_'.$hookPostCommit->getClassname().'" ';
					echo 'value="on" />';
				}
				echo '</td><td';
				if (!$hookPostCommit->isAvailable())
					echo ' class="tabtitle-w" title="'.$hookPostCommit->getDisabledMessage().'"';

				echo ' >';
				echo $hookPostCommit->getName();
				echo '</td><td>';
				echo $hookPostCommit->getDescription();
				echo '</td></tr>';
			}
			echo $HTML->listTableBottom();
		}
	}

	function displayScmHgHook($hooksAvailable, $statusDeploy, $hooksEnabled) {
		global $HTML;
		$hooksServePushPullBundle = array();
		foreach ($hooksAvailable as $hook) {
			switch ($hook->getHookType()) {
				case "serve-push-pull-bundle": {
					$hooksServePushPullBundle[] = $hook;
					break;
				}
				default: {
					//byebye hook.... we do not know you...
					break;
				}
			}
		}
		if (count($hooksServePushPullBundle)) {
			echo '<h3>'._('serve-push-pull-bundle Hooks').'</h3>';
			$tabletop = array('', _('Hook Name'), _('Description'));
			$classth = array('unsortable', '', '');
			echo $HTML->listTableTop($tabletop, false, 'sortable_scmhook_serve-push-pull-bundle', 'sortable', $classth);
			foreach ($hooksServePushPullBundle as $hookServePushPullBundle) {
				$isdisabled = 0;
				if (! empty($hookServePushPullBundle->onlyGlobalAdmin) && ! Permission::isGlobalAdmin()) {
					echo '<tr style="display: none;" ><td>';
				}
				else {
					echo '<tr><td>';
				}
				echo '<input type="checkbox" ';
				echo 'name="'.$hookServePushPullBundle->getLabel().'_'.$hookServePushPullBundle->getClassname().'" ';
				if (in_array($hookServePushPullBundle->getClassname(), $hooksEnabled))
					echo ' checked="checked"';

				if ($statusDeploy) {
					$isdisabled = 1;
					echo ' disabled="disabled"';
				}

				if (!$isdisabled && !$hookServePushPullBundle->isAvailable())
					echo ' disabled="disabled"';

				echo ' />';
				if (in_array($hookServePushPullBundle->getClassname(), $hooksEnabled) && $statusDeploy) {
					echo '<input type="hidden" ';
					echo 'name="'.$hookServePushPullBundle->getLabel().'_'.$hookServePushPullBundle->getClassname().'" ';
					echo 'value="on" />';
				}

				echo '</td><td';
				if (!$hookServePushPullBundle->isAvailable())
					echo ' class="tabtitle-w" title="'.$hookServePushPullBundle->getDisabledMessage().'"';

				echo ' >';
				echo $hookServePushPullBundle->getName();
				echo '</td><td>';
				echo $hookServePushPullBundle->getDescription();
				echo '</td></tr>';
			}
			echo $HTML->listTableBottom();
		}
	}
	function displayScmGitHook($hooksAvailable, $statusDeploy, $hooksEnabled) {
		global $HTML;
		$hooksPostReceive = array();
		foreach ($hooksAvailable as $hook) {
			switch ($hook->getHookType()) {
				case "post-receive": {
					$hooksPostReceive[] = $hook;
					break;
				}
				default: {
					//byebye hook.... we do not know you...
					break;
				}
			}
		}
		if (count($hooksPostReceive)) {
			echo '<h3>'._('post-receive Hooks').'</h3>';
			$tabletop = array('', _('Hook Name'), _('Description'));
			$classth = array('unsortable', '', '');
			echo $HTML->listTableTop($tabletop, false, 'sortable_scmhook_post-receive', 'sortable', $classth);
			foreach ($hooksPostReceive as $hookPostReceive) {
				$isdisabled = 0;
				if (! empty($hookPostReceive->onlyGlobalAdmin) && ! Permission::isGlobalAdmin()) {
					echo '<tr style="display: none;" ><td>';
				}
				else {
					echo '<tr><td>';
				}
				echo '<input type="checkbox" ';
				echo 'name="'.$hookPostReceive->getLabel().'_'.$hookPostReceive->getClassname().'" ';
				if (in_array($hookPostReceive->getClassname(), $hooksEnabled))
					echo ' checked="checked"';

				if ($statusDeploy) {
					$isdisabled = 1;
					echo ' disabled="disabled"';
				}

				if (!$isdisabled && !$hookPostReceive->isAvailable())
					echo ' disabled="disabled"';

				echo ' />';
				if (in_array($hookPostReceive->getClassname(), $hooksEnabled) && $statusDeploy) {
					echo '<input type="hidden" ';
					echo 'name="'.$hookPostReceive->getLabel().'_'.$hookPostReceive->getClassname().'" ';
					echo 'value="on" />';
				}

				echo '</td><td';
				if (!$hookPostReceive->isAvailable())
					echo ' class="tabtitle-w" title="'.$hookPostReceive->getDisabledMessage().'"';

				echo ' >';
				echo $hookPostReceive->getName();
				echo '</td><td>';
				echo $hookPostReceive->getDescription();
				echo '</td></tr>';
			}
			echo $HTML->listTableBottom();
		}
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
