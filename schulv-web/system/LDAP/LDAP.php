<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

require_once("PEAR.php");

class LDAP
{

	var $servername;
	var $base_dn;
	var $bind_dn;
	var $bind_password;

	var $_ldap_server;
	var $_ldap_bind;
	var $_ldap_search;

	var $result;
	var $pos;

	function LDAP($servername, $binddn = false, $password = false)
	{
		$this->servername = $servername;
		$this->bind_dn = $binddn;
		$this->bind_password = $password;
		$this->base_dn = false;

		$this->_ldap_server = 0;
		$this->pos = 0;
	}

	function connect($servername = false, $binddn = false, $bindpassword = false)
	{
		if ($servername)
			$this->servername = $servername;
		if ($binddn)
			$this->bind_dn = $binddn;
		if ($bindpassword)
			$this->bind_password = $bindpassword;

		if (is_resource($this->_ldap_server))
			ldap_close($this->_ldap_server);

		$this->_ldap_server = ldap_connect($this->servername);
		if ($this->_ldap_server) {
			if ($this->bind_dn || $this->bind_password)
				$this->_ldap_bind = ldap_bind($this->_ldap_server, $this->bind_dn, $this->bind_password);
			else
				$this->_ldap_bind = ldap_bind($this->_ldap_server);

			return $this->_ldap_bind;
		}
		return false;
	}

	function close()
	{
		if ($this->isConnected()) {
			ldap_close($this->_ldap_server);
			$this->_ldap_server = null;
			$this->_ldap_bind = null;
			$this->_ldap_search = null;
		}
	}

	function isConnected()
	{
		return is_resource($this->_ldap_server) && $this->_ldap_bind;
	}

    function get($key, $default = false, $delim = "") {
        if (is_array($this->result)) {
            $r = $this->result[$this->pos];
            if (array_key_exists($key, $r)) {
                if (is_array($r[$key])) {
                    $data = array();
                    for ($i = 0; $i < $r[$key]['count']; $i++)
                        $data[] = $r[$key][$i];
					$value = join($delim, $data);
                }
                else
                    $value = $r[$key];
                return $value;
            }
            return "";
        }
        return false;
    }

	function read($basedn, $filter, $attribs = false) {
		if (!$this->isConnected()) {
			if (!$this->connect())
				return false;
		}

		if ($attribs)
			$this->_ldap_search = ldap_read($this->_ldap_server, $basedn, $filter, $attribs);
		else
			$this->_ldap_search = ldap_read($this->_ldap_server, $basedn, $filter);

		$this->result = ldap_get_entries($this->_ldap_server, $this->_ldap_search);

		return ldap_count_entries($this->_ldap_server, $this->_ldap_search);
	}


	function search($basedn, $filter, $attribs = false)
	{
		if (!$this->isConnected()) {
			if (!$this->connect())
				return false;
		}

		if ($attribs)
			$this->_ldap_search = ldap_search($this->_ldap_server, $basedn, $filter, $attribs);
		else
			$this->_ldap_search = ldap_search($this->_ldap_server, $basedn, $filter);

		$this->result = ldap_get_entries($this->_ldap_server, $this->_ldap_search);

		return ldap_count_entries($this->_ldap_server, $this->_ldap_search);
	}

	function modify($recordsdn, $modifyinfo)
	{
		if (!$this->isConnected()) {
			if (!$this->connect())
				return false;
		}

		$this->result = false;
		if (is_object($recordsdn) && is_subclass_of($recordsdn, "LDAP_Object")) {
			return $this->modify($recordsdn->getDN(), $recordsdn->getInfo());
		} else {
			$this->result = ldap_modify($this->_ldap_server, $recordsdn, $modifyinfo);
		}
		return $this->result;
	}

	function attr_replace($recordsdn, $modifyinfo = false)
	{
		if (!$this->isConnected()) {
			if (!$this->connect())
				return false;
		}

		$this->result = false;
		if (is_object($recordsdn) && is_subclass_of($recordsdn, "LDAP_Object")) {
			return $this->modify($recordsdn->getDN(), $recordsdn->getInfo());
		} else if (is_array($modifyinfo)) {
			$this->result = ldap_mod_replace($this->_ldap_server, $recordsdn, $modifyinfo);
		}

		return $this->result;
	}

	function add($recordsdn, $addinfo = false)
	{
		if (!$this->isConnected()) {
			if (!$this->connect()) {
				return false;
			}
		}

		$this->result = false;
		if (is_object($recordsdn) && is_subclass_of($recordsdn, "LDAP_Object")) {
			return $this->add($recordsdn->getDN(), $recordsdn->getInfo());
		} else {
			//print_er($addinfo, "ADDINFO:");
			$this->result = ldap_add($this->_ldap_server, $recordsdn, $addinfo);
		}
		return $this->result;
	}

	function delete($recordsdn)
	{
		if (!$this->isConnected()) {
			if (!$this->connect())
				return false;
		}

		$this->result = false;
		if (is_object($recordsdn) && is_subclass_of($recordsdn, "LDAP_Object")) {
			return $this->delete($recordsdn->getDN());
		} else {
			$this->result = ldap_delete($recordsdn);
		}
		return $this->result;
	}

	function error() {
		if (!$this->isConnected()) {
			if (!$this->connect())
				return "not connected";
		}

		return ldap_error($this->_ldap_server);
	}

	function errno() {
		if (!$this->isConnected()) {
			if (!$this->connect())
				return "not connected";
		}

		return ldap_errno($this->_ldap_server);
	}


};

require_once("LDAP_Object.php");

?>
