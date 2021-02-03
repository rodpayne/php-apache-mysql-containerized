<?php require('../include/check_session_login.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Calendar</title>
	<?php require('../include/app_standard_head.html'); ?>	
</head>

<body onLoad='javascript:OnPageLoad()'>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php include('../include/startup_spinner.html'); ?> 
<div id="CalendarLink" style="display: none; padding: 2vh 2vw;" class="closebox">
	<button type="button" class="close" onClick="document.getElementById('CalendarLink').style.display='none';CalendarOnResize();"><span>&times;</span></button>
	<p>Google calendar does not seem to work when saved to home screen.</p>
	<a href="https://calendar.google.com/calendar/embed?src=r17fncds7rvobkbq38snotbf0s%40group.calendar.google.com&ctz=America%2FDenver">Try this link if the calendar is not displayed below.</a>
</div>
<iframe id="CalendarFrame" class="embed-responsive" src="" style="border-width:0" width="800" height="600" frameborder="0" scrolling="no"></iframe>
<style>
body {
	overflow: hidden;
}
#CalendarFrame {
  width: 100%;
  height: 100%;
  border: 0;
}
</style>
<?php
//-----------------------------------------------------------------------------
//	Javascript page functions:
//-----------------------------------------------------------------------------
?>
<script>
function OnPageLoad() {
	window.addEventListener('resize', CalendarOnResize);
	window.addEventListener('orientationchange', CalendarOnResize);

	var iFrame = document.getElementById('CalendarFrame');
	/* https://www.nczonline.net/blog/2009/09/15/iframes-onload-and-documentdomain/ */
	if (iFrame.attachEvent){
		iFrame.attachEvent("onload", function(){
		<?php include('../include/startup_spinner_done.js'); ?> 
		});
	} else {
		iFrame.onload = function(){
		<?php include('../include/startup_spinner_done.js'); ?> 
		};
	}

	var isStandalone = 'standalone' in window.navigator && window.navigator.standalone;
	if (isStandalone) {
		/* haven't been able to make embed work when running from iOS home screen */
		document.getElementById('CalendarLink').style.display = 'block'
	} 
	
	CalendarOnResize()
}

/* OnResize event is debounced - see http://bencentra.com/code/2015/02/27/optimizing-window-resize.html */

var resizeTimeout = false;
var resizeDelay = 1000; 

function CalendarOnResize() {
	clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(CalendarActualOnResize, resizeDelay);
}

function CalendarActualOnResize() {
	var newSrc;
	var calendarFrame = document.getElementById('CalendarFrame');
	if (window.innerWidth < 600) {		/* mode=AGENDA for narrow devices */
		newSrc = "https://calendar.google.com/calendar/embed?mode=AGENDA&amp;height=600&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src=r17fncds7rvobkbq38snotbf0s%40group.calendar.google.com&amp;color=%23333333&amp;ctz=America%2FDenver".replace(/&amp;/g, '&');
	} else {
		newSrc = "https://calendar.google.com/calendar/embed?height=600&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src=r17fncds7rvobkbq38snotbf0s%40group.calendar.google.com&amp;color=%23333333&amp;ctz=America%2FDenver".replace(/&amp;/g, '&');
	}
	if (newSrc != calendarFrame.src) {
		calendarFrame.src = newSrc;
	}
	calendarFrame.style.height = window.innerHeight - calendarFrame.offsetTop + 'px';
}
</script>
</body>
</html>
