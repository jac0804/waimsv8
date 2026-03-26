<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class outsource_per_rfq_report
{
  public $modulename = 'Outsource Per RFQ Report';
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
    $fields = ['radioprint', 'yourref', 'frompart', 'topart', 'partno', 'model', 'brand'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'yourref.label', 'Reference#');
    data_set($col1, 'yourref.type', 'lookup');
    data_set($col1, 'yourref.lookupclass', 'lookuposrfq');
    data_set($col1, 'yourref.action', 'lookuposrfq');

    data_set($col1, 'partno.label', 'Afti Code');





    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {

    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      '' as yourref,
      '' as frompart,'' as topart,'' as partno,
      '' as brandname,'' as brandid,
      '' as modelid,'' as modelname,
      '' as model,
      '' as brand
      
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

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY

    $query = $this->default_QUERY($config);


    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {


    $brand  = $config['params']['dataparams']['brandid'];
    $model  = $config['params']['dataparams']['modelid'];
    $frompart  = $config['params']['dataparams']['frompart'];
    $topart  = $config['params']['dataparams']['topart'];
    $afticode  = $config['params']['dataparams']['partno'];
    $refno  = $config['params']['dataparams']['yourref'];



    $filter = '';
    if ($brand != "") {
      $filter .= " and brand.brandid  = '$brand'";
    }

    if ($model != "") {
      $filter .= " and m.model_id = '$model'";
    }

    if ($frompart != "" && $topart != "" && $afticode != "") {

      $filter .= " and item.partno between '$frompart' and '$topart' or item.partno = '$afticode'";
    } else {
      if ($frompart != "" && $topart != "") {
        $filter .= " and item.partno between '$frompart' and '$topart'";
      }
      if ($afticode != "") {
        $filter .= " and item.partno = '$afticode'";
      }
    }

    if ($refno != "") {
      $filter .= " and head.yourref like  '%$refno%'";
    }


    $query = "
    select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
    head.terms,head.rem, item.barcode, item.inhouse, item.partno,
    item.itemname, stock.qty as qty, stock.uom, stock.cost as netamt, stock.disc, stock.ext,
    m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stockinfo.leaddur, stockinfo.validity,
    head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
    head.deptid,dept.clientname as deptname, head.yourref, item.inhouse, part.part_name as partname,
    brand.brand_desc as brand,
    stock.currency, iteminfo.itemdescription,head.customer,stockinfo.isvalid,
    ifnull(stockinfo.ovaliddate,'') as ovaliddate,head.ostech,stockinfo.leadtimesettings
    from oshead as head
    left join osstock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid = stock.itemid
    left join iteminfo on item.itemid = iteminfo.itemid
    left join model_masterfile as m on m.model_id = item.model
    left join stockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
    left join client as wh on wh.client=head.wh
    left join client as dept on dept.clientid = head.deptid
    left join client as branch on branch.clientid = head.branch
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    where head.doc='os' $filter
    union all
    select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms,head.rem, item.barcode, item.inhouse, item.partno,
    item.itemname, stock.qty as qty, stock.uom, stock.cost as netamt, stock.disc, stock.ext,
    m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stockinfo.leaddur, stockinfo.validity,
    head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
    head.deptid,dept.clientname as deptname, head.yourref, item.inhouse, part.part_name as partname,
    brand.brand_desc as brand,
    stock.currency, iteminfo.itemdescription,head.customer,stockinfo.isvalid,
    ifnull(stockinfo.ovaliddate,'') as ovaliddate,head.ostech,stockinfo.leadtimesettings
    from hoshead as head
    left join hosstock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid = stock.itemid
    left join iteminfo on item.itemid = iteminfo.itemid
    left join model_masterfile as m on m.model_id = item.model
    left join stockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
    left join client as wh on wh.client=head.wh
    left join client as dept on dept.clientid = head.deptid
    left join client as branch on branch.clientid = head.branch
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    where head.doc='os' $filter
    order by yourref";

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '800';
    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $font =  "Times New Roman";
        break;

      default:
        $font = $this->companysetup->getrptfont($config['params']);
        break;
    }
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<span style="background-color: #FFFF00">AFTI IN-HOUSE CODE</span>', null, null, false, $border, '', '', $font, '30', 'BI', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TECHNICAL DEPT.', null, null, false, $border, '', '', $font, '25', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $str = '';
    $layoutsize = '800';
    switch ($config['params']['companyid']) {
      case 10:
      case 12:
        $font =  "Times New Roman";
        break;

      default:
        $font = $this->companysetup->getrptfont($config['params']);
        break;
    }
    $fontsize = "10";
    $border = "1px solid ";
    $border3 = "3px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->begintable($layoutsize);
    $count = 0;
    $yourref = '';
    foreach ($result as $key => $data) {

      if ($yourref == '' || $yourref != $data->yourref) {
        $str .= $this->reporter->endtable();
        $str .= $this->header_DEFAULT($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<span style="color:blue">Customer:</span>', '80', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->customer, '720', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<span style="color:blue">Reference#:</span>', '80', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->yourref, '720', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, $border3, 'T', '', $font, $fontsize, 'B', '', '', '', 0, 'border-color:red');
        $str .= $this->reporter->col('', '720', null, false, $border3, 'T', '', $font, $fontsize, 'B', '', '', '', 0, 'border-color:red');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, $border3, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '720', null, false, $border3, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }
      $yourref = $data->yourref;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, $border3, 'T', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '720', null, false, $border3, 'T', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Inhouse # :', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data->inhouse, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Selling Price #:', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data->netamt, 2), '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Curerncy : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data->currency, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Part : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data->model, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Brand : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data->brand, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Description : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data->itemdescription, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Quantity : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 0) . ' ' . $data->uom, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      switch (strtoupper($data->leadtimesettings)) {
        case 'EX-STOCK':
          $leadtime = 'LT: EX:STK; SUBJ TO PRIOR SALES; ';
          break;

        case '2-3 WEEKS':
          $leadtime = 'LT: 2 TO 3 WEEKS; SUBJ TO PRIOR SALES; ';
          break;

        default:
          $leadtime = $data->leaddur;
          break;
      }

      if ($data->isvalid) {
        $isreturn = 'NON-RETURNABLE AND NON-CANCELLABLE;';
      } else {
        $isreturn = '';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Leadtime : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($leadtime . ' ' . $isreturn, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Remarks : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data->itemrem, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();


      $validity = 0;

      switch ($data->validity) {
        case '15 Days':
          $validity = 15;
          break;
        case '30 Days':
          $validity = 30;
          break;
        case '45 Days':
          $validity = 45;
          break;
        case '60 Days':
          $validity = 60;
          break;
        default:
          if ($data->ovaliddate != '') {
            $validity = date("F d, Y", strtotime($data->ovaliddate));
          } else {
            $validity = '';
          }

          break;
      }
      if ($validity != '') {
        if ($data->validity != 'Others') {
          $d = $data->ovaliddate;
          $d = date_create($d);
          $d = date_format($d, "F d, Y");
          $d = date("F d, Y", strtotime($d . ' + ' . $validity . ' Days'));
          $d = date_create($d);
          $c = $d;
          $d = date_format($d, "F d, Y");
          $c = date_format($c, 'w');
          switch ($c) {
            case 0:
              $d = date("F d, Y", strtotime($d . ' + 1 Days'));
              break;
            case 6:
              $d = date("F d, Y", strtotime($d . ' + 2 Days'));
              break;
          }
        } else {
          $d = $data->ovaliddate;
          $d = date_create($d);
          $d = date_format($d, "F d, Y");
          $d = date("F d, Y", strtotime($d));
        }
      } else {
        $d = '';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Validity : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($d, '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $count++;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', '10', false, $border3, 'T', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '720', '10', false, $border3, 'T', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class