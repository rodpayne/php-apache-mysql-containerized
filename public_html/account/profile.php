<?php require('../include/check_session_login.php'); ?>
<?php
require_once('../include/execute_sql.php');			

	$allowedAttempts = $static_config['ACCOUNT']['allowedAttempts'];	// maximum number of attempts allowed before shutting down user

	$error=''; 												// error message to display on the password change page
	
/* ------------------------------ */
/* Process the submit of the form */
/* ------------------------------ */

	if (isset($_POST['submitButton'])) {
		$whichSubmitButton = $_POST['submitButton'];

		/* --------------- */
		/* Change Password */
		/* --------------- */
		
		if ($whichSubmitButton == 'Change Password') {
			
			if (empty($_POST['userid']) || empty($_POST['oldpassword'])) {
				$error = "User ID and Password are required.";
			} else {
				// Retrieve $userID and $password
				$userID = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['userid']);
				$password=$_POST['oldpassword'];
				$newPassword=$_POST['newpassword'];	

			# 	Look for the user in the database:

				$query = "SELECT * FROM appusers WHERE userID = '" . Escape_SQL($userID) . "'";
				list($result,$count,$error) = Execute_SQL($query);
				if (! $error) {
					if ($result->num_rows === 1) {
						$record = $result->fetch_assoc();
						$userAttributes = $record['attributes'];
						if (strpos($userAttributes,'/active/') === FALSE) { 
							$error = 'This User ID is not active.';
						} else {
				#	Check the old password:
							$correctPasswordHash = $record['password']; 
							if (! verify_hashed_password($userID,$password,$correctPasswordHash)) {
								$error = 'User ID or Password is not correct. Please re-enter and try again.';
							} else {
                                session_start(); 
                                $_SESSION['password_attempts'] = 0;							// reset counter for number of attempts
                                session_write_close();

					#	The User ID and old password are correct...
								$ID = $record['ID'];
								$hashed_password = hash_password($userID,$newPassword);
								$query = "UPDATE appusers SET `password` = '" . $hashed_password . "' WHERE ID = ".$ID;
								list($result,$count,$error) = Execute_SQL($query);
								if (! $error) {
									$error = 'Success! The password has been changed.';
									if (isset($_COOKIE['userid']) && isset($_COOKIE['password']) && ($_COOKIE['userid'] == $userID)) { 
										setcookie('password',$newmd5Password,time()+60*60*24*365);
										$error .= '<br>(The new credentials are also being remembered for future logins.)';
									}

								/* delete one time passwords (but don't stress over any errors) */

									$query = "DELETE FROM `authtokens` WHERE `authtokens`.`userID` = '" . $userID . "'"; 
									list($result,$count,$error) = Execute_SQL($query); 
								}
							}
						}
					} else {
						$error = 'Incorrect User ID or Password. Please re-enter and try again.';
					}
				}
			}
	
			/* Exit if there have been too many failed password attempts: */

			if ($error) {
				session_start(); 
				if (empty($_SESSION['password_attempts'])) {	
					$_SESSION['password_attempts'] = 0;							// initialize counter for number of attempts
				}
				$_SESSION['password_attempts'] = $_SESSION['password_attempts'] + 1;
				if ($_SESSION['password_attempts'] == ($AllowedAttempts - 1)) {	// last chance?
					$error = 'Last chance: ' . $error;							// prefix for potential error message
				} else {
					if ($_SESSION['password_attempts'] >= $AllowedAttempts) {		// too many attempts?
						session_destroy();
						echo '<html><head><meta http-equiv="refresh" content="0,URL=about:blank"></head><body>Redirecting...</body></html>'; // get them off my server
						exit();
					}
				}
				session_write_close();
			}
			
		/* --------- */
		/* otherwise */
		/* --------- */
		
		} else {
			echo '<div id="MessageFromUpdate" class="closebox">';
			echo '<button type="button" class="close" onClick="document.getElementById(\'MessageFromUpdate\').style.display=\'none\';"><span>&times;</span></button>';

			echo '<p style="color: red;">This function is not yet implemented.</p>';

			echo '</div>';
		}
	}
?> 
<!DOCTYPE html>
<html>
<head>
	<title>Status</title>
	<?php require('../include/app_standard_head.html'); ?>	
</head>

<body>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php include('../include/startup_spinner.html'); ?> 
<div class="center" style="width: 90%">
<form class="content" action="" method="post">
    <div class="logon-content">
	    <div class="container">
			<label><b>Change Password</b></label>
			<p>Enter your user ID and current password, type a new password, and then type it again to confirm it.</p>
		<!--	<p>After making the change, you will need to re-enter the user ID and password to sign in next time. </p>  -->
		</div>
	    <div class="container">
			<label><b>User ID</b></label>
			<input type="text" placeholder="Enter User ID" name="userid" required onInput="ValidateChangeFormUpdate();">

			<label><b>Old Password</b></label>
			<input type="password" placeholder="Enter Current Password" name="oldpassword" required onInput="ValidateChangeFormUpdate();">
		</div>
	    <div class="container">
			<label><b>New Password</b></label>
			<input type="password" placeholder="Enter New Password" id="newpassword" name="newpassword" required onInput="ValidateChangeFormUpdate();">
<?php include('../include/newpassword_meter.php'); ?>
			<input type="password" placeholder="Confirm New Password" name="confirmpassword" required onInput="ValidateChangeFormUpdate();">

		</div>
	    <div class="container">
			<div id="ChangePasswordError" class="center-text" style="color:var(--color-red);"><b><?php echo $error; ?></b></div>
			<button id="ChangePasswordSubmitButton" style="display: none;" name="submitButton" type="submit" value="Change Password" onClick="return PasswordChangeFormSubmitted();"><b>Change Password</b></button>
		</div>
    </div>        
</form>
</div>
<script>
<?php 
	if ($error && (substr($error,0,7) != 'Success')) {
		echo "document.getElementsByName('userid')[0].style.border='#FF0000 1px solid';";
		echo "document.getElementsByName('oldpassword')[0].style.border='#FF0000 1px solid';";
	}
?>
function ValidateChangeFormUpdate() {
	document.getElementById('ChangePasswordError').innerHTML = ''; 					/* remove previous message */
	document.getElementsByName('userid')[0].style.border='';
	document.getElementsByName('oldpassword')[0].style.border='';
	var oldpassword = document.getElementsByName('oldpassword')[0];
	var newpassword = document.getElementsByName('newpassword')[0];
	var confirmpassword = document.getElementsByName('confirmpassword')[0];
	
	if (newpassword.value.length < 6) {
		newpassword.style.border = '#FF0000 1px solid';
		confirmpassword.style.border = '#FF0000 1px solid';
		document.getElementById('ChangePasswordError').innerHTML = 'Please choose a longer password.';
		return false;
	} else {
        newpasswordMeterCheck();
        if (newpasswordMeterCheckResult < 4) {
            newpassword.style.border = '#FF0000 1px solid';
            document.getElementById('ChangePasswordError').innerHTML = 'Please choose a better password.';
            return false;
        }
	}
	
	if (newpassword.value == oldpassword.value) {
		newpassword.style.border = '#FF0000 1px solid';
		confirmpassword.style.border = '#FF0000 1px solid';
		document.getElementById('ChangePasswordError').innerHTML = 'Please choose a different password.';
		return false;
	}

	if (newpassword.value != confirmpassword.value) {
		newpassword.style.border = '#FF0000 1px solid';
		confirmpassword.style.border = '#FF0000 1px solid';
		document.getElementById('ChangePasswordError').innerHTML = 'New password and confirmation do not match.';
		return false;
	} else {
		newpassword.style.border = '';
		confirmpassword.style.border = '';
	}
    
	document.getElementById('ChangePasswordSubmitButton').style.display = 'block';   	/* show the submit button */
    return true;
}
function PasswordChangeFormSubmitted() {
        if (ValidateChangeFormUpdate() == false) {
        return false;
    }
	
	document.getElementById('ChangePasswordSubmitButton').style.display = 'none';   	/* hide the submit button */
	$('.pre-loader').show();
	return true;
}
<?php include('../include/startup_spinner_done.js'); ?> 
</script>
</body>
</html>
