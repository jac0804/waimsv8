<?php

namespace App\Http\Classes;

/*
use Session;*/

use PDF;
use Mail;
use App\Mail\SendMail;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

//use Illuminate\Http\Request;
use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\menusetup;
use App\Http\Classes\menusetupclient;
use App\Http\Classes\sbcdb\login;
use App\Http\Classes\sbcdb\setprefixdoc;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\modules\customerservice\ca;
use App\Http\Classes\selectClass;

use Exception;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Throwable;
use Session;



class moduleClass
{
	private $othersClass;
	private $coreFunctions;
	private $headClass;
	private $logger;
	private $lookupClass;
	private $companysetup;
	private $config = [];
	private $sqlquery;
	private $menusetup;
	private $menusetupclient;
	private $loginupdate;
	private $setprefixdoc;
	private $fieldClass;
	private $selectClass;

	public function __construct()
	{
		$this->othersClass = new othersClass;
		$this->coreFunctions = new coreFunctions;
		$this->headClass = new headClass;
		$this->logger = new Logger;
		$this->lookupClass = new lookupClass;
		$this->companysetup = new companysetup;
		$this->sqlquery = new sqlquery;
		$this->menusetup = new menusetup;
		$this->menusetupclient = new menusetupclient;
		$this->loginupdate = new login;
		$this->setprefixdoc = new setprefixdoc;
		$this->fieldClass = new txtfieldClass;
		$this->selectClass = new selectClass;
	}

	public function seenMsg($data)
	{
		$this->coreFunctions->execqry("update privatechat set isSeen=1 where line=?", 'update', [$data['line']]);
	}

	public function getUsersList($data)
	{
		$users = $this->coreFunctions->opentable("select u.username as username2, u.name as username, md5(u.userid) as userid from useraccess as u");
		if (count($users) > 0) {
			foreach ($users as $user) {
				$user->hasMsg = false;
				$user->isOnline = false;
				$msg = $this->coreFunctions->datareader("select count(*) as value from privatechat where `from`=? and `to`=? and isSeen=0", [$user->userid, $data['userid']]);
				if ($msg > 0) $user->hasMsg = true;
			}
		}
		return ['users' => $users];
	}

	public function getGCmsg($data)
	{
		$lastid = 0;
		$latestid = $data['latestid'];
		$latestmsg = [];
		$firstid = $this->coreFunctions->datareader("select line as value from groupchat where roomname=? order by line asc limit 1", [$data['roomname']]);
		if ($firstid == '') $firstid = 0;
		if ($data['lastid'] == 0) {
			$msg = $this->coreFunctions->opentable("select * from (select * from groupchat where roomname=? order by line desc limit 15) as tbl order by line asc", [$data['roomname']]);
			if (count($msg) > 0) {
				$latestid = end($msg);
				$latestid = $latestid->line;
				$lastid = $msg[0]->line;
			}
		} else {
			$latestmsg = $this->coreFunctions->opentable("select * from groupchat where roomname=? and line>? order by line desc", [$data['roomname'], $data['latestid']]);
			if (count($latestmsg) > 0) $latestid = $latestmsg[0]->line;
			$msg = $this->coreFunctions->opentable("select * from groupchat where roomname=? and line<? order by line desc limit 15", [$data['roomname'], $data['lastid']]);
			if (count($msg) > 0) {
				$lastid = end($msg);
				$lastid = $lastid->line;
			}
		}
		return ['msg' => $msg, 'latestmsg' => $latestmsg, 'lastid' => $lastid, 'latestid' => $latestid, 'firstid' => $firstid];
	}

	public function getPMmsg($data)
	{
		$lastid = 0;
		$latestid = $data['latestid'];
		$latestmsg = [];
		$this->coreFunctions->execqry("update privatechat set isSeen=1 where `from`=? and `to`=?", 'update', [$data['user2'], $data['user1']]);
		$firstid = $this->coreFunctions->datareader("select line as value from privatechat where (`from`=? and `to`=?) or (`from`=? and `to`=?) order by line asc limit 1", [$data['user1'], $data['user2'], $data['user2'], $data['user1']]);
		if ($firstid == '') $firstid = 0;
		if ($data['lastid'] == 0) {
			$msg = $this->coreFunctions->opentable("select * from (select * from privatechat where (`from`=? and `to`=?) or (`from`=? and `to`=?) order by line desc limit 15) as tbl order by line asc", [$data['user1'], $data['user2'], $data['user2'], $data['user1']]);
			if (count($msg) > 0) {
				$latestid = end($msg);
				$latestid = $latestid->line;
				$lastid = $msg[0]->line;
			}
		} else {
			$latestmsg = $this->coreFunctions->opentable("select * from privatechat where ((`from`=? and `to`=?) or (`from`=? and `to`=?)) and line>? order by line desc", [$data['user1'], $data['user2'], $data['user2'], $data['user1'], $data['latestid']]);
			if (count($latestmsg) > 0) $latestid = $latestmsg[0]->line;
			$msg = $this->coreFunctions->opentable("select * from privatechat where ((`from`=? and `to`=?) or (`from`=? and `to`=?)) and line<? order by line desc limit 15", [$data['user1'], $data['user2'], $data['user2'], $data['user1'], $data['lastid']]);
			if (count($msg) > 0) {
				$lastid = end($msg);
				$lastid = $lastid->line;
			}
		}
		return ['msg' => $msg, 'latestmsg' => $latestmsg, 'lastid' => $lastid, 'latestid' => $latestid, 'firstid' => $firstid];
	}

	public function viberbot($data)
	{
		switch ($data['event']) {
			case 'message':

				$sender = $data['sender'];
				$message = $data['message'];

				$check = $this->coreFunctions->datareader("select id as value from viberid where id=?", [$data['sender']['id']]);
				if ($check == '') {
					$waw = $this->coreFunctions->execqry("insert into viberid(id, name) values('{$data['sender']['id']}', '{$data['sender']['name']}')", 'insert');
				}

				//API Url
				$url = 'https://chatapi.viber.com/pa/send_message';
				//Initiate cURL.
				$ch = curl_init($url);
				// The JSON data.

				$d = [
					'receiver' => $sender['id'],
					'sender' => [
						'name' => 'Solutionbase Corp'
					],
					'tracking_data' => 'tracking data',
					'type' => 'text',
					'text' => 'Waimsv2, Hello ' . $sender['name'] . ' You sent message:' . $message['text']
				];
				$jsonData = json_encode($d);

				//Encode the array into JSON.
				$jsonDataEncoded = $jsonData;
				//Tell cURL that we want to send a POST request.
				curl_setopt($ch, CURLOPT_POST, 1);
				//Attach our encoded JSON string to the POST fields.
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
				//Set the content type to application/json
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Viber-Auth-Token: 4d608cfb9b27d279-738bebdd738f46d0-a81ac346e1fac1b6'));
				//Execute the request
				if (!empty($message['text'])) {
					$result = curl_exec($ch);
				}
				return json_decode($result);
				break;
			case 'subscribed':

				$this->coreFunctions->execqry("insert into viberid(id, name) values('{$data['user']['id']}', '{$data['user']['name']}')", 'insert');
				break;
			case 'conversation_started':
				$check = $this->coreFunctions->datareader("select id as value from viberid where id='{$data['user']['id']}'");
				if ($check == '') {
					$this->coreFunctions->execqry("insert into viberid(id, name) values('{$data['user']['id']}', '{$data['user']['name']}')", 'insert');
				}
				break;
			case 'unsubscribed':

				$this->coreFunctions->execqry("delete from viberid where id='{$data['user_id']}'", 'delete');
				break;
		}
	}

	public function setviberwebhook($params)
	{
		$webhook = $params['webhook'];
		$url = 'https://chatapi.viber.com/pa/set_webhook';
		//Initiate cURL.
		$ch = curl_init($url);
		// The JSON data.
		$d = [
			'url' => $params['webhook'],
			'event_types' => [
				'delivered',
				'seen',
				'faiiled',
				'subscribed',
				'unsubscribed',
				'conversation_started'
			],
			'send_name' => true,
			'send_photo' => true
		];
		$jsonData = json_encode($d);

		//Encode the array into JSON.
		$jsonDataEncoded = $jsonData;
		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);
		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
		//Set the content type to application/json
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Viber-Auth-Token: 4d608cfb9b27d279-738bebdd738f46d0-a81ac346e1fac1b6'));
		//Execute the request
		$result = curl_exec($ch);
		// return json_decode($result);
	}

	public function sendvibermsg($params)
	{
		$url = 'https://chatapi.viber.com/pa/send_message';
		//Initiate cURL.
		$ch = curl_init($url);
		// The JSON data.
		$d = [
			'receiver' => $params['user'],
			'sender' => [
				'name' => 'Solutionbase Corp'
			],
			'tracking_data' => 'tracking data',
			'type' => 'text',
			'text' => $params['msg']
		];
		$jsonData = json_encode($d);

		//Encode the array into JSON.
		$jsonDataEncoded = $jsonData;
		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);
		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
		//Set the content type to application/json
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Viber-Auth-Token: 4d608cfb9b27d279-738bebdd738f46d0-a81ac346e1fac1b6'));
		//Execute the request
		$result = curl_exec($ch);
		// return json_decode($result);
	}

	public function getviberusers()
	{
		$users = $this->coreFunctions->opentable("select id, name from viberid");
		return json_encode($users);
	}

	public function escposprintsample($params)
	{
		// escpos documentation
		// https://github.com/mike42/escpos-php

		// share printer to network first.
		if ($params['printer'] != '') {
			$connector = new WindowsPrintConnector("smb:" . $params['printer']); // smb://computername/printername
			//$connector = new WindowsPrintConnector($params['printer']);
			$printer = new Printer($connector);

			// initialize printer
			$printer->initialize();

			// set text to print
			$printer->text("Hello World!");
			$printer->setBarcodeHeight(8);
			$printer->barcode("ABC", Printer::BARCODE_CODE39);

			// feed printer
			$printer->feed(1);

			// close printer
			$printer->close();
		}
	}




	public function login($params)
	{
		$this->loginupdate->onloadtableupdate();
		if ($params['companyid'] == 56) { //homeworks
			$this->loginupdate->updateyearprefix();
		}
		$username = $params['username'];
		$password = $params['password'];
		$logintype = 'users';
		$ismobile = false;

		$errmsg_time = '';
		$msg = 'Error Login 1';

		if (isset($params['mobile'])) $ismobile = $params['mobile'];
		$log = $this->othersClass->checkUseraccess($username, $password);
		if (!$log['status']) {
			$errmsg_time = isset($log['errmsg_time']) ? $log['errmsg_time'] : '';

			if ($errmsg_time == '') {
				$log = $this->othersClass->checkclientaccess($username, $params['pwd']);
				$logintype = 'client';
			} else {
				$msg = $errmsg_time;
			}
		}
		if ($log['status']) {
			if ($params['companyid'] == 28) $this->updateSysDate($params); //xcomp
			$this->loginupdate->adminlogs($params['username']);

			$params['levelid'] = $this->othersClass->getAccessLevel($params['username']);
			return $this->userscredential($params, $log['data'], $logintype);
		} else {
			if ($params['companyid'] == 58) {
				$log = $this->othersClass->checkapplicantaccess($username, $params['pwd']);
				if ($log['status']) {
					$this->loginupdate->adminlogs($params['username']);
					$params['levelid'] = $this->othersClass->getAccessLevel($params['username']);
					$logintype = 'applicant';
					return $this->userscredential($params, $log['data'], $logintype);
				} else {
					$this->logger->sbciplog('LOG-FAIL', $params['ip'], $params['username']);
					return response()->json(['status' => false, 'msg' => $msg], 200);
				}
			} else {
				$this->logger->sbciplog('LOG-FAIL', $params['ip'], $params['username']);
				return response()->json(['status' => false, 'msg' => $msg], 200);
			}
		}
	}

//fpy 3.2.2026
	public function checkrestrictip_uponlogin($params){
		if ($this->companysetup->getrestrictip($params)) {
			$ipaccess = $params['levelid'][0]['attributes'][3722]; //restrict ip access
			if ($ipaccess == 1) {
				$params['allowlogin'] = $this->othersClass->checkip($params);
				if (!$params['allowlogin']) {
					$$params['msg'] = 'RESTRICTED IP, pls inform admin';
				}
				$this->coreFunctions->LogConsole("Your IP - '" . $params['ip'] . "'");
			} else {
				$params['allowlogin'] = true;
			}
		}
		return $this;
	}


	public function updatesysdate($params)
	{
		if ($params['companyid'] == 28) { //xcomp
			$datenow = date('Y-m-d', strtotime('-30 day', strtotime($this->othersClass->getCurrentDate())));
		} else {
			$datenow = $this->othersClass->getCurrentDate();
		}
		$status = $this->coreFunctions->sbcupdate("profile", ["pvalue" => $datenow], ['doc' => 'SYSL']);
	}


	public function userscredential($params, $log, $logintype)
	{
		if (count($log) > 0) {
			$username = $params['username'];
			$password = $params['password'];
			$ismobile = false;
			if (isset($params['mobile'])) $ismobile = $params['mobile'];
			$mobileparents = $this->companysetup->getmobileparents($params);
			$mobilemodules = $this->companysetup->getmobilemodules($params);

			$this->othersClass->logConsole("levelid:" . $params['levelid']);

			$multicenter = $this->companysetup->getmultibranch($params);
			$companyname  = "";

			if (!$multicenter) {
				$companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
				if ($companyname == '') {
					$companyname = $this->companysetup->getcompanyname($params);
				}
			} else {
				$companyname = $this->companysetup->getcompanyname($params);
			}
			$systemtype = $this->companysetup->getsystemtype($params);
			$msg = "";
			$status = false;
			$data = $parent = $child = $access = $center = [];
			$menus = [];
			$report = [];
			$mailcount = 0;

			$this->logger->sbciplog('LOGIN', $params['ip'], $params['username']);
			$this->menusetup->setupparentmenu($params);
			$this->menusetup->generatereportlist($params); // SET UP REPORT
			$this->setprefixdoc->setupdoc($params);

			if ($log[0]->clientid != 0) {
				$qry = "
			  select count(msguser.is_view) as value
			  from conversation_user as msguser
			  left join conversation_msg as msg on msg.conversation_id=msguser.conversation_id
			  where msguser.user_id = " . $log[0]->clientid . " and msg.start=1 and msguser.trash=0 and msguser.is_view=0
			  ";
				$mailcount = $this->coreFunctions->datareader($qry);
			}

			$filter = '';
			if ($ismobile) {
				$parents = '';
				if (!empty($mobileparents)) $parents = "'" . implode("', '", $mobileparents) . "'";
				$parent = $this->coreFunctions->opentable("select id, name, seq, class, doc from left_parent where levelid=" . $params['levelid'] . " and name in (" . $parents . ") order by seq");
				$modules = '';
				if (!empty($mobilemodules)) $modules = "'" . implode("', '", $mobilemodules) . "'";
				if ($modules != '') $filter = " and menu.doc in (" . $modules . ")";
			} else {
				$parent = $this->coreFunctions->opentable("select id, name, seq, class, doc from left_parent where levelid=" . $params['levelid'] . " order by seq");
			}
			$access = $this->othersClass->getAccess($username);
			if (!empty($access)) {
				$access = json_decode(json_encode($access), true);

				foreach ($parent as $p) {
					$child = $this->coreFunctions->opentable("select parent.id, parent.name, parent.class as pclass, parent.doc as pdoc, menu.doc, menu.url, menu.module, menu.class as mclass, menu.access from left_parent as parent left join left_menu as menu on menu.parent_id = parent.id and menu.levelid = parent.levelid where parent.id=" . $p->id . " and menu.doc is not null " . $filter . " and parent.levelid=" . $params['levelid'] . " order by parent.id, menu.seq");
					$pm['name'] = $p->name;
					$pm['id'] = $p->id;
					$pm['class'] = $p->class;
					$pm['docs'] = $p->doc;
					$pm['menus'] = array();
					foreach ($child as $c) {
						$cm = array();
						if ($access[0]['attributes'][$c->access - 1] === '1') {
							$cm['id'] = $c->id;
							$cm['doc'] = $c->doc;
							$cm['module'] = $c->module;
							$cm['url'] = $c->url;
							$cm['icon'] = $c->mclass;
							array_push($pm['menus'], $cm);
						}
					}
					switch ($pm['name']) {
						case 'REPORT LIST':
							if ($logintype != '4e02771b55c0041180efc9fca6e04a77') array_push($menus, $pm);
							break;
						// case 'ANALYTICS':
						// 	array_push($menus, $pm);
						// 	break;
						default:
							if (count($pm['menus']) > 0) {
								array_push($menus, $pm);
							}
							break;
					}
				} // end foreach($parent as $p)

				$parent = $this->coreFunctions->opentable("select description,code,attribute from menu where parent in ('\\\9','\\\A','\\\B','\\\C','\\\D') and levelid=" . $params['levelid'] . "");
				foreach ($parent as $p) {
					if ($access[0]['attributes'][$p->attribute - 1] === '1') {
						$rm['name'] = $p->description;
						$rm['code'] = $p->code;
						$rm['menus'] = [];
						$child = $this->coreFunctions->opentable("select description,code,attribute from menu where parent='\\" . $p->code . "' and levelid=" . $params['levelid'] . " order by description");
						foreach ($child as $c) {
							$rm2 = [];
							if ($access[0]['attributes'][$c->attribute - 1] === '1') {
								$rm2['name'] = $c->description;
								$rm2['code'] = $c->code;
								$rm2['level'] = 1;
								$rm2['menus'] = [];
								$child2 = $this->coreFunctions->opentable("select description,code,attribute from menu where parent='\\" . $c->code . "' and levelid=" . $params['levelid'] . " order by description");
								if (!empty($child2)) {
									foreach ($child2 as $d) {
										$rm3 = [];
										if ($access[0]['attributes'][$d->attribute - 1] === '1') {
											$rm3['name'] = $d->description;
											$rm3['code'] = $d->code;
											$rm3['level'] = 1;
											array_push($rm2['menus'], $rm3);
										}
									}
									$rm2['level'] = 0;
									array_push($rm['menus'], $rm2);
								} else {
									array_push($rm['menus'], $rm2);
								} //end if

							}
						}
						array_push($report, $rm);
					}
				}
			}



			if ($multicenter) {
				$orderby = ' center.name';
				if ($params['companyid'] == 28 || $params['companyid'] == 29 || $params['companyid'] == 49) { //xcomp , sbcmain , hotmix
					$orderby = ' center.code';
				}

				$centername = 'center.name';
				switch ($params['companyid']) {
					case 39: //cbbsi
					case 49: //hotmix
					case 28: //xcomp
						$centername = "case center.shortname when '' then center.name else center.shortname end as name";
						break;
				}

				$this->coreFunctions->logConsole("logintype:" . $logintype);

				// if ($logintype == 'client') { //client users
				// 	goto DefaultCenterHere;
				// } else {
				$center = $this->coreFunctions->opentable("select center.line, center.code, " . $centername . ", center.address, center.tel, center.warehouse, center.sellingprice,
					center.commission, center.icommission, wh.clientname as whname, center.shortname
					from center left join client as wh on wh.client = center.warehouse left join centeraccess on centeraccess.center = center.code where md5(centeraccess.userid) = '{$log[0]->userid}' order by " . $orderby);
				// }
			} else {
				DefaultCenterHere:
				$center = $this->coreFunctions->opentable("select distinct center.line, center.code, center.name, center.address, center.tel, center.warehouse, center.sellingprice,
					center.commission, center.icommission, wh.clientname as whname, center.shortname
					from center left join client as wh on wh.client = center.warehouse left join centeraccess on centeraccess.center=center.code where center.code = '001'");
			}
			$timer = ['timer' => 60, 'visible' => true];

			$isautosaveacctgstock = $this->companysetup->getisautosaveacctgstock($params);
			$collapsiblehead = $this->companysetup->getcollapsiblehead($params);
			$showloading = $this->companysetup->getshowloading($params);
			$usecamera = $this->companysetup->getusecamera($params);
			$dashboardwh =  $this->companysetup->isdashboardwh($params);
			$socketserver = $this->companysetup->getsocketserver($params);
			$socketnotify = $this->companysetup->getsocketnotify($params);
            $lookupclientpermodule= $this->companysetup->getlookupclientpermodule($params);
			return response()->json(['logintype' => $logintype, 'menus' => $menus, 'center' => $center, 'multicenter' => $multicenter, 'user' => $log, 'msg' => 'Login Success', 'status' => true, 'reportmenu' => $report, 'mailcount' => $mailcount, 'companyname' => $companyname, 'timer' => $timer, 'isautosaveacctgstock' => $isautosaveacctgstock, 'collapsiblehead' => $collapsiblehead, 'showloading' => $showloading, 'usecamera' => $usecamera, 'dashboardwh' => $dashboardwh, 'socketserver' => ['url'=>$socketserver,'notify'=>$socketnotify],'lookupclientpermodule'=>$lookupclientpermodule], 200);
		} else {
			$this->logger->sbciplog('LOG-FAIL', $params['ip'], $params['username']);
			$status = false;
			$msg = "Error Login 2";
			return response()->json(['status' => $status, 'msg' => $msg], 200);
		}
	} // end function

	public function getcenter($params)
	{
		$orderby = ' center.name';
		if ($params['companyid'] == 28) { //xcomp
			$orderby = ' center.code';
		}

		$centername = 'center.name';
		switch ($params['companyid']) {
			case 39: //cbbsi
			case 49: //hotmix
				$centername = "case center.shortname when '' then center.name else center.shortname end as name";
				break;
		}

		$center = $this->coreFunctions->opentable("select center.line, center.code, " . $centername . ", center.address, center.tel, center.warehouse, center.sellingprice,
	  center.commission, center.icommission, wh.clientname as whname, center.shortname
	  from center left join client as wh on wh.client = center.warehouse left join centeraccess on centeraccess.center = center.code where md5(centeraccess.userid) = ? order by " . $orderby, [$params['userid']]);
		return response()->json(['status' => true, 'msg' => 'Success', 'center' => $center]);
	} // end function

	public function clientcredential($params, $log)
	{
		if (count($log) > 0) {
			$username = $params['username'];
			$multicenter = $this->companysetup->getmultibranch($params);
			$companyname = $this->companysetup->getcompanyname($params);
			$msg = "";
			$status = false;
			$data = $parent = $child = $access = $center = [];
			$menus = [];
			$report = [];
			$mailcount = 0;

			$this->logger->sbciplog('LOGIN', $params['ip'], $params['username']);
			$this->menusetup->setupparentmenu($params);
			$this->setprefixdoc->setupdoc($params);

			if ($log[0]->clientid != 0) {
				$qry = "
			  select count(msguser.is_view) as value
			  from conversation_user as msguser
			  left join conversation_msg as msg on msg.conversation_id=msguser.conversation_id
			  where msguser.user_id = " . $log[0]->clientid . " and msg.start=1 and msguser.trash=0 and msguser.is_view=0
			  ";
				$mailcount = $this->coreFunctions->datareader($qry);
			}

			$parent = $this->coreFunctions->opentable("select id, name, seq, class, doc from left_parent order by seq");

			foreach ($parent as $p) {
				$pm['name'] = $p->name;
				$pm['id'] = $p->id;
				$pm['class'] = $p->class;
				$pm['docs'] = $p->doc;
				$pm['menus'] = array();
				$child = $this->coreFunctions->opentable("select parent.id, parent.name, parent.class as pclass, parent.doc as pdoc, menu.doc, menu.url, menu.module, menu.class as mclass, menu.access from left_parent as parent left join left_menu as menu on menu.parent_id = parent.id where parent.id=" . $p->id . " and menu.doc is not null order by parent.seq, menu.seq");
				foreach ($child as $c) {
					$cm = array();
					$cm['id'] = $c->id;
					$cm['doc'] = $c->doc;
					$cm['module'] = $c->module;
					$cm['url'] = $c->url;
					$cm['icon'] = $c->mclass;
					array_push($pm['menus'], $cm);
				}
				if (count($pm['menus']) > 0) {
					array_push($menus, $pm);
				}
			} // end foreach($parent as $p)

			$parent = $this->coreFunctions->opentable("select description,code,attribute from menu where parent in ('\\\9','\\\A','\\\B','\\\C','\\\D')");
			foreach ($parent as $p) {
				$rm['name'] = $p->description;
				$rm['code'] = $p->code;
				$rm['menus'] = [];
				$child = $this->coreFunctions->opentable("select description,code,attribute from menu where parent='\\" . $p->code . "'");
				foreach ($child as $c) {
					$rm2 = [];
					$rm2['name'] = $c->description;
					$rm2['code'] = $c->code;
					$rm2['menus'] = [];
					$child2 = $this->coreFunctions->opentable("select description,code,attribute from menu where parent='\\" . $c->code . "'");
					if (!empty($child2)) {
						foreach ($child2 as $d) {
							$rm3 = [];
							$rm3['name'] = $d->description;
							$rm3['code'] = $d->code;
							array_push($rm2['menus'], $rm3);
						}
						array_push($rm['menus'], $rm2);
					} else {
						array_push($rm['menus'], $rm2);
					} //end if

				}
				array_push($report, $rm);
			}


			if ($multicenter) {
				$orderby = ' center.name';
				if ($params['companyid'] == 28) { //xcomp
					$orderby = ' center.code';
				}
				$center = $this->coreFunctions->opentable("select center.line, center.code, center.name, center.address, center.tel, center.warehouse, center.sellingprice,
					center.commission, center.icommission, wh.clientname as whname
					from center left join client as wh on wh.client = center.warehouse left join centeraccess on centeraccess.center = center.code where md5(centeraccess.userid) = '{$log[0]->userid}' order by " . $orderby);
			} else {
				$center = $this->coreFunctions->opentable("select distinct center.line, center.code, center.name, center.address, center.tel, center.warehouse, center.sellingprice,
					center.commission, center.icommission, wh.clientname as whname
					from center left join client as wh on wh.client = center.warehouse left join centeraccess on centeraccess.center=center.code where center.code = '001'");
			}
			$timer = ['timer' => 60, 'visible' => true];
			return response()->json(['menus' => $menus, 'center' => $center, 'multicenter' => $multicenter, 'user' => $log, 'msg' => 'Login Success', 'status' => true, 'reportmenu' => $report, 'mailcount' => $mailcount, 'companyname' => $companyname, 'timer' => $timer], 200);
		} else {
			$this->logger->sbciplog('LOG-FAIL', $params['ip'], $params['username']);
			$status = false;
			$msg = "Error Login 3";
			return response()->json(['status' => $status, 'msg' => $msg], 200);
		}
	}


	public function sbc($params)
	{

		$doc = strtolower($params['doc']);
		$type = strtolower($params['moduletype']);
		$classname = __NAMESPACE__ . '\\modules\\' . $type . '\\' . $doc;
		try {
			$this->config['classname'] = $classname;
			$this->config['docmodule'] = new $classname();
		} catch (Exception $e) {
			echo $e;
			return $this;
		}
		$this->config['params'] = $params;
		if (isset($this->config['params']['logintype'])) {
			if ($this->config['params']['logintype'] == '62608e08adc29a8d6dbc9754e659f125') {
				$access = $this->othersClass->getportalaccess($params['user']);
			} else {
				$access = $this->othersClass->getAccess($params['user']);
			}
		} else {
			$access = $this->othersClass->getAccess($params['user']);
		}
		$this->config['access'] = json_decode(json_encode($access), true);
		$this->config['mattrib'] = $this->config['docmodule']->getAttrib();
		if ($this->companysetup->getrestrictip($params)) {
			$ipaccess = $this->config['access'][0]['attributes'][3722]; //restrict ip access
			if ($ipaccess == 1) {
				$this->config['allowlogin'] = $this->othersClass->checkip($params);
				if (!$this->config['allowlogin']) {
					$this->config['msg'] = 'RESTRICTED IP, pls inform admin';
				}
				$this->coreFunctions->LogConsole("Your IP - '" . $params['ip'] . "'");
			} else {
				$this->config['allowlogin'] = true;
			}
		}

		$istimechecking = $this->othersClass->istimechecking($params);
		if ($istimechecking['status']) {
			$this->config['loginexpired'] = $istimechecking['loginexpired'];
		}

		return $this;
	}




	public function checksecurity($accessid)
	{
		if (isset($this->config['mattrib'][$accessid])) {
			$id = $this->config['mattrib'][$accessid];

			$companyid = $this->config['params']['companyid'];
			if ($companyid == 49) { //hotmix

				if ($this->config['params']['doc'] == 'RR') {
					if (isset($this->config['params']['action'])) {
						if ($this->config['params']['action'] == 'getposummary') $id = $this->config['mattrib']['save'];
						if ($this->config['params']['action'] == 'getpodetails') $id = $this->config['mattrib']['save'];
					}
				}
			}

			$this->config['verifyaccess'] = $this->config['access'][0]['attributes'][$id - 1];
			$this->coreFunctions->LogConsole($id . '-access');
			if ($this->config['verifyaccess'] == 0) {
				$this->config['return'] = ['status' => 'denied', 'msg' => 'Invalid Access ' . $id];
			}
		} else {
			$this->coreFunctions->sbclogger('Undefined ' . $accessid . ' ' . $this->config['params']['doc'] . ' id: ' . $this->config['params']['id']);
			$this->config['return'] = ['status' => 'denied', 'msg' => 'Undefined ' . $accessid . ' ' . $this->config['params']['doc']];
		}

		return $this;
	}

	public function isposted()
	{
		switch ($this->config['params']['doc']) {
			case 'JOBTITLEMASTER':
			case 'PAYROLLSETUP':
			case 'PIECEENTRY':
			case 'WAREHOUSE':
			case 'OTHERCHARGES':
			case 'EMPPROJECTLOGB':
				$this->config['isposted'] = false;
				$this->config['islocked'] = false;
				break;
			default:
				$this->config['isposted'] = $this->othersClass->isposted($this->config);
				$this->config['islocked'] = $this->othersClass->islocked($this->config);
				break;
		}
		return $this;
	}

	public function loadform()
	{
		if ($this->config['verifyaccess'] == 1) {
			$access = $this->getmoduleaccess($this->config['mattrib']);
			$buttons = $this->config['docmodule']->createHeadbutton($this->config);
			$txtfield = $this->config['docmodule']->createHeadField($this->config);
			$tab = $this->config['docmodule']->createTab($access, $this->config);
			$tabbtn = $this->config['docmodule']->createtabbutton($this->config);
			$doclistingcols = $this->config['docmodule']->createdoclisting($this->config);
			$modulename = $this->config['docmodule']->modulename;
			$gridname = $this->config['docmodule']->gridname;
			$isexpiry = $this->companysetup->getisexpiry($this->config['params']);
			$ispallet = $this->companysetup->getispallet($this->config['params']);
			$isproject = $this->companysetup->getisproject($this->config['params']);
			$tab2 = [];
			$paramsdatalisting = [];
			$sbcscript = [];
			$showserialrem = $this->companysetup->getshowserialrem($this->config['params']);
			if (method_exists($this->config['classname'], 'createtab2')) {
				$tab2 = $this->config['docmodule']->createtab2($access, $this->config);
			}
			if (method_exists($this->config['classname'], 'paramsdatalisting')) {
				$paramsdatalisting = $this->config['docmodule']->paramsdatalisting($this->config);
			}

			if (method_exists($this->config['classname'], 'sbcscript')) {
				$sbcscript = $this->config['docmodule']->sbcscript($this->config);
			}

			if ($isexpiry) {
				$expirystatus = $this->config['docmodule']->expirystatus;
				$itembalcol = [
					['name' => 'wh', 'label' => 'Code', 'align' => 'left', 'field' => 'wh'],
					['name' => 'whname', 'label' => 'Warehouse', 'align' => 'left', 'field' => 'whname'],
					['name' => 'loc', 'label' => 'Location', 'align' => 'left', 'field' => 'loc'],
					['name' => 'expiry', 'label' => 'Expiry', 'align' => 'left', 'field' => 'expiry'],
					['name' => 'bal', 'label' => 'Balance', 'align' => 'left', 'field' => 'bal'],
					['name' => 'min', 'label' => 'Minimum', 'align' => 'left', 'field' => 'min'],
					['name' => 'max', 'label' => 'Maximum', 'align' => 'left', 'field' => 'max'],
					['name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom']
				];

				if ($this->config['params']['companyid'] == 46) { //morningsteel
					$col = ['name' => 'cost', 'label' => 'Cost', 'align' => 'left', 'field' => 'cost'];
					array_push($itembalcol, $col);
				}
			} elseif ($ispallet) {
				switch ($this->config['params']['doc']) {
					case 'SO':
						$expirystatus = ['readonly' => true, 'show' => false, 'showdate' => false, 'showpallet' => false];
						break;
					default:
						$expirystatus = ['readonly' => true, 'show' => false, 'showdate' => false, 'showpallet' => true];
						break;
				}
				$itembalcol = [
					['name' => 'whname', 'label' => 'Warehouse', 'align' => 'left', 'field' => 'whname'],
					['name' => 'location', 'label' => 'Location', 'align' => 'left', 'field' => 'location'],
					['name' => 'pallet', 'label' => 'Pallet', 'align' => 'left', 'field' => 'pallet'],
					['name' => 'bal', 'label' => 'Balance', 'align' => 'left', 'field' => 'bal'],
					['name' => 'min', 'label' => 'Minimum', 'align' => 'left', 'field' => 'min'],
					['name' => 'max', 'label' => 'Maximum', 'align' => 'left', 'field' => 'max'],
					['name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom']
				];

				if ($this->config['params']['companyid'] == 46) { //morningsteel
					$col = ['name' => 'cost', 'label' => 'Cost', 'align' => 'left', 'field' => 'cost'];
					array_push($itembalcol, $col);
				}
			} elseif ($isproject) {
				$expirystatus = ['readonly' => true, 'show' => false, 'showdate' => false, 'showstage' => true];
				if ($this->config['params']['companyid'] == 8) { //maxipro
					$expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false, 'showstage' => true];
				}
				$itembalcol = [
					['name' => 'whname', 'label' => 'Warehouse', 'align' => 'left', 'field' => 'whname'],
					['name' => 'bal', 'label' => 'Balance', 'align' => 'left', 'field' => 'bal'],
					['name' => 'loc', 'label' => 'Brand', 'align' => 'left', 'field' => 'loc'],
					['name' => 'min', 'label' => 'Minimum', 'align' => 'left', 'field' => 'min'],
					['name' => 'max', 'label' => 'Maximum', 'align' => 'left', 'field' => 'max'],
					['name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom']
				];

				if ($this->config['params']['companyid'] == 46) { //morningsteel
					$col = ['name' => 'cost', 'label' => 'Cost', 'align' => 'left', 'field' => 'cost'];
					array_push($itembalcol, $col);
				}
			} else {
				switch ($this->config['params']['doc']) {
					case 'OP':
					case 'QS':
						$expirystatus = ['readonly' => true, 'show' => false, 'showdate' => false, 'showwh' => false];
						break;
					default:
						$expirystatus = ['readonly' => true, 'show' => false, 'showdate' => false];
						break;
				}

				if ($this->config['params']['companyid'] == 46) { //morningsteel
					$itembalcol = [
						['name' => 'wh', 'label' => 'Code', 'align' => 'left', 'field' => 'wh'],
						['name' => 'whname', 'label' => 'Warehouse', 'align' => 'left', 'field' => 'whname'],
						['name' => 'bal', 'label' => 'Balance', 'align' => 'left', 'field' => 'bal'],
						['name' => 'cost', 'label' => 'Cost', 'align' => 'left', 'field' => 'cost'],
						['name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom'],
						['name' => 'min', 'label' => 'Minimum', 'align' => 'left', 'field' => 'min'],
						['name' => 'max', 'label' => 'Maximum', 'align' => 'left', 'field' => 'max']
					];
				} else {
					$itembalcol = [
						['name' => 'wh', 'label' => 'Code', 'align' => 'left', 'field' => 'wh'],
						['name' => 'whname', 'label' => 'Warehouse', 'align' => 'left', 'field' => 'whname'],
						['name' => 'bal', 'label' => 'Balance', 'align' => 'left', 'field' => 'bal'],
						['name' => 'min', 'label' => 'Minimum', 'align' => 'left', 'field' => 'min'],
						['name' => 'max', 'label' => 'Maximum', 'align' => 'left', 'field' => 'max'],
						['name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom']
					];
				}
			}

			$showfilter = $this->config['docmodule']->showfilter;
			$showfilteroption = $this->config['docmodule']->showfilteroption;

			if (isset($this->config['docmodule']->rowperpage)) {
				$rowperpage = $this->config['docmodule']->rowperpage;
			} else {
				$rowperpage = 30;
			}

			if (isset($this->config['docmodule']->showfilterlabel)) {
				$showfilterlabel = $this->config['docmodule']->showfilterlabel;
				if ($this->config['params']['companyid'] == 12) { // afti usd
					if ($this->config['params']['doc'] == 'SQ') {
						$showfilterlabel = [
							['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
							['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
							['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
							['val' => 'all', 'label' => 'All', 'color' => 'primary']
						];
					}
				}
			} else {
				$showfilterlabel = [
					['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
					['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
					['val' => 'all', 'label' => 'All', 'color' => 'primary']
				];
			}

			$showselectadd = false;
			$defaultaccounts = [];
			if ($this->config['params']['companyid'] == 26) { //bee healthy
				$showselectadd = true;
				$defaultaccounts = $this->loadDefaultAccounts();
			}

			//if label of posted and locked want to rename, pls set it per module, not here
			$labelposted = 'Posted';
			$labellocked = 'Locked';
			if (isset($this->config['docmodule']->labelposted)) {
				$labelposted = $this->config['docmodule']->labelposted;
			}
			if (isset($this->config['docmodule']->labellocked)) {
				$labellocked = $this->config['docmodule']->labellocked;
			}

			$showcreatebtn = $this->config['docmodule']->showcreatebtn;
			$timer = ['timer' => 60, 'visible' => true];

			switch ($this->config['params']['companyid']) {
				case 26: //bee healthy
				case 47: //kitchenstar
				case 21: //kinggeorge
					$timer = ['timer' => 0, 'visible' => false];
					break;
				case 16: //ATI
					if ($this->config['params']['doc'] == 'PRLISTING') {
						$timer = ['timer' => 0, 'visible' => false];
					}
					break;
			}

			switch ($this->config['params']['moduletype']) {
				case 'PRODUCTINQUIRY':
				case 'MASTERFILE':
				case 'INQUIRY':
					$timer = ['timer' => 0, 'visible' => false];
					break;
			}

			$coaform = $this->accountEntrySetup();
			$itemform = $this->itemEntrySetup();

			$defaultheaddata = [];
			if (method_exists($this->config['classname'], 'defaultheaddata')) {
				$defaultheaddata = $this->config['docmodule']->defaultheaddata($this->config['params']);
				if (!empty($defaultheaddata)) $defaultheaddata = $defaultheaddata[0];
			}

			$doclistdaterange = 6;
			if (isset($this->config['docmodule']->doclistdaterange)) {
				switch ($this->config['params']['companyid']) {
					case 21: //kinggeorge
						$doclistdaterange = 1;
						break;
					default:
						$doclistdaterange = $this->config['docmodule']->doclistdaterange;
						break;
				}
			} else {
				if ($this->config['params']['companyid'] == 21) { //kinggeorge
					$doclistdaterange = 1;
				}
			}


			$historycol = [
				['name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno'],
				['name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid'],
				['name' => 'amt', 'label' => 'Price', 'align' => 'left', 'field' => 'amt'],
				['name' => 'disc', 'label' => 'Disc', 'align' => 'left', 'field' => 'disc'],
				['name' => 'uom', 'label' => 'Unit', 'align' => 'left', 'field' => 'uom']
			];

			$pricecol = [];

			switch ($this->config['params']['companyid']){
				case 60://transpower
					switch ($this->config['params']['doc']) {
						case 'SJ':
						case 'SO':
							$historycol = [
								['name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status'],
								['name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid'],
								['name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno'],
								['name' => 'clientname', 'label' => 'Client Name', 'align' => 'left', 'field' => 'clientname'],
								['name' => 'qty', 'label' => 'Quantity', 'align' => 'left', 'field' => 'qty'],
								['name' => 'amt', 'label' => 'Price', 'align' => 'left', 'field' => 'amt'],
								['name' => 'disc', 'label' => 'Discount', 'align' => 'left', 'field' => 'disc'],
								['name' => 'agentamt', 'label' => 'Agent Amt', 'align' => 'left', 'field' => 'agentamt'],
								['name' => 'yourref', 'label' => 'Your Ref#', 'align' => 'left', 'field' => 'yourref'],
								['name' => 'ourref', 'label' => 'Our Ref#', 'align' => 'left', 'field' => 'ourref'],
							];
	
							$pricecol = [
								['name' => 'pricegrp', 'label' => 'Price Group', 'align' => 'left', 'field' => 'pricegrp'],
								['name' => 'amt', 'label' => 'Price', 'align' => 'left', 'field' => 'amt'],
								['name' => 'disc', 'label' => 'Disc', 'align' => 'left', 'field' => 'disc'],
								['name' => 'netamt', 'label' => 'Net Price', 'align' => 'left', 'field' => 'netamt']
							];
	
							break;
						default:
							$historycol = [
								['name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno'],
								['name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid'],
								['name' => 'amt', 'label' => 'Price', 'align' => 'left', 'field' => 'amt'],
								['name' => 'disc', 'label' => 'Discount', 'align' => 'left', 'field' => 'disc'],
								['name' => 'uom', 'label' => 'Unit', 'align' => 'left', 'field' => 'uom']
							];
							break;
					}
					break;
				case 50://unitech
					switch ($this->config['params']['doc']) {
						case 'SJ':
						case 'SO':
							$historycol = [
								['name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno'],
								['name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid'],
								['name' => 'amt', 'label' => 'Price', 'align' => 'left', 'field' => 'amt'],
								['name' => 'disc', 'label' => 'Disc', 'align' => 'left', 'field' => 'disc'],
								['name' => 'uom', 'label' => 'Unit', 'align' => 'left', 'field' => 'uom'],
								['name' => 'factor', 'label' => 'Factor', 'align' => 'left', 'field' => 'factor']
							];
							break;
					}
					break;
			}


			$this->config['return'] = [
				'txtfield' => $txtfield,
				'toolbar' => $buttons,
				'tab' => $tab,
				'tabbuttons' => $tabbtn,
				'maccess' => $access,
				'status' => true,
				'msg' => 'Loaded Success',
				'modulename' => $modulename,
				'doclistingcols' => $doclistingcols,
				'expirystatus' => $expirystatus,
				'showfilter' => $showfilter,
				'showcreatebtn' => $showcreatebtn,
				'showfilteroption' => $showfilteroption,
				'timer' => $timer,
				'showselectadd' => $showselectadd,
				'defaultaccounts' => $defaultaccounts,
				'itembalcol' => $itembalcol,
				'showfilterlabel' => $showfilterlabel,
				'tab2' => $tab2,
				'paramsdatalisting' => $paramsdatalisting,
				'gridname' => $gridname,
				'coaform' => $coaform,
				'sbcscript' => $sbcscript,
				'itemform' => $itemform,
				'labelposted' => $labelposted,
				'labellocked' => $labellocked,
				'rowperpage' => $rowperpage,
				'defaultheaddata' => $defaultheaddata,
				'doclistdaterange' => $doclistdaterange,
				'showserialrem' => $showserialrem,
				'historycol' => $historycol,
				'pricecol' => $pricecol
			];
		} else {
			$this->logger->sbciplog($this->config['params']['doc'] . '-FAIL', $this->config['params']['ip'], $this->config['params']['user']);
		}
		return $this;
	} // end function


	public function itemEntrySetup()
	{
		$fields = [];
		switch ($this->config['params']['companyid']) {
			case 19: //housegem
				$fields = ['kgs'];
				$col = $this->fieldClass->create($fields);
				switch ($this->config['params']['doc']) {
					case 'SO':
					case 'SJ':
					case 'CM':
						data_set($col, 'kgs.label', 'Selling Kgs');
						break;
					case 'RR':
					case 'DM':
						data_set($col, 'kgs.label', 'Buying Kgs');
						break;
				}
				$itemfield = $this->coreFunctions->opentable("select '1.0000000000' as kgs");
				$itemform = array('col' => $col, 'itemfield' => $itemfield[0], 'title' => 'Enter Item Entry', 'style' => 'width:400px;max-width:400px;');
				break;
			case 60: //transpower
				switch ($this->config['params']['doc']) {
					case 'SO':
					case 'SJ':
						$fields = ['agentamt'];
						$col = $this->fieldClass->create($fields);
						$itemfield = $this->coreFunctions->opentable("select '0.00' as agentamt");
						$itemform = array('col' => $col, 'itemfield' => $itemfield[0], 'title' => 'Enter Item Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					default:
						$fields = [];
						$col = [];
						$itemfield = [];
						$itemform = array('col' => $col, 'coafield' => $itemfield, 'title' => 'Enter Item Entry', 'style' => 'width:400px;max-width:400px;');
						break;
				}

				break;
			case 24: //goodfound
				switch ($this->config['params']['doc']) {
					case 'RN':
						$fields = ['loc'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'loc.label', 'Batch No.');
						data_set($col, 'loc.type', 'input');
						data_set($col, 'loc.readonly', false);
						// data_set($col, 'loc.lookupclass', 'batchlookuploc');
						$itemfield = $this->coreFunctions->opentable("select '' as loc");
						$itemform = array('col' => $col, 'itemfield' => $itemfield[0], 'title' => 'Enter Batch', 'style' => 'width:400px;max-width:400px;');
						break;
					default:
						goto defaulthere;
						break;
				}
				break;
			case 50: //unitech
				switch ($this->config['params']['doc']) {
					case 'RR':
						$fields = ['loc'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'loc.label', 'Brand');
						data_set($col, 'loc.type', 'input');
						data_set($col, 'loc.readonly', false);
						// data_set($col, 'loc.lookupclass', 'batchlookuploc');
						$itemfield = $this->coreFunctions->opentable("select '' as loc");
						$itemform = array('col' => $col, 'itemfield' => $itemfield[0], 'title' => 'Enter Brand', 'style' => 'width:400px;max-width:400px;');
						break;
					default:
						goto defaulthere;
						break;
				}
				break;

			default:
				defaulthere:
				$fields = [];
				$col = [];
				$itemfield = [];
				$itemform = array('col' => $col, 'itemfield' => $itemfield, 'title' => 'Enter Item Entry', 'style' => 'width:400px;max-width:400px;');
				break;
		}
		return $itemform;
	}

	public function accountEntrySetup()
	{
		switch ($this->config['params']['companyid']) {
			case 10: //afti
				switch ($this->config['params']['doc']) {
					case 'PV':
					case 'CV':
						// case 'CR':
					case 'GJ':
						$fields = ['isewt', 'isvewt', 'isvat', 'dewt', 'dprojectname', 'dbranchname', 'ddeptname'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'ddeptname.label', 'Department');
						data_set($col, 'isvat.readonly', false);
						data_set($col, 'dprojectname.label', 'Item Group');
						$coafield = $this->coreFunctions->opentable("select '' as dprojectname, '' as projectcode,'' as projectname, '' as dbranchname,'' as branchcode,'' as branchname,'' as dept,'' as deptname,'' as ddeptname,'' as deptid,'' as dewt,'' as ewt,'' as ewtcode ,'' as ewtrate, '0' as isewt,'0' as isvewt, '0' as isvat ");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					case 'AP':
						// case 'AR':				
					case 'GD':
					case 'GC':
						$fields = ['dprojectname', 'dbranchname', 'ddeptname'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'ddeptname.label', 'Department');
						data_set($col, 'dprojectname.label', 'Item Group');
						$coafield = $this->coreFunctions->opentable("select '' as dprojectname, '' as projectcode,'' as projectname, '' as dbranchname,'' as branchcode,'' as branchname,'' as dept,'' as deptname,'' as ddeptname,'' as deptid,'' as dewt,'' as ewt,'' as ewtrate");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					case 'CR':
						$fields = ['yourref', 'lastdp', 'isewt', 'isvewt', 'isvat', 'dewt', 'dprojectname', 'dbranchname', 'ddeptname'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'ddeptname.label', 'Department');
						data_set($col, 'isvat.readonly', false);
						data_set($col, 'dprojectname.label', 'Item Group');
						data_set($col, 'yourref.type', 'lookup');
						data_set($col, 'yourref.label', 'QTN #');
						data_set($col, 'yourref.action', 'lookupqtn');
						data_set($col, 'yourref.lookupclass', 'lookupaccountingqtn');
						$coafield = $this->coreFunctions->opentable("select '' as yourref, 0 as qttrno, '' as dprojectname, '' as projectcode,'' as projectname, '' as dbranchname,'' as branchcode,'' as branchname,'' as dept,'' as deptname,'' as ddeptname,'' as deptid,'' as dewt,'' as ewt,'' as ewtcode ,'' as ewtrate, '0' as isewt,'0' as isvewt, '0' as isvat,'0' as lastdp ");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					case 'AR':
						$date = $this->othersClass->getCurrentTimeStamp();
						$fields = ['dprojectname', 'dbranchname', 'ddeptname', 'dagentname', 'poref', 'podate'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'ddeptname.label', 'Department');
						data_set($col, 'dagentname.label', 'Sales Person');
						data_set($col, 'dprojectname.label', 'Item Group');
						data_set($col, 'poref.label', 'PO #');
						data_set($col, 'podate.readonly', false);
						$coafield = $this->coreFunctions->opentable("select '' as dprojectname, '' as projectcode,'' as projectname, '' as dbranchname,'' as branchcode,'' as branchname,'' as dept,'' as deptname,'' as ddeptname,'' as deptid,'' as dewt,'' as ewt,'' as ewtrate, '' as poref, date_format('" . $date . "','%m/%d/%Y') as podate,'' as agent,'' as agentname,'' as dagentname,0 as agentid ");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					default:
						$fields = ['dprojectname'];
						$col = $this->fieldClass->create($fields);
						$coafield = $this->coreFunctions->opentable("select '' as dprojectname, '' as projectcode, '' as projectname");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
				}
				break;
			case 8: //maxipro
				switch ($this->config['params']['doc']) {
					case 'CV':
					case 'PV':
					case 'GJ':
						$fields = ['subprojectname'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'subprojectname.label', 'Sub Project');
						data_set($col, 'subprojectname.type', 'lookup');
						data_set($col, 'subprojectname.action', 'lookupsubproject');
						data_set($col, 'subprojectname.lookupclass', 'maxisubprojectname');
						$coafield = $this->coreFunctions->opentable("select '' as subproject, '' as subprojectname");
						$coaform = array('col' => $col, 'coafield' => $coafield, 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					default:
						$fields = [];
						$col = [];
						$coafield = [];
						$coaform = array('col' => $col, 'coafield' => $coafield, 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
				}
				break;

			case 21: //kinggeorge
				switch ($this->config['params']['doc']) {
					case 'AR':
					case 'AP':
						$fields = ['ref'];
						$col = $this->fieldClass->create($fields);
						$coafield = $this->coreFunctions->opentable("select '' as ref");
						$coaform = array('col' => $col, 'coafield' => $coafield, 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					default:
						$fields = [];
						$col = [];
						$coafield = [];
						$coaform = array('col' => $col, 'coafield' => $coafield, 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
				}
				break;

			case 26: //bee healthy
				switch ($this->config['params']['doc']) {
					case 'CV':
					case 'PV':
					case 'GJ':
						$fields = ['isewt', 'isvat', 'dewt'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'isvat.readonly', false);
						$coafield = $this->coreFunctions->opentable("select '' as dewt,'' as ewt,'' as ewtcode ,'' as ewtrate, '0' as isewt, '0' as isvat ");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
					default:
						$fields = [];
						$col = [];
						$coafield = [];
						$coaform = array('col' => $col, 'coafield' => $coafield, 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
						break;
				}
				break;
			case 24: //goodfound
				switch ($this->config['params']['doc']) {
					case 'PV':
						$fields = ['isewt', 'isexcess', 'isvat', 'dewt'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'isvat.readonly', false);
						$coafield = $this->coreFunctions->opentable("select '' as dewt,'' as ewt,'' as ewtcode ,'' as ewtrate, '0' as isewt,'0' as isvewt, '0' as isvat,'0' as isexcess ");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:100px;max-width:100px;');
						break;
					case 'CV':
						$fields = ['isewt', 'isvewt', 'isvat', 'dewt'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'isvat.readonly', false);
						$coafield = $this->coreFunctions->opentable("select '' as dewt,'' as ewt,'' as ewtcode ,'' as ewtrate, '0' as isewt,'0' as isvewt, '0' as isvat ");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:100;max-width:100px;');
						break;
					case 'GJ':
						$fields = ['isewt', 'isexcess', 'isvat', 'dewt'];
						$col = $this->fieldClass->create($fields);
						data_set($col, 'isvat.readonly', false);
						$coafield = $this->coreFunctions->opentable("select '' as dewt,'' as ewt,'' as ewtcode ,'' as ewtrate, '0' as isewt,'0' as isvewt, '0' as isvat,'0' as isexcess ");
						$coaform = array('col' => $col, 'coafield' => $coafield[0], 'title' => 'Enter Accounting Entry', 'style' => 'width:100px;max-width:100px;');
						break;

					default:
						$fields = [];
						$col = [];
						$coafield = [];
						$coaform = array('col' => $col, 'coafield' => $coafield, 'title' => 'Enter Accounting Entry', 'style' => 'width:100px;max-width:100px;');
						break;
				}
				break;


			default:
				$fields = [];
				$col = [];
				$coafield = [];
				$coaform = array('col' => $col, 'coafield' => $coafield, 'title' => 'Enter Accounting Entry', 'style' => 'width:400px;max-width:400px;');
				break;
		}
		return $coaform;
	} // end function



	public function loadheaddata()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->loadheaddata($this->config);
		}
		return $this;
	} // end function

	public function lockunlock()
	{
		if ($this->config['verifyaccess'] == 1) {
			if ($this->config['isposted']) {
				return $this;
			}
			$this->config['return'] = $this->headClass->lockunlock($this->config);
		}
		return $this;
	}

	public function newstockcard($isnew = true)
	{
		try {
			if ($this->config['verifyaccess'] == 1) {
				$barcodelength = $this->companysetup->getbarcodelength($this->config['params']);
				$barcode = trim($this->config['params']['barcode']);

				if ($isnew && $barcodelength > 0) {
					$this->checkstockcardbarcode($barcode, 'GETLASTSEQ');
				} else {
					$itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=?', [$barcode]);
					if ($itemid !== '') {
						$this->config['newbarcode'] = "";
					} else {
						$this->config['newbarcode'] = $barcode;
					}
				}
				$this->config['return'] = $this->config['docmodule']->newstockcard($this->config);
			}
			return $this;
		} catch (Exception $e) {
			echo $e;
		}
	}

	public function newclient($isnew = true)
	{
		try {
			if ($this->config['verifyaccess'] == 1) {
				if (isset($this->config['params']['client'])) {
					$client = trim($this->config['params']['client']);
				} else {
					$client = "";
				}
				$clientlength = $this->companysetup->getclientlength($this->config['params']);
				if ($isnew && $clientlength > 0) {
					$this->checkclientcode($client, 'GETLASTSEQ');
				} else {
					$this->config['newclient'] = $client;
				}
				$this->config['return'] = $this->config['docmodule']->newclient($this->config);
			}
			return $this;
		} catch (Exception $e) {
			echo "newclient - " . $e;
		}
	}

	public function checkpostrans()
	{
		if ($this->config['verifyaccess'] == 1) {
			$data = $this->coreFunctions->opentable("select trno from " . $this->config['docmodule']->tablenum . " where doc='" . $this->config['params']['doc'] . "' and postdate is null");
			if (!empty($data)) {
				$this->config['return'] = ['status' => false, 'msg' => 'has unposted trans'];
			} else {
				$this->config['return'] = ['status' => true, 'msg' => 'no unposted trans'];
			}
		}
		return $this;
	}

	public function newpostrans()
	{
		try {
			if ($this->config['verifyaccess'] == 1) {
				//checking stock
				if (isset($this->config['params']['item'])) {
					$item = $this->config['params']['item'];
				} else {
					$this->config['params']['client'] = $this->config['params']['head']['client'];
					$this->config['params']['trno'] = 0;
					$barcodelength = $this->companysetup->getbarcodelength($this->config['params']);
					$this->config['params']['barcode'] = trim($this->config['params']['barcode']);
					if ($barcodelength == 0) {
						$barcode = $this->config['params']['barcode'];
					} else {
						$barcode = $this->othersClass->padj($this->config['params']['barcode'], $barcodelength);
					}
					// $item = $this->coreFunctions->opentable("select item.itemid, wh.client from item left join rrstatus on rrstatus.itemid=item.itemid left join client as wh on wh.clientid=rrs");
					$wh = $this->config['params']['head']['wh'];
					$item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom, '' as expiry from item where barcode=?", [$barcode]);
					if (!empty($item)) {
						$this->config['params']['barcode'] = $barcode;
						$data = $this->config['docmodule']->getlatestprice($this->config);

						if (!empty($data)) {
							$item[0]->amt = $data['data'][0]->amt;
							$item[0]->disc = $data['data'][0]->disc;
							$item[0]->uom = $data['data'][0]->uom;
						}
						$item = json_decode(json_encode($item[0]), true);
					} else {
						$this->config['return'] = ['status' => false, 'msg' => 'Barcode not found.'];
					}
				}
				$bal = $this->coreFunctions->datareader("select sum(r.bal) as value from rrstatus as r 
				left join client as w on w.clientid = r.whid where r.itemid = " . $item['itemid'] . " and w.client = '" . $item['wh'] . "' and r.loc = '" . $item['loc'] . "'");
				if ($bal < $item['qty']) {
					$this->config['return'] = ['status' => false, 'msg' => 'item out of stock'];
				} else {
					$docnolength = $this->companysetup->getdocumentlength($this->config['params']);
					$pref = $this->othersClass->last_bref($this->config);
					if (!$pref) {
						$prefixes = $this->othersClass->getPrefixes($this->config['params']['doc'], $this->config);
						$pref = isset($prefixes[0]) ? $prefixes[0] : $this->config['params']['doc'];
					} //end if $pref
					$seq = $this->othersClass->getlastseq($pref, $this->config);
					if ($seq == 0 || empty($pref)) {
						if (empty($pref)) {
							$pref = strtoupper($docno);
						}
						$seq = $this->othersClass->getlastseq($pref, $this->config);
					}
					$poseq = $pref . $seq;
					$yr = $this->coreFunctions->datareader("select yr as value FROM profile where psection ='" . $this->config['params']['doc'] . "' and doc ='SED'");
					$newdocno = $this->othersClass->PadJ($poseq, $docnolength, $yr);
					$this->config['newdocno'] = $newdocno;
					$this->config['isposted'] = false;
					$this->config['islocked'] = false;
					$this->config['params']['trno'] = 0;
					$this->savehead();
					// $this->config['params']['head'] = $this->config['params'];
					// $this->config['docmodule']->updatehead($this->config, false);
					// $this->config['return'] = ['status'=>true, 'msg'=>'Transaction saved'];
				}
			}
			return $this;
		} catch (Exception $e) {
			echo $e;
		}
	}

	public function newtransaction()
	{
		$istodo = $this->companysetup->getistodo($this->config['params']);
		$companyid = $this->config['params']['companyid'];
		try {
			if ($this->config['verifyaccess'] == 1) {

				if ($this->config['params']['doc'] == 'SB') {
					$prefix = $this->coreFunctions->getfieldvalue("client", "prefix", "clientid=?", [$this->config['params']['adminid']]);
					if ($prefix == '') {
						$this->config['return'] = ['head' => [], 'griddata' => [], 'islocked' => false, 'isposted' => false, 'status' => false, 'msg' => 'Please setup valid prefix for Branch', 'clickobj' => [], 'backlisting' => true];
						return $this;
					}
				}

				if ($this->config['params']['companyid'] == 56) { //homeworks
					switch ($this->config['params']['doc']) {
						case 'PP':
						case 'PA':
							break;
						default:
							$brprefix = $this->coreFunctions->datareader("SELECT client.prefix AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE c.code='" . $this->config['params']['center'] . "'");
							if ($brprefix == '') {
								$this->config['return'] = ['head' => [], 'griddata' => [], 'islocked' => false, 'isposted' => false, 'status' => false, 'msg' => 'Please setup valid prefix for this branch.', 'clickobj' => [], 'backlisting' => false];
								return $this;
							}
							break;
					}
				}

				if ($this->config['params']['doc'] == 'TC') {
					$unposted = $this->coreFunctions->getfieldvalue("tchead", "docno", "''=''", [], "dateid desc");
					if ($unposted != '') {
						$this->config['return'] = ['head' => [], 'griddata' => [], 'islocked' => false, 'isposted' => false, 'status' => false, 'msg' => 'There are unposted Petty Cash transaction. Post it first to continue.', 'clickobj' => [], 'backlisting' => false];
						return $this;
					}
				}


				if ($this->config['params']['companyid'] == 8) { //maxipro
					switch ($this->config['params']['doc']) {
						case 'RQ':
							$viewall = $this->othersClass->checkAccess($this->config['params']['user'], 2272);
							break;
						case 'JR':
							$viewall = $this->othersClass->checkAccess($this->config['params']['user'], 2445);
							break;
						case 'RR':
							$viewall = $this->othersClass->checkAccess($this->config['params']['user'], 2232);
							break;
						case 'MI':
							$viewall = $this->othersClass->checkAccess($this->config['params']['user'], 2234);
							break;
						case 'JC':
						case 'BR':
							$viewall = 0;
							break;
						default:
							$viewall = 1;
							break;
					}

					if ($viewall == '0') {
						$pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$this->config['params']['user']]);
						if ($pid == '') {
							$this->config['return'] = ['head' => [], 'griddata' => [], 'islocked' => false, 'isposted' => false, 'status' => false, 'msg' => 'Please advice administrator to setup default project.', 'clickobj' => [], 'backlisting' => true];
							return $this;
						}
					}
				}

				switch ($this->config['params']['doc']) {
					case 'PP':
					case 'PA':
						$this->config['params']['fixcenter'] = '001';
						break;
				}

				$docno = trim($this->config['params']['docno']);
				$this->checkdocno($docno, 'GETLASTSEQ');
				$this->othersClass->setDefaultTimeZone();
				$this->config['head'] = $this->config['docmodule']->createnewtransaction($this->config['newdocno'], $this->config['params']);
				$hideobj = [];
				$breadcrumbs = [];
				$doc = $this->config['params']['doc'];
				switch ($doc) {
					case 'LE':
						$clickclient = ['button.cscategory'];
						$hideobj['lbllocked'] = true;
						$hideobj['lblapproved'] = true;
						break;
					case 'SG':
						$clickclient = ['button.cspartreqtype'];
						break;
					case 'CS':
						$clickclient = ['button.cswhname'];
						break;
					case 'OP':
					case 'TE':
					case 'OS':
					case 'WO':
						$hideobj['forquotation'] = true;
						$hideobj['doneapproved'] = true;
						$hideobj['cancel'] = true;
						$clickclient = [];
						break;
					case 'TA':
						$clickclient = [];
						$hideobj['submit'] = true;
						break;
					case 'PR':
						$hideobj['updatepostedinfo'] = true;
						$clickclient = ['button.csclient'];
						break;
					case 'CD':
						$hideobj['forchecking'] = true;
						$hideobj['forposting'] = true;
						$clickclient = ['button.csclient'];
						break;
					case 'PO':
						$clickclient = ['button.csclient'];
						if ($companyid == 16) { //ati
							$hideobj['forrevision'] = true;
							$hideobj['forapproval'] = true;
							$hideobj['doneapproved'] = true;
							$hideobj['ordered'] = true;
							$hideobj['updatepostedinfo'] = true;
							$hideobj['generatecode'] = true;
						} else {
							goto defaultobjhere;
						}
						break;
					case 'RR':
						$hideobj['lblreceived'] = true;
						switch ($companyid) {
							case 16: //ati
								$hideobj['forreceiving'] = true;
								$hideobj['forchecking'] = true;
								$hideobj['forrevision'] = true;
								$hideobj['updatepostedinfo'] = true;
								$hideobj['acknowledged'] = true;
								$hideobj['generatecode'] = true;
								$hideobj['forposting'] = true;
								$hideobj['create'] = true;
								$hideobj['intransit'] = true;
								break;
							case 56: //homeworks
								$hideobj['updatepostedinfo'] = true;
								break;
						}
						$clickclient = ['button.csclient'];

						if ($companyid == 60) { //transpower
							$clickclient = [];
						}

						if ($istodo) {
							$hideobj['donetodo'] = true;
						}
						break;
					case 'CA':
						$hideobj['submit'] = true;
						$hideobj['open'] = true;
						$hideobj['inprogress'] = true;
						$hideobj['resolved'] = true;
						$hideobj['reopen'] = true;
						$hideobj['posted'] = true;
						$hideobj['openstat'] = true;
						$hideobj['iprogresstat'] = true;
						$hideobj['resolvedstat'] = true;
						goto defaultobjhere;
						break;
					case 'HQ':
						$hideobj['approvedby1'] = true;
						$hideobj['disapprovedby1'] = true;
						$hideobj['approvedby2'] = true;
						$hideobj['disapprovedby2'] = true;
						$hideobj['namt4'] = true;
						$hideobj['namt5'] = true;
						goto defaultobjhere;
						break;

					case 'TM':
						$hideobj['forwtinput'] = true;
						$hideobj['lblpaid'] = true;
						$hideobj['forreceiving'] = true;
						$hideobj['lblsubmit'] = true;
						goto defaultobjhere;
						break;
					case 'DY':
						$hideobj['updatenotes'] = true;
						goto defaultobjhere;
						break;
					case 'MYINFO':
						switch ($companyid) {
							case 58: //cdohris
								$hideobj['lbllocked'] = true;
								goto defaultobjhere;
								break;
						}
						break;

					// case 'RO':
					// 	$hideobj['lbltaxes'] = true;
					// 	$hideobj['forwtinput'] = true;
					// 	$clickclient = ['button.csclient'];
					// 	break;

					case 'VR':
						$hideobj['lblreceived'] = true;
						$clickclient = ['button.csclient'];
						break;
					case 'OQ':
						$clickclient = [];
						$hideobj = ['forapproval' => true, 'donetodo' => true, 'forrevision' => true, 'doneapproved' => true,  'posted' => true, 'forposting' => true, 'lblinvreq' => true, 'lblforapproval' => true, 'lblapproved' => true, 'forso' => true, 'foror' => true];
						break;
					case 'OM':
						$clickclient = [];
						$hideobj['forreceiving'] = true;
						$hideobj['forso'] = true;
						$hideobj['forposting'] = true;
						break;
					case 'PV':
						$hideobj['lblpaid'] = true;
						$clickclient = ['button.csclient'];
						if ($istodo) {
							$hideobj['donetodo'] = true;
						}
						break;
					case 'SO':
						if ($companyid == 20) { //proline
							$hideobj['create'] = true;
						}
						if ($companyid == 19) { //housegem
							$hideobj['forrevision'] = true;
							$hideobj['forapproval'] = true;
							$hideobj['doneapproved'] = true;
							$hideobj['duplicatedoc'] = true;
						}
						goto defaultobjhere;
						break;
					case 'SJ':
						if ($companyid == 19) { //housegem
							$hideobj['posted'] = true;
							$hideobj['forwtinput'] = true;
						}
						goto defaultobjhere;
						break;
					case 'CV':
						if ($companyid == 16) { //ati
							$hideobj['updatepostedinfo'] = true;
							$hideobj['forapproval'] = true;
							$hideobj['itemscollected'] = true;
							$hideobj['forwardop'] = true;
							$hideobj['doneapproved'] = true;
							$hideobj['tagreleased'] = true;
							$hideobj['doneinitialchecking'] = true;
							$hideobj['donefinalchecking'] = true;
							$hideobj['forwardencoder'] = true;
							$hideobj['forwardwh'] = true;
							$hideobj['forwardasset'] = true;
							$hideobj['forliquidation'] = true;
							$hideobj['forwardacctg'] = true;
							$hideobj['forchecking'] = true;
							$hideobj['forposting'] = true;
							$hideobj['forrevision'] = true;
							$hideobj['checkissued'] = true;
							$hideobj['paid'] = true;
							$hideobj['checked'] = true;
							$hideobj['advancesclr'] = true;
							$hideobj['soareceived'] = true;
							$hideobj['post'] = true;
						}
						goto defaultobjhere;
						break;
					case 'GP':
						$hideobj['forapproval'] = true;
						$hideobj['doneapproved'] = true;
						goto defaultobjhere;
						break;
					case 'SS':
						$hideobj['forposting'] = true;
						goto defaultobjhere;
						break;
					case 'FC':
						$hideobj['create'] = true;
						goto defaultobjhere;
						break;
					case 'AF':
						$hideobj['lblattached'] = true;
						goto defaultobjhere;
						break;
					case 'CP':
						$clickclient = ['button.csafdocno'];
						$hideobj['lblattached'] = true;
						goto defaultobjhere;
						break;
					case 'CR':
						$hideobj['lblpaid'] = true;
						goto defaultobjhere;
						break;
					case 'VI':
						$hideobj['forclosing'] = true;
						$hideobj['lblrem'] = true;
						goto defaultobjhere;
						break;
					case 'EC':
						$clickclient = ['button.cscourse'];
						break;
					case 'ES':
					case 'EJ':
						$clickclient = ['button.cscurriculum'];
						break;
					case 'PA':
					case 'PP':
						$clickclient = [];
						$hideobj['voidtrans'] = true;
						break;
					default:
						defaultobjhere:
						if ($istodo) {
							$hideobj['donetodo'] = true;
						}
						switch ($companyid) {
							case 23: //labsol cebu
							case 41: //labsol manile
							case 52: //technolab
								$clickclient = [];
								break;
							case 24: //goodfound
							case 48: //seastar
								if ($doc == 'SJ') {
									$clickclient = [];
								} else {
									$clickclient = ['button.csclient'];
								}
								break;
							case 40: //cdo
								if ($doc == 'ST') {
									$clickclient = ['button.csdept'];
								} elseif ($doc == 'CI') {
									$clickclient = [];
								} else {
									$clickclient = ['button.csclient'];
								}
								break;
							case 57: //cdofinance
								if ($doc != 'CE') {
									$clickclient = ['button.csclient'];
								} else {
									$clickclient = [];
								}
								break;
							case 60: //transpower
								$clickclient = [];
								break;
							default:
								$clickclient = ['button.csclient'];
								break;
						}

						break;
				}
				$this->config['return'] = [
					'head' => $this->config['head'],
					'griddata' => [$this->config['docmodule']->gridname => []],
					'islocked' => false,
					'isposted' => false,
					'status' => true,
					'msg' => 'Ready for New Transaction',
					'clickobj' => $clickclient,
					'hideobj' => $hideobj,
					'breadcrumbsbottom' => $breadcrumbs
				];
			} //end if
			return $this;
		} catch (Exception $e) {
			echo $e;
		}
	} //end function

	public function searchbarcode()
	{
		$barcode = trim($this->config['params']['barcode']);
		$barcodelength = $this->companysetup->getbarcodelength($this->config['params']);
		if ($barcodelength > 0 && $this->config['params']['companyid'] != 16) { //not ati
			$this->checkstockcardbarcode($barcode, 'GET');
			$barcode = $this->config['newbarcode'];
		}
		$this->config['params']['barcode'] = $barcode;

		if ($this->config['params']['moduletype'] == 'FIXEDASSET') {
			$itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=? and isfa=1', [$barcode]);
		} else {
			$itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=? and isfa=0', [$barcode]);
		}


		if ($itemid == '') {
			$this->newstockcard(false);
		} else {
			$this->config['params']['itemid'] = $itemid;
			$this->loadheaddata();
		}
		return $this;
	} //end function


	public function searchclient()
	{
		$client = $this->config['params']['client'];
		$client2 = $this->config['params']['client'];

		switch ($this->config['params']['doc']) {
			case "CODECONDUCT": // for ledgergrid template without auto padding
				break;
			default:
				$clientlength = $this->companysetup->getclientlength($this->config['params']);
				if ($clientlength != 0) {
					//$client = $this->othersClass->PadJ($client, $clientlength);//not padding if entered prefix sa masterfile modules
					$this->checkclientcode($client, 'GET');
					$client = $this->config['newclient'];
				}
				break;
		}

		$this->coreFunctions->LogConsole($client . 'searchclient');

		if (isset($this->config['newclient'])) {
			$this->checkclientcode($client, 'GET');
			$client = $this->config['newclient'];
			$this->config['params']['client'] = $client;
		}

		$c = $this->getClient($this->config['params']['doc']);

		$this->coreFunctions->LogConsole(json_encode($c));

		if (isset($c[3])) $client = $client2;
		$clientid = $this->coreFunctions->getfieldvalue($c[0], $c[1], $c[2] . '=?', [$client]);
		if ($clientid == '') {
			$this->newclient(false);
		} else {
			$this->config['params']['clientid'] = $clientid;
			$this->loadheaddata();
		}
		return $this;
	} //end function

	private function getClient($doc)
	{
		//return params @tableName, @fieldIncrement, @fieldCode
		switch (strtoupper($doc)) {
			case 'TM':
				return array('tmhead', 'trno', 'clientid');
				break;
			case 'DY':
				return array('dailytask', 'trno', 'clientid');
				break;
			case 'EN_COURSE':
				return array('en_course', 'line', 'coursecode', 0);
				break;
			case 'EN_STUDDENT':
				return array('client', 'clientid', 'client');
				break;
			case 'EN_SUBJECT':
				return array('en_subject', 'trno', 'subjectcode', 0);
				break;
			case 'CLEARANCE':
				return array('clearance', 'trno', 'docno');
				break;
			case 'APPLICANTLEDGER':
				return array('app', 'empid', 'empcode');
				break;
			case 'EN_COURSE':
				return array('en_course', 'line', 'coursecode');
				break;
			case 'EN_ROOMLIST':
				return array('en_bldg', 'line', 'bldgcode');
				break;
			case 'CODECONDUCT':
				return array('codehead', 'artid', 'code');
				break;
			case 'JOBTITLEMASTER':
				return array('jobthead', 'line', 'docno');
				break;
			case 'ROOMTYPE':
				return array('tblroomtype', 'id', 'roomtype');
				break;
			case 'SHIFTSETUP':
				return array('tmshifts', 'line', 'shftcode');
				break;
			case 'BATCHSETUP':
				return array('batch', 'line', 'batch');
				break;
			case 'EARNINGDEDUCTIONSETUP':
			case 'LOANAPPLICATION':
				return array('standardsetup', 'trno', 'docno');
				break;
			case 'ADVANCESETUP':
				return array('standardsetupadv', 'trno', 'docno');
				break;
			case 'LEAVESETUP':
				return array('leavesetup', 'trno', 'docno');
				break;
			case 'REPLENISHITEM':
				return array('lahead', 'trno', 'docno');
				break;
			case 'LOCATIONLEDGER':
				return array('loc', 'line', 'code');
				break;
			case 'LOANAPPLICATIONPORTAL':
				return array('loanapplication', 'trno', 'docno');
				break;
			default:
				return array('client', 'clientid', 'client');
				break;
		}
	}

	public function searchdocno()
	{
		try {
			$docno = $this->config['params']['docno'];
			$isversion = $this->othersClass->isnumber($this->othersClass->Getsuffix($docno));
			$this->coreFunctions->LogConsole($docno . '1searchdocno');
			$this->checkdocno($docno, 'GET');
			$this->isdocnoprefixvalid();
			$blnExist = $this->config['isdocnoprefixvalid'];
			$pref = $this->config['pref'];
			$seq = $this->config['seq'];
			$newdocno = $this->config['newdocno'];
			$docnolength = $this->config['docnolength'];
			$yr = $this->config['yr'];
			if ($blnExist) {
				if ($isversion) {
					$poseq = $pref . $seq;
				} else {
					$poseq = $docno;
					$newdocno = $docno;
				}

				$newdocno = $this->othersClass->PadJ($poseq, $docnolength, $yr);
				$this->coreFunctions->LogConsole($newdocno . '2searchdocno');

				switch ($this->config['params']['doc']) {
					case 'PA':
					case 'PP':
						if ($this->companysetup->getmultibranch($this->config['params'])) {
							$this->config['params']['fixcenter'] = '001';
							$this->othersClass->logConsole('fixcenter: 001');
						}
						break;
				}

				$trno = $this->othersClass->gettrnodocno($newdocno, $this->config);
				//$this->config['return'] = ['newdocno'=>$newdocno,'poseq'=>$poseq];
				//return $this;
				$this->config['params']['docno'] = $newdocno;
				if ($trno == '') {
					if ($this->config['verifyaccess'] == 1) {
						if ($this->config['params']['doc'] == 'PX') {
							$pcfadmin = $this->othersClass->checkAccess($this->config['params']['user'], 5389);
							if ($pcfadmin == 0) {
								$t = date("H:i");
								if ($t > '16:45') {
									$this->config['return'] = ['head' => [], 'griddata' => [], 'islocked' => false, 'isposted' => false, 'status' => false, 'msg' => 'PCF Creation until 4:45pm only.', 'clickobj' => [], 'backlisting' => false];
									return $this;
								}
							}
						}
						$this->config['head'] = $this->config['docmodule']->createnewtransaction($this->config['newdocno'], $this->config['params']);
						$this->config['return'] = ['head' => $this->config['head'], 'griddata' => ['inventory' => []], 'isnew' => true, 'islocked' => false, 'isposted' => false, 'status' => true, 'msg' => 'Ready for New Transaction'];
					}
				} else {
					if ($this->config['access'][0]['attributes'][$this->config['mattrib']['view']] == 1) {
						$this->config['params']['trno'] = $trno;
						$this->config['return'] = $this->config['docmodule']->loadheaddata($this->config);
					}
				}
			} else {
				$prefix = "/";
				$prefixes = $this->othersClass->getPrefixes($this->config['params']['doc'], $this->config);
				if (empty($prefixes)) {
					if ($this->config['params']['doc'] == 'SB') {
						$message = ''; //'Please setup valid prefix for Branch ';
						$this->config['return'] = ['trno' => 0, 'docno' => '', 'status' => true, 'msg' => $message, 'type' => ''];
						return $this;
					} else {
						$message = 'Please setup valid prefix in "MANAGE PREFIXES" module under Transaction Utilities.';
					}
				} else {
					$message = '';
					if ($this->config['params']['companyid'] == 16 && $this->config['params']['doc'] == 'PO') { //ati
					} else {
						checksetuprefixhere:
						for ($x = 0; $x < count($prefixes); $x++) {
							$prefix .= $prefixes[$x] . " / ";
						} //END FOR EACH
						$message = 'Invalid prefix , Available prefixes are: [' . $prefix . ']';
					}
				}
				$this->config['return'] = ['trno' => 0, 'docno' => '', 'status' => false, 'msg' => $message, 'type' => ''];
			}
			return $this;
		} catch (Exception $e) {
			echo $e;
		}
	} // end function

	public function savestockcard()
	{
		try {
			$itemid = $this->config['params']['itemid'];
			$barcode = $this->config['params']['head']['barcode'];
			$companyid = $this->config['params']['companyid'];

			$barcodelength = $this->companysetup->getbarcodelength($this->config['params']);
			$barcode = $this->othersClass->PadJ($barcode, $barcodelength);
			$this->config['params']['head']['barcode'] = $barcode;
			if ($itemid != 0) {
				$itemid = $this->config['docmodule']->updatehead($this->config, true);
			} else {
				switch ($companyid) {
					case 10: //afti
					case 12: //afti usd
						$i = 0;
						if (substr($barcode, 0, 1) == 'A') {
							$i = 1;
						}
						$exist  = $this->coreFunctions->datareader("select partno as value from item where isoutsource=" . $i . " and partno = '" . $this->config['params']['head']['partno'] . "' and isinactive =0  limit 1");
						if ($exist != "") {
							$this->config['return'] = ['isnew' => true, 'head' => $this->config['params']['head'], 'status' => false, 'msg' => 'SKU/Part No. already exist.'];
							return $this;
						} else {
							$itemid = $this->config['docmodule']->updatehead($this->config, false);
						}
						break;
					case 40: //cdo						
						$exist  = $this->coreFunctions->datareader("select partno as value from item where partno = '" . $this->config['params']['head']['partno'] . "' and isinactive =0  limit 1");
						if ($exist != "") {
							$this->config['params']['head']['partno'] = '';
							$itemid = $this->config['docmodule']->updatehead($this->config, false);
							$this->config['msg'] = 'Part No. already exist.';
						} else {
							$itemid = $this->config['docmodule']->updatehead($this->config, false);
						}
						break;
					case 47: //kitchenstar
						if (floatval($this->config['params']['head']['foramt']) == 0) {
							$this->config['return'] = ['isnew' => true, 'head' => $this->config['params']['head'], 'status' => false, 'msg' => 'Floor Price cannot be zero.'];
							return $this;
						} else {
							$itemid = $this->config['docmodule']->updatehead($this->config, false);
						}
						break;
					default:
						$itemid = $this->config['docmodule']->updatehead($this->config, false);
						break;
				}
			}
			$this->config['params']['itemid'] = $itemid;
			$this->loadheaddata();
			return $this;
		} catch (Exception $e) {
			echo $e;
		}
	}


	public function saveledgerhead()
	{
		try {
			$clientid = $this->config['params']['clientid'];
			$client = $this->config['params']['head']['client'];

			$c = $this->getClient($this->config['params']['doc']);
			switch ($c[0]) {
				case 'en_subject':
				case 'en_course':
				case 'en_bldg':
				case 'codehead':
				case 'tblroomtype':
				case 'tmshifts':
				case 'batch':
				case 'replenishitem':
					break;
				case 'tmhead';
				case 'dailytask';
					$clientid = $this->config['params']['head']['trno'];
					break;
				default:
					$clientlength = $this->companysetup->getclientlength($this->config['params']);
					$client = $this->othersClass->PadJ($client, $clientlength);
					$this->config['params']['head']['client'] = $client;
					break;
			}

			if ($clientid != 0) {
				$result = $this->config['docmodule']->updatehead($this->config, true);
			} else {
				$result = $this->config['docmodule']->updatehead($this->config, false);
			}

			if ($result['status']) {
				$this->config['params']['clientid'] = $result['clientid'];
				if (isset($result['msg'])) {
					$this->config['msg'] = $result['msg'];
				}
				$this->loadheaddata();
			} else {
				$this->config['return'] = $result;
			}

			return $this;
		} catch (Exception $e) {
			echo $e;
		}
	}

	public function savehead()
	{
		try {
			if ($this->config['verifyaccess'] == 1) {
				if ($this->config['isposted']) {
					return $this;
				}
				if ($this->config['islocked']) {
					return $this;
				}
				if (!$this->othersClass->checkDefaultWH($this->config)) {
					$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Invalid warehouse code ' . $this->config['params']['head']['wh'], 'type' => '', 'status' => false];
					return $this;
				}

				$systemtype = $this->companysetup->getsystemtype($this->config['params']);
				if ($systemtype != 'SSMS') {
					if (isset($this->config['params']['head']['terms'])) {
						if ($this->config['params']['head']['terms'] != '') {
							$terms_exist = $this->coreFunctions->getfieldvalue("terms", "terms", "terms=?", [$this->config['params']['head']['terms']]);

							if ($terms_exist == '') {
								$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Terms ' . $this->config['params']['head']['terms'] . ' does not exist', 'type' => '', 'status' => false];
								return $this;
							}
						}
					}
				}

				if ($this->config['params']['companyid'] == 10 || $this->config['params']['companyid'] == 12) { //afti, afti usd
					if ($this->config['params']['doc'] == 'OS') {
						if ($this->config['params']['head']['vendor'] != 'AFTECH') {
							if ($this->config['params']['head']['client'] == '') {
								$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Local Vendor is required', 'type' => '', 'status' => false];
								return $this;
							}
						}
					}
				}

				if ($this->config['params']['companyid'] == 55) { //afli
					if ($this->config['params']['doc'] == 'LE') {
						if ($this->config['params']['head']['civilstatus'] == 'Married') {
							if (
								$this->config['params']['head']['sname'] == '' || $this->config['params']['head']['companyaddress'] == ''
							) {
								$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Borrower\'s Spouse Information Required', 'type' => '', 'status' => false];
								return $this;
							}
						}
						if ($this->config['params']['head']['civilstat'] == 'Married') {
							if ($this->config['params']['head']['empfirst'] == '' || $this->config['params']['head']['pstreet'] == '') {
								$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Co Maker\'s Spouse Information Required', 'type' => '', 'status' => false];
								return $this;
							}
						}
					}
				}


				if ($this->config['params']['companyid'] == 36) { //rozlab
					if ($this->config['params']['doc'] == 'AP') {
						$arrOurref = $this->coreFunctions->opentable("select ourref from lahead where ourref='" . $this->config['params']['head']['ourref'] . "' and trno<>" . $this->config['params']['head']['trno'] . " union all select ourref from glhead where ourref='" . $this->config['params']['head']['ourref'] . "' and trno<>" . $this->config['params']['head']['trno']);
						if (!empty($arrOurref)) {
							$msg = "Ourref " . $this->config['params']['head']['ourref'] . " already exists";
							$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => $msg, 'type' => '', 'status' => false];
							return $this;
						}
					}
				}

				if ($this->config['params']['doc'] == 'PW') {
					$pwexist = $this->coreFunctions->opentable(
						"select trno from pwhead where trno<>" . $this->config['params']['trno'] .
							" and date(dateid)=? and pwrcat=? union all select trno from hpwhead where trno<>" . $this->config['params']['trno'] . " and date(dateid)=? and pwrcat=?",
						[$this->config['params']['head']['dateid'], $this->config['params']['head']['pwrcat'], $this->config['params']['head']['dateid'], $this->config['params']['head']['pwrcat']]
					);
					if (!empty($pwexist)) {
						$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'moduleClass Error: Unable to proceed, there is an existing reading of ' . $this->config['params']['head']['categoryname'] . ' for date ' . $this->config['params']['head']['dateid'], 'type' => '', 'status' => false];
						return $this;
					}
				}

				if ($this->config['params']['companyid'] == 16) { //ati
					switch ($this->config['params']['doc']) {
						case 'PO':
							if ($this->config['params']['head']['prefix'] != '') {
								$poprefix = $this->othersClass->GetPrefix($this->config['params']['head']['docno']);
								if ($this->config['params']['head']['prefix'] != $poprefix) {
									$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => "Please use the prefix " . $this->config['params']['head']['prefix'] . " assigned to supplier " . $this->config['params']['head']['clientname'], 'type' => '', 'status' => false];
									return $this;
								}
							}
							break;
						case 'CV':
							$editapprovedetails = $this->othersClass->checkAccess($this->config['params']['user'], 4144);
							if (!$editapprovedetails) {
								$approve = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerdone", "trno=?", [$this->config['params']['trno']]);
								if ($approve != '') {
									$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'You are not allowed to update approved CV', 'type' => '', 'status' => false];
									return $this;
								}
							}
							break;
					}
				}

				if ($this->config['params']['doc'] != 'QS' &&  $this->config['params']['doc'] != 'TI' &&  $this->config['params']['doc'] != 'OI' &&  $this->config['params']['doc'] != 'PX') {
					//Inventory Cuff-off
					if (!$this->othersClass->checkInvCutOffDate($this->config['params']['head']['dateid'])) {
						$InvCutOffDate = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='RX' and psection='INVCUTOFF'");
						$InvCutOffDatemsg = 'Cannot create/edit Document, inventory cut-off date ' . $InvCutOffDate;
						$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => $InvCutOffDatemsg, 'type' => '', 'status' => false];
						return $this;
					}

					//Lock Date
					if (!$this->othersClass->checkLockDate($this->config['params']['head']['dateid'])) {
						$lockdate = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='SYSL'");
						$lockdatemsg = 'Cannot create/edit Document. System Date is locked from date ' . $lockdate;
						$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => $lockdatemsg, 'type' => '', 'status' => false];
						return $this;
					}
				}


				if ($this->config['params']['doc'] == 'BR') {
					$bltrnocheck = $this->coreFunctions->opentable("
					select bltrno,projectid from hbrhead where bltrno=0 and projectid=" . $this->config['params']['head']['projectid'] . "");
					if (empty($bltrnocheck)) {
					} else {
						$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Check BR Liquidity', 'type' => '', 'status' => false];
						return $this;
					}
				}

				//check plan limit
				if ($this->config['params']['companyid'] == 34) { //evergreen
					if ($this->config['params']['doc'] == 'AF') {
						if (!$this->othersClass->getplanlimit($this->config['params']['head']['plangrpid'], floatval($this->config['params']['head']['amount']), $this->config['params']['trno'])) {
							$allowoverride = $this->othersClass->checkAccess($this->config['params']['user'], 1729);
							if (!$allowoverride) {
								$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Above Plan Limit. Cannot Save. Please change Plan type.', 'type' => '', 'status' => false];
								return $this;
							}
						}
					}
				}

				if ($this->companysetup->getdocyr($this->config['params'])) {
					$pref = $this->othersClass->GetPrefix($this->config['params']['head']['docno']);
					$yr = floatval($this->coreFunctions->datareader("select yr as value FROM profile where psection ='" . $this->config['params']['doc'] . "' and doc ='SED'"));
					$this->config['params']['head']['docno'] = str_replace($pref . $yr, $pref, $this->config['params']['head']['docno']);
					$this->coreFunctions->LogConsole($this->config['params']['head']['docno']);
				}

				switch ($this->config['params']['doc']) {
					case 'PA':
					case 'PP':
						if ($this->companysetup->getmultibranch($this->config['params'])) {
							$this->config['params']['fixcenter'] = '001';
							$this->othersClass->logConsole('fixcenter: 001');
						}
						break;
				}

				if ($this->config['params']['companyid'] == 59) { //roosevelt
					if ($this->config['params']['doc'] == 'SO' || $this->config['params']['doc'] == 'SJ') {
						$clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$this->config['params']['head']['client']]);
						$checkhold = $this->coreFunctions->datareader("select ishold as value from client where clientid=" . $clientid, [], '', true);
						if ($checkhold == 1) {
							$rem = $this->coreFunctions->getfieldvalue("client", "rem", "clientid=?", [$this->config['params']['head']['clientid']]);
							$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => 'Customer is currently on hold. ', 'status' => false];
							return $this;
						}
					}
				}

				$this->checkdocno($this->config['params']['head']['docno'], 'GET');
				$this->config['params']['head']['docno'] = $this->config['newdocno'];
				$this->isdocnoprefixvalid();
				$blnExist = $this->config['isdocnoprefixvalid'];
				if ($blnExist) {
					if ($this->config['params']['trno'] != 0) {
						// update
						$returnhead = $this->config['docmodule']->updatehead($this->config, true);

						if (isset($returnhead['status'])) {
							if (!$returnhead['status']) {
								$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => $returnhead['msg'], 'type' => '', 'status' => false];
								return $this;
							}
						}
					} else {
						$this->inserthead();
					}
					// get the latest data
					$this->loadheaddata();
				} else {
					$prefix = "/";
					$prefixes = $this->othersClass->getPrefixes($this->config['params']['doc'], $this->config);
					if (empty($prefixes)) {
						if ($this->config['params']['doc'] == 'SB') {
							$message = 'Please setup valid prefix for Branch ' . $this->config['params']['head']['clientname'];
						} else {
							$message = 'Please setup valid prefix in "MANAGE PREFIXES" module under Transaction Utilities.';
						}
					} else {
						if ($this->config['params']['companyid'] == 16 && $this->config['params']['doc'] == 'PO') { //ati
							if ($this->config['params']['head']['prefix'] == '') {
								goto checksetuprefixhere;
							}
						} else {
							checksetuprefixhere:
							for ($x = 0; $x < count($prefixes); $x++) {
								$prefix .= $prefixes[$x] . " / ";
							} //END FOR EACH
							$message = 'Invalid prefix , Available prefixes are: [' . $prefix . ']';
						}
					}
					$this->config['return'] = ['trno' => 0, 'docno' => '', 'msg' => $message, 'type' => '', 'status' => false];
				} // else if blnExist
			} //end if
			return $this;
		} catch (Exception $e) {
			echo $e;
		}
	} // end function


	private function checkstockcardbarcode($barcode, $action)
	{
		$barcode = $this->othersClass->sanitize($barcode, 'STRING');
		$barcodelength = $this->companysetup->getbarcodelength($this->config['params']);
		$pref = '';

		if (strlen($barcode) != 0) {
			$pref = $this->othersClass->GetPrefix($barcode);

			if ($pref != $this->config['docmodule']->prefix) {
				$pref = $this->config['docmodule']->prefix;
				if ($this->config['params']['companyid'] == 10 || $this->config['params']['companyid'] == 12) { //afti, afti usd
					if ($this->config['params']['moduletype'] == 'OUTSOURCE') {
						$pref = 'A';
					} else {
						$pref = 'I';
					}
				}
				if ($this->config['params']['companyid'] == 16) $pref = 'ITM'; //ati
			}
		} else {
			$pref = $this->config['docmodule']->prefix;
		} //end if
		if (strlen($pref) == 0) {
			$pref = $this->config['docmodule']->prefix;
		}
		if (!$pref) {
			$prefixes = $this->othersClass->getPrefixes($this->config['params']['doc'], $this->config);
			$pref = isset($prefixes[0]) ? $prefixes[0] : $this->config['params']['doc'];
		} //end if $pref

		switch ($action) {
			case 'GETLASTSEQ':
				$barcode2 = $this->config['docmodule']->getlastbarcode($pref, $this->config['params']['companyid']);
				$seq = intval(substr($barcode2, $this->othersClass->SearchPosition($barcode2), strlen($barcode2)));
				$seq += 1;
				break;
			default:
				$seq = intval(substr($barcode, $this->othersClass->SearchPosition($barcode), strlen($barcode)));
				break;
		}

		if ($seq == 0 || empty($pref)) {
			if (empty($pref)) {
				$pref = strtoupper($barcode);
			}
			$barcode2 = $this->config['docmodule']->getlastbarcode($pref, $this->config['params']['companyid']);
			$seq = intval(substr($barcode2, $this->othersClass->SearchPosition($barcode2), strlen($barcode2)));
			$seq += 1;
		}
		$poseq = $pref . $seq;

		$newbarcode = $this->othersClass->PadJ($poseq, $barcodelength);
		$this->config['pref'] = $pref;
		$this->config['seq'] = $seq;
		$this->config['newbarcode'] = $newbarcode;
		$this->config['barcodeseq'] = $seq;
		$this->config['barcodelength'] = $barcodelength;
		return $this;
	} //end function


	private function checkclientcode($client, $action)
	{
		$companyid = $this->config['params']['companyid'];
		$client = $this->othersClass->sanitize($client, 'STRING');
		$clientlength = $this->companysetup->getclientlength($this->config['params']);
		$pref = '';
		if (strlen($client) != 0) {
			if ($companyid == 29 && $this->config['params']['doc'] == 'CUSTOMER') { //sbc
				$pref =  $this->coreFunctions->getfieldvalue("center", "clprefix", "code=?", [$this->config['params']['center']]);;
			} else {
				$pref = $this->othersClass->GetPrefix($client);
			}
		} else { //new
			$pref = $this->config['docmodule']->prefix;
		} //end if

		if (strlen($pref) == 0) {
			$pref = $this->config['docmodule']->prefix;
		}

		$this->coreFunctions->LogConsole($pref . '3rd');
		$defaultprefix = $this->config['params']['doc'];
		$prefixdoc = $this->config['params']['doc'];
		if ($pref != '') {
			switch ($this->config['params']['doc']) {
				case 'CUSTOMER':
					if ($companyid == 29) { //sbc
						$prefixdoc =  $pref;
					} else {
						$prefixdoc = 'CL';
					}
					break;
				case 'SUPPLIER':
					$prefixdoc = 'SL';
					break;
				case 'AGENT':
					$prefixdoc = 'AG';
					break;
				case 'SUPPLIER':
					$prefixdoc = 'SL';
					break;
				case 'WAREHOUSE':
					$prefixdoc = 'WH';
					break;
				case 'DEPARTMENT':
					$prefixdoc = 'DE';
					break;
				case 'EMPLOYEE':
				case 'EP':
					$prefixdoc = 'EM';
					break;
				case 'BRANCH':
					$prefixdoc = 'BH';
					break;
				case 'TENANT':
					$prefixdoc = 'TL';
					break;
				case 'FINANCINGPARTNER':
					$prefixdoc = 'FP';
					break;
				case 'LOANAPPLICATIONPORTAL':
					$prefixdoc = 'LA';
					$defaultprefix = $prefixdoc;
					break;
				case 'LOANAPPLICATION':
					$prefixdoc = 'EL';
					$defaultprefix = $prefixdoc;
					break;
				case 'APPLICANTLEDGER':
				case 'EARNINGDEDUCTIONSETUP':
				case 'ADVANCESETUP':
				case 'JOBTITLEMASTER':
				case 'LEAVESETUP':
					$prefixdoc = $this->config['docmodule']->prefix;
					$defaultprefix = $prefixdoc;
					break;
				case 'FORWARDER':
					$prefixdoc = $this->config['docmodule']->prefix;
					$defaultprefix = $prefixdoc;
					break;
				case 'EN_ROOMLIST':
					$prefixdoc = 'BLDG';
					$defaultprefix = $prefixdoc;
					break;
				case 'EN_STUDENT':
					$prefixdoc = 'ENSTD';
					$defaultprefix = $prefixdoc;
					break;
				case 'EN_INSTRUCTOR':
					$prefixdoc = 'ENINS';
					$defaultprefix = $prefixdoc;
					break;
				case 'INFRA':
					$prefixdoc = 'IF';
					break;
				default:
					$prefixdoc = $this->config['params']['doc'];
					break;
			}
			// $prefixes = $this->othersClass->getPrefixes($prefixdoc, $this->config);//not padding if entered prefix sa masterfile modules
			// $pref = isset($prefixes[0]) ? $prefixes[0] : $defaultprefix;
			// $this->coreFunctions->LogConsole($pref.'4th');
		} //end if $pref

		//added this to pad if entered prefix sa masterfile modules
		if (!$pref) {
			$prefixes = $this->othersClass->getPrefixes($prefixdoc, $this->config);
			$pref = isset($prefixes[0]) ? $prefixes[0] : $defaultprefix;
		}
		switch ($action) {
			case 'GETLASTSEQ':
				$client2 = $this->config['docmodule']->getlastclient($pref);
				$seq = intval(substr($client2, $this->othersClass->SearchPosition($client2), strlen($client2)));
				if ($seq == '' || empty($seq)) $seq = 0;
				$seq += 1;

				break;
			default:
				$seq = intval(substr($client, $this->othersClass->SearchPosition($client), strlen($client)));
				break;
		}
		$this->coreFunctions->LogConsole($seq . '5th');
		if ($seq == 0 || empty($pref)) {
			if (empty($pref)) {
				$pref = strtoupper($client);
			}
			$client2 = $this->config['docmodule']->getlastclient($pref);
			$this->coreFunctions->LogConsole($client2 . '6th');
			$seq = (substr($client2, $this->othersClass->SearchPosition($client2), strlen($client2)));
			if ($seq == '' || empty($seq)) $seq = 0;
			$seq += 1;
		}
		$poseq = $pref . $seq;
		$c = $this->getClient($this->config['params']['doc']);
		switch ($c[0]) {
			case 'en_subject':
			case 'en_course':
			case 'codehead':
			case 'tblroomtype':
			case 'tmshifts':
			case 'batch':
			case 'replenishitem':
				// case 'standardsetup':
				$newclient = '';
				break;
			default:
				$newclient = $this->othersClass->PadJ($poseq, $clientlength);
				break;
		}
		$this->config['pref'] = $pref;
		$this->config['seq'] = $seq;
		$this->config['newclient'] = $newclient;
		$this->config['clientlength'] = $clientlength;
		return $this;
	} //end function


	private function checkdocno($docno, $action)
	{
		$docno = $this->othersClass->sanitize($docno, 'STRING');
		$docnolength = $this->companysetup->getdocumentlength($this->config['params']);
		$pref = '';

		if (strlen($docno) != 0) {
			$pref = $this->othersClass->GetPrefix($docno);
		} else {
			$pref = $this->othersClass->last_bref($this->config);
		} //end if

		if (strlen($pref) == 0) {
			$pref = $this->othersClass->last_bref($this->config);
		}

		if (!$pref) {
			$prefixes = $this->othersClass->getPrefixes($this->config['params']['doc'], $this->config);
			if ($this->config['params']['doc'] == 'SB') {
				$pref = isset($prefixes[0]) ? $prefixes[0] : '';
			} else {
				$pref = isset($prefixes[0]) ? $prefixes[0] : $this->config['params']['doc'];
			}
		} //end if $pref

		switch ($action) {
			case 'GETLASTSEQ':
				$seq = $this->othersClass->getlastseq($pref, $this->config);
				break;
			default:
				$seq = (substr($docno, $this->othersClass->SearchPosition($docno), strlen($docno)));
				break;
		}

		$this->coreFunctions->LogConsole($seq . $action);
		if (empty($seq) || empty($pref)) {
			if (empty($pref)) {
				$pref = strtoupper($docno);
			}
			$seq = $this->othersClass->getlastseq($pref, $this->config);
		}


		$poseq = $pref . $seq;
		$yr = $this->coreFunctions->datareader("select yr as value FROM profile where psection ='" . $this->config['params']['doc'] . "' and doc ='SED'");
		$newdocno = $this->othersClass->PadJ($poseq, $docnolength, $yr);
		$this->config['pref'] = $pref;
		$this->coreFunctions->logconsole('POSEQ: ' . $poseq);
		if ($this->config['params']['companyid'] == 10 || $this->config['params']['companyid'] == 12) { //afti, afti usd
			if ($this->config['params']['doc'] == 'QS') {
				if (strlen($poseq) == $docnolength && $action <> 'GETLASTSEQ') {
					if (substr($seq, 0, 4) <> $yr) {
						$yr = substr($seq, 0, 4);
						$seq = substr($seq, 4, strlen($seq));
					}
					$this->coreFunctions->logconsole('Seq: ' . substr($seq, 0, 4));
				}
			}

			$this->coreFunctions->logconsole('Seq: ' . $seq);
		}

		$this->config['seq'] = $seq;
		$this->config['newdocno'] = $newdocno;
		$this->config['yr'] = $yr;
		$this->config['docnolength'] = $docnolength;
		return $this;
	} //end function

	// need to call savehead first
	private function inserthead()
	{
		$pref = $this->config['pref'];
		$seq = $this->config['seq'];
		$docno = $this->config['newdocno'];
		$blnExist = $this->config['isdocnoprefixvalid'];
		$docnolength = $this->config['docnolength'];
		$center = $this->config['params']['center'];
		$doc = $this->config['params']['doc'];
		$yr = $this->config['yr'];
		$message = '';

		if (isset($this->config['params']['fixcenter'])) {
			$center = $this->config['params']['fixcenter'];
			$this->othersClass->logConsole('inserthead fixcenter: 001');
		}

		$insertcntnum = $this->insertcntnum($doc, $docno, $seq, $pref, $center, $yr);
		if ($insertcntnum == 0) {
			$i = 5;
			while ($insertcntnum == 0 && $i >= 1) {
				$i = $i - 1;
				$this->checkdocno($docno, 'GETLASTSEQ');
				$this->isdocnoprefixvalid();
				$pref = $this->config['pref'];
				$seq = $this->config['seq'];
				$newdocno = $this->config['newdocno'];
				$yr = $this->config['yr'];
				$blnExist = $this->config['isdocnoprefixvalid'];
				$insertcntnum = $this->insertcntnum($doc, $newdocno, $seq, $pref, $center, $yr);

				if (($docno != $newdocno) && ($insertcntnum != 0)) {
					$odocno = $docno;
					$docno = $newdocno;
					$this->config['params']['head']['docno'] = $newdocno;
					$this->config['msg'] = 'Your transaction (' . $odocno . ') has been saved under document # ' . $newdocno;
					$current_timestamp = $this->othersClass->getCurrentTimeStamp();
					// $data = ['e_detail' => 'ERROR QUERY', 'date_executed' => $current_timestamp, 'querystring' => $this->config['msg']];
					// $this->coreFunctions->sbcinsert('execution_log', $data);
				}
			} //end white insertcntnum
		} //END insertcntnum 0

		if ($insertcntnum == 0) {
			$msg = '1.Error cannot create Document. Please try again.';
			$this->config['msg'] = $msg;
			$current_timestamp = $this->othersClass->getCurrentTimeStamp();
			$data = ['e_detail' => 'ERROR QUERY', 'date_executed' => $current_timestamp, 'querystring' => $this->config['msg']];
			$this->coreFunctions->sbcinsert('execution_log', $data);
			$this->config['return'] = ['trno' => '', 'docno' => '', 'msg' => $msg, 'head' => [], 'type' => '', 'istransposted' => false];
		} else {
			$trno = $insertcntnum;
			$this->config['params']['head']['trno'] = $trno;
			$this->config['params']['trno'] = $trno;

			$this->config['docmodule']->updatehead($this->config, false);
		} //end else if($insertcntnum==0) {

		return $this;
	}

	private function insertcntnum($doc, $docno, $seq, $bref, $center, $yr = 0)
	{
		if (!empty($center) || $center != '') {
			$col = [];
			$col = ['doc' => $doc, 'docno' => $docno, 'seq' => $seq, 'bref' => $bref, 'center' => $center, 'yr' => $yr];
			$table = $this->config['docmodule']->tablenum;
			return $this->coreFunctions->insertGetId($table, $col);
		} else {
			return -1;
		} //end if empty center
	}


	//need to call function checkdocno first
	private function isdocnoprefixvalid()
	{
		$prefixes = $this->othersClass->getPrefixes($this->config['params']['doc'], $this->config);
		$blnExist = false;
		if (!empty($prefixes)) {
			for ($i = 0; $i < count($prefixes); $i++) {
				if ($this->config['pref'] == $prefixes[$i]) {
					$blnExist = true;
				} //END COMPARE
			} //END FOR LOOP
		} //END IF else
		$this->config['isdocnoprefixvalid'] = $blnExist;
		return $this;
	} //end function

	public function getmoduleaccess($attrib)
	{
		$access = $this->config['access'];
		foreach ($attrib as $key => $value) {
			if ($access[0]['attributes'][$value - 1] == 1) {
				$maccess[$key] = true;
			} else {
				$maccess[$key] = false;
			}
		} //end for each
		return $maccess;
	} //end function


	public function openstock()
	{
		$trno = $this->config['params']['trno'];
		$stock = $this->config['docmodule']->openstock($trno, $this->config);
		if (!empty($stock)) {
			$this->config['return'] =  response()->json(['griddata' => ['inventory' => $stock], 'status' => true, 'msg' => $this->config['params']['user'] . ' Data Stock Fetched Success'], 200);
		} else {
			$this->config['return'] = ['status' => false, 'griddata' => ['inventory' => []], 'msg' => $this->config['params']['user'] . ' Data Stock Fetched Failed'];
		}
		return $this;
	} // end function

	public function checkperstock()
	{
		if ($this->config['isposted']) {
			return $this;
		}
		if ($this->config['islocked']) {
			return $this;
		}

		$ischange = false;
		$msg = '';
		$editedfield = $this->config['params']['editedfield'];
		$editedfieldval = $this->config['params']['editedfieldval'];
		$editedfieldval = str_replace(["\r", "\n", "\t", "\r\n"], '', $editedfieldval);
		$this->config['params']['line'] = $this->config['params']['row']['line'];

		if ($this->config['params']['doc'] == 'DP') {
			$this->config['params']['line'] = $this->config['params']['row']['detailtrno'];
		}
		$data = $this->config['docmodule']->openstockline($this->config);

		if (!empty($data)) {
			unset($data[0]->bgcolor);
			unset($data[0]->errcolor);

			switch ($this->config['params']['doc']) {
				case 'VR':
					unset($data[0]->passengername);
					unset($data[0]->itemdesc);
					break;

				case 'RR':
					unset($data[0]->clientname);
					unset($data[0]->category);
					break;
			}
			foreach ($data[0] as $field => $value) {
				$value = str_replace(["\r", "\n", "\t", "\r\n"], '', $value);
				if ($editedfield == $field) {
					if ($editedfieldval !== $value) {
						$ischange = true;
					}
				} else {
					$rowfield = str_replace(["\r", "\n", "\t", "\r\n"], '', $this->config['params']['row'][$field]);
					if ($rowfield != $value) {
						$this->coreFunctions->LogConsole('----' . $rowfield . '--' . $value);
						// if ($this->config['params']['row'][$field] != $value) {

						$ischange = true;
						break;
					}
				}
			}
			$stock = [];
			if ($ischange) {
				switch ($this->config['params']['doc']) {
					case 'DP':
					case 'TC':
					case 'CH':
						$msg = ' Data is still updated, you can continue to edit...';
						$ischange = false;
						break;
					default:
						$stock = $this->config['docmodule']->openstock($this->config['params']['trno'], $this->config);
						$msg = ' Data not updated, pls wait updating...';
						break;
				}
			} else {
				$msg = ' Data is still updated, you can continue to edit...';
			}
			$this->config['return'] = ['status' => $ischange, 'griddata' => ['inventory' => $stock], 'msg' => $msg, 'forcereload' => false];
		} else {
			if ($this->config['params']['doc'] == 'PW') {
				if ($this->config['params']['row']['subcat2'] == 0) {
					$this->config['return'] = ['status' => false, 'msg' => 'Please setup the sub-category (level 2) for sub-category (level 1) ' . $this->config['params']['row']['cat_name'], 'forcereload' => true];
				}
				return $this;
			}
			$this->config['return'] = ['status' => $ischange, 'msg' => 'This product has already been deleted. Kindly wait for an update.', 'forcereload' => true];
		}


		return $this;
	}

	public function checkperacctg()
	{
		if ($this->config['isposted']) {
			return $this;
		}
		if ($this->config['islocked']) {
			return $this;
		}
		$ischange = false;
		$msg = '';
		$editedfield = $this->config['params']['editedfield'];
		$editedfieldval = $this->config['params']['editedfieldval'];
		$this->config['params']['line'] = $this->config['params']['row']['line'];
		$data = $this->config['docmodule']->opendetailline($this->config);
		$x = '';
		$y = '';
		if (!empty($data)) {
			unset($data[0]->bgcolor);
			unset($data[0]->errcolor);
			foreach ($data[0] as $field => $value) {
				$x = $editedfield . ' - ' . $field;
				if ($editedfield == $field) {
					if ($editedfieldval != $value) {
						$ischange = true;
					}
				} else {
					if ($this->config['params']['row'][$field] != $value) {
						$y = $this->config['params']['row'][$field] . ' - ' . $value;
						$ischange = true;
						break;
					}
				}
			}
			$stock = [];
			if ($ischange) {
				$stock = $this->config['docmodule']->opendetail($this->config['params']['trno'], $this->config);
				$msg = ' Data not updated, pls wait updating...' . $x . '=' . $y;
			} else {
				$msg = ' Data is still updated, you can continue to edit...';
			}
			$this->config['return'] = ['status' => $ischange, 'griddata' => ['accounting' => $stock], 'msg' => $msg, 'forcereload' => false];
		} else {
			$this->config['return'] = ['status' => $ischange, 'msg' => 'This entry has already been deleted. Kindly wait for an update.', 'forcereload' => true];
		}

		return $this;
	}

	public function qcardstatus()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->stockstatus($this->config);
		}
		return $this;
	}


	public function stockstatus()
	{
		if ($this->config['isposted']) {
			return $this;
		}
		if ($this->config['islocked']) {
			return $this;
		}
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->stockstatus($this->config);
		}
		return $this;
	}

	public function stockstatusposted()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->stockstatusposted($this->config);
		}
		return $this;
	}

	public function generic()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->generic($this->config);
		}
		return $this;
	}

	public function deletetrans()
	{
		if ($this->config['isposted']) {
			return $this;
		}
		if ($this->config['islocked']) {
			return $this;
		}
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->deletetrans($this->config);
		}
		return $this;
	}

	public function deletestockcard()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->deletetrans($this->config);
		}
		return $this;
	}

	public function deleteclient()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->deletetrans($this->config);
		}
		return $this;
	}

	public function posttrans()
	{
		if ($this->config['verifyaccess'] == 1) {
			switch ($this->config['params']['action']) {
				case 'post':
					$this->config['return'] = $this->config['docmodule']->posttrans($this->config);
					break;
				case 'unpost':
					$this->config['return'] = $this->config['docmodule']->unposttrans($this->config);
					break;
			}
			return $this;
		}
	} // end function

	public function getlatestprice()
	{
		$this->config['return'] = $this->config['docmodule']->getlatestprice($this->config);
		return $this;
	} //end function

	public function txtlookupdirect($params)
	{
		$this->config['params'] = $params;
		$this->config['return'] = $this->lookupClass->lookupsetup($this->config);
		return $this;
	} // end function

	public function lookupcallbackdirect($params)
	{
		$this->config['params'] = $params;
		$this->config['return'] = $this->lookupClass->lookupcallback($this->config);
		return $this;
	} // end function


	public function txtlookup()
	{
		$this->config['return'] = $this->lookupClass->lookupsetup($this->config);
		return $this;
	} // end function

	public function lookupcallback()
	{
		$this->config['return'] = $this->lookupClass->lookupcallback($this->config);
		return $this;
	} // end function

	public function lookupsearch()
	{
		$this->config['return'] = $this->lookupClass->lookupsearch($this->config);
		return $this;
	} // end function

	public function inquiry()
	{
		$this->config['return'] = $this->sqlquery->inquiry($this->config);
		return $this;
	}

	public function doclistingreport()
	{
		$this->config['return'] = $this->config['docmodule']->doclistingreport($this->config);
		return $this;
	}


	public function reportsetup()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->reportsetup($this->config);
		}
		return $this;
	}

	public function sendemail()
	{
		$this->config['return'] = $this->config['docmodule']->sendemail($this->config);
		return $this;
	}

	public function reportdata()
	{

		ini_set('memory_limit', '-1');
		$method = Request::Method();
		if ($method == 'GET') {
			$params = json_decode($this->config['params']['dataparams'], true);
			$this->config['params']['dataparams'] = $params;
			$this->config['params']['companyid'] = intVal($this->config['params']['companyid']);
		}

		$this->config['return'] = $this->config['docmodule']->reportdata($this->config);
		if ($this->config['params']['dataparams']['print'] == 'default' || $this->config['params']['dataparams']['print'] == 'excel') {
		} else if ($this->config['params']['dataparams']['print'] == 'PDFM') {
			$this->config['return'] = $this->config['return']['report'];
		} else {
			if (isset(app($this->companysetup->getreportpath($this->config['params']))->reportParams)) {
				$repParams = app($this->companysetup->getreportpath($this->config['params']))->reportParams;
			} else {
				$repParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
			}

			$str = $this->config['return']['report'];
			switch (strtolower($repParams['format'])) {
				case 'legal':
					if (strtolower($repParams['orientation']) == 'p') {
						$width = 800;
						$height = 1000;
					} else {
						$width = 1000;
						$height = 800;
					}
					break;
				case 'a4':
					if (strtolower($repParams['orientation']) == 'p') {
						$width = 760;
						$height = 950;
					} else {
						$width = 950;
						$height = 760;
					}
					break;
				default: // letter
					if (strtolower($repParams['orientation']) == 'p') {
						$width = 800;
						$height = 1000;
					} else {
						$width = 1000;
						$height = 800;
					}
					break;
			}
			$width = PDF::pixelsToUnits($width);
			$height = PDF::pixelsToUnits($height);
			PDF::SetTitle($this->config['params']['name']);
			PDF::AddPage($repParams['orientation'], [$width, $height]);
			//PDF::setPageUnit('px');
			PDF::SetMargins(0, 0);
			PDF::writeHtml($str, true, 0, true, 0);

			$pdf = PDF::Output($this->config['params']['name'] . '.pdf', 'S');
			if ($method == 'GET') {
				$this->config['return'] = $pdf;
			} else {
				$this->config['params']['pdf'] = $pdf;
				if (isset($this->config['params']['email'])) {
					Mail::to($this->config['params']['email'])->send(new SendMail($this->config['params']));
				}
				$this->config['return'] = $str;
			}
		}
		return $this;
	}

	public function execute()
	{
		if (isset($this->config['allowlogin'])) {
			if (!$this->config['allowlogin']) {

				return response()->json(['status' => 'ipdenied', 'msg' => 'Sorry, Please contact your Network Administrator.'], 200);
			}
		}

		if (isset($this->config['loginexpired'])) {
			if (!$this->config['loginexpired']) {

				return response()->json(['status' => 'invalid', 'msg' => 'Sorry, you are not allowed to access at this moment.'], 200);
			}
		}

		if (isset($this->config['isposted'])) {
			if ($this->config['isposted']) {
				return response()->json(['status' => false, 'msg' => 'Already posted. Please wait while connecting to the server.', 'checkposted' => true], 200);
			} else if ($this->config['islocked']) {
				if (isset($this->config['return']['islocked'])) {
					if (!$this->config['return']['islocked']) {
						return response()->json(['status' => false, 'msg' => 'Document unlocked.', 'checkposted' => true], 200);
					} else {
						return response()->json(['status' => false, 'msg' => $this->config['return']['msg'], 'checkposted' => true], 200);
					}
				}
				return response()->json(['status' => false, 'msg' => 'Already locked. Please wait while connecting to the server.', 'checkposted' => true], 200);
			} else {
				$request = Request();
				if ($request->isMethod('get')) {
					return $this->config['return'];
				} else {
					return response()->json($this->config['return'], 200);
				}
			}
		} else {
			if (Request::isMethod('get')) {
				return $this->config['return'];
			} else {
				return response()->json($this->config['return'], 200);
			}
		}
	} // end function


	public function loaddoclisting()
	{
		if ($this->config['verifyaccess'] == 1) {

			if ($this->config['params']['companyid'] == 16) { //ati
				$this->coreFunctions->sbcinsert(
					$this->config['docmodule']->tablelogs,
					['field' => 'LISTING', 'oldversion' => $this->config['params']['doc'], 'userid' => $this->config['params']['user'], 'dateid' => $this->othersClass->getCurrentTimeStamp()]
				);
			}

			$this->config['return'] = $this->config['docmodule']->loaddoclisting($this->config);
		}
		return $this;
	}

	public function selectFilter()
	{
		$this->config['return'] = $this->selectClass->searchselect($this->config);
		return $this;
	}

	public function loadDefaultAccounts()
	{
		$qry = "select acnoid, acno as description, acnoname as label, left(alias,2) as description2, acno, acnoname, left(alias,2) as alias from coa where detail=1 order by acnoname";
		$acc = $this->coreFunctions->opentable($qry);
		return json_decode(json_encode($acc), true);
	}

	public function getcdoTransactionSummary()
	{
		try {
			$current_timestamp = date('Y-m-d H:i:s');
			$classname = __NAMESPACE__ . '\\modules\\reportlist\\other_reports\\summary_of_transaction_report';
			$this->config['classname'] = $classname;
			$this->config['docmodule'] = new $classname();
			$repdata = $this->config['docmodule']->reportdata();

			//$info['from']='jad@sbc.ph';
			$info['subject'] = '2Cycles-AIMS Daily Updates' . date('m/d/Y', strtotime($current_timestamp));
			$info['filename'] = '2Cycles_AIMS_Daily_Updates' . date('mdY', strtotime($current_timestamp));
			$info['view'] = 'emails.firstnotice';
			$info['msg'] = '<div>Good Day!</div><br><div>Please see attached file for AIMS Daily Update.</div><br><br></div>Thank You,</div><Br><div>SBC</div>';
			$info['email'] = ['cdo2cycles.agm39@gmail.com', 'acctgruel29@gmail.com', 'cdo2cycles.imd@gmail.com', 'cdo2cycles.shok.credit@gmail.com', 'cdo2cycles.controller@gmail.com']; //['jacalawod@gmail.com'];//
			$info['cc'] = ['erick0601@yahoo.com', 'jacalawod@gmail.com']; //['solutionbasecorp@yahoo.com'];//
			//Mail::to(['jacalawod@gmail.com','aldrenala@gmail.com'])->cc(['solutionbasecorp@yahoo.com'])->send(new SendMail($info));

			return json_encode(['result' => $repdata['report'], 'status' => true, 'msg' => '', 'emailinfo' => $info]);
		} catch (Exception $e) {
			return json_encode(['result' => '', 'status' => false, 'msg' => $e]);
		}
	}


	public function getSampleExcel()
	{
		try {
			$config['params'] = [
				'resellerid' => 0,
				'center' => '001',
				'user' => 'sbc',
				'companyid' => 0,
				'dataparams' => [
					'contra' => '',
					'acnoname' => ''
				]
			];
			$classname = __NAMESPACE__ . '\\modules\\reportlist\\masterfile_report\\chart_of_accounts';
			$this->config['classname'] = $classname;
			$this->config['docmodule'] = new $classname();
			$repdata = $this->config['docmodule']->reportdata($config);

			$current_timestamp = date('Y-m-d H:i:s');
			$info['subject'] = '2Cycles-AIMS Daily Updates' . date('m/d/Y', strtotime($current_timestamp));
			$info['filename'] = '2Cycles_AIMS_Daily_Updates' . date('mdY', strtotime($current_timestamp));
			$info['view'] = 'emails.firstnotice';
			$info['msg'] = '<div>Good Day!</div><br><div>Please see attached file for AIMS Daily Update.</div><br><br></div>Thank You,</div><Br><div>SBC</div>';
			$info['email'] = 'zerojad08@gmail.com';
			return json_encode(['result' => $repdata['report'], 'status' => true, 'msg' => '', 'emailinfo' => $info]);
		} catch (Exception $e) {
			return json_encode(['result' => '', 'status' => false, 'msg' => $e]);
		}
	}

	public function getKinggeorgeInventoryBalance($wh)
	{
		try {
			$current_timestamp = date('Y-m-d H:i:s');
			$classname = __NAMESPACE__ . '\\modules\\reportlist\\items\\inventory_balance';
			$this->config['classname'] = $classname;
			$this->config['docmodule'] = new $classname();

			$whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);

			$rrstatus = $this->coreFunctions->datareader("select ifnull(sum(bal),0) as value from rrstatus where whid=? and bal<>0", [$whid], '', true);
			if ($rrstatus == 0) {
				return json_encode(['result' => [], 'status' => false, 'msg' => '', 'emailinfo' => []]);
			}

			$config['params'] = [
				'resellerid' => 0,
				'center' => '001',
				'user' => 'sbc',
				'companyid' => 21,
				'multiheader' => false,
				'dataparams' => [
					'start' => date('Y-m-d', strtotime($current_timestamp)),
					'print' => 'excel',
					'wh' => $wh,
					'whid' => $whid,
					'whname' => $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$whid]),
					'amountformat' => 'isamt',
					'dtagathering' => 'dcurrent',
					'client' => '',
					'clientname' => '',
					'barcode' => '',
					'itemname' => '',
					'classid' => '',
					'classic' => '',
					'categoryid' => '',
					'categoryname' => '',
					'subcat' => '',
					'groupid' => '',
					'stockgrp' => '',
					'brandid' => '',
					'brandname' => '',
					'modelid' => '',
					'modelname' => '',
					'itemstock' => '(0,1)',
					'itemtype' => '(0,1)',
					'project' => '',
					'projectname' => '',
					'partid' => '',
					'partname' => ''
				]
			];

			$repdata = $this->config['docmodule']->reportdata($config);

			$info['subject'] = 'Inventory Balance As Of ' . date('m/d/Y', strtotime($current_timestamp)) . ' - ' . $wh;
			$info['filename'] = $wh . '_Inventory_Balance_' . date('mdY', strtotime($current_timestamp));
			$info['msg'] = '<div>Good Day!</div><br><div>Please see attached file for Inventory Balance.</div><br><br></div>Thank You,</div><Br><div>SBC</div>';
			$info['email'] = ['noimiedelrosario04@gmail.com'];
			$info['cc'] = [];
			$info['view'] = 'emails.firstnotice';

			return json_encode(['result' => $repdata['report'], 'status' => true, 'msg' => '', 'emailinfo' => $info]);
		} catch (Exception $e) {
			return json_encode(['result' => '', 'status' => false, 'msg' => $e]);
		}
	}


	public function getEmailExcel($param)
	{
		switch ($param['func']) {
			case 'getSampleExcel':
				return $this->getSampleExcel();
				break;
			case 'getcdoTransactionSummary':
				return $this->getcdoTransactionSummary();
				break;
			case 'getKinggeorgeInventoryBalance':
				return $this->getKinggeorgeInventoryBalance($param['wh']);
				break;
		}
	}

	public function submitquestionnaire()
	{
		$this->config['return'] = $this->config['docmodule']->submitquestionnaire($this->config);
		return $this;
	}

	public function loadnmodule()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->loadnmoduledata($this->config);
		} else {
			$this->logger->sbciplog($this->config['params']['doc'] . '-FAIL', $this->config['params']['ip'], $this->config['params']['user']);
		}
		return $this;
	}


    public function queuing($params)
	{
      $this->config['params'] = $params;
	  
	}



} // end class
