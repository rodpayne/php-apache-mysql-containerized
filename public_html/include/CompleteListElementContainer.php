<?php 
#-----------------------------------------------------------------------------
#	CompleteListElementContainer.php
#
#	Input: 	$location - which display items to show
#			$locationsTable - table of locations
#			$optionShowDevices - option to show device list
#
#	Provide list of the display items for this location, with current status.
#	Optionally show a reference list of the devices and events in HomeSeer.
#-----------------------------------------------------------------------------
	if (isset($_GET['devices'])) {
		$optionShowDevices = TRUE;
	}
	if (isset($_GET['batteries'])) {
		$optionShowBatteries = TRUE;
	}
?>
<style>
#CompleteListElementContainer {
	position: relative; 
	width: 100%;
	max-width: 800px;
	margin-left: auto;
	margin-right: auto;
    overflow: auto; 		/* Enable scroll if needed */
	x-background-color: var(--background-color);
	font-size: 3vw;
}

@media screen and (min-width: 533px) {
  #CompleteListElementContainer {
    font-size: 16px;		/* max font size */
  }
}
</style>
<?php
#-----------------------------------------------------------------------------
#	Get the list of items to display for this location:
#-----------------------------------------------------------------------------

$displayLayoutTableError = '';				# feedback to display on the page
$displayLayoutTableArray = array();			# results of database query

	Do {
		
		if ($static_config['DB']['useDatabase']) {

	#	Do query:

			$query = "SELECT * FROM displaylayout WHERE displayLocation = '$location'";
			list($result,$count,$error) = Execute_SQL($query);
			if ($error) {
				$displayLayoutTableError = $error;
				break;
			}
			if ($count) {
				$displayLayoutTableArray = mysqli_fetch_all($result,MYSQLI_ASSOC);	
				usort($displayLayoutTableArray, function($a, $b) {
					return strcmp($a['displayText'], $b['displayText']);
				});
#				print_r($displayLayoutTableArray);		
			} else {
				$displayLayoutTableError = 'Database query failed to return results.';
				break;
			}
				
		} else {
			$displayLayoutTableError = 'Database is not available.';
		}
	} while (FALSE);

	for ($i = 0; $i < count($locationsTable); $i++) {
		if ($location == $locationsTable[$i]['Name']) {
			$locationDisplayName = $locationsTable[$i]['DisplayName'];
			break;
		}
	}

#-----------------------------------------------------------------------------
#	Global javascript variables for this page:
#-----------------------------------------------------------------------------
?>
<script>
	var Location = '<?php echo $location; ?>';
	var LocationDisplayName = '<?php echo $locationDisplayName; ?>';
	var displayLayoutTable = <?php echo json_encode($displayLayoutTableArray); ?>;
	var HS_appinfo_devices = [];
	var previous_HS_AppinfoDevicesGetstatusResult = '';
	var watchingAppinfoDevices = false;
</script>
<?php 
#-----------------------------------------------------------------------------
#	Message box for SQL errors from the PHP code:
#-----------------------------------------------------------------------------
	if ($displayLayoutTableError) { 
		echo '<div id="DisplayLayoutTableMessage" class="info closebox" style="color:#f44336">';
		echo '<button type="button" class="close" onClick="document.getElementById(\'DisplayLayoutTableMessage\').style.display=\'none\';"><span>&times;</span></button>';
		echo $displayLayoutTableError.'</div>';
	} 
#-----------------------------------------------------------------------------
#	HTML for our section of the page:
#-----------------------------------------------------------------------------
?>
	<div id="DeviceAlert" style='display: none; width: 95%;'></div>
	<div id="CompleteListElementContainer"></div>
	<div id="DeviceInfo" class="info center content" style='display:none; width: 95%;'>
		<div class="container">
			<p class="section-heading">Devices defined in HomeSeer</p>
			<table id='DeviceTable' class='info' style="width: 100%;"></table>
		</div>
		<div class="container">
			<p class="section-heading">Events defined in HomeSeer</p>
			<table id='EventTable' class='info' style="width: 100%;"></table>
		</div>
	</div>
	<br>
<script>

function FormatCompleteList() {

	/* Provide list of all of the controls */
	var tableHTML = '<div class="PopupContent"><p class="center-text section-heading">All Control Items for "' + LocationDisplayName + '" Location</p><table class="PopupTable"><tr><th>Item</th><th>Status<th></tr>';
	var Items = 0;
	for ($i = 0; $i < displayLayoutTable.length; $i++) {
		Items++;
		tableHTML += FormatDisplayLayoutTableRow($i);
	}
	tableHTML += '</table></div>';
		var CompleteListElementContainer = document.getElementById('CompleteListElementContainer');
	if (Items == 0) { 
		CompleteListElementContainer.style.display = 'none';
	} else {
		CompleteListElementContainer.innerHTML = tableHTML;
		CompleteListElementContainer.style.display = 'block';
	}
	
	var rightEdgeOfImage = $('#FloorImage').outerWidth();
	var spaceToRightOfImage = $(window).width() - rightEdgeOfImage;
	if ((rightEdgeOfImage === undefined) || (spaceToRightOfImage < 360) || (  document.getElementById("DBUpdateForm") && (document.getElementById("DBUpdateForm").style.display == "block") )) {
		CompleteListElementContainer.style.position = 'relative'; 
		CompleteListElementContainer.style.width = '100%';
		CompleteListElementContainer.style.left = 'auto';
		CompleteListElementContainer.style.top = 'auto';
	} else {
		CompleteListElementContainer.style.position = 'absolute'; 
		CompleteListElementContainer.style.width = spaceToRightOfImage + 'px';
		CompleteListElementContainer.style.left = rightEdgeOfImage + 'px';
		CompleteListElementContainer.style.top = '0';
	}
}
function DisplayTypeInfo(displayType) {
    var typeLabel = '';
	var labelForOn = 'on';
	var labelForOff = 'off';
	var buttonTextForOn = '';
        var buttonTextForOff = '';
        var requestValueSetOff = 0;
        var requestValueSetOn = 255;

	switch(displayType) {
		case '1':                               /* On/Off */
                        typeLabel = 'On/Off';
                        buttonTextForOn = 'Turn Off';
                        buttonTextForOff = 'Turn On';
                        requestValueSetOff = 0;
                        requestValueSetOn = 255;
			break;
		case '2':		                /* Open/Closed */
                        typeLabel = 'Open/Closed';
                        labelForOn = 'open';
			labelForOff = 'closed';
			break;
		case '3':		                /* Running/Stopped */
                        typeLabel = 'Running/Stopped';
                        labelForOn = 'running';
			labelForOff = 'stopped';
			break;
		case '4':		                /* Locked/Unlocked */
                        typeLabel = 'Locked/Unlocked';
                        labelForOn = 'locked';
			labelForOff = 'unlocked';
			buttonTextForOn = 'Unlock It';
			buttonTextForOff = 'Lock It';
                        requestValueSetOff = 0;
                        requestValueSetOn = 255;
			break;
                case '5':                               /* Status Only */
                        typeLabel = 'Status Only';
                        break;
                case '6':		                /* Percent */
                        typeLabel = 'Percent';
                        buttonTextForOn = 'Turn Off';
                        buttonTextForOff = 'Turn On';
                        requestValueSetOff = 0;
                        requestValueSetOn = 99;

			break;
	}
	return {
                        typeLabel: typeLabel,
                        labelForOn: labelForOn, 
			labelForOff: labelForOff, 
			buttonTextForOn: buttonTextForOn, 
                        buttonTextForOff: buttonTextForOff,
                        requestValueSetOff: requestValueSetOff,
                        requestValueSetOn: requestValueSetOn,
		};
}

function FormatDisplayLayoutTableRow(displayLayoutIndex)	{
	var deviceRef = displayLayoutTable[displayLayoutIndex].deviceRef;
	var deviceIndex = HS_appinfo_devicesIndexForRef(deviceRef);
	
	if (deviceIndex < 0) {
		return '';				/* no device, no status, no popup, no button */
	}
	var Status = HS_appinfo_devices[deviceIndex].status;
	var HS_ref = HS_appinfo_devices[deviceIndex].ref;
	var value = HS_appinfo_devices[deviceIndex].value;
	var displayText = displayLayoutTable[displayLayoutIndex].displayText;

	var tableHTML = '<tr><td>' + displayText + '</td>';

	var displayTypeInfo = DisplayTypeInfo(displayLayoutTable[displayLayoutIndex].displayType); 
	var buttonID;
	var ButtonText;
	var requestString;

	if (value == 0) {
		buttonID = displayLayoutTable[displayLayoutIndex].buttonIDAlt;
		ButtonText = displayTypeInfo.buttonTextForOff;
		requestString = 'request=controldevicebyvalue&ref=' + HS_ref + '&value=' + displayTypeInfo.requestValueSetOn;
	} else {
		buttonID = displayLayoutTable[displayLayoutIndex].buttonID;
		ButtonText = displayTypeInfo.buttonTextForOn;
		requestString = 'request=controldevicebyvalue&ref=' + HS_ref + '&value=' + displayTypeInfo.requestValueSetOff;
	}

	tableHTML += '<td>' + Status + '</td>';

	if (ButtonText && requestString) {
		tableHTML += '<td><button onClick="HS_action_button_clicked('+HS_ref+',\''+requestString+'\')">' + ButtonText + '</button></td>';								
	}
	if (buttonID != '') {
        var buttonIDArray = buttonID.split(/\s*,\s*/);
        var i;
        for (i = 0; i < buttonIDArray.length; i++) {
            var eventIndex = HS_eventinfo_eventsIndexForID(buttonIDArray[i]);
            if (eventIndex >= 0) {
                requestString = 'request=runevent&id=' + HS_eventinfo_events[eventIndex].id;
                var nameOfButton = HS_eventinfo_events[eventIndex].Name.replace(/\s*\(.*?\)\s*/g, '');  /* remove text within parentheses */
                tableHTML += '<td><button class="yellow" onClick="HS_action_button_clicked('+HS_ref+',\''+requestString+'\')">' + nameOfButton + '</button></td>';								
            }
        }
	}
	tableHTML += '</tr>';
	return tableHTML;
}			

function FormatDeviceReferenceList() {
<?php if ((isset($optionShowDevices) && ($optionShowDevices)) || (isset($optionShowBatteries)) && ($optionShowBatteries)) { ?>

				/* Device List for reference */
	
				var deviceTableMarkup = "<tr><th class='border'>Ref</th><th class='border'>Name</th><th class='border'>Room</th><th class='border'>Floor</th><th class='border'>Value</th><th class='border'>Status</th></tr>";
				for (i=0; i < HS_appinfo_devices.length; i++) {
					var deviceName = HS_appinfo_devices[i].name;
					var deviceTypeString = HS_appinfo_devices[i].device_type_string;
					if (<?php if (isset($optionShowBatteries)) { echo 'false'; } else { echo 'true'; }?> || (deviceName == 'Battery') || (deviceTypeString == 'Z-Wave Battery') ) { 
						var deviceRef = HS_appinfo_devices[i].ref;
						var deviceLocation = HS_appinfo_devices[i].location;
						var deviceLocation2 = HS_appinfo_devices[i].location2;
						var deviceValue = HS_appinfo_devices[i].value;
						var deviceStatus = HS_appinfo_devices[i].status;
						deviceTableMarkup = deviceTableMarkup + "<tr><td class='border'>HS#" + deviceRef + "</td><td class='border'>" + deviceName + "</td><td class='border'>" + deviceLocation + "</td><td class='border'>" + deviceLocation2 + "</td><td class='border'>" + deviceValue + "</td><td class='border'>" + deviceStatus + "</td></tr>";
					}
				}

				/* display it */
	
				document.getElementById('DeviceTable').innerHTML = deviceTableMarkup;
				document.getElementById('DeviceInfo').style.display = 'block';
<?php } ?>
}

function FormatEventReferenceList() {
<?php if (isset($optionShowDevices) && ($optionShowDevices)) { ?>
			
			/* Event List for reference */

			var eventTableMarkup = "<tr><th class='border'>ID</th><th class='border'>Group</th><th class='border'>Name</th></tr>";
			for (i=0; i < HS_eventinfo_events.length; i++) {
				var eventID = HS_eventinfo_events[i].id;
				var eventGroup = HS_eventinfo_events[i].Group;
				var eventName = HS_eventinfo_events[i].Name;
				eventTableMarkup = eventTableMarkup + "<tr><td class='border'>HSE#" + eventID + "</td><td class='border'>" + eventGroup + "</td><td class='border'>" + eventName + "</td></tr>";
			}
			document.getElementById('EventTable').innerHTML = eventTableMarkup;

			/* Display it. */
	
			document.getElementById("DeviceInfo").style.display = "block";
<?php } ?>
}

function HS_action_button_clicked(HS_ref,requestString) {
	$('.pre-loader').show();
	var deviceIndex = HS_appinfo_devicesIndexForRef(HS_ref);
	if (deviceIndex != -1) {
		HS_appinfo_devices[deviceIndex].status = 'Changing';
	}		
	
	console.log("Request string: " + requestString);
	$.ajax({
		url: '/service/hs-device-service.php',
		cache: false,
		data: requestString,
		success: function(result) {
			if (result === undefined) {
				return;
			}
			result = result.trim();
			console.log('CompleteListElementContainer.php - Result: ' + result)
		
			var message;
			if (result.slice(0,1) != '{') {		/* result may be an error message instead of JSON */
				message = result;
			} else {
				message = HS_appinfo = JSON.parse(result).Response;
			}
			if (message !== undefined) {
				console.log('CompleteListElementContainer.php - Response: ' + message)
				if (message !== 'ok') {
					document.getElementById('DeviceAlert').innerHTML = 				
						'<div id="DeviceAlertMessage" class="info closebox" style="color:#f44336">'
					 	+ '<button type="button" class="close" onClick="document.getElementById(\'DeviceAlertMessage\').style.display=\'none\';"><span>&times;</span></button>'
					 	+ '<p>Request string: ' + requestString + '</p><p>' + message + '</p></div>';
					document.getElementById('DeviceAlert').style.display = 'block';
				}
			}
	
			if (typeof StatusOnResize == 'function') { 
				StatusOnResize(); 
			}
			if (typeof DevicesOnResize == 'function') { 
				DevicesOnResize(); 
			}

			RetrieveDeviceDataSoon();
		}
	});
	<?php include('../include/startup_spinner_done.js'); ?> 
}

function FormatMessageForDeviceStatus($i) {
		var deviceIndex = HS_appinfo_devicesIndexForRef(displayLayoutTable[$i].deviceRef);
		if (deviceIndex < 0) {
			return null;				/* no device, no status, no display */
		}
		
		var status = HS_appinfo_devices[deviceIndex].status;
		var value = HS_appinfo_devices[deviceIndex].value;

		var message = displayLayoutTable[$i].displayText;
		if (displayLayoutTable[$i].displayText.charAt(displayLayoutTable[$i].displayText.length-1) == 's') {
			message += ' are ';
		} else {
			message += ' is ';
		}

		var alternate = (value == 0);
		switch(displayLayoutTable[$i].displayType) {
			case '1':									/* On/Off */
				if (value == 0) {
					message += 'off.';
				} else {
					message += 'on.';
				}
				break;
			case '2':									/* Open/Closed */
				if (value == 22) {
					message += 'open.';
					alternate = false;
				} else {
					message += 'closed.';
					alternate = true;
				}
				break;
			case '3':									/* Running/Stopped */
				message += status + '.';
				break;
			case '4':									/* Locked/Unlocked */
				if (value == 0) {
					message += 'unlocked.';
				} else {
					message += 'locked.';
				}
				break;
			case '6':									/* Percent */
				if (value == 0) {
					message += 'off.';
				} else {
					message += value + '%.';
				}
				break;
			default:
				message += status + '.';
		} 	

		if (alternate) {
			var color = displayLayoutTable[$i].displayColorAlt;
			var opacity = displayLayoutTable[$i].displayOpacityAlt;
			var beforeSecure = displayLayoutTable[$i].beforeSecure;
		} else {
			var color = displayLayoutTable[$i].displayColor;
			var opacity = displayLayoutTable[$i].displayOpacity;
			var beforeSecure = displayLayoutTable[$i].beforeSecureAlt;
		}

		return { 	message: message,
					color: color, 
					opacity: opacity, 
					beforeSecure: beforeSecure,
					status: status,
					value: value
				};
}		

/* functions related to HS_appinfo_devices */

function InterestedInAppinfoDevices(trueOrFalse) {
	if (watchingAppinfoDevices = trueOrFalse) {
		previous_HS_AppinfoDevicesGetstatusResult = '';
		RetrieveDeviceDataSoon();
	}
}
	
var refreshDeviceDataTimeout = false;

function RetrieveDeviceDataSoon() {
	clearTimeout(refreshDeviceDataTimeout);
	if (watchingAppinfoDevices) {
		refreshDeviceDataTimeout = setTimeout(RetrieveDeviceData, 1000);
	} else {
		refreshDeviceDataTimeout = setTimeout(RetrieveDeviceData, 5500);
	}
}

function RetrieveDeviceData() {

	$.ajax({
		url: '/service/hs-device-service.php',
		cache: false,
		data: {
			request: 'getstatus'
		},
		success: function( result ) {
			if (result === undefined) {
				return;
			}
			result = result.trim();
			if (result.slice(0,1) != '{') {		/* result may be an error message instead of JSON */
				document.getElementById('DeviceAlert').innerHTML = 				
					'<div id="DeviceAlertMessage" class="info closebox" style="color:#f44336">'
					 + '<button type="button" class="close" onClick="document.getElementById(\'DeviceAlertMessage\').style.display=\'none\';"><span>&times;</span></button>'
					 + result + '</div>';

				document.getElementById('DeviceAlert').style.display = 'block';
				return;
			}
			
			if (result != previous_HS_AppinfoDevicesGetstatusResult) {
				if (previous_HS_AppinfoDevicesGetstatusResult != '') {
					console.log('CompleteListElementContainer.php - Device service getstatus returned new result.');
				}
				previous_HS_AppinfoDevicesGetstatusResult = result;
				HS_appinfo = JSON.parse(result);
				HS_appinfo_devices = HS_appinfo.Devices;

				document.getElementById('DeviceAlert').innerHTML = '';

				<?php include('../include/startup_spinner_done.js'); ?> 

				if (watchingAppinfoDevices) {
					var messageHtml = '';
                    var itemCount = 0;
					for ($i = 0; $i < displayLayoutTable.length; $i++) {
						var statusInfo = FormatMessageForDeviceStatus($i);
						if (statusInfo && (statusInfo.beforeSecure == '1')) {
							messageHtml += statusInfo.message + '<br>';
                            itemCount++
						}
					}
					if (itemCount == 1) {
						messageHtml = '<p>The change will wait until the following is cleared:</p>' + messageHtml;
					} else if (itemCount != 0) {
						messageHtml = '<p>The change will wait until the following are cleared:</p>' + messageHtml;
					}
					$('#confirmation-message').html(messageHtml);		
				}

				if (typeof DBUpdateRefChanged == 'function') { 
					DBUpdateRefChanged(); 
				}
				
				FormatDeviceReferenceList();

				if (typeof StatusOnResize == 'function') { 
					StatusOnResize(); 
				}
				if (typeof DevicesOnResize == 'function') { 
					DevicesOnResize(); 
				}
				
			}	/* end of processing new result */
			
		}
	});

	if (watchingAppinfoDevices) {
		refreshDeviceDataTimeout = setTimeout(RetrieveDeviceData, 5000);
	} else {
		refreshDeviceDataTimeout = setTimeout(RetrieveDeviceData, 30000);
	}
}

function HS_appinfo_devicesIndexForRef(ref) {
	var ref_number = ref;
	if ((ref.length > 3) && (ref.substring(0,3) == 'HS#')) {
		ref_number = ref.substring(3);
	}
	for (index = 0; index < HS_appinfo_devices.length; index++) {	
		if (HS_appinfo_devices[index].ref == ref_number) {
			break;
		}
	}
	if (index >= HS_appinfo_devices.length) {
		return -1;
	} else {
		return index;
	}
}

/* functions related to HS_eventinfo_events */

function HS_eventinfo_eventsIndexForID(eventID) {
	if ( typeof HS_eventinfo_events == 'undefined' || !HS_eventinfo_events ) {
		return -1;
	}
	var eventID_number = eventID;
	if ((eventID.length > 3) && (eventID.substring(0,4) == 'HSE#')) {
		eventID_number = eventID.substring(4);
	}
	for (index = 0; index < HS_eventinfo_events.length; index++) {	
		if (HS_eventinfo_events[index].id == eventID_number) {
			break;
		}
	}
	if (index >= HS_eventinfo_events.length) {
		return -1;
	} else {
		return index;
	}
}

function RetrieveEventData() {

	$.ajax({
		url: '/service/hs-device-service.php',
		cache: false,
		data: {
			request: 'getevents'
		},
		success: function( result ) {
			if (result === undefined) {
				return;
			}
			result = result.trim();
			if (result.slice(0,1) != '{') {		/* result may be an error message instead of JSON */
				document.getElementById('DeviceAlert').innerHTML = 				
					'<div id="DeviceAlertMessage" class="info closebox" style="color:#f44336">'
					 + '<button type="button" class="close" onClick="document.getElementById(\'DeviceAlertMessage\').style.display=\'none\';"><span>&times;</span></button>'
					 + result + '</div>';

				document.getElementById('DeviceAlert').style.display = 'block';
				return;
			}
			HS_eventinfo = JSON.parse(result);
			HS_eventinfo_events = HS_eventinfo.Events;

			document.getElementById('DeviceAlert').innerHTML = '';
			
			if (typeof DBUpdateButtonChanged == 'function') { 
				DBUpdateButtonChanged(); 
			}
			
			FormatEventReferenceList()
		}
	});
}

RetrieveEventData();		/* run it once (it is relatively static)*/
</script>
