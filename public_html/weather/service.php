<?php
#################################################################
#
# 	Service to retrieve weather data from darksky.net's API.
#
#	The first 1,000 requests per day are free, so this service
#	caches the data and refreshes it at most every 90 seconds.
#
#################################################################
	
require('../include/check_session_login.php'); 
require_once('../include/execute_sql.php');			

	$debugging = TRUE;
	if (isset($_SERVER["HTTP_REFERER"])) {
		$debugging = FALSE;
	}
		
//-----------------------------------------------------------------------------
//	Get house configuration to retrieve the primary (and default) location:
//-----------------------------------------------------------------------------

	$houseInfo = Get_Configuration_Item($houseID,'houseInfo');
	if (! $houseInfo) {
			echo "Error: House configuration info is not available. \n";
			exit;		
	}
	$primaryLatitude = $houseInfo['Map'][0]['Latitude'];
	$primaryLongitude = $houseInfo['Map'][0]['Longitude'];
 
	$latitude = $primaryLatitude;
	$longitude = $primaryLongitude;
	if (isset($_GET["latitude"])) {
		$latitude = $_GET["latitude"];
	}
	if (isset($_GET["longitude"])) {
		$longitude = $_GET["longitude"];
	}

//-----------------------------------------------------------------------------
//  Check the database to see if the cached info is fresh enough:
//-----------------------------------------------------------------------------

	if ($static_config['DB']['useDatabase']) {
	
# 	Read the previous values from the database:

		$query = "SELECT * FROM darksky WHERE latitude = '".Escape_SQL($latitude)."' AND longitude = '".Escape_SQL($longitude)."'";
		list($result,$count,$error) = Execute_SQL($query);
		if ($error) {
			echo $error;
			exit;
		}
		
		if (($latitude == $primaryLatitude) && ($longitude == $primaryLongitude)) {
			$refreshInterval = 90;		// 90 seconds for primary site
		} else {
			$refreshInterval = 15 * 60;	// longer for others
		}
		$currentDateTime = date('Y-m-d H:i:s');
		$aWhileAgo = date("Y-m-d H:i:s", strtotime($currentDateTime) - $refreshInterval);
		if ($debugging) print "Current = $currentDateTime<br>";
		if ($debugging) print "A while ago = $aWhileAgo<br>";
		if ($result) {
			$record = $result->fetch_assoc();
			$datetimeFromRecord = $record['datetimeupdated'];
			if ($debugging) print "Previous = $datetimeFromRecord<br>";
		}
	}
	if ($debugging) print "error = $error<br>";
	if ($debugging) print "num_rows = ".$result->num_rows."<br>";
	
//-----------------------------------------------------------------------------
//	Refresh the data from DarkSky if it is stale:
//-----------------------------------------------------------------------------

	if (!$static_config['DB']['useDatabase'] || $error || !$result || ($result->num_rows === 0) || ($datetimeFromRecord < $aWhileAgo)) {
		if ($debugging) echo "<br>Retrieving darksky<br>";		
		$app_info_string = file_get_contents("https://api.darksky.net/forecast/680ab760f0d089d7b6178e34900b738c/$latitude,$longitude");
		if (!$app_info_string) {
			echo "Error: Failed to retrieve info from external provider. \n";
			exit;
		}
		$app_info_encoded = urlencode($app_info_string);
		$WeHaveNewInfo = true;
		if ($static_config['DB']['useDatabase']) {
			if ($result->num_rows === 0) {
				$query = "INSERT INTO darksky (latitude, longitude, datetimeupdated, json) VALUES ('".Escape_SQL($latitude)."', '".Escape_SQL($longitude)."', '$currentDateTime', '$app_info_encoded');";
			} else {
				$query = "UPDATE darksky SET datetimeupdated = '$currentDateTime', json = '$app_info_encoded' WHERE latitude = ".Escape_SQL($latitude)." AND longitude = ".Escape_SQL($longitude).";";
			}
			if ($debugging) print "Saving darksky<br>";
			list($result,$count,$error) = Execute_SQL($query);
			if ($error) {
				echo $error;
				exit;
			}
		}
	} else {
		$app_info_string = urldecode($record['json']); 
		$WeHaveNewInfo = false;
	}

//-----------------------------------------------------------------------------
//	Process some of the info for automation functions:
//-----------------------------------------------------------------------------
	
	if ($WeHaveNewInfo && ($latitude == $primaryLatitude) && ($longitude == $primaryLongitude)) {

		$app_info = json_decode($app_info_string, true);

		$currentTemp = round($app_info["currently"]["temperature"]);
		$currentHumidity = $app_info["currently"]["humidity"] * 100;
		$todaySunriseTime = $app_info["daily"]["data"][0]["sunriseTime"];
		$todaySunsetTime = $app_info["daily"]["data"][0]["sunsetTime"];
		$todayPrecipProbability = $app_info["daily"]["data"][0]["precipProbability"];
		$todayPrecipIntensity = $app_info["daily"]["data"][0]["precipIntensity"];

		if ($debugging) print "Temperature at primary location = ".$currentTemp."<br>";

		# TODO: Do home automation stuff here...
	}

//-----------------------------------------------------------------------------
//	Return the JSON string:
//-----------------------------------------------------------------------------
	
	if (isset($_SERVER["HTTP_REFERER"])) {
		Exit($app_info_string);
	} else {
	#	Format for human who browsed directly:	
		$app_info = json_decode($app_info_string, true);
		print "<hr><pre>";
		print_r($app_info);
		print "<hr>";
		var_dump($_SERVER);
		print "</pre>";	
	}
?>
