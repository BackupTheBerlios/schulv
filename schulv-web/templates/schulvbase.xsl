<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="html" omit-xml-declaration="yes" standalone="yes"/>

<xsl:template match="/">
<html>
    <xsl:apply-templates/>
</html>
</xsl:template>

<xsl:template match="schulv">
  <body bgcolor="#ffffff">
  <xsl:apply-templates/>
  </body>
</xsl:template>

<xsl:template match="locationbar">
	<p>
	<xsl:for-each select="location">
		<xsl:choose>
			<!-- ein echtes "has-attribute()" gibts wohl nicht?! -->
			<xsl:when test="boolean(@href)">
				<a href="{@href}"><xsl:value-of select="."/></a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:if test="count(../location) > position()">
			&#32;&#62;&#32;
		</xsl:if>
	</xsl:for-each>
	</p>
</xsl:template>

<!-- simple pass-through filter for unknown elements -->
<xsl:template match="*">
	<xsl:copy select=".">
		<xsl:for-each select="@*">
			<xsl:copy-of select="."/>
		</xsl:for-each>
		<xsl:apply-templates/>
	</xsl:copy>
</xsl:template>

</xsl:stylesheet>
