<?php

namespace App\Http\Classes\modules\customform;

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

class viewfinancing
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Financing Calculator';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $this->modulename = 'financing Calculator - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);

    $action = 0;
    $status = 1;
    $dateid = 2;
    $due = 3;
    $receivedate = 4;
    $docno = 5;
    $db = 6;
    $cr = 7;
    $bal = 8;
    $ref = 9;
    $rem = 10;
    $krdoc = 11;
    $rem1 = 12;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'status', 'dateid', 'due', 'receivedate', 'docno',  'bal', 'ref', 'rem', 'krdoc', 'rem1']]];

    $stockbuttons = ['referencemodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $companyid = $config['params']['companyid'];
    // 4 = checkdate
    if ($companyid == 17) { //unihome
      $obj[0][$this->gridname]['columns'][$docno]['label'] = 'Doc no';
    }

    // 6 = db
    $obj[0][$this->gridname]['columns'][$db]['align'] = 'text-right';
    // 7 = cr
    $obj[0][$this->gridname]['columns'][$cr]['align'] = 'text-right';
    // 8 = bal
    $obj[0][$this->gridname]['columns'][$bal]['align'] = 'text-right';

    if ($companyid != 10) { //not afti
      $obj[0][$this->gridname]['columns'][$receivedate]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {

    $fields = ['finterestrate', 'termsmonth', 'termspercentdp', 'termsyear', 'termspercent'];
    $col1 = $this->fieldClass->create($fields);


    $fields = ['reservationdate', 'dueday', 'reservationfee', 'farea', 'fpricesqm', 'ftcplot', 'ftcphouse', 'fma1', 'fma2', 'fma3'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'reservationdate.readonly', false);

    $fields = [['fsellingpricegross', 'fdiscount', 'fsellingpricenet', 'fmiscfee', 'fcontractprice', 'fmonthlydp', 'fmonthlyamortization', 'ffi', 'fmri']];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['refresh'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'refresh.action', 'financing');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select 0 as fma1, 0 as fma2, 0 as fma3,0 as fcontractprice,17 as finterestrate,24 as termsmonth,20 as termspercentdp,10 as termsyear,80 as termspercent,left(now(),10) as reservationdate,21 as dueday,35000 as reservationfee,162 as farea,16000 as fpricesqm, 0.00 as ftcplot,6297000 as ftcphouse,0.00 as fsellingpricegross,0.00 as fdiscount,0.00 as fsellingpricenet,0.00 as fmiscfee,0.00 as fmonthlydp,0.00 as fmonthlyamortization,0.00 as ffi,0.00 as fmri');
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $fma1 = $config['params']['dataparams']['fma1'];
    $fma2 = $config['params']['dataparams']['fma2'];
    $fma3 = $config['params']['dataparams']['fma3'];
    $finterestrate = $config['params']['dataparams']['finterestrate'];
    $termsmonth = $config['params']['dataparams']['termsmonth'];
    $termspercentdp = $config['params']['dataparams']['termspercentdp'];
    $termsyear = $config['params']['dataparams']['termsyear'];
    $termspercent = $config['params']['dataparams']['termspercent'];
    $reservationdate = $config['params']['dataparams']['reservationdate'];
    $dueday = $config['params']['dataparams']['dueday'];
    $reservationfee = $config['params']['dataparams']['reservationfee'];
    $farea = $config['params']['dataparams']['farea'];
    $fpricesqm = $config['params']['dataparams']['fpricesqm'];
    $ftcplot = $config['params']['dataparams']['ftcplot'];
    $ftcphouse = $config['params']['dataparams']['ftcphouse'];
    $fsellingpricegross = $config['params']['dataparams']['fsellingpricegross'];
    $fdiscount = $config['params']['dataparams']['fdiscount'];
    $fsellingpricenet = $config['params']['dataparams']['fsellingpricenet'];
    $fcontractprice = $config['params']['dataparams']['fcontractprice'];
    $fmiscfee = $config['params']['dataparams']['fmiscfee'];
    $fmonthlydp = $config['params']['dataparams']['fmonthlydp'];
    $fmonthlyamortization = $config['params']['dataparams']['fmonthlyamortization'];
    $ffi = $config['params']['dataparams']['ffi'];
    $fmri = $config['params']['dataparams']['fmri'];

    $fi = 0.00024;
    $mri = 0.001;
    $ftcplot = round($farea * $fpricesqm, 2);
    $fsellingpricegross = round($ftcplot + $ftcphouse, 2);
    $fsellingpricenet = round($fsellingpricegross - $fdiscount, 2);
    $fmiscfee = round($fsellingpricenet * .10, 2);
    $fcontractprice = round($fsellingpricenet + $fmiscfee, 2);
    $loanamt = $fcontractprice * ($termspercent / 100);
    $loandepo = round($fcontractprice * ($termspercentdp / 100), 2);

    $fma1 = $this->othersClass->calPMT($finterestrate, $termsyear, $loanamt);
    $fma2 = round($fma1 / $loanamt, 8);
    $factorratewithinsurance = $fi + $mri + $fma2;

    $ffi = round($fi * $loanamt, 2);
    $fmri = round($mri * $loanamt, 2);
    $fma3 = round($fma1 + $ffi + $fmri, 2);
    $fmonthlydp = round(($loandepo - $reservationfee) / $termsmonth, 2);
    $fmonthlyamortization = round($loanamt * $factorratewithinsurance, 2);

    $txtdata = $this->coreFunctions->opentable("select " . $fma1 . " as fma1, " . $fma2 . " as fma2, " . $fma3 . " as fma3," . $fcontractprice . " as fcontractprice, " . $finterestrate . " as finterestrate, " . $termsmonth . " as termsmonth," . $termspercentdp . " as termspercentdp," . $termsyear . " as termsyear," . $termspercent . " as termspercent,'" . $reservationdate . "' as reservationdate," . $dueday . " as dueday," . $reservationfee . " as reservationfee," . $farea . " as farea," . $fpricesqm . " as fpricesqm, " . $ftcplot . " as ftcplot," . $ftcphouse . " as ftcphouse," . $fsellingpricegross . " as fsellingpricegross," . $fdiscount . " as fdiscount," . $fsellingpricenet . " as fsellingpricenet," . $fmiscfee . " as fmiscfee," . $fmonthlydp . " as fmonthlydp," . $fmonthlyamortization . " as fmonthlyamortization," . $ffi . " as ffi," . $fmri . " as fmri");

    $qry = "select '' as docno ";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata, 'qry' => $qry];
  }
} //end class
