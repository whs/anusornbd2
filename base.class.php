<?php
/**
 * This file is part of menome engine
 * (C) 2011-2013 Manatsawin H.
 * 
 * menome engine is dual licensed under StealItPL 1.0 and AGPLv3
 * (or any later version of both license)
 * @license https://raw.github.com/whs/whs.github.com/master/LICENSE
 * @license https://www.gnu.org/licenses/agpl.html
 */

class Base{
	/**
	 * The current user as object.
	 * null if not logged in.
	 * Note that this is automatically set by the `init()` method
	 */
	public $user = null;
	/**
	 * The Smarty template engine. Usually sets by `URLRouter`
	 * @type Smarty
	 */
	public $smarty;
	/**
	 * Load user data, setup error handlers
	 * This method is automatically called from `URLRouter`.
	 * Make sure subclass calls `parent::init();` too!
	 */
	public function init(){
		global $MONGO;
		$this->load_user();
		$this->smarty->assign("message", $_SESSION['message']);
		unset($_SESSION['message']);
		/*$conn = $MONGO->getConnections();
		$con = array();
		foreach($conn as $c){
			$host = str_replace('.whs.in.th', '', $c['server']['host']);
			$con[] = $host . ' ' . $c['connection']['connection_type_desc'];
		}
		$this->smarty->assign('coninfo', $con);*/
	}
	/**
	 * Loads the user data and set $this->user
	 * This function should not be called after `init()` have been fired
	 * @throws RuntimeException If the user is already loaded
	 * @see reload_user
	 */
	public function load_user(){
		if($this->user){
			throw new RuntimeException("Use reload_user instead!");
		}
		if(!$_COOKIE['id']){
			return false;
		}
		if(!$_COOKIE['secure']){
			setcookie("id", "", 1);
			return false;
		}
		// check the cookie integrity
		if($_COOKIE['secure'] != hash_hmac("sha256", $_COOKIE['id'], SITE_KEY)){
			setcookie("id", "", 1);
			setcookie("secure", "", 1);
			return false;
		}
		$this->user = array("_id" => $_COOKIE['id']);
		$this->smarty->assign("user", $this->user);
		$this->reload_user();
	}
	public function set_user($uid){
		setcookie("id", $uid, time()+60*60*24*30);
		setcookie("secure", hash_hmac("sha256", $uid, SITE_KEY), time()+60*60*24*30);
	}
	/**
	 * Load or reload the user data. Called automatically by `init()`.
	 * Call this function manually when you have changed the user's data in the database
	 */
	public function reload_user(){
		global $current_user;
		$this->user = $this->DB->users->findOne(array("_id" => $this->user['_id']));
		$current_user = $this->user;

		if($this->smarty){
			$this->smarty->assign("user", $this->user, true);
		}
	}
	public function check_login($redirect=true){
		if(!$this->user){
			if($redirect){
				header("Location: /auth");
				die();
			}else{
				$this->fatal_error("User not logged in");
			}
		}
	}
	/**
	 * Make an HTTP request
	 * Copied from twitteroauth.php
	 *
	 * @return API results
	 */
	function http($url, $method="GET", $postfields = NULL, $agent="Kyou/1.0") {
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $agent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ci, CURLOPT_TIMEOUT, 30);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, true);

		switch ($method) {
		  case 'POST':
			curl_setopt($ci, CURLOPT_POST, TRUE);
			if (!empty($postfields)) {
			  curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
			}
			break;
		  case 'DELETE':
			curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
			if (!empty($postfields)) {
			  $url = "{$url}?{$postfields}";
			}
		}

		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		curl_close ($ci);
		return $response;
	}
	public function fatal_error($msg){
		header("Content-Type: application/json");
		header("HTTP/1.0 400 Bad Request");
		print json_encode(array(
			"error" => $msg
		));
		die();
	}
}