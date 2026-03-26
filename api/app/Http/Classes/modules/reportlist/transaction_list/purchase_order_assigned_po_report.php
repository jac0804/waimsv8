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

class purchase_order_assigned_po_report
{
  public $modulename = 'Purchase Order Assigned PO Report';
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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'dcentername', 'assignedpodocno', 'approved'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
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
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,'' as clientname,'0' as posttype,'0' as reporttype,'' as dclientname,
                '" . $defaultcenter[0]['center'] . "' as center,
                '" . $defaultcenter[0]['centername'] . "' as centername,
                '" . $defaultcenter[0]['dcentername'] . "' as dcentername,'ASC' as sorting,'' as userid,'' as username,
                '' as approved,'' as reportusers,'' as assignedpodocno,'0' as potrno ,'0' as clientid ";


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

    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0: // summarized
        switch ($config['params']['companyid']) {
          default:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
        }

        break;

      case 1: // detailed
        switch ($config['params']['companyid']) {
          default: // default
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        } // end switch
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
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $potrno     = $config['params']['dataparams']['potrno'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }


    $filter1 .= "";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $addjoin = "";

    if ($potrno != "0") {
      $filter .= " and head.trno = '$potrno' ";
    }


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as wh on wh.client = head.wh
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO'  and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname, head.yourref
          order by docno $sorting";

            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname, head.yourref
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname, head.yourref
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='PO' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid, dept.client, dept.clientname, head.yourref
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "
          select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;

          default: // all
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from postock as stock
          left join pohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
          union all
          select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",
          stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,left(head.dateid,10) as dateid,stock.ref,
          dept.client as deptcode, dept.clientname as deptname, head.yourref
          from hpostock as stock
          left join hpohead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='PO' and date(head.dateid) between '$start' and '$end' $filter $filter1
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
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
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



    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;
    $pad1 = '';
    $pad2 = '';

    if ($companyid == 16) { //ati
      $pad1 = '';
      $pad2 = '';
    } else {
      $pad1 = '30px';
      $pad2 = '8px';
    }

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_detailed_DEFAULT($config);
    $docno = "";
    $i = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->printline();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Supplier: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PO No: ' . $data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', $pad1, $pad2);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'R', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', $pad1, $pad2);
        $str .= $this->reporter->endrow();


        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', $pad1, $pad2);
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
    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

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
        $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
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
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];



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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid != 8) { //not maxipro
      $sorting    = $config['params']['dataparams']['sorting'];
      $prefix     = $config['params']['dataparams']['approved'];
      $filterusername  = $config['params']['dataparams']['username'];
      if ($filterusername != "") {
        $user = $filterusername;
      } else {
        $user = "ALL USERS";
      }

      if ($sorting == 'ASC') {
        $sorting = 'Ascending';
      } else {
        $sorting = 'Descending';
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function header_detailed_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Purchase Order Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
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
    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO No.', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class