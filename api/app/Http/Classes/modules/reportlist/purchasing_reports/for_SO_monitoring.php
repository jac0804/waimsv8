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

class for_SO_monitoring
{
    public $modulename = 'For SO Monitoring';
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
        $fields = ['radioprint', 'start', 'end', 'radioposttype'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'effectfromdate.required', true);
        data_set($col1, 'effecttodate.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');


        data_set(
            $col1,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Partial Status', 'value' => '1', 'color' => 'teal']
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
                '0' as posttype
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

        $posttype  = $config['params']['dataparams']['posttype'];

        $filter = "";
        $orderby = "";


        switch ($posttype) {
            case 0: //posted
                $query = "select head.docno,head.clientname as supplier,head.dateid,stock.rrqty,stock.uom,stock.rrcost,
                                stock.disc,stock.ext,warehouse.client as wh,
                                warehouse.clientname as whname,ifnull(info.requestorname,'') as requestorname,
                                ifnull(info.purpose,'') as purpose,item.barcode,ifnull(info.itemdesc,'') as itemdesc,
                                ifnull(info.specs,'') as specs, ifnull(info.unit,'') as unit,stock.rem,stock.ref,
                                item.itemname,pr.clientname,ifnull(cat.category,'') as category,
                                (case when sinfo.intransit<>0 then 'true' else 'false' end) as intransit,
                                ifnull(stat1.status,'') as status1name,FORMAT(sinfo.qty1,2) as qty1,
                                ifnull(stat2.status,'') as status2name,FORMAT(sinfo.qty2,2) as qty2,
                                ifnull(stat3.status,'') as checkstatname,
                                if(stock.pickerstart is null,'false','true') as ispicked,
                                FORMAT(sinfo.tqty,2) as tqty,if(stock.ref='','true','false') as ismanual
                        from glhead as head
                        left join glstock as stock on stock.trno = head.trno
                        left join item on item.itemid=stock.itemid
                        left join client as warehouse on warehouse.clientid=stock.whid
                        left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                        left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                        left join trxstatus as stat1 on stat1.line=sinfo.status1
                        left join trxstatus as stat2 on stat2.line=sinfo.status2
                        left join trxstatus as stat3 on stat3.line=sinfo.checkstat
                        left join hprhead as pr on pr.trno=info.trno
                        left join reqcategory as cat on cat.line=pr.ourref
                        where head.doc ='RR' and cat.line=2 and head.dateid between '$start' and '$end'";
                break;

            case 1: //partial

                $query = "select head.docno,head.clientname as supplier,head.dateid,stock.rrqty,stock.uom,stock.rrcost,
                                stock.disc,stock.ext,warehouse.client as wh,
                                warehouse.clientname as whname,ifnull(info.requestorname,'') as requestorname,
                                ifnull(info.purpose,'') as purpose,item.barcode,ifnull(info.itemdesc,'') as itemdesc,
                                ifnull(info.specs,'') as specs, ifnull(info.unit,'') as unit,stock.rem,stock.ref,
                                item.itemname,pr.clientname,ifnull(cat.category,'') as category,
                                (case when sinfo.intransit<>0 then 'true' else 'false' end) as intransit,
                                ifnull(stat1.status,'') as status1name,FORMAT(sinfo.qty1,2) as qty1,
                                ifnull(stat2.status,'') as status2name,FORMAT(sinfo.qty2,2) as qty2,
                                ifnull(stat3.status,'') as checkstatname,
                                if(stock.pickerstart is null,'false','true') as ispicked,
                                FORMAT(sinfo.tqty,2) as tqty,if(stock.ref='','true','false') as ismanual
                        from lahead as head
                        left join lastock as stock on stock.trno = head.trno
                        left join item on item.itemid=stock.itemid
                        left join client as warehouse on warehouse.clientid=stock.whid
                        left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                        left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                        left join trxstatus as stat1 on stat1.line=sinfo.status1
                        left join trxstatus as stat2 on stat2.line=sinfo.status2
                        left join trxstatus as stat3 on stat3.line=sinfo.checkstat
                        left join hprhead as pr on pr.trno=info.trno
                        left join reqcategory as cat on cat.line=pr.ourref
                        where head.doc ='RR' and cat.line=2 and stat1.line = 51 and stat2.line=53 and head.dateid between '$start' and '$end'";
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
        $layoutsize = '1200';
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
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dateid, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->supplier, '170', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->barcode, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rrqty == 0 ? '-' : number_format($data->rrqty, 2), '60', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->specs, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '', 0, 'max-width:180px;overflow-wrap: break-word;');
                $str .= $this->reporter->col($data->clientname, '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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
        $layoutsize = '1200';
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
        $str .= $this->reporter->col('For SO Monitoring', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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
        $str .= $this->reporter->col('DOCNO', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DATE', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('SUPPLIER', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARCODE', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TEMP. DESCRIPTION', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('REQUEST QTY', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SPECS', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CLIENT NAME', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class