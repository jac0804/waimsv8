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

class quotation_report
{
  public $modulename = 'Quotation Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:3000px;max-width:3000px;';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved'];
    switch ($companyid) {
      case 10: //afti
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
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
        ['label' => 'All', 'value' => '2', 'color' => 'teal'],
        ['label' => 'With PO', 'value' => '3', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '0' as clientid,
      '' as client,
      '' as clientname,
      '' as userid,
      '' as username,
      '' as approved,
      '0' as posttype,
      '0' as reporttype, 
      'ASC' as sorting,
      '' as center,
      '' as centername,'$center' as dcentername,
      '' as dclientname,'' as reportusers,
      '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'0' as deptid";
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

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        if ($companyid == 10 || $companyid == 12) { //afti
          $result = $this->reportDefaultLayout_SUMMARIZEDnew($config);
        } else {
          $result = $this->reportDefaultLayout_SUMMARIZED($config);
        }
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
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($posttype) {
      case '0': // POSTED
        $query = $this->default_QUERY_POSTED($config);
        break;
      case '1': // UNPOSTED
        $query = $this->default_QUERY_UNPOSTED($config);
        break;
      case '2': // ALL
        $query = $this->default_QUERY_ALL($config);
        break;
      case '3': // WITH PO
        $query = $this->default_QUERY_WITH_PO($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }


  public function default_QUERY_WITH_PO($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $fcenter    = $config['params']['dataparams']['center'];
    $projectcode = $config['params']['dataparams']['project'];
    $dept = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $so_join = "";

    if ($companyid == 10) { //afti
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $filter1 .= " and sonum.postdate is not null";

      $so_join = "left join transnum as sonum on sonum.trno=head.sotrno";

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "
      left join model_masterfile as model on model.model_id=item.model 
      left join frontend_ebrands as brand on brand.brandid = item.brand 
      left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";

      $barcodeitemnamefield = "item.barcode,item.itemname";
      $addjoin = "";
    }

    $isqty = 'stock.iss';

    switch ($reporttype) {
      case '1':
        $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname 
        from hqsstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        " . $so_join . "
        where head.doc='QS' and head.dateid between '$start' 
        and '$end' $filter $filter1
        order by docno $sorting";
        break;
      case '0': //summarized
        // $query = "select 
        //   status, docno, supplier, ext, clientname, dateid, wh, deptcode, deptname
        // from (
        // select 'POSTED' as status,head.docno,
        // head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
        // date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname 
        // from hqsstock as stock
        // left join hqshead as head on head.trno=stock.trno
        // left join item on item.itemid=stock.itemid
        // left join transnum on transnum.trno=head.trno
        // left join client on client.client=head.client
        // left join client as wh on wh.client = head.wh
        // left join client as dept on dept.clientid = head.deptid
        // " . $so_join . "
        // where head.doc='QS'
        // and date(head.dateid) between '$start' and '$end' $filter $filter1
        // group by head.docno, head.clientname,
        // wh.clientname, wh.client, head.dateid, dept.client, dept.clientname
        // ) as a
        // order by docno $sorting";
        if ($companyid == 10 || $companyid == 12) { //afti
          $query = "select dateid,customerid, customername,docno, ext,salesperson,status
        from (
        select date(head.dateid) as dateid,client.client as customerid,
        head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,'POSTED' as status
        from hqsstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
         " . $so_join . "
        where head.doc='QS'
        and date(head.dateid) between'$start' and '$end' $filter $filter1
        group by  head.docno, head.clientname,head.dateid,client.client,agent.clientname
        ) as a
        order by docno ASC";
        } else {
          $query = "select 
            status, docno, supplier, ext, clientname, dateid, wh, deptcode, deptname
          from (
          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
          date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname 
          from hqsstock as stock
          left join hqshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          " . $so_join . "
          where head.doc='QS'
          and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, wh.client, head.dateid, dept.client, dept.clientname
          ) as a
          order by docno $sorting";
        }
        break;
    }
    return $query;
  }

  public function default_QUERY_POSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $fcenter    = $config['params']['dataparams']['center'];
    $projectcode = $config['params']['dataparams']['project'];
    $deptcode = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    if ($companyid == 10) { //afti
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptcode != "") {
        $filter1 .= " and head.deptid = $deptid";
      }
      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";
      $barcodeitemnamefield = "item.barcode,item.itemname";
      $addjoin = "";
    }

    $isqty = 'stock.iss';

    switch ($reporttype) {
      case '1': //detailed
        $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname 
        from hqsstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' and date(head.dateid) between '$start' 
        and '$end' $filter $filter1
        union all
        select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname 
        from hqtstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' and date(head.dateid) between '$start' 
        and '$end' $filter $filter1
        order by docno $sorting";
        break;
      case '0': //summarized
        // $query = "select 
        //   status, docno, supplier, ext, clientname, dateid, wh, deptcode, deptname 
        // from (
        // select 'POSTED' as status,head.docno,
        // head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
        // date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname 
        // from hqsstock as stock
        // left join hqshead as head on head.trno=stock.trno
        // left join item on item.itemid=stock.itemid
        // left join transnum on transnum.trno=head.trno
        // left join client on client.client=head.client
        // left join client as wh on wh.client = head.wh
        // left join client as dept on dept.clientid = head.deptid
        // where head.doc='QS'
        // and date(head.dateid) between '$start' and '$end' $filter $filter1
        // group by head.docno, head.clientname,
        // wh.clientname, wh.client, head.dateid, dept.client, dept.clientname
        // union all
        // select 'POSTED' as status,head.docno,
        // head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
        // date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname 
        // from hqtstock as stock
        // left join hqshead as head on head.trno=stock.trno
        // left join item on item.itemid=stock.itemid
        // left join transnum on transnum.trno=head.trno
        // left join client on client.client=head.client
        // left join client as wh on wh.client = head.wh
        // left join client as dept on dept.clientid = head.deptid
        // where head.doc='QS'
        // and date(head.dateid) between '$start' and '$end' $filter $filter1
        // group by head.docno, head.clientname,
        // wh.clientname, wh.client, head.dateid, dept.client, dept.clientname
        // ) as a
        // order by docno $sorting";
        if ($companyid == 10 || $companyid == 12) { //afti
          $query = "select
        dateid,customerid, customername,docno, ext,salesperson,status
        from (
        select date(head.dateid) as dateid,client.client as customerid,
        head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'POSTED' as status
        from hqsstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname
        union all
        select date(head.dateid) as dateid,client.client as customerid,
         head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'POSTED' as status

        from hqtstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname
        ) as a
        order by docno $sorting";
        } else {
          $query = "select 
            status, docno, supplier, ext, clientname, dateid, wh, deptcode, deptname 
          from (
          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
          date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname 
          from hqsstock as stock
          left join hqshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='QS'
          and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, wh.client, head.dateid, dept.client, dept.clientname
          union all
          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname, wh.client as wh,
          date(head.dateid) as dateid, dept.client as deptcode, dept.clientname as deptname 
          from hqtstock as stock
          left join hqshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='QS'
          and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno, head.clientname,
          wh.clientname, wh.client, head.dateid, dept.client, dept.clientname
          ) as a
          order by docno $sorting";
        }
        break;
    }
    // var_dump($query);
    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $fcenter    = $config['params']['dataparams']['center'];
    $projectcode = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];
    $dept = $config['params']['dataparams']['dept'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($companyid == 10) { //afti
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    $isqty = 'stock.iss';

    switch ($reporttype) {
      case '1':
        $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
        from qsstock as stock
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' 
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        union all
        select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
        from qtstock as stock
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' 
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        order by docno $sorting";
        break;
      case '0': //SUMMARIZED
        // $query = "select 'UNPOSTED' as status ,head.yourref,
        //   head.docno,head.clientname as supplier,
        //   sum(stock.ext) as ext, wh.clientname, wh.client as wh,
        //   left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
        //   from qsstock as stock
        //   left join qshead as head on head.trno=stock.trno
        //   left join transnum on transnum.trno=head.trno
        //   left join client on client.client=head.client
        //   left join client as wh on wh.client = head.wh
        //   left join client as dept on dept.clientid = head.deptid
        //   where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
        //   group by head.docno,head.yourref,head.clientname,
        //   wh.clientname,head.dateid, wh.client, dept.client, dept.clientname
        //   union all
        //   select 'UNPOSTED' as status ,head.yourref,
        //   head.docno,head.clientname as supplier,
        //   sum(stock.ext) as ext, wh.clientname, wh.client as wh,
        //   left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
        //   from qtstock as stock
        //   left join qshead as head on head.trno=stock.trno
        //   left join transnum on transnum.trno=head.trno
        //   left join client on client.client=head.client
        //   left join client as wh on wh.client = head.wh
        //   left join client as dept on dept.clientid = head.deptid
        //   where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
        //   group by head.docno,head.yourref,head.clientname,
        //   wh.clientname,head.dateid, wh.client, dept.client, dept.clientname
        //   order by head.docno $sorting";
        if ($companyid == 10 || $companyid == 12) { //afti
          $query = "select
         dateid,customerid, customername,docno, ext,salesperson,status
        from (
        select date(head.dateid) as dateid,client.client as customerid,
         head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'UNPOSTED' as status
        from qsstock as stock
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname
        union all
        select date(head.dateid) as dateid,client.client as customerid,
         head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'UNPOSTED' as status

        from qtstock as stock
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname
        ) as a
        order by docno $sorting";
        } else {
          $query = "select 'UNPOSTED' as status ,head.yourref,
            head.docno,head.clientname as supplier,
            sum(stock.ext) as ext, wh.clientname, wh.client as wh,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
            from qsstock as stock
            left join qshead as head on head.trno=stock.trno
            left join transnum on transnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
            group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client, dept.client, dept.clientname
            union all
            select 'UNPOSTED' as status ,head.yourref,
            head.docno,head.clientname as supplier,
            sum(stock.ext) as ext, wh.clientname, wh.client as wh,
            left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
            from qtstock as stock
            left join qshead as head on head.trno=stock.trno
            left join transnum on transnum.trno=head.trno
            left join client on client.client=head.client
            left join client as wh on wh.client = head.wh
            left join client as dept on dept.clientid = head.deptid
            where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
            group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client, dept.client, dept.clientname
            order by head.docno $sorting";
        }
        break;
    }

    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $fcenter    = $config['params']['dataparams']['center'];
    $projectcode = $config['params']['dataparams']['project'];
    $dept = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";
      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }

    $isqty = 'stock.iss';

    switch ($reporttype) {
      case '0': //SUMMARIZED
        if ($companyid == 10 || $companyid == 12) { //afti
          $query = "select * from (
       select date(head.dateid) as dateid,client.client as customerid,
         head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'UNPOSTED' as status
        from qsstock as stock
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname

        UNION ALL

      select date(head.dateid) as dateid,client.client as customerid,
        head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'POSTED' as status
        from hqsstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname

        union all

        select date(head.dateid) as dateid,client.client as customerid,
         head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'UNPOSTED' as status

        from qtstock as stock
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname

        UNION ALL

        select date(head.dateid) as dateid,client.client as customerid,
         head.clientname as customername,head.docno, sum(stock.ext) as ext, agent.clientname as salesperson,
        'POSTED' as status
        from hqtstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client = head.agent
        where head.doc='QS'
        and date(head.dateid) between '$start' and '$end' $filter $filter1
        group by head.docno, head.clientname,head.dateid,client.client,agent.clientname
        ) as g order by g.docno $sorting";
        } else {
          $query = "select * from (
          select 'UNPOSTED' as status ,
          head.docno,head.clientname as supplier,
          sum(stock.ext) as ext, wh.clientname, wh.client as wh,head.yourref,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from qsstock as stock
          left join qshead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client, dept.client, dept.clientname

          UNION ALL

          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname,  wh.client as wh,head.yourref,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from hqsstock as stock
          left join hqshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client 
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='QS'
          and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client, dept.client, dept.clientname
          union all
          select 'UNPOSTED' as status ,
          head.docno,head.clientname as supplier,
          sum(stock.ext) as ext, wh.clientname, wh.client as wh,head.yourref,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from qtstock as stock
          left join qshead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client, dept.client, dept.clientname

          UNION ALL

          select 'POSTED' as status,head.docno,
          head.clientname as supplier,sum(stock.ext) as ext, wh.clientname,  wh.client as wh,head.yourref,
          left(head.dateid,10) as dateid, dept.client as deptcode, dept.clientname as deptname
          from hqtstock as stock
          left join hqshead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.client=head.client 
          left join client as wh on wh.client = head.wh
          left join client as dept on dept.clientid = head.deptid
          where head.doc='QS'
          and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.yourref,head.clientname,
            wh.clientname,head.dateid, wh.client, dept.client, dept.clientname
          ) as g order by g.docno $sorting";
        }
        break;
      case '1':
        $query = "select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
        from qsstock as stock 
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
        union all
        select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
        from hqsstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
        union all
        select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext,wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
        from qtstock as stock 
        left join qshead as head on head.trno=stock.trno
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1
        union all
        select head.yourref,head.docno,head.clientname as supplier" . $barcodeitemnamefield . ",stock.uom," . $isqty . " as iss,
        stock.isamt,stock.disc,stock.ext, wh.clientname,head.createby,stock.expiry,stock.loc,stock.rem,
        left(head.dateid,10) as dateid,stock.ref, dept.client as deptcode, dept.clientname as deptname
        from hqtstock as stock
        left join hqshead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join transnum on transnum.trno=head.trno
        left join client on client.client=head.client
        left join client as wh on wh.client = head.wh
        left join client as dept on dept.clientid = head.deptid
        " . $addjoin . "
        where head.doc='QS' and date(head.dateid) between '$start' and '$end' $filter $filter1";
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
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($companyid == 10) { //afti
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

      case 3:
        $posttype = 'With PO';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $layoutsize = '1000';
    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    $str = '';
    $count = 38;
    $page = 40;


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
    $str .= $this->reporter->col('Quotation Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    if ($companyid == 10) { //afti
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
    $str .= $this->reporter->endrow();
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
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype    = $config['params']['dataparams']['posttype'];

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
    $docno = "";
    $total = 0;
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= '<br/>';
          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Customer: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Customer: ' . $data->supplier, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          }
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Expiry', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Reference', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->expiry, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ref, '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

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
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  public function tableheadernew($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer ID', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '340', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Document#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    return $str;
  }


  public function reportDefaultLayout_SUMMARIZEDnew($config)
  {
    $result = $this->reportDefault($config);
    $count = 50;
    $page = 50;
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
    $str .= $this->tableheadernew($layoutsize, $config);


    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customerid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customername, '340', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, 'max-width:110px;overflow-wrap: break-word;'); //,'',0,'max-width:50px;overflow-wrap: break-word;'
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->salesperson, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheadernew($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }
    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '340', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL: ', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class