<?php

namespace App\Http\Classes\modules\modulereport\homeworks;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class stockcard
{

  private $modulename = "STOCKCARD";
  private $coreFunctions;
  private $fieldClass;
  private $companysetup;
  private $othersClass;
  private $logger;
  private $reporter;
  private $reportheader;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->reportheader = new reportheader;
  }

  public function createreportfilter($config)
  {
    $fields = ['radiotypeofreport', 'start', 'end', 'wh', 'luom', 'loc', 'prepared', 'approved', 'received', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'wh.lookupclass', 'lookupwhfilter');
    data_set($col1, 'wh.action', 'lookupwhfilter');
    data_set($col1, 'wh.required', true);
    data_set($col1, 'luom.lookupclass', 'uoms');
    data_set($col1, 'luom.required', true);
    data_set($col1, 'loc.lookupclass', 'locs');
    data_set(
      $col1,
      'radiotypeofreport.options',
      [
        ['label' => 'Ledger Report', 'value' => 'ledger', 'color' => 'orange'],
        ['label' => 'Receiving Report', 'value' => 'receiving', 'color' => 'orange'],
        ['label' => 'Purchase Order Report', 'value' => 'po', 'color' => 'orange'],
        ['label' => 'Sales Order Report', 'value' => 'so', 'color' => 'orange']
      ]
    );

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupclient');
      data_set($col1, 'prepared.lookupclass', 'prepared');

      data_set($col1, 'approved.readonly', true);
      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookupclient');
      data_set($col1, 'approved.lookupclass', 'approved');

      data_set($col1, 'received.readonly', true);
      data_set($col1, 'received.type', 'lookup');
      data_set($col1, 'received.action', 'lookupclient');
      data_set($col1, 'received.lookupclass', 'received');
    }

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $itemid = $config['params']['trno'];
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$user]);

    $data[0]['wh'] = $this->companysetup->getwh($config['params']);
    $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data[0]['wh']]);
    $whname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$whid]);
    $warehouse = $data[0]['wh'] . '~' . $whname;
    $uom = $this->coreFunctions->getfieldvalue("item", "uom", "itemid=?", [$itemid]);
    
    $paramstr = "select
    'PDFM' as print,
    'ledger' as typeofreport,
    '' as start,
    '' as end,
    '' as wh,
    '" . $whid . "' as whid,
    '' as loc,
    '' as uom,
    '' as approved,
    '' as received";
    
      $paramstr .= " ,'' as prepared ";

    return $this->coreFunctions->opentable($paramstr);
  }


  public function generateResult($config)
  {
    $reporttype = $config['params']['dataparams']['typeofreport'];

    switch ($reporttype) {
      case 'ledger':
        $query = $this->QUERY_LEDGER($config);
        break;
      case 'receiving':
        $query = $this->QUERY_RECEIVING($config);
        break;
      case 'po':
        $query = $this->QUERY_PO($config);
        break;
      case 'so':
        $query = $this->QUERY_SO($config);
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function QUERY_LEDGER($config)
  {
    ini_set('memory_limit', '-1');
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $whid       = $config['params']['dataparams']['whid'];
    $uom       = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $whbyfield = '';
    if ($whby != '') $whbyfield = " and stock.whid=" . $whid;

    $loc = '';
    if ($location != '') {
      $loc = 'and stock.loc = "' . $location . '"';
    }

    $query = "select '' as expiry, '' as posted,  itemname,  barcode, 0 as trno, '' as doc, 'beginning bal.' as docno,null as  dateid, 
    0 as cost, 0 as rrcost, 0 as qty,'' as yourref, '' as ourref,0 as  amt, 0 as iss, '' as disc, md5(itemid) as itemid,'' as  wh, '' as loc, 
    '' as type, '' as isimport, 0 as line, 0 as cur, '' as forex, 0 as factor, '' as rem, '' as encoded, '' as client, '' as clientname, '' as addr, 
    '' as tel,'' as  email, '' as tin, '' as mobile, '' as contact, '' as fax,'' as headrem, sum(qty-iss) as bal 
    from (select '' as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,head.rem as headrem
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid
    where item.itemid='$itemid' and head.dateid < '$start' " . $whbyfield . " $loc
    union all
    select '' as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,head.rem as headrem
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join cntnum on cntnum.trno=head.trno
    left join client on client.client=head.client
    left join client as wh on wh.clientid=stock.whid
    where item.itemid='$itemid'
    and head.dateid < '$start' " . $whbyfield . " $loc
    order by dateid,trno
    ) as ledger
    group by ledger.itemid,ledger.barcode,ledger.itemname
    UNION ALL
    select stock.expiry as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,head.rem as headrem,0 as bal
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid
    where item.itemid='$itemid' and head.dateid between '$start' and '$end' " . $whbyfield . " $loc
    union all
    select stock.expiry as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
    left(head.dateid,10) as dateid,
    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as qty,
    head.yourref as yourref,head.ourref as ourref,
    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,4) as iss,
    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
    stock.rem as rem,stock.encodeddate as encoded,
    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,head.rem as headrem,0 as bal
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
    left join cntnum on cntnum.trno=head.trno
    left join client on client.client=head.client
    left join client as wh on wh.clientid=stock.whid
    where item.itemid='$itemid' and head.dateid between '$start' and '$end' " . $whbyfield . " $loc
    group by stock.expiry, item.itemname, item.barcode, head.trno, head.doc, head.docno, head.dateid, head.rem, stock.disc, item.itemid, wh.client, 
    stock.loc, head.isimport, stock.line, head.cur, head.forex, head.factor, stock.rem, stock.encodeddate, client.client, client.clientname, 
    client.addr, client.tel, client.email, client.tin, client.mobile, client.contact, client.fax, uom.factor, stock.cost, stock.rrcost, 
    stock.qty, stock.amt, stock.iss, head.yourref, head.ourref order by dateid,trno";

    return $query;
  }

  public function QUERY_RECEIVING($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = md5($config['params']['dataid']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $uom       = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $loc = '';
    if ($location != '') {
      $loc = 'and stock.loc = "' . $location . '"';
    }

    $query = "select cntnum.doc, rrstatus.trno, rrstatus.line,
    client.clientname,
    rrstatus.cost,
    (rrstatus.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
    cast((case when rrstatus.bal=0 then 'applied' else round((rrstatus.bal / (case when ifnull(uom.factor, 0)=0 then 1
    else uom.factor end)),2) end) as char(50)) as status, date(rrstatus.dateid) as dateid,
    rrstatus.whid, rrstatus.uom, rrstatus.disc,
    rrstatus.docno, rrstatus.loc, wh.clientname as whname, stock.rrcost, head.cur, head.forex, item.isinactive, item.isimport,
    item.barcode, item.itemname, brand.brand_desc as brand, model.model_name as model, part.part_name as part, item.sizeid,
    item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
    item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
    from ((((((rrstatus left join client on client.clientid=rrstatus.clientid) left join client as wh on wh.clientid=rrstatus.whid)
    left join item on item.itemid=rrstatus.itemid) left join uom on uom.itemid=rrstatus.itemid and uom.uom='$uom')
    left join cntnum on cntnum.trno=rrstatus.trno) left join glhead as head on head.trno=rrstatus.trno)
    left join glstock as stock on stock.trno=rrstatus.trno and stock.line=rrstatus.line
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join part_masterfile as part on part.part_id = item.part
    left join model_masterfile as model on model.model_id = item.model
    where md5(rrstatus.itemid)='$itemid' and wh.client='$whby' and rrstatus.dateid between '$start' and '$end'  $loc
    order by rrstatus.dateid";

    return $query;
  }

  public function QUERY_PO($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = md5($config['params']['dataid']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $query = "select pohead.trno, pohead.doc, pohead.docno, date(pohead.dateid) as dateid, pohead.clientname,
        (postock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
        (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
        item.barcode, item.itemname,
        brand.brand_desc as brand, model.model_name as model, part.part_name as part,
        item.sizeid,
        item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
        item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2,postock.rrcost/uom.factor as rrcost
        from ((postock left join pohead on pohead.trno=postock.trno) left join item
        on item.itemid=postock.itemid) left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = pohead.trno
        left join client as wh on wh.clientid=postock.whid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join part_masterfile as part on part.part_id = item.part
        left join model_masterfile as model on model.model_id = item.model
        where md5(item.itemid)='$itemid' and wh.client ='$whby'
        and pohead.dateid between '$start' and '$end'
        group by

        pohead.trno, pohead.doc, pohead.docno, pohead.dateid, clientname,
        item.isinactive, item.isimport,
        item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
        item.amt, item.disc, item.amt2, item.disc2,
        item.famt, item.disc3, item.amt4, postock.qty, uom.factor,postock.qa,postock.rrcost

        union all
        select hpohead.trno, hpohead.doc, hpohead.docno, date(hpohead.dateid) as dateid, hpohead.clientname,
        (hpostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
        (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
        item.barcode, item.itemname,
        brand.brand_desc as brand, model.model_name as model, part.part_name as part,
        item.sizeid,
        item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
        item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2,hpostock.rrcost/uom.factor as rrcost
        from ((hpostock left join hpohead on hpohead.trno=hpostock.trno) left join item
        on item.itemid=hpostock.itemid) left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = hpohead.trno
        left join client as wh on wh.clientid=hpostock.whid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join part_masterfile as part on part.part_id = item.part
        left join model_masterfile as model on model.model_id = item.model
        where md5(item.itemid)='$itemid' and wh.client ='$whby'
        and hpohead.dateid between '$start' and '$end'
        group by

        hpohead.trno, hpohead.doc, hpohead.docno, hpohead.dateid, clientname,
        item.isinactive, item.isimport,
        item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
        item.amt, item.disc, item.amt2, item.disc2,
        item.famt, item.disc3, item.amt4, hpostock.qty, uom.factor,hpostock.qa,hpostock.rrcost
        order by dateid";

    return $query;
  }

  public function QUERY_SO($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = md5($config['params']['dataid']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $whby       = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];

    $query = "select sohead.trno, sohead.doc, sohead.docno, date(sohead.dateid) dateid, sohead.clientname,
          (sostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
          (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
          item.barcode, item.itemname, brand.brand_desc as brand, model.model_name as model, part.part_name as part, item.sizeid,
          item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
          item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
          from ((sostock
          left join sohead on sohead.trno=sostock.trno)
          left join item on item.itemid=sostock.itemid)
          left join uom on uom.itemid=item.itemid and uom.uom='$uom'
          left join transnum as cntnum on cntnum.trno = sohead.trno
          left join client as wh on wh.clientid=sostock.whid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          where md5(item.itemid)='$itemid'
          and wh.client ='$whby' and sohead.dateid between '$start' and '$end'
          group by
          sohead.trno, sohead.doc, sohead.docno, sohead.dateid,
          sostock.iss, uom.factor,sostock.qa,
          sohead.clientname, item.isinactive, item.isimport,
          item.barcode, item.itemname, model.model_name, part.part_name, brand.brand_desc, item.sizeid,
          item.amt, item.disc, item.amt2, item.disc2,
          item.famt , item.disc3, item.amt4
          union all
          select hsohead.trno, hsohead.doc, hsohead.docno, date(hsohead.dateid) as dateid,
          hsohead.clientname, (hsostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
          (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
          item.barcode, item.itemname, model.model_name as model, part.part_name as part, brand.brand_desc as brand, item.sizeid,
          item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
          item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
          from ((hsostock
          left join hsohead on hsohead.trno=hsostock.trno)
          left join item on item.itemid=hsostock.itemid)
          left join uom on uom.itemid=item.itemid and uom.uom='$uom'
          left join transnum as cntnum on cntnum.trno = hsohead.trno
          left join client as wh on wh.clientid=hsostock.whid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          where md5(item.itemid)='$itemid' and wh.client ='$whby' and hsohead.dateid between '$start' and '$end'
          group by
          hsohead.trno, hsohead.doc, hsohead.docno, hsohead.dateid,
          hsostock.iss, uom.factor,hsostock.qa,
          hsohead.clientname, item.isinactive, item.isimport,
          item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
          item.amt, item.disc, item.amt2, item.disc2,
          item.famt , item.disc3, item.amt4
          order by dateid";

    return $query;
  }

  public function reportplotting($config)
  {
    $data = $this->generateResult($config);

    $reporttype = $config['params']['dataparams']['typeofreport'];

    switch ($reporttype) {
      case 'ledger':
        $str = $this->report_PDF_LEDGER($config, $data);
        break;
      case 'receiving':
        $str = $this->report_PDF_RECEIVING($config, $data);
        break;
      case 'po':
        $str = $this->report_PDF_PO($config, $data);
        break;
      case 'so':
        $str = $this->report_PDF_SO($config, $data);
        break;
    }
    
    return $str;
  }

  public function rpt_agent_PDF($config, $data)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $count = 55;
    $page = 54;
    $fontsize = "11";
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/VERDANA.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/VERDANA.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/VERDANAB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');


    $this->reportheader->getheader($config);
    PDF::MultiCell(0, 0, "\n");

    // PDF::SetFont($fontbold, '', 12);
    // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    // PDF::SetFont($fontbold, '', 11);
    // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(760, 30, $this->modulename, '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Agent : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->client) ? $data[0]->client : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Telephone No/s: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Fax No/s: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Mobile No/s: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Remarks : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, (isset($data[0]->rem) ? $data[0]->rem : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Email Address: ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(275, 20, '', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 20, "Contact Person : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "", "T");

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Started : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->start) ? $data[0]->start : ''), '', 'L', false, 0);
    if (isset($data[0]->issupplier)) {
      if ($data[0]->issupplier == 1) {
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(300, 20, "|| SUPPLIER", '', 'L', false);
      } else {
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(300, 20, "Supplier", '', 'L', false);
      }
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Status : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->status) ? $data[0]->status : ''), '', 'L', false, 0);
    if (isset($data[0]->iscustomer)) {
      if ($data[0]->iscustomer == 1) {
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(300, 20, "|| CUSTOMER", '', 'L', false);
      } else {
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(300, 20, "Customer", '', 'L', false);
      }
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Quota : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->quota) ? $data[0]->quota : ''), '', 'L', false, 0);
    if (isset($data[0]->isagent)) {
      if ($data[0]->isagent == 1) {
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(300, 20, "|| AGENT", '', 'L', false);
      } else {
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(300, 20, "Agent", '', 'L', false);
      }
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Area : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->area) ? $data[0]->area : ''), '', 'L', false, 0);
    if (isset($data[0]->isemployee)) {
      if ($data[0]->isemployee == 1) {
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(300, 20, "|| EMPLOYEE", '', 'L', false);
      } else {
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(300, 20, "Employee", '', 'L', false);
      }
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Province : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->province) ? $data[0]->province : ''), '', 'L', false, 0);
    PDF::MultiCell(300, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Region : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->region) ? $data[0]->region : ''), '', 'L', false, 0);
    PDF::MultiCell(300, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Group : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, (isset($data[0]->groupid) ? $data[0]->groupid : ''), '', 'L', false, 0);
    PDF::MultiCell(300, 20, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $approved, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function PDF_LEDGER_HEADER($config, $data)
  {

    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = isset($config['params']['dataparams']['whname']) ? $config['params']['dataparams']['whname'] : '';
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
    $data = json_decode(json_encode($data), true);

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $this->reportheader->getheader($config);
  
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 17);
    PDF::MultiCell(700, 0, 'STOCKCARD LEDGER', '', 'L', false);

    $barcode = '';
    $itemname = '';
    for ($i = 0; $i < count($data); $i++) {
      if ((isset($data[$i]['barcode']) && !empty($data[$i]['barcode'])) && (isset($data[$i]['itemname']) && !empty($data[$i]['itemname']))) {
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
      }
    }

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, 'BARCODE: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(370, 0, $barcode, '', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "DATE RANGE: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, $start . ' TO ' . $end, '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "ITEM NAME: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(600, 0, $itemname, '', 'L', false, 1, '',  '');

    $sql = "select factor from uom where itemid = '$itemid' and uom = '$uom'";
    $uomfactor = $this->coreFunctions->opentable($sql);

    PDF::SetFont($font, '', $fontsize);
    //PDF::MultiCell(100, 0, "WAREHOUSE: ", '', 'L', false, 0, '',  '');

    PDF::MultiCell(80, 0, "WAREHOUSE: ", '', 'L', false, 0, 40,  165);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(250, 0, $whname . '~' . $wh, '', 'L', false, 0, '',  '');

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(250, 0, $wh, '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(40, 0, "UOM: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(170, 0, $uom, '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, "FACTOR: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, number_format($uomfactor[0]->factor, 2), '', 'L', false);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 0, "DATE", 'TLB', 'C', false, 0);
    PDF::MultiCell(200, 0, "CLIENTNAME", 'TLB', 'C', false, 0);
    PDF::MultiCell(65, 0, "EXPIRY", 'TLB', 'C', false, 0);
    PDF::MultiCell(100, 0, "DOCUMENT#", 'TLB', 'C', false, 0);
    PDF::MultiCell(70, 0, "QTY IN", 'TLB', 'C', false, 0);
    PDF::MultiCell(70, 0, "QTY OUT", 'TBL', 'C', false, 0);
    PDF::MultiCell(70, 0, "BALANCE", 'TLB', 'C', false, 0);
    PDF::MultiCell(70, 0, "PARTICULAR", 'TLRB', 'C', false);
    
  }

  public function report_PDF_LEDGER($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];
    //$width = 800; $height = 1000;


    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);


    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $this->PDF_LEDGER_HEADER($config, $data);

    $bal = 0;
    $totaliss = 0;
    $totalqty = 0;
    $tobal = 0;
    $bal = 0;
    $i = 0;

    $qtydec = 2;
    if ($companyid == 36) {
      $qtydec = 4;
    }

    //2023.10.26 FMM - remove numberformat sa qty at iss, dapat walang numberformat kasi ginamit sa formula sa lookup, inalis ko yung dash (-) kapag zero
    foreach ($data as $key => $value) {
      $qty = $value->qty;
      $iss = $value->iss;
    
      if ($i == 0) {
        $bal = $value->bal;
        if ($bal == 0) {
          $bal = $value->qty - $value->iss;
        }
      } else {
        // var_dump($bal);
        $bal = $bal - $iss;
        $bal = $bal + $qty;
      } //end if

      // var_dump($i);

      $tobal = $bal;
      if ($tobal == 0) {
        // $tobal = '-';
      } else {
        // if ($companyid != 11 && $companyid != 15) {
        $tobal = $tobal; //* -1;
        // }
        if ($companyid == 36) {
          $tobal = $tobal;
        } else {
          $tobal = round($tobal, 2);
        }
      } //end if

      if ($value->docno == 'beginning bal.') {
        
        $maxrow = 1;
        $dateid = $value->dateid;
        $clientname = $value->clientname;
        $expiry = $value->expiry;
        $docno = $value->docno;
        $qty = number_format($qty, $qtydec);
        $iss = number_format($iss, $qtydec);
        $balance = number_format($tobal, $qtydec);
        $qty = $qty < 0 ? '-' : $qty;
        $iss = $iss < 0 ? '-' : $iss;
        // if ($data->cr != 0) {
        //     $balance = $balance < 0 ? '-' : $balance * -1;
        // }
        $rem = $value->rem;

        $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
        $arr_clientname = $this->reporter->fixcolumn([$clientname], '16', 0);
        $arr_expiry = $this->reporter->fixcolumn([$expiry], '16', 0);
        $arr_docno = $this->reporter->fixcolumn([$docno], '18', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_iss = $this->reporter->fixcolumn([$iss], '13', 0);
        $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_clientname, $arr_expiry, $arr_docno, $arr_qty, $arr_iss, $arr_balance, $arr_rem]);

        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize);

            PDF::SetFont($font, '', 11);
            PDF::MultiCell(75, 0, '', '', 'C', false, 0);
            PDF::MultiCell(200, 0, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(65, 0, '', '', 'C', false, 0);
            PDF::MultiCell(100, 0, '', '', 'C', false, 0);
            PDF::MultiCell(70, 0, '', '', 'R', false, 0);
            PDF::MultiCell(70, 0, '', '', 'R', false, 0);
            PDF::MultiCell(70, 0, (isset($arr_balance[$r]) ? $arr_balance[$r] : '-'), '', 'R', false, 0);
            PDF::MultiCell(70, 0, '', '', 'R', false);
        }
        
      } else {
        $maxrow = 1;
        $dateid = $value->dateid;
        $clientname = $value->clientname;
        $expiry = $value->expiry;
        $docno = $value->docno;
        $qty = number_format($qty, $qtydec);
        $iss = number_format($iss, $qtydec);
        $balance = number_format($tobal, $qtydec);
        $qty = $qty < 0 ? '-' : $qty;
        $iss = $iss < 0 ? '-' : $iss;
        // if ($data->cr != 0) {
        //     $balance = $balance < 0 ? '-' : $balance * -1;
        // }
        $rem = $value->rem;

        $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
        $arr_clientname = $this->reporter->fixcolumn([$clientname], '16', 0);
        $arr_expiry = $this->reporter->fixcolumn([$expiry], '16', 0);
        $arr_docno = $this->reporter->fixcolumn([$docno], '13', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_iss = $this->reporter->fixcolumn([$iss], '13', 0);
        $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_clientname, $arr_expiry, $arr_docno, $arr_qty, $arr_iss, $arr_balance, $arr_rem]);

        for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(75, 0, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', false, 0);
            PDF::MultiCell(200, 0, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(65, 0, (isset($arr_expiry[$r]) ? $arr_expiry[$r] : ''), '', 'C', false, 0);
            PDF::MultiCell(100, 0, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'C', false, 0);
            PDF::MultiCell(70, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : '-'), '', 'R', false, 0);
            PDF::MultiCell(70, 0, (isset($arr_iss[$r]) ? $arr_iss[$r] : '-'), '', 'R', false, 0);
            PDF::MultiCell(70, 0, (isset($arr_balance[$r]) ? $arr_balance[$r] : '-'), '', 'R', false, 0);
            PDF::MultiCell(70, 0, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'R', false);
        }
            
      } //end if
      $totaliss += $iss;
      $totalqty += $qty;
      $i++;

      switch ($companyid) {
        case '24': //GFC
          if (PDF::getY() > 850) {
            $this->PDF_LEDGER_HEADER($config, $data);
          }
          break;
        default:
          if (PDF::getY() > 800) {
            $this->PDF_LEDGER_HEADER($config, $data);
          }
          break;
      }
    }
    
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(75, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", '', 'C', false, 0);
    PDF::MultiCell(65, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'TOTAL QTY : ', 'T', 'R', false, 0);
    PDF::MultiCell(70, 0, number_format($totalqty, $qtydec), 'T', 'R', false, 0);
    PDF::MultiCell(70, 0, number_format($totaliss, $qtydec), 'T', 'R', false, 0);
    PDF::MultiCell(70, 0, number_format($tobal, $qtydec), 'T', 'R', false, 0);
    PDF::MultiCell(70, 0, "", '', 'R', false);
    

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $approved, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_PDF_RECEIVING($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $this->reportheader->getheader($config);
  
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($fontbold, '', 17);
    PDF::MultiCell(700, 0, 'STOCKCARD RECEIVING', '', 'L', false);

    

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, "View Accounts from : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, $start . ' TO ' . $end, '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(75, 0, "View By Unit : ", '', 'L', false, 0, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, $uom, '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Item Code: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->barcode) ? $data[0]->barcode : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Price Levels ', '', 'L', false);

    $hmaxrow = 1;

    $itemname = $data[0]->itemname;
    $priceretail = number_format($data[0]->priceretail,2);
    $discretail = $data[0]->discretail;
    
    $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
    $arr_priceretail = $this->reporter->fixcolumn([$priceretail], '10', 0);
    $arr_discretail = $this->reporter->fixcolumn([$discretail], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_itemname,$arr_priceretail,$arr_discretail]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_priceretail[$r]) ? $arr_priceretail[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 1:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discretail[$r]) ? $arr_discretail[$r] : ''), '', 'L', false);
      
    }

    
    $brand = $data[0]->brand;
    $pricewhole = number_format($data[0]->pricewhole,2);
    $discwhole = $data[0]->discwhole;
    
    $arr_brand = $this->reporter->fixcolumn([$brand], '15', 0);
    $arr_pricewhole = $this->reporter->fixcolumn([$pricewhole], '10', 0);
    $arr_discwhole = $this->reporter->fixcolumn([$discwhole], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_brand,$arr_pricewhole,$arr_discwhole]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricewhole[$r]) ? $arr_pricewhole[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 2:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discwhole[$r]) ? $arr_discwhole[$r] : ''), '', 'L', false);
      
    }

    
    $model = $data[0]->model;
    $pricegrp1 = number_format($data[0]->pricegrp1,2);
    $discgrp1 = $data[0]->discgrp1;
    
    $arr_model = $this->reporter->fixcolumn([$model], '15', 0);
    $arr_pricegrp1 = $this->reporter->fixcolumn([$pricegrp1], '10', 0);
    $arr_discgrp1 = $this->reporter->fixcolumn([$discgrp1], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_model,$arr_pricegrp1,$arr_discgrp1]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_model[$r]) ? $arr_model[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricegrp1[$r]) ? $arr_pricegrp1[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 3:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discgrp1[$r]) ? $arr_discgrp1[$r] : ''), '', 'L', false);
      
    }

    
    $part = $data[0]->part;
    $pricegrp2 = number_format($data[0]->pricegrp2,2);
    $discgrp2 = $data[0]->discgrp2;
    
    $arr_part = $this->reporter->fixcolumn([$part], '15', 0);
    $arr_pricegrp2 = $this->reporter->fixcolumn([$pricegrp2], '10', 0);
    $arr_discgrp2 = $this->reporter->fixcolumn([$discgrp2], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_part,$arr_pricegrp2,$arr_discgrp2]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_part[$r]) ? $arr_part[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricegrp2[$r]) ? $arr_pricegrp2[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 4:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discgrp2[$r]) ? $arr_discgrp2[$r] : ''), '', 'L', false);
      
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Size: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]->sizeid) ? $data[0]->sizeid : ''), '', 'L', false, 0);

    if ((isset($data[0]->isinactive) ? $data[0]->isinactive : '') == 1) {
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(50, 0, 'Inactive', '', 'L', false, 0);
    } else {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(50, 0, 'Inactive', '', 'L', false, 0);
    }

    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    if ((isset($data[0]->isimport) ? $data[0]->isimport : '') == 1) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(70, 0, 'Imported', '', 'L', false);
    } else {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(70, 0, 'Imported', '', 'L', false);
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(200, 0, 'Run Date :' . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 0, "", "B");
    PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));

    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "Document #", 'B', 'C', false, 0);
    PDF::MultiCell(65, 0, "Date", 'B', 'C', false, 0);
    PDF::MultiCell(135, 0, "Supplier Name", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "Exch Rate", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "Purch. Cost", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "Landed Cost", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "Discount", 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, "Qty", 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, "Status", 'B', 'C', false);

    $totalqty = 0;
    $totalstatus = 0;
    
    
    
    foreach ($data as $key => $data) {
      

      $maxrow = 2;

      $docno = $data->docno;
      $dateid = $data->dateid;
      $clientname = $data->clientname;
      $forex = number_format($data->forex, 2);
      $rrcost = number_format($data->rrcost, 2);
      $cost = number_format($data->cost, 2);
      $disc = $data->disc;
      $qty = number_format($data->qty, 2);
      $status = $data->status;
      

      $arr_docno = $this->reporter->fixcolumn([$docno], '13', 0);
      $arr_dateid = $this->reporter->fixcolumn([$dateid], '10', 0);
      $arr_clientname = $this->reporter->fixcolumn([$clientname], '20', 0);
      $arr_forex = $this->reporter->fixcolumn([$forex], '10', 0);
      $arr_rrcost = $this->reporter->fixcolumn([$rrcost], '10', 0);
      $arr_cost = $this->reporter->fixcolumn([$cost], '10', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '10', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
      $arr_status = $this->reporter->fixcolumn([$status], '10', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_clientname, $arr_forex, $arr_rrcost, $arr_cost, $arr_disc, $arr_qty, $arr_status]);
      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(65, 15, ' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(135, 15, ' ' . (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_forex[$r]) ? $arr_forex[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_rrcost[$r]) ? $arr_rrcost[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' . (isset($arr_status[$r]) ? $arr_status[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, '', '', 'C', false, 0);
    PDF::MultiCell(65, 0, '', '', 'C', false, 0);
    PDF::MultiCell(135, 0, '', '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Grand Total", '', 'C', false, 0);
    PDF::MultiCell(75, 0, '', '', 'C', false, 0);
    PDF::MultiCell(75, 0, '', '', 'C', false, 0);
    PDF::MultiCell(75, 0, '', '', 'C', false, 0);
    PDF::MultiCell(50, 0, number_format($totalqty, 2), '', 'R', false, 0);
    PDF::MultiCell(50, 0, number_format($totalstatus, 2), '', 'R', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $approved, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_PDF_PO($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $this->reportheader->getheader($config);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($fontbold, '', 17);
    PDF::MultiCell(700, 0, 'STOCKCARD - PO', '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, "View Accounts from : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, $start . ' TO ' . $end, '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(75, 0, "View By Unit : ", '', 'R', false, 0, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, $uom, '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Item Code: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->barcode) ? $data[0]->barcode : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Price Levels ', '', 'L', false);

    
    $hmaxrow = 1;

    $itemname = $data[0]->itemname;
    $priceretail = number_format($data[0]->priceretail,2);
    $discretail = $data[0]->discretail;
    
    $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
    $arr_priceretail = $this->reporter->fixcolumn([$priceretail], '10', 0);
    $arr_discretail = $this->reporter->fixcolumn([$discretail], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_itemname,$arr_priceretail,$arr_discretail]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_priceretail[$r]) ? $arr_priceretail[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 1:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discretail[$r]) ? $arr_discretail[$r] : ''), '', 'L', false);
      
    }

    
    $brand = $data[0]->brand;
    $pricewhole = number_format($data[0]->pricewhole,2);
    $discwhole = $data[0]->discwhole;
    
    $arr_brand = $this->reporter->fixcolumn([$brand], '15', 0);
    $arr_pricewhole = $this->reporter->fixcolumn([$pricewhole], '10', 0);
    $arr_discwhole = $this->reporter->fixcolumn([$discwhole], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_brand,$arr_pricewhole,$arr_discwhole]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricewhole[$r]) ? $arr_pricewhole[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 2:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discwhole[$r]) ? $arr_discwhole[$r] : ''), '', 'L', false);
      
    }

    
    $model = $data[0]->model;
    $pricegrp1 = number_format($data[0]->pricegrp1,2);
    $discgrp1 = $data[0]->discgrp1;
    
    $arr_model = $this->reporter->fixcolumn([$model], '15', 0);
    $arr_pricegrp1 = $this->reporter->fixcolumn([$pricegrp1], '10', 0);
    $arr_discgrp1 = $this->reporter->fixcolumn([$discgrp1], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_model,$arr_pricegrp1,$arr_discgrp1]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_model[$r]) ? $arr_model[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricegrp1[$r]) ? $arr_pricegrp1[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 3:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discgrp1[$r]) ? $arr_discgrp1[$r] : ''), '', 'L', false);
      
    }

    
    $part = $data[0]->part;
    $pricegrp2 = number_format($data[0]->pricegrp2,2);
    $discgrp2 = $data[0]->discgrp2;
    
    $arr_part = $this->reporter->fixcolumn([$part], '15', 0);
    $arr_pricegrp2 = $this->reporter->fixcolumn([$pricegrp2], '10', 0);
    $arr_discgrp2 = $this->reporter->fixcolumn([$discgrp2], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_part,$arr_pricegrp2,$arr_discgrp2]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_part[$r]) ? $arr_part[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricegrp2[$r]) ? $arr_pricegrp2[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 4:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discgrp2[$r]) ? $arr_discgrp2[$r] : ''), '', 'L', false);
      
    }

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->itemname) ? $data[0]->itemname : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->priceretail) ? number_format($data[0]->priceretail, 2) : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 1:', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discretail) ? $data[0]->discretail : ''), '', 'L', false);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->brand) ? $data[0]->brand : ''), '', 'L', false, 0);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->pricewhole) ? $data[0]->pricewhole : ''), '', 'L', false, 0);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 2: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discwhole) ? $data[0]->discwhole : ''), '', 'L', false);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->model) ? $data[0]->model : ''), '', 'L', false, 0);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->pricegrp1) ? number_format($data[0]->pricegrp1, 2) : ''), '', 'L', false, 0);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 3: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discgrp1) ? $data[0]->discgrp1 : ''), '', 'L', false);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->part) ? $data[0]->part : ''), '', 'L', false, 0);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->pricegrp2) ? number_format($data[0]->pricegrp2, 2) : ''), '', 'L', false, 0);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 4: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discgrp2) ? $data[0]->discgrp2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Size: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]->sizeid) ? $data[0]->sizeid : ''), '', 'L', false, 0);

    if ((isset($data[0]->isinactive) ? $data[0]->isinactive : '') == 1) {
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(50, 0, 'Inactive', '', 'L', false, 0);
    } else {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(50, 0, 'Inactive', '', 'L', false, 0);
    }

    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    if ((isset($data[0]->isimport) ? $data[0]->isimport : '') == 1) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(70, 0, 'Imported', '', 'L', false);
    } else {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(70, 0, 'Imported', '', 'L', false);
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(200, 0, 'Run Date :' . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 0, "", "B");
    PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));

    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "Document #", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "Date", 'B', 'C', false, 0);
    PDF::MultiCell(200, 0, "Supplier", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "Ordered", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "Received", 'B', 'C', false);

    
    foreach ($data as $key => $data) {
      

      $maxrow = 1;

      $docno = $data->docno;
      $dateid = $data->dateid;
      $qty = number_format($data->qty, 2);
      $clientname = $data->clientname;
      $qa = number_format($data->qa, 2);
      

      $arr_docno = $this->reporter->fixcolumn([$docno], '25', 0);
      $arr_dateid = $this->reporter->fixcolumn([$dateid], '10', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_clientname = $this->reporter->fixcolumn([$clientname], '25', 0);
      $arr_qa = $this->reporter->fixcolumn([$qa], '13', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_qty, $arr_clientname, $arr_qa]);
      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(200, 15, ' ' . (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_qa[$r]) ? $arr_qa[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

    }

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $approved, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_PDF_SO($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $this->reportheader->getheader($config);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($fontbold, '', 17);
    PDF::MultiCell(700, 0, 'STOCKCARD - SO', '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, "View Accounts from : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, $start . ' TO ' . $end, '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(75, 0, "View By Unit : ", '', 'L', false, 0, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, $uom, '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Item Code: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->barcode) ? $data[0]->barcode : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Price Levels ', '', 'L', false);

    
    $hmaxrow = 1;

    $itemname = $data[0]->itemname;
    $priceretail = number_format($data[0]->priceretail,2);
    $discretail = $data[0]->discretail;
    
    $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
    $arr_priceretail = $this->reporter->fixcolumn([$priceretail], '10', 0);
    $arr_discretail = $this->reporter->fixcolumn([$discretail], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_itemname,$arr_priceretail,$arr_discretail]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_priceretail[$r]) ? $arr_priceretail[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 1:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discretail[$r]) ? $arr_discretail[$r] : ''), '', 'L', false);
      
    }

    
    $brand = $data[0]->brand;
    $pricewhole = number_format($data[0]->pricewhole,2);
    $discwhole = $data[0]->discwhole;
    
    $arr_brand = $this->reporter->fixcolumn([$brand], '15', 0);
    $arr_pricewhole = $this->reporter->fixcolumn([$pricewhole], '10', 0);
    $arr_discwhole = $this->reporter->fixcolumn([$discwhole], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_brand,$arr_pricewhole,$arr_discwhole]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricewhole[$r]) ? $arr_pricewhole[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 2:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discwhole[$r]) ? $arr_discwhole[$r] : ''), '', 'L', false);
      
    }

    
    $model = $data[0]->model;
    $pricegrp1 = number_format($data[0]->pricegrp1,2);
    $discgrp1 = $data[0]->discgrp1;
    
    $arr_model = $this->reporter->fixcolumn([$model], '15', 0);
    $arr_pricegrp1 = $this->reporter->fixcolumn([$pricegrp1], '10', 0);
    $arr_discgrp1 = $this->reporter->fixcolumn([$discgrp1], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_model,$arr_pricegrp1,$arr_discgrp1]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_model[$r]) ? $arr_model[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricegrp1[$r]) ? $arr_pricegrp1[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 3:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discgrp1[$r]) ? $arr_discgrp1[$r] : ''), '', 'L', false);
      
    }

    
    $part = $data[0]->part;
    $pricegrp2 = number_format($data[0]->pricegrp2,2);
    $discgrp2 = $data[0]->discgrp2;
    
    $arr_part = $this->reporter->fixcolumn([$part], '15', 0);
    $arr_pricegrp2 = $this->reporter->fixcolumn([$pricegrp2], '10', 0);
    $arr_discgrp2 = $this->reporter->fixcolumn([$discgrp2], '10', 0);

    $hmaxrow = $this->othersClass->getmaxcolumn([$arr_part,$arr_pricegrp2,$arr_discgrp2]);
    for ($r = 0; $r < $hmaxrow; $r++) {

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(200, 0, ' ' . (isset($arr_part[$r]) ? $arr_part[$r] : ''), '', 'L', false, 0);
      
      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_pricegrp2[$r]) ? $arr_pricegrp2[$r] : ''), '', 'L', false, 0);

      PDF::SetFont($font, '', 11);
      if($r==0){
        PDF::MultiCell(100, 0, 'Disc 4:', '', 'L', false, 0);
        
      }else{
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, ' ' . (isset($arr_discgrp2[$r]) ? $arr_discgrp2[$r] : ''), '', 'L', false);
      
    }

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->itemname) ? $data[0]->itemname : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->priceretail) ? number_format($data[0]->priceretail, 2) : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 1:', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discretail) ? $data[0]->discretail : ''), '', 'L', false);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->brand) ? $data[0]->brand : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->pricewhole) ? $data[0]->pricewhole : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 2: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discwhole) ? $data[0]->discwhole : ''), '', 'L', false);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->model) ? $data[0]->model : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->pricegrp1) ? number_format($data[0]->pricegrp1, 2) : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 3: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discgrp1) ? $data[0]->discgrp1 : ''), '', 'L', false);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(200, 0, (isset($data[0]->part) ? $data[0]->part : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->pricegrp2) ? number_format($data[0]->pricegrp2, 2) : ''), '', 'L', false, 0);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, 'Disc 4: ', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(100, 0, (isset($data[0]->discgrp2) ? $data[0]->discgrp2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Size: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]->sizeid) ? $data[0]->sizeid : ''), '', 'L', false, 0);

    if ((isset($data[0]->isinactive) ? $data[0]->isinactive : '') == 1) {
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(50, 0, 'Inactive', '', 'L', false, 0);
    } else {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(50, 0, 'Inactive', '', 'L', false, 0);
    }

    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    if ((isset($data[0]->isimport) ? $data[0]->isimport : '') == 1) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(70, 0, 'Imported', '', 'L', false);
    } else {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(70, 0, 'Imported', '', 'L', false);
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(200, 0, 'Run Date :' . date('M-d-Y h:i:s a', time()), '', 'L', false);

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 0, "", "B");
    PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));



    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "Document #", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "Date", 'B', 'C', false, 0);
    PDF::MultiCell(200, 0, "Customer Name", 'B', 'C', false, 0);
    PDF::MultiCell(150, 0, "Ordered", 'B', 'C', false, 0);
    PDF::MultiCell(150, 0, "Sold", 'B', 'C', false);

    foreach ($data as $key => $data) {
      

      $maxrow = 1;

      $docno = $data->docno;
      $dateid = $data->dateid;
      $qty = number_format($data->qty, 2);
      $clientname = $data->clientname;
      $qa = number_format($data->qa, 2);
      

      $arr_docno = $this->reporter->fixcolumn([$docno], '25', 0);
      $arr_dateid = $this->reporter->fixcolumn([$dateid], '10', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_clientname = $this->reporter->fixcolumn([$clientname], '25', 0);
      $arr_qa = $this->reporter->fixcolumn([$qa], '13', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_qty, $arr_clientname, $arr_qa]);
      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(200, 15, ' ' . (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(150, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(150, 15, ' ' . (isset($arr_qa[$r]) ? $arr_qa[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

    }

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'C', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $approved, '', 'C', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'R');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
