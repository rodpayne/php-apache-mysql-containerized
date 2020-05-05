<?php /* ----- include under input field id="newpassword" ----- */ 
	  /* ----- See https://css-tricks.com/password-strength-meter/ ----- */	
?>
<!-- password meter -->
	<span id="password-strength-text"></span>
	<meter max="5" id="password-strength-meter"><span id="password-strength-fallback-text"></span></meter>
<script src="../js/zxcvbn.js"></script><!-- "https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.2.0/zxcvbn.js" -->
<script>
var newpasswordMeterCheckResult = 0;
document.getElementById('newpassword').addEventListener('input', newpasswordMeterCheck);
function newpasswordMeterCheck() {

	var strength = { 1: "Worst", 2: "Bad", 3: "Weak", 4: "Good", 5: "Strong" }
	var password = document.getElementById('newpassword');
	var meter = document.getElementById('password-strength-meter');
	var fallback = document.getElementById('password-strength-fallback-text');
	var text = document.getElementById('password-strength-text');

	var val = password.value;
	var result = zxcvbn(val);
  // Update the password strength meter
	meter.value = result.score + 1;
  // Update the text indicator
	if (val !== "") {
        newpasswordMeterCheckResult = result.score + 1
		meter.style.display = "block";
		meter.title = "Strength: " + strength[newpasswordMeterCheckResult];
		fallback.innerHTML = "Strength: " + "<strong>" + strength[newpasswordMeterCheckResult] + "</strong>"; 
		text.innerHTML = "<i>" + result.feedback.warning + "</i>"; 
	} else {
        newpasswordMeterCheckResult = 0;
		meter.style.display = "none";
		fallback.innerHTML = "";
		text.innerHTML = "";
	}
}
</script>
<style>
meter {
	display:none;
  /* Reset the default appearance */
	-webkit-appearance: none;
       -moz-appearance: none;
            appearance: none;
	x-position: relative;
	x-top: -4px;
	margin: 0;
	width: 100%;
	height: 0.5em;
  /* Applicable only to Firefox */
	background: none;
	background-color: rgba(0, 0, 0, 0.1);
}
meter::-webkit-meter-bar {
	background: none;
	background-color: rgba(0, 0, 0, 0.1);
}
/* Webkit based browsers */
	meter[value="1"]::-webkit-meter-optimum-value { background: var(--color-red); }
	meter[value="2"]::-webkit-meter-optimum-value { background: var(--color-red); }
	meter[value="3"]::-webkit-meter-optimum-value { background: var(--color-yellow); }
	meter[value="4"]::-webkit-meter-optimum-value { background: var(--color-green); }
	meter[value="5"]::-webkit-meter-optimum-value { background: var(--color-green); }
/* Gecko based browsers */
	meter[value="1"]::-moz-meter-bar { background: var(--color-red); }
	meter[value="2"]::-moz-meter-bar { background: var(--color-red); }
	meter[value="3"]::-moz-meter-bar { background: var(--color-yellow); }
	meter[value="4"]::-moz-meter-bar { background: var(--color-green); }
	meter[value="5"]::-moz-meter-bar { background: var(--color-green); }
</style>
<!-- password meter -->