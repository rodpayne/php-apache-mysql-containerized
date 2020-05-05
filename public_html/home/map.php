<?php require('../include/check_session_login.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Map</title>
	<?php require('../include/app_standard_head.html'); ?>	
    <script src="https://api.mqcdn.com/sdk/mapquest-js/v1.3.1/mapquest.js"></script>
    <script src="/js/LeafletHtmlIcon.js"></script>
    <link type="text/css" rel="stylesheet" href="https://api.mqcdn.com/sdk/mapquest-js/v1.3.1/mapquest.css"/>
</head>

<body onLoad='javascript:MapOnPageLoad()'>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php include('../include/startup_spinner.html'); ?> 
<div class="info clearfix" style="position:relative;height:100%;">
    <div id="MapLink" style="display: none; padding: 2vh 2vw;" class="closebox">
        <button type="button" class="close" onClick="document.getElementById('mapLink').style.display='none';MapOnResize();"><span>&times;</span></button>
        <p>Google map does not seem to work when saved to home screen.</p>
        <a href="https://calendar.google.com/calendar/embed?src=r17fncds7rvobkbq38snotbf0s%40group.calendar.google.com&ctz=America%2FDenver">Try this link if the map is not displayed below.</a>
    </div>
    <div id="map" style="height:100%;width:618px;float:left;margin-right:3vw;border-right: 2px solid black;"></div>
    <div class="container" style="padding-top:20px;">
        <?php $show_mapping = TRUE; include('../include/person_status_badges.php'); ?>
        <div id="DeviceAlert" style='display: none; width: 95%;'></div>
    </div>
</div>
<style>
body {
	overflow: hidden;
}
</style>
<?php
//-----------------------------------------------------------------------------
//	Javascript page functions:
//-----------------------------------------------------------------------------
?>
<script>
<?php
    $mapCenter = '';
    if (! empty($_SERVER['QUERY_STRING'])) {
        $requestString=$_SERVER['QUERY_STRING'];
        if (preg_match('/^center=(.*)[&?]/',$requestString, $matches)) {
            $mapCenter = strtolower($matches[1]);
        } elseif (preg_match('/^center=(.*)/',$requestString, $matches)) {
            $mapCenter = strtolower($matches[1]);
        }
    }
    echo ("var mapCenter = '" . $mapCenter . "';");
    echo ("console.log('mapCenter = ' + mapCenter);");
?>
var mapLocations = [];
var mapLocationsHTML = [];
var map;

function MapOnPageLoad() {
	window.addEventListener('resize', MapOnResize);
	window.addEventListener('orientationchange', MapOnResize);

/*	var isStandalone = 'standalone' in window.navigator && window.navigator.standalone;
	if (isStandalone) {
		document.getElementById('MapLink').style.display = 'block'
	} 
  */  
  
  	var mapFrame = document.getElementById('map');
	mapFrame.style.height = window.innerHeight - mapFrame.offsetTop + 'px';

    L.mapquest.key = 'JreAk2KNXfewR7fi64vGWMdRSwaTcPNh';
    L.mapquest.open = true;

    map = L.mapquest.map('map', {
      center: [40.1364486, -111.6076158],
      layers: L.mapquest.tileLayer('map'),
      zoom: 14
    });

    SizeTheMap();
/*    
    const getCircularReplacer = () => {
        const seen = new WeakSet;
        return (key, value) => {
            if (typeof value === "object" && value !== null) {
                if (seen.has(value)) {
                    return;
                }
                seen.add(value);
            }
            return value;
        };
    };

    console.log('mapLocations');
    console.log(JSON.stringify(mapLocations, getCircularReplacer()));
    
    console.log("mapLocations['Rod']");
    console.log(JSON.stringify(mapLocations['Rod'], getCircularReplacer()));
    
    console.log("mapLocations['Test']");
    console.log(JSON.stringify(mapLocations['Test'], getCircularReplacer()));
*/    
    <?php include('../include/startup_spinner_done.js'); ?> 
	
	MapOnResize()
}

/* OnResize event is debounced - see http://bencentra.com/code/2015/02/27/optimizing-window-resize.html */

var resizeTimeout = false;
var resizeDelay = 1000; 

function MapOnResize() {
	clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(MapActualOnResize, resizeDelay);
}

function MapActualOnResize() {
    SizeTheMap();
}

function SizeTheMap() {
    console.log("SizeTheMap");
  	var mapFrame = document.getElementById('map');
	mapFrame.style.height = window.innerHeight - mapFrame.offsetTop + 'px';
    var mapWidth = window.innerWidth - 100;
    if (mapWidth > 800) {
        mapWidth = 800;
    }
	mapFrame.style.width = mapWidth + 'px';
    
    map.invalidateSize();
}

</script>
<style>
.leaflet-control-zoom-in:not(.leaflet-touch), .leaflet-control-zoom-out:not(.leaflet-touch) {
    padding: 0;
}
</style>
</body>
</html>