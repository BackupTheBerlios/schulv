<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

require_once("LDAP/LDAP_Object.php");

class LDAP_Lehrer extends LDAP_Object
{

	function LDAP_Lehrer() {
		$this->LDAP_Object();
	}

	function reset() {
		LDAP_Object::reset();
		$this->addValue("objectclass", "person");
		$this->addValue("objectclass", "organizationalPerson");
		$this->addValue("objectclass", "inetOrgPerson");
		$this->addValue("objectclass", "schulvPerson");
		/* and some AUXILIARYs */
		$this->addValue("objectclass", "schulvAngestellter");
		$this->addValue("objectclass", "schulvLehrer");
	}

	function setDN($uid, $full = false) {
		if (!$full) {
			$uid = eregi_replace(",", " ", $uid);
			$dn = sprintf("uid=%s,ou=personal,%s", $uid, SCHULV_SUFFIX);
		}
		else
			$dn = $uid;
		LDAP_Object::setDN($dn);
	}

	function search($attribs = false) {
		$ldap = Schulv::ldapConnect();
		$filter = "(objectclass=schulvLehrer)";
		if ($ldap->search(sprintf("ou=personal,%s", SCHULV_SUFFIX), $filter, $attribs)) {
			return $ldap->result;
		}
		return false;
	}

	function retrieve() {
		
	}


};

?>
