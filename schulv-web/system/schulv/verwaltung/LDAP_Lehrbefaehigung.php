<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

require_once("LDAP/LDAP_Object.php");

define("LDAP_LEHRBEF_RDN", "ou=lehrbefaehigungen,ou=verwaltung");

class LDAP_Lehrbefaehigung extends LDAP_Object
{

	function LDAP_Lehrbefaehigung() {
		$this->LDAP_Object();
	}

	function reset() {
		LDAP_Object::reset();
		$this->addValue("objectclass", "schulvLehrbefaehigung");
	}

	function setDN($uid, $full = false) {
		if (!$full) {
			$uid = eregi_replace(",", " ", $uid);
			$dn = sprintf("kuerzel=%s,%s,%s", $uid, LDAP_LEHRBEF_RDN, SCHULV_SUFFIX);
		}
		else
			$dn = $uid;
		LDAP_Object::setDN($dn);
	}

	function search($attribs = false) {
		$ldap = Schulv::ldapConnect();
		$filter = "(objectclass=schulvLehrbefaehigung)";
		if ($ldap->search(sprintf("%s,%s", LDAP_LEHRBEF_RDN, SCHULV_SUFFIX), $filter, $attribs)) {
			//print_er($ldap->result);
			return $ldap->result;
		}
		return false;
	}

	function retrieve() {
		
	}


};

?>
