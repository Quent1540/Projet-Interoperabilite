<?php
//Récupération de l'ip du client
$clientIP = $_SERVER['REMOTE_ADDR'];

//Chargement le xml de la geolocalisation du client
$XML = new DOMDocument();
$XML->load( "http://ip-api.com/xml/24.48.0.1");

//Les données qu'on veut qu'on recup depuis le xml
$lat = $XML->getElementsByTagName("lat")->item(0)->nodeValue;
$lon = $XML->getElementsByTagName("lon")->item(0)->nodeValue;
$city = $XML->getElementsByTagName("city")->item(0)->nodeValue;

//Transformation xslt
$xslt = new XSLTProcessor();
$XSL = new DOMDocument();
$XSL->load( '../xsl/meteo.xsl' );
$xslt->importStylesheet( $XSL );
print $xslt->transformToXML( $XML );
?>