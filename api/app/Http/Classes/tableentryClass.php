<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\stockClass;
use App\Http\Classes\othersClass;
use App\Http\Classes\clientClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\lookup\enrollmentlookup;
use Exception;
use Throwable;
use Session;



class tableentryClass
{
	private $othersClass;
	private $coreFunctions;
	private $headClass;
	private $logger;
	private $lookupClass;
	private $companysetup;
	private $config = [];
	private $sqlquery;
	private $enrollmentlookup;

	public function __construct()
	{
		$this->othersClass = new othersClass;
		$this->coreFunctions = new coreFunctions;
		$this->headClass = new headClass;
		$this->logger = new Logger;
		$this->lookupClass = new lookupClass;
		$this->companysetup = new companysetup;
		$this->sqlquery = new sqlquery;
		$this->enrollmentlookup = new enrollmentlookup;
	}

	public function sbc($params)
	{
		if (isset($params['row']['sbcpendingapp']) && $params['doc'] != 'MODULEAPPROVAL') {
			$doc = strtolower($params['row']['sbcpendingapp']);
		} else {
			$doc = strtolower($params['lookupclass']);
		}
		$type = strtolower($params['action']);
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
		if ($doc == 'entryjumpmodule') {
			$this->config['mattrib'] = $this->config['docmodule']->getAttrib($this->config);
		} else {
			switch ($this->config['params']['doc']) {
				case 'PD':
				case 'PI':
					$this->config['mattrib'] = $this->config['docmodule']->getAttrib($this->config);
					break;
				default:
					$this->config['mattrib'] = $this->config['docmodule']->getAttrib();
					break;
			}
		}

		return $this;
	}

	public function checksecurity($accessid)
	{
		$id = $this->config['mattrib'][$accessid];
		$this->config['verifyaccess'] = 1;
		if ($id !== 0) {
			$this->config['verifyaccess'] = $this->config['access'][0]['attributes'][$id - 1];
			if ($this->config['verifyaccess'] == 0) {
				$this->config['return'] = ['status' => 'denied', 'msg' => 'Invalid Access'];
			}
		}
		return $this;
	}


	public function loadform()
	{
		if ($this->config['verifyaccess'] == 1) {
			$tab = $this->config['docmodule']->createTab($this->config);
			$tabbtn = $this->config['docmodule']->createtabbutton($this->config);
			$modulename = $this->config['docmodule']->modulename;
			$gridname =  $this->config['docmodule']->gridname;
			$style = $this->config['docmodule']->style;
			$data =  $this->config['docmodule']->loaddata($this->config);
			if (isset($this->config['docmodule']->rowperpage)) {
				$rowperpage = $this->config['docmodule']->rowperpage;
			} else {
				$rowperpage = 30;
			}
			$gridheaddata = [];
			if (method_exists($this->config['docmodule'], 'gridheaddata')) {
				$gridheaddata =  $this->config['docmodule']->gridheaddata($this->config);
			}
			$sbcscript = '';
			if (method_exists($this->config['docmodule'], 'sbcscript')) {
				$sbcscript = $this->config['docmodule']->sbcscript($this->config);
			}

			$showclosebtn = $this->config['docmodule']->showclosebtn;
			$showsearch = isset($this->config['docmodule']->showsearch) ? $this->config['docmodule']->showsearch : true;
			$this->config['return'] = ['tab' => $tab, 'tabbuttons' => $tabbtn, 'status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'doc' => $this->config['params']['lookupclass'], 'action' => $this->config['params']['action'], 'style' => $style, 'gridname' => $gridname, 'data' => $data, 'showclosebtn' => $showclosebtn, 'gridheaddata' => $gridheaddata, 'rowperpage' => $rowperpage, 'showsearch' => $showsearch, 'sbcscript' => $sbcscript];
		}
		return $this;
	}


	public function getdata()
	{
		$data =  $this->config['docmodule']->loaddata($this->config);
		$gridheaddata = [];
		if (method_exists($this->config['docmodule'], 'gridheaddata')) {
			$gridheaddata =  $this->config['docmodule']->gridheaddata($this->config);
		}
		$isreloadhead = false;
		if (method_exists($this->config['docmodule'], 'isreloadhead')) {
			$isreloadhead =  $this->config['docmodule']->isreloadhead($this->config);
		}

		if (isset($data['status'])) {
			if (!$data['status']) {
				$this->config['return'] = ['status' => $data['status'], 'msg' => $data['msg']];
				return $this;
			}
		}

		$this->config['return'] = ['status' => true, 'msg' => 'Refresh Data',  'data' => $data, 'gridheaddata' => $gridheaddata, 'isreloadhead' => $isreloadhead];
		return $this;
	}

	public function tableentrystatus()
	{
		$action = $this->config['params']['action2'];
		switch ($action) {
			case 'add':
				$data = $this->config['docmodule']->add($this->config);
				if (isset($data['status']) && isset($data['msg'])) {
					$this->config['return'] = ['status' => $data['status'], 'msg' => $data['msg'], 'data' => $data];
				} else {
					$this->config['return'] = ['status' => true, 'msg' => 'Ready For New Record', 'data' => $data];
				}

				break;
			case 'saveallentry':
				$this->config['return'] = $this->config['docmodule']->saveallentry($this->config);
				break;
			case 'saveperitem':
				$this->config['return'] = $this->config['docmodule']->save($this->config);
				break;
			case 'deleteitem':
			case 'delete':
				$this->config['return'] = $this->config['docmodule']->delete($this->config);
				break;
			case 'lookupsetup':
				$this->config['return'] = $this->config['docmodule']->lookupsetup($this->config);
				break;
			case 'lookupcallback':
				$this->config['return'] = $this->config['docmodule']->lookupcallback($this->config);
				break;
			case 'deleteallitem':
				$this->config['return'] = $this->config['docmodule']->deleteallitem($this->config);
				break;
			case 'getdata':
				$this->config['return'] = $this->config['docmodule']->getdata($this->config);
				break;
			case 'approveall':
			case 'approveallreq':
				$this->config['return'] = $this->config['docmodule']->approveall($this->config);
				break;
			case 'generatecurriculum':
				$this->config['return'] = $this->config['docmodule']->generatecurriculum($this->config);
				break;
			case 'archivecurriculum':
				$this->config['return'] = $this->config['docmodule']->archivecurriculum($this->config);
				break;
			case 'adddefaults':
				$this->config['return'] = $this->config['docmodule']->adddefaults($this->config);
				break;
			case 'addmultipleserial':
				$this->config['return'] = $this->config['docmodule']->addmultipleserial($this->config);
				break;
			case 'addserial':
				$this->config['return'] = $this->config['docmodule']->addserial($this->config);
				break;
			case 'generateestud':
				$this->config['return'] = $this->config['docmodule']->generateEStud($this->config);
				break;
			case 'addprocess':
				$this->config['return'] = $this->config['docmodule']->addProcess($this->config);
				break;
			case 'newitem':
				$this->config['return'] = $this->config['docmodule']->newitem($this->config);
				break;
			case 'saveandclose':
				$this->config['return'] = $this->config['docmodule']->saveandclose($this->config);
				break;
			case 'applybarcode':
				$this->config['return'] = $this->config['docmodule']->applybarcode($this->config);
				break;
			case 'markall':
				$this->config['return'] = $this->config['docmodule']->markall($this->config);
				break;
			case 'approvedsummary':
				$this->config['return'] = $this->config['docmodule']->approvedreq($this->config);
				break;
			case 'disapprovedsummary':
				$this->config['return'] = $this->config['docmodule']->disapprovedreq($this->config);
				break;
			case 'approveapp':
				$this->config['return'] = $this->config['docmodule']->updateapp($this->config, 'A');
				break;
			case 'disapproveapp':
				$this->config['return'] = $this->config['docmodule']->updateapp($this->config, 'D');
				break;
			case 'processapp':
				$this->config['return'] = $this->config['docmodule']->updateapp($this->config, 'P');
				break;
			case 'undone':
				$this->config['return'] = $this->config['docmodule']->updateapp($this->config, 'U');
				break;
			case 'accept':
				$this->config['return'] = $this->config['docmodule']->accept($this->config);
				break;
			case 'lookupsetup':
				$this->config['return'] = $this->config['docmodule']->lookupsetup($this->config);
				break;
			case 'cancel':
				$this->config['return'] = $this->config['docmodule']->cancel($this->config);
				break;
			default:
				if (method_exists($this->config['docmodule'], 'tableentrystatus')) {
					$this->config['return'] = $this->config['docmodule']->tableentrystatus($this->config);
				} else {
					$this->config['return'] = ['status' => false, 'msg' => ' Invalid action ' . $action . ', pls check tableentrystatus function in tableentryClass'];
				}
				break;
		}
		return $this;
	}


	public function loaddata()
	{
		$this->config['return'] = $this->config['docmodule']->loaddata($this->config);
		return $this;
	}


	public function reportsetup()
	{
		$this->config['return'] = $this->config['docmodule']->reportsetup($this->config);
		return $this;
	}

	public function reportdata()
	{
		$this->config['return'] = $this->config['docmodule']->reportdata($this->config);
		return $this;
	}


	public function execute()
	{
		return response()->json($this->config['return'], 200);
	} // end function






































} // end class
