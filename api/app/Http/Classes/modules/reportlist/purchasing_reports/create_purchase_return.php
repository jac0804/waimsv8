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

class create_purchase_return
{
    public $modulename = 'Create Purchase Return';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1300'];

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
        $fields = ['radioprint', 'start', 'end', 'effectfromdate', 'effecttodate', 'categoryname', 'repsortby'];

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
        data_set($col1, 'repsortby.lookupclass', 'cprrepsortby');

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
                '' as repsortby
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

        $start      = $config['params']['dataparams']['start'];
        $end        = $config['params']['dataparams']['end'];
        $startd      = $config['params']['dataparams']['effectfromdate'];
        $endd        = $config['params']['dataparams']['effecttodate'];

        $category  = $config['params']['dataparams']['categoryname'];
        $repsortby  = $config['params']['dataparams']['repsortby'];

        $filter = "";
        $orderby = "";
        if (!empty($category)) {
            $filter .= " and reqcat.category = '$category' ";
        }
        if (!empty($repsortby)) {
            $repsortname  = $config['params']['dataparams']['name'];
            $orderby = " order by " . $repsortname;
        }

        $query = "
        select docno,client,clientname,barcode,itemname,ext,podocno,podate,suppcode,supplier,rrdocno,datereceived,specs,deadline,category,dmdocno,
                prdocno,reqtrno,requestorname,departmentname from (
        select head.docno,head.client,head.clientname,item.barcode,item.itemname,stock.ext,
                    poh.docno as podocno,left(poh.dateid,10) as podate,poh.client as suppcode,poh.clientname as supplier,
                    rrh.docno as rrdocno,rrh.dateid as datereceived,prinfo.specs,
                date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,
                reqcat.category,
                (select group_concat(distinct docno separator ', ')
                from (select docno,s.reqtrno,s.reqline
                    from lahead as h
                    left join lastock as s on s.trno=h.trno where h.doc='DM'
                    union all
                    select docno,s.reqtrno,s.reqline
                    from glhead as h
                    left join glstock as s on s.trno=h.trno where h.doc='DM') as k
                where k.reqtrno=stock.reqtrno and k.reqline = stock.reqline ) as dmdocno,
                pr.docno as prdocno,stock.reqtrno,ifnull(prinfo.requestorname,'') as requestorname,dept.clientname as departmentname
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join item on item.itemid = stock.itemid
                left join hpostock as po on po.reqtrno=stock.reqtrno and po.reqline=stock.reqline
                left join hpohead as poh on poh.trno=po.trno
                left join glstock as rr on rr.reqtrno=stock.reqtrno and rr.reqline=stock.reqline
                left join glhead as rrh on rrh.trno=rr.trno and rrh.doc='RR'
                left join hprstock as prh on prh.trno=stock.reqtrno and prh.line=stock.reqline
                left join hprhead as pr on pr.trno=prh.trno
                left join hstockinfotrans as prinfo on prinfo.trno=prh.trno and prinfo.line=prh.line
                left join reqcategory as reqcat on reqcat.line = pr.ourref
                left join client as dept on dept.clientid=pr.deptid
                where head.doc in ('SS','SP') and stock.reqtrno <> ''  and poh.docno is not null and head.yourref = 'PURCHASE RETURN' and head.dateid between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' $filter 
                union all
                select head.docno,client.client,head.clientname,item.barcode,item.itemname,stock.ext,
                    poh.docno as podocno,left(poh.dateid,10) as podate,poh.client as suppcode,poh.clientname as supplier,
                    rrh.docno as rrdocno,rrh.dateid as datereceived,prinfo.specs,
                date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,
                reqcat.category,
                (select group_concat(distinct docno separator ', ')
                from (select docno,s.reqtrno,s.reqline
                    from lahead as h
                    left join lastock as s on s.trno=h.trno where h.doc='DM'
                    union all
                    select docno,s.reqtrno,s.reqline
                    from glhead as h
                    left join glstock as s on s.trno=h.trno where h.doc='DM') as k
                where k.reqtrno=stock.reqtrno and k.reqline = stock.reqline ) as dmdocno,
                pr.docno as prdocno,stock.reqtrno,ifnull(prinfo.requestorname,'') as requestorname,dept.clientname as departmentname
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join item on item.itemid = stock.itemid
                left join client on client.clientid=head.clientid
                left join hpostock as po on po.reqtrno=stock.reqtrno and po.reqline=stock.reqline
                left join hpohead as poh on poh.trno=po.trno
                left join glstock as rr on rr.reqtrno=stock.reqtrno and rr.reqline=stock.reqline
                left join glhead as rrh on rrh.trno=rr.trno and rrh.doc='RR'
                left join hprstock as prh on prh.trno=stock.reqtrno and prh.line=stock.reqline
                left join hprhead as pr on pr.trno=prh.trno
                left join hstockinfotrans as prinfo on prinfo.trno=prh.trno and prinfo.line=prh.line
                left join reqcategory as reqcat on reqcat.line = pr.ourref
                left join client as dept on dept.clientid=pr.deptid
                where head.doc in ('SS','SP') and stock.reqtrno <> '' and poh.docno is not null and head.yourref = 'PURCHASE RETURN' and head.dateid between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' $filter 
                $orderby ) as k
                where dmdocno is null";


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
        $layoutsize = '1300';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9";
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
                $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->podate, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->datereceived, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->specs, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->ext == 0 ? '-' : number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->deadline, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->category, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->requestorname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->departmentname, '210', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

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
        $layoutsize = '1300';
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
        $str .= $this->reporter->col('Create Purchase Return', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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
        $fontsize = "10";
        $border = "1px solid ";
        $company   = $config['params']['companyid'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('PO DATE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE RECEIVED', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '180', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SPECS', '180', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEADLINE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CATEGORY', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REQUESTOR', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPARTMENT', '210', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class