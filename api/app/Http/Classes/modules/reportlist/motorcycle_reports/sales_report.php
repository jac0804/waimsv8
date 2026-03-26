<?php

namespace App\Http\Classes\modules\reportlist\motorcycle_reports;

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

class sales_report
{
    public $modulename = 'Sales Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1340'];

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
        $fields = ['radioprint', 'start', 'end', 'dwhname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');

        data_set($col1,  'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        return $this->coreFunctions->opentable(
            "select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '' as wh,
            '' as whname,
            '' as dwhname"
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
        $result = $this->CDO_Layout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->CDO_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }

    public function CDO_QUERY($config)
    {
        $username   = $config['params']['user'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $wh         = $config['params']['dataparams']['wh'];
        $whname     = $config['params']['dataparams']['whname'];

        $filter = '';

        if ($wh != "") {
            $filter = $filter . " and wh.client='$wh'";
        }

        $query = "     
        select  
        date(head.deldate) as deliverydate,
        c.clientname as customername,
        c.addr as address,
        c.bday as birthdate,
        c.position as occupation,
        c.tin,
        c.contact,
        head.crref as crno,
        '' as arno,
        head.yourref as drno,
        head.ourref as csino,
        sum(stock.ext) as csiamt,
        stock.amt as srp,
        stock.cost,stock.disc,
        hinfo.downpayment as downpayment,
        mode.name as modeofsales,
        brand.brand_desc as brand,
        i.partno as modelcode,
        i.itemname as mcunitmodel,
        sout.color as color,
        sout.serial as engineno,
        sout.chassis as framechassisno,
        head.terms as term,
        format(ifnull(hinfo.fma1,0),2) as ma,
        wh.clientname as branchrelease,head.trno

        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as c on c.client=head.client
        left join serialout as sout on sout.trno=stock.trno and sout.line=stock.line
        left join item as i on i.itemid=stock.itemid
        left join frontend_ebrands as brand on brand.brandid=i.brand
        left join cntnuminfo as hinfo on hinfo.trno=head.trno
        left join client as wh on wh.client=head.wh
        left join mode_masterfile as mode on mode.line = head.modeofsales
        where head.doc ='MJ' and head.dateid between '$start' and '$end'
        $filter

        group by
        head.deldate,
        c.clientname,
        c.addr,
        c.bday,
        c.position,
        c.tin,
        c.contact,head.yourref,head.ourref,head.crref,head.docno ,        
        stock.amt,
        stock.cost,
        hinfo.downpayment,
        mode.name,
        
        brand.brand_desc,
        i.partno,
        i.itemname,
        sout.color,
        sout.serial,
        sout.chassis,
        head.terms,
        hinfo.fma1,stock.disc,
        wh.clientname,head.trno
        

        union all

        select  
        
        date(head.deldate) as deliverydate,
        c.clientname as customername,
        c.addr as address,
        c.bday as birthdate,
        c.position as occupation,
        c.tin,
        c.contact,
        head.crref as crno,
        '' as arno,
        head.yourref as drno,


        head.ourref as csino,
        sum(stock.ext) as csiamt,
        stock.isamt as srp,
        stock.cost,
        stock.disc,
        hinfo.downpayment as downpayment,
        mode.name as modeofsales,
        brand.brand_desc as brand,
        i.partno as modelcode,
        i.itemname as mcunitmodel,
        sout.color as color,
        sout.serial as engineno,
        sout.chassis as framechassisno,
        head.terms as term,
        format(ifnull(hinfo.fma1,0),2) as ma,
        wh.clientname as branchrelease,head.trno

        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as c on c.clientid=head.clientid
        left join serialout as sout on sout.trno=stock.trno and sout.line=stock.line
        
        left join item as i on i.itemid=stock.itemid
        left join frontend_ebrands as brand on brand.brandid=i.brand
        left join hcntnuminfo as hinfo on hinfo.trno=head.trno
        left join client as wh on wh.clientid=head.whid
        left join mode_masterfile as mode on mode.line = head.modeofsales
        where head.doc ='MJ' and head.dateid between '$start' and '$end'
        
        $filter

        group by
        head.deldate,
        c.clientname,
        c.addr,
        c.bday,
        c.position,
        c.tin,
        c.contact,head.yourref,head.ourref,head.crref,head.docno ,
        stock.isamt,
        stock.cost,
        hinfo.downpayment,
        mode.name,        
        brand.brand_desc,
        i.partno,
        i.itemname,
        sout.color,
        sout.serial,
        sout.chassis,
        head.terms,
        hinfo.fma1,stock.disc,
        wh.clientname,head.trno

        ";
        return $query;
    }


    public function CDO_Layout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "8";
        $border = "1px solid ";
        $layoutsize = '1200';
        $totalsrp = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->CDO_header($config);
        $str .= $this->tableheader($layoutsize, $config);

        $lineno = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $lineno++;

                $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();


                $str .= $this->reporter->col($lineno, '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->deliverydate, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->customername, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->address, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->birthdate, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->occupation, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->tin, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->contact, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


                //get mcdetails
                $qry = "select crno,rfno,sicsino,drno,chsino,swsno,dateid from (select yourref as crno,ourref as rfno,sicsino,drno,chsino,swsno,head.dateid from mchead as head left join mcdetail as detail on detail.trno = head.trno where detail.refx = ?
                union all
                select yourref as crno,ourref as rfno,sicsino,drno,chsino,swsno,head.dateid from hmchead as head left join hmcdetail as detail on detail.trno = head.trno where detail.refx = ?) as mc order by dateid limit 1";
  
             
                $mc = $this->coreFunctions->opentable($qry, [$data->trno, $data->trno]);

                $str .= $this->reporter->col(isset($mc[0]->crno) ? $mc[0]->crno : '', '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->arno, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(isset($mc[0]->drno) ? $mc[0]->drno : '', '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(isset($mc[0]->sicsino) ? $mc[0]->sicsino : '', '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(number_format($data->csiamt, 2), '46', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->srp, 2), '46', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->disc, '46', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->cost, 2), '46', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(number_format($data->downpayment, 2), '46', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->modeofsales, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->brand, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->modelcode, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->mcunitmodel, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->color, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->engineno, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->framechassisno, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->term, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ma, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->branchrelease, '46', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
            }
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function CDO_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $monthof        = date("F Y", strtotime($config['params']['dataparams']['end']));
        $wh         = $config['params']['dataparams']['wh'];
        $whname     = $config['params']['dataparams']['whname'];

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
        $str .= $this->reporter->col('Sales Report for the Month of ' . $monthof, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        if ($whname == '') {
            $str .= $this->reporter->col('WH: ALL ', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('WH: ' . $whname, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
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
        $str .= $this->reporter->col('No.', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DELIVERY DATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ADDRESS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BIRTHDATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('OCCUPATION', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TIN#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CONTACT#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CR#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AR#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DR#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->col('CSI #', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CSI AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SRP', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DISCOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COST', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOWNPAYMENT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MODE OF SALES', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRAND', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MODEL CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MC UNIT MODEL', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COLOR', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ENGINE NO', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FRAME/CHASSIS NO', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TERM', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('M.A.', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRANCH RELEASE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }
}//end class