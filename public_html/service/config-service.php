<?php
require('../include/check_session_login.php'); 
require_once('../include/execute_sql.php');			
####################################################################
#
# 	Service to retrieve configuration item from database.
#
####################################################################

	$debugging = TRUE;
	if (isset($_SERVER["HTTP_REFERER"])) {
		$debugging = FALSE;
	}

	if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
		$requestString=$_SERVER['QUERY_STRING'];
		if (preg_match('/request=(.*)&/',$requestString, $matches)) {
			$configItem = $matches[1];		# text between request= and &_=1520207250199 
		} else {
			preg_match('/request=(.*)/',$requestString, $matches);
			$configItem = $matches[1];
		}
	} else {
		$configItem = "currenthousemode";
	}
	
//-----------------------------------------------------------------------------
//	Get the configuration item:
//-----------------------------------------------------------------------------

	$configItemInfo = Get_Configuration_Item($houseID,$configItem);	
	if ($configItemInfo === null) {
			exit("Error: ".$configItem." configuration is not available. \n");
			exit;		
	}

//-----------------------------------------------------------------------------
//	Return the JSON string:
//-----------------------------------------------------------------------------
	if ($debugging == FALSE) {
		exit(json_encode($configItemInfo));
	} else {
	#	Format for human who browsed directly:	
		print "$configItem<hr><pre>";
		print_r($configItemInfo);
		print "</pre>";	
	}
?>
