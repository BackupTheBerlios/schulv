<?php
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */

/* {{{ system initialization */

function core_init_system($root) {
	// TODO: this global stuff could and should go away

	global $__core_base_path;
	global $__core_system_data;

	$__core_base_path = $root;
	
	$__core_system_data = array();
	$__core_system_data['options'] = array();
	$__core_system_data['tags'] = array();
	$__core_system_data['namespaces'] = array();
	$__core_system_data['dirs'] = array();
	$__core_system_data['dirs']['modules'] = $__core_base_path."/c0re/modules";
	$__core_system_data['params'] = array();
	$__core_system_data['druids'] = array();
	$__core_system_data['datasources'] = array();
	$__core_system_data['finish-handler'] = array();

	$includepath = split(":", ini_get("include_path"));
	$includepath[] = $__core_base_path;
	$includepath[] = $__core_system_data['dirs']['modules'];
	ini_set("include_path", join(":", $includepath));
	
	require_once('c0re/druid.php');

	core_load_module("html");
	core_load_module("control");
	core_load_module("formx");
}

/*
 * core_load_module(string modulename)
 * laedt die haupt-datei eines moduls.
 * diese datei muss unter modules/<modulename>/<modulename>.class zu finden
 * sein
 *
 * Achtung: da die Module innerhalb einer Funktion include'd werden, sind
 *     natuerlich die gewohnten Schritte einzuleiten, falls man globale Variablen
 *     verwenden moechte
 */
function core_load_module($name) {
	$dir = $GLOBALS['__core_system_data']['dirs']['modules'];
	$fname = sprintf("%s/%s/%s.class", $dir, $name, $name);
	if (file_exists($fname)) {
		require_once($fname);
	}
	else user_error("[core] failed to load module \"$name\" (expected it in \"$fname\")");
}

/* finish handlers are called only, and only when the stage of preparing to
 * dump() the output dom tree is reached
 */
function core_add_finish_handler($classname) {
	global $__core_system_data;

	if (!in_array($classname, $__core_system_data['finish-handler']))
		$__core_system_data['finish-handler'][] = $classname;
}

function core_register_druid($druid, $classname) {
	global $__core_system_data;
	if (!array_key_exists($druid, $__core_system_data['druids'])) {
		$__core_system_data['druids'][$druid] = $classname;
		return true;
	}
	return false;
}

function core_invoke_druid($druid, $method, $argv = array()) {
	global $__core_system_data;
	if (!array_key_exists($druid, $__core_system_data['druids'])) {
		user_error("The druid \"$druid\" is unknown");
		return false;
	}

	$cn = $__core_system_data['druids'][$druid];
	$d = new $cn($argv);
	if (!method_exists($d, $method)) {
		user_error("The druid \"$druid\" does not support method \"$method\"");
		return false;
	}

	return $d->{$method}();
}

function core_register_datasource($datasource, $classname) {
	global $__core_system_data;
	if (!array_key_exists($datasource, $__core_system_data['datasources'])) {
		$__core_system_data['datasources'][$datasource] = $classname;
		return true;
	}
	return false;
}

function core_use_datasource($datasource, $argv = array()) {
	global $__core_system_data;
	if (!array_key_exists($datasource, $__core_system_data['datasources'])) {
		user_error("The datasource \"$datasource\" is unknown");
		return false;
	}

	$cn = $__core_system_data['datasources'][$datasource];
	return new $cn($argv);
}

/* }}} */

function core_use_xsl($file) {
	global $__core_system_data;
	$__core_system_data['options']['xsl_template'] = $file;
}

/* {{{ phases of request handling */

function core_execute($file) {
	if (!file_exists($file)) {
		header("HTTP/1.0 404 Not Found");
		echo "<h2>404 File not found</h2>";
		echo "The file ".$_SERVER["PHP_SELF"]." was not found on this server";
		exit();
	}
	// TODO: dump usage of "xmltree", use xmldoc() instead

	$fd = fopen($file, "r");
	$myXML = fread($fd, filesize($file));
	fclose($fd);
	if (!($docTree = xmltree($myXML))) {
		header("HTTP/1.0 500 Internal Server Error");
		echo "<h2>500 Internal Server Error</h2>\n";
		echo "Failed to parse the requested file ".$_SERVER['PHP_SELF'].".<br/>\n";
		echo "Refer to the error_log for details.";
		exit();
	}
	$root = $docTree;

	$tree = xml_analyze($root);

	global $__core_system_data;
	if (array_key_exists('xsl_template', $__core_system_data['options'])) {

		//$params = core_get_params();

		$docsheet = xmldocfile($__core_system_data['options']['xsl_template']);
		$sheet = domxml_xslt_stylesheet_doc($docsheet);
		return $sheet->process($tree, array());
	}
	return $tree;
}

function core_do_finish() {
	global $__core_system_data;

	foreach ($__core_system_data['finish-handler'] as $cn) {
		$handler = new $cn;
		if (!method_exists($handler, "finish_handler"))
			user_error("Class $cn is not a valid finish handler");
		else
			$handler->finish_handler();
	}
}

/* }}} */

/* {{{ global variable storage */

function core_kill_prefix($prefix = false) {
	global $__core_system_data;
	if (!is_string($prefix) || !strlen($prefix))
		$prefix = "__default";
	$__core_system_data['params'][$prefix] = array();
}

function core_set_param($param, $value, $prefix = "__default") {
	global $__core_system_data;
	if (!is_string($prefix))
		$prefix = "__default";
	if (!array_key_exists($prefix, $__core_system_data['params']))
		$__core_system_data['params'][$prefix] = array();
	$__core_system_data['params'][$prefix][$param] = $value;
}

function core_get_param($param, $prefix = "__default") {
	global $__core_system_data;
	if (!array_key_exists($prefix, $__core_system_data['params']))
		return false;
	if (array_key_exists($param, $__core_system_data['params'][$prefix]))
		return $__core_system_data['params'][$prefix][$param];
	return false;
}

function core_get_params($prefix = "__default") {
	global $__core_system_data;

	if (!is_string($prefix))
		$prefix = "__default";

	if (!array_key_exists($prefix, $__core_system_data['params'])) {
		return array();
	}
	return $__core_system_data['params'][$prefix];
}

/* }}} */

function get_module_for_element($element) {
	global $__core_system_data;	
	if (array_key_exists($element, $__core_system_data['tags'])) {
//		echo "Found module for $element...\n";
		return $__core_system_data['tags'][$element];
	}
	return false;
}

function register_tags($classname,$tags) {

		global $__core_system_data;

		if (!is_array($__core_system_data))
			$__core_system_data = array();
		if (!is_array($__core_system_data['tags']))
			$__core_system_data = array();

		foreach($tags as $tag){
			if(array_key_exists($tag, $__core_system_data['tags'])){
				//print "ERROR: Module $classname tried to set $tag which has already been claimed by $__core_system_data['tags'][$tag]";
			}else{	
				$__core_system_data['tags'][$tag]=$classname;
			}
		}	
		return true;
}

/* {{{ xml namespaces */

/*
 * einen xml namespace registieren. ist eine classname fuer einen namespace
 * registriert wird die methode namespace_handler() dieser klasse fuer jedes
 * element aufgerufen, fuer das $namespace angegeben wurde. $namespace ist das im
 * xml-dokument definierte namespace-alias, und nicht die damit verknuepfte URI!
 */
function core_register_namespace($namespace, $classname) {
	global $__core_system_data;

	if (!array_key_exists($namespace, $__core_system_data['namespaces']))
		$__core_system_data['namespaces'][$namespace] = $classname;
	else
		user_error("Namespace $namespace already claimed by $classname.");
}

function core_unregister_namespace($namespace, $classname) {
	global $__core_system_data;
	if (core_get_namespace_handler($namespace) === $classname)
		unset($__core_system_data['namespaces'][$namespace]);
}

function core_get_namespace_handler($namespace) {
	global $__core_system_data;
	if (!array_key_exists($namespace, $__core_system_data['namespaces']))
		return false;
	return $__core_system_data['namespaces'][$namespace];
}

/* }}} */

/* {{{ xml input handling */

function xml_analyze($input_tree) {

	$tree = new_xmldoc("1.0");
	$root = xml_translate_tree($input_tree);
	if (is_object($root)) {
		$tree->set_root($root);
		core_do_finish();
	}
	else
		$tree->set_root(new_warning_node("Fatal. No root-node returned by xml_translate_tree()"));
	return $tree;
}

function xml_translate_tree($old_tree, $parent = false)
{
  	if($old_tree->has_child_nodes()) {
    	foreach($old_tree->children() as $current_child) {

			if ($current_child->node_type() == XML_TEXT_NODE) {
				$new_node = $current_child->clone_node();
				if (get_class($parent) == "DomElement")
					$new_node = $parent->add_child($new_node);
			}
			else if ($current_child->node_type() == XML_ELEMENT_NODE) {

				unset($new_node);
				if ($current_child->prefix()) {
					$namespace = $current_child->prefix();
					$cn = core_get_namespace_handler($namespace);
					if ($cn) {
						$handler = new $cn;
						if (!method_exists($handler, 'namespace_handler')) {
							user_error("The namespace handler for $namespace ($cn) is incorrectly implemented");
						}
						else
							$new_node = $handler->namespace_handler($current_child);
					}
				}

				if (!isset($new_node)) {
					if($module = get_module_for_element($current_child->tagname))
						$handler = new $module;
					else
						$handler = new element; /* fallback default HTML handler */
			 
					/* der dispatcher hat entweder eine domxml-node oder ein array von
					 * domxml-nodes zurueckzuliefern. wird ein array mit mehr als
					 * einem element zurueckgeliefert, wenn noch keine root-node gesetzt ist,
					 * wird das erste element des arrays als root-node gesetzt und
					 * alle anderen elemente werden verworfen...
					 */
		      		$new_node = $handler->dispatch($current_child);
				}
    
				if (is_object($new_node)) {
					switch($new_node->node_type()) {
						case XML_ELEMENT_NODE:
						case XML_COMMENT_NODE:
							if (get_class($parent) != "DomElement") {
								$parent = $new_node;
							}	
							else if (get_class($parent) == "DomElement") {
								$new_node = $parent->add_child($new_node);
							}
							else {
								die("Oops. Parent node badly corrupted");
							}

							break;
						case XML_TEXT_NODE:
							if (get_class($parent) == "DomElement")
								$parent->add_child($new_node);
							break;
					}
				}
				else if (is_array($new_node) && sizeof($new_node) > 0) {
					if (get_class($parent) != "DomElement") {
						if (get_class($new_node[0]) == "DomElement")
							$parent = $new_node;
					}
					else {
						foreach($new_node as $nn)
							if (is_object($nn)) {
								$parent->add_child($nn);
							}
					}
				}
			}
    	}
	}

	return $parent;
}

/* }}} */

/* {{{ xml auxiliaries */

function new_warning_node($text, $system = "") {
	$node = domxml_node("b");
	$node->set_content(sprintf("%s Warning: %s", $system, $text));
	return $node;
}

function get_attribute($key,$node){

	if ($node->has_attributes()) {
		foreach($node->attributes() as $attr) {
			if($attr->name == $key){ 
				return $attr->value;
			}
		}
	}

	return false;
}

function get_content($node) {

	$content = array();
	if($node->has_child_nodes()) {
		foreach($node->children() as $child){
			if ($child->node_type() == XML_TEXT_NODE){
				$content[] = $child;
			}
		}
	}
	return $content;
}

function get_content_string($node) {
	$content = get_content($node);
	$value = "";
	foreach($content as $c)
		$value .= $c->content;
	return $value;
}

function get_attr_location($key,$node){

	while(list($o_key,$obj) = each($node->attributes)){
		if($obj->name == $key) {
			return $o_key;
		}
	}	

	return false;
}

function core_warn_attributes($node, $list) {
	if (!is_array($list))
		return;
	foreach ($list as $i) {
		if (get_attribute($i, $node)) {
			$name = $node->node_name();
			if ($node->prefix())
				$name = sprintf("%s:%s", $node->prefix(), $node->node_name());
			user_error(sprintf("Illegal attribute \"%s\" used on attribute %s", $i, $name));
		}
	}
}

/* }}} */

/* {{{ request handling auxiliaries */

function core_href_sprintf($str)
{
	$ret = $str;
	$v = array();
	while (ereg('{\$([A-Za-z_]+)}', $ret, $v)) {
		$pat = sprintf("{\\\$%s}", $v[1]);
		$rep = "";
		if (array_key_exists($v[1], $_REQUEST))
			$rep = $_REQUEST[$v[1]];
		$ret = ereg_replace($pat, $rep, $ret);
	}
	return $ret;
}

/* }}} */

/* debug function. pretty print variable info even with text/html */
function print_er($a, $prefix = ""){

	print "<pre>$prefix ";
	print_r($a);
	print "</pre>\n";
}	

/*
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
?>
