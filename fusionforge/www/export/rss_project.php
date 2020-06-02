<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (C) 2009-2011 Alain Peyrat, Alcatel-Lucent
 * Copyright 2012,2015 Franck Villaume - TrivialDev
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
 * Standard Alcatel-Lucent disclaimer for contributing to open source
 *
 * The code ("Contribution") has not been tested and/or
 * validated for release as or in products, combinations with products or
 * other commercial use. Any use of the Contribution is entirely made at
 * the user's own responsibility and the user can not rely on any features,
 * functionalities or performances Alcatel-Lucent has attributed to the
 * Contribution.
 *
 * THE CONTRIBUTION BY ALCATEL-LUCENT IS PROVIDED AS IS, WITHOUT WARRANTY
 * OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, COMPLIANCE,
 * NON-INTERFERENCE AND/OR INTERWORKING WITH THE SOFTWARE TO WHICH THE
 * CONTRIBUTION HAS BEEN MADE, TITLE AND NON-INFRINGEMENT. IN NO EVENT SHALL
 * ALCATEL-LUCENT BE LIABLE FOR ANY DAMAGES OR OTHER LIABLITY, WHETHER IN
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * CONTRIBUTION OR THE USE OR OTHER DEALINGS IN THE CONTRIBUTION, WHETHER
 * TOGETHER WITH THE SOFTWARE TO WHICH THE CONTRIBUTION RELATES OR ON A STAND
 * ALONE BASIS."
 */

require_once '../env.inc.php';
require_once $gfcommon.'include/pre.php';

$group_id = getIntFromRequest('group_id');

/* We need a group_id */
if (!$group_id) {
    exit_no_group();
}

$project = group_get_object($group_id);
if (!$project|| !is_object($project) || $project->isError()) {
    exit_no_group();
}

$name = $project->getPublicName();

function get_rss_20_doc () {
	return '&nbsp;' . html_image('ic/rss.png',16,16);
}

function get_rss_feed_20 ($page,$rss_title) {
	// RSS 2.0 format
	$rss_feed_20 = '<li><a href="' . $page . '">' . $rss_title . '</a>';
	$rss_feed_20 .= '<link href="' . $page . '" title="' . $rss_title . ', RSS 2.0" rel="alternate" type="application/rss+xml" />';
	$rss_feed_20 .= get_rss_20_doc () . '</li>';
	return $rss_feed_20;
}

$HTML->header(array('title'=>_('RSS Exports')));

echo '<p>';
printf(_('%s data is exported in RSS 2.0 format. Many of the export URLs can also accept form/get data to customize the output. All data generated by these pages is realtime.'), forge_get_config ('forge_name'));

echo _('To get Project News or New Project Releases of a specific project use the Links below.');

$string_rss_20 = '';

// RSS for project news
if ($project->usesNews() && forge_get_config('use_news')) {
	$string_rss_20 .= get_rss_feed_20 ("rss20_news.php?group_id=" . $group_id,
										$name._(': ') . _('Project News'));
}

// RSS for project Activity
if ( ($project->usesNews() && forge_get_config('use_news')) ||
	($project->usesFRS() && forge_get_config('use_frs')) ||
	($project->usesTracker() && forge_get_config('use_tracker')) ||
	($project->usesForum() && forge_get_config('use_forum')) ||
	($project->usesSCM() && forge_get_config('use_scm')) ||
	($project->usesPM() && forge_get_config('use_pm')) ){
	$string_rss_20 .= get_rss_feed_20 ("rss20_activity.php?group_id=" . $group_id,
										$name._(': ') . _('Activity'));
}

// RSS for project Releases
if ($project->usesFRS() && forge_get_config('use_frs')) {
	$string_rss_20 .= get_rss_feed_20 ("rss20_newreleases.php?group_id=" . $group_id,
										$name._(': ') . _('Project Releases'));
}

// RSS for project documents
if ($project->usesDocman() && forge_get_config('use_docman')) {
	$string_rss_20 .= get_rss_feed_20 ("rss20_docman.php?group_id=" . $group_id,
										$name._(': ') . _('Project Document Manager'));
}

// RSS for tasks
if ($project->usesPM() && forge_get_config('use_pm')) {
	$string_rss_20 .= get_rss_feed_20 ("rss20_tasks.php?group_id=" . $group_id,
										$name._(': ') . _('Project Tasks'));
}

// RSS for artifacts
if ($project->usesTracker() && forge_get_config('use_tracker')) {
	$string_rss_20 .= get_rss_feed_20 ("rss20_tracker.php?group_id=" . $group_id,
										$name._(': ') . _('Project Trackers'));
}

?>
<ul>
<?php echo $string_rss_20; ?>
</ul>

<a href="javascript:history.go(-1)">[<?php echo _('Go back') ?>]</a>
<br />
<?php $HTML->footer();