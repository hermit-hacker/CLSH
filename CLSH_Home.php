<?php
/////////////
//
// Cryptolingus Scavenger Hunt (CLSH) version 1.1
//
// Modified: 2014-12-08
// Unit: Home
// File: CLSH_Home.php
//
// Description: The primary user interface
//
////////////


// Pull in the common functions
include 'CLSH_Common.php';



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: needToRegister
// Inputs: Array $postData (The sanitized data from $postValues)
//         Array $cookieData (The sanitized data from $_COOKIE)
// Returns: Whether or not registration is required (TRUE = Needs to register, FALSE = No registration required)
// Description: Inspects posted data to determine if registration screen should be displayed (i.e. no session)
function needToRegister($postData, $cookieData) {
	if (!is_null($postData['clsha'])) {
		if ($postData['clsha'] == 'register') {
			return FALSE;
		} elseif ($postData['clsha'] == 'login' ) {
			return FALSE;
		} elseif (is_null($cookieData['SESSIONID'])) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		if (is_null($cookieData['SESSIONID'])) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
};
//
// END needToRegister
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: printRegistrationScreen
// Inputs: NONE
// Returns: NONE
// Description: Prints the registration screen
function printRegistrationScreen() {
	echo "<center>\n";
	echo "<table width='50%'><tr><td>\n";
	echo "<u>Register</u>\n";
	echo "<form name=\"register\" action=\"CLSH_Home.php\" method=\"post\">\n";
	echo "Name: <input type=\"text\" name=\"username\"><br>\n";
	echo "<input type=\"hidden\" name=\"clsha\" value=\"register\">";
	echo "Password: <input type=\"password\" name=\"userpass\"><br>\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<input type=\"submit\" value=\"Register\">\n";
	echo "</form>\n";
	echo "</td><td>\n";
	echo "<u>Login</u>\n";
	echo "<form name=\"login\" action=\"CLSH_Home.php\" method=\"post\">\n";
	echo "Name: <input type=\"text\" name=\"username\"><br>\n";
	echo "<input type=\"hidden\" name=\"clsha\" value=\"login\">";
	echo "Password: <input type=\"password\" name=\"userpass\"><br>\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<input type=\"submit\" value=\"Login\">\n";
	echo "</form>\n";
	echo "</td></tr></table>\n";
	echo "</center>\n";
};
//
// END printRegistrationScreen
///////////////////////////////////////////////////////////////////////////////////////////



// Convert the $postValues content to an array we can sanitize and use properly
$postValues = $_POST;
$postValues['username'] = filter_var(trim($postValues['username']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH);
$postValues['userpass'] = filter_var(trim($postValues['userpass']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH);
$postValues['clsha'] = filter_var(trim($postValues['clsha']), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH);
// Same for the $_COOKIE data
$cookieData = $_COOKIE;



// Build the CLSHConfiguration file
$shConfig = new CLSHConfiguration("default.ini");
$username = "";
$userpoints = 0;



// Clean the session data
cleanSessionDatabase($shConfig);



// Check for registration and handle the case
$showRegister = needToRegister($postValues);
if (!$showRegister) {
	if ($postValues['clsha'] == 'register') {
		$session = addCLSHUser($shConfig, $postValues);
		$username = $postValues['username'];
		if ($session == "ERR_USER_EXISTS") {
			showHTMLError($shConfig, "User already exists", "Please try a different name or login.");
			exit;
		}
	} elseif ($postValues['clsha'] == 'login') {
		$loginCheck = loginUser($shConfig, $postValues);
		if ($loginCheck == "ERR_BAD_CREDS") {
			showHTMLError($shConfig, "Invalid credentials", "Please try again.");
			exit;
		} else {
			buildUserSession($shConfig, $postValues['username']);
			$username = $postValues['username'];
			$userpoints = getPointsForUser($shConfig, $username);
		}
	} elseif ($postValues['clsha'] == 'logout' ) {
		setcookie("SESSIONID", "", time() - 31337);
		logoutUser($shConfig, getUserFromSession($shConfig, $session));
		$showRegister = TRUE;
	} elseif ($postValues['clsha'] == "postanswers" ) {
		$session = $cookieData['SESSIONID'];
		$username = getUserFromSession($shConfig, $session);
		if ($username == "ERR_NO_SESSION") {
			setcookie("SESSIONID", "", time() - 31337);
			showHTMLError($shConfig, "Login expired", "Please login again to continue.");
			exit;
		} else {
			$postValues['username'] = $username;
		}
		checkUserAnswers($shConfig, $postValues);
		$userpoints = getPointsForUser($shConfig, $username);
		updateScoreboard($shConfig, $username, $userpoints);
	} else {
		$session = $cookieData['SESSIONID'];
		$username = getUserFromSession($shConfig, $session);
		if ($username == "ERR_NO_SESSION") {
			setcookie("SESSIONID", "", time() - 31337);
			showHTMLError($shConfig, "Login expired", "Please login again to continue.");
			exit;
		}
		$userpoints = getPointsForUser($shConfig, $username);
	}
}



// Show the header
printHTMLHeader($shConfig, $username, $userpoints);



// Check cookie, if not current user then force registration
if ($showRegister) {
	printRegistrationScreen();
} else {
	printQuestions($shConfig, $postValues, $username);
}



// Show the footer
printHTMLFooter($shConfig);

?>