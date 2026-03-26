<?php

namespace App\Http\Classes\modules\payrollsetup;

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

class advancesetup
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ADVANCE SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'standardsetupadv';
  public $prefix = 'ADV';
  public $tablelogs = 'payroll_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'docno', 'dateid', 'empid', 'remarks', 'acno', 'amt', 'w1', 'w2', 'w3', 'w4', 'w5',
    'halt', 'priority', 'amortization', 'effdate', 'balance', 'pament', 'w13', 'acnoid'
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
      'view' => 2641,
      'new' => 2639,
      'save' => 2637,
      'delete' => 2640,
      'print' => 2638,
      'edit' => 2642,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $listdocument = 1;
    $listdate = 2;
    $codename = 3;
    $empcode = 4;
    $empname = 5;
    $balance = 6;

    $getcols = ['action', 'listdocument', 'listdate', 'codename', 'empcode', 'empname', 'balance'];
    $stockbuttons = ['view'];

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$codename]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$empcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$empname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $cols[$codename]['label'] = 'Type';
    $cols[$balance]['align'] = 'text-left';
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
      $searchfield = ['p.codename', 'client.client', 's.docno', 'e.emplast', 'e.empfirst', 'e.empmiddle'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select s.trno as clientid, s.docno, date(s.dateid) as dateid, e.empid, 
        client.client as empcode,
        CONCAT(emplast,', ',empfirst,' ',empmiddle) as empname, p.codename, 
        FORMAT(s.balance,2) as balance
        from standardsetupadv as s 
        left join employee as e ON e.empid = s.empid
        left join client on client.clientid=e.empid 
        left join paccount as p on p.line=s.acnoid 
        where $filteroption $filtersearch order by s.docno desc";
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
      'advancetab' => [
        'action' => 'payrollentry',
        'lookupclass' => 'entryadvance',
        'label' => 'ADVANCE'
      ],
      'manualpaymenttab' => [
        'action' => 'payrollentry',
        'lookupclass' => 'entryadvancepayment',
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
    data_set($col1, 'client.lookupclass', 'lookupadvance');

    data_set($col1, 'remarks.type', 'ctextarea');

    $fields = ['empid', 'empcode', 'empname', 'acno', 'acnoname', 'priority'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'empname.action', 'lookupclient');
    data_set($col2, 'empname.lookupclass', 'employee');

    data_set($col2, 'acno.readonly', true);
    data_set($col2, 'acno.type', 'input');
    data_set($col2, 'acno.class', 'csacno sbccsreadonly');

    data_set($col2, 'acnoname.action', 'lookuppacno');
    data_set($col2, 'acnoname.lookupclass', 'lookuppacno');
    data_set($col2, 'acnoname.readonly', true);
    data_set($col2, 'acnoname.class', 'csacnoname sbccsreadonly');

    data_set($col2, 'priority.type', 'cinput');

    $fields = ['amt', 'amortization', 'bal', 'effdate'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'amt.label', 'Amount');
    data_set($col3, 'bal.name', 'balance');

    data_set($col3, 'bal.class', 'sbccsreadonly');

    data_set($col3, 'amt.type', 'cinput');
    data_set($col3, 'amortization.type', 'cinput');
    data_set($col3, 'bal.type', 'cinput');

    $fields = [['w1', 'w4'], ['w2', 'w5'], ['w3', 'w13'], 'halt'];
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
    $qry = "select ifnull(line, 0) as acnoid, ifnull(code, '') as acno, 
      ifnull(codename, '') as acnoname
      from paccount where code = ?";
    $accnt = $this->coreFunctions->opentable($qry, ['PT69']);

    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['remarks'] = '';
    $data[0]['empid'] = '';
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['acno'] = $accnt[0]->acno;
    $data[0]['acnoid'] = $accnt[0]->acnoid;
    $data[0]['acnoname'] = $accnt[0]->acnoname;
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
    $data[0]['halt'] = '0';
    $data[0]['w13'] = '0';

    return $data;
  }


  public function loadheaddata($config)
  {
    $clientid = $config['params']['clientid'];

    $clientid = $this->othersClass->val($clientid);
    if ($clientid == 0) $clientid = $this->getlastclient();

    $qryselect = "select s.trno as clientid, s.docno as client, s.docno, s.dateid, s.empid, s.remarks, pac.code as acno, s.amt, s.paymode,
                    w1,w2,w3,w4,w5,w13,halt,s.priority, s.earnded, s.amortization, s.effdate,s.payment,
                    concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname, pac.codename as acnoname, client.client as empcode,
                    balance, s.acnoid
                    ";

    $qry = $qryselect . " 
        from standardsetupadv as s
        left join employee as e on s.empid = e.empid
        left join client on client.clientid = e.empid
        left join paccount as pac on pac.line = s.acnoid
        left join standardtransadv as st on s.trno = st.trno
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
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['clientid']]);
      $clientid = $head['clientid'];

      // $this->logger->sbcmasterlog(
      // $clientid,
      // $config,
      // 'UPDATE' . ' - ' .$head['client'].' - '.$head['empname'] . ' - '. $head['acnoname']
      // . ' - '. 'AMT: ' .$head['amt']. ' - '. 'BAL: ' .$head['balance']);
    } else {
      $head['balance'] = $head['amt'];
      $data['balance'] = $head['balance'];
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);

      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'CREATE' . ' - ' . $head['client'] . ' - ' . $head['empname'] . ' - ' . $head['acnoname']
          . ' - ' . 'AMT: ' . $head['amt'] . ' - ' . 'BAL: ' . $head['balance']
      );
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

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

    $qry = "select trno as value from standardtransadv where trno=?";
    $count = $this->coreFunctions->datareader($qry, [$clientid]);

    if ($count != '') {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$clientid]);
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  // -> print function

  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter();
    // $txtdata = $this->reportparamsdata($config);

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  // public function reportsetup($config)
  // {
  //   $txtfield = $this->createreportfilter();
  //   $txtdata = $this->reportparamsdata($config);
  //   $modulename = $this->modulename;
  //   $data = [];
  //   $style = 'width:500px;max-width:500px;';
  //   return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  // }

  // public function reportdata($config)
  // {
  //   $data = $this->report_default_query($config);
  //   $str = $this->rpt_leavesetup_masterfile_layout($data, $config);
  //   return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  // }

  // public function createreportfilter()
  // {
  //   $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
  //   $col1 = $this->fieldClass->create($fields);
  //   return array('col1' => $col1);
  // }

  // public function reportparamsdata($config)
  // {
  //   return $this->coreFunctions->opentable(
  //     "select
  //       'default' as print,
  //       '' as prepared,
  //       '' as approved,
  //       '' as received
  //       "
  //   );
  // }

  // private function report_default_query($config)
  // {
  //   $trno = $config['params']['dataid'];
  //   $query = "select ss.trno, ss.docno,ifnull(st.docno, '') as transdoc, date(ss.dateid) as ssdate, ss.remarks,
  //               concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
  //               ifnull(st.batch, '') as batch, date(st.dateid) as stdate, 
  //               ifnull(st.db, 0) as db, ifnull(st.cr, 0) as cr, st.ismanual,
  //               ss.amt,ss.amortization,ss.balance,pa.codename,ss.remarks,date(ss.effdate) as effdate,
  //               (case when ss.w1=1 then 'YES' else 'NO' end) as w1,
  //               (case when ss.w2=1 then 'YES' else 'NO' end) as w2,
  //               (case when ss.w3=1 then 'YES' else 'NO' end) as w3,
  //               (case when ss.w4=1 then 'YES' else 'NO' end) as w4,
  //               (case when ss.w5=1 then 'YES' else 'NO' end) as w5,
  //               (case when ss.w13=1 then 'YES' else 'NO' end) as w13,
  //               (case when ss.halt=1 then 'YES' else 'NO' end) as halt
  //               from standardsetupadv as ss
  //               left join standardtransadv as st on ss.trno = st.trno
  //               left join employee as e on ss.empid = e.empid
  //               left join paccount as pa on pa.line=ss.acnoid
  //               where ss.trno = $trno
  //               order by ss.dateid";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // } //end fn

  // private function rpt_default_header($data, $filters)
  // {

  //   $companyid = $filters['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

  //   $center = $filters['params']['center'];
  //   $username = $filters['params']['user'];

  //   $str = '';
  //   $layoutsize = '1000';
  //   $font = "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->letterhead($center, $username);
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/><br/>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('ADVANCE SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Docno :', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['docno']) ? $data[0]['docno'] : "", '620', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Date :', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['ssdate']) ? $data[0]['ssdate'] : "", '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Employee Name:', '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['empname']) ? $data[0]['empname'] : "", '690', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('600');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Account Description:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['codename']) ? $data[0]['codename'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Deduction Start:', '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['effdate']) ? $data[0]['effdate'] : "", '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('600');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Principal Amount:', '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['amt']) ? $data[0]['amt'] : "", '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Amortization:', '90', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['amortization']) ? $data[0]['amortization'] : "", '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Balance:', '90', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['balance']) ? $data[0]['balance'] : "", '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('500');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Deduction Week:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Week 1:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['w1']) ? $data[0]['w1'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Week 2:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['w2']) ? $data[0]['w2'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Week 3:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['w3']) ? $data[0]['w3'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Week 4:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['w4']) ? $data[0]['w4'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Week 5:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['w5']) ? $data[0]['w5'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('13th Month:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['w13']) ? $data[0]['w13'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->col('Halt:', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['halt']) ? $data[0]['halt'] : "", '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();



  //   $str .= $this->reporter->begintable('250');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Notes:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col(isset($data[0]['remarks']) ? $data[0]['remarks'] : "", '220', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable('250');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('250');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('PAYMENT DETAILS:', '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->pagenumber('Page');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Batch/Document #', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Date', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Debit', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Credit', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
  //   // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
  //   $str .= $this->reporter->endrow();
  //   return $str;
  // }

  // private function rpt_leavesetup_masterfile_layout($data, $filters)
  // {
  //   $companyid = $filters['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

  //   $center = $filters['params']['center'];
  //   $username = $filters['params']['user'];

  //   $str = '';
  //   $layoutsize = '1000';
  //   $font = "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";
  //   $count = 35;
  //   $page = 35;

  //   $str .= $this->reporter->beginreport();
  //   $str .= $this->rpt_default_header($data, $filters);
  //   $totalext = 0;
  //   for ($i = 0; $i < count($data); $i++) {
  //     $str .= $this->reporter->startrow();
  //     $str .= $this->reporter->addline();
  //     $str .= $this->reporter->col($data[$i]['transdoc'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['stdate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['db'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['cr'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->endrow();

  //     if ($this->reporter->linecounter == $page) {
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->page_break();
  //       $str .= $this->rpt_default_header($data, $filters);
  //       $str .= $this->reporter->printline();
  //       $page = $page + $count;
  //     }
  //   }

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->printline();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
  //   $str .= $this->reporter->col('Remarks :', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col($data[0]['remarks'], '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .=  '<br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .=  '<br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->endreport();
  //   return $str;
  // } //end fn


} //end class
