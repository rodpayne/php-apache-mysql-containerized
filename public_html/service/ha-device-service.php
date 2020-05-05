<?php
###########################################################################
#
# 	Proxy service for Home Assistant's API (to hide userid and password).
#
###########################################################################

require("../include/check_session_login.php"); 
require_once("../include/execute_sql.php");			

	$debugging = TRUE;
	if (isset($_SERVER["HTTP_REFERER"])) {
		$debugging = FALSE;
	}
	
	if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
		$requestString=$_SERVER['QUERY_STRING'];
		if (preg_match('/^request=(.*)/',$requestString, $matches)) {
			$requestString = $matches[1];			# remove request=
		}
		if (preg_match('/(^.*)&_=/',$requestString, $matches)) {
			$requestString = $matches[1];			# remove the &_=1520207250199
		}
	} else {
		$requestString="states";
	}
//-----------------------------------------------------------------------------
//	Get house configuration to retrieve the source location parameters:
//-----------------------------------------------------------------------------

	$houseInfo = Get_Configuration_Item($houseID,'houseInfo');
	if (! $houseInfo) {
			echo "Error: House configuration info is not available. \n";
			exit;		
	}

//-----------------------------------------------------------------------------
//	Make the request to Home Assistant (local) or get a saved copy (demo):
//-----------------------------------------------------------------------------

	$app_info_string='';
	switch ($houseInfo['HASource']) {
		case 'local':
//			$app_info_string = file_get_contents($houseInfo['HAURL'].'api/'.$requestString. '?api_password='.$houseInfo['HAPassword']);
			$url = $houseInfo['HAURL'].'api/'.$requestString;
			$options = array(
				'http'=>array(
					'method'=>"GET",
					'header'=>"Content-Type: application/json\r\n" .
						"Authorization: Bearer ".$houseInfo['HAPassword']
//						"Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiIxNWU5OGNlZmMyYWY0MTg0YTFhMTViZGU4ZWZlMTkyMSIsImlhdCI6MTU3NzgzMzA0NSwiZXhwIjoxODkzMTkzMDQ1fQ.XsYaGGQKWPsOLi1sk_OwQtFG8t_g7oGdl0XWKYDFxPg"
				)
			);
			$context = stream_context_create($options);
			$app_info_string = file_get_contents($url, false, $context);
			break;
		case 'demo':	
			$app_info_string = json_encode(Get_Configuration_Item($houseID,'HA-'.$requestString));
			break;
		default:
			echo "Error: HA_source parameter not recognized. \n";
			exit;		
	}
	if (!$app_info_string) {
		echo "Error: Failed to retrieve info from HA provider. \n";
		exit;
	}

//-----------------------------------------------------------------------------
//	Return the JSON string:
//-----------------------------------------------------------------------------
	
	if (isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], 'ha-device-service.php') === FALSE) {
		Exit($app_info_string);
	} else {
//	#	Format for human who browsed directly:	
        print "<a href='/service/ha-device-service.php?states'>states</a><br>";
        print "<a href='/service/ha-device-service.php?config'>config</a><br>";
        print "<a href='/service/ha-device-service.php?discovery_info'>discovery_info</a><br>";
        print "<a href='/service/ha-device-service.php?events'>events</a><br>";
        print "<a href='/service/ha-device-service.php?services'>services</a><br>";
        print "<a href='/service/ha-device-service.php?error_log'>error_log</a><br>";
		print "<hr>";
    
		$app_info = json_decode($app_info_string, true);
		print "$requestString<hr><pre>";
		print_r($app_info);
		print "<hr>";
		var_dump($_SERVER);
		print "</pre>";	
	}
?>
