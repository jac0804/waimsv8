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

class warranty_request_report
{
  public $modulename = 'Warranty Request Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;


  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];



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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'approved'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
    $col2 = $this->fieldClass->create($fields);
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
    left(now(),10) as end,
    '' as client,
    '' as clientname,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as posttype,
    '0' as reporttype, 
    'ASC' as sorting,
    '' as center,
    '' as dclientname,'' as reportusers,
    '0' as clientid
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
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0: // summarized
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;

      case 1: // detailed
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

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
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from hwastock as stock
          left join hwahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as wh on wh.clientid = head.whid
          left join client as supp on supp.clientid = head.clientid
          where head.doc='WA'  and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from wastock as stock
          left join wahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid = head.clientid
          left join client as wh on wh.clientid = head.whid
          where head.doc='WA' and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from wastock as stock
          left join wahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid = head.clientid
          left join client as wh on wh.clientid = head.whid
          where head.doc='WA' and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from hwastock as stock
          left join hwahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid = head.clientid
          left join client as wh on wh.clientid = head.whid
          where head.doc='WA' and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,m.model_name as model
          from hwastock as stock
          left join hwahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid = head.clientid
          left join model_masterfile as m on m.model_id = item.model
          where head.doc='wa' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,m.model_name as model
          from wastock as stock
          left join wahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid = head.clientid
          left join model_masterfile as m on m.model_id = item.model
          where head.doc='wa' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          default: // all
            $query = "select head.docno,head.clientname as supplier,item.barcode,item.itemname,
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,m.model_name as model
          from wastock as stock
          left join wahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid = head.clientid
          left join model_masterfile as m on m.model_id = item.model
          where head.doc='wa' and head.dateid between '$start' and '$end' $filter
          union all
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,m.model_name as model
          from hwastock as stock
          left join hwahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.clientid = head.clientid
          left join model_masterfile as m on m.model_id = item.model
          where head.doc='wa' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;
        } // end switch posttype
        break;
    } // end switch

    return $query;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $i = 0;
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '','', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '','', '8px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '','', '8px');
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '','8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Quantity', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B','', '', '');
          $str .= $this->reporter->col('UOM', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B','', '', '');
          $str .= $this->reporter->col('SKU', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '','', '');
          $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B','', '', '');
          $str .= $this->reporter->col('Model', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '','', '');

          $str .= $this->reporter->col('Price', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B','', '', '');
          $str .= $this->reporter->col('Discount', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '','', '');
          $str .= $this->reporter->col('Total Price', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '','', '');
          $str .= $this->reporter->col('Location', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '','', '');
          $str .= $this->reporter->col('Reference', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B','', '', '');
          $str .= $this->reporter->col('Notes', '55', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '','', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '55', null, false, $border, '', 'C', $font, $fontsize, '','', '', '');
        $str .= $this->reporter->col($data->uom, '55', null, false, $border, '', 'C', $font, $fontsize, '', '','', '');
        $str .= $this->reporter->col($data->barcode, '55', null, false, $border, '', 'C', $font, $fontsize, '', '','', '');
        $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '','', '');
        $str .= $this->reporter->col($data->model, '150', null, false, $border, '', 'L', $font, $fontsize, '', '','', '');

        $str .= $this->reporter->col(number_format($data->rrcost, 2), '55', null, false, $border, '', 'C', $font, $fontsize, '','', '', '');
        $str .= $this->reporter->col($data->disc, '55', null, false, $border, '', 'C', $font, $fontsize, '','', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '55', null, false, $border, '', 'C', $font, $fontsize, '', '','', '');
        $str .= $this->reporter->col($data->loc, '55', null, false, $border, '', 'C', $font, $fontsize, '', '','', '');
        $str .= $this->reporter->col($data->ref, '55', null, false, $border, '', 'C', $font, $fontsize, '', '','', '');
        $str .= $this->reporter->col($data->rem, '55', null, false, $border, '', 'C', $font, $fontsize, '','', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B','', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warranty Request Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '','', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '','', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '','', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class