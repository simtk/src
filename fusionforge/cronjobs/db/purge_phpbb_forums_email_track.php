<?php

// Clean up phpbb forum post email send tracking one day old.

/**
* @ignore
*/
global $auth, $cache, $config, $db, $phpbb_root_path, $phpEx, $template, $user; 

define('IN_PHPBB', true);

// Note: This file is in the directory gforge/cronjobs/db.
// Other paths are defined relative to this directory.
require_once (dirname(__FILE__).'/../../common/include/env.inc.php');
require_once $gfcommon . "include/pre.php";

// Get root path for phpBB plugin.
$phpbbDirWWW = $gfcommon . "../plugins/phpBB/www/";
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $phpbbDirWWW;

// Generate $phpEx.
$phpEx = substr(strrchr(__FILE__, '.'), 1);

// Retrieve phpBB database credentials from "phpBB.ini" config file.
$forgeConfig = FusionForgeConfig::get_instance();
$simtkHost = $forgeConfig->get_value("phpBB", "phpbb_host");
$simtkDbName = $forgeConfig->get_value("phpBB", "phpbb_name");
$simtkDbUser = $forgeConfig->get_value("phpBB", "phpbb_user");
$simtkDbPassword = $forgeConfig->get_value("phpBB", "phpbb_password");

// Connect to phpBB database.
$myconn = pg_connect(
	"host=" . $simtkHost . 
	" dbname=" . $simtkDbName .
	" user=" . $simtkDbUser .
	" password=" . $simtkDbPassword);

$onedayago = time() - 86400;

$strPhpbbForumsEmailTrackPurge = "DELETE FROM phpbb_forums_email_track " .
	"WHERE time<" . $onedayago;
$phpbb_res = pg_query_params($myconn, $strPhpbbForumsEmailTrackPurge, array());

echo "Purged phpbb_forums_email_track";

?>


