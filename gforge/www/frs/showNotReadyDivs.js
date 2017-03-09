/**
 *
 * showNotReadyDivs.js
 * 
 * Display DIV and refresh page which contains any "not_ready" class.
 *
 * Copyright 2005-2017, SimTK Team
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
 
$(document).ready(function() {
	// Check whether "not_ready" DIVs are present.
	var divNotReady = $(".not_ready");
	if (divNotReady.length) {
		// Present.
		// Iterate and open parent package/release panels for each DIV.
		divNotReady.each(function(theIdx) {
			// Extract the id of this DIV and open the 
			// package/release panels containing this DIV.
			openParentPanels($(this).attr("id"));
		});

		// Refresh page after 30 seconds.
		setTimeout(refreshPage, 30000);
	}
});

// Refresh page.
// NOTE: The method used here reduces blinking of page.
function refreshPage() {
	$.get('', function(theData) {
		$(document.body).html(theData);
	});
}

// Open the package/release panels containing the file with the specified id.
// The DIV id has the format: "notready_PACKID_RELID_FILEID".
function openParentPanels(theId) {

	// Retrieve package, release, and file ids.
	var id0 = theId.indexOf("notready_");
	if (id0 == -1) {
		// "notready_" token is not present.
		return;
	}
	var tmpStr0 = theId.substring(9);
	var id1 = tmpStr0.indexOf("_");
	if (id1 == -1) {
		// Package token is not present.
		return;
	}
	var tmpStr1 = tmpStr0.substring(id1 + 1);
	var id2 = tmpStr1.indexOf("_");
	if (id2 == -1) {
		// Release token not present.
		return;
	}

	var packId = tmpStr0.substring(0, id1);
	var relId = tmpStr1.substring(0, id2);
	var fileId = tmpStr1.substring(id2 + 1);

	// Generate the selector.
	var selPanelPackRel = $(".panel" + packId + "_" + relId);

	// Expand previous release.
	if (selPanelPackRel.parent().hasClass("previousReleasesPanel")) {
		$(".previousReleases" + packId).click();
	}

	// Expand hidden release first if needed.
	if (selPanelPackRel.parent().hasClass("hiddenReleasesPanel")) {
		$(".hiddenReleases" + packId).click();
	}

	// Expand release
	$(".panel" + packId + "_" + relId + ">h2>.expander").click();

	// Both offset() and non-zero delay is necessary.
	// Otherwise, the page stays at the top.
	$("html, body").animate({scrollTop: $(".download_date" + packId + "_" + relId).offset().top}, 500);
}


