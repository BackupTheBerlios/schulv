<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE.LGPL file for a copy of this license.
 */

class schulvSchuleDruid extends Druid {
	var $argv;
	var $mapping;

	function schulvSchuleDruid($data) {
		$this->Druid($data);

		/* aufraeumen der argumentliste, damit alles was gebraucht wird, auch da ist */
		$enforce = array("policy");
		foreach ($enforce as $e) {
			if (!array_key_exists($e, $this->argv))
				$this->argv[$e] = false;
		}

		$this->mapping = array (
					'schulname' => 'o',
					'aktuellesSchuljahr' => 'aktuellesSchuljahr',
					'schulnr' => 'schulnr'
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
		echo "schulvSchuleDruid::validate(): unhandled policy \"".$this->argv['policy']."\"";
		return false;
	}

	function store() {
		$st = new LDAP_schulvSchule();

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
				$nr = stripslashes($d['schulnr']);
				$st->setDN($nr);
				$st->setValue("schulnr", $nr);
				break;
		}

		/* TODO: Aendern der SchulNR geht nur ueber ein zusaetzliches ModRDN */
		$st->setValue("o", $d['schulname']);


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
			$st = new LDAP_schulvSchule();
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

				return true;
			}

			return false;
		}
		return false;
	}

};

core_register_druid("schulv::schule", "schulvSchuleDruid");

?>
