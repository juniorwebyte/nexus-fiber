<!-- Import Leaflet and Leaflet-KMZ -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-kmz@latest/dist/leaflet-kmz.js"></script>

<div class="panel" style="padding: 10px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h2 style="margin: 0;">Rota do Projeto MUBI</h2>
        <a href="<?= BASE_URL ?>/mapa/upload" class="btn" style="padding: 8px 15px; font-size: 0.9em;">Subir outro MUBI</a>
    </div>
    
    <div id="map" style="height: 600px; width: 100%; border-radius: 8px; background: #eee; z-index: 1;"></div>
</div>

<script>
    var map = L.map('map').setView([-15.793889, -47.882778], 5); // Base inicial Brasil
    
    // Camada OpenStreetMap Gratuita
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    var isGeojson = <?= json_encode($isGeojson) ?>;
    var fileUrl = '<?= $fileUrl ?>';
    
    if (isGeojson) {
        // MUBI DWG foi convertida para GeoJSON pelo Backend
        fetch(fileUrl)
            .then(res => res.json())
            .then(data => {
                var layer = L.geoJSON(data, {
                    style: function (feature) {
                        return {color: '#06b6d4', weight: 4};
                    },
                    onEachFeature: function (feature, layer) {
                        if (feature.properties && feature.properties.tipo) {
                            var content = "<b>" + feature.properties.tipo + "</b><br>" + (feature.properties.capacidade || feature.properties.nome || '');
                            if (feature.geometry.type === 'Point') {
                                var lat = feature.geometry.coordinates[1];
                                var lon = feature.geometry.coordinates[0];
                                content += "<div style='margin-top: 10px; padding-top: 10px; border-top: 1px solid #ccc;'>";
                                content += "<a href='https://waze.com/ul?ll="+lat+","+lon+"&navigate=yes' target='_blank' style='display:block; margin-bottom:5px; background:var(--primary); color:white; padding:5px; text-align:center; text-decoration:none; border-radius:4px;'>🚘 Navegar com Waze</a>";
                                content += "<a href='https://www.google.com/maps/dir/?api=1&destination="+lat+","+lon+"' target='_blank' style='display:block; background:#06b6d4; color:white; padding:5px; text-align:center; text-decoration:none; border-radius:4px;'>📍 Abrir no Google Maps</a>";
                                content += "</div>";
                            }
                            layer.bindPopup(content);
                        }
                    }
                }).addTo(map);
                
                var bounds = layer.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds);
                }
            });
    } else {
        // Projeto nativo KMZ / KML
        var kmzParser = L.kmzLayer().addTo(map);
        
        kmzParser.on('load', function(e) {
            var control = L.control.layers(null, null, { collapsed:false }).addTo(map);
            control.addOverlay(e.layer, e.name);
            
            e.layer.eachLayer(function(layer) {
                if (layer.feature && layer.feature.geometry && layer.feature.geometry.type === 'Point') {
                    var content = layer.getPopup() ? layer.getPopup().getContent() : "Ponto do Projeto";
                    var lat = layer.feature.geometry.coordinates[1];
                    var lon = layer.feature.geometry.coordinates[0];
                    content += "<div style='margin-top: 10px; padding-top: 10px; border-top: 1px solid #ccc;'>";
                    content += "<a href='https://waze.com/ul?ll="+lat+","+lon+"&navigate=yes' target='_blank' style='display:block; margin-bottom:5px; background:var(--primary); color:white; padding:5px; text-align:center; text-decoration:none; border-radius:4px;'>🚘 Navegar com Waze</a>";
                    content += "<a href='https://www.google.com/maps/dir/?api=1&destination="+lat+","+lon+"' target='_blank' style='display:block; background:#06b6d4; color:white; padding:5px; text-align:center; text-decoration:none; border-radius:4px;'>📍 Abrir no Google Maps</a>";
                    content += "</div>";
                    layer.bindPopup(content);
                }
            });
            
            var bounds = e.layer.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds);
            }
        });
        
        kmzParser.load(fileUrl);
    }
</script>
