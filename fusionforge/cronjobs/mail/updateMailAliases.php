#! /usr/bin/php
<?php

/**
 *
 * updateMailAliases.php
 * 
 * Update mailing list aliases file. 
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
 
require dirname(__FILE__) . '/../../www/env.inc.php';
require_once $gfcommon . 'include/pre.php';

// Find all active mailing lists.
$arrMissingMailList = array();
$strQuery = "SELECT list_name FROM mail_group_list " .
	"WHERE status=3";
$res = db_query_params($strQuery, array());
$numRows = db_numrows($res);
for ($cnt = 0; $cnt < $numRows; $cnt++) {
	$strMailList = strtolower(db_result($res, $cnt, 'list_name'));
	if (findMailListAddr($strMailList) === FALSE) {
		// Mailing list is not in /etc/aliases file.
		// Remember this missing list.
		$arrMissingMailList[] = $strMailList;
	}
}

// Find all deleted mailing lists.
$arrDeletedMailList = array();
$strQuery = "SELECT mailing_list_name FROM deleted_mailing_lists " .
	"WHERE isdeleted=1 " .
	"AND mailing_list_name NOT IN " .
	"(SELECT list_name FROM mail_group_list WHERE status=3);";
$res = db_query_params($strQuery, array());
$numRows = db_numrows($res);
for ($cnt = 0; $cnt < $numRows; $cnt++) {
	$strMailList = strtolower(db_result($res, $cnt, 'mailing_list_name'));
	if (findMailListAddr($strMailList) === TRUE) {
		// Deleted mailing list is still present in /etc/aliases file.
		// Remember this deleted missing list.
		// Add a leading space and ending '"' to search for entry. 
		$arrDeletedMailList[] = " " . $strMailList . '"';
	}
}

if (count($arrMissingMailList) > 0 ||
	count($arrDeletedMailList) > 0) {

	// Has missing mailing lists or deleted mailing lists 
	// that are still present in /etc/aliases.

	// Make a copy of /etc/aliases file first.
	copy("/etc/aliases", "/etc/aliases_" . date('Y_m_d_H_i'));

	$strMissing = "";
	$strEmail = "";
	if (count($arrMissingMailList) > 0) {
		$strEmail .= "The following mailing lists were missing from /etc/aliases. Fixed.<br/>" . PHP_EOL;
	}
	foreach ($arrMissingMailList as $strMailList) {
		$strEmail .= $strMailList . "<br/>" . PHP_EOL;

		$strMissing .= $strMailList . ":" . 
			' 		 "|/var/lib/mailman/mail/mailman post ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-admin:" . 
			' 		 "|/var/lib/mailman/mail/mailman admin ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-bounces:" . 
			' 		 "|/var/lib/mailman/mail/mailman bounces ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-confirm:" . 
			' 		 "|/var/lib/mailman/mail/mailman confirm ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-join:" . 
			' 		 "|/var/lib/mailman/mail/mailman join ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-leave:" . 
			' 		 "|/var/lib/mailman/mail/mailman leave ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-owner:" . 
			' 		 "|/var/lib/mailman/mail/mailman owner ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-request:" . 
			' 		 "|/var/lib/mailman/mail/mailman request ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-subscribe:" . 
			' 		 "|/var/lib/mailman/mail/mailman subscribe ' . $strMailList . '"' . PHP_EOL;
		$strMissing .= $strMailList . "-unsubscribe:" . 
			' 		 "|/var/lib/mailman/mail/mailman unsubscribe ' . $strMailList . '"' . PHP_EOL;
	}

	// NOTE: Put the missing aliases to the line after "mailman-unsubscribe:"
	// such that these aliases are among the mailing lists section, 
	// i.e. somewhere in the middle of the /etc/aliases file.
	// Do not append to the end of /etc/aliases, because there 
	// are #GFORGEBEGIN and #GFORGEEND markers; it is safer to 
	// put the missing section to among the mailing lists and also
	// it'll be easier to find these missing mailing lists.
	$strHead = "";
	$strTail = "";
	$startTail = FALSE;

	$fh = fopen("/etc/aliases", "r");
	while (($line = fgets($fh)) !== false) {

		// Check each entry to see if it has a deleted mailing list.
		$isDeleted = FALSE;
		foreach ($arrDeletedMailList as $strMailList) {
			if (strpos($line, $strMailList) !== FALSE) {
				// Found a mailing list that has been deleted.
				// Do not include in /etc/aliases.
				$isDeleted = TRUE;
				break;
			}
		}

		if ($isDeleted === FALSE) {
			// This entry has not been deleted. Include in /etc/aliases.
			if ($startTail === FALSE) {
				// Get head.
				$strHead .= $line;
			}
			else {
				// Get tail.
				$strTail .= $line;
			}
		}

		// Put lines after the line that starts with 
		// "mailman-subscribe:" to the tail section.
		$idx = strpos($line, "mailman-unsubscribe:");
		if ($idx === 0) {
			// Next line with go to tail section.
			$startTail = TRUE;
		}
	}

	//echo $strMissing;

	// Embed missing aliases between the head and tail sections.
	$strBuff = $strHead . $strMissing . $strTail;

	// Add missing list(s) contents to /etc/aliases.
	//echo $strBuff;
	file_put_contents("/etc/aliases", $strBuff, LOCK_EX);

	fclose($fh);

	if (count($arrDeletedMailList) > 0) {
		$strEmail .= "Deleted the following mailing lists from /etc/aliases.<br/>" . PHP_EOL;
		foreach ($arrDeletedMailList as $strMailList) {
			$strEmail .= $strMailList . "<br/>" . PHP_EOL;
		}
	}

	// Send notification email about fixing mailing lists.
	sendEmail("webmaster@simtk.org", "Fixed mailing lists in /etc/aliases", $strEmail);
}

// Send email.
function sendEmail($theEmailAddr, $theTitle, $theMsgBody) {

	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'Content-type: text/html; charset=iso-8859-1';
	$headers[] = 'From: root@simtk.org';

	mail($theEmailAddr, $theTitle, $theMsgBody, implode("\r\n", $headers));
}

// Search for presence of mailing list in /etc/aliases file.
function findMailListAddr($strMailList) {

	// Append ":" to the mailing list name for searching.
	$strMailList .= ":";

	$foundit = false;
	$fh = fopen("/etc/aliases", "r");

	// Look up each line.
	while (($buff = fgets($fh)) !== false) {
		$idx = strpos($buff, $strMailList);
		// Has to be at beginning of line.
		if ($idx === 0) {
			$foundit = TRUE;
			break;
		}
	}

	fclose($fh);

	return $foundit;
}

?>
