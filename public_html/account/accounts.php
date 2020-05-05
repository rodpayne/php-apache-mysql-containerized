<?php require('../include/check_session_login.php'); ?>
<?php
require_once('../include/execute_sql.php');			

	$allowedAttempts = $static_config['ACCOUNT']['allowedAttempts'];	// maximum number of attempts allowed before shutting down user

	$changeSubmissionError=''; 												// error message to display on the password change page
	
/* ------------------------------ */
/* Process the submit of the form */
/* ------------------------------ */

	if (isset($_POST['submitButton'])) {
		$whichSubmitButton = $_POST['submitButton'];

        if (strpos($userAttributes,'/accounts/') === FALSE) { 
            $changeSubmissionError = "This function is not available without the /accounts/ user attribute.";
        } else {

            $accountID=$_POST['accountID'];
            $yourPassword=$_POST['yourPassword'];
            $userIDInput = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['userIDInput']);
            $userNameInput=$_POST['userNameInput'];	
            $houseIDInput=$_POST['houseIDInput'];	
            $attributesInput=$_POST['attributesInput'];	
            $newpassword=$_POST['newpassword'];	

            /* -------------- */
            /* Change Account */
            /* -------------- */
		
            if ($whichSubmitButton == 'Change Account') {

				Do { 				// ---------- "function" to update database ----------
			
                    if (empty($_POST['userIDInput']) || empty($_POST['yourPassword'])) {
                        $changeSubmissionError = "User ID and Your Password are required.";
                        break;
                    }
    
                # 	Get the administrator's record in the database:

                    $userID = $_SESSION['login_userID']; 

                    $query = "SELECT * FROM appusers WHERE userID = '" . Escape_SQL($userID) . "'";
                    list($result,$count,$changeSubmissionError) = Execute_SQL($query);
                    if ($changeSubmissionError) {
                        break;
                    }
                    if ($result->num_rows !== 1) {
                        $changeSubmissionError = 'Database error - duplicate User ID.';
                        break;
                    }
                    $record = $result->fetch_assoc();
                
                #	Check the administrator's password:
                
                    $correctPasswordHash = $record['password']; 
                    if (! verify_hashed_password($userID,$yourPassword,$correctPasswordHash)) {
                        $changeSubmissionError = 'Password is not correct. Please re-enter and try again.';
                        Write_Log_Message ('security','Invalid administrator password for account change.');
                        break;
                    }
                    session_start(); 
                    $_SESSION['password_attempts'] = 0;							// reset counter for number of attempts
                    session_write_close();
    
                    if ($accountID == -1) {         /* new account? */
                        
                        $hashed_password = hash_password($userIDInput,$newpassword);
                        
                        $query = "INSERT INTO appusers ("
                            . "`userID`,"
                            . "`houseID`,"
                            . "`userName`,"
                            . "`attributes`,"
                            . "`password`"
                            . ") VALUES ("
                            . "'" . Escape_SQL($userIDInput) . "',"
                            . "'" . Escape_SQL($houseIDInput) . "',"
                            . "'" . Escape_SQL($userNameInput) . "',"
                            . "'" . Escape_SQL($attributesInput) . "',"
                            . "'" . $hashed_password . "'"
                            . ");";
                        list($result,$count,$changeSubmissionError) = Execute_SQL($query);
                        if ($changeSubmissionError) {
                            break;
                        }
                        $changeSubmissionError = 'Success! account has been added.';
                        Write_Log_Message ('security',"UserID $userIDInput has been added.");
                        break;
                    }
                    
                # 	Look for the user account in the database:
    
                    $query = "SELECT * FROM appusers WHERE ID = '" . Escape_SQL($accountID) . "'";
                    list($result,$count,$changeSubmissionError) = Execute_SQL($query);
                    if ($changeSubmissionError) {
                        break;
                    }
                    if ($result->num_rows === 1) {
                        $record = $result->fetch_assoc();
                        $ID = $record['ID'];
                        
                        if ((! $newpassword) && ($userIDInput != $record['userID'])) {
                            $changeSubmissionError = 'New password must be set if User ID changes.';
                            break;
                        }
                        
                        if ($newpassword) {
                            $hashed_password = hash_password($userIDInput,$newpassword);
                        } else {
                            $hashed_password = $record['password']; 
                        }
                        $query = "UPDATE appusers SET "
                            . "`userID` = '" . Escape_SQL($userIDInput) . "',"
                            . "`houseID` = '" . Escape_SQL($houseIDInput) . "',"
                            . "`userName` = '" . Escape_SQL($userNameInput) . "',"
                            . "`attributes` = '" . Escape_SQL($attributesInput) . "',"
                            . "`password` = '" . $hashed_password . "'"
                            . " WHERE ID = ".$ID;
                        list($result,$count,$changeSubmissionError) = Execute_SQL($query);
                        if ($changeSubmissionError) {
                            break;
                        }
                        $changeSubmissionError = 'Success! account has been updated.';
                        Write_Log_Message ('security',"UserID $userIDInput has been updated.");
    
                    /* delete one time passwords (but don't stress over any errors) */
    
                        $query = "DELETE FROM `authtokens` WHERE `authtokens`.`userID` = '" . $userIDInput . "'"; 
                        list($result,$count,$error) = Execute_SQL($query); 
                                
                    }

                } while (FALSE);	// ---------- end of inline "function" -----------
	
            /* Exit if there have been too many failed password attempts: */

                if ($changeSubmissionError == 'Password is not correct. Please re-enter and try again.') {
                    session_start(); 
                    if (empty($_SESSION['password_attempts'])) {	
                        $_SESSION['password_attempts'] = 0;							// initialize counter for number of attempts
                    }
                    $_SESSION['password_attempts'] = $_SESSION['password_attempts'] + 1;
                    if ($_SESSION['password_attempts'] == ($AllowedAttempts - 1)) {	// last chance?
                        $changeSubmissionError = 'Last chance: ' . $changeSubmissionError;							// prefix for potential error message
                    } else {
                        if ($_SESSION['password_attempts'] >= $AllowedAttempts) {		// too many attempts?
                            session_destroy();
                            echo '<html><head><meta http-equiv="refresh" content="0,URL=about:blank"></head><body>Redirecting...</body></html>'; // get them off my server
                            exit();
                        }
                    }
                    session_write_close();
                }
                
            /* -------------- */
            /* Delete Account */
            /* -------------- */
		
            } elseif ($whichSubmitButton == 'Delete Account') {

				Do { 				// ---------- "function" to update database ----------
			
                    if (empty($_POST['userIDInput']) || empty($_POST['yourPassword'])) {
                        $changeSubmissionError = "User ID and Your Password are required.";
                        break;
                    }
    
                # 	Get the administrator's record in the database:

                    $userID = $_SESSION['login_userID']; 

                    $query = "SELECT * FROM appusers WHERE userID = '" . Escape_SQL($userID) . "'";
                    list($result,$count,$changeSubmissionError) = Execute_SQL($query);
                    if ($changeSubmissionError) {
                        break;
                    }
                    if ($result->num_rows !== 1) {
                        $changeSubmissionError = 'Database error - duplicate User ID.';
                        break;
                    }
                    $record = $result->fetch_assoc();
                
                #	Check the administrator's password:
                
                    $correctPasswordHash = $record['password']; 
                    if (! verify_hashed_password($userID,$yourPassword,$correctPasswordHash)) {
                        $changeSubmissionError = 'Password is not correct. Please re-enter and try again.';
                        Write_Log_Message ('security','Invalid administrator password for account delete.');
                        break;
                    }
                    session_start(); 
                    $_SESSION['password_attempts'] = 0;							// reset counter for number of attempts
                    session_write_close();
                        
                # 	Delete the user account from the database:
    
                    $query = "DELETE FROM appusers WHERE ID = '" . Escape_SQL($accountID) . "'";
                    list($result,$count,$changeSubmissionError) = Execute_SQL($query);
                    if ($changeSubmissionError) {
                        break;
                    }
                    $changeSubmissionError = 'Success! account has been deleted.';
                    Write_Log_Message ('security',"UserID $userIDInput has been deleted.");
    
                /* delete one time passwords (but don't stress over any errors) */
    
                    $query = "DELETE FROM `authtokens` WHERE `authtokens`.`userID` = '" . $userIDInput . "'"; 
                    list($result,$count,$error) = Execute_SQL($query); 

                } while (FALSE);	// ---------- end of inline "function" -----------
	
            /* Exit if there have been too many failed password attempts: */

                if ($changeSubmissionError == 'Password is not correct. Please re-enter and try again.') {
                    session_start(); 
                    if (empty($_SESSION['password_attempts'])) {	
                        $_SESSION['password_attempts'] = 0;							// initialize counter for number of attempts
                    }
                    $_SESSION['password_attempts'] = $_SESSION['password_attempts'] + 1;
                    if ($_SESSION['password_attempts'] == ($AllowedAttempts - 1)) {	// last chance?
                        $changeSubmissionError = 'Last chance: ' . $changeSubmissionError;							// prefix for potential error message
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
    }
?> 
<!DOCTYPE html>
<html>
<head>
	<title>Home Accounts</title>
	<?php require('../include/app_standard_head.html'); ?>	
</head>

<body>
<?php include('../include/header_dropdown_menu.php'); ?>
<?php

#-----------------------------------------------------------------------------
#	Get the current account information for javascript (excluding passwords):
#-----------------------------------------------------------------------------

$accountsTableError = '';				# feedback to display on the page
$accountsTableArray = array();			# results of database query

if (strpos($userAttributes,'/accounts/') === FALSE) { 
	$accountsTableError = "This function is not available without the /accounts/ user attribute.";
} else {

	Do {
		
		if ($static_config['DB']['useDatabase']) {

	#	Do query:

			$query = "SELECT ID,userID,houseID,userName,attributes FROM appusers;";
			list($result,$count,$error) = Execute_SQL($query);
			if ($error) {
				$accountsTableError = $error;
				break;
			}
			if ($count) {
				$accountsTableArray = mysqli_fetch_all($result,MYSQLI_ASSOC);	
				usort($accountsTableArray, function($a, $b) {
					return strcmp($a['userID'], $b['userID']);
				});
#				print_r($accountsTableArray);		
			} else {
				$accountsTableError = 'Database query failed to return results.';
				break;
			}
				
		} else {
			$accountsTableError = 'Database is not available.';
		}
	} while (FALSE);
}

#-----------------------------------------------------------------------------
#	Global javascript variables for this page:
#-----------------------------------------------------------------------------
?>
<script>
	var accountsTable = <?php echo json_encode($accountsTableArray); ?>;
</script>
<?php 
#-----------------------------------------------------------------------------
#	Message box for SQL errors from the PHP code:
#-----------------------------------------------------------------------------
	if ($accountsTableError) { 
		echo '<div id="accountsTableMessage" class="info closebox" style="color:#f44336">';
		echo '<button type="button" class="close" onClick="document.getElementById(\'accountsTableMessage\').style.display=\'none\';"><span>&times;</span></button>';
		echo $accountsTableError.'</div>';
	} 
?>
<?php include('../include/startup_spinner.html'); ?> 

<div class="info center" style="width: 90%">

<div id="AccountsDisplay" class="container" style="position:relative;"></div>

<form class="content" action="" method="post">
    <div id="ChangeFormDiv" class="logon-content" style="display:none; position:absolute; top:8px; right:12px; ">
	    <div class="container">
			<input type="hidden" name="accountID">
			<p>Make changes as necessary:</p>
			<label><b>User ID</b></label>
			<input type="text" placeholder="Enter User ID" name="userIDInput" onInput="ValidateChangeFormUpdate();">
			<label><b>User Name</b></label>
			<input type="text" placeholder="Enter User Name" name="userNameInput" onInput="ValidateChangeFormUpdate();">
			<label><b>House ID</b></label>
			<input type="text" placeholder="Enter House ID" name="houseIDInput" onInput="ValidateChangeFormUpdate();">
			<label><b>Attributes</b></label>
			<input type="text" placeholder="Enter Attributes" name="attributesInput" onInput="ValidateChangeFormUpdate();">
			<label><b>New Password</b></label>
			<input type="password" placeholder="Enter New Password (if change is necessary)" id="newpassword" name="newpassword" onInput="ValidateChangeFormUpdate();">
<?php include('../include/newpassword_meter.php'); ?>
			<input type="password" placeholder="Confirm New Password" name="confirmpassword" onInput="ValidateChangeFormUpdate();">
		</div>
	    <div class="container">
			<label>Enter your password to validate the change:</label>
			<input type="password" placeholder="Enter Your Password" name="yourPassword" onInput="ValidateChangeFormUpdate();">
		</div>
	    <div class="container">
			<div id="ChangeFormError" class="center-text" style="color:var(--color-red);"><b><?php echo $changeSubmissionError; ?></b></div>
			<button id="ChangeSubmitButton" class="green" style="display: none;" name="submitButton" type="submit" value="Change Account" onClick="return ChangeFormSubmitted();"><b>Change User Account</b></button>
			<button id="DeleteSubmitButton" class="yellow" style="display: none;" name="submitButton" type="submit" value="Delete Account" onClick="return DeleteFormSubmitted();"><b>Delete User Account</b></button>
		</div>
    </div>        
</form>
</div>
<script>
function DisplayAccounts(accountsTable) {
	var tableHTML = '<table class="border-td center-text" style="width: 90%">';
	tableHTML += '<tr><th>User ID</th><th>User Name</th><th>House ID</th><th>Attributes</th></tr>';
	for (i = 0; i < accountsTable.length; i++) {
        tableHTML += '<tr id="UserAccount-' + accountsTable[i]['ID'] + '" name="UserAccountLine" onClick="SelectAccountForChangeForm(' + accountsTable[i]['ID']+ ');">';
		tableHTML +=    '<td>'+accountsTable[i]['userID']+'</td>';
		tableHTML +=    '<td>'+accountsTable[i]['userName']+'</td>';
		tableHTML +=    '<td>'+accountsTable[i]['houseID']+'</td>';
		tableHTML +=    '<td>'+accountsTable[i]['attributes']+'</td>';
		tableHTML += '</tr>';		
	}
    tableHTML += '<tr><td>';
    tableHTML +=    '<button class="green" type="button" onClick="SelectAccountForChangeForm(-1);"><b>New User</b></button>';
    tableHTML += '</td></tr>';

	tableHTML += '</table>';
	document.getElementById('AccountsDisplay').innerHTML = tableHTML;
}

DisplayAccounts(accountsTable);

<?php 
	if (($changeSubmissionError) && ( ($whichSubmitButton == 'Change Account') || (substr($changeSubmissionError,0,7) != 'Success') ) )  {
?>        
    /* display the update form for an account which just had an update submitted */
    
        document.getElementsByName('yourPassword')[0].value = '';
    
        document.getElementsByName('accountID')[0].value = '<?php echo $accountID; ?>';
        document.getElementsByName('userIDInput')[0].value = '<?php echo $userIDInput; ?>';
        document.getElementsByName('userNameInput')[0].value = '<?php echo $userNameInput; ?>';
        document.getElementsByName('houseIDInput')[0].value = '<?php echo $houseIDInput; ?>';
        document.getElementsByName('attributesInput')[0].value = '<?php echo $attributesInput; ?>';
        document.getElementsByName('newpassword')[0].value = '<?php echo $newpassword; ?>';
        document.getElementsByName('confirmpassword')[0].value = '<?php echo $newpassword; ?>';
    
        if (typeof newpasswordMeterCheck == 'function') { 
            newpasswordMeterCheck();
        }
        document.getElementById('ChangeSubmitButton').style.display = 'none';  
        document.getElementById('DeleteSubmitButton').style.display = 'block';  
        document.getElementById('ChangeFormDiv').style.display = 'block';
<?php
        if (substr($changeSubmissionError,0,7) != 'Success') {
            echo "document.getElementsByName('yourPassword')[0].style.border='#FF0000 1px solid';";
        }
	}
?>

function SelectAccountForChangeForm(accountID) {
    var UserAccountLines = document.getElementsByName('UserAccountLine')
	for (i = 0; i < UserAccountLines.length; i++) {
        UserAccountLines[i].className = '';
    }

    document.getElementsByName('yourPassword')[0].value = '';
    document.getElementsByName('accountID')[0].value = accountID;
    
    if (accountID == -1) {          /* new account? */
    
        document.getElementsByName('userIDInput')[0].value = '';
        document.getElementsByName('userNameInput')[0].value = '';
        document.getElementsByName('houseIDInput')[0].value = '<?php echo $houseID; ?>';
        document.getElementsByName('attributesInput')[0].value = '/active/';
        document.getElementsByName('newpassword')[0].value = '';
        document.getElementsByName('confirmpassword')[0].value = '';

        document.getElementById('DeleteSubmitButton').style.display = 'none';  

    } else {

        document.getElementById('UserAccount-' + accountID).className = 'selected';
    
        for (i = 0; i < UserAccountLines.length; i++) {
            if (accountsTable[i]['ID'] == accountID) {
                break;
            }
        }

        document.getElementsByName('userIDInput')[0].value = accountsTable[i]['userID'];
        document.getElementsByName('userNameInput')[0].value = accountsTable[i]['userName'];
        document.getElementsByName('houseIDInput')[0].value = accountsTable[i]['houseID'];
        document.getElementsByName('attributesInput')[0].value = accountsTable[i]['attributes'];
        document.getElementsByName('newpassword')[0].value = '';
        document.getElementsByName('confirmpassword')[0].value = '';

        document.getElementById('DeleteSubmitButton').style.display = 'block';  
    }
    
    if (typeof newpasswordMeterCheck == 'function') { 
        newpasswordMeterCheck();
    }
    document.getElementById('ChangeSubmitButton').style.display = 'none';  
    document.getElementById('ChangeFormDiv').style.display = 'block';
}

function ValidateChangeFormUpdate() {
	document.getElementById('ChangeFormError').innerHTML = ''; 					/* remove previous message */

    var accountID = document.getElementsByName('accountID')[0].value;
    var userIDInput = document.getElementsByName('userIDInput')[0];
    var userNameInput = document.getElementsByName('userNameInput')[0];
    var houseIDInput = document.getElementsByName('houseIDInput')[0];
    var attributesInput = document.getElementsByName('attributesInput')[0];
	var newpassword = document.getElementsByName('newpassword')[0];
	var confirmpassword = document.getElementsByName('confirmpassword')[0];
	var yourPassword = document.getElementsByName('yourPassword')[0];

    userIDInput.style.border = '';
    userNameInput.style.border = '';
    houseIDInput.style.border = '';
    attributesInput.style.border = '';
	newpassword.style.border = '';
    confirmpassword.style.border = '';
    yourPassword.style.border = '';

	for (var i = 0; i < accountsTable.length; i++) {
        if ((accountsTable[i]['ID'] != accountID) && (accountsTable[i]['userID'] == userIDInput.value)) {
            userIDInput.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'User ID is already in use.';
            return false;
        }
    }
	
	if (newpassword.value.length > 0) {
        newpasswordMeterCheck();
        if (newpasswordMeterCheckResult < 4) {
            newpassword.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'Please choose a better password.';
            return false;
        }
	}
	
	if (newpassword.value != confirmpassword.value) {
		newpassword.style.border = '#FF0000 1px solid';
		confirmpassword.style.border = '#FF0000 1px solid';
		document.getElementById('ChangeFormError').innerHTML = 'New password and confirmation do not match.';
		return false;
	}

    if (accountID == -1) {          /* new account? */
        if (userIDInput.value.length == 0) {
            userIDInput.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'Enter required items.';
        }
        if (userNameInput.value.length == 0) {
            userNameInput.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'Enter required items.';
        }
        if (houseIDInput.value.length == 0) {
            houseIDInput.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'Enter required items.';
        }
        if (attributesInput.value.length == 0) {
            attributesInput.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'Enter required items.';
        }
        if (newpassword.value.length == 0) {
            newpassword.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'Enter required items.';
        } 
        if  (yourPassword.value.length == 0) {
            yourPassword.style.border = '#FF0000 1px solid';
            document.getElementById('ChangeFormError').innerHTML = 'Enter required items.';
        }
        if (document.getElementById('ChangeFormError').innerHTML !== '') {
            return false;
        }
    }
    
	if (! yourPassword.value) {
        yourPassword.style.border = '#FF0000 1px solid';
        document.getElementById('ChangeFormError').innerHTML = 'Your Password is required.';
        return false;
	}
     
	document.getElementById('ChangeSubmitButton').style.display = 'block';   	/* show the submit button */
    return true;
}

function ChangeFormSubmitted() {
    if (ValidateChangeFormUpdate() == false) {
        return false;
    }
	
    document.getElementById('ChangeSubmitButton').style.display = 'none';   	/* hide the submit button */
	$('.pre-loader').show();
	return true;
}
<?php include('../include/startup_spinner_done.js'); ?> 
</script>
</body>
</html>
