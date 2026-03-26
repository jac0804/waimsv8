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

class project_cost_and_expenses
{
  public $modulename = 'Project Cost and Expenses';
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


    $fields = ['start', 'project', 'radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', "As Of");
    data_set($col2, 'project.name', "projectname");
    data_set($col2, 'project.required', true);
    data_set($col2, 'start.required', true);


    data_set($col2, 'radioposttype.options', array(
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal',],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'ALL', 'value' => '2', 'color' => 'teal']
    ));

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
    '0' as posttype,
    left(now(),10) as start
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
    $query = "select acno, acnoname, acnoid,parent,levelid from coa where parent = '\\\\5' union all
              select acno, acnoname, acnoid,parent,levelid
              from coa
              where isprojexp=1 and cat <> 'E' order by acno";
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

    $posttype  = $config['params']['dataparams']['posttype'];
    switch ($posttype) {
      case 0:
        $posttype = "POSTED";
        break;

      case 1:
        $posttype = "UNPOSTED";

        break;

      default:
        $posttype = "ALL TRANSACTIONS";
        break;
    }

    $projectname = $config['params']['dataparams']['projectname'];
    $projectcode = $config['params']['dataparams']['projectcode'];
    $projectid = $config['params']['dataparams']['projectid'];
    $tcp = $this->coreFunctions->datareader("select tcp as value from pmhead where projectid = $projectid union all select tcp as value from hpmhead where projectid = $projectid ");
    $start  = date('M-d-Y', strtotime($config['params']['dataparams']['start']));

    $str = "";
    $layoutsize = '800';
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
    $str .= $this->reporter->col('PROJECT COST AND EXPENSES REPORTS', '200', null, false, $border, '', 'L', $font, '14', 'b', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AS OF: ' . $start, '200', null, false, $border, '', 'L', $font, $fontsize, 'b', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Project Name</b>: ' . $projectname, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Transaction Type</b> : ' . $posttype, '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Project Contract Amount - Onshore : <u>' . number_format($tcp, 2) . '</u><i> Php</i></b>', '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COA #', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('COST BREAKDOWN', '400', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('OVERALL COST', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('% COST RELATIVE', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
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

    $projectname  = $config['params']['dataparams']['projectname'];
    $projectcode  = $config['params']['dataparams']['projectcode'];
    $projectid = $config['params']['dataparams']['projectid'];

    $posttype  = $config['params']['dataparams']['posttype'];



    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));


    $tcp = $this->coreFunctions->datareader("select tcp as value from pmhead where projectid = $projectid union all select tcp as value from hpmhead where projectid = $projectid ");
    if ($tcp == 0) {
      return $this->othersClass->withoutcontractprice($config);
    }
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $count = 38;
    $page = 38;

    $str = '';
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header($config, $result);

    $acno = '';
    $filter = '';
    $color = 0;
    $subtot = 0;
    $tot = 0;
    $costr = 0;
    foreach ($result as $key => $value) {

      switch ($posttype) {
        case 0: // posted
          $qry = " select acno, acnoname, sum(db-cr) as tot from (
           select coa.acno, coa.acnoname, detail.db, detail.cr from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' and detail.projectid = " . $projectid . " $filter
          union all
          select coa.acno, coa.acnoname, detail.db, detail.cr from hjchead as head
          left join gldetail as detail on detail.trno = head.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' 
           and detail.projectid = " . $projectid . " $filter) as x
          group by acno, acnoname
          order by acno          
          ";
          break;

        case 1: // unposted
          $qry = " select acno, acnoname, sum(db-cr) as tot from (select coa.acno, coa.acnoname, detail.db, detail.cr from lahead as head
          left join ladetail as detail on detail.trno = head.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' and detail.projectid = " . $projectid . " $filter
          union all
          select coa.acno, coa.acnoname, detail.db, detail.cr from jchead as head
          left join ladetail as detail on head.trno = detail.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' 
          and detail.projectid = " . $projectid . " $filter) as x
          group by acno, acnoname
          order by acno          
          ";
          break;

        default: // all
          $qry = " select acno, acnoname, sum(db-cr) as tot from (select coa.acno, coa.acnoname, detail.db, detail.cr from lahead as head
          left join ladetail as detail on head.trno = detail.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' and detail.projectid = " . $projectid . " $filter
          union all
          select coa.acno, coa.acnoname, detail.db, detail.cr from jchead as head
          left join ladetail as detail on head.trno = detail.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' and detail.projectid = " . $projectid . " $filter
          union all
          select coa.acno, coa.acnoname, detail.db, detail.cr from glhead as head
          left join gldetail as detail on head.trno = detail.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' and detail.projectid = " . $projectid . " $filter
          union all
          select coa.acno, coa.acnoname, detail.db, detail.cr from hjchead as head
          left join gldetail as detail on head.trno = detail.trno
          left join coa on coa.acnoid = detail.acnoid
          left join projectmasterfile as pm on pm.line = detail.projectid
          left join subproject as sp on sp.line=detail.subproject and sp.projectid=pm.line
          where coa.isprojexp =1 and coa.parent = '\\" . $value->acno . "' and head.dateid <= '" . $start . "' 
          and detail.projectid = " . $projectid . " $filter) as x
          group by acno, acnoname
          order by acno          
          ";
          break;
      }
      $jks = $this->coreFunctions->opentable($qry);

      if (!empty($jks)) {
        if ($acno != $value->acno) {
          if ($acno != '') {
            $costd = $tcp / 100;
            $costr = $subtot / $costd;

            if ($subtot > 0) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '100', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->col('Subtotal', '400', null, $color, $border, 'BTLR', 'R', $font, $fontsize, 'BI', '', '5px');
              $str .= $this->reporter->col(number_format($subtot, 2), '100', null, $color, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->col(number_format($costr, 6) . ' %', '100', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
              $str .= $this->reporter->endrow();
            }

            $subtot = 0;
          }

          if ($key == 0) {
            $color = '#94C973';
          } else {
            $color = '#68BBE3';
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($value->acno, '100', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->col($value->acnoname, '400', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->col('', '100', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->col('', '100', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->endrow();
        }

        $rownum = 1;
        foreach ($jks as $keys => $jk) {
          $costd = $tcp / 100;
          $costr = $jk->tot / $costd;

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->col($jk->acnoname, '400', null, false, $border, 'BTLR', 'L', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->col(number_format($jk->tot, 2), '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->col(number_format($costr, 6) . ' %', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
          $str .= $this->reporter->endrow();

          $subtot += $jk->tot;
          $tot += $jk->tot;
          $rownum += 1;
        }
      }

      $acno = $value->acno;
    } // end foreach

    $costd = $tcp / 100;
    $costr = $subtot / $costd;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Subtotal', '400', null, $color, $border, 'BTLR', 'R', $font, $fontsize, 'BI', '', '5px');
    $str .= $this->reporter->col(number_format($subtot, 2), '100', null, $color, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($costr, 6) . ' %', '100', null, $color, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $costd = $tcp / 100;
    $costr = $tot / $costd;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Actual Cost Todate', '400', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'BI', '', '5px');
    $str .= $this->reporter->col(number_format($tot, 2), '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($costr, 6) . ' %', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $pbtot = $crtot = 0;

    switch ($posttype) {
      case 0: // posted
        $qry = "  select sum(cr) as value from (
            select cr from glhead  as head
            left join gldetail as detail on head.trno = detail.trno
            left join coa on coa.acnoid=detail.acnoid
            where doc = 'PB' and coa.alias ='SA1' and detail.projectid = " . $projectid . ") as xs";
        break;

      case 1: // unposted
        $qry = "  select sum(cr) as value from (select cr from lahead  as head
                left join ladetail as detail on head.trno = detail.trno
                left join coa on coa.acnoid=detail.acnoid
                where doc = 'PB' and coa.alias ='SA1' and detail.projectid = " . $projectid . ") as xs";
        break;

      default:
        $qry = "  select sum(cr) as value from (select cr from lahead  as head
                left join ladetail as detail on head.trno = detail.trno
                left join coa on coa.acnoid=detail.acnoid
                where doc = 'PB' and coa.alias ='SA1' and detail.projectid = " . $projectid . "
                union all
                select cr from glhead  as head
                left join gldetail as detail on head.trno = detail.trno
                left join coa on coa.acnoid=detail.acnoid
                where doc = 'PB' and coa.alias ='SA1' and detail.projectid = " . $projectid . ") as xs";
        break;
    }
    $pbtot = $this->coreFunctions->datareader($qry);

    $costd = $tcp / 100;
    $costr = $pbtot / $costd;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Progress Billing', '400', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'BI', '', '5px');
    $str .= $this->reporter->col(number_format($pbtot, 2), '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($costr, 6) . ' %', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    switch ($posttype) {
      case 0: // posted
        $qry = "  select sum(cr) as value from (
                select cr from glhead  as head
                left join gldetail as detail on head.trno = detail.trno
                left join coa on coa.acnoid=detail.acnoid
                where doc = 'CR' and detail.projectid = " . $projectid . " and coa.alias in ('AR1','AR4')) as xs";
        break;

      case 1:
        $qry = "  select sum(cr) as value from (select cr from lahead  as head
                left join ladetail as detail on head.trno = detail.trno
                left join coa on coa.acnoid=detail.acnoid
                where doc = 'CR' and detail.projectid = " . $projectid . " and coa.alias in ('AR1','AR4') ) as xs";
        break;

      default:
        $qry = "select sum(cr) as value
                from (select cr from lahead  as head
                      left join ladetail as detail on head.trno = detail.trno
                      left join coa on coa.acnoid=detail.acnoid
                      where doc = 'CR' and detail.projectid = " . $projectid . " and coa.alias in ('AR1','AR4')
                      union all
                      select cr from glhead  as head
                      left join gldetail as detail on head.trno = detail.trno
                      left join coa on coa.acnoid=detail.acnoid
                      where doc = 'CR' and detail.projectid = " . $projectid . " and coa.alias in ('AR1','AR4')) as xs";
        break;
    }
    $crtot = $this->coreFunctions->datareader($qry);

    $costd = $tcp / 100;
    $costr = $crtot / $costd;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Collected Amount', '400', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'BI', '', '5px');
    $str .= $this->reporter->col(number_format($crtot, 2), '100', null, false, $border, 'BTLR', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($costr, 6) . ' %', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class