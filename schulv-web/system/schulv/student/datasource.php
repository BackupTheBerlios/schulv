<?php

class schulvDatasourceStudentListe extends controlDatasource {
	var $attribs;
	var $ldap_result;
	var $pos;

	function schulvDatasourceStudentListe($argv) {
		$this->controlDatasource($argv);
		$this->pos = 0;
		$this->attribs = array("cn", "givenname", "surname");
	}

	function get($key, $default = false) {
		if (is_array($this->ldap_result)) {
			$r = $this->ldap_result[$this->pos-1];
			if (array_key_exists($key, $r)) {
				if (is_array($r[$key])) {
					$value = "";
					for ($i = 0; $i < $r[$key]['count']; $i++)
						$value .= $r[$key][$i];
				}
				else
					$value = $r[$key];
				return $value;
			}
			return "";
		}
		return "";
	}

	function next() {
		if (is_array($this->ldap_result)) {
			if ($this->pos >= $this->ldap_result['count'])
				return false;
			$this->pos++;
			return true;
		}
		return false;
	}

	function init() {
		$this->pos = 0;
		$st = new LDAP_Student();
		if (($this->ldap_result = $st->search($this->attribs))) {
//			print_er($this->ldap_result);
			return true;
		}
		return false;
	}

	function show() {
		$tree = domxml_node("student");
		$tree->set_attribute("dn", urlencode($this->get('dn')));

		$tr = $tree->new_child('vorname', $this->get('givenname'));
		$tr = $tree->new_child('nachname', $this->get('sn'));

		$href = "undef";
//		$tr = $tree->new_child('record-dn', $href);
		return $tree;
	}

	function finish() {
		$this->ldap_result = false;
		return false;
	}

};

core_register_datasource("schulv::student::liste", "schulvDatasourceStudentListe");

?>
