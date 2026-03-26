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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class summary_sales_report
{
  public $modulename = 'Sales Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:3000px;max-width:3000px;';
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

    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end";
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

  private function summarized_query($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";
    $qry = "
    select sum(ifnull(stock.ext,0)) as total, ifnull(client.groupid,'') as cgroup, ifnull(agent.clientname,'') as agentname, 
    ifnull(p.name,'') as itemgroup  from lahead as head
    left join lastock as stock on head.trno = stock.trno
    left join client on head.client = client.client
    left join client as agent on head.agent = agent.client
    left join item on stock.itemid = item.itemid
    left join projectmasterfile as p on item.projectid = p.line
    where head.doc = 'SJ' and head.dateid between '" . $start . "' and  '" . $end . "'  
    group by client.groupid, agent.clientname, p.name
    union all
    select sum(ifnull(stock.ext,0)) as total, ifnull(client.groupid,'') as cgroup, ifnull(agent.clientname,'') as agentname, 
    ifnull(p.name,'') as itemgroup  from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join client on head.clientid = client.clientid
    left join client as agent on head.agentid = agent.clientid
    left join item on stock.itemid = item.itemid
    left join projectmasterfile as p on item.projectid = p.line
    where head.doc = 'SJ' and head.dateid between '" . $start . "' and  '" . $end . "'  
    group by client.groupid, agent.clientname, p.name
  ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  }

  public function reportDefault($config)
  {
    $data = $this->summarized_query($config);
    return $data;
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $result = $this->salesreport_summarized($config);
    return $result;
  }


  public function header_summarized($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $day = date("d", strtotime($config['params']['dataparams']['start']));
    $month = date("m", strtotime($config['params']['dataparams']['start']));
    $year = date("Y", strtotime($config['params']['dataparams']['start']));
    $week = $day + 6;
    $end = "$year-$month-$week";

    $str = '';
    $count = 38;
    $page = 40;

    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable($layoutsize);


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUMMARY SALES REPORT', '400', null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->col(date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '400', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->pagenumber('Page : ', '400', null, false, $border, '', 'R', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, '1px dotted', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Group', '200', null, false, '1px dotted', 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '200', null, false, '1px dotted', 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Group', '200', null, false, '1px dotted', 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total', '200', null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    return $str;
  }

  public function salesreport_summarized($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $this->reporter->linecounter = 0;
    $count = 68;
    $page = 70;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $dt = [];
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_summarized($config);
    $yield = 0;
    $totalpopulation = 0;
    $totaleggs = 0;
    $totalyield = 0;
    $totallteggs = 0;
    $totalltfeeds = 0;

    for ($i = 0; $i < count($result); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($result[$i]['cgroup'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($result[$i]['agentname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($result[$i]['itemgroup'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($result[$i]['total'], 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->header_summarized($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->endreport();

    return $str;
  }

  // -> add graphical this



}//end class
