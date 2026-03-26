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

class supplier_performance_report
{
  public $modulename = 'Supplier Performance Report';
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
    $fields = ['radioprint', 'start', 'end', 'prepared', 'approved'];
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

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    // $companyid       = $config['params']['companyid'];

    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start, left(now(),10) as end, 
    '' as client, '' as clientname, '' as year, '' as approved, '' as prepared, '0' as posttype, '' as dclientname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '$center' as dcentername, '' as project, 0 as projectid, '' as projectname,
    0 as deptid, '' as ddeptname, '' as dept, '' as deptname";

    // NAME NG INPUT YUNG NAKA ALIAS
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
    return $this->reportDefaultLayout($config);;
  }

  public function reportDefault($config)
  {
    $center     = $config['params']['dataparams']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and stock.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $addedqry = '';
    switch ($companyid) {
      case 6: //MITSUKOSHI
        $addedqry = "
        union all
        select 'purchases' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
        (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, sum(stock.ext) as amount
        from glhead as head left join glstock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join client as agent on agent.clientid=head.agentid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('rp') and head.dateid between '$start' and '$end' " . $filter . "
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
        ";
        break;
    }

    $amount = ", sum(stock.ext) as amount";
    $join = "left join glstock as stock on stock.trno=head.trno";
    $docfilter = "head.doc='rr'";
    $amountfilter = "";

    if ($systemtype == 'AMS') {
      $amount = ",sum(detail.cr-detail.db) as amount";
      $join = "
      left join gldetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";
    }

    $query = "select client, clientname,sum(amount) as amount from (
    select 'purchases' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
    (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
    client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref $amount
    from glhead as head 
    $join
    left join client on client.clientid=head.clientid
    left join client as agent on agent.clientid=head.agentid
    left join cntnum on cntnum.trno=head.trno
    where $docfilter and head.dateid between '$start' and '$end' $filter $filter1 $amountfilter
    group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
    " . $addedqry . ") as s
    group by client,clientname
    order  by sum(s.amount) desc";

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault1($config)
  {
    $center     = $config['params']['dataparams']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $filter = "";
    $filter1 = "";
    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['project'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    $addedqry = "";

    switch ($companyid) {
      case 6: //MITSUKOSHI
        $addedqry = "
        union all
        select 'purchases' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
        (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref, sum(stock.ext) as amount
        from glhead as head left join glstock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join client as agent on agent.clientid=head.agentid
        left join cntnum on cntnum.trno=head.trno
        where head.doc = 'rp' and head.dateid between '$start' and '$end' " . $filter . "
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
      ";
        break;
    }

    $amount = ", sum(stock.ext) as amount";
    $join = "left join glstock as stock on stock.trno=head.trno";
    $docfilter = "head.doc='rr'";
    $amountfilter = "";

    if ($systemtype == 'AMS') {
      $amount = ",sum(detail.cr-detail.db) as amount";
      $join = "
      left join gldetail as detail on detail.trno=head.trno   
      left join coa as c on c.acnoid=detail.acnoid";
      $docfilter = "head.doc in ('PV','CV')";
      $amountfilter = "and detail.refx=0 and left(c.alias,2)='AP'";
    }

    $query = "select sum(amount) as amount from (
    select 'purchases' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
    (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
    client.client, client.clientname, agent.client as agcode, agent.clientname as agent, head.yourref $amount
    from glhead as head 
    $join
    left join client on client.clientid=head.clientid
    left join client as agent on agent.clientid=head.agentid
    left join cntnum on cntnum.trno=head.trno
    where $docfilter and head.dateid between '$start' and '$end' $filter $filter1 $amountfilter
    group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref
    " . $addedqry . "
    ) as s";

    return $this->coreFunctions->opentable($query);
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
    $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('SUPPLIER NAME', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PERCENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    return $str;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    // $filtercenter = $config['params']['dataparams']['center'];
    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];
    // $prepared     = $config['params']['dataparams']['prepared'];
    // $approved     = $config['params']['dataparams']['approved'];

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
    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER PERFORMANCE REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br>';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('DEPARTMENT : ' . $deptname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('ITEM GROUP : ' . $projname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result  = $this->reportDefault($config);
    $data1   = $this->reportDefault1($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $filtercenter = $config['params']['dataparams']['center'];
    // $start        = $config['params']['dataparams']['start'];
    // $end          = $config['params']['dataparams']['end'];
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->default_displayHeadertable($config);

    $percent = 0;
    $total = 0;
    $tpercent = 0;
    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($data1[0]->amount == 0) {
        $percent = $data->amount * 100;
      } else {
        if ($data->amount == 0) {
          $percent = 0;
        } else {
          $percent = ($data->amount / $data1[0]->amount) * 100;
        }
      }
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($percent, 2) . '%', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $total = $total + $data->amount;
      $tpercent = $tpercent + $percent;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->default_displayHeader($config);
        $str .= $this->default_displayHeadertable($config);
        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '150', null, false, $border, 'TB', 'R', 'Century Gothic', '10', 'B', '', '');


    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br><br>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}
