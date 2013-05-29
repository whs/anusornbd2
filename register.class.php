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
class Register extends Base{
	public $entType = array(
		"exam" => "สอบตรง",
		"direct" => "รับตรง",
		"admission" => "แอดมิชชั่น",
		"quota" => "โควต้า"
	);
	public $uniGroup = array(
		"วิทยาศาสตร์สุขภาพ" => array("เภสัชศาสตร์", "สหเวชศาสตร์", "สัตวแพทยศาสตร์", "ทันตแพทยศาสตร์", "แพทยศาสตร์", "พยาบาลศาสตร์", "เทคนิคการสัตวแพทย์"),
		"วิทยาศาสตร์กายภาพ" => array("ทรัพยากรธรรมชาติ", "เทคโนโลยีสารสนเทศ", "วิทยาศาสตร์"),
		"วิศวกรรมศาสตร์" => array("การบิน", "วิศวกรรมศาสตร์"),
		"สถาปัตยกรรมศาสตร์" => array("สถาปัตยกรรมศาสตร์"),
		"เกษตรศาสตร์" => array("เกษตรศาสตร์", "ประมง"),
		"บริหาร พาณิชยศาสตร์ การบัญชี การท่องเที่ยวและการโรงแรม และเศรษฐศาสตร์" => array(array("การท่องเที่ยวและโรงแรม", "ท่องเที่ยว", "โรงแรม"), "บริหารธุรกิจ", "การบัญชี", "เศรษฐศาสตร์"),
		"ครุศาสตร์/ศึกษาศาสตร์" => array(array("ครุศาสตร์", "ศึกษาศาสตร์")),
		"ศิลปกรรม ดุริยางคศิลป์ และนาฎยศิลป์" => array("ศิลปกรรม", "ดุริยางคศิลป์", "นาฎยศิลป์"),
		"มนุษยศาสตร์และสังคมศาสตร์" => array(array("นิเทศศาสตร์", "วารสารศาสตร์"), array("อักษรศาสตร์", "ศิลปศาสตร์", "มนุษยศาสตร์"), "นิติศาสตร์", "รัฐศาสตร์"),
	);

	public function index(){
		$this->set_caching(24*60*60);
		$this->smarty->display("register/index.html");
	}
	/**
	 * Used during development when anusorn class is not available
	 */
	public function redirect(){
		$this->set_caching(24*60*60);
		header("Location: /register/");
	}
	public function classinfo(){
		$cls = (int) $this->phraw->request[1];
		$mem = $this->DB->students->find(array(
			"class" => $cls,
			"year" => YEAR
		));
		$mem->sort(array("no" => 1));
		$mem = iterator_to_array($mem);
		if(count($mem) == 0){
			$this->smarty->display_error(404);
			return;
		}

		$classMembers = array();
		foreach($mem as $m){
			$classMembers[] = $m['_id'];
		}

		$uni = $this->DB->register->find(array(
			"latest" => true,
			"student" => array('$in' => $classMembers)
		));
		foreach($uni as $item){
			$mem[$item['student']] = array_merge($item, $mem[$item['student']]);
		}
		$this->set_info($uni);

		$classDef = "";
		if($cls == 1){
			$classDef = "อังกฤษ - ฝรั่งเศส";
		}else if($cls == 2){
			$classDef = "อังกฤษ - จีน";
		}else if($cls == 3){
			$classDef = "อังกฤษ - ญี่ปุ่น";
		}else if($cls >= 4 && $cls <= 8){
			$classDef = "อังกฤษ - คณิตศาสตร์";
		}else{
			$classDef = "คณิตศาสตร์ - วิทยาศาสตร์";
		}
		$this->smarty->assign("member", $mem);
		$this->smarty->assign("class", $cls);
		$this->smarty->assign("classDef", $classDef);
		$this->smarty->assign("entType", $this->entType);
		$this->smarty->assign("auth", $_SESSION['register_auth'] === true);
		$this->set_caching();
		$this->smarty->display("register/class.html");
	}
	public function edit(){
		$this->require_auth();
		$who = (int) $this->phraw->request[1];
		if($_SERVER['REQUEST_METHOD'] == "POST"){
			$allowedFields = array("prefix", "name", "surname", "nick", "class", "no");
			$saveData = array();
			foreach($allowedFields as $f){
				$saveData[$f] = $_POST[$f];
			}
			$saveData['no'] = (int) $saveData['no'];
			$saveData['class'] = (int) $saveData['class'];
			$saveData["birthday"] = array((int) $_POST['birthday_0'], (int) $_POST['birthday_1'], (int) $_POST['birthday_2']);
			$this->DB->students->update(array("_id" => $who), array(
				'$set' => $saveData
			));
			$this->smarty->assign("success", true);
		}
		$person = $this->DB->students->findOne(array("_id" => $who));
		$_SESSION['adrid'] = $person['_id'];
		$_SESSION['adryr'] = $person['year'];
		$this->smarty->assign("person", $person);
		$this->smarty->display("register/edit.html");
	}
	public function add(){
		$error = null;
		if($_SERVER['REQUEST_METHOD'] == "POST"){
			$error = $this->save_add();
		}else{
			$this->set_caching(24*60*60);
		}
		$this->smarty->assign("error", $error);
		$this->smarty->assign("form", $_POST);
		$this->smarty->assign("unilist", $this->uni_list());
		$this->smarty->assign("auth", $_SESSION['register_auth'] === true);
		$this->smarty->display("register/add.html");
	}
	public function history(){
		$cls = (int) $this->phraw->request[1];
		$no = (int) $this->phraw->request[2];
		$student = $this->DB->students->findOne(array(
			"class" => $cls,
			"no" => $no,
			'year' => YEAR
		));
		if(!$student){
			$this->smarty->display_error(404);
			die();
		}
		$history = $this->DB->register->find(array(
			"student" => $student['_id'],
		));
		$history->sort(array("time" => 1));
		$this->set_info($history);
		$history = iterator_to_array($history);
		foreach($history as &$item){
			if(isset($item['fillerid'])){
				$item['fillerid'] = $this->DB->students->findOne(array(
					"_id" => (int) $item['fillerid']
				));
			}
		}
		$this->smarty->assign("person", $student);
		$this->smarty->assign("history", $history);
		$this->smarty->assign("entType", $this->entType);
		$this->smarty->assign("auth", $_SESSION['register_auth'] === true);
		$this->set_caching();
		$this->smarty->display("register/history.html");
	}
	public function reg_edit(){
		$this->require_auth();
		$who = new MongoId($this->phraw->request[1]);
		if($_SERVER['REQUEST_METHOD'] == "POST"){
			$allowedFields = array("student", "uni", "fac", "examtype", "fund", "latest");
			$saveData = array();
			foreach($allowedFields as $f){
				$saveData[$f] = $_POST[$f];
			}
			$saveData['student'] = (int) $saveData['student'];
			$saveData['fund'] = (bool) $saveData['fund'];
			$saveData['latest'] = (bool) $saveData['latest'];
			$this->DB->register->update(array("_id" => $who), array(
				'$set' => $saveData
			));
			$this->smarty->assign("success", true);
		}
		$reg = $this->DB->register->findOne(array("_id" => $who));
		$this->smarty->assign("reg", $reg);
		$this->smarty->display("register/reg_edit.html");
	}
	public function addfac(){
		$this->require_auth();
		if($_SERVER['REQUEST_METHOD'] == "POST"){
			$allowedFields = array("university", "name");
			$saveData = array();
			foreach($allowedFields as $f){
				$saveData[$f] = $_POST[$f];
			}
			$this->DB->faculty->insert($saveData);
			$this->smarty->assign("success", true);
		}
		$this->smarty->display("register/addfac.html");
	}
	public function stats(){
		// History: This used to be a mapreduce, until MongoDB 2.4
		// that db got removed.
		$uni = array();
		$fac = array();
		// optional query factor
		$query = array('latest' => true);
		if(isset($_GET['all'])){
			$query = array();
		}
		if(isset($_GET['examtype'])){
			$query['examtype'] = (string) $_GET['examtype'];
		}
		if(isset($_GET['fund'])){
			$query['fund'] = true;
		}
		if(isset($_GET['before'])){
			$query['time'] = array(
				'$lt' => new MongoDate(strtotime($_GET['before']))
			);
		}
		if(isset($_GET['after'])){
			$query['time'] = array(
				'$lt' => new MongoDate(strtotime($_GET['after']))
			);
		}
		$q = $this->DB->register->find($query);
		$this->set_info($q);
		foreach($q as $reg){
			$student = $this->DB->students->findOne(array('_id' => $reg['student']), array('prefix' => 1));
			$gender = $student['prefix'] == 'นาย' ? 'male' : 'female';
			$otherGender = $student['prefix'] != 'นาย' ? 'male' : 'female';
			$uni[$reg['uni']][$gender] += 1;
			$uni[$reg['uni']][$otherGender] += 0;
			$uni[$reg['uni']]['_id'] = $reg['uni'];
			$uni[$reg['uni']]['total'] += 1;
			$found = false;
			foreach($this->uniGroup as $kind => $names){
				foreach($names as $name){
					if(is_array($name)){
						foreach($name as $i){
							if(strpos($reg['fac'], $i) !== false){
								$found = true;
								break;
							}
						}
						$name = $name[0];
						//$name = implode(' / ', $name);
					}else{
						$found = strpos($reg['fac'], $name) !== false;
					}
					// special case!
					if(strpos($reg['fac'], 'เคมี') !== false && strpos($reg['fac'], 'วิศวกรรม') !== false){
						$name = 'วิศวกรรมศาสตร์';
					}
					if($found){
						$fac[$name]['_id'] = $name;
						$fac[$name][$gender] += 1;
						$fac[$name][$otherGender] += 0;
						$fac[$name]['total'] += 1;

						$fac['k_'.$kind]['_id'] = $kind;
						$fac['k_'.$kind][$gender] += 1;
						$fac['k_'.$kind][$otherGender] += 0;
						$fac['k_'.$kind]['total'] += 1;
						$fac['k_'.$kind]['master'] = true;
						$found = true;
						break;
					}
				}
				if($found){
					break;
				}
			}
		}
		// make the graph and table for unigroup
		$facMale = array();
		$facFemale = array();
		$uniGroup = array();
		foreach($this->uniGroup as $kind => $names){
			$facMale[] = $fac['k_'.$kind]['male'];
			$facFemale[] = $fac['k_'.$kind]['female'];
			if(isset($fac['k_'.$kind])){
				$uniGroup[] = $fac['k_'.$kind];
			}else{
				$uniGroup[] = array(
					'_id' => $kind,
					'male' => 0, 'female' => 0, 'total' => 0,
					'master' => true
				);
			}
			foreach($names as $name){
				if(is_array($name)){
					$name = $name[0];
					//$name = implode(' / ', $name);
				}
				if(isset($fac[$name])){
					$uniGroup[] = $fac[$name];
				}else{
					$uniGroup[] = array(
						'_id' => $name,
						'male' => 0, 'female' => 0, 'total' => 0,
					);
				}
			}
		}
		// sort uni results and get the results
		if(is_array($uni)){
			usort($uni, array($this, "sort_by_total"));
			$uni = array_reverse($uni);
			$uniID = array();
			$uniMale = array();
			$uniFemale = array();
			foreach(array_slice($uni, 0, 10) as $key => $item){
				$uniID[] = $item['_id'];
				$uniMale[] = $item['male'];
				$uniFemale[] = $item['female'];
			}
			$this->smarty->assign("uniID", $uniID);
			$this->smarty->assign("uniMale", $uniMale);
			$this->smarty->assign("uniFemale", $uniFemale);
		}
		$this->smarty->assign("uni", $uni);
		$this->smarty->assign("fac", $uniGroup);
		$this->smarty->assign("uniGroup", array_keys($this->uniGroup));
		$this->smarty->assign("facMale", $facMale);
		$this->smarty->assign("facFemale", $facFemale);
		$this->set_caching(60*60);
		$this->smarty->display("register/statshome.html");
	}
	/**
	 * Replay view
	 * @since 28 Mar 2013
	 */
	public function replay(){
		if(isset($_GET['json'])){
			header('Content-Type: application/json');
			$q = $this->DB->register->find();
			if($_GET['json'] != 'null'){
				$q->addOption('$min', array('_id' => new MongoId($_GET['json'])));
			}
			$q->sort(array('time' => 1));
			$out = array();
			$skipped = false;
			// find 30 items, and until the time seperate more than 3 days
			foreach($q as $item){
				if(!$skipped && $_GET['json'] != 'null'){
					$skipped=true;
					continue; // skip the first result as it will be duplicate from the last page
				}
				$item['student'] = $this->DB->students->findOne(array(
					'_id' => $item['student']
				), array('_id', 'name', 'prefix', 'surname', 'class'));
				$item['time'] = ($item['time']->sec * 1000) + $item['time']->usec;
				$item['_id'] = (string) $item['_id'];
				$out[] = $item;
				if(count($out) >= 30 && $item['time'] - $lastitem['time'] > 60000 * 60 * 24 * 3){
					break;
				}
				$lastitem = $item;
			}
			print json_encode($out);
			die();
		}
		$this->smarty->display("register/replay.html");
	}
	public function stats_uni(){
		$data = $this->DB->register->find(array(
			"uni" => (string) $this->phraw->request[1],
			"latest" => true
		));
		$this->set_info($data);
		if($data->count() == 0){
			$this->smarty->display_error(404);
			die();
		}
		$data = iterator_to_array($data);
		foreach($data as &$item){
			$item['student'] = $this->DB->students->findOne(array(
				"_id" => (int) $item['student']
			));
		}
		usort($data, array($this, "sort_by_class_no"));
		$this->smarty->assign("data", $data);
		$this->smarty->assign("entType", $this->entType);
		$this->set_caching(60*60);
		$this->smarty->display("register/stats_uni.html");
	}
	public function stdinfo(){
		header("Content-Type: text/plain; charset=UTF-8");
		$who = (int) $this->phraw->request[1];
		$person = $this->DB->students->findOne(array(
			"_id" => $who,
			"year" => YEAR
		));
		if($person){
			print $person['prefix']." ".$person['name']." ".$person['surname'];
			$this->set_caching(60*60*24);
		}else{
			header("X-DB-Status: 404");
			print "ไม่พบข้อมูล";
		}
	}
	public function faculty(){
		$uni = (string) $_GET['uni'];
		$fac = $this->DB->faculty->find(array("university" => $uni));
		$fac->sort(array("name" => 1));
		$this->set_info($fac);
		$out = array();
		foreach($fac as $f){
			$out[] = array(
				//"id" => (string) $f['_id'],
				"name" => $f['name']
			);
		}
		ob_start("ob_gzhandler");
		header("Content-Type: application/json");
		header("Cache-Control: max-age=".(60*60*24));
		header_remove("Expires");
		header_remove("Pragma");
		print json_encode($out);
	}
	/**
	 * Import data
	 * You should get a CSV with one line header
	 * and data should be in this format
	 * 6,1,1,12345,นาย,สมชาย,นามสมมุติ,03072555,1111111111113,ชาย
	 * (6/1 no. 1 student no. 12345 นาย สมชาย นามสมมุติ born on 3 Jul 2555 B.E. nickname ชาย)
	 */
	public function import(){
		$this->require_auth();
		header("Content-Type: text/plain");
		$fp = fopen("data.csv", "r");
		fgets($fp);
		while($l = fgets($fp)){
			$l = explode(",", trim($l));
			$birthday = $l[7];
			$birthday = array((int) substr($birthday, 0, 2), (int) substr($birthday, 2, 2), (int) substr($birthday, 4));
			$info = array(
				"class" => (int) $l[1],
				"no" => (int) $l[2],
				"_id" => (int) $l[3],
				"prefix" => $l[4],
				"name" => $l[5],
				"surname" => $l[6],
				"birthday" => $birthday,
				"tssn" => substr($l[8], -4),
				"nick" => $l[9],
				"year" => (int) YEAR
			);
			$this->DB->students->insert($info, array("safe" => true));
			print $info['_id']."\n";
		}
		print "\n\nDone.";
	}
	/**
	 * Import from new.csv, a tab-seperated file
	 * no stuid prefix name surname class
	 */
	public function import2(){
		$this->require_auth();
		header("Content-Type: text/plain");
		$fp = fopen("new.csv", "r");
		fgets($fp);
		while($l = fgets($fp)){
			$l = explode("\t", trim($l));
			if($l[5][0] != "3"){ // only import m.3
				continue;
			}
			$info = array(
				"class" => (int) substr($l[5], 1),
				"no" => (int) $l[0],
				"_id" => (int) $l[1],
				"prefix" => $l[2],
				"name" => $l[3],
				"surname" => $l[4],
				"year" => 21 // warning: hardcoded
			);
			$this->DB->students->insert($info, array("safe" => true));
			print $info['_id']."\n";
		}
		print "\n\nDone.";
	}
	/**
	 * Import admissions data from eduzone
	 * Format: tsv "index	admId	name	fac"
	 * File name: adm56.tsv
	 */
	public function importedz(){
		$this->require_auth();
		$fp = fopen("adm56.tsv", "r");
		header("Content-Type: text/plain");
		while(!feof($fp)){
			$l = explode("\t", trim(fgets($fp)));
			$name = explode(" ", $l[2]);
			$stu = $this->DB->students->findOne(array(
				"name" => $name[0],
				"surname" => $name[1]
			), array("_id"));
			if(!$stu){
				print "[Error] Cannot find ".implode(" ", $name)."\n";
				continue;
			}
			$q = $this->DB->register->find(array(
				"student" => $stu['_id'],
				'$or' => array(
					array("filler" => "self"),
					array("time" => array('$gt' => new MongoDate(strtotime("2013-05-01 07:00:00")) ))
				)
			));
			if($q->count() > 0){
				print "[Warn] Didn't import ".implode(" ", $name).": already filled\n";
				continue;
			}
			$this->DB->register->update(array(
				'student' => $stu['_id'],
				'latest' => true
			), array(
				'$set' => array(
					'latest' => false
				)
			));
			$unidata = explode(" ", $l[3]);
			$uni = $unidata[0];
			$fac = implode(" ", array_slice($unidata, 1));
			$this->DB->register->insert(array(
				"student" => $stu['_id'],
				"uni" => $uni,
				"fac" => $fac,
				"filler" => "adm",
				"time" => new MongoDate(),
				"examtype" => "admission",
				"fund" => false,
				"latest" => true
			));
			print "[OK] Imported ".$stu['_id']."\n";
		}
		print 'Import process done.';
	}
	/**
	 * Import faculty data from anusorn17
	 */
	public function importfac(){
		$this->require_auth();
		header("Content-Type: text/plain");
		$fac = json_decode(file_get_contents("faculty.json"));
		$this->DB->faculty->ensureIndex(array("university" => 1, "name" => 1), array("unique" => 1));
		foreach($fac as $item){
			$this->DB->faculty->insert($item);
			print json_encode($item)."\n";
		}
	}
	/**
	 * Hacky auth. Use ?auth=password to sign in
	 */
	public function auth(){
		global $PASSWORD;
		if($_GET['auth'] == $PASSWORD){
			$_SESSION['register_auth'] = true;
			print "Login OK!";
		}else{
			$this->smarty->display_error(403);
		}
	}

	public function export(){
		$this->require_auth();
		header('Content-Type: text/csv');
		$year = YEAR;
		if($_GET['year']){
			$year = (int) $_GET['year'];
		}
		$q = $this->DB->students->find(array("year"=> $year));
		$q->sort(array('class' => 1, '_id' => 1));
		$cols = explode(',', 'class,no,prefix,name,surname,nick,birthday,address,telmobile,twitter,facebook,email,msn,line');
		print implode(',', $cols).",innewformat\n";
		foreach($q as $stu){
			$out = array();
			$addr = $this->DB->address->findOne(array(
				"student" => $stu['_id'],
				"latest" => true
			));
			foreach($cols as $col){
				$data = $stu[$col];
				if(isset($addr[$col])){
					$data = $addr[$col];
				}
				if($col == 'birthday' && is_array($data)){
					$out[] = '"'. implode('/', $data) .'"';
				}else{
					$out[] = '"'. $data . '"';
				}
			}
			print implode(',', $out).",".($data?'"true"':'')."\n";
		}
	}

	/**
	 * Export2: Export names with university data
	 * Format: csv
	 * stuid,prefix,name,surname,class,uni,fac,filler,otherCnt
	 * otherCnt: how many other records are filled by this user
	 */
	public function export2(){
		$this->require_auth();
		header('Content-Type: text/csv');
		$q = $this->DB->register->find(array('latest' => true));
		foreach($q as $item){
			$student = $this->DB->students->findOne(array(
				'_id' => $item['student']
			));
			$others = $this->DB->register->find(array(
				'student' => $item['student'],
				'latest' => false
			))->count();
			$out = array(
				$item['student'],
				$student['prefix'],
				$student['name'],
				$student['surname'],
				'6/'.$student['class'],
				$item['uni'],
				$item['fac'],
				$item['filler'] == "other" ? $item['fillerid'] : $item['filler'],
				$others
			);
			foreach($out as &$item){
				$item = '"'.$item.'"';
			}
			echo implode(',', $out), "\n";
		}
	}

	/**
	 * Export faculty list in the format used by importfac
	 * @see importfac
	 */
	public function exportfac(){
		$this->require_auth();
		$out = array();
		foreach($this->DB->faculty->find() as $item){
			$out[] = array(
				'university' => $item['university'],
				'name' => $item['name']
			);
		}
		header('Content-Type: application/json');
		echo json_encode($out);
	}

	/** 
	 * Export as xlsx for eduzone data requesting
	 * http://ez.eduzones.com/admissions56_result/admis51_result_school.php
	 */
	public function exportnames(){
		$this->require_auth();
		require_once "lib/PHPExcel.php";
		PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_apc);
		$xlsx = new PHPExcel();
		$xlsx->createSheet();
		$xlsx->createSheet(); // seems that eduzone parse on second worksheet
		$sheet = $xlsx->getActiveSheet();
		$students = $this->DB->students->find(array("year" => YEAR));
		$ind = 1;
		foreach($students as $s){
			$sheet->getCell("A".$ind)->setValue($s['name']." ".$s['surname']);
			$ind++;
		}
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="students.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($xlsx, 'Excel5');
		$objWriter->save('php://output');
	}

	public function dllist(){
		$out = array(
			array('register', true),
			array('register/stats', true),
		);
		foreach($this->DB->students->distinct('class', array('year' => YEAR)) as $cls){
			$out[] = array('register/6/'.$cls, true);
		}
		foreach($this->DB->students->find(array('year' => YEAR)) as $stu){
			$out[] = array('register/6/'.$stu['class'].'/'.$stu['no'].'/history', false);
		}
		foreach($this->DB->register->distinct('uni', array('latest' => true)) as $uni){
			$out[] = array('register/stats/'.urlencode($uni), false, 'register/stats/'.$uni);
		}

		header('Content-Type: text/plain');
		foreach($out as $i){
			$outpath = $i[0];
			if(isset($i[2])){
				$outpath = $i[2];
			}
			if($i[1]){
				$outpath .= '/index.html';
			}
			print 'mkdir -p '.dirname($outpath)."\n";
			print 'wget \'http://'.$_SERVER['SERVER_NAME'].'/'.$i[0].'\' -O \''.$outpath."'\n";
		}
	}
	
	public function require_auth(){
		if($_SESSION['register_auth'] !== true){
			$this->smarty->display_error(403);
			die();
		}
	}

	public function uni_list(){
		return $this->DB->faculty->distinct("university");
	}

	public function sort_by_total($a, $b){
		if($a['total'] < $b['total']){
			return -1;
		}else if($a['total'] > $b['total']){
			return 1;
		}
		return $this->sort_by_name($a, $b);
	}
	public function sort_by_name($a, $b){
		return strcmp($a['_id'], $b['_id']);
	}
	public function sort_by_class_no($a, $b){
		if($a['class'] < $b['class']){
			return -1;
		}else if($a['class'] > $b['class']){
			return 1;
		}else if($a['no'] < $b['no']){
			return -1;
		}else if($a['no'] > $b['no']){
			return 1;
		}else{
			return 0;
		}
	}

	public function save_add(){
		$required = array("id", "uni", "filler");
		foreach($required as $item){
			if(!array_key_exists($item, $_POST) || empty($_POST[$item])){
				return "กรอกข้อมูลไม่ครบ";
			}
		}

		if($_POST['filler'] == "self" && empty($_POST['tssn'])){
			return "กรอกข้อมูลไม่ครบ";
		}else if($_POST['filler'] == "other" && empty($_POST['fillerid'])){
			return "กรอกข้อมูลไม่ครบ";
		}else if(!in_array($_POST['filler'], array("self", "other"))){
			return "Invalid post data";
		}else if(!in_array($_POST['examtype'], array_keys($this->entType))){
			return "Invalid post data";
		}

		$student = $this->DB->students->findOne(array(
			"_id" => (int) $_POST['id'],
			"year" => YEAR
		), array("_id", "tssn", "locked"));
		if(!$student){
			return "ไม่พบนักเรียนเลขประจำตัวนี้";
		}
		if($student['locked']){
			return 'ข้อมูลนักเรียนถูกล็อค กรุณาแจ้งในกรุ๊ปรุ่นหรือผู้ดูแลระบบ';
		}
		if(!empty($_POST['tssn']) && $_POST['tssn'] != $student['tssn']){
			return "เลขบัตรประชาชนไม่ถูกต้อง";
		}

		if($_POST['fillerid']){
			if($_POST['fillerid'] == $_POST['id']){
				$_POST['filler'] = "self"; // form hack
				return "กรุณาใช้ระบบกรอกด้วยตัวเอง";
			}
			$filler = $this->DB->students->findOne(array("_id" => (int) $_POST['fillerid']), array("_id"));
			if(!$filler){
				return "ไม่พบนักเรียนเลขประจำตัวนี้";
			}
		}

		$uni = (string) $_POST['uni'];
		$fac = (string) $_POST['fac'];
		if($uni == "other"){
			$uni = $_POST['otheruni'];
			$fac = $_POST['otherfac'];
			if(empty($uni)){
				return "ไม่ได้ระบุชื่อมหาวิทยาลัย";
			}
			if(empty($fac)){
				return "ไม่ได้ระบุชื่อคณะ";
			}
		}
		if(empty($uni) || empty($fac)){
			return "ไม่ได้ระบุชื่อคณะ";
		}

		$saveData = array(
			"student" => (int) $_POST['id'],
			"uni" => $uni,
			"fac" => $fac,
			"filler" => $_POST['filler'],
			"time" => new MongoDate(),
			"examtype" => $_POST['examtype'],
			"fund" => $_POST['fund'] == "Y",
			"latest" => true
		);

		if($_POST['fillerid']){
			$saveData['fillerid'] = (int) $_POST['fillerid'];
		}

		if($_POST['filler'] == "self"){
			/*$fields = array("twitter", "facebook", "tel", "telmobile", "email", "msn", "line", "menome", "address");
			$setData = array();
			foreach($fields as $field){
				if(!empty($_POST[$field])){
					if(in_array($field, array("twitter", "menome", "line"))){
						if(mb_strlen($_POST[$field], "UTF-8") > 16){
							return "ความยาวช่อง ".ucfirst($field)." ยาวเกินไป";
						}
					}else if(in_array($field, array("email", "msn"))){
						if(!filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)){
							return "ช่อง ".ucfirst($field)." ไม่ถูกต้อง";
						}
					}else if($field == "facebook"){
						if(!filter_var($_POST[$field], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
							return "ช่อง ".ucfirst($field)." ไม่ถูกต้อง";
						}
					}else if(in_array($field, array("tel", "telmobile"))){
						if(!preg_match('~^0[0-9]{8,9}$~', $_POST[$field])){
							return "ช่อง ".ucfirst($field)." ไม่ถูกต้อง";
						}
					}
					$setData[$field] = $_POST[$field];
				}
			}
			if(count($setData) > 0){
				$this->DB->students->update(array(
					"_id" => (int) $_POST['id']
				), array(
					'$set' => $setData
				));
			}*/
		}

		$this->DB->register->update(array(
			"student" => (int) $_POST['id'],
			"latest" => true
		), array(
			'$set' => array(
				'latest' => false
			)
		));
		$this->DB->register->insert($saveData);
		$_SESSION['message'] = array("success", "บันทึกข้อมูลแล้ว");
		header("Location: /register/");
	}

	public function set_info($q){
		$explain = $q->explain();
		$this->smarty->assign('coninfo', array(str_replace('.whs.in.th', '', $explain['server'])));
	}
	public function set_caching($max_age=600){
		header_remove("Expires");
		header_remove("Pragma");
		header("Cache-Control: public, max-age=".$max_age);
		$this->smarty->assign('cacheinfo', $max_age/60);
	}
}