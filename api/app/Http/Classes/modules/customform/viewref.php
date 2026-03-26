<?php

namespace App\Http\Classes\modules\customform;

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

class viewref
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'References';
  public $gridname = 'viewrefgrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;

  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $cols = [];

    switch ($config['params']['doc']) {
      case 'PL':
        $cols = ['action', 'ref', 'barcode', 'itemname', 'rrqty', 'uom'];
        $this->modulename = 'Packing List Details';
        break;
      case 'SA':
      case 'SB':
      case 'SC':
        $cols = ['action', 'docno', 'dateid', 'rem', 'status', 'waybill'];
        break;
      case 'BQ':
        $cols = ['action', 'docno', 'dateid'];
        break;
      default:
        $cols = ['action', 'docno', 'dateid', 'rem'];
        break;
    }

    $tab = [$this->gridname => ['gridcolumns' => $cols]];

    $stockbuttons = ['jumpmodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    switch ($config['params']['doc']) {
      case 'PL':
        break;

      default:
        $obj[0][$this->gridname]['columns'][3]['label'] = 'Particulars';
        break;
    }

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    return [];
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid, 0.0 as db, 0.0 as cr,0.0 as bal');
  }

  public function data($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $qry = '';
    $url = '';
    switch ($doc) {
      case 'SR':
        $qry = "
          select head.docno,head.dateid, head.rem as rem,head.trno ,head.doc,'/module/sales/' as url,'module' as moduletype
          from hqshead as head 
          left join hsrhead as sr on sr.qtrno = head.trno
          where sr.trno = " . $trno . "
          union all
          select head.docno,head.dateid, head.rem as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype
          from hqshead as head 
          left join srhead as sr on sr.qtrno = head.trno
          where sr.trno = " . $trno . "
          union all
          select head.docno,head.dateid, '' as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype
          from hsshead as head 
          left join hsrhead as sr on sr.sotrno = head.trno
          where sr.trno = " . $trno . "
          union all
          select head.docno,head.dateid, '' as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype
          from sshead as head 
          left join hsrhead as sr on sr.sotrno = head.trno
          where sr.trno = " . $trno . "
          union all
          select head.docno,head.dateid,CAST(concat('Total JO Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype
          from johead as head
          left join jostock as stock on stock.trno = head.trno
          where stock.refx =" . $trno . "
          group by head.docno,head.dateid,head.doc,head.trno
          union all
          select head.docno,head.dateid,CAST(concat('Total JO Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype
          from hjohead as head
          left join hjostock as stock on stock.trno = head.trno
          where stock.refx = " . $trno . "
          group by head.docno,head.dateid,head.doc,head.trno
          ";
        break;
      case 'RR':
      case 'AC':
      case 'JC':
      case 'SN':
        $qry = '
          select po.docno,po.dateid,CAST(concat("Total PO Amt: ",sum(s.ext)) as CHAR) as rem,po.trno ,po.doc,"" as url,"module" as moduletype 
          from hpohead as po 
          left join hpostock as s on s.trno = po.trno 
          left join glstock as g on g.refx = po.trno and g.linex = s.line 
          where g.trno = ' . $trno . ' 
          group by po.docno,po.dateid,po.trno,po.doc

          union all

          select po.docno,po.dateid,CAST(concat("Total PO Amt: ",sum(s.ext)) as CHAR) as rem,po.trno ,po.doc,"/module/purchase/" as url,"module" as moduletype from hjohead as po 
          left join hjostock as s on s.trno = po.trno 
          left join glstock as g on g.refx = po.trno and g.linex = s.line 
          where g.trno = ' . $trno . ' 
          group by po.docno,po.dateid,po.trno,po.doc

          union all

          select apledger.docno,apledger.dateid,CAST(concat("Amount: ",apledger.db+apledger.cr,"  -  ","BALANCE: ",apledger.bal) as CHAR) as rem,head.trno ,head.doc,"/module/purchase/" as url,"module" as moduletype
          from apledger 
          left join glhead as head on head.trno = apledger.trno 
          where apledger.trno=' . $trno . '

          union all

          select arledger.docno,arledger.dateid,CAST(concat("Amount: ",arledger.db+arledger.cr,"  -  ","BALANCE: ",arledger.bal) as CHAR) as rem,head.trno ,head.doc,"/module/purchase/" as url,"module" as moduletype 
          from arledger 
          left join glhead as head on head.trno = arledger.trno
          where arledger.trno=' . $trno . '

          union all

          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno ,head.doc,"/module/payable/" as url,"module" as moduletype 
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno 
          where head.doc in ("PV","CV") and detail.refx=' . $trno . ' 

          union all

          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno ,head.doc,"/module/payable/" as url,"module" as moduletype from glhead as head
          left join gldetail as detail on detail.trno=head.trno 
          where head.doc in ("PV","CV") and detail.refx=' . $trno . ' 

          union all

          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno ,head.doc,"/module/accounting/" as url,"module" as moduletype 
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno 
          where head.doc in ("GJ") and detail.refx=' . $trno . ' 

          union all

          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno ,head.doc,"/module/accounting/" as url,"module" as moduletype 
          from glhead as head
          left join gldetail as detail on detail.trno=head.trno 
          where head.doc in ("GJ") and detail.refx=' . $trno . ' 

          union all 

          select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.isqty) as CHAR) as rem,head.trno ,head.doc,"/module/purchase/" as url,"module" as moduletype 
          from lahead as head
          left join lastock as stock on stock.trno=head.trno 
          left join item on item.itemid=stock.itemid 
          where stock.refx=' . $trno . ' and head.doc = "DM"

          union all

          select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.isqty) as CHAR) as rem,head.trno ,head.doc,"/module/purchase/" as url,"module" as moduletype 
          from glhead as head
          left join glstock as stock on stock.trno=head.trno 
          left join item on item.itemid = stock.itemid 
          where stock.refx=' . $trno . ' and head.doc = "DM"

          union all

          select num.docno,head.dateid,CAST(concat("Counter Receipt: ",num.docno , " (" ,num.center,")") as CHAR) as rem,head.trno ,head.doc,"/module/receivable/" as url,"module" as moduletype 
          from transnum as num
          left join krhead as head on head.trno = num.trno 
          left join arledger as ledger on ledger.kr = num.trno and num.doc = "KR" 
          where ledger.trno = ' . $trno . ' 

          union all

          select cntnum.docno, h.dateid, CAST(concat("Out-reference: ", item.itemname, ", QtyOut: ", c.served) as CHAR) as rem,h.trno ,h.doc,"" as url,"module" as moduletype 
          from costing as c 
          left join glstock as s on s.trno=c.trno and s.line = c.line 
          left join cntnum on cntnum.trno=c.trno 
          left join glhead as h on h.trno=cntnum.trno 
          left join item on item.itemid=s.itemid
          where c.refx=' . $trno . ' and cntnum.postdate is not null

          union all

          select cntnum.docno, h.dateid, CAST(concat("Out-reference: ", item.itemname, ", QtyOut: ", c.served) as CHAR) as rem,h.trno ,h.doc,"" as url,"module" as moduletype
          from costing as c 
          left join lastock as s on s.trno=c.trno and s.line = c.line 
          left join cntnum on cntnum.trno=c.trno 
          left join lahead as h on h.trno=cntnum.trno 
          left join item on item.itemid=s.itemid
          where c.refx=' . $trno . ' and cntnum.postdate is null';
        break;
      case 'DM':
      case 'CM':
        $qry = '
          select apledger.docno,apledger.dateid,CAST(concat("Amount: ",apledger.db+apledger.cr,"  -  ","BALANCE: ",apledger.bal) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype
          from apledger 
          left join cntnum as num on num.trno = apledger.trno 
          where num.trno=' . $trno . '

          union all

          select arledger.docno,arledger.dateid,CAST(concat("Amount: ",arledger.db+arledger.cr,"  -  ","BALANCE: ",arledger.bal) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype 
          from arledger
          left join cntnum as num on num.trno = arledger.trno 
          where num.trno=' . $trno . '

          union all

          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno 
          where detail.refx=' . $trno . '

          union all

          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
          from glhead as head
          left join gldetail as detail on detail.trno=head.trno 
          where detail.refx=' . $trno . '

          union all 

          select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.isqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
          from lahead as head
          left join lastock as stock on stock.trno=head.trno 
          left join item on item.itemid=stock.itemid 
          where stock.isqty<>0 and stock.refx=' . $trno . '

          union all

          select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.isqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
          from glhead as head
          left join glstock as stock on stock.trno=head.trno 
          left join item on item.itemid = stock.itemid 
          where stock.isqty<>0 and stock.refx=' . $trno . '

          union all

          select num.docno,head.dateid,CAST(concat("Counter Receipt: ",num.docno , " (" ,num.center,")") as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype 
          from transnum as num
          left join krhead as head on head.trno = num.trno 
          left join arledger as ledger on ledger.kr = num.trno and num.doc = "KR" 
          where ledger.trno = ' . $trno;
        break;
      case 'SJ':
      case 'MJ':
        switch ($companyid) {
          case 40: //cdo
            $qry = '
            select docno,date_format(dateid,"%m/%d/%Y") as dateid,rem,trno,doc,url,moduletype from (
            select sohead.docno,sohead.dateid, "" as rem,sohead.trno,sohead.doc,"" as url,"module" as moduletype
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join hsostock as qtstock on stock.refx = qtstock.trno and stock.linex = qtstock.line
            left join hsohead as sohead on sohead.trno = qtstock.trno
            where head.trno = ' . $trno . ' and qtstock.ext is not null
            group by sohead.docno,sohead.dateid,sohead.doc,sohead.trno
            union all
            select apledger.docno,head.dateid,CAST(concat("Amount: ",format(sum(apledger.db+apledger.cr),2),"  -  ","BALANCE: ",format(sum(apledger.bal),2)) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype
            from apledger 
            left join cntnum as num on num.trno = apledger.trno 
            left join glhead as head on head.trno = num.trno 
            where num.trno=' . $trno . ' 
            group by apledger.docno,head.dateid,num.trno,num.doc
            union all
            select arledger.docno,head.dateid,CAST(concat("Amount: ",format(sum(arledger.db+arledger.cr),2),"  -  ","BALANCE: ",format(sum(arledger.bal),2)) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype 
            from arledger
            left join cntnum as num on num.trno = arledger.trno 
            left join glhead as head on head.trno = num.trno 
            where num.trno=' . $trno . ' 
            group by arledger.docno,head.dateid,num.trno,num.doc
            union all          
            select head.docno,head.dateid,concat(CAST(concat("Applied Amount: ",format(detail.db+detail.cr,2)) as CHAR)," ",detail.rem) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from lahead as head
            left join ladetail as detail on detail.trno=head.trno 
            left join cntnum as num on num.trno = head.trno 
            where num.recontrno =0 and detail.refx=' . $trno . ' 
            union all
            select head.docno,head.dateid,concat(CAST(concat("Applied Amount: ",format(detail.db+detail.cr,2)) as CHAR)," ",detail.rem) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from glhead as head
            left join gldetail as detail on detail.trno=head.trno 
            left join cntnum as num on num.trno = head.trno 
            where num.recontrno =0 and  detail.refx=' . $trno . ' 
            union all 
            select head.docno,head.dateid,CAST(concat("RECONSTRUCTION:"," Applied Amount: ",format(sum(detail.db+detail.cr),2)) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from glhead as head
            left join gldetail as detail on detail.trno=head.trno 
            left join cntnum num on num.trno = head.trno 
            where num.recontrno=' . $trno . ' and detail.refx <>0 
            group by head.docno,head.dateid,head.trno,head.doc
            union all
            select head.docno,head.dateid,CAST(concat("RECONSTRUCTION:"," Applied Amount: ",format(sum(detail.db+detail.cr),2)) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from lahead as head
            left join ladetail as detail on detail.trno=head.trno 
            left join cntnum as num on num.trno = head.trno 
            where num.recontrno=' . $trno . ' and detail.refx <>0 
            group by head.docno,head.dateid,head.trno,head.doc
            union all
            select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.rrqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from lahead as head
            left join lastock as stock on stock.trno=head.trno 
            left join item on item.itemid=stock.itemid 
            where stock.refx=' . $trno . ' and head.doc = "CM"
            union all
            select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.rrqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from glhead as head
            left join glstock as stock on stock.trno=head.trno 
            left join item on item.itemid = stock.itemid 
            where stock.refx=' . $trno . ' and head.doc = "CM"
            union all
            select num.docno,head.dateid,CAST(concat("Counter Receipt: ",num.docno , " (" ,num.center,")") as CHAR) as rem ,num.trno,num.doc,"" as url,"module" as moduletype 
            from transnum as num
            left join krhead as head on head.trno = num.trno 
            left join arledger as ledger on ledger.kr = num.trno
            and num.doc = "KR" 
            where ledger.trno = ' . $trno . ') as A order by dateid';
            break;
          default:
            $add = '';
            if ($companyid == 48) { //seastar
              $add = 'union all select head.docno,head.dateid,CAST(concat("Qty: ",stock.isqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno 
                      where stock.refx=' . $trno . ' and head.doc = "LL"
                      union all
                      select head.docno,head.dateid,CAST(concat("Qty: ",stock.isqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
                      from glhead as head
                      left join glstock as stock on stock.trno=head.trno 
                      where stock.refx=' . $trno . ' and head.doc = "LL"';
            }

            $qry = '
            select qthead.docno,qthead.dateid,CAST(concat("Total QT Amt: ", sum(qtstock.ext)) as CHAR) as rem,qthead.trno,qthead.doc,"" as url,"module" as moduletype
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join hqsstock as qtstock on stock.refx = qtstock.trno and stock.linex = qtstock.line
            left join hqshead as qthead on qthead.trno = qtstock.trno
            where head.trno = ' . $trno . ' and qtstock.ext is not null
            group by qthead.docno,qthead.dateid,qthead.doc,qthead.trno
            union all
            select sohead.docno,sohead.dateid, "" as rem,sohead.trno,sohead.doc,"" as url,"module" as moduletype
            from glhead as head
            left join glstock as stock on head.trno = stock.trno
            left join hqsstock as qtstock on stock.refx = qtstock.trno and stock.linex = qtstock.line
            left join hqshead as qthead on qthead.trno = qtstock.trno
            left join hsqhead as sohead on sohead.trno = qthead.sotrno
            left join hqsstock as sostock on sostock.trno = sohead.trno
            where head.trno = ' . $trno . ' and qtstock.ext is not null
            group by sohead.docno,sohead.dateid,sohead.doc,sohead.trno
            union all
            select apledger.docno,apledger.dateid,CAST(concat("Amount: ",apledger.db+apledger.cr,"  -  ","BALANCE: ",apledger.bal) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype
            from apledger 
            left join cntnum as num on num.trno = apledger.trno 
            where num.trno=' . $trno . '
            union all
            select arledger.docno,arledger.dateid,CAST(concat("Amount: ",arledger.db+arledger.cr,"  -  ","BALANCE: ",arledger.bal) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype 
            from arledger
            left join cntnum as num on num.trno = arledger.trno 
            where num.trno=' . $trno . '
            union all
            select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from lahead as head
            left join ladetail as detail on detail.trno=head.trno 
            where detail.refx=' . $trno . ' 
            union all
            select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from glhead as head
            left join gldetail as detail on detail.trno=head.trno 
            where detail.refx=' . $trno . '
            union all 
            select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.rrqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from lahead as head
            left join lastock as stock on stock.trno=head.trno 
            left join item on item.itemid=stock.itemid 
            where stock.refx=' . $trno . ' and head.doc = "CM"
            union all
            select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.rrqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype 
            from glhead as head
            left join glstock as stock on stock.trno=head.trno 
            left join item on item.itemid = stock.itemid 
            where stock.refx=' . $trno . ' and head.doc = "CM"
            union all
            select num.docno,head.dateid,CAST(concat("Counter Receipt: ",num.docno , " (" ,num.center,")") as CHAR) as rem ,num.trno,num.doc,"" as url,"module" as moduletype 
            from transnum as num
            left join krhead as head on head.trno = num.trno 
            left join arledger as ledger on ledger.kr = num.trno
            and num.doc = "KR" 
            where ledger.trno = ' . $trno . ' ' . $add;
            break;
        }

        break;
      case 'CS':
      case 'CI':
        $qry = '
          select qthead.docno,qthead.dateid,CAST(concat("Total QT Amt: ", sum(qtstock.ext)) as CHAR) as rem,qthead.trno,qthead.doc,"" as url,"module" as moduletype
          from glhead as head
          left join glstock as stock on head.trno = stock.trno
          left join hqsstock as qtstock on stock.refx = qtstock.trno and stock.linex = qtstock.line
          left join hqshead as qthead on qthead.trno = qtstock.trno
          where head.trno = ' . $trno . ' and qtstock.ext is not null
          group by qthead.docno,qthead.dateid,qthead.doc,qthead.trno
          union all
          select sohead.docno,sohead.dateid, "" as rem,sohead.trno,sohead.doc,"" as url,"module" as moduletype
          from glhead as head
          left join glstock as stock on head.trno = stock.trno
          left join hqsstock as qtstock on stock.refx = qtstock.trno and stock.linex = qtstock.line
          left join hqshead as qthead on qthead.trno = qtstock.trno
          left join hsqhead as sohead on sohead.trno = qthead.sotrno
          left join hqsstock as sostock on sostock.trno = sohead.trno
          where head.trno = ' . $trno . ' and qtstock.ext is not null
          group by sohead.docno,sohead.dateid,sohead.doc,sohead.trno
          union all
          select apledger.docno,apledger.dateid,CAST(concat("Amount: ",apledger.db+apledger.cr,"  -  ","BALANCE: ",apledger.bal) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype
          from apledger left join cntnum as num on num.trno = apledger.trno where num.trno=' . $trno . '
          union all
          select arledger.docno,arledger.dateid,CAST(concat("Amount: ",arledger.db+arledger.cr,"  -  ","BALANCE: ",arledger.bal) as CHAR) as rem,num.trno,num.doc,"" as url,"module" as moduletype from arledger
          left join cntnum as num on num.trno = arledger.trno where num.trno=' . $trno . ' 
          union all
          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype from lahead as head
          left join ladetail as detail on detail.trno=head.trno where detail.refx=' . $trno . ' 
          union all
          select head.docno,head.dateid,CAST(concat("Applied Amount: ",detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype from glhead as head
          left join gldetail as detail on detail.trno=head.trno where detail.refx=' . $trno . ' 
          union all 
          select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.rrqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype from lahead as head
          left join lastock as stock on stock.trno=head.trno 
          left join item on item.itemid=stock.itemid where stock.refx=' . $trno . ' and head.doc = "CM"
          union all
          select head.docno,head.dateid,CAST(concat("Return Item: ",item.barcode,"-",item.itemname," Qty: ",stock.rrqty) as CHAR) as rem,head.trno,head.doc,"" as url,"module" as moduletype from glhead as head
          left join glstock as stock on stock.trno=head.trno 
          left join item on item.itemid = stock.itemid where stock.refx=' . $trno . ' and head.doc = "CM"
          union all
          select num.docno,head.dateid,CAST(concat("Counter Receipt: ",num.docno , " (" ,num.center,")") as CHAR) as rem ,num.trno,num.doc,"" as url,"module" as moduletype from transnum as num
          left join krhead as head on head.trno = num.trno left join arledger as ledger on ledger.kr = num.trno
          and num.doc = "KR" where ledger.trno = ' . $trno;
        break;
      case 'PO':
      case 'SO':
      case 'TR':
      case 'CN':
        $field = 'round(stock.isqty,' . $this->companysetup->getdecimal('currency', $config['params']) . ')';
        $added_union = '';
        switch ($doc) {
          case 'PO':
            $field = 'round(stock.rrqty,' . $this->companysetup->getdecimal('currency', $config['params']) . ')';
            switch ($config['params']['companyid']) {
              case 6: // mitsukoshi
                $added_union = " 
                union all
                select head.docno,left(head.dateid,10) as dateid,
                CAST(concat('Total PL Amt: ',round(sum(s.ext),2)) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
                from hplhead as head 
                left join hplstock as s on s.trno = head.trno
                left join hpostock as postock on postock.trno = s.refx and postock.line = s.linex
                left join hpohead as pohead on pohead.trno = postock.trno
                where pohead.trno = '" . $trno . "'
                group by head.docno,head.dateid,head.doc,head.trno
                union all
                select head.docno,left(head.dateid,10) as dateid,
                CAST(concat('Total PL Amt: ',round(sum(s.ext),2)) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
                from plhead as head 
                left join plstock as s on s.trno = head.trno
                left join hpostock as postock on postock.trno = s.refx and postock.line = s.linex
                left join hpohead as pohead on pohead.trno = postock.trno
                where pohead.trno = '" . $trno . "'
                group by head.docno,head.dateid,head.trno,head.doc";
                break;

              case 16: //ati
                $added_union = "
                union all
                select head.docno,left(head.dateid,10) as dateid, CAST(concat('CV Amt: ',round(detail.db,2)) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
                from cvitems as cvi left join lahead as head on head.trno=cvi.trno left join ladetail as detail on detail.trno=head.trno where head.doc='CV' and cvi.refx=" . $trno . "
                group by head.docno,head.dateid, detail.db,head.trno,head.doc
                union all
                select head.docno,left(head.dateid,10) as dateid, CAST(concat('CV Amt: ',round(detail.db,2)) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
                from hcvitems as cvi left join glhead as head on head.trno=cvi.trno left join gldetail as detail on detail.trno=head.trno where head.doc='CV' and cvi.refx=" . $trno . "
                group by head.docno,head.dateid, detail.db,head.trno,head.doc";
                break;

              case 10: //afti
              case 12: //afti usd
                $added_union = "union all 
                  select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.isqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
                  from hqshead as qs
                  left join hqsstock as stock on stock.trno=qs.trno
                  left join hsqhead as head on head.trno=qs.sotrno
                  left join hpostock as pos on pos.sorefx = stock.trno and pos.solinex = stock.line left join item on item.itemid = stock.itemid
                  where pos.trno=" . $trno;
                break;
            }
            break;

          case 'SO':
            switch ($config['params']['companyid']) {
              case 39: //cbbsi
                $returnfield = 'round(stock.rrqty,' . $this->companysetup->getdecimal('currency', $config['params']) . ')';
                $field = 'round(stock.isqty,' . $this->companysetup->getdecimal('currency', $config['params']) . ')';
                $added_union = "union all
                select head.docno,date(head.dateid) as dateid,CAST(concat('Return: ',item.barcode,' - ',item.itemname,' - '," . $returnfield . ") as CHAR) as rem,
                head.trno,head.doc,'' as url,'module' as moduletype 
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno 
                left join item on item.itemid=stock.itemid 
                where head.doc not in ('DM','CM','CV') and 
                stock.refx in (select trno from glstock where refx=" . $trno . ")
                union all
                select head.docno,date(head.dateid) as dateid,CAST(concat('Return: ',item.barcode,' - ',item.itemname,' - '," . $returnfield . ") as CHAR) as rem,
                head.trno,head.doc,'' as url,'module' as moduletype 
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno 
                left join item on item.itemid = stock.itemid 
                where head.doc not in ('DM','CM','CV') and 
                stock.refx in (select trno from glstock where refx=" . $trno . ")
                union all

                select d.docno,date(d.dateid) as dateid,d.rem,d.trno,d.doc,d.url,d.moduletype from(
                  select dp.docno,dp.dateid,'Dispatch' as rem,
                  dp.trno,dp.doc,'' as url,'module' as moduletype 
                  from lahead as head 
                  left join lastock as stock on stock.trno=head.trno 
                  left join item on item.itemid=stock.itemid 
                  left join cntnum as num on num.trno=head.trno
                  left join dphead as dp on dp.trno=num.dptrno
                  where head.doc not in ('DM','CM','CV') and 
                  stock.refx=" . $trno . " and dp.trno is not null
                  union all
                  select dp.docno,dp.dateid,'Dispatch' as rem,
                  dp.trno,dp.doc,'' as url,'module' as moduletype 
                  from glhead as head 
                  left join glstock as stock on stock.trno=head.trno 
                  left join item on item.itemid = stock.itemid 
                  left join cntnum as num on num.trno=head.trno
                  left join hdphead as dp on dp.trno=num.dptrno
                  where head.doc not in ('DM','CM','CV') and stock.refx=" . $trno . " and dp.trno is not null
                  union all
                  select head.docno,date(head.dateid) as dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,
                  head.trno,head.doc,'' as url,'module' as moduletype
                  from lahead as head
                  left join transnum as num on num.sitagging = head.trno
                  left join hsostock as stock on stock.trno = num.trno
                  left join item on item.itemid=stock.itemid
                  left join hsohead as sohead on sohead.trno = stock.trno
                  where stock.trno = " . $trno . " and  num.sitagging <> 0 group by head.docno,head.dateid,stock.isqty,item.itemname,item.barcode,head.trno,head.doc
                   union all
                  select head.docno,date(head.dateid) as dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,
                  head.trno,head.doc,'' as url,'module' as moduletype
                  from glhead as head
                  left join transnum as num on num.sitagging = head.trno
                  left join hsostock as stock on stock.trno = num.trno
                  left join item on item.itemid=stock.itemid
                  left join hsohead as sohead on sohead.trno = stock.trno
                  where stock.trno = " . $trno . " and  num.sitagging <> 0 group by head.docno,head.dateid,stock.isqty,item.itemname,item.barcode,head.trno,head.doc
                ) as d 
                group by d.docno,d.dateid,d.rem,d.trno,d.doc,d.url,d.moduletype
                ";
                break;
              case 19: //housegem
                $field = 'round(stock.isqty,' . $this->companysetup->getdecimal('currency', $config['params']) . ')';
                $added_union = "union all
                select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from rohead as head left join rostock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
                union all
                select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from hrohead as head left join hrostock as stock on stock.trno=head.trno left join item on item.itemid = stock.itemid where stock.refx=" . $trno . "";
                break;
            }
            break;
        }

        $qry = "
            select head.docno,date(head.dateid) as dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head left join lastock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc not in ('DM','CM','CV') and stock.refx=" . $trno . "
            union all
            select head.docno,date(head.dateid) as dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid = stock.itemid where head.doc not in ('DM','CM','CV') and stock.refx=" . $trno . "
            " . $added_union . "";
        break;

      case 'JB':
        $field = 'round(stock.rrqty,' . $this->companysetup->getdecimal('currency', $config['params']) . ')';
        $qry = "select distinct head.docno,head.dateid, '' as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype
        from hsshead as head left join hsrhead as sr on sr.sotrno = head.trno
        left join hjostock as jo on jo.refx=sr.trno
        where jo.trno = " . $trno . "
        union all
        select distinct sr.docno,sr.dateid, sr.rem as rem,sr.trno,sr.doc,'/module/sales/' as url,'module' as moduletype
        from  hsrhead as sr
        left join hjostock as jo on jo.refx=sr.trno
        where jo.trno = " . $trno . "
        union all
        select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype  from lahead as head left join lastock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc = 'AC' and stock.refx=" . $trno . "
        union all
        select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - '," . $field . ") as CHAR) as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype  from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid = stock.itemid where head.doc = 'AC' and stock.refx=" . $trno;
        break;

      case 'SA':
      case 'SB':
      case 'SC':
      case 'SG':
      case 'WA':
        $qty = "stock.isqty";
        if ($doc == 'WA') {
          $qty = "stock.rrqty";
        }
        $qry = "select head.trno,head.docno,head.dateid,
          CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(" . $qty . "," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem, cntnum.status, head.waybill,
          head.trno,head.doc,'' as url,'module' as moduletype
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno 
          left join item on item.itemid=stock.itemid left 
          join cntnum on cntnum.trno=head.trno 
          where head.doc not in ('DM','CM','CV') and stock.refx=" . $trno . "
          union all
          select head.trno,head.docno,head.dateid,
          CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(" . $qty . "," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem, cntnum.status , head.waybill,
          head.trno,head.doc,'' as url,'module' as moduletype
          from glhead as head 
          left join glstock as stock on stock.trno=head.trno 
          left join item on item.itemid = stock.itemid 
          left join cntnum on cntnum.trno=head.trno 
          where head.doc not in ('DM','CM','CV') and stock.refx='" . $trno . "'
          union all
          select head.trno,head.docno,head.dateid,
          CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem, '' as status,'' as waybill,
          head.trno,head.doc,'' as url,'module' as moduletype
          from wahead as head 
          left join wastock as stock on stock.trno=head.trno 
          left join item on item.itemid = stock.itemid 
          left join transnum on transnum.trno=head.trno 
          where stock.refx='" . $trno . "'
          union all
          select head.trno,head.docno,head.dateid,
          CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem, '' as status,'' as waybill,
          head.trno,head.doc,'' as url,'module' as moduletype
          from hwahead as head 
          left join hwastock as stock on stock.trno=head.trno 
          left join item on item.itemid = stock.itemid 
          left join transnum on transnum.trno=head.trno 
          where stock.refx='" . $trno . "'
          union all
          select head.trno,wbhead.docno,wbhead.dateid,
          CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem, '' as status,'' as waybill,
          head.trno,head.doc,'' as url,'module' as moduletype
          from hwahead as head 
          left join hwastock as stock on stock.trno=head.trno 
          left join lastock as wbstock on wbstock.refx = stock.trno and wbstock.linex = stock.line
          left join lahead as wbhead on wbhead.trno = wbstock.trno
          left join item on item.itemid = wbstock.itemid 
          left join transnum on transnum.trno=head.trno 
          where stock.refx='" . $trno . "'
          union all
          select head.trno,sghead.docno,sghead.dateid,
          CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem, '' as status,'' as waybill,
          head.trno,head.doc,'' as url,'module' as moduletype
          from hwahead as head 
          left join hwastock as stock on stock.trno=head.trno 
          left join hsgstock as sgstock on sgstock.trno = stock.refx and sgstock.line = stock.linex
          left join hsghead as sghead on sghead.trno = sgstock.trno
          left join item on item.itemid = sgstock.itemid 
          left join transnum on transnum.trno=head.trno 
          where head.trno='" . $trno . "'
        ";
        break;
      case 'MR':
        $qry = "select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.isqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head left join lastock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
            union all
            select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.isqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid = stock.itemid where stock.refx=" . $trno;
        break;
      case 'JO':
        $qry = "select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from jchead as head left join jcstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
            union all
            select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from hjchead as head left join hjcstock as stock on stock.trno=head.trno left join item on item.itemid = stock.itemid where stock.refx=" . $trno;
        break;
      case 'CD':
        $qry = "select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from pohead as head left join postock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.cdrefx=" . $trno . "
            union all
            select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from hpohead as head left join hpostock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.cdrefx=" . $trno;
        break;
      case 'PR':
      case 'RQ':
        $qry = "select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from pohead as head left join postock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
            union all
            select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from hpohead as head left join hpostock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
            union all
            select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from johead as head left join jostock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.prrefx=" . $trno . "
            union all
            select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from hjohead as head left join hjostock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.prrefx=" . $trno;

        if ($doc == 'PR' && $config['params']['companyid'] == 16) { //ati
          $qry .= "
          union all
          select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from cdhead as head left join cdstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
          union all
          select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from hcdhead as head left join hcdstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
          union all
          select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head left join lastock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc='SS' and stock.refx=" . $trno . "
          union all
          select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc='SS' and stock.refx=" . $trno . "";
        }

        break;
      case 'PE':
        $qry = "
        select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head left join lastock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc='PN' and head.prdtrno=" . $trno . "
        union all
        select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc='PN' and head.prdtrno=" . $trno . "
        union all
        select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.isqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head left join lastock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc = 'MI' and stock.refx=" . $trno . "
        union all
        select head.docno,head.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.isqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where head.doc = 'MI' and stock.refx=" . $trno . "";
        break;
      case 'MI':
        if ($companyid == 8) { //maxipro
          $qry = "select pr.docno,pr.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',
                        round(prs.rrqty,2)) as rem,pr.trno,pr.doc,'' as url,'module' as moduletype
                  from lahead as head
                  left join lastock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join glhead as pr on pr.trno = stock.refx
                  left join glstock as prs on prs.trno = pr.trno and prs.line = stock.linex
                  where head.doc='MI' and head.trno= " . $trno . " and pr.docno is not null
                  union all
                  select pr.docno,pr.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',
                        round(prs.rrqty,2)) as rem,pr.trno,pr.doc,'' as url,'module' as moduletype
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join glhead as pr on pr.trno = stock.refx
                  left join glstock as prs on prs.trno = pr.trno and prs.line = stock.linex
                  where head.doc='MI' and head.trno= " . $trno . " and pr.docno is not null
                  union all
                  select hhead.docno,hhead.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',
                        round(det.cr,2)) as rem,hhead.trno,hhead.doc,'' as url,'module' as moduletype
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join gldetail as det on det.refx = stock.trno and det.linex = stock.line
                  left join glhead as hhead on hhead.trno=det.trno
                  where head.doc='MI' and head.trno= " . $trno . " and hhead.docno is not null
                  union all
                  select hhead.docno,hhead.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',
                        round(det.cr,2)) as rem,hhead.trno,hhead.doc,'' as url,'module' as moduletype
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join ladetail as det on det.refx = stock.trno and det.linex = stock.line
                  left join lahead as hhead on hhead.trno=det.trno
                  where head.doc='MI' and head.trno= " . $trno . " and hhead.docno is not null";
        } else {
          $qry = "select pr.docno,pr.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',
                         round(prs.rrqty,2)) as rem,pr.trno,pr.doc,'' as url,'module' as moduletype 
                  from lahead as head
                  left join lastock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join prhead as pr on pr.trno = stock.refx 
                  left join prstock as prs on prs.trno = pr.trno and prs.line = stock.linex
                  where head.doc='MI' and head.trno= " . $trno . "
                  union all
                  select pr.docno,pr.dateid,concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',
                         round(prs.rrqty,2)) as rem,pr.trno,pr.doc,'' as url,'module' as moduletype 
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join hprhead as pr on pr.trno = stock.refx 
                  left join hprstock as prs on prs.trno = pr.trno and prs.line = stock.linex
                  where head.doc='MI' and head.trno= " . $trno . "";
        }

        break;
      case 'JR':
        $qry = "select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from johead as head left join jostock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where stock.refx=" . $trno . "
            union all
            select head.docno,head.dateid,CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.rrqty," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from hjohead as head left join hjostock as stock on stock.trno=head.trno left join item on item.itemid = stock.itemid where stock.refx=" . $trno;
        break;

      case 'CR':
        $qry = "select  arhead.docno, date(arhead.dateid) as dateid,
          CAST(concat('Applied Amount: ', round(ardetail.db+ardetail.cr,2)) as CHAR) as rem,arhead.trno,arhead.doc,'' as url,'module' as moduletype
          from glhead as arhead
          left join gldetail as ardetail on arhead.trno = ardetail.trno
          left join gldetail as crdetail on crdetail.refx = ardetail.trno and crdetail.linex = ardetail.line
          left join glhead as crhead on crhead.trno = crdetail.trno
          where crhead.trno = '" . $trno . "'
          union all
          select head.docno,head.dateid,CAST(concat('DEPOSIT REFERENCE: ',head.docno,' / ',detail.checkno) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head
          left join ladetail as detail on detail.trno = head.trno
          where detail.refx = " . $trno . "
          union all
          select head.docno,head.dateid,CAST(concat('DEPOSIT REFERENCE: ',head.docno,' / ',detail.checkno) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          where detail.refx = " . $trno . "";
        break;

      case 'PV':
      case 'AP':
      case 'AR':
      case 'GJ':
      case 'GD':
      case 'GC':
      case 'MB':
        $qry = 'select apledger.docno,apledger.dateid,concat("Amount: ",apledger.db+apledger.cr,"  -  ","BALANCE: ",apledger.bal) as rem,num.trno,num.doc,"" as url,"module" as moduletype
            from apledger left join cntnum as num on num.trno = apledger.trno where num.trno=' . $trno . '
            union all
            select arledger.docno,arledger.dateid,concat("Amount: ",arledger.db+arledger.cr,"  -  ","BALANCE: ",arledger.bal) as rem,num.trno,num.doc,"" as url,"module" as moduletype 
            from arledger left join cntnum as num on num.trno = arledger.trno where num.trno=' . $trno . '
            union all
            select head.docno,head.dateid,concat("Applied Amount: ",detail.db+detail.cr) as rem,head.trno,head.doc,"" as url,"module" as moduletype from lahead as head
            left join ladetail as detail on detail.trno=head.trno where detail.refx=' . $trno . '
            union all
            select head.docno,head.dateid,concat("Applied Amount: ",detail.db+detail.cr) as rem,head.trno,head.doc,"" as url,"module" as moduletype from glhead as head
            left join gldetail as detail on detail.trno=head.trno where detail.refx=' . $trno;
        break;

      case 'PL':
        $qry = "
          select stock.line,stock.trno,stock.itemid,item.barcode,item.itemname,stock.refx,stock.linex,stock.uom,
          round(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
          round(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty, stock.ref,head.trno,head.doc,'' as url,'module' as moduletype
          from hplstock as stock left join hplhead as head on head.trno = stock.trno
          left join item on item.itemid=stock.itemid 
          where stock.trno = '" . $trno . "' 
          union all 
          select rpstock.line,rpstock.trno,rpstock.itemid,item.barcode,item.itemname,rpstock.refx,rpstock.linex,rpstock.uom,
          round(rpstock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
          round(rpstock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty, rphead.docno,rphead.trno,rphead.doc,'' as url,'module' as moduletype
          from hplstock as stock 
          left join lastock as rpstock on rpstock.refx = stock.trno and rpstock.linex = stock.line
          left join lahead as rphead on rphead.trno = rpstock.trno
          left join item on item.itemid=rpstock.itemid 
          where rpstock.refx = '" . $trno . "' 
          union all 
          select rpstock.line,rpstock.trno,rpstock.itemid,item.barcode,item.itemname,rpstock.refx,rpstock.linex,rpstock.uom,
          round(rpstock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
          round(rpstock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty, rphead.docno,rphead.trno,rphead.doc,'' as url,'module' as moduletype
          from hplstock as stock 
          left join glstock as rpstock on rpstock.refx = stock.trno and rpstock.linex = stock.line
          left join glhead as rphead on rphead.trno = rpstock.trno
          left join item on item.itemid=rpstock.itemid 
          where rpstock.refx = '" . $trno . "' 
        ";

        break;
      case 'BR':
        $qry = "select head.docno,head.dateid,head.rem,concat('Amount: ',format(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem from blhead as head left join blstock as stock on stock.trno = head.trno where head.brtrno=" . $trno . " group by head.docno,head.dateid,head.rem
            union all
            select head.docno,head.dateid,head.rem,concat('Amount: ',format(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem from hblhead as head left join hblstock as stock on stock.trno = head.trno where head.brtrno=" . $trno . " group by head.docno,head.dateid,head.rem
            union all 
            select head.docno,head.dateid,head.rem,concat('Amount: ',format(sum(stock.db)," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem from lahead as head left join ladetail as stock on stock.trno = head.trno where head.brtrno=" . $trno . " group by head.docno,head.dateid,head.rem
            union all
            select head.docno,head.dateid,head.rem,concat('Amount: ',format(sum(stock.db)," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem from glhead as head left join gldetail as stock on stock.trno = head.trno where head.brtrno=" . $trno . " group by head.docno,head.dateid,head.rem";
        break;
      case 'BL':
        $qry = "select head.docno,head.dateid,head.rem,concat('Amount: ',format(sum(stock.amount)," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as rem 
        from hbrhead as head 
        left join hbrstock as stock on stock.trno = head.trno 
        where head.bltrno=" . $trno . " 
        group by head.docno,head.dateid,head.rem";
        break;
      case 'OP':
        $qry_qt = "select head.trno as value
        from hqshead as head
        left join hqsstock as stock on head.trno = stock.trno
        where refx = ?
        group by head.trno
        union all 
        select head.trno as value
        from qshead as head
        left join qsstock as stock on head.trno = stock.trno
        where refx = ?
        group by head.trno
        union all
        select head.trno as value
        from hqshead as head
        left join hqtstock as stock on head.trno = stock.trno
        where refx = ?
        group by head.trno
        union all 
        select head.trno as value
        from qshead as head
        left join qtstock as stock on head.trno = stock.trno
        where refx = ?
        group by head.trno
        limit 1";
        $qt_trno = $this->coreFunctions->datareader($qry_qt, [$trno, $trno, $trno, $trno]);

        $qry = "
        select qthead.docno,qthead.dateid,CAST(concat('Total QT Amt: ', sum(qtstock.ext)) as CHAR) as rem ,qthead.trno,qthead.doc,'' as url,'module' as moduletype
        from hophead as sahead
        left join hopstock as sastock on sastock.trno = sahead.trno 
        left join qsstock as qtstock on qtstock.refx = sahead.trno and qtstock.linex = sastock.line
        left join qshead as qthead on qthead.trno = qtstock.trno 
        where sahead.trno = '" . $trno . "' and qtstock.ext is not null
        group by qthead.docno,qthead.dateid,qthead.doc,qthead.trno
        union all 
        select qthead.docno,qthead.dateid,CAST(concat('Total QT Amt: ', sum(qtstock.ext)) as CHAR) as rem ,qthead.trno,qthead.doc,'' as url,'module' as moduletype
        from hophead as sahead
        left join hopstock as sastock on sastock.trno = sahead.trno 
        left join hqsstock as qtstock on qtstock.refx = sahead.trno and qtstock.linex = sastock.line
        left join hqshead as qthead on qthead.trno = qtstock.trno 
        where sahead.trno = '" . $trno . "' and qtstock.ext is not null
        group by qthead.docno,qthead.dateid,qthead.doc,qthead.trno
        union all 
        select qthead.docno,qthead.dateid,CAST(concat('Total QT Amt: ', sum(qtstock.ext)) as CHAR) as rem ,qthead.trno,qthead.doc,'' as url,'module' as moduletype
        from hophead as sahead
        left join hopstock as sastock on sastock.trno = sahead.trno 
        left join qtstock as qtstock on qtstock.refx = sahead.trno and qtstock.linex = sastock.line
        left join qshead as qthead on qthead.trno = qtstock.trno 
        where sahead.trno = '" . $trno . "' and qtstock.ext is not null
        group by qthead.docno,qthead.dateid,qthead.doc,qthead.trno
        union all 
        select qthead.docno,qthead.dateid,CAST(concat('Total QT Amt: ', sum(qtstock.ext)) as CHAR) as rem ,qthead.trno,qthead.doc,'' as url,'module' as moduletype
        from hophead as sahead
        left join hopstock as sastock on sastock.trno = sahead.trno 
        left join hqtstock as qtstock on qtstock.refx = sahead.trno and qtstock.linex = sastock.line
        left join hqshead as qthead on qthead.trno = qtstock.trno 
        where sahead.trno = '" . $trno . "' and qtstock.ext is not null
        group by qthead.docno,qthead.dateid,qthead.doc,qthead.trno
        union all 
        select sqhead.docno,sqhead.dateid,CAST(concat('Total SO Amt: ', sum(qtstock.ext)) as CHAR) as rem,sqhead.trno,sqhead.doc,'' as url,'module' as moduletype
        from hophead as sahead
        left join hopstock as sastock on sastock.trno = sahead.trno
        left join hqsstock as qtstock on qtstock.refx = sahead.trno and qtstock.linex = sastock.line
        left join hqshead as qthead on qthead.trno = qtstock.trno
        left join sqhead as sqhead on sqhead.trno = qthead.sotrno
        where sahead.trno = '" . $trno . "' and qtstock.ext is not null and sqhead.docno is not null
        group by sqhead.docno,sqhead.dateid,sqhead.doc,sqhead.trno
        union all 
        select sqhead.docno,sqhead.dateid,CAST(concat('Total SO Amt: ', sum(qtstock.ext)) as CHAR) as rem,sqhead.trno,sqhead.doc,'' as url,'module' as moduletype
        from hophead as sahead
        left join hopstock as sastock on sastock.trno = sahead.trno
        left join hqsstock as qtstock on qtstock.refx = sahead.trno and qtstock.linex = sastock.line
        left join hqshead as qthead on qthead.trno = qtstock.trno
        left join hsqhead as sqhead on sqhead.trno = qthead.sotrno
        where sahead.trno = '" . $trno . "' and qtstock.ext is not null and sqhead.docno is not null
        group by sqhead.docno,sqhead.dateid,sqhead.trno,sqhead.doc
        union all 
        select head.docno,head.dateid,CAST(concat('Total SJ Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        where stock.refx = '" . $qt_trno . "' and head.doc = 'SJ' and stock.ext is not null and head.docno is not null
        group by head.docno,head.dateid,head.trno,head.doc
        union all 
        select head.docno,head.dateid,CAST(concat('Total SJ Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        where stock.refx = '" . $qt_trno . "' and head.doc = 'SJ' and stock.ext is not null and head.docno is not null
        group by head.docno,head.dateid,head.trno,head.doc
        
        ";
        break;
      case 'QS':
        $qry = "
          select ophead.docno, date(ophead.dateid) as dateid, CAST(concat('Total OP Amt: ', sum(opstock.ext)) as CHAR) as rem,qthead.trno,qthead.doc,'/module/sales/' as url,'module' as moduletype
          from hqshead as qthead 
          left join hqsstock as qtstock on qtstock.trno=qthead.trno 
          left join hopstock as opstock on opstock.trno = qtstock.refx and opstock.line = qtstock.linex
          left join hophead as ophead on ophead.trno = opstock.trno
          where qthead.trno='" . $trno . "' and ophead.docno is not null
          group by ophead.docno,ophead.dateid,qthead.trno,qthead.doc
          union all
          select so.docno, date(so.dateid) as dateid, qt.rem,so.trno,so.doc,'/module/sales/' as url,'module' as moduletype from sqhead as so left join hqshead as qt on qt.sotrno=so.trno where qt.trno='" . $trno . "'
          union all
          select so.docno, date(so.dateid) as dateid, qt.rem,so.trno,so.doc,'/module/sales/' as url,'module' as moduletype from hsqhead as so left join hqshead as qt on qt.sotrno=so.trno where qt.trno='" . $trno . "'
          union all
          select so.docno, date(so.dateid) as dateid, qt.rem,so.trno,so.doc,'/module/purchase/' as url,'module' as moduletype from srhead as so left join hqshead as qt on qt.trno=so.qtrno where qt.trno='" . $trno . "'
          union all
          select so.docno, date(so.dateid) as dateid, qt.rem,so.trno,so.doc,'/module/purchase/' as url,'module' as moduletype from hsrhead as so left join hqshead as qt on qt.trno=so.qtrno where qt.trno='" . $trno . "' 
          union all
          select head.docno, date(head.dateid) as dateid, CAST(concat('Total SJ Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype 
          from lahead as head 
          left join lastock as stock on head.trno=stock.trno 
          where stock.refx='" . $trno . "'
          group by head.docno,head.dateid,head.trno,head.doc
          union all
          select head.docno, date(head.dateid) as dateid, CAST(concat('Total SJ Amt: ', sum(stock.ext)) as CHAR) as rem ,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype 
          from glhead as head 
          left join glstock as stock on head.trno=stock.trno 
          where stock.refx='" . $trno . "'
          group by head.docno,head.dateid,head.trno,head.doc
          union all
          select head.docno, date(head.dateid) as dateid, CAST(concat('Total BS Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype 
          from lahead as head 
          left join lastock as stock on head.trno=stock.trno
          left join hsrhead as sr on sr.trno = stock.refx
          where sr.qtrno = '" . $trno . "'
          group by head.docno,head.dateid,head.trno,head.doc
          union all
          select head.docno, date(head.dateid) as dateid, CAST(concat('Total BS Amt: ', sum(stock.ext)) as CHAR) as rem ,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype 
          from glhead as head
          left join glstock as stock on head.trno=stock.trno
           left join hsrhead as sr on sr.trno = stock.refx
          where sr.qtrno ='" . $trno . "'
          group by head.docno,head.dateid,head.trno,head.doc
        ";
        break;
      case 'SQ':
        $qt_trno = $this->coreFunctions->getfieldvalue('hqshead', "trno", "sotrno=?", [$trno]);
        $qry = "
          select ophead.docno,ophead.dateid,CAST(concat('Total OP Amt: ', sum(opstock.ext)) as CHAR) as rem,qthead.trno,qthead.doc,'/module/sales/' as url,'module' as moduletype
          from hqshead as qthead
          left join hqsstock as qtstock on qtstock.trno = qthead.trno
          left join hopstock as opstock on opstock.trno = qtstock.refx and opstock.line = qtstock.linex
          left join hophead as ophead on ophead.trno = opstock.trno
          where qthead.sotrno = '" . $trno . "' and qtstock.ext is not null and ophead.docno is not null
          group by ophead.docno,ophead.dateid,qthead.doc,qthead.trno
          union all
          select qthead.docno,qthead.dateid,CAST(concat('Total QT Amt: ', sum(qtstock.ext)) as CHAR) as rem,qthead.trno,qthead.doc,'/module/sales/' as url,'module' as moduletype
          from hqshead as qthead
          left join hqsstock as qtstock on qtstock.trno = qthead.trno
          where qthead.sotrno = '" . $trno . "' and qtstock.ext is not null
          group by qthead.docno,qthead.dateid,qthead.doc,qthead.trno
          union all
          select head.docno,head.dateid,'' as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype
          from pohead as head
          left join postock as stock on stock.trno = head.trno
          where head.sotrno = '" . $trno . "'
          group by head.docno,head.dateid,head.doc,head.trno
          union all
          select head.docno,head.dateid,'' as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype
          from hpohead as head
          left join hpostock as stock on stock.trno = head.trno
          where head.sotrno = '" . $trno . "'
          group by head.docno,head.dateid,head.doc,head.trno
          union all
          select head.docno,head.dateid,CAST(concat('Total SJ Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno ,head.doc,'/module/sales/' as url,'module' as moduletype
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          where stock.refx = '" . $qt_trno . "' and head.doc='SJ'
          group by head.docno,head.dateid,head.doc,head.trno
          union all 
          select head.docno,head.dateid,CAST(concat('Total SJ Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno ,head.doc,'/module/sales/' as url,'module' as moduletype
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          where stock.refx = '" . $qt_trno . "' and head.doc='SJ'
          group by head.docno,head.dateid,head.doc,head.trno
        ";
        break;
      case 'AO':
        $sr_trno = $this->coreFunctions->getfieldvalue('hsrhead', "trno", "sotrno=?", [$trno]);
        $qry = "
          select head.docno,head.dateid, head.rem as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype
          from hsrhead as head
          left join hsrstock as stock on stock.trno = head.trno
          where head.sotrno = '" . $trno . "'
          group by head.docno,head.dateid, head.rem,head.trno,head.doc
          union all
          select head.docno,head.dateid,'' as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype
          from johead as head
          left join jostock as stock on stock.trno = head.trno
          left join hsrstock as srs on srs.trno = stock.refx and srs.line = stock.linex
          left join hsrhead as srh on srh.trno = srs.trno
          where srh.sotrno = '" . $trno . "'
          group by head.docno,head.dateid,head.doc,head.trno
          union all
          select head.docno,head.dateid,'' as rem,head.trno,head.doc,'/module/purchase/' as url,'module' as moduletype
          from hjohead as head
          left join hjostock as stock on stock.trno = head.trno
          left join hsrstock as srs on srs.trno = stock.refx and srs.line = stock.linex
          left join hsrhead as srh on srh.trno = srs.trno
          where srh.sotrno = '" . $trno . "'
          group by head.docno,head.dateid,head.doc,head.trno
          union all
          select head.docno,head.dateid,CAST(concat('Total SI Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          where stock.refx = '" . $sr_trno . "'
          group by head.docno,head.dateid,head.trno,head.doc
          union all
          select head.docno,head.dateid,CAST(concat('Total SI Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'/module/sales/' as url,'module' as moduletype
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          where stock.refx = '" . $sr_trno . "'
          group by head.docno,head.dateid,head.trno,head.doc
        ";
        break;
      case 'AI':
        $sr_trno = $this->coreFunctions->getfieldvalue('hsrhead', "trno", "sotrno=?", [$trno]);
        $qry = "select head.docno,head.dateid,CAST(concat('Total SO Amt: ', sum(stock.ext)) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype
          from hsshead as head
          left join hsrhead as hsrhead on hsrhead.sotrno = head.trno
          left join hsrstock as stock on stock.trno = hsrhead.trno
          left join glstock as glstock on glstock.refx = stock.trno and glstock.linex = stock.line
          where glstock.trno = '" . $trno . "'
          group by head.docno,head.dateid,head.trno,head.doc
          union all
          select hsrhead.docno,hsrhead.dateid,hsrhead.rem as rem,hsrhead.trno,hsrhead.doc,'' as url,'module' as moduletype
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join hsrstock as hsrstock on hsrstock.trno = stock.refx and hsrstock.line = stock.linex
          left join hsrhead as hsrhead on hsrhead.trno = hsrstock.trno
          where head.trno = '" . $trno . "'
          group by hsrhead.docno,hsrhead.dateid, hsrhead.rem,hsrhead.doc,hsrhead.trno
          union all
          select arledger.docno,arledger.dateid,CAST(concat('Amount: ',arledger.db+arledger.cr,'  -  ','BALANCE: ',arledger.bal) as CHAR) as rem,num.trno,num.doc,'' as url,'module' as moduletype 
          from arledger left join cntnum as num on num.trno = arledger.trno
          where num.trno='" . $trno . "'
          union all
          select head.docno,head.dateid,CAST(concat('Applied Amount: ',detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head
          left join ladetail as detail on detail.trno=head.trno where detail.refx='" . $trno . "'
          union all
          select head.docno,head.dateid,CAST(concat('Applied Amount: ',detail.db+detail.cr) as CHAR) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head
          left join gldetail as detail on detail.trno=head.trno where detail.refx='" . $trno . "'
        ";
        break;

      case 'DS':
        $qry = "select ghead.docno,ghead.dateid,concat('Applied Amount: ', sum(gdetail.db - gdetail.cr)) as rem,ghead.trno,ghead.doc,'' as url,'module' as moduletype
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join gldetail as gdetail on gdetail.trno = detail.refx
          left join glhead as ghead on ghead.trno = gdetail.trno
          where head.trno = '" . $trno . "' and gdetail.refx = 0 and ghead.docno is not null and ghead.doc not in ('SJ', 'CM', 'RR', 'DM', 'AJ', 'IS', 'TS', 'SD', 'SE', 'SF', 'SH')
          group by ghead.docno,ghead.dateid,ghead.trno,ghead.doc
          union all
          select ghead.docno,ghead.dateid,concat('Applied Amount: ', sum(gstock.ext)) as rem,ghead.trno,ghead.doc,'' as url,'module' as moduletype
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join glstock as gstock on gstock.trno = detail.refx
          left join glhead as ghead on ghead.trno = gstock.trno
          where head.trno = '" . $trno . "' and ghead.docno is not null and ghead.doc in ('SJ', 'CM', 'RR', 'DM', 'AJ', 'IS', 'TS', 'SD', 'SE', 'SF', 'SH')
          group by ghead.docno,ghead.dateid,ghead.doc,ghead.trno
        ";
        break;
      case 'PQ':
        $qry = 'select head.docno,head.dateid,concat("Applied Amount: ",detail.db+detail.cr) as rem,head.trno,head.doc,"" as url,"module" as moduletype from svhead as head
            left join svdetail as detail on detail.trno=head.trno where detail.refx=' . $trno . '
            union all
            select head.docno,head.dateid,concat("Applied Amount: ",detail.db+detail.cr) as rem,head.trno,head.doc,"" as url,"module" as moduletype from hsvhead as head
            left join hsvdetail as detail on detail.trno=head.trno where detail.refx=' . $trno . ' ';
        break;
      case 'SV':
        $qry = "
          select ghead.docno,ghead.dateid,concat('Applied Amount: ', sum(gdetail.db - gdetail.cr)) as rem
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join gldetail as gdetail on gdetail.trno = detail.refx
          left join glhead as ghead on ghead.trno = gdetail.trno
          where head.trno = '" . $trno . "' and gdetail.refx = 0 and ghead.docno is not null and ghead.doc and ghead.doc = 'SV'
          group by ghead.docno,ghead.dateid
          union all
          select ghead.docno,ghead.dateid,concat('Applied Amount: ', sum(gstock.ext)) as rem
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join glstock as gstock on gstock.trno = detail.refx
          left join glhead as ghead on ghead.trno = gstock.trno
          where head.trno = '" . $trno . "' and ghead.docno is not null and ghead.doc = 'SV'
          group by ghead.docno,ghead.dateid";
        break;
      case 'CV':
        $qry = "
          select  pohead.docno, date(pohead.dateid) as dateid,
          CAST(concat('Applied Amount: ', sum(postock.ext)) as CHAR) as rem,pohead.trno,pohead.doc,'' as url,'module' as moduletype
          from hpohead as pohead 
          left join hpostock as postock on pohead.trno = postock.trno
          left join glstock as rrstock on rrstock.refx = postock.trno and rrstock.linex = postock.line
          left join gldetail as cvdetail on cvdetail.refx = rrstock.trno
          where cvdetail.trno = '" . $trno . "'
          group by pohead.docno, pohead.dateid,pohead.trno,pohead.doc
          union all 
          select  rrhead.docno, date(rrhead.dateid) as dateid,
          CAST(concat('Applied Amount: ', sum(rrstock.ext)) as CHAR) as rem,rrhead.trno,rrhead.doc,'' as url,'module' as moduletype
          from glhead as rrhead
          left join glstock as rrstock on rrhead.trno = rrstock.trno
          left join gldetail as cvdetail on cvdetail.refx = rrstock.trno
          left join glhead as cvhead on cvhead.trno = cvdetail.trno
          where cvhead.trno = '" . $trno . "' and rrhead.doc = 'RR'
          group by rrhead.docno, rrhead.dateid,rrhead.doc,rrhead.trno
          union all 
          select  dmhead.docno, date(dmhead.dateid) as dateid,
          CAST(concat('Applied Amount: ', sum(dmstock.ext)) as CHAR) as rem,dmhead.trno,dmhead.doc,'' as url,'module' as moduletype
          from glhead as dmhead
          left join glstock as dmstock on dmhead.trno = dmstock.trno
          left join glstock as rrstock on rrstock.trno = dmstock.refx and rrstock.line = dmstock.linex
          left join gldetail as cvdetail on cvdetail.refx = rrstock.trno
          left join glhead as cvhead on cvhead.trno = cvdetail.trno
          where cvhead.trno = '" . $trno . "' and dmhead.doc = 'DM' 
          group by dmhead.docno, dmhead.dateid,dmhead.doc,dmhead.trno 
          union all 
          select gjhead.docno, date(gjhead.dateid) as dateid,
                  CAST(concat('Applied Amount: ', sum(gjdetail.db+gjdetail.cr)) as CHAR) as rem,gjhead.trno,
                  gjhead.doc,'' as url,'module' as moduletype
          from lahead as gjhead
          left join ladetail as gjdetail on gjdetail.trno = gjhead.trno
          left join gldetail as cvdetail on cvdetail.trno = gjdetail.refx and cvdetail.line=gjdetail.linex
          left join glhead as cvhead on cvhead.trno = cvdetail.trno
          where cvhead.trno = '" . $trno . "' and gjhead.doc in ('GJ','PV')
          group by gjhead.docno, gjhead.dateid,gjhead.doc,gjhead.trno
          union all
          select gjhead.docno, date(gjhead.dateid) as dateid,
                  CAST(concat('Applied Amount: ', sum(gjdetail.db+gjdetail.cr)) as CHAR) as rem,
                  gjhead.trno,gjhead.doc,'' as url,'module' as moduletype
          from glhead as gjhead
          left join gldetail as gjdetail on gjdetail.trno = gjhead.trno
          left join gldetail as cvdetail on cvdetail.trno = gjdetail.refx and cvdetail.line=gjdetail.linex
          left join glhead as cvhead on cvhead.trno = cvdetail.trno
          where cvhead.trno = '" . $trno . "' and gjhead.doc in ('GJ','PV')
          group by gjhead.docno, gjhead.dateid,gjhead.doc,gjhead.trno
          union all
          select apledger.docno,head.dateid,concat('Amount: ',sum(apledger.db+apledger.cr),'  -  ','BALANCE: ',sum(apledger.bal)) as rem,num.trno,num.doc,'' as url,'module' as moduletype
            from apledger left join cntnum as num on num.trno = apledger.trno 
            left join glhead as head on head.trno = num.trno where num.trno='" . $trno . "'
            group by apledger.docno, head.dateid,num.doc,num.trno
            union all
            select arledger.docno,head.dateid,concat('Amount: ',sum(arledger.db+arledger.cr),'  -  ','BALANCE: ',sum(arledger.bal)) as rem,num.trno,num.doc,'' as url,'module' as moduletype 
            from arledger left join cntnum as num on num.trno = arledger.trno
            left join glhead as head on head.trno = num.trno where num.trno='" . $trno . "'
            group by arledger.docno, head.dateid,num.doc,num.trno
            union all
            select head.docno,head.dateid,concat('Applied Amount: ',sum(detail.db+detail.cr)) as rem,head.trno,head.doc,'' as url,'module' as moduletype from lahead as head
            left join ladetail as detail on detail.trno=head.trno where detail.refx='" . $trno . "'
            group by head.docno, head.dateid,head.doc,head.trno
            union all
            select head.docno,head.dateid,concat('Applied Amount: ',sum(detail.db+detail.cr)) as rem,head.trno,head.doc,'' as url,'module' as moduletype from glhead as head
            left join gldetail as detail on detail.trno=head.trno where detail.refx='" . $trno . "'
            group by head.docno, head.dateid,head.doc,head.trno";
        break;
      case 'WB':
        $qry = "
          select sghead.trno,sghead.docno,left(sghead.dateid,10) as dateid,
          CAST(concat('Total WA Amt: ',round(sum(sgstock.ext),2)) as CHAR) as rem, s.refx,sghead.doc,'' as url,'module' as moduletype
          from hwahead as head
          left join hwastock as s on s.trno = head.trno
          left join glstock as wbstock on wbstock.refx = s.trno and wbstock.linex = s.line
          left join hsgstock as sgstock on sgstock.trno = s.refx and sgstock.line = s.linex
          left join hsghead as sghead on sghead.trno = sgstock.trno
          where wbstock.trno = '" . $trno . "'
          group by sghead.trno,sghead.docno,sghead.dateid, s.refx,sghead.doc
          union all
          select head.trno,head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total WA Amt: ',round(sum(s.ext),2)) as CHAR) as rem, s.refx,head.doc,'' as url,'module' as moduletype
          from hwahead as head 
          left join hwastock as s on s.trno = head.trno
          left join glstock as wbstock on wbstock.refx = s.trno and wbstock.linex = s.line
          where wbstock.trno = '" . $trno . "'
          group by head.trno,head.docno,head.dateid, s.refx,head.doc
        ";
        break;
      case 'QT':
        $qry = "select head.trno,head.doc,head.docno,date(head.dateid) as dateid,
         CAST(concat('Item Served: ',item.barcode,' - ',item.itemname,' - ',round(stock.isqty,2)) as CHAR) as rem,
        '' as url,'module' as moduletype
        from hsohead as head
        left join hsostock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join hqtstock as qtstock on qtstock.trno = stock.refx and qtstock.line = stock.linex
        where stock.refx = $trno group by head.trno,head.docno,head.dateid, stock.refx,stock.linex,head.doc,item.barcode,item.itemname,stock.isqty";
        break;
      case 'BQ':
        $qry = "select h.docno,h.dateid,h.doc,'/module/construction/' as url,'module' as moduletype
                from hprhead as h
                left join hprstock as s on s.trno=h.trno
                left join hsostock as bq on bq.trno=s.refx and bq.line=s.linex
                where bq.trno= $trno
                group by docno,dateid,doc
                union all
                select h.docno,h.dateid,h.doc,'/module/construction/' as url,'module' as moduletype
                from prhead as h
                left join prstock as s on s.trno=h.trno
                left join hsostock as bq on bq.trno=s.refx and bq.line=s.linex
                where bq.trno=$trno
                group by docno,dateid,doc
                union all

                select h.docno,h.dateid,h.doc,'/module/purchase/' as url,'module' as moduletype
                from pohead as h
                left join postock as s on s.trno=h.trno
                left join hprstock as pr on pr.trno=s.refx and pr.line=s.linex
                left join hsostock as bq on bq.trno=pr.refx and bq.line=pr.linex
                where bq.trno=$trno
                group by docno,dateid,doc
                union all
                select h.docno,h.dateid,h.doc,'/module/purchase/' as url,'module' as moduletype
                from hpohead as h
                left join hpostock as s on s.trno=h.trno
                left join hprstock as pr on pr.trno=s.refx and pr.line=s.linex
                left join hsostock as bq on bq.trno=pr.refx and bq.line=pr.linex
                where bq.trno=$trno
                group by docno,dateid,doc
                union all
                select h.docno,h.dateid,h.doc,'' as url,'module' as moduletype
                from lahead as h
                left join lastock as s on s.trno=h.trno
                left join hpostock as po on po.trno=s.refx and po.line=s.linex
                left join hprstock as pr on pr.trno=po.refx and pr.line=po.linex
                left join hsostock as bq on bq.trno=pr.refx and bq.line=pr.linex
                where bq.trno=$trno
                group by docno,dateid,doc
                union all
                select h.docno,h.dateid,h.doc,'' as url,'module' as moduletype
                from glhead as h
                left join glstock as s on s.trno=h.trno
                left join hpostock as po on po.trno=s.refx and po.line=s.linex
                left join hprstock as pr on pr.trno=po.refx and pr.line=po.linex
                left join hsostock as bq on bq.trno=pr.refx and bq.line=pr.linex
                where bq.trno=$trno
                group by docno,dateid,doc";
        break;
      case 'MT':
        $qry = 'select cntnum.docno, h.dateid, CAST(concat("Out-reference: ", item.itemname, ", QtyOut: ", c.served) as CHAR) as rem,h.trno ,h.doc,"" as url,"module" as moduletype 
                from costing as c 
                left join glstock as s on s.trno=c.trno and s.line = c.line 
                left join cntnum on cntnum.trno=c.trno 
                left join glhead as h on h.trno=cntnum.trno 
                left join item on item.itemid=s.itemid
                where c.refx=' . $trno . ' and cntnum.postdate is not null
                union all
                select cntnum.docno, h.dateid, CAST(concat("Out-reference: ", item.itemname, ", QtyOut: ", c.served) as CHAR) as rem,h.trno ,h.doc,"" as url,"module" as moduletype
                from costing as c 
                left join lastock as s on s.trno=c.trno and s.line = c.line 
                left join cntnum on cntnum.trno=c.trno 
                left join lahead as h on h.trno=cntnum.trno 
                left join item on item.itemid=s.itemid
                where c.refx=' . $trno . ' and cntnum.postdate is null';
        break;
    }
    $data = $this->coreFunctions->opentable($qry);

    switch ($doc) {
      case 'SA':
      case 'SB':
      case 'SC':
      case 'SG':
        foreach ($data as $key => $value) {
          $date = '';
          switch ($value->status) {
            case 'FOR PICKING':
              $date = $this->coreFunctions->getfieldvalue("lahead", "lockdate", "trno=?", [$value->trno]);
              break;

            case 'PICKED':
              $date = $this->coreFunctions->datareader("select pickerstart as value from lastock where trno=? order by pickerstart desc", [$value->trno]);
              break;

            case 'CHECKER: ON-PROCESS':
              $date = $this->coreFunctions->datareader("select checkerrcvdate as value from cntnuminfo where trno=?", [$value->trno]);
              break;

            case 'CHECKER: DONE':
              $date = $this->coreFunctions->datareader("select checkerdone as value from cntnuminfo where trno=?", [$value->trno]);
              break;

            case 'FOR DISPATCH':
              $date = $this->coreFunctions->datareader("select checkerdate as value from cntnuminfo where trno=?", [$value->trno]);
              break;

            case 'FOR LOADING':
              $date = $this->coreFunctions->datareader("select forloaddate as value from cntnuminfo where trno=?", [$value->trno]);
              break;

            case 'IN-TRANSIT':
              $date = $this->coreFunctions->datareader("select dispatchdate as value from cntnuminfo where trno=?", [$value->trno]);
              break;

            case 'DELIVERED':
              $date = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$value->trno]);
              break;
          }
          if ($date <> '') {
            $date = date_create($date);
          }
          $value->status = $value->status . ' ' . ($date <> '' ?  ' - ' . date_format($date, "m/d/Y h:i:sa") : ''); // date_format($newdate, "Y" . $c . "m" . $c . "d")
          $value->url = $this->checkdoc($value->doc, $config['params']['companyid']);
        }
        break;

      case 'BL':
      case 'BR':
        return $data;
        break;


      default:
        foreach ($data as $key => $value) {
          $value->url = $this->checkdoc($value->doc, $config['params']['companyid']);
        }
        break;
    }


    return $data;
  }

  public function checkdoc($doc, $companyid)
  {
    $purchases = 'purchase';
    $payable = 'payable';
    if ($companyid == 16) { //ati
      $purchases = 'ati';
      $payable = 'ati';
    }

    $url = '';
    switch (strtolower($doc)) {
      case 'sj':
      case 'cm':
      case 'sq':
      case 'qs':
      case 'ai':
      case 'ao':
        $url = "/module/sales/";
        break;
      case 'ar':
      case 'cr':
        if ($companyid == 55) {
          $url = "/module/lending/";
        } else {
          $url = "/module/receivable/";
        }

        break;
      case 'dm':
      case 'rr':
      case 'jb':
      case 'ac':
      case 'os':
      case 'po':
      case 'sr':
        $url = "/module/" . $purchases . "/";
        break;
      case 'ap':
      case 'cv':
      case 'pv':
        $url = "/module/" . $payable . "/";
        break;
      case 'ds':
      case 'gc':
      case 'gd':
      case 'gj':
        $url = "/module/accounting/";
        break;
      case 'sd':
      case 'se':
      case 'sf':
        $url = "/module/warehousing/";
        break;
      case 'aj':
      case 'is':
        $url = "/module/inventory/";
        break;
      case 'st':
        $url = "/module/issuance/";
        break;
      case 'su':
        if ($companyid == 10) { //afti
          $url = "/module/sales/";
        } else {
          $url = "/module/issuance/";
        }

        break;
      case 'mi':
      case 'bq':
      case 'jr':
      case 'rq':
      case 'mt':
        $url = "/module/construction/";
        break;
    }
    return $url;
  }

  public function loaddata($config)
  {
    return [];
  }
} //end class
