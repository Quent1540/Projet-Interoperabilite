<?php

$XML = new DOMDocument();
$XML->load( '');

$xslt = new XSLTProcessor();

$XSL = new DOMDocument();
$XSL->load( 'meteo.xsl' );
$xslt->importStylesheet( $XSL );
print $xslt->transformToXML( $XML );
?>