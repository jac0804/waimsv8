<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use Exception;
use Throwable;
use Session;



class headtableClass
{
	private $othersClass;
	private $coreFunctions;
	private $headClass;
	private $logger;
	private $lookupClass;
	private $companysetup;
	private $config = [];
	private $sqlquery;

	public function __construct()
	{
		$this->othersClass = new othersClass;
		$this->coreFunctions = new coreFunctions;
		$this->headClass = new headClass;
		$this->logger = new Logger;
		$this->lookupClass = new lookupClass;
		$this->companysetup = new companysetup;
		$this->sqlquery = new sqlquery;
	}

	public function sbc($params)
	{
		$doc = strtolower($params['lookupclass']);
		$type = strtolower($params['action']);
		$classname = __NAMESPACE__ . '\\modules\\' . $type . '\\' . $doc;
		try {
			$this->config['classname'] = $classname;
			$this->config['docmodule'] = new $classname;
		} catch (Exception $e) {
			echo $e;
			return $this;
		}
		$this->config['params'] = $params;
		$access = $this->othersClass->getAccess($params['user']);
		$this->config['access'] = json_decode(json_encode($access), true);
		$this->config['mattrib'] = $this->config['docmodule']->getAttrib();
		return $this;
	}

	public function checksecurity($accessid)
	{
		$id = $this->config['mattrib'][$accessid];
		$this->config['verifyaccess'] = $this->config['access'][0]['attributes'][$id - 1];
		if ($this->config['verifyaccess'] == 0) {
			$this->config['return'] = ['status' => 'denied', 'msg' => 'Invalid Access'];
		}
		return $this;
	}


	public function loadform()
	{
		if ($this->config['verifyaccess'] == 1) {
			$access = $this->getmoduleaccess($this->config['mattrib']);
			$buttons = $this->config['docmodule']->createHeadbutton($this->config);
			$txtfield = $this->config['docmodule']->createHeadField($this->config);
			$data = $this->config['docmodule']->data($this->config);
			$tab = $this->config['docmodule']->createTab($this->config);
			$tabbtn = $this->config['docmodule']->createtabbutton($this->config);
			$modulename = $this->config['docmodule']->modulename;
			$gridname =  $this->config['docmodule']->gridname;
			$issearchshow =  $this->config['docmodule']->issearchshow;
			$style = $this->config['docmodule']->style;
			$tab2 = [];
			$rowperpage = 25;
			$hideobj = [];
			$loadtable = false;
			$griddata = [];
			if (method_exists($this->config['classname'], 'hideobj')) {
				$hideobj = $this->config['docmodule']->hideobj($this->config);
			}
			if(isset($this->config['docmodule']->rowperpage)) $rowperpage = $this->config['docmodule']->rowperpage;
			if (method_exists($this->config['classname'], 'createtab2')) {
				$tab2 = $this->config['docmodule']->createtab2($access, $this->config);
			}
			if (isset($this->config['docmodule']->loadtable)) $loadtable = $this->config['docmodule']->loadtable;
			if (method_exists($this->config['docmodule'], 'griddata')) {
				$griddata = $this->config['docmodule']->griddata($this->config);
			}
			$this->config['return'] = [
				'txtfield' => $txtfield, 'tab' => $tab, 'tabbuttons' => $tabbtn,
				'toolbar' => $buttons, 'status' => true, 'msg' => 'Loaded Success',
				'modulename' => $modulename, 'doc' => $this->config['params']['lookupclass'],
				'action' => $this->config['params']['action'], 'style' => $style, 'gridname' => $gridname, 'data' => $data, 'issearchshow' => $issearchshow,
				'maccess' => $access, 'tab2' => $tab2, 'rowperpage'=>$rowperpage, 'hideobj'=>$hideobj, 'loadtable' => $loadtable, 'griddata'=>$griddata
			];
		}
		return $this;
	}


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

	public function loaddata()
	{
		$this->config['return'] = $this->config['docmodule']->loaddata($this->config);
		return $this;
	}


	public function headtablestatus()
	{
		$this->config['return'] = $this->config['docmodule']->headtablestatus($this->config);
		return $this;
	}


	public function execute()
	{
		return response()->json($this->config['return'], 200);
	} // end function






































} // end class
