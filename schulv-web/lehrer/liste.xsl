<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="html" indent="yes"/>

<xsl:include href="../templates/schulvbase.xsl"/>


<xsl:template match="overview">
  <table width="300">
    <tr>
	  <th align="left">Vorname</th>
	  <th align="left">Nachname</th>
	  <th colspan="2"/>
	</tr>
	<xsl:apply-templates select="lehrer"/>
  </table>
</xsl:template>

<xsl:template match="lehrer">
  <tr>
    <td><xsl:value-of select="vorname"/></td>
    <td><xsl:value-of select="nachname"/></td>
	<td><a href="/lehrer/edit.xml?dn={@dn}">edit</a></td>
	<td><a href="/lehrer/detail.xml?dn={@dn}">anzeigen</a></td>
  </tr>
</xsl:template>

</xsl:stylesheet>

