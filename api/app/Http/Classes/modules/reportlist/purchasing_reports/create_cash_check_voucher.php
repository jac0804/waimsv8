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

class create_cash_check_voucher
{
    public $modulename = 'Create Cash/Check Voucher';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1500px;max-width:2500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '2000'];

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
        $fields = ['radioprint', 'start', 'end', 'effectfromdate', 'effecttodate', 'paymentname', 'salestype', 'categoryname', 'repsortby'];

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

        data_set($col1, 'salestype.label', 'Payment Terms');
        data_set($col1, 'salestype.action', 'lookuppaymentterms');
        data_set($col1, 'salestype.lookupclass', 'lookuppaymentterms');
        data_set($col1, 'salestype.required', false);

        data_set($col1, 'repsortby.lookupclass', 'createCVSortBy');

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
                '' as paymentname,
                '' as salestype,
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

        $paymentname        = $config['params']['dataparams']['paymentname'];
        $terms        = $config['params']['dataparams']['salestype'];

        $category  = $config['params']['dataparams']['categoryname'];
        $filter = "";

        $order = '';
        $repsortby  = $config['params']['dataparams']['repsortby'];

        $order = '';
        if (!empty($repsortby)) {
            $repsortname  = $config['params']['dataparams']['name'];
            $order = " order by $repsortname ";
        }

        if (!empty($category)) {
            $filter .= " and category = '$category' ";
        }

        if (!empty($paymentname)) {
            $filter .= " and modeofpayment = '$paymentname' ";
        }

        if (!empty($terms)) {
            $filter .= " and salestype = '$terms' ";
        }

        $query = "select terms,porrdocno,client,clientname,prdocno,ctrlno,porrdate,barcode,itemname,itemdesc,rrcost,disc,rrqty,qa,pending,deadline,cddocno,
                            category,requestorname,departmentname,cvdocno,modeofpayment,salestype,db,cr,left(postdate,10) as postdate
                            
                            from (
        select head.terms,head.docno as porrdocno,head.client,head.clientname,pr.docno as prdocno,prinfo.ctrlno,
                        left(head.dateid,10) as porrdate,i.barcode,i.itemname,ifnull(prinfo.itemdesc,'') as itemdesc, stock.rrcost,stock.disc,stock.rrqty,stock.qa,
                        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as pending, date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,
                        hc.docno as cddocno, reqcat.category,ifnull(prinfo.requestorname,'') as requestorname,dept.clientname as departmentname,
                        (select group_concat(distinct docno separator ', ') from (select docno,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno
                        union all select docno,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as cvdocno,
                        (select group_concat(distinct modeofpayment separator ', ') from (select modeofpayment,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno
                        union all select modeofpayment,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as modeofpayment,
                        (select group_concat(distinct salestype separator ', ') from (select salestype,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno
                        union all select salestype,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as salestype,
                        (select sum(db) from (select db,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                        select db,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as db,
                        (select sum(cr) from (select cr,cvh.trno from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                        select cr,cvh.trno from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.trno=stock.cvtrno ) as cr,
                        t.postdate
                from hpohead as head
                left join hpostock as stock on stock.trno=head.trno
                left join item as i on i.itemid=stock.itemid
                left join uom on uom.itemid=i.itemid and uom.uom=stock.uom
                left join hcdstock as hcd on hcd.trno=stock.cdrefx and hcd.line=stock.cdlinex
                left join hcdhead as hc on hc.trno=hcd.trno
                left join hprstock as hpr on hpr.trno=stock.reqtrno and hpr.line=stock.reqline
                left join hprhead as pr on pr.trno=hpr.trno
                left join reqcategory as reqcat on reqcat.line = pr.ourref
                left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                left join client as dept on dept.clientid=pr.deptid
                left join transnum as t on t.trno=head.trno
                where stock.void=0 and stock.isadv=1 and date(head.dateid) between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' 
                group by head.terms,head.docno,head.client,head.clientname,pr.docno,prinfo.ctrlno,head.dateid, i.barcode,i.itemname,prinfo.itemdesc,stock.rrcost,stock.disc,stock.rrqty,
                        stock.qa,stock.qty,uom.factor,prinfo.deadline,hc.docno,reqcat.category, prinfo.requestorname,dept.clientname,cvdocno,modeofpayment,salestype,stock.cvtrno,stock.trno,t.postdate
                union all
                select head.terms,head.docno as porrdocno,client.client,client.clientname, pr.docno as prdocno,prinfo.ctrlno,
                        left(head.dateid,10) as porrdate,item.barcode,item.itemname,ifnull(prinfo.itemdesc,'') as itemdesc, stock.rrcost,stock.disc,stock.rrqty,stock.qa,
                        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as pending, date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,'' as cddocno,
                        reqcat.category, ifnull(prinfo.requestorname,'') as requestorname,dept.clientname as departmentname,
                        (select group_concat(distinct docno separator ', ') from (select docno,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno
                        union all select docno,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=stock.trno ) as cvdocno,
                        (select group_concat(distinct modeofpayment separator ', ') from (select modeofpayment,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno
                        union all select modeofpayment,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=stock.trno ) as modeofpayment,
                        (select group_concat(distinct salestype separator ', ') from (select salestype,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno
                        union all select salestype,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=stock.trno ) as salestype,
                        (select sum(db) from (select db,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                        select db,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=stock.trno ) as db,
                        (select sum(cr) from (select cr,refx from lahead as cvh left join ladetail as cvd on cvd.trno=cvh.trno union all
                        select cr,refx from glhead as cvh left join gldetail as cvd on cvd.trno=cvh.trno) as k where k.refx=stock.trno ) as cr,
                        t.postdate
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join hstockinfotrans as prinfo on prinfo.trno=stock.reqtrno and prinfo.line=stock.reqline
                left join hprhead as pr on pr.trno=prinfo.trno
                left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
                left join item on item.itemid=stock.itemid
                left join client on client.clientid=head.clientid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                left join reqcategory as reqcat on reqcat.line = pr.ourref
                left join client as dept on dept.clientid=pr.deptid
                left join hpostock as po on po.trno=stock.refx and po.line = stock.linex
                left join transnum as t on t.trno=head.trno
                where head.doc ='RR' and po.isadv = 0  and date(head.dateid) between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' 
                group by head.terms,head.docno,client.client,client.clientname,pr.docno,prinfo.ctrlno,
                        head.dateid,item.barcode,item.itemname,prinfo.itemdesc,stock.rrcost,stock.disc,stock.rrqty,
                        stock.qa,stock.qty,prinfo.deadline,reqcat.category,uom.factor,prinfo.requestorname,dept.clientname,stock.trno,t.postdate) as k where 1=1 $filter   $order";
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
        $layoutsize = '2200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        $bal = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $bal = $data->db - $data->cr;
                $str .= $this->reporter->col($data->clientname, '170', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ctrlno, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->porrdocno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->porrdate, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->barcode, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->rrcost == 0 ? '-' : number_format($data->rrcost, 2), '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->disc, '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rrqty == 0 ? '-' : number_format($data->rrqty, 2), '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->qa == 0 ? '-' : number_format($data->qa, 2), '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pending == 0 ? '-' : number_format($data->pending, 2), '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->db == 0 ? '-' : number_format($data->db, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->cr == 0 ? '-' : number_format($data->cr, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($bal == 0 ? '-' : number_format($bal, 2), '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->deadline, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->modeofpayment, '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->salestype, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->category, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->requestorname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->departmentname, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->postdate, '180', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
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


        $str = '';
        $layoutsize = '2200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = '10';
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Create Cash/Check Voucher', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '700', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

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
        $fontsize = '10';
        $border = "1px solid ";
        $company   = $config['params']['companyid'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('CTRL NO.', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PR DOCNO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DATE', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('BARCODE', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEMNAME', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TEMP DESCRIPTION', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('AMT', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DISCOUNT', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ORDER QTY', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SERVED', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PENDING', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEADLINE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('PAYMENT TYPE', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PAYMENT TERMS', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CATEGORY', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('REQUESTOR', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPARTMENT', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('POST DATE', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class