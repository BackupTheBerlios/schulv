<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE.LGPL file for a copy of this license.
 */

require_once("LDAP/LDAP_Object.php");

class LDAP_schulvSchule extends LDAP_Object
{

	function LDAP_schulvSchule() {
		$this->LDAP_Object();
	}

	function reset() {
		LDAP_Object::reset();
		$this->addValue("objectclass", "organization");
		$this->addValue("objectclass", "schulvSchule");
	}

	function setDN($schulnr, $full = false) {
		if (!$full) {
			$schulnr = eregi_replace(",", " ", $schulnr);
			$dn = sprintf("schulnr=%s,%s", $schulnr, SCHULV_SUFFIX);
		}
		else
			$dn = $schulnr;
		LDAP_Object::setDN($dn);
	}

	function search($attribs = false) {
		$ldap = Schulv::ldapConnect();
		$filter = "(objectclass=schulvSchule)";
		if ($ldap->search(SCHULV_SUFFIX, $filter, $attribs)) {
			return $ldap->result;
		}
		return false;
	}

	function retrieve() {
		
	}


};

?>
