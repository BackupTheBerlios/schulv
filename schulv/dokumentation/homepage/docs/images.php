<?php
	// wie kann man diesen HTML Kopf eigentlich eleganter einfuegen lassen? 
	echo "<!DOCTYPE html PUBLIC \"-//w3c//dtd html 4.0 transitional//en\">\n";
	echo "<html>\n<head>\n";
	echo "<meta http-equiv=\"Content-Type\" ";
	echo "content=\"text/html; charset=iso-8859-1\">\n";
	echo "<meta name=\"Author\" content=\"Sebastian Stein\">\n";
	echo "<title>Bilder zur Dokumentation</title>\n";
	echo "</head>\n<body>\n";
	echo "<h1>Bilder zur Dokumentation</h1>";
	echo "entnommen aus CVS: /schulv/dokumentation/bilder<p>";

	// Verzeichnis images oeffnen
	$handle=opendir('./images/');

	// jeden einzelnen Dateinamen aus dem Verzeichnis ueberpruefen
	while ($file = readdir ($handle))
	{
		// handelt es sich bei der Datei um ein jpg, dann fuegen wir einen Link
		// ein
		if (stristr($file, ".jpg") != FALSE)
			echo "<a href=\"./images/$file\">$file</a><br>";
	}
	closedir($handle); // Verzeichnis schliessen

	// Footer -> elegantere Loesung
	echo "<p><a href=\"../index.html\">Zur&uuml;ck zur Startseite...</a>";
	echo "<hr>\n<b>Kontakt: </b>\n<font size=\"-1\">\n";
	echo "<a href=\"mailto:lukas@edeal.de?subject=Schulv\">Lukas Schoeder</a>;"; 
	echo " <a href=\"mailto:steinchen@mail.berlios.de?subject=Schulv\">";
	echo "Sebastian Stein</a>\n<br>\n<b>letzte &Auml;nderung:</b>";
	echo " 08.02.2002</font>\n</body>\n</html>";	
?>
