<?php
/**
 * URL Routing framework
 * This file is part of menome engine
 * (C) 2011-2013 Manatsawin H.
 * 
 * menome engine is dual licensed under StealItPL 1.0 and AGPLv3
 * (or any later version of both license)
 * @license https://raw.github.com/whs/whs.github.com/master/LICENSE
 * @license https://www.gnu.org/licenses/agpl.html
 * @package Engine
 * @author whs
 * @since 21 Nov 2011
 */

class URLRouter{
	/**
	 * @param Array URL mapping. Supported formats: 
	 *        - array('regex' => array("file.php", array("cls", "method")))	> Call cls->init(); cls->method(); from file.php. If cls->init() is not exists then it is not called.
	 *        - array('regex' => array("file.php", "func"))					> Call func(); from file.php
	 *        - array('regex' => "file.php")								> Include file.php (deprecated)
	 * @param SmartyTemplateEngine Smarty object
	 * @param Array Mapping of class properties to set in the target class
	 * @param String Base include path
	 */
	public function __construct($url, $smarty=null, $passthru=null, $basepath=""){
		$this->url = $url;
		$this->smarty = $smarty;
		$this->passthru = $passthru;
		$this->basepath = $basepath;
	}
	/**
	 * Perform routing
	 */
	public function route(){
		global $phraw;
		$routeFoundWrongMethod = false;
		$routeFound = false;
		foreach($this->url as $k=>$v){
			$method = null;
			if(strstr($k, " ")){
				$k = explode(" ", $k);
				$method = $k[0];
				$k = implode(" ", array_slice($k, 1));
			}
			if(@$phraw->route($k) && ($method == null || $_SERVER['REQUEST_METHOD'] == $method)){
				if(is_array($v)){
					require $this->basepath.$v[0];
					if(is_array($v[1])){
						$this->obj = new $v[1][0]();
						$this->obj->smarty = $this->smarty;
						foreach($this->passthru as $o => $d){
							$this->obj->$o = $d;
						}
						if(method_exists($this->obj, "init")){
							call_user_func(array($this->obj, "init"));
						}
						call_user_func(array($this->obj, $v[1][1]));
					}else{
						call_user_func($v[1]);
					}
				}else{
					// Compat
					header("X-Warn: using compat layer");
					global $DB, $SMARTY, $current_user;
					require $this->basepath.$v;
				}
				$routeFound = true;
				$routeFoundWrongMethod = false;
				break;
			}else if(@$phraw->route($k)){
				$routeFoundWrongMethod = true;
			}
		}
		if(!$routeFound){
			$this->notfound();
		}
		if($routeFoundWrongMethod){
			$this->notallowed();	
		}
	}
	public function notfound(){
		if($this->smarty){
			$this->smarty->display_error(404);
		}else{
			header("Content-Type: application/json");
			header("HTTP/1.0 404 Not Found");
			print json_encode(array("error" => "Endpoint not found."));
		}
	}
	public function notallowed(){
		header("Content-Type: application/json");
		header("HTTP/1.0 405 Method Not Allowed");
		print json_encode(array("error" => "Method not allowed."));
	}
}