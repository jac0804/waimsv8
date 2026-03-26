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

class project_monitoring
{
  public $modulename = 'Poject monitoring';
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
      $filter .= " and prj.line = '$projectid'";
    }
    $filter .= " and date(head.dateid) between '$start' and '$end'";

    $query = "
    select * from (
      select 'BR' as transtype, '' as clientname,head.docno,head.dateid,sum(stock.ext) as amt,0 as bl,0 as jo,0 as pb,0 as basic,0 as ot,0 as misc, head.rem,
      head.address, prj.name , pmhead.tcp
      from brhead as head 
      left join brstock as stock on stock.trno = head.trno 
      left join projectmasterfile as prj on prj.line = head.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      where 1=1 $filter
      group by head.docno,head.dateid,head.rem, head.address, prj.name, pmhead.tcp
      union all
      select 'BR' as transtype, '' as clientname,head.docno,head.dateid,sum(stock.ext) as amt, 0 as bl,0 as jo,0 as pb,0 as basic,0 as ot,0 as misc, head.rem,
      head.address, prj.name, pmhead.tcp 
      from hbrhead as head 
      left join hbrstock as stock on head.trno = stock.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      left join (select h.brtrno,sum(s.ext) as ext 
      from blhead as h 
      left join blstock as s on s.trno = h.trno 
      group by h.brtrno
      union all
      select h.brtrno,sum(s.ext) as ext 
      from hblhead as h 
      left join hblstock as s on s.trno = h.trno group by h.brtrno) as bl on bl.brtrno = head.trno
      where 1=1 $filter
      group by head.docno,head.dateid,head.rem, head.address, prj.name, pmhead.tcp
    ) as tblbr
    union all
    select * from (
      select 'JO' as transtype, head.clientname as clientname,head.docno,head.dateid,0 as amt,0 as bl,ifnull(sum(stock.ext),0) as jo,0 as pb,0 as basic,0 as ot,0 as misc,head.rem ,
      head.address, prj.name, pmhead.tcp
      from johead as head 
      left join jostock as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      where 1=1 $filter
      group by head.docno,head.dateid,head.rem, head.clientname, head.address, prj.name, pmhead.tcp
      union all
      select 'JO' as transtype, head.clientname as clientname,head.docno,head.dateid,0 as amt,0 as bl,ifnull(sum(stock.ext),0) as jo,0 as pb,0 as basic,0 as ot,0 as misc,head.rem,
      head.address, prj.name, pmhead.tcp
      from hjohead as head 
      left join hjostock as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      where 1=1 $filter
      group by head.docno,head.dateid,head.rem, head.clientname, head.address, prj.name, pmhead.tcp
     union all
      select 'JC' as transtype, head.clientname, head.docno,head.dateid,0 as amt,0 as bl,ifnull(sum(stock.ext),0) as jo,0 as pb,0 as basic,0 as ot,0 as misc,head.rem ,
      head.address, prj.name, pmhead.tcp
      from jchead as head 
      left join jcstock as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      where 1=1 $filter
      group by head.docno,head.dateid,head.rem, head.clientname, head.address, prj.name, pmhead.tcp
      union all
      select 'JC' as transtype, head.clientname, head.docno,head.dateid,0 as amt,0 as bl,0 as jc,ifnull(sum(stock.ext),0) as pb,0 as basic,0 as ot,0 as misc,head.rem ,
      head.address, prj.name, pmhead.tcp
      from hjchead as head 
      left join hjcstock as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      where 1=1 $filter
      group by head.docno,head.dateid,head.rem, head.clientname, head.address, prj.name, pmhead.tcp
      order by clientname
    ) as tbljojc
    union all
    select * from (
      select 'S' as transtype, '' as clientname,head.docno,head.dateid,0 as amt,0 as bl,0 as jc,0 as pb,case when coa.alias = 'EX2' then sum(detail.db-detail.cr) else 0 end  as basic,0 as ot,
      case when  coa.alias = 'EX2' then 0 else sum(detail.db-detail.cr) end as misc,head.rem ,
      head.address, prj.name, pmhead.tcp
      from lahead as head 
      left join ladetail as detail on detail.trno = head.trno 
      left join coa on coa.acnoid = detail.acnoid 
      left join projectmasterfile as prj on prj.line = detail.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      where coa.cat ='E' $filter
      group by head.docno,head.dateid,head.rem, coa.acno, head.address, prj.name, pmhead.tcp,coa.alias
      union all
      select 'S' as transtype, '' as clientname,head.docno,head.dateid,0 as amt,0 as bl,0 as jc,0 as pb,case when coa.alias = 'EX2' then sum(detail.db-detail.cr) else 0 end as basic,0 as ot,
      case when coa.alias = 'EX2' then 0 else sum(detail.db-detail.cr) end as misc,head.rem ,
      head.address, prj.name, pmhead.tcp
      from glhead as head 
      left join gldetail as detail on detail.trno = head.trno 
      left join coa on coa.acnoid = detail.acnoid 
      left join projectmasterfile as prj on prj.line = detail.projectid
      left join pmhead as pmhead on pmhead.projectid = head.projectid
      where coa.cat ='E' $filter
      group by head.docno,head.dateid,head.rem, coa.acno, head.address, prj.name, pmhead.tcp,coa.alias
    ) as tbls
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
    $fontsize = "10";
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
    $str .= $this->reporter->col('PROJECT MONITORING', '200', null, false, $border, '', 'L', $font, '14', 'b', '', '5px');
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
      $location = $result[0]->address;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Project Name</b>: ' . $projectname, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Location</b>: ' . $location, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('<b>Contract Amount</b>: ' . number_format($result[0]->tcp, $decimal), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Contract No.</b>: ' . $projectcode, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('<b>Offshore</b>: ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('<b>Offshore</b>: ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Description', '250', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Approved Budget', '120', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Liquidation', '120', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Contract Amount', '120', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Progress Billing', '120', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Basic', '120', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('OT', '120', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Misc.', '120', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Remarks', '810', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
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
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header($config, $result);
    $transtype = "";
    $clientname = "";

    $subtotal_amt = 0;
    $subtotal_bl = 0;
    $subtotal_jo = 0;
    $subtotal_pb = 0;
    $subtotal_basic = 0;
    $subtotal_ot = 0;
    $subtotal_misc = 0;

    $grandtotal = 0;

    $subcon = "";
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      $type = "";
      if ($transtype != $data->transtype) {
        switch ($data->transtype) {
          case 'BR':
            $type = "BUDGET REQUEST";
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col($type, '250', null, false, $border, 'LRBT', 'L', $font, $fontsize, 'B', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '810', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->endrow();
            break;
          case 'JO':
          case 'JC':
            $type = "SUB-CONTRACTOR";
            if ($subcon == "") {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '100', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col($type, '250', null, false, $border, 'LRBT', 'L', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->col('', '810', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
              $str .= $this->reporter->endrow();


              $subcon = $data->transtype;
            }

            break;
          case 'S':
            $type = "SALARY";
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col($type, '250', null, false, $border, 'LRBT', 'L', $font, $fontsize, 'B', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col('', '810', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->endrow();
            break;
          default:
            $type = "";
            break;
        }
      }

      if ($data->transtype == "JC" || $data->transtype == "JO") {
        if ($clientname != $data->clientname) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col($data->clientname, '250', null, false, $border, 'LRBT', 'L', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->col('', '810', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
      }

      $mt = 0;
      $bl = 0;
      $jo = 0;
      $pb = 0;
      $basic = 0;
      $ot = 0;
      $misc = 0;

      if ($data->amt != 0) {
        $amt = number_format($data->amt, $decimal);
      } else {
        $amt = "";
      }

      if ($data->bl != 0) {
        $bl = number_format($data->bl, $decimal);
      } else {
        $bl = "";
      }

      if ($data->jo != 0) {
        $jo = number_format($data->jo, $decimal);
      } else {
        $jo = "";
      }

      if ($data->pb != 0) {
        $pb = number_format($data->pb, $decimal);
      } else {
        $pb = "";
      }

      if ($data->basic != 0) {
        $basic = number_format($data->basic, $decimal);
      } else {
        $basic = "";
      }

      if ($data->ot != 0) {
        $ot = number_format($data->ot, $decimal);
      } else {
        $ot = "";
      }

      if ($data->misc != 0) {
        $misc = number_format($data->misc, $decimal);
      } else {
        $misc = "";
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'LRBT', 'CT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->docno, '250', null, false, $border, 'LRBT', 'LT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($amt, '120', null, false, $border, 'LRBT', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($bl, '120', null, false, $border, 'LRBT', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($jo, '120', null, false, $border, 'LRBT', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($pb, '120', null, false, $border, 'LRBT', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($basic, '120', null, false, $border, 'LRBT', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($ot, '120', null, false, $border, 'LRBT', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($misc, '120', null, false, $border, 'LRBT', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->rem, '810', null, false, $border, 'LRBT', 'LT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->endrow();

      $transtype = $data->transtype;
      $clientname = $data->clientname;
      $subtotal_amt += $data->amt;
      $subtotal_bl += $data->bl;
      $subtotal_jo += $data->jo;
      $subtotal_pb += $data->pb;
      $subtotal_basic += $data->basic;
      $subtotal_ot += $data->ot;
      $subtotal_misc += $data->misc;
    }
    $grandtotal = $subtotal_bl  + $subtotal_pb +
      $subtotal_basic + $subtotal_ot + $subtotal_misc;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col("EQUIPMENT RENTAL", '250', null, false, $border, 'LRBT', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '810', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col("OTHERS", '250', null, false, $border, 'LRBT', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '810', null, false, $border, 'LRBT', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $subtotal_amt = $subtotal_amt != 0 ? number_format($subtotal_amt, $decimal) : "-";
    $subtotal_bl = $subtotal_bl != 0 ? number_format($subtotal_bl, $decimal) : "-";
    $subtotal_jo = $subtotal_jo != 0 ? number_format($subtotal_jo, $decimal) : "-";
    $subtotal_pb = $subtotal_pb != 0 ? number_format($subtotal_pb, $decimal) : "-";
    $subtotal_basic = $subtotal_basic != 0 ? number_format($subtotal_basic, $decimal) : "-";
    $subtotal_ot = $subtotal_ot != 0 ? number_format($subtotal_ot, $decimal) : "-";
    $subtotal_misc = $subtotal_misc != 0 ? number_format($subtotal_misc, $decimal) : "-";

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col($subtotal_amt, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col($subtotal_bl, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col($subtotal_jo, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col($subtotal_pb, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col($subtotal_basic, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col($subtotal_ot, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col($subtotal_misc, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '810', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('Total', '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($grandtotal, $decimal), '810', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By: ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($config['params']['dataparams']['prepared'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endreport();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class