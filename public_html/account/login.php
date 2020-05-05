<?php

// Long-Term Persistent Authentication:
// 		See https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
// Other design points:
// 		See https://stackoverflow.com/questions/549/the-definitive-guide-to-form-based-website-authentication/477579#477579

session_start();

/* Initialization: */

require_once('../include/execute_sql.php');			

if (empty($_SESSION['login_return'])) {						// is this the first display of the login page?
	if (empty($_SERVER['HTTP_REFERER'])) {
		$_SESSION['login_return'] = '/home/status.php';			// the user started on login page, go elsewhere after logging on
	} else {
		$_SESSION['login_return'] = filter_input(INPUT_SERVER,'HTTP_REFERER',FILTER_SANITIZE_URL); 	// otherwise, return to the page that the user entered from
	}
}
if (empty($_SESSION['password_attempts'])) {	
	$_SESSION['password_attempts'] = 0;					// initialize counter for number of attempts
}

$error=''; 												// error message to display on the login page

$allowedAttempts = $static_config['ACCOUNT']['allowedAttempts'];	// maximum number of attempts allowed before shutting down user

//----------------------------function log_on_the_user_and_exit--------------
function log_on_the_user_and_exit ($userID,$password,$userName,$houseID,$userAttributes,$remember) {

global $static_config;

			/* provide a one time password for "remember me" functionality */
			
				if ($remember) {	
					if (function_exists('random_bytes')) {	
						$randomSelector = bin2hex(random_bytes(16));
						$randomPassword = bin2hex(random_bytes(16));
					} else {					/* removed in PHP 7.2.0 */
						$randomSelector = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
						$randomPassword = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
					}
					$hashed_password = hash_password($userID,$randomPassword);
					$currentDateTime = date('Y-m-d H:i:s');

					$query = "INSERT INTO `authtokens` (`selector`, `password`, `userID`, `datetimeupdated`) VALUES ('" . $randomSelector . "', '" . $hashed_password . "', '" . $userID . "', '" . $currentDateTime . "')";
					list($result,$count,$error) = Execute_SQL($query);
					if ($error) {
						echo '<br>Error setting "remember me" [' . $error . ']<br>';
					} else {
						setcookie('userid',$userID,time() + ($static_config['ACCOUNT']['rememberMeRetention'] * 24*60*60)); 	// set cookies
						setcookie('password',$randomSelector . $randomPassword,time() + ($static_config['ACCOUNT']['rememberMeRetention'] * 24 * 60 * 60));
					}
				}
			
			/* initialize session variables */

				$_SESSION['login_userID']=$userID; 
				$_SESSION['login_userName']=$userName; 
				$_SESSION['login_userAttributes']=$userAttributes; 
				$_SESSION['login_houseID']=$houseID;
				$_SESSION['login_time']=time();
                $_SESSION['password_attempts'] = 0;
				
			/* go to their starting page */
			
 				header('location: ' . $_SESSION['login_return']); 		// redirect to original page
				echo '<html><head><meta http-equiv="refresh" content="0,URL=' . $_SESSION['login_return'] . '"></head><body>Redirecting...</body></html>';
				exit();
}
//-----------------------------------------------------------------------------

//----------------------------function log_off_the_user------------------------
function log_off_the_user ($userID) {

	/* delete cookies */

		setcookie('userid','',time()-60*60*24*365);	
		setcookie('password','',time()-60*60*24*365);

	/* delete session variables */
		
		$_SESSION['login_userName'] = ""; 
		$_SESSION['login_userID'] = "";
		$_SESSION['login_userAttributes'] = "";
		$_SESSION['login_houseID'] = "";
		$_SESSION['login_time'] = "";
		$_SESSION['password_attempts'] = 0;

	/* delete one time passwords (but don't stress over any errors) */

		$query = "DELETE FROM `authtokens` WHERE `authtokens`.`userID` = '" . $userID . "'"; 
		list($result,$count,$error) = Execute_SQL($query);
}
//-----------------------------------------------------------------------------

//----------------------------function validate_user_credentials---------------
function validate_user_credentials ($userID,$password) { 

		global $static_config;

#	$hashed_password = hash_password($userID,$password);
#	echo var_dump($hashed_password);
#	return "testing";
		if ($static_config['DB']['useDatabase']) {

		/* look for the user in the database */

			$query = "SELECT * FROM appusers WHERE userID = '" . Escape_SQL($userID) . "'";
			list($result,$count,$error) = Execute_SQL($query);
			if ($error) {
				return $error;
				exit;
			}
			if ($result->num_rows === 1) {
				$record = $result->fetch_assoc();
				
			/* verify the user supplied password against the stored hashed password */
			
				$correctPasswordHash = $record['password']; 
				if (verify_hashed_password($userID,$password,$correctPasswordHash)) {
					$userName = $record['userName']; 
					$houseID = $record['houseID']; 

				/* make sure that the user is active */

					$userAttributes = $record['attributes'];
					if (strpos($userAttributes,'/active/') === FALSE) {
						return('This User ID is not active.'); 
					}

				/* log the user on */
				
					log_on_the_user_and_exit ($userID,$password,$userName,$houseID,$userAttributes,(!empty($_POST['rememberme'])));
				}
			}
		}

	/* Allow for a setup account, with a hashed password stored in the config file */
	
		if (verify_hashed_password($userID,$password,$static_config['ACCOUNT']['LOGIN_password'])) {
			$userName = 'Rod Payne (setup)';
			$houseID = 'A';
			log_on_the_user_and_exit ($userID,$password,$userName,$houseID,'/active/setup/',false);				
		} else {
			return('User ID or Password is not correct.');
		}
}

//-----------------------------------------------------------------------------

//----------------------------function validate_user_onetime_credentials---------------
function validate_user_onetime_credentials ($userID,$onetimeSelector,$onetimePassword) { 			

		global $static_config;
			
		if ($static_config['DB']['useDatabase']) {

		/* look for the one time password in the database */

			$query = "SELECT * FROM authtokens WHERE selector = '" . Escape_SQL($onetimeSelector) . "'";
			list($result,$count,$error) = Execute_SQL($query);
			if ($error) {
				return 'Unable to verify "remember me" credentials. [' . $error . ']';
				exit;
			}
			if ($result->num_rows === 1) {
				$record = $result->fetch_assoc();
				$correctPasswordHash = $record['password'];
			
			/* delete the one time password from the database */
			
				$ID = $record['ID'];		
				$query = "DELETE FROM `authtokens` WHERE `authtokens`.`ID` = $ID"; 
				list($result,$count,$error) = Execute_SQL($query);
			
			/* verify the cookie supplied password against the stored hashed password */

				if (! verify_hashed_password($userID,$onetimePassword,$correctPasswordHash)) {
					return 'Unable to verify "remember me" credentials. [1]';
					exit;
				}
			} else {
				return 'Unable to verify "remember me" credentials. [2]';
				exit;
			}	

		/* look for the user in the database */

			$query = "SELECT * FROM appusers WHERE userID = '" . Escape_SQL($userID) . "'";
			list($result,$count,$error) = Execute_SQL($query);
			if ($error) {
				return $error;
				exit;
			}
			if ($result->num_rows === 1) {
				$record = $result->fetch_assoc();
				$userName = $record['userName']; 
				$houseID = $record['houseID']; 
				$userAttributes = $record['attributes'];
				if (strpos($userAttributes,'/active/') === FALSE) { return("This User ID is not active."); }

				/* log the user on */

				log_on_the_user_and_exit ($userID,$password,$userName,$houseID,$userAttributes,true);
			}
		}
}

//-----------------------------------------------------------------------------

	/* ------------------------------ */
	/* Process the submit of the form */
	/* ------------------------------ */

if (isset($_POST['submitButton'])) {
	if ($_POST['submitButton'] == "logoff") {
		$error='User ID "' . $_SESSION['login_userID'] . '" has been logged off.';
		log_off_the_user($_SESSION['login_userID']);
	} else {

	/* try the credentials entered on the form if the form was submitted */	

		if (empty($_POST['userid']) || empty($_POST['password'])) {
			$error = 'User ID and Password are required.';
		} else {

		/* retrieve $userID and $password and validate them */

			$userID = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['userid']);
			$password=$_POST['password'];

			$error = validate_user_credentials ($userID,$password);
		}
	
	/* 	Exit if there have been too many failed password attempts */

		if (empty($_SESSION['login_user'])) {  								// login not yet successful?
			$_SESSION['password_attempts'] = $_SESSION['password_attempts'] + 1;
			if ($_SESSION['password_attempts'] == ($allowedAttempts - 1)) {	// last chance?
				$error = "Last chance: " . $error;							// prefix for potential error message
			} else {
				if ($_SESSION['password_attempts'] >= $allowedAttempts) {		// too many attempts?
					session_destroy();
					echo '<html><head><meta http-equiv="refresh" content="0,URL=about:blank"></head><body>Redirecting...</body></html>'; // get them off my server
					exit();
				}
			}
		}
	}
} else {

	/* page was called directly (no form submitted), so try the saved "remember me" credentials */

	if (isset($_COOKIE['userid']) && isset($_COOKIE['password'])) { 
		$cookiePassword = preg_replace('/[^a-f0-9]/', '', $_COOKIE['password']);
		$onetimeSelector = substr($cookiePassword,0,32);
		$onetimePassword = substr($cookiePassword,32);
		$userID = preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE['userid']);
		validate_user_onetime_credentials ($userID,$onetimeSelector,$onetimePassword);	
#		log_off_the_user($userID);				// delete the cookies that don't work anymore
	}
}

//	The rest is the login web page:
?>
<!DOCTYPE html>
<html>
<head>
	<title>Log On <?php echo $_SESSION['login_return']; ?></title>
	<?php require('../include/app_standard_head.html'); ?>
</head>
<body>
<?php include('../include/startup_spinner.html'); ?> 
<div class="center" style="width: 90%">
<form class="content" action="" method="post">
    <div class="logon-content">
		<div class="container">
			<label><b>User ID</b></label>
			<input type="text" placeholder="Enter User ID" name="userid" required onInput="LoginFormUpdated();">

			<label><b>Password</b></label>
			<input type="password" placeholder="Enter Password" name="password" required onInput="LoginFormUpdated();">

			<div id="LoginError" class="center-text" style="color:var(--color-red);"><b><?php echo $error; ?></b></div>

			<label>
				<input name="rememberme" type="checkbox"<?php if (!empty($_POST["rememberme"])) {echo ' checked';} ?>> Remember me
			</label>
		</div>        
		<div class="container">
			<button name="submitButton" type="submit" value=" Login " onClick="return LoginFormSubmitted();"><b>Login</b></button>
		</div>
	</div>
</form>
</div>
<?php
//-------------------------------------debugging-info--------------------------
//	print '<hr><pre>$_SESSION = ';
//	var_dump($_SESSION);
//	print '<hr>$_COOKIE = ';
//	var_dump($_COOKIE);
//	print '<hr>$_SERVER = ';
//	var_dump($_SERVER);
//	print "<hr></pre>";	
//-----------------------------------------------------------------------------
?>
<script>
<?php 
	if ($error && (substr($error,0,7) != 'Success')) {
		echo "document.getElementsByName('userid')[0].style.border='#FF0000 1px solid';";
		echo "document.getElementsByName('password')[0].style.border='#FF0000 1px solid';";
	}
?>
function LoginFormUpdated() {
	document.getElementById('LoginError').innerHTML = ''; 					/* remove previous message */
	document.getElementsByName('userid')[0].style.border='';
	document.getElementsByName('password')[0].style.border='';
}
function LoginFormSubmitted() {
	var LoginError = document.getElementById('LoginError');
	LoginError.innerHTML = '';

	var userid = document.getElementsByName('userid')[0];	
	if (! userid.value) {
		userid.style.border = '#FF0000 1px solid';
		LoginError.innerHTML = 'User ID and Password are required.';
	}

	var password = document.getElementsByName('password')[0];	
	if (! password.value) {
		password.style.border = '#FF0000 1px solid';
		LoginError.innerHTML = 'User ID and Password are required.';
	}
	
	if (LoginError.innerHTML != '') {
		return false;
	} else {
		$('.pre-loader').show();
		return true;
	}
}
<?php include('../include/startup_spinner_done.js'); ?> 
</script>

</body>
</html>
