<?php
//Configs pour la loc
//Local ou iut
$whitelist = array('127.0.0.1', '::1');
$isLocal = in_array($_SERVER['REMOTE_ADDR'], $whitelist);

//Options de base
$opts = array(
        'http' => array(
                'method' => "GET",
            //Pour éviter les erreurs 400/403 sur le ArcGIS de fznefzi
                'header' => "User-Agent: ProjetEtudiant/1.0\r\n"
        ),
        'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
        )
);

//Si on est à l'iut (= pas en local), on ajoute le proxy aux options existantes
if (!$isLocal) {
    $proxy = 'tcp://www-cache.iutnc.univ-lorraine.fr:3128';
    $opts['http']['proxy'] = $proxy;
    $opts['http']['request_fulluri'] = true;
}
$context = stream_context_create($opts);

//Geoloc
$clientIP = $_SERVER['REMOTE_ADDR'];
if ($isLocal) $clientIP = '';

$geoUrl = "http://ip-api.com/xml/{$clientIP}";
//@ pour éviter les erreurs visuelles
$geoXmlStr = @file_get_contents($geoUrl, false, $context);

//Iut par dfaut
$lat = 48.6822;
$lon = 6.1611;
$ville = "Nancy";

if ($geoXmlStr) {
    $geoXml = simplexml_load_string($geoXmlStr);
    if ($geoXml && $geoXml->status == 'success') {
        $lat = (float)$geoXml->lat;
        $lon = (float)$geoXml->lon;
        $ville = (string)$geoXml->city;
    }
}

//Météo
$weatherUrl = "https://www.infoclimat.fr/public-api/gfs/xml?_ll=48.67103,6.15083&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";
$meteoHtml = "";
$weatherXmlStr = @file_get_contents($weatherUrl, false, $context);

if ($weatherXmlStr && strlen($weatherXmlStr) > 0) {
    $weatherXML = new DOMDocument();
    if (@$weatherXML->loadXML($weatherXmlStr)) {
        //Transformation xslt
        $xslt = new XSLTProcessor();
        $XSL = new DOMDocument();
        if (file_exists('Atmosphere/public/xsl/meteo.xsl')) {
            $XSL->load('Atmosphere/public/xsl/meteo.xsl');
            $xslt->importStylesheet($XSL);
            $meteoHtml = $xslt->transformToXML($weatherXML);
        } else {
            $meteoHtml = "<p>Erreur : Fichier meteo.xsl introuvable</p>";
        }
    }
}

//Qualité air bhqhuxwbkjl

$params = array(
        'where'             => "code_zone='54395'",
        'outFields'         => 'lib_qual,date_ech,code_qual',
        'orderByFields'     => 'date_ech DESC',
        'resultRecordCount' => 1,
        'f'                 => 'json'
);

$queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

$baseUrl = "https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest_4j/FeatureServer/0/query";
$airUrl = $baseUrl . "?" . $queryString;

$optsAir = $opts;
if (isset($optsAir['http']['request_fulluri'])) {
    unset($optsAir['http']['request_fulluri']);
}
$contextAir = stream_context_create($optsAir);

$airData = @file_get_contents($airUrl, false, $contextAir);
$airInfo = null;
$airError = "";

if ($airData === false) {
    $error = error_get_last();
    $airError = "Erreur connexion : " . $error['message'];
} else {
    $json = json_decode($airData);
    if ($json === null) {
        $airError = "Erreur lecture JSON.";
    } elseif (isset($json->error)) {
        $airError = "Erreur ArcGIS " . $json->error->code . " : " . $json->error->message;
    } elseif (isset($json->features[0]->attributes)) {
        $airInfo = $json->features[0]->attributes;
    } else {
        $airError = "Aucune donnée trouvée pour Nancy (code 54395)";
    }
}

//Pour la couleur de la qualité
function getAirColor($qualite) {
    switch($qualite) {
        case 'Bon': return '#50f0e6';
        case 'Moyen': return '#50ccaa';
        case 'Dégradé': return '#f0e641';
        case 'Mauvais': return '#ff5050';
        default: return '#ccc';
    }
}
//Html
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - <?php echo $ville; ?></title>
    <link rel="stylesheet" href="Atmosphere/public/css/atmosphere.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>
<header style="width:100%; text-align:center;">
    <h1>Info mobilité : <?php echo $ville; ?></h1>
</header>

<section>
    <?php
    if (!empty($meteoHtml)) {
        echo $meteoHtml;
    } else {
        echo "<p>Météo indisponible (erreur connexion ou api).</p>";
    }
    ?>
</section>

<section>
    <h2>Trafic Grand Nancy</h2>
    <div id="map"></div>
</section>

<section>
    <h2>Suivi Covid (eaux usées)</h2>
    <canvas id="covidChart"></canvas>
</section>

<section id="air-quality">
    <h2>Qualité de l'air (Nancy)</h2>
    <?php if ($airInfo): ?>
        <div style="background-color: <?php echo getAirColor($airInfo->lib_qual); ?>; padding:15px; border-radius:8px; text-align:center; font-weight:bold; color: white;">
            Indice : <?php echo $airInfo->lib_qual; ?> <br>
            <small>Date : <?php echo date('d/m/Y', $airInfo->date_ech / 1000); ?></small>
        </div>
    <?php else: ?>
        <p style="color:red; font-weight:bold;">Problème technique :</p>
        <p style="font-size:0.8em; word-wrap: break-word;"><?php echo $airError; ?></p>
    <?php endif; ?>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const userLat = <?php echo $lat; ?>;
    const userLon = <?php echo $lon; ?>;
</script>
<script src="Atmosphere/public/js/app.js"></script>

<footer style="margin-top: 30px; padding: 20px; background: #f0f0f0; text-align: center; font-size: 0.8em; border-top: 1px solid #ccc; width: 100%;">
    <p><strong>Sources des données :</strong></p>
    <ul style="list-style: none; padding: 0;">
        <li>Lien Github du projet : <a href="https://github.com/Quent1540/Projet-Interoperabilite" target="_blank">Projet Interop</a></li>
        <li>Géolocalisation : <a href="https://ip-api.com/docs/" target="_blank">ip-api.com</a></li>
        <li>Météo : <a href="https://www.infoclimat.fr/public-api/gfs/xml?_ll=48.67103,6.15083&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2" target="_blank">Infoclimat</a></li>
        <li>Qualité de l'air : <a href="https://www.atmo-grandest.eu/" target="_blank">Atmo Grand Est (ArcGIS)</a></li>
        <li>Covid-19 : <a href="https://odisse.santepubliquefrance.fr/api/explore/v2.1/catalog/datasets/sum-eau-indicateurs/records?where=commune%3D%22NANCY%22&limit=50" target="_blank">Santé Publique France (Odissé)</a></li>
        <li>Fond de carte : <a href="https://www.openstreetmap.org/" target="_blank">OpenStreetMap</a></li>
    </ul>
    <p>CADET Mattéo - DIEUDONNE Quentin</p>
</footer>
</body>
</html>