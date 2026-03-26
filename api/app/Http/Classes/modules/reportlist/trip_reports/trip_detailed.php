<?php

namespace App\Http\Classes\modules\reportlist\trip_reports;

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

class trip_detailed
{
    public $modulename = 'Trip Detailed';
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
        $fields = ['radioprint', 'start', 'end', 'dclientname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'dclientname.label', 'From:');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable(
            "select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '' as client,
              '' as clientname"

        );
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
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $filter     = "";
        if ($client != '') {
            $filter .= "and cl.client='$client'";
        }

        $center    = $config['params']['center'];
        $query = "select dateid,docno,tarrived,tdepart, Aarrive,Adepart, itemdesc1, itemcode, unit, qty, receivedate,clname,whname,truckcode,itemdesc2,empcode,empname,activity,rate,jobtitle,payrollb,adate1,adate2,bdate1,bdate2 from (
            select head.dateid,head.docno,date(head.sdate1) as tarrived,date(head.sdate2) as tdepart,date(info.sdate1) as Aarrive,date(info.sdate2) as Adepart,
ifnull(item.itemname,'') as itemdesc1,ifnull(item.barcode,'') as itemcode ,stock.uom as unit,
stock.rrqty as qty,date(info.receivedate) as receivedate,
wh.clientname as clname ,head.clientname as whname,
asset.barcode as truckcode,asset.itemname as itemdesc2,emp.client as empcode,emp.clientname as empname,d.activity, d.rate,jt.jobtitle,
batch.batch as payrollb,head.strdate1 as adate1,head.strdate2 as adate2,info.strdate1 as bdate1,info.strdate2 as bdate2
 from lahead as head
left join tripdetail  as d on d.trno = head.trno
left join cntnuminfo as info on info.trno=head.trno
left join lastock as stock on stock.trno = head.trno
left join item on item.itemid=stock.itemid
left join item as asset on asset.itemid=d.itemid
left join client as emp on emp.clientid=d.clientid
left join employee on employee.empid = emp.clientid
left join jobthead as jt on jt.line = employee.jobid
left join batch on batch.line = d.batchid
left join client as wh on wh.client=head.wh
left join cntnum on cntnum.trno=head.trno
left join client as cl on cl.client=head.client
where head.dateid between '$start' and '$end' and head.doc = 'rr' and stock.line = 1 and cntnum.center = '" . $center . "' " . $filter . "
    union all
select head.dateid,head.docno,date(head.sdate1) as tarrived,date(head.sdate2) as tdepart,date(info.sdate1) as Aarrive,date(info.sdate2) as Adepart,
ifnull(item.itemname,'') as itemdecs1,ifnull(item.barcode,'') as itemcode ,stock.uom as unit,
stock.rrqty  as qty,date(info.receivedate) as receivedate,
wh.clientname as clname, head.clientname as whname,
asset.barcode as truckcode,asset.itemname as itemdesc2,emp.client as empcode,emp.clientname as empname,d.activity, d.rate,jt.jobtitle,
batch.batch as payrollb,head.strdate1 as adate1,head.strdate2 as adate2,info.strdate1 as bdate1,info.strdate2 as bdate2
 from glhead as head
left join htripdetail  as d on d.trno = head.trno
left join hcntnuminfo as info on info.trno=head.trno
left join glstock as stock on stock.trno = head.trno
left join item on item.itemid=stock.itemid
left join item as asset on asset.itemid=d.itemid
left join client as emp on emp.clientid=d.clientid
left join employee on employee.empid = emp.clientid
left join jobthead as jt on jt.line = employee.jobid
left join batch on batch.line = d.batchid
left join client as wh on wh.clientid=head.whid
left join cntnum on cntnum.trno=head.trno
left join client as cl on cl.clientid=head.clientid
where  head.dateid between '$start' and '$end' and head.doc = 'rr' and stock.line = 1 and cntnum.center = '" . $center . "' " . $filter . "
union all 
select head.dateid,head.docno,date(head.sdate1) as tarrived,date(head.sdate2) as tdepart,date(info.sdate1) as Aarrive,date(info.sdate2) as Adepart,
ifnull(item.itemname,'') as itemdecs1,ifnull(item.barcode,'') as itemcode ,stock.uom as unit,
stock.isqty as qty,date(info.receivedate) as receivedate,
head.clientname as clname, wh.clientname as whname,
asset.barcode as truckcode,asset.itemname as itemdesc2,emp.client as empcode,emp.clientname as empname,d.activity, d.rate,jt.jobtitle,
batch.batch as payrollb,head.strdate1 as adate1,head.strdate2 as adate2,info.strdate1 as bdate1,info.strdate2 as bdate2
 from lahead as head
left join tripdetail  as d on d.trno = head.trno
left join cntnuminfo as info on info.trno=head.trno
left join lastock as stock on stock.trno = head.trno
left join item on item.itemid=stock.itemid
left join item as asset on asset.itemid=d.itemid
left join client as emp on emp.clientid=d.clientid
left join employee on employee.empid = emp.clientid
left join jobthead as jt on jt.line = employee.jobid
left join batch on batch.line = d.batchid
left join client as wh on wh.client=head.wh
left join cntnum on cntnum.trno=head.trno
left join client as cl on cl.client=head.client
where head.dateid between '$start' and '$end' and head.doc in ('sj','ts','mi') and stock.line = 1 and cntnum.center = '" . $center . "' " . $filter . "
union all 
select head.dateid,head.docno,date(head.sdate1) as tarrived,date(head.sdate2) as tdepart,date(info.sdate1) as Aarrive,date(info.sdate2) as Adepart,
ifnull(item.itemname,'') as itemdecs1,ifnull(item.barcode,'') as itemcode ,stock.uom as unit,
stock.isqty as qty,date(info.receivedate) as receivedate,
head.clientname as clname, wh.clientname as whname,
asset.barcode as truckcode,asset.itemname as itemdesc2,emp.client as empcode,emp.clientname as empname,d.activity, d.rate,jt.jobtitle,
batch.batch as payrollb,head.strdate1 as adate1,head.strdate2 as adate2,info.strdate1 as bdate1,info.strdate2 as bdate2
 from glhead as head
left join htripdetail  as d on d.trno = head.trno
left join hcntnuminfo as info on info.trno=head.trno
left join glstock as stock on stock.trno = head.trno
left join item on item.itemid=stock.itemid
left join item as asset on asset.itemid=d.itemid
left join client as emp on emp.clientid=d.clientid
left join employee on employee.empid = emp.clientid
left join jobthead as jt on jt.line = employee.jobid
left join batch on batch.line = d.batchid
left join client as wh on wh.clientid=head.whid
left join cntnum on cntnum.trno=head.trno
left join client as cl on cl.clientid=head.clientid
where  head.dateid between '$start' and '$end' and head.doc in ('sj','ts','mi') and stock.line = 1 and cntnum.center = '" . $center . "' " . $filter . "
        ) as trip order by clname,whname,dateid";
        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $companyid = $config['params']['companyid'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '2330';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);
        $totalrate = 0;
        $doc = '';
        if (!empty($result)) {
            foreach ($result as $key => $data) {

                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->whname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->adate1, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->adate2, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->bdate1, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->bdate2, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc1, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->qty, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->unit, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->truckcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc2, '220', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empcode, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->jobtitle, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->activity, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rate, '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->receivedate, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->payrollb, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totalrate += $data->rate;
            }
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '2250', null, false, '1px dotted', 'B', '', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL RATE: ', '1900', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('' . number_format($totalrate, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '190', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $client   = $config['params']['dataparams']['client'];
        $clientname   = $config['params']['dataparams']['clientname'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '600';
        if ($client == '') {
            $from = 'ALL';
        } else {
            $from =  $client . ' - ' . $clientname;
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

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TRIP DETAILED', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('From : ' . $from, '350', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
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
        $layoutsize = '2330';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC NO', '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FROM', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TO', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ARRIVED', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPART', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ARRIVED', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPART', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DESC', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('TRUCK CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DESC', '220', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CODE', '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('EMPLOYEE NAME', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JOB TITILE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ACTIVITY', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('RATE', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APPROVED DATE', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PAYROLL BATCH', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class