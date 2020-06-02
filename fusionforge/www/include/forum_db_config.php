<?php

// Default forum hostname.
$GLOBALS['forum_host'] = "simtkhub-forum";

// Convenience functions.
// Set the forum hostname.
function forum_db_sethost($forumHostName) {
	$GLOBALS['forum_host'] = $forumHostName;
}

// Get the forum hostname.
function forum_db_gethost() {
	return $GLOBALS['forum_host'];
}

?>
