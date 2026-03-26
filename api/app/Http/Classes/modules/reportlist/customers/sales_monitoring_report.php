<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class sales_monitoring_report
{
  public $modulename = 'Sales Monitoring Report';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'ditemname', 'categoryname', 'subcatname'];

    $col1 = $this->fieldClass->create($fields);


    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    $fields = ['radiosalescustomerperitem', 'print'];

    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    $paramstr = "
    select 'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    'sales' as options,
    '' as dclientname,
    '' as itemname,
    '' as barcode,
    '' as itemid,
    '' as categoryname,
    '' as category,
    '' as subcat,
    
    '' as subcat,
    '' as subcatname";

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
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];
    $itemid  = $config['params']['dataparams']['itemid'];
    $itemname  = $config['params']['dataparams']['itemname'];
    $category  = $config['params']['dataparams']['category'];
    $subcatname =  $config['params']['dataparams']['subcat'];


    $filter = "";

    if (
      $category != ""
    ) {
      $filter = $filter . " and item.category='$category'";
    }

    if (
      $subcatname != ""
    ) {
      $filter = $filter . " and item.subcat='$subcatname'";
    }


    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    } //end if

    if ($itemname != "") {
      $filter .= " and item.itemid='$itemid'";
    } //end if

    $fieldoption = 'stock.ext';
    if ($option == 'qty') {
      $fieldoption = 'stock.isqty';
    }

    $query = "select distinct c.areacode,
              ifnull(sum(port),0) as port, ifnull(sum(pozz),0) as pozz
              from client as c
                left join (
                select client.clientid,
                (case when icat.line=1 then $fieldoption else 0 end) as port,
                (case when icat.line=3114 then $fieldoption else 0 end) as pozz
                from lahead as head
                left join client on head.client=client.client
                left join lastock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as icat on icat.line=item.category
                where head.doc in ('SJ') $filter
                and date(head.dateid) between '$start' and '$end'
                union all
                select client.clientid,
                (case when icat.line=1 then $fieldoption else 0 end) as port,
                (case when icat.line=3114 then $fieldoption else 0 end) as pozz
                from glhead as head
                left join client on head.clientid=client.clientid
                left join glstock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as icat on icat.line=item.category
                where head.doc in ('SJ') $filter
                and date(head.dateid) between '$start' and '$end'
              ) as sa
              on sa.clientid=c.clientid
              group by c.areacode";

   
    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $item     = $config['params']['dataparams']['itemname'];
    $option     = $config['params']['dataparams']['options'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];



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
    
    $str .= $this->reporter->col('SALES MONITORING REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($item == '') {
      $str .= $this->reporter->col('Item : ALL', '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Item :' . $item, '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer :' . $client, '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($option) {
      $str .= $this->reporter->col('Option : AMOUNT', '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Option :' . strtoupper($option), '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL',  '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }


    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    
    $str .= $this->reporter->col('AREA CODE', '200', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PORTLAND', '200', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('POZZOLAN', '200', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function reportDefaultLayout($config)
  {
    // ini_set('memory_limit', '-1');

    $result  = $this->reportDefault($config);

  

    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $option     = $config['params']['dataparams']['options'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $subtotal = 0;
    $remtotal = 0;

    $clientname = "";
    $i = 0;
    $linetotal = 0;
    $grandtotal = 0;
    $porttotal = 0;
    $pozztotal = 0;
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
   
      $str .= $this->reporter->startrow();
      if ($data->areacode == '') {
        $str .= $this->reporter->col('No Area Code', '200', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->areacode, '200', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->col(number_format($data->port, 2), '200', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->pozz, 2), '200', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
      $linetotal = $data->port + $data->pozz;
      $grandtotal += $linetotal;
      $porttotal += $data->port;
      $pozztotal += $data->pozz;
      $str .= $this->reporter->col(number_format($linetotal, 2), '200', null, false, $border, 'TLR', 'R', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('TOTAL: ', '200', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($porttotal, 2), '200', null, false, $border, 'TLB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($pozztotal, 2), '200', null, false, $border, 'TLB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '200', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class