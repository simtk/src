<?php
 
/**
 *
 * XmlObject.class.php
 * 
 * File to generate base XML.
 * 
 * Copyright 2005-2016, SimTK Team
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
 
class XmlObject {
	
	function getXmlData() {
		$result = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$result .= "<data>";
		
		$result .= "<session>";
		
		$user = session_get_user();
		if (!empty($user)) {
			$result .= "<user>";
			$result .= "<name>".escapeOnce( $user->getRealName() )."</name>";
			$result .= "<login>".escapeOnce( $user->getUnixName() )."</login>";

			if (!empty($GLOBALS['group_id'])) {
				$group = group_get_object($GLOBALS['group_id']);
				if (is_object($group) && !empty($group)) {
					$perm = $group->getPermission($user);
					if (is_object($perm)) {
						if ($perm->isMember()) {
							$result .= "<is_member>true</is_member>";
						}
						if ($perm->isAdmin()) {
							$result .= "<is_admin>true</is_admin>";
						}
						if ($perm->isForumAdmin()) {
							$result .= "<is_forum_admin>true</is_forum_admin>";
						}
						if ($perm->isDocEditor()) {
							$result .= "<is_doc_admin>true</is_doc_admin>";
						}
						if ($perm->isPubsEditor()) {
							$result .= "<is_pubs_admin>true</is_pubs_admin>";
						}
						if ($perm->isDashAdmin()) {
							$result .= "<is_dash_admin>true</is_dash_admin>";
						}
						if ($perm->isReleaseTechnician()) {
							$result .= "<is_download_admin>true</is_download_admin>";
						}
						if ($perm->isArtifactAdmin()) {
							$result .= "<is_tracker_admin>true</is_tracker_admin>";
						}
						if ($perm->isPMAdmin()) {
							$result .= "<is_task_admin>true</is_task_admin>";
						}
					}
				}
			}
			$result .= "</user>";
		}
		
		$result .= "<section>";
		
		if (!empty($GLOBALS['toptab'])) {
			$result .= "<id>".$GLOBALS['toptab']."</id>";
		}

		if (!empty($GLOBALS['forum_id'])) {
			$result .= "<forum_id>".$GLOBALS['forum_id']."</forum_id>";
		}
		
		if (!empty($GLOBALS['atid'])) {
			$result .= "<tracker_id>".$GLOBALS['atid']."</tracker_id>";
		}
		
		if (!empty($GLOBALS['group_project_id'])) {
			$result .= "<taskgroup_id>".$GLOBALS['group_project_id']."</taskgroup_id>";
		}

		$result .= "</section>";
		
		if (!empty($GLOBALS['feedback'])) {
			$result .= "<feedback>".$GLOBALS['feedback']."</feedback>";
		}
		
		if (!empty($GLOBALS['REQUEST_URI'])) {
			$result .= "<current_uri>".urlencode( $GLOBALS['REQUEST_URI'] )."</current_uri>";
		}

		if (!empty($_SERVER['QUERY_STRING'])) {
			$result .= "<query_string>".urlencode( $_SERVER['QUERY_STRING'] )."</query_string>";
		}

		if (!empty($GLOBALS['sys_name']))
		{
			$result .= "<sys_name>".$GLOBALS['sys_name']."</sys_name>";
		}

		$result .= "</session>";
		
		$result .= $this->getXmlDataInternal();
		
		$result .= "</data>";
		#echo $result;exit;
		return $result;
	}

	
	function getXmlDataInternal() {}
	

	function getIlikeCondition($fieldName, $wordList, $operator = "AND") {
		if ( count( $wordList) == 0 )
			return $fieldName . " ILIKE '%'";
		return $fieldName." ILIKE '%" . implode("%' ".$operator." ".$fieldName." ILIKE '%", $wordList) ."%'";
	}
	

	function getList($string) {
		if (!$string) {
			return array();
		}
		
		$string = htmlspecialchars($string);
		$string = strtr($string, array('%' => ''));
		$string = preg_replace("/[ \t]+/", ' ', $string);
		if(strlen($string) < 1) {
			return array();
		}
		
		return explode(' ', quotemeta($string));	
	}
	

}

?>
