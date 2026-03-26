<?php

namespace App\Http\Classes\modules\reportlist\purchasing_reports;

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

class create_receiving_report
{
    public $modulename = 'Create Receiving Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'dwhname', 'start', 'end', 'effectfromdate', 'effecttodate', 'radiosjafti', 'categoryname', 'repsortby'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'effectfromdate.required', true);
        data_set($col1, 'effecttodate.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'effectfromdate.label', 'Deadline Start Date');
        data_set($col1, 'effecttodate.label', 'Deadline End Date');
        data_set($col1, 'categoryname.action', 'lookupreqcategory');
        data_set($col1, 'categoryname.lookupclass', 'lookupreqcategory');
        data_set($col1, 'repsortby.lookupclass', 'createRRSortBy');
        data_set($col1, 'radiosjafti.label', 'Source Option');
        data_set(
            $col1,
            'radiosjafti.options',
            [
                ['label' => 'PR Source', 'value' => 'PR', 'color' => 'blue'],
                ['label' => 'PO Source', 'value' => 'PO', 'color' => 'blue']
            ]
        );

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];

        return $this->coreFunctions->opentable("select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                adddate(left(now(),10),-360) as effectfromdate,
                left(now(),10) as effecttodate,
                '' as categoryname,
                '' as wh,
                '' as whname,
                'PR' as radiosjafti,
                '' as repsortby,'' as name
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];


        $result = $this->reportDefaultLayout($config);


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

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $startd      = date("Y-m-d", strtotime($config['params']['dataparams']['effectfromdate']));
        $endd        = date("Y-m-d", strtotime($config['params']['dataparams']['effecttodate']));

        $warehouse        = $config['params']['dataparams']['whname'];
        $category  = $config['params']['dataparams']['categoryname'];

        $repsortby  = $config['params']['dataparams']['repsortby'];
        $order = '';
        if (!empty($repsortby)) {
            $repsortname  = $config['params']['dataparams']['name'];
            $order = " order by $repsortname";
        }

        $filter = "";
        if (!empty($category)) {
            $filter .= " and reqcat.category = '$category' ";
        }

        if (!empty($warehouse)) {
            $wh        = $config['params']['dataparams']['wh'];
            $filter .= " and hc.wh = '$wh' ";
        }

        switch ($config['params']['dataparams']['radiosjafti']) {
            case 'PR':
                $query = "select source,docno,dateid,barcode,itemname,itemdesc,rrcost,disc,rrqty,qa,pending,advpayment,deadline,
                            cddocno,podocno,rrdocno,cvdocno,modeofpayment,salestype,db,cvadvdocno,cvadvmop,cvadvst,cvadvdb,category,
                            requestorname,departmentname,pono,customername,suppliername,ctrlno,postdate
                        from (select 'Purchase Requisition' as source,head.docno,left(head.dateid,10) as dateid,
                                    i.barcode,i.itemname,ifnull(prinfo.itemdesc,'') as itemdesc,hcd.rrcost,hcd.disc,stock.rrqty,stock.qa,
                                    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as pending,
                                    0 as advpayment,date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,hc.docno as cddocno,
                                    hpo.docno as podocno,
                                    (select group_concat(distinct docno separator ', ') from (select docno,rrs.reqtrno,rrs.reqline from lahead as rr left join lastock as rrs on rrs.trno=rr.trno union all
                                    select docno,rrs.reqtrno,rrs.reqline from glhead as rr left join glstock as rrs on rrs.trno=rr.trno) as k where k.reqtrno=stock.trno and k.reqline = stock.line ) as rrdocno,
                                    (select group_concat(distinct docno separator ', ') from (select docno,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select docno,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as cvdocno,
                                    (select group_concat(distinct modeofpayment separator ', ') from (select modeofpayment,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select modeofpayment,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as modeofpayment,
                                    (select group_concat(distinct salestype separator ', ') from (select salestype,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select salestype,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as salestype,
                                    (select sum(db) from (select db,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select db,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as db,
                                    (select group_concat(distinct docno separator ', ') from (select docno,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select docno,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=hpos.cvtrno ) as cvadvdocno,
                                    (select group_concat(distinct modeofpayment separator ', ') from (select modeofpayment,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select modeofpayment,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=hpos.cvtrno ) as cvadvmop,
                                    (select group_concat(distinct salestype separator ', ') from (select salestype,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select salestype,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=hpos.cvtrno ) as cvadvst,
                                    (select sum(db) from (select db,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select db,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=hpos.cvtrno ) as cvadvdb,
                                    reqcat.category,ifnull(prinfo.requestorname,'') as requestorname,dept.clientname as departmentname,
                                    hpo.yourref as pono,head.clientname as customername,hpo.clientname as suppliername,prinfo.ctrlno,date(num.postdate) as postdate
                            from hprhead as head
                            left join hprstock as stock on stock.trno=head.trno
                            left join item as i on i.itemid=stock.itemid
                            left join hstockinfotrans as prinfo on prinfo.trno=stock.trno and prinfo.line=stock.line
                            left join uom on uom.itemid=i.itemid and uom.uom=stock.uom
                            left join hcdstock as hcd on hcd.reqtrno=stock.trno and hcd.reqline=stock.line
                            left join hcdhead as hc on hc.trno=hcd.trno
                            left join hpostock as hpos on hpos.cdrefx=hcd.trno and hpos.cdlinex=hcd.line
                            left join hpohead as hpo on hpo.trno=hpos.trno
                            left join glstock as rrs on rrs.refx=hpos.trno and rrs.linex=hpos.line
                            left join glhead as rr on rr.trno=rrs.trno
                            left join reqcategory as reqcat on reqcat.line = head.ourref
                            left join client as dept on dept.clientid=head.deptid
                            left join cntnum as num on num.trno=head.trno
                            where hpo.dateid between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' $filter  
                            group by head.docno,head.dateid,i.barcode,i.itemname,prinfo.itemdesc,hcd.rrcost,hcd.disc,stock.rrqty,stock.qa, stock.qty,
                                uom.factor,prinfo.deadline,hc.docno,hpo.docno,rr.docno,
                                reqcat.category,prinfo.requestorname,dept.clientname,stock.trno,stock.line,rrs.trno,hpos.cvtrno,hpo.yourref,head.clientname,hpo.clientname,prinfo.ctrlno, num.postdate) as k where rrdocno is null
                        group by source,docno,dateid,barcode,itemname,itemdesc,rrcost,disc,rrqty,qa,pending,
                            advpayment,deadline,cddocno,podocno,rrdocno,category,
                            requestorname,departmentname,cvdocno,modeofpayment,salestype,db,cvadvdocno,cvadvmop,cvadvst,cvadvdb,pono,customername,suppliername,ctrlno,postdate $order";

                break;
            case 'PO':
                $query = "select source,docno,dateid,barcode,itemname,itemdesc,rrcost,disc,rrqty,qa,pending,deadline,cddocno,rrdocno,
                                 cvdocno,modeofpayment, salestype,db,cvadvdocno,cvadvmop,cvadvst,cvadvdb,category,
                                 requestorname,departmentname,customername,suppliername,pono,ctrlno,prdocno,postdate
                        from (select 'Purchase Order' as source,head.docno,left(head.dateid,10) as dateid,
                                    i.barcode,i.itemname,ifnull(prinfo.itemdesc,'') as itemdesc,stock.ext as rrcost,
                                    stock.disc,stock.rrqty,stock.qa,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as pending,
                                    date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,hc.docno as cddocno,
                                    (select group_concat(distinct docno separator ', ') from (select docno,rrs.refx,rrs.linex from lahead as rr left join lastock as rrs on rrs.trno=rr.trno union all
                                    select docno,rrs.refx,rrs.linex from glhead as rr left join glstock as rrs on rrs.trno=rr.trno) as k where k.refx=stock.trno and k.linex = stock.line ) as rrdocno,
                                    (select group_concat(distinct docno separator ', ') from (select docno,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select docno,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as cvdocno,
                                    (select group_concat(distinct modeofpayment separator ', ') from (select modeofpayment,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select modeofpayment,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as modeofpayment,
                                    (select group_concat(distinct salestype separator ', ') from (select salestype,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select salestype,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as salestype,
                                    (select sum(db) from (select db,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select db,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=rrs.trno ) as db,
                                    (select group_concat(distinct docno separator ', ') from (select docno,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select docno,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as cvadvdocno,
                                    (select group_concat(distinct modeofpayment separator ', ') from (select modeofpayment,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select modeofpayment,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as cvadvmop,
                                    (select group_concat(distinct salestype separator ', ') from (select salestype,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select salestype,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as cvadvst,
                                    (select sum(db) from (select db,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                                    select db,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as cvadvdb,
                                    reqcat.category,ifnull(xinfo.requestorname,'') as requestorname,dept.clientname as departmentname,
                                    pr.clientname as customername,head.clientname as suppliername,head.yourref as pono,prinfo.ctrlno,pr.docno as prdocno,date(num.postdate) as postdate
                            from hpohead as head
                            left join hpostock as stock on stock.trno=head.trno
                            left join item as i on i.itemid=stock.itemid
                            left join uom on uom.itemid=i.itemid and uom.uom=stock.uom
                            left join hcdstock as hcd on hcd.trno=stock.cdrefx and hcd.line=stock.cdlinex
                            left join hcdhead as hc on hc.trno=hcd.trno
                            left join hprstock as hpr on hpr.trno=stock.reqtrno and hpr.line=stock.reqline
                            left join hprhead as pr on pr.trno=hpr.trno
                            left join glstock as rrs on rrs.refx=stock.trno and rrs.linex=stock.line
                            left join glhead as rr on rr.trno=rrs.trno
                            left join reqcategory as reqcat on reqcat.line = pr.ourref
                            left join hstockinfotrans as prinfo on prinfo.trno=hpr.trno and prinfo.line=hpr.line
                            left join hstockinfotrans as xinfo on xinfo.trno=stock.reqtrno and xinfo.line=stock.reqline
                            left join client as dept on dept.clientid=hcd.deptid
                            left join transnum as num on num.trno=head.trno
                            where date(head.dateid) between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' $filter 
                            group by head.docno,head.dateid,i.barcode,i.itemname,prinfo.itemdesc,stock.ext,stock.disc,stock.rrqty,stock.qa,
                                    stock.qty,prinfo.deadline,hc.docno,rr.docno,reqcat.category,
                                    uom.factor,xinfo.requestorname,dept.clientname,rrs.trno,stock.cvtrno,
                                    pr.clientname,head.clientname,head.yourref,stock.trno,stock.line,prinfo.ctrlno,pr.docno,num.postdate) as k
                        where (cvadvdocno is not null or cvdocno is not null) and rrdocno is null
                        group by source,docno,dateid,barcode,itemname,itemdesc,rrcost,disc,rrqty,qa,pending,deadline,cddocno,rrdocno,
                                 cvdocno,modeofpayment, salestype,db,cvadvdocno,cvadvmop,cvadvst,cvadvdb,category,
                                 requestorname,departmentname,customername,suppliername,pono,ctrlno,prdocno,postdate $order";
                break;
        }
        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $company   = $config['params']['companyid'];

        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        if ($config['params']['dataparams']['radiosjafti'] == 'PO') {
            $layoutsize = '2200';
        } else {
            $layoutsize = '2200';
        }
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
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->source, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ctrlno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                if ($config['params']['dataparams']['radiosjafti'] == 'PO') {
                    $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                }
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->dateid, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pono, '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->customername, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->suppliername, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->barcode, '170', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(number_format($data->rrcost, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(number_format($data->rrqty, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->qa, 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->pending, 2), '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                if ($data->db == 0) {
                    $str .= $this->reporter->col(number_format($data->cvadvdb, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                } else {
                    $str .= $this->reporter->col(number_format($data->db, 2), '90', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                }
                if ($data->cvdocno == '') {
                    $str .= $this->reporter->col($data->cvadvdocno, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                } else {
                    $str .= $this->reporter->col($data->cvdocno, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                }

                $str .= $this->reporter->col($data->deadline, '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->category, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                if ($data->modeofpayment == '') {
                    $str .= $this->reporter->col($data->cvadvmop, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                } else {
                    $str .= $this->reporter->col($data->modeofpayment, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                }
                if ($data->salestype == '') {
                    $str .= $this->reporter->col($data->cvadvst, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                } else {
                    $str .= $this->reporter->col($data->salestype, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                }

                $str .= $this->reporter->col($data->requestorname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->departmentname, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->postdate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

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
        $warehouse  = $config['params']['dataparams']['whname'];

        $str = '';

        if ($config['params']['dataparams']['radiosjafti'] == 'PO') {
            $layoutsize = '2200';
        } else {
            $layoutsize = '2100';
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


        if (!empty($warehouse)) {
            $wh        = $config['params']['dataparams']['wh'];
        } else {
            $wh = "ALL";
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Create Receiving Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '700', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse : ' . $wh, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endrow();
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
        $str .= $this->reporter->col('SOURCE', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', ''); //100
        $str .= $this->reporter->col('CTRL NO.', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        if ($config['params']['dataparams']['radiosjafti'] == 'PO') {
            $str .= $this->reporter->col('PR DOCNO', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('DOC#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DATE', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        //PONO
        $str .= $this->reporter->col('PONO', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        //CUSTOMER NAME
        $str .= $this->reporter->col('CUSTOMER', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        //SUPPLIER NAME
        $str .= $this->reporter->col('SUPPLIER', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('BARCODE', '170', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '180', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TEMP DESC.', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('AMT', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('ORDER QTY', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SERVED', '40', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PENDING', '40', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ADV PAYMENT', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CV REF', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEADLINE', '70', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CATEGORY', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PAYMENT TYPE', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PAYMENT TERMS', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REQUESTOR', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPARTMENT', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('POSTDATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class