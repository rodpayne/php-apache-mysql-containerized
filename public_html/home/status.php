<?php require('../include/check_session_login.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Status</title>
	<?php require('../include/app_standard_head.html'); ?>	
</head>

<body onLoad='javascript:StatusOnPageLoad()'>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php include('../include/startup_spinner.html'); ?> 
<div class="center" style="margin-bottom: 2vh; width: 90%">
<?php
//-----------------------------------------------------------------------------
//	Get (relatively static) house modes info:
//-----------------------------------------------------------------------------

	$houseModesTable = Get_Configuration_Item($houseID,'housemodes');
	if (! $houseModesTable) {
		echo '<div id="MessageAboutHouseModes" class="closebox">';
		echo '<button type="button" class="close" onClick="document.getElementById(\'MessageAboutHouseModes\').style.display=\'none\';"><span>&times;</span></button>';
		echo 'House Modes were not yet set up in the database.';
		$houseModesTable[0] = array('Mode'=>'0', 'Color'=>'var(--color-red)', 'Name'=>'Vacation', 'Description'=>'House is unoccupied.', 'Alarms'=>'All alarms active.', 'Temperature'=>'Set back.', 'Order'=>'1', 'Occupied'=>'Secure');
		$houseModesTable[1] = array('Mode'=>'1', 'Color'=>'var(--color-yellow)', 'Name'=>'Away', 'Description'=>'House is unoccupied except for pets.', 'Alarms'=>'All except motion.', 'Temperature'=>'Set back.', 'Order'=>'2', 'Occupied'=>'Secure');
		$houseModesTable[2] = array('Mode'=>'2', 'Color'=>'var(--color-yellow)', 'Name'=>'Standby', 'Description'=>'House should get ready to be occupied.', 'Alarms'=>'All except motion.', 'Temperature'=>'Normal.', 'Order'=>'3', 'Occupied'=>'Secure');
		$houseModesTable[3] = array('Mode'=>'3', 'Color'=>'var(--color-green)', 'Name'=>'Occupied', 'Description'=>'House is occupied.', 'Alarms'=>'Voice for most.  Alarm on break-in.', 'Temperature'=>'Normal.', 'Order'=>'4', 'Occupied'=>'Occupied');
		$houseModesTable[4] = array('Mode'=>'4', 'Color'=>'Black', 'Name'=>'Night Security', 'Description'=>'House is occupied, but locked for the night.', 'Alarms'=>'Voice, then alarm.', 'Temperature'=>'Set back.', 'Order'=>'5', 'Occupied'=>'Occupied');
		Set_Configuration_Item($houseID,'housemodes',urlencode(json_encode($houseModesTable)));	
		echo '</div>';
	}
	if ($houseModesTable) {
		usort($houseModesTable, function($a, $b) {
			return strcmp($a['Order'], $b['Order']);
		});
	}

	$currentHouseMode = Get_Configuration_Item($houseID,'currenthousemode');	

	function houseModesTableIndexForMode($Mode,$houseModesTable) {
		for ($i=0; $i < count($houseModesTable); $i++) {	
			if ($houseModesTable[$i]['Mode'] == $Mode) {
				break;
			}
		}
		return $i;
	}

//-----------------------------------------------------------------------------
//	Process the submit of the form:
//-----------------------------------------------------------------------------

	$error = '';				// feedback to be displayed on the form

	if (isset($_POST['submitButton'])) {
		$whichSubmitButton = $_POST['submitButton'];
		echo '<div id="MessageFromUpdate" class="closebox">';
		echo '<button type="button" class="close" onClick="document.getElementById(\'MessageFromUpdate\').style.display=\'none\';"><span>&times;</span></button>';

        /* ------------------------------------------------------------------------------- */
        /*	get house configuration to retrieve the device controller location parameters  */
        /* ------------------------------------------------------------------------------- */


        $houseInfo = Get_Configuration_Item($houseID,'houseInfo');
        if (! $houseInfo) {
            echo "Error: House configuration info is not available. \n";
			exit;		
        }

// Method: POST, PUT, GET, etc.
// Data: array("param" => "value") ==> index.php?param=value

function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

//    // Optional Authentication:
//    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//    curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}
        
		/* ---------------------------- */
		/*  set the current house mode  */
		/* ---------------------------- */
		
		if (substr($whichSubmitButton,0,15) == 'set house mode ') {
			$newHouseMode = substr($whichSubmitButton,15);

            if ($newHouseMode == $currentHouseMode) {
                $message =  'House mode has already changed to ' . $houseModesTable[houseModesTableIndexForMode($newHouseMode,$houseModesTable)]['Name'] . ' somehow.';
            } else {
                
                /* set it in the database */
                
                Set_Configuration_Item($houseID,'currenthousemode',$newHouseMode);

                $message =  'House mode changed from '.$houseModesTable[houseModesTableIndexForMode($currentHouseMode,$houseModesTable)]['Name'] . ' to ' . $houseModesTable[houseModesTableIndexForMode($newHouseMode,$houseModesTable)]['Name'] . '.';
                $currentHouseMode = $newHouseMode;
            }

            echo "<p>$message</p>";
            Write_Log_Message ('info',$message);

            /* set it in HomeSeer */
            
            if ($houseInfo['HSSource'] == 'local') {
#                $HSresponse = file_get_contents($houseInfo['HSURL'].'JSON?request=controldevicebyvalue&ref=70&value=' . $newHouseMode);
                $HSresponse = CallAPI('GET', $houseInfo['HSURL'].'JSON?request=controldevicebyvalue&ref=70&value=' . $newHouseMode);
                Write_Log_Message ('debug','HS Response = ' . $HSresponse);
            }
            
            /* set it in Home Assistant */
            
            if ($houseInfo['HASource'] == 'local') {
                $HAresponse = CallAPI('POST', $houseInfo['HAURL'] . 'api/states/input_select.current_house_mode?api_password=' . $houseInfo['HAPassword'], '{"state": "' . $houseModesTable[houseModesTableIndexForMode($newHouseMode,$houseModesTable)]['Name'] .'",  "attributes": {"options": ["Vacation","Away","Standby","Occupied","Night Security"], "friendly_name": "Current House Mode"}}');
                Write_Log_Message ('debug','HA Response = ' . $HAresponse);
            }
            
		/* ----------- */
		/*  otherwise  */
		/* ----------- */
		
		} else {
			echo "<p style='color: red;'>This function is not yet implemented.</p>";
		}
		echo '</div>';
	}
?> 
<script>
	var houseModesTable = <?php echo json_encode($houseModesTable); ?>;

	var currentHouseMode = <?php echo "'" . $currentHouseMode . "'"; ?>;
	var clickedHouseMode;
	var watchingCurrentHouseMode = false;
				 	
</script>
<form id="HouseModeSubmitForm" class="info border content" action="" method="post">
	<input id="HouseModeSubmitButton" name="submitButton" type="hidden">
<div id="HouseModesDisplay" class="container" style="position:relative;">
<script>
function SubmitHouseModeForm(houseMode) {
	var HouseModeSubmitButton = document.getElementById('HouseModeSubmitButton');
	HouseModeSubmitButton.value = 'set house mode '+houseMode;
	$('.pre-loader').show();	
	var HouseModeSubmitForm = document.getElementById('HouseModeSubmitForm');
	HouseModeSubmitForm.submit();	
}

function DisplayHouseModes(currentHouseMode,houseModesTable) {
	var tableHTML = '<table class="border-td center-text" style="width: 90%">';
	tableHTML += '<tr><th>Name</th><th>Description</th><th class="optional-400">Alarms</th><th class="optional-500">Temperature</th></tr>';
	for (i = 0; i < houseModesTable.length; i++) {
		if (currentHouseMode == houseModesTable[i]['Mode']) {
			tableHTML += '<tr id="Mode-' + houseModesTable[i]['Mode'] + '" class="selected"><td style="background-color: '+houseModesTable[i]['Color']+'">';
		} else {
			tableHTML += '<tr id="Mode-' + houseModesTable[i]['Mode'] + '" class="not-selected"><td>';
		}
		tableHTML += '<button style="background-color: '+houseModesTable[i]['Color']+'; ';
		if (! houseModesTable[i]['Color'].includes('yellow')) {
			tableHTML += 'color: white; ';
		}
		tableHTML += '" name="submitButton" type="submit"';
		tableHTML += ' onClick="clickedHouseMode='+houseModesTable[i]['Mode']+';return SetHouseMode()"';
		tableHTML += '>';
		tableHTML += houseModesTable[i]['Name']+'</button>';
		tableHTML += '		</td>';
		tableHTML += '		<td>'+houseModesTable[i]['Description']+'</td>';
		tableHTML += '		<td class="optional-400">'+houseModesTable[i]['Alarms']+'</td>';
		tableHTML += '		<td class="optional-500">'+houseModesTable[i]['Temperature']+'</td>';
		if (currentHouseMode == houseModesTable[i]['Mode']) {
			tableHTML += '		'+'<td>&nbsp;&#10004;&nbsp;</td>';
		}
		tableHTML += '	</tr>';		
	}
	tableHTML += '</table>';
	document.getElementById('HouseModesDisplay').innerHTML = tableHTML;
}

function SetHouseMode() {
	if (clickedHouseMode == currentHouseMode) {
		return false;
	} else {
		var clickedHouseModeIndex = HouseModesTableIndexForMode(clickedHouseMode,houseModesTable);
		var currentHouseModeIndex = HouseModesTableIndexForMode(currentHouseMode,houseModesTable);
		if (houseModesTable[clickedHouseModeIndex]['Occupied'] == 'Secure') {				/* switching to secure */ 
			if (houseModesTable[currentHouseModeIndex]['Occupied'] == 'Occupied') {		/* from occupied */ 
				ShowConfirmationTimer(clickedHouseMode);
				return false;
			}
		} else { 																	/* switching to occupied */
			if (houseModesTable[currentHouseModeIndex]['Occupied'] == 'Secure') {			/* from secure */ 
				ShowConfirmationPinPad(clickedHouseMode);
				return false;
			}
		}

		ConfirmationSatisfied(clickedHouseMode);
		return false;
	}
}

function ConfirmationSatisfied(clickedHouseMode) {
		InterestedInCurrentHouseMode(false);
		InterestedInAppinfoDevices(false);
		$('#confirmation-screen').hide();

		SubmitHouseModeForm(clickedHouseMode);
}
	
function ConfirmationCancelled() {
		InterestedInCurrentHouseMode(false);
		InterestedInAppinfoDevices(false);
		$('#confirmation-screen').hide();
}
	
DisplayHouseModes(currentHouseMode,houseModesTable);

function DisplayHouseModeChange(currentHouseMode,houseModesTable,clickedHouseMode) {
	var clickedHouseModeIndex = HouseModesTableIndexForMode(clickedHouseMode,houseModesTable);
	var currentHouseModeIndex = HouseModesTableIndexForMode(currentHouseMode,houseModesTable);

	var message = 'Changing house mode from ';
	message += '<span style="background-color: '+houseModesTable[currentHouseModeIndex]['Color']+'; ';
	if (! houseModesTable[currentHouseModeIndex]['Color'].includes('yellow')) {
		message += 'color: white; ';
	}
	message += '">&nbsp;<b>'+houseModesTable[currentHouseModeIndex]['Name']+'</b>&nbsp;</span>';
	
	message += ' to ';
	
	message += '<span style="background-color: '+houseModesTable[clickedHouseModeIndex]['Color']+'; ';
	if (! houseModesTable[clickedHouseModeIndex]['Color'].includes('yellow')) {
		message += 'color: white; ';
	}
	message += '">&nbsp;<b>'+houseModesTable[clickedHouseModeIndex]['Name']+'</b>&nbsp;</span>';
	
	return message;
}

function InterestedInCurrentHouseMode(trueOrFalse) {
	if (watchingCurrentHouseMode = trueOrFalse) {
		RetrieveCurrentHouseModeSoon();
	}
}
	
var refreshTimeout = false;

function RetrieveCurrentHouseModeSoon() {
	clearTimeout(refreshTimeout);
	if (watchingCurrentHouseMode) {
		refreshTimeout = setTimeout(RetrieveCurrentHouseMode, 1000);
	} else {
		refreshTimeout = setTimeout(RetrieveCurrentHouseMode, 5500);
	}
}

function RetrieveCurrentHouseMode() {
	$.ajax({
		url: '/service/config-service.php',
		cache: false,
		data: {
			request: "currenthousemode"
		},
		success: function( result ) {
			if (result === undefined) {
				return;
			}
			newHouseMode = JSON.parse(result.trim());
	
			if (newHouseMode != currentHouseMode) {
				ConfirmationCancelled();

				currentHouseMode = newHouseMode;
				DisplayHouseModes(currentHouseMode,houseModesTable);
			}
		}
	});
	if (watchingCurrentHouseMode) {
		refreshTimeout = setTimeout(RetrieveCurrentHouseMode, 1000);
	} else {
		refreshTimeout = setTimeout(RetrieveCurrentHouseMode, 30000);
	}
}
</script>
        <?php $show_mapping = FALSE; include('../include/person_status_badges.php'); ?>
	</div></form>
</div>
<div  id='WeatherAlertContainer'><span id='WeatherAlert'></span></div>
<?php 
//-----------------------------------------------------------------------------
//	Get the weather info to provide weather alerts:
//-----------------------------------------------------------------------------
	$mapIndex=0; 
	require('../include/weather-alert-scripts.php'); 
?>
<script>

function RetrieveWeatherData() {
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
				document.getElementById("WeatherAlert").innerHTML = "<p style='color:var(--color-red);'>"+result+"</p>";
				document.getElementById("WeatherAlertContainer").style.display = "block";
				return;
			} else {
				document.getElementById("WeatherAlert").innerHTML = "";
				document.getElementById("WeatherAlertContainer").style.display = "none";
			}
			
			weatherAppInfo = JSON.parse(result);

			SaveWeatherIconAndAlertInfo();
		}
	});
}

setTimeout(RetrieveWeatherData,1 * 1000);	
var weatherTimer = setInterval(RetrieveWeatherData,60 * 1000);	

</script>
<?php $location='AlarmStatus'; $locationDisplayName='Alarm Status'; require('../include/CompleteListElementContainer.php'); ?>
<?php
//-----------------------------------------------------------------------------
//	Hide the web page and show a confirmation-screen for changing house mode:
//-----------------------------------------------------------------------------
?>
<div id="confirmation-screen" class="container-fluid" style="display: none;">
	<div id="confirmation-content">
<?php
		/* ------------------------------------------------------------------------- */
		/*  ShowConfirmationTimer() function providing a countdown timer before "Away" status:  */
		/* ------------------------------------------------------------------------- */
?>
		<div id="countdown-timer">
			<h1>Exit Now</h1>
			<div id="countdown-timer-message"></div>
			<div id="countdown-clock"></div>
			<button type="button" class="btn countdown-btn" id="btn-cancel">
				<i class="fas fa-stop"></i>
				Cancel
			</button>
			<button type="button" class="btn countdown-btn" id="btn-reset">
				<i class="fas fa-fast-backward"></i> 
				Restart
			</button>
			<button type="button" class="btn countdown-btn" id="btn-pause">
				<i class="fas fa-pause"></i> 
				Pause
			</button>
			<button type="button" class="btn countdown-btn" disabled id="btn-resume">
				<i class="fas fa-play"></i>
				Resume
			</button>
			<button type="button" class="btn countdown-btn" id="btn-immediate">
				<i class="fas fa-check-circle"></i>
				Immediate
			</button>
		</div>
<?php
		/* ----------------------------------------------------------------------*/
		/*  ShowConfirmationPinPad() function to enter a pin to turn off the "Away" status:  */
		/* --------------------------------------------------------------------- */
?>
		<div id="pin-pad">
			<h1>Please enter passcode</h1>
			<div id="pin-pad-message"></div>
			<input type="text" id="pin-pad-code" name="pin-pad-code" width="6" />
<!-- http://jsfiddle.net/gweebz/bRu88/ -->
<!-- TODO: look at https://css-tricks.com/better-password-inputs-iphone-style/ -->
			<div class="keypad">
				<div class="key-row">
					<div class="key-cell">
						<button class="btn key numberkey" type="button">1<br/><span class="key-alt-fn">-</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key numberkey" type="button">2<br/><span class="key-alt-fn">ABC</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key numberkey" type="button">3<br/><span class="key-alt-fn">DEF</span></button>
					</div>
				</div>
				<div class="key-row">
					<div class="key-cell">
						<button class="btn key numberkey" type="button">4<br/><span class="key-alt-fn">GHI</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key numberkey" type="button">5<br/><span class="key-alt-fn">JKL</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key numberkey" type="button">6<br/><span class="key-alt-fn">MNO</span></button>
					</div>
				</div>
				<div class="key-row">
					<div class="key-cell">
						<button class="btn key numberkey" type="button">7<br/><span class="key-alt-fn">PQRS</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key numberkey" type="button">8<br/><span class="key-alt-fn">TUV</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key numberkey" type="button">9<br/><span class="key-alt-fn">WXYZ</span></button>
					</div>
				</div>
				<div class="key-row">
					<div class="key-cell">
						<button class="btn key" id="btn-pinpad-cancel" type="button"><i class="fas fa-stop"></i><br/><span class="key-alt-fn">Cancel</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key numberkey" type="button">0<br/><span class="key-alt-fn">-</span></button>
					</div>
					<div class="key-cell">
						<button class="btn key" id="btn-pinpad-delete" type="button"><i class="fas fa-eraser"></i><br/><span class="key-alt-fn">Delete</span></button>
					</div>
				</div>
			</div>			
		</div>
		<div id="confirmation-message">test</div>
    </div>
</div>
<style>

/* ----- status.php ----- */

#confirmation-screen {
	z-index: 1025; 		/* over page, including header menu */
    position: fixed;
    height: 100%;
    width: 100%;
    left: 0;
    top: 0;
	background-color: white;
	opacity: 0.98;
}

#confirmation-content {
	text-align: center;
	margin: auto;
	width: 80%;
	background-color: white;
	opacity: 1;
	
	position: absolute;
    top: 45%;
    left: 50%;
    transform: translate(-50%, -50%);}

#countdown-clock {
      margin: 5vh 0;
	  font-size: 12vw;
}

.countdown-img {
	height: 30px;
	width: auto;
	position: relative;
	top: 8px;
}

.countdown-btn {
	margin: 0.5vw;
    padding: var(--button-padding);
	font-size: var(--button-font-size);
	background-color: lightgray;
	border: 1px solid black;
}

.countdown-btn:disabled {
	border: 1px solid transparent;
}

#pin-pad-code {
	width: 120px;
	margin-top: 20px;
}

.keypad {
    display: table;
    margin: 10px auto;
    padding: 0;
    width: 300px; /* not actually a static size, % in real use */
}

.keypad .key-row {
    display: table-row;
    width: 100%
}

.keypad .key-row .key-cell {
    display: table-cell;
    padding: 0.6vh 0.6vw;
    width: 33%;
}

.keypad .key-row .key-cell button.key {
    font-size: medium;
    font-weight: var(--font-weight-bold);
    height: 70px;
    width: 100%;
	background-color: lightgray;
	border: 1px solid black;
}

.keypad .key-row .key-cell button .key-alt-fn {
    font-size: small;
    font-weight: normal;
}

@media only screen and (max-width: 480px) { 			/* iPhone [portrait + landscape] */
	#confirmation-content {
		width: 100%;
	}
	.keypad .key-row .key-cell button.key {
		height: 14vh;
	}
	#confirmation-content {
		font-size: 4vw;
	}
}
</style>
<?php
		/* ----------------------------------------------------------- */
		/*  Javascript functions specific to the confirmation-screen:  */
		/*------------------------------------------------------------ */
?>
<script src='../js/jquery.countdown.min.js'></script>
<script>   /* http://hilios.github.io/jQuery.countdown/documentation.html */
		
var blipSound = new Audio('../images/Robot_blip-Marianne_Gagnon-120342607 (2).wav'); // buffers automatically when created

function PlayBlipSound() {  
	setTimeout(function(){ blipSound.play(); }, 0);
}

function timeFromNow(delaySeconds) {
	return new Date(new Date().valueOf() + delaySeconds * 1000);
}

var delaySeconds = 45;
var countdownTimeRemaining;

function ShowConfirmationTimer() {

	var $countdownClock = $('#countdown-clock');
	$countdownClock.countdown(timeFromNow(delaySeconds),{elapse: true})
	.on('update.countdown', function(event) {
		countdownTimeRemaining = event.offset.totalSeconds;
		var format = '%-S second%!S';
		if(event.offset.totalMinutes >= 1) {
			format = '%M:%S';
		}
		if(event.offset.totalHours >= 1) {
			format = '%H:' + format;
		}
		if(event.offset.days > 0) {
			format = '%-d day%!d ' + format;
		}
		if(event.offset.weeks > 0) {
			format = '%-w week%!w ' + format;
		}
		if (event.elapsed) { 						/* satisfaction when {elapse: true} is set */
			format = '+ ' + format;
			if ($('#confirmation-message').html() == '') {
				$(this).html('Timer has expired!')
				$('#countdown-clock').countdown('stop');
				ConfirmationSatisfied(clickedHouseMode);
			}
		}
		$(this).html(event.strftime(format));
		PlayBlipSound();  
	})
	.on('finish.countdown', function(event) {		/* satisfaction when {elapse: true} is NOT set */
		$(this).html('Timer has expired!')
		ConfirmationSatisfied(clickedHouseMode);
	});

	$('#countdown-timer-message').html(DisplayHouseModeChange(currentHouseMode,houseModesTable,clickedHouseMode));
	$('#confirmation-message').html('');
  
	$('#btn-resume').prop("disabled",true);
	$('#btn-pause').prop("disabled",false);

	$('#pin-pad').hide();
	$('#countdown-timer').show();
	$('#confirmation-screen').show();

	InterestedInCurrentHouseMode(true);
	InterestedInAppinfoDevices(true);
}

$('#btn-reset').click(function() {
	$('#countdown-clock').countdown(timeFromNow(delaySeconds));
	$('#btn-resume').prop("disabled",true);
	$('#btn-pause').prop("disabled",false);
});

$('#btn-cancel').click(function() {
	$('#countdown-clock').countdown('stop');
	ConfirmationCancelled();
});

$('#btn-pause').click(function() {
	$('#countdown-clock').countdown('pause');
	$('#btn-resume').prop("disabled",false);
	$('#btn-pause').prop("disabled",true);
});

$('#btn-resume').click(function() {
	$('#countdown-clock').countdown(timeFromNow(countdownTimeRemaining),{elapse: true});
/*		$('#countdown-clock').countdown('resume'); 	*/
	$('#btn-resume').prop("disabled",true);
	$('#btn-pause').prop("disabled",false);
});
  
$('#btn-immediate').click(function() {
	$('#countdown-clock').countdown('stop');
	ConfirmationSatisfied(clickedHouseMode);
});

function ShowConfirmationPinPad() {	
	var currentTime = (new Date()).getTime() / 1000;
	if ((currentTime - <?php echo $loginTime; ?>) < 30) {	/* don't require pincode right after login */ 
		ConfirmationSatisfied(clickedHouseMode);
	} else {
	
		$('#pin-pad-message').html(DisplayHouseModeChange(currentHouseMode,houseModesTable,clickedHouseMode));
		$('#pin-pad-code').val('');
		$('#confirmation-message').html('');

		$('#pin-pad').show();
		$('#countdown-timer').hide();
		$('#confirmation-screen').show();
		InterestedInCurrentHouseMode(true);
	}
}

function CheckPinPadCode(clickedHouseMode) {
	var pinPadCode = $('#pin-pad-code').val();
	/* TODO: use list of hashed pincodes */
	if (pinPadCode == '2373') {		
		$('#pin-pad').hide();
		/* TODO: save time of unlock so that panel can be skipped within a grace period */
		ConfirmationSatisfied(clickedHouseMode);
	}
}

$('.numberkey').click(function() {
	$('#pin-pad-code').val($('#pin-pad-code').val() + $(this).html().substring(0, 1));
	PlayBlipSound();
	CheckPinPadCode(clickedHouseMode);
});

$('#btn-pinpad-cancel').click(function() {
	ConfirmationCancelled();
});

$('#btn-pinpad-delete').click(function() {
	var pinPadCode = $('#pin-pad-code').val();
	$('#pin-pad-code').val(pinPadCode.substring(0, pinPadCode.length-1));
});

$('#pin-pad-code').on('input', function() {		
	CheckPinPadCode(clickedHouseMode);
});
<?php
//-----------------------------------------------------------------------------
//	Javascript page functions:
//-----------------------------------------------------------------------------
?>
function StatusOnPageLoad() {
	console.log('status.php - StatusOnPageLoad()');
	RetrieveDeviceData();
	RetrieveCurrentHouseModeSoon();
	window.addEventListener('resize', StatusOnResize);
	window.addEventListener('orientationchange', StatusOnResize);
}

/* OnResize event is debounced - see http://bencentra.com/code/2015/02/27/optimizing-window-resize.html */

var resizeTimeout = false;
var resizeDelay = 100; 

function StatusOnResize() {
	clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(StatusActualOnResize, resizeDelay);
}

function StatusActualOnResize() {
    PositionPersonContainers()
    
	FormatCompleteList();
}
</script>
</body>
</html>
