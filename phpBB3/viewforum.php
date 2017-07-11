<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @copyright 2016, Henry Kwong, Tod Hing - SimTK Team
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Redirect to indexPhpbb.php, which has access control and header of the project.
$forum_id = request_var('f', 0);
$start = request_var('start', 0);
if ($start != 0) {
	// Start page is specified.
	echo '<script>top.window.location.href = "/plugins/phpBB/indexPhpbb.php?f=' . $forum_id . 
		'&start=' . $start . '";</script>';
}
else {
	echo '<script>top.window.location.href = "/plugins/phpBB/indexPhpbb.php?f=' . $forum_id . '";</script>';
}
