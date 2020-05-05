<?php require('../include/check_session_login.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Weather</title>
	<?php require('../include/app_standard_head.html'); ?>
	<style>
		html { -webkit-text-size-adjust: 100%; /* Prevent font scaling in landscape */ }
		button { cursor: pointer; border-radius: 8px; }
		td { padding-top:0; padding-bottom:0; }
	
		.summary { font-size: 32px; font-weight: 600; }
		@media screen and (max-width: 600px) {
			.summary { font-size: 24px; font-weight: 600; }
		}
		.shadow { /* box-shadow: 10px 10px 10px black; */ margin: 10px; }
	
		#currentInfo-container { position: relative; left: -2px; }
		#map-container { position: relative; left: 1px }
		#refresh-map { position: absolute; top: 8px; left: 8px; }
	</style>
	<style>
		html {
			-ms-touch-action: none;
		}
		body {
			/* On modern browsers, prevent the whole page to bounce */
			overflow: hidden;
		}
		.wrapper {
			position: relative;
			width: 100%;
			height: 100px;
			overflow: hidden;
	
			/* Prevent native touch events on Windows */
			-ms-touch-action: none;

			/* Prevent the callout on tap-hold and text selection */
			-webkit-touch-callout: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;

			/* Prevent text resize on orientation change, useful for web-apps */
			-webkit-text-size-adjust: none;
			-moz-text-size-adjust: none;
			-ms-text-size-adjust: none;
			-o-text-size-adjust: none;
			text-size-adjust: none;
		}
		.slider {
			position: absolute;
			cursor: grab;

			/* Prevent elements to be highlighted on tap */
			-webkit-tap-highlight-color: rgba(0,0,0,0);

			/* Put the scroller into the HW Compositing layer right from the start */
			-webkit-transform: translateZ(0);
			-moz-transform: translateZ(0);
			-ms-transform: translateZ(0);
			-o-transform: translateZ(0);
			transform: translateZ(0);
		}
	</style>
</head>
<body onLoad='javascript:WeatherOnPageLoad()'>
<?php include("../include/startup_spinner.html"); ?> 
<div id="WeatherInfo" style='display:none'>
	<table width='100%'>
		<tr class='vat'><td id='currentInfo-container'>
		<div id="LeftSide">
<?php include("../include/header_dropdown_menu.php"); ?>
			<table id='currentInfo' class='info shadow border'>
				<tr>
					<td colspan='2'>
						<div style='float: left'><canvas id='currentIcon' width='128' height='128' style='width:128;height:128;margin:2vh 2vw -1vh 2vw;'></canvas></div>
						<div id='summary' class='center-text center'>
							<div style='margin:1vh 1vw;' class='summary'> <span id='currentTemp'></span>˚ <span id='currentSummary'></span></div>
							<div style='margin:1vh 1vw;' id='hourlySummary'></div>
						</div>
						<div class='details center-text' id='dailySummary'></div>
					</td>
				</tr><tr>
					<td colspan='2'>
						<div id='WeatherAlertContainer'><span id='WeatherAlert'></span></div>
						<table width='100%'>
							<tr class='vat'>
								<td>Sunrise:</td><td><span id='todaySunriseTime'></span>&nbsp;</td>
								<td>Wind: <span id='currentWindSpeed'></span> mph&nbsp;&nbsp;&nbsp;<span id='direction'>↑</span></td>
								<td id='currentInfo-opt1'>Gusts: <span id='currentWindGust'></span> mph</td>
							</tr><tr class='vat'>
								<td>Sunset:</td><td><span id='todaySunsetTime'></span>&nbsp;</td>
								<td>Humidity: <span id='currentHumidity'></span>%</td>
								<td  id='currentInfo-opt2'>Wind chill: <span id='apparentTemperature'></span>˚</td>
							</tr>
						</table>
					</td>
				</tr><tr>
					<td colspan='2' align='center'>
						<div id='minutelyForecast'></div>
						<a id='poweredby' href='https://darksky.net/poweredby/'><img src='poweredby-oneline.png' style='height: 2rem;'/></a>
					</td>
				</tr>
			</table>
			<!--- blue box ---><table id='currentTime-container' class='info-reverse shadow' width='100%'>
				<tr><td class='center-text' width='100%'><div id=currentTime class='summary' style='padding-top:5px;padding-bottom:5px;'> </div></td></tr></table>
		</div></td>
		<!-- weather map --><td id='map-container'>	
			<iframe class='info' id='embedded-map' height='200' width='400' style='border:none;' src='about:blank'></iframe>
		<button id="refresh-map" onclick="var iframe = document.getElementById('embedded-map'); iframe.src = iframe.src;">
			<i class="fas fa-redo"></i>
		</button>
		</td></tr>
	</table>
	<div id='hourlyForecast-wrapper' class='shadow wrapper' style='width: 100%;'><div id='hourlyForecast' class='slider'></div></div>
	<div id='dailyForecast-wrapper' class='shadow wrapper' style='width: 100%;'><div id='dailyForecast' class='slider'></div></div>
	<div id='diag'></div>
</div>
<?PHP
//-----------------------------------------------------------------------------
//	Get house configuration info (if not already loaded):
//-----------------------------------------------------------------------------

	if (! isset($houseInfo)) {
		$houseInfo = Get_Configuration_Item($houseID,'houseInfo');
	}
	if (! $houseInfo) {
			echo "Error: House configuration info is not available. \n";
			exit;		
	}
?>
<?php require('../include/weather-alert-scripts.php'); ?>

<script>
var skyconsCurrent;
var skyconsHourly;
var skyconsDaily;
<!-- Scrolling -->
var myScrollHourlyForecast;
var myScrollDailyForecast;

var previousCurrentlyTime = null;

function RetrieveWeatherData() {
	var now = new Date();
	document.getElementById('currentTime').innerHTML = formatDate(now,'dddd h:mm tt');

	$.ajax({
		url: '/service/weather-service.php',
		cache: false,
		data: {
			latitude: weatherSelectedLatitude,
			longitude: weatherSelectedLongitude
		},
		success: function( result ) {
			if (result === undefined) {
				return;
			}
			result = result.trim();
			if (result.slice(0,1) !== "{") {							/* result may be a PHP error message instead of JSON */
				document.getElementById("WeatherAlert").innerHTML = "<p style='color:var(--color-red);'>" + result + "</p>";
				document.getElementById("WeatherAlertContainer").style.display = "block";
				return;
			} else {
				document.getElementById("WeatherAlert").innerHTML = "";
				document.getElementById("WeatherAlertContainer").style.display = "none";
			}
			
			weatherAppInfo = JSON.parse(result);

			var currentlyTime = new Date(weatherAppInfo['currently']['time'] * 1000);
			var currentlyTimeString = formatDate(currentlyTime,'h:mm:ss tt');
			if (currentlyTimeString == previousCurrentlyTime) {
				console.log('weather.php - weather info unchanged');
				return;
			} else {
				console.log('weather.php - weather info changed - ' + currentlyTimeString);
				previousCurrentlyTime = currentlyTimeString;
			}
			
	/* Format Current Conditions */
	
			skyconsCurrent = new Skycons({"color": "black"});
			var currentIcon = weatherAppInfo.currently.icon;
			skyconsCurrent.add("currentIcon", currentIcon);
			skyconsCurrent.play();		/* Comment this out if the CPU usage bothers you. */
			document.getElementById('currentTemp').innerHTML = Math.round(weatherAppInfo.currently.temperature);
			document.getElementById('apparentTemperature').innerHTML = Math.round(weatherAppInfo.currently.apparentTemperature);
			document.getElementById('currentSummary').innerHTML = weatherAppInfo.currently.summary;
			document.getElementById('hourlySummary').innerHTML = weatherAppInfo.hourly.summary;
			var strDailySummary = weatherAppInfo.daily.summary;
			document.getElementById('dailySummary').innerHTML = strDailySummary;
			document.getElementById('currentHumidity').innerHTML = Math.round(weatherAppInfo.currently.humidity * 100);
			document.getElementById('currentWindSpeed').innerHTML = Math.round(weatherAppInfo.currently.windSpeed);
			document.getElementById('currentWindGust').innerHTML = Math.round(weatherAppInfo.currently.windGust);

			var currentWindBearing = weatherAppInfo['currently']['windBearing'];
			document.getElementById('direction').outerHTML = "<span id='direction' class='direction' style='display:inline-block;-ms-transform:rotate(" + currentWindBearing + "deg);-webkit-transform:rotate(" + currentWindBearing + "deg);transform:rotate(" + currentWindBearing + "deg);'>↑</span>";

			var todaySunriseTime = new Date(weatherAppInfo['daily']['data'][0]['sunriseTime'] * 1000);
			document.getElementById('todaySunriseTime').innerHTML = formatDate(todaySunriseTime,"h:mm tt");
	
			var todaySunsetTime = new Date(weatherAppInfo['daily']['data'][0]['sunsetTime'] * 1000);
			document.getElementById('todaySunsetTime').innerHTML = formatDate(todaySunsetTime,"h:mm tt");

			SaveWeatherIconAndAlertInfo();
		
	/* Display weather info: */

<?php include('../include/startup_spinner_done.js'); ?> 
	
			document.getElementById('WeatherInfo').style.display = 'block';

			if (typeof weatherAppInfo['flags']['darksky-unavailable'] !== 'undefined') {
				document.getElementById('poweredby').title = weatherAppInfo['flags']['darksky-unavailable'];
			} else {
				document.getElementById('poweredby').title = 'as of ' + formatDate(currentlyTime,'h:mm tt');
			}

			WeatherOnResize();
		}
	});
}

function WeatherOnPageLoad() {
	RetrieveWeatherData();
	window.addEventListener('resize', WeatherOnResize);
	window.addEventListener('orientationchange', WeatherOnResize);
	var weatherTimer = setInterval(RetrieveWeatherData,60000);   /* weather service is throttled for once every 96 seconds */	
}

/* OnResize event is debounced - see http://bencentra.com/code/2015/02/27/optimizing-window-resize.html */

var resizeTimeout = false;
var resizeDelay = 100; 

function WeatherOnResize() {
	clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(WeatherActualOnResize, resizeDelay);
}

function WeatherActualOnResize() {

	if ( typeof weatherAppInfo !== 'undefined' && weatherAppInfo ) {

	/* Format the Hourly Forecast */	
	
		var paddingBorderMargin = 5;

		var hoursToShow = 24; 
		var numberOfFull = Math.floor(window.innerWidth / 80);
		if (numberOfFull < 1) { numberOfFull = 1 } else if (numberOfFull > hoursToShow) { numberOfFull = hoursToShow };
		var sizeOfEach = Math.floor(window.innerWidth / numberOfFull) - paddingBorderMargin;
		var hourlyTableTime = "";
		var hourlyTableTemp = "";
		var hourlyTableIcon = "";
		var hourlyTableSummary = "";
		var lastHourlySummary = weatherAppInfo.hourly.data[1].summary;
		var LastHourlySummaryCount = 0;
	
		for (i=1; i < (hoursToShow + 1); i++) {
			var hourlyTime = new Date(weatherAppInfo.hourly.data[i].time * 1000);
			hourlyTableTime = hourlyTableTime + "<td style='width: " + sizeOfEach + "px' class='left-right'>" + formatDate(hourlyTime,"h tt") + "</td>";
			var hourlyTemp = Math.round(weatherAppInfo.hourly.data[i].temperature);
			hourlyTableTemp = hourlyTableTemp + "<td class='left-right center-text summary'>" + hourlyTemp + "˚</td>";
			hourlyTableIcon = hourlyTableIcon + "<td class='left-right'><canvas class='center' id='hourlyIcon" + i + "' width='32' height='32' xstyle='width:100%;height:100%'></canvas></td>";
			var hourlySummary = weatherAppInfo.hourly.data[i].summary;
			if (hourlySummary == lastHourlySummary) {
				LastHourlySummaryCount = LastHourlySummaryCount + 1;
			} else {
				hourlyTableSummary = hourlyTableSummary + "<td class='border left-right x-center-text' colspan='" + LastHourlySummaryCount + "'>" + lastHourlySummary + "</td>";
				lastHourlySummary = hourlySummary;
				LastHourlySummaryCount = 1;
			}
		}
		if (LastHourlySummaryCount > 0) {
			hourlyTableSummary = hourlyTableSummary + "<td class='border x-center-text' colspan='" + LastHourlySummaryCount + "'>" + lastHourlySummary + "</td>";
		}
		document.getElementById("hourlyForecast").innerHTML = "<table style='table-layout:fixed; width: 100%' class='info border' ><tr>" + hourlyTableTime + "</tr><tr>" + hourlyTableIcon + "</tr><tr>" + hourlyTableTemp + "</tr><tr class='vat'>" + hourlyTableSummary + "</tr></table>";
		skyconsHourly = new Skycons({"color": "black"});
		for (i=1; i < (hoursToShow + 1); i++) {
			skyconsHourly.add("hourlyIcon" + i, weatherAppInfo.hourly.data[i].icon);
		}

		/* skyconsHourly.play();		/* Comment this out if the CPU usage bothers you. */
			
		<!-- Scrolling -->
		document.getElementById("hourlyForecast-wrapper").setAttribute('data-sizeOfEach', sizeOfEach + 3); 
		document.getElementById("hourlyForecast-wrapper").style.height = (document.getElementById("hourlyForecast").offsetHeight + 0) + "px";	
		setTimeout(function () { myScrollHourlyForecast.refresh(); }, 0);
		
	/* Format the Daily Forecast */		

		var daysToShow = 7; 
		var numberOfFull = Math.floor(window.innerWidth / 200); if (numberOfFull < 1) { numberOfFull = 1 } else if (numberOfFull > daysToShow) { numberOfFull = daysToShow };
		var sizeOfEach = Math.floor(window.innerWidth / numberOfFull) - paddingBorderMargin;
		var dailyTableTime = "";
		var dailyTableTemp = "";
		var dailyTableIcon = "";
		var dailyTableSummary = "";
		for (i=0; i < daysToShow; i++) {
			var dailyTime = new Date(weatherAppInfo.daily.data[i].time * 1000);
			dailyTableTime = dailyTableTime + "<td style='width: " + sizeOfEach + "px' class='left-right'>" + formatDate(dailyTime,"dddddddd") + "</td>";
			var dailyTempHigh = Math.round(weatherAppInfo.daily.data[i].temperatureHigh);
			var dailyTempLow = Math.round(weatherAppInfo.daily.data[i].temperatureLow);
			dailyTableTemp = dailyTableTemp + "<td class='left-right center-text summary'>" + dailyTempHigh + "˚/" + dailyTempLow + "˚</td>";
			dailyTableIcon = dailyTableIcon + "<td class='left-right'><canvas class='center' id='dailyIcon" + i + "' width='32' height='32' xstyle='width:100%;height:100%'></canvas></td>";
			var dailySummary = weatherAppInfo.daily.data[i].summary;
			dailyTableSummary = dailyTableSummary + "<td class='left-right center-text border'>" + dailySummary + "</td>";
		}
		document.getElementById("dailyForecast").innerHTML = "<table style='table-layout:fixed; width:100%' class='info border'><tr>" + dailyTableTime + "</tr><tr>" + dailyTableIcon + "</tr><tr>" + dailyTableTemp + "</tr><tr class='vat'>" + dailyTableSummary + "</tr></table>";
			
		skyconsDaily = new Skycons({"color": "black"});
		for (i=0; i < daysToShow; i++) {
			skyconsDaily.add("dailyIcon" + i, weatherAppInfo.daily.data[i].icon);
		}
		/* skyconsDaily.play();		/* Comment this out if the CPU usage bothers you. */
		<!-- Scrolling -->
		document.getElementById("dailyForecast-wrapper").setAttribute('data-sizeOfEach', sizeOfEach + 3); 
		document.getElementById("dailyForecast-wrapper").style.height = document.getElementById("dailyForecast").offsetHeight + "px";	
		setTimeout(function () { myScrollDailyForecast.refresh(); }, 0);
	}
		
	/* Determine width and split between current info and map */
	
	var iframe = document.getElementById("embedded-map");
	var leftWidth;
	
	var innerWidth = window.innerWidth; 
	if (innerWidth < 600) {									/* not enough room to show the map */
		leftWidth = window.innerWidth - 20;
		
		if (iframe.src !== "about:blank") {
			iframe.src = "about:blank";
		}
		document.getElementById("map-container").style.display = "none";
		document.getElementById("currentInfo").width = leftWidth;
		if (innerWidth < 400) {								/* not enough room to show the last column */
			document.getElementById("currentInfo-opt1").style.display = "none";
			document.getElementById("currentInfo-opt2").style.display = "none";
		} else {											/* show the last column */
			document.getElementById("currentInfo-opt1").style.display = "block";
			document.getElementById("currentInfo-opt2").style.display = "block";
		}	
	} else {												/* show the map */
		leftWidth = Math.round(window.innerWidth * 0.40);
		
		if (leftWidth < 380) {
			leftWidth = 380
		}
		if (leftWidth > 480) {
			leftWidth = 480
		}
		document.getElementById("currentInfo").width = leftWidth;
		iframe.width = window.innerWidth - leftWidth - 45;
		document.getElementById("map-container").style.display = "block";
		if (iframe.src == "about:blank") {
			if ( typeof weatherAppInfo !== 'undefined' && weatherAppInfo ) {
				var	actualLatitude = weatherAppInfo["latitude"];
				var	actualLongitude = weatherAppInfo["longitude"];
				iframe.src = 'https://maps.darksky.net/@precipitation_rate,' + weatherSelectedLatitude + ',' + weatherSelectedLongitude + ',11?marker=' + actualLatitude + ',' + actualLongitude + '&linkto=maps';
			}
		}

		document.getElementById("currentInfo-opt1").style.display = "block";
		document.getElementById("currentInfo-opt2").style.display = "block";
	}
	document.getElementById("currentTime-container").width = document.getElementById("currentInfo").width
	
	/* Format Minutely Forecast if there is anything to report */	
	
	if ( typeof weatherAppInfo !== 'undefined' && weatherAppInfo ) {
	
		var PrecipitationAnyMinute = false;
		var maxPrecipIntensity = 0;
		if ( (typeof weatherAppInfo == 'undefined') || !weatherAppInfo || (typeof weatherAppInfo.minutely == 'undefined') || !weatherAppInfo.minutely) {
			document.getElementById("minutelyForecast").innerHTML = "";
			document.getElementById("minutelyForecast").style.paddingTop = "0px"
		} else {
			for (i=0; i < 60; i++) {
				if (weatherAppInfo.minutely.data[i].precipProbability > 0) {
					PrecipitationAnyMinute = true;
					var minutelyPrecipIntensity = weatherAppInfo.minutely.data[i].precipIntensity;
					if (minutelyPrecipIntensity > maxPrecipIntensity) {
						maxPrecipIntensity = minutelyPrecipIntensity;
					}
				}
			}
		}
	
		var minutelyCanvasTitleHeight = 0;
		
		if (!PrecipitationAnyMinute) {
			document.getElementById("minutelyForecast").innerHTML = "";
		} else {
			var minutelyCanvasHeightScale = 0.002;
			if (maxPrecipIntensity > 0.200) {
				minutelyCanvasHeightScale = maxPrecipIntensity / 100;  	/* limit the maximum height to about 100 */
			}
			var minutelyCanvasHeight = Math.round((maxPrecipIntensity / minutelyCanvasHeightScale) + 0.5) + 1;	/* round up */
			if (minutelyCanvasHeight < 20) {
				minutelyCanvasHeight = 20; 								/* limit the minimum height to 20 */
			}
			var minutelyCanvasWidthScale = Math.floor(leftWidth / 60);
			var minutelyCanvasWidth = minutelyCanvasWidthScale * 60;
			document.getElementById("minutelyForecast").innerHTML = "<table style='padding:0; margin:0;' width='100%' class='details'><tr><td class='text-left'>" + formatDate(new Date(weatherAppInfo.minutely.data[0].time * 1000),"h:mm tt") + "</td><td class='center-text'>Forecast for the next hour</td><td class='right-text'>" + formatDate(new Date(weatherAppInfo.minutely.data[59].time * 1000),"h:mm tt") + "</td></tr><tr><td colspan='3' class='center-text'><canvas id='minutelyCanvas' style='border: 1px solid black;' width='" + minutelyCanvasWidth + "' height='" + (minutelyCanvasHeight + minutelyCanvasTitleHeight) + "'></canvas></td></tr></table>";
			var canvas = document.getElementById("minutelyCanvas");
			var ctx = canvas.getContext("2d");
			var previousPrecipIntensity = weatherAppInfo.minutely.data[0].precipIntensity;
			for (i=0; i < 60; i++) {
				ctx.fillStyle = "#888888"; 						
				ctx.fillRect(i * minutelyCanvasWidthScale,minutelyCanvasTitleHeight,1,minutelyCanvasHeight);	/* x scale line */		
				var minutelyPrecipProbability = weatherAppInfo.minutely.data[i].precipProbability;
				if (minutelyPrecipProbability > 0) {
					var minutelyPrecipIntensity = weatherAppInfo.minutely.data[i].precipIntensity;
					var minutelyBarHeight = Math.floor(minutelyPrecipIntensity / minutelyCanvasHeightScale);
					var opacity = (minutelyPrecipProbability + 0.5) * 2 / 3;
					ctx.fillStyle = "rgba(0,0,255," + opacity + ")";
					ctx.fillRect((i * minutelyCanvasWidthScale),minutelyCanvasHeight - minutelyBarHeight + minutelyCanvasTitleHeight,minutelyCanvasWidthScale,minutelyBarHeight);	
					previousPrecipIntensity = minutelyPrecipIntensity;
				}
			}
			for (i=0.1; i < maxPrecipIntensity; i += 0.1) {   	
				var scaleLine = minutelyCanvasHeight - (i / minutelyCanvasHeightScale) + minutelyCanvasTitleHeight;
				ctx.fillStyle = "pink";
				ctx.fillRect(1,scaleLine,minutelyCanvasWidth - 2 + minutelyCanvasTitleHeight,1);					/* y scale line */
				var scaleText = i.toFixed(1);
				ctx.font="10px Calibri";
				ctx.fillRect(minutelyCanvasWidth - ctx.measureText(scaleText).width - 4,scaleLine - 5,ctx.measureText(scaleText).width + 4,10);
				ctx.fillStyle = "black";
				ctx.fillText(scaleText,minutelyCanvasWidth - ctx.measureText(scaleText).width - 2,scaleLine + 3);	/* y scale label */
			}
		}
	}
	
	/* Determine height of the map relative to the left-hand panel, after considering some vertical padding */
	
	var constMinimumTimePadding = 6;
	var constMaximumTimePadding = 60;

	var workHeight = window.innerHeight - 40 - document.getElementById("hourlyForecast-wrapper").offsetHeight - document.getElementById("dailyForecast-wrapper").offsetHeight;
/*	if (workHeight > 400) {
		document.getElementById("summary").style.padding = "5px 40px";	
		document.getElementById("dailySummary").style.padding = "10px 5px";	
		if (document.getElementById("minutelyForecast").innerHTML != "") {
			document.getElementById("minutelyForecast").style.paddingTop = "10px"
		}
	} else {
		document.getElementById("summary").style.padding = "initial";	
		document.getElementById("dailySummary").style.padding = "initial";	
		document.getElementById("minutelyForecast").style.paddingTop = "0px"
	}
*/	var currentTimePadding = document.getElementById("currentTime").style.paddingTop.replace('px','');
	var leftHeightWithoutPadding = document.getElementById("LeftSide").offsetHeight - ((currentTimePadding - constMinimumTimePadding) * 2);
	if (leftHeightWithoutPadding >= window.innerHeight) {
		document.getElementById("hourlyForecast-wrapper").style.display = "none";
		document.getElementById("dailyForecast-wrapper").style.display = "none";
	} else {
		document.getElementById("hourlyForecast-wrapper").style.display = "block";
		document.getElementById("dailyForecast-wrapper").style.display = "block";
	}
	if (leftHeightWithoutPadding - document.getElementById("dailySummary").offsetHeight >= window.innerHeight) {
		document.getElementById("dailySummary").style.display = "none";
	} else {
		document.getElementById("dailySummary").style.display = "block";
	}
	leftHeightWithoutPadding = document.getElementById("LeftSide").offsetHeight - ((currentTimePadding - constMinimumTimePadding) * 2);

	workHeight = window.innerHeight - 40 - document.getElementById("hourlyForecast-wrapper").offsetHeight - document.getElementById("dailyForecast-wrapper").offsetHeight;
	if (document.getElementById("map-container").style.display == "none") {
		if (workHeight < leftHeightWithoutPadding) {
			workHeight = window.innerHeight - 20 - document.getElementById("hourlyForecast-wrapper").offsetHeight;
			if (workHeight < leftHeightWithoutPadding) {
				workHeight = leftHeightWithoutPadding
			}
		}
	} else {
		if (workHeight < (leftHeightWithoutPadding + (constMinimumTimePadding * 2))) {
			workHeight = leftHeightWithoutPadding + (constMinimumTimePadding * 2);
		}
		if (workHeight < 100) {
			workHeight = 100;
		}
		iframe.height = workHeight;
	}
	var proposedTimePadding = (Math.round((workHeight - leftHeightWithoutPadding) / 2));
	if (proposedTimePadding < constMinimumTimePadding) { 
		proposedTimePadding = constMinimumTimePadding; 
	}
	if (proposedTimePadding > constMaximumTimePadding) { 
		proposedTimePadding = constMaximumTimePadding;
	}
	document.getElementById("currentTime").style.paddingTop = proposedTimePadding + "px"
	document.getElementById("currentTime").style.paddingBottom = proposedTimePadding + "px"
}
</script>

<!--------------------------------------------------------------------------->
<!-- Scrolling... see http://cubiq.org/iscroll-5 and http://iscrolljs.com/ -->
<!--------------------------------------------------------------------------->

<Script src="../js/iscroll-lite.js"></script>
<script type="text/javascript" charset="utf-8">
		myScrollHourlyForecast = new IScroll('#hourlyForecast-wrapper', { scrollX: true, scrollY: false, mouseWheel: true, momentum: false });
		myScrollHourlyForecast.on('scrollEnd',function(e) { 		/* snap to a column after a scroll */
			if ((this.x !== 0) && (this.x > this.maxScrollX)) { 	/* note that x runs negative */
				var sizeOfEach = document.getElementById("hourlyForecast-wrapper").getAttribute("data-sizeOfEach"); 
				var proposedX = Math.round(this.x / (sizeOfEach)) * (sizeOfEach);
				setTimeout(function () { 
					myScrollHourlyForecast.scrollTo(proposedX,0,0,false);
					myScrollHourlyForecast.refresh(); 
				}, 0);
			}
		});
		
		myScrollDailyForecast = new IScroll('#dailyForecast-wrapper', { scrollX: true, scrollY: false, mouseWheel: true, momentum: false });
		myScrollDailyForecast.on('scrollEnd',function(e) { 			/* snap to a column after a scroll */
			if ((this.x !== 0) && (this.x > this.maxScrollX)) { 	/* note that x runs negative */
				var sizeOfEach = document.getElementById("dailyForecast-wrapper").getAttribute("data-sizeOfEach"); 
				var proposedX = Math.round(this.x / (sizeOfEach)) * (sizeOfEach);
				setTimeout(function () { 
					myScrollDailyForecast.scrollTo(proposedX,0,0,false);
					myScrollDailyForecast.refresh(); 
				}, 0);
			}
		});

		</script>

</body>
</html>