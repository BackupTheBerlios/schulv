<?php

class schulvDatasourceVerwaltungLehrbefaehigungen extends controlDatasource {
	var $attribs;
	var $ldap_result;
	var $pos;

	function schulvDatasourceVerwaltungLehrbefaehigungen($argv) {
		$this->controlDatasource($argv);
		$this->pos = 0;
		$this->attribs = array("kuerzel", "description");
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
		$st = new LDAP_Lehrbefaehigung();
		if (($this->ldap_result = $st->search($this->attribs))) {
			//print_er($this->ldap_result);

			$template = $this->argv('template');
			if (!$template)
				$template = "lehrbefaehigung";
			$tree = domxml_node($template);
			return $tree;
		}
		return false;
	}

	function show() {
		$tree = domxml_node("lehrbefaehigung");
		$tree->set_attribute("dn", urlencode($this->get('dn')));

		$tr = $tree->new_child('kuerzel', $this->get('kuerzel'));
		$tr = $tree->new_child('description', $this->get('description'));

		return $tree;
	}

	function finish() {
		$this->ldap_result = false;
		return false;
	}

};

core_register_datasource("schulv::verwaltung::lehrbefaehigungen", "schulvDatasourceVerwaltungLehrbefaehigungen");

?>
