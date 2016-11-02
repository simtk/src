<?php

/**
 *
 * sshCommUtils.php
 * 
 * SSH communications utilities for handling simulations.
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
 
require_once('phpseclib/Math/BigInteger.php');
require_once('phpseclib/Net/SSH2.php');
require_once('phpseclib/Net/SFTP.php');
require_once('phpseclib/Crypt/RC4.php');
require_once('phpseclib/Crypt/RSA.php');

// Wrapper for exec.
function sshExec($theSsh, $cmd) {

	// Check SSH class used.
	$strClass = "";
	if (is_object($theSsh)) {
		$strClass = trim(get_class($theSsh));
	}
	if ($strClass == "Net_SSH2") {
		// phpseclib method.
		$result = $theSsh->exec($cmd);
	}
	else {
		// libssh2-php method.
		$stream = @ssh2_exec($theSsh, $cmd);
		if (!$stream) {
			return false;
		}
		stream_set_blocking($stream, true);
		$result = stream_get_contents($stream);

		@fclose($stream);
	}

	return $result;
}

// Wrapper for sftp get.
function sftpGet($theSftp, $strRemoteFilePath, $strLocalFilePath) {

	$status = true;

	// Check SFTP class used.
	$strClass = "";
	if (is_object($theSftp)) {
		$strClass = trim(get_class($theSftp));
	}
	if ($strClass == "Net_SFTP") {
		// phpseclib method.
		$status = $theSftp->get($strRemoteFilePath, $strLocalFilePath);
	}
	else {
		// libssh2-php method.
		$strLocalFilePath = getcwd() . "/" . $strLocalFilePath;

		$sftp = @ssh2_sftp($theSftp);
		if (!$sftp) {
			return false;
		}
		$stream = @fopen("ssh2.sftp://$sftp$strRemoteFilePath", 'r');
		if (!$stream) {
			return false;
		}
		$contents = fread($stream, filesize("ssh2.sftp://$sftp$strRemoteFilePath"));
		file_put_contents($strLocalFilePath, $contents);

		@fclose($stream);
	}

	return $status;
}

// Wrapper for sftp put.
function sftpPut($theSftp, $strRemoteFilePath, $strLocalFilePath) {

	// Check SFTP class used.
	$strClass = "";
	if (is_object($theSftp)) {
		$strClass = trim(get_class($theSftp));
	}
	if ($strClass == "Net_SFTP") {
		// phpseclib method.
		$theSftp->put($strRemoteFilePath, $strLocalFilePath, NET_SFTP_LOCAL_FILE);
	}
	else {
		// libssh2-php method.
		$strLocalFilePath = getcwd() . "/" . $strLocalFilePath;

		$sftp = @ssh2_sftp($theSftp);
		if (!$sftp) {
			return;
		}

		$stream = @fopen("ssh2.sftp://$sftp$strRemoteFilePath", 'w');
		if (!$stream) {
			return;
		}

		$data_to_send = @file_get_contents($strLocalFilePath);
		if ($data_to_send === false) {
			@fclose($stream);
			return;
		}
		if (@fwrite($stream, $data_to_send) === false) {
			@fclose($stream);
			return;
		}

		@fclose($stream);
	}
}

// Get ssh access to remote server.
function getRemoteServerSshAccess($theRemoteServerName, 
	$theRemoteUserName, $theRemotePassword, $theRemoteAuthMethod,
	&$strRemoteServerHomeDir = null) {

	// Use SSH to login and execute command.

	if ($theRemoteAuthMethod == 2) {
		// Use phpseclib method for handling RSA private key.
		$resSsh = new Net_SSH2($theRemoteServerName, 22);
	}
	else {
		// libssh2-php method.
		$resSsh = @ssh2_connect($theRemoteServerName, 22);
	}

	if ($theRemoteAuthMethod == 2) {
		// Using RSA private key.
		// $theRemotePassword contains name of the private key file.

		// phpseclib method.
		$key = new Crypt_RSA();
		$pathPrivateKeyFile = "/usr/share/gforge/awsConfigData/" . $theRemotePassword;
		$key->loadKey(file_get_contents($pathPrivateKeyFile));

		if (!$resSsh || !$resSsh->login($theRemoteUserName, $key)) {
			// Cannot login to remote server.
			return false;
		}

		return false;
	}
	else {
		// Using username/password.

/*
		if (!$resSsh || !$resSsh->login($theRemoteUserName, $theRemotePassword)) {
			// Cannot login to remote server.
			return false;
		}
*/

		// libssh2-php method.
		if (!$resSsh) {
			return false;
		}
		if (!@ssh2_auth_password($resSsh, $theRemoteUserName, $theRemotePassword)) {
			return false;
		}

		// Result contains newline. Trim it.
		$strRemoteServerHomeDir = trim(sshExec($resSsh, "/bin/pwd"));
	}

	return $resSsh;
}

// Get access to remote server.
function getRemoteServerSftpAccess($theRemoteServerName, 
	$theRemoteUserName, $theRemotePassword, $theRemoteAuthMethod,
	&$strRemoteServerHomeDir = null) {

	// Use SFTP to send file to remote server.

	if ($theRemoteAuthMethod == 2) {
		// Use phpseclib method for handling RSA private key.
		$resSftp = new Net_SFTP($theRemoteServerName, 22);
	}
	else {
		// libssh2-php method.
		$resSftp = @ssh2_connect($theRemoteServerName, 22);
	}

	if ($theRemoteAuthMethod == 2) {
		// Using RSA private key.
		// $theRemotePassword contains name of the private key file.

		// phpseclib method.
		$key = new Crypt_RSA();
		$pathPrivateKeyFile = "/usr/share/gforge/awsConfigData/" . $theRemotePassword;
		$key->loadKey(file_get_contents($pathPrivateKeyFile));
		if (!$resSftp || !$resSftp->login($theRemoteUserName, $key)) {
			// Cannot login to remote server.
			return false;
		}

		return false;
	}
	else {
		// Using username/password.

/*
		if (!$resSftp || !$resSftp->login($theRemoteUserName, $theRemotePassword)) {
			// Cannot login to remote server.
			return false;
		}
*/

		// libssh2-php method.
		if (!$resSftp) {
			return false;
		}
		if (!@ssh2_auth_password($resSftp, $theRemoteUserName, $theRemotePassword)) {
			return false;
		}

		// Result contains newline. Trim it.
		$strRemoteServerHomeDir = trim(sshExec($resSftp, "/bin/pwd"));
	}

	return $resSftp;
}

?>
