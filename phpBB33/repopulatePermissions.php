<?php

require_once '../../env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfwww.'include/forum_db_utils.php';
require_once 'userPermsSetup.php';

$query_forum = "SELECT forum_id from phpbb_forums";
$res_forum = queryForum($query_forum);
while ($row_forum = pg_fetch_array($res_forum)) {
	$forumId = $row_forum["forum_id"];

	// Set up user permissions in forum.
	userPermsSetup($forumId);
}
pg_free_result($res_forum);

?>
