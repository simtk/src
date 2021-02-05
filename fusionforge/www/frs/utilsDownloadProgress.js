/**
 *
 * utilsDownloadProgress.js
 * 
 * Utilities for handling progress in download.
 *
 * Copyright 2005-2021, SimTK Team
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

// Update UI and track download progress.
function trackDownloadProgress(divDownload,
	divBrowse, 
	divSubmit,
	divUserInputs,
	tokenDownloadProgress) {

	// Hide submit button once download started.
	$("#" + divSubmit).hide();

	// Hide user input div once download started.
	$("#" + divUserInputs).hide();

	// Disable browse button.
	$("#" + divBrowse).prop("disabled", true);
	$("#" + divBrowse).css("opacity", 0.5);

	// Show download started message.
	$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Downloading file... Please wait. Do not navigate away from this page until the download is complete.</b></div>');
	$("." + divDownload)[0].scrollIntoView(false);

	// Start tracking download progress.
	setTimeout(getDownloadStatus, 
		3000, 
		divDownload, 
		divBrowse, 
		tokenDownloadProgress);
}


// Update download progress.
function getDownloadStatus(divDownload,
	divBrowse,
	tokenDownloadProgress) {

	// Retrieve completion status.
	var theData = new Array();
	theData.push({name: "tokenDownloadProgress", value: tokenDownloadProgress});
	$.ajax({
		url: "/frs/getDownloadStatus.php",
		type: "POST",
		data: theData,
		dataType: 'json',
	}).done(function(statusCompletion) {

		// Get completion status.
		statusCompletion = statusCompletion.trim();

		if (statusCompletion != "done") {
			// Not finished yet. Update message with download progress.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Downloading file... (' + statusCompletion + ')  Do not navigate away from this page until the download is complete.</b></div>');

			// Continue tracking download progress.
			setTimeout(getDownloadStatus,
				3000, 
				divDownload, 
				divBrowse, 
				tokenDownloadProgress);
		}
		else {
			// Done. Update UI.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Downloaded file</b></div>');

			$("#" + divBrowse).prop("disabled", false);
			$("#" + divBrowse).css("opacity", 1.0);
		}
	}).fail(function() {
		console.log("Error retrieving download status");
	})
}

