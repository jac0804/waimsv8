<?php

namespace App\Http\Classes;

use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;

use Exception;
use Throwable;

use Illuminate\Support\Str;

class sbcatiappClass
{
    private $othersClass;
    private $coreFunctions;
    private $logger;

    public function __construct()
    {
        $this->othersClass = new othersClass;
        $this->coreFunctions = new coreFunctions;
        $this->logger = new Logger;
    }

    public function sbcatiapp($params)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        try {
            switch ($params['action']) {
                case 'getSupplier':
                    $sql = "select client.clientid as SupplierID, '' as Site, client.clientname as Supplier, client.contact as ContactPerson, client.email as Email, tel as ContactNumber, client.addr as Address, '' as ModeOfPayment, 
                    '' as PaymentDetails, client.tin as TinNo, client.rem as Remarks, ifnull((select group_concat(businessnature SEPARATOR '~') from othermaster where othermaster.clientid=client.clientid),'') as Category
                    from client 
                    left join category_masterfile as category on category.cat_id = client.category
                    where client.issupplier=1 order by client.clientname";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getPurchaseHistory':
                    $sql = "select cdh.trno as CanvassTrNo, cds.line as CanvassLine, cdh.docno as CanvassDocNo, cdh.dateid as CanvassTransDate, num.postdate as CanvassPostDate, cdh.client as CanvassSupplier, cdh.client as CanvassSupplierName,
                        item.itemid as ItemID, item.barcode as ItemCode, item.itemname as ItemName, cds.uom as UOM, ifnull(brand.brand_desc,'') as Brand, info.unit as PRUom,
                        cds.rrcost as Cost, cds.rrqty as CanvassQty, if(cds.status=1,'YES','NO') as CanvassApproved, if(cds.void=1,'YES','NO') as CanvassVoid,
                        info.ctrlno as CtrlNo, date(info.deadline) as Deadline, ifnull(cat.category,'') as Category, info.requestorname as Requestor, prh.clientname as Client, user.clientname as AssignedUser,
                        poh.trno as POTrNO, pos.line as POLine, poh.docno as PODocNo, poh.dateid as POTransDate, numpo.postdate as POPostDate, pos.rrqty as POQty, pos.rrcost as POCost, info.itemdesc as tempdesc
                        from hcdhead as cdh left join transnum as num on num.trno=cdh.trno
                        left join hcdstock as cds on cds.trno=cdh.trno
                        left join item on item.itemid=cds.itemid
                        left join frontend_ebrands as brand on brand.brandid = item.brand
                        left join hstockinfotrans as info on info.trno=cds.reqtrno and info.line=cds.reqline
                        left join hprstock as prs on prs.trno=cds.reqtrno and prs.line=cds.reqline
                        left join client as user on user.clientid=prs.suppid
                        left join hprhead as prh on prh.trno=info.trno
                        left join reqcategory as cat on cat.line=prh.ourref
                        left join client as dept on dept.clientid = prh.deptid
                        left join hpostock as pos on pos.cdrefx=cds.trno and pos.cdlinex=cds.line
                        left join hpohead as poh on poh.trno=pos.trno
                        left join transnum as numpo on numpo.trno=poh.trno
                        where cds.status<>0 order by cdh.docno,cds.line";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getItem':
                    $sql = "select itemid as ItemID, barcode as ItemCode, itemname as ItemDesc from item where isfa=0 order by itemid";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getPR':
                    $sql = "select h.trno, s.line,  h.docno, h.dateid, num.postdate,h.clientname as customer, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.encodeddate, 
                            info.specs, info.deadline, ifnull(d.duration,'') as duration, ifnull(d.days,0) as durationdays, info.ctrlno, info.unit as temp_uom, s.void, ifnull(dept.clientname,'') as deptname
                            from prhead as h left join prstock as s on s.trno=h.trno
                            left join stockinfotrans as info on info.trno=s.trno and info.line=s.line
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            left join duration as d on d.line=info.durationid
                            left join client as dept on dept.clientid = h.deptid
                            union all
                            select h.trno, s.line, h.docno, h.dateid, num.postdate,h.clientname as customer, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.encodeddate, 
                            info.specs, info.deadline, ifnull(d.duration,'') as duration, ifnull(d.days,0) as durationdays, info.ctrlno, info.unit as temp_uom, s.void, ifnull(dept.clientname,'') as deptname
                            from hprhead as h left join hprstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            left join duration as d on d.line=info.durationid
                            left join client as dept on dept.clientid = h.deptid
                            order by docno";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getCanvass':
                    $sql = "select h.trno, s.line,  h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.rrcost as cost, s.ext, s.encodeddate, info.ctrlno, s.void, 
                            s.approveddate, s.reqtrno, s.reqline, case s.status when 0 then 'Pending' when 1 then 'Approved' else 'Disapproved' end as status, s.ref
                            from cdhead as h left join cdstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            left join duration as d on d.line=info.durationid
                            union all
                            select h.trno, s.line, h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.rrcost as cost, s.ext, s.encodeddate, info.ctrlno, s.void, 
                            s.approveddate, s.reqtrno, s.reqline, case s.status when 0 then 'Pending' when 1 then 'Approved' else 'Disapproved' end as status, s.ref
                            from hcdhead as h left join hcdstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            left join duration as d on d.line=info.durationid
                            order by docno";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getPO':
                    $sql = "select h.trno, s.line,  h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.rrcost as cost, s.ext, s.encodeddate, info.ctrlno, s.void, 
                            s.reqtrno, s.reqline, s.ref
                            from pohead as h left join postock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            left join duration as d on d.line=info.durationid
                            union all
                            select h.trno, s.line, h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.rrcost as cost, s.ext, s.encodeddate, info.ctrlno, s.void, 
                            s.reqtrno, s.reqline, s.ref
                            from hpohead as h left join hpostock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            left join duration as d on d.line=info.durationid
                            order by docno";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getRR':
                    $sql = "select h.trno, s.line,  h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.rrcost as cost, s.ext, s.encodeddate, info.ctrlno, 
                            s.reqtrno, s.reqline, s.ref, ifnull(stat1.status,'') as receivestatus1, ifnull(stat2.status,'') as receivestatus2, ifnull(stat3.status,'') as checkstatus, item.isgeneric as genericitem
                            from lahead as h left join glstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join stockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
                            left join item on item.itemid=s.itemid
                            left join cntnum as num on num.trno=h.trno
                            left join trxstatus as stat1 on stat1.line=sinfo.status1
                            left join trxstatus as stat2 on stat2.line=sinfo.status2
                            left join trxstatus as stat3 on stat3.line=sinfo.checkstat
                            where h.doc='RR' and s.trno is not null
                            union all
                            select h.trno, s.line, h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, s.rrcost as cost, s.ext, s.encodeddate, info.ctrlno,
                            s.reqtrno, s.reqline, s.ref, ifnull(stat1.status,'') as receivestatus1, ifnull(stat2.status,'') as receivestatus2, ifnull(stat3.status,'') as checkstatus, item.isgeneric as genericitem
                            from glhead as h left join glstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join hstockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
                            left join item on item.itemid=s.itemid
                            left join cntnum as num on num.trno=h.trno
                            left join trxstatus as stat1 on stat1.line=sinfo.status1
                            left join trxstatus as stat2 on stat2.line=sinfo.status2
                            left join trxstatus as stat3 on stat3.line=sinfo.checkstat
                            where h.doc='RR' and s.trno is not null
                            order by docno";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getSS':
                    $sql = "select h.trno, s.line,  h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.isqty as qty, s.uom, s.encodeddate, info.ctrlno, 
                            s.reqtrno, s.reqline, s.ref
                            from lahead as h left join glstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS' and s.trno is not null
                            union all
                            select h.trno, s.line, h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.isqty as qty, s.uom, s.encodeddate, info.ctrlno,
                            s.reqtrno, s.reqline, s.ref
                            from glhead as h left join glstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join cntnum as num on num.trno=h.trno
                            where h.doc='SS' and s.trno is not null
                            order by docno";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getCV':
                    // $sql = "select h.trno, d.line, h.docno, h.clientname, h.dateid, poh.docno as ref, d.db as cvamt, po.rrqty as qty, po.rrcost as cost, po.disc, po.ext, info.ctrlno, ifnull(stat.status,'DRAFT') as status
                    //         from lahead as h left join ladetail as d on d.trno=h.trno 
                    //         left join cvitems as cv on cv.trno=d.trno and cv.line=d.line
                    //         left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                    //         left join hpohead as poh on poh.trno=po.trno
                    //         left join hstockinfotrans as info on info.trno=po.reqtrno and info.line=po.reqline
                    //         left join cntnum as c on c.trno=h.trno
                    //         left join trxstatus as stat on stat.line=c.statid
                    //         where h.doc='CV' and cv.trno is not null
                    //         union all
                    //         select h.trno, d.line, h.docno, h.clientname, h.dateid, poh.docno as ref, d.db as cvamt, po.rrqty as qty, po.rrcost as cost, po.disc, po.ext, info.ctrlno, ifnull(stat.status,'DRAFT') as status
                    //         from glhead as h left join gldetail as d on d.trno=h.trno 
                    //         left join hcvitems as cv on cv.trno=d.trno and cv.line=d.line
                    //         left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                    //         left join hpohead as poh on poh.trno=po.trno
                    //         left join hstockinfotrans as info on info.trno=po.reqtrno and info.line=po.reqline
                    //         left join cntnum as c on c.trno=h.trno
                    //         left join trxstatus as stat on stat.line=c.statid
                    //         where h.doc='CV' and cv.trno is not null";
                    $sql = "select h.trno, d.line, h.docno, h.clientname, h.dateid, poh.docno as ref, d.db as cvamt, po.rrqty as qty, po.rrcost as cost, po.disc, po.ext, info.ctrlno, c.postdate, ifnull(stat.status,'DRAFT') as status, 
                            if(cinfo.releasedate is null,'NO','YES') as payment_release, if(cinfo.ischqreleased=0,'NO','YES') as for_liquidation
                            from lahead as h left join ladetail as d on d.trno=h.trno 
                            left join cvitems as cv on cv.trno=d.trno and cv.line=d.line
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join hpohead as poh on poh.trno=po.trno
                            left join hstockinfotrans as info on info.trno=po.reqtrno and info.line=po.reqline
                            left join cntnum as c on c.trno=h.trno
                            left join trxstatus as stat on stat.line=c.statid
                            left join cntnuminfo as cinfo on cinfo.trno=h.trno
                            where h.doc='CV' and cv.trno is not null
                            union all
                            select h.trno, d.line, h.docno, h.clientname, h.dateid, poh.docno as ref, d.db as cvamt, po.rrqty as qty, po.rrcost as cost, po.disc, po.ext, info.ctrlno, c.postdate, ifnull(stat.status,'DRAFT') as status, 
                            if(cinfo.releasedate is null,'NO','YES') as payment_release, if(cinfo.ischqreleased=0,'NO','YES') as for_liquidation
                            from glhead as h left join gldetail as d on d.trno=h.trno 
                            left join hcvitems as cv on cv.trno=d.trno and cv.line=d.line
                            left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
                            left join hpohead as poh on poh.trno=po.trno
                            left join hstockinfotrans as info on info.trno=po.reqtrno and info.line=po.reqline
                            left join cntnum as c on c.trno=h.trno
                            left join trxstatus as stat on stat.line=c.statid
                            left join hcntnuminfo as cinfo on cinfo.trno=h.trno
                            where h.doc='CV' and cv.trno is not null
                            union all
                            select h.trno, d.line, h.docno, h.clientname, h.dateid, d.ref, d.db as cvamt, rr.rrqty as qty, rr.rrcost as cost, rr.disc, rr.ext, info.ctrlno, c.postdate, ifnull(stat.status,'DRAFT') as status, 
                            if(cinfo.releasedate is null,'NO','YES') as payment_release, if(cinfo.ischqreleased=0,'NO','YES') as for_liquidation
                            from lahead as h left join ladetail as d on d.trno=h.trno 
                            left join glstock as rr on rr.trno=d.refx
                            left join hstockinfotrans as info on info.trno=rr.reqtrno and info.line=rr.reqline
                            left join cntnum as c on c.trno=h.trno
                            left join trxstatus as stat on stat.line=c.statid
                            left join cntnuminfo as cinfo on cinfo.trno=h.trno
                            where h.doc='CV' and d.refx<>0
                            union all
                            select h.trno, d.line, h.docno, h.clientname, h.dateid, d.ref, d.db as cvamt, rr.rrqty as qty, rr.rrcost as cost, rr.disc, rr.ext, info.ctrlno, c.postdate, ifnull(stat.status,'DRAFT') as status, 
                            if(cinfo.releasedate is null,'NO','YES') as payment_release, if(cinfo.ischqreleased=0,'NO','YES') as for_liquidation
                            from glhead as h left join gldetail as d on d.trno=h.trno 
                            left join glstock as rr on rr.trno=d.refx
                            left join hstockinfotrans as info on info.trno=rr.reqtrno and info.line=rr.reqline
                            left join cntnum as c on c.trno=h.trno
                            left join trxstatus as stat on stat.line=c.statid
                            left join hcntnuminfo as cinfo on cinfo.trno=h.trno
                            where h.doc='CV' and d.refx<>0
                            order by docno;";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getOCR':
                    $sql = "select h.trno, s.line,  h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, info.unit as requestor_uom, s.encodeddate, info.ctrlno,
                            s.reqtrno, s.reqline, s.ref
                            from oqhead as h left join oqstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            union all
                            select h.trno, s.line, h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, info.unit as requestor_uom, s.encodeddate, info.ctrlno,
                            s.reqtrno, s.reqline, s.ref
                            from hoqhead as h left join hoqstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            order by docno";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
                case 'getOSI':
                    $sql = "select h.trno, s.line,  h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, info.unit as requestor_uom, s.encodeddate, info.ctrlno,
                            s.reqtrno, s.reqline, s.ref, (select group_concat(sono SEPARATOR '\n\r') from omso where omso.trno=s.trno and omso.line=s.line) as sono
                            from omhead as h left join omstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            union all
                            select h.trno, s.line, h.docno, h.dateid, num.postdate,h.clientname as supplier, ifnull(item.barcode,'') as barcode, ifnull(item.itemname,'') as stockcard_itemname, ifnull(info.itemdesc,'') as requestor_itemname, s.rrqty as qty, s.uom, info.unit as requestor_uom, s.encodeddate, info.ctrlno,
                            s.reqtrno, s.reqline, s.ref, (select group_concat(sono SEPARATOR '\n\r') from homso where homso.trno=s.trno and homso.line=s.line) as sono
                            from homhead as h left join homstock as s on s.trno=h.trno
                            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                            left join item on item.itemid=s.itemid
                            left join transnum as num on num.trno=h.trno
                            order by docno";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;
            }
        } catch (Exception $e) {
            $this->coreFunctions->LogConsole('sbcatiapp - ' . $e);
            return json_encode(['status' => false, 'msg' => 'sbcappreg - ' . $e]);
        }
    }
}
