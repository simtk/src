<?php
/**
 * FusionForge constants
 *
 * Copyright 1999-2001, VA Linux Systems, Inc.
 * Copyright 2012, Franck Villaume - TrivialDev
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

// Set timezone.
// This is necessary for PHP 5.6.
date_default_timezone_set('America/Los_Angeles');

/* Search */

define('SEARCH__TYPE_IS_ARTIFACT', 'artifact');
define('SEARCH__TYPE_IS_SOFTWARE', 'soft');
define('SEARCH__TYPE_IS_FORUM', 'forum');
define('SEARCH__TYPE_IS_PEOPLE', 'people');
define('SEARCH__TYPE_IS_SKILL', 'skill');
define('SEARCH__TYPE_IS_DOCS', 'docs');
define('SEARCH__TYPE_IS_ALLDOCS', 'alldocs');
define('SEARCH__TYPE_IS_TRACKERS', 'trackers');
define('SEARCH__TYPE_IS_TASKS', 'tasks');
define('SEARCH__TYPE_IS_FORUMS', 'forums');
define('SEARCH__TYPE_IS_NEWS', 'news');
define('SEARCH__TYPE_IS_FRS', 'frs');
define('SEARCH__TYPE_IS_FULL_PROJECT', 'full');
define('SEARCH__TYPE_IS_ADVANCED', 'advanced');

define('SEARCH__DEFAULT_ROWS_PER_PAGE', 25);
define('SEARCH__ALL_SECTIONS', 'all');

define('SEARCH__PARAMETER_GROUP_ID', 'group_id');
define('SEARCH__PARAMETER_ARTIFACT_ID', 'atid');
define('SEARCH__PARAMETER_FORUM_ID', 'forum_id');
define('SEARCH__PARAMETER_GROUP_PROJECT_ID', 'group_project_id');

define('SEARCH__OUTPUT_RSS', 'rss');
define('SEARCH__OUTPUT_HTML', 'html');

define('SEARCH__MODE_OR', 'or');
define('SEARCH__MODE_AND', 'and');

/* Mailing lists */

define('MAIL__MAILING_LIST_IS_PRIVATE', '0');
define('MAIL__MAILING_LIST_IS_PUBLIC', '1');
define('MAIL__MAILING_LIST_IS_DELETED', '9');

define('MAIL__MAILING_LIST_IS_REQUESTED', '1');
define('MAIL__MAILING_LIST_IS_CREATED', '2');
define('MAIL__MAILING_LIST_IS_CONFIGURED', '3');
define('MAIL__MAILING_LIST_PW_RESET_REQUESTED', '4');
define('MAIL__MAILING_LIST_IS_UPDATED', '5');

define('MAIL__MAILING_LIST_NAME_MIN_LENGTH', 4);

/* Groups */
define('GROUP_IS_MASTER', 1);
define('GROUP_IS_STATS', forge_get_config('stats_group'));
define('GROUP_IS_NEWS', forge_get_config('news_group'));
define('GROUP_IS_PEER_RATINGS', forge_get_config('peer_rating_group'));
define('GROUP_IS_TEMPLATE', forge_get_config('template_group'));

/* Admin */
define('ADMIN_CRONMAN_ROWS', 30);

/* SCM repositories */
define('SCM_EXTRA_REPO_ACTION_UPDATE', 0);
define('SCM_EXTRA_REPO_ACTION_DELETE', 1);

/* FRS */

define(
	'FRS__DEFAULT_AGREEMENT',
	'Portions copyright (c) 2005 - ' . date("Y") . ' Stanford University<br/><br/>
Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject
to the following conditions:
<br/><br/>
The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.
<br/><br/>
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS, CONTRIBUTORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.'
);

define(
	'FRS__MIT_LICENSE',
	'Copyright (c) ' . date("Y") . ', Stanford University

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
SOFTWARE.'
);

define(
	'FRS__BSD_LICENSE',
	'Copyright (c) ' . date("Y") . ', Stanford University
All rights reserved.</p>

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met: 

* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the authors and should not be interpreted as representing official policies, either expressed or implied, of the FreeBSD Project.'
);

define(
	'FRS__GPL_LICENSE',
	'Copyright (C) ' . date("Y") .' Stanford University

    This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along with this program.  If not, see http://www.gnu.org/licenses/gpl.html'
);

define(
	'FRS__LGPL_LICENSE',
	'Copyright (C) ' . date("Y") . ' Stanford University

    This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License along with this program.  If not, see http://www.gnu.org/licenses/lgpl.html.'
);

define(
	'FRS__CCANC_LICENSE',
	'Copyright (C) ' . date("Y") . ' Stanford University

  This work is available under the Creative Commons Attribution-NonCommercial License, summarized below. For full legal text, please see http://creativecommons.org/licenses/by-nc/3.0/legalcode

You are free:
* to Share (to copy, distribute, and transmit the work)
* to Remix (to adapt the work)

Under the following conditions:
* Attribution - You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).
* Noncommercial - You may not use this work for commercial purposes.

With the understanding that:
* Waiver - Any of the above conditions can be waived if you get permission from the copyright holder.
* Public Domain - Where the work or any of its elements is in the public domain under applicable law, that status is in no way affected by the license.
* Other Rights - In no way are any of the following rights affected by the license:
	- Your fair dealing or fair use rights, or other applicable copyright exceptions and limitations;
	- The moral rights of the author;
	- Rights other persons may have either in the work itself or in how the work is used, such as publicity or privacy rights.
* Notice - For any reuse or distribution, you must make clear to others the license terms of this work. The best way to do this is with a link to: http://creativecommons.org/licenses/by-nc/3.0/
'
);

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
