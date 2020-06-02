<?php

/**
 *
 * awsUtils.php
 * 
 * Utilities to handle AWS EC2.
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
 
require dirname(__FILE__) . '/awsSDK/aws-autoloader.php';

use Aws\Ec2\Ec2Client;


// Launch EC2 instasnce and get its public IP address.
function getEC2($instanceIdEC2, $key, $secret, $region) {

	$addrPublicIP = false;

	// Launch the EC2 instance. Retry 10 times if failed.
	// It may fail the EC2 instance may be in the state of "stopping".
	// NOTE: Does not hurt if EC2 was launched already.
	for ($cnt = 0; $cnt < 10; $cnt++) {
		$res = launchEC2($instanceIdEC2, $key, $secret, $region);
		if ($res == "") {
			// Instance launched or already launched before. Done.
			break;
		}

		// Failed to launch EC2. Retry.
		if ($cnt == 9) {
			echo "Cannot launch EC2 \n";
			return false;
		}

		// Wait 10 seconds.
		sleep(10);
	}

	// Get IP address of EC2 instance. The address is different every time.
	// Wait for launch completion if not launched already.
	for ($cnt = 0; $cnt < 100; $cnt++) {
		// Try to get public IP address of EC2 instance that was launched.
		$addrPublicIP = getEC2PublicIpAddr($instanceIdEC2, $key, $secret, $region, $status);
		if ($status === "ok" && $addrPublicIP !== false) {
			// Instance has public IP address and is ready. Done.
			echo "$cnt : $addrPublicIP \n";
			break;
		}

		// Instance is not ready. Wait 5 seconds.
		echo "$cnt Status: " . $status . "\n";
		sleep(5);
	}

	return $addrPublicIP;
}

// Stop EC2 instance.
function stopEC2($instanceIdEC2, $key, $secret, $region) {
	try {
		// Create Ec2Client object.
		$theClient = Ec2Client::factory(
			array(
				'key'=>$key,
				'secret'=>$secret,
				'region'=>$region,
				'ssl.certificate_authority'=>'system',
			)
		);

		// Stop EC2 instance.
		$result = $theClient->stopInstances(
			array('InstanceIds'=>array($instanceIdEC2))
		);
	}
	catch (Exception $e) {
		return "***ERROR***" . $e;
	}

	return "";
}

// Launch EC2 instance.
function launchEC2($instanceIdEC2, $key, $secret, $region) {
	try {
		// Create Ec2Client object.
		$theClient = Ec2Client::factory(
			array(
				'key'=>$key,
				'secret'=>$secret,
				'region'=>$region,
				'ssl.certificate_authority'=>'system',
			)
		);

		// Start EC2 instance.
		$result = $theClient->startInstances(
			array('InstanceIds'=>array($instanceIdEC2))
		);
	}
	catch (Exception $e) {
		return "***ERROR***" . $e;
	}

	return "";
}

// Get public IP address of EC2 instance.
function getEC2PublicIpAddr($instanceIdEC2, $key, $secret, $region, &$retStatus) {

	$retStatus = false;
	try {
		// Create Ec2Client object.
		$theClient = Ec2Client::factory(
			array(
				'key'=>$key,
				'secret'=>$secret,
				'region'=>$region,
				'ssl.certificate_authority'=>'system',
			)
		);

		// Get EC2 status.
		$resStatus = $theClient->DescribeInstanceStatus(
			array(
				'InstanceId'=>$instanceIdEC2
			)
		);
		$statuses = $resStatus["InstanceStatuses"];
		// NOTE: Other instance ids may be returned.
		// Hence, need to check for a match.
		for ($cntInst = 0; $cntInst < count($statuses); $cntInst++) {
			if (!isset($statuses[$cntInst]["InstanceId"])) {
				// Instance id not set.
				continue;
			}

			if ($statuses[$cntInst]["InstanceId"] != $instanceIdEC2) {
				// Instance id not the one requested.
				continue;
			}
				
			if (!isset($statuses[$cntInst]["InstanceStatus"]["Status"])) {
				// Status not available.
				return false;
			}

			// Has first element if instance is present.
			$retStatus = $statuses[$cntInst]["InstanceStatus"]["Status"];
			if ($retStatus == "ok") {
				// Retrieve network information to get public IP address.
				$resNetwork = $theClient->DescribeNetworkInterfaces(
					array(
						'InstanceId'=>$instanceIdEC2
					)
				);

				// Find the public IP address. Get the first one (should have only one).
				$network = $resNetwork["NetworkInterfaces"];
				for ($cntAddr = 0; $cntAddr < count($network); $cntAddr++) {

					if (isset($network[$cntAddr]["Attachment"]["InstanceId"]) &&
						$network[$cntAddr]["Attachment"]["InstanceId"] == $instanceIdEC2 &&
						isset($network[$cntAddr]["Association"]["PublicIp"])) {

						$addrPublicIP = $network[$cntAddr]["Association"]["PublicIp"];
						return $addrPublicIP;
					}
				}
			}
		}
	}
	catch (Exception $e) {
	}

	return false;
}

?>


