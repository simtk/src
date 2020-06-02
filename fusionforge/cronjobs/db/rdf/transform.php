<?php

/**
 *
 * transform.php
 * 
 * File to transform XML.
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
 

function transform($xmlStr, $xslFile) {
	$xml = new DomDocument();
	$xml->substituteEntities=true;
	$xml->resolveExternals=true;
	$xml->loadXML($xmlStr);

	//load the style sheet

	$xsl = new DomDocument();
	$xsl->substituteEntities=true;
	$xml->resolveExternals=true;
	$xsl->load($xslFile);

	//transform the data to HTML

	$proc = new XsltProcessor();
	$proc->importStylesheet($xsl);

	return $proc->transformToXML($xml);
}

function transformStr($xmlStr, $xslStr) {
	if ($GLOBALS["showxml"]) {
		return $xmlStr;
	}
	
	if ($GLOBALS["showxsl"]) {
		return $xslStr;
	}

	$xml = new DomDocument();
	$xml->substituteEntities=true;
	$xml->resolveExternals=true;
	$xml->loadXML($xmlStr);

	//load the style sheet

	$xsl = new DomDocument();
	$xsl->substituteEntities=true;
	$xml->resolveExternals=true;

	// Fix problem in locating isolated '& " that should be converted
	// into "&amp ". Otherwise, the XSLT transform will fail! HK.
	$xslStr = str_replace('& ', '&amp; ', $xslStr);

	$xsl->loadXML($xslStr);

	//transform the data to HTML

	$proc = new XsltProcessor();
	$proc->importStylesheet($xsl);

	return $proc->transformToXML($xml);
}

?>
