<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- Перевод строки -->
<xsl:template name="Newline"><xsl:text>
</xsl:text></xsl:template>


<!-- Вывод даты в виде "dd.mm.yyyy tt" -->
<xsl:template name="FormatDateTime">
   <xsl:param name="DateTime"/>

   <xsl:variable name="date">
     <xsl:choose>
       <xsl:when test="contains($DateTime, 'T')">
         <xsl:value-of select="substring-before($DateTime, 'T')"/>
       </xsl:when>
       <xsl:otherwise>
         <xsl:value-of select="$DateTime"/>
       </xsl:otherwise>
     </xsl:choose>
   </xsl:variable>

   <xsl:variable name="month_postfixYear" select="substring-after($date, '-')"/>
   <xsl:value-of select="substring-after($month_postfixYear, '-')"/> <xsl:text>.</xsl:text>
   <xsl:value-of select="substring-before($month_postfixYear, '-')"/> <xsl:text>.</xsl:text>
   <xsl:value-of select="substring-before($date, '-')"/> <xsl:text> </xsl:text>
   <xsl:value-of select="substring-after($DateTime, 'T')"/>
</xsl:template>


<!-- Вывод данных: число/строка -->
<xsl:template name="FormatData">
   <xsl:param name="Val"/>
   <xsl:param name="Fmt"/>
   <xsl:choose>
     <xsl:when test="$Fmt=''">
       <xsl:value-of select="$Val"/>
     </xsl:when>
     <xsl:when test="$Fmt='--'">
       <xsl:text>--</xsl:text>
     </xsl:when>
     <xsl:otherwise>
       <xsl:value-of select="format-number($Val, $Fmt, 'num-format')"/>
     </xsl:otherwise>
   </xsl:choose>
</xsl:template>


<!-- Рамка для ячейки  -->

<!--
<xsl:template name="BorderStyle">
  <xsl:param name="Border"></xsl:param>
    <xsl:attribute name="style">
      <xsl:if test="$Border != ''">
        <xsl:choose>
          <xsl:when test="substring($Border, 1, 1)='2'">border-top:1.5pt solid;</xsl:when>
          <xsl:when test="substring($Border, 1, 1)='1'">border-top:0.5pt solid;</xsl:when>
          <xsl:otherwise>border-top:none;</xsl:otherwise>
        </xsl:choose>

        <xsl:choose>
          <xsl:when test="substring($Border, 2, 1)='2'">border-bottom:1.5pt solid;</xsl:when>
          <xsl:when test="substring($Border, 2, 1)='1'">border-bottom:0.5pt solid;</xsl:when>
          <xsl:otherwise>border-bottom:none;</xsl:otherwise>
        </xsl:choose>

        <xsl:choose>
          <xsl:when test="substring($Border, 3, 1)='2'">border-left:1.5pt solid;</xsl:when>
          <xsl:when test="substring($Border, 3, 1)='1'">border-left:0.5pt solid;</xsl:when>
          <xsl:otherwise>border-left:none;</xsl:otherwise>
        </xsl:choose>
     
        <xsl:choose>
          <xsl:when test="substring($Border, 4, 1)='2'">border-right:1.5pt solid;</xsl:when>
          <xsl:when test="substring($Border, 4, 1)='1'">border-right:0.5pt solid;</xsl:when>
          <xsl:otherwise>border-right:none;</xsl:otherwise>
        </xsl:choose>
      </xsl:if>
  </xsl:attribute>
</xsl:template>
-->

<xsl:template name="BorderStyle">
  <xsl:param name="Border"></xsl:param>

  <xsl:if test="$Border != '' and $Border != '0111'">
    <xsl:attribute name="style">
      <xsl:choose>
        <xsl:when test="substring($Border, 1, 1)='2'">border-top:2px solid;</xsl:when>
        <xsl:when test="substring($Border, 1, 1)='1'">border-top:1px solid;</xsl:when>
        <xsl:otherwise></xsl:otherwise>
      </xsl:choose>

      <xsl:choose>
        <xsl:when test="substring($Border, 2, 1)='2'">border-bottom:2px solid;</xsl:when>
        <xsl:when test="substring($Border, 2, 1)='1'"/>
        <xsl:otherwise>border-bottom:none;</xsl:otherwise>
      </xsl:choose>

      <xsl:choose>
        <xsl:when test="substring($Border, 3, 1)='2'">border-left:2px solid;</xsl:when>
        <xsl:when test="substring($Border, 3, 1)='1'"/>
        <xsl:otherwise>border-left:none;</xsl:otherwise>
      </xsl:choose>
   
      <xsl:choose>
        <xsl:when test="substring($Border, 4, 1)='2'">border-right:2px solid;</xsl:when>
        <xsl:when test="substring($Border, 4, 1)='1'"/>
        <xsl:otherwise>border-right:none;</xsl:otherwise>
      </xsl:choose>
    </xsl:attribute>
  </xsl:if>
</xsl:template>


</xsl:stylesheet>