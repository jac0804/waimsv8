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

class purchase_requisition_report
{
  public $modulename = 'Purchase Requisition Report';
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
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'approved', 'ddeptname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'dcentername.required', true);
        break;
      default:
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dcentername.required', true);
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    switch ($companyid) {
      case 16: //ati
        $fields = ['radioposttype', 'radiodatetype', 'radioreporttype', 'radiosorting', 'expediteradio'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'radiodatetype.label', 'Date Range');
        data_set(
          $col2,
          'radiodatetype.options',
          [
            ['label' => 'Transaction Date', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Post Date', 'value' => '1', 'color' => 'teal']
          ]
        );
        break;
      default:
        $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
        $col2 = $this->fieldClass->create($fields);
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
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $companyid = $config['params']['companyid'];

    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr =  "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as `end`,'' as client,'' as clientname,'' as userid,'' as username,
                        '' as approved,'2' as posttype,'0' as reporttype,'0' as transdate,'ASC' as sorting,'' as dclientname,'' as reportusers,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as ddeptname, '' as dept, '' as deptname,
                        '0' as expediteradio, '0' as clientid, '0' as deptid";

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
    $companyid = $config['params']['companyid'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $reportdate = $config['params']['dataparams']['transdate'];

    if ($companyid == 16) { //ati
      switch ($reporttype) {
        case 0: // summarized
          $result = $this->reportDefaultLayout_ATI_SUMMARIZED($config);
          break;
        case 1: // detailed
          $result = $this->report_Layout_DETAILED_ati($config);
          break;
      }
    } else {
      switch ($reporttype) {
        case 0: // summarized
          $result = $this->reportDefaultLayout_SUMMARIZED($config);
          break;
        case 1: // detailed
          $result = $this->reportDefaultLayout_DETAILED($config);
          break;
      }
    }
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype   = $config['params']['dataparams']['reporttype'];

    if ($config['params']['companyid'] == 16) { //ati
      switch ($reporttype) {
        case 0:
          $query = $this->default_QUERY_SUMMARIZED_ATI($config);
          break;
        case 1:
          $query = $this->default_QUERY_DETAILED_ATI($config);
          break;
      }
    } else {
      switch ($reporttype) {
        case 0:
          $query = $this->default_QUERY_SUMMARIZED($config);
          break;
        case 1:
          $query = $this->default_QUERY_DETAILED($config);
          break;
      }
    }


    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $transdate   = $config['params']['dataparams']['transdate'];

    $filter = "";
    $filter1 = "";
    $leftjoin = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoin .= " left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptcode = $config['params']['dataparams']['dept'];
      $deptid = $config['params']['dataparams']['deptid'];
      // if ($deptid == "") {
      //   $dept = "";
      // } else {
      //   $dept = $config['params']['dataparams']['dept'];
      // }
      if ($deptcode != "") {
        $filter1 .= " and head.deptid = '$deptid'";
      }
      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";
      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    switch ($companyid) {
      case 8: //maxipro
        $doc = 'RQ';
        break;

      default:
        $doc = 'PR';
        break;
    }
    $addfield = "";
    if ($companyid == 16) { //ati
      $addfield = ",ifnull(dept.clientname,'') as dept,ifnull(d.duration,'') as duration,left(transnum.postdate,10) as postdate,ifnull(left(info.deadline,10),'') as deadline,info.ctrlno";
    } else {
      $addfield = "";
    }
    $filterdate = "date(head.dateid)";
    if ($companyid == 16) { //ati
      if ($transdate == 1) {
        $filterdate = "date(transnum.postdate)";
      }
    }

    switch ($posttype) {
      case 1: // unposted
        $query =
          "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext" . $addfield . ",
      client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem,dept.clientname as deptname
      from prstock as stock
      left join prhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join transnum on transnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as dept on dept.clientid = head.deptid
      left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
      left join duration as d on d.line=info.durationid
      " . $addjoin . "
      where head.doc = '$doc'  and $filterdate between '$start' and '$end' $filter $filter1  
      order by head.docno $sorting";


        break;

      case 0: // posted

        $query =
          "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext " . $addfield . ",
      client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem,dept.clientname as deptname
      from hprstock as stock
      left join hprhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join transnum on transnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as dept on dept.clientid = head.deptid
      left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
      left join duration as d on d.line=info.durationid
      " . $addjoin . "
      where head.doc = '$doc' and $filterdate between '$start' and '$end' $filter $filter1   
      order by head.docno $sorting";

        break;
      default: // all
        switch ($companyid) {
          case 16: //ati
            $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext" . $addfield . ",
      client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem,'UNPOSTED' as status
      from prstock as stock
      left join prhead as head on head.trno=stock.trno
      left join item on item.itemid=stock.itemid
      left join transnum on transnum.trno=head.trno
      left join client on client.clientid=stock.whid
      $leftjoin
      left join client as dept on dept.clientid = head.deptid
      left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
      left join duration as d on d.line=info.durationid
            " . $addjoin . "
            where head.doc = '$doc' and $filterdate between '$start' and '$end' $filter $filter1  
            union all
          select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext " . $addfield . ",
            client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem,'POSTED' as status
            from hprstock as stock
            left join hprhead as head on head.trno=stock.trno
            left join item on item.itemid=stock.itemid
            left join transnum on transnum.trno=head.trno
            left join client on client.clientid=stock.whid
            $leftjoin
            left join client as dept on dept.clientid = head.deptid
      left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
      left join duration as d on d.line=info.durationid
            " . $addjoin . "
            where head.doc = '$doc' and $filterdate between '$start' and '$end'  $filter $filter1  
            order by docno $sorting";
            break;

          default:
            $query = "select docno,supplier,barcode,itemname,uom,rrqty,rrcost,disc,ext,clientname,createby,loc,rem,dateid,ref,hrem,status,deptname
            from(select head.docno,head.clientname as supplier" . $barcodeitemnamefield . "
            ,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
            client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem, 'UNPOSTED' as status,dept.clientname as deptname
            from prstock as stock
            left join prhead as head on head.trno=stock.trno
            left join item on item.itemid=stock.itemid
            left join transnum on transnum.trno=head.trno
            left join client on client.clientid=stock.whid
            $leftjoin
            left join client as dept on dept.clientid = head.deptid
            " . $addjoin . "
            where head.doc='$doc' and $filterdate between '$start' and '$end' $filter $filter1   
            union all
            select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
            client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem, 'POSTED' as status,dept.clientname as deptname
            from hprstock as stock
            left join hprhead as head on head.trno=stock.trno
            left join item on item.itemid=stock.itemid
            left join transnum on transnum.trno=head.trno
            left join client on client.clientid=stock.whid
            $leftjoin
            left join client as dept on dept.clientid = head.deptid
            " . $addjoin . "
            where head.doc='$doc' and $filterdate between '$start' and '$end' $filter $filter1  ) as tb
            order by docno $sorting";
            break;
        }
    }


    return $query;
  }


  public function default_QUERY_DETAILED_ATI($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $transdate   = $config['params']['dataparams']['transdate'];
    $isexpedite   = $config['params']['dataparams']['expediteradio'];
    $adminid = $config['params']['adminid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $leftjoin = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoin .= " left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $filter1 .= "";
    $barcodeitemnamefield = ",item.barcode,item.itemname";
    $addjoin = "";

    $doc = 'PR';
    $addfield = "";

    if ($companyid == 16) { //ati
      $addfield = ",ifnull(dept.clientname,'') as dept,ifnull(d.duration,'') as duration,left(transnum.postdate,10) as postdate,ifnull(left(info.deadline,10),'') as deadline,info.ctrlno";
    } else {
      $addfield = "";
    }
    $filterdate = "date(head.dateid)";
    if ($transdate == 1) {
      $filterdate = "date(transnum.postdate)";
    }

    $expeditefilter = "";
    if ($companyid == 16) { //ati
      if ($isexpedite == 1) {
        $expeditefilter = " and head.isexpedite=1";
      } else {
        $expeditefilter = "";
      }
    }


    $trnxx = '';
    $leftjoin1 = '';
    $leftjoin = '';

    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      if ($trnx != "") {
        $trnxx .= " and info1.trnxtype='" . $trnx . "' ";
        $leftjoin = "left join headinfotrans as info1 on info1.trno=head.trno";
        $leftjoin1 = "left join hheadinfotrans as info1 on info1.trno=head.trno";
      }
    }


    switch ($posttype) {
      case 1: // unposted
        $query =
          "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,
                  stock.rrcost,stock.disc,stock.ext ,ifnull(dept.clientname,'') as dept,
                  ifnull(d.duration,'') as duration,left(transnum.postdate,10) as postdate,
                  ifnull(left(info.deadline,10),'') as deadline,info.ctrlno,
              client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem
              from prstock as stock
              left join prhead as head on head.trno=stock.trno
              left join item on item.itemid=stock.itemid
              left join transnum on transnum.trno=head.trno
              left join client on client.clientid=stock.whid
              left join client as supp on supp.client = head.client
              left join client as dept on dept.clientid = head.deptid
              left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
              left join duration as d on d.line=info.durationid    $leftjoin
              " . $addjoin . "
              where head.doc = '$doc'  and $filterdate between '$start' and '$end' $filter $filter1  $filter2  $expeditefilter  $trnxx
              order by head.dateid $sorting, head.docno";


        break;
      case 0: // posted
        $query =
          "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,
                    stock.rrcost,stock.disc,stock.ext,ifnull(dept.clientname,'') as dept,
                    ifnull(d.duration,'') as duration,left(transnum.postdate,10) as postdate,
                    ifnull(left(info.deadline,10),'') as deadline,info.ctrlno,
                client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem
                from hprstock as stock
                left join hprhead as head on head.trno=stock.trno
                left join item on item.itemid=stock.itemid
                left join transnum on transnum.trno=head.trno
                left join client on client.clientid=stock.whid
                left join client as supp on supp.client = head.client
                left join client as dept on dept.clientid = head.deptid
                left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                left join duration as d on d.line=info.durationid    $leftjoin1
                " . $addjoin . "
                where head.doc = '$doc' and $filterdate between '$start' and '$end' $filter $filter1  $filter2  $expeditefilter  $trnxx
                order by head.dateid $sorting,head.docno";

        break;

      default: // all
        $query = "select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext" . $addfield . ",
            client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem,'UNPOSTED' as status
            from prstock as stock
            left join prhead as head on head.trno=stock.trno
            left join item on item.itemid=stock.itemid
            left join transnum on transnum.trno=head.trno
            left join client on client.clientid=stock.whid
            left join client as supp on supp.client = head.client
            left join client as dept on dept.clientid = head.deptid
            left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
            left join duration as d on d.line=info.durationid    $leftjoin
                  " . $addjoin . "
                  where head.doc = '$doc' and $filterdate between '$start' and '$end' $filter $filter1  $filter2  $expeditefilter  $trnxx
                  union all
                select head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext " . $addfield . ",
                  client.clientname,head.createby,stock.loc,stock.rem,head.dateid,stock.ref,head.rem as hrem,'POSTED' as status
                  from hprstock as stock
                  left join hprhead as head on head.trno=stock.trno
                  left join item on item.itemid=stock.itemid
                  left join transnum on transnum.trno=head.trno
                  left join client on client.clientid=stock.whid
                  left join client as supp on supp.client = head.client
                  left join client as dept on dept.clientid = head.deptid
            left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
            left join duration as d on d.line=info.durationid    $leftjoin1
            " . $addjoin . "
            where head.doc = '$doc' and $filterdate between '$start' and '$end'  $filter $filter1  $filter2  $expeditefilter  $trnxx
            order by docno,dateid $sorting,docno";

        break;
    }

    return $query;
  }


  public function default_QUERY_SUMMARIZED_ATI($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
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
    $transdate   = isset($config['params']['dataparams']['transdate']) ? $config['params']['dataparams']['transdate'] : '';
    $isexpedite   = $config['params']['dataparams']['expediteradio'];
    $adminid = $config['params']['adminid'];
    $filter = "";
    $filter1 = "";

    $filter2 = "";
    $leftjoin = "";

    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $leftjoin .= " left join client as supp on supp.client = head.client ";
      $filter .= " and supp.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }


    $filter1 .= "";


    $filterdate = " date(head.dateid)";
    if ($transdate == 1) {
      $filterdate = " date(transnum.postdate)";
    }

    $expeditefilter = "";
    if ($companyid == 16) { //ati
      if ($isexpedite == 1) {
        $expeditefilter = " and head.isexpedite=1";
      } else {
        $expeditefilter = "";
      }
    }

    $trnxx = '';


    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      if ($trnx != '') {
        $trnxx .= " and info1.trnxtype='" . $trnx . "' ";
        $leftjoin .= "left join headinfotrans as info1 on info1.trno=head.trno";
      }
    }



    $query = '';
    switch ($posttype) {
      case 0: // posted
        $query = "select docno,supplier,clientname,createby,dateid,hrem,status,deptname,postdate,duration,
                        group_concat(distinct date(dateneeded)) as dateneeded,
                        group_concat(distinct ctrlno separator ', ') as ctrlno,
                        group_concat(distinct podocno separator ', ') as podocno 
                  from (select head.docno,head.clientname as supplier,
                              client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 
                              'POSTED' as status, dept.clientname as deptname, date(transnum.postdate) as postdate,
                              (group_concat(distinct concat(d.duration,' (',DATE_ADD(date(ifnull(transnum.postdate,head.dateid)), 
                              INTERVAL IFNULL(d.days,0) DAY),')'))) as duration, group_concat(distinct date(info.dateneeded)) as dateneeded,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              (select group_concat(distinct docno separator ', ')
                              from (select docno,reqtrno,reqline
                                    from pohead as h
                                    left join postock as s on s.trno=h.trno
                                    union all
                                    select docno,reqtrno,reqline
                                    from hpohead as h
                                    left join hpostock as s on s.trno=h.trno) as k
                              where k.reqtrno=stock.trno and k.reqline=stock.line) as podocno
                        from hprstock as stock
                        left join hprhead as head on head.trno=stock.trno
                        left join item on item.itemid=stock.itemid
                        left join transnum on transnum.trno=head.trno
                        left join client on client.clientid=stock.whid    
                        left join client as dept on dept.clientid = head.deptid  
                        left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                        left join duration as d on d.line=info.durationid  $leftjoin    
                        where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1 $filter2  $expeditefilter $trnxx
                        group by head.docno, head.clientname,client.clientname,head.createby,
                                head.dateid,head.rem,client.clientname,dept.clientname,info.ctrlno,
                                transnum.postdate,stock.trno,stock.line) as k
                  group by docno,supplier,clientname,createby,dateid,hrem,status,deptname,postdate,duration
                  order by dateid $sorting";

        break;
      case 1: // unposted
        $query = "select docno,supplier,clientname,createby,dateid,hrem,status,deptname,postdate,duration,
                         group_concat(distinct date(dateneeded)) as dateneeded,
                         group_concat(distinct ctrlno separator ', ') as ctrlno,
                         group_concat(distinct podocno separator ', ') as podocno 
                 from (select head.docno,head.clientname as supplier,
                              client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 'UNPOSTED' as status, 
                              dept.clientname as deptname, date(transnum.postdate) as postdate,
                              (group_concat(distinct concat(d.duration,' (',DATE_ADD(date(ifnull(transnum.postdate,head.dateid)), 
                              INTERVAL IFNULL(d.days,0) DAY),')'))) as duration, 
                              group_concat(distinct date(info.dateneeded)) as dateneeded,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              (select group_concat(distinct docno separator ', ')
                              from (select docno,reqtrno,reqline
                                    from pohead as h
                                    left join postock as s on s.trno=h.trno
                                    union all
                                    select docno,reqtrno,reqline
                                    from hpohead as h
                                    left join hpostock as s on s.trno=h.trno) as k
                              where k.reqtrno=stock.trno and k.reqline=stock.line) as podocno
                        from prstock as stock
                        left join prhead as head on head.trno=stock.trno
                        left join item on item.itemid=stock.itemid
                        left join transnum on transnum.trno=head.trno
                        left join client on client.clientid=stock.whid
                        left join client as dept on dept.clientid = head.deptid
                        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                        left join duration as d on d.line=info.durationid   $leftjoin      
                        where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1 $filter2  $expeditefilter $trnxx
                        group by head.docno, head.clientname,client.clientname,head.createby,
                                 head.dateid,head.rem,client.clientname,dept.clientname,info.ctrlno,transnum.postdate,stock.trno,stock.line) as k
                  group by docno,supplier,clientname,createby,dateid,hrem,status,deptname,postdate,duration
                                 order by dateid $sorting";


        break;
      default:

        $query = "select docno,supplier,clientname,createby,dateid,hrem,status,deptname,postdate,duration,
                         group_concat(distinct date(dateneeded)) as dateneeded,
                         group_concat(distinct ctrlno separator ', ') as ctrlno,
                         group_concat(distinct podocno separator ', ') as podocno 
                 from (select head.docno,head.clientname as supplier,
                              client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 
                              'POSTED' as status, dept.clientname as deptname, date(transnum.postdate) as postdate,
                              (group_concat(distinct concat(d.duration,' (',DATE_ADD(date(ifnull(transnum.postdate,head.dateid)), 
                              INTERVAL IFNULL(d.days,0) DAY),')'))) as duration, group_concat(distinct date(info.dateneeded)) as dateneeded,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              (select group_concat(distinct docno separator ', ')
                              from (select docno,reqtrno,reqline
                                    from pohead as h
                                    left join postock as s on s.trno=h.trno
                                    union all
                                    select docno,reqtrno,reqline
                                    from hpohead as h
                                    left join hpostock as s on s.trno=h.trno) as k
                              where k.reqtrno=stock.trno and k.reqline=stock.line) as podocno
                        from hprstock as stock
                        left join hprhead as head on head.trno=stock.trno
                        left join item on item.itemid=stock.itemid
                        left join transnum on transnum.trno=head.trno
                        left join client on client.clientid=stock.whid    
                        left join client as dept on dept.clientid = head.deptid  
                        left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                        left join duration as d on d.line=info.durationid    $leftjoin  
                        where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1 $filter2  $expeditefilter $trnxx
                        group by head.docno, head.clientname,client.clientname,head.createby,
                                head.dateid,head.rem,client.clientname,dept.clientname,info.ctrlno,
                                transnum.postdate,stock.trno,stock.line
                                
                  union all
                  
                  select head.docno,head.clientname as supplier,
                              client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 'UNPOSTED' as status, 
                              dept.clientname as deptname, date(transnum.postdate) as postdate,
                              (group_concat(distinct concat(d.duration,' (',DATE_ADD(date(ifnull(transnum.postdate,head.dateid)), 
                              INTERVAL IFNULL(d.days,0) DAY),')'))) as duration, 
                              group_concat(distinct date(info.dateneeded)) as dateneeded,
                              group_concat(distinct info.ctrlno separator ', ') as ctrlno,
                              (select group_concat(distinct docno separator ', ')
                              from (select docno,reqtrno,reqline
                                    from pohead as h
                                    left join postock as s on s.trno=h.trno
                                    union all
                                    select docno,reqtrno,reqline
                                    from hpohead as h
                                    left join hpostock as s on s.trno=h.trno) as k
                              where k.reqtrno=stock.trno and k.reqline=stock.line) as podocno
                        from prstock as stock
                        left join prhead as head on head.trno=stock.trno
                        left join item on item.itemid=stock.itemid
                        left join transnum on transnum.trno=head.trno
                        left join client on client.clientid=stock.whid
                        left join client as dept on dept.clientid = head.deptid
                        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                        left join duration as d on d.line=info.durationid  $leftjoin       
                        where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1 $filter2  $expeditefilter $trnxx
                        group by head.docno, head.clientname,client.clientname,head.createby,
                                 head.dateid,head.rem,client.clientname,dept.clientname,info.ctrlno,transnum.postdate,stock.trno,stock.line) as k
                  group by docno,supplier,clientname,createby,dateid,hrem,status,deptname,postdate,duration
                                 order by dateid $sorting
                  ";

        break;
    }
    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $transdate   = isset($config['params']['dataparams']['transdate']) ? $config['params']['dataparams']['transdate'] : '';

    $addfield = '';
    $addfieldmain = '';
    $addgrp = '';
    $addgrpmain = '';

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


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptcode = $config['params']['dataparams']['dept'];
      $deptid = $config['params']['dataparams']['deptid'];
      // if ($deptname == "") {
      //   $dept = "";
      // } else {
      //   $dept = $config['params']['dataparams']['dept'];
      // }
      if ($deptcode != "") {
        $filter1 .= " and head.deptid = '$deptid'";
      }
    } else {
      $filter1 .= "";
    }

    $filterdate = " date(head.dateid)";
    if ($companyid == 16) { //ati
      $addfieldmain .= ', duration, dateneeded, postdate,ctrlno';
      $addfield .= ", (group_concat(distinct concat(d.duration,' (',DATE_ADD(date(ifnull(transnum.postdate,head.dateid)), INTERVAL IFNULL(d.days,0) DAY),')'))) as duration, group_concat(distinct date(info.dateneeded)) as dateneeded,group_concat(distinct info.ctrlno separator ', ') as ctrlno";
      $addgrp .= ',info.ctrlno,transnum.postdate';
      $addgrpmain .= ',ctrlno';

      if ($transdate == 1) {
        $filterdate = " date(transnum.postdate)";
      }
    }

    $query = '';
    switch ($posttype) {
      case 0: // posted
        $query = "select head.docno,head.clientname as supplier,
        client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 'POSTED' as status, dept.clientname as deptname, date(transnum.postdate) as postdate " . $addfield . "
        from hprstock as stock
        left join hprhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join transnum on transnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as supp on supp.client = head.client    
        left join client as dept on dept.clientid = head.deptid  
        left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
        left join duration as d on d.line=info.durationid     
        where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1
        group by 
        head.docno, head.clientname, transnum.postdate,
        client.clientname,head.createby,
        head.dateid,head.rem,client.clientname,dept.clientname " . $addgrp . "
        order by head.docno $sorting";
        $this->othersClass->logConsole($query);
        break;
      case 1: // unposted
        $query = "select head.docno,head.clientname as supplier,
        client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 'UNPOSTED' as status, dept.clientname as deptname, date(transnum.postdate) as postdate " . $addfield . "
        from prstock as stock
        left join prhead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join transnum on transnum.trno=head.trno
        left join client on client.clientid=stock.whid
        left join client as supp on supp.client = head.client
        left join client as dept on dept.clientid = head.deptid
        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
        left join duration as d on d.line=info.durationid        
        where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1
        group by 
        head.docno, head.clientname, transnum.postdate,
        client.clientname,head.createby,
        head.dateid,head.rem,client.clientname,dept.clientname " . $addgrp . "
        order by head.docno $sorting";
        $this->othersClass->logConsole($query);
        break;
      default:
        $query = "select docno,supplier,clientname,createby,hrem,dateid,status,deptname " . $addfieldmain . "
                from(select head.docno,head.clientname as supplier,
                      client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 'UNPOSTED' as status, dept.clientname as deptname, date(transnum.postdate) as postdate " . $addfield . "
                      from prstock as stock
                      left join prhead as head on head.trno=stock.trno
                      left join item on item.itemid=stock.itemid
                      left join transnum on transnum.trno=head.trno
                      left join client on client.clientid=stock.whid
                      left join client as supp on supp.client = head.client
                      left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                      left join duration as d on d.line=info.durationid
                      left join client as dept on dept.clientid = head.deptid
                      where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1
                      group by head.docno,head.clientname,client.clientname,head.createby,head.dateid,head.rem,dept.clientname,transnum.postdate " . $addgrp . "
                    union all
                    select head.docno,head.clientname as supplier,
                      client.clientname,head.createby,date(head.dateid) as dateid,head.rem as hrem, 'POSTED' as status, dept.clientname as deptname, date(transnum.postdate) as postdate " . $addfield . "
                    from hprstock as stock
                    left join hprhead as head on head.trno=stock.trno
                    left join item on item.itemid=stock.itemid
                    left join transnum on transnum.trno=head.trno
                    left join client on client.clientid=stock.whid
                    left join client as supp on supp.client = head.client
                    left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                    left join duration as d on d.line=info.durationid
                    left join client as dept on dept.clientid = head.deptid
                    where head.doc='PR' and " . $filterdate . " between '$start' and '$end' $filter $filter1
                    group by head.docno,head.clientname,
                    client.clientname,head.createby,head.dateid,head.rem,dept.clientname,transnum.postdate " . $addgrp . ") as tb
                order by docno $sorting";
        break;
    }
    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname     = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and supp.client = '$client' ";
    }


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 2: // all
            $query = "select head.docno,head.clientname as supplier,item.barcode,
          item.itemname,stock.uom,sum(stock.rrqty) as rrqty,sum(stock.rrcost) as rrcost,stock.disc,sum(stock.ext) as ext,
          client.clientname,head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,stock.ref, 'UNPOSTED' as status
          from prstock as stock
          left join prhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          where head.doc='PR' and date(head.dateid) between '$start' and '$end' $filter 
          and transnum.center = '$center'
          group by head.docno,
          head.clientname,item.barcode,
          item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,
          stock.ext,client.clientname,head.createby,stock.loc,
          stock.rem,head.dateid,stock.ref
          union all
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,sum(stock.rrqty) as rrqty,sum(stock.rrcost) as rrcost,stock.disc,sum(stock.ext) as ext,
          client.clientname,head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,stock.ref, 'POSTED' as status
          from hprstock as stock
          left join hprhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          where head.doc='PR' and date(head.dateid) between '$start' and '$end' $filter 
          and transnum.center = '$center'
          group by head.docno,
          head.clientname,item.barcode,
          item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,
          stock.ext,client.clientname,head.createby,stock.loc,
          stock.rem,head.dateid,stock.ref
          order by docno $sorting";
            break;
        }
        break;
      case 1: // detailed
        switch ($posttype) {
          case 2:
            $query = "select head.docno,head.clientname as supplier,item.barcode,
          item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,stock.ref,head.rem as hrem
          from prstock as stock
          left join prhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          where head.doc='PR' and date(head.dateid) between '$start' and '$end' $filter 
          and transnum.center = '$center'
          union all
          select head.docno,head.clientname as supplier,item.barcode,item.itemname,stock.uom,stock.rrqty,stock.rrcost,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,date(head.dateid) as dateid,stock.ref,head.rem as hrem
          from hprstock as stock
          left join hprhead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join client as supp on supp.client = head.client
          where head.doc='PR' and date(head.dateid) between '$start' and '$end' $filter 
          and transnum.center = '$center' 
          order by docno $sorting";
            break;
        }
        break;
    }

    return $query;
  }

  public function default_header_detailed($config)
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
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
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
    $str .= $this->reporter->col('Purchase Requisition Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '700', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '140', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, '700', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '140', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
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
    $companyid   = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

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

    $total = 0;

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header_detailed($config);

    $str .= $this->reporter->printline();
    $docno = "";
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, '14', 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->startrow();

          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $str .= $this->reporter->col('Department: ' . $data->deptname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          }

          $str .= $this->reporter->col('Notes: ' . $data->hrem, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
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
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_header_detailed($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
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
    $company   = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

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

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        if ($company == 3) { //conti
          $str .= $this->reporter->col($data->hrem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format((isset($data->rrqty) ? $data->rrqty : 0), 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_ATI_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $company   = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;
    $str = '';
    $layoutsize = '1400';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader_ati($layoutsize, $config);

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->deptname, '260', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '130', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '230', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->duration, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateneeded, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->postdate, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ctrlno, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->podocno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
    }

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
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str = '';
    if ($companyid == 16) { //ati
      $layoutsize = '1400';
    } else {
      $layoutsize = '1000';
    }
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
    $str .= $this->reporter->col('Purchase Requisition Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    switch ($companyid) {
      case 16: //ati
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix : ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by : ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;

      default:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User : ' . $user, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
          $str .= $this->reporter->col('Department : ' . $deptname, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by : ' . $sorting, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
    }
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
    $company   = $config['params']['companyid'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEPARTMENT', '260', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    if ($company == 3) { //conti
      $str .= $this->reporter->col('NOTES', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('QUANTITY', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      if ($company == 16) { //ati
        $str .= $this->reporter->col('Post Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Duration', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Deadline', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      }
    }

    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function tableheader_ati($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $company   = $config['params']['companyid'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEPARTMENT', '260', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '230', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEADLINE', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE NEEDED', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('POST DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CTRL NO.', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO DOCNO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  //report detailed 
  public function report_Layout_DETAILED_ati($config)
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

    $total = 0;

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header_detailed($config);

    $str .= $this->reporter->printline();
    $docno = "";
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if (
          $docno != "" && $docno != $data->docno
        ) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total Quantity: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, '14', 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        } //end if

        if (
          $docno == "" || $docno != $data->docno
        ) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . date("Y-m-d", strtotime($data->dateid)), '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Department: ' . $data->dept, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes: ' . $data->hrem, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Clientname: ' . $data->supplier, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Ctrl No.', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Post Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Duration', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Deadline', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->ctrlno, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->postdate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->duration, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->deadline, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if (
          $docno == $data->docno
        ) {
          $total += $data->rrqty;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_header_detailed($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class