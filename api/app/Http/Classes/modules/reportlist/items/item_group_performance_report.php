<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class item_group_performance_report
{
  public $modulename = 'Item Group Performance Report';
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
    $fields = ['radioprint', 'start', 'end', 'dcentername', 'project', 'ddeptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'project.required', false);
    data_set($col1, 'ddeptname.label', 'Department');
    data_set($col1, 'project.label', 'Item Group');

    $fields = ['prepared', 'approved', 'radioreporttype', 'print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
      '' as center,
      '' as centername,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as prepared,
      '' as approved,
      '' as dcentername, 
      '' as project, 
      0 as projectid, 
      '' as projectname, 
      '' as ddeptname,
      0 as deptid,
      '' as dept, 
      '' as deptname,
      '0' as reporttype";

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
    $type = $config['params']['dataparams']['reporttype'];
    switch ($type) {
      case 0:
        $result = $this->reportDefaultLayout_Summary($config);
        break;

      default:
        $result = $this->reportDefaultLayout_Detailed($config);
        break;
    }

    return $result;
  }

  //SUMMARY START=====
  // QUERY FOR PERCENT PER ITEM for Summary
  public function reportDefault_Summary_Percent_Per_item($config)
  {
    // QUERY
    $center     = $config['params']['dataparams']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";
    $filter1 = "";
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $project = $config['params']['dataparams']['project'];
    $deptname = $config['params']['dataparams']['ddeptname'];
    if ($project != "") {
      $projectid = $config['params']['dataparams']['projectid'];
      $filter1 .= " and stock.projectid=" . $projectid;
    }
    if ($deptname != "") {
      $deptid = $config['params']['dataparams']['deptid'];
      $filter1 .= " and head.deptid=" . $deptid;
    }

    $query = "select proj.code, sum(stock.ext) as amount
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join cntnum on cntnum.trno=head.trno 
    left join projectmasterfile as proj on proj.line=stock.projectid
    where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end' 
    $filter $filter1
    group by proj.code
    order by sum(stock.ext) desc";

    return $this->coreFunctions->opentable($query);
  }
  // QUERY1 FOR OVERALL for Summary
  public function reportDefault_Summary_Overall($config)
  {
    // QUERY
    $center     = $config['params']['dataparams']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";
    $filter1 = "";
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $project = $config['params']['dataparams']['project'];
    $deptname = $config['params']['dataparams']['ddeptname'];
    if ($project != "") {
      $projectid = $config['params']['dataparams']['projectid'];
      $filter1 .= " and stock.projectid=" . $projectid;
    }
    if ($deptname != "") {
      $deptid = $config['params']['dataparams']['deptid'];
      $filter1 .= " and head.deptid=" . $deptid;
    }

    $query = "select sum(amount) as amount 
    from (select sum(stock.ext) as amount
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join cntnum on cntnum.trno=head.trno 
    where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end' 
    $filter $filter1
    ) as s ";

    return $this->coreFunctions->opentable($query);
  }
  // HEADER FOR SUMMARY
  private function default_displayHeader_Summary($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];

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
    $str .= $this->reporter->col($this->modulename . ' (Summary)', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ' . $deptname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Group : ' . $projname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM GROUP', '650', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');

    return $str;
  }
  // LAYOUT FOR SUMMARY
  public function reportDefaultLayout_Summary($config)
  {
    $result  = $this->reportDefault_Summary_Percent_Per_item($config);
    $data1   = $this->reportDefault_Summary_Overall($config);
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
    $str .= $this->default_displayHeader_Summary($config);
    $str .= $this->reporter->begintable($layoutsize);

    // $totalsales = number_format($data1[0]->amount, 2);
    $percent = 0;
    $total = 0;
    $tpercent = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $percent = ($data->amount / $data1[0]->amount) * 100;

      $str .= $this->reporter->col($data->code, '650', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $total = $total + $data->amount;
      $tpercent = $tpercent + $percent;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader_Summary($config);
        $page = $page + $count;
      }
    } //end for each

    $str .= $this->reporter->col('GRAND TOTAL :', '650', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

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
  //SUMMARY END=====

  //DETAIL START=====
  // QUERY FOR PERCENT PER ITEM for Detail
  public function reportDefault_Detailed_Percent_Per_Item($config)
  {
    // QUERY
    $center     = $config['params']['dataparams']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";
    $filter1 = "";
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

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

    $query = "select
    proj.code,ag.clientname,
    sum(stock.ext) as amount
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join projectmasterfile as proj on proj.line=stock.projectid
    left join client as ag on ag.clientid=head.agentid
    where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end' 
    $filter $filter1
    group by proj.code,ag.clientname
    order by code,clientname";

    return $this->coreFunctions->opentable($query);
  }
  // QUERY1 FOR OVERALL for Detail
  public function reportDefault_Detailed_Overall($config)
  {
    // QUERY
    $center     = $config['params']['dataparams']['center'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = "";
    $filter1 = "";
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

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

    $query = "select sum(amount) as amount from (
    select 
    sum(stock.ext) as amount
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end' 
    $filter $filter1
    ) as s";

    return $this->coreFunctions->opentable($query);
  }
  // HEADER FOR DETAILED
  private function default_displayHeader_Detail($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];

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
    $str .= $this->reporter->col($this->modulename . ' (Detailed)', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ' . $deptname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Group : ' . $projname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES', '650', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('AMOUNT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PERCENT', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  // LAYOUT FOR DETAILED
  public function reportDefaultLayout_Detailed($config)
  {
    $result  = $this->reportDefault_Detailed_Percent_Per_Item($config);
    $data1   = $this->reportDefault_Detailed_Overall($config);
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
    $str .= $this->default_displayHeader_Detail($config);
    $str .= $this->reporter->begintable($layoutsize);

    $percent = 0;
    $total = 0;
    $tpercent = 0;
    $spercent = 0;
    $part = '';
    $sub = 0;
    foreach ($result as $key => $data) {
      $percent = ($data->amount / $data1[0]->amount) * 100;

      if ($part == '' || $part != $data->code) {
        if ($part != $data->code && $part != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Subtotal:', '550', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($sub, 2), '175', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($spercent, 2) . '%', '175', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $sub = 0;
          $spercent = 0;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data->code, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '550', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      // $str .=$this->reporter->col($data->code.' ~ '.$data->clientname,'650',null,false,$border,'','L',$font, $fontsize,'','','1px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '550', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($percent, 2) . '%', '175', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $part = $data->code;
      $sub = $sub + $data->amount;
      $spercent = $spercent + $percent;
      $total = $total + $data->amount;
      $tpercent = $tpercent + $percent;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader_Detail($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    } //end for each
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Subtotal:', '550', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($sub, 2), '175', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($spercent, 2) . '%', '175', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $sub = 0;
    $spercent = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '550', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tpercent, 2) . '%', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

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
  //DETAIL END=====

}//end class