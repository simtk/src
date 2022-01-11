/**
 *
 * handlerDiskUsage.js
 * 
 * Copyright 2005-2022, SimTK Team
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
 

// Check disk space used before proceeding with adding file.
function handlerDiskUsage(groupId) {

	var ok_diskusage = false;
	var total_bytes = false;
	var allowed_bytes = false;
	groupId = Number(groupId);

	var theData = new Array();
	theData.push({name: "GroupId", value: groupId});
	$.ajax({
		type: "POST",
		data: theData,
		dataType: "json",
		url: "/frs/admin/checkDiskUsage.php",
		async: false,
	}).done(function(res) {
		// Result is already in JSON-decoded.
		ok_diskusage = res.ok_diskusage;
		total_bytes = Number(res.total_bytes);
		allowed_bytes = Number(res.allowed_bytes);
	}).fail(function(res) {
	});

	// Clear previous message first.
	$(".du_warning_msg").html('');

	if (!ok_diskusage) {
		if (total_bytes != false && allowed_bytes != false) {
			$(".du_warning_msg").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Total disk space used (' + total_bytes + ' GB) exceeded project quota (' + allowed_bytes + ' GB). Please contact SimTK WebMaster.</b></div>');
			$(".du_warning_msg")[0].scrollIntoView(false);

			if (typeof event != "undefined") {
				event.preventDefault();
			}
			return false;
		}
		else {
			// Cannot get disk usage or project quota. Proceed to import data.
			// Show a message in console.
			console.log("total bytes: " + total_bytes + "; allowed bytes: " + allowed_bytes);
			return true;
		}
	}
	else {
		// OK. Proceed.
		return true;
	}

}



