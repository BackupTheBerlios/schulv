<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

require_once("LDAP/LDAP_Object.php");

define("LDAP_LEHRAMT_RDN", "ou=lehraemter,ou=verwaltung");

class LDAP_Lehramt extends LDAP_Object
{

	function LDAP_Lehramt() {
		$this->LDAP_Object();
	}

	function reset() {
		LDAP_Object::reset();
		$this->addValue("objectclass", "schulvLehramt");
	}

	function setDN($uid, $full = false) {
		if (!$full) {
			$uid = eregi_replace(",", " ", $uid);
			$dn = sprintf("kuerzel=%s,%s,%s", $uid, LDAP_LEHRAMT_RDN, SCHULV_SUFFIX);
		}
		else
			$dn = $uid;
		LDAP_Object::setDN($dn);
	}

	function search($attribs = false) {
		$ldap = Schulv::ldapConnect();
		$filter = "(objectclass=schulvLehramt)";
		if ($ldap->search(sprintf("%s,%s", LDAP_LEHRAMT_RDN, SCHULV_SUFFIX), $filter, $attribs)) {
			return $ldap->result;
		}
		return false;
	}

	function retrieve() {
		
	}


};

?>
