<?php

/**
 *
 * news plugin Class - For Initialization in FusionForge
 * 
 * Copyright 2005-2025, SimTK Team
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

class simtk_newsPlugin extends Plugin {
	var $text;

	public function __construct($id=0) {
		parent::__construct($id) ;
		$this->name = "simtk_news";
		$this->text = "News"; // To show in the tabs, use...
		$this->_addHook("user_personal_links");//to make a link to the user's personal part of the plugin
		$this->_addHook("usermenu");
		$this->_addHook("groupmenu");	// To put into the project tabs
		$this->_addHook("groupisactivecheckbox"); // The "use ..." checkbox in editgroupinfo
		$this->_addHook("groupisactivecheckboxpost"); //
		$this->_addHook("userisactivecheckbox"); // The "use ..." checkbox in user account
		$this->_addHook("userisactivecheckboxpost"); //
		$this->_addHook("project_admin_plugins"); // to show up in the admin page fro group
	}

	function CallHook ($hookname, &$params) {
		global $use_simtk_newsplugin,$G_SESSION,$HTML;
		if ($hookname == "usermenu") {
			$text = $this->text; // this is what shows in the tab
			if (is_object($G_SESSION) && $G_SESSION->usesPlugin("simtk_news")) {
				$param = '?type=user&id=' . $G_SESSION->getId() . "&pluginname=" . $this->name; // we indicate the part we're calling is the user one
				echo $HTML->PrintSubMenu (array ($text),
						  array ('/plugins/simtk_news/index.php' . $param ));

			}
		} elseif ($hookname == "groupmenu") {
			$group_id=$params['group'];
			$project = group_get_object($group_id);
			if (!$project || !is_object($project)) {
				return;
			}
			if ($project->isError()) {
				return;
			}
			if (!$project->isProject()) {
				return;
			}
			if ( $project->usesPlugin ( $this->name ) ) {
				$params['TITLES'][]=$this->text;
				$params['DIRS'][]=util_make_url ('/plugins/simtk_news/index.php?group_id=' . $group_id . "&pluginname=" . $this->name) ; // we indicate the part we're calling is the project one
			} else {
			//	$params['TITLES'][]=$this->text." is [Off]";
		//		$params['DIRS'][]='';
			}
			(($params['toptab'] == $this->name) ? $params['selected']=(count($params['TITLES'])-1) : '' );
		} elseif ($hookname == "groupisactivecheckbox") {
			//Check if the group is active
			// this code creates the checkbox in the project edit public info page to activate/deactivate the plugin
			$group_id=$params['group'];
			$group = group_get_object($group_id);
			echo "<tr>";
			echo "<td>";
			echo ' <input type="checkbox" name="use_simtk_newsplugin" value="1" ';
			// checked or unchecked?
			if ( $group->usesPlugin ( $this->name ) ) {
				echo "checked";
			}
			echo " /><br/>";
			echo "</td>";
			echo "<td>";
			echo "<strong>Use ".$this->text." Plugin</strong>";
			echo "</td>";
			echo "</tr>";
		} elseif ($hookname == "groupisactivecheckboxpost") {
			// this code actually activates/deactivates the plugin after the form was submitted in the project edit public info page
			$group_id=$params['group'];
			$group = group_get_object($group_id);
			$use_simtk_newsplugin = getStringFromRequest('use_simtk_newsplugin');
			if ( $use_simtk_newsplugin == 1 ) {
				$group->setPluginUse ( $this->name );
			} else {
				$group->setPluginUse ( $this->name, false );
			}
		} elseif ($hookname == "user_personal_links") {
			// this displays the link in the user's profile page to it's personal simtk_news (if you want other sto access it, youll have to change the permissions in the index.php
			$userid = $params['user_id'];
			$user = user_get_object($userid);
			$text = $params['text'];
			//check if the user has the plugin activated
			if ($user->usesPlugin($this->name)) {
				echo '	<p>' ;
				echo util_make_link ("/plugins/simtk_news/index.php?id=$userid&type=user&pluginname=".$this->name,
						     _('View Personal News')
					);
				echo '</p>';
			}
		} elseif ($hookname == "project_admin_plugins") {
			// this displays the link in the project admin options page to it's simtk_news administration
			$group_id = $params['group_id'];
			$group = group_get_object($group_id);
			if ( $group->usesPlugin ( $this->name ) ) {
				echo '<p>'.util_make_link ("/plugins/simtk_news/admin/index.php?group_id=".$group->getID().'&type=admin&pluginname='.$this->name,
						     _('News Admin')).'</p>' ;
			}
		}
		elseif ($hookname == "blahblahblah") {
			// ...
		}
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
