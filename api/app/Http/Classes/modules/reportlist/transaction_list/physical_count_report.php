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

class physical_count_report
{
  public $modulename = 'Physical Count Report';
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
    $companyid = $config['params']['companyid'];
    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'FAMS':
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['dwhname', 'radioposttype'];
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
        break;
      default:
        if ($companyid == 21) { //kinggeorge
          $fields = ['radioprint', 'start', 'end', 'dwhname', 'reportusers', 'approved'];
        } else {
          $fields = ['radioprint', 'start', 'end', 'dwhname', 'dcentername', 'reportusers', 'approved'];
        }
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            array_push($fields, 'project');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'project.required', false);
            data_set($col1, 'project.label', 'Item Group');
            break;
          default:
            $col1 = $this->fieldClass->create($fields);
            break;
        }

        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'dcentername.required', true);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
        $col2 = $this->fieldClass->create($fields);
        switch ($companyid) {
          case 11: //summit
            data_set(
              $col2,
              'radioreporttype.options',
              [
                ['label' => 'Summarized Per Item', 'value' => '2', 'color' => 'orange'],
                ['label' => 'Summarized Per Document', 'value' => '0', 'color' => 'orange'],
                ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],

              ]
            );
            break;
        }

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
        break;
    }
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end, '0' as whid,'' as wh,'' as whname,'' as userid,
                         '' as username,'' as approved,'0' as posttype,'0' as reporttype, 'ASC' as sorting,'' as reportusers,'' as dwhname,
                         '" . $defaultcenter[0]['center'] . "' as center,
                         '" . $defaultcenter[0]['centername'] . "' as centername,
                         '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                         '' as project, '' as projectid, '' as projectname";

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
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($systemtype) {
      case 'FAMS':
        $result = $this->FAMS_INVENTORY_COUNT($config);
        break;
      default:
        switch ($companyid) {
          case 11: // summit
            switch ($reporttype) {
              case '0': // SUMMARIZED
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
              case '1': // DETAILED
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
              case '2':
                $result = $this->SUMMIT_summarized_per_item($config);
                break;
            }
            break;
          default:
            switch ($reporttype) {
              case 0:
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
              case 1:
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
            }
            break;
        }
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    // QUERY

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'FAMS':
        $query = $this->fams_default_query($config);
        break;
      default:
        switch ($companyid) {
          case 11: //summit
            $query = $this->SUMMIT_QUERY($config);
            break;
          default:
            $query = $this->default_QUERY($config);
            break;
        }
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function fams_default_query($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];

    $filter = "";
    if ($wh != "") {
      $filter .= " and location.clientid = '$whid' ";
    }

    $query = "
    select date(head.dateid) as dateid, head.docno, item.barcode,
    item.itemname, stock.rem as srem, stock.consignee
    from pchead as head 
    left join pcstock as stock on stock.trno=head.trno 
    left join client on client.client=head.wh
    left join item on item.itemid = stock.itemid
    left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
    left join client as location on location.clientid = iteminfo.locid
    where head.doc='pc' and head.dateid between '$start' and '$end' $filter
    union all
    select date(head.dateid) as dateid, head.docno, item.barcode,
    item.itemname, stock.rem as srem, stock.consignee
    from hpchead as head 
    left join hpcstock as stock on stock.trno=head.trno 
    left join client on client.client=head.wh
    left join item on item.itemid = stock.itemid
    left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
    left join client as location on location.clientid = iteminfo.locid
    where head.doc='pc' and head.dateid between '$start' and '$end' $filter
    group by head.dateid, head.docno, item.barcode, item.itemname, stock.rem, stock.consignee";



    return $query;
  }

  public function default_QUERY($config)
  {
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];

    $filter = "";
    $filter1 = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($companyid != 21) { //not kinggeorge
      $fcenter    = $config['params']['dataparams']['center'];
      if ($fcenter != "") {
        $filter .= " and transnum.center = '$fcenter'";
      }
    }

    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid' ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $project = $config['params']['dataparams']['projectid'];
      if ($prjid != "") {
        $filter1 .= " and stock.projectid = $project";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC'  and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC'  and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC'  and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          union all
          select 'POSTED' as status, head.docno,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC' and head.dateid between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0:
            $query = "select head.docno,wh.clientname,left(head.dateid,10) as dateid,stock.rrqty,stock.uom" . $barcodeitemnamefield . ",stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          " . $addjoin . "
          where head.doc='PC' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;

          case 1:
            $query = "select head.docno,wh.clientname,left(head.dateid,10) as dateid,stock.rrqty,
          stock.uom" . $barcodeitemnamefield . ",stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          " . $addjoin . "
          where head.doc='PC' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;

          default:
            $query = "select head.docno,wh.clientname,left(head.dateid,10) as dateid,stock.rrqty,
          stock.uom" . $barcodeitemnamefield . ",stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          " . $addjoin . "
          where head.doc='PC' and date(head.dateid) between '$start' and '$end' $filter $filter1
          union all
          select head.docno,wh.clientname,left(head.dateid,10) as dateid,stock.rrqty,stock.uom" . $barcodeitemnamefield . ",stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          " . $addjoin . "
          where head.doc='PC' and date(head.dateid) between '$start' and '$end' $filter $filter1
          order by docno $sorting";
            break;
        }
        break;
    }
    return $query;
  }

  public function SUMMIT_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $wh         = $config['params']['dataparams']['wh'];
    $whid         = $config['params']['dataparams']['whid'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    if ($wh != "") {
      $filter .= " and wh.clientid = '$whid' ";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC'  and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC'  and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC'  and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          union all
          select 'POSTED' as status, head.docno,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client = head.wh
          where head.doc='PC' and head.dateid between '$start' and '$end' $filter
          group by head.docno, head.clientname, wh.clientname, head.createby, head.dateid
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0:
            $query = "select head.docno,wh.clientname,head.dateid,item.barcode,stock.rrqty,stock.uom,item.itemname,stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          where head.doc='PC' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          case 1:
            $query = "select head.docno,wh.clientname,head.dateid,item.barcode,stock.rrqty,
          stock.uom,item.itemname,stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          where head.doc='PC' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;

          default:
            $query = "select head.docno,wh.clientname,head.dateid,item.barcode,stock.rrqty,
          stock.uom,item.itemname,stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from pcstock as stock
          left join pchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          where head.doc='PC' and head.dateid between '$start' and '$end' $filter
          union all
          select head.docno,wh.clientname,head.dateid,item.barcode,stock.rrqty,stock.uom,item.itemname,stock.rrcost,
          stock.ext,stock.loc,stock.expiry,stock.rem
          from hpcstock as stock
          left join hpchead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as wh on wh.client=head.wh
          left join item on item.itemid=stock.itemid
          where head.doc='PC' and head.dateid between '$start' and '$end' $filter
          order by docno $sorting";
            break;
        }
        break;
      case 2:
      case 2: // summarize per item
        switch ($posttype) {
          case 0: // posted
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.qty as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from hpchead as head
              left join hpcstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join transnum on transnum.trno=head.trno
              where head.doc = 'PC' and head.dateid between '$start' and '$end' $filter) as a
              group by whname, itemname, uom
              order by whname " . $sorting . "";
            break;
          case 1: // unposted
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.qty as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from pchead as head
              left join pcstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join transnum on transnum.trno=head.trno
              where head.doc = 'PC' and head.dateid between '$start' and '$end' $filter) as a
              group by whname, itemname, uom
              order by whname " . $sorting . "";
            break;
          case 2: // all
            $query = "
              select whname, itemname, uom, sum(qty) as qty, sum(amount) as amount 
              from (
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.qty as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from pchead as head
              left join pcstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join transnum on transnum.trno=head.trno
              where head.doc = 'PC' and head.dateid between '$start' and '$end' $filter
              union all
              select head.trno, head.docno, stock.itemid, item.barcode, item.itemname, 
              item.uom, stock.qty as qty, 
              stock.ext as amount,
              wh.client as whcode, wh.clientname as whname
              from hpchead as head
              left join hpcstock as stock on stock.trno = head.trno
              left join item as item on item.itemid = stock.itemid
              left join client as wh on wh.client = head.wh
              left join transnum on transnum.trno=head.trno
              where head.doc = 'PC' and head.dateid between '$start' and '$end' $filter) as a
              group by whname, itemname, uom
              order by whname " . $sorting . "";
            break;
        }
        break;
        break;
    }

    return $query;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
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

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($config['params']['companyid']) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', $fontsize, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
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

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];

      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

    $str = '';
    $layoutsize = '1000';
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
    $str .= $this->reporter->col('Physical Count Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

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
    $totalext = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();

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

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('WAREHOUSE', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function header_summarized_per_item($config)
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
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

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

    $layoutsize = '1000';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Physical Count Summarized Per Item', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by: ' . $sorting, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function SUMMIT_summarized_per_item($config)
  {
    $result = $this->reportDefault($config);
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_summarized_per_item($config);
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $whname = "";
    $subtotal = 0;
    $grandtotal = 0;

    $counter = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        if ($whname != $data->whname) {
          if ($whname != "") {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col("SUBTOTAL: ", '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $subtotal = 0;
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("Warehouse: " . $data->whname, '100', null, false, $border, '', 'L', $font, '14', 'B', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ITEMNAME', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_summarized_per_item($config);
          $str .= $this->reporter->begintable($layoutsize);
          $page = $page + $count;
        } //end if

        $whname = $data->whname;
        $subtotal += $data->amount;
        $grandtotal += $data->amount;
        $counter++;

        if ($counter == count($result)) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("SUBTOTAL: ", '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }



  public function FAMS_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['wh'];
    $clientname = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($client == '') {
      $cl = 'ALL';
    } else {
      $cl = $client . ' - ' . $clientname;
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
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INVENTORY COUNT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Location: ' . $cl, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TAG CODE', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEMNAME', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CONSIGNEE', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    return $str;
  }

  public function FAMS_INVENTORY_COUNT($config)
  {
    $result = $this->reportDefault($config);

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->FAMS_header($config);
    if (!empty($result)) {

      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->consignee, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->srem, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->FAMS_header($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class