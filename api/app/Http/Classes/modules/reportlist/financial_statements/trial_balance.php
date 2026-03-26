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
use DateTime;

class trial_balance
{
  public $modulename = 'Trial Balance';
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

    $fields = ['dateid', 'due'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'dbranchname', 'costcenter', 'ddeptname');
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'ddeptname.label', 'Department');
        data_set($col2, 'costcenter.label', 'Item Group');
        break;
      default:
        array_push($fields, 'dcentername', 'costcenter');
        $col2 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);
    data_set($col2, 'dacnoname.action', 'lookupcoa');
    data_set($col2, 'dacnoname.lookupclass', 'detail');

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
        '" . $defaultcenter[0]['center'] . "' as center,'' as code,'' as name,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '' as costcenter,'0' as costcenterid";
        break;
      case 12: //afti usd
      case 10: //afti
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as dateid,
        left(now(),10) as due,
        0 as branchid,
        '' as branch,
        '' as branchname,
        '' as branchcode,
        '' as dbranchname,
        '' as code,
        '' as name,
        '' as forex,
        '0' as posttype,
        '' as costcenter,
        '' as ddeptname, '' as dept, '' as deptname";
        break;
      default:
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,left(now(),10) as due,'0' as posttype,'' as center,'' as code,'' as name,'' as centername,'' as dcentername,'' as costcenter,'0' as costcenterid 
        ";
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
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));

    $filter = "";
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['costcenter'];

    if ($center != '') {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($costcenter != "") {
      $costcenterid = $filters['params']['dataparams']['costcenterid'];
      if ($company == 8) { //maxipro
        $filter .= " and detail.projectid=" . $costcenterid;
      } else {
        $filter .= " and head.projectid=" . $costcenterid;
      }
    }

    $selecthjc = '';
    $selectjc = '';
    if ($company == 8) { //maxipro
      $selecthjc = "union all select coa.acno, ifnull(sum(round(detail.db,2)),0) as db, ifnull(sum(round(detail.cr,2)),0) as cr 
                    from ((hjchead as head left join gldetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid) left join cntnum on cntnum.trno=head.trno
                    where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                    group by coa.acno ";
      $selectjc = " union all select  coa.acno, ifnull(sum(round(detail.db,2)),0) as db,ifnull(sum(round(detail.cr,2)),0) as cr 
                    from ((jchead as head 
                    left join ladetail as detail on detail.trno=head.trno)
                    left join coa on coa.acnoid=detail.acnoid) left join cntnum on cntnum.trno=head.trno 
                    where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
                    group by coa.acno ";
    }

    switch ($isposted) {
      case 1: //unposted 
        $query = "select 'u' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(tb.db,0)-ifnull(tb.cr,0)) as amt,coa.detail
                  from coa 
                  left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,ifnull(sum(round(detail.cr,2)),0) as cr 
                            from ((lahead as head 
                            left join ladetail as detail on detail.trno=head.trno)
                            left join coa on coa.acnoid=detail.acnoid)
                            left join cntnum on cntnum.trno=head.trno 
                            where  detail.void = 0 and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
                            group by coa.acno 
                            $selectjc) as tb on tb.acno=coa.acno
                  group by coa.acno, coa.acnoname, coa.levelid,coa.detail order by coa.acno, coa.acnoname,coa.detail";
        break;
      case 0: //posted 
        $query = "select 'p' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(tb.db,0)-ifnull(tb.cr,0)) as amt ,coa.detail
                  from coa 
                  left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
                                    ifnull(sum(round(detail.cr,2)),0) as cr 
                            from ((glhead as head 
                            left join gldetail as detail on detail.trno=head.trno)
                            left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                            where detail.void = 0 and head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                            group by coa.acno 
                            $selecthjc) as tb on tb.acno=coa.acno
                  group by coa.acno, coa.acnoname, coa.levelid,coa.detail     order by acno, acnoname, detail";
        break;

      case 2: // all
        $query = "select  acno,acnoname, levelid, sum(amt) as amt, detail,sum(db) as db ,sum(cr) as cr
                  from (select 'p' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(tb.db,0)-ifnull(tb.cr,0)) as amt,
                                coa.detail,tb.cr,tb.db
                        from coa
                        left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,ifnull(sum(round(detail.cr,2)),0) as cr
                                  from ((glhead as head
                                  left join gldetail as detail on detail.trno=head.trno)
                                  left join coa on coa.acnoid=detail.acnoid)
                                  left join cntnum on cntnum.trno=head.trno
                                  where detail.void = 0 and head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                                  group by coa.acno 
                                  $selecthjc) as tb on tb.acno=coa.acno
                        group by coa.acno, coa.acnoname, coa.levelid,coa.detail,tb.cr,tb.db
                        union all
                        select 'u' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(tb.db,0)-ifnull(tb.cr,0)) as amt,
                                coa.detail,tb.cr,tb.db
                        from coa
                        left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,ifnull(sum(round(detail.cr,2)),0) as cr
                                  from ((lahead as head
                                  left join ladetail as detail on detail.trno=head.trno)
                                  left join coa on coa.acnoid=detail.acnoid)
                                  left join cntnum on cntnum.trno=head.trno
                                  where detail.void = 0 and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
                                  group by coa.acno 
                                  $selectjc) as tb on tb.acno=coa.acno
                        group by  coa.acno, coa.acnoname, coa.levelid,coa.detail,tb.cr,tb.db) as x
                  group by  acno, acnoname, levelid,detail
                  order by acno, acnoname, detail";
        break;
    } //END SWITCH

    $result = $this->coreFunctions->opentable($query);
    $array = json_decode(json_encode($result), true); // for convert to array
    return $array;
  }

  public function gfc_query($filters)
  {
    // $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));

    $filter = "";
    $costcenter = $filters['params']['dataparams']['costcenter'];
    $center = $filters['params']['dataparams']['center'];

    if ($center != '') {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($costcenter != "") {
      $costcenterid = $filters['params']['dataparams']['costcenterid'];
      $filter .= " and head.projectid=" . $costcenterid;
    }

    switch ($isposted) {
      case 1: //unposted
        $query = "
        select a.acno, a.acnoname, a.levelid,
        sum(ifnull(a.db,0)) as db,sum(ifnull(a.cr,0)) as cr,sum(ifnull(a.begbal,0)) as begbal
        from(
          select coa.acno, coa.acnoname, coa.levelid,
          0 as db,0 as cr,sum(ifnull(bb.db,0)-ifnull(bb.cr,0)) as begbal
          from coa
          left join (
            select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
            ifnull(sum(round(detail.cr,2)),0) as cr from
            lahead as head
            left join ladetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            where date(head.dateid) < '$start'  
            $filter
            group by coa.acno
          ) as bb on bb.acno=coa.acno
          group by coa.acno, coa.acnoname, coa.levelid
          union all
          select coa.acno, coa.acnoname, coa.levelid,
          sum(ifnull(tb.db,0)) as db,sum(ifnull(tb.cr,0)) as cr,0 as begbal
          from coa
          left join (
            select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
            ifnull(sum(round(detail.cr,2)),0) as cr from
            lahead as head
            left join ladetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            where
            date(head.dateid) between '$start' and '$end'  
            $filter

            group by coa.acno
          ) as tb on tb.acno=coa.acno
          group by coa.acno, coa.acnoname, coa.levelid
        ) as a
        group by a.acno,a.acnoname,a.levelid
          ";
        break;
      case 0: //posted
        $query = "
          select a.acno, a.acnoname, a.levelid,
          sum(ifnull(a.db,0)) as db,sum(ifnull(a.cr,0)) as cr,sum(ifnull(a.begbal,0)) as begbal
          from(
            select coa.acno, coa.acnoname, coa.levelid,
            0 as db,0 as cr,sum(ifnull(bb.db,0)-ifnull(bb.cr,0)) as begbal
            from coa
            left join (
              select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
              ifnull(sum(round(detail.cr,2)),0) as cr from
              glhead as head
              left join gldetail as detail on detail.trno=head.trno
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where date(head.dateid) < '$start'  
              $filter
              group by coa.acno
            ) as bb on bb.acno=coa.acno
            group by coa.acno, coa.acnoname, coa.levelid
            union all
            select coa.acno, coa.acnoname, coa.levelid,
            sum(ifnull(tb.db,0)) as db,sum(ifnull(tb.cr,0)) as cr,0 as begbal
            from coa
            left join (
              select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
              ifnull(sum(round(detail.cr,2)),0) as cr from
              glhead as head
              left join gldetail as detail on detail.trno=head.trno
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where
              date(head.dateid) between '$start' and '$end'  
              $filter

              group by coa.acno
            ) as tb on tb.acno=coa.acno
            group by coa.acno, coa.acnoname, coa.levelid
          ) as a
          group by a.acno,a.acnoname,a.levelid

          ";
        break;
    } //END SWITCH

    $this->coreFunctions->LogConsole($query);
    $result = $this->coreFunctions->opentable($query);

    $array = json_decode(json_encode($result), true); // for convert to array
    return $array;
  }

  public function aftechdefault_query($filters, $company)
  {
    // if ($filters['params']['dataparams']['branchcode'] == "") {
    //   $center = "";
    // } else {
    //   $center = $filters['params']['dataparams']['branch'];
    // }

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $costcenter = $filters['params']['dataparams']['code'];
    $deptname = $filters['params']['dataparams']['ddeptname'];
    $branch = $filters['params']['dataparams']['branchcode'];

    $filter = "";
    if ($branch != '') {
      $branchid = $filters['params']['dataparams']['branchid'];
      $filter .= " and detail.branch=" . $branchid;
    }
    if ($costcenter != "") {
      $costcenterid = $filters['params']['dataparams']['costcenterid'];
      $filter .= " and detail.project=" . $costcenterid;
    }
    if ($deptname != "") {
      $deptid = $filters['params']['dataparams']['deptid'];
      $filter .= " and head.deptid=" . $deptid;
    }

    switch ($isposted) {
      case 1:
        $query = "
          select 'u' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(tb.db,0)-ifnull(tb.cr,0)) as amt,
          sum(ifnull(tb.db,0)- 0) as debit,sum(ifnull(tb.cr,0)-0) as credit
          from coa left join (select  coa.acno, ifnull(sum(round(detail.db,2)),0) as db, 
          ifnull(sum(round(detail.cr,2)),0) as cr from ((lahead as head 
          left join ladetail as detail on detail.trno=head.trno)
          left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno 
          where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
          group by coa.acno) as tb on tb.acno=coa.acno
          group by coa.acno, coa.acnoname, coa.levelid order by coa.acno, coa.acnoname";
        break;
      case 0:
        $query = "
          select 'p' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(tb.db,0)-ifnull(tb.cr,0)) as amt,
          sum(ifnull(tb.db,0)- 0) as debit,sum(ifnull(tb.cr,0)-0) as credit
          from coa 
          left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
          ifnull(sum(round(detail.cr,2)),0) as cr 
          from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
          left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
          where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
          group by coa.acno) as tb on tb.acno=coa.acno
          group by coa.acno, coa.acnoname, coa.levelid";
        break;
    } //END SWITCH

    $result = $this->coreFunctions->opentable($query);
    $array = json_decode(json_encode($result), true); // for convert to array
    return $array;
  }

  public function reportplotting($config)
  {
    $company = $config['params']['companyid'];

    switch ($company) {
      case 10: //afti
      case 12: //afti usd
        $result = $this->aftechdefault_query($config, $company);
        $reportdata =  $this->AFTECH_DEFAULT_TRIAL_BALANCE_LAYOUT($config, $result);
        break;
      case 24: //goodfound
        $result = $this->gfc_query($config);
        $reportdata =  $this->GFC_TRIAL_BALANCE_LAYOUT($config, $result);
        break;
      case 32: //3m
        $result = $this->default_query($config);
        $reportdata =  $this->MMM_TRIAL_BALANCE_LAYOUT($config, $result);
        break;
      case 59: //roosevelt
        // $result = $this->roosevelt_query($config);
        $reportdata =  $this->roosevelt_trial_balance_layout($config);
        break;
      default:
        $result = $this->default_query($config);
        $reportdata =  $this->DEFAULT_TRIAL_BALANCE_LAYOUT($config, $result);
        break;
    }

    return $reportdata;
  }

  private function DEFAULT_HEADER_LAYOUT($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';

    $str = '';
    $companyid = $params['params']['companyid'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

    $center = $params['params']['dataparams']['center'];
    $costcenter = $params['params']['dataparams']['name'];

    if ($center == '') {
      $center = "ALL";
    }
    if ($costcenter == '') {
      $costcenter = 'ALL';
    }

    switch ($isposted) {
      case 0:
        $isposted = 'posted';
        break;
      case 1:
        $isposted = 'unposted';
      case 2:
        $isposted = 'all';
        break;
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRIAL BALANCE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('Project :' . $costcenter, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Cost Center :' . $costcenter, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Center :' . $center, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '', '');
    $str .= $this->reporter->col('Transaction :' . strtoupper($isposted), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize10, $config)
  {
    $str = '';
    $fontsize10 = '10';
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT #', '20px', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '30px', null, false, '1px solid ', '', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('ACCOUNT TITLE', '110px', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('DEBIT', '20px', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('CREDIT', '20px', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    return $str;
  }

  private function DEFAULT_TRIAL_BALANCE_LAYOUT($params, $data)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize11 = 11;

    $str = "";
    $count = 71;
    $page = 70;
    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER_LAYOUT($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    $totaldb = 0;
    $totalcr = 0;
    for ($i = 0; $i < count($data); $i++) {

      if ($data[$i]['amt'] < 0) {
        $cr = $data[$i]['amt'] * -1;
      } else {
        $cr = 0;
      }

      if ($data[$i]['amt'] > 0) {
        $db = $data[$i]['amt'];
      } else {
        $db = 0;
      }

      if ($params['params']['companyid'] == 8) {
        if ($data[$i]['amt'] <> 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($data[$i]['acno'], '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col('     ', '30px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '110px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

          if ($db == 0) {
            $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($db, 2), '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          }

          $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

          if ($cr == 0) {
            $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($cr, 2), '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          }

          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acno'], '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('     ', '30px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '110px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

        if ($db == 0) {
          $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($db, 2), '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        }

        $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

        if ($cr == 0) {
          $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($cr, 2), '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        }

        $str .= $this->reporter->endrow();
      }

      $totaldb = $totaldb + $cr;
      $totalcr = $totalcr + $db;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_HEADER_LAYOUT($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    } //END FOR EACH

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20px', null, false, '1px solid ', 'TB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '30px', null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '110px', null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '20px', null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '20px', null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  // GFC START
  private function GFC_HEADER_LAYOUT($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $str = '';

    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
    $center = $params['params']['dataparams']['center'];
    $costcenter = $params['params']['dataparams']['name'];

    if ($center == '') {
      $center = "ALL";
    }
    if ($costcenter == '') {
      $costcenter = 'ALL';
    }

    if ($isposted == '0') {
      $isposted = 'POSTED';
    } else {
      $isposted = 'UNPOSTED';
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRIAL BALANCE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Center :' . $center, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '', '');
    $str .= $this->reporter->col('Cost Center :' . $costcenter, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '', '');
    $str .= $this->reporter->col('Transaction :' . strtoupper($isposted), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function GFC_table_cols($layoutsize, $border, $font, $fontsize10, $config)
  {
    $str = '';
    $fontsize10 = '10';
    // $companyid = $config['params']['companyid'];
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '130', null, false, '1px solid ', '', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, '1px solid ', '', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '1px solid ', '', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('CURRENT', '260', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT #', '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('ACCOUNT TITLE', '180', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('BEG. BAL', '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('DEBIT', '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('CREDIT', '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('ENDING', '100', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    return $str;
  }

  private function GFC_TRIAL_BALANCE_LAYOUT($params, $data)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize11 = 11;

    $str = "";
    $count = 71;
    $page = 70;
    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->GFC_HEADER_LAYOUT($params);
    $str .= $this->GFC_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    $totaldb = 0;
    $totalcr = 0;
    $ending = 0;
    $begbal = 0;
    $totalending = 0;
    for ($i = 0; $i < count($data); $i++) {
      $cr = $data[$i]['cr'];
      $db = $data[$i]['db'];
      $begbal = $data[$i]['begbal'];
      $ending = $data[$i]['begbal'] + $data[$i]['db'] - $data[$i]['cr'];

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['acno'], '130', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($data[$i]['acnoname'], '180', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

      if ($begbal == 0) {
        $str .= $this->reporter->col('-', '130', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col(number_format($begbal, 2), '130', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      }

      if ($db == 0) {
        $str .= $this->reporter->col('-', '130', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col(number_format($db, 2), '130', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      }

      if ($cr == 0) {
        $str .= $this->reporter->col('-', '130', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col(number_format($cr, 2), '130', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      }

      if ($ending == 0) {
        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      } else {
        $str .= $this->reporter->col(number_format($ending, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      }

      $totaldb = $totaldb + $cr;
      $totalcr = $totalcr + $db;
      $totalending = $totalending + $ending;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->GFC_HEADER_LAYOUT($params);
        }
        $str .= $this->GFC_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    } //END FOR EACH

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '180', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('', '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '130', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalending, 2), '100', null, false, '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  // GFC END
  private function AFTECH_DEFAULT_TRIAL_BALANCE_LAYOUT($params, $data)
  {
    // $border = '1px solid';
    // $border_line = '';
    // $alignment = '';

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
    $fontsize10 = '10';
    // $padding = '';
    // $margin = '';

    $str = "";
    // $count = 50;
    // $page = 50;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->AFTECH_DEFAULT_HEADER_LAYOUT($params);
    $totaldb = 0;
    $totalcr = 0;
    $totalsgddb = 0;
    $totalsgdcr = 0;

    for ($i = 0; $i < count($data); $i++) {
      $cr = $data[$i]['credit'];
      $db = $data[$i]['debit'];
      if ($cr < 0) $cr = $cr * -1;
      $forex = $params['params']['dataparams']['forex'];
      $sgddb = $data[$i]['debit'] == 0 ? 0 : $data[$i]['debit'] / $forex;
      $sgdcr = $data[$i]['credit'] == 0 ? 0 : $data[$i]['credit'] / $forex;
      $str .= $this->reporter->addline();

      switch ($data[$i]['levelid']) {
        case 1:
        case 2:
        case 3:
          $str .= $this->reporter->startrow();
          switch ($data[$i]['levelid']) {
            case 1:
              $str .= $this->reporter->col('&nbsp;' . $data[$i]['acnoname'], '400', null, false, '1px solid ', 'LRB', 'L', $font, '13', 'B', '', '');
              break;
            case 2:
              $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '400', null, false, '1px solid ', 'LRB', 'L', $font, $fontsize10, 'B', '', '');
              break;
            default:
              $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '400', null, false, '1px solid ', 'LRB', 'L', $font, $fontsize10, '', '', '');
              break;
          }

          $str .= $this->reporter->col($db == 0 ? '' : number_format($db, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
          $str .= $this->reporter->col($cr == 0 ? '' : number_format($cr, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
          $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', 'R', 'L', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col($sgddb == 0 ? '' : number_format($sgddb, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
          $str .= $this->reporter->col($sgdcr == 0 ? '' : number_format($sgdcr, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
          $str .= $this->reporter->endrow();
          break;
        default:
          if ($db != '0.00' || $cr != '0.00') {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '400', null, false, '1px solid ', 'LRB', 'L', $font, $fontsize10, '', '', '');
            $str .= $this->reporter->col($db == 0 ? '' : number_format($db, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
            $str .= $this->reporter->col($cr == 0 ? '' : number_format($cr, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
            $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', 'R', 'L', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col($sgddb == 0 ? '' : number_format($sgddb, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
            $str .= $this->reporter->col($sgdcr == 0 ? '' : number_format($sgdcr, 2), '90', null, false, '1px solid', 'TRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
            $str .= $this->reporter->endrow();
          }
          break;
      }

      $totaldb = $totaldb + $db;
      $totalcr = $totalcr + $cr;

      $totalsgddb = $totalsgddb + $sgddb;
      $totalsgdcr = $totalsgdcr + $sgdcr;
    } //END FOR EACH

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL: ' . '&nbsp;&nbsp;', '500', null, false, '1px solid ', 'TBR', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '70', null, false, '1px solid ', 'RB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, 2), '70', null, false, '1px solid ', 'BR', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', 'B', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsgddb, 2), '70', null, false, '1px solid ', 'LBR', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalsgdcr, 2), '70', null, false, '1px solid ', 'RB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function AFTECH_DEFAULT_HEADER_LAYOUT($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';

    $branch = $params['params']['dataparams']['branchcode'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
    $costcenter = $params['params']['dataparams']['code'];
    $dept   = $params['params']['dataparams']['ddeptname'];
    $str = '';

    if ($branch == '') {
      $branch = "ALL";
    }

    if ($isposted == '0') {
      $isposted = 'POSTED';
    } else {
      $isposted = 'UNPOSTED';
    }

    if ($costcenter != "") {
      $costcenter = $params['params']['dataparams']['name'];
    } else {
      $costcenter = "ALL";
    }

    if ($dept != "") {
      $deptname = $params['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Access Frontier Technologies Inc.', null, null, false, '1px solid ', '', 'C', $font, '20', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Trial Balance', null, null, false, '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center : ' . $branch, '300', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($isposted), '300', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project : ' . $costcenter, '300', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, '1px solid ', 'LT', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('(in', '90', null, false, '1px solid ', 'LTB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('PHP)', '90', null, false, '1px solid ', 'TBR', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', 'T', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('(in', '90', null, false, '1px solid ', 'LTB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('SGD)', '90', null, false, '1px solid ', 'TBR', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, '1px solid ', 'LTBR', 'C', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col('Debit', '90', null, false, '1px solid ', 'BR', 'C', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col('Credit', '90', null, false, '1px solid ', 'BR', 'C', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col('     ', '20', null, false, '1px solid ', '', 'L', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col('Debit', '90', null, false, '1px solid ', 'LBR', 'C', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col('Credit', '90', null, false, '1px solid ', 'BR', 'C', $font, $fontsize10, 'B', '', '4px');

    return $str;
  }

  private function MMM_TRIAL_BALANCE_LAYOUT($params, $data)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize10 = '10';
    $fontsize11 = 11;

    $str = "";
    $count = 71;
    $page = 70;
    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_HEADER_LAYOUT($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    $totaldb = 0;
    $totalcr = 0;
    for ($i = 0; $i < count($data); $i++) {

      if ($data[$i]['amt'] < 0) {
        $cr = $data[$i]['amt'] * -1;
      } else {
        $cr = 0;
      }

      if ($data[$i]['amt'] > 0) {
        $db = $data[$i]['amt'];
      } else {
        $db = 0;
      }

      if ($data[$i]['detail'] == 1) {
        if ($db != 0 || $cr != 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($data[$i]['acno'], '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
          $str .= $this->reporter->col('     ', '30px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
          switch ($data[$i]['levelid']) {
            case 1:
              $str .= $this->reporter->col($data[$i]['acnoname'], '110px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
              break;
            case 2:
              $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '110px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
              break;
            case 3:
              $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '110px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
              break;
            default:
              $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['acnoname'], '110px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
              break;
          }
          $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

          if ($db == 0) {
            $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($db, 2), '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          }

          $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

          if ($cr == 0) {
            $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($cr, 2), '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
          }

          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acno'], '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('     ', '30px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($data[$i]['acnoname'], '110px', null, false, '1px solid ', '', 'L', $font, $fontsize10, 'B', '', '');
        $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('-', '20px', null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->endrow();
      }

      $totaldb = $totaldb + $cr;
      $totalcr = $totalcr + $db;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_HEADER_LAYOUT($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    } //END FOR EACH

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20px', null, false, '1px solid ', 'TB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '30px', null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '110px', null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '20px', null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('     ', '20px', null, false, '1px solid ', 'TB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '20px', null, false, '1px solid ', 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  public function roosevelt_query($filters)
  {
    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));

    $filter = "";
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['costcenter'];

    if ($center != '') {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($costcenter != "") {
      $costcenterid = $filters['params']['dataparams']['costcenterid'];
      $filter .= " and head.projectid=" . $costcenterid;
    }

    //cr -- left(coa.alias,2) in ('CA','CR','PC')

    switch ($isposted) {
      case 1: //unposted 

        $query = "select sum(amt) as amt, trans, doc from (

                      select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                      left join cntnum on cntnum.trno=head.trno
                      where head.doc='sj' and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "

                      union all

                      select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                      from lahead as head
                      left join ladetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                      left join cntnum on cntnum.trno=head.trno
                      where  head.doc='gd'  and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
                      union all
                      select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                      from lahead as head
                      left join ladetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                       left join cntnum on cntnum.trno=head.trno
                      where  head.doc='gc'  and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "

                      union all
                      select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                      from lahead as head
                      left join ladetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                      left join cntnum on cntnum.trno=head.trno
                      where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC') and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "

                      union all

                      select 0 as amt, 'credit' as trans,'Official Receipt (OR)' as doc
                      union all
                      select 0 as amt, 'credit' as trans,'Certificate of Appreciation (CA)' as doc) as xy
                      group by trans, doc order by trans desc";
        break;


      case 0: //posted 
        $query = "select sum(amt) as amt, trans, doc from (

                select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                 left join cntnum on cntnum.trno=head.trno
                where head.doc='sj' and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                union all

                select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                 left join cntnum on cntnum.trno=head.trno
                where  head.doc='gd' and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "
                union all
                select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                 left join cntnum on cntnum.trno=head.trno
                where  head.doc='gc' and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                union all
                select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                 left join cntnum on cntnum.trno=head.trno
                where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC') and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                union all

                select 0 as amt, 'credit' as trans,'Official Receipt (OR)' as doc
                union all
                select 0 as amt, 'credit' as trans,'Certificate of Appreciation (CA)' as doc) as xy
                group by trans, doc order by trans desc";
        break;

      case 2: // all

        $query = "select sum(amt) as amt, trans, doc from (
                      select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                      from glhead as head
                      left join glstock as stock on stock.trno=head.trno
                       left join cntnum on cntnum.trno=head.trno
                      where head.doc='sj' and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                      union all

                      select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                       left join cntnum on cntnum.trno=head.trno
                      where head.doc='sj' and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                      union all

                      select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                      from glhead as head
                      left join gldetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                       left join cntnum on cntnum.trno=head.trno
                      where  head.doc='gd' and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                      union all

                      select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                      from lahead as head
                      left join ladetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                       left join cntnum on cntnum.trno=head.trno
                      where  head.doc='gd'  and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "


                      union all

                      select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                      from glhead as head
                      left join gldetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                       left join cntnum on cntnum.trno=head.trno
                      where  head.doc='gc' and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                      union all


                      select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                      from lahead as head
                      left join ladetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                       left join cntnum on cntnum.trno=head.trno
                      where  head.doc='gc'  and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                      union all


                      select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                      from glhead as head
                      left join gldetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                       left join cntnum on cntnum.trno=head.trno
                      where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC') and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                      union all


                      select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                      from lahead as head
                      left join ladetail as d on d.trno=head.trno
                      left join coa on coa.acnoid=d.acnoid
                       left join cntnum on cntnum.trno=head.trno
                      where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC') and date(head.dateid) between  '" . $start . "' and '" . $end . "' " . $filter . "

                      union all

                      select 0 as amt, 'credit' as trans,'Official Receipt (OR)' as doc
                      union all
                      select 0 as amt, 'credit' as trans,'Certificate of Appreciation (CA)' as doc) as xy
                      group by trans, doc order by trans desc";
        break;
    } //END SWITCH

    // var_dump($query);
    $result = $this->coreFunctions->opentable($query);
    return $result;
  }
  private function default_displayHeader_roosevelt($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '12';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['due']));

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRIAL BALANCE', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

    $startdate = $start;
    $startt = new DateTime($startdate);
    $start = $startt->format('m/d/Y');

    $enddate = $end;
    $endd = new DateTime($enddate);
    $end = $endd->format('m/d/Y');

    $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', '',  'L', $font, '5', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', '',  'L', $font, '5', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '50', null, false,  '', '',  'L', $font, '5', '', '', '', '');
    $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', '',  'L', $font, '5', '', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function roosevelt_trial_balance_layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));

    $startt1 = new DateTime($start);
    $start1 = $startt1->format('F'); // October 01, 2025
    // var_dump($start1);
    // Kunin ang unang araw ng current month
    $firstOfMonth = date('Y-m-01', strtotime($start));
    // Bawasan ng 1 araw para makuha ang last day ng previous month
    $start = date('Y-m-d', strtotime($firstOfMonth . ' -1 day'));
    $startt = new DateTime($start);
    $start = $startt->format('F d, Y'); // October 01, 2025

    $fontsize10 = '10';
    $fontsize11 = 11;
    $layoutsize = '1000';

    $result = $this->roosevelt_query($config);
    $result2 = $this->balance_query($config);

    $str = "";
    $count = 71;
    $page = 70;
    $this->reporter->linecounter = 0;
    $trans = '';
    $tlamt = 0;
    $tlamt2 = 0;
    $totalbegbal = 0;
    if (empty($result) || empty($result2)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_roosevelt($config);

    $begbal = 0;
    $total1 = 0;
    $total2 = 0;
    foreach ($result2 as $key => $data2) {
      if ($data2->trans == 'debit') {
        $total1 = $data2->amt;
      } else {
        $total2 = $data2->amt;
      }
      $begbal = $total1 - $total2;
    }

    if ($begbal != 0) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Balance as of ' . $start, '400', null, false, '', 'TB', 'L', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '275', null, false, '', 'TBR', 'L', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '50', null, false,   '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col(number_format($begbal, 2), '275', null, false, '', 'TBR', 'R', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable($layoutsize);
    }


    foreach ($result as $key => $data) {

      $begbal = $begbal;
      if ($trans != $data->trans) {

        if ($trans != '') {

          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '275', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '50', null, false,   '', 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '275', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '400', null, false, '', 'B', 'L', $font, $fontsize11, 'B', '', '', '5px');
          $str .= $this->reporter->col(number_format($tlamt, 2), '275', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '5px');
          $str .= $this->reporter->col('', '50', null, false, '', '', 'L', $font, $fontsize11, 'B', '', '', '5px');
          $str .= $this->reporter->col(number_format($tlamt2, 2), '275', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '5px');

          $str .= $this->reporter->endrow();

          if ($trans == 'debit') {
            $tlbeg = $tlamt2 + $begbal;
            $totalbegbal = $tlbeg;
          } else {
            $tlbeg = $totalbegbal - $tlamt2;
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '50', null, false,   '', 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '275', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '400', null, false, '', 'B', 'L', $font, $fontsize11, 'B', '', '', '5px');
          $str .= $this->reporter->col('', '275', null, false, '', '', 'R', $font, $fontsize11, 'B', '', '', '5px');
          $str .= $this->reporter->col('', '50', null, false, '', '', 'L', $font, $fontsize11, 'B', '', '', '5px');
          $str .= $this->reporter->col(number_format($tlbeg, 2), '275', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '5px');


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->addline();


          $tlamt = 0;
          $tlamt2 = 0;

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', '',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', '',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '50', null, false,  '', '',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', '',  'L', $font, '3', '', '', '', '');
          $str .= $this->reporter->endrow();
        }

        $trans = $data->trans;
        $label = "";
        if ($trans == 'debit') {
          $label = "Add: Debit Transactions";
        } else {
          $label = "Less: Credit Transactions";
        }
        // $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($label . '&nbsp;&nbsp;&nbsp;' . ' -' . $start1, '400', null, false, '', 'TB', 'L', $font, $fontsize11 + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '275', null, false, '', 'TBR', 'L', $font, $fontsize11 + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '50', null, false, '', 'TBR', 'L', $font, $fontsize11 + 1, 'B', '', '', '5px');
        $str .= $this->reporter->col('', '275', null, false, '', 'TBR', 'L', $font, $fontsize11 + 1, 'B', '', '', '5px');
        $str .= $this->reporter->endrow();

        // $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;' . $data->doc, '400', null, false,  $border, '', 'L', $font, $fontsize11, '', '', '', '2px');
      $str .= $this->reporter->col(number_format($data->amt, 2), '275', null, false,  '', 'R', 'R', $font, $fontsize11, '', '', '', '2px');
      $str .= $this->reporter->col('', '50', null, false,  '', 'R', 'L', $font, $fontsize11, '', '', '', '2px');
      $str .= $this->reporter->col('', '275', null, false,  '', 'R', 'L', $font, $fontsize11, '', '', '', '2px');
      $str .= $this->reporter->endrow();

      $tlamt += $data->amt;
      $tlamt2 += $data->amt;
    }


    if ($trans != '') {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '50', null, false,   '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, '', 'B', 'L', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col(number_format($tlamt, 2), '275', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '50', null, false, '', '', 'L', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col(number_format($tlamt2, 2), '275', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->endrow();

      if ($trans == 'debit') {
        $tlbeg = $tlamt2 + $begbal;
        $totalbegbal = $tlbeg;
      } else {
        $tlbeg = $totalbegbal - $tlamt2; //absolute value 
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '50', null, false,   '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  $border, 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, '', 'B', 'L', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '275', null, false, '', '', 'R', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col('', '50', null, false, '', '', 'L', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->col(number_format($tlbeg, 2), '275', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '5px');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '50', null, false,   '', 'T',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  $border, 'B',  'L', $font, '3', '', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp;', '400', null, false,  '', '',  'L', $font, '1', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  '', 'B',  'L', $font, '1', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '50', null, false,   '', 'B',  'L', $font, '1', '', '', '', '');
      $str .= $this->reporter->col('&nbsp;', '275', null, false,  '3px solid', 'T',  'L', $font, '1', '', '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  public function balance_query($filters)
  {
    $company = $filters['params']['companyid'];
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));

    $filter = "";
    $center = $filters['params']['dataparams']['center'];
    $costcenter = $filters['params']['dataparams']['costcenter'];

    if ($center != '') {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($costcenter != "") {
      $costcenterid = $filters['params']['dataparams']['costcenterid'];

      $filter .= " and head.projectid=" . $costcenterid;
    }


    switch ($isposted) {
      case 1: //unposted 
        $query = "select sum(amt) as amt, trans from (

                select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnum on cntnum.trno=head.trno
                where head.doc='sj' and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                from lahead as head
                left join ladetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gd' and date(head.dateid) <  '" . $start . "' " . $filter . "
                union all
                select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                from lahead as head
                left join ladetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gc' and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all
                select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                from lahead as head
                left join ladetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC') and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select 0 as amt, 'credit' as trans,'Official Receipt (OR)' as doc
                union all
                select 0 as amt, 'credit' as trans,'Certificate of Appreciation (CA)' as doc) as xy
                group by trans order by trans desc";

        break;
      case 0: //posted
        $query = "select sum(amt) as amt, trans from (

                select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join cntnum on cntnum.trno=head.trno
                where head.doc='sj' and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gd' and date(head.dateid) <  '" . $start . "' " . $filter . "
                union all
                select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gc' and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all
                select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC') and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select 0 as amt, 'credit' as trans,'Official Receipt (OR)' as doc
                union all
                select 0 as amt, 'credit' as trans,'Certificate of Appreciation (CA)' as doc) as xy
                group by trans order by trans desc";

        break;

      case 2: // all
        $query = "select sum(amt) as amt, trans from (

                select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join cntnum on cntnum.trno=head.trno
                where head.doc='sj' and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select sum(stock.ext) as amt,'debit' as trans,'Sales Invoice (SI)' as doc
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnum on cntnum.trno=head.trno
                where head.doc='sj' and date(head.dateid) <  '" . $start . "' " . $filter . "


                union all

                select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gd' and date(head.dateid) <  '" . $start . "' " . $filter . "
               
                union all

                select sum(d.cr) as amt,'debit' as trans,'Debit Memo (DM)' as doc
                from lahead as head
                left join ladetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gd' and date(head.dateid) <  '" . $start . "' " . $filter . "
               
                union all

                select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gc' and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select sum(d.db) as amt,'credit' as trans,'Credit Memo (GC)' as doc
                from lahead as head
                left join ladetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='gc' and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC') and date(head.dateid) <  '" . $start . "' " . $filter . "


                union all

                select sum(d.db) as amt,'credit' as trans,'Collection Rcpt (CR)' as doc
                from lahead as head
                left join ladetail as d on d.trno=head.trno
                left join coa on coa.acnoid=d.acnoid
                left join cntnum on cntnum.trno=head.trno
                where  head.doc='cr' and left(coa.alias,2) in ('CA','CR','PC')  and date(head.dateid) <  '" . $start . "' " . $filter . "

                union all

                select 0 as amt, 'credit' as trans,'Official Receipt (OR)' as doc
                union all
                select 0 as amt, 'credit' as trans,'Certificate of Appreciation (CA)' as doc) as xy
                group by trans order by trans desc";
        break;
    } //END SWITCH
    // var_dump($query);
    $result = $this->coreFunctions->opentable($query);
    return $result;
  }
}//end class