<?php
####################################################################
#
# 	Proxy service for HomeSeer's API (to hide userid and password).
#
####################################################################

require("../include/check_session_login.php"); 
require_once("../include/execute_sql.php");			

	$debugging = TRUE;
	if (isset($_SERVER["HTTP_REFERER"])) {
		$debugging = FALSE;
	}
	
	if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
		$requestString=$_SERVER['QUERY_STRING'];
	} else {
		$requestString="request=getstatus";
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
//	Make the request to HS controller (local) or Homeseer.com (online), or get a saved copy (demo):
//-----------------------------------------------------------------------------

	$app_info_string='';
	switch ($houseInfo['HSSource']) {
		case 'local':
			$app_info_string = file_get_contents($houseInfo['HSURL'].'JSON?'.$requestString);
			break;
		case 'online':
			$app_info_string = file_get_contents("https://connected.homeseer.com/JSON?user=".$houseInfo['HSUser']."&pass=".$houseInfo['HSPassword']."&$requestString");
			break;
		case 'demo':	
			if (preg_match('/(^.*)&_=/',$requestString, $matches)) {
				$requestString = $matches[1];			# remove the &_=1520207250199
			}
#			$app_info = Get_Configuration_Item('HS-'.$requestString);
			$app_info_string = json_encode(Get_Configuration_Item($houseID,'HS-'.$requestString));
			break;
		default:
			echo "Error: HS_source parameter not recognized. \n";
			exit;		
	}
	if (!$app_info_string) {
		echo "Error: Failed to retrieve info from external provider. \n";
		exit;
	}
	
#	if (preg_match('/(^.*)&_=/',$requestString, $matches)) {
#		$requestString = $matches[1];			# remove the &_=1520207250199
#	}
#	if ($requestString=="request=getstatus") {
#		$app_info = json_decode($app_info_string, true);
#		$app_info_Devices = $app_info['Devices'];
#		usort($app_info_Devices, function($a, $b) {
#			return $a['ref'] - $b['ref'];
#		});
#		$app_info['Devices'] = $app_info_Devices;
#		$app_info_string = json_encode($app_info); 
# /* sort example ^ */
#	}

//-----------------------------------------------------------------------------
//	Return the JSON string:
//-----------------------------------------------------------------------------
	
	if (isset($_SERVER["HTTP_REFERER"])) {
		Exit($app_info_string);
	} else {
	#	Format for human who browsed directly:	
		$app_info = json_decode($app_info_string, true);
		print "<br>$requestString<hr><pre>";
		print_r($app_info);
		print "<hr>";
		var_dump($_SERVER);
		print "</pre>";	
	}
?>
