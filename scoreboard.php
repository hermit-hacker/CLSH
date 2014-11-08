<?php
/////////////
//
// Cryptolingus Scavenger Hunt (CLSH) version 1.0
//
// Modified: 2014-11-08
// Unit: Scoreboard
// File: scoreboard.php
//
// Description: Displays a simple scoreboard
//
////////////

include 'CLSH_Common.php';

// Build the CLSHConfiguration file
$shConfig = new CLSHConfiguration("default.ini");

// Show the header
printHTMLSBHeader($shConfig, $teamname, $teampoints);

// Pull in the data
$scoreFile = $shConfig->getSetting("General", "Scoreboard");
$scores = file($scoreFile);
foreach ($scores as $line) {
	$tmpScores = explode(":", $line);
	$usernames[] = $tmpScores[0];
	$userscores[] = $tmpScores[1];
}

// Sort the data
array_multisort($userscores, SORT_DESC, $usernames, SORT_ASC);

// Build a table
$looper = 0;
echo "<center><table width=\"400\">\n";
echo "<tr> <th align=\"left\" bgcolor='#882222'><u>Username</u></th> <th align=\"left\" bgcolor='#882222'><u>Score</u></th> </tr>\n";
while (!is_null($userscores[$looper])) {
	echo "<tr> <td>" . $usernames[$looper] .  "</td>";
	echo "<td>" . $userscores[$looper] . "</td>";
	echo "</tr>\n";
	$looper += 1;
}
echo "</table></center>\n";

// Show the footer
printHTMLFooter($shConfig);

?>