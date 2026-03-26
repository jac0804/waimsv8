<?php

namespace App\Http\Classes\modules\vehiclescheduling;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class driver
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DRIVER LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'DL'; // driver ledger
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;

  private $fields = [
    'client', 'clientname', 'addr', 'start', 'tel2',
    'contact', 'rem', 'type',
    'iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isinactive', 'isemployee', 'isdepartment', 'picture', 'isdriver',
    'isadmin', 'uv_ischecker', 'uv_ispicker', 'deptid', 'position', 'empid', 'salesgroupid', 'branchid'
  ];
  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isinactive', 'isdriver', 'isemployee', 'isdepartment', 'isadmin', 'uv_ischecker', 'uv_ispicker'];
  private $acctg = [];
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
      'view' => 2878,
      'edit' => 2879,
      'new' => 2880,
      'save' => 2881,
      'change' => 2882,
      'delete' => 2883,
      'print' => 2884,
      'load' => 2877
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = $config['params']['date1'];
    $date2 = $config['params']['date2'];
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientname', 'client.addr'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select client.clientid,client.client,client.clientname,client.addr from client where isdriver =1 $filtersearch
     order by client";

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
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createTab2($access, $config)
  {
    $ar = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewar']];
    $ap = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewap']];
    $inv = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewcustomerinv']];
    $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryitemgroup', 'label' => 'itemgroup']];
    $itemgroup = $this->tabClass->createtab($tab, []);
    $return = [];

    $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];

    return $return;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['client', 'clientname', 'addr', 'start'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Employee Code');
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'wh');
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'addr.type', 'cinput');
    $fields = ['contact', 'tel2', 'rem', 'type'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.label', 'Contact Person');
    data_set($col2, 'rem.required', false);
    data_set($col2, 'type.required', false);
    data_set($col2, 'type.type', 'input');
    data_set($col2, 'type.class', 'cstype');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'addr.type', 'cinput');
    data_set($col2, 'tel2.type', 'cinput');

    $fields = ['picture', 'isdriver', 'iswarehouse', 'issupplier', 'iscustomer', 'isagent', 'isinactive', 'isdepartment', 'isadmin'];

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'isdriver.class', 'csissupplier sbccsreadonly');
    data_set($col3, 'picture.lookupclass', 'client');
    data_set($col3, 'picture.folder', 'employee');
    data_set($col3, 'picture.table', 'client');
    data_set($col3, 'picture.fieldid', 'clientid');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }


  public function newclient($config)
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['contact'] = '';
    $data[0]['tel2'] = '';
    $data[0]['type'] = '';
    $data[0]['rem'] = '';
    $data[0]['position'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
    $data[0]['empid'] = 0;
    $data[0]['empname'] = '';
    $data[0]['empcode'] = '';
    $data[0]['start'] = null;

    $data[0]['isdriver'] = '1';
    $data[0]['isemployee'] = '1';
    $data[0]['iscustomer'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isdepartment'] = '0';
    $data[0]['isadmin'] = '0';
    $data[0]['uv_ischecker'] = '0';
    $data[0]['uv_ispicker'] = '0';
    $data[0]['picture'] = '';
    $data[0]['salesgroupid'] = '0';
    $data[0]['branchid'] = '0';
    $data[0]['branchname'] = '';
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where isdriver=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = "client.clientid, sgroup.groupname as salesgroup, ifnull(branch.clientname, '') as branchname";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    $qryselect = "select " . $fields;

    $qry = $qryselect . ", dept.client as dept, dept.clientname as deptname,
      emp.client as empcode, emp.clientname as empname
      from client
      left join client as dept on client.deptid = dept.clientid
      left join client as emp on client.empid = emp.clientid
      left join salesgroup as sgroup on sgroup.line = client.salesgroupid
      left join client as branch on branch.clientid = client.branchid
      where client.clientid = ? and client.isdriver = 1";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['clientname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }




  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    $companyid = $config['params']['companyid'];
    if ($isupdate) {
      unset($this->fields['client']);
    }
    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['isdriver'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    // return $clientid;
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  isdriver=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isdriver=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }





  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select vrhead.trno as value from vrhead where driverid=?
            union all
            select hvrhead.trno as value from hvrhead where driverid=? limit 1";
    $count = $this->coreFunctions->datareader($qry, [$clientid, $clientid]);

    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and isdriver=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $client);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function createreportfilter()
  {
    $fields = [
      'prepared',
      'approved',
      'received',
      'print'
    ];

    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
  ");
  }


  public function generateResult($config)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = md5($config['params']['dataid']);

    $query = "select client.client,client.clientname,client.addr,client.tel,
    client.tel2,client.tin,client.mobile,client.rem,
    client.email,client.contact,client.fax,client.start,client.status,client.quota,
    client.area,client.province,client.region,client.groupid,client.issupplier,client.iscustomer,
    client.isagent,client.isemployee,client.isdriver
    from client where md5(client.clientid)='$clientid'";

    return $this->coreFunctions->opentable($query);
  }


  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $data = $this->generateResult($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_employee_layout($config, $data);
    } else {
      $str = $this->rpt_employee_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_employee_layout($config, $data)
  {
    $data     = $this->generateResult($config);
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE LEDGER - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function rpt_employee_PDF($config, $data)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $count = 55;
    $page = 54;
    $fontsize = "11";
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/VERDANA.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/VERDANA.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/VERDANAB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    if ($companyid == 3) { //conti
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    }

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(760, 30, "EMPLOYEE LEDGER - PROFILE", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 20, "Employee : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(690, 20, '(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '   ' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 20, "Address : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(690, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $approved, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
} //end class
