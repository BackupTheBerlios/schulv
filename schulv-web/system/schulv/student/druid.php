<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

class schulvStudentDruid extends Druid {
	var $argv;
	var $mapping;

	function schulvStudentDruid($data) {
		$this->Druid($data);

		/* aufraeumen der argumentliste, damit alles was gebraucht wird, auch da ist */
		$enforce = array("policy");
		foreach ($enforce as $e) {
			if (!array_key_exists($e, $this->argv))
				$this->argv[$e] = false;
		}

		$this->mapping = array (
					'commonname' => 'cn',
					'vorname' => 'givenname',
					'nachname' => 'sn', // surname
					'geschlecht' => 'geschlecht',
					'geburtsdatum' => 'geburtsdatum',
					'adresse' => 'postaladdress',
					'telefonnummer' => 'telephonenumber'
				);
	}

	function validate() {
		switch(strtolower($this->argv['policy'])) {
			case "create":
				// TODO: check database whether entry already exists
				return true;
				break;
			case "finish":
				// TODO: check whether data is ok for storage
				return true;
				break;
		}
		echo "schulvStudentDruid::validate(): unhandled policy \"".$this->argv['policy']."\"";
		return false;
	}

	function store() {
		$st = new LDAP_Student();

		$info = array_keys($this->mapping);
		$d = array();

		foreach($info as $k) {
			$d[$k] = trim(stripslashes(core_get_param($k, $this->argv['prefix'])));
		}

		$mode = array_key_exists('mode', $this->argv) ? $this->argv['mode'] : "create";
		switch($mode) {
			case "modify":
				if (!($dn = core_get_param('__schulv_record_dn', $this->argv['prefix']))) {
					user_error("RecordDN not set. Cannot modify database entry");
					return false;
				}
				$st->setDN($dn, true);
				break;

			case "create":
			default:
				$uid = sprintf("%s%d", stripslashes($d['nachname']), Schulv::nextUid());
				$st->setDN($uid);
				$st->setValue("uid", $uid);
				break;
		}

		$cn = sprintf("%s %s", $d['vorname'], $d['nachname']);
		$st->setValue("cn", $cn);

		$st->setValue("givenname", $d['vorname']);
		$st->setValue("surname", $d['nachname']);
		$st->setValue("geschlecht", $d['geschlecht']);
		$st->setValue("telephonenumber", $d['telefonnummer']);
		$st->setValue("geburtsdatum", $d['geburtsdatum']);
		$st->addMultiValue("postaladdress", $d['adresse']);

		$ldap = Schulv::ldapConnect();
		switch($mode) {
			case "modify":
				if (!$ldap->attr_replace($st)) {
					print "LDAP Error: ".sprintf("%d: %s", $ldap->errno(), $ldap->error());
					return false;
				}
				break;

			case "create":
			default:
				if (!$ldap->add($st)) {
					print "LDAP Error: ".sprintf("%d: %s", $ldap->errno(), $ldap->error());
					return false;
				}
				break;
		}

		if (array_key_exists("onsuccess", $this->argv)) {
			header("Location: ".$this->argv['onsuccess']);
			exit();
		}
		return true;
	}

	function import() {
		if (array_key_exists('dn', $_REQUEST)) {
			$st = new LDAP_Student();
			$st->setDN($_REQUEST['dn'], true);
			core_set_param('__schulv_record_dn', $_REQUEST['dn'], $this->argv['prefix']);

			core_set_param('dn', $_REQUEST['dn'], $this->argv['prefix']);

			$ldap = Schulv::ldapConnect();
			if (($result = $ldap->read($st->getDN(), "(objectClass=*)"))) {
				//print_er($ldap->result);

				reset($this->mapping);
				while(list($k, $v) = each($this->mapping)) {
					core_set_param($k, $ldap->get($v), $this->argv['prefix']);
				}

				core_set_param('adresse', $ldap->get($this->mapping['adresse'], false, "\n"), $this->argv['prefix']);
				return true;
			}

			return false;
		}
		return false;
	}

};

core_register_druid("schulv::student", "schulvStudentDruid");

?>
