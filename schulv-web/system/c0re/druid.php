<?php

class Druid
{
	var $argv;

	function Druid($data) {
		$this->argv = $data;

		$enforce = array("prefix");
		foreach ($enforce as $e) {
			if (!array_key_exists($e, $this->argv))
				$this->argv[$e] = false;
		}
	}

};


?>
