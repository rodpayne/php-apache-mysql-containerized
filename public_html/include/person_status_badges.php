<?php // provide badges showing device tracker status ?>
<!--- start of include/person_status_badges.php --->
<div id="ContainerVacation" class="PersonElementsContainer" style="display:none;"></div>
<div id="ContainerAway" class="PersonElementsContainer" style="display:none;"></div>
<div id="ContainerOccupied" class="PersonElementsContainer" style="display:none;"></div>
<script>
var refreshTimeout_HADeviceData = null;
var HADeviceData = [];
var previous_HADeviceDataResult = '';

function HADeviceEntity(entity_id) {
    var i;
	for (i = 0; i < HADeviceData.length; i++) {
		if (entity_id == HADeviceData[i]['entity_id']) {
           return HADeviceData[i];
        }
    }
    return null;
}

function PositionPersonContainers() {     /* called from OnResize */
    if (document.getElementById("Mode-0") != undefined) {
        document.getElementById("ContainerVacation").style.top = (document.getElementById("Mode-0").offsetTop - 4 + document.getElementById("Mode-0").offsetHeight - document.getElementById("ContainerVacation").offsetHeight) + 'px';
        document.getElementById("ContainerAway").style.top = (document.getElementById("Mode-1").offsetTop - 4) + 'px';
        document.getElementById("ContainerOccupied").style.top = (document.getElementById("Mode-3").offsetTop - 4) + 'px';
    } else {
        document.getElementById("ContainerVacation").style.position = 'static';
        document.getElementById("ContainerAway").style.position = 'static';
        document.getElementById("ContainerOccupied").style.position = 'static';
    }
}

    /* https://developer.mapquest.com/documentation/icons-api/v2/ */
    var markerSize = {'sm': [28, 35], 'md': [35, 44], 'lg': [42, 53]};
    var markerAnchor = {'sm': [14, 35], 'md': [17, 44], 'lg': [21, 53]};
    var markerPopupAnchor = {'sm': [1, -35], 'md': [1, -44], 'lg': [2, -53]};
    var firstTimeMapInit = true;

function RetrieveHADeviceData() {

	$.ajax({
		url: '/service/ha-device-service.php',
		cache: false,
		data: {
			request: 'states'
		},
		success: function( result ) {
			if (result === undefined) {
				return;
			}
			result = result.trim();
			if ((result.slice(0,1) != '{') && (result.slice(0,1) != '[')) {		/* result may be an error message instead of JSON */
				document.getElementById('DeviceAlert').innerHTML =
					'<div id="DeviceAlertMessage" class="info closebox" style="color:#f44336">'
					 + '<button type="button" class="close" onClick="document.getElementById(\'DeviceAlertMessage\').style.display=\'none\';"><span>&times;</span></button>'
					 + result + '</div>';

				document.getElementById('DeviceAlert').style.display = 'block';	
				return;
			}
			if (result != previous_HADeviceDataResult) {
				if (previous_HADeviceDataResult != '') {
					console.log('HA device service states returned new result.');
				}
                previous_HADeviceDataResult = result;
                document.getElementById('DeviceAlert').innerHTML = '';

                HADeviceData = JSON.parse(result);

                ContainerVacationHTML = '';
                ContainerAwayHTML = '';
                ContainerOccupiedHTML = '';
                <?php if ($show_mapping) { ?>
                    groupObject = HADeviceEntity('group.show_on_map');
                <?php } else { ?>
                    groupObject = HADeviceEntity('group.people_status');
                <?php } ?>
                if (groupObject === null) { return; }
    console.log('Group = ' + groupObject['attributes']['friendly_name']);
                membersArray = groupObject['attributes']['entity_id'];
                if (! membersArray) { return; }
               	for (i = 0; i < membersArray.length; i++) {
    console.log('Member = ' + membersArray[i]);
                    MemberObject = HADeviceEntity(membersArray[i]);
                    if (MemberObject === null) { continue; }  
    console.log('Member friendly_name =  ' + MemberObject['attributes']['friendly_name']);
                /* handle zone */
                    if (membersArray[i].substring(0,5) == 'zone.') {
                    <?php if ($show_mapping) { ?>
                        if (firstTimeMapInit) {
                            mapLocations[MemberObject['attributes']['friendly_name']] = L.circle([MemberObject['attributes']['latitude'], MemberObject['attributes']['longitude']], { radius: MemberObject['attributes']['radius'], opacity: 0.2, fillOpacity: 0.2, color: '#0057b8' }).bindTooltip(MemberObject['attributes']['friendly_name']).addTo(map);
                        }
                    <?php } ?>
                    } else {    
                /* handle device tracker */
                        var location = MemberObject['attributes']['reported_state'] || MemberObject['state'];
                        var PersonBadgeHTML = '<div class="PersonElement" title="' + MemberObject['attributes']['friendly_name'] + '" onClick="location.href = \'/home/map.php?center=' + MemberObject['attributes']['person_name'] + '&iframe=true\';"><p>' + MemberObject['attributes']['person_name'] + '</p><span>' + location + '</span></div>';
                        if (MemberObject['state'] == 'Home' || MemberObject['state'] == 'Just Arrived') {
                            ContainerOccupiedHTML += PersonBadgeHTML;
                        } else if (MemberObject['state'] == 'Extended') {
                            ContainerVacationHTML += PersonBadgeHTML;
                        } else {
                            ContainerAwayHTML += PersonBadgeHTML;
                        }

                        <?php if ($show_mapping) { ?>

                        if ((MemberObject['attributes']['latitude'] === null) || (MemberObject['attributes']['longitude'] === null)) { continue; }  

                    /* Update the map (in map.php) */

                        if (map != undefined) {
                            var person_name = MemberObject['attributes']['person_name'];
                            var smallMarker = L.icon({
                                iconUrl: 'https://assets.mapquestapi.com/icon/v2/marker-sm-0057b8-' + person_name.substring(0,1) + '.png',
                                iconRetinaUrl: 'https://assets.mapquestapi.com/icon/v2/marker-sm-0057b8-' + person_name.substring(0,1) + '@2x.png',
                                iconSize: markerSize.sm,
                                iconAnchor: markerAnchor.sm,
                                popupAnchor: markerPopupAnchor.sm
                            });

                            if (mapLocations[person_name] != undefined) {
                                map.removeLayer(mapLocations[person_name]);
                            }
                            mapLocations[person_name] = L.marker([MemberObject['attributes']['latitude'], MemberObject['attributes']['longitude']], {
                                icon: smallMarker,
                                draggable: false
                            }).bindTooltip(MemberObject['attributes']['friendly_name']).addTo(map);

                        /* center the map on this person TODO */
            console.log(MemberObject['attributes']['person_name'].toLowerCase());           
                            if (MemberObject['attributes']['person_name'].toLowerCase() == mapCenter) {
            console.log("panning");
                                map.panTo(new L.LatLng(MemberObject['attributes']['latitude'], MemberObject['attributes']['longitude']));
                            }
                        }
                        <?php } ?>
                    }
                }

                firstTimeMapInit = false;

                PositionPersonContainers();

                document.getElementById("ContainerVacation").innerHTML = ContainerVacationHTML;
                if (ContainerVacationHTML == '') {
                    document.getElementById("ContainerVacation").style.display = 'none';
                } else {
                    document.getElementById("ContainerVacation").style.display = 'block';
                }
                              document.getElementById("ContainerAway").innerHTML = ContainerAwayHTML;
                if (ContainerAwayHTML == '') {
                    document.getElementById("ContainerAway").style.display = 'none';
                } else {
                    document.getElementById("ContainerAway").style.display = 'block';
                }

                document.getElementById("ContainerOccupied").innerHTML = ContainerOccupiedHTML;
                if (ContainerOccupiedHTML == '') {
                    document.getElementById("ContainerOccupied").style.display = 'none';
                } else {
                    document.getElementById("ContainerOccupied").style.display = 'block';
                }
            }
        }
	});

	refreshTimeout_HADeviceData = setTimeout(RetrieveHADeviceData, 15000);
}

RetrieveHADeviceData();
</script>
<style>
.PersonElementsContainer { 
    background: rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 8px 0 rgba(255, 255, 255, 0.3), 0 6px 20px 0 rgba(255, 255, 255, 0.19); 
    border-radius: 32px;
    padding: 1px 4px 4px 4px;
    position: absolute;
    right: 8px;
}
.PersonElement {
    position: relative;
    display: inline-block;
    background-color: var(--color-royal-blue);
    color: white;
    width: 40px;
    height: 40px;
    x-margin: 8px 4px 12px 6px;
    margin: 8px auto 12px auto;
    text-align: center;
    border-radius: 20px;
    border: 2px solid black;
        box-shadow: 0 4px 8px 0 rgba(0, 87, 184, 0.2), 0 6px 20px 0 rgba(0, 87, 184, 0.19);  /* #0057b8 */
        cursor: pointer;
}  
.PersonElement p {
    display: inline-block;
    vertical-align: middle;
    margin: 10px auto 0px auto;
    font-size: 16px;
}
.PersonElement span {
    position: relative;
    left: -5px;
    bottom: -2px;
    font-size: 10px;
    min-width: 44px;
    display: inline-block;
    vertical-align: middle;
    margin: 0px auto 0px auto;
    border-radius: 11px;
    x-heigth: 22px;
    border: 2px solid black;
    padding: 1px;
    background-color: white;
    color: var(--color-royal-blue);
        box-shadow: 0 4px 8px 0 rgba(0, 87, 184, 0.2), 0 6px 20px 0 rgba(0, 87, 184, 0.19);
}
</style>
<!--- end of include/person_status_badges.php --->
