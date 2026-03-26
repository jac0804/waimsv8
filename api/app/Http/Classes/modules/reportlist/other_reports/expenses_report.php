<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class expenses_report
{
  public $modulename = 'Expense Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $fields = ['radioprint', 'start', 'end', 'dcentername'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    $fields = ['radioposttype', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '0' as posttype,
    '0' as reporttype,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    0 as deptid,
    '' as ddeptname, '' as dept, '' as deptname";

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $paramstr .= ", '' as ddeptname, '' as dept, '' as deptname";
    // }
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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $center      = $config['params']['dataparams']['center'];
    $isposted    = $config['params']['dataparams']['posttype'];
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['dataparams']['center'];

    if ($center == '') {
      $center = $config['params']['center'];
    }

    switch ($companyid) {
      case 8: //maxipro
        switch ($isposted) {
          case '1':
            $query = $this->maxipro_unposted($config);
            break;
          case '0':
            $query = $this->maxipro_posted($config);
            break;
          default:
            $query = $this->maxipro_ALL($config);
            break;
        }
        break;
      default: //all
        switch ($isposted) {
          case '1':
            $query = $this->defaultQuery_unposted($config);
            break;
          case '0':
            $query = $this->defaultQuery_posted($config);
            break;
          default:
            $query = $this->default_QUERY_ALL($config);
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function defaultQuery_unposted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['dataparams']['center'];

    $isdetailed  = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center      = $config['params']['dataparams']['center'];


    $deptid      = $config['params']['dataparams']['deptid']; //afti
    $dept = $config['params']['dataparams']['dept']; //afti

    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter = " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
    }

    switch ($isdetailed) {
      case '1': //detailed
        $query = "select docno,date(dateid) as dateid,clientname,acno,acnoname,snotes as description, sum(db-cr) as amount  from (
        select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname,  coa.acno, coa.acnoname,
        detail.db, detail.cr, info.rem as snotes
        from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
        where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ) as exp
        group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
        order by dateid, docno";
        break;
      case '0': //summarized
        $query = "select acno, acnoname, sum(db-cr) as amount
        from (select   coa.acno, coa.acnoname,
        detail.db, detail.cr
        from lahead as head left join ladetail as detail on detail.trno=head.trno 
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ) as exp
        group by acno, acnoname
        order by acnoname";
        break;
    }
    return $query;
  }


  public function defaultQuery_posted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['dataparams']['center'];
    $isdetailed  = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center      = $config['params']['dataparams']['center'];

    $deptid      = $config['params']['dataparams']['deptid']; //afti
    $dept = $config['params']['dataparams']['dept']; //afti
    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter = " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
    }

    switch ($isdetailed) {
      case '1': //detailed
        $query = "select docno,date(dateid) as dateid,clientname,acno,acnoname,snotes as description, sum(db-cr) as amount  from (
        select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname,coa.acno, coa.acnoname,
        detail.db, detail.cr, info.rem as snotes
        from glhead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
        where coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1) as exp
        group by docno, dateid, clientname, acno, acnoname, db, cr, snotes
        order by dateid, docno";
        break;
      case '0': //summarized
        $query = "select acno, acnoname, sum(db-cr) as amount
        from (select coa.acno, coa.acnoname, detail.db, detail.cr
        from glhead as head 
        left join gldetail as detail on detail.trno=head.trno 
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        where coa.cat='e' and date(head.dateid) 
        between '$start' and '$end' $filter $filter1) as exp
        group by acno, acnoname
        order by acnoname";
        break;
    }
    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['dataparams']['center'];
    $posttype    = $config['params']['dataparams']['posttype'];
    // 0 = SUMMARIZED / POSTED
    // 1 = DETAILED   / UNPOSTED

    $reporttype  = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center      = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter = " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $selectjcsum = '';
    $selectjcdet = '';
    $selecthjcsum = '';
    $selecthjcdet = '';

    if ($companyid == 8) { //maxipro
      // posted
      $selecthjcsum = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, detail.rem as snotes, detail.postdate
      from hjchead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      where head.doc='jc' and coa.cat='e' and date(head.dateid) between '$start' and '$end' $filter $filter1 ";

      $selecthjcdet = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, info.rem as snotes, detail.postdate
      from hjchead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";

      //unposted
      $selectjcsum = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, detail.rem as snotes, detail.postdate
      from jchead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";

      $selectjcdet = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, info.rem as snotes, detail.postdate
      from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";
    }


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 2: // all
            $query = "select acno, acnoname, sum(db-cr) as amount
            from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, detail.rem as snotes, detail.postdate
            from glhead as head
            left join gldetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('cv','pv') and coa.cat='e' and date(head.dateid) between '$start' and '$end' $filter $filter1 $selecthjcsum) as exp
            group by acno, acnoname
            union all
            (select acno, acnoname, sum(db-cr) as amount
            from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, detail.rem as snotes, detail.postdate
            from lahead as head
            left join ladetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcsum) as exp
            group by acno, acnoname)
            order by acnoname;";
            break;
        }
        break;
      case 1: // detailed
        switch ($posttype) {
          case 2:
            $query = "select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
            select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, info.rem as snotes, detail.postdate
            from glhead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
            where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selecthjcdet) as exp
            group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
            union all
            select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
            select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, info.rem as snotes, detail.postdate
            from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
            where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcdet) as exp
            group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
            order by dateid, docno;";
            break;
        }
        break;
    }

    return $query;
  }



  public function maxipro_unposted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['dataparams']['center'];
    $isposted    = $config['params']['dataparams']['posttype'];
    // 0 = SUMMARIZED / POSTED
    // 1 = DETAILED   / UNPOSTED

    $isdetailed  = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center      = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter = " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $selectjcsum = '';
    $selectjcdet = '';
    if ($companyid == 8) { //maxipro
      $selectjcsum = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, detail.rem as snotes, detail.postdate
      from jchead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";

      $selectjcdet = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, info.rem as snotes, detail.postdate
      from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";
    }
    switch ($isdetailed) {
      case '1':
        $query = "select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
        select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
        detail.db, detail.cr, info.rem as snotes, detail.postdate
        from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
        where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcdet) as exp
        group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
        order by dateid, docno";
        break;
      case '0':
        $query = "select acno, acnoname, sum(db-cr) as amount
        from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
        detail.db, detail.cr, detail.rem as snotes, detail.postdate
        from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcsum) as exp
        group by acno, acnoname
        order by acnoname";
        break;
    }
    return $query;
  }


  public function maxipro_posted($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['dataparams']['center'];
    $isposted    = $config['params']['dataparams']['posttype'];
    // 0 = SUMMARIZED / POSTED
    // 1 = DETAILED   / UNPOSTED

    $isdetailed  = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center      = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter = " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $selecthjcsum = '';
    $selecthjcdet = '';

    if ($companyid == 8) { //maxipro
      $selecthjcsum = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, detail.rem as snotes, detail.postdate
      from hjchead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      where head.doc='jc' and coa.cat='e' and date(head.dateid) between '$start' and '$end' $filter $filter1 ";
      $selecthjcdet = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, info.rem as snotes, detail.postdate
      from hjchead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";
    }

    switch ($isdetailed) {
      case '1':
        $query = "select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
        select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
        detail.db, detail.cr, info.rem as snotes, detail.postdate
        from glhead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
        where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selecthjcdet) as exp
        group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
        order by dateid, docno";
        break;
      case '0':
        $query = "select acno, acnoname, sum(db-cr) as amount
        from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
        detail.db, detail.cr, detail.rem as snotes, detail.postdate
        from glhead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('cv','pv') and coa.cat='e' and date(head.dateid) between '$start' and '$end' $filter $filter1 $selecthjcsum) as exp
        group by acno, acnoname
        order by acnoname";
        break;
    }
    return $query;
  }


  public function maxipro_ALL($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $center      = $config['params']['dataparams']['center'];
    $posttype    = $config['params']['dataparams']['posttype'];
    // 0 = SUMMARIZED / POSTED
    // 1 = DETAILED   / UNPOSTED

    $reporttype  = $config['params']['dataparams']['reporttype'];
    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center      = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter = " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $selectjcsum = '';
    $selectjcdet = '';
    $selecthjcsum = '';
    $selecthjcdet = '';

    if ($companyid == 8) { //maxipro
      // posted
      $selecthjcsum = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, detail.rem as snotes, detail.postdate
      from hjchead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      where head.doc='jc' and coa.cat='e' and date(head.dateid) between '$start' and '$end' $filter $filter1 ";

      $selecthjcdet = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, info.rem as snotes, detail.postdate
      from hjchead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";

      //unposted
      $selectjcsum = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, detail.rem as snotes, detail.postdate
      from jchead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";

      $selectjcdet = " union all select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
      detail.db, detail.cr, info.rem as snotes, detail.postdate
      from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
      where head.doc='jc' and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 ";
    }


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 2: // all
            $query = "select acno, acnoname, sum(db-cr) as amount
            from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, detail.rem as snotes, detail.postdate
            from glhead as head
            left join gldetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('cv','pv') and coa.cat='e' and date(head.dateid) between '$start' and '$end' $filter $filter1 $selecthjcsum) as exp
            group by acno, acnoname
            union all
            (select acno, acnoname, sum(db-cr) as amount
            from (select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, detail.rem as snotes, detail.postdate
            from lahead as head
            left join ladetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcsum) as exp
            group by acno, acnoname)
            order by acnoname;";
            break;
        }
        break;
      case 1: // detailed
        switch ($posttype) {
          case 2:
            $query = "select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
            select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, info.rem as snotes, detail.postdate
            from glhead as head left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
            where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selecthjcdet) as exp
            group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
            union all
            select docno,date(dateid) as dateid,clientname,hnotes,acno,acnoname,db,cr,snotes as description,postdate, sum(db-cr) as amount  from (
            select concat(left(head.docno,3),right(head.docno,5)) as docno, date(head.dateid) as dateid, head.clientname, head.rem as hnotes, coa.acno, coa.acnoname,
            detail.db, detail.cr, info.rem as snotes, detail.postdate
            from lahead as head left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno
            left join detailinfo as info on info.trno = detail.trno and info.line = detail.line
            where head.doc in ('cv','pv') and coa.cat='e' and head.dateid between '$start' and '$end' $filter $filter1 $selectjcdet) as exp
            group by docno, dateid, clientname, hnotes, acno, acnoname, db, cr, snotes, postdate
            order by dateid, docno;";
            break;
        }
        break;
    }

    return $query;
  }


  public function defaultHeader_layout($config, $title)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($title, null, null, '', $border, 'LRTB', 'C', $font, '18', 'B', '', '<br/>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= '<br>';
    $str .= $this->reporter->begintable('800');

    return $str;
  }
  public function reportDefaultLayout($config)
  {
    // PRINT LAYOUT
    $result     = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }


    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': //summarized
        $count = 56;
        $page = 55;
        $this->reporter->linecounter = 0;
        $str = '';
        $str .= $this->reporter->beginreport('800');
        $str .= $this->defaultHeader_layout($config, "EXPENSES REPORT SUMMARY");

        $totalsum = 0;

        foreach ($result as $key => $data) {
          $str .= $this->reporter->startrow();
          $sumamt = number_format($data->amount, 2);
          if ($sumamt == 0) {
            $sumamt = '-';
          }

          $str .= $this->reporter->col($sumamt, null, null, '', $border, 'LRTB', 'R', $font, '10', '', '', '5px');
          $str .= $this->reporter->col($data->acnoname, null, null, '', $border, 'LRTB', 'R', $font, '10', '', '', '5px');
          $totalsum = $totalsum + $data->amount;
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->addline();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->page_break();
            $str .= $this->defaultHeader_layout($config, "EXPENSES REPORT SUMMARY");
            $page = $page + $count;
          }
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL : ', null, null, '', $border, 'LRTB', 'R', $font, '10', 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totalsum, 2), null, null, '', $border, 'LRTB', 'R', $font, '10', 'B', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        break;
      case '1': //detailed
        $count = 56;
        $page = 55;
        $this->reporter->linecounter = 0;
        $str = '';

        $str .= $this->reporter->beginreport();
        $str .= $this->defaultHeader_layout($config, "EXPENSES REPORT DETAILED");
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
        $str .= $this->reporter->col('PCV#', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
        $str .= $this->reporter->col('NAME', '150', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
        $str .= $this->reporter->col('ACCT NAME', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
        $str .= $this->reporter->col('DESCRIPTION', '250', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');
        $str .= $this->reporter->col('AMOUNT', '100', null, '', $border, 'LRTB', 'C', $font, '10', 'B', '', '3px');

        $totaldet = 0;

        foreach ($result as $key => $data) {
          $str .= $this->reporter->startrow();
          $detamt = number_format($data->amount, 2);
          if ($detamt == 0) {
            $detamt = '-';
          }

          $str .= $this->reporter->col($data->dateid, '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->clientname, '150', null, '', $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->acnoname, '100', null, '', $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->description, '250', null, '', $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($detamt, '100', null, '', $border, 'LRTB', 'R', $font, $fontsize, '', '', '');
          $totaldet = $totaldet + $data->amount;
          $str .= $this->reporter->endrow();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->page_break();
            $str .= $this->defaultHeader_layout($config, "EXPENSES REPORT DETAILED");
            $page = $page + $count;
          }
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL : ', '100', null, '', $border, 'LTB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, '', $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, '', $border, 'TB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, '', $border, 'TB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '250', null, '', $border, 'TB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totaldet, 2), '100', null, '', $border, 'RTB', 'R', $font, '10', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        break;
    } // end switch
    return $str;
  }
}//end class