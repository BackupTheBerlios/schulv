<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

require_once("LDAP/LDAP_Object.php");

class LDAP_Student extends LDAP_Object
{

	function LDAP_Student() {
		$this->LDAP_Object();
	}

	function reset() {
		LDAP_Object::reset();
		$this->addValue("objectclass", "person");
		$this->addValue("objectclass", "schulvStudent");
	}

	function setDN($uid, $full = false) {
		if (!$full) {
			$uid = eregi_replace(",", " ", $uid);
			$dn = sprintf("uid=%s,ou=students,%s", $uid, SCHULV_SUFFIX);
		}
		else
			$dn = $uid;
		LDAP_Object::setDN($dn);
	}

	function search($attribs = false) {
		$ldap = Schulv::ldapConnect();
		$filter = "(objectclass=schulvStudent)";
		if ($ldap->search(sprintf("ou=students,%s", SCHULV_SUFFIX), $filter, $attribs)) {
			return $ldap->result;
		}
		return false;
	}

	function retrieve() {
		
	}


};

?>
