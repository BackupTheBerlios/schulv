<?php


error_reporting(E_ALL);

require_once("LDAP/LDAP.php");
require_once("c0re/functions.inc");

session_start();

/* find our current location and copy it to $SCHULV_ROOT. */
$me = "/system/handler.php";
if (!ereg($me."$", $_SERVER['SCRIPT_FILENAME'])) {
	die('Cannot determine my document root directory!');
}
$SCHULV_ROOT = ereg_replace($me."$", "", $_SERVER['SCRIPT_FILENAME']);
unset($me);

core_init_system($SCHULV_ROOT."/system");

/* only after initializing the system we can go ahead and actually use what
 * the system offers
 */
require_once('schulv/schulv.php');


$file = $SCHULV_ROOT."/".$_SERVER['PHP_SELF'];
if (is_dir($file)) {
	$search = array("/index.php", "/index.xml", "/index.html");
	$found = false;
	foreach ($search as $s) {
		if (file_exists($file.$s)) {
			$file .= $s;
			$found = true;
			break;
		}
	}
	if (!$found) {
        header("HTTP/1.0 404 Not Found");
		echo "<h2>404 File not found</h2>";
		echo "There was index file found in the directory you requested.<br>\n";
		echo "These index filenames are supported: ".join(", ", $search);
        exit();
	}
}

if (ereg("\.xml$", $_SERVER['PHP_SELF'])) {
	$xslfile = ereg_replace("\.xml$", ".xsl", $file);
	if (file_exists($xslfile))
		core_use_xsl($xslfile);
	$tree = core_execute($file);
	echo $tree->dumpmem();
}
else if (!file_exists($file)) {
       header("HTTP/1.0 404 Not Found");
       echo "<h2>404 File not found</h2>";
       echo "The file ".$_SERVER["PHP_SELF"]." was not found on this server";
       exit();
}
else {
  include($file);
}
?>
