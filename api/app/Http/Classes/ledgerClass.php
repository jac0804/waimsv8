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



class ledgerClass
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
		if (strtolower($params['action']) == 'optioncustomform') {
			$type = 'customform';
		} else {
			$type = strtolower($params['action']);
		}
		$classname = __NAMESPACE__ . '\\modules\\' . $type . '\\' . $doc;
		try {
			$this->config['classname'] = $classname;
			$this->config['docmodule'] = new $classname;
		} catch (Exception $e) {
			echo $e;
			return $this;
		}
		$this->config['params'] = $params;
		return $this;
	}

	public function loadaform()
	{
		$txtfield = $txtdata = $data = $tab = $tabbtn = $sbcscript = [];
		$modulename = $gridname = $style = '';
		$showclosebtn = $issearchshow = true;
		if (method_exists($this->config['docmodule'], 'createHeadField')) $txtfield = $this->config['docmodule']->createHeadField($this->config);
		if (method_exists($this->config['docmodule'], 'paramsdata')) $txtdata = $this->config['docmodule']->paramsdata($this->config);
		if (method_exists($this->config['docmodule'], 'data')) $data = $this->config['docmodule']->data($this->config);
		if (method_exists($this->config['docmodule'], 'createTab')) $tab = $this->config['docmodule']->createTab($this->config);
		if (method_exists($this->config['docmodule'], 'createtabbutton')) $tabbtn = $this->config['docmodule']->createtabbutton($this->config);
		if (method_exists($this->config['docmodule'], 'getmodulename')) $modulename = $this->config['docmodule']->getmodulename($this->config);
		if (method_exists($this->config['docmodule'], 'getgridname')) $gridname = $this->config['docmodule']->getgridname($this->config);
		if (method_exists($this->config['docmodule'], 'getisshowsearch')) $issearchshow = $this->config['docmodule']->getisshowsearch($this->config);
		if (method_exists($this->config['docmodule'], 'getshowclosebtn')) $showclosebtn = $this->config['docmodule']->getshowclosebtn($this->config);
		if (method_exists($this->config['docmodule'], 'getstyle')) $style = $this->config['docmodule']->getstyle($this->config);
		if (method_exists($this->config['docmodule'], 'sbcscript')) $sbcscript = $this->config['docmodule']->sbcscript($this->config);
		$this->config['return'] = ['txtfield' => $txtfield, 'txtdata' => $txtdata, 'tab' => $tab, 'tabbuttons' => $tabbtn, 'status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'doc' => $this->config['params']['lookupclass'], 'action' => $this->config['params']['action'], 'style' => $style, 'gridname' => $gridname, 'data' => $data, 'issearchshow' => $issearchshow, 'showclosebtn' => $showclosebtn, 'sbcscript' => $sbcscript];
		return $this;
	}


	public function loadform()
	{
		$txtfield = $this->config['docmodule']->createHeadField($this->config);
		$txtdata = $this->config['docmodule']->paramsdata($this->config);
		$data = $this->config['docmodule']->data($this->config);
		$tab = $this->config['docmodule']->createTab($this->config);
		$tabbtn = $this->config['docmodule']->createtabbutton($this->config);
		$modulename = $this->config['docmodule']->modulename;
		$gridname =  $this->config['docmodule']->gridname;
		$issearchshow =  $this->config['docmodule']->issearchshow;
		$showclosebtn = $this->config['docmodule']->showclosebtn;
		$style = $this->config['docmodule']->style;
		$sbcscript = [];
		if (method_exists($this->config['docmodule'], 'sbcscript')) $sbcscript = $this->config['docmodule']->sbcscript($this->config);
		$this->config['return'] = ['txtfield' => $txtfield, 'txtdata' => $txtdata, 'tab' => $tab, 'tabbuttons' => $tabbtn, 'status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'doc' => $this->config['params']['lookupclass'], 'action' => $this->config['params']['action'], 'style' => $style, 'gridname' => $gridname, 'data' => $data, 'issearchshow' => $issearchshow, 'showclosebtn' => $showclosebtn, 'sbcscript' => $sbcscript];
		return $this;
	}

	public function optionloadform($params)
	{
		$doc = strtolower($params['row']['lookupclass']);
		$type = strtolower($params['row']['action']);
		$classname = __NAMESPACE__ . '\\modules\\' . $type . '\\' . $doc;
		try {
			$this->config['docmodule'] = new $classname;
		} catch (Exception $e) {
			echo $e;
			return $this;
		}
		$this->config['params'] = $params;
		return $this->loadform();
	}

	public function printcustomform($params)
	{
		$doc = strtolower($params['lookupclass']);
		$type = strtolower($params['action']);
		$classname = __NAMESPACE__ . '\\modules\\' . $type . '\\' . $doc;
		try {
			$this->config['classname'] = new $classname;
			$this->config['docmodule'] = new $classname;
		} catch (Exception $e) {
			echo $e;
			return $this;
		}
		$this->config['params'] = $params;
		$this->config['return'] = $this->config['docmodule']->reportsetup($this->config);
		return $this;
	}


	public function loaddata()
	{
		$this->config['return'] = $this->config['docmodule']->loaddata($this->config);
		return $this;
	}

	public function execute()
	{
		return response()->json($this->config['return'], 200);
	} // end function






































} // end class
