<?php
/////////////
//
// Cryptolingus Scavenger Hunt (CLSH) version 1.1
//
// Modified: 2014-12-09
// Unit: Common
// File: CLSH_Common.php
//
// Description: Supporting functions for CLSH
//
////////////

///////////////////////////////////////////////////////////////////////////////////////////
// Function name: CLASS_DEF CLSHConfiguration
// Inputs:  String $dbType
// Returns: null
// Description: Builds a CLSHConfiguraiton object
class CLSHConfiguration
{
    // Sections is list of sections,  settings is just settings, and filename is the location of the config files
	private $sections = array();
	private $settings = array();
	private $filename = "default.ini";

    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->{default constructor}
    // Inputs:  String $configFile
    // Returns: null
    // Description: Builds a new CLSHConfiguration object by reading in a configuration file
	function __construct($configFile) {
		$this->filename = $configFile;
		$this->readConfigFile($this->filename);
	}
    //
    // END CLSHConfiguration->{default constructor}
    ///////////////////////////////////////////////////////////////////////////////////////////
    
	
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->getSections
    // Inputs:  null
    // Returns: Array containing all section names
    // Description: Get an array of all sections
	public function getSections() {
		return $this->sections;
	}
    //
    // END CLSHConfiguration->getSections
    ///////////////////////////////////////////////////////////////////////////////////////////
     

        
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->getSetting
    // Inputs:  String $cfgsection (the section to retrieve a setting from)
    //          String $cfgsetting (the actual setting key to retrieve)
    // Returns: String containing the specified value for the requested section/key
    // Description: Get the setting for a specified key in a specified section
	public function getSetting($cfgsection, $cfgsetting) {
		return $this->settings[$cfgsection][$cfgsetting];
	}
    //
    // END CLSHConfiguration->getSetting
    ///////////////////////////////////////////////////////////////////////////////////////////
    
	
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->readConfigFile
    // Inputs:  String $theFile (file to be read in)
    // Returns: null
    // Description: Takes a specified PHP INI format file and reads it into a CLSHConfiguration object, enumerating sections as it goes
	private function readConfigFile($theFile) {
		$this->settings = parse_ini_file($theFile, TRUE) or die("Could not open config file: " . $theFile . "\n");
		foreach ($this->settings as $section => $setting) {
			$this->sections[] = $section;
		}
	}
    //
    // END CLSHConfiguration->readConfigFile
    ///////////////////////////////////////////////////////////////////////////////////////////

    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->setValue
    // Inputs:  String $cfgsection    (the section to set the key/value in)
    //          String $cfgsetting    (the setting key to be set)
    //          String $settingValue  (the value to be set for the specified key)
    // Returns: null
    // Description: Sets a specified key in a specified section to a specified value
	public function setValue($cfgsection, $cfgsetting, $settingValue) {
		$this->settings[$cfgsection][$cfgsetting] = $settingValue;
	}
    //
    // END CLSHConfiguration->setValue
    ///////////////////////////////////////////////////////////////////////////////////////////

    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->showAllSettings
    // Inputs:  null
    // Returns: null
    // Description: Prints all settings to the console
    public function showAllSettings() {
		foreach ($this->settings as $section => $setting) {
			echo $section . "\n";
			echo "-----------------------\n";
			foreach ($setting as $key => $value ) {
				echo "   " . $key . " : " . $value . "\n";
			}
		}
        userAck();
	}
    //
    // END CLSHConfiguration->showAllSettings
    ///////////////////////////////////////////////////////////////////////////////////////////
    
    
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->showSections
    // Inputs:  null
    // Returns: null
    // Description: Prints a list of all sections to the console
	public function showSections() {
		echo "Listing sections:\n";
		echo "-----------------\n";
		foreach ($this->sections as $section) {
			echo $section . "\n";
		}
	}
    //
    // END CLSHConfiguration->showSections
    ///////////////////////////////////////////////////////////////////////////////////////////

	
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function name: CLSHConfiguration->writeConfigurationFile
    // Inputs:  String $theFile (Specifies the configuration file to be written)
    // Returns: null
    // Description: Writes the CLSHConfiguration object out to a file
	public function writeConfigFile($theFile) {
		$fh = fopen($theFile, 'w');
		$newData = "";
		foreach ($this->settings as $section => $setting) {
			$newData .= "[" . $section . "]\n";
			foreach ($setting as $key => $value) {
				$newData .= $key . " = " . $value . "\n";
			}
		}
		fwrite($fh, $newData);
		fclose($fh);
	}
    //
    // END CLSHConfiguration->writeConfigFile
    ///////////////////////////////////////////////////////////////////////////////////////////
}
//
// END CLASS_DEF CLSHConfiguration
///////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
//                                                                                       //
//                                      Generic Functions                                //
//                                                                                       //
///////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
// Function name: addCLSHUser
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         Array $postValues  (Sanitized results of $_POST)
// Returns: A string that contains a user session or an error code
// Description: Attempts to add a new user account after checking for valid formats
function addCLSHUser($clshConfig, $postValues) {
	if ((strncmp($postValues['username'], "ERR_", 4) == 0)) {
		return "ERR_USER_EXISTS";	
	}
	if ( userExists($clshConfig->getSetting("General", "UserDatabase"), $postValues['username'])) {
		return "ERR_USER_EXISTS";
	}
	$userEntry = $postValues['username'] . ":" . password_hash($postValues['userpass'], PASSWORD_DEFAULT) . "\n";
	appendToFile($clshConfig->getSetting("General", "UserDatabase"), $userEntry);
	$successFile = $clshConfig->getSetting("General", "SubmissionsDirectory") . "/" . $postValues['username'] . ".db";
	$failureFile = $clshConfig->getSetting("General", "SubmissionsDirectory") . "/" . $postValues['username'] . "_wrong.db";
	writeFile($successFile, "");
	writeFile($failureFile, "");
	updateScoreboard($clshConfig, $postValues['username'], 0);
	return buildUserSession($clshConfig, $postValues['username']);
};
//
// END addCLSHUser
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: appendToFile
// Inputs: String $theFile  (The file to be written to)
//         String $theData  (The data to write to the end of the file)
// Returns: null
// Description: Appends data to a specified file
function appendToFile($theFile, $theData) {
	file_put_contents($theFile, $theData, FILE_APPEND | LOCK_EX );
};
//
// END appendToFile
///////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
// Function name: buildUserSession
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         String $userName  (The username for which a session will be built)
// Returns: A string that contains a user session or an error code
// Description: Creates a user session and returns the session identifier
function buildUserSession($clshConfig, $userName) {
	$session = getPassword(80);
	$now = new DateTime('now', new DateTimeZone("UTC") );
	$goodcookie = setcookie("SESSIONID", $session, time() + $clshConfig->getSetting("General", "Timeout"));
	$sessionEntry = $session . ":" . $userName . ":" . $now->format('U') . "\n";
	appendToFile($clshConfig->getSetting("General", "SessionDatabase"), $sessionEntry);
	return $session;
}
//
// END buildUserSession
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: checkUserAnswers
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         Array $postValues  (The sanitized $_POST data)
// Returns: NONE
// Description: Grades user answers and assigns points
function checkUserAnswers($clshConfig, $postValues) {
	$subFile = $clshConfig->getSetting("General", "SubmissionsDirectory") . "/" . $postValues['username'] . ".db";
	$wrongFile = $clshConfig->getSetting("General", "SubmissionsDirectory") . "/" . $postValues['username'] . "_wrong.db";
	$clshSections = $clshConfig->getSections();
	$subOut = "";
	$wrongOut = "";
	foreach ($clshSections as $section) {
		if ($section != "General") {
			$question = 1;
			while (!is_null($clshConfig->getSetting($section, "Q" . $question))) {
				$formName = $section . "_Q" . $question;
				if (!userAlreadyAnswered($clshConfig, $postValues['username'], $section, $question)) {
					if ( trim($postValues[$formName]) !== "" ) {
						$answer = $clshConfig->getSetting($section, "A" . $question);
						$subEntry = $formName . ":" . $postValues[$formName] . ":";
						if ($postValues[$formName] == $answer) {
							$subOut .= $subEntry . $clshConfig->getSetting($section, "P" . $question) . "\n";
						} else {
							$wrongOut .= $subEntry . "0\n";
						}
					}	
				}
				$question += 1;
			}
		}
	}
	appendToFile($subFile, $subOut);
	appendToFile($wrongFile, $wrongOut);
}
//
// END checkUserAnswers
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: cleanSessionDatabase
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
// Returns: NONE
// Description: Clears out a session from the list of active sessions following a user logout
function cleanSessionDatabase($clshConfig) {
	$allSessionData = file($clshConfig->getSetting("General", "SessionDatabase"));
	$newSessionData = "";
	$dtz = new DateTimeZone("UTC");
	foreach ($allSessionData as $line) {
		$sessionDate = intval(trim(substr(strstr(substr(strstr($line, ":"), 1), ":"), 1))) + intval($clshConfig->getSetting("General", "Timeout"));
		$now = new DateTime('now', $dtz);
		$expiration = date_create_from_format('U', $sessionDate, $dtz);
		if ($now < $expiration) {
			$newSessionData .= $line . "\n";
		}
	}
	writeFile($clshConfig->getSetting("General", "SessionDatabase"), $newSessionData);
}
//
// END cleanSessionDatabase
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getPassword
// Inputs: Int $length (Optional variable, defaults to 15, length of the password to get)
// Returns: A random password of the specified length (default 15)
// Description: Generates a random password by iteratively taking the MD5 of a random value
function getPassword($length=15) {
	$quickSet = "";
	while (strlen($quickSet) < $length) {
		$quickSet .= md5(rand());
	}
	$quickSet = substr($quickSet, 0, $length);
	return $quickSet;
}
//
// END getPassword
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getPointsForUser
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         String $userName  (The sanitized $_POST data)
// Returns: The total points currently held by the specified user
// Description: Takes a username and looks up how many points they have gained
function getPointsForUser($clshConfig, $userName) {
	$subFile = $clshConfig->getSetting("General", "SubmissionsDirectory") . "/" . $userName . ".db";
	$pointRecords = file($subFile);
	$totalPoints = 0;
	foreach ($pointRecords as $line) {
		$pointCheck = intval(substr(strstr(substr(strstr($line, ":"), 1), ":"), 1));
		$totalPoints += $pointCheck;
	}
	return $totalPoints;
}
//
// END getPointsForUser
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: getUserFromSession
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         String $sessionID  (The session information passed by the user)
// Returns: Either the usernae or an error string
// Description: Looks up a session to determine the matching username
function getUserFromSession($clshConfig, $sessionID) {
	$allSessionData = file($clshConfig->getSetting("General", "SessionDatabase"));
	$pattern = "/^" . $sessionID . "/";
	foreach ($allSessionData as $line) {
		if (preg_match($pattern, $line)) {
			$userName = strstr(substr(strstr($line, ":"), 1), ":", TRUE);
			return $userName;
		}
	}
	return "ERR_NO_SESSION";
}
//
// END getUserFromSession
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: loginUser
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         Array $postValues  (The sanitized $_POST data)
// Returns: A string describing the status of the request
// Description: Attempts to log a user into CLSH
function loginUser($clshConfig, $postValues) {
	if ((strncmp($postValues['username'], "ERR_", 4) == 0)) {
		return "ERR_BAD_CREDS";	
	}
	if ( ! userExists($clshConfig->getSetting("General", "UserDatabase"), $postValues['username'])) {
		return "ERR_BAD_CREDS";
	}
	$userCreds = file($clshConfig->getSetting("General", "UserDatabase"));
	$pattern = "/^" . $postValues['username'] . ":.*/";
	foreach ($userCreds as $line) {
		if (preg_match($pattern, $line)) {
			$userHash = trim(substr(strstr($line, ':' ), 1));
		}
	}
	if (password_verify($postValues['userpass'], $userHash)) {
		return "GOOD_PASSWORD";
	} else {
		return "ERR_BAD_CREDS";
	}
}
//
// END loginUser
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: logoutUser
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         String $userName  (The username to logout)
// Returns: NONE
// Description: Logs a user out of CLSH
function logoutUser($clshConfig, $userName) {
	$sessionsFile = $clshConfig->getSetting("General", "SessionDatabase");
	$allSessionData = file($sessionsFile);
	$pattern = "/^.*:" . $userName . ":.*/";
	$newSessions = "";
	foreach ($allSessionData as $line) {
		if (preg_match($pattern, $line) == FALSE) {
			$newSessions .= $line;
		}
	}
	writeFile($sessionsFile, $newSessions);
}
//
// END logoutUser
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: printHTMLFooter
// Inputs: $clshConfig	A CLSHConfiguration object used to customize the page display
// Returns: null
// Description: Prints a standard table encapsulating footer
function printHTMLFooter($clshConfig) {
	echo "</td></tr>\n";
	echo "<tr><td width=\"100%\"><center>";
	echo $clshConfig->getSetting("General", "SHFooter");
	echo "</center></td></tr></table>\n";
	echo "</body>\n";
	echo "</html>\n";
}
//
// END printHTMLFooter
///////////////////////////////////////////////////////////////////////////////////////////

    
    
///////////////////////////////////////////////////////////////////////////////////////////
// Function name: printHTMLHeader
// Inputs: $clshConfig	A CLSHConfiguration object used to customize the page display
// Returns: null
// Description: Prints a standard table encapsulating header
function printHTMLHeader($clshConfig, $username = "", $userpoints = 0) {
	echo "<html>\n";
	echo "<head><title>CLCS - ";
	echo $clshConfig->getSetting("General", "SHName");
	echo "</title></head>\n";
	echo "<style type=\"text/css\">table.bottomBorder { border-collapse:collapse; }\n";
	echo "table.bottomBorder td, table.bottomBorder th { border-bottom:1px dotted black;padding:5px; }\n";
	echo "</style>\n";
	echo "<body bgcolor=\"#000\" text=\"#fff\" link=\"#00f\" alink=\"#00f\" vlink=\"#00f\">\n";
	echo "<table width=\"100%\" height=\"100%\">\n";
	echo "<tr><td width=\"100%\"><center><font face=\"Lucida Console, Monaco, monospace\" size=\"+4\"><u>";
	echo $clshConfig->getSetting("General","SHHeader");
	echo "</u></font></center></td></tr>\n";
	if ($username !== "" ) {
		echo "<tr><td width=\"100%\" align=\"right\">Logged in as: " . $username;
		echo "<form name=\"CLSHLogout\" action=\"CLSH_Home.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"clsha\" value=\"logout\">";
		echo "<input type=\"submit\" value=\"Logout\">\n";
		echo "</form>\n";
		echo "Points: " . $userpoints . "</td></tr>\n";	
	}
	echo "<tr with=\"100%\" height=\"100%\"><td>\n";
}
//
// END printHTMLHeader
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: printHTMLSBHeader
// Inputs: $clshConfig	A CLSHConfiguration object used to customize the page display
// Returns: null
// Description: Prints a standard table encapsulating header
function printHTMLSBHeader($clshConfig) {
	echo "<html>\n";
	echo "<head>";
	echo "<meta http-equiv=\"refresh\" content=\"30\">";
	echo "<title>CLCS - " . $clshConfig->getSetting("General", "SHName") . "Scoreboard";
	echo "</title></head>\n";
	echo "<body bgcolor=\"#000\" text=\"#fff\" link=\"#00f\" alink=\"#00f\" vlink=\"#00f\">\n";
	echo "<table width=\"100%\" height=\"100%\">\n";
	echo "<tr><td width=\"100%\"><center><font face=\"Lucida Console, Monaco, monospace\" size=\"+4\"><u>";
	echo "Scoreboard";
	echo "</u></font></center></td></tr>\n";
	echo "<tr with=\"100%\" height=\"100%\" valign=\"top\"><td>\n";
}
//
// END printHTMLSBHeader
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: printQuestions
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         Array $postValues  (The sanitized $_POST data)
//         String $username (The username to filter the questions)
// Returns: NONE
// Description: Builds the table of user questions with answer questions blocked out
function printQuestions($clshConfig, $postValues, $username) {
	echo "<form name=\"CLSHQuestions\" action=\"CLSH_Home.php\" method=\"post\">\n";
	echo "<table width=\'600px\' border=\'0\' class=\"bottomBorder\">\n";
	$clshSections = $clshConfig->getSections();
	foreach ($clshSections as $section) {
		if ($section != "General") {
			if (is_null($clshConfig->getSetting($section, "LevelName"))) {
				echo "<tr> <th colspan='3' bgcolor='#882222'>$section</th> </tr>\n";
			} else {
				echo "<tr> <th colspan='3' bgcolor='#882222'>" . $clshConfig->getSetting($section, "LevelName") . "</th> </tr>\n";
			}
			$question = 1;
			while (!is_null($clshConfig->getSetting($section, "Q" . $question))) {
				echo "<tr> <td>" . $clshConfig->getSetting($section, "Q". $question). "</td>";
				if (userAlreadyAnswered($clshConfig, $username, $section, $question) ) {
					echo "<td> <font color='#555555'>Correct!</font> </td>";	
				} else {
					echo "<td> <input type=\"text\" size='40' name=\"" . $section . "_Q" . $question . "\"> </td>";	
				}
				echo "<td> <font color='#555555'> (" . $clshConfig->getSetting($section, "P" . $question) . "pts) </font> </td>";
				echo "</tr>\n";
				$question += 1;
			}
		}
	}
	echo "</table>\n";
	echo "<input type=\"hidden\" name=\"clsha\" value=\"postanswers\">";
	echo "<input type=\"submit\" value=\"Submit Answers\">\n";
	echo "</form>\n";
};
//
// END printQuestions
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: sanitizeInput
// Inputs: String $inputData  (A string to be sanitized)
// Returns: The sanitized string
// Description: Strips out all HTML tags and ASCII characters <32 or >127
function sanitizeInput($inputData) {
	return filter_var(trim($inputData), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
}
//
// END sanitizeInput
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: showHTMLError
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         String $theError  (The error title to display)
//         String $theDescription (The error description to display)
// Returns: NONE
// Description: Displays a formatted error message
function showHTMLError($clshConfig, $theError, $theDescription) {
	printHTMLHeader($clshConfig);
	echo "<center><h1>ERROR: " . $theError . "</h1><br>\n";
	echo "<h5>" . $theDescription . "</h5><br>\n";
	echo "<a href=\"CLSH_Home.php\">Return to home page</a></center>\n";
	printHTMLFooter($clshConfig);
}
//
// END showHTMLError
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: updateScoreboard
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         String $username (The username to filter the questions)
//         String $points (The number of points to set the scoreboard to for that user)
// Returns: NONE
// Description: Updates the scoreboard file with the specified user's current score
function updateScoreboard($clshConfig, $username, $points) {
	$theFile = $clshConfig->getSetting("General", "Scoreboard");
	if (! file_exists($theFile))
	{
		$quickCreate = fopen($theFile, "w");
		fclose($quickCreate);
	}
	$allScores = file($theFile);
	$newScores = "";
	$foundScore = FALSE;
	foreach ($allScores as $line) {
		if (trim($line) !== "") {
			$scoreData = explode(":", $line);
			if ($scoreData[0] == $username ) {
				$foundScore = TRUE;
				if ($scoreData[1] != $points ) {
					$dtz = new DateTimeZone("UTC");
					$now = new DateTime('now', $dtz);
					$newScores .= $username . ":" . $points . ":" . $now->format('U'). "\n";
				} else {
					$newScores .= $line;
				}
			} else {
				$newScores .= $line;
			}	
		}
	}
	if (! $foundScore) {
		$dtz = new DateTimeZone("UTC");
		$now = new DateTime('now', $dtz);
		$newScores .= $username . ":" . $points . ":" . $now->format('U') . "\n";
	}
	writeFile($theFile, $newScores);
}
//
// END updateScoreboard
///////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////
// Function name: userAlreadyAnswered
// Inputs: CLSHConfiguration $clshConfig  (The configuration data object)
//         String $username (The username to filter the questions)
//         String $section (The section to check)
//         String $question (The question to check)
// Returns: A Boolean value indicating whether or not the question has already been answered
// Description: Determines if a user has already answered a question
function userAlreadyAnswered($clshConfig, $username, $section, $question) {
	$subFile = $clshConfig->getSetting("General", "SubmissionsDirectory") . "/" . $username . ".db";
	$allAnswers = file($subFile);
	$requestedCheck = $section . "_Q" . $question;
	foreach ($allAnswers as $line) {
		$answerValues = explode(":", $line);
		if ($answerValues[0] == $requestedCheck) {
			return TRUE;
		}
	}
	return FALSE;
}
//
// END userAlreadyAnswered
///////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
// Function name: userExists
// Inputs: String $theFile   (The user database)
//         String $userName  (The username)
// Returns: Boolean indicating whether or not the username was found
// Description: Searches the user database for a given username.  Note that the search is
//              case insensitive to avoid duplication of names, while registration and login
//              ARE case sensitive.
function userExists($theFile, $userName) {
	$allUserData = file($theFile);
	$pattern = "/^" . $userName . ":.*/i";
	foreach ($allUserData as $line) {
		if (preg_match($pattern, $line)) {
			return TRUE;
		}
	}
	return FALSE;
}
//
// END userExists
///////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////
// Function name: writeFile
// Inputs: String $theFile  (The file to be written to)
//         String $theData  (The data to write to the file)
// Returns: null
// Description: Writes data to a specified file
function writeFile($theFile, $theData) {
	$fh = fopen($theFile, 'w');
	fwrite($fh, $theData);
	fclose($fh);
}
//
// END writeFile
///////////////////////////////////////////////////////////////////////////////////////////


?>
