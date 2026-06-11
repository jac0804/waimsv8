<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class analyze_customer_collection_monthly
{
  public $modulename = 'Analyzed Customer Collection Monthly';
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

    $fields = ['radioprint', 'dclientname', 'dcentername'];
    switch ($companyid) {
      case 10: // afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['year', 'radioposttype'];

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
      '' as client,
      '0' as clientid,
      '' as clientname,
      left(now(),4) as year,
      '0' as posttype,
      '' as dclientname,
      '" . $defaultcenter[0]['center'] . "' as center,
      '" . $defaultcenter[0]['centername'] . "' as centername,
      '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= ", '' as ddeptname, '' as dept, '' as deptname ";
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0': // POSTED
        $query = $this->reportDefault_POSTED($config);
        break;
      case  '1': // UNPOSTED
        $query = $this->reportDefault_UNPOSTED($config);
        break;
      case  '2': // ALL
        $query = $this->reportDefault_all($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_POSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $year     = $config['params']['dataparams']['year'];

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }

    if ($center != "") {
      $filter = $filter . " and cntnum.center='$center'";
    }

    if ($year != "") {
      $filter = $filter . " and year(glhead.dateid)='$year'";
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

    $query = "select client.client,client.clientname,year(glhead.dateid) as yr,
    sum(case when month(glhead.dateid)=1 then gldetail.db else 0 end) as mojan,
    sum(case when month(glhead.dateid)=2 then gldetail.db else 0 end) as mofeb,
    sum(case when month(glhead.dateid)=3 then gldetail.db else 0 end) as momar,
    sum(case when month(glhead.dateid)=4 then gldetail.db else 0 end) as moapr,
    sum(case when month(glhead.dateid)=5 then gldetail.db else 0 end) as momay,
    sum(case when month(glhead.dateid)=6 then gldetail.db else 0 end) as mojun,
    sum(case when month(glhead.dateid)=7 then gldetail.db else 0 end) as mojul,
    sum(case when month(glhead.dateid)=8 then gldetail.db else 0 end) as moaug,
    sum(case when month(glhead.dateid)=9 then gldetail.db else 0 end) as mosep,
    sum(case when month(glhead.dateid)=10 then gldetail.db else 0 end) as mooct,
    sum(case when month(glhead.dateid)=11 then gldetail.db else 0 end) as monov,
    sum(case when month(glhead.dateid)=12 then gldetail.db else 0 end) as modec
    from ((glhead
    left join gldetail on glhead.trno=gldetail.trno)
    left join client on glhead.clientid=client.clientid)
    left join cntnum on glhead.trno=cntnum.trno
    left join coa on gldetail.acnoid=coa.acnoid 
    where (coa.alias like '%ca%' or coa.alias like '%cr%' or coa.alias like '%pc%' or coa.alias like '%cb%') and glhead.doc='cr' $filter $filter1
    group by client,clientname,year(glhead.dateid)";
    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $year     = $config['params']['dataparams']['year'];

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }

    if ($center != "") {
      $filter = $filter . " and cntnum.center='$center'";
    }

    if ($year != "") {
      $filter = $filter . " and year(lahead.dateid)='$year'";
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

    $query = "select lahead.doc,lahead.docno,client.client,client.clientname,
    cntnum.center,coa.acno,coa.acnoname,year(lahead.dateid) as yr,
    sum(case when month(lahead.dateid)=1 then ladetail.db else 0 end) as mojan,
    sum(case when month(lahead.dateid)=2 then ladetail.db else 0 end) as mofeb,
    sum(case when month(lahead.dateid)=3 then ladetail.db else 0 end) as momar,
    sum(case when month(lahead.dateid)=4 then ladetail.db else 0 end) as moapr,
    sum(case when month(lahead.dateid)=5 then ladetail.db else 0 end) as momay,
    sum(case when month(lahead.dateid)=6 then ladetail.db else 0 end) as mojun,
    sum(case when month(lahead.dateid)=7 then ladetail.db else 0 end) as mojul,
    sum(case when month(lahead.dateid)=8 then ladetail.db else 0 end) as moaug,
    sum(case when month(lahead.dateid)=9 then ladetail.db else 0 end) as mosep,
    sum(case when month(lahead.dateid)=10 then ladetail.db else 0 end) as mooct,
      sum(case when month(lahead.dateid)=11 then ladetail.db else 0 end) as monov,
    sum(case when month(lahead.dateid)=12 then ladetail.db else 0 end) as modec
    from ((lahead
    left join ladetail on lahead.trno=ladetail.trno)
    left join client on lahead.client=client.client)
    left join cntnum on lahead.trno=cntnum.trno
    left join coa on ladetail.acnoid=coa.acnoid where (coa.alias like '%ca%' or coa.alias like '%cr%' or coa.alias like '%pc%' or coa.alias like '%cb%') and lahead.doc='CR' $filter $filter1
    group by ifnull(client.client,''),year(lahead.dateid),
    lahead.doc,lahead.docno,client.clientname,cntnum.center,coa.acno,coa.acnoname,client.client";
    return $query;
  }


  public function reportDefault_all($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $year     = $config['params']['dataparams']['year'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }

    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }

    if ($center != "") {
      $filter = $filter . " and cntnum.center='$center'";
    }

    if ($year != "") {
      $filter = $filter . " and year(lahead.dateid)='$year'";
      $filter2 = $filter2 . " and year(glhead.dateid)='$year'";
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

    $query = "select  client,clientname, yr,
            sum( mojan) as mojan, sum(mofeb) as mofeb, sum( momar) as momar, sum(moapr) as moapr,
            sum(momay) as momay, sum( mojun) as mojun,  sum(mojul) as mojul, sum( moaug) as moaug,
            sum( mosep) as mosep,  sum(mooct) as mooct, sum( monov) as monov, sum(modec) as modec
       
            from (
            select  client.client,client.clientname,year(glhead.dateid) as yr,
            sum(case when month(glhead.dateid)=1 then gldetail.db else 0 end) as mojan,
            sum(case when month(glhead.dateid)=2 then gldetail.db else 0 end) as mofeb,
            sum(case when month(glhead.dateid)=3 then gldetail.db else 0 end) as momar,
            sum(case when month(glhead.dateid)=4 then gldetail.db else 0 end) as moapr,
            sum(case when month(glhead.dateid)=5 then gldetail.db else 0 end) as momay,
            sum(case when month(glhead.dateid)=6 then gldetail.db else 0 end) as mojun,
            sum(case when month(glhead.dateid)=7 then gldetail.db else 0 end) as mojul,
            sum(case when month(glhead.dateid)=8 then gldetail.db else 0 end) as moaug,
            sum(case when month(glhead.dateid)=9 then gldetail.db else 0 end) as mosep,
            sum(case when month(glhead.dateid)=10 then gldetail.db else 0 end) as mooct,
            sum(case when month(glhead.dateid)=11 then gldetail.db else 0 end) as monov,
            sum(case when month(glhead.dateid)=12 then gldetail.db else 0 end) as modec
            from ((glhead
            left join gldetail on glhead.trno=gldetail.trno)
            left join client on glhead.clientid=client.clientid)
            left join cntnum on glhead.trno=cntnum.trno
            left join coa on gldetail.acnoid=coa.acnoid
             where (coa.alias like '%ca%' or coa.alias like '%cr%' or coa.alias like '%pc%' or coa.alias like '%cb%') and glhead.doc='cr' $filter2 $filter1
            group by client.client,client.clientname,year(glhead.dateid)

        union all

            select client.client,client.clientname,year(lahead.dateid) as yr,
            sum(case when month(lahead.dateid)=1 then ladetail.db else 0 end) as mojan,
            sum(case when month(lahead.dateid)=2 then ladetail.db else 0 end) as mofeb,
            sum(case when month(lahead.dateid)=3 then ladetail.db else 0 end) as momar,
            sum(case when month(lahead.dateid)=4 then ladetail.db else 0 end) as moapr,
            sum(case when month(lahead.dateid)=5 then ladetail.db else 0 end) as momay,
            sum(case when month(lahead.dateid)=6 then ladetail.db else 0 end) as mojun,
            sum(case when month(lahead.dateid)=7 then ladetail.db else 0 end) as mojul,
            sum(case when month(lahead.dateid)=8 then ladetail.db else 0 end) as moaug,
            sum(case when month(lahead.dateid)=9 then ladetail.db else 0 end) as mosep,
            sum(case when month(lahead.dateid)=10 then ladetail.db else 0 end) as mooct,
            sum(case when month(lahead.dateid)=11 then ladetail.db else 0 end) as monov,
            sum(case when month(lahead.dateid)=12 then ladetail.db else 0 end) as modec
            from ((lahead
            left join ladetail on lahead.trno=ladetail.trno)
            left join client on lahead.client=client.client)
            left join cntnum on lahead.trno=cntnum.trno
            left join coa on ladetail.acnoid=coa.acnoid where (coa.alias like '%ca%' or coa.alias like '%cr%' or coa.alias like '%pc%' or coa.alias like '%cb%') and lahead.doc='CR' $filter $filter1
            group by client.client,client.clientname,year(lahead.dateid)
            ) as x
            group by client,clientname, yr";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('ANALYZE CUSTOMER COLLECTION (MONTHLY)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow('200', null, false, $border, '', 'C', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Year : ' . strtoupper($year), '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');


    switch ($posttype) {
      case 0: //posted
        $posttype = 'Posted';
        break;
      case 1: //unposted
        $posttype = 'Unposted';
        break;
      case 2: //all
        $posttype = 'ALL';
        break;
    }

    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Center : ' . $filtercenter, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CLIENT NAME', '120', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
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

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

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

      $str .= $this->reporter->col($data->clientname, '120px', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
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
      $str .= $this->reporter->col(number_format($amt, 2), '100px', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');

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
        $str .= $this->default_displayHeader($config);
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
}//end class