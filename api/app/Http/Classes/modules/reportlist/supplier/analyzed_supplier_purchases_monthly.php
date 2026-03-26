<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class analyzed_supplier_purchases_monthly
{
  public $modulename = 'Analyzed Supplier Purchases Monthly';
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
    $fields = ['radioprint', 'dclientname', 'year'];
    switch ($companyid) {
      case 6: //mitsukoshi
        $col1 = $this->fieldClass->create($fields);
        break;
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'dcentername', 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      default:
        array_push($fields, 'dcentername');
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    $fields = ['radioposttype'];
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
    // $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print,'' as client,'' as clientname,left(now(),4) as year,'0' as posttype,'' as dclientname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    0 as projectid, '' as project, '' as projectname, 0 as deptid, '' as ddeptname, '' as dept, '' as deptname";

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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0':
        $query = $this->reportDefault_POSTED($config);
        break;
      case  '1':
        $query = $this->reportDefault_UNPOSTED($config);
        break;
      default:
        $query = $this->default_QUERY_ALL($config);
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_POSTED($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.client='$client'";
    }
    $center     = $config['params']['dataparams']['center'];
    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectid = $config['params']['dataparams']['projectid'];
      $project = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['deptid'];
      $deptname = $config['params']['dataparams']['ddeptname'];

      if ($project != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    switch ($companyid) {
      case 6: //mitsukoshi
        $addqry = "union all
        select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
        sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
        sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
        sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
        sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
        sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
        sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
        sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
        sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
        sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
        sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
        sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
        sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
        from ((glhead as head left join glstock as stock on stock.trno=head.trno)
        left join client on client.clientid=stock.suppid)left join cntnum on cntnum.trno=head.trno
        where head.doc='rp' and year(head.dateid)=$year  $filter $filter1 and stock.ext<>0
        group by ifnull(client.clientname,''), year(head.dateid)";
        break;
      default:
        $addqry = "";
        break;
    }
    $amount = "stock.ext";
    $join = "left join glstock as stock on stock.trno=head.trno";
    $docfilter = "head.doc='rr'";
    $amountfilter = "and stock.ext<>0";

    if ($systemtype == 'AMS') {
      $amount = "detail.cr-detail.db";
      $join = "
      left join gldetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";
    }
    $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
    sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
    sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
    select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
    sum(case when month(head.dateid)=1 then $amount else 0 end) as mojan,
    sum(case when month(head.dateid)=2 then $amount else 0 end) as mofeb,
    sum(case when month(head.dateid)=3 then $amount else 0 end) as momar,
    sum(case when month(head.dateid)=4 then $amount else 0 end) as moapr,
    sum(case when month(head.dateid)=5 then $amount else 0 end) as momay,
    sum(case when month(head.dateid)=6 then $amount else 0 end) as mojun,
    sum(case when month(head.dateid)=7 then $amount else 0 end) as mojul,
    sum(case when month(head.dateid)=8 then $amount else 0 end) as moaug,
    sum(case when month(head.dateid)=9 then $amount else 0 end) as mosep,
    sum(case when month(head.dateid)=10 then $amount else 0 end) as mooct,
    sum(case when month(head.dateid)=11 then $amount else 0 end) as monov,
    sum(case when month(head.dateid)=12 then $amount else 0 end) as modec
    from glhead as head 
    $join
    left join client on client.clientid=head.clientid
    left join cntnum on cntnum.trno=head.trno
    where $docfilter and year(head.dateid)=$year $filter $filter1 
    $amountfilter
    group by ifnull(client.clientname,''), year(head.dateid)
    $addqry
    ) as x group by clientname, yr order by clientname, yr
    ";

    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $year       = $config['params']['dataparams']['year'];
    $companyid       = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    $center     = $config['params']['dataparams']['center'];
    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectid = $config['params']['dataparams']['projectid'];
      $project = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['deptid'];
      $deptname = $config['params']['dataparams']['ddeptname'];

      if ($project != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    switch ($companyid) {
      case 6: //mitsukoshi
        $addqry = "
        union all
        select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
        sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
        sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
        sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
        sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
        sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
        sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
        sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
        sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
        sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
        sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
        sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
        sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
        from ((lahead as head left join lastock as stock on stock.trno=head.trno)
        left join client on client.clientid=stock.suppid)left join cntnum on cntnum.trno=head.trno
        where head.doc='rp' and year(head.dateid)=$year $filter  and stock.ext<>0
        group by ifnull(client.clientname,''), year(head.dateid)";
        break;
      default:
        $addqry = "";
        break;
    }

    $amount = "stock.ext";
    $join = "left join lastock as stock on stock.trno=head.trno";
    $docfilter = "head.doc='rr'";
    $amountfilter = "and stock.ext<>0";

    if ($systemtype == 'AMS') {
      $amount = "detail.cr-detail.db";
      $join = "
      left join ladetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";
    }

    $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
    sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
    sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
    select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
    sum(case when month(head.dateid)=1 then $amount else 0 end) as mojan,
    sum(case when month(head.dateid)=2 then $amount else 0 end) as mofeb,
    sum(case when month(head.dateid)=3 then $amount else 0 end) as momar,
    sum(case when month(head.dateid)=4 then $amount else 0 end) as moapr,
    sum(case when month(head.dateid)=5 then $amount else 0 end) as momay,
    sum(case when month(head.dateid)=6 then $amount else 0 end) as mojun,
    sum(case when month(head.dateid)=7 then $amount else 0 end) as mojul,
    sum(case when month(head.dateid)=8 then $amount else 0 end) as moaug,
    sum(case when month(head.dateid)=9 then $amount else 0 end) as mosep,
    sum(case when month(head.dateid)=10 then $amount else 0 end) as mooct,
    sum(case when month(head.dateid)=11 then $amount else 0 end) as monov,
    sum(case when month(head.dateid)=12 then $amount else 0 end) as modec
    from lahead as head 
    $join
    left join client on client.client=head.client
    left join cntnum on cntnum.trno=head.trno
    where $docfilter and year(head.dateid)=$year  $filter $filter1 
    $amountfilter
    group by ifnull(client.clientname,''), year(head.dateid)
    $addqry
    ) as x group by clientname, yr order by clientname, yr";
    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $posttype     = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.client='$client'";
    }
    $center     = $config['params']['dataparams']['center'];
    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $projectid = $config['params']['dataparams']['projectid'];
      $project = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['deptid'];
      $deptname = $config['params']['dataparams']['ddeptname'];

      if ($project != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    $from = '';
    if ($posttype == 0) {
      $from = '((glhead as head left join glstock as stock on stock.trno=head.trno)';
    }
    if ($posttype == 1) {
      $from = '((lahead as head left join lastock as stock on stock.trno=head.trno)';
    }

    switch ($companyid) {
      case 6: //mitsukoshi
        $addqry = "union all
        select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
        sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
        sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
        sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
        sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
        sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
        sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
        sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
        sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
        sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
        sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
        sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
        sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
        from $from
        left join client on client.clientid=stock.suppid)left join cntnum on cntnum.trno=head.trno
        where head.doc='rp' and year(head.dateid)=$year  $filter $filter1 and stock.ext<>0
        group by ifnull(client.clientname,''), year(head.dateid)";
        break;
      default:
        $addqry = "";
        break;
    }

    $amount = "stock.ext";
    $docfilter = "head.doc='rr'";
    $amountfilter = "and stock.ext<>0";
    $join = '';
    $join2 = '';

    if ($systemtype == 'AMS') {
      $amount = "detail.cr-detail.db";
      $join = "
      left join gldetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $join2 = "
      left join ladetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";
    }

    $query = "select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
    sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
    sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
    select 'p' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
    sum(case when month(head.dateid)=1 then $amount else 0 end) as mojan,
    sum(case when month(head.dateid)=2 then $amount else 0 end) as mofeb,
    sum(case when month(head.dateid)=3 then $amount else 0 end) as momar,
    sum(case when month(head.dateid)=4 then $amount else 0 end) as moapr,
    sum(case when month(head.dateid)=5 then $amount else 0 end) as momay,
    sum(case when month(head.dateid)=6 then $amount else 0 end) as mojun,
    sum(case when month(head.dateid)=7 then $amount else 0 end) as mojul,
    sum(case when month(head.dateid)=8 then $amount else 0 end) as moaug,
    sum(case when month(head.dateid)=9 then $amount else 0 end) as mosep,
    sum(case when month(head.dateid)=10 then $amount else 0 end) as mooct,
    sum(case when month(head.dateid)=11 then $amount else 0 end) as monov,
    sum(case when month(head.dateid)=12 then $amount else 0 end) as modec
    from glhead as head 
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join cntnum on cntnum.trno=head.trno
    $join
    where $docfilter and year(head.dateid)=$year $filter $filter1  
    $amountfilter
    group by ifnull(client.clientname,''), year(head.dateid) $addqry
    ) as x group by clientname, yr
    union all
    select clientname, yr, sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
    sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun, sum(mojul) as mojul, sum(moaug) as moaug,
    sum(mosep) as mosep, sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec from (
    select 'u' as tr, ifnull(client.clientname,'') as clientname, year(head.dateid) as yr,
    sum(case when month(head.dateid)=1 then $amount else 0 end) as mojan,
    sum(case when month(head.dateid)=2 then $amount else 0 end) as mofeb,
    sum(case when month(head.dateid)=3 then $amount else 0 end) as momar,
    sum(case when month(head.dateid)=4 then $amount else 0 end) as moapr,
    sum(case when month(head.dateid)=5 then $amount else 0 end) as momay,
    sum(case when month(head.dateid)=6 then $amount else 0 end) as mojun,
    sum(case when month(head.dateid)=7 then $amount else 0 end) as mojul,
    sum(case when month(head.dateid)=8 then $amount else 0 end) as moaug,
    sum(case when month(head.dateid)=9 then $amount else 0 end) as mosep,
    sum(case when month(head.dateid)=10 then $amount else 0 end) as mooct,
    sum(case when month(head.dateid)=11 then $amount else 0 end) as monov,
    sum(case when month(head.dateid)=12 then $amount else 0 end) as modec
    from lahead as head 
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join cntnum on cntnum.trno=head.trno
    $join2
    where $docfilter and year(head.dateid)=$year $filter $filter1  
    $amountfilter
    group by ifnull(client.clientname,''), year(head.dateid) $addqry
    ) as x group by clientname, yr
    order by clientname, yr;";
    return $query;
  }

  private function default_displayHeadertable($config)
  {
    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER NAME', '120', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JAN', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEB', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAR', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APR', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUN', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUL', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUG', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEP', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCT', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOV', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEC', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    return $str;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    // $client       = $config['params']['dataparams']['client'];
    // $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == '') {
      $filtercenter = 'ALL';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ANALYZED SUPPLIER PURCHASES (MONTHLY)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow('200', null, false, $border, '', 'C', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Year : ' . strtoupper($year), '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    // $year         = $config['params']['dataparams']['year'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->default_displayHeadertable($config);

    $totalmojan = 0;
    $totalmofeb = 0;
    $totalmomar = 0;
    $totalmoapr = 0;
    $totalmomay = 0;
    $totalmojun = 0;
    $totalmojul = 0;
    $totalmoaug = 0;
    $totalmosep = 0;
    $totalmooct = 0;
    $totalmonov = 0;
    $totalmodec = 0;
    $amt = 0;
    $totalamt = 0;
    foreach ($result as $key => $data) {
      $mojan = number_format($data->mojan, 2);
      if ($mojan < 1) {
        $mojan = '-';
      }
      $mofeb = number_format($data->mofeb, 2);
      if ($mofeb < 1) {
        $mofeb = '-';
      }
      $momar = number_format($data->momar, 2);
      if ($momar < 1) {
        $momar = '-';
      }
      $moapr = number_format($data->moapr, 2);
      if ($moapr < 1) {
        $moapr = '-';
      }
      $momay = number_format($data->momay, 2);
      if ($momay < 1) {
        $momay = '-';
      }
      $mojun = number_format($data->mojun, 2);
      if ($mojun < 1) {
        $mojun = '-';
      }
      $mojul = number_format($data->mojul, 2);
      if ($mojul < 1) {
        $mojul = '-';
      }
      $moaug = number_format($data->moaug, 2);
      if ($moaug < 1) {
        $moaug = '-';
      }
      $mosep = number_format($data->mosep, 2);
      if ($mosep < 1) {
        $mosep = '-';
      }
      $mooct = number_format($data->mooct, 2);
      if ($mooct < 1) {
        $mooct = '-';
      }
      $monov = number_format($data->monov, 2);
      if ($monov < 1) {
        $monov = '-';
      }
      $modec = number_format($data->modec, 2);
      if ($modec < 1) {
        $modec = '-';
      }

      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->clientname, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojan, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mofeb, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momar, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moapr, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($momay, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojun, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mojul, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($moaug, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mosep, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($mooct, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($monov, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col($modec, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');

      $totalmojan = $totalmojan + $data->mojan;
      $totalmofeb = $totalmofeb + $data->mofeb;
      $totalmomar = $totalmomar + $data->momar;
      $totalmoapr = $totalmoapr + $data->moapr;
      $totalmomay = $totalmomay + $data->momay;
      $totalmojun = $totalmojun + $data->mojun;
      $totalmojul = $totalmojul + $data->mojul;
      $totalmoaug = $totalmoaug + $data->moaug;
      $totalmosep = $totalmosep + $data->mosep;
      $totalmooct = $totalmooct + $data->mooct;
      $totalmonov = $totalmonov + $data->monov;
      $totalmodec = $totalmodec + $data->modec;
      $totalamt = $totalamt + $amt;

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->default_displayHeader($config);
        $str .= $this->default_displayHeadertable($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}
