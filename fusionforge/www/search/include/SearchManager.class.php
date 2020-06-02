<?php
/**
 * Search Engine
 *
 * Copyright 2004 (c) Guillaume Smet
 * Copyright 2011, Franck Villaume - Capgemini
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

require_once $gfwww.'search/include/engines/GroupSearchEngine.class.php';
require_once $gfwww.'search/include/engines/DocsGroupSearchEngine.class.php';
require_once $gfwww.'search/include/engines/ForumsGroupSearchEngine.class.php';
require_once $gfwww.'search/include/engines/FrsGroupSearchEngine.class.php';
require_once $gfwww.'search/include/engines/NewsGroupSearchEngine.class.php';
require_once $gfwww.'search/include/engines/TasksGroupSearchEngine.class.php';
require_once $gfwww.'search/include/engines/TrackersGroupSearchEngine.class.php';

require_once $gfwww.'search/include/engines/ArtifactSearchEngine.class.php';
require_once $gfwww.'search/include/engines/ForumSearchEngine.class.php';

function & getSearchManager() {
	if(!isset($GLOBALS['OBJ_SEARCH_MANAGER'])) {
		$GLOBALS['OBJ_SEARCH_MANAGER'] = new SearchManager();
	}
	return $GLOBALS['OBJ_SEARCH_MANAGER'];
}

class SearchManager {
	var $searchEngines = array();
	var $parameters = array();
	var $parametersValues = array();

	function __construct() {
		$this->loadSearchEngines();
		$this->loadParameters();
	}

	function setParametersValues($parametersValues) {
		for($i = 0, $max = count($this->parameters); $i < $max; $i++) {
			if(isset($parametersValues[$this->parameters[$i]])) {
				$this->parametersValues[$this->parameters[$i]] = (int) $parametersValues[$this->parameters[$i]];
			}
		}
	}

	function getParameters() {
		return $this->parametersValues;
	}

	function addSearchEngine($type, &$searchEngine, $format = SEARCH__OUTPUT_HTML) {
		$this->searchEngines[$format][$type] =& $searchEngine;
	}

	function addParameter($parameterName) {
		if(!in_array($parameterName, $this->parameters)) {
			$this->parameters[] = $parameterName;
		}
	}

	function & getAvailableSearchEngines($format = SEARCH__OUTPUT_HTML) {
		$availableSearchEngines = array();
		if(isset($this->searchEngines[$format])) {
			foreach($this->searchEngines[$format] AS $type => $searchEngine) {
				if($searchEngine->isAvailable($this->parametersValues)) {
					$availableSearchEngines[] = $searchEngine;
				}
			}
		}
		return $availableSearchEngines;
	}

	function getSearchRenderer($typeOfSearch, $words, $offset, $exact, $format = SEARCH__OUTPUT_HTML) {
		if(isset($this->searchEngines[$format]) && isset($this->searchEngines[$format][$typeOfSearch])) {
			$searchEngine =& $this->searchEngines[$format][$typeOfSearch];
			if($searchEngine->isAvailable($this->parametersValues)) {
				return $searchEngine->getSearchRenderer($words, $offset, $exact, $this->parametersValues);
			}
		}
		return false;
	}

	function loadSearchEngines() {
		// Specific search engines
		//$this->addSearchEngine(SEARCH__TYPE_IS_ARTIFACT, new ArtifactSearchEngine());
		//$this->addSearchEngine(SEARCH__TYPE_IS_FORUM, new ForumSearchEngine());
		$holder_var_ArtifactSearchEngine = new ArtifactSearchEngine();
		$holder_var_ForumSearchEngine = new ForumSearchEngine();
		$this->addSearchEngine(SEARCH__TYPE_IS_ARTIFACT, $holder_var_ArtifactSearchEngine);
		$this->addSearchEngine(SEARCH__TYPE_IS_FORUM, $holder_var_ForumSearchEngine);

		// Project search engines
		//$this->addSearchEngine(SEARCH__TYPE_IS_FULL_PROJECT, new GroupSearchEngine(SEARCH__TYPE_IS_FULL_PROJECT, 'FullProjectHtmlSearchRenderer', _('Search the entire project')));
		//$this->addSearchEngine(SEARCH__TYPE_IS_TRACKERS, new TrackersGroupSearchEngine());
		//$this->addSearchEngine(SEARCH__TYPE_IS_FORUMS, new ForumsGroupSearchEngine());
		//$this->addSearchEngine(SEARCH__TYPE_IS_TASKS, new TasksGroupSearchEngine());
		//$this->addSearchEngine(SEARCH__TYPE_IS_FRS, new FrsGroupSearchEngine());
		//$this->addSearchEngine(SEARCH__TYPE_IS_DOCS, new DocsGroupSearchEngine());
		//$this->addSearchEngine(SEARCH__TYPE_IS_NEWS, new NewsGroupSearchEngine());
		$holder_var_GroupSearchEngineProject = new GroupSearchEngine(SEARCH__TYPE_IS_FULL_PROJECT, 'FullProjectHtmlSearchRenderer', _('Search the entire project'));
		$holder_var_TrackersGroupSearchEngine = new TrackersGroupSearchEngine();
		$holder_var_ForumsGroupSearchEngine = new ForumsGroupSearchEngine();
		$holder_var_TasksGroupSearchEngine = new TasksGroupSearchEngine();
		$holder_var_FrsGroupSearchEngine = new FrsGroupSearchEngine();
		$holder_var_DocsGroupSearchEngine = new DocsGroupSearchEngine();
		$holder_var_NewsGroupSearchEngine = new NewsGroupSearchEngine();
		$this->addSearchEngine(SEARCH__TYPE_IS_FULL_PROJECT, $holder_var_GroupSearchEngineProject);
		$this->addSearchEngine(SEARCH__TYPE_IS_TRACKERS, $holder_var_TrackersGroupSearchEngine);
		$this->addSearchEngine(SEARCH__TYPE_IS_FORUMS, $holder_var_ForumsGroupSearchEngine);
		$this->addSearchEngine(SEARCH__TYPE_IS_TASKS, $holder_var_TasksGroupSearchEngine);
		$this->addSearchEngine(SEARCH__TYPE_IS_FRS, $holder_var_FrsGroupSearchEngine);
		$this->addSearchEngine(SEARCH__TYPE_IS_DOCS, $holder_var_DocsGroupSearchEngine);
		$this->addSearchEngine(SEARCH__TYPE_IS_NEWS, $holder_var_NewsGroupSearchEngine);

		# Hook to be able to load new search engine
		plugin_hook_by_reference('group_search_engines', $this);

		// Global search engine
		//$this->addSearchEngine(SEARCH__TYPE_IS_SOFTWARE, new GFSearchEngine(SEARCH__TYPE_IS_SOFTWARE, 'ProjectHtmlSearchRenderer', _('Projects')));
		//$this->addSearchEngine(SEARCH__TYPE_IS_PEOPLE, new GFSearchEngine(SEARCH__TYPE_IS_PEOPLE, 'PeopleHtmlSearchRenderer', _('People')));
		//$this->addSearchEngine(SEARCH__TYPE_IS_ALLDOCS, new GFSearchEngine(SEARCH__TYPE_IS_ALLDOCS, 'DocsAllHtmlSearchRenderer', _('Documents')));
		$holder_var_GFSearchEngineSoftware = new GFSearchEngine(SEARCH__TYPE_IS_SOFTWARE, 'ProjectHtmlSearchRenderer', _('Projects'));
		$holder_var_GFSearchEnginePeople = new GFSearchEngine(SEARCH__TYPE_IS_PEOPLE, 'PeopleHtmlSearchRenderer', _('People'));
		$holder_var_GFSearchEngineAlldocs = new GFSearchEngine(SEARCH__TYPE_IS_ALLDOCS, 'DocsAllHtmlSearchRenderer', _('Documents'));
		$this->addSearchEngine(SEARCH__TYPE_IS_SOFTWARE, $holder_var_GFSearchEngineSoftware);
		$this->addSearchEngine(SEARCH__TYPE_IS_PEOPLE, $holder_var_GFSearchEnginePeople);
		$this->addSearchEngine(SEARCH__TYPE_IS_ALLDOCS, $holder_var_GFSearchEngineAlldocs);
		if (forge_get_config('use_people')) {
			//$this->addSearchEngine(SEARCH__TYPE_IS_SKILL, new GFSearchEngine(SEARCH__TYPE_IS_SKILL, 'SkillHtmlSearchRenderer', _('Skills')));
			$holder_var_GFSearchEngineSkill = new GFSearchEngine(SEARCH__TYPE_IS_SKILL, 'SkillHtmlSearchRenderer', _('Skills'));
			$this->addSearchEngine(SEARCH__TYPE_IS_SKILL, $holder_var_GFSearchEngineSkill);
		}

		// Rss search engines
		//$this->addSearchEngine(SEARCH__TYPE_IS_SOFTWARE, new GFSearchEngine(SEARCH__TYPE_IS_SOFTWARE, 'ProjectRssSearchRenderer', _('Projects')), SEARCH__OUTPUT_RSS);
		$holder_var_GFSearchEngineRss = new GFSearchEngine(SEARCH__TYPE_IS_SOFTWARE, 'ProjectRssSearchRenderer', _('Projects'));
		$this->addSearchEngine(SEARCH__TYPE_IS_SOFTWARE, $holder_var_GFSearchEngineRss, SEARCH__OUTPUT_RSS);

		plugin_hook('search_engines', array('object' => $this));
	}

	function loadParameters() {
		$this->parameters = array(
			SEARCH__PARAMETER_GROUP_ID,
			SEARCH__PARAMETER_ARTIFACT_ID,
			SEARCH__PARAMETER_FORUM_ID,
			SEARCH__PARAMETER_GROUP_PROJECT_ID
		);
	}

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
