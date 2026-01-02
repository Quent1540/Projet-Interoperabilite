document.addEventListener('DOMContentLoaded', () => {
    //Init de la carte
    const map = L.map('map').setView([userLat, userLon], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([userLat, userLon]).addTo(map)
        .bindPopup("Vous êtes ici").openPopup();

    //Trafic à refaire

    //Graphique covid STATIQUE pour le moment
    const ctx = document.getElementById('covidChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['S40', 'S41', 'S42', 'S43'],
            datasets: [{
                label: 'Charge virale (Nancy)',
                data: [12, 19, 3, 5],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        }
    });
});