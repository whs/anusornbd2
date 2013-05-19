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

require_once "base.class.php";

class Address extends Base{
	public function closed(){
		$this->smarty->display("address/closed.html");
	}
	public function login(){
		if($this->_check_login()){
			header("Location: /address/edit");
			return;
		}
		if(!empty($_POST['id'])){
			$user = $this->DB->students->findOne(array(
				'_id' => (int) $_POST['id'],
				//'tssn' => (string) $_POST['pw']
			));
			if($user && (($user['year'] == YEAR && $user['tssn'] == (string) $_POST['pw']) || ($user['year'] != YEAR))){
				$_SESSION['adrid'] = $user['_id'];
				$_SESSION['adryr'] = $user['year'];
				header("Location: /address/edit");
				return;
			}else{
				$this->smarty->assign("error", "เลขประจำตัว หรือเลขบัตรประชาชนผิด");
			}
		}
		$this->smarty->display("address/login.html");
	}
	public function edit(){
		$user = $this->_check_login();
		if(!$user){
			header("Location: /address");
			return;
		}
		if(!empty($_POST['prefix'])){
			$savefields = array("prefix", "name", "surname", "nick", "birthday", "address", "telmobile");
			$saveOK = true;
			$savedata = array(
				"student" => $_SESSION['adrid'],
				"latest" => true,
				"year" => (int) $_SESSION['adryr']
			);
			foreach($savefields as $f){
				if(empty($_POST[$f])){
					$this->smarty->assign("error", "กรอกข้อมูลไม่ครบในช่อง ".$f." กรุณากรอกใหม่");
					$saveOK=false;
					break;
				}else if($f == 'prefix' && !in_array($_POST['prefix'], array('นาย', 'น.ส.', 'ด.ช.', 'ด.ญ.'))){
					$this->smarty->assign("error", "Bad prefix");
					$saveOK=false;
					break;
				}else{
					if($f == "birthday"){
						$savedata[$f] = array_reverse(explode("-", $_POST[$f]));
						$savedata[$f][2] += 543;
					}else{
						$savedata[$f] = $_POST[$f];
					}
				}
			}
			if($saveOK){
				$this->DB->address->update(array(
					"student" => $_SESSION['adrid'],
					"latest" => true
				), array(
					'$set' => array(
						'latest' => false
					)
				), array("multiple" => true));
				$this->DB->address->insert($savedata);
				$this->smarty->assign("info", "บันทึกข้อมูลแล้ว");
			}
		}
		$userData = $this->_load_data($user);
		$this->smarty->assign("user", $user);
		$this->smarty->assign("data", $userData);
		$this->smarty->display("address/edit.html");
	}
	public function stat(){
		$newfmt = $this->DB->address->find(array(
			"latest"=>true,
		), array('student'));
		$oldfmt = $this->DB->students->find(array(
			'$or' => array(
				array("address"=>array('$exists'=>true, '$ne' => '')),
				array("tel"=>array('$exists'=>true, '$ne' => '')),
				array("telmobile"=>array('$exists'=>true, '$ne' => '')),
			),
		), array('_id'));
		$entered = array();
		$nw = array();
		$ol = array();
		foreach($newfmt as $u){
			$entered[] = $u['student'];
			$nw[] = $u['student'];
		}
		foreach($oldfmt as $u){
			$entered[] = $u['_id'];
			$ol[] = $u['_id'];
		}
		$entered = array_unique($entered);
		$cnt = count($entered);
		$oldOnly = array_diff($ol, $nw);

		$names = $this->DB->students->find(array(
			'_id' => array('$in' => $entered),
		));
		$names->sort(array('year'=>1,'class' => 1, 'no' => 1));

		$stucnt = $this->DB->students->find()->count();
		$this->smarty->assign("cnt", $cnt);
		$this->smarty->assign("stucnt", $stucnt);
		$this->smarty->assign("oldonly", $oldOnly);
		$this->smarty->assign("percent", $cnt*100/$stucnt);
		$this->smarty->assign("names", $names);
		$this->smarty->display("address/stat.html");
	}
	public function logout(){
		$_SESSION['adrid'] = null;
		header("Location: /address");
	}
	/**
	 * Load user data from either addressdb or anusorndb
	 */
	public function _load_data($user){
		$out = array();
		if($user){
			$fields = array("prefix", "name", "surname", "nick", "birthday", "twitter", "facebook", "tel", "telmobile", "email", "msn", "line", "address");
			foreach($fields as $f){
				if(isset($user[$f])){
					$out[$f] = $user[$f];
				}
			}
		}
		$data = $this->DB->address->findOne(array(
			"student" => $_SESSION['adrid'],
			"latest" => true
		));
		if(is_array($data)){
			$out = array_merge($out, $data);
		}
		return $out;
	}
	public function _check_login(){
		if(!isset($_SESSION['adrid'])){
			return false;
		}
		return $this->DB->students->findOne(array(
			"_id" => $_SESSION['adrid']
		));
	}
}