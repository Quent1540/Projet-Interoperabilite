<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>

    <xsl:template match="/previsions">
        <div class="meteo-container">
            <h2>Météo (prochaines heures)</h2>
            <table border="1" style="border-collapse: collapse; width: 100%; text-align: center;">
                <tr style="background-color: #f0f0f0;">
                    <th>Délai</th>
                    <th>Ciel</th>
                    <th>Temp.</th>
                </tr>

                <xsl:apply-templates select="echeance[position() &lt; 5]">
                </xsl:apply-templates>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="echeance">
        <xsl:variable name="heure" select="@hour"/>
        <xsl:variable name="temp" select="temperature/level[@val='2m'] - 273.15"/>

        <xsl:variable name="vent">
            <xsl:choose>
                <xsl:when test="vent_moyen/level[@val='10m']">
                    <xsl:value-of select="vent_moyen/level[@val='10m']"/>
                </xsl:when>
                <xsl:otherwise>0</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <tr>
            <td>+<xsl:value-of select="$heure"/>h</td>

            <td class="icone" style="font-size: 1.5em;">
                <xsl:choose>
                    <xsl:when test="risque_neige/level > 0">❄️</xsl:when>
                    <xsl:when test="pluie/level > 0">🌧️</xsl:when>
                    <xsl:when test="$vent > 20">💨</xsl:when>
                    <xsl:otherwise>☀️</xsl:otherwise>
                </xsl:choose>
            </td>
            <td>
                <strong><xsl:value-of select="round($temp)"/>°C</strong>
            </td>
        </tr>
    </xsl:template>

</xsl:stylesheet>