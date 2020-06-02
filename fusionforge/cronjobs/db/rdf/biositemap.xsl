<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:param name="PrimaryCenter">Simbios</xsl:param>
	<xsl:output method='xml' version='1.0' encoding='UTF-8' indent='yes'/>
	<xsl:template match='data' name='biositemap'>
		<rdf:RDF
			xml:base="http://www.bioontologies.org/biositemaps/NCBO.rdf"
			xmlns:BRO="http://bioontology.org/ontologies/BiomedicalResourceOntology.owl#"
			xmlns:xsp="http://www.owl-ontologies.com/2005/08/07/xsp.owl#"
			xmlns="http://www.bioontologies.org/biositemaps/NCBO.rdf#"
			xmlns:swrlb="http://www.w3.org/2003/11/swrlb#"
			xmlns:protege="http://protege.stanford.edu/plugins/owl/protege#"
			xmlns:swrl="http://www.w3.org/2003/11/swrl#"
			xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
			xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
			xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
			xmlns:owl="http://www.w3.org/2002/07/owl#"
			xmlns:desc="http://bioontology.org/ontologies/biositemap.owl#"
			xmlns:p1="http://bioontology.org/ontologies/BiomedicalResources.owl#">
		  <owl:Ontology rdf:about="">
			<owl:imports rdf:resource="http://bioontology.org/ontologies/BiomedicalResources.owl"/>
		  </owl:Ontology>
		  <xsl:for-each select='/data/projects/project'>
			  <desc:Resource_Description>
			    <xsl:attribute name='rdf:ID'><xsl:value-of select='unix_group_name'/></xsl:attribute>
				<desc:resource_name rdf:datatype="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select='group_name'/></desc:resource_name>
				<desc:organization rdf:datatype="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select='member_list/member[project_lead=2 or project_lead=1]/university_name'/></desc:organization>
				<desc:keywords rdf:datatype="http://www.w3.org/2001/XMLSchema#string"><xsl:for-each select="keyword_list/keyword">
					<xsl:value-of select="."/>
					<xsl:if test="position() != last()">
					  <xsl:text>, </xsl:text>
					</xsl:if>
				  </xsl:for-each></desc:keywords>
				<xsl:for-each select="ontology/resource">
				  <desc:resource_type>
				    <xsl:element name="{concat('BRO:', .)}"/>
				  </desc:resource_type>
				</xsl:for-each>
				<xsl:if test='count(ontology/resource) = 0'>
					<desc:resource_type />
				</xsl:if>
				<desc:resource_sharable>Yes</desc:resource_sharable>
				<desc:contact_person rdf:datatype="http://www.w3.org/2001/XMLSchema#string">
				  <xsl:for-each select='member_list/member[project_lead>0]'>
                    <xsl:sort select="project_lead"  data-type="number"/>
                    <xsl:sort select="user_group_id" data-type="number"/>
				    <xsl:value-of select='realname'/>
					<xsl:if test="position() != last()">
					  <xsl:text>, </xsl:text>
					</xsl:if>
				  </xsl:for-each>
				</desc:contact_person>
				<desc:description rdf:datatype="http://www.w3.org/2001/XMLSchema#string">
				  <xsl:value-of select='long_description'/>
				</desc:description>
				<desc:biositemap_author rdf:datatype="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select="$PrimaryCenter" /></desc:biositemap_author>
				<desc:URL rdf:datatype="http://www.w3.org/2001/XMLSchema#string">https://simtk.org/projects/<xsl:value-of select='unix_group_name'/></desc:URL>
				<desc:research_program rdf:datatype="http://www.w3.org/2001/XMLSchema#string">NCBC</desc:research_program>
				<desc:center rdf:datatype="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select="$PrimaryCenter" />
				</desc:center>
				<desc:platforms><xsl:for-each select='file_list/platform_list/platform'>
				    <xsl:value-of select='.'/>
					<xsl:if test="position() != last()">
					  <xsl:text>, </xsl:text>
					</xsl:if>
				</xsl:for-each></desc:platforms>
			  </desc:Resource_Description>
		  </xsl:for-each>
		</rdf:RDF>
	</xsl:template>
</xsl:stylesheet>
