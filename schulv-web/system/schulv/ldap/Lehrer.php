<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

require_once("LDAP/LDAP_Object.php");

class Lehrer extends LDAP_Object
{

	function Lehrer() {
		$this->LDAP_Object();
	}

	function reset() {
		LDAP_Object::reset();
		$this->addValue("objectclass", "person");
		$this->addValue("objectclass", "schulvLehrer");
	}

};

?>
