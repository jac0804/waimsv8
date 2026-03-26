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

class stock_issuance_report
{
  public $modulename = 'Stock Issuance Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];



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
    $fields = ['radioprint', 'start', 'end', 'ddeptname', 'reportusers', 'dcentername', 'approved'];
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
    '' as dept,'0' as deptid, '' as deptname,
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
    $company = $config['params']['companyid'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($company) {
      case 10: //afti
      case 12: //afti usd
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportAftiLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportAftiLayout_DETAILED($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
    }


    return $result;
  }

  public function tableheader_afti($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CUSTOMER', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT NO', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('REMARKS', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function header_afti($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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
    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Stock Issuance Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportAftiLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader_afti($layoutsize, $config);

    $totalqty = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalqty = $totalqty + $data->qty;
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
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '150', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }



  public function reportAftiLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '800', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '800', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Department: ' . $data->deptname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Model', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Brand', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->model, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->brand, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->col($data->ref, '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->hrem, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($docno == $data->docno) {
          $total += $data->qty;
        }
        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '800', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function afti_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptid     = $config['params']['dataparams']['deptid'];
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
    if ($dept != "") {
      $filter .= " and head.deptid = '$deptid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select 
                status, docno, supplier, qty, clientname, dateid, wh, deptcode,deptname
                from (
                select 'POSTED' as status,head.docno,
                head.clientname as supplier,sum(stock.isqty) as qty, wh.clientname, wh.client as wh,
                date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname,head.rem
                from glstock as stock
                left join glhead as head on head.trno=stock.trno
                left join item on item.itemid=stock.itemid
                left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid 
                left join client as wh on wh.clientid = head.whid
                left join client as dept on dept.clientid = head.deptid
                where head.doc='SU'
                and date(head.dateid) between '$start' and '$end' $filter 
                group by head.docno, head.clientname,
                wh.clientname, wh.client, head.dateid, dept.client, dept.clientname,head.rem
                
                ) as a
                order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status ,head.yourref,
                head.docno,head.clientname as supplier,
                sum(stock.isqty) as qty, wh.clientname, wh.client as wh,
                left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,head.rem
                from lastock as stock
                left join lahead as head on head.trno=stock.trno
                left join cntnum on cntnum.trno=head.trno
                left join client on client.client=head.client
                left join client as wh on wh.client = head.wh
                left join client as dept on dept.clientid = head.deptid
                where head.doc='SU' and head.dateid between '$start' and '$end' $filter 
                group by head.docno,head.yourref,head.clientname,
                wh.clientname,head.dateid, wh.client, dept.client, dept.clientname,head.rem
                order by head.docno $sorting";
        break;

      default: // sana all
        $query = "select * from (
                select 'UNPOSTED' as status ,
                head.docno,head.clientname as supplier,
                sum(stock.isqty) as qty, wh.clientname, wh.client as wh,head.yourref,
                left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,head.rem
                from lastock as stock
                left join lahead as head on head.trno=stock.trno
                left join cntnum on cntnum.trno=head.trno
                left join client on client.client=head.client
                left join client as wh on wh.client = head.wh
                left join client as dept on dept.clientid = head.deptid
                where head.doc='SU' and head.dateid between '$start' and '$end' $filter 
                group by head.docno,head.yourref,head.clientname,
                  wh.clientname,head.dateid, wh.client,dept.client,dept.clientname,head.rem

                UNION ALL

                select 'POSTED' as status,head.docno,
                head.clientname as supplier,sum(stock.isqty) as qty, wh.clientname,  wh.client as wh,head.yourref,
                left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname,head.rem
                from glstock as stock
                left join glhead as head on head.trno=stock.trno
                left join item on item.itemid=stock.itemid
                left join cntnum on cntnum.trno=head.trno
                left join client on client.clientid=head.clientid 
                left join client as wh on wh.clientid = head.whid
                left join client as dept on dept.clientid = head.deptid
                where head.doc='SU'
                and head.dateid between '$start' and '$end' $filter 
                group by head.docno,head.yourref,head.clientname,
                  wh.clientname,head.dateid, wh.client,dept.client, dept.clientname,head.rem

                ) as g order by g.docno $sorting";
        break;
    }

    return $query;
  }

  public function afti_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptid     = $config['params']['dataparams']['deptid'];
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
    if ($dept != "") {
      $filter .= " and dept.clientid = '$deptid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( select head.docno,head.clientname as deptname,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,eb.brand_desc as brand,mm.model_name as model
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join model_masterfile as mm on mm.model_id = item.model
      left join frontend_ebrands as eb on eb.brandid=item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      left join model_masterfile as model on model.model_id=item.model
      left join frontend_ebrands as brand on brand.brandid=item.brand
      left join iteminfo as i on i.itemid=item.itemid
      where head.doc='SU' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      case 1: // unposted
        $query = "select * from (
        select head.docno,head.clientname as deptname,item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,stock.uom,stock.iss,stock.isqty as qty,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center,eb.brand_desc as brand,mm.model_name as model
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join frontend_ebrands as eb on eb.brandid=item.brand
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        left join model_masterfile as model on model.model_id=item.model
        left join frontend_ebrands as brand on brand.brandid=item.brand
        left join iteminfo as i on i.itemid=item.itemid
        where head.doc='SU' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      default: // all
        $query = "select * from ( select head.docno,head.clientname as deptname,item.partno as barcode,concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,eb.brand_desc as brand,mm.model_name as model
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join model_masterfile as mm on mm.model_id = item.model
      left join frontend_ebrands as eb on eb.brandid=item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      left join model_masterfile as model on model.model_id=item.model
      left join frontend_ebrands as brand on brand.brandid=item.brand
      left join iteminfo as i on i.itemid=item.itemid
      where head.doc='SU' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      union all
      select head.docno,head.clientname as deptname,item.partno as barcode,concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,eb.brand_desc as brand,mm.model_name as model
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join model_masterfile as mm on mm.model_id = item.model
      left join frontend_ebrands as eb on eb.brandid=item.brand
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      left join model_masterfile as model on model.model_id=item.model
      left join frontend_ebrands as brand on brand.brandid=item.brand
      left join iteminfo as i on i.itemid=item.itemid
      where head.doc='SU' and stock.iss<>0  and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;
    }

    return $query;
  }


  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    $company = $config['params']['companyid'];
    switch ($company) {
      case 10: //afti
      case 12: //afti usd
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->afti_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->afti_QUERY_DETAILED($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->default_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->default_QUERY_DETAILED($config);
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptid     = $config['params']['dataparams']['deptid'];
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
    if ($dept != "") {
      $filter .= " and dept.clientid = '$deptid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select * from ( select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,prinfo.ctrlno,pr.docno as prdocno,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
      left join hprhead as pr on pr.trno=prinfo.trno
      where head.doc='SS' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      case 1: // unposted
        $query = "select * from (
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,prinfo.ctrlno,pr.docno as prdocno,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
        left join prhead as pr on pr.trno=prinfo.trno
        where head.doc='SS' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;

      default: // sana all
        $query = "select * from ( select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,prinfo.ctrlno,pr.docno as prdocno,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
      left join hprhead as pr on pr.trno=prinfo.trno
      where head.doc='SS' and stock.iss<>0 and date(head.dateid) between '$start' and '$end' $filter
      union all
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,prinfo.ctrlno,pr.docno as prdocno,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center
      from lastock as stock
      left join lahead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
      left join prhead as pr on pr.trno=prinfo.trno
      where head.doc='SS' and stock.iss<>0  and date(head.dateid) between '$start' and '$end' $filter
      ) as a order by docno,center $sorting";
        break;
    }
    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['dept'];
    $deptid     = $config['params']['dataparams']['deptid'];
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
    if ($dept != "") {
      $filter .= " and dept.clientid = '$deptid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(qty) as qty,hrem,center,ctrlno,prdocno from ( 
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
      client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
      stock.ref,client.client as wh,head.rem as hrem,cntnum.center,prinfo.ctrlno,pr.docno as prdocno
      from glstock as stock
      left join glhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join cntnum on cntnum.trno=head.trno
      left join client on client.clientid=stock.whid
      left join client as dept on dept.clientid=head.deptid
      left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
         left join hprhead as pr on pr.trno=prinfo.trno
      where head.doc='SS'  and date(head.dateid) between '$start' and '$end' $filter
      ) as a 
      group by docno,dateid,deptname,wh,clientname,hrem,center,ctrlno,prdocno
      order by docno $sorting";
        break;

      case 1: // unposted
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(qty) as qty,hrem,center,ctrlno,prdocno from ( 
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center,prinfo.ctrlno,pr.docno as prdocno
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
           left join hprhead as pr on pr.trno=prinfo.trno
        where head.doc='SS'  and date(head.dateid) between '$start' and '$end' $filter
        ) as a 
        group by docno,dateid,deptname,wh,clientname,hrem,center,ctrlno,prdocno
        order by docno $sorting";
        break;

      default: // sana all
        $query = "select docno,dateid,deptname,wh,clientname as whname,sum(qty) as qty,hrem,center,ctrlno,prdocno from ( 
        select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center,prinfo.ctrlno,pr.docno as prdocno
        from glstock as stock
        left join glhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
           left join hprhead as pr on pr.trno=prinfo.trno
        where head.doc='SS'  and date(head.dateid) between '$start' and '$end' $filter
      union all
      select head.docno,head.clientname as deptname,item.barcode,item.itemname,stock.uom,stock.iss,stock.isqty as qty,
        client.clientname,head.createby,stock.expiry,stock.loc,stock.rem,left(head.dateid,10) as dateid,
        stock.ref,client.client as wh,head.rem as hrem,cntnum.center,prinfo.ctrlno,pr.docno as prdocno
        from lastock as stock
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join cntnum on cntnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as dept on dept.clientid=head.deptid
        left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
           left join hprhead as pr on pr.trno=prinfo.trno
        where head.doc='SS'  and date(head.dateid) between '$start' and '$end' $filter
      ) as a 
      group by docno,dateid,deptname,wh,clientname,hrem ,center,ctrlno,prdocno
      order by docno $sorting";
        break;
    }

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Stock Issuance Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $str = '';

    switch ($companyid) {
      case 16: //ati
        $layoutsize = '1000';
        break;
      default:
        $layoutsize = '800';
        break;
    }

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $docno = "";
    $total = 0;

    foreach ($result as $key => $data) {
      if ($docno != "" && $docno != $data->docno) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '900', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }

      if ($docno == "" || $docno != $data->docno) {
        $docno = $data->docno;
        $total = 0;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Department: ' . $data->deptname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $str .= $this->reporter->col('Quantity', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('UOM', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Barcode', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Model', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Brand', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Location', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Expiry', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Reference', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Notes', '83', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            break;
          default:
            if ($companyid == 16) { //ati
              $str .= $this->reporter->col('PR Docno', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            } else {
              $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            }
            $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Item Description', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Expiry', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            if ($companyid == 16) { //ati
              $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col('Ctrl No', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            } else {
              $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            }
            break;
        }


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->col(number_format($data->qty, 2), '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->barcode, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->model, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->brand, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->loc, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->expiry, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->ref, '83', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->rem, '83', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          break;
        default:
          if ($companyid == 16) { //ati
            $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->qty, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          } else {
            $str .= $this->reporter->col(number_format($data->qty, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          }
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->expiry, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          if ($companyid == 16) { //ati
            $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          } else {
            $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          }
          break;
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();
      if ($docno == $data->docno) {
        $total += $data->qty;
      }
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $count = 38;
    $page = 40;

    $str = '';
    switch ($companyid) {
      case 16: //ati
        $layoutsize = '1100';
        break;
      default:
        $layoutsize = '800';
        break;
    }
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);

    $docno = "";
    $total = 0;
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->prdocno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        }
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col($data->deptname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->deptname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->col($data->whname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col($data->hrem, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->hrem, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $total = $total + $data->qty;
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
          if ($companyid == 16) { //ati
          } else {
            $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Grand Total', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          }
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
    $companyid = $config['params']['companyid'];
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 16) { //ati
      $str .= $this->reporter->col('Document No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('PR Docno.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('Document No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    if ($companyid == 16) { //ati
      $str .= $this->reporter->col('Department', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('Department', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }

    $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    if ($companyid == 16) { //ati
      $str .= $this->reporter->col('Remarks', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('Ctrl No', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('Remarks', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class