<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

class controlDatasource {
	var $__argv;

	function controlDatasource($argv) {
		$this->__argv = $argv;

		if (!is_array($this->__argv))
			$this->__argv = array();
	}

	function argv($key){ 
		if (array_key_exists($key, $this->__argv))
			return $this->__argv[$key];
		return false;
	}

	/* initialize datasource */
	function init() {
		user_error("controlDatasource::init has to be overloaded");
	}

	/* advance to the next record, return false if not available */
	function next() {
		user_error("controlDatasource::next has to be overloaded");
	}

	/* get one value */
	function get($key, $default = false) {
		user_error("controlDatasource::get has to be overloaded");
	}

	/* display one record set */
	function show() {
		user_error("controlDatasource::show has to be overloaded");
	}

	/* cleanup */
	function finish() {
		user_error("controlDatasource::finish has to be overloaded");
	}

};

?>
