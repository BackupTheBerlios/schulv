<?php

require_once("config.php");

require_once("ldap/Lehrer.php");

require_once("student/LDAP_Student.php");
require_once('student/validator.php');
require_once('student/datasource.php');


class Schulv
{

	function ldapConnect() {
/* TODO: if everything is known to work, we might be able to optimize connection behaviour
		global $__schulv_ldap;
		if (!is_object($__schulv_ldap)) {
			$__schulv_ldap = new LDAP("localhost:9999", "uid=interface,ou=users,o=schule.edeal.de", "interface");
		}
*/

		$ldap = new LDAP("localhost:9999", "uid=interface,ou=users,o=schule.edeal.de", "interface");
		return $ldap;
	}

	function baseDN($unit = "students") {
		if (!is_string($unit) || !strlen($unit))
			die("Schulv::baseDN braucht eine organizationalUnit als Parameter!");
		return sprintf("ou=%s,%s", $unit, SCHULV_SUFFIX);
	}

	function nextUid($idname = 'schulvLastuid') {

		/*  hier gibts eine Race-Condition zwischen ldapSearch und ldapModify;
			soetwas wie record-locking gibts aber __noch__ nicht in OpenLDAP...
			im OpenLDAP CVS Tree wird soetwas unterstuezt im Zusammenspiel mit Sleepycat DB 4.x
			sodass wir in Zukunft hoffentlich mit Locking arbeiten koennen...
		 */

		$ldap = Schulv::ldapConnect();
		$res = $ldap->search(SCHULV_SUFFIX, "(objectclass=schulvAdministration)");

		if ($res > 0) {
			$uidnr = $ldap->result[0]['schulvlastuid'][0] + 1;
			$modifyinfo['schulvlastuid'] = $uidnr;
			$ldap->attr_replace($ldap->result[0]["dn"], $modifyinfo);
		}
		else die("Fatal Error. schulvAdministration Objekt nicht gefunden in Schulv::nextUid.");

		return $uidnr;
	}

};


?>
