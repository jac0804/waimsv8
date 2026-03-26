<?php

namespace App\Http\Classes\modules\masterfile;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class locationledger
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LOCATION LEDGER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'loc';
  public $detail = '';
  public $prefix = 'LL';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = '';
  private $stockselect;
  private $tablenum;

  private $fields = [
    'line', 'code', 'name', 'emeter', 'wmeter', 'semeter', 'area', 'phase', 'section'
  ];
  private $except = ['clientid', 'client'];
  private $blnfields = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 375,
      'view' => 121,
      'edit' => 2065,
      'new' => 2080,
      'save' => 244,
      'delete' => 228,
      'print' => 213,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'code', 'name'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';
    $cols[1]['label'] = 'Code';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['line', 'code', 'name', 'emeter', 'wmeter', 'semeter', 'area', 'phase', 'section'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select line as clientid, code, name, emeter, wmeter, semeter, area, phase, section
    from loc 
    where 1=1 " . $filtersearch . "
    order by line";
    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $client = 0;
    $clientname = 1;
    $start = 2;
    $end = 3;

    $tab = [$this->gridname => ['gridcolumns' => ['client', 'clientname', 'start', 'enddate']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['label'] = 'TENANTS';
    $obj[0][$this->gridname]['columns'][$client]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$client]['label'] = "Tenant Code";
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Tenant Name";

    $obj[0][$this->gridname]['columns'][$client]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$start]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$end]['type'] = "label";

    return $obj;
  }

  public function createtab2($access, $config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [
      'client', 'name',
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.required', true);
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookuplocationledger_mms');

    $fields = [
      'emeter', 'wmeter',
    ];
    $col2 = $this->fieldClass->create($fields);

    $fields = [
      'semeter', 'area',
    ];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'area.type', 'input');
    data_set($col3, 'area.class', 'csarea');
    data_set($col3, 'area.label', 'SQM');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['code'] = '';
    $data[0]['name'] = '';
    $data[0]['emeter'] = '';
    $data[0]['wmeter'] = '';
    $data[0]['semeter'] = '';
    $data[0]['area'] = '0';

    return $data;
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $this->othersClass->val($config['params']['clientid']);
    $center = $config['params']['center'];

    if ($clientid == 0) $clientid = $this->getlastclient();

    $qry = "select line as clientid, code as client, 
      name, emeter, wmeter, semeter, area, phase, section
      from loc
      where line = ? ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      $stock = $this->openstock($clientid, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'griddata' => ['inventory' => $stock]];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...', 'griddata' => ['inventory' => []]];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['code']);
    } else {
      $data['code'] = $head['client'];
      $head['code'] = $head['client'];
    }
    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
      $clientid = $head['clientid'];
    } else {
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);
      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'CREATE' . ' - ' . $head['code'] . ' - ' . $head['name'] . ' - ' . $head['emeter'] . ' - ' . $head['wmeter'] . ' - ' . $head['semeter'] . ' - ' . $head['area']
      );
    }

    $stock = $this->openstock($clientid, $config);
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid, 'griddata' => ['inventory' => $stock]];
  } // end function

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select line as value 
        from " . $this->head . " 
        order by line DESC LIMIT 1");

    return $last_id;
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];

    $qry = "select locid as value from client where locid = ? LIMIT 1";
    $count = $this->coreFunctions->datareader($qry, [$clientid]);
    if ($count != '') {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where line=?', 'delete', [$clientid]);
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function openstock($trno, $config)
  {
    $qry = "select clientid, client, clientname,date_format(start,'%m/%d/%Y') as start,date_format(enddate,'%m/%d/%Y') as enddate
    from client
    where locid = ? and istenant = 1 union all
    select distinct a.clientid ,c.client ,c.clientname,date_format(c.start,'%m/%d/%Y') as start,date_format(c.enddate,'%m/%d/%Y') as enddate from arledger as a 
    left join client as c on c.clientid = a.clientid  where a.locid = ? and c.isinactive = 1";

    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  } //end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
