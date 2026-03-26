<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class gatepass_out
{
  public $modulename = 'GATEPASS REPORT';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $fields = ['radioprint', 'start', 'end', 'dclientname'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployeelist');
    data_set($col1, 'dclientname.label', 'Employee');

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as dclientname,
    '' as client,
    '' as clientname";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= ", '' as ddeptname, '' as dept, '' as deptname";
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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    // 0 = SUMMARIZED / POSTED
    // 1 = DETAILED   / UNPOSTED    
    $query = $this->defaultQuery($config);
    return $this->coreFunctions->opentable($query);
  }

  public function defaultQuery($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    // 0 = SUMMARIZED / POSTED
    // 1 = DETAILED   / UNPOSTED

    $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client      = $config['params']['dataparams']['client'];
    $clientname  = $config['params']['dataparams']['clientname'];

    $filter = "";
    if ($clientname != "") {
      $filter .= " and emp.client = '$client'";
    }

    $query = "
    select head.trno, head.docno, head.client, emp.clientname, left(head.dateid, 10) as dateid,
    stock.itemid, item.barcode, item.itemname, stock.serialno
    from gphead as head
    left join gpstock as stock on stock.trno = head.trno
    left join item as item on item.itemid = stock.itemid and item.isfa = 1
    left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
    left join client as emp on emp.client = head.client
    where date(head.dateid) between '$start' and '$end'
    union all
    select head.trno, head.docno, head.client, emp.clientname, left(head.dateid, 10) as dateid,
    stock.itemid, item.barcode, item.itemname, stock.serialno
    from hgphead as head
    left join hgpstock as stock on stock.trno = head.trno
    left join item as item on item.itemid = stock.itemid and item.isfa = 1
    left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
    left join client as emp on emp.client = head.client
    where date(head.dateid) between '$start' and '$end'
    " . $filter . "
    order by docno";

    return $query;
  }

  public function defaultHeader_layout($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = "1000";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("GATEPASS REPORT", null, null, '', '', 'LRTB', 'L', $font, '18', 'B', '', '<br/>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, '', '', 'LRTB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->col('Docno', '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Name', '200', null, '', $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Barcode', '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '200', null, '', $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Serial #', '100', null, '', $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    // PRINT LAYOUT
    $result     = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $count = 56;
    $page = 55;
    $str = "";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->defaultHeader_layout($config);
    $docno = "";
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($docno != $data->docno) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '100', null, '', '', 'LRTB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, '', '', 'LRTB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, '', '', 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, '', '', 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, '', '', 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, '', '', 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, '', '', 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->serialno, '100', null, '', '', 'LRTB', 'L', $font, $fontsize, '', '', '');
      $docno = $data->docno;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->defaultHeader_layout($config);
        $page = $page + $count;
      }
    }
    return $str;
  }
}//end class