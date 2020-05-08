<?php
# ---------------------------------------------------------------------------
#	Execute_SQL - execute an SQL query
#
#	Example of call:  	list($result,$count,$error) = Execute_SQL($query);
# ---------------------------------------------------------------------------

			global $static_config;
			$static_config = parse_ini_file('../../MySQL/config.ini.php', true);

			global $mysqli;

			# 	Open the database:

			$mysqli = new mysqli($static_config['DB']['host'], $static_config['DB']['username'], $static_config['DB']['password'], $static_config['DB']['database']); # , $static_config['DB']['port']);
#			$mysqli = new mysqli('172.19.54.7','webserver','autoserv3833','homeautomation');
			if ($mysqli->connect_errno) {
				echo "Error: Failed to make a MySQL connection, here is why: <br>";
				echo "Errno: " . $mysqli->connect_errno . "<br>";
				echo "Error: " . $mysqli->connect_error . "<br>";
	#			return array(FALSE,0,"Application Error: Failed to make a database connection [Error " . $mysqli->connect_errno . ": " . $mysqli->connect_error . "]");
	#			exit;
			}

function Execute_SQL($sqlQuery) { 			
			global $mysqli;
			
			# 	Execute the SQL command:

			if (!$result = $mysqli->query($sqlQuery)) {
				echo "Error: Our query failed to execute and here is why: <br>";
				echo "Query: " . $sqlQuery . "<br>";
				echo "Errno: " . $mysqli->errno . "<br>";
				echo "Error: " . $mysqli->error . "<br>";
				
				return array(FALSE,0,"Application Error: Database query failed to execute [Error " . $mysqli->errno . ": " . $mysqli->error . "]");
				exit;
			}

			if (substr($sqlQuery,0,7) == 'INSERT ') {
				$count = $mysqli->insert_id;				// get the new ID for an insert
			} else {
				$count = mysqli_affected_rows($mysqli);		// get the number of rows
			}
			
//			$mysqli->close();

			#	Pass the result back to the caller:

			return array($result,$count,"");
}

# -------------------------------------------------------------------------------------------
#	Get_Configuration_Item - retrieve a set of attributes from the configuration database
# -------------------------------------------------------------------------------------------

function Get_Configuration_Item($houseID,$configurationKey) {
	
	$query = "SELECT * FROM `configuration` WHERE  `houseID` = '".$houseID."' AND `configkey` = '".Escape_SQL($configurationKey)."';";
	list($result,$count,$error) = Execute_SQL($query);
	if ($error) {
		echo '<p style="color: red;">'.$error.'</p>';
	}
	if ($result) {
		$record = $result->fetch_assoc();
		$configurationAttributesTable = json_decode(urldecode($record['attributes']), true);
		return $configurationAttributesTable;
	} else {
		return array();
	}
}		

# -------------------------------------------------------------------------------------------
#	Set_Configuration_Item - save a set of attributes to the configuration database
#
#	Example call from PHP:
#		Set_Configuration_Item('housemodes',urlencode(json_encode($houseModesTable)));	
#
#	Example call from Javascript:
#		<input id="locationsTableInput" type="hidden" name="locationsTable">
#		document.getElementById('locationsTableInput').value = encodeURI(JSON.stringify(locationsTable));
#		Set_Configuration_Item('locations',$_POST['locationsTableInput'])
# -------------------------------------------------------------------------------------------

function Set_Configuration_Item($houseID,$configurationKey,$configurationAttributes) {

	$currentDateTime = date('Y-m-d H:i:s');		/* datetimeupdated makes the update unique */
	
	$query = "UPDATE `configuration` SET `datetimeupdated` = '$currentDateTime', `attributes`='".Escape_SQL($configurationAttributes)."' WHERE `houseID` = '".$houseID."' AND `configkey` = '".Escape_SQL($configurationKey)."';";
	list($result,$count,$error) = Execute_SQL($query);
	if ($error) {
		echo '<p style="color: red;">'.$error.'</p>';
	}
	if ($count == 0) {
		$query = "INSERT INTO `configuration` (`houseID`, `configkey`, `datetimeupdated`, `attributes`) VALUES ('".$houseID."', '".Escape_SQL($configurationKey)."', '".$currentDateTime."', '".Escape_SQL($configurationAttributes)."')";
		list($result,$count,$error) = Execute_SQL($query);
		if ($error) {
			echo '<p style="color: red;">'.$error.'</p>';
		}
	}
	return $result;
}		

# -------------------------------------------------------------------------------------------
#	Write_Log_Message - write a record to the log database
# -------------------------------------------------------------------------------------------

function Write_Log_Message ($severity,$message) {
	$currentDateTime = date('Y-m-d H:i:s');
    $clientIP = $_SERVER['REMOTE_ADDR'];
    $houseID = $_SESSION['login_houseID'];
    $userID = $_SESSION['login_userID'];

    $query = "INSERT INTO `logrecords` (`houseID`, `userID`, `REMOTE_ADDR`, `eventdatetime`, `severity`, `message`) VALUES ('".$houseID."', '".$userID."', '".$clientIP."', '".$currentDateTime."', '".Escape_SQL($severity)."', '".Escape_SQL($message)."')";
	list($result,$count,$error) = Execute_SQL($query);
	if ($error) {
		echo '<p style="color: red;">'.$error.'</p>';
	}
}

# -------------------------------------------------------------------------------------------
#	Escape_SQL - escape a string before using it in a query
# -------------------------------------------------------------------------------------------

function Escape_SQL ($string) {
	global $mysqli;
	
	return mysqli_real_escape_string($mysqli, $string);
}

# -------------------------------------------------------------------------------------------
#	Password functions for login.php, profile.php, accounts.php
# -------------------------------------------------------------------------------------------

function hash_password($userID,$newPassword) {
	return password_hash( base64_encode( hash('sha384', $userID . $newPassword, true) ),PASSWORD_DEFAULT );
}
function verify_hashed_password($userID,$password,$correctPasswordHash) {
	return password_verify( base64_encode( hash('sha384', $userID . $password, true) ), $correctPasswordHash);
}

