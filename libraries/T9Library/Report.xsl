<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:import href="RepBase.xsl"/>

<xsl:decimal-format name="num-format" decimal-separator="." grouping-separator="," pattern-separator=";" />

<!-- Описание используемых стилей -->

<xsl:attribute-set name="title-style">
  <xsl:attribute name="class">rep_title</xsl:attribute>
</xsl:attribute-set>

<xsl:attribute-set name="param-style">
  <xsl:attribute name="class">rep_param</xsl:attribute>
</xsl:attribute-set>

<xsl:attribute-set name="tab-title-style">
  <xsl:attribute name="class">TabTitle</xsl:attribute>
</xsl:attribute-set>

<xsl:attribute-set name="tab-style">
  <xsl:attribute name="class">report</xsl:attribute>
</xsl:attribute-set>


<!-- Перекрывайте этот шаблон, чтобы явно задавать ширины колонок (по имени) -->
<xsl:template name="ColWidth">
  <xsl:param name="ColName"></xsl:param>
  <col/>
</xsl:template>



<!-- Основные преобразования -->

<xsl:template match="/Reports">

  <xsl:for-each select="Report">

    <xsl:call-template name="Newline"/>
    <div class='ReportTop'>

    <xsl:call-template name="Newline"/>
    <p xsl:use-attribute-sets="title-style"><xsl:value-of select="Title"/></p>

    <xsl:if test="@Type='ByReference'">
      <xsl:if test="Reference!=''">
        <xsl:call-template name="Newline"/>
        <p xsl:use-attribute-sets="param-style">Справочник: <xsl:value-of select="Reference"/></p>
      </xsl:if>
      <xsl:if test="Parameters[position()=2]!=''">
        <xsl:call-template name="Newline"/>
        <p xsl:use-attribute-sets="param-style">Параметры: <xsl:value-of select="Parameters"/></p>
      </xsl:if>
    </xsl:if>
    <xsl:if test="Period/@BegDate!=''">
      <xsl:call-template name="Newline"/>
      <p xsl:use-attribute-sets="param-style">За период c
        <xsl:call-template name="FormatDateTime">
          <xsl:with-param name="DateTime"><xsl:value-of select="Period/@BegDate"/></xsl:with-param>
        </xsl:call-template>
        по
        <xsl:call-template name="FormatDateTime">
          <xsl:with-param name="DateTime"><xsl:value-of select="Period/@EndDate"/></xsl:with-param>
        </xsl:call-template>
      </p>
    </xsl:if>

    <xsl:if test="Plan!=''">
      <xsl:call-template name="Newline"/>
      <p xsl:use-attribute-sets="param-style">План счетов: <xsl:value-of select="Plan"/></p>
    </xsl:if>
    <xsl:if test="Accounts!=''">
      <xsl:call-template name="Newline"/>
      <p xsl:use-attribute-sets="param-style">Cчета: <xsl:value-of select="Accounts"/></p>
    </xsl:if>

    <xsl:choose>
      <xsl:when test="@Type='ByReference' and Parameters[position()=2]!=''">
        <xsl:call-template name="Newline"/>
        <p xsl:use-attribute-sets="param-style">Параметры: <xsl:value-of select="Parameters[position()=2]"/></p>
      </xsl:when>
      <xsl:otherwise>
        <xsl:if test="Parameters!=''">
          <xsl:call-template name="Newline"/>
          <p xsl:use-attribute-sets="param-style">Параметры: <xsl:value-of select="Parameters"/></p>
        </xsl:if>
      </xsl:otherwise>
    </xsl:choose>

    <xsl:call-template name="Newline"/>
    </div> <xsl:comment> ReportTop </xsl:comment>


    <xsl:call-template name="Newline"/>
    <div class='ReportBody'>

    <!-- Таблицы -->
    <xsl:for-each select="Table">

      <xsl:call-template name="Newline"/>
      <xsl:call-template name="Newline"/>
      <div class='ReportTable'>

      <xsl:if test="@Title!=''">
        <xsl:call-template name="Newline"/>
        <div xsl:use-attribute-sets="tab-title-style"><xsl:value-of select="@Title"/></div>
      </xsl:if>

      <xsl:call-template name="Newline"/>
      <table xsl:use-attribute-sets="tab-style">

        <!-- Список колонок -->
        <xsl:for-each select="Row[position()=1]">

          <xsl:call-template name="Newline"/>
          <colgroup>

          <xsl:for-each select="ColGroup/Cell">
            <xsl:call-template name="Newline"/>

            <xsl:call-template name="ColWidth">
              <xsl:with-param name="ColName"><xsl:value-of select="@Value"/></xsl:with-param>
            </xsl:call-template>

          </xsl:for-each>

          <xsl:call-template name="Newline"/>
          </colgroup>

        </xsl:for-each>

        <!-- Строки... -->
        <xsl:for-each select="Row">
          <xsl:variable name="RowType"><xsl:value-of select="@Type"/></xsl:variable>
          <xsl:variable name="RowSplit"><xsl:value-of select="@Value"/></xsl:variable>
          <xsl:variable name="IsHeaderRow" select="$RowType='SplitByCol' or $RowType='SumKind' or $RowType='DebCre' or $RowType='Indicator' "/>
          <xsl:variable name="IsTotalRow" select="contains($RowType,'Total')"/>

          <xsl:call-template name="Newline"/>
          <tr>

            <xsl:choose>
              <xsl:when test="$IsHeaderRow">
                <xsl:attribute name="class">header</xsl:attribute>
              </xsl:when>
              <xsl:when test="$IsTotalRow">
                <xsl:attribute name="class">total</xsl:attribute>
              </xsl:when>
              <xsl:otherwise>
                <xsl:attribute name="class">data</xsl:attribute>
              </xsl:otherwise>
            </xsl:choose>


            <xsl:for-each select="ColGroup">
              <xsl:variable name="CurrCol" select="@Type"/>

              <!-- колонки разбиения -->
              <xsl:if test="@Type='SplitByTab' or @Type='SplitByRow'">
                <xsl:for-each select="Cell">

                  <xsl:call-template name="Newline"/>
                  <td>
                    <xsl:attribute name="class">spl</xsl:attribute>

                    <!--Бордюр-->
                    <xsl:call-template name="BorderStyle">
                      <xsl:with-param name="Border"><xsl:value-of select="@Border"/></xsl:with-param>
                    </xsl:call-template>

                    <xsl:if test="@Align!='' and @Align!='left'">
                      <xsl:attribute name="align"><xsl:value-of select="@Align"/></xsl:attribute>
                    </xsl:if>

                    <xsl:choose>
                      <xsl:when test="@Span !=1 ">
                        <xsl:attribute name="colspan"><xsl:value-of select="@Span"/></xsl:attribute>
                      </xsl:when>
                      <xsl:otherwise>
                      </xsl:otherwise>
                    </xsl:choose>

<!--                <xsl:value-of select="@Value"/>  -->
                    <xsl:choose>
                      <xsl:when test="position()=1">
<!--                    <a href='#'><xsl:value-of select="@Value"/></a>  -->

                        <a>
                          <xsl:attribute name="href">task?id=<xsl:copy-of select="$RowSplit" /></xsl:attribute>
                          <xsl:value-of select="@Value"/>
                        </a> 

                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:value-of select="@Value"/>  
                      </xsl:otherwise>
                    </xsl:choose>

                  </td>

                </xsl:for-each>
              </xsl:if>

              <!-- колонки данных -->
              <xsl:if test="@Type='Body'">
                <xsl:for-each select="Cell">

                  <xsl:call-template name="Newline"/>
                  <td>
                    <!--Класс клетки: Init, Turn, Rest-->
                    <xsl:attribute name="class"><xsl:value-of select="@Type"/></xsl:attribute>

<!--
                    <xsl:choose>
                      <xsl:when test="">
                      </xsl:when>
                      <xsl:otherwise>
                      </xsl:otherwise>
                    </xsl:choose>
-->

                    <!--Бордюр-->
                    <xsl:call-template name="BorderStyle">
                      <xsl:with-param name="Border"><xsl:value-of select="@Border"/></xsl:with-param>
                    </xsl:call-template>


                    <xsl:choose>
                      <xsl:when test="@Span!=1">
                        <xsl:attribute name="colspan"><xsl:value-of select="@Span"/></xsl:attribute>
                      </xsl:when>
                      <xsl:otherwise>
                        <!--<xsl:attribute name="NOWRAP"/>-->
                      </xsl:otherwise>
                    </xsl:choose>


                    <xsl:choose>
                      <xsl:when test="$IsHeaderRow">
                        <!-- шапка -->
                        <xsl:attribute name="align"><xsl:value-of select="@Align"/></xsl:attribute>  
                        <xsl:value-of select="@Value"/>
                      </xsl:when>

                      <xsl:when test="@Format!=''">
                        <!-- данные -->
                        <div class="num">
                          <xsl:if test="@Align!='' and @Align!='right'">
                            <xsl:attribute name="align"><xsl:value-of select="@Align"/></xsl:attribute>  
                          </xsl:if>

                          <!-- <xsl:value-of select="format-number(@Value,@Format,'num-format')"/> -->
                          <xsl:call-template name="FormatData">
                            <xsl:with-param name="Val"><xsl:value-of select="@Value"/></xsl:with-param>
                            <xsl:with-param name="Fmt"><xsl:value-of select="@Format"/></xsl:with-param>
                          </xsl:call-template>
                        </div>
                      </xsl:when>

                      <xsl:otherwise>
                        <!-- данные -->
                        <div class="val">
                          <xsl:if test="@Align!='' and @Align!='left'">
                            <xsl:attribute name="align"><xsl:value-of select="@Align"/></xsl:attribute>  
                          </xsl:if>

                          <xsl:value-of select="@Value"/>
                        </div>
                      </xsl:otherwise>
                    </xsl:choose>
                  </td>
                </xsl:for-each> <!-- Cell -->
              </xsl:if>

            </xsl:for-each>  <!-- Column -->

          <xsl:call-template name="Newline"/>
          </tr>
        </xsl:for-each> <!-- Row -->

      <xsl:call-template name="Newline"/>
      </table>

      <xsl:call-template name="Newline"/>
      </div> <xsl:comment> ReportTable </xsl:comment>

    </xsl:for-each>   <!-- Table -->

    <xsl:call-template name="Newline"/>
    </div> <xsl:comment> ReportBody </xsl:comment>

  </xsl:for-each>   <!-- Report -->

</xsl:template>

</xsl:stylesheet>