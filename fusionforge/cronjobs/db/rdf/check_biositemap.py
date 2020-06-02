#!/usr/local/bin/python

"""

check_biositemap.py

File to display search results of projects.

Copyright 2005-2016, SimTK Team

This file is part of the SimTK web portal originating from
Simbios, the NIH National Center for Physics-Based
Simulation of Biological Structures at Stanford University,
funded under the NIH Roadmap for Medical Research, grant
U54 GM072970, with continued maintenance and enhancement
funded under NIH grants R01 GM107340 & R01 GM104139, and
the U.S. Army Medical Research & Material Command award
W81XWH-15-1-0232R01.

SimTK is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of
the License, or (at your option) any later version.

SimTK is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public
License along with SimTK. If not, see
<http://www.gnu.org/licenses/>.  

"""

"""Checks the integrity of a biositemap RDF file"""

from optparse import OptionParser
from xml.dom.minidom import *
import re
import sys
import shutil
import tempfile

def main():
	# Parse the command line
	opts = OptionParser(usage="usage: %prog [options] RDFFILE", description="Checks basic integrity of a Simbios biositemaps file. If the file is not given as an argument, STDIN will be used instead." )
	opts.add_option( "-o", dest="output", help="copy input file to this file when done" )
	opts.add_option( "-v", dest="verbose", type="int", help="set verbosity level" )
	opts.set_defaults()
	( options, args ) = opts.parse_args()
	
	# Check if we were given a file and try to open it, otherwise wait for stdin
	if len( args ) < 1:
		if options.verbose > 0:
			print( "Using standard input" )
		bsmfile = tempfile.TemporaryFile()
		for line in sys.stdin:
			bsmfile.write( line )
		bsmfile.seek( 0 )
	else:
		if options.verbose > 0:
			print( "Reading file " + args[ 0 ] )
		bsmfile = open( args[ 0 ] )
	try:
		dom = parse( bsmfile )
	except ( xml.parsers.expat.ExpatError ):
		bsmfile.close()
		if len( args ) > 0:
			sys.exit( args[ 0 ] + " is not a valid XML file." )
		else:
			sys.exit( "STDIN did not pass in a valid XML file." )
	
	# Check the ID (should be project unix name)
	if options.verbose > 1:
		print( "Checking ID nodes" )
	idNodes = dom.getElementsByTagName( "desc:Resource_Description" )
	for idNode in idNodes:
		if len( idNode.getAttribute( "rdf:ID" ) ) == 0:
			bsmfile.close()
			sys.exit( "The id resource " + str( idNode ) + " has no ID." )
	
	# Check the author (should be "Simbios")
	if options.verbose > 1:
		print( "Checking author nodes" )
	authNodes = dom.getElementsByTagName( "desc:biositemap_author" )
	for authNode in authNodes:
		if authNode.childNodes[0].data != "Simbios":
			bsmfile.close()
			sys.exit( "The author resource at " + str( authNode ) + " should be 'Simbios', but isn't" )
	
	# Check the center (should also be "Simbios")
	if options.verbose > 1:
		print( "Checking center nodes" )
	ctrNodes = dom.getElementsByTagName( "desc:center" )
	for ctrNode in ctrNodes:
		if ctrNode.childNodes[0].data != "Simbios":
			bsmfile.close()
			sys.exit( "The center resource at " + str( ctrNode ) + " should be 'Simbios', but isn't" )
	
	# Check the research program (should be "NCBC")
	if options.verbose > 1:
		print( "Checking research program nodes" )
	researchNodes = dom.getElementsByTagName( "desc:research_program" )
	for researchNode in researchNodes:
		if researchNode.childNodes[0].data != "NCBC":
			bsmfile.close()
			sys.exit( "The research program at " + str( researchNode ) + " should be 'NCBC', but isn't" )
	
	# Check the BRO resources
	if options.verbose > 1:
		print( "Checking resource types" )
	broContainers = dom.getElementsByTagName( "desc:resource_type" )
	for broContainer in broContainers:
		for bro in broContainer.childNodes:
			if not ( re.match( "BRO:", bro.nodeName ) or re.match( "[ \t\n]*$", bro.data ) ):
				bsmfile.close()
				sys.exit( "The resource '" + bro.nodeName + "' does not seem like a valid resource" )
				
	# Check the project url
	if options.verbose > 1:
		print( "Checking project URLs" )
	urlNodes = dom.getElementsByTagName( "desc:URL" )
	for urlNode in urlNodes:
		if not re.match( "https://simtk.org/projects/", urlNode.childNodes[0].data ):
			bsmfile.close()
			sys.exit( "The URL '" + urlNode.childNodes[0].data + "' doesn't match the Simtk.org schema" )
	
	# Tell the user the file looks good if verbose output is requested
	if options.verbose > 0:
		print( "This looks like a valid biositemap file!" )
	
	# If we read from a file, just copy it. Otherwise write the stdinput to the given output
	if options.output is not None and  len( options.output ) > 0:
		if options.verbose > 0:
			print( "Copying file to ") + options.output
		if len( args ) > 0:
			bsmfile.close()
			shutil.copyfile( args[ 0 ], options.output )
		else:
			destfile = open( options.output, "w" )
			bsmfile.seek( 0 )
			for buffer in bsmfile:
				destfile.write( buffer )
			destfile.close()
			bsmfile.close()

if __name__ == "__main__":
	main()
