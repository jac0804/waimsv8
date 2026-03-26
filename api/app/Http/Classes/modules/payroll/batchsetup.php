<?php

namespace App\Http\Classes\modules\payroll;

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
use DateTime;

class batchsetup
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BATCH SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'batch';
  public $prefix = '';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'batch',
    'dateid',
    'startdate',
    'enddate',
    'paymode',
    'postdate',
    'sss',
    'ph',
    'hdmf',
    'tax',
    'adjustm',
    'custcode',
    'allow',
    'pgroup',
    'is13',
    '13start',
    '13end',
    'divid',
    'branchid'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = ['istax', 'is13'];
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
      'view' => 1351,
      'new' => 1349,
      'save' => 1347,
      'delete' => 1350,
      'print' => 1348,
      'edit' => 1352,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];

    // $action = 0;
    // $batch = 1;
    // $listdate = 2;
    // $startdate = 3;
    // $enddate = 4;
    // $paymode = 5;
    // $paygroup = 6;
    // $postdate = 7;
    // $listpostedby = 8;

    $getcols = ['action', 'batch', 'listdate', 'startdate', 'enddate', 'paymode', 'paygroup', 'divname', 'branch', 'postdate', 'listpostedby'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$startdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$enddate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $cols[$listdate]['label'] = 'Create Date';

    $cols[$enddate]['label'] = 'To Date';
    $cols[$postdate]['label'] = 'Closed Date';
    $cols[$listpostedby]['label'] = 'Closed by';

    $cols[$listpostedby]['name'] = 'postby';


    if ($companyid == 58) {
      $cols[$divname]['label'] = 'Company';

      $cols[$divname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
      $cols[$branch]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    } else {
      $cols[$divname]['type'] = 'coldel';
      $cols[$branch]['type'] = 'coldel';
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['b.line', 'b.batch', 'b.custcode', 'pay.paygroup', 'b.postby'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }
    $qry = "
          select
            b.line as clientid,
            b.batch as client,
            b.batch, date(b.dateid) as dateid,
            date(b.startdate) as startdate, date(b.enddate) as enddate,
            case
              when b.paymode = 'm' then 'Monthly'
              when b.paymode = 's' then 'Semi-Monthly'
              when b.paymode = 'w' then 'Weekly'
              when b.paymode = 'p' then 'Pierce'
              when b.paymode = 'l' then 'Last Pay'
            end as paymode,
            b.postdate, b.sss, b.ph, b.hdmf,
            b.tax, b.adjustm, b.custcode, b.allow, pay.paygroup as paygroup,
            b.is13, b.13start, b.13end, b.postby, d.divname, br.clientname as branch
          from batch as b 
          left join paygroup as pay on pay.line=b.pgroup
          left join division as d on d.divid=b.divid left join client as br on br.clientid=b.branchid
          where 1=1 " . $filtersearch . "
          order by b.enddate desc
        ";

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
      // 'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config) {}

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['client', ['paymode', 'paymodetype'], 'tpaygroupname', 'dateid'];

    if ($companyid == 58) { //cdohris
      array_push($fields, "divname", "branchname");
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsreadonly');
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupledgerratesetup');
    data_set($col1, 'client.required', false);

    data_set($col1, 'dateid.label', 'Month Covered');
    data_set($col1, 'dateid.required', true);

    data_set($col1, 'paymode.label', 'Pay Mode');
    data_set($col1, 'paymode.required', true);
    data_set($col1, 'paymode.lookupclass', 'lookupbatchsetuppaymode');
    data_set($col1, 'paymode.action', 'lookupbatchsetuppaymode');

    data_set($col1, 'paymodetype.label', 'Type');
    data_set($col1, 'paymodetype.lookupclass', 'lookuppaymodetype');
    data_set($col1, 'paymodetype.required', true);

    // data_set($col1, 'tpaygroup.name', 'pgroup');
    data_set($col1, 'tpaygroupname.label', 'Pay Group');
    data_set($col1, 'tpaygroupname.lookupclass', 'batchsetuppaygroup');


    if ($companyid == 58) { //cdohris
      data_set($col1, 'divname.class', 'csdivname sbccsreadonly');
      data_set($col1, 'divname.lookupclass', 'lookupempdivision');
      data_set($col1, 'divname.action', 'lookupempdivision');
      data_set($col1, 'divname.type', 'lookup');
      data_set($col1, 'divname.label', 'Company');

      data_set($col1, 'branchname.class', 'csbranchname sbccsreadonly');
      data_set($col1, 'branchname.lookupclass', 'dbranch');
      data_set($col1, 'branchname.action', 'lookupclient');
    }

    $fields = ['start', 'end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.name', 'startdate');
    data_set($col2, 'start.label', 'From');

    data_set($col2, 'end.name', 'enddate');
    data_set($col2, 'end.label', 'To');

    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

    $fields = ['start', 'end', 'is13', 'istax'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'start.name', '13start');
    data_set($col3, 'start.label', 'From');

    data_set($col3, 'end.name', '13end');
    data_set($col3, 'end.label', 'To');

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['paymode'] = '';
    $data[0]['paymodetype'] = '';
    $data[0]['tpaygroupname'] = '';
    $data[0]['pgroup'] = '';
    $data[0]['paycode'] = '';
    $data[0]['startdate'] = null;
    $data[0]['enddate'] = null;
    $data[0]['13start'] = null;
    $data[0]['13end'] = null;
    $data[0]['istax'] = '0';
    $data[0]['is13'] = '0';
    $data[0]['divid'] = '0';
    $data[0]['divname'] = '';
    $data[0]['branchid'] = '0';
    $data[0]['branchname'] = '';
    return $data;
  }


  public function loadheaddata($config)
  {
    $clientid = $this->othersClass->val($config['params']['clientid']);
    if ($clientid == 0) $clientid = $this->getlastclient();

    $qryselect = "
        select
          b.line as clientid,
          b.batch as client,
          date(b.dateid) as dateid,
          b.startdate, b.enddate,
          case
            when b.paymode = 'm' then 'Monthly'
            when b.paymode = 's' then 'Semi-Monthly'
            when b.paymode = 'w' then 'Weekly'
            when b.paymode = 'p' then 'Pierce'
            when b.paymode = 'l' then 'Last Pay'
          end as paymode,
          b.postdate, b.sss, b.ph, b.hdmf,
          case
            when (b.paymode = 's' or b.paymode = 'w' or b.paymode = 'p' or b.paymode = 'l') and right(b.batch, 2) = '13' then '13th'
            when (b.paymode = 'w' or b.paymode = 'p' or b.paymode = 'l') and right(b.batch, 2) = '01' then 'W1'
            when (b.paymode = 'w' or b.paymode = 'p' or b.paymode = 'l') and right(b.batch, 2) = '02' then 'W2'
            when (b.paymode = 'w' or b.paymode = 'p' or b.paymode = 'l') and right(b.batch, 2) = '03' then 'W3'
            when (b.paymode = 'w' or b.paymode = 'p' or b.paymode = 'l') and right(b.batch, 2) = '04' then 'W4'
            when (b.paymode = 'w' or b.paymode = 'p' or b.paymode = 'l') and right(b.batch, 2) = '05' then 'W5'
            when (b.paymode = 's') and right(b.batch, 2) = '02' then '1st Half'
            when (b.paymode = 's') and right(b.batch, 2) = '04' then '2nd Half'
            when (b.paymode = 'm') and right(b.batch, 2) = '04' then '2nd Half'
          end as paymodetype,
          b.tax as istax, b.tax, b.adjustm, b.custcode, b.allow, b.pgroup, is13, b.13start, b.13end, pay.paygroup as tpaygroupname, pay.code as paycode, b.divid, ifnull(d.divname,'') as divname, b.branchid, ifnull(br.clientname,'') as branchname
        ";

    $qry = $qryselect . " from " . $this->head . " as b left join paygroup as pay on pay.line=b.pgroup left join division as d on d.divid=b.divid left join client as br on br.clientid=b.branchid
        where b.line = ?";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else {
          $head[0]->$value = "0";
        }
      }
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];

    $data = [];

    $clientid = 0;

    $val = $head['val'];
    if (substr($head['paymodetype'], -2, 2) == '13') {
      $val = '13';
      $head['is13'] = 1;
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if
      }
    }

    if ($head['13start'] == "") {
      unset($head['13start']);
    }

    if ($head['13end'] == "") {
      unset($head['13end']);
    }

    if ($config['params']['companyid'] == 58) { //cdohris
      if ($data['divid'] == 0) return ['status' => false, 'msg' => 'Please select valid company.'];
      if ($data['branchid'] == 0) return ['status' => false, 'msg' => 'Please select valid branch.'];
    }

    $data['tax'] = $head['istax'];
    $data['paymode'] = substr($head['paymode'][0], 0, 1);
    $data['batch'] = 'P' . substr($head['paymode'][0], 0, 1) . $head['paycode'] . date('Ym', strtotime($head['dateid'])) . $val;

    $dateid = new DateTime($head['dateid']);
    $startdate = new DateTime($head['startdate']);
    $enddate = new DateTime($head['enddate']);
    $msg = '';

    if ($this->coreFunctions->datareader("select batch as value from batch where batch=? and divid=? and branchid=?", [$data['batch'], $data['divid'], $data['branchid']]) == '') {

      if ($dateid->format('yyyy-mm-dd') < $startdate->format('yyyy-mm-dd')) {
        return ['status' => false, 'msg' => 'From: startdate is greater than month covered'];
      }
      if ($config['params']['companyid'] == 58) { //cdohris
      } else {
        if ($enddate->format('yyyy-mm-dd') < $dateid->format('yyyy-mm-dd')) {
          return ['status' => false, 'msg' => 'To: enddate is less  than month covered'];
        }
      }
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);

      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'CREATE' . ' - ' . $data['batch'] . ' - ' . $head['paymode']
      );
    } else {
      $msg = 'Batch code already exist';
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select line as value 
        from " . $this->head . " 
        order by line DESC LIMIT 1");

    return $last_id;
  }

  public function openstock($trno, $config)
  {
    $qry = 'select line, trno, description from jobtdesc where trno=?';
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];

    $qry = "select batchid as value from paytrancurrent where batchid = ?
                union all
                select batchid as value from paytranhistory where batchid = ?";
    $count = $this->coreFunctions->datareader($qry, [$clientid, $clientid]);

    if ($count) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where line=?', 'delete', [$clientid]);
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  //print


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

    $this->logger->sbcviewreportlog($config);
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);

    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
