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

class unserved_purchase_order
{
  public $modulename = 'Unserved Purchase Order';
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
    $fields = ['radioprint', 'start', 'end', 'ditemname', 'dclientname'];
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
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as start,
    '' as end,
    '' as barcode,
    '' as itemname,
    '' as ditemname,
    '' as client,
    '' as dclientname
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
  // LAYOUT OF REPORT
  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout_DETAILED($config);

    return $result;
  }
  // RESULT QUERY
  public function reportDefault($config)
  {
    $query = $this->reportQuery_DETAILED($config);


    return $this->coreFunctions->opentable($query);
  }

  public function reportQuery_DETAILED($config)
  {
    // QUERY
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $barcode     = $config['params']['dataparams']['barcode'];
    $client     = $config['params']['dataparams']['client'];

    $filter = '';
    if ($client != '') {
      $filter .= " and supp.client = '$client'";
    }

    if ($barcode != '') {
      $filter .= " and i.barcode = '$barcode'";
    }

    $query = "
    select 'U' as tr,date_format(head.dateid,'%m/%d/%Y') as dateid,
    right(head.docno,8) as docno,supp.clientname,i.barcode,i.itemname,
    uom.uom,stock.rrqty,stock.qty,stock.qa,stock.qty-stock.qa as unserved 
    from pohead as head
    left join postock as stock on stock.trno=head.trno
    left join client as supp on supp.client=head.client
    left join item as i on i.itemid=stock.itemid
    left join uom on uom.itemid=i.itemid
    where head.dateid between '$start' and '$end' and stock.qa<>stock.qty $filter
    union all
    select 'P' as tr,date_format(head.dateid,'%m/%d/%Y') as dateid,
    right(head.docno,8) as docno,supp.clientname,i.barcode,i.itemname,
    uom.uom,stock.rrqty,stock.qty,stock.qa,stock.qty-stock.qa as unserved 
    from hpohead as head
    left join hpostock as stock on stock.trno=head.trno
    left join client as supp on supp.client=head.client
    left join item as i on i.itemid=stock.itemid
    left join uom on uom.itemid=i.itemid
    where head.dateid between '$start' and '$end' and stock.qa<>stock.qty $filter
    order by dateid,docno  
  ";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $client     = $config['params']['dataparams']['client'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('UNSERVED PURCHASE ORDER', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br /><br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col($start . ' - ' . $end, '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PO DATE', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('PO NO', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('SUPPLIER', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('ITEMNAME', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result  = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $barcode    = $config['params']['dataparams']['barcode'];
    $itemname   = $config['params']['dataparams']['itemname'];
    $client     = $config['params']['dataparams']['client'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->default_displayHeader($config);

    $str .= $this->reporter->begintable('1000');
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->unserved, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class