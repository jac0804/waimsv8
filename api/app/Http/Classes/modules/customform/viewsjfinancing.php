<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewsjfinancing
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  private $logger;
  public $modulename = 'Financing';
  public $gridname = 'customformacctg';
  private $fields = ['downpayment', 'fmiscfee', 'interestrate', 'fma2', 'penalty', 'rebate', 'fma1'];
  private $table = 'cntnuminfo';
  private $htable = 'hcntnuminfo';



  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';

  public $style = 'width:50%;max-width:50%;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 4606, 'edit' => 4607);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $trno = $config['params']['clientid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");

    $fields = ['downpayment', 'fmiscfee', 'fma1'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'fmiscfee.readonly', false);
    data_set($col1, 'fma1.readonly', false);
    data_set($col1, 'fma1.label', 'Monthly Amortization');


    $fields = ['interestrate', 'fma2'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'fma2.readonly', false);
    data_set($col2, 'fma2.label', 'Factor');
    data_set($col2, 'interestrate.label', 'Interest Rate(%)');

    $fields = ['penalty', 'rebate'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'penalty.label', 'Penalty(%)');
    data_set($col3, 'penalty.readonly', false);
    data_set($col3, 'rebate.readonly', false);


    $fields = ['refresh', 'reload'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'refresh.label', 'Save');
    data_set($col4, 'refresh.action', 'refresh');
    data_set($col4, 'reload.label', 'RELOAD GRID');

    if ($isposted) {
      data_set($col1, 'downpayment.readonly', true);
      data_set($col1, 'fmiscfee.readonly', true);

      data_set($col2, 'interestrate.readonly', true);
      data_set($col2, 'fma2.readonly', true);

      data_set($col3, 'penalty.readonly', true);
      data_set($col3, 'rebate.readonly', true);
      data_set($col1, 'fma1.readonly', true);

      data_set($col4, 'refresh.type', 'hidden');
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function cntnuminfo_qry($trno)
  {
    $qry = "
    select " . $trno . " as trno, ifnull(hinfo.interestrate,0) as interestrate,format(ifnull(hinfo.downpayment,0),2) as downpayment,format(ifnull(hinfo.fmiscfee,0),2) as fmiscfee,ifnull(hinfo.fma2,0) as fma2,
    ifnull(hinfo.penalty,0) as penalty,format(ifnull(hinfo.rebate,0),2) as rebate,format(ifnull(hinfo.fma1,0),2) as fma1
    from cntnuminfo as hinfo
    where hinfo.trno=?
    union all
    select " . $trno . " as trno,ifnull(hinfo.interestrate,0) as interestrate,format(ifnull(hinfo.downpayment,0),2) as downpayment,format(ifnull(hinfo.fmiscfee,0),2) as fmiscfee,ifnull(hinfo.fma2,0) as fma2,
    ifnull(hinfo.penalty,0) as penalty,format(ifnull(hinfo.rebate,0),2) as rebate,format(ifnull(hinfo.fma1,0),2) as fma1
    from hcntnuminfo as hinfo
    where hinfo.trno=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);

    if (empty($data)) {
      $data = $this->coreFunctions->opentable("select  $trno as trno,0 as interestrate,0 as downpayment,0 as fmiscfee,0 as fma2,
      0 as penalty,0 as rebate,0 as fma1");
    }

    return $data;
  }

  public function getheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $trno = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['dataparams']['trno'];

    $data = $this->cntnuminfo_qry($trno);
    return $data;
  }

  public function data($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $this->modulename = 'Financing Calculator';

    $interest = 0;
    $principal = 1;
    $payment = 2;
    $tab = [$this->gridname => ['gridcolumns' => ['interest', 'principal', 'payment']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$interest]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$principal]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$payment]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$interest]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$principal]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$payment]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['totalfield'] = [];
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function loaddata($config)
  {
    $action = $config['params']['action2'];
    $trno  = $config['params']['dataparams']['trno'];

    switch ($action) {
      case 'refresh';
        return $this->save($config);
        break;

      default:
        $txtdata = $this->getheaddata($config);

        $qry = "select format(interest,2) as interest,format(principal,2) as principal,format(payment,2) as payment from detailinfo where trno =  " . $trno . "
                union all 
                select format(interest,2) as interest,format(principal,2) as principal,format(payment,2) as payment from hdetailinfo where trno =  " . $trno;
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata, 'qry' => $qry];
        break;
    }
  }

  private function save($config)
  {
    $trno  = $config['params']['dataparams']['trno'];
    $head = $config['params']['dataparams'];

    foreach ($this->fields as $key2) {
      $info[$key2] = $head[$key2];
      $info[$key2] = $this->othersClass->sanitizekeyfield($key2, $info[$key2]);
    }

    $allow_edit = $this->othersClass->checkAccess($config['params']['user'], 4607);
    if ($allow_edit) {
      $this->coreFunctions->LogConsole('MA:' . $info['fma1']);
      if (floatval($info['fma1']) == 0) {
        $data = $this->coreFunctions->opentable("select head.dateid,head.terms," . $info['downpayment'] . " as downpayment, " . $info['fmiscfee'] . " as fmiscfee, 
        " . $info['rebate'] . " as rebate, " . $info['interestrate'] . " as interestrate,
        " . $info['penalty'] . " as penalty, " . $info['fma2'] . " as factor,terms.days,
        ifnull(hinfo.termsmonth,0) as termsmonth,
        (select ifnull(sum(stock.ext),0) as ext from lastock as stock where stock.trno = $trno) as amt from lahead as head 
        left join cntnuminfo as hinfo on hinfo.trno = head.trno left join terms on terms.terms = head.terms  where head.trno = ?", [$trno]);

        if (!empty($data)) {
          if(floatval($data[0]->amt) <> 0){
            $data[0]->amt = $this->othersClass->sanitizekeyfield('amt', $data[0]->amt);
            $data[0]->fmiscfee = $this->othersClass->sanitizekeyfield('amt', $data[0]->fmiscfee);
            $data[0]->downpayment = $this->othersClass->sanitizekeyfield('amt', $data[0]->downpayment);
            $this->coreFunctions->LogConsole('Amt:' . $data[0]->amt);
            $this->coreFunctions->LogConsole('Misc:' . $data[0]->fmiscfee);
            $this->coreFunctions->LogConsole('DP:' . $data[0]->downpayment);
            $financeamt = ($data[0]->amt + $data[0]->fmiscfee) - $data[0]->downpayment;
            $ma = round($data[0]->factor * $financeamt, 0, PHP_ROUND_HALF_UP);
            $info['fma1'] = $ma;
          }else{
            return ['status' => false, 'msg' => 'Update Not Allowed, Transaction has 0 value. Please select an MC Unit.', 'txtdata' => $data, 'data' => []];
          }
          
        }
      }

      $exist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$trno]);
      $ext = $this->coreFunctions->getfieldvalue("lastock", "ifnull(sum(ext),0)", "trno=?", [$trno]);

      if($ext == 0){
        $data = $this->getheaddata($config);
        return ['status' => false, 'msg' => 'Update Not Allowed, Transaction has 0 value. Please select an MC Unit.', 'txtdata' => $data, 'data' => []];
      }

      if ($exist == 0) {
        $info['trno'] = $trno;
        $this->coreFunctions->sbcinsert('cntnuminfo', $info);
        $this->logger->sbcwritelog(
          $trno,
          $config,
          'CREATE',
          'CNTNUMINFO TRNO: ' . $info['trno']
        );
      } else {
        $this->coreFunctions->sbcupdate('cntnuminfo', $info, ['trno' => $head['trno']]);

        $this->logger->sbcwritelog(
          $trno,
          $config,
          "UPDATE",
          'Downpayment: ' . $info['downpayment'] . ' - ' .
            'MA : ' . $info['fma1'] . ' - ' .
            'Misc Fee. : ' . $info['fmiscfee'] . ' - ' .
            'Interest : ' . $info['interestrate'] . ' - ' .
            'Factor : ' . $info['fma2'] . ' - ' .
            'Penalty : ' . $info['penalty'] . ' - ' .
            'Rebate : ' . $info['rebate']
        );
      }

      $data = $this->getheaddata($config);
      return $this->compute($config);
    } else {
      $data = $this->getheaddata($config);
      return ['status' => false, 'msg' => 'Update Not Allowed, Please Contact Admin.', 'txtdata' => $data, 'data' => []];
    }
  }


  public function compute($config)
  {
    $trno = $config['params']['dataparams']['trno'];
    $head = $config['params']['dataparams'];

    $d = [];
    $det = [];
    $di = [];
    $dinfo = [];
    $i = 2;
    $prevbal = 0;
    $principal = 0;
    $prevbalh = 0;
    $prevball = 0;
    $prevprincipalcol = 0;
    $financeamt = 0;
    $ma = 0;
    $status = true;
    $msg = '';
    $total = 0;

    $data = $this->coreFunctions->opentable("select head.dateid,head.terms,ifnull(hinfo.downpayment,0) as downpayment, ifnull(hinfo.fmiscfee,0) as fmiscfee, 
    ifnull(hinfo.rebate,0) as rebate, ifnull(hinfo.interestrate,0) as interestrate,
    ifnull(hinfo.penalty,0) as penalty, ifnull(hinfo.fma2,0) as factor,terms.days,
    ifnull(hinfo.termsmonth,0) as termsmonth, ifnull(hinfo.fma1,0) as fma1,
    (select sum(stock.ext) as ext from lastock as stock where stock.trno = head.trno) as amt from lahead as head 
    left join cntnuminfo as hinfo on hinfo.trno = head.trno left join terms on terms.terms = head.terms  where head.trno = ?", [$trno]);

    if (!empty($data)) {
      $data[0]->amt = $this->othersClass->sanitizekeyfield('amt', $data[0]->amt);
      $data[0]->fmiscfee = $this->othersClass->sanitizekeyfield('amt', $data[0]->fmiscfee);
      $data[0]->downpayment = $this->othersClass->sanitizekeyfield('amt', $data[0]->downpayment);
      $data[0]->fma1 = $this->othersClass->sanitizekeyfield('amt', $data[0]->fma1);

      $financeamt = ($data[0]->amt + $data[0]->fmiscfee) - $data[0]->downpayment;
      $ma = $data[0]->fma1;


      $prevbal = $financeamt;
      //monthly ma
      $balmons = $data[0]->days;
      $rdate = strtotime($data[0]->dateid);
      $mos = $data[0]->days;

      for ($y = 1; $y <= $balmons; $y++) {
        //detailinfo
        $interest = $prevbal * ($data[0]->interestrate / 100);
        $principal = $ma - $interest;

        $di['trno'] = $trno;
        $di['line'] = $i;
        $di['interest'] = 0;
        $di['principal'] = $principal;
        $di['payment'] = 0;
        array_push($dinfo, $di);

        $i += 1;
        $di['trno'] = $trno;
        $di['line'] = $i;
        $di['interest'] = round($interest, 2);
        $di['principal'] = 0;
        $di['payment'] = 0;

        $prevbal = $prevbal - $principal;
        $total = $total + $ma;
        array_push($dinfo, $di);
        $i += 1;
      }
    }

    $this->coreFunctions->execqry("delete from detailinfo where trno = " . $trno);
    if (!$this->coreFunctions->sbcinsert('detailinfo', $dinfo)) {
      $status = false;
      $msg = 'Error in Detail info';
    }

    $txtdata = $this->getheaddata($config);

    $qry = "select format(interest,2) as interest,format(principal,2) as principal,format(payment,2) as payment from detailinfo where trno =  " . $trno;
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata, 'qry' => $qry];
  }
}
