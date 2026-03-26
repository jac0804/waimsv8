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

class customer_performance_report
{
  public $modulename = 'Customer Performance Report';
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS') {
      $fields = ['radioprint', 'start', 'end', 'dcentername'];
    } else {
      $fields = ['radioprint', 'start', 'end', 'dcentername', 'categoryname', 'subcatname'];
    }
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
    $fields = ['prepared', 'approved', 'print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start, left(now(),10) as end,'' as prepared,'' as approved,'' as category, '' as subcat,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= " ,'' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname ";
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout($config);

    return $result;
  }
  // QUERY
  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $fields = ['radioprint', 'start', 'end', 'dcentername', 'categoryname', 'subcatname'];

    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";

    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if (
      $subcatname != ""
    ) {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    $filter1 = "";
    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
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

    if ($systemtype == 'AMS') {
      $query = "select client, clientname, sum(amount) as amount
      from (
        select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
        (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
        client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(detail.cr-detail.db) as amount
        from glhead as head 
        left join gldetail as detail on detail.trno=head.trno 
        left join client on client.clientid=head.clientid
        left join client as agent on agent.clientid=head.agentid 
        left join cntnum on cntnum.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        where head.doc in ('sj', 'sd', 'se', 'sf') and date(head.dateid) between '" . $start . "' and '" . $end . "' and left(coa.alias,2) in ('SA', 'SD', 'SR')
        " . $filter . " " . $filter1 . "
        group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname) as s
        group by client, clientname order by sum(s.amount) desc";
    } else {
      $addfield = '';
      $addfield2 = '';
      if ($companyid == 32) { //3m
        $addfield = ",client.brgy, client.area";
        $addfield2 = ',brgy,area';
      }
      $query = "select client, clientname,sum(amount) as amount " . $addfield2 . "
      from (
      select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
      (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
      client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
      cat.name as category, subcat.name as subcatname " . $addfield . "
      from glhead as head 
      left join glstock as stock on stock.trno=head.trno
      left join client on client.clientid=head.clientid
      left join client as agent on agent.clientid=head.agentid
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join itemcategory as cat on cat.line = item.category
      left join itemsubcategory as subcat on subcat.line = item.subcat
      where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
      $filter $filter1 and item.isofficesupplies=0
      group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name " . $addfield . ") as s
      group by client,clientname " . $addfield2 . "
      order by sum(s.amount) desc";
    }

    return $this->coreFunctions->opentable($query);
  }
  // QUERY1
  public function reportDefault1($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if (
      $subcatname != ""
    ) {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    $filter1 = "";
    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $group = "";
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
      $group = "group by client,clientname";
      if ($companyid == 36) { // rozlab
        $group = "";
      }
    }

    $query = "select sum(amount) as amount
   
    from ( select 'sales' as type, 'u' as tr, (case when 'report'='date' then date(head.dateid) else head.docno end) as sort1,
    (case when 'report'='doc' then date(head.dateid) else head.docno end) as sort2, date(head.dateid) as dateid, head.docno,
    client.client, client.clientname, agent.client as agcode, agent.clientname as agent, sum(stock.ext) as amount,
      cat.name as category, subcat.name as subcatname
    
    from glhead as head 
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join client as agent on agent.clientid=head.agentid
    left join cntnum on cntnum.trno=head.trno
    
    left join item on item.itemid=stock.itemid
    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    
    where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
    $filter $filter1
    group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, cat.name, subcat.name) as s
    $group ";

    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];

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
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER PERFORMANCE REPORT', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CODE', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('CUSTOMER NAME', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    } elseif ($companyid == 32) { //3m
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('BARANGAY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AREA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    }
    return $str;
  }

  public function reportDefaultLayout($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      default:
        $result  = $this->reportDefault($config);
        $data1   = $this->reportDefault1($config);
        break;
    }

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];
    $prepared     = $config['params']['dataparams']['prepared'];
    $approved     = $config['params']['dataparams']['approved'];


    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);


    $percent = 0;
    $total = 0;
    $tpercent = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $percent = ($data->amount / $data1[0]->amount) * 100;
      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $str .= $this->reporter->col($data->client, '250', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } elseif ($companyid == 32) { //3m
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }
      $tpercent += $percent;
      $percent = 0;

      $str .= $this->reporter->endrow();
      $total = $total + $data->amount;


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    } //end for each


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '400', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } elseif ($companyid == 32) { //3m
      $str .= $this->reporter->col('GRAND TOTAL :', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('GRAND TOTAL :', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/><br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
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
}//end class