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
use Exception;
use Throwable;
use Session;



class uniqueClass
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
		$doc = strtolower($params['doc']);
		$classname = __NAMESPACE__ . '\\modules\\masterfile\\' . $doc;
		try {
			$this->config['docmodule'] = new $classname;
		} catch (Exception $e) {
			echo $e;
			return $this;
		}
		$this->config['params'] = $params;
		$access = $this->othersClass->getAccess($params['user']);
		$this->config['access'] = json_decode(json_encode($access), true);
		$this->config['mattrib'] = $this->config['docmodule']->getAttrib();

		$istimechecking = $this->othersClass->istimechecking($params);

		if ($istimechecking['status']) {
			// $this->othersClass->logConsole(json_encode($istimechecking));
			$this->config['loginexpired'] = $istimechecking['loginexpired'];
		}

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
			$modulename = $this->config['docmodule']->modulename;
			$gridname =  $this->config['docmodule']->gridname;
			$data =  $this->config['docmodule']->loaddata($this->config);
			$this->config['return'] = ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'doc' => $this->config['params']['action'], 'data' => $data];
		} else {
			$this->logger->sbciplog($this->config['params']['doc'] . '-FAIL', $this->config['params']['ip'], $this->config['params']['user']);
		}
		return $this;
	}


	public function status()
	{
		if ($this->config['verifyaccess'] == 1) {
			$this->config['return'] = $this->config['docmodule']->status($this->config);
		}
		return $this;
	}


	public function loaddata()
	{
		$this->config['return'] = $this->config['docmodule']->loaddata($this->config);
		return $this;
	}

	public function execute()
	{
		if (isset($this->config['loginexpired'])) {
			if (!$this->config['loginexpired']) {

				return response()->json(['status' => 'invalid', 'msg' => 'Sorry, you are not allowed to access at this moment'], 200);
			}
		}

		return response()->json($this->config['return'], 200);
	} // end function






































} // end class
