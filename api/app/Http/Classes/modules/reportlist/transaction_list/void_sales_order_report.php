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

class void_sales_order_report
{
  public $modulename = 'Void Sales Order Report';
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
    $companyid    = $config['params']['companyid'];
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'approved'];


    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    data_set($col1, 'dcentername.required', false);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
    if ($companyid == 19) { //housegem
      unset($fields[0]);
      unset($fields[1]);
      unset($fields[2]);
    }

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
    '' as centername,'' as dcentername,
    '' as dclientname,'' as reportusers,'0' as clientid
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
    $result = $this->reportDefault($config);
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid    = $config['params']['companyid'];

    switch ($companyid) {
      case 19: //housegem
        $result = $this->reportDefaultLayout_Customer($config, $result);
        break;

      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_SUMMARIZED($config, $result);
            break;
          case '1': // DETAILED
            $result = $this->report_cancelled_so_sso_layout($config, $result);
            break;
        }
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];
    $companyid    = $config['params']['companyid'];

    switch ($companyid) {
      case 19: //housegem
        $query = $this->default_QUERY_hg_POSTED($config);
        break;
      default:
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
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $leftjoin = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      if ($reporttype == 1) {
        $leftjoin .= " left join client as cl on cl.client=vt.client ";
        $filter .= " and cl.clientid = '$clientid' ";
      } else {
        $leftjoin .= " left join client as cl on cl.client=head.client ";
        $filter .= " and cl.clientid = '$clientid' ";
      }
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
        $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
        break;
      default:
        $barcodeitemnamefield = ",item.barcode,item.itemname";
        $addjoin = "";
        break;
    }

    switch ($reporttype) {
      case '1':
        $query = "
        select qs.trno, vt.ourref, agent.clientname as agentname, qs.clientname, qs.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from hvthead as vt 
        left join hvtstock as stock on vt.trno = stock.trno
        left join hsqhead as sq on sq.trno = vt.sotrno
        left join item on item.itemid = stock.itemid
        left join hqshead as qs on vt.sotrno = qs.sotrno
        left join client as agent on agent.client = qs.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter

        union all 

        select qs.trno, vt.ourref, agent.clientname as agentname, qs.clientname, qs.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from hvshead as vt 
        left join hvsstock as stock on vt.trno = stock.trno
        left join hsshead as sq on sq.trno = vt.sotrno
        
        left join hsrhead as qthead on qthead.sotrno = sq.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno

        left join item on item.itemid = stock.itemid
        left join hqshead as qs on qthead.qtrno = qs.trno
        left join client as agent on agent.client = qthead.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter 
        ";
        break;
      case '0':
        $query = "select 
          status, docno, customer, ext, dateid
        from (
        select 'POSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        left(head.dateid, 10) as dateid 
        FROM hvthead as head
        left join hvtstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        ) as a

        union all 

        select 
          status, docno, customer, ext, dateid
        from (
        select 'POSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        left(head.dateid, 10) as dateid 
        FROM hvshead as head
        left join hvsstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        ) as a
        order by docno $sorting
        ";
        break;
    }

    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $leftjoin = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      if ($reporttype == 1) {
        $leftjoin .= " left join client as cl on cl.client=vt.client ";
        $filter .= " and cl.clientid = '$clientid' ";
      } else {
        $leftjoin .= " left join client as cl on cl.client=head.client ";
        $filter .= " and cl.clientid = '$clientid' ";
      }
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    switch ($config['params']['companyid']) {
      case 10: //afti 
      case 12: //afti usd
        $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
        $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
        break;
      default:
        $barcodeitemnamefield = ",item.barcode,item.itemname";
        $addjoin = "";
        break;
    }

    switch ($reporttype) {
      case '1':
        $query = "
        select qs.trno, vt.ourref, agent.clientname as agentname, qs.clientname, qs.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from vthead as vt 
        left join vtstock as stock on vt.trno = stock.trno
        left join hsqhead as sq on sq.trno = vt.sotrno
        left join item on item.itemid = stock.itemid
        left join hqshead as qs on vt.sotrno = qs.sotrno
        left join client as agent on agent.client = qs.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter

        union all 

        select qs.trno, vt.ourref, agent.clientname as agentname, qthead.clientname, qthead.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from vshead as vt 
        left join vsstock as stock on vt.trno = stock.trno
        left join hsshead as sq on sq.trno = vt.sotrno
        
        left join hsrhead as qthead on qthead.sotrno = sq.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno

        left join item on item.itemid = stock.itemid
        left join hqshead as qs on qthead.qtrno = qs.trno
        left join client as agent on agent.client = qthead.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter 
        ";
        break;
      case '0':
        $query = "select 
          status, docno, customer, ext, dateid
        from (
        select 'UNPOSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        date(head.dateid) as dateid 
        FROM vthead as head
        left join vtstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        ) as a

        union all

        select 
          status, docno, customer, ext, dateid
        from (
        select 'UNPOSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        date(head.dateid) as dateid 
        FROM vshead as head
        left join vsstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        ) as a
        order by docno $sorting
        ";
        break;
    }

    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $leftjoin = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      if ($reporttype == 1) {
        $leftjoin .= " left join client as cl on cl.client=vt.client ";
        $filter .= " and cl.clientid = '$clientid' ";
      } else {
        $leftjoin .= " left join client as cl on cl.client=head.client ";
        $filter .= " and cl.clientid = '$clientid' ";
      }
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $isqty = 'stock.iss';

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
        $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
        break;
      default:
        $barcodeitemnamefield = ",item.barcode,item.itemname";
        $addjoin = "";
        break;
    }

    switch ($reporttype) {
      case '0':
        $query = "
        select 
          status, docno, customer, ext, dateid
        from (
        select 'POSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        date(head.dateid) as dateid 
        FROM hvthead as head
        left join hvtstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        UNION ALL
        select 'UNPOSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        date(head.dateid) as dateid 
        FROM vthead as head
        left join vtstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        ) as g 

        union all 

        select 
          status, docno, customer, ext, dateid
        from (
        select 'POSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        date(head.dateid) as dateid 
        FROM hvshead as head
        left join hvsstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        UNION ALL
        select 'UNPOSTED' as status,head.docno,
        head.clientname as customer, sum(stock.ext) as ext,
        date(head.dateid) as dateid 
        FROM vshead as head
        left join vsstock as stock on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join transnum as transnum on transnum.trno = head.trno $leftjoin
        where date(head.dateid) between '$start' and '$end' 
        $filter 
        group by head.docno, head.clientname, head.dateid
        ) as g 
        order by docno $sorting

        ";
        break;
      case '1':
        $query = "
      select qs.trno, vt.ourref, agent.clientname as agentname, qs.clientname, qs.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from vthead as vt 
        left join vtstock as stock on vt.trno = stock.trno
        left join hsqhead as sq on sq.trno = vt.sotrno
        left join item on item.itemid = stock.itemid
        left join hqshead as qs on vt.sotrno = qs.sotrno
        left join client as agent on agent.client = qs.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter

        union all 

        select qs.trno, vt.ourref, agent.clientname as agentname, qthead.clientname, qthead.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from vshead as vt 
        left join vsstock as stock on vt.trno = stock.trno
        left join hsshead as sq on sq.trno = vt.sotrno
        
        left join hsrhead as qthead on qthead.sotrno = sq.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno

        left join item on item.itemid = stock.itemid
        left join hqshead as qs on qthead.qtrno = qs.trno
        left join client as agent on agent.client = qthead.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter 

        union all

        select qs.trno, vt.ourref, agent.clientname as agentname, qs.clientname, qs.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from hvthead as vt 
        left join hvtstock as stock on vt.trno = stock.trno
        left join hsqhead as sq on sq.trno = vt.sotrno
        left join item on item.itemid = stock.itemid
        left join hqshead as qs on vt.sotrno = qs.sotrno
        left join client as agent on agent.client = qs.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter

        union all 

        select qs.trno, vt.ourref, agent.clientname as agentname, qthead.clientname, qthead.yourref, item.itemname, sq.docno as sodocno, vt.rem, sjstock.docno as sjdocno,vt.reason, vt.rem, vt.sotrno,vt.newpo
        from hvshead as vt 
        left join hvsstock as stock on vt.trno = stock.trno
        left join hsshead as sq on sq.trno = vt.sotrno
        
        left join hsrhead as qthead on qthead.sotrno = sq.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno

        left join item on item.itemid = stock.itemid
        left join hqshead as qs on qthead.qtrno = qs.trno
        left join client as agent on agent.client = qthead.agent
        left join transnum on transnum.trno = vt.trno $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('sj','ai') and s.refx<>0)
        as sjstock on sjstock.refx=qs.trno
        where date(vt.dateid) between '$start' and '$end' $filter";
        break;
    }

    return $query;
  }

  public function default_QUERY_hg_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and cl.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $query = " select head.docno, date(head.dateid) as dateid, head.ourref, agent.clientname as agentname, cl.clientname,item.barcode, item.itemname, head.rem, stock.iss-stock.qa as voidqty
        from hsohead as head
        left join hsostock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client as cl on cl.client=head.client
        left join client as agent on agent.client = head.agent
        left join transnum on transnum.trno = head.trno
        where date(head.dateid) between '$start' and '$end'  and stock.void=1  $filter  ";
    return $query;
  }



  public function cancelled_so_sso_header($config)
  {
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
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PO CANCELLATIONS/ AMENDMENT & CLOSED TRANSACTION ', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CONTROL #', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('REQUESTOR', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('PO NO.', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('ITEM CODE ', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('SO #', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('ERP #', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('DR/SI#', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('REASON', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('REMARKS', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->col('NEW PO#', '100', null, 'darkblue', $border, 'TBLR', 'C', $font, $fontsize, 'B', 'yellow', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function report_cancelled_so_sso_layout($config, $result)
  {

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->cancelled_so_sso_header($config);

    $docno = "";
    $total = 0;
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $qry1 = "select group_concat(distinct 
                concat(
                  case when num.bref = 'POD' then 'D' 
                  else 'P' 
                end,
                right(num.yr,2),
                right(head.docno,5))) as docno,sum(qsstock.ext) as ext, head.insurance
                from hpohead as head
                left join hqshead as qthead on qthead.sotrno=head.sotrno
                left join hqsstock as qsstock on qsstock.trno=qthead.trno
                left join item as i on i.itemid=qsstock.itemid
                left join transnum as num on num.trno = head.trno
                where head.sotrno = $data->sotrno
                group by head.docno, head.insurance
              union all
              select group_concat(distinct 
                concat(
                  case when num.bref = 'POD' then 'D' 
                  else 'P' 
                end,
                right(num.yr,2),
                right(head.docno,5))) as docno,sum(qsstock.ext) as ext, head.insurance
                from pohead as head
                left join hqshead as qthead on qthead.sotrno=head.sotrno
                left join hqsstock as qsstock on qsstock.trno=qthead.trno
                left join item as i on i.itemid=qsstock.itemid
                left join transnum as num on num.trno = head.trno
                where head.sotrno = $data->sotrno
                group by head.docno,head.insurance
                union all
                select group_concat(distinct 
                concat(
                  case when num.bref = 'JO' then 'O' 
                  else 'O' 
                end,
                right(num.yr,2),
                right(joh.docno,5))) as docno, sum(qsstock.ext) as ext, qsstock.insurance 
                from hjohead as joh left join hjostock as jos on jos.trno = joh.trno
                left join hsrstock as qsstock on qsstock.trno = jos.refx and qsstock.line = jos.line
                left join hsrhead as qshead on qshead.trno = qsstock.trno
                left join transnum as num on num.trno = joh.trno
                where qshead.sotrno = $data->sotrno
                group by joh.docno,qsstock.insurance
                union all
                select group_concat(distinct 
                concat(
                  case when num.bref = 'JO' then 'O' 
                  else 'O' 
                end,
                right(num.yr,2),
                right(joh.docno,5))) as docno, sum(qsstock.ext) as ext, qsstock.insurance 
                from johead as joh left join jostock as jos on jos.trno = joh.trno
                left join hsrstock as qsstock on qsstock.trno = jos.refx and qsstock.line = jos.line
                left join hsrhead as qshead on qshead.trno = qsstock.trno
                left join transnum as num on num.trno = joh.trno
                where qshead.sotrno = $data->sotrno
                group by joh.docno,qsstock.insurance";
        $subresult1 = $this->coreFunctions->opentable($qry1);
        $erpnum = '';

        if (!empty($subresult1)) {
          foreach ($subresult1 as $key => $data1) {
            $erpnum = str_replace(",", ",<br>", $data1->docno);
          }
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->ourref, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->agentname, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->sodocno, '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col($erpnum, '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col(($data->sjdocno != '' ? $data->sjdocno : 'NONE'), '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->reason, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col($data->newpo, '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
      }
    }

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
    $str .= $this->report_summary_Header($config);
    $str .= $this->tableheader($layoutsize, $config);

    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customer, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->report_summary_Header($config);
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

  public function report_summary_Header($config)
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

    $str = '';
    $count = 38;
    $page = 40;

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
    $str .= $this->reporter->col('Cancelled SO SSO Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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


  public function hgem_void_sales_order_header($config)
  {

    $str = '';

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str .= '<br><br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('VOID SALES ORDER REPORT ', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->col('DOCNO', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->col('CUSTOMER', '285', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->col('', '5', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->col('ITEMNAME', '285', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->col('AGENT NAME', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->col('VOID QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px', '');
    $str .= $this->reporter->endrow();

    return $str;
  }


  public function reportDefaultLayout_Customer($config)
  {
    $result = $this->reportDefault($config);
    $count = 50;
    $page = 50;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->hgem_void_sales_order_header($config);

    foreach ($result as $key => $data) {
      $itemname = $data->itemname;
      $clientname = $data->clientname;
      $docno = isset($data->docno) ? $data->docno : '';
      $date = isset($data->dateid) ? $data->dateid : '';
      $agentname = isset($data->agentname) ? $data->agentname : '';
      $voidqty = isset($data->voidqty) ? number_format($data->voidqty, 2) : 0;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($date, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($clientname, '285', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($itemname, '285', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($agentname, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($voidqty, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->hgem_void_sales_order_header($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class