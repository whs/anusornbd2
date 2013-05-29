<?php
/**
 * This file is part of BD2Anusorn18
 * (C) 2012-2013 Manatsawin H.
 * 
 * @license AGPLv3
 * BD2Anusorn18 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BD2Anusorn18 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with BD2Anusorn18.  If not, see <http://www.gnu.org/licenses/>.
 */
 
require "engine/config.php";
require "engine/bootstrap.php";
require "engine/routing.php";

ob_start();
session_start();

$SMARTY->assign("year", YEAR);
$SMARTY->assign("referer", $_SERVER['HTTP_REFERER']);

$router = new URLRouter(array(
    ""                    		=> array("register.class.php", array("Register", "redirect")),
	"register[\/]{0,1}"			=> array("register.class.php", array("Register", "index")),
	"register\/add"	    		=> array("register.class.php", array("Register", "add")),
	"register\/stats"	    		=> array("register.class.php", array("Register", "stats")),
	"register\/stats\/([^\/]+)"	    => array("register.class.php", array("Register", "stats_uni")),
	"register\/_xhr\/faculty\/"	=> array("register.class.php", array("Register", "faculty")),
	"register\/_xhr\/student\/([0-9]+)"	=> array("register.class.php", array("Register", "stdinfo")),
	"register\/6\/([0-9]+)"		=> array("register.class.php", array("Register", "classinfo")),
	"register\/6\/([0-9]+)\/([0-9]+)\/history"	=> array("register.class.php", array("Register", "history")),
	"register\/([0-9]{5})\/@edit"	=> array("register.class.php", array("Register", "edit")),
	"register\/@import"			=> array("register.class.php", array("Register", "import")),
	"register\/@importfac"		=> array("register.class.php", array("Register", "importfac")),
	"register\/@import2"		=> array("register.class.php", array("Register", "import2")),
	"register\/@importedz"		=> array("register.class.php", array("Register", "importedz")),
	"register\/@addfac"			=> array("register.class.php", array("Register", "addfac")),
	"register\/@auth"			=> array("register.class.php", array("Register", "auth")),
	"register\/tx\/([0-9a-f]+)"		=> array("register.class.php", array("Register", "reg_edit")),
	"register\/replay"			=> array("register.class.php", array("Register", "replay")),
	"register\/@export"			=> array("register.class.php", array("Register", "export")),
	"register\/@exportfac"			=> array("register.class.php", array("Register", "exportfac")),
	"register\/@export2"			=> array("register.class.php", array("Register", "export2")),
	"register\/@exportnames"	=> array("register.class.php", array("Register", "exportnames")),
	"register\/_dllist"			=> array("register.class.php", array("Register", "dllist")),

	"address"					=> array("address.class.php", array("Address", "closed")),
	/*"address\/edit"				=> array("address.class.php", array("Address", "edit")),
	"address\/@logout"			=> array("address.class.php", array("Address", "logout")),*/
	"address\/stat"				=> array("address.class.php", array("Address", "stat"))
), $SMARTY, array(
	"phraw" => $phraw,
	"DB" => $DB,
));

try{
	$router->route();
}catch(Exception $e){
	ob_end_clean();
	header("Content-Type: text/plain");
	print_r($e);
	die();
}