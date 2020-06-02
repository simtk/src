<?php
/**
 *
 * image.php
 *
 * Contains functions to support manipulation of images using imageMagick.
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
 
function imageCreateThumbnail($origFile, $thumbFile, $thumbWidth, $thumbHeight) {
//	$convert = $GLOBALS["sys_imagemagick_dir"] . "convert " . $origFile . " -thumbnail "
//	$convert = "convert " . $origFile . " -thumbnail "
	$convert = "/usr/bin/convert " . $origFile . " -thumbnail "
		. $thumbWidth . "x" . $thumbHeight . " -gravity center -crop "
		. $thumbWidth . "x" . $thumbHeight ."+0+0 -page +0+0 -quality 90 " . $thumbFile;
	$convert = escapeshellcmd($convert); 
	
	exec($convert, $output, $ret);
	
	return ($ret == 0);
}   

function imageResize($origFile, $targetFile, $width, $height) {
	//$convert = $GLOBALS["sys_imagemagick_dir"] . "convert -resize " . $width . "x" . $height . " " . $origFile . " " . $targetFile;
	$convert = "convert -resize " . $width . "x" . $height . " " . $origFile . " " . $targetFile;
	$convert = escapeshellcmd($convert); 

	exec($convert, $output, $ret);         

	return ($ret == 0);
}

function crop_uploaded_file($file) {
	// Note: Do NOT use escapeshellcmd() here because the parameters
	// strings do not work after escaped!!!
	$convert = '/usr/bin/convert ' . 
		escapeshellarg($file) . 
		' -virtual-pixel edge -set option:distort:viewport ' .
		'"%[fx:min(w,h)]x%[fx:min(w,h)]+%[fx:max((w-h)/2,0)]+%[fx:max((h-w)/2,0)]" ' .
		'-filter point -distort SRT 0 +repage ' .
		escapeshellarg($file);

	exec($convert, $output, $ret);
	
	return ($ret == 0);
}   


function imageUploaded($tmpfile, $file, $crop=true) {
	$thumb = $file."_thumb";

	if (file_exists($file)) {
		unlink($file);
	}
				
	if (file_exists($thumb)) {
		unlink($thumb);
	}
	
	if (!move_uploaded_file($tmpfile, $file)) {
                //echo "tmpfile: " . $tmpfile . "<br>";
                //echo "file: " . $file;
                //exit;
		return false;
	}

	if ($crop && !crop_uploaded_file($file)) {
		return false;
	}

	//create thumbnail
	if (!imageCreateThumbnail($file, $thumb, 79, 77)) {
		return false;
	}

	//resize the image
	if (!imageResize($file, $file, 250, 300)) {
		return false;
	}
	return true;
}

function imageRenamed($tmpfile, $file, $crop=true) {
	$thumb = $file."_thumb";

	if (file_exists($file)) {
		unlink($file);
	}
				
	if (file_exists($thumb)) {
		unlink($thumb);
	}
	
	// Move file.
	if (!rename($tmpfile, $file)) {
		return false;
	}

	if ($crop && !crop_uploaded_file($file)) {
		return false;
	}

	//create thumbnail
	if (!imageCreateThumbnail($file, $thumb, 79, 77)) {
		return false;
	}

	//resize the image
	if (!imageResize($file, $file, 250, 300)) {
		return false;
	}
	return true;
}

function newsImageUploaded($tmpfile, $file) {
	$thumb = $file."_thumb";

	if (file_exists($file)) {
		unlink($file);
	}
				
	if (file_exists($thumb)) {
		unlink($thumb);
	}
	
	if (!move_uploaded_file($tmpfile, $file)) {
		return false;
	}
	
	//create thumbnail
	if (!imageCreateThumbnail(escapeshellarg($file), escapeshellarg($thumb), 79, 77)) {
		return false;
	}

	//if size is bigger than a given size, resize the image; if not, keep the original size.
	$size = getimagesize($file);
	if($size[0] > 250) {
		if (!imageResize(escapeshellarg($file), escapeshellarg($file), 250, 300)) {
			return false;
		}
	}

	return true;	
}

function imageDelete($file) {
	
	$thumb = $file."_thumb";

	if (file_exists($file)) {
		unlink($file);
	}
				
	if (file_exists($thumb)) {
		unlink($thumb);
	}
	
	$command = "cd /var/simtk/newspics/; rm ".escapeshellarg($file)." ".escapeshellarg($thumb);
	
	exec($command, $output, $ret);         

	return ($ret == 0);	
}

function findImageName ($filepath) 
{ 
	$filepath = strtolower($filepath) ; 
	$tokens = split("[\//]", $filepath);
//	$exts = split("[.]", $filename) ; 
	$n = count($tokens)-1; 
	$filename = $tokens[$n]; 
	return $filename; 
} 

function findext ($filename)
{
	$filename = strtolower($filename);
	$tokens = split("[.]", $filename);
	$n = count($tokens)-1; 
	$ext = $tokens[$n]; 
	return $ext; 	
}

function findname ($filename)
{
	$filename = strtolower($filename);
	$tokens = split("[.]", $filename);
	$name = $tokens[0]; 
	return $name; 	
}

?>
