<?php

class schulvValidatorStudent extends FormXValidator {

	function schulvValidatorStudent(&$form, $data) {
		$this->FormXValidator($form, $data);
	}

	function validate() {
		switch(strtolower($this->data['policy'])) {
			case "create":
				// TODO: check database whether entry already exists
				return true;
				break;
			case "finish":
				// TODO: check whether data is ok for storage
				return true;
				break;
		}
		echo "schulvValidatorStudent: unhandled policy \"".$this->data['policy']."\"";
		return false;
	}

	function store() {
		$st = new Student();

		$info = array("vorname", "nachname", "adresse", "geschlecht", "telefonnummer");
		$d = array();

		foreach($info as $k) {
			$d[$k] = trim(stripslashes(FormXHelper::retrieve_value($k)));
		}

		$mode = array_key_exists('mode', $this->data) ? $this->data['mode'] : "create";
		switch($mode) {
			case "modify":
				if (!($dn = FormXHelper::retrieve_value('__schulv_record_dn'))) {
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
		$st->setValue("givenname", $d['vorname'], $d['nachname']);
		$st->setValue("surname", $d['nachname']);

		if ($d['adresse']) {
			$lines = split("\n", $d['adresse']);
			foreach($lines as $line) {
				$line = trim(stripslashes($line));
				if (strlen($line) > 0)
					$st->addValue("postaladdress", $line);
			}
		}

		$st->setValue("geschlecht", $d['geschlecht']);
		$st->setValue("telephonenumber", $d['telefonnummer']);

		$ldap = Schulv::ldapConnect();;
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

// uncomment only while debugging!
		FormXHelper::cleanup_values();
		if (array_key_exists("onsuccess", $this->data)) {
			header("Location: ".$this->data['onsuccess']);
			exit();
		}
		return true;
	}

	function import() {
		if (array_key_exists('dn', $_REQUEST)) {
			$st = new Student();
			$st->setDN($_REQUEST['dn'], true);
			FormXHelper::store_value('__schulv_record_dn', $_REQUEST['dn']);

			$ldap = Schulv::ldapConnect();
			if (($result = $ldap->read($st->getDN(), "(objectClass=*)"))) {
				//print_er($ldap->result);

				FormXHelper::store_value('vorname', $ldap->get('givenname'));
				FormXHelper::store_value('nachname', $ldap->get('sn'));
				FormXHelper::store_value('geschlecht', $ldap->get('geschlecht'));
				FormXHelper::store_value('geburtsdatum', $ldap->get('geburtsdatum'));
				FormXHelper::store_value('adresse', $ldap->get('postaladdress'));
				FormXHelper::store_value('telefonnummer', $ldap->get('telephonenumber'));

				return true;
			}

			return false;
		}
		return false;
	}

};

FormXHelper::add_validator("schulv::student", "schulvValidatorStudent");

?>
