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

class create_oracle_code_request
{
    public $modulename = 'Create Oracle Code Request';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1800'];

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
        data_set($col1, 'repsortby.lookupclass', 'oraclerepsortby');

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



        $query = "select docno,customer,sanodesc,ctrlno,prdocno,dateid,barcode,itemname,itemdesc,rrqty,qa,pending,pendingamt,deadline,
                        category,requestorname,departmentname,oqdocno ,postdate
                from (select head.docno,c.clientname as customer,sa.sano as sanodesc,prinfo.ctrlno,stock.ref as prdocno,
                            left(head.dateid,10) as dateid,item.barcode,item.itemname,ifnull(prinfo.itemdesc,'') as itemdesc,
                            stock.rrqty,stock.qa,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as pending,
                            round((stock.qty-stock.qa) *stock.rrcost,2) as pendingamt,date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,
                            reqcat.category,ifnull(xinfo.requestorname,'') as requestorname,dept.clientname as departmentname,
                            (select group_concat(distinct docno separator ', ') from (select docno,oqs.cdrefx,oqs.cdlinex from oqhead as oq left join oqstock as oqs on oqs.trno=oq.trno union all
                            select docno,oqs.refx,oqs.linex from hoqhead as oq left join hoqstock as oqs on oqs.trno=oq.trno) as k where k.cdrefx=stock.trno and k.cdlinex = stock.line ) as oqdocno,date(num.postdate) as postdate
                    from hcdhead as head
                    left join hcdstock as stock on stock.trno=head.trno
                    left join client as c on c.clientid=stock.suppid
                    left join clientsano as sa on sa.line=stock.sano
                    left join hstockinfotrans as xinfo on xinfo.trno=stock.reqtrno and xinfo.line=stock.reqline
                    left join hprhead as pr on pr.trno=stock.refx
                    left join hprstock as prs on prs.trno=stock.refx and prs.line=stock.linex
                    left join hstockinfotrans as prinfo on prinfo.trno=prs.trno and prinfo.line=prs.line
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join reqcategory as reqcat on reqcat.line = pr.ourref
                    left join client as dept on dept.clientid=stock.deptid
                    left join transnum as num on num.trno=head.trno
                    where head.dateid between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' $filter 
                    group by head.docno,c.clientname,sa.sano,prinfo.ctrlno,stock.ref,
                    head.dateid,item.barcode,item.itemname,prinfo.itemdesc,stock.rrqty,stock.qa,
                    uom.factor,stock.rrcost,stock.qty,prinfo.deadline,reqcat.category,xinfo.requestorname,dept.clientname,stock.trno,stock.line,num.postdate
                    union all
                    select head.docno,c.clientname as customer,sa.sano as sanodesc,prinfo.ctrlno,stock.ref as prdocno,
                            left(head.dateid,10) as dateid,item.barcode,item.itemname,ifnull(prinfo.itemdesc,'') as itemdesc,stock.rrqty,stock.qa,
                            round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as pending,
                            round((stock.qty-stock.qa) *stock.rrcost,2) as pendingamt,date(ifnull(prinfo.deadline,'9998-12-31')) as deadline,reqcat.category,
                            ifnull(xinfo.requestorname,'') as requestorname,dept.clientname as departmentname,
                            (select group_concat(distinct docno separator ', ') from (select docno,oqs.cdrefx,oqs.cdlinex from oqhead as oq left join oqstock as oqs on oqs.trno=oq.trno union all
                            select docno,oqs.refx,oqs.linex from hoqhead as oq left join hoqstock as oqs on oqs.trno=oq.trno) as k where k.cdrefx=stock.trno and k.cdlinex = stock.line ) as oqdocno,date(num.postdate) as postdate
                    from cdhead as head
                    left join cdstock as stock on stock.trno=head.trno
                    left join client as c on c.clientid=stock.suppid
                    left join clientsano as sa on sa.line=stock.sano
                    left join hstockinfotrans as xinfo on xinfo.trno=stock.reqtrno and xinfo.line=stock.reqline
                    left join hprhead as pr on pr.trno=stock.refx
                    left join hprstock as prs on prs.trno=stock.refx and prs.line=stock.linex
                    left join hstockinfotrans as prinfo on prinfo.trno=prs.trno and prinfo.line=prs.line
                    left join item on item.itemid=stock.itemid
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                    left join reqcategory as reqcat on reqcat.line = pr.ourref
                    left join client as dept on dept.clientid=stock.deptid
                    left join transnum as num on num.trno=head.trno
                    where head.dateid between '$start' and '$end' and date(ifnull(prinfo.deadline,curdate())) between '$startd' and '$endd' $filter 
                    group by head.docno,c.clientname,sa.sano,prinfo.ctrlno,stock.ref,
                            head.dateid,item.barcode,item.itemname,prinfo.itemdesc,stock.rrqty,stock.qa,
                            uom.factor,stock.rrcost,stock.qty,prinfo.deadline,reqcat.category,xinfo.requestorname,dept.clientname,stock.trno,stock.line,num.postdate) as k
                where oqdocno is null $orderby";


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
        $layoutsize = '1900';
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
                $str .= $this->reporter->col('CANVASS SHEET', '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->customer, '190', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sanodesc, '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ctrlno, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->prdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dateid, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->deadline, '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rrqty == 0 ? '-' : number_format($data->rrqty, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->qa == 0 ? '-' : number_format($data->qa, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pending == 0 ? '-' : number_format($data->pending, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pendingamt == 0 ? '-' : number_format($data->pendingamt, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('&nbsp&nbsp' . $data->category, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->requestorname, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->departmentname, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
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


        $str = '';
        $layoutsize = '1800';
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
        $str .= $this->reporter->col('Create Oracle Code Request', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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
        $str .= $this->reporter->col('SOURCE', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '190', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SA#', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CTRL NO.', '60', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PR#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TEMP DESC', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEADLINE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ORDER QTY', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SERVED QTY', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PENDING QTY', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PENDING AMT', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CATEGORY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REQUESTOR', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPARTMENT', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('POSTDATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class