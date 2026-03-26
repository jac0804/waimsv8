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

class osi_report
{
    public $modulename = 'OSI Report';
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

        $fields = ['radioprint', 'start', 'end', 'categoryname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'categoryname.action', 'lookupreqcategory');
        data_set($col1, 'categoryname.lookupclass', 'lookupreqcategory');

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

        data_set(
            $col2,
            'radioreporttype.options',
            [
                ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
                ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
                ['label' => 'Posted OSI with Surcharge', 'value' => '2', 'color' => 'orange']
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
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start, left(now(),10) as end,
                        '0' as posttype,'0' as reporttype, 'ASC' as sorting,'' as categoryname";

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
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0:
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
            case 1:
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
            case 2:
                $result = $this->reportDefaultLayout_surcharge($config);
                break;
        }

        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $category  = $config['params']['dataparams']['categoryname'];

        $filter = "";
        if ($category != "") {
            $filter .= " and cat.category = '$category' ";
        }

        switch ($reporttype) {
            case 0: // summarized
                switch ($posttype) {
                    case 0: // posted
                        $query = "select docno,dateid,sum(rrqty) as rrqty,sum(rrcost) as rrcost,sum(ext) as ext, group_concat(ctrlno,' ') as ctrlno
                                from (select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,round(stock.rrqty,2)  as rrqty,
                                            round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,item.barcode,
                                            ifnull(info.itemdesc,'') as itemdesc,ifnull(info.requestorname,'') as requestorname,
                                            ifnull(dept.clientname,'') as department,stock.ref,ifnull(sup.clientname,'') as supplier,
                                            pr.clientname as customer,ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc,
                                            cat.category
                                    FROM homhead as head
                                    right join homstock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join client as warehouse on warehouse.clientid=stock.whid
                                    left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                    left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                    left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                    left join hprhead as pr on pr.trno=prs.trno
                                    left join clientsano as svs on svs.line=stock.svsno
                                    left join clientsano as sa on sa.line=pr.sano
                                    left join client as dept on dept.clientid=stock.deptid
                                    left join client as sup on sup.clientid=stock.suppid
                                    left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                    left join reqcategory as cat on cat.line=pr.ourref
                                    where date(head.dateid) between '$start' and '$end' $filter ) as a
                                group by docno,dateid
                                order by docno $sorting";
                        break;

                    case 1: // unposted
                        $query = "select docno,dateid,sum(rrqty) as rrqty,sum(rrcost) as rrcost,sum(ext) as ext, group_concat(ctrlno,' ') as ctrlno
                                from (select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,round(stock.rrqty,2)  as rrqty,
                                            round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,item.barcode,
                                            ifnull(info.itemdesc,'') as itemdesc,ifnull(info.requestorname,'') as requestorname,
                                            ifnull(dept.clientname,'') as department,stock.ref,ifnull(sup.clientname,'') as supplier,
                                            pr.clientname as customer,ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc,
                                            cat.category
                                    FROM omhead as head
                                    right join omstock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join client as warehouse on warehouse.clientid=stock.whid
                                    left join stockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                    left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                    left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                    left join hprhead as pr on pr.trno=prs.trno
                                    left join clientsano as svs on svs.line=stock.svsno
                                    left join clientsano as sa on sa.line=pr.sano
                                    left join client as dept on dept.clientid=stock.deptid
                                    left join client as sup on sup.clientid=stock.suppid
                                    left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                    left join reqcategory as cat on cat.line=pr.ourref
                                    where date(head.dateid) between '$start' and '$end' $filter ) as a
                                group by docno,dateid
                                order by docno $sorting";
                        break;

                    default: // sana all
                        $query = "select docno,dateid,sum(rrqty) as rrqty,sum(rrcost) as rrcost,sum(ext) as ext, group_concat(ctrlno,' ') as ctrlno
                                from (select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,round(stock.rrqty,2)  as rrqty,
                                            round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,item.barcode,
                                            ifnull(info.itemdesc,'') as itemdesc,ifnull(info.requestorname,'') as requestorname,
                                            ifnull(dept.clientname,'') as department,stock.ref,ifnull(sup.clientname,'') as supplier,
                                            pr.clientname as customer,ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc,
                                            cat.category
                                    FROM homhead as head
                                    right join homstock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join client as warehouse on warehouse.clientid=stock.whid
                                    left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                    left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                    left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                    left join hprhead as pr on pr.trno=prs.trno
                                    left join clientsano as svs on svs.line=stock.svsno
                                    left join clientsano as sa on sa.line=pr.sano
                                    left join client as dept on dept.clientid=stock.deptid
                                    left join client as sup on sup.clientid=stock.suppid
                                    left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                    left join reqcategory as cat on cat.line=pr.ourref
                                    where date(head.dateid) between '$start' and '$end' $filter 
                                    union all
                                    select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,round(stock.rrqty,2)  as rrqty,
                                            round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,item.barcode,
                                            ifnull(info.itemdesc,'') as itemdesc,ifnull(info.requestorname,'') as requestorname,
                                            ifnull(dept.clientname,'') as department,stock.ref,ifnull(sup.clientname,'') as supplier,
                                            pr.clientname as customer,ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc,
                                            cat.category
                                    FROM omhead as head
                                    right join omstock as stock on stock.trno=head.trno
                                    left join item on item.itemid=stock.itemid
                                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                    left join client as warehouse on warehouse.clientid=stock.whid
                                    left join stockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                    left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                    left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                    left join hprhead as pr on pr.trno=prs.trno
                                    left join clientsano as svs on svs.line=stock.svsno
                                    left join clientsano as sa on sa.line=pr.sano
                                    left join client as dept on dept.clientid=stock.deptid
                                    left join client as sup on sup.clientid=stock.suppid
                                    left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                    left join reqcategory as cat on cat.line=pr.ourref
                                    where date(head.dateid) between '$start' and '$end' $filter ) as a
                                group by docno,dateid
                                order by docno $sorting";
                        break;
                }
                break;

            case 1: // detailed
                switch ($posttype) {
                    case 0: // posted
                        $query = "select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,
                                        round(stock.rrqty,2)  as rrqty,round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,
                                        item.barcode,ifnull(info.itemdesc,'') as itemdesc,
                                        ifnull(info.requestorname,'') as requestorname,ifnull(dept.clientname,'') as department,
                                        stock.ref,ifnull(sup.clientname,'') as supplier,pr.clientname as customer,
                                        ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc, cat.category,
                                        (select group_concat(distinct yourref separator ', ')
                                        from (select yourref,pos.reqtrno,pos.reqline
                                            from hpohead as po
                                            left join hpostock as pos on pos.trno=po.trno where pos.void=0) as k
                                        where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono
                                FROM homhead as head
                                right join homstock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join model_masterfile as mm on mm.model_id = item.model
                                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                left join client as warehouse on warehouse.clientid=stock.whid
                                left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                left join hprhead as pr on pr.trno=prs.trno
                                left join clientsano as svs on svs.line=stock.svsno
                                left join clientsano as sa on sa.line=pr.sano
                                left join client as dept on dept.clientid=stock.deptid
                                left join client as sup on sup.clientid=stock.suppid
                                left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                left join reqcategory as cat on cat.line=pr.ourref
                                where date(head.dateid) between '$start' and '$end' $filter
                                order by docno $sorting";
                        break;

                    case 1: // unposted
                        $query = "select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,
                                        round(stock.rrqty,2)  as rrqty,round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,
                                        item.barcode,ifnull(info.itemdesc,'') as itemdesc,
                                        ifnull(info.requestorname,'') as requestorname,ifnull(dept.clientname,'') as department,
                                        stock.ref,ifnull(sup.clientname,'') as supplier,pr.clientname as customer,
                                        ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc, cat.category,
                                        (select group_concat(distinct yourref separator ', ')
                                        from (select yourref,pos.reqtrno,pos.reqline
                                            from hpohead as po
                                            left join hpostock as pos on pos.trno=po.trno where pos.void=0) as k
                                        where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono
                                FROM omhead as head
                                right join omstock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join model_masterfile as mm on mm.model_id = item.model
                                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                left join client as warehouse on warehouse.clientid=stock.whid
                                left join stockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                left join hprhead as pr on pr.trno=prs.trno
                                left join clientsano as svs on svs.line=stock.svsno
                                left join clientsano as sa on sa.line=pr.sano
                                left join client as dept on dept.clientid=stock.deptid
                                left join client as sup on sup.clientid=stock.suppid
                                left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                left join reqcategory as cat on cat.line=pr.ourref
                                where date(head.dateid) between '$start' and '$end' $filter
                                order by docno $sorting";
                        break;

                    default: // all
                        $query = "select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,
                                        round(stock.rrqty,2)  as rrqty, round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,
                                        item.barcode,ifnull(info.itemdesc,'') as itemdesc,
                                        ifnull(info.requestorname,'') as requestorname,ifnull(dept.clientname,'') as department,
                                        stock.ref,ifnull(sup.clientname,'') as supplier,pr.clientname as customer,
                                        ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc, cat.category,
                                        (select group_concat(distinct yourref separator ', ')
                                        from (select yourref,pos.reqtrno,pos.reqline
                                            from hpohead as po
                                            left join hpostock as pos on pos.trno=po.trno where pos.void=0) as k
                                        where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono
                                FROM homhead as head
                                right join homstock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join model_masterfile as mm on mm.model_id = item.model
                                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                left join client as warehouse on warehouse.clientid=stock.whid
                                left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                left join hprhead as pr on pr.trno=prs.trno
                                left join clientsano as svs on svs.line=stock.svsno
                                left join clientsano as sa on sa.line=pr.sano
                                left join client as dept on dept.clientid=stock.deptid
                                left join client as sup on sup.clientid=stock.suppid
                                left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                left join reqcategory as cat on cat.line=pr.ourref
                                where date(head.dateid) between '$start' and '$end' $filter
                                union all
                                select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,
                                        round(stock.rrqty,2)  as rrqty,round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,
                                        item.barcode,ifnull(info.itemdesc,'') as itemdesc,
                                        ifnull(info.requestorname,'') as requestorname,ifnull(dept.clientname,'') as department,
                                        stock.ref,ifnull(sup.clientname,'') as supplier,pr.clientname as customer,
                                        ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc, cat.category,
                                        (select group_concat(distinct yourref separator ', ')
                                        from (select yourref,pos.reqtrno,pos.reqline
                                            from hpohead as po
                                            left join hpostock as pos on pos.trno=po.trno where pos.void=0) as k
                                        where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono
                                FROM omhead as head
                                right join omstock as stock on stock.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join model_masterfile as mm on mm.model_id = item.model
                                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                                left join client as warehouse on warehouse.clientid=stock.whid
                                left join stockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                                left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                                left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                                left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                                left join hprhead as pr on pr.trno=prs.trno
                                left join clientsano as svs on svs.line=stock.svsno
                                left join clientsano as sa on sa.line=pr.sano
                                left join client as dept on dept.clientid=stock.deptid
                                left join client as sup on sup.clientid=stock.suppid
                                left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                                left join reqcategory as cat on cat.line=pr.ourref
                                where date(head.dateid) between '$start' and '$end' $filter
                                order by docno $sorting";
                        break;
                } // end switch
                break;
            case 2:
                $query = "select docno,dateid,ctrlno,oraclecode,rrqty,rrcost,ext,barcode,itemdesc,requestorname,
                                department,ref,supplier,customer,svsnum,sanodesc,category,pono,surcharge 
                         from (select head.docno,date(head.dateid) as dateid,info.ctrlno,stock.oraclecode,
                                round(stock.rrqty,2)  as rrqty,round(stock.rrcost,2) as rrcost,round(stock.ext,2) as ext,
                                item.barcode,ifnull(info.itemdesc,'') as itemdesc,
                                ifnull(info.requestorname,'') as requestorname,ifnull(dept.clientname,'') as department,
                                stock.ref,ifnull(sup.clientname,'') as supplier,pr.clientname as customer,
                                ifnull(svs.sano,'') as svsnum,ifnull(sa.sano,'') as sanodesc, cat.category,
                                (select group_concat(distinct yourref separator ', ')
                                from (select yourref,pos.reqtrno,pos.reqline
                                    from hpohead as po
                                    left join hpostock as pos on pos.trno=po.trno where pos.void=0) as k
                                where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono,
                                ifnull((select sum(scamt) as surcharge
                                from (select cvs.scamt,cvs.reqtrno,cvs.reqline
                                        from hcvitems as cvs
                                        union all
                                        select cvs.scamt,cvs.reqtrno,cvs.reqline
                                        from cvitems as cvs) as a
                                where a.reqtrno=stock.reqtrno and a.reqline=stock.reqline and a.scamt <> 0),0) as surcharge
                        FROM homhead as head
                        right join homstock as stock on stock.trno=head.trno
                        left join item on item.itemid=stock.itemid
                        left join model_masterfile as mm on mm.model_id = item.model
                        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                        left join client as warehouse on warehouse.clientid=stock.whid
                        left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                        left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                        left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
                        left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
                        left join hprhead as pr on pr.trno=prs.trno
                        left join clientsano as svs on svs.line=stock.svsno
                        left join clientsano as sa on sa.line=pr.sano
                        left join client as dept on dept.clientid=stock.deptid
                        left join client as sup on sup.clientid=stock.suppid
                        left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
                        left join reqcategory as cat on cat.line=pr.ourref
                        where date(head.dateid) between '$start' and '$end' $filter) as k
                        where surcharge <> 0
                        order by docno $sorting";
                break;
        } // end switch


        return $query;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $category  = $config['params']['dataparams']['categoryname'];

        if ($category == "") {
            $category = "ALL";
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

            default:
                $posttype = 'All';
                break;
        }

        if ($reporttype == 0) {
            $reporttype = 'Summarized';
            $layoutsize = '700';
        } else {
            $reporttype = 'Detailed';
            $layoutsize = '1600';
        }

        switch ($reporttype) {
            case 0:
                $reporttype = 'Summarized';
                $layoutsize = '700';
                break;
            case 1;
                $reporttype = 'Detailed';
                $layoutsize = '1600';
                break;
            case 2:
                $reporttype = 'with Surcharge';
                $layoutsize = '1700';
                break;
        }

        $str = '';


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

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OSI Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category: ' . $category, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $count = 41;
        $page = 40;
        $this->reporter->linecounter = 0;

        $str = '';

        $layoutsize = '1600';
        $font = $this->companysetup->getrptfont($config['params']);
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
                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Ctrl No', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Oracle Code', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Quantity', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Amount', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Requestor', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Department', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Supplier Name', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Customer', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('SVS #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('SA #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('PO #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->ctrlno, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->oraclecode, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->rrqty, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->rrcost, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->requestorname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->department, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->supplier, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->customer, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->svsnum, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sanodesc, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pono, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');


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
                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
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
        $count = 41;
        $page = 40;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '700';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        $total = 0;
        $i = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->ctrlno, '170', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col(number_format($data->rrqty, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();
                $total = $total + $data->ext;
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
                    $str .= $this->reporter->col('', '500', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Grand Total', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportDefaultLayout_surcharge($config)
    {
        $result = $this->reportDefault($config);
        $count = 41;
        $page = 40;
        $this->reporter->linecounter = 0;

        $str = '';

        $layoutsize = '1700';
        $font = $this->companysetup->getrptfont($config['params']);
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
        $gtotal = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno != "" && $docno != $data->docno) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total: ' . number_format($gtotal, 2), '1700', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1700', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                } //end if

                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $total = 0;
                    $gtotal = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Doc#: ' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Date: ' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Ctrl No', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Oracle Code', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Quantity', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Amount', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Surcharge', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Requestor', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Department', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Supplier Name', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Customer', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('SVS #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('SA #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('PO #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $total = $data->surcharge + $data->ext;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->ctrlno, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->oraclecode, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->rrqty, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->rrcost, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->surcharge, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->requestorname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->department, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->supplier, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->customer, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->svsnum, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sanodesc, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pono, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');


                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                if ($docno == $data->docno) {
                    $gtotal += $data->surcharge + $data->ext;
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
                    $str .= $this->reporter->col('Total: ' . number_format($gtotal, 2), '1700', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1700', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
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
        $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document No.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Ctrl No', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Quantity', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class