<?php require("../include/check_session_login.php"); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Devices</title>
	<?php require("../include/app_standard_head.html"); ?>
</head>

<body onLoad='javascript:DevicesOnPageLoad()'>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php include('../include/startup_spinner.html'); ?> 
<style>
#FloorImage {
    max-width: 100%;
    height: auto;
	border: 2px solid black;
	border-top: 0;
} 

#PopupElementContainer {
	display: none;
	position: absolute; 
		top: 60px;
		left: 60px;
	width:80vw;
	border-radius:16px;
	z-index: 1010; 			/* Sit on top */
    overflow: auto; 		/* Enable scroll if needed */
    background-color: rgb(0,0,0); 		/* Fallback color */
    background-color: rgba(0,0,0,0.5); 	/* Black w/ opacity */
	box-shadow: 8px 8px 8px rgba(0,0,0,0.2);
	font-size: 2.1vw;
	padding: 1%;
}
@media screen and (min-width: 760px) {
  #PopupElementContainer {
    font: normal 16px sans-serif;		/* max font size */
	width:460px;						/* max width */
  }
}

</style>
<?php
	
	if (isset($_GET['update']) && (strpos($userAttributes,'/setup/') !== FALSE)) {
		$optionDBUpdate = TRUE;
		$optionShowDevices = TRUE;
	} else {
		$optionDBUpdate = FALSE;
		$optionShowDevices = FALSE;
	}
	
if ((! $isMobile) && ($optionDBUpdate)) { 
//-----------------------------------------------------------------------------
//	As an option, a form is provided to allow update of the device layout DB.
//-----------------------------------------------------------------------------
//	Process the submit of the DB Update form:
//-----------------------------------------------------------------------------
	$DBUpdateError = '';
	
	function quote_escape($html_escape) {
		$html_escape =  str_replace('"', '\"', $html_escape);
		return $html_escape;
	}

	$error = '';				// feedback for the next display of the form

	if (isset($_POST['submitButton'])) {
			
		//	Get values from the form:

            $displayID = $_POST['displayID'];
			$deviceRef = $_POST['deviceRef'];
			$displayType = $_POST['displayType'];
			$displayXCoord = $_POST['displayXCoord'];
			$displayYCoord = $_POST['displayYCoord'];
			$displayWidth = $_POST['displayWidth'];
			$displayHeight = $_POST['displayHeight'];
			$displayColor = $_POST['displayColor'];
			$displayOpacity = $_POST['displayOpacity'];
			$displayColorAlt = $_POST['displayColorAlt'];
			$displayOpacityAlt = $_POST['displayOpacityAlt'];
			$buttonID = $_POST['buttonID'];
			$buttonIDAlt = $_POST['buttonIDAlt'];
			if (isset($_POST['beforeSecure'])) {
				$beforeSecure = '1';
			} else {
				$beforeSecure = '0';
			}
			if (isset($_POST['beforeSecureAlt'])) {
				$beforeSecureAlt = '1';
			} else {
				$beforeSecureAlt = '0';
			}
			$displayText = $_POST['displayText'];
			
			if ($_POST['submitButton'] == "save") {
		
				Do { 				// ---------- "function" to update database ----------
		
			//	Validate the form's values:

					if (($displayID != 'new') && (!is_numeric($displayID))) {
						$DBUpdateError .= "Item ID must be 'new' or match an item. ";
					}
					# deviceRef
					if ( strlen($deviceRef) > 12) {
						$DBUpdateError .= "Device Reference is too long. ";
					}
					# displayType
					# displayXCoord
					if (!is_numeric($displayXCoord) || ($displayXCoord < 0) || ($displayXCoord > 1024)) {
						$DBUpdateError .= "Coordinate must be at least 0 and no more than 1024. ";
					}
					# displayYCoord
					if (!is_numeric($displayYCoord) || ($displayYCoord < 0) || ($displayYCoord > 768)) {
						$DBUpdateError .= "Coordinate must be at least 0 and no more than 768. ";
					}
					# displayWidth
					if (!is_numeric($displayWidth) || ($displayWidth < 5) || ($displayWidth > 1024)) {
						$DBUpdateError .= "Width must be at least 5 and no more than 1024. ";
					}
					# displayHeight
					if (!is_numeric($displayHeight) || ($displayHeight < 5) || ($displayHeight > 768)) {
						$DBUpdateError .= "Height must be at least 5 and less than 768. ";
					}
					# displayColor
					if (!preg_match('/^\d+,\d+,\d+$/',$displayColor)) {
						$DBUpdateError .= "Color is invalid. ";
					}
					# displayOpacity
					if (!is_numeric($displayOpacity) || ($displayOpacity < 0) || ($displayOpacity > 1)) {
						$DBUpdateError .= "Opacity must be between 0 and 1. ";
					}
					# displayColorAlt
					if (!preg_match('/^\d+,\d+,\d+$/',$displayColorAlt)) {
						$DBUpdateError .= "Alternate Color is invalid. ";
					}
					# displayOpacityAlt
					if (!is_numeric($displayOpacityAlt) || ($displayOpacityAlt < 0) || ($displayOpacityAlt > 1)) {
						$DBUpdateError .= "Alternate Opacity must be between 0 and 1. ";
					}
					# buttonID
					if ( strlen($buttonID) > 32) {
						$DBUpdateError .= "Button ID is too long. ";
					}
					# buttonIDAlt
					if ( strlen($buttonIDAlt) > 32) {
						$DBUpdateError .= "Alternate Button ID is too long. ";
					}
					# beforeSecure
					# beforeSecureAlt
					# displayText
					if ( strlen($displayText) > 32) {
						$DBUpdateError .= "Text is too long. ";
					}
	
					if ($DBUpdateError !== '') {
						break;
					} 

					if ($static_config['DB']['useDatabase']) {


				//	Do insert or update:

						if ($displayID == 'new') {
							$query = "INSERT INTO `displaylayout` (`ID`, `houseID`, `displayLocation`, `deviceRef`, `displayType`, `displayXCoord`, `displayYCoord`, `displayWidth`, `displayHeight`, `displayColor`, `displayOpacity`, `buttonID`, `displayColorAlt`, `displayOpacityAlt`, `buttonIDAlt`, `beforeSecure`, `beforeSecureAlt`, `displayText`) VALUES (NULL, '$houseID', '$location', '".Escape_SQL($deviceRef)."', '$displayType', '$displayXCoord', '$displayYCoord', '$displayWidth', '$displayHeight', '$displayColor', '$displayOpacity', '".Escape_SQL($buttonID)."', '$displayColorAlt', '$displayOpacityAlt', '".Escape_SQL($buttonIDAlt)."', '".$beforeSecure."', '".$beforeSecureAlt."', '".Escape_SQL($displayText)."')";
						} else {	
							$query = "UPDATE `displaylayout` SET `deviceRef` = '".Escape_SQL($deviceRef)."', `displayText` = '".Escape_SQL($displayText)."', `displayType` = '$displayType', `displayXCoord` = '$displayXCoord', `displayYCoord` = '$displayYCoord', `displayWidth` = '$displayWidth', `displayHeight` = '$displayHeight', `displayColor` = '$displayColor', `displayOpacity` = '$displayOpacity', `buttonID` = '".Escape_SQL($buttonID)."', `displayColorAlt` = '$displayColorAlt', `displayOpacityAlt` = '$displayOpacityAlt', `buttonIDAlt` = '".Escape_SQL($buttonIDAlt)."', `beforeSecure` = '".$beforeSecure."', `beforeSecureAlt` = '".$beforeSecureAlt."' WHERE `displaylayout`.`ID` = $displayID"; 
						}	
						list($result,$count,$DBUpdateError) = Execute_SQL($query);
						if ($DBUpdateError) {
							break;
						}
						if ($displayID == 'new') {
							$displayID = $count;
						}

						$DBUpdateError = "Item saved!";
					} else {
						$DBUpdateError = "Database is not available.";
					}
				} while (FALSE);	// ---------- end of inline "function" -----------
		
			} elseif ($_POST['submitButton'] == "delete") {

				//	Do delete:

				$query = "DELETE FROM `displaylayout` WHERE `displaylayout`.`ID` = $displayID"; 
				list($result,$count,$DBUpdateError) = Execute_SQL($query);
				if (! $DBUpdateError) {
					$displayID = 'new';
					$DBUpdateError = "Item deleted!";
				}
			}
		
	} else {
	
		// defaults for first display of the form:
	
		$displayID = 'new';
		$deviceRef = '';
		$displayType = '';
		$displayXCoord = '0';
		$displayYCoord = '0';
		$displayWidth = '20';
		$displayHeight = '20';
		$displayColor = '0,0,255';
		$displayOpacity = '1';
		$displayColorAlt = '0,0,255';
		$displayOpacityAlt = '0';
		$buttonID = '';
		$buttonIDAlt = '';
		$beforeSecure = '0';
		$beforeSecureAlt = '0';
		$displayText = '';
	}
}
?>

<div style="position: relative; z-index: 1005;">
	<img id="FloorImage" src="images/<?php echo $imageSource; ?>">
	<div id="ImageElementContainer"></div>
	<div id="PopupElementContainer"></div>
	<div id="DBUpdateElementContainer"></div>
<?php require('../include/CompleteListElementContainer.php'); ?>
</div>
<?php

if ((! $isMobile) && ($optionDBUpdate)) { 

//-----------------------------------------------------------------------------
//	The DB Update form:
//-----------------------------------------------------------------------------
?>
<div id="DBUpdate" class="DBUpdate">
  <form id="DBUpdateForm" class="content" action="" method="post">
    <div class="container">
	  <p>Display Selection</p>
      <label>Item</label>
      <select name="displayID" size="1" required onChange="DBUpdateItemChanged(this.value);">
	    <option value="new">New</option>
<?php
//	usort($displayLayoutTableArray, function($a, $b) {
//		return strcmp($a['displayText'], $b['displayText']);
//	});
	foreach ($displayLayoutTableArray as $value) {
		print('<option value="'.$value['ID'].'">'.$value['displayText'].'</option>');
	}
?>
	  </select>
	  <br>
		<label>Text</label>
		<input type="text" name="displayText" size="20" onInput="DBUpdateFormUpdated();">	  
	</div>
	<div class="container">
	  <p>Device Information</p>
      <label>Reference</label>
      <input type="text" name="deviceRef" size="8" onInput="DBUpdateRefChanged();DBUpdateFormUpdated();">
	<br>
      <label>Name</label>
      <input type="text" name="deviceName" size="32" disabled>
	</div>
    <div class="container">
	  <p>Display Information</p>
      <label>Type</label>
      <select id="typeSelector" name="displayType" size="1" onChange="DBUpdateFormUpdated();">
		<option value="1">On/Off</option>
		<option value="2">Open/Closed</option>
		<option value="3">Running/Stopped</option>
		<option value="4">Locked/Unlocked</option>
		<option value="5">Status Only</option>
		<option value="6">Percent</option>
	  </select>
	<br>
      <label>X/Y-Coord</label>
      <input type="text" name="displayXCoord" size="3" onInput="DBUpdateDisplayBox();">&nbsp;
	  <img id="DBUpdateXL" src="/images/nav-arrow-left-open.png" title="Left" onClick="DBUpdateIncrement('displayXCoord',-1);">
	  <img id="DBUpdateXR" src="/images/nav-arrow-right-open.png" title="Right" onClick="DBUpdateIncrement('displayXCoord',+1);">&nbsp;
<!--	<br>
      <label>Y-Coord</label>
-->      <input type="text" name="displayYCoord" size="3" onInput="DBUpdateDisplayBox();">&nbsp;
	  <img id="DBUpdateYU" src="/images/nav-arrow-up-open.png" title="Up" onClick="DBUpdateIncrement('displayYCoord',-1);">
	  <img id="DBUpdateYD" src="/images/nav-arrow-down-open.png" title="Down" onClick="DBUpdateIncrement('displayYCoord',+1);">
	<br>
      <label>Width/Height</label>
      <input type="text" name="displayWidth" size="3" onInput="DBUpdateDisplayBox();">&nbsp;
	  <img id="DBUpdateWU" src="/images/nav-arrow-up-open.png" title="Wider" onClick="DBUpdateIncrement('displayWidth',+1);">
	  <img id="DBUpdateWD" src="/images/nav-arrow-down-open.png" title="Narrower" onClick="DBUpdateIncrement('displayWidth',-1);">&nbsp;
<!--	<br>
      <label>Height</label>
--->      <input type="text" name="displayHeight" size="3" onInput="DBUpdateDisplayBox();">&nbsp;
	  <img id="DBUpdateHU" src="/images/nav-arrow-up-open.png" title="Taller" onClick="DBUpdateIncrement('displayHeight',+1);">
	  <img id="DBUpdateHD" src="/images/nav-arrow-down-open.png" title="Shorter" onClick="DBUpdateIncrement('displayHeight',-1);">
	</div>
    <div class="container"><!-- TODO: label the state based on the type -->
	  <p>State:<span  id="LabelForOn" style="width: 120px; display: inline-block;">on</span><span id="LabelForOff" style="width: 120px; display: inline-block;">off</span></p>
      <label>Color</label>
	  <span style="width: 120px; display: inline-block;">
		<select name="displayColor" size="1" style="width: 110px; margin-right: 6px;" title="Color when device is On/Open/Running/Locked" onChange="DBUpdateDisplayBox();">
			<option value="0,0,255">Blue</option>
			<option value="0,255,0">Green</option>
			<option value="255,0,0">Red</option>
			<option value="255,255,0">Yellow</option>
		</select>
	  </span>
      <select name="displayColorAlt" size="1" style="width: 110px;" title="Color when device is Off/Closed/Stopped/Unlocked"  onChange="DBUpdateDisplayBox();">
		<option value="0,0,255">Blue</option>
		<option value="0,255,0">Green</option>
		<option value="255,0,0">Red</option>
		<option value="255,255,0">Yellow</option>
	  </select>
	<br>
      <label>Opacity</label>
      <span style="width: 120px; display: inline-block;">
		<select name="displayOpacity" size="1" style="width: 110px; margin-right: 6px;" title="Opacity when device is On/Open/Running/Locked"  onChange="DBUpdateDisplayBox();">
			<option value="1">Dense</option>
			<option value="0.5">Light</option>
			<option value="0">Transparent</option>
		</select>
	  </span>
      <select name="displayOpacityAlt" size="1" style="width: 110px;" title="Opacity when device is Off/Closed/Stopped/Unlocked"  onChange="DBUpdateDisplayBox();">
		<option value="1">Dense</option>
		<option value="0.5">Light</option>
		<option value="0">Transparent</option>
	  </select>
	<br>
      <label>Button</label>
      <span style="width: 120px; display: inline-block;">
		<input type="text" name="buttonID" size="8" onInput="DBUpdateButtonChanged();DBUpdateFormUpdated();">
	  </span>
      <input type="text" name="buttonIDAlt" size="8" onInput="DBUpdateButtonChanged();DBUpdateFormUpdated();">
	<br>
      <label>Event Name</label>
      <span style="width: 120px; display: inline-block;">
		<input type="text" name="eventName" size="12" disabled>
	  </span>
      <input type="text" name="eventNameAlt" size="12" disabled>
	<br>
			<label title="Check device before changing to an Away status?">Before Secure?</label>
			<span style="width: 120px; display: inline-block;">
			<input type="checkbox" name="beforeSecure" value='1' onInput="DBUpdateFormUpdated();" title="Wait until device is On/Open/Running/Locked?">
			</span>
			<input type="checkbox" name="beforeSecureAlt" value='1' onInput="DBUpdateFormUpdated();" title="Wait until device is Off/Closed/Stopped/Unlocked?">  
	</div>
    <div class="container" style="padding-top: 0px;">
 	  <p id="DBUpdateError" style="color:#f44336"><b><?php echo $DBUpdateError; ?></b></p>
      <button id="DBUpdateSubmitButton" name="submitButton" type="submit" value="save" class="green" onClick="DBUpdateFormHide(true);"><b>Save Item</b></button>
	  </div>
	  <div class="container" style="padding-top: 2px;">
	  <button id="DBUpdateDeleteButton" name="submitButton" type="submit" value="delete" class="yellow" onClick="return confirm('Are you sure that you want to delete this item?');"><b>Delete Item</b></button>
    </div>
  </form>
</div>
<?php
//-----------------------------------------------------------------------------
//	Javascript functions specific to the DB Update form:
//-----------------------------------------------------------------------------
?>
<script>
var DBUpdateForm = document.getElementById("DBUpdateForm")

$('input[name=beforeSecure]').change(function() {
	if (this.checked) {
		DBUpdateForm.beforeSecureAlt.checked = false;
	}
});

$('input[name=beforeSecureAlt]').change(function() {
	if (this.checked) {
		DBUpdateForm.beforeSecure.checked = false;
	}
});

function DBUpdateRefChanged() {
	var i = HS_appinfo_devicesIndexForRef(DBUpdateForm.deviceRef.value);
	if (i < 0) {
		DBUpdateForm.deviceName.value = '';
	} else {
		DBUpdateForm.deviceName.value = HS_appinfo_devices[i].name + '/' + HS_appinfo_devices[i].location + '/' + HS_appinfo_devices[i].location2;
	}
}

function DBUpdateButtonChanged() {
	var valueString = '';
	var titleString = '';
    if (DBUpdateForm.buttonID.value != '') {
        var buttonIDArray = DBUpdateForm.buttonID.value.split(/\s*,\s*/);
        var i;
        for (i = 0; i < buttonIDArray.length; i++) {
            var eventIndex = HS_eventinfo_eventsIndexForID(buttonIDArray[i]);
            if (eventIndex >= 0) {
                valueString += HS_eventinfo_events[eventIndex].Name.replace(/\s*\(.*?\)\s*/g, '') + ', ';
                titleString += HS_eventinfo_events[eventIndex].Name + ', ';
            }
        }
    }
	DBUpdateForm.eventName.value = valueString.slice(0, -2);
	DBUpdateForm.eventName.title = titleString.slice(0, -2);
    
	var valueString = '';
	var titleString = '';
    if (DBUpdateForm.buttonIDAlt.value != '') {
        var buttonIDArray = DBUpdateForm.buttonIDAlt.value.split(/\s*,\s*/);
        var i;
        for (i = 0; i < buttonIDArray.length; i++) {
            var eventIndex = HS_eventinfo_eventsIndexForID(buttonIDArray[i]);
            if (eventIndex >= 0) {
                valueString += HS_eventinfo_events[eventIndex].Name.replace(/\s*\(.*?\)\s*/g, '') + ', ';
                titleString += HS_eventinfo_events[eventIndex].Name + ', ';
            }
        }
    }
	DBUpdateForm.eventNameAlt.value = valueString.slice(0, -2);
	DBUpdateForm.eventNameAlt.title = titleString.slice(0, -2);
}

function DBUpdateFormHide(TrueOrFalse) {
	if (TrueOrFalse) {
		document.getElementById("DBUpdateForm").style.display = "none";
		$('.pre-loader').show();
	} else {
		document.getElementById("DBUpdateForm").style.display = "block";
	}
}
										/* retrieve values from the previously submitted form */

DBUpdateForm.displayID.value ="<?php echo quote_escape($displayID); ?>";
DBUpdateForm.displayText.value = "<?php echo quote_escape($displayText); ?>";
DBUpdateForm.deviceRef.value = "<?php echo quote_escape($deviceRef); ?>";
DBUpdateRefChanged();
DBUpdateForm.displayType.value = "<?php echo quote_escape($displayType); ?>";
DBUpdateForm.displayXCoord.value = "<?php echo quote_escape($displayXCoord); ?>";
DBUpdateForm.displayYCoord.value = "<?php echo quote_escape($displayYCoord); ?>";
DBUpdateForm.displayWidth.value = "<?php echo quote_escape($displayWidth); ?>";
DBUpdateForm.displayHeight.value = "<?php echo quote_escape($displayHeight); ?>";
DBUpdateForm.displayColor.value = "<?php echo quote_escape($displayColor); ?>";
DBUpdateForm.displayOpacity.value = "<?php echo quote_escape($displayOpacity); ?>";
DBUpdateForm.displayColorAlt.value = "<?php echo quote_escape($displayColorAlt); ?>";
DBUpdateForm.displayOpacityAlt.value = "<?php echo quote_escape($displayOpacityAlt); ?>";
DBUpdateForm.buttonID.value = "<?php echo quote_escape($buttonID); ?>";
DBUpdateForm.buttonIDAlt.value = "<?php echo quote_escape($buttonIDAlt); ?>";
DBUpdateButtonChanged();
DBUpdateForm.beforeSecure.checked = <?php if ($beforeSecure == '1') { echo 'true'; } else { echo 'false'; }; ?>;
DBUpdateForm.beforeSecureAlt.checked = <?php if ($beforeSecureAlt == '1') { echo 'true'; } else { echo 'false'; }; ?>;
<?php if ($displayID == 'new') { ?>
	document.getElementById("DBUpdateDeleteButton").style.display = "none";   	/* hide the delete button */	
<?php } ?>
document.getElementById("DBUpdateSubmitButton").style.display = "none";   		/* hide the submit button until something is changed */
DBUpdateFormHide(false);				   										/* but show the form */

function DBUpdateItemChanged(idvalue) { 				/* get values from DB for newly selected item */
	var DBUpdateForm = document.getElementById("DBUpdateForm")
	var displayLayoutTable = <?php echo json_encode($displayLayoutTableArray); ?>;
	var Form = document.getElementById("DBUpdateForm")
	if (idvalue=='new') {									/* new */
		DBUpdateForm.deviceRef.value = '';
		DBUpdateRefChanged();
		DBUpdateForm.displayType.value = '';
		DBUpdateForm.displayXCoord.value = '0';
		DBUpdateForm.displayYCoord.value = '0';
		DBUpdateForm.displayWidth.value = '20';
		DBUpdateForm.displayHeight.value = '20';
		DBUpdateForm.displayColor.value = '0,0,255';
		DBUpdateForm.displayOpacity.value = '1';
		DBUpdateForm.displayColorAlt.value = '0,0,255';
		DBUpdateForm.displayOpacityAlt.value = '0';
		DBUpdateForm.buttonID.value = '';
		DBUpdateForm.buttonIDAlt.value = '';
		DBUpdateButtonChanged();
		DBUpdateForm.beforeSecure.checked = false;
		DBUpdateForm.beforeSecureAlt.checked = false;
		DBUpdateForm.displayText.value = '';

		document.getElementById("DBUpdateDeleteButton").style.display = "none";   		

	} else {												/* existing */
		for (i=0; i < displayLayoutTable.length; i++) {	
			if (displayLayoutTable[i].ID == idvalue) {
				break;
			}
		}
		DBUpdateForm.displayText.value = displayLayoutTable[i].displayText;
		DBUpdateForm.deviceRef.value = displayLayoutTable[i].deviceRef;
		DBUpdateRefChanged();
		DBUpdateForm.displayType.value = displayLayoutTable[i].displayType;
		DBUpdateForm.displayXCoord.value = displayLayoutTable[i].displayXCoord;
		DBUpdateForm.displayYCoord.value = displayLayoutTable[i].displayYCoord;
		DBUpdateForm.displayWidth.value = displayLayoutTable[i].displayWidth;
		DBUpdateForm.displayHeight.value = displayLayoutTable[i].displayHeight;
		DBUpdateForm.displayColor.value = displayLayoutTable[i].displayColor;
		DBUpdateForm.displayOpacity.value = displayLayoutTable[i].displayOpacity;
		DBUpdateForm.displayColorAlt.value = displayLayoutTable[i].displayColorAlt;
		DBUpdateForm.displayOpacityAlt.value = displayLayoutTable[i].displayOpacityAlt;
		DBUpdateForm.buttonID.value = displayLayoutTable[i].buttonID;
		DBUpdateForm.buttonIDAlt.value = displayLayoutTable[i].buttonIDAlt;
		DBUpdateButtonChanged();
		DBUpdateForm.beforeSecure.checked = ('1' == displayLayoutTable[i].beforeSecure);
		DBUpdateForm.beforeSecureAlt.checked = ('1' == displayLayoutTable[i].beforeSecureAlt);

		DBUpdateDisplayBox();
		DBUpdateFormUpdated();

		document.getElementById("DBUpdateDeleteButton").style.display = "block";   		
	}
	document.getElementById("DBUpdateSubmitButton").style.display = "none";   		/* hide the submit button until something is changed */
}

function DBUpdateFormUpdated() {
	document.getElementById("DBUpdateError").innerHTML = ''; 					/* remove previous message */
	document.getElementById("DBUpdateSubmitButton").style.display = "block";   	/* show the submit button */
	
	/* fill in the on/off labels */	
	
	var displayTypeLabel = DisplayTypeInfo(document.getElementById("typeSelector").value)
	document.getElementById('LabelForOn').innerHTML = displayTypeLabel.labelForOn;
	document.getElementById('LabelForOff').innerHTML = displayTypeLabel.labelForOff;
}

function DBUpdateIncrement(ElementName,Amount) {		/* increment or decrement an input value */
	var Element = document.getElementById("DBUpdateForm").elements[ElementName];
	if (isNumeric(Element.value)) {
		Element.value = parseInt(Amount) + parseInt(Element.value);
	} else {
		Element.value = parseInt(Amount);
	}
	DBUpdateFormUpdated();
	DBUpdateDisplayBox();
}
function DBUpdateDisplayBox() {				/* display the proposed item on the image (if we have enough info) */
	DBUpdateFormUpdated();
	
	var DBUpdateForm = document.getElementById("DBUpdateForm");
	if (isNumeric(DBUpdateForm.displayXCoord.value) && isNumeric(DBUpdateForm.displayYCoord.value) && isNumeric(DBUpdateForm.displayWidth.value) && isNumeric(DBUpdateForm.displayHeight.value) && DBUpdateForm.displayColor.value.match(/\d+,\d+,\d+/) && isNumeric(DBUpdateForm.displayOpacity.value) && DBUpdateForm.displayColorAlt.value.match(/\d+,\d+,\d+/) && isNumeric(DBUpdateForm.displayOpacityAlt.value)) {
		DBUpdateDisplayOnImage("This is the display element that is being defined.",DBUpdateForm.displayXCoord.value,DBUpdateForm.displayYCoord.value,DBUpdateForm.displayWidth.value,DBUpdateForm.displayHeight.value,DBUpdateForm.displayColor.value,DBUpdateForm.displayOpacity.value)
		return true;
	} else {
		return false;
	}
}

var DBUpdateImageMouseDown = false;
var DBUpdateBorder = 1;
	
function DBUpdateDisplayOnImage(Message,XCoord,YCoord,Width,Height,Color,Opacity) { 		/* display the item on the image */
	var DBUpdateElementContainer = document.getElementById("DBUpdateElementContainer");
	var cssTop = /*Math.round*/(YScale * (YCoord - (Height / 2)));
	var cssLeft = Math.round(XScale * (XCoord - (Width / 2)));
	var cssHeight = /*Math.round*/(YScale * Height) - (2 * DBUpdateBorder);
	var cssWidth = Math.round(XScale * Width - (2 * DBUpdateBorder));
	var cssOpacity = Opacity * 0.4;
	var element = document.getElementById("DBUpdateImage");
	if (element) {
		element.parentNode.removeChild(element);
	}

	DBUpdateElementContainer.innerHTML += '<div ID="DBUpdateImage" title="'+Message+'" style="position:absolute; top:'+cssTop+'px; left:'+cssLeft+'px; height: '+cssHeight+'px; width: '+cssWidth+'px; border-radius:10%; background: radial-gradient(circle, rgba('+Color+','+cssOpacity+'), rgba(255,255,255,0)); border: '+DBUpdateBorder+'px solid #ccc; color: black; line-height: '+cssHeight+'px; text-align: center; cursor: pointer;" onClick="return true;">+</div>';

}
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}
var DBUpdateInterval; var DBUpdateThis; 			/* repeat the click action while the mouse is held down over a direction icon: */

$("#DBUpdateXL").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});
$("#DBUpdateXR").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});

$("#DBUpdateYU").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});
$("#DBUpdateYD").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});

$("#DBUpdateWU").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});
$("#DBUpdateWD").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});

$("#DBUpdateHU").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});
$("#DBUpdateHD").mousedown(function() { DBUpdateThis = this; DBUpdateInterval = setTimeout(DBUpdateMouseDown, 500); }).mouseup(function() { clearInterval(DBUpdateInterval);}).mouseout(function() { clearInterval(DBUpdateInterval);});

function DBUpdateMouseDown() {
	clearInterval(DBUpdateInterval);
	DBUpdateThis.click();
	DBUpdateInterval = setTimeout(DBUpdateMouseDown, 50);
}
</script>
<?php
//-----------------------------------------------------------------------------
//	We now return you to our regularly scheduled program...
//-----------------------------------------------------------------------------
 }
// if (! $isMobile) { ?>
<script>
(function($) {
    $(document).ready(function() {

        $(window).click(function(e) {
				if (<?php
					if (isset($_REQUEST['iframe'])) {
						echo("true");
					} else { 
						echo("(! document.getElementById('hdm_bar').contains(e.target)) /* ignore clicks on the header dropdown menu */");
					}
					if ($optionDBUpdate) {
						echo("&& (! document.getElementById('DBUpdateForm').contains(e.target)) /* ignore clicks on the DB update form */");
					}
				?>) {
				var FloorImage = document.getElementById("FloorImage");
				var XScale = FloorImage.clientWidth / 1024;
				var YScale = FloorImage.clientHeight / 768;

				var rect = FloorImage.getBoundingClientRect();
						/* ( size of border = 2px ) */
				var relativeX = (e.pageX - rect.left - window.scrollX) - 2;
				var relativeY = (e.pageY - rect.top - window.scrollY) - 2;
				var scaledX = relativeX / XScale;
				var scaledY = relativeY / YScale;

				if (relativeX >= 0 && relativeY >= 0 && relativeX <= FloorImage.offsetWidth && relativeY <= FloorImage.offsetHeight) {
					var tableHTML = '<div class="PopupContent"><table class="PopupTable"><tr><th>Item</th><th>Status<th></tr>';
					var Items = 0;
					for ($i = 0; $i < displayLayoutTable.length; $i++) {
						var Width = parseInt(displayLayoutTable[$i].displayWidth)
						if (Width < 30 / XScale) { Width = 30 / XScale; }  /* fudge factor for finger touch */
						var Height = parseInt(displayLayoutTable[$i].displayHeight)
						if (Height < 30 / YScale) { Height = 30 / YScale; }
						var Left = parseInt(displayLayoutTable[$i].displayXCoord) - (Width / 2);
						var Right = parseInt(displayLayoutTable[$i].displayXCoord) + (Width / 2);
						var Top = parseInt(displayLayoutTable[$i].displayYCoord) - (Height / 2);
						var Bottom = parseInt(displayLayoutTable[$i].displayYCoord) + (Height / 2);
						if ((scaledX >=  Left) && (scaledX <= Right) && (scaledY >= Top) && (scaledY <= Bottom)) {
							Items++;
							tableHTML += FormatDisplayLayoutTableRow($i);
						}
					}
					tableHTML += "</table></div>";
					var PopupElementContainer = document.getElementById("PopupElementContainer");
					var ArrowBackoff = 24;		/* distance from the click point to the box corner */
					if (Items == 0) { 
						PopupElementContainer.style.display = 'none';
					} else {
						PopupElementContainer.innerHTML = tableHTML;
						PopupElementContainer.style.display = 'block';
						
						if (scaledY > (768 / 2)) {
							PopupElementContainer.style.top = (e.pageY - PopupElementContainer.offsetHeight - ArrowBackoff - 40)+"px";
						} else {
							PopupElementContainer.style.top = (relativeY + ArrowBackoff)+"px";
						}
						if (scaledX > (1024 / 2)) {	
							var leftEdge = e.pageX - PopupElementContainer.offsetWidth - ArrowBackoff;
							if (leftEdge < -4) { 
								leftEdge = -4; }
							PopupElementContainer.style.left = (leftEdge)+"px";
							if (scaledY > (768 / 2)) {
								PopupElementContainer.style.borderRadius = "16px 16px 0 16px";
							} else {
								PopupElementContainer.style.borderRadius = "16px 0 16px 16px";
							}
						} else {
							var leftEdge = relativeX + ArrowBackoff;
							if (leftEdge + PopupElementContainer.offsetWidth > FloorImage.clientWidth + 4) {
								leftEdge = FloorImage.clientWidth - PopupElementContainer.offsetWidth + 4; }
							PopupElementContainer.style.left = (leftEdge)+"px";
							if (scaledY > (768 / 2)) {
								PopupElementContainer.style.borderRadius = "16px 16px 16px 0";
							} else {
								PopupElementContainer.style.borderRadius = "0 16px 16px 16px";
							}
						}
					}
				}
			}
        });
   
	});
})(jQuery);

/* https://www.codicode.com/art/easy_way_to_add_touch_support_to_your_website.aspx */

document.addEventListener("touchstart", touch2Mouse, true);
document.addEventListener("touchmove", touch2Mouse, true);
document.addEventListener("touchend", touch2Mouse, true);
function touch2Mouse(e)
{
  var theTouch = e.changedTouches[0];
  var mouseEv;

  switch(e.type)
  {
    case "touchstart":
						if (! document.getElementById("hdm_bar").contains(e.target) /* ignore clicks on the header dropdown menu */ <?php if ($optionDBUpdate) echo "&& ! document.getElementById('DBUpdateForm').contains(e.target) /* and the DB update form */"; ?>) {
							mouseEv="click";
						} else {
							mouseEv="mousedown";
						}
						break;

    case "touchend":   	mouseEv="mouseup";
						break;

    case "touchmove":  	mouseEv="mousemove";
						break;

    default: return;
  }

  var mouseEvent = document.createEvent("MouseEvent");
  mouseEvent.initMouseEvent(mouseEv, true, true, window, 1, theTouch.screenX, theTouch.screenY, theTouch.clientX, theTouch.clientY, false, false, false, false, 0, null);
  theTouch.target.dispatchEvent(mouseEvent);

/*  e.preventDefault(); */
}
</script>
<?php // } /* ----------------------------------------------------------------------------------------- */ ?>	
<script>

function DevicesOnPageLoad() {
	RetrieveDeviceData();
	window.addEventListener('resize', DevicesOnResize);
	window.addEventListener('orientationchange', DevicesOnResize);
/*	var timer = setInterval(RetrieveDeviceData,60000); */	
}

/* OnResize event is debounced - see http://bencentra.com/code/2015/02/27/optimizing-window-resize.html */

var resizeTimeout = false;
var resizeDelay = 100; 

function DevicesOnResize() {
	clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(DevicesActualOnResize, resizeDelay);
}

var XScale = 1;
var YScale = 1;

function DevicesActualOnResize() {

	FormatCompleteList();
<?php if ($optionDBUpdate) { ?>
	var rightEdgeOfImage = $("#FloorImage").outerWidth();
	var spaceToRightOfImage = $(window).width() - rightEdgeOfImage;
	var DBUpdateMargin = (spaceToRightOfImage - $("#DBUpdate").outerWidth()) / 2;
	if (DBUpdateMargin < 10) {
		DBUpdateMargin = 10;
	}
	document.getElementById("DBUpdate").style.right = DBUpdateMargin+'px';
<?php } ?>	

	/* Put indicators on the floor image */

	var FloorImage = document.getElementById("FloorImage");
	XScale = FloorImage.clientWidth / 1024;
	YScale = FloorImage.clientHeight / 768;

	var ImageElementContainer = document.getElementById("ImageElementContainer");
	ImageElementContainer.innerHTML = '';

	for ($i = 0; $i < displayLayoutTable.length; $i++) {
		var statusInfo = FormatMessageForDeviceStatus($i);
		if (statusInfo) {
			DisplayOnImage(statusInfo.message,displayLayoutTable[$i].displayXCoord,displayLayoutTable[$i].displayYCoord,displayLayoutTable[$i].displayWidth,displayLayoutTable[$i].displayHeight,statusInfo.color,statusInfo.opacity);
		}
	}
	
	function DisplayOnImage(Message,XCoord,YCoord,Width,Height,Color,Opacity) {
		ImageElementContainer = document.getElementById("ImageElementContainer");
		var cssTop = /*Math.round*/(YScale * (YCoord - (Height / 2)));
		var cssLeft = Math.round(XScale * (XCoord - (Width / 2)));
		var cssHeight = /*Math.round*/(YScale * Height);
		var cssWidth = Math.round(XScale * Width);
		var cssOpacity = Opacity * 0.4;
		ImageElementContainer.innerHTML += '<div title="'+Message+'" style="position:absolute; top:'+cssTop+'px; left:'+cssLeft+'px; height: '+cssHeight+'px; width: '+cssWidth+'px; border-radius:10%; background: radial-gradient(circle, rgba('+Color+','+cssOpacity+'), rgba(255,255,255,0)); cursor: pointer;" onClick="return true;"></div>';
	}
}
</script>

</body>
</html>
