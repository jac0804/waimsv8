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

class comparative_income_statement
{
  public $modulename = 'Comparative Income Statement';
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

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['dbranchname', 'costcenter', 'year', 'month', 'year2', 'month2'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'costcenter.label', 'Item Group');
        break;
      default:
        $fields = ['dcentername', 'costcenter', 'year'];
        $col2 = $this->fieldClass->create($fields);
        break;
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,left(now(),10) as due,left(now(),4) as year ,
                          '" . $defaultcenter[0]['center'] . "' as center,'' as code,'' as name,
                          '" . $defaultcenter[0]['centername'] . "' as centername,
                          '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                          '' as costcenter ";
        break;
      case 10: //afti
      case 12: //afti usd
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as dateid,
        left(now(),10) as due,
        '' as branch,
        '' as branchname,
        '' as branchcode,
        '' as dbranchname,
        left(now(),4) as year,left(now(),4) as year2,
        month(now()) as month,month(now()) as month2,
        '' as code,
        '' as name,
        '' as costcenter
        ";
        break;
      default:
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,left(now(),10) as due,left(now(),4) as year ,'' as center,'' as code,'' as name,'' as centername,'' as dcentername,'' as costcenter ";
        break;
    }


    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportplotting($config)
  {
    $company = $config['params']['companyid'];

    if ($company == 10 || $company == 12) { //afti, afti usd
      $result = $this->aftech_default_query($config, $company);
      $reportdata =  $this->AFTECH_DEFAULT_COMPARATIVE_INCOME_STATEMENT_LAYOUT($config, $result);
    } else {
      $result = $this->default_query($config);
      $reportdata =  $this->DEFAULT_COMPARATIVE_INCOME_STATEMENT_LAYOUT($config, $result);
    }

    return $reportdata;
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }
  //QRY START
  public function default_query($filters)
  {
    $company = $filters['params']['companyid'];
    $year = intval($filters['params']['dataparams']['year']);
    $filter = '';
    $params = $filters['params']['dataparams'];

    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['code'];
    if ($center != '') {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($costcenter != "") {
      if ($company == 8) { //maxipro
        $filter .= " and proj.code = '" . $costcenter . "'";
      } else {
        $filter .= " and head.projectid = '" . $costcenter . "'";
      }
    }

    $year1 = $year - 2;
    $year2 = $year;
    $view = '3YEARS';

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as detail,0 as year1,0 as year2,0 as year3";
    $result = $this->coreFunctions->opentable($query2);
    $coa = json_decode(json_encode($result), true); // for convert to array

    $month = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $month2 = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthE = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthE2 = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthO = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthO2 = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $this->PLANTTREE($coa, '\\\\', 'R', $year1, $year2, $view, $month, $month2, $filter, $company, $params);
    $this->PLANTTREE($coa, '\\\\', 'G', $year1, $year2, $view, $month, $month2, $filter, $company, $params);
    $this->PLANTTREE($coa, '\\\\', 'E', $year1, $year2, $view, $monthE, $monthE2, $filter, $company, $params);
    $this->PLANTTREE($coa, '\\\\', 'O', $year1, $year2, $view, $monthO, $monthO2, $filter, $company, $params);

    $coa[] = array('acno' => '//4999', 'acnoname' => 'NET INCOME', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'detail' => 2, 'year1' => $month2['year1'] - $monthE2['year1'] - $monthO2['year1'], 'year2' => $month2['year2'] - $monthE2['year2'] - $monthO2['year2'], 'year3' => $month2['year3'] - $monthE2['year3'] - $monthO2['year3']);
    $array = json_decode(json_encode($coa), true);
    return $array;
  }

  public function aftech_default_query($filters, $company)
  {
    if ($filters['params']['dataparams']['branchcode'] == "") {
      $center = "";
    } else {
      $center = $filters['params']['dataparams']['branch'];
    }
    $year = intval($filters['params']['dataparams']['year']);
    $params = $filters['params']['dataparams'];
    $costcenter = $filters['params']['dataparams']['code'];
    $filter = '';

    if ($center != '') {
      $filter .= " and detail.branch='" . $center . "' ";
    }

    if ($costcenter != "") {
      $filter .= " and detail.project = '" . $costcenter . "'";
    }

    $year1 = $year - 2;
    $year2 = $year;
    $view = '3YEARS';


    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as detail,0 as year1,0 as year2,0 as year3";

    $result = $this->coreFunctions->opentable($query2);
    $coa = json_decode(json_encode($result), true); // for convert to array

    $month = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $month2 = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthE = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthE2 = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthO = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $monthO2 = array('year1' => 0, 'year2' => 0, 'year3' => 0);
    $this->PLANTTREE($coa, '\\\\', 'R', $year1, $year2, $view, $month, $month2, $filter, $company, $params);
    $this->PLANTTREE($coa, '\\\\', 'G', $year1, $year2, $view, $month, $month2, $filter, $company, $params);
    $this->PLANTTREE($coa, '\\\\', 'E', $year1, $year2, $view, $monthE, $monthE2, $filter, $company, $params);
    $this->PLANTTREE($coa, '\\\\', 'O', $year1, $year2, $view, $monthO, $monthO2, $filter, $company, $params);

    $coa[] = array('acno' => '//4999', 'acnoname' => 'NET INCOME', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'detail' => 2, 'year1' => $month2['year1'] - $monthE2['year1'] - $monthO2['year1'], 'year2' => $month2['year2'] - $monthE2['year2'] - $monthO2['year2'], 'year3' => $month2['year3'] - $monthE2['year3'] - $monthO2['year3']);
    $array = json_decode(json_encode($coa), true);
    return $array;
  }

  //PLANT TREE
  private function PLANTTREE(&$a, $acno, $cat, $year1, $year2, $view, &$month, &$month2, $filters, $company, $params)
  {
    $query2 = $this->DEFAULT_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $company, $params);
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
        if ($this->PLANTTREE($a, '\\' . $result2[$b]['acno'], $result2[$b]['cat'], $year1, $year2, $view, $month, $month2, $filters, $company, $params)) {
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
              $loss = $this->DEFAULT_BALANCE_SHEETDUE('CREDIT', $C, $year1, $year2, $view, $filters, $company);
              $C = "('E','O')";
              $loss2 = $this->DEFAULT_BALANCE_SHEETDUE('DEBIT', $C, $year1, $year2, $view, $filters, $company);

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

  //QRY PER LINE
  private function DEFAULT_BALANCE_SHEET_QUERY($cat, $acno, $year1, $year2, $view, $filters, $company, $params)
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

    $filter = '';
    $selecthjc = '';


    switch ($view) {
      case 'MONTHLY':
        if ($company == 10 || $company == 12) {
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
            select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, 
            $field as amt from glhead as head 
            left join gldetail as detail on detail.trno=head.trno 
            left join cntnum on cntnum.trno=head.trno 
            where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . "
            group by detail.acnoid, month(head.dateid), year(head.dateid)) as inc 
          group by acno, acnoname, levelid, cat, parent, detail, yr";
        } else {
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
            select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, 
            $field as amt from glhead as head 
            left join gldetail as detail on detail.trno=head.trno 
            left join cntnum on cntnum.trno=head.trno 
            where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . "
            group by detail.acnoid, month(head.dateid), year(head.dateid)) as tb on tb.acnoid=coa.acnoid
            where coa.parent='$acno' and coa.cat='$cat'
            group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr
            ) as inc group by acno, acnoname, levelid, cat, parent, detail, yr";
        }

        break;

      case "3YEARS":
        switch ($company) {
          case 10:
          case 12:
            $yr1 = $params['year'];
            $yr2 = $params['year2'];
            $mon1 = $params['month'];
            $mon2 = $params['month2'];

            $query1 = "select acno, acnoname, levelid, cat, parent, detail,
            ifnull(sum(case when yr=$yr1 and mon=$mon1 then amt else 0 end),0) year1,
            ifnull(sum(case when yr=$yr2 and mon=$mon2 then amt else 0 end),0) year2,
            ifnull(sum(case when yr=$year2 then amt else 0 end),0) year3, 
            
            yr, ifnull(sum(amt),0) as amt
            from (
            select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr, round(ifnull(sum(tb.amt),0),2) as amt
            from coa left join (
              select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, $field as amt from glhead as head left join gldetail as detail on detail.trno=head.trno left join cntnum on cntnum.trno=head.trno 
              where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . "
              group by detail.acnoid, month(head.dateid), year(head.dateid)) as tb on tb.acnoid=coa.acnoid
            where coa.parent='$acno' and coa.cat='$cat'
            group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr
            ) as inc group by acno, acnoname, levelid, cat, parent, detail, yr";
            break;
          case 8:
            $query1 = "select acno, acnoname, levelid, cat, parent, detail,ifnull(sum(case when yr=$year2-2 then amt else 0 end),0) year1,
                            ifnull(sum(case when yr=$year2-1 then amt else 0 end),0) year2,
                            ifnull(sum(case when yr=$year2 then amt else 0 end),0) year3, yr, ifnull(sum(amt),0) as amt
                    from (select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, 
                                 tb.yr, round(ifnull(sum(tb.amt),0),2) as amt
                          from coa 
                          left join (select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, $field as amt 
                                     from glhead as head 
                                     left join gldetail as detail on detail.trno=head.trno 
                                     left join cntnum on cntnum.trno=head.trno 
                                     left join projectmasterfile as proj on proj.line=detail.projectid
                                     where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . "
                                     group by detail.acnoid, month(head.dateid), year(head.dateid) 
                                     union all 
                                     select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, $field as amt 
                                      from hjchead as head 
                                      left join gldetail as detail on detail.trno=head.trno 
                                      left join cntnum on cntnum.trno=head.trno 
                                      left join projectmasterfile as proj on proj.line=detail.projectid
                                      where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . "
                                      group by detail.acnoid, month(head.dateid), year(head.dateid)) as tb on tb.acnoid=coa.acnoid
                            where coa.parent='$acno' and coa.cat='$cat'
                            group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr) as inc 
                      group by acno, acnoname, levelid, cat, parent, detail, yr";
            break;
          default:
            $query1 = "select acno, acnoname, levelid, cat, parent, detail,ifnull(sum(case when yr=$year2-2 then amt else 0 end),0) year1,
                            ifnull(sum(case when yr=$year2-1 then amt else 0 end),0) year2,
                            ifnull(sum(case when yr=$year2 then amt else 0 end),0) year3, yr, ifnull(sum(amt),0) as amt
                    from (select coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, 
                                 tb.yr, round(ifnull(sum(tb.amt),0),2) as amt
                          from coa 
                          left join (select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, $field as amt 
                                     from glhead as head 
                                     left join gldetail as detail on detail.trno=head.trno 
                                     left join cntnum on cntnum.trno=head.trno 
                                     where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' " . $filters . "
                                     group by detail.acnoid, month(head.dateid), year(head.dateid)) as tb on tb.acnoid=coa.acnoid
                            where coa.parent='$acno' and coa.cat='$cat'
                            group by coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.mon, tb.yr) as inc 
                      group by acno, acnoname, levelid, cat, parent, detail, yr";
            break;
        }

        break;
    }
    return $query1;
  } // end fn

  private function DEFAULT_BALANCE_SHEETDUE($entry, $cat, $year1, $year2, $view, $filters, $company)
  {
    $field = '';
    $filter = '';
    switch ($entry) {
      case 'CREDIT':
        $field = ' round(ifnull(sum(detail.cr-detail.db),0),2) ';
        break;
      default:
        $field = ' round(ifnull(sum(detail.db-detail.cr),0),2) ';
        break;
    }

    $selecthjc = '';
    if ($company == 8) {
      $selecthjc = " union all 
                     select detail.acnoid, month(head.dateid) as mon,year(head.dateid) as yr, $field as amt 
                     from hjchead as head 
                     left join gldetail as detail on detail.trno=head.trno 
                     left join cntnum on cntnum.trno=head.trno 
                     where year(head.dateid) between '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . " " . $filters . "
                     group by detail.acnoid, month(head.dateid), year(head.dateid)";
    }

    switch ($view) {
      case 'MONTHLY':
        if ($company == 10 || $company == 12) {
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
            select $field as cr, year(head.dateid) as yr, month(head.dateid) as mon
            from glhead as head left join gldetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid left join cntnum on cntnum.trno=head.trno
            where year(head.dateid) between  '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . "  " . $filters . "
            group by year(head.dateid), month(head.dateid)) as tb group by yr";
        } else {
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
            select $field as cr, year(head.dateid) as yr, month(head.dateid) as mon
            from glhead as head left join gldetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid left join cntnum on cntnum.trno=head.trno
            where year(head.dateid) between  '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . "  " . $filters . "
            group by year(head.dateid), month(head.dateid)) as tb group by yr";
        }

        break;

      case '3YEARS':
        if ($company == 10 || $company == 12) {
          $query1 = "select yr,ifnull(sum(case when yr=$year2-2 then cr else 0 end),0) as year1,
          ifnull(sum(case when yr=$year2-1 then cr else 0 end),0) as year2,
          ifnull(sum(case when yr=$year2 then cr else 0 end),0) as year3
          from (
          select $field as cr, year(head.dateid) as yr, month(head.dateid) as mon
          from glhead as head left join gldetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid=detail.acnoid left join cntnum on cntnum.trno=head.trno
          where year(head.dateid) between  '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . " " . $filters . "
          group by year(head.dateid), month(head.dateid)) as tb";
        } else {
          $query1 = "select yr,ifnull(sum(case when yr=$year2-2 then cr else 0 end),0) as year1,
                            ifnull(sum(case when yr=$year2-1 then cr else 0 end),0) as year2,
                            ifnull(sum(case when yr=$year2 then cr else 0 end),0) as year3
                    from (select $field as cr, year(head.dateid) as yr, month(head.dateid) as mon
                          from glhead as head 
                          left join gldetail as detail on detail.trno=head.trno
                          left join coa on coa.acnoid=detail.acnoid 
                          left join cntnum on cntnum.trno=head.trno
                          where year(head.dateid) between  '" . $year1 . "' and '" . $year2 . "' and coa.cat in " . $cat . " " . $filters . "
                          group by year(head.dateid), month(head.dateid) $selecthjc) as tb";
        }

        break;
    } // end switch


    $data = $this->coreFunctions->opentable($query1);
    $result = json_decode(json_encode($data), true);
    return $result;
  } // end fn

  //LAYOUTS START
  private function DEFAULT_HEADER($params, $data)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = '10';
    $fontsize15 = '15';
    $fontsize12 = '12';

    $year = $params['params']['dataparams']['year'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $center = $params['params']['dataparams']['center'];
    if ($center == '') {
      $center = 'ALL';
    }

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMPARATIVE INCOME STATEMENT', null, null, false, '1px solid ', '', '', $font, $fontsize15, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1300');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center :  ' . $center, 100, null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year :  ' . $year, 100, null, false, '1px solid ', '', '', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  } // end fn

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $year = $config['params']['dataparams']['year'];

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTS', '300', null, false, '1px solid ', 'B', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($year - 2, '110', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($year - 1, '110', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($year, '110', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '170', null, false, '1px solid ', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function DEFAULT_COMPARATIVE_INCOME_STATEMENT_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = '10';
    $fontsize11 = 11;

    $count = 71;
    $page = 70;
    $this->reporter->linecounter = 0;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER($params, $data);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);


    for ($i = 0; $i < count($data); $i++) {
      $total = 0;
      $lineTotal = 0;
      $bold = '';

      if ($companyid == 8) { //maxipro
        $total = $data[$i]['year1'] + $data[$i]['year2'] + $data[$i]['year3'];
        if ($data[$i]['levelid'] <> 3 || ($data[$i]['levelid'] == 3 && $total <> 0)) {
          if ($data[$i]['detail'] == 1 && $total == 0) {
          } else {
            $indent = '5' * ($data[$i]['levelid'] * 3);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            if ($data[$i]['detail'] == 2) {
              $bold = 'B';
            }
            $str .= $this->reporter->col($data[$i]['acnoname'], '280', null, false, '1px solid ', '', '', $font, $fontsize, $bold, '', '0px 0px 0px ' . $indent . 'px');

            if ($data[$i]['detail'] != 0) {
              if ($data[$i]['year1'] == 0) {
                $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
              } else {
                $str .= $this->reporter->col(number_format($data[$i]['year1'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
              }
              if ($data[$i]['year2'] == 0) {
                $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
              } else {
                $str .= $this->reporter->col(number_format($data[$i]['year2'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
              }
              if ($data[$i]['year3'] == 0) {
                $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
              } else {
                $str .= $this->reporter->col(number_format($data[$i]['year3'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
              }

              $lineTotal = $data[$i]['year1'] + $data[$i]['year2'] + $data[$i]['year3'];
              if ($lineTotal == 0) {
                $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
              } else {
                $str .= $this->reporter->col(number_format($lineTotal, 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
              }
            }

            $str .= $this->reporter->endrow();
          }
        }
      } else {
        if ($data[$i]['acnoname'] != '') {

          $indent = '5' * ($data[$i]['levelid'] * 3);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();

          if ($data[$i]['detail'] == 2) {
            $bold = 'B';
          }
          $str .= $this->reporter->col($data[$i]['acnoname'], '280', null, false, '1px solid ', '', '', $font, $fontsize, $bold, '', '0px 0px 0px ' . $indent . 'px');

          if ($data[$i]['detail'] != 0) {
            if ($data[$i]['year1'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['year1'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            }
            if ($data[$i]['year2'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['year2'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            }
            if ($data[$i]['year3'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['year3'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            }

            $lineTotal = $data[$i]['year1'] + $data[$i]['year2'] + $data[$i]['year3'];
            if ($lineTotal == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($lineTotal, 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
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
      } //if   
    } // end forloop

    $str .= $this->reporter->endtable();
    $str .= "<br/><br/>";
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function AFTECH_DEFAULT_HEADER($params, $data)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize12 = '12';
    $fontsize18 = '18';
    $fontsize20 = '20';

    $center = $params['params']['dataparams']['branch'];
    $year = $params['params']['dataparams']['year'];
    $year2 = $params['params']['dataparams']['year2'];
    $mon = $params['params']['dataparams']['month'];
    $mon2 = $params['params']['dataparams']['month2'];

    if ($center == '') {
      $center = 'ALL';
    } else {
      $center = $params['params']['dataparams']['branchname'];
    }

    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Access Frontier Technologies Inc.', null, null, false, '1px solid ', '', 'L', $font, $fontsize20, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Comparative Profit & Loss Report', null, null, false, '1px solid ', '', 'L', $font, $fontsize18, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year: ' . $year, null, null, false, '1px solid ', '', 'L', $font, $fontsize18, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', '', $font, $fontsize18, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('1300');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTS', '300', null, false, '1px solid ', 'B', '', $font, $fontsize12, 'B', '', '');

    $str .= $this->reporter->col($mon . '/' . $year, '170', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col($mon2 . '/' . $year2, '170', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');

    $str .= $this->reporter->col('CHANGE', '110', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->col('% CHANGE', '110', null, false, '1px solid ', 'B', 'R', $font, $fontsize12, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } // end fn

  private function AFTECH_DEFAULT_COMPARATIVE_INCOME_STATEMENT_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $font = "cambria";
        break;
      default:
        $font = $this->companysetup->getrptfont($params['params']);
        break;
    }

    $fontsize = '10';
    $count = 40;
    $page = 40;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->AFTECH_DEFAULT_HEADER($params, $data);


    for ($i = 0; $i < count($data); $i++) {

      $lineTotal = 0;
      $bold = '';

      if ($data[$i]['year2'] == 0 && $data[$i]['year3'] == 0) {
        if ($data[$i]['detail'] == 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['acnoname'], '300', null, false, '1px solid ', '', 'L', $font, $fontsize, $bold, '', '');
          $str .= $this->reporter->col('', '170', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
          $str .= $this->reporter->col('', '170', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        if ($data[$i]['acnoname'] != '') {
          $indent = '5' * ($data[$i]['levelid'] * 3);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();

          if ($data[$i]['detail'] == 2) {
            $bold = 'B';
          }
          $str .= $this->reporter->col($data[$i]['acnoname'], '300', null, false, '1px solid ', '', '', $font, $fontsize, $bold, '', '0px 0px 0px ' . $indent . 'px');

          if ($data[$i]['detail'] != 0) {
            if ($data[$i]['year1'] == 0) {
              $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['year1'], 2), '90', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            }
            if ($data[$i]['year2'] == 0) {
              $str .= $this->reporter->col('-', '170', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            } else {
              $str .= $this->reporter->col(number_format($data[$i]['year2'], 2), '170', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            }
            if ($data[$i]['year3'] == 0) {
            } else {
            }

            $lineTotal = $data[$i]['year1'] + $data[$i]['year2'] + $data[$i]['year3'];
            if ($lineTotal == 0) {
            } else {
            }

            $change = 0;
            $change = $data[$i]['year2'] - $data[$i]['year1'];
            $str .= $this->reporter->col(number_format($change, 2), '110', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');

            $percent = 0;

            if ($data[$i]['year2'] <> 0) {
              $percent = floatval($change) / floatval($data[$i]['year2']);
              $str .= $this->reporter->col(number_format($percent, 2) . ' %', '110', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            } else {
              $str .= $this->reporter->col('-', '110', null, false, '1px solid ', '', 'R', $font, $fontsize, $bold, '', '');
            }
          }

          $str .= $this->reporter->endrow();
        }
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->AFTECH_DEFAULT_HEADER($params, $data);

        $page = $page + $count;
      } //if   
    } // end forloop

    $str .= $this->reporter->endtable();
    $str .= "<br/><br/>";
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn
  //LAYOUTS END
}//end class