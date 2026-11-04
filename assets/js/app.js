/* Cartes Leaflet / OpenStreetMap — aucune clé API n'est nécessaire. */
(function () {
    'use strict';

    function baseMap(elementId, center, zoom) {
        var map = L.map(elementId).setView(center, zoom);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        return map;
    }

    // Carte de consultation (index.php + fiche) : marqueurs colorés par statut.
    var listMap = document.getElementById('reports-map');
    if (listMap && window.REPORTS_MAP) {
        var map = baseMap('reports-map', [31.79, -7.09], 6); // Centré sur le Maroc.
        var bounds = [];
        window.REPORTS_MAP.forEach(function (r) {
            if (!r.latitude || !r.longitude) return;
            var marker = L.circleMarker([r.latitude, r.longitude], {
                radius: 9, color: r.color, fillColor: r.color, fillOpacity: 0.85, weight: 2
            }).addTo(map);
            marker.bindPopup('<strong>' + r.title + '</strong><br>' + r.status_label +
                '<br><a href="signalement.php?id=' + r.id + '">Voir le signalement</a>');
            bounds.push([r.latitude, r.longitude]);
        });
        if (bounds.length) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
    }

    // Carte de dépôt (signaler.php) : un clic place le marqueur et remplit lat/lng.
    var picker = document.getElementById('picker-map');
    if (picker) {
        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');
        var pickMap = baseMap('picker-map', [31.79, -7.09], 6);
        var marker = null;
        function place(lat, lng) {
            if (marker) pickMap.removeLayer(marker);
            marker = L.marker([lat, lng]).addTo(pickMap);
            latInput.value = lat.toFixed(6);
            lngInput.value = lng.toFixed(6);
        }
        pickMap.on('click', function (e) { place(e.latlng.lat, e.latlng.lng); });
        var geoBtn = document.getElementById('geolocate-btn');
        if (geoBtn && navigator.geolocation) {
            geoBtn.addEventListener('click', function () {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    pickMap.setView([pos.coords.latitude, pos.coords.longitude], 15);
                    place(pos.coords.latitude, pos.coords.longitude);
                });
            });
        }
    }

    // Carte d'un seul signalement (fiche).
    var single = document.getElementById('single-map');
    if (single && window.SINGLE_REPORT) {
        var r = window.SINGLE_REPORT;
        var singleMap = baseMap('single-map', [r.latitude, r.longitude], 15);
        L.marker([r.latitude, r.longitude]).addTo(singleMap)
            .bindPopup(r.title).openPopup();
    }
})();
