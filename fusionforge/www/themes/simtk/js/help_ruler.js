/**
 *
 * help_ruler.js
 * 
 * Javascript utilities for layout.
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
 
/* 
 * Set of help tools for creating layout
 */

function create_guideline(top){
    $("<div class='help_ruler_guideline' style=' top: "+top+"px;'></div>").appendTo($(document.body));
}

function create_guideline_vertical(left){
    $("<div class='help_ruler_guideline_vertical' style=' left: "+left+"px;'></div>").appendTo($(document.body));
}

function create_help_box(obj){
    var top = obj.top, left = obj.left, width = obj.width, height = obj.height;
    $("<div class='help_box' style=' left: "+left+"px; top: "+top+"px; width: "+width+"px; height: "+height+"px;'></div>").appendTo($(document.body));
}

/**
 * 
 * @param jQuery object element
 * @returns {undefined}
 */
function add_borders_to_descendants_divs(element){
    
    element.find('div').addClass('debuging_borders');
}
var g_colors = {
        "row": "grey",
        'marker' : "green",
        "project_header" : "#EBFFC2",
        "project_title" : "#D6FF85",
        "project_left" : "#CCFF66",
        "project_info":"#B8E65C",
        "project_share" : "#8FB247",
        "social_buttons": "#8AE62E",
        "share_text" : "#ADFF5C",
        
        "project_menu_row":"#E0F0FF",
        "project_menu_col":"#ADD6FF",
        "project_menu":"#7AA3CC",
        
        "project_overview_main":"#FFF0B2",
        "side_bar":"#FFD633",
        "main_col":"#CCA300",
        "main_col p" : "#DBBF4D",
        
        "home_page_header":"#0047B2",
        "home_page_header .left_container":"#0052CC",
        "home_page_header .right_container":"#3385FF",
        "home_page_header .home_page_descr":"#80B2FF",
        "home_page_header .home_page_info":"#CCE0FF",
        
        "projects_slideshow":"#FFF0B2",
        "projects_slide":"#FFD633"
        
        
    };
function add_colors(){
    var colors = g_colors;
    
    for( var i in colors){
        console.log("."+i,colors[i]);
        $("."+i).css('background-color',colors[i]);
    }
}
function remove_colors(){
     var colors = g_colors;
    
    for( var i in colors){
        console.log("."+i,colors[i]);
        $("."+i).css('background-color','transparent');
    }
}

