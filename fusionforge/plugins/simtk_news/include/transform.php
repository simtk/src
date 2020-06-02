<?php
/*
 * Created on Feb 15, 2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 

function transform($xmlStr, $xslFile) {
	if ($GLOBALS["showxml"]) {
		return $xmlStr;
	}
	
	$xml = new DomDocument();
	$xml->substituteEntities=true;
	$xml->resolveExternals=true;
	$xml->loadXML($xmlStr);

	//load the style sheet

	$xsl = new DomDocument();
	$xsl->substituteEntities=true;
	$xml->resolveExternals=true;
	$xsl->load($xslFile);

	if ($GLOBALS["showxsl"]) {
		return $xsl->saveXML();
	}

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
