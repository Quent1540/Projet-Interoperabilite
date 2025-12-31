<?php
//Config proxy webetu
$proxy = 'tcp://127.0.0.1:8080';
$opts = array(
    'http' => array('proxy' => $proxy, 'request_fulluri' => true),
    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)
);
$context = stream_context_create($opts);

//Geoloc
$clientIP = $_SERVER['REMOTE_ADDR'];
if ($clientIP === '::1' || $clientIP === '127.0.0.1') {
    $clientIP = '';
}

$geoUrl = "http://ip-api.com/xml/{$clientIP}";
$geoXmlStr = @file_get_contents($geoUrl, false, $context);

//Iut par défaut
$lat = 48.6822;
$lon = 6.1611;
$city = "Nancy";

if ($geoXmlStr) {
    $geoXML = simplexml_load_string($geoXmlStr);
    if ($geoXML && $geoXML->status == 'success') {
        $lat = (float)$geoXML->lat;
        $lon = (float)$geoXML->lon;
        $city = (string)$geoXML->city;
    }
}

//Meteo d'infoclimat
//Clef auth, jsp si ça marche aussi pour toi quentin
$authParams = "&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";
$weatherUrl = "https://www.infoclimat.fr/public-api/gfs/xml?_ll={$lat},{$lon}" . $authParams;

$meteoHtml = "";

//Tentative de recup
$weatherXmlStr = @file_get_contents($weatherUrl, false, $context);
if ($weatherXmlStr && strlen($weatherXmlStr) > 0) {
    $weatherXML = new DOMDocument();
    //On essaie de charger le xml
    if (@$weatherXML->loadXML($weatherXmlStr)) {
        //Si le xml est valide, on transforme
        $xslt = new XSLTProcessor();
        $XSL = new DOMDocument();
        $XSL->load('../xsl/meteo.xsl');
        $xslt->importStylesheet($XSL);
        $meteoHtml = $xslt->transformToXML($weatherXML);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?php echo $city; ?></title>
    <link rel="stylesheet" href="../css/meteo.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>
<header style="width:100%; text-align:center;">
    <h1>Info Mobilité : <?php echo $city; ?></h1>
</header>

<section>
    <?php
    if (!empty($meteoHtml)) {
        echo $meteoHtml;
    } else {
        echo "<p>Météo indisponible (API non accessible).</p>";
    }
    ?>
</section>

<section>
    <h2>Trafic Grand Nancy</h2>
    <div id="map"></div>
</section>

<section>
    <h2>Suivi Covid (Eaux Usées)</h2>
    <canvas id="covidChart"></canvas>
</section>

<section id="air-quality">
    <h2>Qualité de l'air</h2>
    <div id="air-data">Chargement...</div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const userLat = <?php echo $lat; ?>;
    const userLon = <?php echo $lon; ?>;
</script>
<script src="../js/app.js"></script>
</body>
</html>