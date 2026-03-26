<?php

namespace App\Http\Classes\modules\reportlist\construction_reports;

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
use Illuminate\Support\Facades\URL;

class site_monitoring_report
{
  public $modulename = 'Site Monitoring Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];



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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['start', 'end', 'project', 'prepared'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'project.name', "projectname");
    data_set($col2, 'project.required', true);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as project,
    '' as projectcode,
    '0' as projectid,
    '' as projectname,
    '' as prepared,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end
    ");
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

    $result = $this->default_layout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->default_query($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_query($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $projectid = $config['params']['dataparams']['projectid'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $filter = "";

    if ($projectid != 0) {
      $filter .= " and head.projectid = '$projectid'";
    }
    $filter .= " and date(head.dateid) between '$start' and '$end'";

    $query = "select p.code, p.name, sp.subproject, sm.stage, ss.substage, sa.subactivity, stock.iss, stock.isqty, stock.uom, left(i.barcode,3) as barcode, i.itemname, stock.trno, stock.line, head.docno from sostock as stock 
      left join sohead as head on head.trno = stock.trno and head.stageid = stock.stageid
      left join projectmasterfile as p on p.line = head.projectid
      left join subproject as sp on sp.line = head.subproject and sp.projectid = head.projectid
      left join stagesmasterfile as sm on sm.line = stock.stageid and head.stageid = sm.line 
      left join substages as ss on  ss.line = stock.substage
      left join psubactivity as ps on ps.line = stock.subactivity and ps.subactid = stock.subactid and stock.substage = ps.substage and ps.stage = head.stageid
      left join subactivity as sa on ps.line = sa.line
      left join item as i on stock.itemid = i.itemid
      where  1=1 $filter
      group by p.code, p.name, sp.subproject, sm.stage, ss.substage, sa.subactivity, stock.iss, stock.isqty, stock.uom, left(i.barcode,3), i.itemname, stock.trno, stock.line, head.docno
      union all
      select  p.code, p.name, sp.subproject, sm.stage, ss.substage, sa.subactivity, stock.iss, stock.isqty, stock.uom, left(i.barcode,3) as barcode, i.itemname, stock.trno, stock.line, head.docno 
      from hsostock as stock 
      left join hsohead as head on head.trno = stock.trno and head.stageid = stock.stageid
      left join projectmasterfile as p on p.line = head.projectid
      left join subproject as sp on sp.line = head.subproject and sp.projectid = head.projectid
      left join stagesmasterfile as sm on sm.line = stock.stageid and head.stageid = sm.line 
      left join substages as ss on  ss.line = stock.substage
      left join psubactivity as ps on ps.line = stock.subactivity and ps.subactid = stock.subactid and stock.substage = ps.substage and ps.stage = head.stageid
      left join subactivity as sa on ps.line = sa.line
      left join item as i on stock.itemid = i.itemid
      where  1=1 $filter
      group by p.code, p.name, sp.subproject, sm.stage, ss.substage, sa.subactivity, stock.iss, stock.isqty, stock.uom, left(i.barcode,3), i.itemname, stock.trno, stock.line, head.docno 
  ";
    return $query;
  }

  private function generateReportHeader($center, $username)
  {
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'L', 'Century Gothic', '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    return $str;
  } //end function generate report header

  public function default_header($config, $result)
  {
    $mdc = URL::to('/images/reports/mdc.jpg');
    $tuv = URL::to('/images/reports/tuv.jpg');

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $projectname  = $config['params']['dataparams']['projectname'];
    $projectcode  = $config['params']['dataparams']['projectcode'];
    $start  = date('M-d-Y', strtotime($config['params']['dataparams']['start']));
    $end  = date('M-d-Y', strtotime($config['params']['dataparams']['end']));
    $subprojectname = "";

    $str = "";
    $layoutsize = '2000';
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    $str .= "<div style='position: relative;'>";
    $str .= "<div style='position: absolute; left: 150px;'>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->generateReportHeader($center, $username);
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "<div style='position:absolute; top: 60px;'>";
    $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
    $str .= "</div>";
    $str .= "</div>";

    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SITE MONITORING REPORTS', '200', null, false, $border, '', 'L', $font, '14', 'b', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From: ' . $start . ' to ' . $end, '200', null, false, $border, '', 'L', $font, $fontsize, 'b', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($projectname == "") {
      $projectname = "ALL";
      $projectcode = "ALL";
      $location = "ALL";
    } else {
      $projectname = $config['params']['dataparams']['projectname'];
      $projectcode = $config['params']['dataparams']['projectcode'];
      $projectid = $config['params']['dataparams']['projectid'];
      $location = $this->coreFunctions->datareader("select wh as value from pmhead where projectid = $projectid union all select wh as value from hpmhead where projectid = $projectid ");
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Project Name</b>: ' . $projectname, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Location</b>: ' . $location, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item No.', '80', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Description', '200', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Unit', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PR/JR Date', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PR/JR#', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PR/JR QTY', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PO/JO DATE', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PO/JO#', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PO/JO QTY', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('MATERIAL RECEIVING DATE', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('MATERIAL RECEIVING NO.', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('MATERIAL RECEIVING', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('MATERIAL ISSUANCE / JO ACCOMPLISHMENT DATE', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('MATERIAL ISSUANCE / JO ACCOMPLISHMENT NO.', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('MATERIAL ISSUANCE / JO ACCOMPLISHMENT QTY', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('MATERIAL ISSUANCE INVENTORY ENDING AS OF', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PO/JO STATUS', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function default_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $count = 38;
    $page = 38;

    $str = '';
    $layoutsize = '2000';
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header($config, $result);

    $stage = '';
    $substage = '';
    $subactivity = '';
    $barcode = '';


    foreach ($result as $key => $value) {

      if ($stage != $value->stage) {

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($value->stage, '200', null, false, $border, 'BTLR', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->endrow();
      }

      if ($substage != $value->substage) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $value->substage, '200', null, false, $border, 'BTLR', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->endrow();
      }

      if ($subactivity != $value->subactivity) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $value->subactivity, '200', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($value->iss, 2), '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($value->uom, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->endrow();
      }

      $qry = "select prhead.docno as value
              from prstock
              left join prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line
              union all
              select prhead.docno as value 
              from hprstock as prstock 
              left join hprhead as prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line";
      $docno = $this->coreFunctions->datareader($qry);


      $qry = "select date(prhead.dateid) as value
              from prstock
              left join prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line
              union all
              select date(prhead.dateid) as value
              from hprstock as prstock 
              left join hprhead as prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line";
      $date = $this->coreFunctions->datareader($qry);


      $qry = "select prstock.rrqty as value
              from prstock
              left join prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line
              union all
              select prstock.rrqty as value
              from hprstock as prstock 
              left join hprhead as prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line";
      $qty = $this->coreFunctions->datareader($qry);

      $qry = "select prstock.trno, prstock.line
              from prstock
              left join prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line
              union all
              select prstock.trno, prstock.line
              from hprstock as prstock 
              left join hprhead as prhead on prhead.trno = prstock.trno
              where prstock.refx = $value->trno and prstock.linex = $value->line";
      $prdata = $this->coreFunctions->opentable($qry);

      $podocno = '';
      $podate = '';
      $poqty = '';
      $rrdocno = '';
      $rrdate = '';
      $rrqty = '';

      $jcdocno = '';
      $jcdate = '';
      $jcqty = '';
      $status = '';

      if (!empty($prdata)) {
        $qry = "select ifnull(docno,'') as docno, ifnull(date(dateid),'') as dateid, ifnull(qty,'') as qty from pohead as head
                left join postock as stock on head.trno = stock.trno
                where stock.refx = " . $prdata[0]->trno . " and stock.linex = " . $prdata[0]->line . "
                union all 
                select ifnull(docno,'') as docno, ifnull(date(dateid),'') as dateid, ifnull(qty,'') as qty from hpohead as head
                left join hpostock as stock on head.trno = stock.trno
                where stock.refx = " . $prdata[0]->trno . " and stock.linex = " . $prdata[0]->line . " 
                union all
                select ifnull(docno,'') as docno, ifnull(date(dateid),'') as dateid, ifnull(qty,'') as qty from johead as head
                left join jostock as stock on head.trno = stock.trno
                where stock.refx = " . $prdata[0]->trno . " and stock.linex = " . $prdata[0]->line . "
                union all 
                select ifnull(docno,'') as docno, ifnull(date(dateid),'') as dateid, ifnull(qty,'') as qty from hjohead as head
                left join hjostock as stock on head.trno = stock.trno
                where stock.refx = " . $prdata[0]->trno . " and stock.linex = " . $prdata[0]->line . " ";

        $podata = $this->coreFunctions->opentable($qry);

        if (!empty($podata)) {
          $podocno = $podata[0]->docno;
          $podate = $podata[0]->dateid;
          $poqty = number_format($podata[0]->qty, 2);
        }

        $qry = "select stock.trno, stock.line from hpohead as head
                left join hpostock as stock on head.trno = stock.trno
                where stock.refx = " . $prdata[0]->trno . " and stock.linex = " . $prdata[0]->line . " 
                union all
                select stock.trno, stock.line from hjohead as head
                left join hjostock as stock on head.trno = stock.trno
                where stock.refx = " . $prdata[0]->trno . " and stock.linex = " . $prdata[0]->line . " ";
        $hpodata = $this->coreFunctions->opentable($qry);

        if (!empty($hpodata)) {
          $qry = "select ifnull(docno,'') as docno, ifnull(date(dateid),'') as dateid, ifnull(qty,'') as qty from lahead as head
                  left join lastock as stock on head.trno = stock.trno
                  where stock.refx = " . $hpodata[0]->trno . " and stock.linex = " . $hpodata[0]->line . "
                  union all 
                  select ifnull(docno,'') as docno, ifnull(date(dateid),'') as dateid, ifnull(qty,'') as qty from glhead as head
                  left join glstock as stock on head.trno = stock.trno
                  where stock.refx = " . $hpodata[0]->trno . " and stock.linex = " . $hpodata[0]->line . " ";
          $rrdata = $this->coreFunctions->opentable($qry);

          if (!empty($rrdata)) {
            $rrdocno = $rrdata[0]->docno;
            $rrdate = $rrdata[0]->dateid;
            $rrqty = number_format($rrdata[0]->qty, 2);
          }

          $qry = "select ifnull(head.docno,'') as docno, ifnull(date(head.dateid),'') as dateid, 
                         ifnull(qty,'') as qty, stat.status 
                  from jchead as head
                  left join jcstock as stock on head.trno = stock.trno
                  left join transnum as num on num.trno = head.trno
                  left join trxstatus as stat on stat.line=num.statid
                  where stock.refx = " . $hpodata[0]->trno . " and stock.linex = " . $hpodata[0]->line . "
                  union all 
                  select ifnull(head.docno,'') as docno, ifnull(date(head.dateid),'') as dateid, 
                         ifnull(qty,'') as qty, stat.status 
                  from hjchead as head
                  left join hjcstock as stock on head.trno = stock.trno
                  left join transnum as num on num.trno = head.trno
                  left join trxstatus as stat on stat.line=num.statid
                  where stock.refx = " . $hpodata[0]->trno . " and stock.linex = " . $hpodata[0]->line . " ";
          $jcdata = $this->coreFunctions->opentable($qry);

          if (!empty($jcdata)) {
            $jcdocno = $jcdata[0]->docno;
            $jcdate = $jcdata[0]->dateid;
            $status = $jcdata[0]->status;
            $jcqty = number_format($jcdata[0]->qty, 2);
          }
        }

        if ($qty != '') {
          $qty = number_format($qty, 2);
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->barcode, '80', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $value->itemname, '200', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($value->iss, 2), '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($value->uom, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($date, '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($docno, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($qty, '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($podate, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($podocno, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($poqty, '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($rrdate, '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($rrdocno, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($rrqty, '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($jcdate, '90', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($jcdocno, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($jcqty, '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($status, '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->endrow();
      }

      $stage = $value->stage;
      $substage = $value->substage;
      $subactivity = $value->subactivity;
      $barcode = $value->barcode;
    } // end foreach

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class