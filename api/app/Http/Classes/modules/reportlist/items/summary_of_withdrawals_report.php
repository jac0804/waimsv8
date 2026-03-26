<?php

namespace App\Http\Classes\modules\reportlist\items;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class summary_of_withdrawals_report
{
  public $modulename = 'Summary of Withdrawals Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end'];

    $col1 = $this->fieldClass->create($fields);

    $fields = ['print'];

    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    $paramstr = "
    select 'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end
    ";

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

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  // QUERY
  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $query = "select
    right(dept.client,4) as costcenter,i.itemname as name,
    ic.cl_name as specification,i.uom,head.rem as remarks,
    ifnull(sum(stock.iss),0) as qty,
    ifnull(stock.cost,0) as cost,
    ifnull(sum(stock.ext),0) as amt
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client as dept on dept.clientid=head.deptid
    left join item as i on i.itemid=stock.itemid
    left join item_class as ic on ic.cl_id=i.class

    where head.doc in ('RM','RN') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
    group by dept.client,i.itemname,ic.cl_name,i.uom,head.rem,stock.cost
    union all
    select

    right(dept.client,4) as costcenter,i.itemname as name,
    ic.cl_name as specification,i.uom,head.rem as remarks,
    ifnull(sum(stock.iss),0) as qty,
    ifnull(stock.cost,0) as cost,
    ifnull(sum(stock.ext),0) as amt
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as dept on dept.clientid=head.deptid
    left join item as i on i.itemid=stock.itemid
    left join item_class as ic on ic.cl_id=i.class
    where head.doc in ('RM','RN') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
    group by dept.client,i.itemname,ic.cl_name,i.uom,head.rem,stock.cost
    order by name";
    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $date      = date("F, Y", strtotime($config['params']['dataparams']['start']));


    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SUMMARY OF WITHDRAWALS REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Month of: ' . $date, null, null, false, $border, '', '', $font, '18', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result  = $this->reportDefault($config);
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 11;
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $subtotal = 0;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('COST CENTER', 50, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NAME', 225, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SPECIFICATION', 150, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UNIT', 50, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('QTY', 60, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UNIT COST', 60, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COST', 60, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', 145, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');


    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->costcenter, 50, null, false, $border, 'TLB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->name, 225, null, false, $border, 'TLB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->specification, 150, null, false, $border, 'TLB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, 50, null, false, $border, 'TLB', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(number_format($data->qty, 2), 60, null, false, $border, 'TLB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2), 60, null, false, $border, 'TLB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2), 60, null, false, $border, 'TLB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->remarks, 145, null, false, $border, 'TLBR', 'L', $font, $fontsize, '', '', '');
    } //loop end

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class