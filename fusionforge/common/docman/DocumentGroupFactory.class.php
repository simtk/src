<?php
/**
 * DocumentGroupFactory.class.php
 *
 * FusionForge document manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002-2003, Tim Perdue/GForge, LLC
 * Copyright 2009, Roland Mas
 * Copyright 2010, Franck Villaume - Capgemini
 * Copyright 2012-2014, Franck Villaume - TrivialDev
 * Copyright (C) 2012 Alain Peyrat - Alcatel-Lucent
 * Copyright 2016-2025, SimTK Team
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

/*
 Document Groups
*/

require_once $gfcommon.'include/FFError.class.php';

class DocumentGroupFactory extends FFError {
	/**
	 * This variable holds the document groups.
	 */
	var $flat_groups;

	/**
	 * This variable holds the document groups for reading them in nested form.
	 */
	var $nested_groups;

	/**
	 * The Group object.
	 */
	var $Group;

	/**
	 * Constructor.
	 *
	 * @param	$Group
	 * @return	\DocumentGroupFactory
	 */
	function __construct(&$Group) {
		parent::__construct();

		if (!$Group || !is_object($Group)) {
			$this->setError(_('No Valid Group Object'));
			return;
		}
		if ($Group->isError()) {
			$this->setError('DocumentGroupFactory::'.' '.$Group->getErrorMessage());
			return;
		}
		$this->Group =& $Group;
	}

	/**
	 * getNested - Return an array of DocumentGroup objects arranged for nested views.
	 *
	 * @param	int	$stateid	The stateid of DocumentGroup : default is public (1).
	 * @return	array	The array of DocumentGroup.
	 */
	function &getNested($stateid = 1) {
		if ($this->nested_groups) {
			return $this->nested_groups;
		}

		$this->flat_groups = array();

		$result = db_query_params('SELECT * FROM doc_groups WHERE group_id=$1 AND stateid=$2 ORDER BY groupname ASC',
						array($this->Group->getID(), $stateid));

		if (!$result) {
			$this->setError(_('No Documents Folder Found').' '.db_error());
			return false;
		} else {
			while ($arr = db_fetch_array($result)) {
				$this->flat_groups[] = new DocumentGroup($this->Group, $arr);
			}
		}

		// Build the nested array
		$count = count($this->flat_groups);
		for ($i=0; $i < $count; $i++) {
			$this->nested_groups["".$this->flat_groups[$i]->getParentID()][] =& $this->flat_groups[$i];
		}
		return $this->nested_groups;
	}

	/**
	 * getDocumentGroups - Return an array of DocumentGroup objects.
	 *
	 * @param	int	$stateid	The stateid of DocumentGroups : default is public (1).
	 * @return	array	The array of DocumentGroup.
	 */
	function &getDocumentGroups($stateid = 1) {
		if ($this->flat_groups) {
			return $this->flat_groups;
		}

		$this->flat_groups = array();

		$result = db_query_params('SELECT * FROM doc_groups WHERE group_id=$1 AND stateid=$2 ORDER BY groupname ASC',
						array($this->Group->getID(), $stateid));
		$rows = db_numrows($result);

		if (!$result || $rows < 1) {
			$this->setError(_('No Documents Folder Found').' '.db_error());
		} else {
			while ($arr = db_fetch_array($result)) {
				$this->flat_groups[] = new DocumentGroup($this->Group, $arr);
			}
		}

		return $this->flat_groups;
	}
	
	function getDocGroups($stateid = 1) {

		$result = db_query_params('SELECT * FROM doc_groups WHERE group_id=$1 AND stateid=$2 ORDER BY groupname ASC',
						array($this->Group->getID(), $stateid));
		$rows = db_numrows($result);
		
		if (!$result || $rows < 1) {
			$this->setError(_('No Documents Folder Found').' '.db_error());
		}
		return $result;

	}
	
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
