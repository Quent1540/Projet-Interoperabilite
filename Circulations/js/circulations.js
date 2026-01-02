document.addEventListener('DOMContentLoaded', async () => { // Changé en async pour meilleure gestion
    //On init une carte en utilisant leaflet
    const map = L.map('map').setView([48.69, 6.18], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '&copy; OSM'}).addTo(map);

    try {
        //Localisation de l'utilisateur en utilisant ip-api.com
        const u = await fetch('https://ip-api.com/json/').then(r => r.json()).catch(() => null);
        const lat = u?.lat || 48.6822;
        const lon = u?.lon || 6.1611;
        const city = u?.city || "Nancy";

        document.querySelector('#météo').textContent = `Météo de ${city}`;
        map.setView([lat, lon], 15);
        L.marker([lat, lon]).addTo(map).bindPopup("Vous êtes ici").openPopup();

        //On utilisise l'API Infoclimat afin d'obtenir la météo de Nancy(dur d'obtenir la météo de la position de l'utilisateur car le lien de l'API Infoclimat contient déjà des coordonnées et ça ne marche plus en les modifiant)
        const urlInfoclimat = "https://www.infoclimat.fr/public-api/gfs/xml?_ll=48.67103,6.15083&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";

        //On récupère la météo(vent, pluie, température) depuis Infoclimat en XML et la qualité de l'air(pollution) depuis Open Meteo en format JSON
        const [txt, air] = await Promise.all([
            fetch(urlInfoclimat).then(r => r.text()), // XML
            fetch(`https://air-quality-api.open-meteo.com/v1/air-quality?latitude=${lat}&longitude=${lon}&current=european_aqi`).then(r => r.json())
        ]);

        const xml = new DOMParser().parseFromString(txt, "text/xml").querySelector("echeance");
        //L'api Infoclimat contient les températures en Kelvin, on doit donc convertir en Celsius et arrondir
        const temp = (xml.querySelector("temperature level[val='sol']").textContent - 273.15).toFixed(1);
        const pluie = parseFloat(xml.querySelector("pluie").textContent);
        const vent = parseFloat(xml.querySelector("vent_moyen level[val='10m']").textContent);
        const aqi = air.current.european_aqi;

        const div = document.getElementById('conseil');
        let msg = "Conditions favorables", bg = "#d4edda";

        if (pluie > 0.5 || vent > 40) {msg = "☔/💨 Vélo non conseillé"; bg = "#fff3cd";}
        if (aqi > 50) {
            msg += " / Pollution élevée"; bg = "#f8d7da";
        }

        div.style.background = bg;
        div.innerHTML = `${msg} <br><small>Température:${temp}°C | ☔:${pluie}mm | 💨:${vent}km/h | Indice de qualité de l'air:${aqi}</small>`;

    } catch (error) {
        console.error("Erreur météo:", error);
        document.getElementById('conseil').innerHTML = "Erreur de chargement des données météo";
    }

    try {
        //on récupère les vélos en libre service à Nancy depuis l'API Cyclocity
        const root = await fetch("https://api.cyclocity.fr/contracts/nancy/gbfs/gbfs.json").then(r => r.json());
        const f = root.data.fr.feeds;
        const [info, st] = await Promise.all([
            fetch(f.find(x => x.name === "station_information").url).then(r => r.json()),
            fetch(f.find(x => x.name === "station_status").url).then(r => r.json())
        ]);
        const mapSt = Object.fromEntries(st.data.stations.map(s => [s.station_id, s]));

        info.data.stations.forEach(s => {
            const stat = mapSt[s.station_id];
            if(stat) {
                const IconeVelo = L.divIcon({
                    html: `<div style="font-size:25px">🚲</div>`,
                    className: 'bike-marker',
                    iconSize: [30, 30],
                    iconAnchor: [15, 30]
                });
                L.marker([s.lat, s.lon], {icon: IconeVelo}).addTo(map).bindPopup(`<b>${s.name}</b><br>Vélos libres : ${stat.num_bikes_available}<br>Places libres : ${stat.num_docks_available}`);
            }
        });
    } catch (error) {
        console.error("Erreur dans la récupération des informations concernant les vélos:", error);
    }
});
