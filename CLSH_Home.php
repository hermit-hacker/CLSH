<?php
/////////////
//
// Cryptolingus Scavenger Hunt (CLSH) version 1.0
//
// Modified: 2014-11-08
// Unit: Home
// File: CLSH_Home.php
//
// Description: The primary user interface
//
////////////

include 'CLSH_Common.php';


function needToRegister() {
	if (!is_null($_POST['clsha'])) {
		if ($_POST['clsha'] == 'register') {
			return FALSE;
		} elseif ($_POST['clsha'] == 'login' ) {
			return FALSE;
		} elseif (is_null($_COOKIE['SESSIONID'])) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		if (is_null($_COOKIE['SESSIONID'])) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
};


function printRegistrationScreen() {
	echo "<center>\n";
	echo "<table width='50%'><tr><td>\n";
	echo "<u>Register</u>\n";
	echo "<form name=\"register\" action=\"CLSH_Home.php\" method=\"post\">\n";
	echo "Name: <input type=\"text\" name=\"teamname\"><br>\n";
	echo "<input type=\"hidden\" name=\"clsha\" value=\"register\">";
	echo "Password: <input type=\"password\" name=\"teampass\"><br>\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<input type=\"submit\" value=\"Register\">\n";
	echo "</form>\n";
	echo "</td><td>\n";
	echo "<u>Login</u>\n";
	echo "<form name=\"login\" action=\"CLSH_Home.php\" method=\"post\">\n";
	echo "Name: <input type=\"text\" name=\"teamname\"><br>\n";
	echo "<input type=\"hidden\" name=\"clsha\" value=\"login\">";
	echo "Password: <input type=\"password\" name=\"teampass\"><br>\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<input type=\"submit\" value=\"Login\">\n";
	echo "</form>\n";
	echo "</td></tr></table>\n";
	echo "</center>\n";
};

// Build the CLSHConfiguration file
$shConfig = new CLSHConfiguration("default.ini");
$teamname = "";
$teampoints = 0;

// Clean the session data
cleanSessionDatabase($shConfig);

// Check for registration
$showRegister = needToRegister();
if (!$showRegister) {
	if ($_POST['clsha'] == 'register') {
		$session = addCLSHUser($shConfig, $_POST);
		$teamname = $_POST['teamname'];
		if ($session == "ERR_USER_EXISTS") {
			showHTMLError($shConfig, "User already exists", "Please try a different name or login.");
			exit;
		}
	} elseif ($_POST['clsha'] == 'login') {
		$loginCheck = loginUser($shConfig, $_POST);
		if ($loginCheck == "ERR_BAD_CREDS") {
			showHTMLError($shConfig, "Invalid credentials", "Please try again.");
			exit;
		} else {
			buildUserSession($shConfig, $_POST['teamname']);
			$teamname = $_POST['teamname'];
			$teampoints = getPointsForUser($shConfig, $teamname);
		}
	} elseif ($_POST['clsha'] == 'logout' ) {
		setcookie("SESSIONID", "", time() - 31337);
		logoutUser($shConfig, getUserFromSession($shConfig, $session));
		$showRegister = TRUE;
	} elseif ($_POST['clsha'] == "postanswers" ) {
		$session = $_COOKIE['SESSIONID'];
		$teamname = getUserFromSession($shConfig, $session);
		if ($teamname == "ERR_NO_SESSION") {
			setcookie("SESSIONID", "", time() - 31337);
			showHTMLError($shConfig, "Login expired", "Please login again to continue.");
			exit;
		} else {
			$_POST['teamname'] = $teamname;
		}
		checkUserAnswers($shConfig, $_POST);
		$teampoints = getPointsForUser($shConfig, $teamname);
		updateScoreboard($shConfig, $teamname, $teampoints);
	} else {
		$session = $_COOKIE['SESSIONID'];
		$teamname = getUserFromSession($shConfig, $session);
		if ($teamname == "ERR_NO_SESSION") {
			setcookie("SESSIONID", "", time() - 31337);
			showHTMLError($shConfig, "Login expired", "Please login again to continue.");
			exit;
		}
		$teampoints = getPointsForUser($shConfig, $teamname);
	}
}

// Show the header
printHTMLHeader($shConfig, $teamname, $teampoints);

// Check cookie, if not current user then force registration
if ($showRegister) {
	printRegistrationScreen();
} else {
	printQuestions($shConfig, $_POST, $teamname);
}

// Show the footer
printHTMLFooter($shConfig);

?>