<?php require('../include/check_session_login.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Setup</title>
	<?php require('../include/app_standard_head.html'); ?>	
</head>

<body>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php include('../include/startup_spinner.html'); ?> 
<div class="houseID">House<br>- <?PHP echo $houseID; ?> -</div>
<div class="center" style="width: 90%">
<?php

//-----------------------------------------------------------------------------
//	Process the submit of the form:
//-----------------------------------------------------------------------------

	$error = '';				// feedback for the next display of the form

	if (isset($_POST['submitButton'])) {
		$whichSubmitButton = $_POST['submitButton'];
		echo '<div id="MessageFromUpdate" class="closebox">';
		echo '<button type="button" class="close" onClick="document.getElementById(\'MessageFromUpdate\').style.display=\'none\';"><span>&times;</span></button>';
		if (strpos($userAttributes,'/setup/') === FALSE) { 
			echo "<p style='color: red;'>This function is not available without the /setup/ user attribute.</p>";
		} else {

			/* ------------------------------------- */
			/* back up the database tables:          */
			/* note that all house IDs are backed up */
			/* ------------------------------------- */
		
			if ($whichSubmitButton == "backup") {
		
				$tableNames = array('displaylayout','configuration','appusers');

				foreach ($tableNames as $tableName)	{
					$backupFile = "../../MySQL/backups/$tableName$houseID.sql";
					$query      = "SELECT * FROM $tableName";
					list($result,$count,$error) = Execute_SQL($query);
					if ($error) {
						echo '<p style="color: red;">'.$error.'</p>';
					}
					if ($result) {
						$resultArray = mysqli_fetch_all($result,MYSQLI_ASSOC);	
						$resultJSON = json_encode($resultArray);
	
						$myfile = fopen($backupFile, "w") or die("<p style='color: red;'>Unable to open file $backupFile!</p>");
						fwrite($myfile, $resultJSON);
						fclose($myfile);
	
						echo '<p>Backup of table "'.$tableName.'" ('.$count.' records) to file "'.$backupFile.'" has completed.</p>';
					}
				}

			/* ------------------------------------------- */
			/* restore a database table:                   */
			/* note that only selected houseID is restored */
			/* ------------------------------------------- */
		
			} elseif ((substr($whichSubmitButton,0,8) == 'restore ') || (substr($whichSubmitButton,0,8) == 'preview ')) {
				$sizeLimit = 100 * 1024;
				$preview = substr($whichSubmitButton,0,8) == 'preview ';
				$tableName = substr($whichSubmitButton,8);
				$backupFile = "../../MySQL/backups/$tableName$houseID.sql";
				if ($preview) {
					echo '<p><b>Contents of "'.$backupFile.'" for restore of table "'.$tableName.'":</b></p>';
				}
				if (filesize($backupFile) > $sizeLimit) {		// something is wrong if it gets this big?
					echo "<p style='color: red;'>Unable to open file $backupFile because it is too large.</p>";
				} elseif (filesize($backupFile) == 0) {
					echo "<p style='color: red;'>$backupFile is empty.</p>";
				} else {
					$myfile = fopen($backupFile, "r") or die("<p style='color: red;'>Unable to open file $backupFile!</p>");
					$resultJSON = fread($myfile,$sizeLimit);
					fclose($myfile);

					$resultArray = json_decode($resultJSON,TRUE);

					$properties = array_keys($resultArray[0]);
					$properties_string = "(";
					foreach( $properties as $col ) {
						$properties_string .= $col . ", "; 
					}
					$properties_string = substr($properties_string, 0, -2).')'; 								
					if ($preview) {
						echo $properties_string;
						echo '<br>';
					}
				
					$insert_string = '';
					foreach( $resultArray as $row ) {
						if ($row['houseID'] == $houseID) {
							$values = array_values($row);
							$values_string = "(";
							for ($i=0; $i<count($values); $i++) {				
								if ($properties[$i] == 'ID') {
									$values_string .= $values[$i].",";
								} else {
									$values_string .= "'".Escape_SQL($values[$i])."',";
								}
							}
							$values_string = substr($values_string, 0, -1).')'; 								
							if ($preview) {
								echo $values_string;
								echo '<br>';
							}
							$insert_string .= $values_string.",";
						}
					}

					$insert_string = substr($insert_string, 0, -1).';'; 								
				
					if (! $preview) {
						$query = "DELETE FROM $tableName WHERE `houseID` = '".$houseID."';";
						list($result,$count,$error) = Execute_SQL($query);
						if ($error) {
							echo '<p style="color: red;">'.$error.'</p>';
						}

						$query = "INSERT INTO $tableName $properties_string VALUES ".$insert_string.";";
						list($result,$count,$error) = Execute_SQL($query);
						if ($error) {
							echo '<p style="color: red;">'.$error.'</p>';
						} else {
							echo '<p>Restore of "'.$tableName.'" table from file "'.$backupFile.'" has completed.</p>';
						}
					}
				}

			/* ---------------------------------------- */
			/* save the updated HouseInfo configuration */
			/* ---------------------------------------- */
		
			} elseif ($whichSubmitButton =='update HouseInfo') {
				$houseInfo = $_POST['houseInfo'];
				if (Set_Configuration_Item($houseID,'houseInfo',$houseInfo)) {
					echo '<p>Update of house configuration information has completed.</p>';
				}
				$houseInfo = json_decode(urldecode($houseInfo),true); 
 
			/* --------------------------------------------- */
			/* save the updated LocationsTable configuration */
			/* --------------------------------------------- */
		
			} elseif ($whichSubmitButton =='update LocationsTable') {
				$locationsTable = $_POST['locationsTable'];
				if (Set_Configuration_Item($houseID,'locations',$locationsTable)) {
					echo '<p>Update of locations configuration has completed.</p>';
				}
				$locationsTable = json_decode(urldecode($locationsTable),true); 

			/* --------- */
			/* otherwise */
			/* --------- */
		
			} else {
				echo "<p style='color: red;'>This function ($whichSubmitButton) is not yet implemented.</p>";
			}
		}
		echo '</div>';			/* close out the message box */
	}

//-----------------------------------------------------------------------------
//	Get house configuration info (if not already loaded):
//-----------------------------------------------------------------------------

	if (! isset($houseInfo)) {
#		echo 'Reloading $houseInfo<br>';
		$houseInfo = Get_Configuration_Item($houseID,'houseInfo');
	}
	if (! $houseInfo) {
		echo 'Loading default house configuration info.';
		$addingDefaultHouseInfo = TRUE;
		$houseInfo = array("HSSource"=>"none", "HSURL"=>"http://localhost/", "HSUser"=>"demo@homeseer.com", "HSPassword"=>"demo100", "HASource"=>"none", "HAURL"=>"http://localhost:8123/", "HAPassword"=>"demo100", "Map"=>array(0=>array("SearchLocation"=>"Springville, UT", "Latitude"=>"40.165232", "Longitude"=>"-111.610625", "City"=>"Springville"),1=>array("SearchLocation"=>"Mona, UT", "Latitude"=>"39.814729", "Longitude"=>"-111.855272", "City"=>"Mona")));
	} else {
		$addingDefaultHouseInfo = FALSE;
	}
?> 
<script>

var houseInfo = <?php echo json_encode($houseInfo); ?>;

</script>
<style>
label {
	display:inline-block;
	width:100px;
	font-weight: 600;
}

input[type=text],select {
    padding: 6px 10px;
    margin: 0.5vh 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
}
</style>
<div class="info border content">
<table class="container" width="100%"><tr><td class="vat">
	<p class="section-heading">HomeSeer Connection Information</p>
		<label>HS Source</label>
		<select name="HSSource" title="Select the type of access to the HomeSeer controller" size="1" required onChange="HSSourceUpdated();">
			<option value="none"  title="HomeSeer controller is not configured">Not Used</option>
			<option value="local"  title="Connect directly to the HomeSeer controller">Local Controller</option>
			<option value="online" title="Connect using the HomeSeer online service"  >Online MyHS</option>
			<option value="demo"   title="Use previously captured device info"        >Demo Sample</option>
		</select>
	<br>
      <label>Local URL</label>
      <input type="text" name="HSURL" size="24" onInput="CheckHouseInfo();" title="Enter URL for the local HomeSeer controller">
      <a href="#" class="green" onClick="window.open(document.getElementsByName('HSURL')[0].value, '_blank');">Open&nbsp;URL</a><!-- TODO -->
	<br>
      <label>User</label>
      <input type="text" name="HSUser" size="20" onInput="CheckHouseInfo();" title="Enter MyHS user name">
	<br>
      <label>Password</label>
      <input type="text" name="HSPassword" size="20" onInput="CheckHouseInfo();" title="Enter MyHS password">

	<p class="section-heading">Home Assistant Connection Information</p>
		<label>HA Source</label>
		<select name="HASource" title="Select the type of access to the Home Assistant controller" size="1" required onChange="HASourceUpdated();">
			<option value="none"  title="Home Assistant controller is not configured">Not Used</option>
			<option value="local"  title="Connect directly to the Home Assistant controller">Local Controller</option>
			<option value="demo"   title="Use previously captured device info"        >Demo Sample</option>
		</select>
	<br>
      <label>Local URL</label>
      <input type="text" name="HAURL" size="24" onInput="CheckHouseInfo();" title="Enter URL for the local Home Assistant controller">
      <a href="#" class="green" onClick="window.open(document.getElementsByName('HAURL')[0].value, '_blank');return FALSE;">Open&nbsp;URL</a><!-- TODO -->
	<br>
      <label>API Token</label>
      <input type="text" name="HAPassword" size="20" onInput="CheckHouseInfo();" title="Enter HA API Token">

	  <form class="content" action="" method="post">
		<div class="container">
			<p id="HouseInfoErrorMessage" style="color:#f44336"><b><?php if (isSet($HouseInfoErrorMessage)) { echo $HouseInfoErrorMessage; } ?></b></p>
			<button id="SaveHouseInfo" class="green" name="submitButton" type="submit" value="update HouseInfo" onClick="Submit_HouseInfo();">
			Save&nbsp;Changes</button>
				<input id="houseInfoInput" type="hidden" name="houseInfo">
				<script>
					function Submit_HouseInfo() {
						houseInfo.HSSource = document.getElementsByName("HSSource")[0].value;
						houseInfo.HSURL = document.getElementsByName("HSURL")[0].value;
						houseInfo.HSUser = document.getElementsByName("HSUser")[0].value;
						houseInfo.HSPassword = document.getElementsByName("HSPassword")[0].value;

						houseInfo.HASource = document.getElementsByName("HASource")[0].value;
						houseInfo.HAURL = document.getElementsByName("HAURL")[0].value;
						houseInfo.HAPassword = document.getElementsByName("HAPassword")[0].value;

						document.getElementById('houseInfoInput').value = encodeURI(JSON.stringify(houseInfo));
						document.getElementById('SaveHouseInfo').style.display = 'none';
						$('.pre-loader').show();						
					}
				</script>
		</div>
	</form>

</td><td class="vat">
	  <p class="section-heading">Weather Station Information</p>
	  <table><tr><th class="left-text">City</th><th class="optional-800">Latitude</th><th class="optional-800">Longitude</th></tr>
	  <?php	  	
	for ($i = 0; $i < count($houseInfo['Map']); $i++) {
        echo "<tr><td>".$houseInfo['Map'][$i]['City']."</td><td class='optional-800'>".$houseInfo['Map'][$i]['Latitude']."</td><td class='optional-800'>".$houseInfo['Map'][$i]['Longitude']."</td>";
		echo "<td><button class='green' name='submitButton' type='button' onClick=\"DBUpdateEditMap($i);\">";
		echo "<b>Edit</b></button>";
		echo "</td></tr>";
	}
	  ?>
		<tr><td colspan="2">
			<button class="green" name="submitButton" type="button" value="new location" onClick="DBUpdateEditMap('new');">
			<b>New&nbsp;Weather&nbsp;Station</b></button>
		</td></tr>
	  </table>
</td></tr>
</table>
<script>
	/* ----- fill in the initial values for the form ----- */
	
	document.getElementsByName("HSSource")[0].value = houseInfo.HSSource;
	document.getElementsByName("HSURL")[0].value = houseInfo.HSURL;
	document.getElementsByName("HSUser")[0].value = houseInfo.HSUser;
	document.getElementsByName("HSPassword")[0].value = houseInfo.HSPassword;

	document.getElementsByName("HASource")[0].value = houseInfo.HASource;
	document.getElementsByName("HAURL")[0].value = houseInfo.HAURL;
	document.getElementsByName("HAPassword")[0].value = houseInfo.HAPassword;
	HSSourceUpdated();
	document.getElementById('SaveHouseInfo').style.display='none';
	<?php if ($addingDefaultHouseInfo) { echo "document.getElementById('SaveHouseInfo').style.display = 'block';"; } ?>

	/* ----- update of House Info items ----- */
	
function HSSourceUpdated() {
	var HSURL = document.getElementsByName("HSURL")[0];
	var HSUser = document.getElementsByName("HSUser")[0];
	var HSPassword = document.getElementsByName("HSPassword")[0];
	switch(document.getElementsByName("HSSource")[0].value) {
		case "none":
			HSURL.required = false;
			HSUser.required = false;
			HSPassword.required = false;
            break;
		case "local":
			if (!HSURL.value) {
				HSURL.value = "http://localhost/";
			}
			HSURL.required = true;
			HSUser.required = false;
			HSPassword.required = false;
			break;
		case "online":
			HSURL.required = false;
			HSUser.required = true;
			HSPassword.required = true;
			break;
		case "demo":
			HSURL.required = false;
			HSUser.required = false;
			HSPassword.required = false;
			break;
	}
	CheckHouseInfo();	
}

function HASourceUpdated() {
	var HAURL = document.getElementsByName("HAURL")[0];
	var HAPassword = document.getElementsByName("HAPassword")[0];
	switch(document.getElementsByName("HASource")[0].value) {
		case "none":
			HAURL.required = false;
			HAPassword.required = false;
            break;
		case "local":
			if (!HAURL.value) {
				HAURL.value = "http://localhost:8123/";
			}
			HAURL.required = true;
			HAPassword.required = true;
			break;
		case "demo":
			HAURL.required = false;
			HAPassword.required = false;
			break;
	}
	CheckHouseInfo();	
}

function CheckHouseInfo() {
	var HouseInfoErrorMessage = '';
	
	var HSURL = document.getElementsByName('HSURL')[0];
	var HSUser = document.getElementsByName('HSUser')[0];
	var HSPassword = document.getElementsByName('HSPassword')[0];
	if (HSURL.required && !(HSURL.value)) {
		HouseInfoErrorMessage += 'HS Local URL is required. ';
		HSURL.style.border = '#FF0000 1px solid';
	} else {
		HSURL.style.border = '';
	}
	if (HSUser.required && !(HSUser.value)) {
		HouseInfoErrorMessage += 'HS User is required. ';
		HSUser.style.border = '#FF0000 1px solid';
	} else {
		HSUser.style.border = '';
	}
	if (HSPassword.required && !(HSPassword.value)) {
		HouseInfoErrorMessage += 'HS Password is required. ';
		HSPassword.style.border = '#FF0000 1px solid';
	} else {
		HSPassword.style.border = '';
	}
	
	var HAURL = document.getElementsByName('HAURL')[0];
	var HAPassword = document.getElementsByName('HAPassword')[0];
	if (HAURL.required && !(HAURL.value)) {
		HouseInfoErrorMessage += 'HA Local URL is required. ';
		HAURL.style.border = '#FF0000 1px solid';
	} else {
		HAURL.style.border = '';
	}
	if (HAPassword.required && !(HAPassword.value)) {
		HouseInfoErrorMessage += 'HA Password is required. ';
		HAPassword.style.border = '#FF0000 1px solid';
	} else {
		HAPassword.style.border = '';
	}

	document.getElementById('HouseInfoErrorMessage').innerHTML = HouseInfoErrorMessage;
	if (HouseInfoErrorMessage) {
		document.getElementById('SaveHouseInfo').style.display='none';
		return false;
	} else {
		document.getElementById('SaveHouseInfo').style.display='block';
		return true;
	}
}
</script>
<?php
//-----------------------------------------------------------------------------
//	The DB Update Map form:
//-----------------------------------------------------------------------------
?>	  
<div id="DBUpdateMap" class="DBUpdate">
	<form id="DBUpdateMapForm" class="content" action="" method="post" style="display:none;">
	  <p>Weather Station Information</p>
		<input type="hidden" name="mapIndex">
		<div class="container">
			<label>Location</label>
			<input type="text" name="SearchLocation" size="20" onInput="DBUpdateMapFormUpdated();" title="Enter city, state, and/or postal code to search for latitude and longitude">&nbsp;
			<button class="green" style="width: auto; " name="SearchLocationButton" type="button" onClick="GeoCodeSearchLocation();">Search</button>
		<br>
			<label>Latitude</label>
			<input type="text" name="HouseLatitude" size="12" required onInput="DBUpdateMapFormUpdated();">
		<br>
			<label>Longitude</label>
			<input type="text" name="HouseLongitude" size="12" required onInput="DBUpdateMapFormUpdated();">
		<br>
			<label>City</label>
			<input type="text" name="HouseCity" size="12" required onInput="DBUpdateMapFormUpdated();">
		<div class="container" style="padding-top: 0px;">
			<p id="DBUpdateMapError" style="color:#f44336"><b><?php if (isSet($DBUpdateMapError)) { echo $DBUpdateMapError; } ?></b></p>
			<button id="DBUpdateMapSubmitButton" class="green" type="button" value="save" onClick="return DBUpdateMapValidateAndSubmitForm();"><b>Save Weather Station</b></button>
		</div>
		<div class="container" style="padding-top: 2px;">
			<button id="DBUpdateMapDeleteButton" class="red" type="button" onClick="return DBUpdateMapDeleteAndSubmitForm();"><b>Delete Weather Station</b></button>
		</div>
		<div class="container" style="padding-top: 2px;">
			<button type="button" class="yellow" onClick="document.getElementById('DBUpdateMapForm').style.display='none';">Cancel</button>
		</div>

		</div>
	</form>
</div>
<?php
//-----------------------------------------------------------------------------
//	Javascript functions specific to the DB Update Map form:
//-----------------------------------------------------------------------------
?>
<script>

function GeoCodeSearchLocation(){
    var SearchLocationValue = document.getElementsByName("SearchLocation")[0].value;
    $.ajax({
                type: "POST",                
				url: "http://www.mapquestapi.com/geocoding/v1/address",
				data: {
					key: 'JreAk2KNXfewR7fi64vGWMdRSwaTcPNh',
					location: SearchLocationValue,
					thumbMaps: false,
					maxResults: 1
				},
				success: function(response) {
					if (response === undefined) {
						return;
					}

					if ((typeof response.info.statuscode !== 'undefined') && (response.info.statuscode == '0')) {
						locationInfo = response.results[0].locations[0];
						document.getElementsByName("HouseLatitude")[0].value = locationInfo.latLng.lat;
						document.getElementsByName("HouseLongitude")[0].value = locationInfo.latLng.lng;
						document.getElementsByName("HouseCity")[0].value = locationInfo.adminArea5;
						DBUpdateMapFormUpdated();
					} else {
						alert(response);
					}
                }})
}

function DBUpdateMapFormHide(TrueOrFalse) {
	if (TrueOrFalse) {
		document.getElementById("DBUpdateMapForm").style.display = "none";
		$('.pre-loader').show();
	} else {
		document.getElementById("DBUpdateMapForm").style.display = "block";	}
}

function DBUpdateEditMap(indexValue) { 				/* get values from DB for newly selected item */
	var DBUpdateMapForm = document.getElementById("DBUpdateMapForm")
	var Form = document.getElementById("DBUpdateMapForm")

	if (indexValue == 'new') {									/* new */
		DBUpdateMapForm.mapIndex.value = indexValue;
		document.getElementsByName("SearchLocation")[0].value = '';
		document.getElementsByName("HouseLatitude")[0].value = '';
		document.getElementsByName("HouseLongitude")[0].value = '';
		document.getElementsByName("HouseCity")[0].value = '';

		document.getElementById('DBUpdateMapDeleteButton').style.display = "none";   		

	} else {												/* existing */
		DBUpdateMapForm.mapIndex.value = indexValue;
		document.getElementsByName("SearchLocation")[0].value = houseInfo.Map[indexValue].SearchLocation;
		document.getElementsByName("HouseLatitude")[0].value = houseInfo.Map[indexValue].Latitude;
		document.getElementsByName("HouseLongitude")[0].value = houseInfo.Map[indexValue].Longitude;
		document.getElementsByName("HouseCity")[0].value = houseInfo.Map[indexValue].City;
		
		DBUpdateMapFormUpdated();

		document.getElementById('DBUpdateMapDeleteButton').style.display = 'block';   		
	}
	document.getElementById('DBUpdateMapSubmitButton').style.display = 'none';   	/* hide the submit button until something is changed */
	DBUpdateMapFormHide(false);				   										/* show the update form */
}

function DBUpdateMapFormUpdated() {
		document.getElementById("DBUpdateMapError").innerHTML = ''; 					/* remove previous message */
		document.getElementById("DBUpdateMapSubmitButton").style.display = "block";   	/* show the submit button */
}

function DBUpdateMapValidateAndSubmitForm() {
	var DBUpdateMapForm = document.getElementById("DBUpdateMapForm");
	var indexValue = DBUpdateMapForm.mapIndex.value;
	var HouseLatitude = document.getElementsByName('HouseLatitude')[0];
	var HouseLongitude = document.getElementsByName('HouseLongitude')[0];
	var HouseCity = document.getElementsByName('HouseCity')[0];
    var SearchLocation = document.getElementsByName('SearchLocation')[0];

	document.getElementById("DBUpdateMapError").innerHTML = '';

	if ( !(HouseLatitude.value)) {
		HouseInfoErrorMessage += 'Latitude is required. ';
		HouseLatitude.style.border = '#FF0000 1px solid';
	} else {
		HouseLatitude.style.border = '';
	}
	if ( !(HouseLongitude.value)) {
		HouseInfoErrorMessage += 'Longitude is required. ';
		HouseLongitude.style.border = '#FF0000 1px solid';
	} else {
		HouseLongitude.style.border = '';
	}
	if ( !(HouseCity.value)) {
		HouseInfoErrorMessage += 'City is required. ';
		HouseCity.style.border = '#FF0000 1px solid';
	} else {
		HouseCity.style.border = '';
	}
	if ( (!(HouseLatitude.value)) || (!(HouseLongitude.value)) || (!(HouseCity.value)) ) {
		SearchLocation.style.border = '#FF0000 1px solid';
	} else {
		SearchLocation.style.border = '';
	}

	if (document.getElementById("DBUpdateMapError").innerHTML == '') {

		if (indexValue == 'new') {
			houseInfo.Map[houseInfo.Map.length] = { 'SearchLocation':DBUpdateMapForm.SearchLocation.value, 'Latitude':DBUpdateMapForm.HouseLatitude.value, 'Longitude':DBUpdateMapForm.HouseLongitude.value, 'City':DBUpdateMapForm.HouseCity.value };
		} else {
			houseInfo.Map[indexValue].SearchLocation = document.getElementsByName("SearchLocation")[0].value;
			houseInfo.Map[indexValue].Latitude = document.getElementsByName("HouseLatitude")[0].value;
			houseInfo.Map[indexValue].Longitude = document.getElementsByName("HouseLongitude")[0].value;
			houseInfo.Map[indexValue].City = document.getElementsByName("HouseCity")[0].value;
		}
		DBUpdateMapFormHide(true);
		document.getElementById("SaveHouseInfo").click();
		return true;
	} else {
		return false;
	}
}

function DBUpdateMapDeleteAndSubmitForm() {
	var DBUpdateMapForm = document.getElementById("DBUpdateMapForm");
	var indexValue = DBUpdateMapForm.mapIndex.value;
	if (confirm('Are you sure that you want to delete this item?')) {
		houseInfo.Map.splice(indexValue, 1);
		DBUpdateMapFormHide(true);
		document.getElementById("SaveHouseInfo").click();
		return true;
	} else {
		return false;
	}
	
}

</script>
<?php
//-----------------------------------------------------------------------------
//	Get location configuration info (if not already loaded):
//-----------------------------------------------------------------------------

	if (! isset($locationsTable)) {
		echo 'Reloading $locationsTable<br>';
		$locationsTable = Get_Configuration_Item($houseID,'locations');
	}
	if (! $locationsTable) {
		echo 'Loading default location configuration info.';
		$addingDefaultLocationsTable = TRUE;
		$locationsTable[0] = array("Name"=>"1stFloor", "DisplayName"=>"First Floor", "ImageSource"=>"FirstFloor.jpg", "Order"=>"1");
		$locationsTable[1] = array("Name"=>"Environmental", "DisplayName"=>"Environmental", "ImageSource"=>"FirstFloor.jpg", "Order"=>"3");
		$locationsTable[2] = array("Name"=>"Exterior", "DisplayName"=>"Exterior", "ImageSource"=>"Exterior.jpg", "Order"=>"2");
		$locationsTable[3] = array("Name"=>"AlarmStatus", "DisplayName"=>"Alarm Status", "ImageSource"=>"FirstFloor.jpg", "Order"=>"4");
	} else {
		$addingDefaultLocationsTable = FALSE;
	}
	if ($locationsTable) {
		usort($locationsTable, function($a, $b) {
			return strcmp($a['Order'], $b['Order']);
		});
	}
?>
<script>

var locationsTable = <?php echo json_encode($locationsTable); ?>;

</script>
<table class="container">
	<tr><th colspan="2"><p class="left-text section-heading">Location Information</p><th></tr>
	<tr>
		<th class="left-text optional-800">Location Name&nbsp;</th><th class="optional-500">Order&nbsp;</th><th class="left-text">Display Name</th><th class="optional-1024">Image Source</th><th class="optional-500">Image</th>
	</tr>
<?php
	for ($i = 0; $i < count($locationsTable); $i++) { ?>
	<tr>
		<td class="optional-800"><?php echo $locationsTable[$i]['Name']; ?></td>
		<td class="optional-500 center-text"><?php echo $locationsTable[$i]['Order']; ?></td>
		<td><?php echo $locationsTable[$i]['DisplayName']; ?></td>
		<td class="optional-1024 center-text"><?php echo $locationsTable[$i]['ImageSource']; ?></td>
		<td class="optional-500"><img src="/HS/images/<?php echo $locationsTable[$i]['ImageSource']; ?>" class="thumbnail"></td>		
		<td>
			<button class="green" name="submitButton" type="button" onClick="DBUpdateEditLocation('<?php echo $i; ?>');">
			<b>Edit</b></button>
		</td><td>
			<a class="green" href="/HS/devices.php?location=<?php echo $locationsTable[$i]['Name']; ?>&update&iframe=true"><b>Update&nbsp;Displayed&nbsp;Items</b></a>		
		</td>
	</tr>		
<?php } ?>
	<tr>
		<td colspan="2"><?php if ($addingDefaultLocationsTable) { echo "Default locations added."; } ?></td>
		<td class="optional-800"></td><td class="optional-1024"></td>
		<td>
			<button class="green" name="submitButton" type="button" value="new location" onClick="DBUpdateEditLocation('new');">
			<b>New&nbsp;Location</b></button>
		</td>
		<td colspan="2">
			<form class="content" action="" method="post">
				<button id="locationsTableButton" style="display:none;" class="green" name="submitButton" type="submit" value="update LocationsTable" onClick="Submit_LocationsTable();">
				<b>Save&nbsp;Location&nbsp;Changes</b></button>
				<input id="locationsTableInput" type="hidden" name="locationsTable">
				<script>
					<?php if ($addingDefaultLocationsTable) { echo "document.getElementById('locationsTableButton').style.display = 'block';"; } ?>
					function Submit_LocationsTable() {
						document.getElementById('locationsTableInput').value = encodeURI(JSON.stringify(locationsTable));
						document.getElementById('locationsTableButton').style.display = 'none';	
						$('.pre-loader').show();						
					}
				</script>
			</form>
		</td>
		</td>		
	</tr>		
</table>
<?php
//-----------------------------------------------------------------------------
//	Backup and Restore section of the page:
//-----------------------------------------------------------------------------
?>
<form class="container" action="" method="post">
<table>
	<tr><th colspan="2"><p class="left-text section-heading">Database Backup and Restore</p></th></tr>
	<tr>
		<td> </td>
		<td colspan=2>
			<button class="green" name="submitButton" type="submit" value="backup" onClick="$('.pre-loader').show();">
			<b>Back&nbsp;Up&nbsp;Database&nbsp;Tables</b></button>
		</td>
	</tr>
<?php $tableNames = array('appusers','configuration','displaylayout');
	foreach ($tableNames as $tableName)	{ ?>
	<tr>
		<td><?php  
		$backupFile = "../../MySQL/backups/$tableName$houseID.sql";
		if (file_exists($backupFile)) {
			echo 'The backup available for "'.$tableName.'" table was made '.date ("F d, Y H:i:s", filemtime($backupFile)).' GMT.';
		} else {
			echo 'A backup of the "'.$tableName.'" table is not available.';
		} ?>
		</td>
		<td>
			<button class="green" name="submitButton" type="submit" value="preview <?php echo $tableName; ?>" onClick="$('.pre-loader').show();">
			<b>Preview</b></button>
		</td><td>
			<button class="yellow" name="submitButton" type="submit" value="restore <?php echo $tableName; ?>" onClick="return confirm('Are you sure that you want to restore this table?');">
			<b>Restore&nbsp;"<?php echo $tableName; ?>"&nbsp;Table</b></button>
		</td>
	</tr>		
<?php } ?>
</table>
</form>
<script><?php include('../include/startup_spinner_done.js'); ?></script> 
<?php
//-----------------------------------------------------------------------------
//	The DB Update Location form:
//-----------------------------------------------------------------------------
?>
<div id="DBUpdate" class="DBUpdate">
  <form id="DBUpdateForm" class="content" action="" method="post" style="display:none;">
	<div class="container">
	  <p>Location Information</p>
      <input type="hidden" name="locationIndex">
      <label>Location&nbsp;Name</label>
      <input type="text" name="locationName" size="12" required onInput="DBUpdateFormUpdated();">&nbsp;
	<br>
      <label>Display Order</label>
      <input type="text" name="locationOrder" size="4" required onInput="DBUpdateFormUpdated();">&nbsp;
	<br>
      <label>Display Name</label>
      <input type="text" name="locationDisplayName" size="16" required onInput="DBUpdateFormUpdated();">&nbsp;
	<br>
      <label>Image Source</label>
<!--      <input type="text" name="locationImageSource" size="12" required onInput="DBUpdateImageSourceChanged(this.value);DBUpdateFormUpdated();">&nbsp;
-->		<select name="locationImageSource" onChange="DBUpdateImageSourceChanged(this.value);DBUpdateFormUpdated();">
    <?php
		foreach (glob('../HS/images/*.*') as $filepath) {
			$filename = basename($filepath);
			echo "<option value='" . $filename ."'>" . $filename . "</option>"; 
		}
    ?>
    </select>	  
	<br>
	  <img name="locationImageThumbnail" class="thumbnail" onError="if (this.src.indexOf(BadImage) < 0) { this.src=BadImage; this.style.width='80px'; this.style.height='auto' }; return false;">
<!-- see https://www.w3schools.com/php/php_file_upload.asp for an upload example -->  
	</div>        
    <div class="container" style="padding-top: 0px;">
 	  <p id="DBUpdateError" style="color:#f44336"><b><?php if (isSet($DBUpdateError)) { echo $DBUpdateError; } ?></b></p>
      <button id="DBUpdateSubmitButton" type="button" value="save" onClick="return DBUpdateValidateAndSubmitForm();"><b>Save Location</b></button>
	</div>
	<div class="container" style="padding-top: 2px;">
      <button id="DBUpdateDeleteButton" class="red" type="button" onClick="return DBUpdateDeleteAndSubmitForm();"><b>Delete Location</b></button>
	</div>
	<div class="container" style="padding-top: 2px;">
	  <button type="button" class="yellow" onClick="document.getElementById('DBUpdateForm').style.display='none';">Cancel</button>
    </div>
  </form>
</div>
<?php
//-----------------------------------------------------------------------------
//	Javascript functions specific to the DB Update Location form:
//-----------------------------------------------------------------------------
?>
<script>
var DBUpdateForm = document.getElementById("DBUpdateForm")

function DBUpdateFormHide(TrueOrFalse) {
	if (TrueOrFalse) {
		document.getElementById("DBUpdateForm").style.display = "none";
		$('.pre-loader').show();
	} else {
		document.getElementById("DBUpdateForm").style.display = "block";	}
}

function DBUpdateEditLocation(indexValue) { 				/* get values from DB for newly selected item */
	var DBUpdateForm = document.getElementById("DBUpdateForm")
	var Form = document.getElementById("DBUpdateForm")
	if (indexValue == 'new') {									/* new */
		DBUpdateForm.locationIndex.value = indexValue;
		DBUpdateForm.locationName.value = '';
		DBUpdateForm.locationOrder.value = '';
		DBUpdateForm.locationDisplayName.value = '';
		DBUpdateForm.locationImageSource.value = '';
		DBUpdateForm.locationImageThumbnail.src = '';

		document.getElementById('DBUpdateDeleteButton').style.display = "none";   		

	} else {												/* existing */
		DBUpdateForm.locationIndex.value = indexValue;
		DBUpdateForm.locationName.value = locationsTable[indexValue].Name;
		DBUpdateForm.locationDisplayName.value = locationsTable[indexValue].DisplayName;
		DBUpdateForm.locationImageSource.value = locationsTable[indexValue].ImageSource;
		DBUpdateImageSourceChanged(locationsTable[indexValue].ImageSource);
		DBUpdateForm.locationOrder.value = locationsTable[indexValue].Order;
		
		DBUpdateFormUpdated();

		document.getElementById('DBUpdateDeleteButton').style.display = 'block';   		
	}
	document.getElementById('DBUpdateSubmitButton').style.display = 'none';   		/* hide the submit button until something is changed */
	DBUpdateFormHide(false);				   										/* show the update form */
}

function DBUpdateImageSourceChanged(ImageSource) {
	var DBUpdateForm = document.getElementById("DBUpdateForm");

	DBUpdateForm.locationImageThumbnail.src = '/HS/images/'+ImageSource;
	DBUpdateForm.locationImageThumbnail.style.width = '320px';
	DBUpdateForm.locationImageThumbnail.style.height = 'auto';
}

function DBUpdateFormUpdated() {
		document.getElementById("DBUpdateError").innerHTML = ''; 					/* remove previous message */
		document.getElementById("DBUpdateSubmitButton").style.display = "block";   	/* show the submit button */
}

var BadImage = '/images/Image-missing.png';

function DBUpdateValidateAndSubmitForm() {
	var DBUpdateForm = document.getElementById("DBUpdateForm");
	var indexValue = DBUpdateForm.locationIndex.value;
	document.getElementById("DBUpdateError").innerHTML = '';

	if (DBUpdateForm.locationName.value.trim() == '') {
		DBUpdateForm.locationName.style.border = '#FF0000 1px solid';
		document.getElementById("DBUpdateError").innerHTML += 'Location Name cannot be blank. ';
	} else {
		for (var i = 0; i < locationsTable.length; i++) { 
			if ((i != indexValue) && (locationsTable[i].Name.toLowerCase() == DBUpdateForm.locationName.value.toLowerCase())) {
				DBUpdateForm.locationName.style.border = '#FF0000 1px solid';
				document.getElementById("DBUpdateError").innerHTML += 'Location Name is a duplicate. ';
				break;
			} else {
				DBUpdateForm.locationName.style.border = '';
			}
		}		
	}
	if (DBUpdateForm.locationOrder.value.trim() == '') {
		DBUpdateForm.locationOrder.style.border = '#FF0000 1px solid';
		document.getElementById("DBUpdateError").innerHTML += 'Display Order cannot be blank. ';
	} else {
		for (var i = 0; i < locationsTable.length; i++) { 
			if ((i != indexValue) && (locationsTable[i].Order.toLowerCase() == DBUpdateForm.locationOrder.value.toLowerCase())) {
				DBUpdateForm.locationOrder.style.border = '#FF0000 1px solid';
				document.getElementById("DBUpdateError").innerHTML += 'Display Order is a duplicate. ';
				break;
			} else {
				DBUpdateForm.locationOrder.style.border = '';
			}
		}		
	}
	if (DBUpdateForm.locationDisplayName.value.trim() == '') {
		DBUpdateForm.locationDisplayName.style.border = '#FF0000 1px solid';
		document.getElementById("DBUpdateError").innerHTML += 'Display Name cannot be blank. ';
	} else {
		DBUpdateForm.locationDisplayName.style.border = '';
	}
	if (DBUpdateForm.locationImageSource.value.trim() == '') {
		DBUpdateForm.locationImageSource.style.border = '#FF0000 1px solid';
		document.getElementById("DBUpdateError").innerHTML += 'Image Source cannot be blank. ';
	} else {
		DBUpdateForm.locationImageSource.style.border = '';
	}
	if (DBUpdateForm.locationImageThumbnail.src.indexOf(BadImage) >= 0) {
		DBUpdateForm.locationImageSource.style.border = '#FF0000 1px solid';
		document.getElementById("DBUpdateError").innerHTML += 'Image does not exist. ';
	} else {
		DBUpdateForm.locationImageSource.style.border = '';
	}

	if (document.getElementById("DBUpdateError").innerHTML == '') {

		if (indexValue == 'new') {
			locationsTable[locationsTable.length] = { 'Name':DBUpdateForm.locationName.value, 'DisplayName':DBUpdateForm.locationDisplayName.value, 'ImageSource':DBUpdateForm.locationImageSource.value, 'Order':DBUpdateForm.locationOrder.value };
		} else {
			locationsTable[indexValue].Name = DBUpdateForm.locationName.value;
			locationsTable[indexValue].Order = DBUpdateForm.locationOrder.value;
			locationsTable[indexValue].DisplayName = DBUpdateForm.locationDisplayName.value;
			locationsTable[indexValue].ImageSource = DBUpdateForm.locationImageSource.value;
		}
		DBUpdateFormHide(true);
		document.getElementById("locationsTableButton").click();
		return true;
	} else {
		return false;
	}
}

function DBUpdateDeleteAndSubmitForm() {
	var DBUpdateForm = document.getElementById("DBUpdateForm");
	var indexValue = DBUpdateForm.locationIndex.value;

	if (confirm('Are you sure that you want to delete this item?')) {
		locationsTable.splice(indexValue, 1);
		DBUpdateFormHide(true);
		document.getElementById("locationsTableButton").click();
		return true;
	} else {
		return false;
	}
	
}
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}
</script>
<?php if (! strpos($userAttributes,'/server setup/') === FALSE) { 
//-----------------------------------------------------------------------------
//	Troubleshooting Information section of the page:
//-----------------------------------------------------------------------------
?>
	<table class="container vat">
		<tr><th colspan="2"><p class="left-text section-heading">Troubleshooting Information</p></th></tr>
		<tr><td>
		<details onToggle="FillBrowserVariablesInfo();"><summary class="section-heading">Browser Variables</summary>
		<div id="BrowserVariablesInfo"></div>
		</details>
		<details><summary class="section-heading">Cookies</summary>
			<pre>$_COOKIE = <?php
				print_r($_COOKIE); ?>
			</pre>
		</details>
		<details><summary class="section-heading">Server Variables</summary>
			<pre>$_SERVER = <?php
				print_r($_SERVER); ?>
			</pre>
		</details>
		<details><summary class="section-heading">Session Variables</summary>
			<pre>$_SESSION = <?php
				print_r($_SESSION); ?>
			</pre>
		</details>
		</td></tr>
	</table>

	</div>
<br>
<script>
	function FillBrowserVariablesInfo() {
		var info = 	'<ul>';
		info += 		'<li>screen</li>';
		info += 			'<ul>';
		info += 				'<li>availHeight = '+screen.availHeight+'</li>';
		info += 				'<li>availWidth = '+screen.availWidth+'</li>';
		info += 				'<li>height = '+screen.height+'</li>';
		info += 				'<li>width = '+screen.width+'</li>';
		info += 			'</ul>';
		info += 		'<li>window</li>';
		info += 			'<ul>';
		info += 				'<li>innerHeight = '+window.innerHeight+'</li>';
		info += 				'<li>innerWidth = '+window.innerWidth+'</li>';
		info += 				'<li>outerHeight = '+window.outerHeight+'</li>';
		info += 				'<li>outerWidth = '+window.outerWidth+'</li>';
		info += 				'<li>pageXOffset = '+window.pageXOffset+'</li>';
		info += 				'<li>pageYOffset = '+window.pageYOffset+'</li>';
		info += 			'</ul>';
		info += 	'</ul>';
		document.getElementById('BrowserVariablesInfo').innerHTML = info;

/*		this.scrollIntoView(false); */
	}
	window.addEventListener('resize', FillBrowserVariablesInfo);
</script>
<?php } ?>
</body>
</html>