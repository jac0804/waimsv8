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

class monthly_income_statement
{
  public $modulename = 'Monthly Income Statement';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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

    $fields = ['dcentername', 'costcenter'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'ddeptname.label', 'Department');
        data_set($col2, 'costcenter.label', 'Item Group');
        break;

      default:
        $col2 = $this->fieldClass->create($fields);
        break;
    }

    $fields = ['year', 'print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,left(now(),10) as due,left(now(),4) as year,'' as code,'' as name,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as costcenter ";
    
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= ", '' as ddeptname, '' as dept, '' as deptname ";
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

  public function CDO_query($filters)
  {
    $company = $filters['params']['companyid'];
    $year = intval($filters['params']['dataparams']['year']);
    $year2 = $year + 1;
    $center = $filters['params']['dataparams']['center'];
    $cc = '';
    $filter = '';
    $year2 = $year;

    $filter = 0;
    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as detail,
    0 as monjan,
    0 as monfeb,
    0 as monmar,
    0 as monapr,
    0 as monmay,
    0 as monjun,
    0 as monjul,
    0 as monaug,
    0 as monsep,
    0 as monoct,
    0 as monnov,
    0 as mondec,
    0 as amt,
    0 as total";

    //initialized values
    $coa = $this->coreFunctions->opentable($query2);

    $month = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);

    $computed_month_gross_margin =
      $computed_month_net_income_loss_from_operation =
      $computed_month_net_profit_loss_before_admin_expenses =
      $computed_less_admin =
      $month_revenue1 = $month_revenue2 =
      $month_cost_of_sale1 = $month_cost_of_sale2 =
      $month_expenses1 = $month_expenses2 =
      $month_net_income_loss1 = $month_net_income_loss2 =
      $month_net_profit_loss_before = $month_net_profit = $month;

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
    $acno_R = ['', '\\40602', '\\40603', '\\40604', '\\40605', '\\40606', '\\40607', '\\40609'];
    $acno_E = ['', '\\502'];
    $inex_clude_acno = $acno_R;

    //array value building for layout
    $this->CDO_PLANTTREE(
      $coa,
      '\\\\',
      'R',
      $amt1,
      $amt1b,
      $a,
      $year,
      $year2,
      $center,
      $cc,
      $filter,
      $company,
      ["complex_acno" => $inex_clude_acno, "inex_clude" => 0],
      0,
      $amtgrp,
      $prev_incomegrp2,
      "",
      $month_revenue1,
      $month_revenue2
    );

    $brgross = $month_revenue2;
    $coa[] = array(
      'acno' => '\\771',
      'acnoname' => '<b>GROSS SALES</b>',
      'levelid' => 1,
      'cat' => 'E',
      'parent' => 'X',
      'detail' => 0,
      'monjan' => number_format((float)$month_revenue2['mjan'], 2, '.', ''),
      'monfeb' => number_format((float)$month_revenue2['mfeb'], 2, '.', ''),
      'monmar' => number_format((float)$month_revenue2['mmar'], 2, '.', ''),
      'monapr' => number_format((float)$month_revenue2['mapr'], 2, '.', ''),

      'monmay' => number_format((float)$month_revenue2['mmay'], 2, '.', ''),
      'monjun' => number_format((float)$month_revenue2['mjun'], 2, '.', ''),
      'monjul' => number_format((float)$month_revenue2['mjul'], 2, '.', ''),
      'monaug' => number_format((float)$month_revenue2['maug'], 2, '.', ''),

      'monsep' => number_format((float)$month_revenue2['msep'], 2, '.', ''),
      'monoct' => number_format((float)$month_revenue2['moct'], 2, '.', ''),
      'monnov' => number_format((float)$month_revenue2['mnov'], 2, '.', ''),
      'mondec' => number_format((float)$month_revenue2['mdec'], 2, '.', ''),
      'amt' => 0,
      'total' => $amt1b
    );

    $this->CDO_GET_BRANCH($coa, '\\\\502', 'E', $amt2, $amt2b, $a, $year, $year2, $center, $cc, $filter, $company, ["defaultfield_filter" => "acno"], $month_cost_of_sale1, $month_cost_of_sale2);
    $amt1b_less2b = $amt1b - $amt2b;
    $computed_month_gross_margin['mjan'] = $month_revenue2['mjan'] - $month_cost_of_sale2['mjan'];
    $computed_month_gross_margin['mfeb'] = $month_revenue2['mfeb'] - $month_cost_of_sale2['mfeb'];
    $computed_month_gross_margin['mmar'] = $month_revenue2['mmar'] - $month_cost_of_sale2['mmar'];
    $computed_month_gross_margin['mapr'] = $month_revenue2['mapr'] - $month_cost_of_sale2['mapr'];
    $computed_month_gross_margin['mmay'] = $month_revenue2['mmay'] - $month_cost_of_sale2['mmay'];
    $computed_month_gross_margin['mjun'] = $month_revenue2['mjun'] - $month_cost_of_sale2['mjun'];
    $computed_month_gross_margin['mjul'] = $month_revenue2['mjul'] - $month_cost_of_sale2['mjul'];
    $computed_month_gross_margin['maug'] = $month_revenue2['maug'] - $month_cost_of_sale2['maug'];
    $computed_month_gross_margin['msep'] = $month_revenue2['msep'] - $month_cost_of_sale2['msep'];
    $computed_month_gross_margin['moct'] = $month_revenue2['moct'] - $month_cost_of_sale2['moct'];
    $computed_month_gross_margin['mnov'] = $month_revenue2['mnov'] - $month_cost_of_sale2['mnov'];
    $computed_month_gross_margin['mdec'] = $month_revenue2['mdec'] - $month_cost_of_sale2['mdec'];

    $coa[] = array(
      'acno' => '\\772',
      'acnoname' => '<b>GROSS MARGIN</b> ',
      'levelid' => 1,
      'cat' => 'E',
      'parent' => 'X',
      'detail' => 0,
      'monjan' => number_format((float)$computed_month_gross_margin['mjan'], 2, '.', ''),
      'monfeb' => number_format((float)$computed_month_gross_margin['mfeb'], 2, '.', ''),
      'monmar' => number_format((float)$computed_month_gross_margin['mmar'], 2, '.', ''),
      'monapr' => number_format((float)$computed_month_gross_margin['mapr'], 2, '.', ''),
      'monmay' => number_format((float)$computed_month_gross_margin['mmay'], 2, '.', ''),
      'monjun' => number_format((float)$computed_month_gross_margin['mjun'], 2, '.', ''),
      'monjul' => number_format((float)$computed_month_gross_margin['mjul'], 2, '.', ''),
      'monaug' => number_format((float)$computed_month_gross_margin['maug'], 2, '.', ''),
      'monsep' => number_format((float)$computed_month_gross_margin['msep'], 2, '.', ''),
      'monoct' => number_format((float)$computed_month_gross_margin['moct'], 2, '.', ''),
      'monnov' => number_format((float)$computed_month_gross_margin['mnov'], 2, '.', ''),
      'mondec' => number_format((float)$computed_month_gross_margin['mdec'], 2, '.', ''),
      'amt' => 0,
      'total' => $amt1b_less2b
    );

    $inex_clude_acno = $acno_E;
    $this->CDO_PLANTTREE($coa, '\\\\', 'E', $amt3, $amt3b, $a, $year, $year2, $center, $cc, $filter, $company, ["complex_acno" => $inex_clude_acno, "inex_clude" => 0], 0, $amtgrp, $prev_incomegrp2, "TOTAL OPERATING EXPENSE", $month_expenses1, $month_expenses2);

    $amt1b_less2b_less3b = $amt1b_less2b - $amt3b;
    $computed_month_net_income_loss_from_operation['mjan'] = $computed_month_gross_margin['mjan'] - $month_expenses2['mjan'];
    $computed_month_net_income_loss_from_operation['mfeb'] = $computed_month_gross_margin['mfeb'] - $month_expenses2['mfeb'];
    $computed_month_net_income_loss_from_operation['mmar'] = $computed_month_gross_margin['mmar'] - $month_expenses2['mmar'];
    $computed_month_net_income_loss_from_operation['mapr'] = $computed_month_gross_margin['mapr'] - $month_expenses2['mapr'];
    $computed_month_net_income_loss_from_operation['mmay'] = $computed_month_gross_margin['mmay'] - $month_expenses2['mmay'];
    $computed_month_net_income_loss_from_operation['mjun'] = $computed_month_gross_margin['mjun'] - $month_expenses2['mjun'];
    $computed_month_net_income_loss_from_operation['mjul'] = $computed_month_gross_margin['mjul'] - $month_expenses2['mjul'];
    $computed_month_net_income_loss_from_operation['maug'] = $computed_month_gross_margin['maug'] - $month_expenses2['maug'];
    $computed_month_net_income_loss_from_operation['msep'] = $computed_month_gross_margin['msep'] - $month_expenses2['msep'];
    $computed_month_net_income_loss_from_operation['moct'] = $computed_month_gross_margin['moct'] - $month_expenses2['moct'];
    $computed_month_net_income_loss_from_operation['mnov'] = $computed_month_gross_margin['mnov'] - $month_expenses2['mnov'];
    $computed_month_net_income_loss_from_operation['mdec'] = $computed_month_gross_margin['mdec'] - $month_expenses2['mdec'];

    $coa[] = array(
      'acno' => '\\773',
      'acnoname' => '<b>Net Income/Loss from Operation</b> ',
      'levelid' => 1,
      'cat' => 'E',
      'parent' => 'X',
      'detail' => 0,
      'monjan' => number_format((float)$computed_month_net_income_loss_from_operation['mjan'], 2, '.', ''),
      'monfeb' => number_format((float)$computed_month_net_income_loss_from_operation['mfeb'], 2, '.', ''),
      'monmar' => number_format((float)$computed_month_net_income_loss_from_operation['mmar'], 2, '.', ''),
      'monapr' => number_format((float)$computed_month_net_income_loss_from_operation['mapr'], 2, '.', ''),
      'monmay' => number_format((float)$computed_month_net_income_loss_from_operation['mmay'], 2, '.', ''),
      'monjun' => number_format((float)$computed_month_net_income_loss_from_operation['mjun'], 2, '.', ''),
      'monjul' => number_format((float)$computed_month_net_income_loss_from_operation['mjul'], 2, '.', ''),
      'monaug' => number_format((float)$computed_month_net_income_loss_from_operation['maug'], 2, '.', ''),
      'monsep' => number_format((float)$computed_month_net_income_loss_from_operation['msep'], 2, '.', ''),
      'monoct' => number_format((float)$computed_month_net_income_loss_from_operation['moct'], 2, '.', ''),
      'monnov' => number_format((float)$computed_month_net_income_loss_from_operation['mnov'], 2, '.', ''),
      'mondec' => number_format((float)$computed_month_net_income_loss_from_operation['mdec'], 2, '.', ''),
      'amt' => 0,
      'total' => $amt1b_less2b_less3b
    );
    $inex_clude_acno = $acno_R;

    foreach ($inex_clude_acno as $key => $value) {
      if ($key != 0) {
        $this->CDO_GET_BRANCH($coa, '\\' . $value, 'R', $amt4, $amt4b, $a, $year, $year2, $center, $cc, $filter, $company, ["defaultfield_filter" => "acno"], $month_net_income_loss1, $month_net_income_loss2);
      }
    }
    $amt1b_less2b_less3b_less4b = $amt1b_less2b_less3b - $amt4b;
    $computed_month_net_profit_loss_before_admin_expenses['mjan'] = $computed_month_net_income_loss_from_operation['mjan'] - $month_net_income_loss2['mjan'];
    $computed_month_net_profit_loss_before_admin_expenses['mfeb'] = $computed_month_net_income_loss_from_operation['mfeb'] - $month_net_income_loss2['mfeb'];
    $computed_month_net_profit_loss_before_admin_expenses['mmar'] = $computed_month_net_income_loss_from_operation['mmar'] - $month_net_income_loss2['mmar'];
    $computed_month_net_profit_loss_before_admin_expenses['mapr'] = $computed_month_net_income_loss_from_operation['mapr'] - $month_net_income_loss2['mapr'];
    $computed_month_net_profit_loss_before_admin_expenses['mmay'] = $computed_month_net_income_loss_from_operation['mmay'] - $month_net_income_loss2['mmay'];
    $computed_month_net_profit_loss_before_admin_expenses['mjun'] = $computed_month_net_income_loss_from_operation['mjun'] - $month_net_income_loss2['mjun'];
    $computed_month_net_profit_loss_before_admin_expenses['mjul'] = $computed_month_net_income_loss_from_operation['mjul'] - $month_net_income_loss2['mjul'];
    $computed_month_net_profit_loss_before_admin_expenses['maug'] = $computed_month_net_income_loss_from_operation['maug'] - $month_net_income_loss2['maug'];
    $computed_month_net_profit_loss_before_admin_expenses['msep'] = $computed_month_net_income_loss_from_operation['msep'] - $month_net_income_loss2['msep'];
    $computed_month_net_profit_loss_before_admin_expenses['moct'] = $computed_month_net_income_loss_from_operation['moct'] - $month_net_income_loss2['moct'];
    $computed_month_net_profit_loss_before_admin_expenses['mnov'] = $computed_month_net_income_loss_from_operation['mnov'] - $month_net_income_loss2['mnov'];
    $computed_month_net_profit_loss_before_admin_expenses['mdec'] = $computed_month_net_income_loss_from_operation['mdec'] - $month_net_income_loss2['mdec'];

    $coa[] = array(
      'acno' => '\\774',
      'acnoname' => '<b>Net Profit/Loss before Admin Expenses</b> ',
      'levelid' => 1,
      'cat' => 'E',
      'parent' => 'X',
      'detail' => 0,
      'monjan' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mjan'], 2, '.', ''),
      'monfeb' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mfeb'], 2, '.', ''),
      'monmar' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mmar'], 2, '.', ''),
      'monapr' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mapr'], 2, '.', ''),
      'monmay' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mmay'], 2, '.', ''),
      'monjun' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mjun'], 2, '.', ''),
      'monjul' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mjul'], 2, '.', ''),
      'monaug' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['maug'], 2, '.', ''),
      'monsep' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['msep'], 2, '.', ''),
      'monoct' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['moct'], 2, '.', ''),
      'monnov' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mnov'], 2, '.', ''),
      'mondec' => number_format((float)$computed_month_net_profit_loss_before_admin_expenses['mdec'], 2, '.', ''),
      'amt' => 0,
      'total' => number_format((float)$amt1b_less2b_less3b_less4b, 2, '.', '')
    );

    $adminshare = $this->cdogetadminshare($filters, $brgross);

    $coa[] = array(
      'acno' => '\\775',
      'acnoname' => 'Less: Administrative Expense Share',
      'levelid' => 2,
      'cat' => 'E',
      'parent' => 'X',
      'detail' => 0,

      'monjan' => number_format((float)$adminshare['mjan'], 2, '.', ''),
      'monfeb' => number_format((float)$adminshare['mfeb'], 2, '.', ''),
      'monmar' => number_format((float)$adminshare['mmar'], 2, '.', ''),
      'monapr' => number_format((float)$adminshare['mapr'], 2, '.', ''),
      'monmay' => number_format((float)$adminshare['mmay'], 2, '.', ''),
      'monjun' => number_format((float)$adminshare['mjun'], 2, '.', ''),
      'monjul' => number_format((float)$adminshare['mjul'], 2, '.', ''),
      'monaug' => number_format((float)$adminshare['maug'], 2, '.', ''),
      'monsep' => number_format((float)$adminshare['msep'], 2, '.', ''),
      'monoct' => number_format((float)$adminshare['moct'], 2, '.', ''),
      'monnov' => number_format((float)$adminshare['mnov'], 2, '.', ''),
      'mondec' => number_format((float)$adminshare['mdec'], 2, '.', ''),
      'amt' => number_format((float)$adminshare['amt'], 2, '.', ''),
      'total' => number_format((float)$adminshare['amt'], 2, '.', '')
    );


    $amt1234_lessadmin = $amt1b_less2b_less3b_less4b - $adminshare['amt'];
    $computed_less_admin['mjan'] = $computed_month_net_profit_loss_before_admin_expenses['mjan'] - $adminshare['mjan'];
    $computed_less_admin['mfeb'] = $computed_month_net_profit_loss_before_admin_expenses['mfeb'] - $adminshare['mfeb'];
    $computed_less_admin['mmar'] = $computed_month_net_profit_loss_before_admin_expenses['mmar'] - $adminshare['mmar'];
    $computed_less_admin['mapr'] = $computed_month_net_profit_loss_before_admin_expenses['mapr'] - $adminshare['mapr'];
    $computed_less_admin['mmay'] = $computed_month_net_profit_loss_before_admin_expenses['mmay'] - $adminshare['mmay'];
    $computed_less_admin['mjun'] = $computed_month_net_profit_loss_before_admin_expenses['mjun'] - $adminshare['mjun'];
    $computed_less_admin['mjul'] = $computed_month_net_profit_loss_before_admin_expenses['mjul'] - $adminshare['mjul'];
    $computed_less_admin['maug'] = $computed_month_net_profit_loss_before_admin_expenses['maug'] - $adminshare['maug'];
    $computed_less_admin['msep'] = $computed_month_net_profit_loss_before_admin_expenses['msep'] - $adminshare['msep'];
    $computed_less_admin['moct'] = $computed_month_net_profit_loss_before_admin_expenses['moct'] - $adminshare['moct'];
    $computed_less_admin['mnov'] = $computed_month_net_profit_loss_before_admin_expenses['mnov'] - $adminshare['mnov'];
    $computed_less_admin['mdec'] = $computed_month_net_profit_loss_before_admin_expenses['mdec'] - $adminshare['mdec'];
    $coa[] = array(
      'acno' => '\\776',
      'acnoname' => '<b>Net Profit/Loss</b> ',
      'levelid' => 1,
      'cat' => 'E',
      'parent' => 'X',
      'detail' => 0,
      'monjan' => number_format((float)$computed_less_admin['mjan'], 2, '.', ''),
      'monfeb' => number_format((float)$computed_less_admin['mfeb'], 2, '.', ''),
      'monmar' => number_format((float)$computed_less_admin['mmar'], 2, '.', ''),
      'monapr' => number_format((float)$computed_less_admin['mapr'], 2, '.', ''),
      'monmay' => number_format((float)$computed_less_admin['mmay'], 2, '.', ''),
      'monjun' => number_format((float)$computed_less_admin['mjun'], 2, '.', ''),
      'monjul' => number_format((float)$computed_less_admin['mjul'], 2, '.', ''),
      'monaug' => number_format((float)$computed_less_admin['maug'], 2, '.', ''),
      'monsep' => number_format((float)$computed_less_admin['msep'], 2, '.', ''),
      'monoct' => number_format((float)$computed_less_admin['moct'], 2, '.', ''),
      'monnov' => number_format((float)$computed_less_admin['mnov'], 2, '.', ''),
      'mondec' => number_format((float)$computed_less_admin['mdec'], 2, '.', ''),
      'amt' => 0,
      'total' => number_format((float)$amt1234_lessadmin, 2, '.', '')
    );


    $array = json_decode(json_encode($coa), true); // for clearing set to array
    // $array = $coa; // for clearing set to array
    return $array;
  }

  public function default_query($filters)
  {
    $year = intval($filters['params']['dataparams']['year']);
    $companyid = $filters['params']['companyid'];
    $filter = '';
    $filter1 = '';
    $filter2 = '';

    $year1 = $year;
    $year2 = $year;
    $view = 'MONTHLY';

    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['code'];
    if ($center != '') {
      $filter .= " and cntnum.center = '" . $center . "'";
    }
    if ($costcenter != "") {
      $filter1 = " and head.projectid = '" . $costcenter . "'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $filters['params']['dataparams']['ddeptname'];
      if ($deptid != "") {
        $dept = $filters['params']['dataparams']['deptid'];
        $filter2 = " and head.deptid = $dept";
      }
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as detail,0 as monjan,0 as monfeb,0 as monmar,0 as monapr,0 as monmay,0 as monjun,0 as monjul,0 as monaug,0 as monsep,0 as monoct,0 as monnov,0 as mondec,'$year1' as year";
    $result = $this->coreFunctions->opentable($query2);
    $coa = json_decode(json_encode($result), true); // for convert to array

    $month = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $month2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthE = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthE2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthO = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthO2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $this->PLANTTREE($coa, '\\\\', 'R', $year1, $year2, $view, $month, $month2, $filter, $filter1, $filter2, $companyid);
    $this->PLANTTREE($coa, '\\\\', 'G', $year1, $year2, $view, $month, $month2, $filter, $filter1, $filter2, $companyid);
    $this->PLANTTREE($coa, '\\\\', 'E', $year1, $year2, $view, $monthE, $monthE2, $filter, $filter1, $filter2, $companyid);
    $this->PLANTTREE($coa, '\\\\', 'O', $year1, $year2, $view, $monthO, $monthO2, $filter, $filter1, $filter2, $companyid);

    $coa[] = array('acno' => '//4999', 'acnoname' => 'NET INCOME', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'detail' => 2, 'monjan' => $month2['mjan'] - $monthE2['mjan'] - $monthO2['mjan'], 'monfeb' => $month2['mfeb'] - $monthE2['mfeb'] - $monthO2['mfeb'], 'monmar' => $month2['mmar'] - $monthE2['mmar'] - $monthO2['mmar'], 'monapr' => $month2['mapr'] - $monthE2['mapr'] - $monthO2['mapr'], 'monmay' => $month2['mmay'] - $monthE2['mmay'] - $monthO2['mmay'], 'monjun' => $month2['mjun'] - $monthE2['mjun'] - $monthO2['mjun'], 'monjul' => $month2['mjul'] - $monthE2['mjul'] - $monthO2['mjul'], 'monaug' => $month2['maug'] - $monthE2['maug'] - $monthO2['maug'], 'monsep' => $month2['msep'] - $monthE2['msep'] - $monthO2['msep'], 'monoct' => $month2['moct'] - $monthE2['moct'] - $monthO2['moct'], 'monnov' => $month2['mnov'] - $monthE2['mnov'] - $monthO2['mnov'], 'mondec' => $month2['mdec'] - $monthE2['mdec'] - $monthO2['mdec'], 'yr' => $year1);
    $array = json_decode(json_encode($coa), true);
    return $array;
  }

  public function maxipro_query($filters)
  {
    $year = intval($filters['params']['dataparams']['year']);
    $companyid = $filters['params']['companyid'];
    $filter = '';
    $filter1 = '';
    $filter2 = '';

    $year1 = $year;
    $year2 = $year;
    $view = 'MONTHLY';

    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['code'];
    if ($center != '') {
      $filter .= " and cntnum.center = '" . $center . "'";
    }
    if ($costcenter != "") {
      $filter1 = " and proj.code = '" . $costcenter . "'";
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as detail,0 as monjan,0 as monfeb,0 as monmar,0 as monapr,0 as monmay,0 as monjun,0 as monjul,0 as monaug,0 as monsep,0 as monoct,0 as monnov,0 as mondec,'$year1' as year";
    $result = $this->coreFunctions->opentable($query2);
    $coa = json_decode(json_encode($result), true); // for convert to array

    $month = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $month2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthE = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthE2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthO = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $monthO2 = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);
    $this->MAXIPRO_PLANTTREE($coa, '\\\\', 'R', $year1, $year2, $view, $month, $month2, $filter, $filter1, $filter2, $companyid);
    $this->MAXIPRO_PLANTTREE($coa, '\\\\', 'G', $year1, $year2, $view, $month, $month2, $filter, $filter1, $filter2, $companyid);
    $this->MAXIPRO_PLANTTREE($coa, '\\\\', 'E', $year1, $year2, $view, $monthE, $monthE2, $filter, $filter1, $filter2, $companyid);
    $this->MAXIPRO_PLANTTREE($coa, '\\\\', 'O', $year1, $year2, $view, $monthO, $monthO2, $filter, $filter1, $filter2, $companyid);

    $coa[] = array(
      'acno' => '//4999',
      'acnoname' => 'NET INCOME',
      'levelid' => 1,
      'cat' => 'X',
      'parent' => 'X',
      'detail' => 2,
      'monjan' => $month2['mjan'] - $monthE2['mjan'] - $monthO2['mjan'],
      'monfeb' => $month2['mfeb'] - $monthE2['mfeb'] - $monthO2['mfeb'],
      'monmar' => $month2['mmar'] - $monthE2['mmar'] - $monthO2['mmar'],
      'monapr' => $month2['mapr'] - $monthE2['mapr'] - $monthO2['mapr'],
      'monmay' => $month2['mmay'] - $monthE2['mmay'] - $monthO2['mmay'],
      'monjun' => $month2['mjun'] - $monthE2['mjun'] - $monthO2['mjun'],
      'monjul' => $month2['mjul'] - $monthE2['mjul'] - $monthO2['mjul'],
      'monaug' => $month2['maug'] - $monthE2['maug'] - $monthO2['maug'],
      'monsep' => $month2['msep'] - $monthE2['msep'] - $monthO2['msep'],
      'monoct' => $month2['moct'] - $monthE2['moct'] - $monthO2['moct'],
      'monnov' => $month2['mnov'] - $monthE2['mnov'] - $monthO2['mnov'],
      'mondec' => $month2['mdec'] - $monthE2['mdec'] - $monthO2['mdec'],
      'yr' => $year1
    );
    $array = json_decode(json_encode($coa), true);
    return $array;
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 40: //cdo
        $result = $this->CDO_query($config);
        $reportdata =  $this->CDO_MONTHLY_INCOME_STATEMENT_LAYOUT($config, $result);
        break;
      case 8: //maxipro
        $result = $this->maxipro_query($config);
        $reportdata =  $this->MAXIPRO_MONTHLY_INCOME_STATEMENT_LAYOUT($config, $result);
        break;

      default:
        $result = $this->default_query($config);
        $reportdata =  $this->DEFAULT_MONTHLY_INCOME_STATEMENT_LAYOUT($config, $result);
        break;
    }


    return $reportdata;
  }

  private function MAXIPRO_PLANTTREE(&$a, $acno, $cat, $year1, $year2, $view, &$month, &$month2, $filters, $filter1, $filter2, $companyid)
  {

    $query2 = $this->MAXIPRO_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $filter1, $filter2, $companyid);
    $data = $this->coreFunctions->opentable($query2);
    $result2 = json_decode(json_encode($data), true);
    $oldacno = '';
    $key = '';
    for ($b = 0; $b < count($result2); $b++) {

      switch ($view) {
        case 'MONTHLY':
          if ($oldacno == '' || $oldacno != $result2[$b]['acno']) {
            $a[] = array(
              'acno' => $result2[$b]['acno'],
              'acnoname' => $result2[$b]['acnoname'],
              'levelid' => $result2[$b]['levelid'],
              'cat' => $result2[$b]['cat'],
              'parent' => $result2[$b]['parent'],
              'detail' => $result2[$b]['detail'],
              'monjan' => number_format((float)$result2[$b]['monjan'], 2, '.', ''),
              'monfeb' => number_format((float)$result2[$b]['monfeb'], 2, '.', ''),
              'monmar' => number_format((float)$result2[$b]['monmar'], 2, '.', ''),
              'monapr' => number_format((float)$result2[$b]['monapr'], 2, '.', ''),
              'monmay' => number_format((float)$result2[$b]['monmay'], 2, '.', ''),
              'monjun' => number_format((float)$result2[$b]['monjun'], 2, '.', ''),
              'monjul' => number_format((float)$result2[$b]['monjul'], 2, '.', ''),
              'monaug' => number_format((float)$result2[$b]['monaug'], 2, '.', ''),
              'monsep' => number_format((float)$result2[$b]['monsep'], 2, '.', ''),
              'monoct' => number_format((float)$result2[$b]['monoct'], 2, '.', ''),
              'monnov' => number_format((float)$result2[$b]['monnov'], 2, '.', ''),
              'mondec' => number_format((float)$result2[$b]['mondec'], 2, '.', ''),
              'yr' => $result2[$b]['yr']
            );
            $oldacno = $result2[$b]['acno'];
          } else {
            $key = array_search($result2[$b]['acno'], array_column($a, 'acno'));
            $a[$key]['monjan'] = $a[$key]['monjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
            $a[$key]['monfeb'] = $a[$key]['monfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
            $a[$key]['monmar'] = $a[$key]['monmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
            $a[$key]['monapr'] = $a[$key]['monapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
            $a[$key]['monmay'] = $a[$key]['monmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
            $a[$key]['monjun'] = $a[$key]['monjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
            $a[$key]['monjul'] = $a[$key]['monjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
            $a[$key]['monaug'] = $a[$key]['monaug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
            $a[$key]['monsep'] = $a[$key]['monsep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
            $a[$key]['monoct'] = $a[$key]['monoct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
            $a[$key]['monnov'] = $a[$key]['monnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
            $a[$key]['mondec'] = $a[$key]['mondec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');
          }

          $month['mjan'] = $month['mjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
          $month['mfeb'] = $month['mfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
          $month['mmar'] = $month['mmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
          $month['mapr'] = $month['mapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
          $month['mmay'] = $month['mmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
          $month['mjun'] = $month['mjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
          $month['mjul'] = $month['mjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
          $month['maug'] = $month['maug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
          $month['msep'] = $month['msep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
          $month['moct'] = $month['moct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
          $month['mnov'] = $month['mnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
          $month['mdec'] = $month['mdec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');

          $month2['mjan'] = $month2['mjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
          $month2['mfeb'] = $month2['mfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
          $month2['mmar'] = $month2['mmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
          $month2['mapr'] = $month2['mapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
          $month2['mmay'] = $month2['mmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
          $month2['mjun'] = $month2['mjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
          $month2['mjul'] = $month2['mjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
          $month2['maug'] = $month2['maug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
          $month2['msep'] = $month2['msep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
          $month2['moct'] = $month2['moct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
          $month2['mnov'] = $month2['mnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
          $month2['mdec'] = $month2['mdec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');
          break;

        case '3YEARS':
          if ($oldacno == '' || $oldacno != $result2[$b]['acno']) {
            $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => $result2[$b]['detail'], 'year1' => number_format((float)$result2[$b]['year1'], 2, '.', ''), 'year2' => number_format((float)$result2[$b]['year2'], 2, '.', ''), 'year3' => number_format((float)$result2[$b]['year3'], 2, '.', ''));
            $oldacno = $result2[$b]['acno'];
          } else {
            $key = array_search($result2[$b]['acno'], array_column($a, 'acno'));
            $a[$key]['year1'] = $a[$key]['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
            $a[$key]['year2'] = $a[$key]['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
            $a[$key]['year3'] = $a[$key]['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');
          }
          $month['year1'] = $month['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
          $month['year2'] = $month['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
          $month['year3'] = $month['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');

          $month2['year1'] = $month2['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
          $month2['year2'] = $month2['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
          $month2['year3'] = $month2['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');
          break;
      }

      if ($result2[$b]['detail'] == 0) {
        if ($this->MAXIPRO_PLANTTREE($a, '\\' . $result2[$b]['acno'], $result2[$b]['cat'], $year1, $year2, $view, $month, $month2, $filters, $filter1, $filter2, $companyid)) {
          if ($result2[$b]['levelid'] > 1) {
            switch ($view) {
              case 'MONTHLY':
                $a[] = array(
                  'acno' => $result2[$b]['acno'],
                  'acnoname' =>
                  'TOTAL ' . $result2[$b]['acnoname'],
                  'levelid' => $result2[$b]['levelid'],
                  'cat' => $result2[$b]['cat'],
                  'parent' => $result2[$b]['parent'],
                  'detail' => 2,
                  'monjan' => $month['mjan'],
                  'monfeb' => $month['mfeb'],
                  'monmar' => $month['mmar'],
                  'monapr' => $month['mapr'],
                  'monmay' => $month['mmay'],
                  'monjun' => $month['mjun'],
                  'monjul' => $month['mjul'],
                  'monaug' => $month['maug'],
                  'monsep' => $month['msep'],
                  'monoct' => $month['moct'],
                  'monnov' => $month['mnov'],
                  'mondec' => $month['mdec'],
                  'yr' => $year1
                );
                $month['mjan'] = 0;
                $month['mfeb'] = 0;
                $month['mmar'] = 0;
                $month['mapr'] = 0;
                $month['mmay'] = 0;
                $month['mjun'] = 0;
                $month['mjul'] = 0;
                $month['maug'] = 0;
                $month['msep'] = 0;
                $month['moct'] = 0;
                $month['mnov'] = 0;
                $month['mdec'] = 0;
                break;

              case '3YEARS':
                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'year1' => $month['year1'], 'year2' => $month['year2'], 'year3' => $month['year3']);
                $month['year1'] = 0;
                $month['year2'] = 0;
                $month['year3'] = 0;
                break;
            }
          } else {
            if ($cat == 'C') {
              $C = "('R','G')";
              $loss = $this->DEFAULT_BALANCE_SHEETDUE('CREDIT', $C, $year1, $year2, $view, $filters, $filter1, $filter2);
              $C = "('E','O')";
              $loss2 = $this->DEFAULT_BALANCE_SHEETDUE('DEBIT', $C, $year1, $year2, $view, $filters, $filter1, $filter2);

              $L1 = $loss[0]['year1'] - $loss2[0]['year1'];
              $L2 = $loss[0]['year2'] - $loss2[0]['year2'];
              $L3 = $loss[0]['year3'] - $loss2[0]['year3'];

              $month2['year1'] = $month2['year1'] + number_format((float)$L1, 2, '.', '');
              $month2['year2'] = $month2['year2'] + number_format((float)$L2, 2, '.', '');
              $month2['year3'] = $month2['year3'] + number_format((float)$L3, 2, '.', '');

              $a[] = array('acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET', 'levelid' => $result2[$b]['levelid'] + 1, 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 1, 'year1' => $L1, 'year2' => $L2, 'year3' => $L3);
            }

            switch ($view) {
              case 'MONTHLY':
                $a[] = array(
                  'acno' => $result2[$b]['acno'],
                  'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'],
                  'levelid' => $result2[$b]['levelid'],
                  'cat' => $result2[$b]['cat'],
                  'parent' => $result2[$b]['parent'],
                  'detail' => 2,
                  'monjan' => $month2['mjan'],
                  'monfeb' => $month2['mfeb'],
                  'monmar' => $month2['mmar'],
                  'monapr' => $month2['mapr'],
                  'monmay' => $month2['mmay'],
                  'monjun' => $month2['mjun'],
                  'monjul' => $month2['mjul'],
                  'monaug' => $month2['maug'],
                  'monsep' => $month2['msep'],
                  'monoct' => $month2['moct'],
                  'monnov' => $month2['mnov'],
                  'mondec' => $month2['mdec']
                );
                break;

              case '3YEARS':
                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'year1' => $month2['year1'], 'year2' => $month2['year2'], 'year3' => $month2['year3']);
                break;
            }
          }
        }
      }
    }

    if (count($result2) > 0) {
      return true;
    } else {
      return false;
    }
  } // end fn

  private function PLANTTREE(&$a, $acno, $cat, $year1, $year2, $view, &$month, &$month2, $filters, $filter1, $filter2, $companyid)
  {

    $query2 = $this->DEFAULT_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $filter1, $filter2, $companyid);
    $data = $this->coreFunctions->opentable($query2);
    $result2 = json_decode(json_encode($data), true);
    $oldacno = '';
    $key = '';
    for ($b = 0; $b < count($result2); $b++) {

      switch ($view) {
        case 'MONTHLY':
          if ($oldacno == '' || $oldacno != $result2[$b]['acno']) {
            $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => $result2[$b]['detail'], 'monjan' => number_format((float)$result2[$b]['monjan'], 2, '.', ''), 'monfeb' => number_format((float)$result2[$b]['monfeb'], 2, '.', ''), 'monmar' => number_format((float)$result2[$b]['monmar'], 2, '.', ''), 'monapr' => number_format((float)$result2[$b]['monapr'], 2, '.', ''), 'monmay' => number_format((float)$result2[$b]['monmay'], 2, '.', ''), 'monjun' => number_format((float)$result2[$b]['monjun'], 2, '.', ''), 'monjul' => number_format((float)$result2[$b]['monjul'], 2, '.', ''), 'monaug' => number_format((float)$result2[$b]['monaug'], 2, '.', ''), 'monsep' => number_format((float)$result2[$b]['monsep'], 2, '.', ''), 'monoct' => number_format((float)$result2[$b]['monoct'], 2, '.', ''), 'monnov' => number_format((float)$result2[$b]['monnov'], 2, '.', ''), 'mondec' => number_format((float)$result2[$b]['mondec'], 2, '.', ''), 'yr' => $result2[$b]['yr']);
            $oldacno = $result2[$b]['acno'];
          } else {
            $key = array_search($result2[$b]['acno'], array_column($a, 'acno'));
            $a[$key]['monjan'] = $a[$key]['monjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
            $a[$key]['monfeb'] = $a[$key]['monfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
            $a[$key]['monmar'] = $a[$key]['monmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
            $a[$key]['monapr'] = $a[$key]['monapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
            $a[$key]['monmay'] = $a[$key]['monmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
            $a[$key]['monjun'] = $a[$key]['monjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
            $a[$key]['monjul'] = $a[$key]['monjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
            $a[$key]['monaug'] = $a[$key]['monaug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
            $a[$key]['monsep'] = $a[$key]['monsep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
            $a[$key]['monoct'] = $a[$key]['monoct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
            $a[$key]['monnov'] = $a[$key]['monnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
            $a[$key]['mondec'] = $a[$key]['mondec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');
          }

          $month['mjan'] = $month['mjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
          $month['mfeb'] = $month['mfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
          $month['mmar'] = $month['mmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
          $month['mapr'] = $month['mapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
          $month['mmay'] = $month['mmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
          $month['mjun'] = $month['mjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
          $month['mjul'] = $month['mjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
          $month['maug'] = $month['maug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
          $month['msep'] = $month['msep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
          $month['moct'] = $month['moct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
          $month['mnov'] = $month['mnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
          $month['mdec'] = $month['mdec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');

          $month2['mjan'] = $month2['mjan'] + number_format((float)$result2[$b]['monjan'], 2, '.', '');
          $month2['mfeb'] = $month2['mfeb'] + number_format((float)$result2[$b]['monfeb'], 2, '.', '');
          $month2['mmar'] = $month2['mmar'] + number_format((float)$result2[$b]['monmar'], 2, '.', '');
          $month2['mapr'] = $month2['mapr'] + number_format((float)$result2[$b]['monapr'], 2, '.', '');
          $month2['mmay'] = $month2['mmay'] + number_format((float)$result2[$b]['monmay'], 2, '.', '');
          $month2['mjun'] = $month2['mjun'] + number_format((float)$result2[$b]['monjun'], 2, '.', '');
          $month2['mjul'] = $month2['mjul'] + number_format((float)$result2[$b]['monjul'], 2, '.', '');
          $month2['maug'] = $month2['maug'] + number_format((float)$result2[$b]['monaug'], 2, '.', '');
          $month2['msep'] = $month2['msep'] + number_format((float)$result2[$b]['monsep'], 2, '.', '');
          $month2['moct'] = $month2['moct'] + number_format((float)$result2[$b]['monoct'], 2, '.', '');
          $month2['mnov'] = $month2['mnov'] + number_format((float)$result2[$b]['monnov'], 2, '.', '');
          $month2['mdec'] = $month2['mdec'] + number_format((float)$result2[$b]['mondec'], 2, '.', '');
          break;

        case '3YEARS':
          if ($oldacno == '' || $oldacno != $result2[$b]['acno']) {
            $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => $result2[$b]['detail'], 'year1' => number_format((float)$result2[$b]['year1'], 2, '.', ''), 'year2' => number_format((float)$result2[$b]['year2'], 2, '.', ''), 'year3' => number_format((float)$result2[$b]['year3'], 2, '.', ''));
            $oldacno = $result2[$b]['acno'];
          } else {
            $key = array_search($result2[$b]['acno'], array_column($a, 'acno'));
            $a[$key]['year1'] = $a[$key]['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
            $a[$key]['year2'] = $a[$key]['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
            $a[$key]['year3'] = $a[$key]['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');
          }
          $month['year1'] = $month['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
          $month['year2'] = $month['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
          $month['year3'] = $month['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');

          $month2['year1'] = $month2['year1'] + number_format((float)$result2[$b]['year1'], 2, '.', '');
          $month2['year2'] = $month2['year2'] + number_format((float)$result2[$b]['year2'], 2, '.', '');
          $month2['year3'] = $month2['year3'] + number_format((float)$result2[$b]['year3'], 2, '.', '');
          break;
      }

      if ($result2[$b]['detail'] == 0) {
        if ($this->PLANTTREE($a, '\\' . $result2[$b]['acno'], $result2[$b]['cat'], $year1, $year2, $view, $month, $month2, $filters, $filter1, $filter2, $companyid)) {
          if ($result2[$b]['levelid'] > 1) {
            switch ($view) {
              case 'MONTHLY':
                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'monjan' => $month['mjan'], 'monfeb' => $month['mfeb'], 'monmar' => $month['mmar'], 'monapr' => $month['mapr'], 'monmay' => $month['mmay'], 'monjun' => $month['mjun'], 'monjul' => $month['mjul'], 'monaug' => $month['maug'], 'monsep' => $month['msep'], 'monoct' => $month['moct'], 'monnov' => $month['mnov'], 'mondec' => $month['mdec'], 'yr' => $year1);
                $month['mjan'] = 0;
                $month['mfeb'] = 0;
                $month['mmar'] = 0;
                $month['mapr'] = 0;
                $month['mmay'] = 0;
                $month['mjun'] = 0;
                $month['mjul'] = 0;
                $month['maug'] = 0;
                $month['msep'] = 0;
                $month['moct'] = 0;
                $month['mnov'] = 0;
                $month['mdec'] = 0;
                break;

              case '3YEARS':
                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'year1' => $month['year1'], 'year2' => $month['year2'], 'year3' => $month['year3']);
                $month['year1'] = 0;
                $month['year2'] = 0;
                $month['year3'] = 0;
                break;
            }
          } else {
            if ($cat == 'C') {
              $C = "('R','G')";
              $loss = $this->DEFAULT_BALANCE_SHEETDUE('CREDIT', $C, $year1, $year2, $view, $filters, $filter1, $filter2);
              $C = "('E','O')";
              $loss2 = $this->DEFAULT_BALANCE_SHEETDUE('DEBIT', $C, $year1, $year2, $view, $filters, $filter1, $filter2);

              $L1 = $loss[0]['year1'] - $loss2[0]['year1'];
              $L2 = $loss[0]['year2'] - $loss2[0]['year2'];
              $L3 = $loss[0]['year3'] - $loss2[0]['year3'];

              $month2['year1'] = $month2['year1'] + number_format((float)$L1, 2, '.', '');
              $month2['year2'] = $month2['year2'] + number_format((float)$L2, 2, '.', '');
              $month2['year3'] = $month2['year3'] + number_format((float)$L3, 2, '.', '');

              $a[] = array('acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET', 'levelid' => $result2[$b]['levelid'] + 1, 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 1, 'year1' => $L1, 'year2' => $L2, 'year3' => $L3);
            }

            switch ($view) {
              case 'MONTHLY':
                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'monjan' => $month2['mjan'], 'monfeb' => $month2['mfeb'], 'monmar' => $month2['mmar'], 'monapr' => $month2['mapr'], 'monmay' => $month2['mmay'], 'monjun' => $month2['mjun'], 'monjul' => $month2['mjul'], 'monaug' => $month2['maug'], 'monsep' => $month2['msep'], 'monoct' => $month2['moct'], 'monnov' => $month2['mnov'], 'mondec' => $month2['mdec']);
                break;

              case '3YEARS':
                $a[] = array('acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2, 'year1' => $month2['year1'], 'year2' => $month2['year2'], 'year3' => $month2['year3']);
                break;
            }
          }
        }
      }
    }

    if (count($result2) > 0) {
      return true;
    } else {
      return false;
    }
  } // end fn

  private function DEFAULT_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $filter1, $filter2, $companyid, $defaultfield_filter = '')
  {
    $filters3 = '';
    if ($defaultfield_filter == '') {
      $filters3 = " where coa.parent='$acno' and coa.cat='$cat' "; // default filters
    } else {
      $filters3 = " where coa." . $defaultfield_filter . "='$acno' and coa.cat='$cat' "; // default filters
    }

    $field = '';
    switch ($cat) {
      case 'L':
      case 'R':
      case 'G':
      case 'C':
        $field = ' sum(detail.cr-detail.db) ';
        break;
      default:
        $field = 'sum(detail.db-detail.cr) ';
        break;
    }

    $years = '';
    $months = '';
    if ($companyid == 19) { //housegem
      $years = "year(detail.postdate)";
      $months = "month(detail.postdate)";
    } else {
      $years = "year(head.dateid)";
      $months = "month(head.dateid)";
    }

    $selecthjc = '';

    if ($companyid == 8) { //maxipro
      $selecthjc = " union all select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, 
      $field as amt from hjchead as head 
      left join gldetail as detail on detail.trno=head.trno 
      left join cntnum on cntnum.trno=head.trno 
      where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
      group by detail.acnoid, month(head.dateid), year(head.dateid)";
    }

    switch ($view) {
      case 'MONTHLY':
        $query1 = "select acno, acnoname, levelid, cat, parent, detail,
        ifnull(sum(case when mon=1 then amt else 0 end),0) as monjan,
        ifnull(sum(case when mon=2 then amt else 0 end),0) as monfeb,
        ifnull(sum(case when mon=3 then amt else 0 end),0) as monmar,
        ifnull(sum(case when mon=4 then amt else 0 end),0) as monapr,
        ifnull(sum(case when mon=5 then amt else 0 end),0) as monmay,
        ifnull(sum(case when mon=6 then amt else 0 end),0) as monjun,
        ifnull(sum(case when mon=7 then amt else 0 end),0) as monjul,
        ifnull(sum(case when mon=8 then amt else 0 end),0) as monaug,
        ifnull(sum(case when mon=9 then amt else 0 end),0) as monsep,
        ifnull(sum(case when mon=10 then amt else 0 end),0) as monoct,
        ifnull(sum(case when mon=11 then amt else 0 end),0) as monnov,
        ifnull(sum(case when mon=12 then amt else 0 end),0) as mondec, yr, ifnull(sum(amt),0) as amt
        from (
        select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr, ifnull(sum(tb.amt),0) as amt
        from coa left join (
        select detail.acnoid, $months as mon, $years as yr, 
        $field as amt from glhead as head 
        left join gldetail as detail on detail.trno=head.trno 
        left join cntnum on cntnum.trno=head.trno 
        where  $years between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
        group by detail.acnoid, $months,  $years $selecthjc ) as tb on tb.acnoid=coa.acnoid
        $filters3
        group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr
        ) as inc group by acno, acnoname, levelid, cat, parent, detail, yr";
        break;

      case "3YEARS":
        $query1 = "select acno, acnoname, levelid, cat, parent, detail,
        ifnull(sum(case when yr=$year2-2 then amt else 0 end),0) year1,
        ifnull(sum(case when yr=$year2-1 then amt else 0 end),0) year2,
        ifnull(sum(case when yr=$year2 then amt else 0 end),0) year3, yr, ifnull(sum(amt),0) as amt
        from (
        select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr, round(ifnull(sum(tb.amt),0),2) as amt
        from coa left join (
        select detail.acnoid, $months as mon, $years as yr, $field as amt from glhead as head left join gldetail as detail on detail.trno=head.trno left join cntnum on cntnum.trno=head.trno 
        where $years between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
        group by detail.acnoid, $months,  $years) as tb on tb.acnoid=coa.acnoid
        where coa.parent='$acno' and coa.cat='$cat'
        group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr
        ) as inc group by acno, acnoname, levelid, cat, parent, detail, yr";
        break;
    }
    return $query1;
  } // end fn

  private function MAXIPRO_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $filter1, $filter2, $companyid)
  {
    $field = '';
    switch ($cat) {
      case 'L':
      case 'R':
      case 'G':
      case 'C':
        $field = ' sum(detail.cr-detail.db) ';
        break;
      default:
        $field = 'sum(detail.db-detail.cr) ';
        break;
    }

    $years = '';
    $months = '';
    $years = "year(head.dateid)";
    $months = "month(head.dateid)";

    switch ($view) {
      case 'MONTHLY':
        $query1 = "select acno, acnoname, levelid, cat, detail,ifnull(sum(case when mon=1 then amt else 0 end),0) as monjan,
                          ifnull(sum(case when mon=2 then amt else 0 end),0) as monfeb,ifnull(sum(case when mon=3 then amt else 0 end),0) as monmar,
                          ifnull(sum(case when mon=4 then amt else 0 end),0) as monapr,ifnull(sum(case when mon=5 then amt else 0 end),0) as monmay,
                          ifnull(sum(case when mon=6 then amt else 0 end),0) as monjun,ifnull(sum(case when mon=7 then amt else 0 end),0) as monjul,
                          ifnull(sum(case when mon=8 then amt else 0 end),0) as monaug,ifnull(sum(case when mon=9 then amt else 0 end),0) as monsep,
                          ifnull(sum(case when mon=10 then amt else 0 end),0) as monoct,ifnull(sum(case when mon=11 then amt else 0 end),0) as monnov,
                          ifnull(sum(case when mon=12 then amt else 0 end),0) as mondec,yr, ifnull(sum(amt),0) as amt,
                          incomegrp, (case when incomegrp = '' then parent else inc.parent2 end) as parent
                  from (select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr, 
                               ifnull(sum(tb.amt),0) as amt, coa.incomegrp,(select acno from coa as c where coa.incomegrp=c.acnoname) as parent2
                        from coa 
                        left join (select detail.acnoid, $months as mon, $years as yr, $field as amt 
                                  from glhead as head 
                                  left join gldetail as detail on detail.trno=head.trno 
                                  left join cntnum on cntnum.trno=head.trno 
                                  left join projectmasterfile as proj on proj.line=detail.projectid
                                  where  $years between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
                                  group by detail.acnoid, $months,  $years 
                                  union all 
                                  select detail.acnoid, $months as mon, $years as yr, $field as amt 
                                  from hjchead as head 
                                  left join gldetail as detail on detail.trno=head.trno 
                                  left join cntnum on cntnum.trno=head.trno 
                                  left join projectmasterfile as proj on proj.line=detail.projectid
                                  where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
                                  group by detail.acnoid, $months,$years) as tb on tb.acnoid=coa.acnoid
                        group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr,coa.incomegrp,parent2) as inc 
                        where (case when incomegrp = '' then parent else inc.parent2 end) = '$acno' and cat='$cat'
                  group by acno, acnoname, levelid, cat, detail, yr,incomegrp,inc.parent,parent2
                  ";
        break;

      case "3YEARS":
        $query1 = "select acno, acnoname, levelid, cat, parent, detail,
        ifnull(sum(case when yr=$year2-2 then amt else 0 end),0) year1,
        ifnull(sum(case when yr=$year2-1 then amt else 0 end),0) year2,
        ifnull(sum(case when yr=$year2 then amt else 0 end),0) year3, yr, ifnull(sum(amt),0) as amt
        from (
        select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr, round(ifnull(sum(tb.amt),0),2) as amt
        from coa left join (
        select detail.acnoid, $months as mon, $years as yr, $field as amt from glhead as head left join gldetail as detail on detail.trno=head.trno left join cntnum on cntnum.trno=head.trno 
        where $years between '" . $year1 . "' and '" . $year2 . "' " . $filters . " " . $filter1 . " " . $filter2 . "
        group by detail.acnoid, $months,  $years) as tb on tb.acnoid=coa.acnoid
        where coa.parent='$acno' and coa.cat='$cat'
        group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr
        ) as inc group by acno, acnoname, levelid, cat, parent, detail, yr";
        break;
    }
    return $query1;
  } // end fn

  private function DEFAULT_BALANCE_SHEETDUE($entry, $cat, $year1, $year2, $view, $filters, $filter1, $filter2)
  {

    $field = '';
    switch ($entry) {
      case 'CREDIT':
        $field = ' round(ifnull(sum(detail.cr-detail.db),0),2) ';
        break;
      default:
        $field = ' round(ifnull(sum(detail.db-detail.cr),0),2) ';
        break;
    }

    $years = '';
    $months = '';
    if ($filters['params']['companyid'] == 19) { //housegem
      $years = "year(detail.postdate)";
      $months = "month(detail.postdate)";
    } else {
      $years = "year(head.dateid)";
      $months = "month(head.dateid)";
    }

    switch ($view) {
      case 'MONTHLY':
        $query1 = "select yr,ifnull(sum(case when mon=1 then cr else 0 end),0) as monjan,
      ifnull(sum(case when mon=2 then cr else 0 end),0) as monfeb,
      ifnull(sum(case when mon=3 then cr else 0 end),0) as monmar,
      ifnull(sum(case when mon=4 then cr else 0 end),0) as monapr,
      ifnull(sum(case when mon=5 then cr else 0 end),0) as monmay,
      ifnull(sum(case when mon=6 then cr else 0 end),0) as monjun,
      ifnull(sum(case when mon=7 then cr else 0 end),0) as monjul,
      ifnull(sum(case when mon=8 then cr else 0 end),0) as monaug,
      ifnull(sum(case when mon=9 then cr else 0 end),0) as monsep,
      ifnull(sum(case when mon=10 then cr else 0 end),0) as monoct,
      ifnull(sum(case when mon=11 then cr else 0 end),0) as monnov,
      ifnull(sum(case when mon=12 then cr else 0 end),0) as mondec
      from (
      select $field as cr, $years as yr, $months as mon
      from glhead as head left join gldetail as detail on detail.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid left join cntnum on cntnum.trno=head.trno
      where $years between  '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . " " . $filters . " " . $filter1 . " " . $filter2 . "
      group by $years, $months
      ) as tb group by yr";
        break;

      case '3YEARS':
        $query1 = "select yr,ifnull(sum(case when yr=$year2-2 then cr else 0 end),0) as year1,
      ifnull(sum(case when yr=$year2-1 then cr else 0 end),0) as year2,
      ifnull(sum(case when yr=$year2 then cr else 0 end),0) as year3
      from (
      select $field as cr, $years as yr, $months as mon
      from glhead as head left join gldetail as detail on detail.trno=head.trno
      left join coa on coa.acnoid=detail.acnoid left join cntnum on cntnum.trno=head.trno
      where $years between '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . " " . $filters . " " . $filter1 . " " . $filter2 . "
      group by $years, $months
      ) as tb group by yr";

        break;
    } // end switch

    $data = $this->coreFunctions->opentable($query1);
    $result = json_decode(json_encode($data), true);
    return $result;
  } // end fn

  private function DEFAULT_HEADER($params, $data)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize12 = '12';

    $year = $params['params']['dataparams']['year'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

    $center = $params['params']['dataparams']['center'];
    $costcenter = $params['params']['dataparams']['code'];
    if ($center == '') {
      $center = "ALL";
    }

    switch ($companyid) {
      case 12: //afti usd
      case 10: //afti
        $layoutsize = 800;
        break;

      default:
        $layoutsize = 1480;
        break;
    }


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $params['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $params['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }

      if ($costcenter != "") {
        $costcenter = $params['params']['dataparams']['name'];
      } else {
        $costcenter = "ALL";
      }
    }

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MONTHLY INCOME STATEMENT', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center :  ' . $center, 100, null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project :  ' . $costcenter, '800', null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department :  ' . $deptname, '800', null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year :  ' . $year, 100, null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    return $str;
  } // end fn

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $fontsize12 = '12';
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 12: //afti usd
      case 10: //afti
        $layoutsize = 1000;
        break;

      default:
        $layoutsize = 1480;
        break;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTS', '280', null, false, '1px solid ', 'TB', '', $font, $fontsize12, 'B', '', '4px');
    $str .= $this->reporter->col('JAN', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('FEB', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('MAR', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('APR', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('MAY', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('JUN', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('JUL', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('AUG', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('SEP', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('OCT', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('NOV', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('DEC', '90', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '120', null, false, '1px solid ', 'TB', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function DEFAULT_MONTHLY_INCOME_STATEMENT_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $border = '1px solid';
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $font = 'cambria';
        break;

      default:

        $font = $this->companysetup->getrptfont($params['params']);
        break;
    }

    $fontsize11 = 11;
    $fontsize12 = 12;
    $count = 67;
    $page = 66;
    $this->reporter->linecounter = 0;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_HEADER($params, $data);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

    for ($i = 0; $i < count($data); $i++) {

      $lineTotal = 0;
      $bold = '';

      if ($data[$i]['detail'] == 1 and ($data[$i]['monjan'] == 0 and $data[$i]['monfeb'] == 0 and $data[$i]['monmar'] == 0 and $data[$i]['monapr'] == 0 and $data[$i]['monmay'] == 0 and $data[$i]['monjun'] == 0 and $data[$i]['monjul'] == 0 and $data[$i]['monaug'] == 0 and $data[$i]['monsep'] == 0 and $data[$i]['monoct'] == 0 and $data[$i]['monnov'] == 0 and $data[$i]['mondec'] == 0)) {
      } else {

        if ($data[$i]['acnoname'] != '') {

          $indent = '5' * ($data[$i]['levelid'] * 3);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();

          if ($data[$i]['detail'] == 2) {
            $bold = 'B';
          }
          $str .= $this->reporter->col($data[$i]['acnoname'], '280', null, false, '1px solid ', '', '', $font, $fontsize12, $bold, '', '0px 0px 0px ' . $indent . 'px');

          if ($data[$i]['detail'] != 0) {
            if ($data[$i]['monjan'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font,  $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monjan'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monfeb'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monfeb'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monmar'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monmar'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monapr'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monapr'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monmay'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monmay'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monjun'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monjun'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monjul'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monjul'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monaug'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monaug'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monsep'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monsep'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monoct'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monoct'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monnov'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monnov'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['mondec'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['mondec'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }

            $lineTotal = $data[$i]['monjan'] + $data[$i]['monfeb'] + $data[$i]['monmar'] + $data[$i]['monapr'] + $data[$i]['monmay'] + $data[$i]['monjun'] + $data[$i]['monjul'] + $data[$i]['monaug'] + $data[$i]['monsep'] + $data[$i]['monoct'] + $data[$i]['monnov'] + $data[$i]['mondec'];
            if ($lineTotal == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($lineTotal, 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
          }

          $str .= $this->reporter->endrow();
        }
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_HEADER($params, $data);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function MAXIPRO_MONTHLY_INCOME_STATEMENT_LAYOUT($params, $data)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize11 = 11;
    $fontsize12 = 12;

    $count = 67;
    $page = 66;
    $this->reporter->linecounter = 0;
    $str = '';
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_HEADER($params, $data);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

    for ($i = 0; $i < count($data); $i++) {

      $lineTotal = 0;
      $bold = '';

      if ($data[$i]['detail'] == 1 and ($data[$i]['monjan'] == 0 and $data[$i]['monfeb'] == 0
        and $data[$i]['monmar'] == 0 and $data[$i]['monapr'] == 0 and $data[$i]['monmay'] == 0
        and $data[$i]['monjun'] == 0 and $data[$i]['monjul'] == 0 and $data[$i]['monaug'] == 0
        and $data[$i]['monsep'] == 0 and $data[$i]['monoct'] == 0 and $data[$i]['monnov'] == 0
        and $data[$i]['mondec'] == 0)) {
      } else {
        if ($data[$i]['acnoname'] != '') {
          $indent = '5' * ($data[$i]['levelid'] * 3);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();

          if ($data[$i]['detail'] == 2) {
            $bold = 'B';
          }
          $str .= $this->reporter->col($data[$i]['acnoname'], '280', null, false, '1px solid ', '', '', $font, $fontsize12, $bold, '', '0px 0px 0px ' . $indent . 'px');
          if ($data[$i]['detail'] != 0) {
            if ($data[$i]['monjan'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font,  $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monjan'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monfeb'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monfeb'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monmar'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monmar'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monapr'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monapr'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monmay'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monmay'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monjun'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monjun'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monjul'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monjul'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monaug'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monaug'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monsep'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monsep'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monoct'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monoct'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['monnov'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['monnov'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
            if ($data[$i]['mondec'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['mondec'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }

            $lineTotal = $data[$i]['monjan'] + $data[$i]['monfeb'] + $data[$i]['monmar'] + $data[$i]['monapr'] + $data[$i]['monmay'] + $data[$i]['monjun'] + $data[$i]['monjul'] + $data[$i]['monaug'] + $data[$i]['monsep'] + $data[$i]['monoct'] + $data[$i]['monnov'] + $data[$i]['mondec'];
            if ($lineTotal == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($lineTotal, 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize12, $bold, '', '');
            }
          }

          $str .= $this->reporter->endrow();
        }
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();


        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_HEADER($params, $data);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function CDO_MONTHLY_INCOME_STATEMENT_LAYOUT($filters, $data)
  {
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize10 = '10';
    $fontsize11 = 11;

    $net_profit_loss =

      $admin_expense_share =

      $expenses_purchases =
      $expenses_labor =
      $expenses_other_expense =

      $gross_sales =
      $gross_margin =

      $companyid = $filters['params']['companyid'];
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


    $key_words_total = ['Net', 'TOTAL', 'GROSS'];
    $acno_formula_requirements = ['\\40501', '\\502', '\\402', '\\501', '\\503', '\\504', '\\771', '\\772', '\\775', '\\776'];
    for ($x = 0; $x < count($data); $x++) {


      $is_total = 0;
      foreach ($key_words_total as $key => $keyword) {
        if (strpos($data[$x]['acnoname'], $keyword) >= 0 && strpos($data[$x]['acnoname'], $keyword) != '') {
          $is_total = 1;
        }
      }

      foreach ($acno_formula_requirements as $key => $acno) {


        if (strpos($data[$x]['acno'], $acno) == 0) {
          switch ($data[$x]['acno']) {
            case '\\402': //sales cash mc
              $sales_cash_mc = $data[$x];
              break;
            case '\\40501': //sales credit mc
              $sales_credit_mc = $data[$x];
              break;
            case '\\502': //less cost of sale
              $less_cost_of_sale = $data[$x];
              break;
            case '\\501': //gross margin
              if ($is_total) {
                $expenses_purchases = $data[$x];
              }
              break;
            case '\\503': //gross margin
              if ($is_total) {
                $expenses_labor = $data[$x];
              }
              break;
            case '\\504': //gross margin
              if ($is_total) {
                $expenses_other_expense = $data[$x];
              }
              break;

            case '\\771': //gross sales
              $gross_sales = $data[$x];
              break;
            case '\\772': //gross margin
              $gross_margin = $data[$x];
              break;
            case '\\775': //admin share share
              $admin_expense_share = $data[$x];
              break;
            case '\\776': //net profit loss
              $net_profit_loss = $data[$x];
              break;
          }
        }
      }
    }


    for ($i = 0; $i < count($data); $i++) {
      if (($data[$i]['detail'] == 1 or ($data[$i]['detail'] == 0 and $data[$i]['levelid'] == 0)) and ($data[$i]['monjan'] == 0 and $data[$i]['monfeb'] == 0 and $data[$i]['monmar'] == 0 and $data[$i]['monapr'] == 0 and $data[$i]['monmay'] == 0 and $data[$i]['monjun'] == 0 and $data[$i]['monjul'] == 0 and $data[$i]['monaug'] == 0 and $data[$i]['monsep'] == 0 and $data[$i]['monoct'] == 0 and $data[$i]['monnov'] == 0 and $data[$i]['mondec'] == 0)) {
      } else {
        $str .= $this->reporter->startrow();

        $indent = '5' * ($data[$i]['levelid'] * 3);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acnoname'], '250', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '0px 0px 0px ' . $indent . 'px');

        $is_total = 0;
        foreach ($key_words_total as $key => $keyword) {

          if (strpos($data[$i]['acnoname'], $keyword) >= 0 && strpos($data[$i]['acnoname'], $keyword) != '') {
            $is_total = 1;
          }
        }



        if ($is_total == 1) {
          $str .= $this->reporter->col(isset($data[$i]['monjan']) ? ($data[$i]['monjan'] == 0 ? '-' : number_format((float)$data[$i]['monjan'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monfeb']) ? ($data[$i]['monfeb'] == 0 ? '-' : number_format((float)$data[$i]['monfeb'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monmar']) ? ($data[$i]['monmar'] == 0 ? '-' : number_format((float)$data[$i]['monmar'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monapr']) ? ($data[$i]['monapr'] == 0 ? '-' : number_format((float)$data[$i]['monapr'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monmay']) ? ($data[$i]['monmay'] == 0 ? '-' : number_format((float)$data[$i]['monmay'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monjun']) ? ($data[$i]['monjun'] == 0 ? '-' : number_format((float)$data[$i]['monjun'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monjul']) ? ($data[$i]['monjul'] == 0 ? '-' : number_format((float)$data[$i]['monjul'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monaug']) ? ($data[$i]['monaug'] == 0 ? '-' : number_format((float)$data[$i]['monaug'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monsep']) ? ($data[$i]['monsep'] == 0 ? '-' : number_format((float)$data[$i]['monsep'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monoct']) ? ($data[$i]['monoct'] == 0 ? '-' : number_format((float)$data[$i]['monoct'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monnov']) ? ($data[$i]['monnov'] == 0 ? '-' : number_format((float)$data[$i]['monnov'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['mondec']) ? ($data[$i]['mondec'] == 0 ? '-' : number_format((float)$data[$i]['mondec'], 2)) : '', '70', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['total']) ?  ($data[$i]['total'] == 0 ? '-' : number_format((float)$data[$i]['total'], 2)) : '', '110', null, false, '1px solid ', 'T', 'R', $font, $fontsize10, 'B', '', '0px 0px 0px ');
        } else {

          $str .= $this->reporter->col(isset($data[$i]['monjan']) ? ($data[$i]['monjan'] == 0 ? '-' : number_format((float)$data[$i]['monjan'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monfeb']) ? ($data[$i]['monfeb'] == 0 ? '-' : number_format((float)$data[$i]['monfeb'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monmar']) ? ($data[$i]['monmar'] == 0 ? '-' : number_format((float)$data[$i]['monmar'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monapr']) ? ($data[$i]['monapr'] == 0 ? '-' : number_format((float)$data[$i]['monapr'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monmay']) ? ($data[$i]['monmay'] == 0 ? '-' : number_format((float)$data[$i]['monmay'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monjun']) ? ($data[$i]['monjun'] == 0 ? '-' : number_format((float)$data[$i]['monjun'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monjul']) ? ($data[$i]['monjul'] == 0 ? '-' : number_format((float)$data[$i]['monjul'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monaug']) ? ($data[$i]['monaug'] == 0 ? '-' : number_format((float)$data[$i]['monaug'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monsep']) ? ($data[$i]['monsep'] == 0 ? '-' : number_format((float)$data[$i]['monsep'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monoct']) ? ($data[$i]['monoct'] == 0 ? '-' : number_format((float)$data[$i]['monoct'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['monnov']) ? ($data[$i]['monnov'] == 0 ? '-' : number_format((float)$data[$i]['monnov'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
          $str .= $this->reporter->col(isset($data[$i]['mondec']) ? ($data[$i]['mondec'] == 0 ? '-' : number_format((float)$data[$i]['mondec'], 2)) : '', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

          $str .= $this->reporter->col(isset($data[$i]['total']) ?  ($data[$i]['total'] == 0 ? '-' : number_format((float)$data[$i]['total'], 2)) : '', '110', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px ');
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

        $page = $page + $count;
      }
    } //for

    $percentage_analysis = ['Cost', 'Gross', 'Opex', 'Admin', 'Net'];
    $ini = [
      'monjan' => 0,
      'monfeb' => 0,
      'monmar' => 0,
      'monapr' => 0,
      'monmay' => 0,
      'monjun' => 0,
      'monjul' => 0,
      'monaug' => 0,
      'monsep' => 0,
      'monoct' => 0,
      'monnov' => 0,
      'mondec' => 0,
      'total' => 0
    ];



    $str .= $this->reporter->addline();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Percentage Analysis based on gross revenue', '250', null, false, '1px solid ', '', 'L', $font, $fontsize10, 'B', '', '0px 0px 0px ' . $indent . 'px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

    $str .= $this->reporter->col('', '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

    $str .= $this->reporter->endrow();
    for ($g = 0; $g < count($percentage_analysis); $g++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $computed_fields = $ini;
      switch ($percentage_analysis[$g]) {
        case 'Cost':
          // foreach ($computed_fields as $key => $value) {
          //   if(isset($less_cost_of_sale[$key]) && isset($gross_sales[$key])){
          //     if($less_cost_of_sale[$key]!=0 && $gross_sales[$key]!=0){
          //       $computed_fields[$key] = ($less_cost_of_sale[$key] / $gross_sales[$key])*100;
          //     }
          //   }
          // }
          break;
        case 'Gross':
          $percentage_analysis[$g] = 'Gross Margin';
          foreach ($computed_fields as $key => $value) {
            if (isset($gross_margin[$key]) && isset($gross_sales[$key])) {
              if ($gross_margin[$key] != 0 && $gross_sales[$key] != 0) {
                $computed_fields[$key] = ($gross_margin[$key] / $gross_sales[$key]) * 100;
              }
            }
          }

          break;
        case 'Opex':
          foreach ($computed_fields as $key => $value) {
            if (isset($expenses_purchases[$key]) && isset($expenses_labor[$key]) && isset($expenses_other_expense[$key]) && isset($gross_sales[$key])) {
              if (($expenses_purchases[$key] != 0 || $expenses_labor[$key] != 0 || $expenses_other_expense[$key] != 0) && $gross_sales[$key] != 0) {
                $computed_fields[$key] = (($expenses_purchases[$key] + $expenses_labor[$key] + $expenses_other_expense[$key]) / $gross_sales[$key]) * 100;
              }
            }
          }
          break;
        case 'Admin':
          foreach ($computed_fields as $key => $value) {
            if (isset($admin_expense_share[$key]) && isset($gross_sales[$key])) {
              if ($admin_expense_share[$key] != 0 && $gross_sales[$key] != 0) {
                $computed_fields[$key] = ($admin_expense_share[$key] / $gross_sales[$key]) * 100;
              }
            }
          }
          break;
        case 'Net':
          foreach ($computed_fields as $key => $value) {
            if (isset($net_profit_loss[$key]) && isset($gross_sales[$key])) {
              if ($net_profit_loss[$key] != 0 && $gross_sales[$key] != 0) {
                $computed_fields[$key] = ($net_profit_loss[$key] / $gross_sales[$key]) * 100;
              }
            }
          }
          break;
      }
      $indent = 5 * (2 * 3);

      $str .= $this->reporter->col($percentage_analysis[$g], '250', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '0px 0px 0px ' . $indent . 'px');
      $str .= $this->reporter->col(($computed_fields['monjan'] == 0 ? '-' : number_format((float)$computed_fields['monjan'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monfeb'] == 0 ? '-' : number_format((float)$computed_fields['monfeb'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monmar'] == 0 ? '-' : number_format((float)$computed_fields['monmar'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monapr'] == 0 ? '-' : number_format((float)$computed_fields['monapr'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

      $str .= $this->reporter->col(($computed_fields['monmay'] == 0 ? '-' : number_format((float)$computed_fields['monmay'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monjun'] == 0 ? '-' : number_format((float)$computed_fields['monjun'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monjul'] == 0 ? '-' : number_format((float)$computed_fields['monjul'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monaug'] == 0 ? '-' : number_format((float)$computed_fields['monaug'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

      $str .= $this->reporter->col(($computed_fields['monsep'] == 0 ? '-' : number_format((float)$computed_fields['monsep'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monoct'] == 0 ? '-' : number_format((float)$computed_fields['monoct'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['monnov'] == 0 ? '-' : number_format((float)$computed_fields['monnov'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');
      $str .= $this->reporter->col(($computed_fields['mondec'] == 0 ? '-' : number_format((float)$computed_fields['mondec'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');

      $str .= $this->reporter->col(($computed_fields['total'] == 0 ? '-' : number_format((float)$computed_fields['total'], 2) . '%'), '70', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '0px 0px 0px');


      $str .= $this->reporter->endrow();
    }



    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //CDO_Layout

  private function CDO_table_cols($layoutsize, $border, $font, $fontsize, $config, $center)
  {
    $str = '';
    $fontsize10 = '10';
    $fontsize12 = '12';
    $companyid = $config['params']['companyid'];
    $year = intval($config['params']['dataparams']['year']);
    $costcenter = '';

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('INCOME STATEMENT', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year: ' . $year, null, null, false, '1px solid ', '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Center:' . $center, null, null, false, '1px solid ', '', '', $font, $fontsize10, '', '', '');
    if ($config['params']['dataparams']['costcenter'] == "") {
      $costcenter = "ALL";
    } else {
      $costcenter = isset($config['params']['dataparams']['centername']) ? $config['params']['dataparams']['centername'] : '';
    }
    $str .= $this->reporter->col('Cost Center:' . $costcenter, null, null, false, '1px solid ', '', '', $font, $fontsize10, '', '', '');


    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTS', '250', null, false, '1px solid ', 'B', '', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('JAN', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('FEB', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('MAR', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('APR', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('MAY', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('JUN', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('JUL', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('AUG', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('SEP', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('OCT', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('NOV', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('DEC', '70', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '110', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function CDO_HEADER($filters, $center)
  {
    $font = $this->companysetup->getrptfont($filters['params']);
    $font_size = '10';

    $center1 = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $filters);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br/><br/>";

    return $str;
  } //CDO HEADER

  private function INCOME_STATEMENT_INNER_QUERY($cat, $acno, $year, $year2, $center, $cc, $filter, $company, $defaultfield_filter = '')
  {
    $field = '';

    if ($defaultfield_filter == '') {
      $filters = " where coa.parent='$acno' and coa.cat='$cat' "; // default filters
    } else {
      $filters = " where coa." . $defaultfield_filter . "='$acno' and coa.cat='$cat' "; // default filters
    }

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = " ifnull(sum(round(iq.cr-iq.db,2)),0) ";
        break;
      case 'G':

        $field = " ifnull(sum(round(iq.cr-iq.db,2)),0) ";
        break;
      default:

        $field = " ifnull(sum(round(iq.db-iq.cr,2)),0) ";


        break;
    } //end swtich

    $orderby = " order by tb.levelid asc, tb.acno,tb.acnoname asc ";

    $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail ,  sum(tb.amt) as amt, tb.isshow, tb.iscompute, tb.isparenttotal,
        ifnull(sum(case when mon=1 then amt else 0 end),0) as monjan,
        ifnull(sum(case when mon=2 then amt else 0 end),0) as monfeb,
        ifnull(sum(case when mon=3 then amt else 0 end),0) as monmar,
        ifnull(sum(case when mon=4 then amt else 0 end),0) as monapr,
        ifnull(sum(case when mon=5 then amt else 0 end),0) as monmay,
        ifnull(sum(case when mon=6 then amt else 0 end),0) as monjun,
        ifnull(sum(case when mon=7 then amt else 0 end),0) as monjul,
        ifnull(sum(case when mon=8 then amt else 0 end),0) as monaug,
        ifnull(sum(case when mon=9 then amt else 0 end),0) as monsep,
        ifnull(sum(case when mon=10 then amt else 0 end),0) as monoct,
        ifnull(sum(case when mon=11 then amt else 0 end),0) as monnov,
        ifnull(sum(case when mon=12 then amt else 0 end),0) as mondec
        from (

          select 
          iq.yr, iq.mon,
          coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,
          coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal, $field as amt
          from coa 
          left join (
          select detail.acnoid,head.dateid, year(head.dateid) as yr, month(head.dateid) as mon,  
          detail.db,detail.cr
          from glhead as head
          left join gldetail as detail on detail.trno=head.trno
          left join cntnum on cntnum.trno=head.trno 
          where year(head.dateid) = '$year' 
          ) as iq on iq.acnoid=coa.acnoid
          " . $filters . " 
          group by iq.yr, iq.mon,
          coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,
          coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal

          union all

          select 
          iq.yr, iq.mon,
          coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,
          coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal, $field as amt
          from coa 
          left join (
          select detail.acnoid,head.dateid, year(head.dateid) as yr, month(head.dateid) as mon,  
          detail.db,detail.cr
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno
          left join cntnum on cntnum.trno=head.trno 
          where year(head.dateid) = '$year' 
          ) as iq on iq.acnoid=coa.acnoid
          " . $filters . " 
          group by iq.yr, iq.mon,
          coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,
          coa.detail, coa.isshow, coa.iscompute, coa.isparenttotal 
        ) as  tb
        group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias, tb.isshow, tb.iscompute, tb.isparenttotal
        $orderby ";

    return $query1;
  } // DEFAULT BALANCE SHEET

  private function INCOME_STATEMENT_GET_SUM($entry, $cat, $year, $year2, $center, $company, $filter)
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
        if ($filter != 0) {
          $filters .= " and head.projectid = '" . $filter . "' "; // cost center filter
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

    $selectjc = '';
    $query1 = $query1 . " (
        select $field as cr from lahead as head 
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        where year(head.dateid) = '$year' and coa.cat in " . $cat . " " . $filters . " $selectjc
        union all 
        select $field as cr from glhead as head 
        left join gldetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        where year(head.dateid) = '$year' and coa.cat in " . $cat . " " . $filters . " $selectjc
        ) as tb ";


    $result = $this->coreFunctions->opentable($query1);
    $result = $result[0]->amt;
    return $result;
  } // DEFAULT BALANCE SHEETDUE

  private function CDO_PLANTTREE(&$a, $acno, $cat, &$amt1, &$amt9, $z, $year, $year2, $center, $cc, $filter, $company, $addtionalparams, $chkgrp, &$amtgrp, &$prev_incomegrp2, $label, &$month1, &$month2)
  {
    $z = $z + 1;
    $amt = 0;
    $amt2 = 0;
    $prevamt9 = 0;
    $oldacno = '';
    $prevmonth = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);

    $defaultfield_filter = '';
    if (isset($addtionalparams['defaultfield_filter'])) {
      $defaultfield_filter = $addtionalparams['defaultfield_filter'];
    }

    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $year, $year2, $center, $cc, $filter, $company, $defaultfield_filter);

    $result2 = $this->coreFunctions->opentable($query2);

    $prev_incomegrp = $prev_incomegrp2;
    $prev_incomegrp_amt = $amtgrp;

    $counter = 1;

    foreach ($result2 as $key => $value) {



      //A LCR E
      //this is what loops lowest level acno and value
      if (isset($addtionalparams['complex_acno'])) { //has complex back and forth display of a single category
        if ($addtionalparams['inex_clude'] == 0) { //0 to exclude, 1 to include

          if (array_search($value->acno, $addtionalparams['complex_acno']) == false) { //if false, returns all revenue except the ones in array
            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => $value->levelid == 1 ? '<b>' . ($value->acnoname == 'Expenses' ? 'LESS OPERATING EXPENSES: ' : $value->acnoname) . '</b>' : $value->acnoname,
              'levelid' => $value->levelid,
              'cat' => $value->cat,
              'parent' => $value->parent,
              'detail' => $value->detail,
              'alias' => '',
              'isshow' => $value->isshow,
              'monjan' => number_format((float)$value->monjan, 2, '.', ''),
              'monfeb' => number_format((float)$value->monfeb, 2, '.', ''),
              'monmar' => number_format((float)$value->monmar, 2, '.', ''),
              'monapr' => number_format((float)$value->monapr, 2, '.', ''),
              'monmay' => number_format((float)$value->monmay, 2, '.', ''),
              'monjun' => number_format((float)$value->monjun, 2, '.', ''),
              'monjul' => number_format((float)$value->monjul, 2, '.', ''),
              'monaug' => number_format((float)$value->monaug, 2, '.', ''),
              'monsep' => number_format((float)$value->monsep, 2, '.', ''),
              'monoct' => number_format((float)$value->monoct, 2, '.', ''),
              'monnov' => number_format((float)$value->monnov, 2, '.', ''),
              'mondec' => number_format((float)$value->mondec, 2, '.', ''),
              'amt' => $value->amt,
              'total' => $value->amt
            );

            $oldacno = $value->acno;

            $prev_incomegrp_amt += $value->amt;

            $prevamt9 = $amt9;
            $amt = $amt + $value->amt;
            $amt1 = $amt1 + $amt;
            $amt9 = $amt9 + $value->amt;
            $amt = 0;

            //previous amt9
            $prevmonth['mjan'] = $month2['mjan'];
            $prevmonth['mfeb'] = $month2['mfeb'];
            $prevmonth['mmar'] = $month2['mmar'];
            $prevmonth['mapr'] = $month2['mapr'];

            $prevmonth['mmay'] = $month2['mmay'];
            $prevmonth['mjun'] = $month2['mjun'];
            $prevmonth['mjul'] = $month2['mjul'];
            $prevmonth['maug'] = $month2['maug'];

            $prevmonth['msep'] = $month2['msep'];
            $prevmonth['moct'] = $month2['moct'];
            $prevmonth['mnov'] = $month2['mnov'];
            $prevmonth['mdec'] = $month2['mdec'];

            //current amt1 
            $month1['mjan'] = $month1['mjan'] + $value->monjan;
            $month1['mfeb'] = $month1['mfeb'] + $value->monfeb;
            $month1['mmar'] = $month1['mmar'] + $value->monmar;
            $month1['mapr'] = $month1['mapr'] + $value->monapr;

            $month1['mmay'] = $month1['mmay'] + $value->monmay;
            $month1['mjun'] = $month1['mjun'] + $value->monjun;
            $month1['mjul'] = $month1['mjul'] + $value->monjul;
            $month1['maug'] = $month1['maug'] + $value->monaug;

            $month1['msep'] = $month1['msep'] + $value->monsep;
            $month1['moct'] = $month1['moct'] + $value->monoct;
            $month1['mnov'] = $month1['mnov'] + $value->monnov;
            $month1['mdec'] = $month1['mdec'] + $value->mondec;

            //current amt9
            $month2['mjan'] = $month2['mjan'] + $value->monjan;
            $month2['mfeb'] = $month2['mfeb'] + $value->monfeb;
            $month2['mmar'] = $month2['mmar'] + $value->monmar;
            $month2['mapr'] = $month2['mapr'] + $value->monapr;

            $month2['mmay'] = $month2['mmay'] + $value->monmay;
            $month2['mjun'] = $month2['mjun'] + $value->monjun;
            $month2['mjul'] = $month2['mjul'] + $value->monjul;
            $month2['maug'] = $month2['maug'] + $value->monaug;

            $month2['msep'] = $month2['msep'] + $value->monsep;
            $month2['moct'] = $month2['moct'] + $value->monoct;
            $month2['mnov'] = $month2['mnov'] + $value->monnov;
            $month2['mdec'] = $month2['mdec'] + $value->mondec;
          }
        } else {
          if (array_search($value->acno, $addtionalparams['complex_acno']) != false) { //if not false, returns all revenue inside the array only
            $a[] = array(
              'acno' => $value->acno,
              'acnoname' => $value->levelid == 1 ? '<b>' . $value->acnoname . '</b>' : $value->acnoname,
              'levelid' => $value->levelid,
              'cat' => $value->cat,
              'parent' => $value->parent,
              'amt' => $value->amt,
              'detail' => $value->detail,
              'total' => $value->amt,
              'alias' => '',
              'isshow' => $value->isshow
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
          'acno' => $value->acno,
          'acnoname' => $value->levelid == 1 ? '<b>' . ($value->acnoname == 'Expenses' ? 'LESS OPERATING EXPENSES: ' : $value->acnoname) . '</b>' : $value->acnoname,
          'levelid' => $value->levelid,
          'cat' => $value->cat,
          'parent' => $value->parent,
          'amt' => $value->amt,
          'detail' => $value->detail,
          'total' => $value->amt,
          'alias' => '',
          'isshow' => $value->isshow
        );

        $prev_incomegrp_amt += $value->amt;

        $prevamt9 = $amt9;
        $amt = $amt + $value->amt;
        $amt1 = $amt1 + $amt;
        $amt9 = $amt9 + $value->amt;
        $amt = 0;
      }


      if ($value->detail == 0) {
        if ($this->CDO_PLANTTREE($a, '\\' . $value->acno, $value->cat, $amt, $amt9, $z, $year, $year2, $center, $cc, $filter, $company, $addtionalparams, $chkgrp, $prev_incomegrp_amt, $value->incomegrp, $label, $month1, $month2)) {
          if ($value->levelid > 1) {
            if ($value->levelid == 2) { //for titles
              $level2amt = $amt9 - $prevamt9;
              $level2jan = $month2['mjan'] - $prevmonth['mjan'];
              $level2feb = $month2['mfeb'] - $prevmonth['mfeb'];
              $level2mar = $month2['mmar'] - $prevmonth['mmar'];
              $level2apr = $month2['mapr'] - $prevmonth['mapr'];

              $level2may = $month2['mmay'] - $prevmonth['mmay'];
              $level2jun = $month2['mjun'] - $prevmonth['mjun'];
              $level2jul = $month2['mjul'] - $prevmonth['mjul'];
              $level2aug = $month2['maug'] - $prevmonth['maug'];

              $level2sep = $month2['msep'] - $prevmonth['msep'];
              $level2oct = $month2['moct'] - $prevmonth['moct'];
              $level2nov = $month2['mnov'] - $prevmonth['mnov'];
              $level2dec = $month2['mdec'] - $prevmonth['mdec'];


              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno,
                'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid,
                'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => 0,
                'detail' => $value->detail,
                'total' => $level2amt,
                'alias' => $value->alias,
                'isshow' => $value->isshow, // original 'amt' => $amt2 , 'total' => $level2amt
                'monjan' => number_format((float)$level2jan, 2, '.', ''),
                'monfeb' => number_format((float)$level2feb, 2, '.', ''),
                'monmar' => number_format((float)$level2mar, 2, '.', ''),
                'monapr' => number_format((float)$level2apr, 2, '.', ''),
                'monmay' => number_format((float)$level2may, 2, '.', ''),
                'monjun' => number_format((float)$level2jun, 2, '.', ''),
                'monjul' => number_format((float)$level2jul, 2, '.', ''),
                'monaug' => number_format((float)$level2aug, 2, '.', ''),
                'monsep' => number_format((float)$level2sep, 2, '.', ''),
                'monoct' => number_format((float)$level2oct, 2, '.', ''),
                'monnov' => number_format((float)$level2nov, 2, '.', ''),
                'mondec' => number_format((float)$level2dec, 2, '.', ''),
                'amt' => 0,
                'total' => $level2amt
              );

              $prev_incomegrp_amt += $level2amt;
            } else { //levelid 3 or more is detail or the lowest(with value)
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno,
                'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                'levelid' => $value->levelid,
                'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => 0,
                'detail' => $value->detail,
                'total' => $amt,
                'alias' => $value->alias,
                'isshow' => $value->isshow // original 'amt' => $amt2, 'total' => $amt
              );
              $prev_incomegrp_amt += $amt;
            } //end if
          } else { //if levelid less than 1 or equal to 1// this is what totals for A LCR E
            if ($cat == 'C') {
              $loss = 0;
              $C = "('R','G')";
              $loss = $this->INCOME_STATEMENT_GET_SUM('CREDIT', $C, $year, $year, $center, $company, $filter);
              $C = "('E','O')";
              $loss = $loss - $this->INCOME_STATEMENT_GET_SUM('DEBIT', $C, $year, $year, $center, $company, $filter);
              $amt9 = $amt9 + $loss;
              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => '\3999',
                'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET',
                'levelid' => $value->levelid + 1,
                'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => $loss,
                'detail' => $value->detail,
                'total' => $loss,
                'alias' => $value->alias,
                'isshow' => $value->isshow
              );
            } //end if

            //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
            // $parentname = 'NET ' . $value->acnoname;
            // $totalamt9 = $amt9;
            // if ($label != "") {
            //   $parentname = $label;
            //   $totalamt9 = $amt9 - $prevamt9;
            // }
            // $a[] = array(
            //   'acno' => $value->acno,
            //   'acnoname' => '<b>' . strtoupper($parentname) . '</b>',
            //   'levelid' => $value->levelid, 'cat' => $value->cat,
            //   'parent' => $value->parent,
            //   'amt' => $amt2, 'detail' => $value->detail, 'total' => $totalamt9, 'alias' => $value->alias, 'isshow' => $value->isshow
            // );
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
                'levelid' => $value->levelid,
                'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => $prev_incomegrp_amt,
                'detail' => $value->detail,
                'total' => 0,
                'alias' => $value->alias,
                'isshow' => $value->isshow
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
  private function CDO_GET_BRANCH(
    &$a,
    $acno,
    $cat,
    &$amt1,
    &$amt9,
    $z,
    $year,
    $year2,
    $center,
    $cc,
    $filter,
    $company,
    $addtionalparams,
    &$month1,
    &$month2
  ) {
    $z = $z + 1;
    $amt = 0;
    $amt2 = 0;
    $prevamt9 = 0;
    $prevmonth = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);

    $defaultfield_filter = '';
    if (isset($addtionalparams['defaultfield_filter'])) {
      $defaultfield_filter = $addtionalparams['defaultfield_filter'];
    }

    $query2 = $this->INCOME_STATEMENT_INNER_QUERY($cat, $acno, $year, $year2, $center, $cc, $filter, $company, $defaultfield_filter);
    $result2 = $this->coreFunctions->opentable($query2);

    foreach ($result2 as $key => $value) {

      $a[] = array(
        'acno' => $value->acno,

        'acnoname' => ($value->acnoname == 'Cost Of Sale ' ? 'LESS : COST OF SALES ' : $value->acnoname),
        'levelid' => $value->levelid,
        'cat' => $value->cat,
        'parent' => $value->parent,
        'alias' => '',
        'isshow' => $value->isshow,
        'detail' => $value->detail,
        'monjan' => number_format((float)$value->monjan, 2, '.', ''),
        'monfeb' => number_format((float)$value->monfeb, 2, '.', ''),
        'monmar' => number_format((float)$value->monmar, 2, '.', ''),
        'monapr' => number_format((float)$value->monapr, 2, '.', ''),
        'monmay' => number_format((float)$value->monmay, 2, '.', ''),
        'monjun' => number_format((float)$value->monjun, 2, '.', ''),
        'monjul' => number_format((float)$value->monjul, 2, '.', ''),
        'monaug' => number_format((float)$value->monaug, 2, '.', ''),
        'monsep' => number_format((float)$value->monsep, 2, '.', ''),
        'monoct' => number_format((float)$value->monoct, 2, '.', ''),
        'monnov' => number_format((float)$value->monnov, 2, '.', ''),
        'mondec' => number_format((float)$value->mondec, 2, '.', ''),
        'amt' => $value->amt,
        'total' => $value->amt
      );

      $prevamt9 = $amt9;
      $amt = $amt + $value->amt;
      $amt1 = $amt1 + $amt;
      $amt9 = $amt9 + $value->amt;
      $amt = 0;

      //current amt1 
      $month1['mjan'] = $month1['mjan'] + $value->monjan;
      $month1['mfeb'] = $month1['mfeb'] + $value->monfeb;
      $month1['mmar'] = $month1['mmar'] + $value->monmar;
      $month1['mapr'] = $month1['mapr'] + $value->monapr;

      $month1['mmay'] = $month1['mmay'] + $value->monmay;
      $month1['mjun'] = $month1['mjun'] + $value->monjun;
      $month1['mjul'] = $month1['mjul'] + $value->monjul;
      $month1['maug'] = $month1['maug'] + $value->monaug;

      $month1['msep'] = $month1['msep'] + $value->monsep;
      $month1['moct'] = $month1['moct'] + $value->monoct;
      $month1['mnov'] = $month1['mnov'] + $value->monnov;
      $month1['mdec'] = $month1['mdec'] + $value->mondec;

      //current amt9
      $month2['mjan'] = $month2['mjan'] + $value->monjan;
      $month2['mfeb'] = $month2['mfeb'] + $value->monfeb;
      $month2['mmar'] = $month2['mmar'] + $value->monmar;
      $month2['mapr'] = $month2['mapr'] + $value->monapr;

      $month2['mmay'] = $month2['mmay'] + $value->monmay;
      $month2['mjun'] = $month2['mjun'] + $value->monjun;
      $month2['mjul'] = $month2['mjul'] + $value->monjul;
      $month2['maug'] = $month2['maug'] + $value->monaug;

      $month2['msep'] = $month2['msep'] + $value->monsep;
      $month2['moct'] = $month2['moct'] + $value->monoct;
      $month2['mnov'] = $month2['mnov'] + $value->monnov;
      $month2['mdec'] = $month2['mdec'] + $value->mondec;
    }
  } //CDO_Plantree

  private function cdogetadminshare($filters, $brgross)
  {
    $company = $filters['params']['companyid'];
    $isposted = 2;
    $year = intval($filters['params']['dataparams']['year']);
    $center = $filters['params']['dataparams']['center'];
    $cc = '';
    $filter = '';

    $share_month = array('mjan' => 0, 'mfeb' => 0, 'mmar' => 0, 'mapr' => 0, 'mmay' => 0, 'mjun' => 0, 'mjul' => 0, 'maug' => 0, 'msep' => 0, 'moct' => 0, 'mnov' => 0, 'mdec' => 0);

    $adminshare = $share_month;


    $tgross = 0;
    $tadminopex = 0;

    if ($isposted == 2) {

      $tgross = $this->coreFunctions->opentable("
      select  ifnull(sum(case when mon=1 then amt else 0 end),0) as mjan,
      ifnull(sum(case when mon=2 then amt else 0 end),0) as mfeb,
      ifnull(sum(case when mon=3 then amt else 0 end),0) as mmar,
      ifnull(sum(case when mon=4 then amt else 0 end),0) as mapr,
      ifnull(sum(case when mon=5 then amt else 0 end),0) as mmay,
      ifnull(sum(case when mon=6 then amt else 0 end),0) as mjun,
      ifnull(sum(case when mon=7 then amt else 0 end),0) as mjul,
      ifnull(sum(case when mon=8 then amt else 0 end),0) as maug,
      ifnull(sum(case when mon=9 then amt else 0 end),0) as msep,
      ifnull(sum(case when mon=10 then amt else 0 end),0) as moct,
      ifnull(sum(case when mon=11 then amt else 0 end),0) as mnov,
      ifnull(sum(case when mon=12 then amt else 0 end),0) as mdec,sum(amt) as amt from ( 
        select year(head.dateid) as yr,month(head.dateid) as mon,head.dateid,cr-db as amt from ladetail as detail left join lahead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.parent in ('\\\\401','\\\\405') 
        and year(head.dateid) = '$year'
        union all
        select year(head.dateid) as yr,month(head.dateid) as mon,head.dateid,cr-db as amt from gldetail as detail left join glhead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.parent in ('\\\\401','\\\\405') 
        and year(head.dateid) = '$year'
        
        union all
        select year(head.dateid) as yr,month(head.dateid) as mon,head.dateid,cr-db as amt from ladetail as detail left join lahead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.parent in ('\\\\4') and coa.acno not like '%\\\\406%' and coa.acno not in ('\\\\401','\\\\405')
        and year(head.dateid) = '$year'
        union all
        select year(head.dateid) as yr,month(head.dateid) as mon,head.dateid,cr-db as amt from gldetail as detail left join glhead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.parent in ('\\\\4') and coa.acno not like '%\\\\406%' and coa.acno not in ('\\\\401','\\\\405')
        and year(head.dateid) = '$year'

        union all
        select year(head.dateid) as yr,month(head.dateid) as mon,head.dateid,cr-db as amt from ladetail as detail left join lahead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.acno in ('\\\\406') and year(head.dateid) = '$year'
        union all
        select year(head.dateid) as yr,month(head.dateid) as mon,head.dateid,cr-db as amt from gldetail as detail left join glhead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid where coa.cat ='R' and coa.acno in ('\\\\406') and year(head.dateid) = '$year'
      ) as a
      group by yr
      
      ");


      $tadminopex = $this->coreFunctions->opentable("
      select 
      ifnull(sum(case when mon=1 then amt else 0 end),0) as mjan,
      ifnull(sum(case when mon=2 then amt else 0 end),0) as mfeb,
      ifnull(sum(case when mon=3 then amt else 0 end),0) as mmar,
      ifnull(sum(case when mon=4 then amt else 0 end),0) as mapr,
      ifnull(sum(case when mon=5 then amt else 0 end),0) as mmay,
      ifnull(sum(case when mon=6 then amt else 0 end),0) as mjun,
      ifnull(sum(case when mon=7 then amt else 0 end),0) as mjul,
      ifnull(sum(case when mon=8 then amt else 0 end),0) as maug,
      ifnull(sum(case when mon=9 then amt else 0 end),0) as msep,
      ifnull(sum(case when mon=10 then amt else 0 end),0) as moct,
      ifnull(sum(case when mon=11 then amt else 0 end),0) as mnov,
      ifnull(sum(case when mon=12 then amt else 0 end),0) as mdec,
      yr,sum(amt) as amt from ( 
        select year(head.dateid) as yr,month(head.dateid) as mon,db-cr as amt from ladetail as detail left join lahead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid left join cntnum on cntnum.trno = head.trno 
        where cntnum.center ='001' and coa.cat ='E' and coa.parent ='\\\\504' and year(head.dateid) = '$year'
        union all
        select year(head.dateid) as yr,month(head.dateid) as mon,db-cr as amt from gldetail as detail left join glhead as head on head.trno = detail.trno 
        left join coa on coa.acnoid = detail.acnoid left join cntnum on cntnum.trno = head.trno 
        where cntnum.center ='001' and coa.cat ='E' and coa.parent ='\\\\504' and year(head.dateid) = '$year'
      ) as a
      group by yr
      
      ");
    }

    $branchrev = $brgross;

    foreach ($tgross as $key => $value) {
      foreach ($value as $keymonth => $amt) {
        if ($keymonth != 'yr' && $keymonth != 'amt') {
          if ($amt <> 0) {
            $share_month[$keymonth] = $branchrev[$keymonth] / $amt;
          }
        }
      }
    }

    foreach ($tadminopex as $key => $value) {
      foreach ($value as $keymonth_admin => $amt_admin) {
        if ($keymonth_admin != 'yr' && $keymonth_admin != 'amt') {
          $adminshare[$keymonth_admin] =
            $amt_admin *
            $share_month[$keymonth_admin];
        }
      }
    }

    if (isset(json_decode(json_encode($tadminopex), true)[0]['amt'])) {
      $tadminopex_amt = json_decode(json_encode($tadminopex), true)[0]['amt'];
    } else {
      $tadminopex_amt = 0;
    }

    if (isset(json_decode(json_encode($tgross), true)[0]['amt'])) {
      $tgross_amt = json_decode(json_encode($tgross), true)[0]['amt'];
    } else {
      $tgross_amt = 0;
    }


    $branchrev_amt = array_sum($brgross);
    $share_amt = 0;


    if ($tgross_amt <> 0) {
      $share_amt = $branchrev_amt / $tgross_amt;
    } else {
      $share_amt = 0;
    }

    $adminshare['amt'] = $tadminopex_amt * $share_amt;

    return $adminshare;
  }
}//end class