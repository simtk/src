/**
 *
 * download_utils.js
 * 
 * Handle submission of file download request.
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
 
$(function() {
	// Handle form submission. Check "expted_use" first, if present.
	$("form").submit(function(event) {

		if ($(".expected_use").length == 0) {
			// "expected_use" not present.
			// Check not needed; submit the form.
			return;
		}

		// Check expected_use input before submit..
		var valExpectedUse = $(".expected_use").val();
		if ($.trim(valExpectedUse).length == 0) {
			$("#feedback").remove();
			var theMsg = '<div id="feedback" class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;">';

			// Left-justified message.
			theMsg += '<div style="float:left;">You must provide a description of how you expect to use this software</div>';

			// 'X' to close.
			var msgX = '<div style="float:right;" onclick="' +
				"$('.warning_msg').hide('slow');" +
				'">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div>';
			theMsg += msgX;

			// Clear formatting.
			theMsg += '<div style="clear: both;"></div>';
			theMsg += '</div>';

			$(".project_menu_row").after(theMsg);
		}
		else if (valExpectedUse.length < 7) {
			$("#feedback").remove();
			var theMsg = '<div id="feedback" class="warning_msg" style="padding:8px;border:1px dotted;margin-top:12px;margin-bottom:12px;line-height:18px;">';

			// Left-justified message.
			theMsg += '<div style="float:left;">You must provide an expected use at least 7 letters long</div>';

			// 'X' to close.
			var msgX = '<div style="float:right;" onclick="' +
				"$('.warning_msg').hide('slow');" +
				'">&nbsp;&nbsp;&nbsp;&nbsp;X&nbsp;&nbsp;&nbsp;&nbsp;</div>';
			theMsg += msgX;

			// Clear formatting.
			theMsg += '<div style="clear: both;"></div>';
			theMsg += '</div>';

			$(".project_menu_row").after(theMsg);
		}
		else {
			$("#feedback").remove();
			// OK; submit.
			return;
		}

		// Do not submit.
		event.preventDefault();
	});
});

