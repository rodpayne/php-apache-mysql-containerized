<?php
//-----------------------------------------------------------------------------
//	Application Shell: HTML, CSS, and JavaScript for initial page load
//-----------------------------------------------------------------------------
require('../include/check_session_login.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Getting Home Page...</title>
	<?php require('../include/app_standard_head.html'); ?>	
</head>
<body onLoad='javascript:OnPageLoad()'>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php include('../include/startup_spinner.html'); ?> 
<div id="PageContent" style="width: 100%; border: 0px; margin: 0px;"></div>
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
//-----------------------------------------------------------------------------
//	Javascript global variables:
//-----------------------------------------------------------------------------
?><script>
var houseModesTable = <?php echo json_encode($houseModesTable); ?>;
var loginTime = <?php echo json_encode($loginTime); ?>;
<?php
//-----------------------------------------------------------------------------
//	Javascript functions for the application shell:
//-----------------------------------------------------------------------------
?>
var previouslyLoadedPageURL = locationPathname;
var previouslyLoadedJsFile = '';
var previouslyLoadedCssFile = '';
<?php
//-----------------------------------------------------------------------------
//	Function RetrievePageContents dynamically loads the HTML into the
//	PageContents div and loads the files containing the corresponding
//	script and style elements using function _LoadJsCssFile.
//
// 	See http://www.javascriptkit.com/javatutors/loadjavascriptcss.shtml
//-----------------------------------------------------------------------------
?>
function _RemoveJsCssFile( filename, filetype ){
    var targetelement=(filetype=="js")? "script" : (filetype=="css")? "link" : "none" //determine element type to create nodelist from
    var targetattr=(filetype=="js")? "src" : (filetype=="css")? "href" : "none" //determine corresponding attribute to test for
    var allsuspects=document.getElementsByTagName(targetelement)
    for (var i=allsuspects.length; i>=0; i--){ //search backwards within nodelist for matching elements to remove
    if (allsuspects[i] && allsuspects[i].getAttribute(targetattr)!=null && allsuspects[i].getAttribute(targetattr).indexOf(filename)!=-1)
        allsuspects[i].parentNode.removeChild(allsuspects[i]) //remove element by calling parentNode.removeChild()
    }
}

function _LoadJsCssFile( filename, filetype ){
    if (filetype=="js"){ //if filename is a external JavaScript file
        var fileref=document.createElement('script')
        fileref.setAttribute("type","text/javascript")
        fileref.setAttribute("src", filename)
    }
    else if (filetype=="css"){ //if filename is an external CSS file
        var fileref=document.createElement("link")
        fileref.setAttribute("rel", "stylesheet")
        fileref.setAttribute("type", "text/css")
        fileref.setAttribute("href", filename)
    }
    if (typeof fileref!="undefined")
        document.getElementsByTagName("head")[0].appendChild(fileref)
}
 
function RetrievePageContent( pageURL ) {
	_RemoveJsCssFile(previouslyLoadedJsFile,'js');
	_RemoveJsCssFile(previouslyLoadedCssFile,'css');
	var queryStringChar = (pageURL.includes('?'))? '&' : '?';
	$.ajax({
		url: pageURL + queryStringChar + 'only=html',
		cache: false,
		success: function( result ) {
			if (result === undefined) {
				return;
			}
			document.getElementById('PageContent').innerHTML = result;
			previouslyLoadedPageURL = pageURL;
			previouslyLoadedJsFile = pageURL + queryStringChar + 'only=js';
			_LoadJsCssFile(previouslyLoadedJsFile,'js');
			previouslyLoadedCssFile = pageURL + queryStringChar + 'only=css';
			_LoadJsCssFile(previouslyLoadedCssFile,'css');
			RefreshDropdownMenu(pageURL);
		}
	})
}

function OnPageLoad() {
	window.addEventListener('resize', OnResize);
	window.addEventListener('orientationchange', OnResize);
	RetrievePageContent('/home/status.php');
	<?php include('../include/startup_spinner_done.js'); ?>
	OnResize();
}
<?php
//-----------------------------------------------------------------------------
//  OnResize event is debounced - see http://bencentra.com/code/2015/02/27/optimizing-window-resize.html
//-----------------------------------------------------------------------------
?>
var resizeTimeout = false;
var resizeDelay = 100;

function OnResize() {
	clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(ActualOnResize, resizeDelay);
}

function ActualOnResize() {
	ContentPageResized(previouslyLoadedPageURL);
}
</script>
</body>
</html>
