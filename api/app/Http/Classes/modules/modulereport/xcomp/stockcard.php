<?php

namespace App\Http\Classes\modules\modulereport\xcomp;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class stockcard
{
  private $modulename = "STOCKCARD";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter($config)
  {
    $fields = ['radiotypeofreport', 'start', 'end', 'wh', 'luom', 'loc', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'wh.lookupclass', 'whs');
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

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select
    'PDFM' as print,
    'ledger' as typeofreport,
    adddate(left(now(),10),-360) as start,
    date(now()) as end,
    '' as wh,
    '' as loc,
    '' as uom,
    '' as prepared,
    '' as approved,
    '' as received
  ");
  }


  public function generateResult($config)
  {
    $reporttype = $config['params']['dataparams']['typeofreport'];

    switch ($reporttype) {
      case 'ledger':
        $query = $this->QUERY_LEDGER($config);
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
        break;
      default:
        switch ($reporttype) {
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
        break;
    }

    

  }

  public function QUERY_LEDGER($config)
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

    $whbyfield = '';
    if ($whby != '') $whbyfield = " and wh.client='" . $whby . "'";

    $loc = '';
    if ($location != '') {
      $loc = 'and stock.loc = "' . $location . '"';
    }

    $query = "select '' as expiry, '' as posted,  '' as itemname,  '' as barcode, 0 as trno, '' as doc, 
                    'beginning bal.' as docno,null as  dateid, 0 as cost, 0 as rrcost, 0 as qty,
                    '' as yourref, '' as ourref,0 as  amt, 0 as iss, '' as disc, md5(itemid) as itemid,'' as  wh,
                    '' as loc, '' as type, '' as isimport, 0 as line, 0 as cur, '' as forex,
                    0 as factor, '' as rem, '' as encoded, '' as client, '' as clientname, '' as addr, '' as tel,
                    '' as  email, '' as tin, '' as mobile, '' as contact, '' as fax, sum(qty-iss) as bal,stockref 
              from (select '' as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,
                          head.doc as doc,head.docno as docno,left(head.dateid,10) as dateid,
                          round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
                          round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
                          round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
                          head.yourref as yourref,head.ourref as ourref,
                          round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
                          round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
                          stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
                          head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
                          stock.rem as rem,stock.encodeddate as encoded,
                          client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,stock.ref as stockref
                          from glhead as head
                          left join glstock as stock on stock.trno=head.trno
                          left join item on item.itemid=stock.itemid
                          left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
                          left join client as wh on wh.clientid=stock.whid
                          left join cntnum on cntnum.trno=head.trno
                          left join client on client.clientid=head.clientid
                          where md5(item.itemid)='$itemid'
                          and date(head.dateid) < '$start' " . $whbyfield . " $loc
                    union all
                    select '' as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,head.doc as doc,head.docno as docno,
                          left(head.dateid,10) as dateid,
                          round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
                          round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
                          round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
                          head.yourref as yourref,head.ourref as ourref,
                          round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
                          round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
                          stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
                          head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
                          stock.rem as rem,stock.encodeddate as encoded,
                          client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,stock.ref as stockref
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
                    left join cntnum on cntnum.trno=head.trno
                    left join client on client.client=head.client
                    left join client as wh on wh.clientid=stock.whid
                    where md5(item.itemid)='$itemid'
                    and date(head.dateid) < '$start' " . $whbyfield . " $loc
                    order by dateid,trno
                    ) as ledger
              group by ledger.itemid,ledger.stockref
              UNION ALL
              select stock.expiry as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,
                    head.doc as doc,head.docno as docno,left(head.dateid,10) as dateid,
                    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
                    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
                    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
                    head.yourref as yourref,head.ourref as ourref,
                    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
                    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
                    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
                    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
                    stock.rem as rem,stock.encodeddate as encoded,
                    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,0 as bal,stock.ref as stockref
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
              left join client as wh on wh.clientid=stock.whid
              left join cntnum on cntnum.trno=head.trno
              left join client on client.clientid=head.clientid
              where md5(item.itemid)='$itemid'
              and date(head.dateid) between '$start' and '$end' " . $whbyfield . " $loc
              union all
              select stock.expiry as expiry, '' as posted,item.itemname,item.barcode,head.trno as trno,
                    head.doc as doc,head.docno as docno,left(head.dateid,10) as dateid,
                    round(case when uom.factor <= 1 then ifnull((stock.cost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as cost,
                    round(case when uom.factor <= 1 then ifnull((stock.rrcost / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.rrcost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as rrcost,
                    round(case when uom.factor <= 1 then ifnull((stock.qty * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as qty,
                    head.yourref as yourref,head.ourref as ourref,
                    round(case when uom.factor <= 1 then ifnull((stock.amt / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as amt,
                    round(case when uom.factor <= 1 then ifnull((stock.iss * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) else ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end,2) as iss,
                    stock.disc as disc,item.itemid as itemid,wh.client as wh,stock.loc as loc,0 as type,
                    head.isimport as isimport,stock.line as line,head.cur as cur,head.forex as forex,head.factor as factor,
                    stock.rem as rem,stock.encodeddate as encoded,
                    client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.mobile,client.contact,client.fax,0 as bal,stock.ref as stockref
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
              left join cntnum on cntnum.trno=head.trno
              left join client on client.client=head.client
              left join client as wh on wh.clientid=stock.whid
              where md5(item.itemid)='$itemid' and date(head.dateid) between '$start' and '$end' " . $whbyfield . " $loc
              group by stock.expiry,item.itemname,item.barcode,head.trno,head.doc,head.docno,head.dateid,stock.disc,item.itemid,
                        wh.client,stock.loc,head.isimport,stock.line,head.cur,head.forex,head.factor,stock.rem,
                        stock.encodeddate,client.client,client.clientname,client.addr,client.tel,
                        client.email,client.tin,client.mobile,client.contact,
                        client.fax,uom.factor,stock.cost,stock.rrcost,stock.qty,stock.amt,stock.iss,head.yourref,head.ourref,stock.ref
              order by dateid,trno";
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
    where md5(rrstatus.itemid)='$itemid' and wh.client='$whby' and date(rrstatus.dateid) between '$start' and '$end'  $loc
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
        item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
        from ((postock left join pohead on pohead.trno=postock.trno) left join item
        on item.itemid=postock.itemid) left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = pohead.trno
        left join client as wh on wh.clientid=postock.whid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join part_masterfile as part on part.part_id = item.part
        left join model_masterfile as model on model.model_id = item.model
        where md5(item.itemid)='$itemid' and wh.client ='$whby'
        and date(pohead.dateid) between '$start' and '$end'
        group by

        pohead.trno, pohead.doc, pohead.docno, pohead.dateid, clientname,
        item.isinactive, item.isimport,
        item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
        item.amt, item.disc, item.amt2, item.disc2,
        item.famt, item.disc3, item.amt4, postock.qty, uom.factor,postock.qa

        union all
        select hpohead.trno, hpohead.doc, hpohead.docno, date(hpohead.dateid) as dateid, hpohead.clientname,
        (hpostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qty,
        (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) as qa, item.isinactive, item.isimport,
        item.barcode, item.itemname,
        brand.brand_desc as brand, model.model_name as model, part.part_name as part,
        item.sizeid,
        item.amt as priceretail, item.disc as discretail, item.amt2 as pricewhole, item.disc2 as discwhole,
        item.famt as pricegrp1, item.disc3 as discgrp1, item.amt4 as pricegrp2, item.disc as discgrp2
        from ((hpostock left join hpohead on hpohead.trno=hpostock.trno) left join item
        on item.itemid=hpostock.itemid) left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = hpohead.trno
        left join client as wh on wh.clientid=hpostock.whid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join part_masterfile as part on part.part_id = item.part
        left join model_masterfile as model on model.model_id = item.model
        where md5(item.itemid)='$itemid' and wh.client ='$whby'
        and date(hpohead.dateid) between '$start' and '$end'
        group by

        hpohead.trno, hpohead.doc, hpohead.docno, hpohead.dateid, clientname,
        item.isinactive, item.isimport,
        item.barcode, item.itemname, brand.brand_desc, model.model_name, part.part_name, item.sizeid,
        item.amt, item.disc, item.amt2, item.disc2,
        item.famt, item.disc3, item.amt4, hpostock.qty, uom.factor,hpostock.qa
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
          and wh.client ='$whby' and date(sohead.dateid) between '$start' and '$end'
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
          where md5(item.itemid)='$itemid' and wh.client ='$whby' and date(hsohead.dateid) between '$start' and '$end'
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

  public function reportplotting($config, $data)
  {
    // $data = $this->generateResult($config);


    $reporttype = $config['params']['dataparams']['typeofreport'];


    if ($config['params']['dataparams']['print'] == "default") {
      // $str = $this->rpt_agent_layout($config, $data);
      switch ($reporttype) {
        case 'ledger':
          $str = $this->report_default_LEDGER($config, $data);
          break;
        case 'receiving':
          $str = $this->report_default_RECEIVING($config, $data);
          break;
        case 'po':
          $str = $this->report_default_PO($config, $data);
          break;
        case 'so':
          $str = $this->report_default_SO($config, $data);
          break;
      }
    } else {
      // $str = $this->rpt_agent_PDF($config, $data);
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
    }

    return $str;
  }

  public function report_PDF_LEDGER_Header($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname         = $config['params']['dataparams']['whname'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
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


    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 15);
    PDF::MultiCell(700, 0, 'STOCKCARD LEDGER', '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'BARCODE: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(370, 0, (isset($data[0]['barcode']) ? $data[0]['barcode'] : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "DATE RANGE: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $start . ' TO ' . $end, '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "ITEM NAME: ", '', 'L', false, 0, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, (isset($data[0]['itemname']) ? $data[0]['itemname'] : ''), '', 'L', false, 1, '',  '');

    $sql = "select factor from uom where itemid = '$itemid' and uom = '$uom'";
    $uomfactor = $this->coreFunctions->opentable($sql);

    PDF::SetFont($font, '', $fontsize);
    //PDF::MultiCell(100, 0, "WAREHOUSE: ", '', 'L', false, 0, '',  '');

    if ($wh == '') {
      PDF::MultiCell(100, 0, "Warehouse: ALL", '', 'L', false, 0, '',  '');
      PDF::MultiCell(250, 0, '', '', 'L', false, 0, '',  '');
    } else {
      PDF::MultiCell(100, 0, "WAREHOUSE: ", '', 'L', false, 0, 40,  165);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(250, 0, $whname . '~' . $wh, '', 'L', false, 0, '',  '');
    }
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(250, 0, $wh, '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "UOM: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, $uom, '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "FACTOR: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, number_format($uomfactor[0]->factor, 2), '', 'L', false);

    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 0, "DATE", 'TLB', 'C', false, 0);
    PDF::MultiCell(100, 0, "DOCUMENT#", 'TLB', 'C', false, 0);
    PDF::MultiCell(100, 0, "CLIENTNAME", 'TLB', 'C', false, 0);
    PDF::MultiCell(75, 0, "LOCATION", 'TLB', 'C', false, 0);
    PDF::MultiCell(75, 0, "EXPIRY", 'TLB', 'C', false, 0);
    PDF::MultiCell(65, 0, "QTY IN", 'TLB', 'C', false, 0);
    PDF::MultiCell(65, 0, "QTY OUT", 'TBL', 'C', false, 0);
    PDF::MultiCell(65, 0, "BALANCE", 'TLB', 'C', false, 0);
    PDF::MultiCell(100, 0, "PARTICULAR", 'TLRB', 'C', false);
  }

  public function report_PDF_LEDGER($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->report_PDF_LEDGER_Header($params, $data);

    $bal = 0;
    $totalqty = 0;
    $totaliss = 0;
    $tobal = 0;
    $iss = 0;

    $qty = 0;
    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;
      $qty = $data[$i]['qty'] == 0 ? '-' : $data[$i]['qty'];
      $iss = $data[$i]['iss'] == 0 ? '-' : $data[$i]['iss'];
      if ($i == 0) {
        $bal = $data[$i]['bal'];
        if ($bal == 0) $bal = $data[$i]['qty'];
      } else {
        $bal = $bal - $iss;
        $bal = $bal + $qty;
      }
      $tobal = $bal;
      if ($tobal == 0) {
        $tobal = '-';
      } else {
          $tobal = $tobal * 1;
        $tobal = number_format($tobal, 2);
      }
      $arr_dateid = $this->reporter->fixcolumn([$data[$i]['dateid']], '10', 0);
      $arr_clientname = $this->reporter->fixcolumn([$data[$i]['clientname']], '15', 0);
      $arr_location = $this->reporter->fixcolumn([$data[$i]['loc']], '15', 0);
      $arr_expiry = $this->reporter->fixcolumn([$data[$i]['expiry']], '10', 0);
      $arr_docno = $this->reporter->fixcolumn([$data[$i]['docno']], '15', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
      $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
      $arr_tobal = $this->reporter->fixcolumn([$tobal], '10', 0);
      $arr_rem = $this->reporter->fixcolumn([$data[$i]['rem']], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_clientname, $arr_location, $arr_expiry, $arr_docno, $arr_qty, $arr_iss, $arr_tobal, $arr_rem]);

      for ($r = 0; $r < $maxrow; $r++) {
        if ($data[$i]['docno'] == 'beginning bal.') {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(75, 0, "", '', 'C', false, 0);
          PDF::MultiCell(100, 0, $data[$i]['docno'], '', 'C', false, 0);
          PDF::MultiCell(100, 0, "", '', 'C', false, 0);
          PDF::MultiCell(75, 0, "", '', 'C', false, 0);
          PDF::MultiCell(75, 0, "", '', 'C', false, 0);
          PDF::MultiCell(65, 0, "", '', 'R', false, 0);
          PDF::MultiCell(65, 0, "", '', 'R', false, 0);
          PDF::MultiCell(65, 0, number_format($data[$i]['bal'], 2), '', 'R', false, 0);
          PDF::MultiCell(100, 0, "", '', 'C', false);
        }
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(75, 0, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(100, 0, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(100, 0, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false, 0);
        PDF::MultiCell(75, 0, (isset($arr_location[$r]) ? $arr_location[$r] : ''), '', 'L', false, 0);
        PDF::MultiCell(75, 0, (isset($arr_expiry[$r]) ? $arr_expiry[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(65, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(65, 0, (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(65, 0, (isset($arr_tobal[$r]) ? $arr_tobal[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(100, 0, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'C', false);
      }

      $totaliss = $totaliss + $iss;
      $totalqty = $totalqty + $qty;
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, "", 'B', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "", '', 'C', false, 0);
    PDF::MultiCell(75, 0, 'TOTAL QTY : ', '', 'R', false, 0);
    PDF::MultiCell(65, 0, number_format($totalqty, 2), '', 'R', false, 0);
    PDF::MultiCell(65, 0, number_format($totaliss, 2), '', 'R', false, 0);
    PDF::MultiCell(65, 0, $tobal, '', 'R', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');



    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_PDF_RECEIVING_Header($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
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


    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


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

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->itemname) ? $data[0]->itemname : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->discretail) ? $data[0]->discretail : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->brand) ? $data[0]->brand : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricewhole) ? $data[0]->pricewhole : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 2: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discwhole) ? $data[0]->discwhole : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->model) ? $data[0]->model : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricegrp1) ? $data[0]->pricegrp1 : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 3: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discgrp1) ? $data[0]->discgrp1 : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->part) ? $data[0]->part : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricegrp2) ? $data[0]->pricegrp2 : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 4: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discgrp2) ? $data[0]->discgrp2 : ''), '', 'L', false);

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
    PDF::MultiCell(75, 0, "Date", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "Supplier Name", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "Exch Rate", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "Purch. Cost", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "Landed Cost", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "Discount", 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, "Qty", 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, "Status", 'B', 'C', false);
  }

  public function report_PDF_RECEIVING($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->report_PDF_RECEIVING_Header($params, $data);

    $totalqty = 0;
    $totalstatus = 0;

    foreach ($data as $key => $data) {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(100, 0, $data->docno, '', 'C', false, 0);
      PDF::MultiCell(75, 0, $data->dateid, '', 'C', false, 0);
      PDF::MultiCell(100, 0, $data->clientname, '', 'C', false, 0);
      PDF::MultiCell(75, 0, number_format($data->forex, 2), '', 'C', false, 0);
      PDF::MultiCell(75, 0, number_format($data->rrcost, 2), '', 'C', false, 0);
      PDF::MultiCell(75, 0, number_format($data->cost, 2), '', 'C', false, 0);
      PDF::MultiCell(100, 0, $data->disc, '', 'C', false, 0);
      PDF::MultiCell(50, 0, number_format($data->qty, 2), '', 'C', false, 0);
      PDF::MultiCell(50, 0, $data->status, '', 'C', false);

      $totalqty = $totalqty + $data->qty;
      $totalstatus = $totalstatus + $data->status;
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, '', '', 'C', false, 0);
    PDF::MultiCell(75, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Grand Total", '', 'C', false, 0);
    PDF::MultiCell(75, 0, '', '', 'C', false, 0);
    PDF::MultiCell(75, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'C', false, 0);
    PDF::MultiCell(50, 0, number_format($totalqty, 2), '', 'C', false, 0);
    PDF::MultiCell(50, 0, number_format($totalstatus, 2), '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_PDF_PO_Header($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
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

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($fontbold, '', 17);
    PDF::MultiCell(700, 0, 'STOCKCARD - PO', '', 'L', false);

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

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->itemname) ? $data[0]->itemname : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->discretail) ? $data[0]->discretail : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->brand) ? $data[0]->brand : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricewhole) ? $data[0]->pricewhole : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 2: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discwhole) ? $data[0]->discwhole : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->model) ? $data[0]->model : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricegrp1) ? $data[0]->pricegrp1 : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 3: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discgrp1) ? $data[0]->discgrp1 : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->part) ? $data[0]->part : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricegrp2) ? $data[0]->pricegrp2 : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 4: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discgrp2) ? $data[0]->discgrp2 : ''), '', 'L', false);

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
    PDF::MultiCell(150, 0, "Ordered", 'B', 'C', false, 0);
    PDF::MultiCell(150, 0, "Received", 'B', 'C', false);
  }

  public function report_PDF_PO($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->report_PDF_PO_Header($params, $data);

    foreach ($data as $key => $data) {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(100, 0, $data->docno, 'B', 'C', false, 0);
      PDF::MultiCell(100, 0, $data->dateid, 'B', 'C', false, 0);
      PDF::MultiCell(200, 0, $data->clientname, 'B', 'C', false, 0);
      PDF::MultiCell(150, 0, number_format($data->qty, 2), 'B', 'C', false, 0);
      PDF::MultiCell(150, 0, number_format($data->qa, 2), 'B', 'C', false);
    }

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_PDF_SO_Header($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $itemid     = $config['params']['dataid'];
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


    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


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

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Item Name: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->itemname) ? $data[0]->itemname : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Retail: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->discretail) ? $data[0]->discretail : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Brand: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->brand) ? $data[0]->brand : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Wholesale: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricewhole) ? $data[0]->pricewhole : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 2: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discwhole) ? $data[0]->discwhole : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Model: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->model) ? $data[0]->model : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Group 1: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricegrp1) ? $data[0]->pricegrp1 : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 3: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discgrp1) ? $data[0]->discgrp1 : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Part#: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]->part) ? $data[0]->part : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Group 2: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->pricegrp2) ? $data[0]->pricegrp2 : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, 'Disc 4: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]->discgrp2) ? $data[0]->discgrp2 : ''), '', 'L', false);

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
  }

  public function report_PDF_SO($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->report_PDF_SO_Header($params, $data);


    foreach ($data as $key => $data) {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(100, 0, $data->docno, 'B', 'C', false, 0);
      PDF::MultiCell(100, 0, $data->dateid, 'B', 'C', false, 0);
      PDF::MultiCell(200, 0, $data->clientname, 'B', 'C', false, 0);
      PDF::MultiCell(150, 0, number_format($data->qty, 2), 'B', 'C', false, 0);
      PDF::MultiCell(150, 0, number_format($data->qa, 2), 'B', 'C', false);
    }

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_default_LEDGER($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Avenir";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD LEDGER  ', null, null, false, $border, '', '', $font, '18', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE :' . (isset($result[1]->barcode) ? $result[1]->barcode : ''), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '525', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM NAME :', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col((isset($result[1]->itemname) ? $result[1]->itemname : '') . '', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('DATE RANGE: ' . $start . ' TO ' . $end, '525', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->col('WAREHOUSE: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col($wh, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('UOM: ' . $uom, '325', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');

    $sql = "select factor from uom where itemid = '$itemid' and uom = '$uom'";
    $uomfactor = $this->coreFunctions->opentable($sql);

    $str .= $this->reporter->col('FACTOR: ' . number_format($uomfactor[0]->factor, 2), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '75', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('CLIENT NAME', '230', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('EXPIRY', '70', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('QTY IN ', '75', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('QTY OUT ', '75', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('PARTICULAR', '95', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '1px');

    $bal = 0;
    $totaliss = 0;
    $totalqty = 0;
    $tobal = 0;
    $bal = 0;
    $i = 0;

    foreach ($result as $key => $data) {
      $qty = $data->qty;

      if ($qty == 0) { // change to zero para lumabas yung may mga decimal na qty
        $qty = '-';
      } //end if

      $iss = $data->iss;

      if ($iss == 0) {
        $iss = '-';
      } //end if

      if ($i == 0) {
        $bal = $data->bal;
        if ($bal == 0) {
          $bal = $data->qty;
        }
      } else {
        $bal = $bal - $iss;
        $bal = $bal + $qty;
      } //end if

      $tobal = $bal;
      if ($tobal == 0) {
        $tobal = '-';
      } else {
          $tobal = $tobal * -1;
        $tobal = number_format($tobal, 2);
      } //end if


      $str .= $this->reporter->startrow();
      if ($data->docno == 'beginning bal.') {
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '230', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->bal, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp;' . $data->clientname, '230', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->expiry, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($qty, '65', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($iss, '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($tobal, '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '95', null, false, '1psx solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      } //end if
      $totaliss = $totaliss + $iss;
      $totalqty = $totalqty + $qty;
      $i++;
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '75', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp;', '230', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp;', '70', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL QTY : ', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalqty, 2), '65', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totaliss, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($tobal, '70', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp;', '95', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '350', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '225', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '225', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '350', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_default_RECEIVING($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD - RECEIVING ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('View Accounts from :', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($start . ' to ' . $end, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('View by Unit :', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->barcode) ? $result[0]->barcode : ''), '375', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Price Levels', '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Name:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->itemname) ? $result[0]->itemname : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Retail:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->priceretail) ? number_format($result[0]->priceretail, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    // $str .= $this->reporter->col('Disc 1:','75',null,false,$border,'','L',$font,$fontsize,'','','1px');
    $str .= $this->reporter->col((isset($result[0]->discretail) ? $result[0]->discretail : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->brand) ? $result[0]->brand : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Wholesale:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricewhole) ? number_format($result[0]->pricewhole, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discwhole) ? $result[0]->discwhole : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->model) ? $result[0]->model : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp1) ? number_format($result[0]->pricegrp1, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 3:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp1) ? $result[0]->discgrp1 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('773');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part#:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->part) ? $result[0]->part : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp2) ? number_format($result[0]->pricegrp2, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 4:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp2) ? $result[0]->discgrp2 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Size:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->sizeid) ? $result[0]->sizeid : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isinactive) ? $result[0]->isinactive : '') == 1) {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isimport) ? $result[0]->isimport : '') == 1) {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Name', '175', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Exch Rate', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Purch. Cost', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Landed Cost', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    $totalqty = 0;
    $totalstatus = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '175', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->forex, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->rrcost, 2), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->status, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $totalqty = $totalqty + $data->qty;
      $totalstatus = $totalstatus + $data->status;
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalstatus, 2), '75', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '350', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '225', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '225', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '350', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '225', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_default_PO($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD - PO ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('View Accounts from :', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($start . ' to ' . $end, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('View by Unit :', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->barcode) ? $result[0]->barcode : ''), '375', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Price Levels', '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Name:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->itemname) ? $result[0]->itemname : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Retail:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->priceretail) ? number_format($result[0]->priceretail, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discretail) ? $result[0]->discretail : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->brand) ? $result[0]->brand : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Wholesale:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricewhole) ? number_format($result[0]->pricewhole, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discwhole) ? $result[0]->discwhole : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->model) ? $result[0]->model : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp1) ? number_format($result[0]->pricegrp1, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 3:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp1) ? $result[0]->discgrp1 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('773');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part#:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->part) ? $result[0]->part : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp2) ? number_format($result[0]->pricegrp2, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 4:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp2) ? $result[0]->discgrp2 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Size:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->sizeid) ? $result[0]->sizeid : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isinactive) ? $result[0]->isinactive : '') == 1) {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isimport) ? $result[0]->isimport : '') == 1) {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Name', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ordered', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Received', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qa, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_default_SO($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $itemid     = $config['params']['dataid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['typeofreport'];
    $wh         = $config['params']['dataparams']['wh'];
    $uom        = $config['params']['dataparams']['uom'];
    $location   = $config['params']['dataparams']['loc'];
    $prepared   = $config['params']['dataparams']['prepared'];
    $received   = $config['params']['dataparams']['received'];
    $approved   = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 55;
    $page = 54;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STOCKCARD - SO ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('View Accounts from :', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($start . ' to ' . $end, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('View by Unit :', '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col($uom, '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Code:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->barcode) ? $result[0]->barcode : ''), '375', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Price Levels', '350', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Item Name:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->itemname) ? $result[0]->itemname : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Retail:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->priceretail) ? number_format($result[0]->priceretail, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discretail) ? $result[0]->discretail : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Brand:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->brand) ? $result[0]->brand : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Wholesale:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricewhole) ? number_format($result[0]->pricewhole, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discwhole) ? $result[0]->discwhole : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('770');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->model) ? $result[0]->model : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 1:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp1) ? number_format($result[0]->pricegrp1, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 3:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp1) ? $result[0]->discgrp1 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('773');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part#:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->part) ? $result[0]->part : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Group 2:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->pricegrp2) ? number_format($result[0]->pricegrp2, 2) : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('Disc 4:', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->discgrp2) ? $result[0]->discgrp2 : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('775');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Size:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($result[0]->sizeid) ? $result[0]->sizeid : ''), '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isinactive) ? $result[0]->isinactive : '') == 1) {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Innactive', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col('', '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    if ((isset($result[0]->isimport) ? $result[0]->isimport : '') == 1) {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    } else {
      $str .= $this->reporter->col('Imported', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ordered', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sold', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qa, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}
