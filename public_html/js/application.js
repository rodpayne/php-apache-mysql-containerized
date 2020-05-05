/* ----- watch for any ajaxError ----- */
$( document ).ajaxError(
        function(e, x, settings, exception) {
            var message;
            var statusErrorMap = {
                '400' : '400 Server understood the request, but request content was invalid',
                '401' : '401 Unauthorized access',
                '403' : "403 Forbidden resource can't be accessed",
                '404' : '404 URL Not Found',
                '500' : '500 Internal server error',
                '503' : '503 Service unavailable'
            };
            if (x.status) {
                message = statusErrorMap[x.status];
                if(!message){
                    message=x.status + ' Unknown Error';
                }
			if (exception) {
				}else if(exception=='parsererror'){
					message='Parsing JSON Request failed';
				}else if(exception=='timeout'){
					message='Request Time out';
				}else if(exception=='abort'){
					message='Request was aborted by the server';
				}else {
					message='Unknown error: ' + exception;
				}
			}
			if (message) {
                                console.log('ajax error: ' + message + '; url = ' + settings.url);
                                alert('ajax error: ' + message + '; url = ' + settings.url);
			}
      });

/* get and set CSS variable values - https://davidwalsh.name/css-variables-javascript */
const cssVar = ( name, value ) => {
        if(name.substr(0, 2) !== "--") {
            name = "--" + name;
        }
        if(value) {
            document.documentElement.style.setProperty(name, value)
        }
        return getComputedStyle(document.documentElement).getPropertyValue(name);
}
    
//-----------------------------------------------------------------------------
//      bigger tap targets for the touchers:
//      default in application.css: --button-padding: 1.4rem; 
//      mouse clicks --> 1.4rem; finger clicks --> up to 2.8rem 
//-----------------------------------------------------------------------------

let buttonPadding;
if (localStorage.getItem('buttonPadding')) {
        buttonPadding = localStorage.getItem('buttonPadding');
        cssVar('--button-padding',buttonPadding);
} else if (sessionStorage.getItem('buttonPadding')) {
        buttonPadding = sessionStorage.getItem('buttonPadding');
        cssVar('--button-padding',buttonPadding);
}

//      See codeburst.io/the-only-way-to-detect-touch-with-javascript-7791a3346685 
window.addEventListener('pointerdown', function onFirstPointer(e) {
                let pointerHeight = e.height;
                window.removeEventListener('pointerdown', onFirstPointer, false);
                buttonPadding = '' + ((pointerHeight > 28)? 28 : (pointerHeight < 14)? 14 : pointerHeight) / 10 + 'rem';
                
                cssVar('--button-padding',buttonPadding);

                if (storageAvailable('localStorage')) {
                        localStorage.setItem('buttonPadding', buttonPadding);
                } else if (storageAvailable('sessionStorage')) {
                        sessionStorage.setItem('buttonPadding', buttonPadding);
                }
        }, false);

//-----------------------------------------------------------------------------
// 	See http://www.javascriptkit.com/javatutors/loadjavascriptcss.shtml
//-----------------------------------------------------------------------------

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
 

function HouseModesTableIndexForMode(mode,houseModesTable) {
	var index;
	for (index=0; index < houseModesTable.length; index++) {	
		if (houseModesTable[index]['Mode'] == mode) {
			break;
		}
	}
	return index;
}

	  
/* https://stackoverflow.com/questions/14638018/current-time-formatting-with-javascript */
/* http://jsfiddle.net/BNkkB/1/ */

function formatDate(date, format, utc) {
    var MMMM = ["\x00", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    var MMM = ["\x01", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    var dddd = ["\x02", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    var ddd = ["\x03", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

    function ii(i, len) {
        var s = i + "";
        len = len || 2;
        while (s.length < len) s = "0" + s;
        return s;
    }

    var y = utc ? date.getUTCFullYear() : date.getFullYear();
    format = format.replace(/(^|[^\\])yyyy+/g, "$1" + y);
    format = format.replace(/(^|[^\\])yy/g, "$1" + y.toString().substr(2, 2));
    format = format.replace(/(^|[^\\])y/g, "$1" + y);

    var M = (utc ? date.getUTCMonth() : date.getMonth()) + 1;
    format = format.replace(/(^|[^\\])MMMM+/g, "$1" + MMMM[0]);
    format = format.replace(/(^|[^\\])MMM/g, "$1" + MMM[0]);
    format = format.replace(/(^|[^\\])MM/g, "$1" + ii(M));
    format = format.replace(/(^|[^\\])M/g, "$1" + M);

    var d = utc ? date.getUTCDate() : date.getDate();
    format = format.replace(/(^|[^\\])dddd+/g, "$1" + dddd[0]);
    format = format.replace(/(^|[^\\])ddd/g, "$1" + ddd[0]);
    format = format.replace(/(^|[^\\])dd/g, "$1" + ii(d));
    format = format.replace(/(^|[^\\])d/g, "$1" + d);

    var H = utc ? date.getUTCHours() : date.getHours();
    format = format.replace(/(^|[^\\])HH+/g, "$1" + ii(H));
    format = format.replace(/(^|[^\\])H/g, "$1" + H);

    var h = H > 12 ? H - 12 : H == 0 ? 12 : H;
    format = format.replace(/(^|[^\\])hh+/g, "$1" + ii(h));
    format = format.replace(/(^|[^\\])h/g, "$1" + h);

    var m = utc ? date.getUTCMinutes() : date.getMinutes();
    format = format.replace(/(^|[^\\])mm+/g, "$1" + ii(m));
    format = format.replace(/(^|[^\\])m/g, "$1" + m);

    var s = utc ? date.getUTCSeconds() : date.getSeconds();
    format = format.replace(/(^|[^\\])ss+/g, "$1" + ii(s));
    format = format.replace(/(^|[^\\])s/g, "$1" + s);

    var f = utc ? date.getUTCMilliseconds() : date.getMilliseconds();
    format = format.replace(/(^|[^\\])fff+/g, "$1" + ii(f, 3));
    f = Math.round(f / 10);
    format = format.replace(/(^|[^\\])ff/g, "$1" + ii(f));
    f = Math.round(f / 10);
    format = format.replace(/(^|[^\\])f/g, "$1" + f);

    var T = H < 12 ? "AM" : "PM";
    format = format.replace(/(^|[^\\])TT+/g, "$1" + T);
    format = format.replace(/(^|[^\\])T/g, "$1" + T.charAt(0));

    var t = T.toLowerCase();
    format = format.replace(/(^|[^\\])tt+/g, "$1" + t);
    format = format.replace(/(^|[^\\])t/g, "$1" + t.charAt(0));

    var tz = -date.getTimezoneOffset();
    var K = utc || !tz ? "Z" : tz > 0 ? "+" : "-";
    if (!utc) {
        tz = Math.abs(tz);
        var tzHrs = Math.floor(tz / 60);
        var tzMin = tz % 60;
        K += ii(tzHrs) + ":" + ii(tzMin);
    }
    format = format.replace(/(^|[^\\])K/g, "$1" + K);

    var day = (utc ? date.getUTCDay() : date.getDay()) + 1;
    format = format.replace(new RegExp(dddd[0], "g"), dddd[day]);
    format = format.replace(new RegExp(ddd[0], "g"), ddd[day]);

    format = format.replace(new RegExp(MMMM[0], "g"), MMMM[M]);
    format = format.replace(new RegExp(MMM[0], "g"), MMM[M]);

    format = format.replace(/\\(.)/g, "$1");

    return format;
};

function md5(string) {

   function RotateLeft(lValue, iShiftBits) {
           return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
   }

   function AddUnsigned(lX,lY) {
           var lX4,lY4,lX8,lY8,lResult;
           lX8 = (lX & 0x80000000);
           lY8 = (lY & 0x80000000);
           lX4 = (lX & 0x40000000);
           lY4 = (lY & 0x40000000);
           lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
           if (lX4 & lY4) {
                   return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
           }
           if (lX4 | lY4) {
                   if (lResult & 0x40000000) {
                           return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                   } else {
                           return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                   }
           } else {
                   return (lResult ^ lX8 ^ lY8);
           }
   }

   function F(x,y,z) { return (x & y) | ((~x) & z); }
   function G(x,y,z) { return (x & z) | (y & (~z)); }
   function H(x,y,z) { return (x ^ y ^ z); }
   function I(x,y,z) { return (y ^ (x | (~z))); }

   function FF(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function GG(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function HH(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function II(a,b,c,d,x,s,ac) {
           a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
           return AddUnsigned(RotateLeft(a, s), b);
   };

   function ConvertToWordArray(string) {
           var lWordCount;
           var lMessageLength = string.length;
           var lNumberOfWords_temp1=lMessageLength + 8;
           var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
           var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
           var lWordArray=Array(lNumberOfWords-1);
           var lBytePosition = 0;
           var lByteCount = 0;
           while ( lByteCount < lMessageLength ) {
                   lWordCount = (lByteCount-(lByteCount % 4))/4;
                   lBytePosition = (lByteCount % 4)*8;
                   lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                   lByteCount++;
           }
           lWordCount = (lByteCount-(lByteCount % 4))/4;
           lBytePosition = (lByteCount % 4)*8;
           lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
           lWordArray[lNumberOfWords-2] = lMessageLength<<3;
           lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
           return lWordArray;
   };

   function WordToHex(lValue) {
           var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
           for (lCount = 0;lCount<=3;lCount++) {
                   lByte = (lValue>>>(lCount*8)) & 255;
                   WordToHexValue_temp = "0" + lByte.toString(16);
                   WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
           }
           return WordToHexValue;
   };

   function Utf8Encode(string) {
           string = string.replace(/\r\n/g,"\n");
           var utftext = "";

           for (var n = 0; n < string.length; n++) {

                   var c = string.charCodeAt(n);

                   if (c < 128) {
                           utftext += String.fromCharCode(c);
                   }
                   else if((c > 127) && (c < 2048)) {
                           utftext += String.fromCharCode((c >> 6) | 192);
                           utftext += String.fromCharCode((c & 63) | 128);
                   }
                   else {
                           utftext += String.fromCharCode((c >> 12) | 224);
                           utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                           utftext += String.fromCharCode((c & 63) | 128);
                   }

           }

           return utftext;
   };

   var x=Array();
   var k,AA,BB,CC,DD,a,b,c,d;
   var S11=7, S12=12, S13=17, S14=22;
   var S21=5, S22=9 , S23=14, S24=20;
   var S31=4, S32=11, S33=16, S34=23;
   var S41=6, S42=10, S43=15, S44=21;

   string = Utf8Encode(string);

   x = ConvertToWordArray(string);

   a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

   for (k=0;k<x.length;k+=16) {
           AA=a; BB=b; CC=c; DD=d;
           a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
           d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
           c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
           b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
           a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
           d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
           c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
           b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
           a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
           d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
           c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
           b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
           a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
           d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
           c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
           b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
           a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
           d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
           c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
           b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
           a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
           d=GG(d,a,b,c,x[k+10],S22,0x2441453);
           c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
           b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
           a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
           d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
           c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
           b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
           a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
           d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
           c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
           b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
           a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
           d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
           c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
           b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
           a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
           d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
           c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
           b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
           a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
           d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
           c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
           b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
           a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
           d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
           c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
           b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
           a=II(a,b,c,d,x[k+0], S41,0xF4292244);
           d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
           c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
           b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
           a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
           d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
           c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
           b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
           a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
           d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
           c=II(c,d,a,b,x[k+6], S43,0xA3014314);
           b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
           a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
           d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
           c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
           b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
           a=AddUnsigned(a,AA);
           b=AddUnsigned(b,BB);
           c=AddUnsigned(c,CC);
           d=AddUnsigned(d,DD);
   		}

   	var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);

   	return temp.toLowerCase();
}

function storageAvailable(type) {		/* https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API/Using_the_Web_Storage_API */
    try {
        var storage = window[type],
            x = '__storage_test__';
        // @ts-ignore
        storage.setItem(x, x);
        // @ts-ignore
        storage.removeItem(x);
        return true;
    }
    catch(e) {
        return e instanceof DOMException && (
            // everything except Firefox
            e.code === 22 ||
            // Firefox
            e.code === 1014 ||
            // test name field too, because code might not be present
            // everything except Firefox
            e.name === 'QuotaExceededError' ||
            // Firefox
            e.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
            // acknowledge QuotaExceededError only if there's something already stored
            storage.length !== 0;
    }
}

//-----------------------------------------------------------------------------
//  For status.php
//-----------------------------------------------------------------------------

var currentHouseMode;
var clickedHouseMode;
var watchingCurrentHouseMode = false;
var houseModesTable = [];

function SubmitHouseModeForm(houseMode) {
	var HouseModeSubmitButton = document.getElementById('HouseModeSubmitButton');
	HouseModeSubmitButton.value = 'set house mode '+houseMode;
	$('.pre-loader').show();	
	var HouseModeSubmitForm = document.getElementById('HouseModeSubmitForm');
	HouseModeSubmitForm.submit();	
}

function DisplayHouseModes(currentHouseMode,houseModesTable) {
        if (document.getElementById('HouseModesDisplay') === null) {
                console.log('ID = HouseModesDisplay is null');
        } else {
	        var tableHTML = '<table class="border-td center-text" style="width: 90%">';
	        tableHTML += '<tr><th>Name</th><th>Description</th><th class="optional-400">Alarms</th><th class="optional-500">Temperature</th></tr>';
	        for (var i = 0; i < houseModesTable.length; i++) {
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
	/*	InterestedInAppinfoDevices(false);
	*/	$('#confirmation-screen').hide();

		SubmitHouseModeForm(clickedHouseMode);
}
	
function ConfirmationCancelled() {
		InterestedInCurrentHouseMode(false);
	/*	InterestedInAppinfoDevices(false);
	*/	$('#confirmation-screen').hide();
}
        
var houseModesTable;

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

function RetrieveConfigItem(configItem) {
	$.ajax({
		url: '/service/config-service.php',
		cache: false,
		data: {
			request: configItem
		},
		success: function( result ) {
			if (result === undefined) {
				return;
			}
			return JSON.parse(result.trim());
			}
		}
	);
}

var currentHouseMode = RetrieveCurrentHouseMode();

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
			var newHouseMode = JSON.parse(result.trim());
	
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
        return currentHouseMode;
}

function RetrieveWeatherData() {
/*	$.ajax({
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
/*				document.getElementById("WeatherAlert").innerHTML = "<p style='color:var(--color-red);'>"+result+"</p>";
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
*/ 
}

setTimeout(RetrieveWeatherData,1 * 1000);	
var weatherTimer = setInterval(RetrieveWeatherData,60 * 1000);	

/* Javascript functions specific to the confirmation-screen */
/* http://hilios.github.io/jQuery.countdown/documentation.html */
		
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
/*	InterestedInAppinfoDevices(true);
*/
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
	if ((currentTime - loginTime < 30)) {	/* don't require pincode right after login */ 
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

//-----------------------------------------------------------------------------
//  OnPageLoad/OnResize functions for pages loaded by the application shell.
//  (Pages are loaded as straight HTML, with no <style> or <script> blocks.)
//-----------------------------------------------------------------------------
function ContentPageLoaded(pageURL) {
        console.log("ContentPageLoaded('" + pageURL + "');");
        
        if (pageURL.substring(0,12) == '/home/status') {
                StatusPageLoaded()
        }
}

function ContentPageResized(pageURL) {
        console.log("ContentPageResized('" + pageURL + "');");
        
        if (pageURL.substring(0,12) == '/home/status') {
                StatusPageResized()
        }
}

function StatusPageLoaded() {
        if (document.getElementById('HouseModesDisplay') !== null) {
                DisplayHouseModes(currentHouseMode,houseModesTable);
        }
/*	RetrieveDeviceData();
*/	RetrieveCurrentHouseModeSoon();
}

function StatusPageResized() {
/*    PositionPersonContainers()
    
    FormatCompleteList();
*/
}
