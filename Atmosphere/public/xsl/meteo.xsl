<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output method="html" encoding="UTF-8" indent="yes"/>

    <xsl:template match="/previsions">
        <html>
            <head>
                <link rel="stylesheet" href="meteo.css"/>
            </head>
            <body>
                <div class="meteo-container">
                    <h2>Météo du jour</h2>

                    <table>
                        <tr>
                            <th>Moment</th>
                            <th>Météo</th>
                            <th>Température</th>
                        </tr>

                        <xsl:apply-templates select="echeance[contains(@hour, '08:00')]">
                            <xsl:with-param name="moment" select="'Matin'"/>
                        </xsl:apply-templates>

                        <xsl:apply-templates select="echeance[contains(@hour, '14:00')]">
                            <xsl:with-param name="moment" select="'Après-midi'"/>
                        </xsl:apply-templates>

                        <xsl:apply-templates select="echeance[contains(@hour, '20:00')]">
                            <xsl:with-param name="moment" select="'Soir'"/>
                        </xsl:apply-templates>
                    </table>
                </div>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="echeance">
        <xsl:param name="moment"/>
        <xsl:variable name="temp" select="temperature/level[@val='2m']"/>
        <xsl:variable name="vent" select="vent_moyen/level[@val='10m']"/>

        <tr>
            <td><strong><xsl:value-of select="$moment"/></strong></td>

            <td class="icone">
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
