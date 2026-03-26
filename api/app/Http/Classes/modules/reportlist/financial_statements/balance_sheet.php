<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;


class balance_sheet
{
  public $modulename = 'Balance Sheet';
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
    $company = $config['params']['companyid'];

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    switch ($company) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['dateid', 'dbranchname', 'costcenter', 'ddeptname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'ddeptname.label', 'Department');
        data_set($col2, 'dateid.readonly', false);
        data_set($col2, 'costcenter.label', 'Item Group');
        break;
      case 56: //homeworks
        $fields = ['dateid', 'enddate', 'dcentername', 'costcenter'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dateid.label', 'Start Date');
        data_set($col2, 'enddate.label', 'End Date');
        data_set($col2, 'dateid.readonly', false);
        break;
      default:
        $fields = ['dateid', 'dcentername', 'costcenter'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dateid.label', 'As Of');
        data_set($col2, 'dateid.readonly', false);
        break;
    }

   

    if ($company == 10 || $company == 12) { //afti, afti usd
      $fields = ['forex', 'radioposttype'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'forex.readonly', false);
      data_set($col3, 'forex.required', true);
      data_set($col3, 'forex.label', 'Forex SGD');
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

    if ($company == 8) { //maxipro
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

        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,'0' as posttype,
                            '" . $defaultcenter[0]['center'] . "' as center,
                            '" . $defaultcenter[0]['centername'] . "' as centername,
                            '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                            '' as code,'' as name,'' as costcenter, 0 as costcenterid";
        break;
      case 12: //afti usd
      case 10: //afti
        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as dateid,'' as branch,
                            '' as code,'' as name, 0 as branchid, '' as branchname,'' as branchcode,'' as dbranchname,
                            '' as forex,'0' as posttype, 0 as costcenterid, '' as costcenter, 0 as deptid, '' as ddeptname, '' as dept, '' as deptname";
        break;
      case 32: //3m
        $paramstr = "select 'default' as print, concat(year(now()),'-01-01') as dateid,'0' as posttype,
                            '' as center,'' as centername,'' as dcentername,'' as code,'' as name,'' as costcenter,
                            0 as costcenterid";
        break;

      default:
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid, left(now(),10) as enddate,'0' as posttype,
                            '' as center,'' as centername,'' as dcentername,'' as code,'' as name,'' as costcenter,
                            0 as costcenterid";
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

  public function default_query($filters)
  {
    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $asof = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $addedparams = [];

    if ($company == 56) { //homeworks
      array_push($addedparams, date("Y-m-d", strtotime($filters['params']['dataparams']['enddate'])));
    }
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
    $a = 0;
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'A', $amt1, $amt1b, $a, $asof, $center, $isposted, $cc, $filter, $company, $addedparams);
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'L', $amt2, $amt2b, $a, $asof, $center, $isposted, $cc, $filter, $company, $addedparams);
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'C', $amt3, $amt3b, $a, $asof, $center, $isposted, $cc, $filter, $company, $addedparams);
    $coa[] = array('acno' => '//4999', 'acnoname' => 'TOTAL LIABILITIES AND STOCKHOLDERS EQUITY', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt2b + $amt3b);

    $array = json_decode(json_encode($coa), true); // for clearing set to array
    return $array;
  }

  public function aftech_default_query($filters)
  {
    if ($filters['params']['dataparams']['branchcode'] == "") {
      $center = "";
    } else {
      $center = $filters['params']['dataparams']['branch'];
    }

    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $asof = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));

    $costcenter = $filters['params']['dataparams']['code'];
    $deptid = $filters['params']['dataparams']['ddeptname'];

    $cc = '';
    $filter = '';

    if ($costcenter != "") {
      $filter = $costcenter;
    }

    if ($deptid != "") {
      $dept = $filters['params']['dataparams']['deptid'];
      $cc = $dept;
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as amt,0 as detail,0 as total";
    $coa = $this->coreFunctions->opentable($query2);
    $amt1 = 0;
    $amt1b = 0;
    $amt2 = 0;
    $amt3 = 0;
    $amt2b = 0;
    $amt3b = 0;
    $a = 0;
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'A', $amt1, $amt1b, $a, $asof, $center, $isposted, $cc, $filter, $company);
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'L', $amt2, $amt2b, $a, $asof, $center, $isposted, $cc, $filter, $company);
    $this->DEFAULT_PLANTTREE($coa, '\\\\', 'C', $amt3, $amt3b, $a, $asof, $center, $isposted, $cc, $filter, $company);
    $coa[] = array('acno' => '//4999', 'acnoname' => 'TOTAL LIABILITIES AND STOCKHOLDERS EQUITY', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'amt' => 0, 'detail' => 0, 'total' => $amt2b + $amt3b);

    $array = json_decode(json_encode($coa), true); // for clearing set to array
    return $array;
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
      case 32: //3m
        $result = $this->default_query($config);
        $reportdata =  $this->MMM_BALANCE_SHEET_LAYOUT($config, $result);
        break;
      default:
        $result = $this->default_query($config);
        $reportdata =  $this->DEFAULT_BALANCE_SHEET_LAYOUT($config, $result);
        break;
    }


    return $reportdata;
  }

  private function DEFAULT_PLANTTREE(&$a, $acno, $cat, &$amt1, &$amt9, $z, $asof, $center, $status, $cc, $filter, $company, $addtionalparams = [])
  {

    static $mtotal = 0;
    $z = $z + 1;
    $amt = 0;
    $amt2 = 0;

    $query2 = $this->DEFAULT_BALANCE_SHEET_QUERY($cat, $acno, $asof, $center, $status, $cc, $filter, $company, $addtionalparams);

    $result2 = $this->coreFunctions->opentable($query2);

    if (!empty($result2)) {
      foreach ($result2 as $key => $value) {
        $a[] = array(
          'acno' => $value->acno, 'acnoname' => $value->levelid == 1 ? '<b>' . $value->acnoname . '</b>' : $value->acnoname, 'levelid' => $value->levelid,
          'cat' => $value->cat, 'parent' => $value->parent, 'amt' => $value->amt,
          'detail' => $value->detail, 'total' => $value->amt, 'alias' => ''
        );

        $prevamt9 = $amt9;
        $amt = $amt + $value->amt;
        $amt1 = $amt1 + $amt;
        $amt9 = $amt9 + $value->amt;
        $amt = 0;


        if ($value->detail == 0) {
          if ($this->DEFAULT_PLANTTREE($a, '\\' . $value->acno, $value->cat, $amt, $amt9, $z, $asof, $center, $status, $cc, $filter, $company, $addtionalparams)) {
            if ($value->levelid > 1) {
              if ($value->levelid >= 2) {
                $level2amt = $amt9 - $prevamt9;
                //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
                $a[] = array(
                  'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                  'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                  'amt' => $amt2, 'detail' => $value->detail, 'total' => ($mtotal != 0 ? $mtotal : $level2amt), 'alias' => $value->alias
                );
                $mtotal = 0;
              } else {
                //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
                $a[] = array(
                  'acno' => $value->acno, 'acnoname' => '<b>TOTAL ' . $value->acnoname . '</b>',
                  'levelid' => $value->levelid, 'cat' => $value->cat, 'parent' => $value->parent,
                  'amt' => $amt2, 'detail' => $value->detail, 'total' => $amt, 'alias' => $value->alias
                );
                $mtotal += $value->amt;
              } //end if
            } else {

              if ($cat == 'C') {
                $loss = 0;
                switch ($company) {
                  case 10:
                  case 12:
                    $C = "('R','G')";
                    break;
                  case 32:
                    $C = "('R','O')";
                    break;

                  default:
                    $C = "('R','G')";
                    break;
                }
                $loss = $this->DEFAULT_BALANCE_SHEETDUE('CREDIT', $C, $asof, $center, $status, $cc, $filter, $company, $addtionalparams);
                switch ($company) {
                  case 10:
                  case 12:
                    $C = "('E')";
                    break;
                  case 32:
                    $C = "('E','G')";
                    break;
                  default:
                    $C = "('E','O')";
                    break;
                }
                $loss = $loss - $this->DEFAULT_BALANCE_SHEETDUE('DEBIT', $C, $asof, $center, $status, $cc, $filter, $company, $addtionalparams);
                $amt9 = $amt9 + $loss;
                //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
                $a[] = array(
                  'acno' => '\3999', 'acnoname' => 'NET INCOME/LOSS TO BALANCE SHEET',
                  'levelid' => $value->levelid + 1, 'cat' => $value->cat, 'parent' => $value->parent,
                  'amt' => $loss, 'detail' => $value->detail + 1, 'total' => $loss, 'alias' => $value->alias
                );
              } //end if

              //THIS NEXT 3 ROWS IS USED TO ADD NEW ROWS TO THE ARRAY (USED FOR PLOTTING NEW ROWS ON REPORT)
              $a[] = array(
                'acno' => $value->acno,
                'acnoname' => '<b>TOTAL ' . strtoupper($value->acnoname) . '</b>',
                'levelid' => $value->levelid, 'cat' => $value->cat,
                'parent' => $value->parent,
                'amt' => $amt2, 'detail' => $value->detail, 'total' => $amt9, 'alias' => $value->alias
              );
            } //end if IF LEVELID = 1
          }
        }
      } //end foreach
    } //end if

    if (count((array)$result2) > 0) { // cast to array result2 is an object count function not work on object
      return true;
    } else {
      return false;
    }
  } //Plantree

  private function DEFAULT_BALANCE_SHEET_QUERY($cat, $acno, $asof, $center, $status, $cc, $filter, $company, $addtionalparams)
  {
    $field = '';
    $filters = " where coa.parent='$acno' and coa.cat='$cat' "; // default filters
    $addedfilters = '';
    // var_dump($addtionalparams[0]);
    switch ($company) {
      case 10: //afti
      case 12: //afti usd
        if ($filter != '') {
          $addedfilters .= " and detail.projectid = '" . $filter . "' "; // cost center filter
        }

        if ($center != '') {
          $addedfilters .= " and detail.branch = '" . $center . "' "; // center filter
        }

        if ($cc != '') {
          $addedfilters .= " and head.deptid = $cc "; // department filter
        }
        break;
      default:
        if ($company == 8) {
          if ($filter != 0) {
            $addedfilters .= " and detail.projectid = '" . $filter . "' "; // cost center filter
          }
        } else {
          if ($filter != 0) {
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
      case 'O':
      case 'C':
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
        $field = " sum(round(detail.db-detail.cr,2)) ";
        break;
    } //end swtich

    $selecthjc = '';
    $selectjc = '';

    if ($company == 8) { //maxipro
      $selecthjc = " union all select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, 
                      coa.detail,ifnull((select $field from hjchead as head 
                      left join gldetail as detail on detail.trno=head.trno 
                      left join cntnum on cntnum.trno=head.trno 
                      where detail.acnoid=coa.acnoid and date(head.dateid) <=  '" . $asof . "' " . $addedfilters . "),0) as amt 
                      from coa " . $filters . " ";
      $selectjc = " union all select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, 
                              coa.detail,ifnull((select $field from jchead as head 
                              left join ladetail as detail on detail.trno=head.trno 
                              left join cntnum on cntnum.trno=head.trno 
                              where detail.acnoid=coa.acnoid and date(head.dateid) <= '" . $asof . "' " . $addedfilters . "),0) as amt 
                              from coa " . $filters . " ";
    }


    // 2025/01/31 add acctgbal table and get cutoffdate 
    // switch ($status) {
    //   case 0: // posted
    //     $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,sum(tb.amt) as amt 
    //                from (select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
    //                             ifnull((select $field from glhead as head left join gldetail as detail on detail.trno=head.trno 
    //                                     left join cntnum on cntnum.trno=head.trno 
    //                                     where detail.acnoid=coa.acnoid and date(head.dateid) <=  '" . $asof . "' " . $addedfilters . "),0) as amt 
    //                       from coa " . $filters . " $selecthjc ) as  tb 
    //                group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias";
    //     break;
    //   case 1: // unposted
    //     $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,sum(tb.amt) as amt 
    //                from (select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
    //                             ifnull((select $field from lahead as head left join ladetail as detail on detail.trno=head.trno 
    //                             left join cntnum on cntnum.trno=head.trno 
    //                             where detail.acnoid=coa.acnoid and date(head.dateid) <= '" . $asof . "' " . $addedfilters . "),0) as amt 
    //                     from coa " . $filters . " $selectjc ) as tb
    //                group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias";
    //     break;

    //   case 2: // all transactions
    //     $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,sum(tb.amt) as amt 
    //                from (select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
    //                             ifnull((select $field from lahead as head left join ladetail as detail on detail.trno=head.trno 
    //                                     left join cntnum on cntnum.trno=head.trno 
    //                                     where detail.acnoid=coa.acnoid and date(head.dateid) <= '" . $asof . "' " . $addedfilters . "),0) as amt 
    //                     from coa " . $filters . " $selectjc
    //                     union all
    //                     select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
    //                            ifnull((select $field from glhead as head left join gldetail as detail on detail.trno=head.trno 
    //                                    left join cntnum on cntnum.trno=head.trno 
    //                                    where detail.acnoid=coa.acnoid and date(head.dateid) <=  '" . $asof . "' " . $addedfilters . "),0) as amt 
    //                     from coa " . $filters . " $selecthjc ) as tb
    //               group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias";
    //     break;
    // } //end switch


    //for testing
    $cutoffdate = $this->coreFunctions->datareader("select pvalue as value from profile where psection= ? limit 1", ['ACCTGCUTOFF']);
    $unionacctgbal = "";
    if ($cutoffdate != "") {
      $addfilters = "";

      if ($filter != 0) {
        $addfilters .= " and detail.projectid = '" . $filter . "' ";
      }
      if ($company == 12 || $company == 10) {
        if ($center != 0) {
          $addfilters .= " and detail.branchid = '" . $center . "'";
        }
      } else {
        if ($center != '') {
          $addfilters .= " and detail.center = '" . $center . "' "; // center filter
        }
      }
      if ($cc != '') {
        $addfilters .= " and detail.deptid = $cc "; // department filter
      }


      if ($cutoffdate > $asof) {
        goto def;
      }
      $filtetdate = "date(head.dateid) > '$cutoffdate' and date(head.dateid) <= '$asof'";
      $unionacctgbal = " union all select coa.alias,coa.acno,coa.acnoname,coa.levelid,coa.cat,coa.parent,coa.detail,ifnull($field,0) as amt
    from acctgbal as detail
    left join coa on coa.acnoid = detail.acnoid
    " . $filters . " and detail.dateid <= '$cutoffdate' $addfilters
    group by coa.acno,coa.acnoname,coa.levelid,coa.cat,coa.parent,coa.detail,coa.alias ";
    } else {
      def:
      if ($company == 56) { //homeworks
        $filtetdate = " date(head.dateid) between '" . $asof . "' and '" . $addtionalparams[0] . "' ";
      } else {
        $filtetdate = " date(head.dateid) <=  '" . $asof . "' ";
      }
    }
    switch ($status) {
      case 0: // posted
        $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,sum(tb.amt) as amt 
                   from (select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
                                ifnull((select $field from glhead as head left join gldetail as detail on detail.trno=head.trno 
                                        left join cntnum on cntnum.trno=head.trno 
                                        where detail.acnoid=coa.acnoid and $filtetdate " . $addedfilters . "),0) as amt 
                          from coa " . $filters . " $selecthjc 
                          $unionacctgbal ) as  tb 
                   group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias";
        break;
      case 1: // unposted
        $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,sum(tb.amt) as amt 
                   from (select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
                                ifnull((select $field from lahead as head left join ladetail as detail on detail.trno=head.trno 
                                left join cntnum on cntnum.trno=head.trno 
                                where detail.acnoid=coa.acnoid and $filtetdate " . $addedfilters . "),0) as amt 
                        from coa " . $filters . " $selectjc ) as tb
                   group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias";
        break;

      case 2: // all transactions
        $query1 = "select tb.alias,tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,sum(tb.amt) as amt 
                   from (select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
                                ifnull((select $field from lahead as head left join ladetail as detail on detail.trno=head.trno 
                                        left join cntnum on cntnum.trno=head.trno 
                                        where detail.acnoid=coa.acnoid and $filtetdate " . $addedfilters . "),0) as amt 
                        from coa " . $filters . " $selectjc
                        union all
                        select coa.alias,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent,coa.detail,
                               ifnull((select $field from glhead as head left join gldetail as detail on detail.trno=head.trno 
                                       left join cntnum on cntnum.trno=head.trno 
                                       where detail.acnoid=coa.acnoid and $filtetdate " . $addedfilters . "),0) as amt 
                        from coa " . $filters . " $selecthjc $unionacctgbal ) as tb
                  group by tb.acno, tb.acnoname, tb.levelid, tb.cat, tb.parent, tb.detail,tb.alias";
        break;
    } //end switch



    return $query1;
  } // DEFAULT BALANCE SHEET

  private function DEFAULT_BALANCE_SHEETDUE($entry, $cat, $asof, $center, $status, $cc, $filter, $company)
  {
    $field = '';
    $filters = "";

    $addedfilters = '';

    if ($company == '10' || $company == '12') {
      if ($filter != '') {
        $filters .= " and detail.project = '" . $filter . "' "; // cost center filter
      }

      if ($center != "") {
        $filters .= " and detail.branch = '" . $center . "' "; // branch filter
      }

      if ($cc != "") {
        $filters .= " and head.deptid = $cc "; // department filter
      }
    } else {
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
    }

    switch ($entry) {
      case 'CREDIT':
        $field = ' sum(detail.cr-detail.db) ';
        $query1 = "select  ifnull(sum(tb.cr),0) as value from  ";
        break;
      default:
        $field = ' sum(detail.db-detail.cr) ';
        $query1 = "select  ifnull(sum(tb.cr),0) as value from ";
        break;
    }

    $selecthjc = '';
    $selectjc = '';

    if ($company == 8) { //maxipro
      $selecthjc = " union all
                     select $field as cr 
                     from ((hjchead as head left join gldetail as detail on detail.trno=head.trno)
                     left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                     where date(head.dateid) <= '" . $asof . "' and coa.cat in $cat " . $filters . " ";
      $selectjc = " union all 
                    select $field as cr from ((jchead as head left join ladetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                    where  date(head.dateid) <= '" . $asof . "' and coa.cat in $cat " . $filters . "";
    }

    switch ($status) {
      case 0: // posted
        $query1 = $query1 . " (select $field as cr 
                               from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                               left join coa on coa.acnoid=detail.acnoid)
                               left join cntnum on cntnum.trno=head.trno
                               where date(head.dateid) <= '" . $asof . "' and coa.cat in $cat " . $filters . " 
                               $selecthjc ) as tb ";
        break;
      case 1: // unposted
        $query1 = $query1 . " (select $field as cr 
                               from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                               left join coa on coa.acnoid=detail.acnoid) 
                               left join cntnum on cntnum.trno=head.trno
                               where  date(head.dateid) <= '" . $asof . "' and coa.cat in $cat " . $filters . " 
                               $selectjc ) as tb ";
        break;

      case 2: // all transactions
        $query1 = $query1 . " (select $field as cr 
                               from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                               left join coa on coa.acnoid=detail.acnoid) 
                               left join cntnum on cntnum.trno=head.trno
                               where  date(head.dateid) <= '" . $asof . "' and coa.cat in $cat " . $filters . " 
                               $selectjc
                               union all
                               select $field as cr 
                               from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                               left join coa on coa.acnoid=detail.acnoid)
                               left join cntnum on cntnum.trno=head.trno
                               where date(head.dateid) <= '" . $asof . "' and coa.cat in $cat " . $filters . " 
                               $selecthjc) as tb ";
        break;
    }
    $result = $this->coreFunctions->datareader($query1);
    return $result;
  } // DEFAULT BALANCE SHEETDUE

  private function DEFAULT_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($filters['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);

    $isposted = $filters['params']['dataparams']['posttype'];
    $asof = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['code'];

    if ($center == "") {
      $center = "ALL";
    }

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $count = 79;
    $page = 78;
    $this->reporter->linecounter = 0;
    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER($filters, $center);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $filters, $center);
    $str .= $this->reporter->begintable();

    for ($i = 0; $i < count($data); $i++) {
      if ($companyid == 8) { //maxipro
        if ($data[$i]['levelid'] <> 3 || ($data[$i]['levelid'] == 3 && $data[$i]['total'] <> 0)) {
          if ($data[$i]['detail'] == 1 && $data[$i]['total'] == 0) {
          } else {
            $str .= $this->reporter->startrow();

            $indent = '5' * ($data[$i]['levelid'] * 3);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, $border, '', '', $font, $font_size, '', '', '0px 0px 0px ' . $indent . 'px');

            if ($data[$i]['amt'] == 0) {
              $amt = '';
            } else {
              $amt = number_format($data[$i]['amt'], $decimal_currency);
            }
            $str .= $this->reporter->col($amt, '100', null, false, $border, '', 'r', $font, $font_size, '', '', '');

            if ($data[$i]['total'] == 0) {
              $total = '';
            } else {
              if ($amt == 0) {
                $total = number_format($data[$i]['total'], 2);
              } else {
                $total = '';
              }
            }
            $str .= $this->reporter->col($total, '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
          }
        }
      } else {
        $str .= $this->reporter->startrow();

        $indent = '5' * ($data[$i]['levelid'] * 3);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, $border, '', '', $font, $font_size, '', '', '0px 0px 0px ' . $indent . 'px');

        if ($data[$i]['amt'] == 0) {
          $amt = '';
        } else {
          $amt = number_format($data[$i]['amt'], $decimal_currency);
        }
        $str .= $this->reporter->col($amt, '100', null, false, $border, '', 'r', $font, $font_size, '', '', '');

        if ($data[$i]['total'] == 0) {
          $total = '';
        } else {
          if ($amt == 0) {
            $total = number_format($data[$i]['total'], 2);
          } else {
            $total = '';
          }
        }
        $str .= $this->reporter->col($total, '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
      }


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($filters['params']);
        if (!$allowfirstpage) {
          $str .=  $this->DEFAULT_HEADER($filters, $center);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $filters, $center);
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } //end for loop

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //default_Layout

  private function AFTECH_DEFAULT_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';


    $fontsize = '10';
    $padding = '';
    $margin = '';

    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);
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
    $asof = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $center = $filters['params']['dataparams']['branch'];
    $costcenter = $filters['params']['dataparams']['code'];

    if ($center <> "") {
      $center = $filters['params']['dataparams']['dbranchname'];
    } else {
      $center = "ALL";
    }

    $count = 58;
    $page = 48;
    $str = '';

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
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TB', 'TR', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', 'T', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TR', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('in SGD', '80', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '4px;');
    $str .= $this->reporter->col('', '80', null, false, '1px solid', 'TB', 'TR', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->startrow();

    for ($i = 0; $i < count($data); $i++) {

      $indent = '5' * ($data[$i]['levelid'] * 3);
      $str .= $this->reporter->addline();

      $forex = $filters['params']['dataparams']['forex'];

      if ($data[$i]['amt'] == 0) {
        $amt = '';
        $sgd = '';
      } else {
        $amt = number_format($data[$i]['amt'], $decimal_currency);
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $sgd = number_format($data[$i]['amt'] / $forex, $decimal_currency);
            break;
          default:
            $sgd = number_format($data[$i]['amt'] * $forex, $decimal_currency);
            break;
        }
      }

      if ($data[$i]['total'] == 0) {
        $total = '';
        $sgdtotal = '';
      } else {
        if ($amt == 0) {
          $total = number_format($data[$i]['total'], 2);
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $sgdtotal = number_format($data[$i]['total'] / $forex, 2);
              break;
            default:
              $sgdtotal = number_format($data[$i]['total'] * $forex, 2);
              break;
          }
        } else {
          $total = '';
          $sgdtotal = '';
        }
      }

      switch ($data[$i]['detail']) {
        case '1':
          if ($data[$i]['amt'] != 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['acnoname'], '300', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
            $str .= $this->reporter->col($amt, '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '', '', 0, '', 1);
            $str .= $this->reporter->col($total, '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
            $str .= $this->reporter->col('', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($sgd, '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '', '', 0, '', 1);
            $str .= $this->reporter->col($sgdtotal, '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
            $str .= $this->reporter->col('', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          }
          break;

        default:
          switch ($data[$i]['levelid']) {
            case '1':
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:red;">' . $total . '</span>&nbsp' : $total . '&nbsp;', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
              $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:red;">' . $sgdtotal . '</span>&nbsp' : $sgdtotal . '&nbsp;', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
              $str .= $this->reporter->endrow();
              break;
            case '2':
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:red;">' . $total . '</span>&nbsp' : $total . '&nbsp;', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
              $str .= $this->reporter->col('&nbsp;', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(isset($data[$i]['color']) ? '<span style="color:red;">' . $sgdtotal . '</span>&nbsp' : $sgdtotal . '&nbsp;', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->endrow();
              break;
            default:
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($data[$i]['acnoname'], '310', null, false, $border, 'TLRB', '', $font, $fontsize, '', '', '0px 0px 0px ' . $indent . 'px');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($total, '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TRBL', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('     ', '10', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($sgdtotal, '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 1);
              $str .= $this->reporter->col('&nbsp', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->endrow();
              break;
          }
          break;
      }
    } //end for loop

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //aftech_Layout

  private function DEFAULT_HEADER($filters, $center)
  {
    $center1 = $filters['params']['center'];
    $username = $filters['params']['user'];

    // if ($costcenter == '') {
    //   $ccenter = 'ALL';
    // } else {
    //   $ccenter = $filters['params']['dataparams']['name'];
    // }

    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $filters);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br/><br/>";

    return $str;
  } //DEFAULT HEADER

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $filters, $center)
  {
    $str = '';
    $costcenter = $filters['params']['dataparams']['costcenter'];

    if ($costcenter == '') {
      $ccenter = 'ALL';
    } else {
      $ccenter = $filters['params']['dataparams']['name'];
    }

    $isposted = $filters['params']['dataparams']['posttype'];
    $asof = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));

    $str .= $this->reporter->printline();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('BALANCE SHEET', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    if ($filters['params']['companyid'] == 56) { //homeworks
      $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));
      $str .= $this->reporter->col('Date Range: ' . date('M-d-Y', strtotime($asof)) . ' to ' . date('M-d-Y', strtotime($enddate)), null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('As Of: ' . date('M-d-Y', strtotime($asof)), null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('Cost Center: ' . $ccenter, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center:' . $center, null, null, false, $border, '', '', $font, $fontsize, '', '', '');

    switch ($isposted) {
      case 0:
        $str .= $this->reporter->col('Transaction: POSTED', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
      case 1:
        $str .= $this->reporter->col('Transaction: UNPOSTED', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
      case 2:
        $str .= $this->reporter->col('Transaction: ALL TRANSACTIONS', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
    }
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function AFTECH_DEFAULT_HEADER($filters, $center)
  {
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = '10';

    $isposted = $filters['params']['dataparams']['posttype'];
    $asof = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));

    $costcenter = $filters['params']['dataparams']['code'];
    $dept   = $filters['params']['dataparams']['ddeptname'];

    if ($center != "") {
      $center = $center;
    } else {
      $center = "ALL";
    }

    if ($costcenter != "") {
      $costcenter = $filters['params']['dataparams']['name'];
    } else {
      $costcenter = "ALL";
    }

    if ($dept != "") {
      $deptname = $filters['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Access Frontier Technologies Inc.', null, null, false, '1px solid ', '', 'C', $font, '20', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance Sheet', null, null, false, '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . date('M-d-Y', strtotime($asof)), null, null, false, '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center : ' . $center, '250', null, false, $border, '', '', $font, $fontsize, '', '', '');
    if ($isposted == 0) {
      $str .= $this->reporter->col('Transaction : POSTED', '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Transaction : UNPOSTED', '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('Project : ' . $costcenter, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  } //AFTECH HEADER

  private function MMM_BALANCE_SHEET_LAYOUT($filters, $data)
  {
    $border = '1px solid';

    $font = $this->companysetup->getrptfont($filters['params']);
    $font_size = '10';
    $companyid = $filters['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $filters['params']);
    $center = $filters['params']['dataparams']['center'];

    if ($center == "") {
      $center = "ALL";
    }

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $count = 79;
    $page = 78;
    $this->reporter->linecounter = 0;
    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER($filters, $center);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $filters, $center);
    $str .= $this->reporter->begintable();

    for ($i = 0; $i < count($data); $i++) {

      $indent = '5' * ($data[$i]['levelid'] * 3);


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

      switch ($data[$i]['detail']) {
        case 1:
          if ($data[$i]['amt'] != 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, $border, '', '', $font, $font_size, '', '', '0px 0px 0px ' . $indent . 'px');
            $str .= $this->reporter->col($amt, '100', null, false, $border, '', 'r', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($total, '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
          }
          break;
        default:
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($data[$i]['acnoname'], '580', null, false, $border, '', '', $font, $font_size, '', '', '0px 0px 0px ' . $indent . 'px');
          $str .= $this->reporter->col($amt, '100', null, false, $border, '', 'r', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($total, '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->endrow();
          break;
      }


      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($filters['params']);
        if (!$allowfirstpage) {
          $str .=  $this->DEFAULT_HEADER($filters, $center);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $font_size, $filters, $center);
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
    } //end for loop

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //default_Layout
}//end class