<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

/* base class for formx elements */

class it_element {
	var $el;

	function it_element(&$el) {
		$this->el =& $el;
	}

	function prepend_error_node() {
		if (array_key_exists('__error', $this->el)) {
			$node = domxml_node("b");
			$node->set_content($this->el['__error']);

			// the php_domxml calling convention for "insert_before" is just sick!
			$this->el['__node']->insert_before($node, $this->el['__node']);

			$node = domxml_node("br");
			$this->el['__node']->insert_before($node, $this->el['__node']);
		}
	}

	function set_error($str) {
		$this->el['__error'] = $str;
	}
	
	function get_name() {
		$name = $this->el['name'];
		$name = ereg_replace("\[\]$", "", $name);
		return $name;
	}

	function get_value($use_default = true) {
		$value = ($use_default && array_key_exists('value', $this->el)) ? $this->el['value'] : "";
		if (isset($this->el['__loaded_value']))
			$value = $this->el['__loaded_value'];
		return $value;
	}

	function load() {
		unset($this->el['__loaded_value']);

		$name = $this->get_name();
		if (array_key_exists($name, $_REQUEST))
			$this->el['__loaded_value'] = $_REQUEST[$name];
		else if (($value = FormXHelper::retrieve_value($name))) {
			$this->el['__loaded_value'] = $value;
		}
	}

	function show() {
		$value = $this->get_value();
		$this->prepend_error_node();
		$this->el['__node']->set_attribute('value', $value);
	}

	function validate() {
		$this->el['__error'] = "it_element does not validate! overload this method!";
		return false;
	}

	function store() {
		FormXHelper::store_value($this->get_name(), $this->get_value());
	}

};

/* default html-inputtypes */

class it_hidden extends it_element {
	function it_hidden(&$el) {
		$this->it_element($el);
	}

	function load() {
		// no op
	}

	function show() {
		if (!array_key_exists('value', $this->el))
			$this->el['value'] = "";
		$this->el['__node']->set_attribute('value', $this->el['value']);
	}

	function validate() {
		unset($this->el['__error']);
		return true;
	}
};

class it_submit extends it_element {

	function it_submit(&$el) {
		$this->it_element($el);
	}

	function load() {
		// no op
	}

	function show() {
		if (!array_key_exists('value', $this->el) || !strlen($this->el['value']))
			$this->el['value'] = "Submit Query";
		$this->el['__node']->set_attribute('value', $this->el['value']);
	}

	function validate() {
		unset($this->el['__error']);
		return true;
	}
};

class it_text extends it_element {

	function it_text(&$el) {
		$this->it_element($el);
	}

	function validate() {
		unset($this->el['__error']);

		if (array_key_exists('length_e', $this->el) 
			&& strlen($this->el['__loaded_value']) < $this->el['minlength']) {
			$this->set_error($this->el['length_e']);
		}
		else if (array_key_exists('valid_e', $this->el)) {
			if (array_key_exists('icase', $this->el)) {
				if (!eregi($this->el['valid_regex'], $this->el['__loaded_value']))
					$this->set_error($this->el['valid_e']);
			}
			else if (!eregi($this->el['valid_regex'], $this->el['__loaded_value']))
				$this->set_error($this->el['valid_e']);
		}

		return !array_key_exists('__error', $this->el);
	}
};

class it_radio extends it_element {

	function it_radio(&$el) {
		$this->it_element($el);
	}

	function show() {
		$value = $this->get_value(false);

		$have_checked = false;

		/* find the radio-item to set the checked flag for
		 */

		$this->el['__node']->set_attribute("value", $this->el['value']);
		if ($value == $this->el['value']) {
			$this->el['__node']->set_attribute("checked", "");
			$have_checked = true;
		}

		for ($index = 0; $index < sizeof($this->el['__siblings']); $index++) {
			$el =& $this->el['__siblings'][$index];
			$el['__node']->set_attribute("value", $el['value']);

			if (!$have_checked && $value == $el['value'])
				$el['__node']->set_attribute("checked", "");
		}
	}

	function validate() {
		return true;
	}

	function store() {
		FormXHelper::store_value($this->get_name(), $this->get_value(false));
	}
};

class it_checkbox extends it_element {

	function it_checkbox(&$el) {
		$this->it_element($el);
	}

	function show() {
		$multiple = ereg("\[\]$", $this->el['name']);

		$value = $this->get_value(false);

		$this->el['__node']->set_attribute("value", $this->el['value']);
		if (($multiple && is_array($value) && in_array($this->el['value'], $value))
			|| (!$multiple && !is_array($value) && $value == $this->el['value'])) {
				$this->el['__node']->set_attribute("checked", "");
		}

		if (array_key_exists('__siblings', $this->el)) {
			for ($index = 0; $index < sizeof($this->el['__siblings']); $index++) {
				$el =& $this->el['__siblings'][$index];
				$el['__node']->set_attribute("value", $el['value']);

				if (($multiple && is_array($value) && in_array($el['value'], $value))
					|| (!$multiple && !is_array($value) && $value == $el['value'])) {
						$el['__node']->set_attribute("checked", "");
				}
			}
		}
	}

	function validate() {
		return true;
	}

	function store() {
		FormXHelper::store_value($this->get_name(), $this->get_value(false));
	}
};

class it_textarea extends it_element {

	function it_textarea(&$el) {
		$this->it_element($el);
	}

	function show() {
		$value = $this->get_value();
		$this->prepend_error_node();
		$this->el['__node']->set_content($value);
	}

	function validate() {
		unset($this->el['__error']);

		if (array_key_exists('length_e', $this->el) 
			&& strlen($this->el['__loaded_value']) < $this->el['minlength']) {
			$this->set_error($this->el['length_e']);
		}
		else if (array_key_exists('valid_e', $this->el)) {
			if (array_key_exists('icase', $this->el)) {
				if (!eregi($this->el['valid_regex'], $this->el['__loaded_value']))
					$this->set_error($this->el['valid_e']);
			}
			else if (!eregi($this->el['valid_regex'], $this->el['__loaded_value']))
				$this->set_error($this->el['valid_e']);
		}

		return !array_key_exists('__error', $this->el);
	}
};

?>
