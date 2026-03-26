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

class purchase_return_report
{
  public $modulename = 'Purchase Return Report';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'reportusers', 'dcentername', 'approved'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      case 60: //Trans power
        array_push($fields, 'radiostatus');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radiostatus.label', 'Type of Status');
        data_set($col1, 'radiostatus.options',[
                  ['label' => 'Open', 'value' => '0', 'color' => 'orange'],
                  ['label' => 'Close', 'value' => '1', 'color' => 'orange'],
                  ['label' => 'Both', 'value' => '2', 'color' => 'orange']
                ]
                );
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'dclientname.lookupclass', 'wasupplier');

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
    switch ($companyid) {
      case 11: //SUMMIT
        data_set(
          $col2,
          'radioreporttype.options',
          [
            ['label' => 'Summary Per Document', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Detailed', 'value' => '1', 'color' => 'teal'],
            ['label' => 'Summarized Per Item', 'value' => '2', 'color' => 'teal']
          ]
        );
        break;
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $companyid = $config['params']['companyid'];
    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start, left(now(),10) as end, '' as client,'' as clientname, '' as userid,
                        '' as username,'' as approved,'0' as posttype,'0' as reporttype, 'ASC' as sorting, '' as dclientname,'' as reportusers,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,
                        '0' as clientid, '0' as deptid, '2' as status ";

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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case 0:
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;

      case 1:
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
      case 2: // SUMMARIZED PER ITEM
        $result = $this->reportDefaultLayout_SUMMARYPERITEM($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case 2:
        $query = $this->SUMMIT_QUERY($config);
        break;

      default:
        $query = $this->default_QUERY($config);
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {

    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $prjcode = $config['params']['dataparams']['project'];
    $deptcode = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];
    $fcenter    = $config['params']['dataparams']['center'];

    $status = $config['params']['dataparams']['status'];

    $statusFilter = "";
    $statusFilter1 = "";

      // if ($status == 0) {          // open
      //   $statusFilter = " AND bal > 0 ";
      // } elseif ($status == 1) {    // closed
      //   $statusFilter = " AND bal <= 0 ";
      // }

      if ($status == 0) {
        $statusFilter1 = "  x.status  = 'OPEN' ";
      } elseif ($status == 1) {
        $statusFilter1 = "  x.status  = 'CLOSED' ";
      } else { 
        $statusFilter1 = "  x.status  in ('CLOSED','OPEN') ";}
      




    $filter = "";
    $filter1 = "";
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

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($prjcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptcode != "") {
        $filter1 .= " and head.deptid = $deptid";
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
            $query = "select * from (select docno, dateid, supplier, wh, clientname as whname, ext as amount, hrem, deptcode, deptname, prdoc, ctrlno, ifnull(bal,0) as balance,
              (case when bal = 0 then 'CLOSED' else 'OPEN' end) as status
              from (select head.docno, head.clientname as supplier, sum(stock.ext) as ext, wh.clientname, left(head.dateid,10) as dateid,
              wh.client as wh, head.rem as hrem,dept.client as deptcode, dept.clientname as deptname,pr.docno as prdoc, prinfo.ctrlno, sum(stock.ext) as bal
              from glstock as stock left join glhead as head on head.trno=stock.trno left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno left join client as wh on wh.clientid=stock.whid
              left join client as supp on supp.clientid=head.clientid left join client as dept on dept.clientid = head.deptid
              left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline left join hprhead as pr on pr.trno=prinfo.trno
              where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
              group by head.docno, head.clientname, wh.clientname, head.dateid, wh.client, head.rem, dept.client, dept.clientname, pr.docno, prinfo.ctrlno
              ) as a 
              ) as x
              where $statusFilter1
              order by docno $sorting";
            break;

          case 1: // unposted
            $query = "select * from (select docno, dateid, supplier, wh, clientname as whname, ext as amount, hrem, deptcode, deptname, prdoc, ctrlno, ifnull(bal,0) as balance,
              (case when bal = 0 then 'CLOSED' else 'OPEN' end) as status
              from (select head.docno, head.clientname as supplier, sum(stock.ext) as ext, wh.clientname, left(head.dateid,10) as dateid, wh.client as wh, head.rem as hrem,
              dept.client as deptcode, dept.clientname as deptname, pr.docno as prdoc, prinfo.ctrlno, ap.bal as bal
              from lastock as stock left join lahead as head on head.trno=stock.trno left join cntnum on cntnum.trno=head.trno left join client as wh on wh.clientid=stock.whid
              left join client as supp on supp.client = head.client left join item on item.itemid=stock.itemid left join client as dept on dept.clientid = head.deptid
              left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline left join hprhead as pr on pr.trno=prinfo.trno
              left join apledger as ap on ap.trno = head.trno
              where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
              group by head.docno, head.clientname, wh.clientname, head.dateid, wh.client, head.rem, dept.client, dept.clientname, pr.docno, prinfo.ctrlno,bal
              ) as a 
              ) as x
              where $statusFilter1
              order by docno $sorting";
            break;

          default: // all
            $query = "select * from (select docno, dateid, supplier, wh, clientname as whname, ext as amount, hrem, deptcode, deptname, prdoc, ctrlno, ifnull(bal,0) as balance,
              (case when bal = 0 then 'CLOSED' else 'OPEN' end) as status
              from (select head.docno, head.clientname as supplier, sum(stock.ext) as ext, wh.clientname, left(head.dateid,10) as dateid,
              wh.client as wh, head.rem as hrem,dept.client as deptcode, dept.clientname as deptname,pr.docno as prdoc, prinfo.ctrlno, sum(stock.ext) as bal
              from glstock as stock left join glhead as head on head.trno=stock.trno left join item on item.itemid=stock.itemid
              left join cntnum on cntnum.trno=head.trno left join client as wh on wh.clientid=stock.whid
              left join client as supp on supp.clientid=head.clientid left join client as dept on dept.clientid = head.deptid
              left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline left join hprhead as pr on pr.trno=prinfo.trno
              where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
              group by head.docno, head.clientname, wh.clientname, head.dateid, wh.client, head.rem, dept.client, dept.clientname, pr.docno, prinfo.ctrlno
              union all
              select head.docno, head.clientname as supplier, sum(stock.ext) as ext, wh.clientname, left(head.dateid,10) as dateid, wh.client as wh, head.rem as hrem,
              dept.client as deptcode, dept.clientname as deptname, pr.docno as prdoc, prinfo.ctrlno, ap.bal as bal
              from lastock as stock left join lahead as head on head.trno=stock.trno left join cntnum on cntnum.trno=head.trno left join client as wh on wh.clientid=stock.whid
              left join client as supp on supp.client = head.client left join item on item.itemid=stock.itemid left join client as dept on dept.clientid = head.deptid
              left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline left join hprhead as pr on pr.trno=prinfo.trno
              left join apledger as ap on ap.trno = head.trno
              where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
              group by head.docno, head.clientname, wh.clientname, head.dateid, wh.client, head.rem, dept.client, dept.clientname, pr.docno, prinfo.ctrlno,bal
              ) as a
              ) as x
              where $statusFilter1
              order by docno $sorting";
              // var_dump($query);
            break;
        }
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select * from (select head.docno, head.clientname as supplier " . $barcodeitemnamefield . ", stock.uom, stock.rrqty, 
            stock.rrcost, stock.disc, stock.ext, wh.clientname, head.createby, stock.expiry, stock.loc,
            stock.rem, head.dateid, stock.ref, dept.client as deptcode, dept.clientname as deptname, stock.isqty, 
            stock.iss, stock.isamt, stock.amt, pr.docno as prdoc, prinfo.ctrlno, 'OPEN' as status
            from glstock as stock left join glhead as head on head.trno=stock.trno left join item on item.itemid=stock.itemid 
            left join cntnum on cntnum.trno=head.trno left join client as wh on wh.clientid=stock.whid 
            left join client as supp on supp.clientid=head.clientid left join client as dept on dept.clientid = head.deptid
            left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline 
            left join hprhead as pr on pr.trno=prinfo.trno " . $addjoin . "
            where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
            ) as x
            where $statusFilter1
            order by docno $sorting";
          // var_dump($query);
            break;

          case 1: // unposted
            $query = "select * from (select head.docno, head.clientname as supplier " . $barcodeitemnamefield . ", stock.uom, stock.rrqty, 
            stock.rrcost, stock.disc, stock.ext, wh.clientname, head.createby, stock.expiry, stock.loc,
            stock.rem, head.dateid, stock.ref, dept.client as deptcode, dept.clientname as deptname, stock.isqty, 
            stock.iss, stock.isamt, stock.amt, pr.docno as prdoc, prinfo.ctrlno,
            (case when ap.bal = 0 then 'CLOSED' else 'OPEN' end) as status
            from lastock as stock left join lahead as head on head.trno=stock.trno left join cntnum on cntnum.trno=head.trno 
            left join client as wh on wh.clientid=stock.whid left join item on item.itemid=stock.itemid 
            left join client as supp on supp.client = head.client left join client as dept on dept.clientid = head.deptid
            left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline 
            left join hprhead as pr on pr.trno=prinfo.trno
            left join apledger as ap on ap.trno = head.trno " . $addjoin . "
            where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
            ) as x
            where $statusFilter1
            order by docno $sorting";

            break;

          default: // all
            $query = "select * from (select head.docno, head.clientname as supplier " . $barcodeitemnamefield . ", stock.uom, stock.rrqty, 
            stock.rrcost, stock.disc, stock.ext, wh.clientname, head.createby, stock.expiry, stock.loc,
            stock.rem, head.dateid, stock.ref, dept.client as deptcode, dept.clientname as deptname, stock.isqty, 
            stock.iss, stock.isamt, stock.amt, pr.docno as prdoc, prinfo.ctrlno, 'OPEN' as status
            from glstock as stock left join glhead as head on head.trno=stock.trno left join item on item.itemid=stock.itemid 
            left join cntnum on cntnum.trno=head.trno left join client as wh on wh.clientid=stock.whid 
            left join client as supp on supp.clientid=head.clientid left join client as dept on dept.clientid = head.deptid
            left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline 
            left join hprhead as pr on pr.trno=prinfo.trno " . $addjoin . "
            where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
            union all
            select head.docno, head.clientname as supplier " . $barcodeitemnamefield . ", stock.uom, stock.rrqty, 
            stock.rrcost, stock.disc, stock.ext, wh.clientname, head.createby, stock.expiry, stock.loc,
            stock.rem, head.dateid, stock.ref, dept.client as deptcode, dept.clientname as deptname, stock.isqty, 
            stock.iss, stock.isamt, stock.amt, pr.docno as prdoc, prinfo.ctrlno,
            (case when ap.bal = 0 then 'CLOSED' else 'OPEN' end) as status
            from lastock as stock left join lahead as head on head.trno=stock.trno left join cntnum on cntnum.trno=head.trno 
            left join client as wh on wh.clientid=stock.whid left join item on item.itemid=stock.itemid 
            left join client as supp on supp.client = head.client left join client as dept on dept.clientid = head.deptid
            left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline 
            left join hprhead as pr on pr.trno=prinfo.trno
            left join apledger as ap on ap.trno = head.trno " . $addjoin . "
            where head.doc='DM' and head.dateid between '$start' and '$end' $filter $filter1 
            ) as x
            where $statusFilter1
            order by docno $sorting ";
            // var_dump($query);
            break;
        } // end switch

        break;
    } // end switch


    return $query;
  }


  public function SUMMIT_QUERY($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
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
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
          sum(stock.ext) as ext
          from glstock as stock
          left join glhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.clientid=head.clientid
          left join client as wh on wh.clientid = head.whid
          where head.doc='DM'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting
          ";
        break;

      case 1: // unposted
        $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
          sum(stock.ext) as ext
          from lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join cntnum on cntnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          where head.doc='DM'
          and date(head.dateid) between '$start' and '$end' $filter 
          group by wh.clientname, wh.client, item.itemname,item.uom
          order by clientname,itemname $sorting";
        break;

      default: // all
        $query = "select 'UNPOSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
                    sum(stock.ext) as ext
                    from lastock as stock
                    left join lahead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.client=head.client
                    left join client as wh on wh.client = head.wh
                    where head.doc='DM'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    UNION ALL
                    select 'POSTED' as status,wh.clientname,wh.client as wh,item.itemname,item.uom,sum(stock.qty) as qty,
                    sum(stock.ext) as ext
                    from glstock as stock
                    left join glhead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.clientid=head.clientid
                    left join client as wh on wh.clientid = head.whid
                    where head.doc='DM'
                    and date(head.dateid) between '$start' and '$end' $filter 
                    group by wh.clientname, wh.client, item.itemname,item.uom
                    order by clientname,itemname $sorting";
        break;
    } // end switch posttype



    return $query;
  }

  public function setreporttimestamp($config, $username, $headerdata)
  {
    $date = date("Y-m-d H:i:s");
    return "Printed by: " . $username . " | Date Printed: " . $date;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
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

    $layoutsize = '1000';
    $font = 'Tahoma';
    $fontsize = "11";
    $border = "1px solid ";

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $reporttimestamp = $this->setreporttimestamp($config, $username, $headerdata);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    $str .=  $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '9', '', '', '', 0, '', 0, 5);
    $str .=  $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';


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
    $str .= $this->reporter->col('Purchase Return Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
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
    }

    $str .= $this->reporter->endtable();

    return $str;
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

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';

    switch ($config['params']['companyid']) {
      case 16: //ati
        $layoutsize = '1200';
        break;
      case 60: //Trans power
        $layoutsize = '1200';
        break;
      default:
        $layoutsize = '1000';
        break;
    }
    $font = 'Tahoma';
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    $str .= $this->reporter->printline();
    $docno = "";
    $i = 0;
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
           $str .= $this->reporter->col('', '600', null, false, '1px dotted', 'B', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '400', null, false, '1px dotted', 'B', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '600', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);

          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Supplier: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Supplier: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          }
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            case 60: //Trans power
              $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          if ($companyid == 16) { //ati
            $str .= $this->reporter->col('PR Docno', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          } else {
            $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          }

          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          // $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          if ($companyid == 16) { //ati
            $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Ctrl No', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          } else {
            $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($companyid == 60) { //Trans power
          $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        }
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col($data->prdoc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->col(number_format($data->isqty, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        // $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if


        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '600', null, false, $border, 'B', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '400', null, false, '1px dotted', 'B', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '600', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
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
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $status = $config['params']['dataparams']['status'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';


    switch ($config['params']['companyid']) {
      case 16: //ati
        $layoutsize = '1200';
        break;
      default:
        $layoutsize = '1000';
        break;
    }
    $font = 'Tahoma';
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
          $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->prdoc, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        }
       
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->supplier, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', ''); //150
        $str .= $this->reporter->col($data->whname, '125', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '125', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col($data->hrem, '200', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->hrem, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', ''); //100
        }

        if ($companyid == 60) { //Trans power
           $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $total = $total + $data->amount;
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($companyid == 16) { //ati
            $str .= $this->reporter->col('', '800', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Grand Total', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          } else {
            $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Grand Total', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '310', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
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

  public function reportDefaultLayout_SUMMARYPERITEM($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_SUMMARYPERITEM($config);
    $client = "";
    $total = 0;
    $i = 0;
    $totalext = 0;
    $totalqty = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($client != "" && $client != $data->clientname) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($client == "" || $client != $data->clientname) {
          $client = $data->clientname;
          $totalqty = 0;
          $totalext = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize + 5, 'B', '', '', '8px');

          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('ITEM', '425', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('UOM', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('QUANTITY', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->itemname, '425', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->uom, '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->qty, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($client == $data->clientname) {
          $totalext += $data->ext;
          $totalqty += $data->qty;
        }
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_SUMMARYPERITEM($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('TOTAL :', '125', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalext, 2), '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_SUMMARYPERITEM($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
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
    $str .= $this->reporter->col('Purchase Return Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


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

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = 'Tahoma';
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 16) { //ati
      $str .= $this->reporter->col('Document No.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('PR Docno', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('Document No.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }

    

    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Name', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Warehouse', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Amount', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    if ($companyid == 16) { //ati
      $str .= $this->reporter->col('Remarks', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('Ctrl No', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('Remarks', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }

    if ($companyid == 60) { //Trans power
      $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class