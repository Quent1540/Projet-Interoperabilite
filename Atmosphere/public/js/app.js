document.addEventListener('DOMContentLoaded', () => {
    //Init de la carte
    const map = L.map('map').setView([userLat, userLon], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([userLat, userLon]).addTo(map)
        .bindPopup("Vous êtes ici").openPopup();

    //Trafic à refaire

    //Graphique Covid
    const ctx = document.getElementById('covidChart').getContext('2d');
    const covidChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], //Les semaines (ex: 2023-S40)
            datasets: [{
                label: 'Indicateur SARS-CoV-2 (Nancy - eaux usées)',
                data: [],
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.2)',
                tension: 0.3,
                fill: true,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                x: { display: true, title: { display: true, text: 'Semaine' } },
                y: { display: true, beginAtZero: true }
            }
        }
    });

    //Récup des données api SUM'Eau
    const covidApiUrl = 'https://odisse.santepubliquefrance.fr/api/explore/v2.1/catalog/datasets/sum-eau-indicateurs/records?where=commune%3D%22NANCY%22&limit=50';
    fetch(covidApiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.results && data.results.length > 0) {
                const records = data.results;
                const labels = [];
                const valeurs = [];

                records.forEach(record => {
                    const datePoint = record.semaine || record.date || record.date_semaine;
                    const valeurPoint = record.indicateur || record.valeur || record.mesure;

                    //On ajoute que si on a bien une date et une valeur
                    if (datePoint && valeurPoint !== undefined) {
                        labels.push(datePoint);
                        valeurs.push(valeurPoint);
                    }
                });

                //Maj graphique
                covidChart.data.labels = labels;
                covidChart.data.datasets[0].data = valeurs;
                covidChart.update();
            } else {
                console.warn("Covid : Aucune donnée trouvée pour Nancy.");
            }
        })
        .catch(error => {
            console.error("Erreur chargement données Covid :", error);
        });
});