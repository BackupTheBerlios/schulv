<?
/* 
 * Copyright (C) 2002 edeal Schroeder & Maihoefer GbR
 * <code@edeal.de>, http://edeal.de/
 *
 * This file is subject to the GNU Lesser General Public License version 2.1;
 * see the LICENSE file for a copy of this license.
 */


$BASE_PATH = dirname($_SERVER['SCRIPT_FILENAME']);

// TODO: this global stuff could go away
$__core_system_data = array();
$__core_system_data['options'] = array();
$__core_system_data['tags'] = array();
$__core_system_data['dirs'] = array();
$__core_system_data['dirs']['modules'] = $BASE_PATH."/c0re/modules";


$includepath = split(":", ini_get("include_path"));
$includepath[] = $BASE_PATH;
$includepath[] = $__core_system_data['dirs']['modules'];
ini_set("include_path", join(":", $includepath));

core_load_module("html");
core_load_module("control");
core_load_module("formx");


/* core_load_module(string modulename)
   laedt die haupt-datei eines moduls.
   diese datei muss unter modules/<modulename>/<modulename>.class zu finden
   sein

   Achtung: da die Module innerhalb einer Funktion include'd werden, sind
	   natuerlich die gewohnten Schritte einzuleiten, falls man globale Variablen
       verwenden moechte
 */
function core_load_module($name) {
	$dir = $GLOBALS['__core_system_data']['dirs']['modules'];
	$fname = sprintf("%s/%s/%s.class", $dir, $name, $name);
	if (file_exists($fname)) {
		require_once($fname);
	}
	else user_error("[core] failed to load module \"$name\" (expected it in \"$fname\")");
}

function core_use_xsl($file) {
	global $__core_system_data;
	$__core_system_data['options']['xsl_template'] = $file;
}

function core_execute($file) {
	if (!file_exists($file)) {
		header("HTTP/1.0 404 Not Found");
		echo "<h2>404 File not found</h2>";
		echo "The file ".$_SERVER["PHP_SELF"]." was not found on this server";
		exit();
	}
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
	$root = $docTree->children[0];
	$content = xml_parse_from_array($root);

	global $__core_system_data;
	if (array_key_exists('xsl_template', $__core_system_data['options'])) {
		$xsldata = join("\n", file($__core_system_data['options']['xsl_template']));
		$args = array("/_xml" => $content, "/_xsl" => $xsldata);
		$xh = xslt_create();
//		print "XSL CONTENT = --$content--\n";
		$content = xslt_process($xh, "arg:/_xml", "arg:/_xsl", NULL, $args);
		if (!$content) {
			user_error("XSLT error: ".xslt_error($xh));
		}
		xslt_free($xh);
	}
	return $content;
}

function get_module_for_element($element){

		global $__core_system_data;	
		if (array_key_exists($element, $__core_system_data['tags']))
			return $__core_system_data['tags'][$element];
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

function xml_parse_from_array($input_tree){

	$tree = new_xmldoc("1.0");
	$root = xml_translate_tree($input_tree);
	if (is_object($root))
		$tree->set_root($root);
	else
		$tree->set_root(new_warning_node("Fatal. No root-node returned by xml_translate_tree()"));
	return $tree->dumpmem();
}

function xml_translate_tree($old_tree, $parent = false)
{
  	if($old_tree->has_child_nodes()) {
    	foreach($old_tree->children() as $current_child) {

			if ($current_child->type == XML_TEXT_NODE) {
				$new_node = $current_child->clone_node();
				if (get_class($parent) == "DomElement")
					$new_node = $parent->add_child($new_node);
			}
			else if ($current_child->type == XML_ELEMENT_NODE) {
				if($module = get_module_for_element($current_child->tagname))
					$handler = new $module;
				else
					$handler = new element; /* fallback default HTML handler */
		 
				/* der dispatcher hat entweder eine domxml-node oder ein array von
				   domxml-nodes zurueckzuliefern. wird ein array mit mehr als
				   einem element zurueckgeliefert, wenn noch keine root-node gesetzt ist,
				   wird das erste element des arrays als root-node gesetzt und
				   alle anderen elemente werden verworfen...
				 */
	      		$new_node = $handler->dispatch($current_child);
    
				if (is_object($new_node)) {
					switch($new_node->type) {
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
//					else
//						die("Oops. Parent node badly corrupted");
				}
			}
    	}
	}

	return $parent;
}

function new_warning_node($text, $system = "") {
	$node = domxml_node("b");
	$node->set_content(sprintf("%s Warning: %s", $system, $text));
	return $node;
}

function which_module($tag){

	global $__core_system_data;

	return $__core_system_data[elements][$tag];
}

function get_attribute($key,$node){

	foreach($node->attributes() as $attr){
		if($attr->name == $key){ 
			return $attr->value;
		}
	}

	return false;
}

function get_content($node){

	$content = array();
	if($node->has_child_nodes()) {
		foreach($node->children() as $child){
			if ($child->type == XML_TEXT_NODE){
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

function print_er($a, $prefix = ""){

	print "<pre>$prefix ";
	print_r($a);
	print "</pre>\n";
}	

?>
