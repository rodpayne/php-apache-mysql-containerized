<?php
# ------------------------------------------------------------
#	Check session variables to make sure user is logged on.
#	Place at the top of every page (except the login page).
#
#	If not logged on, redirect to the login page.
#
#	If logged on, provide particular global variables:
#		$userName - user's display name
#       $userAttributes - user's attributes, like permissions
#		$houseID - ID for the particular house web site
#		$loginTime - time() when the login was done
# ------------------------------------------------------------

session_start(); 
if (empty($_SESSION['login_userName'])) {
	if (empty($_SESSION['login_return'])) {	
		$_SESSION['login_return'] = $_SERVER['REQUEST_URI'];
	}
	header('location: /account/login.php');
	echo '<html><head><meta http-equiv="refresh" content="0,URL=/account/login.php"></head><body>Redirecting to login page...</body></html>'; 
	exit();
} else {
	$userName=$_SESSION['login_userName'];
	$userAttributes=$_SESSION['login_userAttributes'];
	$houseID=$_SESSION['login_houseID'];
	$loginTime=$_SESSION['login_time'];
}
session_write_close();

