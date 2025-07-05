<?php
// Get latitude and longitude from query parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : 0;
$zoom = isset($_GET['zoom']) ? intval($_GET['zoom']) : 2;
$marker_title = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : 'Location Marker';

// Validate coordinates
if ($lat < -90 || $lat > 90) {
    $lat = 0;
}
if ($lng < -180 || $lng > 180) {
    $lng = 0;
}
if ($zoom < 1 || $zoom > 18) {
    $zoom = 2;
}

// Get additional parameters for customization
$show_marker = isset($_GET['marker']) ? $_GET['marker'] === 'true' : true;
$show_popup = isset($_GET['popup']) ? $_GET['popup'] === 'true' : true;
$map_height = isset($_GET['height']) ? intval($_GET['height']) : 400;
$tile_layer = isset($_GET['tiles']) ? $_GET['tiles'] : 'osm';

// Validate map height
if ($map_height < 200 || $map_height > 1000) {
    $map_height = 400;
}

// Define tile layer options
$tile_layers = [
    'osm' => [
        'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>',
        'maxZoom' => 19
    ],
    'satellite' => [
        'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        'attribution' => '&copy; <a href="https://www.esri.com/">Esri</a>',
        'maxZoom' => 18
    ],
    'terrain' => [
        'url' => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
        'maxZoom' => 17
    ]
];

// Select tile layer
$selected_tiles = isset($tile_layers[$tile_layer]) ? $tile_layers[$tile_layer] : $tile_layers['osm'];

// Create location info for display
$location_info = [
    'lat' => $lat,
    'lng' => $lng,
    'zoom' => $zoom,
    'title' => $marker_title,
    'coordinates_display' => number_format($lat, 6) . ', ' . number_format($lng, 6)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Dynamic Leaflet Map - <?php echo htmlspecialchars($marker_title); ?></title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin="" />
    
    <style>
        html, body {
            height: 100%;
            padding: 0;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header .info {
            opacity: 0.9;
            font-size: 14px;
            margin: 0;
        }
        
        .container {
            display: flex;
            height: calc(100vh - 70px);
        }
        
        .sidebar {
            width: 300px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-y: auto;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        
        .info-card p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .coordinates {
            font-family: 'Courier New', monospace;
            background: #e9ecef;
            padding: 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #495057;
        }
        
        .usage-examples {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .usage-examples h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
            font-size: 16px;
        }
        
        .usage-examples code {
            background: #fff;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 12px;
            color: #c7254e;
            word-break: break-all;
        }
        
        .usage-examples .example {
            margin: 8px 0;
            font-size: 12px;
            line-height: 1.4;
        }
        
        #map {
            flex: 1;
            height: 100%;
            min-height: <?php echo $map_height; ?>px;
        }
        
        .leaflet-popup-content {
            text-align: center;
            min-width: 120px;
        }
        
        .leaflet-popup-content h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 16px;
        }
        
        .leaflet-popup-content .coords {
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px 8px;
            border-radius: 4px;
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                max-height: 200px;
            }
            #map {
                height: calc(100vh - 270px);
            }
        }
    </style>
</head>
<body>

    
    <div class="container">
        <div class="sidebar">
           
    
        
        <div id="map"></div>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>

    <script>
        // PHP variables passed to JavaScript
        const mapData = {
            lat: <?php echo json_encode($lat); ?>,
            lng: <?php echo json_encode($lng); ?>,
            zoom: <?php echo json_encode($zoom); ?>,
            title: <?php echo json_encode($marker_title); ?>,
            showMarker: <?php echo json_encode($show_marker); ?>,
            showPopup: <?php echo json_encode($show_popup); ?>,
            tileUrl: <?php echo json_encode($selected_tiles['url']); ?>,
            attribution: <?php echo json_encode($selected_tiles['attribution']); ?>,
            maxZoom: <?php echo json_encode($selected_tiles['maxZoom']); ?>
        };

        // Initialize the map
        console.log('🗺️ Initializing map with data:', mapData);
        
        const map = L.map('map').setView([mapData.lat, mapData.lng], mapData.zoom);

        // Add the selected tile layer
        L.tileLayer(mapData.tileUrl, {
            maxZoom: mapData.maxZoom,
            attribution: mapData.attribution
        }).addTo(map);

        // Add scale control
        L.control.scale({
            position: 'bottomleft'
        }).addTo(map);

        // Add marker if enabled
        if (mapData.showMarker) {
            const marker = L.marker([mapData.lat, mapData.lng]).addTo(map);
            
            if (mapData.showPopup) {
                const popupContent = `
                    <div class="popup-content">
                        <h4>${mapData.title}</h4>
                        <p>📍 Latitude: ${mapData.lat}</p>
                        <p>🌐 Longitude: ${mapData.lng}</p>
                        <div class="coords">${mapData.lat.toFixed(6)}, ${mapData.lng.toFixed(6)}</div>
                    </div>
                `;
                
                marker.bindPopup(popupContent).openPopup();
            }
        }

        // Add click event to show coordinates
        map.on('click', function(e) {
            const { lat, lng } = e.latlng;
            console.log(`Clicked at: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
            
            // Create URL with new coordinates
            const newUrl = `${window.location.pathname}?lat=${lat.toFixed(6)}&lng=${lng.toFixed(6)}&zoom=${map.getZoom()}&title=Clicked Location`;
            console.log('New URL:', newUrl);
            
            // Optional: You can add a temporary popup on click
            L.popup()
                .setLatLng(e.latlng)
                .setContent(`
                    <div style="text-align: center;">
                        <strong>Clicked Location</strong><br>
                        <small>${lat.toFixed(6)}, ${lng.toFixed(6)}</small><br>
                        <a href="${newUrl}" style="color: #667eea; text-decoration: none; font-size: 12px;">
                            📍 Navigate here
                        </a>
                    </div>
                `)
                .openOn(map);
        });

        // Log current URL parameters for debugging
        console.log('📍 Current coordinates:', mapData.lat, mapData.lng);
        console.log('🔍 Zoom level:', mapData.zoom);
        console.log('🏷️ Title:', mapData.title);
        
        // Update URL when map is moved (optional)
        map.on('moveend', function() {
            const center = map.getCenter();
            const zoom = map.getZoom();
            console.log(`Map moved to: ${center.lat.toFixed(6)}, ${center.lng.toFixed(6)} (zoom: ${zoom})`);
        });
    </script>
</body>
</html>