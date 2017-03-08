<?php

/**
 *
 * validateUrl.php
 * 
 * File to validate the URL.
 *
 * Copyright 2005-2016, SimTK Team
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
 
// URL parameter is sent using POST.
// Validate the url.
$strUrl = $_POST["URL"];

$result = '';
$ext = ShowExtension($strUrl);
if (validateURL($strUrl) == 0) {

	$result = "Invalid#";
	$result=$result. "<img src='/account/form_error.gif' />#";
}
else {
	$starttime = date("s",time());
	$httpCode = urlExistance($strUrl);
	$endtime = date("s",time());

	if (($endtime - $starttime) >= 5) {
		$result = "Invalid#";
		$result=$result . "<img src='/account/form_error.gif' />#";
	}
	else if ($httpCode == 200) {
		if (strlen(trim($ext)) > 0) {
			$result = "Valid#";
			$result=$result . "<img src='/account/form_ok.gif' />#";
			$result = $result."URL validated. Thank you!#";
		}
		else {
			$result = "Invalid#";
			$result=$result . "<img src='/account/form_error.gif' />#";
		}
	}
	else if ($httpCode == 302) {
		if (strlen(trim($ext)) > 0) {
			$result = "Valid#";
			$result=$result . "<img src='/account/form_ok.gif' />#";
			$result = $result."URL validated. Thank you!#";
		}
		else {
			$result = "Invalid#";
			$result=$result . "<img src='/account/form_error.gif' />#";
		}
	}
	else if ($httpCode == 404) {
		$result = "NotFound#";
		$result=$result . "<img src='/account/form_error.gif' />#";
	}
	else {
		$result = "Invalid#";
		$result=$result . "<img src='/account/form_error.gif' />#";
	}
	//curl_close($handle);
}

echo $result;



function validateURL($strUrl) {
	$pattern = '|[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
	return preg_match($pattern, $strUrl);
}

function ShowExtension($path) {
	preg_match('/[^?]*/', $path, $matches);
	$string = $matches[0];
	$pattern = preg_split('/\./', $string, -1, PREG_SPLIT_OFFSET_CAPTURE);
	if (count($pattern) > 1) {
		$namepart = $pattern[count($pattern)-1][0];
		preg_match('/[^?]*/', $namepart, $matches);
		return $matches[0];
	}
} 

function urlExistance($strUrl) {
	$handle = curl_init($strUrl);
	$timeout = 5;
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle,  CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

	curl_setopt($handle, CURLOPT_HEADER, true);
	curl_setopt($handle, CURLOPT_NOBODY, true);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($handle, CURLOPT_AUTOREFERER, true);

	// Get the HTML or whatever is linked in $strUrl.
	$response = curl_exec($handle);
	echo $response;

	// Check for 404 (file not found).
	$httpCode1 = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	echo $httpCode1;

	curl_close($handle);

	return $httpCode1;
}

?> 



