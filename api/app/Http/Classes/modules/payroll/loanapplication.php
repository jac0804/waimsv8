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
use DateInterval;
use DatePeriod;
use DateTime;

class loanapplication
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LOAN APPLICATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'standardsetup';
  public $prefix = 'EL';
  public $tablelogs = 'payroll_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'docno',
    'dateid',
    'empid',
    'remarks',
    'acno',
    'amt',
    'w1',
    'w2',
    'w3',
    'w4',
    'w5',
    'halt',
    'priority',
    'amortization',
    'effdate',
    'balance',
    'pament',
    'w13',
    'acnoid',
    'totalterms',
    'enddate'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = ['w1', 'w2', 'w3', 'w4', 'w5', 'halt', 'w13'];
  public $showfilteroption = true;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'With Balance', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Without Balance', 'color' => 'primary']
  ];

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
      'view' => 2425,
      'new' => 2423,
      'save' => 2421,
      'delete' => 2424,
      'print' => 2422,
      'edit' => 2426,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $acnoname = 5;
    $bal = 6;

    $getcols = ['action', 'listdocument', 'listdate', 'empcode', 'empname', 'acnoname', 'bal'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$acnoname]['type'] = 'label';
    $cols[$acnoname]['label'] = 'Account Name';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filteroption = '';
    $option = $config['params']['itemfilter'];
    if ($option == 'draft') {
      $filteroption = " s.balance<>0";
    } else {
      $filteroption = " s.balance=0";
    }
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['e.empid', 'client.client', 'client.clientname', 'acct.codename', 's.docno'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }
    // $id = $config['params']['adminid'];
    $qry = "select s.trno as clientid, s.docno, date(s.dateid) as dateid, e.empid, client.client as empcode, 
        client.clientname as empname, acct.codename as acnoname, s.balance as bal
        from standardsetup as s 
        left join employee as e ON e.empid = s.empid
        left join client on client.clientid=e.empid
        left join paccount as acct on acct.line = s.acnoid
        where $filteroption $filtersearch
        order by s.docno desc";
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
    $tab = [
      'jobdesctab' => [
        'action' => 'payrollentry',
        'lookupclass' => 'entryearningdeduction',
        'label' => 'EARNING AND DEDUCTION'
      ],
      'skilldesctab' => [
        'action' => 'payrollentry',
        'lookupclass' => 'entryedpayment',
        'label' => 'MANUAL PAYMENT'
      ]
    ];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $fields = ['client', 'dateid', 'remarks'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.label', 'Docno #');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupearningdeduction');

    data_set($col1, 'remarks.type', 'ctextarea');

    $fields = ['empcode', 'empname', 'acno', 'acnoname', 'status'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'acno.lookupclass', 'lookuploanapp_account');
    data_set($col2, 'acnoname.readonly', true);
    data_set($col2, 'acnoname.class', 'csacnoname sbccsreadonly');

    $fields = ['amt', 'totalterms', ['effdate', 'enddate'], 'amortization', 'bal'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'amt.label', 'Total Loan Amount');
    data_set($col3, 'amt.type', 'cinput');

    data_set($col3, 'bal.name', 'balance');
    data_set($col3, 'bal.type', 'cinput');
    data_set($col3, 'bal.class', 'csbal sbccsreadonly');
    data_set($col3, 'enddate.class', 'csenddate sbccsreadonly');
    data_set($col3, 'amt.required', true);
    data_set($col3, 'totalterms.required', true);
    data_set($col3, 'amortization.type', 'cinput');
    data_set($col3, 'amortization.class', 'csamortization sbccsreadonly');

    $fields = [['w1', 'w4'], ['w2', 'w5'], ['w3']];
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
    $data[0]['remarks'] = '';
    $data[0]['empid'] = 0;
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['acno'] = '';
    $data[0]['acnoid'] = 0;
    $data[0]['acnoname'] = '';
    $data[0]['priority'] = 0;
    $data[0]['amt'] = 0;
    $data[0]['amortization'] = 0;
    $data[0]['balance'] = 0;
    $data[0]['effdate'] = $this->othersClass->getCurrentDate();
    $data[0]['w1'] = '0';
    $data[0]['w2'] = '0';
    $data[0]['w3'] = '0';
    $data[0]['w4'] = '0';
    $data[0]['w5'] = '0';
    $data[0]['status'] = 'ENTRY';

    return $data;
  }

  public function loadheaddata($config)
  {
    $clientid = $config['params']['clientid'];

    if ($clientid == 0) {
      $clientid = $this->getlastclient();
    }

    $qryselect = "select s.trno as clientid, s.docno as client, 
        s.docno, s.dateid, s.empid, s.remarks, pac.code as acno, s.amt, s.paymode, 
        w1,w2,w3,w4,w5,w13,halt,s.priority, s.earnded, s.amortization, 
        s.effdate,s.payment,
        client.clientname as empname, 
        pac.codename as acnoname, client.client as empcode,
        balance, s.acnoid, 
        case 
          when s.status = 'E' then 'ENTRY'
          else s.status
        END as status,s.totalterms,s.enddate";

    $qry = $qryselect . " from standardsetup as s
        left join employee as e on s.empid = e.empid
        left join client on client.clientid = e.empid
        left join paccount as pac on pac.line = s.acnoid
        left join standardtrans as st on s.trno = st.trno
        where s.trno = ? ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      //$stock = $this->openstock($clientid, $config);
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
    if ($isupdate) {
      unset($this->fields['docno']);
    } else {
      $data['docno'] = $head['client'];
      $head['docno'] = $head['client'];
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
      if (substr($head['status'], 0, 1) != 'E') {
        return ['status' => false, 'msg' => "Can't Modified", 'clientid' => '0'];
      }
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];

      $data['w2'] = '1';
      $data['w4'] = '1';

      if ($data['totalterms'] == 0) {
        $data['amortization'] = $data['amt'];
      } else {
        $data['amortization'] = ($data['amt'] / $data['totalterms']) / 2;
      }

      $paymentmonths = $data['totalterms'];
      $date = new DateTime($data['effdate']);
      $date->modify("+$paymentmonths months");
      $end = $date->format('Y-m-d');

      $data['enddate'] = $end;

      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['clientid']]);
      $clientid = $head['clientid'];

      // $this->logger->sbcmasterlog(
      // $clientid,
      // $config,
      // "UPDATE - NAME: ".$head['empname'].", ACCNT: ".$head['acnoname'].", AMT: ".$head['amt'].", BAL: ".$head['balance'].""); 
    } else {

      $data['status'] = "E";
      $data['balance'] = $data['amt'];
      $data['w2'] = '1';
      $data['w4'] = '1';

      if ($data['totalterms'] == 0) {
        $data['amortization'] = $data['amt'];
      } else {
        $data['amortization'] = ($data['amt'] / $data['totalterms']) / 2;
      }


      $paymentmonths = $data['totalterms'];
      $date = new DateTime($data['effdate']);
      $date->modify("+$paymentmonths months");
      $end = $date->format('Y-m-d');

      $data['enddate'] = $end;

      $clientid = $this->coreFunctions->insertGetId($this->head, $data);

      $balance = $head['balance'];
      if ($balance == 0) {
        $balance = $head['amt'];
      }

      $data2 = [];

      if ($config['params']['companyid'] == 58) { //cdo
        $chkexist = $this->coreFunctions->getfieldvalue("standardsetup", "trno", "acnoid=? and empid=? and balance > 0 and trno <> ?", [$data['acnoid'], $data['empid'], $clientid]);
        if ($chkexist) {
          $docno = $this->coreFunctions->getfieldvalue("standardsetup", "docno", "trno=?", [$chkexist]);
          $bal = $this->coreFunctions->datareader("select balance as value from standardsetup where trno=" . $chkexist);

          $data2 = [
            'trno' => $clientid,
            'docno' => $docno,
            'dateid' => $this->othersClass->getCurrentTimeStamp(),
            'empid' => $data['empid'],
            'cr' => $bal,
            'acnoid' => $data['acnoid'],
            'ismanual' => 1,
            'manualref' => $docno
          ];

          if ($this->coreFunctions->insertGetId('standardtrans', $data2)) {
            $this->coreFunctions->execqry("update standardsetup set balance = 0 where trno=" . $chkexist);
          }
        }
      }

      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        "CREATE - NAME: " . $head['empname'] . ", ACCNT: " . $head['acnoname'] . ", AMT: " . $head['amt'] . ", BAL: " . $balance . ""
      );
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function approvers($params)
  {
    $companyid = $params['companyid'];
    switch ($companyid) {
      case 44: // stonepro
      case 58: // cdohris
        $approvers = ['issupervisor', 'isapprover'];
        break;
      default:
        $approvers = ['isapprover'];
        break;
    }
    return $approvers;
  }

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select trno as value 
      from " . $this->head . " 
      order by trno DESC LIMIT 1");

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

    $qry = "select line as value from standardtrans where line=?";
    $count = $this->coreFunctions->datareader($qry, [$clientid]);

    if ($count != '') {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$clientid]);
    if ($config['params']['companyid'] == 58) {
      $this->coreFunctions->execqry('delete from pendingapp where trno=?', 'delete', [$clientid]);
    }
    return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  // -> print function
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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
