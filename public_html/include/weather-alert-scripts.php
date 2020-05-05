<?php /** weather-alert-scripts.php - scripts to set the header menu weather icon and to display any weather alerts
       *
	   *		Input:	$mapIndex - which weather station to process (0 = primary)
	   *
	   *		Output:	WeatherAlertContainer div - will be displayed or hidden depending on if there is an alert
	   *				WeatherAlert span - will contain the alert message and a close box for the details
	   *				currentWeatherIcon image - will be updated with the source for the header weather icon
	   *				currentMenuIconCanvas canvas - work area
	   *
	   *		Session Storage:
	   *				currentWeatherAlert - contains a hash of the alert message if the user has dismissed it 
	   *				currentWeatherName - used to tell when the weather changes and the icon needs to change
	   *				currentWeatherIcon - used to retain the icon source
	   */
?>
<Script src="../js/skycons.js"></script>
<script>
var weatherAppInfo;

var weatherSelectedLatitude = '<?PHP echo $houseInfo['Map'][$mapIndex]['Latitude']; ?>';
var weatherSelectedLongitude = '<?PHP echo $houseInfo['Map'][$mapIndex]['Longitude']; ?>';

var weatherPreviousName;
if (localStorage.getItem('currentWeatherName')) {
	weatherPreviousName = localStorage.getItem('currentWeatherName');
} else if (sessionStorage.getItem('currentWeatherName')) {
	weatherPreviousName = sessionStorage.getItem('currentWeatherName');
} else {
	weatherPreviousName = "<?php // it is just easier to get a cookie from PHP
		if (isset($_COOKIE['currentWeatherName'])) {
			echo preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE['currentWeatherName']);
		} else {
			echo 'unknown';
		}
	?>"
}
document.getElementById("WeatherAlertContainer").style.display = "none";

function SaveWeatherIconAndAlertInfo() {
	if ('<?PHP echo $mapIndex; ?>' == '0') {

	/* display weather alert if present */
		
			if (typeof weatherAppInfo["alerts"] !== 'undefined') {
				var alertTitle = weatherAppInfo["alerts"][0]["title"];
				var alertDescription = weatherAppInfo["alerts"][0]["description"];
				var alertUri = weatherAppInfo["alerts"][0]["uri"];
				var shortMessage = "<a href='"+alertUri+"' style='color:var(--color-red);' title='"+alertDescription+"'>"+alertTitle+"</a><br>";
				var extendedMessage = shortMessage + '<div id="ExtendedWeatherAlertMessage" class="closebox details"><button type="button" class="close" onClick="DismissExtendedWeatherAlertMessage()"><span>&times;</span></button>'+alertDescription+'</div>';
				/* display additional details until dismissed by user */
				document.getElementById("WeatherAlert").innerHTML = extendedMessage;
				extendedMessage = document.getElementById("WeatherAlert").innerHTML;		/* get reformat; whitespace matters */
				if ((sessionStorage.getItem('currentWeatherAlert')) && (sessionStorage.getItem('currentWeatherAlert') == md5(extendedMessage))) {
					document.getElementById("WeatherAlert").innerHTML = shortMessage;
				}
				document.getElementById("WeatherAlertContainer").style.display = "block";
			} else {
				document.getElementById("WeatherAlertContainer").style.display = "none";
			}

		/* save the menu icon image when it changes */

		var currentWeatherName = weatherAppInfo.currently.icon;
			if (currentWeatherName != weatherPreviousName) {
				console.log('currentWeatherName = ' + currentWeatherName);
				var currentMenuIconCanvas = document.createElement("canvas");
				currentMenuIconCanvas.width='30';
				currentMenuIconCanvas.height='30';
				var skyconsMenuIcon = new Skycons({"color": "white"});
				skyconsMenuIcon.add(currentMenuIconCanvas, currentWeatherName);
				var currentMenuIconData = currentMenuIconCanvas.toDataURL();
				if (storageAvailable('localStorage')) {
					localStorage.setItem('currentWeatherIcon', encodeURIComponent(currentMenuIconData));
					localStorage.setItem('currentWeatherName', currentWeatherName);
				} else if (storageAvailable('sessionStorage')) {
					sessionStorage.setItem('currentWeatherIcon', encodeURIComponent(currentMenuIconData));
					sessionStorage.setItem('currentWeatherName', currentWeatherName);
				}
				weatherPreviousName = currentWeatherName;
				if (typeof currentWeatherIcon == 'object') {
					document.getElementById('currentWeatherIcon').src = currentMenuIconData;
				}
			}
	}			
}

function DismissExtendedWeatherAlertMessage() {
	if (storageAvailable('sessionStorage')) {  /* save the alert so we know when to pop it up again if it changes */
		var alertDescription = md5(document.getElementById('WeatherAlert').innerHTML);
		if ((! sessionStorage.getItem('currentWeatherAlert')) || (sessionStorage.getItem('currentWeatherAlert') != alertDescription)) {
			sessionStorage.setItem('currentWeatherAlert', alertDescription);
		}
	}
	document.getElementById('ExtendedWeatherAlertMessage').style.display='none';
	
	if (typeof OnResize == 'function') { 
		OnResize(); 
	}

}
</script>
