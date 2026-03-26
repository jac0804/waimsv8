<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

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

class income_statement
{
  public $modulename = 'Income Statement';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $fields = ['dateid', 'due', 'dbranchname', 'costcenter'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'costcenter.label', 'Item Group');
    } else {
      $fields = ['dateid', 'due', 'dcentername', 'costcenter'];
      $col2 = $this->fieldClass->create($fields);
    }

    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $fields = ['forex', 'radioposttype'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'forex.readonly', false);
      data_set($col3, 'forex.required', true);
    } else {
      $fields = ['radioposttype'];
      $col3 = $this->fieldClass->create($fields);
      data_set(
        $col3,
        'radioposttype.options',
        [
          ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
          ['label' => 'All Transactions', 'value' => '2', 'color' => 'teal']
        ]
      );
    }

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,left(now(),10) as due,'0' as posttype,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '' as costcenter,'' as code,'' as name,'0' as costcenterid 
        ";
        break;
      case 12: //afti usd
      case 10: //afti
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as dateid,
        left(now(),10) as due,
        0 as branchid,
        '' as branch,
        '' as branchcode,
        '' as branchname,
        '' as dbranchname,
        0 as costcenterid,
        '' as code,
        '' as name,
        '' as forex,
        '0' as posttype,
        '' as costcenter";
        break;
      default:
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,left(now(),10) as due,
                            '0' as posttype,'' as center,'' as centername,'' as dcentername,
                            '' as costcenter,'' as code,'' as name,'0' as costcenterid";
        break;
    }
    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $company = $config['params']['companyid'];
    switch ($company) {
      case 10: //afti
      case 12: //afti usd
        $result = $this->aftech_default_query($config);
        $reportdata =  $this->AFTECH_DEFAULT_BALANCE_SHEET_LAYOUT($config, $result);
        break;
      case 19: //housegem
        $result = $this->housegem_default_query($config);
        $reportdata =  $this->HOUSEGEM_DEFAULT_BALANCE_SHEET_LAYOUT($config, $result);
        break;
      case 32: //3m
        $result = $this->mmm_default_query($config);
        $reportdata =  $this->MMM_BALANCE_SHEET_LAYOUT($config, $result);
        break;
      case 40: //cdo
        $result = $this->CDO_query($config);
        $reportdata =  $this->CDO_INCOME_STATEMENT_LAYOUT($config, $result);
        break;
      default:
        $result = $this->default_query($config);
        $reportdata =  $this->DEFAULT_BALANCE_SHEET_LAYOUT($config, $result);
        break;
    }

    return $reportdata;
  }

  //MAIN QRY START
  public function default_query($filters)
  {
    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['costcenterid'];
    $costcentercode = $filters['params']['dataparams']['code'];
    $cc = '';
    $filter = '';

    if ($costcentercode != "") {
      $filter = $costcenter;
    } else {
      $filter = 0;
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as amt,0 as detail,0 as total";

    $coa = $this->coreFunctions->opentable($query2);
    $amt1 = 0;
    $amt1b = 0;
    $amt2 = 0;
    $amt3 = 0;
    $amt2b = 0;
    $amt3b = 0;
    $amtgrp = 0;
    $a = 0;

    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'R', $amt1, $amt1b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>NET SALES</b>', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b);
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'G', $amt1, $amt1b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'E', $amt2, $amt2b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'O', $amt3, $amt3b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $incomelabel = '<b>NET INCOME</b>';
    $coa[] = array('acno' => '//4999', 'acnoname' => $incomelabel, 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b - $amt2b - $amt3b);

    $array = json_decode(json_encode($coa), true); // for clearing set to array
    return $array;
  }

  public function mmm_default_query($filters)
  {
    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['costcenterid'];
    $costcentercode = $filters['params']['dataparams']['code'];
    $cc = '';
    $filter = '';

    if ($costcentercode != "") {
      $filter = $costcenter;
    } else {
      $filter = 0;
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as amt,0 as detail,0 as total";

    $coa = $this->coreFunctions->opentable($query2);
    $amt1 = 0;
    $amt1b = 0;
    $amt2 = 0;
    $amt2b = 0;
    $amt3 = 0;
    $amt3b = 0;
    $amt4 = 0;
    $amt4b = 0;
    $amt5 = 0;
    $amt5b = 0;
    $amtgrp = 0;
    $a = 0;

    $this->MMM_DEFAULT_PLANTTREE($coa, '\\\\', 'R', $amt1, $amt1b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $this->MMM_DEFAULT_PLANTTREE($coa, '\\\\5', 'G', $amt4, $amt4b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $this->MMM_DEFAULT_PLANTTREE($coa, '\\\\4', 'G', $amt5, $amt5b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $coa[] = array('acno' => '//666', 'acnoname' => '', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt4b + $amt5b);
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>INCOME FROM SALES</b>', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b - ($amt4b + $amt5b));
    $this->MMM_DEFAULT_PLANTTREE($coa, '\\\\4', 'O', $amt3, $amt3b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>GROSS INCOME</b>', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => ($amt1b + $amt3b) - ($amt4b + $amt5b));
    $this->MMM_DEFAULT_PLANTTREE($coa, '\\\\', 'E', $amt2, $amt2b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "");

    $incomelabel = '<b>NET INCOME</b>';
    $coa[] = array('acno' => '//4999', 'acnoname' => $incomelabel, 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => ($amt1b + $amt3b) - ($amt4b + $amt5b + $amt2b));

    $array = json_decode(json_encode($coa), true); // for clearing set to array
    return $array;
  }

  public function housegem_default_query($filters)
  {
    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['costcenterid'];
    $costcentercode = $filters['params']['dataparams']['code'];
    $cc = '';
    $filter = '';


    if ($costcentercode != "") {
      $filter = $costcenter;
    } else {
      $filter = 0;
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as amt,0 as detail,0 as total, 0 as isshow, 0 as iscompute, 0 as isparenttotal";

    $coa = $this->coreFunctions->opentable($query2);
    $amt1 = 0;
    $amt1b = 0;
    $amt2 = 0;
    $amt3 = 0;
    $amt2b = 0;
    $amt3b = 0;
    $amtgrp = 0;
    $a = 0;

    $chkgrp = 0;
    if ($company == 19) {
      $chkgrp = 1;
    }

    $prev_incomegrp2 = '';

    $this->HGC_PLANTTREE($coa, '\\\\', 'R', $amt1, $amt1b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], $chkgrp, $amtgrp, $prev_incomegrp2, "");
    $this->HGC_PLANTTREE($coa, '\\\\', 'G', $amt1, $amt1b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], $chkgrp, $amtgrp, $prev_incomegrp2, "NET OF COST SALES");
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>GROSS PROFIT</b>', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b, 'isshow' => 1);
    $this->HGC_PLANTTREE($coa, '\\\\', 'E', $amt2, $amt2b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], $chkgrp, $amtgrp, $prev_incomegrp2, "TOTAL EXPENSES");
    $coa[] = array('acno' => '//667', 'acnoname' => '<b>NET INCOME</b>', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b - $amt2b, 'isshow' => 1);
    $this->HGC_PLANTTREE($coa, '\\\\', 'O', $amt3, $amt3b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], $chkgrp, $amtgrp, $prev_incomegrp2, "TOTAL OTHERS INCOME/LOSS");
    $incomelabel = '<b>NET REVENUE</b>';
    $coa[] = array('acno' => '//4999', 'acnoname' => $incomelabel, 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b - $amt2b + $amt3b, 'isshow' => 1);

    $array = json_decode(json_encode($coa), true); // for clearing set to array
    return $array;
  }

  public function aftech_default_query($filters)
  {
    $company = $filters['params']['companyid'];
    if ($filters['params']['dataparams']['branchcode'] == "") {
      $center = "";
    } else {
      $center = $filters['params']['dataparams']['branch'];
    }

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $costcenter = $filters['params']['dataparams']['code'];
    $cc = '';
    $filter = '';

    if ($costcenter != "") {
      $filter = $costcenter;
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as amt,0 as detail,0 as total";

    $coa = $this->coreFunctions->opentable($query2);
    $amt1 = 0;
    $amt1b = 0;
    $amt2 = 0;
    // $amt3 = 0;
    $amt2b = 0;
    // $amt3b = 0;
    $a = 0;

    $prev_incomegrp2 = '';
    $prev_incomegrp3 = '';

    $amtgrp = 0;
    $this->AFTI_PLANTTREE($coa, '\\\\', 'R', $amt1, $amt1b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "", $prev_incomegrp3);
    $this->AFTI_PLANTTREE($coa, '\\\\', 'E', $amt2, $amt2b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, [], 0, $amtgrp, $prev_incomegrp2, "", $amt1b);
    $array = json_decode(json_encode($coa), true); // for clearing set to array
    return $array;
  }

  public function CDO_query($filters)
  {
    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['costcenterid'];
    $costcentercode = $filters['params']['dataparams']['code'];
    $cc = '';
    $filter = '';

    if ($costcentercode != "") {
      $filter = $costcenter;
    } else {
      $filter = 0;
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as amt,0 as detail,0 as total";

    //initialized values
    $coa = $this->coreFunctions->opentable($query2);
    $amt1 = 0;
    $amt1b = 0;
    $amt2 = 0;
    $amt2b = 0;
    $amt3 = 0;
    $amt3b = 0;
    $amt4 = 0;
    $amt4b = 0;
    $amtgrp = 0;
    $a = 0;
    $adminshare = 0;
    $amt1b_less2b = 0;
    $amt1b_less2b_less3b = 0;
    $amt1b_less2b_less3b_less4b = 0;
    $amt1234_lessadmin = 0;

    //arrays for excluding/including
    $acno_R = ['', '\\40602', '\\40603', '\\40604', '\\40605', '\\40606', '\\40607', '\\40609', '\\409', '\\410', '\\412', '\\413', '\\414', '\\415', '\\416', '\\417', '\\418', '\\419','\\420'];
    $acno_E = ['', '\\501', '\\502', '\\503', '\\504'];
    $inex_clude_acno = $acno_R;
    //aaa
    //array value building for layout
    $this->CDO_PLANTTREE($coa, '\\\\4', 'R', $amt1, $amt1b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, ["complex_acno" => $inex_clude_acno, "inex_clude" => 0], 0, $amtgrp, $prev_incomegrp2, "");

    $brgross = $amt1b;
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>GROSS SALES</b>', 'levelid' => 1, 'cat' => 'E', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b);

    $this->CDO_GET_BRANCH($coa, '\\\\502', 'E', $amt2, $amt2b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, ["defaultfield_filter" => "acno"]);
    $amt1b_less2b = $amt1b - $amt2b;
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>GROSS MARGIN</b> ', 'levelid' => 1, 'cat' => 'E', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b_less2b);
    $inex_clude_acno = $acno_E;
    $this->CDO_PLANTTREE($coa, '\\\\', 'E', $amt3, $amt3b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, ["complex_acno" => $inex_clude_acno, "inex_clude" => 0], 0, $amtgrp, $prev_incomegrp2, "TOTAL OPERATING EXPENSE");
    
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>TOTAL OPERATING EXPENSE</b> ', 'levelid' => 1, 'cat' => 'R', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt3b);
    $amt1b_less2b_less3b = $amt1b_less2b - $amt3b;

    $coa[] = array('acno' => '//666', 'acnoname' => '<b>Net Income/Loss from Operation</b> ', 'levelid' => 1, 'cat' => 'E', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b_less2b_less3b);
    $inex_clude_acno = $acno_R;
    foreach ($inex_clude_acno as $key => $value) {
      if ($key != 0) {
        $this->CDO_GET_BRANCH($coa, '\\'.$value, 'R', $amt4, $amt4b, $a, $start, $end, $center, $isposted, $cc, $filter, $company, ["defaultfield_filter" => "acno"]);
      }
    }
    $amt1b_less2b_less3b_less4b = $amt1b_less2b_less3b + $amt4b;
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>Net Profit/Loss before Admin Expenses</b> ', 'levelid' => 1, 'cat' => 'E', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1b_less2b_less3b_less4b);

    $adminshare = $this->cdogetadminshare($filters, $brgross);
    $coa[] = array('acno' => '//666', 'acnoname' => 'Less: Administrative Expense Share', 'levelid' => 2, 'cat' => 'E', 'parent' => 'X', 'amt' => $adminshare, 'detail' => 0, 'total' => $adminshare);

    $amt1234_lessadmin = $amt1b_less2b_less3b_less4b - $adminshare;
    $coa[] = array('acno' => '//666', 'acnoname' => '<b>Net Profit/Loss</b> ', 'levelid' => 1, 'cat' => 'E', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt1234_lessadmin);


    $array = json_decode(json_encode($coa), true); // for clearing set to array
    return $array;
  }
  //END

  private function cdogetadminshare($filters, $brgross)
  {
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));

    $tgross = 0;
    $tadminopex = 0;

    if ($isposted == 1) {
      $tgross = $this->coreFunctions->datareader("select sum(cr-db) as value from ladetail as detail left join lahead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.incomegrp in ('SALES') and date(head.dateid) between '$start' and '$end'");

      $tadminopex = $this->coreFunctions->datareader("select sum(db-cr) as value from ladetail as detail left join lahead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid left join cntnum on cntnum.trno = head.trno where cntnum.center ='001' and coa.cat ='E' and coa.incomegrp in ('expenses') and date(head.dateid) between '$start' and '$end'");
    }

    if ($isposted == 0) {
      $tgross = $this->coreFunctions->datareader("select sum(cr-db) as value from gldetail as detail left join glhead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.incomegrp in ('SALES') and date(head.dateid) between '$start' and '$end'");

      $tadminopex = $this->coreFunctions->datareader("select sum(db-cr) as value from gldetail as detail left join glhead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid left join cntnum on cntnum.trno = head.trno where cntnum.center ='001' and coa.cat ='E' and coa.incomegrp in ('expenses') and date(head.dateid) between '$start' and '$end'");
    }

    if ($isposted == 2) {
      $this->coreFunctions->LogConsole('here');
      $tgross = $this->coreFunctions->datareader("select sum(amt) as value from ( select sum(cr-db) as amt from ladetail as detail left join lahead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.incomegrp in ('SALES') and date(head.dateid) between '$start' and '$end'
      union all
      select sum(cr-db) as amt from gldetail as detail left join glhead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.incomegrp in ('SALES') and date(head.dateid) between '$start' and '$end') as a");

      $tadminopex = $this->coreFunctions->datareader("select sum(amt) as value from (select sum(db-cr) as amt from ladetail as detail left join lahead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid left join cntnum on cntnum.trno = head.trno where cntnum.center ='001' and coa.cat ='E' and coa.incomegrp in ('expenses') and date(head.dateid) between '$start' and '$end'
      union all
      select sum(db-cr) as amt from gldetail as detail left join glhead as head on head.trno = detail.trno 
      left join coa on coa.acnoid = detail.acnoid left join cntnum on cntnum.trno = head.trno where cntnum.center ='001' and coa.cat ='E' and coa.incomegrp in ('expenses') and date(head.dateid) between '$start' and '$end') as a");
    }

    $branchrev = $brgross;
    if ($tgross <> 0) {
      $share = $branchrev / $tgross;
    } else {
      $share = 0;
    }

    $adminshare = $tadminopex * $share;
    $this->coreFunctions->LogConsole('branch:' . $tgross . '-' . $brgross);
    $this->coreFunctions->LogConsole('adminshare:' . $adminshare);
    $this->coreFunctions->LogConsole('opex:' . $tadminopex);
    return $adminshare;
  }

  //PLANT TREE START
  private function DEFAULT_PLANTTREE(&$a, $acno, $cat, &$amt1, &$amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, &$amtgrp, &$prev_incomegrp2, $label)
  {
    $z = $z + 1;
    $amt = 0;
    $amt2 = 0;

    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company);
    $result2 = $this->coreFunctions->opentable($query2);

    $prev_incomegrp = $prev_incomegrp2;
    $prev_incomegrp_amt = $amtgrp;

    $counter = 1;

    foreach ($result2 as $key => $value) {

      if ($chkgrp) {
        plotgrouptotalhere:
        if ($value->incomegrp != "") {
          if ($value->incomegrp != $prev_incomegrp && $prev_incomegrp != "") {

            $labelsubgroup = $prev_incomegrp;
            $arrgrp = explode(".", $prev_incomegrp);
            if (count($arrgrp) > 1) {
              $labelsubgroup =   $arrgrp[1];
            }

            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) . '</b>',
              'levelid' => $value->levelid, 'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
            );
            $prev_incomegrp_amt = 0;
          }
        }
      }

      $a[] = array(
        'acno' => $value->acno, 'acnoname' => $value->levelid == 1 ? '<b>' . $value->acnoname . '</b>' : $value->acnoname, 'levelid' => $value->levelid,
        'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
        'detail' => $value->detail, 'total' => $value->amt, 'alias' => '', 'isshow' => $value->isshow
      );

      $prev_incomegrp_amt += $value->amt;

      $prevamt9 = $amt9;
      $amt = $amt + $value->amt;
      $amt1 = $amt1 + $amt;
      $amt9 = $amt9 + $value->amt;
      $amt = 0;

      if ($value->detail == 0) {
        if ($this->DEFAULT_PLANTTREE($a, '\\' . $value->acno, $value->cat, $amt, $amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, $prev_incomegrp_amt, $value->incomegrp, $label)) {
          if ($value->levelid > 1) {
            if ($value->levelid == 2) {
              $level2amt = $amt9 - $prevamt9;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => 0, 'detail' => $value->detail, 'total' => $level2amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2 , 'total' => $level2amt
              );

              $prev_incomegrp_amt += $level2amt;
            } else {
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => 0, 'detail' => $value->detail, 'total' => $amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2, 'total' => $amt
              );
              $prev_incomegrp_amt += $amt;
            } //end if
          } else {
            if ($cat == 'C') {
              $loss = 0;
              $C = "('R','G')";
              $loss = $this->INCOME_STATEMENT_GET_SUM('CREDIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $C = "('E','O')";
              $loss = $loss - $this->INCOME_STATEMENT_GET_SUM('DEBIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $amt9 = $amt9 + $loss;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET',
                'levelid' => $value->levelid + 1, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => $loss, 'detail' => $value->detail, 'total' => $loss, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
            } //end if

            //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
            $parentname = 'NET ' . $value->acnoname;
            $totalamt9 = $amt9;
            if ($label != "") {
              $parentname = $label;
              $totalamt9 = $amt9 - $prevamt9;
            }
            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => '<b>' . strtoupper($parentname) . '</b>',
              'levelid' => $value->levelid, 'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $amt2, 'detail' => $value->detail, 'total' => $totalamt9, 'alias' => $value->alias, 'isshow' => $value->isshow
            );
          } //end if IF LEVELID = 1

        }
      }


      if ($chkgrp) {
        $prev_incomegrp = $value->incomegrp;

        if ($counter >= count($result2)) {
          if ($prev_incomegrp != "") {
            if ($value->levelid <= 2) {

              $labelsubgroup = $prev_incomegrp;
              $arrgrp = explode(".", $prev_incomegrp);
              if (count($arrgrp) > 1) {
                $labelsubgroup =   $arrgrp[1];
              }

              $a[] = array(
                'acno' => $value->acno,
                'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) .  '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
              $prev_incomegrp_amt = 0;
            }
          }
        }
      }

      $counter += 1;
    }
    exithere:
    if (count((array)$result2) > 0) { // cast to array result2 is an object count function not work on object
      return true;
    } else {
      return false;
    }
  } //Plantree

  private function MMM_DEFAULT_PLANTTREE(&$a, $acno, $cat, &$amt1, &$amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, &$amtgrp, &$prev_incomegrp2, $label)
  {
    $z = $z + 1;
    $amt = 0;
    $amt2 = 0;

    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company);
    $this->coreFunctions->LogConsole($query2);
    $result2 = $this->coreFunctions->opentable($query2);

    $prev_incomegrp = $prev_incomegrp2;
    $prev_incomegrp_amt = $amtgrp;

    $counter = 1;

    foreach ($result2 as $key => $value) {

      if ($chkgrp) {
        plotgrouptotalhere:
        if ($value->incomegrp != "") {
          if ($value->incomegrp != $prev_incomegrp && $prev_incomegrp != "") {

            $labelsubgroup = $prev_incomegrp;
            $arrgrp = explode(".", $prev_incomegrp);
            if (count($arrgrp) > 1) {
              $labelsubgroup =   $arrgrp[1];
            }

            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) . '</b>',
              'levelid' => $value->levelid, 'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
            );
            $prev_incomegrp_amt = 0;
          }
        }
      }


      $a[] = array(
        'acno' => $value->acno, 'acnoname' => $value->levelid == 1 ? '<b>' . $value->acnoname . '</b>' : $value->acnoname, 'levelid' => $value->levelid,
        'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
        'detail' => $value->detail, 'total' => $value->amt, 'alias' => '', 'isshow' => $value->isshow
      );


      $prev_incomegrp_amt += $value->amt;

      $prevamt9 = $amt9;
      $amt = $amt + $value->amt;
      $amt1 = $amt1 + $amt;
      $amt9 = $amt9 + $value->amt;
      $amt = 0;

      if ($value->detail == 0) {
        if ($this->MMM_DEFAULT_PLANTTREE($a, '\\' . $value->acno, $value->cat, $amt, $amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, $prev_incomegrp_amt, $value->incomegrp, $label)) {
          if ($value->levelid > 1) {
            if ($value->levelid == 2) {
              $level2amt = $amt9 - $prevamt9;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => 0, 'detail' => $value->detail, 'total' => $level2amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2 , 'total' => $level2amt
              );

              $prev_incomegrp_amt += $level2amt;
            } else {
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => 0, 'detail' => $value->detail, 'total' => $amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2, 'total' => $amt
              );
              $prev_incomegrp_amt += $amt;
            } //end if
          } else {
            if ($cat == 'C') {
              $loss = 0;
              $C = "('R','G')";
              $loss = $this->INCOME_STATEMENT_GET_SUM('CREDIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $C = "('E','O')";
              $loss = $loss - $this->INCOME_STATEMENT_GET_SUM('DEBIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $amt9 = $amt9 + $loss;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET',
                'levelid' => $value->levelid + 1, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => $loss, 'detail' => $value->detail, 'total' => $loss, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
            } //end if

            //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
            $parentname = 'NET ' . $value->acnoname;
            if (strtoupper($value->acnoname)  == 'REVENUE') {
              $parentname = 'NET SALES';
            }

            $totalamt9 = $amt9;
            if ($label != "") {
              $parentname = $label;
              $totalamt9 = $amt9 - $prevamt9;
            }
            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => '<b>' . strtoupper($parentname) . '</b>',
              'levelid' => $value->levelid, 'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $amt2, 'detail' => $value->detail, 'total' => $totalamt9, 'alias' => $value->alias, 'isshow' => $value->isshow
            );
          } //end if IF LEVELID = 1

        }
      }


      if ($chkgrp) {
        $prev_incomegrp = $value->incomegrp;

        if ($counter >= count($result2)) {
          if ($prev_incomegrp != "") {
            if ($value->levelid <= 2) {

              $labelsubgroup = $prev_incomegrp;
              $arrgrp = explode(".", $prev_incomegrp);
              if (count($arrgrp) > 1) {
                $labelsubgroup =   $arrgrp[1];
              }

              $a[] = array(
                'acno' => $value->acno,
                'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) .  '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
              $prev_incomegrp_amt = 0;
            }
          }
        }
      }

      $counter += 1;
    }
    exithere:
    if (count((array)$result2) > 0) { // cast to array result2 is an object count function not work on object
      return true;
    } else {
      return false;
    }
  } //Plantree

  private function AFTI_PLANTTREE(&$a, $acno, $cat, &$amt1, &$amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, &$amtgrp, &$prev_incomegrp2, $label, &$prev_incomegrp3)
  {
    $z = $z + 1;
    $amt = 0;
    $amt2 = 0;

    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company);
    $result2 = $this->coreFunctions->opentable($query2);

    $prev_incomegrp = $prev_incomegrp2;

    $prev_incomegrp_amt = $amtgrp;

    $counter = 1;

    foreach ($result2 as $key => $value) {

      if ($chkgrp) {
        plotgrouptotalhere:
        if ($value->incomegrp != "") {
          if ($value->incomegrp != $prev_incomegrp && $prev_incomegrp != "") {

            $labelsubgroup = $prev_incomegrp;
            $arrgrp = explode(".", $prev_incomegrp);
            if (count($arrgrp) > 1) {
              $labelsubgroup =   $arrgrp[1];
            }

            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) . '</b>',
              'levelid' => $value->levelid, 'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
            );
            $prev_incomegrp_amt = 0;
          }
        }
      }

      $a[] = array(
        'acno' => $value->acno, 'acnoname' => $value->levelid == 1 ? '<b>' . $value->acnoname . '</b>' : $value->acnoname, 'levelid' => $value->levelid,
        'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
        'detail' => $value->detail, 'total' => $value->amt, 'alias' => '', 'isshow' => $value->isshow
      );

      $prev_incomegrp_amt += $value->amt;

      $prevamt9 = $amt9;
      $amt = $amt + $value->amt;
      $amt1 = $amt1 + $amt;
      $amt9 = $amt9 + $value->amt;
      $amt = 0;
      // $trev = 0;

      if ($value->detail == 0) {
        if ($this->AFTI_PLANTTREE($a, '\\' . $value->acno, $value->cat, $amt, $amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, $prev_incomegrp_amt, $value->incomegrp, $label, $prev_incomegrp3)) {
          if ($value->levelid > 1) {
            if ($value->levelid == 2) {
              $level2amt = $amt9 - $prevamt9;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              if ($level2amt != 0) {
                $a[] = array(
                  'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                  'levelid' => $value->levelid - 1, 'cat' => $value->cat, 'parent' => $value->parent,
                  'amt' => 0, 'detail' => $value->detail, 'total' => $level2amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2 , 'total' => $level2amt
                );




                if ($value->acno  == '\501') {

                  $gpsx = $prev_incomegrp3 - $level2amt;

                  $a[] = array(
                    'acno' => $value->acno,
                    'acnoname' => '<b></b>',
                    'levelid' => $value->levelid, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => '-', 'alias' => $value->alias, 'isshow' => $value->isshow
                  );

                  $a[] = array(
                    'acno' => '\5',
                    'acnoname' => '<b>GROSS PROFIT</b>',
                    'levelid' => $value->levelid - 1, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => $gpsx, 'alias' => $value->alias, 'isshow' => $value->isshow
                  );

                  $a[] = array(
                    'acno' => $value->acno,
                    'acnoname' => '<b></b>',
                    'levelid' => $value->levelid, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => '-', 'alias' => $value->alias, 'isshow' => $value->isshow
                  );
                }

                if ($value->acno  == '\502') {
                  $netloss = $gpsx - $level2amt;
                  $a[] = array(
                    'acno' => $value->acno,
                    'acnoname' => '<b></b>',
                    'levelid' => $value->levelid, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => '-', 'alias' => $value->alias, 'isshow' => $value->isshow
                  );

                  $a[] = array(
                    'acno' => $value->acno,
                    'acnoname' => '<b>NET INCOME/LOSS BEFORE OTHER GAINS AND LOSSES</b>',
                    'levelid' => $value->levelid - 1, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => $netloss, 'alias' => $value->alias, 'isshow' => $value->isshow
                  );

                  $a[] = array(
                    'acno' => $value->acno,
                    'acnoname' => '<b></b>',
                    'levelid' => $value->levelid, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => '-', 'alias' => $value->alias, 'isshow' => $value->isshow
                  );
                }

                if ($value->acno  == '\503') {

                  $a[] = array(
                    'acno' => $value->acno,
                    'acnoname' => '<b></b>',
                    'levelid' => $value->levelid, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => '-', 'alias' => $value->alias, 'isshow' => $value->isshow
                  );

                  $a[] = array(
                    'acno' => $value->acno,
                    'acnoname' => '<b>NET INCOME/LOSS AFTER OTHER GAINS & LOSSES & BEFORE TAX</b>',
                    'levelid' => $value->levelid - 1, 'cat' => $value->cat,
                    'parent' => $value->parent,
                    'amt' => $amt2, 'detail' => $value->detail, 'total' => $netloss + $level2amt, 'alias' => $value->alias, 'isshow' => $value->isshow
                  );
                }
              }

              $prev_incomegrp_amt += $level2amt;
            } else {
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => 0, 'detail' => $value->detail, 'total' => $amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2, 'total' => $amt
              );
              $prev_incomegrp_amt += $amt;
            } //end if
          } else {
            if ($cat == 'C') {
              $loss = 0;
              $C = "('R','G')";
              $loss = $this->INCOME_STATEMENT_GET_SUM('CREDIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $C = "('E','O')";
              $loss = $loss - $this->INCOME_STATEMENT_GET_SUM('DEBIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $amt9 = $amt9 + $loss;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET',
                'levelid' => $value->levelid + 1, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => $loss, 'detail' => $value->detail, 'total' => $loss, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
            } //end if

            //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
            $parentname = ' ';
            // $totalamt9 = $amt9;
            if ($label != "") {
              $parentname = $label;
              // $totalamt9 = $amt9 - $prevamt9;
            }
            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => '<b>' . strtoupper($parentname) . '</b>',
              'levelid' => $value->levelid, 'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $amt2, 'detail' => $value->detail, 'total' => '-', 'alias' => $value->alias, 'isshow' => $value->isshow
            );
          } //end if IF LEVELID = 1

        }
      }

      if ($chkgrp) {
        $prev_incomegrp = $value->incomegrp;

        if ($counter >= count($result2)) {
          if ($prev_incomegrp != "") {
            if ($value->levelid <= 2) {

              $labelsubgroup = $prev_incomegrp;
              $arrgrp = explode(".", $prev_incomegrp);
              if (count($arrgrp) > 1) {
                $labelsubgroup =   $arrgrp[1];
              }

              $a[] = array(
                'acno' => $value->acno,
                'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) .  '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
              $prev_incomegrp_amt = 0;
            }
          }
        }
      }

      $counter += 1;
    }
    exithere:
    if (count((array)$result2) > 0) { // cast to array result2 is an object count function not work on object
      return true;
    } else {
      return false;
    }
  } //Plantree

  private function HGC_PLANTTREE(&$a, $acno, $cat, &$amt1, &$amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, &$amtgrp, &$prev_incomegrp2, $label)
  {
    $z = $z + 1;
    $amt = 0;
    $amt2 = 0;

    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company);
    $result2 = $this->coreFunctions->opentable($query2);

    $prev_incomegrp = $prev_incomegrp2;
    $prev_incomegrp_amt = $amtgrp;

    $counter = 1;

    foreach ($result2 as $key => $value) {

      if ($chkgrp) {
        plotgrouptotalhere:
        if ($value->incomegrp != "") {
          if ($value->incomegrp != $prev_incomegrp && $prev_incomegrp != "") {

            $labelsubgroup = $prev_incomegrp;
            $arrgrp = explode(".", $prev_incomegrp);
            if (count($arrgrp) > 1) {
              $labelsubgroup =   $arrgrp[1];
            }

            $a[] = array(
              'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) . '</b>', 'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
              'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow, 'istotal' => true
            );
            $prev_incomegrp_amt = 0;
          }
        }
      }

      $parentaccountamt = 0;

      $displayamt = $value->amt;
      $displaytotal = $value->amt;

      if ($value->detail == 0) {
        if ($value->iscompute) {
          $parentaccountamt = $this->getParentComputeAmt($cat, $value->acno, $date1, $date2, $center, $status, $cc, $filter, $company);
          $displayamt = $parentaccountamt;
          $displaytotal = $parentaccountamt;
        }
      }

      $a[] = array(
        'acno' => $value->acno, 'acnoname' => $value->levelid == 1 ? '<b>' . $value->acnoname . '</b>' : $value->acnoname, 'levelid' => $value->levelid,
        'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $displayamt,
        'detail' => $value->detail, 'total' => $displaytotal, 'alias' => '', 'isshow' => $value->isshow
      );

      $prev_incomegrp_amt += $value->amt;

      $prevamt9 = $amt9;
      $amt = $amt + $value->amt;
      $amt1 = $amt1 + $amt;
      $amt9 = $amt9 + $value->amt;
      $amt = 0;

      if ($value->detail == 0) {
        if ($this->HGC_PLANTTREE($a, '\\' . $value->acno, $value->cat, $amt, $amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, $prev_incomegrp_amt, $value->incomegrp, $label)) {
          if ($value->levelid > 1) {
            if ($value->levelid == 2) {
              $level2amt = $amt9 - $prevamt9;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              if (!$value->iscompute) {
                $a[] = array(
                  'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>', 'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                  'amt' => $level2amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow, 'isparenttotal' => $value->isparenttotal, 'istotal' => true
                );
              }

              $prev_incomegrp_amt += $level2amt;
            } else {
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              if (!$value->iscompute) {
                $a[] = array(
                  'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>', 'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                  'amt' => $amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow, 'isparenttotal' => $value->isparenttotal, 'istotal' => true
                );
              }
              $prev_incomegrp_amt += $amt;
            } //end if
          } else {
            if ($cat == 'C') {
              $loss = 0;
              $C = "('R','G')";
              $loss = $this->INCOME_STATEMENT_GET_SUM('CREDIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $C = "('E','O')";
              $loss = $loss - $this->INCOME_STATEMENT_GET_SUM('DEBIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $amt9 = $amt9 + $loss;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET', 'levelid' => $value->levelid + 1, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => $loss, 'detail' => $value->detail, 'total' => $loss, 'alias' => $value->alias, 'isshow' => $value->isshow, 'isparenttotal' => $value->isparenttotal, 'istotal' => true
              );
            } //end if

            //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
            $parentname = 'NET ' . $value->acnoname;
            $totalamt9 = $amt9;
            if ($label != "") {
              $parentname = $label;
              $totalamt9 = $amt9 - $prevamt9;
            }
            $a[] = array(
              'acno' => $value->acno, 'acnoname' => '<b>' . strtoupper($parentname) . '</b>', 'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
              'amt' => $amt2, 'detail' => $value->detail, 'total' => $totalamt9, 'alias' => $value->alias, 'isshow' => $value->isshow, 'isparenttotal' => $value->isparenttotal, 'istotal' => true
            );
          } //end if IF LEVELID = 1

        }
      }


      if ($chkgrp) {
        $prev_incomegrp = $value->incomegrp;

        if ($counter >= count($result2)) {
          if ($prev_incomegrp != "") {
            if ($value->levelid <= 2) {

              $labelsubgroup = $prev_incomegrp;
              $arrgrp = explode(".", $prev_incomegrp);
              if (count($arrgrp) > 1) {
                $labelsubgroup =   $arrgrp[1];
              }

              $a[] = array(
                'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) .  '</b>', 'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow, 'istotal' => true
              );
              $prev_incomegrp_amt = 0;
            }
          }
        }
      }

      $counter += 1;
    }
    exithere:
    if (count((array)$result2) > 0) { // cast to array result2 is an object count function not work on object
      return true;
    } else {
      return false;
    }
  } //Plantree

  //for groups or multiple accounts
  private function CDO_PLANTTREE(&$a, $acno, $cat, &$amt1, &$amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, &$amtgrp, &$prev_incomegrp2, $label)
  {
    $z = $z + 1;
    $amt = 0;
    // $amt2 = 0;
    $prevamt9 = 0;

    $defaultfield_filter = '';
    if (isset($addtionalparams['defaultfield_filter'])) {
      $defaultfield_filter = $addtionalparams['defaultfield_filter'];
    }
//bbb
    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company, $defaultfield_filter);
    $result2 = $this->coreFunctions->opentable($query2);

    $prev_incomegrp = $prev_incomegrp2;
    $prev_incomegrp_amt = $amtgrp;

    $counter = 1;

    foreach ($result2 as $key => $value) {

      if ($chkgrp) {
        plotgrouptotalhere:
        if ($value->incomegrp != "") {
          if ($value->incomegrp != $prev_incomegrp && $prev_incomegrp != "") {

            $labelsubgroup = $prev_incomegrp;
            $arrgrp = explode(".", $prev_incomegrp);
            if (count($arrgrp) > 1) {
              $labelsubgroup =   $arrgrp[1];
            }

            $a[] = array(
              'acno' => 'aaa ' . $value->acno,
              'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup). '</b>',
              'levelid' => $value->levelid, 'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
            );
            $prev_incomegrp_amt = 0;
          }
        }
      }

      //A LCR E
      //this is what loops lowest level acno and value
      if (isset($addtionalparams['complex_acno'])) { //has complex back and forth display of a single category
        if ($addtionalparams['inex_clude'] == 0) { //0 to exclude, 1 to include

          if (array_search($value->acno, $addtionalparams['complex_acno']) == false) { //if false, returns all revenue except the ones in array
            $a[] = array(
              'acno' => 'bbb ' . $value->acno,
              'acnoname' => $value->levelid == 1 ? '<b>' . ($value->acnoname == 'EXPENSES' ? 'LESS OPERATING EXPENSES: ' : $value->acnoname) . '</b>' : $value->acnoname,
              'levelid' => $value->levelid,
              'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
              'detail' => $value->detail, 'total' => $value->amt, 'alias' => '', 'isshow' => $value->isshow
            );

            $prev_incomegrp_amt += $value->amt;

            $prevamt9 = $amt9;
            $amt = $amt + $value->amt;
            $amt1 = $amt1 + $amt;
            $amt9 = $amt9 + $value->amt;
            $amt = 0; 
          }
        } else {
          if (array_search($value->acno, $addtionalparams['complex_acno']) != false) { //if not false, returns all revenue inside the array only
            $a[] = array(
              'acno' => 'ccc ' . $value->acno, 'acnoname' => $value->levelid == 1 ? '<b>' . $value->acnoname . '</b>' : $value->acnoname, 'levelid' => $value->levelid,
              'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
              'detail' => $value->detail, 'total' => $value->amt, 'alias' => '', 'isshow' => $value->isshow
            );

            $prev_incomegrp_amt += $value->amt;

            $prevamt9 = $amt9;
            $amt = $amt + $value->amt;
            $amt1 = $amt1 + $amt;
            $amt9 = $amt9 + $value->amt;
            $amt = 0;
          }
        }
      } else { //display/loops as normal
        $a[] = array(
          'acno' => 'ddd ' . $value->acno,
          'acnoname' => $value->levelid == 1 ? '<b>' . ($value->acnoname == 'Expenses' ? 'LESS OPERATING EXPENSES: ' : $value->acnoname) . '</b>' : $value->acnoname,
          'levelid' => $value->levelid,
          'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
          'detail' => $value->detail, 'total' => $value->amt, 'alias' => '', 'isshow' => $value->isshow
        );

        $prev_incomegrp_amt += $value->amt;

        $prevamt9 = $amt9;
        $amt = $amt + $value->amt;
        $amt1 = $amt1 + $amt;
        $amt9 = $amt9 + $value->amt;
        $amt = 0;
      }


      if ($value->detail == 0) {
        if ($this->CDO_PLANTTREE($a, '\\' . $value->acno, $value->cat, $amt, $amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams, $chkgrp, $prev_incomegrp_amt, $value->incomegrp, $label)) {
          if ($value->levelid > 1) {
            if ($value->levelid == 2) { //for titles
              $level2amt = $amt9 - $prevamt9;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
   

              if (array_search($value->acno, $addtionalparams['complex_acno']) == false) { //if false, returns all revenue except the ones in array
                $a[] = array(
                'acno' => 'eee ' . $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname.'</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => 0, 'detail' => $value->detail, 'total' => $level2amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2 , 'total' => $level2amt
              );
                $prev_incomegrp_amt += $level2amt;
              }
              
              
            } else { //levelid 3 or more is detail or the lowest(with value)
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => 'fff ' . $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => 0, 'detail' => $value->detail, 'total' => $amt, 'alias' => $value->alias, 'isshow' => $value->isshow // original 'amt' => $amt2, 'total' => $amt
              );
              $prev_incomegrp_amt += $amt;
            } //end if
          } else { //if levelid less than 1 or equal to 1// this is what totals for A LCR E
            if ($cat == 'C') {
              $loss = 0;
              $C = "('R','G')";
              $loss = $this->INCOME_STATEMENT_GET_SUM('CREDIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $C = "('E','O')";
              $loss = $loss - $this->INCOME_STATEMENT_GET_SUM('DEBIT', $C, $date1, $date2, $center, $status, $company, $filter);
              $amt9 = $amt9 + $loss;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => 'ggg ' . '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET',
                'levelid' => $value->levelid + 1, 'cat' => $value->cat, 'parent' => $value->parent,
                'amt' => $loss, 'detail' => $value->detail, 'total' => $loss, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
            } //end if
          } //end if IF LEVELID = 1

        }
      }


      if ($chkgrp) {
        $prev_incomegrp = $value->incomegrp;

        if ($counter >= count($result2)) {
          if ($prev_incomegrp != "") {
            if ($value->levelid <= 2) {

              $labelsubgroup = $prev_incomegrp;
              $arrgrp = explode(".", $prev_incomegrp);
              if (count($arrgrp) > 1) {
                $labelsubgroup =   $arrgrp[1];
              }

              $a[] = array(
                'acno' => 'hhh ' . $value->acno,
                'acnoname' => '<b>TOTAL ' . strtoupper($labelsubgroup) .  '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => $prev_incomegrp_amt, 'detail' => $value->detail, 'total' => 0, 'alias' => $value->alias, 'isshow' => $value->isshow
              );
              $prev_incomegrp_amt = 0;
            }
          }
        }
      }

      $counter += 1;
    }
    exithere:
    if (count((array)$result2) > 0) { // cast to array result2 is an object count function not work on object
      return true;
    } else {
      return false;
    }
  } //CDO_Plantree

  //for specific account
  private function CDO_GET_BRANCH(&$a, $acno, $cat, &$amt1, &$amt9, $z, $date1, $date2, $center, $status, $cc, $filter, $company, $addtionalparams)
  {
    $z = $z + 1;
    $amt = 0;
    // $amt2 = 0;
    // $prevamt9 = 0;

    $defaultfield_filter = '';
    if (isset($addtionalparams['defaultfield_filter'])) {
      $defaultfield_filter = $addtionalparams['defaultfield_filter'];
    }

    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company, $defaultfield_filter);
    $result2 = $this->coreFunctions->opentable($query2);

    foreach ($result2 as $key => $value) {

      $a[] = array(
        'acno' => $value->acno,

        'acnoname' => ($value->acnoname == 'COST OF SALE' ? 'LESS : COST OF SALES ' : ($value->acnoname == 'INTEREST INCOME' ? 'ADD: ' . $value->acnoname : $value->acnoname)),
        'levelid' => $value->levelid,
        'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
        'detail' => $value->detail, 'total' => $value->amt, 'alias' => '', 'isshow' => $value->isshow
      );

      // $prevamt9 = $amt9;
      $amt = $amt + $value->amt;
      $amt1 = $amt1 + $amt;
      $amt9 = $amt9 + $value->amt;
      $amt = 0;
    }
  } //CDO_Plantree
  //END

  //LAYOUT START
  private function AFTECH_DEFAULT_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $border = '1px solid';
    $fontsize = '10';

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('', $filters['params']);
    switch ($companyid) {
      case 10: //afti
      case 11: //afti usd
        $font = 'cambria';
        break;
      default:
        $font = $this->companysetup->getrptfont($filters['params']);
        break;
    }

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['branchcode'];
    $costcenter = $filters['params']['dataparams']['code'];
    $forex = $filters['params']['dataparams']['forex'];

    if ($center == "") {
      $center = "ALL";
    }

    // $count = 58;
    // $page = 48;
    $str = '';

    // need yung date galing dataparams, hindi yung nka format na sa date()
    $date1 = preg_replace('/[\W\s\/]+/', '-', $filters['params']['dataparams']['dateid']);
    $date2 = preg_replace('/[\W\s\/]+/', '-', $filters['params']['dataparams']['due']);
    if (!$this->othersClass->checkreportdate($date1, 'Y-m-d')) return $this->othersClass->invaliddatereport();
    if (!$this->othersClass->checkreportdate($date2, 'Y-m-d')) return $this->othersClass->invaliddatereport();

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->AFTECH_DEFAULT_HEADER($filters, $center);
    $str .= $this->reporter->begintable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '310', null, false, '1px solid ', 'LTBR', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('in PHP', '80', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '4px;');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', 'RL', 'L', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('in SGD', '80', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '4px;');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TBR', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TBR', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->endrow();

    $r = 0;
    $r2 = 0;
    $rs = 0;
    $rs2 = 0;

    $e = 0;
    // $e2 = 0;
    $es = 0;
    // $es2 = 0;

    $fi = 0;
    $fi2 = 0;
    $fie = 0;
    $fie2 = 0;

    $data2 = [];
    $i = 1;

    foreach ($data as $key => $value) {
      if ($value['acno'] == '\401') {
        $r = $value['total'];
      }

      if ($value['acno'] == '\402') {
        $r2 = $value['total'];
      }

      if ($value['acnoname'] == '<b>TOTAL COST OF SALES</b>') {
        $e = $value['total'];
      }

      if ($value['acno'] == '\50102') {
        $e2 = $value['total'];
      }

      if ($value['acnoname'] == '<b>TOTAL OPERATING EXPENSES</b>') {
        $fi = $value['total'];
      }

      if ($value['acnoname'] == '<b>TOTAL OTHER GAINS AND LOSSES - NET</b>') {
        $fi2 = $value['total'];
      }

      if (str_contains($value['acnoname'], '<b>TOTAL ') && $value['total'] != 0) {
        $data2[$i]['acnoname'] = $value['acnoname'];
        $data2[$i]['total'] = $value['total'];
        $data2[$i]['parent'] = $value['parent'];
        $data2[$i]['acno'] = $value['acno'];

        $i++;
      }
    }

    $rs = $r / $forex;
    $rs2 = $r2 / $forex;
    $es = $e / $forex;
    // $es2 = $e2 / $forex;

    $fie = $fi / $forex;
    $fie2 = $fi2 / $forex;
    for ($i = 0; $i < count($data); $i++) {

      $indent = '5' * ($data[$i]['levelid'] * 3);
      $str .= $this->reporter->addline();

      if ($data[$i]['amt'] == 0) {
        $amt = '';
        $sgd = '';
      } else {
        $amt = $data[$i]['amt'];
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $sgd = $data[$i]['amt'] / $forex;
            break;
          default:
            $sgd = $data[$i]['amt'] * $forex;
            break;
        }
      }

      if ($data[$i]['total'] == 0) {
        $total = '';
        $sgdtotal = '';
      } else {
        if ($amt == 0) {
          $total = $data[$i]['total'];
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $sgdtotal = $data[$i]['total'] / $forex;
              break;
            default:
              $sgdtotal = $data[$i]['total'] * $forex;
              break;
          }
        } else {
          $total = '';
          $sgdtotal = '';
        }
      }

      //start test

      $totgrp = 0;

      switch ($data[$i]['detail']) {
        case '1':

          if ($amt != 0) {
            $pc = $this->coreFunctions->getfieldvalue("coa", "acno", "parent=? and detail =0", [$data[$i]['acno']]);
            if ($pc != 0) {
              if ($data[$i]['parent'] == '\401') {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($total, $decimal_currency), '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                $str .= $this->reporter->col(number_format(($amt / $r) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($sgdtotal, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                $str .= $this->reporter->col(number_format(($sgd / $rs) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0);
                $str .= $this->reporter->endrow();
              } else {

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($total, $decimal_currency), '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                $str .= $this->reporter->col(number_format(($amt / $r2) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($sgdtotal, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                $str .= $this->reporter->col(number_format(($sgd / $rs2) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "", 0);
                $str .= $this->reporter->endrow();
              }
            } else {

              if ($data[$i]['levelid'] == 3) {

                if ($data[$i]['parent'] == '\401') {
                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col(number_format(($amt / $r) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col(number_format(($sgd / $rs) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                  $str .= $this->reporter->endrow();
                } else if ($data[$i]['parent'] == '\503') {
                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);

                  if ($amt != 0 && $r != 0) {
                    if ($fi2 < 0) {
                      $str .= $this->reporter->col(number_format((($amt / $fi2) * 100) * -1, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                    } else {
                      $str .= $this->reporter->col(number_format(($amt / $fi2) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                    }
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                  }
                  $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);

                  if ($sgd != 0 && $fi2 != 0) {
                    if ($fi2 < 0) {
                      $str .= $this->reporter->col(number_format((($sgd / $fie2) * 100) * -1, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                    } else {
                      $str .= $this->reporter->col(number_format(($sgd / $fie2) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                    }
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  }
                  $str .= $this->reporter->endrow();
                } else if ($data[$i]['parent'] == '\402') {
                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col(number_format(($amt / ($r + $r2)) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col(number_format(($sgd / ($rs + $rs2)) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                  $str .= $this->reporter->endrow();
                } else {

                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);

                  if ($amt != 0 && $r != 0) {
                    $str .= $this->reporter->col(number_format(($amt / $fi) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                  }
                  $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);

                  if ($sgd != 0 && $rs != 0) {
                    $str .= $this->reporter->col(number_format(($sgd / $fie) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  }
                  $str .= $this->reporter->endrow();
                }
              } else {
                if ($data[$i]['parent'] == '\50101' || $data[$i]['parent'] == '\50102') {

                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                  $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  if ($amt != 0 && $e != 0) {
                    $str .= $this->reporter->col(number_format(($amt / $e) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "", 0);
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "", 0);
                  }
                  $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                  $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  if ($sgd != 0 && $es != 0) {
                    $str .= $this->reporter->col(number_format(($sgd / $es) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                  }
                  $str .= $this->reporter->endrow();
                } else {
                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col($data[$i]['acnoname'], '400', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                  $str .= $this->reporter->col(number_format($amt, $decimal_currency), '95', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col(number_format(($amt / $fi) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "", 0);
                  $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(number_format($sgd, $decimal_currency), '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                  $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '95', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                  $str .= $this->reporter->col(number_format(($sgd / $fie) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '', 0, "");
                  $str .= $this->reporter->endrow();
                }
              }
            }
          }
          break;
          // to follow
        default:

          $totgrp = $this->CHECK_TOTALPARENT($data[$i]['acno'], $isposted, $start, $end, $filters['params']['dataparams']['branchcode'], $costcenter);
          switch ($data[$i]['levelid']) {
            case '1':
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
              $str .= $this->reporter->col('', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:"' . $data[$i]["color"] . '";">' . number_format($total, $decimal_currency) . '</span>' : ($total != '' ? number_format($total, $decimal_currency) : '&nbsp;') . '&nbsp;', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');

              if ($total != 0) {
                if ($data[$i]['acno'] == '\5') {
                  $str .= $this->reporter->col(number_format(($total / $r) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                } else {
                  switch ($data[$i]['acnoname']) {
                    case '<b>NET INCOME/LOSS BEFORE OTHER GAINS AND LOSSES</b>':
                    case '<b>NET INCOME/LOSS AFTER OTHER GAINS & LOSSES & BEFORE TAX</b>':
                      $str .= $this->reporter->col(number_format(($total / ($r + $r2)) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                      break;

                    default:
                      $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                      break;
                  }
                }
              } else {
                $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
              }
              $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:"' . $data[$i]["color"] . '";">' . number_format($sgdtotal, $decimal_currency) . '</span>' : ($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '&nbsp;') . '&nbsp;', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
              if ($sgdtotal != 0) {
                if ($data[$i]['acno'] == '\5') {
                  if ($rs != 0) {
                    $str .= $this->reporter->col(number_format(($sgdtotal / $rs) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  }
                } else {
                  switch ($data[$i]['acnoname']) {
                    case '<b>NET INCOME/LOSS BEFORE OTHER GAINS AND LOSSES</b>':
                    case '<b>NET INCOME/LOSS AFTER OTHER GAINS & LOSSES & BEFORE TAX</b>':
                      if ($rs != 0) {
                        $str .= $this->reporter->col(number_format(($sgdtotal / ($rs + $rs2)) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                      } else {
                        $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                      }
                      break;

                    default:
                      $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                      break;
                  }
                }
              } else {
                $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
              }
              $str .= $this->reporter->endrow();
              break;
            case '2':

              if ($totgrp != 0) {

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                $str .= $this->reporter->col('', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:"' . $data[$i]["color"] . '";">' . number_format($total, $decimal_currency) . '</span>' : ($total != '' ? number_format($total, $decimal_currency) : '&nbsp;') . '&nbsp;', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                if ($total != 0) {
                  $str .= $this->reporter->col(number_format(($total / $r) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                } else {
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                }
                $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:"' . $data[$i]["color"] . '";">' . number_format($sgdtotal, $decimal_currency) . '</span>' : ($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '&nbsp;') . '&nbsp;', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                if ($data[$i]['total'] != 0) {
                  $str .= $this->reporter->col(number_format(($sgdtotal / $e) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                } else {
                  $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                }
                $str .= $this->reporter->endrow();
              } else {
                //check if may child na parent padin
                $pc = $this->coreFunctions->getfieldvalue("coa", "acno", "parent=? and detail =0", [$data[$i]['acno']]);
                if (strlen($pc) != 0) {
                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                  $str .= $this->reporter->col('', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:"' . $data[$i]["color"] . '";">' . number_format($total, $decimal_currency) . '</span>' : ($total != '' ? number_format($total, $decimal_currency) : '&nbsp;') . '&nbsp;', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  $str .= $this->reporter->col('', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  if ($data[$i]['total'] != 0) {
                    $str .= $this->reporter->col(number_format(($total / $r) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  }
                  $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:"' . $data[$i]["color"] . '";">' . number_format($sgdtotal, $decimal_currency) . '</span>' : ($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '&nbsp;') . '&nbsp;', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                  $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                  if ($data[$i]['total'] != 0) {
                    $str .= $this->reporter->col(number_format(($sgdtotal / $e) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  } else {
                    $str .= $this->reporter->col('', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                  }
                  $str .= $this->reporter->endrow();
                }
              }
              break;
            default:

              if ($totgrp != 0) {
                $acnonx = "";

                foreach ($data2 as $keyx => $valuex) {
                  if ($data[$i]['acnoname'] == $valuex['acnoname'] && $valuex['parent'] == $data[$i]['parent'] && $data[$i]['total'] != 0) {
                    if ($data[$i]['parent'] == '\501') {
                      $str .= $this->reporter->startrow();
                      $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                      $str .= $this->reporter->col(number_format(($total / $e) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                      $str .= $this->reporter->col(number_format(($sgdtotal / $es) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->endrow();
                    } else {
                      $str .= $this->reporter->startrow();
                      $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col($total != '' ? number_format($total, $decimal_currency) : '', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
                      $str .= $this->reporter->col(number_format(($total / $fi) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->col($sgdtotal != '' ? number_format($sgdtotal, $decimal_currency) : '', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, "", 1);
                      $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                      $str .= $this->reporter->col(number_format(($sgdtotal / $fie) * 100, $decimal_currency) . ' %', '95', null, false, $border, 'TRBL', 'R', $font, $fontsize, '', '', '');
                      $str .= $this->reporter->endrow();
                    }
                  } else if ($data[$i]['total'] == 0) {
                    if ($acnonx != $data[$i]['acnoname']) {
                    }
                  }
                  $acnonx = $data[$i]['acnoname'];
                }
              } else {
                //check if may child na parent padin
                $pc = $this->coreFunctions->getfieldvalue("coa", "acno", "parent=? and detail =0", [$data[$i]['acno']]);
                if (strlen($pc) != 0) {
                }
              }
              break;
          }

          break;
      }
      //end test

    } //for
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //AFTI_Layout

  private function HOUSEGEM_DEFAULT_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = '10';
    $fontsize11 = 11;

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);
    $center = $filters['params']['dataparams']['center'];

    if ($center == "") {
      $center = "ALL";
    }

    $count = 68;
    $page = 67;
    $this->reporter->linecounter = 0;
    $str = '';
    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->HOUSEGEM_DEFAULT_HEADER($filters, $center);
    $str .= $this->housegem_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
    $str .= $this->reporter->begintable();

    for ($i = 0; $i < count($data); $i++) {
      if ($data[$i]['isshow'] == 0) {
        continue;
      }

      if (isset($data[$i]['isparenttotal'])) {
        if (!$data[$i]['isparenttotal']) {
          continue;
        }
      }

      $indent = '5' * ($data[$i]['levelid'] * 3);

      $level1font = '10';
      $level1borderline = '';
      if ($data[$i]['levelid'] == 1 && $data[$i]['cat'] == 'X') {
        $level1font = '12';
        $level1borderline = 'B';

        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp', '580', null, false, '1px solid ', '', '', '', '', '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->addline();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, '1px solid ', '', '', $font, $level1font, '', '', '0px 0px 0px ' . $indent . 'px');

      if ($data[$i]['amt'] == 0) {
        $amt = '';
      } else {
        $amt = number_format($data[$i]['amt'], $decimal_currency);
      }
      $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'r', $font, $fontsize, (isset($data[$i]['istotal']) ? true : false) ? 'B' : '', '', '');

      if ($data[$i]['total'] == 0) {
        $total = '';
      } else {
        if ($amt == 0) {
          $total = number_format($data[$i]['total'], 2);
        } else {
          $total = '';
        }
      }

      $str .= $this->reporter->col($total, '100', null, false, '1px solid ', $level1borderline, 'R', $font, $level1font, 'B', '', '');
      $str .= $this->reporter->endrow();


      if ($data[$i]['levelid'] == 1 && $data[$i]['cat'] == 'X') {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp', '580', null, false, '1px solid ', '', '', '', '', '', '', '');
        $str .= $this->reporter->endrow();
      } elseif ((isset($data[$i]['istotal']) ? true : false) && ($data[$i]['levelid'] != 1 || $data[$i]['total'] != 0)) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp', '580', null, false, '1px solid ', '', '', '', '', '', '', '');
        $str .= $this->reporter->endrow();
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($filters['params']);
        if (!$allowfirstpage) {
          $str .=  $this->HOUSEGEM_DEFAULT_HEADER($filters, $center);
        }
        $str .= $this->housegem_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } //for



    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //HOUSEGEM_Layout

  private function MMM_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = '10';
    $fontsize11 = 11;

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);
    $center = $filters['params']['dataparams']['center'];

    if ($center == "") {
      $center = "ALL";
    }

    $count = 68;
    $page = 67;
    $this->reporter->linecounter = 0;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER($filters, $center);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
    $str .= $this->reporter->begintable();

    for ($i = 0; $i < count($data); $i++) {

      if ($data[$i]['detail'] == 1) {
        if ($data[$i]['amt'] != 0) {
          $str .= $this->reporter->startrow();

          $indent = '5' * ($data[$i]['levelid'] * 3);
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, '1px solid ', '', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
          if ($data[$i]['amt'] == 0) {
            $amt = '';
          } else {
            $amt = number_format($data[$i]['amt'], $decimal_currency);
          }
          $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'r', $font, $fontsize,  '', '', '');

          if ($data[$i]['total'] == 0) {
            $total = '';
          } else {
            if ($amt == 0) {
              $total = number_format($data[$i]['total'], 2);
            } else {
              $total = '';
            }
          }

          $str .= $this->reporter->col($total, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();

        $indent = '5' * ($data[$i]['levelid'] * 3);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, '1px solid ', '', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
        if ($data[$i]['amt'] == 0) {
          $amt = '';
        } else {
          $amt = number_format($data[$i]['amt'], $decimal_currency);
        }
        $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'r', $font, $fontsize,  '', '', '');

        if ($data[$i]['total'] == 0) {
          $total = '';
        } else {
          if ($amt == 0) {
            $total = number_format($data[$i]['total'], 2);
          } else {
            $total = '';
          }
        }

        $str .= $this->reporter->col($total, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($filters['params']);
        if (!$allowfirstpage) {
          $str .=  $this->DEFAULT_HEADER($filters, $center);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } //for
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //MMM_Layout

  private function KINGGEORGE_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize12 = '10';

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);

    $count = 68;
    $page = 67;
    $this->reporter->linecounter = 0;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->KINGGEORGE_HEADER($filters);
    $str .= $this->reporter->begintable();

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();

      $indent = '5' * ($data[$i]['levelid'] * 3);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, '1px solid ', '', '', $font, $fontsize12, '', '', '0px 0px 0px ' . $indent . 'px');
      if ($data[$i]['amt'] == 0) {
        $amt = '';
      } else {
        $amt = number_format($data[$i]['amt'], $decimal_currency);
      }
      $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'r', $font, $fontsize12,  '', '', '');

      if ($data[$i]['total'] == 0) {
        $total = '';
      } else {
        if ($amt == 0) {
          $total = number_format($data[$i]['total'], 2);
        } else {
          $total = '';
        }
      }

      $str .= $this->reporter->col($total, '100', null, false, '1px solid ', '', 'R', $font, $fontsize12, 'B', '', '');


      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .=  $this->KINGGEORGE_HEADER($filters);
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } //for
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //kinggeorge_Layout

  private function CDO_INCOME_STATEMENT_LAYOUT($filters, $data)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = '10';
    $fontsize11 = 11;

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);
    $center = $filters['params']['dataparams']['center'];

    if ($center == "") {
      $center = "ALL";
    }

    $count = 68;
    $page = 67;
    $this->reporter->linecounter = 0;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->CDO_HEADER($filters, $center);
    $str .= $this->CDO_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
    $str .= $this->reporter->begintable();

    $key_words_total = ['Net', 'TOTAL', 'GROSS'];
    for ($i = 0; $i < count($data); $i++) {
      if (($data[$i]['detail'] == 1) and $data[$i]['amt'] == 0 and $data[$i]['total'] == 0) {
      } else {
        $str .= $this->reporter->startrow();

        $indent = '5' * ($data[$i]['levelid'] * 3);
        $str .= $this->reporter->addline();

        $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, '1px solid ', '', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
        if ($data[$i]['amt'] == 0) {
          $amt = '';
        } else {
          $amt = number_format($data[$i]['amt'], $decimal_currency);
        }


        if ($data[$i]['total'] == 0) {
          $total = '';
        } else {
          if ($amt == 0) {
            $total = number_format($data[$i]['total'], 2);
          } else {
            $total = '';
          }
        }

        $is_total = 0;
        foreach ($key_words_total as $key => $keyword) {

          if (strpos($data[$i]['acnoname'], $keyword) >= 0 && strpos($data[$i]['acnoname'], $keyword) != '') {
            $is_total = 1;
          }
        }


        if ($is_total == 1) {
          if ($total > 0) {
            $str .= $this->reporter->col($total, '100', null, false, '1px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col($total, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, 'B', '', '');
          }
        } else {
          $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'r', $font, $fontsize,  '', '', '');
        }


        $str .= $this->reporter->endrow();
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($filters['params']);
        if (!$allowfirstpage) {

          $str .=  $this->CDO_HEADER($filters, $center);
        }
        $str .= $this->CDO_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } //for
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //CDO_Layout

  private function DEFAULT_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = '10';
    $fontsize11 = 11;

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);
    $center = $filters['params']['dataparams']['center'];

    if ($center == "") {
      $center = "ALL";
    }

    $count = 68;
    $page = 67;
    $this->reporter->linecounter = 0;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER($filters, $center);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
    $str .= $this->reporter->begintable();

    for ($i = 0; $i < count($data); $i++) {
      if ($companyid == 8) { //maxipro
        if ($data[$i]['levelid'] <> 3 || ($data[$i]['levelid'] == 3 && $data[$i]['total'] <> 0)) {
          if ($data[$i]['detail'] == 1 && $data[$i]['total'] == 0) {
          } else {
            $str .= $this->reporter->startrow();
            $indent = '5' * ($data[$i]['levelid'] * 3);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, '1px solid ', '', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
            if ($data[$i]['amt'] == 0) {
              $amt = '';
            } else {
              $amt = number_format($data[$i]['amt'], $decimal_currency);
            }
            $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'r', $font, $fontsize,  '', '', '');
            if ($data[$i]['total'] == 0) {
              $total = '';
            } else {
              if (
                $amt == 0
              ) {
                $total = number_format($data[$i]['total'], 2);
              } else {
                $total = '';
              }
            }
            $str .= $this->reporter->col($total, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          }
        }
      } else {
        $str .= $this->reporter->startrow();
        $indent = '5' * ($data[$i]['levelid'] * 3);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, '1px solid ', '', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
        if ($data[$i]['amt'] == 0) {
          $amt = '';
        } else {
          $amt = number_format($data[$i]['amt'], $decimal_currency);
        }
        $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'r', $font, $fontsize,  '', '', '');
        if ($data[$i]['total'] == 0) {
          $total = '';
        } else {
          if (
            $amt == 0
          ) {
            $total = number_format($data[$i]['total'], 2);
          } else {
            $total = '';
          }
        }
        $str .= $this->reporter->col($total, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($filters['params']);
        if (!$allowfirstpage) {

          $str .=  $this->DEFAULT_HEADER($filters, $center);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $filters, $center);
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } //for
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //default_Layout
  //END

  //TABLE_COLS START
  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config, $center)
  {
    $str = '';
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['due']));
    $costcenter = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('INCOME STATEMENT', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center:' . $center, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    if ($config['params']['dataparams']['costcenter'] == "") {
      $costcenter = "ALL";
    } else {
      $costcenter = isset($config['params']['dataparams']['centername']) ? $config['params']['dataparams']['centername'] : '';
    }

    $posttype = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:
        $post = 'Transaction: POSTED';
        break;
      case 1:
        $post = 'Transaction: UNPOSTED';
        break;
      default:
        $post = 'Transaction: All';
        break;
    }

    $str .= $this->reporter->col($post, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');


    $str .= $this->reporter->col('Cost Center:' . $costcenter, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  }

  private function CDO_table_cols($layoutsize, $border, $font, $fontsize, $config, $center)
  {
    $str = '';
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['due']));
    $costcenter = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('INCOME STATEMENT', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center:' . $center, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    if ($config['params']['dataparams']['costcenter'] == "") {
      $costcenter = "ALL";
    } else {
      $costcenter = isset($config['params']['dataparams']['centername']) ? $config['params']['dataparams']['centername'] : '';
    }
    $str .= $this->reporter->col('Cost Center:' . $costcenter, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');

    $posttype = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:
        $post = 'Transaction: POSTED';
        break;
      case 1:
        $post = 'Transaction: UNPOSTED';
        break;
      default:
        $post = 'Transaction: All';
        break;
    }

    $str .= $this->reporter->col($post, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  }

  private function housegem_table_cols($layoutsize, $border, $font, $fontsize, $config, $center)
  {
    $str = '';
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['due']));
    $isposted = $config['params']['dataparams']['posttype'];


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Housegem', null, null, false, '1px solid ', '', 'L', $font, '20', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Profit & Loss', null, null, false, '1px solid ', '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center:' . $center, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    if ($isposted == 0) {
      $str .= $this->reporter->col('Transaction: POSTED', null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Transaction: UNPOSTED', null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  }
  //END

  //HEADER START
  private function AFTECH_DEFAULT_HEADER($filters, $center)
  {
    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = '10';

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Access Frontier Technologies Inc.', null, null, false, '1px solid ', '', 'L', $font, '20', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Profit and Loss Statement', null, null, false, '1px solid ', '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the period ended ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center:' . $center, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    if ($isposted == 0) {
      $str .= $this->reporter->col('Transaction: POSTED', null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Transaction: UNPOSTED', null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  } //DEFAULT HEADER

  private function HOUSEGEM_DEFAULT_HEADER($filters, $center)
  {
    $str = '';
    return $str;
  } //DEFAULT HEADER

  private function KINGGEORGE_HEADER($filters)
  {
    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = '10';
    $fontsize12 = '12';
    $fontsize15 = '15';

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $companyid = $filters['params']['companyid'];
    $center = $this->coreFunctions->datareader("select code as value from center order by code limit 1");
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $filters);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br/><br/>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('I N C O M E&nbsp&nbsp&nbsp&nbspS T A T E M E N T', null, null, false, '1px solid ', '', '', $font, $fontsize15, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('', '200');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');

    if ($isposted == 0) {
      $str .= $this->reporter->col('Transaction: POSTED', null, null, false, '1px solid ', '', '', $font, $fontsize12, '', '', '');
    } else {
      $str .= $this->reporter->col('Transaction: UNPOSTED', null, null, false, '1px solid ', '', '', $font, $fontsize12, '', '', '');
    }
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  } //KINGGEORGE HEADER

  private function CDO_HEADER($filters, $center)
  {
    $center1 = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $filters);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br/><br/>";

    return $str;
  } //CDO HEADER

  private function DEFAULT_HEADER($filters, $center)
  {
    $center1 = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $filters);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br/><br/>";

    return $str;
  } //DEFAULT HEADER
  //END

  //MISC FUNCTIONS START
  function isBold($string)
  {
    return preg_match("<b>", $string, $m) != 0;
  }

  private function CHECK_TOTALPARENT($parent, $isposted, $start, $end, $branch, $itemgroup)
  {
    if ($parent == '') {
      return 0;
    }

    $filter = '';

    if ($branch != '') {
      $filter .=  "and detail.branch ='$branch' ";
    }

    if ($itemgroup != '') {
      $filter .=  "and detail.projectid ='$itemgroup' ";
    }

    switch ($isposted) {
      case 1:
        $qry = "select ifnull(sum(detail.db-detail.cr),0) as value from ladetail as detail left join lahead as head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid left join client as br on br.clientid = detail.branch where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.parent ='\\" . $parent . "'" . $filter;
        break;
      default:
        $qry = "select ifnull(sum(detail.db-detail.cr),0) as value from gldetail as detail left join glhead as head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid left join client as br on br.clientid = detail.branch where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.parent ='\\" . $parent . "'" . $filter;
        break;
    }

    $tot = $this->coreFunctions->datareader($qry);
    return $tot;
  }

  private function getParentComputeAmt($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company)
  {
    $field = '';
    $filters = " where coa.parent='\\" . "$acno' and coa.cat='$cat' "; // default filters
    $addedfilters = '';

    if ($company == 8) {
      if ($filter != '') {
        $addedfilters .= " and detail.projectid = '" . $filter . "' "; // cost center filter
      }
    } else {
      if ($filter != '') {
        $addedfilters .= " and head.projectid = '" . $filter . "' "; // cost center filter
      }
    }

    if ($center != '') {
      $addedfilters .= " and cntnum.center = '" . $center . "' "; // center filter
    }

    switch ($cat) {
      case 'L':
      case 'R':
      case 'G':
      case 'C':
      case 'O':
        $field = " sum(round(detail.cr-detail.db,2)) ";
        break;
      default:
        $field = " sum(round(detail.db-detail.cr,2)) ";
        break;
    } //end swtich

    switch ($status) {
      case 1: // unposted
        $query1 = "select sum(tb.amt) as value 
                    from (select ifnull((select $field from lahead as head left join ladetail as detail on detail.trno=head.trno left join cntnum on cntnum.trno=head.trno 
                    where detail.acnoid=coa.acnoid and coa.isshow=0 and date(detail.postdate) between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt from coa " . $filters . " ) as tb ";
        break;

      default: // posted
        $query1 = "select sum(tb.amt) as value from (select ifnull((select $field from glhead as head left join gldetail as detail on detail.trno=head.trno left join cntnum on cntnum.trno=head.trno 
                    where detail.acnoid=coa.acnoid and coa.isshow=0 and date(detail.postdate) between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt 
                    from coa " . $filters . ") as  tb ";
        break;
    } //end switch

    $amt = $this->coreFunctions->datareader($query1);
    if ($amt == '') {
      $amt = 0;
    }
    return $amt;
  }

  private function INCOME_STATEMENT_INNER_QUERY($cat, $acno, $date1, $date2, $center, $status, $cc, $filter, $company, $defaultfield_filter = '')
  {
    $field = '';

    if ($defaultfield_filter == '') {
      $filters = " where coa.parent='$acno' and coa.cat='$cat' "; // default filters
    } else {
        $filters = " where coa." . $defaultfield_filter . "='$acno' and coa.cat='$cat'"; // default filters
    }

    $addedfilters = '';

    switch ($company) {
      case 10:
      case 12:
        if ($filter != '') {
          $addedfilters .= " and detail.project = '" . $filter . "' "; // cost center filter
        }

        if ($center != '') {
          $addedfilters .= " and detail.branch = '" . $center . "' "; // center filter
        }
        break;

      default:
        if ($company == 8) {
          if ($filter != '') {
            $addedfilters .= " and detail.projectid = '" . $filter . "' "; // cost center filter
          }
        } else {
          if ($filter != '') {
            $addedfilters .= " and head.projectid = '" . $filter . "' "; // cost center filter
          }
        }

        if ($center != '') {
          $addedfilters .= " and cntnum.center = '" . $center . "' "; // center filter
        }
        break;
    }

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = " sum(round(detail.cr-detail.db,2)) ";
        break;
      case 'G':
        if ($company == 32) {
          $field = " sum(round(detail.db-detail.cr,2)) ";
        } else {
          $field = " sum(round(detail.cr-detail.db,2)) ";
        }
        break;
      default:
        if ($company == 10) {
          $field = " case when coa.alias in ('PD1','SA10','GL1','GL2','BC1') then sum(round(detail.db-detail.cr,2)) *-1 else sum(round(detail.db-detail.cr,2)) end ";
        } else {
          $field = " sum(round(detail.db-detail.cr,2)) ";
        }

        break;
    } //end swtich

    $datefield = 'date(head.dateid)';
    switch ($company) {
      case 19:
        $datefield = "date(detail.postdate)";
        $addfieldG = " , tb.incomegrp ";
        $addfield = " , coa.incomegrp ";
        $orderby = " order by tb.parent, tb.incomegrp, tb.acno ";
        break;
      case 32:
        $addfieldG = "";
        $addfield = "";
        $orderby = " order by tb.levelid asc, tb.acno";
        break;
      case 40:
        $addfieldG = " , tb.incomegrp ";
        $addfield = " , coa.incomegrp ";
        $orderby = " order by tb.incomegrp, tb.acno  ,tb.parent";
        break;

      default:
        $addfieldG = "";
        $addfield = "";
        $orderby = " order by tb.levelid asc, tb.acno,tb.acnoname asc ";
        break;
    }

    $selecthjc = '';
    $selectjc = '';

    if ($company == 8) { //maxipro
      $selecthjc = " union all select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, 
                                  coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal $addfield ,ifnull((select $field from hjchead as head 
                                  left join gldetail as detail on detail.trno=head.trno 
                                  left join cntnum on cntnum.trno=head.trno 
                                  where detail.acnoid=coa.acnoid and " . $datefield . " between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt 
                          from coa " . $filters . "";
      $selectjc = " union all select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, 
                                coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal $addfield,ifnull((select $field from jchead as head 
                                left join ladetail as detail on detail.trno=head.trno 
                                left join cntnum on cntnum.trno=head.trno 
                                where detail.acnoid=coa.acnoid and " . $datefield . " between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt 
                          from coa " . $filters . "";
    }

    switch ($status) {
      case 1: // unposted
        $query1 = "
          select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail $addfieldG ,sum(tb.amt) as amt, tb.isshow, tb.iscompute, tb.isparenttotal
          from (
            select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, 
            coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal $addfield,ifnull((select $field from lahead as head 
            left join ladetail as detail on detail.trno=head.trno 
            left join cntnum on cntnum.trno=head.trno 
            where detail.acnoid=coa.acnoid and " . $datefield . " between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt 
            from coa " . $filters . " $selectjc
          ) as tb
          group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias, tb.isshow, tb.iscompute, tb.isparenttotal $addfieldG
          $orderby ";
        break;

      case 0: // posted
        $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail $addfieldG ,sum(tb.amt) as amt, tb.isshow, tb.iscompute, tb.isparenttotal
          from (
            select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, 
            coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal $addfield ,ifnull((select $field from glhead as head 
            left join gldetail as detail on detail.trno=head.trno 
            left join cntnum on cntnum.trno=head.trno 
            where detail.acnoid=coa.acnoid and " . $datefield . " between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt 
            from coa " . $filters . " $selecthjc
          ) as  tb
          group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias, tb.isshow, tb.iscompute, tb.isparenttotal  $addfieldG
                    $orderby ";
        break;

      case 2: // sana all
        $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail $addfieldG, sum(tb.amt) as amt, tb.isshow, tb.iscompute, tb.isparenttotal
          from (

            select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,
            coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal $addfield ,
            ifnull((select  $field  from glhead as head
            left join gldetail as detail on detail.trno=head.trno
            left join cntnum on cntnum.trno=head.trno 
            where detail.acnoid=coa.acnoid and " . $datefield . " between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt 
            from coa " . $filters . " $selecthjc

            union all

            select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,
            coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal $addfield ,
            ifnull( (select  $field from lahead as head
            left join ladetail as detail on detail.trno=head.trno
            left join cntnum on cntnum.trno=head.trno
            where detail.acnoid=coa.acnoid and " . $datefield . " between '" . $date1 . "' and '" . $date2 . "' " . $addedfilters . "),0) as amt 
            from coa " . $filters . " $selectjc 
          ) as  tb
          group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias, tb.isshow, tb.iscompute, tb.isparenttotal $addfieldG
          $orderby ";

        break;
    } //end switch

    return $query1;
  } // DEFAULT BALANCE SHEET

  private function INCOME_STATEMENT_GET_SUM($entry, $cat, $date1, $date2, $center, $status, $company, $filter)
  {
    $field = "";
    $filters = "";

    switch ($company) {
      case 12:
      case 10:
        if ($center != "") {
          $filters .= " and detail.branch = '" . $center . "' "; // branch filter
        }
        break;

      case 40:
        # code...
        break;
      default:
        if ($company == 8) {
          if ($filter != 0) {
            $filters .= " and detail.projectid = '" . $filter . "' "; // cost center filter
          }
        } else {
          if ($filter != 0) {
            $filters .= " and head.projectid = '" . $filter . "' "; // cost center filter
          }
        }

        if ($center != "") {
          $filters .= " and cntnum.center = '" . $center . "' "; // center filter
        }
        break;
    }

    switch ($entry) {
      case 'CREDIT':
        $field = ' sum(detail.cr-detail.db) ';
        $query1 = "select  ifnull(sum(tb.cr),0) as amt from  ";
        break;
      default:
        $field = ' sum(detail.db-detail.cr) ';
        $query1 = "select  ifnull(sum(tb.cr),0) as amt from ";
        break;
    }

    $selecthjc = '';
    $selectjc = '';

    if ($company == 8) { //maxipro
      $selecthjc = " union all select $field as cr from ((hjchead as head left join gldetail as detail on detail.trno=head.trno)
          left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
          where  head.dateid between '" . $date1 . "' and '" . $date2 . "' and coa.cat in " . $cat . " " . $filters . " ";
      $selectjc = " union all select $field as cr from ((jchead as head left join ladetail as detail on detail.trno=head.trno)
          left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
          where  head.dateid between '" . $date1 . "' and '" . $date2 . "' and coa.cat in " . $cat . " " . $filters . " ";
    }

    switch ($status) {
      case 1: // unposted
        $query1 = $query1 . " (select $field as cr from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
          left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
          where  head.dateid between '" . $date1 . "' and '" . $date2 . "' and coa.cat in " . $cat . " " . $filters . " $selectjc
          ) as tb ";
        break;

      default: // posted
        $query1 = $query1 . " (select $field as cr from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
          left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
          where  head.dateid between '" . $date1 . "' and '" . $date2 . "' and coa.cat in " . $cat . " " . $filters . " $selecthjc
          ) as tb ";
        break;
    }
    $result = $this->coreFunctions->opentable($query1);
    $result = $result[0]->amt;
    return $result;
  } // DEFAULT BALANCE SHEETDUE
  //END

}//end class