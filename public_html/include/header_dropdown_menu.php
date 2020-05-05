<?php
  echo('<!--- start of /include/header_dropdown_menu.php --->');
# ------------------------------------------------------------
#    Provide a standard menu at or near the top of the page.
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

	/* ----- determine our title and which item is active based on our URI ----- */

	$hdm_URI = $_SERVER['REQUEST_URI'];
  if ( !(isset($_GET["map"])) || !($mapIndex = $_GET["map"]) || !is_numeric($mapIndex) || ($mapIndex >= count($houseInfo['Map'])) ) {
    $mapIndex = 0; 													/* this gets passed to main.php */
  }
	if (substr($hdm_URI,0,4)=='/HS/') {
		$hdmActive=1;
		$title='Home Devices'; 											/* default */
		$location = $locationsTable[0]['Name']; 					/* default */
		$imageSource = $locationsTable[0]['ImageSource'];			/* default */
		for ($i=0; $i < count($locationsTable); $i++) {	
			if (strpos($hdm_URI,$locationsTable[$i]['Name'])) {
				$locationDisplayName = $locationsTable[$i]['DisplayName']; 
				$title='Devices - '.$locationDisplayName;
				$location = $locationsTable[$i]['Name'];					/* this gets passed to devices.php and CompleteListElementContainer.php */
				$imageSource = $locationsTable[$i]['ImageSource'];			/* this gets passed to devices.php */
				break;
			}
    }
  } elseif (substr($hdm_URI,0,9)=='/weather/') {
		$hdmActive=2;
		$title=$houseInfo['Map'][$mapIndex]['City'].' Weather';
	} elseif (substr($hdm_URI,0,9)=='/account/') {
		$hdmActive=3;
		$title="Home Accounts";
	} else {
		$hdmActive=0;
		$title=$houseInfo['Map'][0]['City'].' Home';
	}

	/* ----- simplify the menu for iPhone or other mobile ----- */

	$isMobile = (isset($_SERVER['HTTP_USER_AGENT']) && ((strpos($_SERVER['HTTP_USER_AGENT'],'iPhone') > 0) || (strpos($_SERVER['HTTP_USER_AGENT'],'Mobile') > 0)));
  /* $isMobile = TRUE;	/* testing */
  /* $isMobile = FALSE;	/* testing */

	/* ----- skip the menu if within an iframe -----*/

	if ((! isset($_REQUEST['iframe'])) || empty($_REQUEST['iframe'])) {
?>
<div id="hdm-bar" class="hdm-bar">
  <ul style="
  display: flex;
  flex-flow: row wrap;
  /* This aligns items to the end line on main-axis */
  justify-content: flex-end;
  align-items: baseline;">
    <span id="hdm-title" class="hdm-title" <?php // style does not pull from style file for some reason ?>
        style="
          flex-grow: 2;
          color: white; 
          font-size: 2.3rem; 
          margin: 0; 
          margin-left: env(safe-area-inset-left,0);
          padding-left: 2vw;
        " 
        onMouseOver="HdmHideDropdowns();"><?php echo $title; ?></span>
    <li id="hdm-active0" <?php if($hdmActive==0) {echo 'class="hdm-active" ';}?>
      style="padding-left: 2vw;"
      onMouseOver="HdmShowDropdown(this);" onClick="HdmShowDropdown(this);"><i class="fas fa-home"></i> Home
  	  <ul onMouseOver="event.stopPropagation();">
        <li onClick="SetLocation('/home/calendar.php');">Calendar</li>
        <li onClick="SetLocation('/home/map.php');">Map</li>
    <?php if ((! $isMobile) && (strpos($userAttributes,'/setup/') !== FALSE)) { ?>
		  	<li onClick="SetLocation('/home/setup.php');">Setup</li>
		<?php } ?>
        <li onClick="SetLocation('/home/status.php');">Status</li>
	    </ul>
    </li>
    <li id="hdm-active1" <?php  if($hdmActive==1) {echo 'class="hdm-active" ';}?>onMouseOver="HdmShowDropdown(this);" onClick="HdmShowDropdown(this);"><i class="fas fa-lightbulb"></i> Devices<!-- onClick return true is a fix for iOS :hover handling -->
  	  <ul onMouseOver="event.stopPropagation();">
<?php
	  for ($i = 0; $i < count($locationsTable); $i++) {
        echo "<li onClick=\"SetLocation('/HS/devices.php?location=".$locationsTable[$i]['Name']."');\">".$locationsTable[$i]['DisplayName']."</li>";
	  }
?>
	    </ul>
    </li>
    <li id="hdm-active2" <?php if($hdmActive==2) {echo 'class="hdm-active" ';}?>onMouseOver="HdmShowDropdown(this);" onClick="HdmShowDropdown(this);">
      <img id="currentWeatherIcon" style="width: 24px; height: 24px; margin-top:-8px; margin-bottom:-5px; border: 0;"> Weather
	    <ul onMouseOver="event.stopPropagation();">
<?php
	  for ($i = 0; $i < count($houseInfo['Map']); $i++) {
        echo "<li onClick=\"SetLocation('/weather/weather.php?map=".$i."');\">".$houseInfo['Map'][$i]['City']."</li>";
	  }
?>
	    </ul>
    </li>
    <li id="hdm-active3" class="hdm-right<?php if($hdmActive==3) {echo ' hdm-active';}?>"
      style="margin-right: env(safe-area-inset-right,0); padding-right: 2vw"
      onMouseOver="HdmShowDropdown(this);" onClick="HdmShowDropdown(this);">
      <i class="fas fa-user"></i> <?php echo $userName; ?>
      <ul onMouseOver="event.stopPropagation();">
		<?php if ((! $isMobile) && (strpos($userAttributes,'/accounts/') !== FALSE)) { ?>
		  	<li onClick="SetLocation('/account/accounts.php');">Accounts</li>
		<?php } ?>
        <li onClick="SetLocation('/account/profile.php');">Profile</li>
        <li onClick="document.getElementById('hdm_logoff_1').submit();">
		      Sign Out
	        <form id="hdm_logoff_1" action="/account/login.php" method="post">
		        <input name="submitButton" style="width:100%;" type="hidden" value="logoff">
	        </form>
	      </li>
      </ul>
    </li>
  </ul>
</div>

<script>
  var houseInfo = <?php echo json_encode($houseInfo);           // from header_dropdown_menu.php ?>;
	var locationsTable = <?php echo json_encode($locationsTable); // from header_dropdown_menu.php ?>;

  var locationPathname = location.pathname;
  var locationSearch = location.search;
  var locationHash = decodeURIComponent(location.hash);

function HdmShowDropdown(thisElement) {
  console.log('HdmShowDropdown(' + thisElement.id + ')');

  HdmHideDropdowns();
  thisElement.className += " hdm-show-dropdown";
}

function HdmHideDropdowns() {
  console.log('HdmHideDropdowns()');

  document.getElementById('hdm-active0').className = document.getElementById('hdm-active0').className.replace(' hdm-show-dropdown','');
  document.getElementById('hdm-active1').className = document.getElementById('hdm-active1').className.replace(' hdm-show-dropdown','');
  document.getElementById('hdm-active2').className = document.getElementById('hdm-active2').className.replace(' hdm-show-dropdown','');
  document.getElementById('hdm-active3').className = document.getElementById('hdm-active3').className.replace(' hdm-show-dropdown','');
}

function SetLocation(locationPathname) {
  console.log('header_dropdown_menu.php - SetLocation(' + locationPathname + ')');
  if (typeof ContentFrame == 'object') {
    var queryStringChar = (locationPathname.includes('?'))? '&' : '?';
    ContentFrame.src = locationPathname + queryStringChar + "iframe=true";
    RefreshDropdownMenu(locationPathname);
  } else {
    location.href = locationPathname;
  }
//  event.preventDefault ? event.preventDefault() : event.returnValue = false;
  return true;
}

function _getUrlVars(location = window.location.href)
{
    var vars = [], hash;
    var hashes = location.slice(location.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

function RefreshDropdownMenu(locationPathname) {
    var hdmActive;
    var documentTitle;
    if (locationPathname.substring(0,4) === '/HS/') {
      hdmActive = 1;
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
      console.log('header_dropdown_menu.php - location=' + location);
      console.log('header_dropdown_menu.php - imageSource=' + imageSource);
    } else if (locationPathname.substring(0,9) === '/weather/') {
      hdmActive=2;

	    var mapIndex = _getUrlVars(locationPathname)["map"];
	    if ( (mapIndex === undefined) || (isNaN(mapIndex)) || (mapIndex >= houseInfo['Map'].length) ) {
		    mapIndex = 0;
	    }
	    documentTitle=houseInfo['Map'][mapIndex]['City']+' Weather';

    } else if (locationPathname.substring(0,9) === '/account/') {
		  hdmActive=3;
		  documentTitle="Home Accounts";
    } else if (locationPathname.substring(0,6) === '/home/') {
		  hdmActive=0;
		  documentTitle=houseInfo['Map'][0]['City']+' Home';
  		if (locationPathname.substring(6,12) === 'status' || locationPathname.substring(6,11) === 'index') {
	  		documentTitle = documentTitle + ' Status';
		  } else if (locationPathname.substring(6,11) === 'setup') {
			  documentTitle = documentTitle + ' Setup';
		  } else if (locationPathname.substring(6,9) === 'map') {
			  documentTitle = documentTitle + ' Map';
	  	} else if (locationPathname.substring(6,14) === 'calendar') {
		  	documentTitle = documentTitle + ' Calendar';
		  }
    } else {
		  hdmActive=0;
		  documentTitle=houseInfo['Map'][0]['City']+' Home';
    }

    document.getElementById('hdm-active0').className = (hdmActive == 0)? 'hdm-active' : '';
    document.getElementById('hdm-active1').className = (hdmActive == 1)? 'hdm-active' : '';
    document.getElementById('hdm-active2').className = (hdmActive == 2)? 'hdm-active' : '';
    document.getElementById('hdm-active3').className = (hdmActive == 3)? 'hdm-active hdm-right' : 'hdm-right';

  //  console.log('header_dropdown_menu.php - hdmActive=' + hdmActive);
    console.log('header_dropdown_menu.php - documentTitle=' + documentTitle);
    document.title = documentTitle;
	  if (history.pushState) {
		  history.pushState(null, null, '#' + documentTitle);
	  } else {
		  location.hash = '#' + documentTitle;
	  }
	  document.getElementById('hdm-title').innerHTML = documentTitle;
  }

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
<?php } /* end iframe */ 
echo('<!--- end of /include/header_dropdown_menu.php --->');
?>

