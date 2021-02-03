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
  <style>
    body, html { width: 100%; height: 100%; margin: 0; padding: 0; }
    .body-flex-container { display: flex; width: 100%; height: 100%; flex-direction: column; overflow: hidden; background-color: black; }
    .menu-flex-row { background-color: black; }
    .content-flex-row { flex-grow: 1; border: none; margin: 0; padding: 0; 
      margin-right: env(safe-area-inset-right); margin-left: env(safe-area-inset-left);
    }
</style>
</head>
<body onLoad='javascript:OnPageLoad()'>
<div class="body-flex-container">
	<div class="menu-flex-row">
		<?php include('../include/header_dropdown_menu.php'); ?>
  </div> 
	<iframe class="content-flex-row" id="ContentFrame" onMouseOver="HdmHideDropdowns();"
   src="about:blank">Browser not supported.</iframe>
</div>
<?php
//-----------------------------------------------------------------------------
//	Javascript functions for the application shell:
//-----------------------------------------------------------------------------
?>
<script>
pageLoadDone = false;

function OnPageLoad() {
	console.log('index.php - OnPageLoad()');
  <?php
//  This following is the reverse of RefreshDropdownMenu(locationPathname) in header_dropdown_menu. */
//  We use the URL fragment (hash) in a shortcut to remember what page is loaded in the iframe. */
?>
	if (locationPathname == '/home/index.php') {
    if (locationHash.indexOf('#Home Devices') > -1) {
      locationPathname = '/HS/devices.php';
    } else if (locationHash.indexOf('#Devices - ') > -1) {
      locationPathname = '/HS/devices.php';
      for (var $i=0; $i < locationsTable.length; $i++) {
        if (locationHash.substring(11) === locationsTable[$i]['DisplayName']) {
          locationPathname = locationPathname + '?location=' + locationsTable[$i]['Name']
          break;
        }
	  }
    } else if (locationHash.indexOf('#Home Accounts') > -1) {
      locationPathname = '/account/profile.php';
    } else if (locationHash.indexOf(' Weather') > -1) {
      locationPathname = '/weather/weather.php';
      for (var $i=0; $i < houseInfo['Map'].length; $i++) {
        if (locationHash.indexOf(houseInfo['Map'][$i]['City'] + ' Weather') > -1) {
          locationPathname = locationPathname + '?map=' + $i;
          break;
        }
	  }	  
    } else if (locationHash.indexOf(' Status') > -1) {
      locationPathname = '/home/status.php';
    } else if (locationHash.indexOf(' Setup') > -1) {
      locationPathname = '/home/setup.php';
    } else if (locationHash.indexOf(' Map') > -1) {
      locationPathname = '/home/map.php';
    } else if (locationHash.indexOf(' Calendar') > -1) {
      locationPathname = '/home/calendar.php';
    } else {
      locationPathname = '/home/status.php';
    }
  }
  ContentFrame.addEventListener('load', OnContentFrameLoad);
  console.log('index.php - new locationPathname = ' + locationPathname);
  SetLocation(locationPathname);
  
	window.addEventListener('resize', OnResize);
	window.addEventListener('orientationchange', OnResize);
	window.addEventListener('focus', OnFocus);
  window.addEventListener('pageshow', OnPageShow);
	OnResize();
}

function OnContentFrameLoad() {
	console.log('index.php - OnContentFrameLoad()');
  HdmHideDropdowns();
}

function OnPageShow() {
	console.log('index.php - OnPageShow()');
//	alert('OnPageShow()');
	ReloadOnReopen();
}
function OnFocus() {
	console.log('index.php - OnFocus()');
	ReloadOnReopen();
}
function ReloadOnReopen() {
    <?php if ($isMobile) { ?>
	if (pageLoadDone) {
		SetLocation(ContentFrame.src.replace('&iframe=true','').replace('?iframe=true',''));
	}
    <?php } ?>
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
	console.log('index.php - ActualOnResize()');
  pageLoadDone = true;
}
</script>
</body>
</html>
