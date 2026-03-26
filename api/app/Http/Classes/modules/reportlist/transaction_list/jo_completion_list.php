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

class jo_completion_list
{
  public $modulename = 'JO Completion List';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'dcentername', 'approved'];
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
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as `end`,
    '' as client,
    '0' as clientid,
    '' as clientname,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as posttype,
    '0' as reporttype, 
    'ASC' as sorting,
    '' as center,'' as dcentername,
    '' as dclientname,'' as reportusers
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
      case '0': // SUMMARIZED
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $query = $this->default_QUERY_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $query = $this->default_QUERY_DETAILED($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and cl.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( 
      select head.docno, cl.clientname as supplier,
      item.barcode, item.itemname, stock.uom,
      stock.rrqty, stock.rrcost, stock.disc, stock.ext,
      wh.clientname, head.createby, stock.loc,
      stock.rem, left(head.dateid,10) as dateid,stock.ref, cntnum.center
      from hjchead as head
      left join hjcstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as cntnum on cntnum.trno = head.trno
      left join client as cl on cl.client = head.client
      left join client as wh on wh.client = head.wh
      where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      case 1: // unposted
        $query = "select * from (
      select head.docno, cl.clientname as supplier,
      item.barcode, item.itemname, stock.uom,
      stock.rrqty, stock.rrcost, stock.disc, stock.ext,
      wh.clientname, head.createby, stock.loc,
      stock.rem, left(head.dateid,10) as dateid,stock.ref, cntnum.center
      from jchead as head
      left join jcstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as cntnum on cntnum.trno = head.trno
      left join client as cl on cl.client = head.client
      left join client as wh on wh.client = head.wh
      where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      default: // sana all
        $query = "select * from ( 
      select head.docno, cl.clientname as supplier,
      item.barcode, item.itemname, stock.uom,
      stock.rrqty, stock.rrcost, stock.disc, stock.ext,
      wh.clientname, head.createby, stock.loc,
      stock.rem, left(head.dateid,10) as dateid,stock.ref, cntnum.center
      from hjchead as head
      left join hjcstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as cntnum on cntnum.trno = head.trno
      left join client as cl on cl.client = head.client
      left join client as wh on wh.client = head.wh
      where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
      union all
      select head.docno, cl.clientname as supplier,
      item.barcode, item.itemname, stock.uom,
      stock.rrqty, stock.rrcost, stock.disc, stock.ext,
      wh.clientname, head.createby, stock.loc,
      stock.rem, left(head.dateid,10) as dateid,stock.ref, cntnum.center
      from jchead as head
      left join jcstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as cntnum on cntnum.trno = head.trno
      left join client as cl on cl.client = head.client
      left join client as wh on wh.client = head.wh
      where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;
    }

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
   $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
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
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "
      select docno,dateid,supplier,wh, whname,
      sum(ext) as amount, hrem, center 
      from (
        select head.docno, date(head.dateid) as dateid, stock.ext, 
        head.rem as hrem, cntnum.center,
        supp.clientname as supplier,
        wh.clientname as whname, wh.client as wh
        from hjchead as head
        left join hjcstock as stock on stock.trno = head.trno
        left join item as item on item.itemid = stock.itemid
        left join cntnum as cntnum on cntnum.trno = head.trno
        left join client as supp on supp.client = head.client
        left join client as wh on wh.client = head.wh
        where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
      ) as tbl
      group by docno,dateid,supplier,wh,whname,hrem,center
      order by docno $sorting";
        break;

      case 1: // unposted
        $query = "
        select docno,dateid,supplier,wh, whname,
        sum(ext) as amount, hrem, center 
        from (
          select head.docno, date(head.dateid) as dateid, stock.ext, 
          head.rem as hrem, cntnum.center,
          supp.clientname as supplier,
          wh.clientname as whname, wh.client as wh
          from jchead as head
          left join jcstock as stock on stock.trno = head.trno
          left join item as item on item.itemid = stock.itemid
          left join cntnum as cntnum on cntnum.trno = head.trno
          left join client as supp on supp.client = head.client
          left join client as wh on wh.client = head.wh
          where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
        ) as tbl
        group by docno,dateid,supplier,wh,whname,hrem,center
        order by docno $sorting";
        break;

      default: // sana all
        $query = "select docno,dateid,supplier,wh, whname,
      sum(ext) as amount, hrem, center 
      from (
        select head.docno, date(head.dateid) as dateid, stock.ext, 
        head.rem as hrem, cntnum.center,
        supp.clientname as supplier,
        wh.clientname as whname, wh.client as wh
        from hjchead as head
        left join hjcstock as stock on stock.trno = head.trno
        left join item as item on item.itemid = stock.itemid
        left join cntnum as cntnum on cntnum.trno = head.trno
        left join client as supp on supp.client = head.client
        left join client as wh on wh.client = head.wh
        where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
        union all 
        select head.docno, date(head.dateid) as dateid, stock.ext, 
        head.rem as hrem, cntnum.center,
        supp.clientname as supplier,
        wh.clientname as whname, wh.client as wh
        from jchead as head
        left join jcstock as stock on stock.trno = head.trno
        left join item as item on item.itemid = stock.itemid
        left join cntnum as cntnum on cntnum.trno = head.trno
        left join client as supp on supp.client = head.client
        left join client as wh on wh.client = head.wh
        where cntnum.doc='JC'  and date(head.dateid) between '$start' and '$end' $filter
      ) as tbl
      group by docno,dateid,supplier,wh,whname,hrem,center
      order by docno $sorting";
        break;
    }

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
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
    $str .= $this->reporter->col('JO Completion List (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Subcon: ' . $data->supplier, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);

    $total = 0;
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->supplier, '150', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '150', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->hrem, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $total = $total + $data->amount;
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Grand Total', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($total, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

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
    $str .= $this->reporter->col('Document No.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Name', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Amount', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class