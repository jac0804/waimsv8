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

class zero_rated_report
{
  public $modulename = 'Zero Rated Sales Report';
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

    $fields = ['dateid', 'due', 'dcentername', 'project', 'ddeptname'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'project.required', false);
    data_set($col2, 'ddeptname.label', 'Department');
    data_set($col2, 'project.label', 'Item Group');

    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print,
      adddate(left(now(),10),-360) as dateid,
      adddate(left(now(),10),1) as due,
      '" . $defaultcenter[0]['center'] . "' as center,
      '" . $defaultcenter[0]['centername'] . "' as centername,
      '" . $defaultcenter[0]['dcentername'] . "' as dcentername, 
      0 as projectid, '' as project, '' as projectid, '' as projectname, 
      0 as deptid, '' as ddeptname, '' as dept, '' as deptname ";

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

  /// --- > TO BE FOLLOW
  public function default_query($filters)
  {
    $companyid = $filters['params']['companyid'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $filter = "";
    $filter1 = "";

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $filters['params']['dataparams']['project'];
      $deptname = $filters['params']['dataparams']['ddeptname'];
      if ($project != "") {
        $projectid = $filters['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid=" . $projectid;
      }
      if ($deptname != "") {
        $deptid = $filters['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
    }

    $query = "select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr, ifnull(agent.clientname,'') as agentname,
          head.docno ,sum(stock.ext) as db, sum(stock.ext) as cr, sum(stock.ext) as net, concat(bill.addrline1,' ',
          bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress,sum(stock.cost * stock.iss) as cost
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join client as agent on agent.clientid = head.agentid
          left join billingaddr as bill on bill.line = head.billid and bill.clientid = client.clientid
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('SJ','SD','SE','SF', 'AI') and head.tax = 0 and 
          head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1
          group by dateid, clientname, tin, addr, docno, billingaddress,agent.clientname order by dateid,docno";

         // var_dump($query);exit;

    $data = $this->coreFunctions->opentable($query);

    return $data;
  }

  public function reportplotting($config)
  {
    $result = $this->default_query($config);
    $reportdata =  $this->zero_rated_sales_layout($result, $config);
    return $reportdata;
  }

  // zero rated
  private function zero_rated_header($params)
  {
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

    $companyid = $params['params']['companyid'];
    $ccenter = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $params['params']['dataparams']['ddeptname'];
      $proj   = $params['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $params['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $params['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($ccenter, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Zero Rated Sales Report', null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period of ' . date('m/d/y', strtotime($start)) . ' - ' . date('m/d/y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ' . $deptname, '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project : ' . $projname, '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Customer', '150', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Agent', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Address', '225', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Tax ID No.', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Doc #', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Total', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Total Cost', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function zero_rated_sales_layout($data, $params)
  {
    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = $page = 40;
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }
    $group = $str = '';

    $a = $b = $c = $totala = $totalb = $totalc = $totalcost = 0;

    $cnt = count((array) $data);
    $cnt1 = 0;


    $str .= $this->reporter->beginreport('1000');

    #header here
    $str .= $this->zero_rated_header($params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable('1000');
    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->clientname, '150', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->agentname, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->billingaddress, '225', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->tin, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col(str_replace("DR", "SI", $data_->docno), '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col(number_format($data_->cr, $decimal_currency), '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col(number_format($data_->cost, $decimal_currency), '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->endrow();

      $dateid = $data_->dateid;
      $a += $data_->db;
      $b += $data_->cr;
      $c += $data_->net;
      $totala = $totala + $data_->db;
      $totalb = $totalb + $data_->cr;
      $totalc = $totalc + $data_->net;
      $totalcost = $totalcost + $data_->cost;
    } # end for loop

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '225', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($totalb, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($totalcost, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class
