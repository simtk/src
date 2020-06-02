<?php

/**
 *
 * popupmenuHandler.php
 *
 * Contains functions to generate project menu and dropdowns.
 * 
 * Copyright 2005-2019, SimTK Team
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
 
// Generate the dropdown menu string for Hamburger.
function genDropdownMenu($sectionName, $sectionUrl, $groupId) {

	// Cannot have space in the id. Strip space.
	$theMenuId = str_replace(" ", "", $sectionName);
	$theMenuId = str_replace(".", "", $theMenuId);
	$theMenuId = str_replace("/", "", $theMenuId);

	// Get dropdown menu items.
	$strMenuItems = getDropdownMenuItems($sectionName, $sectionUrl, $groupId);
	if ($strMenuItems == "") {
		// No dropdown menu items.
		return "<li class='intend'><a href='" . $sectionUrl . "'>" . $sectionName . "</a></li>";
	}

	// Start the dropdown menus for the specified section.
	$strBegin = beginDropdownMenuString($theMenuId, $sectionName);
	// End the menu item.
	$strEnd = endDropdownMenuString($theMenuId);

	// Dropdown menu string.
	$strDropdownMenu = $strBegin . $strMenuItems . $strEnd;

	return $strDropdownMenu;
}

// Generate the popup menu string.
function genPopupMenu($sectionName, $groupId) {

	// Cannot have space in the id. Strip space.
	$theMenuId = str_replace(" ", "", $sectionName);
	$theMenuId = str_replace(".", "", $theMenuId);
	$theMenuId = str_replace("/", "", $theMenuId);

	// jQuery hover handling code.
	$strBegin = genMenuHoverJquery($theMenuId);

	// Get popup menu items.
	$strMenuItems = getPopupMenuItems($sectionName, $groupId);
	if ($strMenuItems == "") {
		// No popup menu items.
		return $strBegin;
	}

	// Start the popup menus for the specified section.
	$strBegin .= beginPopupMenuString($theMenuId);
	// End the menu item.
	$strEnd = endPopupMenuString($theMenuId);

	// Popup menu string.
	$strPopupMenu = $strBegin . $strMenuItems . $strEnd;

	return $strPopupMenu;
}

// Get popup menu items.
function getPopupMenuItems($sectionName, $groupId) {

	$strPopupMenuItems = "";

	// Get popup menu items for the given section of the group.
	$menuTitles = array();
	$menuUrls = array();
	$menuDispNames = array();
	sectionPopupMenuItems($sectionName, $groupId, $menuTitles, $menuUrls, $menuDispNames);

	// Generate string with popup/sub-menu items.
	foreach ($menuTitles as $key=>$val) {
		// Try fetching submenu.
		$theSubMenuName = $menuTitles[$key];
		$strSubMenu = genPopupSubMenu($sectionName, $theSubMenuName, $groupId, $menuDispNames);
		if ($strSubMenu != "") {
			// This menu item has a submenu.
			$strPopupMenuItems .= $strSubMenu;
		}
		else {
			$theDispName = $theSubMenuName;
			if (isset($menuDispNames[$theSubMenuName])) {
				$theDispName = $menuDispNames[$theSubMenuName];
			}
			// Regular popup menu item.
			$strPopupMenuItems .= '<li >' .
				'<a href="' . $menuUrls[$key] . '">' . 
				$theDispName . '</a></li>';
		}
	}

	return $strPopupMenuItems;
}


// Generate dropdown submenu given the submenu title..
function genDropdownSubMenu($sectionName, $theSubMenuTitle, $groupId, $menuDispNames) {

	// Cannot have space in the id. Strip space.
	$theSubMenuId = str_replace(" ", "", $theSubMenuTitle);
	$theSubMenuId = str_replace(".", "", $theSubMenuId);
	$theSubMenuId = str_replace("/", "", $theSubMenuId);

	// Get submenu menu items.
	$strSubMenuItems = getDropdownSubMenuItems($sectionName, $theSubMenuTitle, $groupId);
	if ($strSubMenuItems == "") {
		// No submenu items.
		return "";
	}

	// Start the submenu for the specified section.
	$strBegin = beginDropdownSubMenuString($theSubMenuTitle, $theSubMenuId, $menuDispNames);
	// End the submenu item.
	$strEnd = endDropdownSubMenuString($theSubMenuId);

	// Submenu string.
	$strSubMenu = $strBegin . $strSubMenuItems . $strEnd;

	return $strSubMenu;
}

// Generate popup submenu given the submenu title..
function genPopupSubMenu($sectionName, $theSubMenuTitle, $groupId, $menuDispNames) {

	// Cannot have space in the id. Strip space.
	$theSubMenuId = str_replace(" ", "", $theSubMenuTitle);
	$theSubMenuId = str_replace(".", "", $theSubMenuId);
	$theSubMenuId = str_replace("/", "", $theSubMenuId);

	// Get submenu menu items.
	$strSubMenuItems = getPopupSubMenuItems($sectionName, $theSubMenuTitle, $groupId);
	if ($strSubMenuItems == "") {
		// No submenu items.
		return "";
	}

	// jQuery hover handling code.
	$strBegin = genMenuHoverJquery($theSubMenuId, true);
	// Start the submenu for the specified section.
	$strBegin .= beginPopupSubMenuString($theSubMenuTitle, $theSubMenuId, $menuDispNames);
	// End the submenu item.
	$strEnd = endPopupSubMenuString($theSubMenuId);

	// Submenu string.
	$strSubMenu = $strBegin . $strSubMenuItems . $strEnd;

	return $strSubMenu;
}


// Get dropdown submenu itemes given the submenu title.
function getDropdownSubMenuItems($sectionName, $theSubMenuTitle, $groupId) {

	$strSubMenuItems = "";

	// Get submenu items for the given section of the group.
	// (Use same data from popup submenu items.)
	$submenuTitles = array();
	$submenuUrls = array();
	$submenuDispNames = array();
	sectionSubMenuItems($sectionName, $theSubMenuTitle, $groupId, 
		$submenuTitles, $submenuUrls, $submenuDispNames);

	// Generate string with submenu items.
	foreach ($submenuTitles as $key=>$val) {
		$theDispName = $submenuTitles[$key];
		if (isset($submenuDispNames[$theDispName])) {
			$theDispName = $submenuDispNames[$theDispName];
		}
		$strSubMenuItems .= '<li class="intend">' .
			'<a href="' . $submenuUrls[$key] . '" tabindex="-1" >' . 
			$theDispName .
			'</a></li>';
	}

	return $strSubMenuItems;
}

// Get popup submenu itemes given the submenu title.
function getPopupSubMenuItems($sectionName, $theSubMenuTitle, $groupId) {

	$strSubMenuItems = "";

	// Get submenu items for the given section of the group.
	$submenuTitles = array();
	$submenuUrls = array();
	$submenuDispNames = array();
	sectionSubMenuItems($sectionName, $theSubMenuTitle, $groupId, 
		$submenuTitles, $submenuUrls, $submenuDispNames);

	// Generate string with submenu items.
	foreach ($submenuTitles as $key=>$val) {
		$theDispName = $submenuTitles[$key];
		if (isset($submenuDispNames[$theDispName])) {
			$theDispName = $submenuDispNames[$theDispName];
		}
		$strSubMenuItems .= '<li >' .
			'<a href="' . $submenuUrls[$key] . '" tabindex="-1" >' . 
			$theDispName .
			'</a></li>';
	}

	return $strSubMenuItems;
}

// Get dropdown menu items.
function getDropdownMenuItems($sectionName, $sectionUrl, $groupId) {

	$strDropdownMenuItems = "";

	// Get dropdown menu items for the given section of the group.
	// (Use same data from popup menu items.)
	$menuTitles = array();
	$menuUrls = array();
	$menuDispNames = array();
	sectionPopupMenuItems($sectionName, $groupId, $menuTitles, $menuUrls, $menuDispNames);

	// Generate string with popup/sub-menu items.
	foreach ($menuTitles as $key=>$val) {
		// Try fetching submenu.
		$strSubMenu = genDropdownSubMenu($sectionName, $menuTitles[$key], $groupId, $menuDispNames);
		if ($strSubMenu != "") {
			// This menu item has a submenu.
			$strDropdownMenuItems .= $strSubMenu;
		}
		else {
			// Regular dropdown menu item.
			$theDispName = $menuTitles[$key];
			if (isset($menuDispNames[$theDispName])) {
				$theDispName = $menuDispNames[$theDispName];
			}
			$strDropdownMenuItems .= '<li>' .
				'<a style="padding-left:20px;" class="intend" href="' . $menuUrls[$key] . '">' . 
				$theDispName . '</a></li>';
		}
	}

	return $strDropdownMenuItems;
}


// Start dropdown menu header.
function beginDropdownMenuString($theMenuId, $sectionName) {
	$strHeader = '<li class="intend">';
	$strHeader .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" ' .
		'role="button" aria-expanded="false">';
	$strHeader .= $sectionName;
	$strHeader .= '<span class="caret"></span></a>';
	$strHeader .= '<ul id="dropdown_' . $theMenuId . '" class="dropdown-menu" role="menu">';

	return $strHeader;
}

// End the dropdown menu.
function endDropdownMenuString($theMenuId) {
	$strEnd = '</ul>';
	$strEnd .= '</li>';

	return $strEnd;
}

// Start popup menu header.
function beginPopupMenuString($theMenuId) {

	$strHeader = '<li class="dropdown" id="' . $theMenuId . '-dropdown-menu" >';
        $strHeader .= '<ul ' .
		'style="left:-100px;border-top-left-radius:0px;border-top-right-radius:0px;" ' .
                'class="dropdown-menu" ' .
                'style="min-width:100px;" ' .
                'role="menu">';

	return $strHeader;
}

// End the popup menu.
function endPopupMenuString($theMenuId) {
	$strEnd = '</ul>';
	$strEnd .= '</li>';

	return $strEnd;
}

// Start dropdown submenu header.
function beginDropdownSubMenuString($theSubMenuTitle, $theSubMenuId, $menuDispNames) {

	$theDispName = $theSubMenuTitle;
	if (isset($menuDispNames[$theDispName])) {
		$theDispName = $menuDispNames[$theDispName];
	}
	$strHeader = "<li class='dropdown-header'>" . $theDispName . "</li>";

	return $strHeader;
}

// End the dropdown submenu.
function endDropdownSubMenuString($theMenuId) {
	$strEnd = "";

	return $strEnd;
}

// Start popup submenu header.
function beginPopupSubMenuString($theSubMenuTitle, $theSubMenuId, $menuDispNames) {

	$theDispName = $theSubMenuTitle;
	if (isset($menuDispNames[$theSubMenuTitle])) {
		$theDispName = $menuDispNames[$theSubMenuTitle];
	}
	$strHeader = '<li class="dropdown-submenu" id="' . $theSubMenuId . '-dropdown-submenu" >';
	$strHeader .= '<a href="#" data-toggle="dropdown">' . $theDispName . '</a>';
	$strHeader .= '<ul class="dropdown-menu" id="' . $theSubMenuId . '-dropdown-menu" >';

	return $strHeader;
}

// End the popup submenu.
function endPopupSubMenuString($theMenuId) {
	$strEnd = '</ul>';
	$strEnd .= '</li>';

	return $strEnd;
}

// Generate the jQuery hover handling code.
function genMenuHoverJquery($theMenuId, $isSubMenu = false) {

	$theHoverClass = '.dropdown';
	$theHoverSrc = $theMenuId;
	$theDropDown = $theMenuId . '-dropdown-menu';
	if ($isSubMenu === true) {
		$theHoverClass = '.dropdown-submenu';
		$theHoverSrc .= '-dropdown-submenu';
		$theDropDown = $theHoverSrc;
	}

	// NOTE: Use mouseenter()/mouseleave() (instead of hover())
	// to check on target element type of $theHoverSrc.
	// If the target element is a DIV, the mouse is leaving 
	// the menu bar. Pop down the menu.
	$strMenuHover = 
	'<script>' .
       		'$(function() {' .
/*
			'$("#' . $theHoverSrc . '").hover(' .
				'function() {' .
					'$("' . $theHoverClass . '").removeClass("open");' .
					'$("#' . $theDropDown . '").addClass("open");' .
				'}' .
			');' .
*/
			'$("#' . $theHoverSrc . '").mouseenter(function() {' .
					'$("' . $theHoverClass . '").removeClass("open");' .
					'$("#' . $theDropDown . '").addClass("open");' .
			'});' .
			'$("#' . $theHoverSrc . '").mouseleave(function(e) {' .
				'if ($(e.relatedTarget).is("div")) {' .
					'$("' . $theHoverClass . '").removeClass("open");' .
				'}' .
			'});' .
			'$("#' . $theDropDown . '").mouseleave(function() {' .
				'$("#' . $theDropDown . '").removeClass("open");' .
			'});' .
		'});' .
	'</script>';

	return $strMenuHover;
}

?>
