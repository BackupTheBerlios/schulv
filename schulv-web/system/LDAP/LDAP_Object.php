<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

class LDAP_Object
{
	var $_dn;
	var $_info;

	function LDAP_Object() {
		$this->reset();
	}

	function reset() {
		$this->_dn = array();
		$this->_info = array();

		/* base objectclass settings */
		$this->addValue("objectclass", "top");
	}

	function clearValue($key) {
		$this->_info[$key] = array();
	}

	function setValue($key, $value) {
		$this->clearValue($key);
		$this->_info[$key][] = $value;
	}

	function addValue($key, $value) {
		if (!array_key_exists($key, $this->_info) || !is_array($this->_info[$key]))
			$this->clearValue($key);
		$this->_info[$key][] = $value;
	}

	function setDN($newdn) {
		$this->_dn = $newdn;
	//	$this->setValue("dn", $this->_dn);
	}

	function getDN() {
		return $this->_dn;
	}

	function getDNFilter($rdn) {
		$filter = "";
		$rdns = split(",", $this->_dn);

		$parts = array();
		foreach($rdns as $rdn) {
			$rdn = trim($rdn);
			$parts[] = sprintf("(%s)", $rdn);
		}
		$filter = sprintf("(&%s)", join("", $parts));
		return $filter;
	}

	function getInfo() {
		return $this->_info;
	}

	function search() {
		user_error("LDAP_Object::search() has to be overloaded");
	}

	function retrieve() {
		user_error("LDAP_Object::retrieve() has to be overloaded");
	}

};

