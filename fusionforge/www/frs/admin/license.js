/**
 *
 * license.js
 * 
 * Handle setting license.
 *
 * Copyright 2005-2020, SimTK Team
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

	/*
	// Bootstrap pover for help with license.
	$(".popoverLic").click(function(e) {
		// Show popover.
		$("#popoverLic").popover();

		// Need to disable the default behavior.
		// Otherwise, the page reloads.
		e.preventDefault();
	});

	// Display help dialog.
	$('.lic_question').click(function(event) {
		// Update description.
		$('.msgDesc').html($(this).attr("lic_desc"));
		// Update title.
		$('.lic_dialog').dialog({
			modal: true,
			title: $(this).attr("lic_name")
		});
	});
	*/

	// User has clicked a different radio button.
	$('.use_agreement').change(function() {
		// Show license agreement.
		var license = $('input[name="use_agreement"]:checked').val();
		if (license == 2) {
			// MIT.
			$(".license_preview").val('Copyright (c) [Insert Year(s)], [Insert organization or names of copyright holder(s)]\r\rPermission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:\r\rThe above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.\r\rTHE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.');
		}
		else if (license == 3) {
			$(".license_preview").val('Copyright (c) [Insert Year(s)], [Insert organization or names of copyright holder(s)]\r\rThis program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.\r\rThis program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.\r\rYou should have received a copy of the GNU Lesser General Public License along with this program.  If not, see http://www.gnu.org/licenses/lgpl.html.');
		}
		else if (license == 4) {
			$(".license_preview").val('Copyright (c) [Insert Year(s)], [Insert organization or names of copyright holder(s)]\r\rThis program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.\r\rThis program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.\r\rYou should have received a copy of the GNU General Public License along with this program.  If not, see http://www.gnu.org/licenses/gpl.html');
		}
		else if (license == 5) {
			$(".license_preview").val('Copyright (c) [Insert Year(s)], [Insert organization or names of copyright holder(s)]\r\rThis work is available under the Creative Commons Attribution-NonCommercial License, summarized below. For full legal text, please see http://creativecommons.org/licenses/by-nc/3.0/legalcode\r\rYou are free:\r* to Share (to copy, distribute, and transmit the work)\r* to Remix (to adapt the work)\r\rUnder the following conditions:\r* Attribution - You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\r* Noncommercial - You may not use this work for commercial purposes.\r\rWith the understanding that:\r* Waiver - Any of the above conditions can be waived if you get permission from the copyright holder.\r* Public Domain - Where the work or any of its elements is in the public domain under applicable law, that status is in no way affected by the license.\r* Other Rights - In no way are any of the following rights affected by the license:\r      - Your fair dealing or fair use rights, or other applicable copyright exceptions and limitations;\r      - The moral rights of the author;\r      - Rights other persons may have either in the work itself or in how the work is used, such as publicity or privacy rights.\r* Notice - For any reuse or distribution, you must make clear to others the license terms of this work. The best way to do this is with a link to: http://creativecommons.org/licenses/by-nc/3.0/');
		}
		else if (license == 6) {
			$(".license_preview").val('Copyright (c) [Insert Year(s)], [Insert organization or names of copyright holder(s)]\r\rThis work is available under the Creative Commons Attribution 4.0 International Public License, summarized below. For full legal text, please see http://creativecommons.org/licenses/by/4.0/legalcode\r\r Creative Commons Attribution 4.0 International Public License\n\n  You are free to:\n\nShare — copy and redistribute the material in any medium or format\nAdapt — remix, transform, and build upon the material for any purpose, even commercially.\n\nThe licensor cannot revoke these freedoms as long as you follow the license terms.\n\nUnder the following terms:\n\nAttribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.\n\nNo additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.\n\nNotices:\n\nYou do not have to comply with the license for elements of the material in the public domain or where your use is permitted by an applicable exception or limitation.\n\nNo warranties are given. The license may not give you all of the permissions necessary for your intended use. For example, other rights such as publicity, privacy, or moral rights may limit how you use the material.');
		}
		else if (license == 7) {
                        // Apache 2.0.
                        $(".license_preview").val('Copyright (c) [Insert Year(s)], [Insert organization or names of copyright holder(s)]\r\rLicensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License.\nYou may obtain a copy of the License at\n\nhttp://www.apache.org/licenses/LICENSE-2.0\n\nUnless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.  See the License for the specific language governing permissions and limitations under the License.');
                }
		if (license == 1) {
			// Custom.
			var strLicense = $(".custom_license").val();
			if (strLicense !== undefined &&
				strLicense.trim() != "") {
				$(".license_preview").val(strLicense);
			}
			else {
				$(".license_preview").val("Copyright (c) [Insert Year(s)], [Insert organization or names of copyright holder(s)]\r\r");
			}
		}
		else if (license == 0) {
			$(".license_preview").val('');
		}

		if (license == 0) {
			$(".edit_notice").hide();
		}
		else {
			$(".edit_notice").show();
		}
	});
});

