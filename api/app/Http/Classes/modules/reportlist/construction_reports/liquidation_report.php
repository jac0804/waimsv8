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

class liquidation_report
{
  public $modulename = 'Liquidation Report';
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
    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['project', 'subprojectname', 'radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'project.name', "projectname");
    data_set($col2, 'project.required', false);
    data_set($col2, 'subprojectname.type', 'lookup');
    data_set($col2, 'subprojectname.lookupclass', 'lookupsubproject');
    data_set($col2, 'subprojectname.action', 'lookupsubproject');
    data_set($col2, 'subprojectname.addedparams', ['projectid']);
    data_set($col2, 'subprojectname.required', false);
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
    return $this->coreFunctions->opentable("select 
    'default' as print,
     adddate(left(now(),10),-360) as start,
    left(now(),10) as `end`,
    '0' as posttype,
    '0' as subproject,
    '' as subprojectname,
    '' as project,
    '' as projectcode,
    '0' as projectid,
    '' as projectname
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
    $posttype   = $config['params']['dataparams']['posttype'];

    $query = $this->default_query($config);


    return $this->coreFunctions->opentable($query);
  }

  public function default_query($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $subprojectid = $config['params']['dataparams']['subproject'];
    $projectid = $config['params']['dataparams']['projectid'];

    $filter = "";

    if ($projectid != 0) {
      $filter .= " and prj.line = '$projectid'";
    }

    if ($subprojectid != 0) {
      $filter .= " and sprj.line = '$subprojectid'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "
        select head.docno, date(head.dateid) as dateid,
        stock.location, stock.supplier, stock.address,
        stock.tin, stock.ref as ornumber, stock.particulars,
        stock.rrcost, stock.qty, stock.ext, stock.uom,
        stock.isvat, date(stock.ordate) as ordate, date(stock.dateid) as entrydate
        from hblhead as head
        left join hblstock as stock on stock.trno = head.trno
        left join transnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = head.projectid
        left join subproject as sprj on sprj.line = head.subproject
        where num.doc = 'BL' and date(head.dateid) between '$start' and '$end' $filter
        order by head.docno";
        break;
      case 1: // unposted
        $query = "select head.docno, date(head.dateid) as dateid,
        stock.location, stock.supplier, stock.address,
        stock.tin, stock.ref as ornumber, stock.particulars,
        stock.rrcost, stock.qty, stock.ext, stock.uom,
        stock.isvat, date(stock.ordate) as ordate, date(stock.dateid) as entrydate
        from blhead as head
        left join blstock as stock on stock.trno = head.trno
        left join transnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = head.projectid
        left join subproject as sprj on sprj.line = head.subproject
        where num.doc = 'BL' and date(head.dateid) between '$start' and '$end' $filter
        order by head.docno";
        break;
      default: // all
        $query = "
        select head.docno, date(head.dateid) as dateid,
        stock.location, stock.supplier, stock.address,
        stock.tin, stock.ref as ornumber, stock.particulars,
        stock.rrcost, stock.qty, stock.ext, stock.uom,
        stock.isvat, date(stock.ordate) as ordate, date(stock.dateid) as entrydate
        from hblhead as head
        left join hblstock as stock on stock.trno = head.trno
        left join transnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = head.projectid
        left join subproject as sprj on sprj.line = head.subproject
        where num.doc = 'BL' and date(head.dateid) between '$start' and '$end' $filter
        union all
        select head.docno, date(head.dateid) as dateid,
        stock.location, stock.supplier, stock.address,
        stock.tin, stock.ref as ornumber, stock.particulars,
        stock.rrcost, stock.qty, stock.ext, stock.uom,
        stock.isvat, date(stock.ordate) as ordate, date(stock.dateid) as entrydate
        from blhead as head
        left join blstock as stock on stock.trno = head.trno
        left join transnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = head.projectid
        left join subproject as sprj on sprj.line = head.subproject
        where num.doc = 'BL' and date(head.dateid) between '$start' and '$end' $filter
        order by docno
      ";
        break;
    }

    return $query;
  }

  public function default_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = "";
    $layoutsize = '1500';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MAXIPRO DEVELOPMENT CORPORATION', '1000', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PERIOD COVERD: ' . date('M d y', strtotime($start)) . ' - ' . date('M d y', strtotime($end)), '1000', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Liq. No.', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Location', '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Supplier', '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Address', '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('TIN Number', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('OR Number', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Particulars', '200', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Qty', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Purchase', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Input VAT', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Total Amount', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('OR Date', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('VAT', '50', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Entry Date', '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, 'B', '', '5px');

    return $str;
  }

  public function default_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 38;

    $str = '';
    $layoutsize = '1500';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header($config);
    $totalamt = 0;
    $totalpurchase = 0;
    $totalvat = 0;
    $totalext = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->location, '200', null, false, $border, 'LTRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->supplier, '200', null, false, $border, 'LTRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->address, '200', null, false, $border, 'LTRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ornumber, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->particulars, '200', null, false, $border, 'LTRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, $decimal), '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->rrcost, $decimal), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');


      $vat = 0;
      $purchase = 0;
      $vatstatus = "F";
      if ($data->isvat != 0) {
        $purchase = $data->rrcost / 1.12;
        $vat = ($data->rrcost / 1.12) * .12;
        $vatstatus = "T";
      }

      $str .= $this->reporter->col(number_format($purchase, $decimal), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($vat, $decimal), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->ext, $decimal), '100', null, false, $border, 'LTRB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ordate, '100', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($vatstatus, '50', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->entrydate, '50', null, false, $border, 'LTRB', 'C', $font, $fontsize, '', '', '');

      $totalamt += $data->rrcost;
      $totalpurchase += $purchase;
      $totalvat += $vat;
      $totalext += $data->ext;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($config);
        $str .= $this->reporter->endrow();

        $page = $page + $count;
      }
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totalamt, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totalpurchase, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totalvat, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Form No. FIN-004-0', '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('____________________________________<br><b>PREPARED BY:</b>', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('____________________________________<br><b>CHECKED/VERIFIED</b>', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '800', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class