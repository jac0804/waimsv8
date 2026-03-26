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

class trip_summary
{
    public $modulename = 'Trip Summmary';
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
        data_set($col1, 'dclientname.label', 'FROM :');
        // data_set($col1, 'client.label', 'Supplier');

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
                    '' as clientname,
                    '' as dclientname"

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
        $center     = $config['params']['center'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $filter     = "";
        if ($client != '') {
            $filter .= "and cl.client='$client'";
        }

        $query = "select trno,dateid,docno,MAX(itemdesc1) as itemdesc1,MAX(itemcode) as itemcode ,unit,MAX(qty) as qty,arrive,clname,whname,receiveby,receivedate
         from (
        select head.trno,head.dateid,head.docno,
        (SELECT IFNULL(item.itemname, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemdesc1,
       (SELECT IFNULL(item.barcode, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemcode,
           
            stock.uom as unit,

        (SELECT IFNULL(stock1.rrqty, '')
            FROM glstock as stock1
            WHERE stock1.trno = head.trno
            LIMIT 1) AS qty,

        info.strdate1 as  arrive,
        wh.clientname as clname ,head.clientname as whname, info.receiveby,ifnull(date(info.receivedate), '') as receivedate
        from lahead as head
        left join tripdetail  as d on d.trno = head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join lastock as stock on stock.trno = head.trno
        left join item on item.itemid=stock.itemid
        left join client as emp on emp.clientid=d.clientid
        left join employee on employee.empid = emp.clientid
        left join jobthead as jt on jt.line = employee.jobid
        left join client as wh on wh.client=head.wh
        left join cntnum on cntnum.trno=head.trno
        left join client as cl on cl.client=head.client
        where head.dateid between '$start' and '$end' and head.doc = 'rr' and cntnum.center = '" . $center . "'  " . $filter . "
        union all 
        select head.trno,head.dateid,head.docno,
        (SELECT IFNULL(item.itemname, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemdesc1,

       (SELECT IFNULL(item.barcode, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemcode,

        stock.uom as unit,

        (SELECT IFNULL(stock1.rrqty, '')
            FROM glstock as stock1
            WHERE stock1.trno = head.trno
            LIMIT 1) AS qty,
        info.strdate1 as  arrive,
        wh.clientname as clname ,head.clientname as whname,info.receiveby,ifnull(date(info.receivedate), '') as receivedate
        from glhead as head
        left join htripdetail  as d on d.trno = head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid=stock.itemid
        left join client as emp on emp.clientid=d.clientid
        left join employee on employee.empid = emp.clientid
        left join jobthead as jt on jt.line = employee.jobid
        left join client as wh on wh.clientid=head.whid
        left join cntnum on cntnum.trno=head.trno
        left join client as cl on cl.clientid=head.clientid
        where head.dateid between '$start' and '$end' and  head.doc = 'rr' and cntnum.center = '" . $center . "'  " . $filter . "
        union all 
        select head.trno,head.dateid,head.docno,
        
        (SELECT IFNULL(item.itemname, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemdesc1,


       (SELECT IFNULL(item.barcode, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemcode,

        stock.uom as unit,

        (SELECT IFNULL(stock1.rrqty, '')
            FROM glstock as stock1
            WHERE stock1.trno = head.trno
            LIMIT 1) AS qty,
        info.strdate1 as  arrive,
        head.clientname as clname ,wh.clientname as whname,info.receiveby,ifnull(date(info.receivedate), '') as receivedate
        from lahead as head
        left join tripdetail  as d on d.trno = head.trno
        left join cntnuminfo as info on info.trno=head.trno
        left join lastock as stock on stock.trno = head.trno
        left join item on item.itemid=stock.itemid
        left join client as emp on emp.clientid=d.clientid
        left join employee on employee.empid = emp.clientid
        left join jobthead as jt on jt.line = employee.jobid
        left join client as wh on wh.client=head.wh
        left join cntnum on cntnum.trno=head.trno
        left join client as cl on cl.client=head.client
        where head.dateid between '$start' and '$end' and head.doc in ('sj','ts','mi') and cntnum.center = '" . $center . "'  " . $filter . "
        union all
        select head.trno,head.dateid,head.docno,
        (SELECT IFNULL(item.itemname, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemdesc1,


       (SELECT IFNULL(item.barcode, '')
            FROM item
            WHERE item.itemid = stock.itemid
            LIMIT 1) AS itemcode,
        stock.uom as unit,

        (SELECT IFNULL(stock1.rrqty, '')
            FROM glstock as stock1
            WHERE stock1.trno = head.trno
            LIMIT 1) AS qty,

        info.strdate1 as  arrive,
        head.clientname as clname ,wh.clientname as whname,info.receiveby,ifnull(date(info.receivedate), '') as receivedate
        from glhead as head
        left join htripdetail  as d on d.trno = head.trno
        left join hcntnuminfo as info on info.trno=head.trno
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid=stock.itemid
        left join client as emp on emp.clientid=d.clientid
        left join employee on employee.empid = emp.clientid
        left join jobthead as jt on jt.line = employee.jobid
        left join client as wh on wh.clientid=head.whid
        left join cntnum on cntnum.trno=head.trno
        left join client as cl on cl.clientid=head.clientid
        where head.dateid between '$start' and '$end' and head.doc in ('sj','ts','mi') and cntnum.center = '" . $center . "'  " . $filter . " ) as trip 
        group by trno,dateid,docno,unit,arrive,clname,whname,receiveby,receivedate
        order by clname,whname";
        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1200';

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
                $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->whname, '170', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->arrive, '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc1, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->unit, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->receiveby, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->receivedate, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
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
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientname     = $config['params']['dataparams']['clientname'];
        $result = $this->reportDefault($config);
        $str = '';
        $layoutsize = '1200';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $count = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $trnx = $data->trno;
                $count++;
            }
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
        $str .= $this->reporter->col('Trip Summary', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('From : ' . $clientname, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No of Trnx : ' . $count, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
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
        $layoutsize = '1200';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC NO', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FROM', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TO', '170', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TIME ARRIVE', '70', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DESC', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APPROVED', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class