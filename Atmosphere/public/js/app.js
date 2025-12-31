document.addEventListener('DOMContentLoaded', () => {
    //Init de la carte via Leaflet
    //C'est centré sur les coos recup du php
    const map = L.map('map').setView([userLat, userLon], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    //Position de l'utilisateur
    L.marker([userLat, userLon]).addTo(map)
        .bindPopup("Vous êtes ici (ou IUT)").openPopup();

    //Trafic
    //Il faut trouver l'URL GeoJSON sur data.grandest.fr pour "Trafic temps réel" ou "Chantiers"
    const trafficUrl = 'url api trafic du grandest';

    fetch(trafficUrl)
        .then(res => res.json())
        .then(data => {
            //Si c'est du GeoJSON
            L.geoJSON(data, {
                onEachFeature: (feature, layer) => {
                    if(feature.properties) {
                        layer.bindPopup(`<b>Info:</b> ${feature.properties.description}`);
                    }
                },
                style: { color: 'red' } //Couleur des lignes de trafic
            }).addTo(map);
        })
        .catch(err => console.log("Erreur chargement trafic", err));

    //Graphique du Covid (Chart.js)
    //Données du Sras dans les égoûts
    const ctx = document.getElementById('covidChart').getContext('2d');

    //Ex de données statiques pour tester le graphique, faudra faire un fetch vers l'api data.gouv
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Semaine 1', 'Semaine 2', 'Semaine 3', 'Semaine 4'],
            datasets: [{
                label: 'Charge virale (Eaux usées - Nancy/Maxéville)',
                data: [12, 19, 3, 5], //Remplacer par données réelles
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        }
    });
});