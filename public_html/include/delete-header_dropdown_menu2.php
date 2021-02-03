<?php
# ------------------------------------------------------------
#	Provide a standard menu at or near the top of the screen.
# ------------------------------------------------------------

	/* ----- get the locations information ----- */
	
	require_once('../include/execute_sql.php'); 
	
	$locationsTable = Get_Configuration_Item($houseID,'locations');
	if ($locationsTable) {
		usort($locationsTable, function($a, $b) {
			return strcmp($a['Order'], $b['Order']);
		});
	}
	
	/* ----- get house configuration info ----- */

  $houseInfo = Get_Configuration_Item($houseID,'houseInfo');

?><script>
  var houseInfo = <?php echo json_encode($houseInfo);           // from header_dropdown_menu.php ?>;
	var locationsTable = <?php echo json_encode($locationsTable); // from header_dropdown_menu.php ?>;

  var locationPathname = location.pathname;
  var locationSearch = location.search;

//  function $_GET(q,s) {
//     s = s ? s : window.location.search;
//     var re = new RegExp('&'+q+'(?:=([^&]*))?(?=&|$)','i');
//     return (s=s.replace(/^?/,'&').match(re)) ? (typeof s[1] == 'undefined' ? '' : decodeURIComponent(s[1])) : undefined;
//  }

</script><?PHP

	/* ----- determine our title and which item is active based on our URI ----- */
	
  	$hdm_active=0;
	
	if (empty(htmlspecialchars($_REQUEST['ha-iframe']))) {
?>
<div id="hdm_bar" class="hdm_bar">
  <span id="hdm_list_items">
  <ul>
      <li <?php if($hdm_active==0) {echo 'class="hdm_active" ';}?> onClick="return true;"><i class="fas fa-home"></i> Home
	  <ul>
        <li onClick="location.href = '/home/calendar.php';">Calendar</li>
        <li onClick="location.href = '/home/map.php';">Map</li>
			<li onClick="location.href = '/home/setup.php';">Setup</li>
        <li onClick="location.href = '/home/status.php';">Status</li>
	  </ul>
    </li>
    <li <?php if($hdm_active==1) {echo 'class="hdm_active" ';}?>onClick="return true;"><i class="fas fa-lightbulb"></i> Devices<!-- onClick return true is a fix for iOS :hover handling -->
	  <ul>
<?php	  	
	for ($i = 0; $i < count($locationsTable); $i++) {
    echo "<li onClick=\"location.href = '/HS/devices.php?location=".$locationsTable[$i]['Name']."';\">".$locationsTable[$i]['DisplayName']."</li>";
  }
?>
	  </ul>
    </li>
    <li <?php if($hdm_active==2) {echo 'class="hdm_active" ';}?>onClick="return true;">
      <img id="currentWeatherIcon" style="width: 24px; height: 24px; margin-top:-8px; margin-bottom:-5px; border: 0;"> Weather
	  <ul>
<?php	  	
	for ($i = 0; $i < count($houseInfo['Map']); $i++) {
        echo "<li onClick=\"location.href = '/weather/weather.php?map=".$i."';\">".$houseInfo['Map'][$i]['City']."</li>";
	}
?>
	  </ul>
    </li>
    <li class="hdm_right<?php if($hdm_active==3) {echo ' hdm_active';}?>" onClick="return true;">
      <i class="fas fa-user"></i> <?php echo $userName; ?>
      <ul>
			<li onClick="location.href = '/account/accounts.php';">Accounts</li>
        <li onClick="location.href = '/account/profile.php';">Profile</li>
        <li onClick="document.getElementById('hdm_logoff_1').submit();">
		  Sign Out
	      <form id="hdm_logoff_1" action="/account/login.php" method="post">
		    <input name="submitButton" style="width:100%;" type="hidden" value="logoff">
	      </form>
	    </li>
      </ul>
    </li>
  </ul>
  </span>
</div>
<script>
function FormatDropdownMenu(locationPathname) {
    var hdm_active;
    var documentTitle;
    if (locationPathname.substring(0,4) === '/HS/') {
      hdm_active = 1;
      documentTitle = 'Home Devices';                     /* default */
      var location = locationsTable[0]['Name'];           /* default */
      var imageSource = locationsTable[0]['ImageSource']; /* default */
      for (var $i=0; $i < locationsTable.length; $i++) {	
  			if (locationPathname.indexOf(locationsTable[$i]['Name']) >= 0) {
	  			var locationDisplayName = locationsTable[$i]['DisplayName']; 
		  		documentTitle='Devices - '+locationDisplayName;
			  	location = locationsTable[$i]['Name'];					/* this gets passed to devices.php and CompleteListElementContainer.php */
				  imageSource = locationsTable[$i]['ImageSource'];			/* this gets passed to devices.php */
				  break;
			  }
		  }
      console.log('location=' + location);
      console.log('imageSource=' + imageSource);
    } else if (locationPathname.substring(0,9) === '/weather/') {
      hdm_active=2;
      
	//	  if ( !(isset($_GET("map",locationPathname)) || !("map",locationPathname) || !is_numeric(mapIndex) || (mapIndex >= count(houseInfo['Map'])) ) {
			    mapIndex = 0; 	
	//	  }
		  documentTitle=houseInfo['Map'][mapIndex]['City']+' Weather';
	  } else if (locationPathname.substring(0,9) === '/account/') {
		  hdm_active=3;
		  documentTitle="Home Accounts";
	  } else {
		  hdm_active=0;
		  documentTitle=houseInfo['Map'][0]['City']+' Home';
	  }
    console.log('hdm_active=' + hdm_active);
    console.log('documentTitle=' + documentTitle);
    document.title = documentTitle;
  }
  
  FormatDropdownMenu(locationPathname+locationSearch);

</script>
<style>

/* ---------- .hdm_bar is the container for the menu bar ---------- */

#hdm_bar {
  x-background: #333;
  background: black;
  position: -webkit-sticky; /* Safari */
  position: sticky;
  top: 0;
  right: 0;
  left: 0;
  margin: 0;
  height: 40px;
  z-index: 1020;
}

#hdm_bar {					/* viewport-fit=cover hack https://ayogo.com/blog/ios11-viewport/ */
  padding-top: 20px; 							/* iOS 10    */
  padding-top: constant(safe-area-inset-top); /* iOS 11.0  */
  padding-top: env(safe-area-inset-top); 		/* iOS 11.2+ */
}
/*@media screen and (orientation:landscape) {
*/  @media screen and (max-height: 450px) and (orientation:landscape) {

  #hdm_bar {
    display:none;			/* save some vertical space in landscape (rotate the iPhone to get the navigation back) */
  }
}

/* ---------- menu list ---------- */

ul {
  text-align: left;
  display: inline-block;
  margin: 0;
  padding: 0;
  list-style: none;
  -webkit-box-shadow: 0 0 5px rgba(0, 0, 0, 0.15);
  -moz-box-shadow: 0 0 5px rgba(0, 0, 0, 0.15);
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.15);
  width: 100%;
}

/* ---------- show top level menu items in the menu bar ---------- */

ul li {
  display: inline-block;
  x-margin-right: -4px;
  margin:0 1px; 
  position: relative;
  font: normal 12px/20px sans-serif;		/* min font size */
  padding: 10px 1vw 10px 1vw;
  background: #333;
  color: #f2f2f2;
  cursor: pointer;
  -webkit-transition: all 0.2s;
  -moz-transition: all 0.2s;
  -ms-transition: all 0.2s;
  -o-transition: all 0.2s;
  transition: all 0.2s;
  --imgFilterInvert: invert(0%);
}
ul li.hdm_active {
  background: var(--color-royal-blue);
}
@media screen and (min-width: 600px) {	
  ul li {
    font: normal 2vw/20px sans-serif;		/* 2vw = 12px @ 600; 16px @ 800 */
  }
}
@media screen and (min-width: 800px) {
  ul li {
    font: normal 16px/20px sans-serif;		/* max font size */
  }
}
ul li:hover {
  background-color: #ddd;
  color: black; 
  --imgFilterInvert: invert(100%);
}
ul li img {
  -webkit-filter: var(--imgFilterInvert);
}

/* ---------- hdm_right is a top level item that is shown in the right corner ---------- */

.hdm_right {
  float: right; 
  text-align: right;
}
.hdm_right:hover {
  text-align: left;
  width: 146px;
}

/* ---------- dropdown items to appear under the top level item ---------- */

ul li ul {
  padding: 0;
  position: absolute;
  top: 40px;
  left: 0;
  width: 150px;
  -webkit-box-shadow: none;
  -moz-box-shadow: none;
  box-shadow: none;
  display: none;
  opacity: 0;
  visibility: hidden;
  -webkit-transiton: opacity 0.2s;
  -moz-transition: opacity 0.2s;
  -ms-transition: opacity 0.2s;
  -o-transition: opacity 0.2s;
  -transition: opacity 0.2s;
}
ul li ul li { 
  background: #555; 
  display: block; 
  color: #fff;
  text-shadow: 0 -1px 0 #000;
}
ul li ul li:hover { background: #ddd; }
ul li:hover ul {
  display: block;
  opacity: 1;
  visibility: visible;
}
</style>
<script>

var currentWeatherIcon;
if (localStorage.getItem('currentWeatherIcon')) {
	currentWeatherIcon = decodeURIComponent(localStorage.getItem('currentWeatherIcon'));
} else if (sessionStorage.getItem('currentWeatherIcon')) {
	currentWeatherIcon = decodeURIComponent(sessionStorage.getItem('currentWeatherIcon'));
} else {
	currentWeatherIcon = "/images/WeatherIcon.jpg";
}
document.getElementById('currentWeatherIcon').src = currentWeatherIcon;

</script>

	<?php } /* end iframe test */ ?>

