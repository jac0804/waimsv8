<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class total_consumption_per_supplier
{
    public $modulename = 'Total Consumption Per Supplier';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;



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
        $fields = ['radioprint', 'year', 'dclientname', 'dagentname', 'radioposttype'];
        $col1 = $this->fieldClass->create($fields);

        data_set(
            $col1,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            year(now()) as year,
            '' as client,
            '' as clientname,
            '' as dclientname,
            '' as agent,
            0 as agentid,
            '0' as posttype,
            '' as agentname,
            '' as dagentname
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
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $data = $this->reportDefault($config);

        return $this->reportDefaultLayout($config, $data);
    }

    public function reportDefault($config)
    {
        $center   = $config['params']['center'];
        $year   = $config['params']['dataparams']['year'];
        $client   = $config['params']['dataparams']['client'];
        $agent   = $config['params']['dataparams']['agent'];
        $posttype   = $config['params']['dataparams']['posttype'];

        $filter = "";
        if ($client != "") {
            $filter .= " and supp.client = '$client'";
        }

        if ($agent != "") {
            $filter .= " and agent.client = '$agent'";
        }

        switch ($posttype) {
            case '0': // posted
                $query = "select supplier, month,clientname,itemid,itemname,sum(isqty) as isqty,isamt,uom,agentname 
                from (
                select rr.clientname as supplier,month(head.dateid) as month,head.clientname, stock.itemid,item.itemname,
                stock.isqty,stock.isamt,stock.uom,agent.client as agent,agent.clientname as agentname
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join costing as cost on cost.trno=head.trno and cost.line = stock.line
                left join glhead as rr on rr.trno=cost.refx
                left join glstock as rrs on rrs.trno=rr.trno and rrs.itemid=cost.itemid
                left join item on item.itemid=stock.itemid
                left join client as supp on supp.clientid=rr.clientid
                left join client as agent on agent.clientid=head.agentid
                where head.doc='SJ' and supp.issupplier=1 and year(head.dateid)= $year $filter
                group by rr.clientname,month(head.dateid),head.clientname, stock.itemid,item.itemname,
                stock.isqty,stock.isamt,stock.uom,agent.client,agent.clientname
                order by supplier,clientname,itemname) as a
                group by supplier, month,clientname,itemid,itemname,isamt,uom,agentname
                order by supplier,agentname,clientname,itemname,month";
                break;
            case '1': // unposted
                $query = "select supplier, month,clientname,itemid,itemname,sum(isqty) as isqty,isamt,uom,agentname 
                from (select rr.clientname as supplier,month(head.dateid) as month,head.clientname, 
                stock.itemid,item.itemname,stock.isqty,stock.isamt,stock.uom,head.agent,agent.clientname as agentname
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join costing as cost on cost.trno=head.trno and cost.line = stock.line
                left join glhead as rr on rr.trno=cost.refx
                left join glstock as rrs on rrs.trno=rr.trno and rrs.itemid=cost.itemid
                left join item on item.itemid=stock.itemid
                left join client as supp on supp.clientid=rr.clientid
                left join client as agent on agent.client=head.agent
                where head.doc='SJ' and supp.issupplier=1 and year(head.dateid)= $year $filter
                group by rr.clientname,month(head.dateid),head.clientname, stock.itemid,item.itemname,
                stock.isqty,stock.isamt,stock.uom,head.agent,agent.clientname
               ) as a
                group by supplier, month,clientname,itemid,itemname,isamt,uom,agentname
                order by supplier,agentname,clientname,itemname,month";
                break;
            default: // all
                $query = "select supplier, month, clientname, itemid, itemname, sum(isqty) as isqty, isamt, uom, agentname 
                from (
                select rr.clientname as supplier, month(head.dateid) as month, head.clientname, stock.itemid, item.itemname,
                stock.isqty, stock.isamt, stock.uom, agent.clientname as agentname
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join costing as cost on cost.trno = head.trno and cost.line = stock.line
                left join glhead as rr on rr.trno = cost.refx
                left join glstock as rrs on rrs.trno = rr.trno and rrs.itemid = cost.itemid
                left join item on item.itemid = stock.itemid
                left join client as supp on supp.clientid = rr.clientid
                left join client as agent on agent.clientid = head.agentid
                where head.doc = 'SJ' and supp.issupplier = 1 and year(head.dateid) = $year $filter
                group by rr.clientname, month(head.dateid), head.clientname, stock.itemid, item.itemname,
                stock.isqty, stock.isamt, stock.uom, agent.clientname
                union all
                select rr.clientname as supplier, month(head.dateid) as month, head.clientname, 
                stock.itemid, item.itemname, stock.isqty, stock.isamt, stock.uom, agent.clientname as agentname
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join costing as cost on cost.trno = head.trno and cost.line = stock.line
                left join glhead as rr on rr.trno = cost.refx
                left join glstock as rrs on rrs.trno = rr.trno and rrs.itemid = cost.itemid
                left join item on item.itemid = stock.itemid
                left join client as supp on supp.clientid = rr.clientid
                left join client as agent on agent.client = head.agent
                where head.doc = 'SJ' and supp.issupplier = 1 and year(head.dateid) = $year $filter 
                group by rr.clientname, month(head.dateid), head.clientname, stock.itemid, item.itemname,
                stock.isqty, stock.isamt, stock.uom, agent.clientname
                ) as x
                group by supplier, month, clientname, itemid, itemname, isamt, uom, agentname
                order by supplier, agentname, clientname, itemname, month;";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config, $layoutsize)
    {
        $center   = $config['params']['center'];
        $year   = $config['params']['dataparams']['year'];
        $client   = $config['params']['dataparams']['client'];
        $supplier   = $config['params']['dataparams']['clientname'];
        $agent   = $config['params']['dataparams']['agent'];
        $agentname   = $config['params']['dataparams']['agentname'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $font = $this->companysetup->getrptfont($config['params']);
        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br>');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Consumption per Supplier', null, null, false, '1px solid ', '', 'C', $font, '17', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Year : ' . $year, null, null, false, '1px solid ', '', 'C', $font, '17', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br>";

        if ($supplier == "") {
            $supplier = "ALL";
        }
        if ($agentname == "") {
            $agentname = "ALL";
        }

        if ($posttype == 0) {
            $posttype = 'Posted';
        } else if ($posttype == 1) {
            $posttype = 'Unposted';
        } else {
            $posttype = 'All';
        }

        $str .= $this->reporter->begintable('1200');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier : ' . $supplier, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        $str .= $this->reporter->col('Agent : ' . $agentname, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        $str .= $this->reporter->col('Transaction Type : ' . $posttype, null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
        $str .= $this->reporter->pagenumber('Page', null, null, '', '1px solid ', '', 'r', '', '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable('1600');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier', '150', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Agent', '100', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Customer', '150', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Item', '140', null, '', '2px solid ', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Market Price', '100', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('JANUARY', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('FEBRUARY', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('MARCH', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('APRIL', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('MAY', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('JUNE', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('JULY', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('AUGUST', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('SEPTEMBER', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('OCTOBER', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('NOVEMBER', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('DECEMBER', '80', null, '', '2px solid ', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        //
        $str .= $this->reporter->endtable();
        //
        return $str;
    }

    private function reportDefaultLayout($config, $result)
    {
        $str = '';
        $count = 60;
        $page = 59;
        $layoutsize = 1600;
        $font = $this->companysetup->getrptfont($config['params']);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // decimal settings
        $companyid = $config['params']['companyid'];
        $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);
        $decimal_qty = $this->companysetup->getdecimal('qty', $config['params']);
        $decimal_price = $this->companysetup->getdecimal('price', $config['params']);

        $str .= $this->reporter->beginreport('1600');
        $str .= $this->displayHeader($config, $layoutsize, $result);

        $supplier = "";
        $customername = "";
        $agent = '';

        $janqty = $febqty = $marqty = $aprqty = $mayqty = $juneqty = $julqty = $augqty = $septqty = $octqty = $novqty = $decqty = 0;
        $sjanqty = $sfebqty = $smarqty = $saprqty = $smayqty = $sjuneqty = 0;
        $sjulqty = $saugqty = $sseptqty = $soctqty = $snovqty = $sdecqty = 0;

        $subjanqty = $subfebqty = $submarqty = $subaprqty = $submayqty = $subjuneqty = 0;
        $subjulqty = $subaugqty = $subseptqty = $suboctqty = $subnovqty = $subdecqty = 0;

        $gjanqty = $gfebqty = $gmarqty = $gaprqty = $gmayqty = $gjuneqty = 0;
        $gjulqty = $gaugqty = $gseptqty = $goctqty = $gnovqty = $gdecqty = 0;

        foreach ($result as $key => $value) {

            if ($value->supplier == "") {
                $value->supplier = "NO SUPPLIER";
            }

            if ($supplier != $value->supplier || $agent != $value->agentname || $customername != $value->clientname) {

                if ($supplier != $value->supplier) {

                    if ($supplier != "") {
                        //
                        $str .= $this->reporter->begintable('1600');
                        //
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->addline();
                        $str .= $this->reporter->col($supplier . ' GRAND-TOTAL', '640', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subjanqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subfebqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($submarqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subaprqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($submayqty, 2), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subjuneqty, 2), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subjulqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subaugqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subseptqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($suboctqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subnovqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->col(number_format($subdecqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
                        $str .= $this->reporter->endrow();
                        //
                        $str .= $this->reporter->endtable();
                        //
                        $subjanqty = $subfebqty = $submarqty = $subaprqty = $submayqty = $subjuneqty = 0;
                        $subjulqty = $subaugqty = $subseptqty = $suboctqty = $subnovqty = $subdecqty = 0;
                        if ($this->reporter->linecounter == $page) {
                            $str .= $this->reporter->endtable();
                            $str .= $this->reporter->page_break();
                            $str .= $this->displayHeader($config, $layoutsize, $result);
                            $page = $page + $count;
                        } //end if
                    }
                    $str .= $this->reporter->begintable('1600');
                    //
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col($value->supplier, '400', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '140', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                    $str .= $this->reporter->endrow();
                    //
                    $str .= $this->reporter->endtable();
                    //
                    if ($this->reporter->linecounter == $page) {
                        $str .= $this->reporter->endtable();
                        $str .= $this->reporter->page_break();
                        $str .= $this->displayHeader($config, $layoutsize, $result);
                        $page = $page + $count;
                    } //end if
                }

                $str .= $this->reporter->begintable('1600');
                //
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col($value->agentname, '390', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->endrow();
                //
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable('1500');
                //
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col($value->clientname, '290', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '4px');
                $str .= $this->reporter->endrow();
                //
                $str .= $this->reporter->endtable();
            }


            //
            $str .= $this->reporter->begintable('1600');
            //

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col($value->itemname, '140', null, '', '1px dashed ', 'T', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col(number_format($value->isamt, 2), '100', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');

            switch ($value->month) {
                case 1:
                    $janqty = $value->isqty;
                    break;
                case 2:
                    $febqty = $value->isqty;
                    break;
                case 3:
                    $marqty = $value->isqty;
                    break;
                case 4:
                    $aprqty = $value->isqty;
                    break;
                case 5:
                    $mayqty = $value->isqty;
                    break;
                case 6:
                    $juneqty = $value->isqty;
                    break;
                case 7:
                    $julqty = $value->isqty;
                    break;
                case 8:
                    $augqty = $value->isqty;
                    break;
                case 9:
                    $septqty = $value->isqty;
                    break;
                case 10:
                    $octqty = $value->isqty;
                    break;
                case 11:
                    $novqty = $value->isqty;
                    break;
                case 12:
                    $decqty = $value->isqty;
                    break;
            }

            $str .= $this->reporter->col($janqty == 0 ? '-' : number_format($janqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($febqty == 0 ? '-' : number_format($febqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($marqty == 0 ? '-' : number_format($marqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($aprqty == 0 ? '-' : number_format($aprqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($mayqty == 0 ? '-' : number_format($mayqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($juneqty == 0 ? '-' : number_format($juneqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($julqty == 0 ? '-' : number_format($julqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($augqty == 0 ? '-' : number_format($augqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($septqty == 0 ? '-' : number_format($septqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($octqty == 0 ? '-' : number_format($octqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($novqty == 0 ? '-' : number_format($novqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col($decqty == 0 ? '-' : number_format($decqty, 2), '80', null, '', '1px dashed ', 'T', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
            //
            $str .= $this->reporter->endtable();
            //
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $layoutsize, $result);
                $page = $page + $count;
            } //end if
            $supplier = $value->supplier;
            $customername = $value->clientname;
            $agent = $value->agentname;


            $sjanqty += $janqty;
            $sfebqty += $febqty;
            $smarqty += $marqty;
            $saprqty += $aprqty;
            $smayqty += $mayqty;
            $sjuneqty += $juneqty;
            $sjulqty += $julqty;
            $saugqty += $augqty;
            $sseptqty += $septqty;
            $soctqty += $octqty;
            $snovqty += $novqty;
            $sdecqty += $decqty;

            $subjanqty += $janqty;
            $subfebqty += $febqty;
            $submarqty += $marqty;
            $subaprqty += $aprqty;
            $submayqty += $mayqty;
            $subjuneqty += $juneqty;
            $subjulqty += $julqty;
            $subaugqty += $augqty;
            $subseptqty += $septqty;
            $suboctqty += $octqty;
            $subnovqty += $novqty;
            $subdecqty += $decqty;

            $gjanqty += $janqty;
            $gfebqty += $febqty;
            $gmarqty += $marqty;
            $gaprqty += $aprqty;
            $gmayqty += $mayqty;
            $gjuneqty += $juneqty;
            $gjulqty += $julqty;
            $gaugqty += $augqty;
            $gseptqty += $septqty;
            $goctqty += $octqty;
            $gnovqty += $novqty;
            $gdecqty += $decqty;
        } //end for eachs

        // Last part for sub total
        //
        $str .= $this->reporter->begintable('1600');
        //
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->agentname . ' SUB-TOTAL', '150', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '140', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($sjanqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($sfebqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($smarqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($saprqty, $decimal_qty), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($smayqty, $decimal_qty), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($sjuneqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($sjulqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($saugqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($sseptqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($soctqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($snovqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($sdecqty, $decimal_qty), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        //
        $str .= $this->reporter->endtable();
        //

        //
        $str .= $this->reporter->begintable('1600');
        //
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($supplier . ' GRAND-TOTAL', '640', null, false, '1px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subjanqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subfebqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($submarqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subaprqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($submayqty, 2), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subjuneqty, 2), '80', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subjulqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subaugqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subseptqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($suboctqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subnovqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($subdecqty, 2), '80', null, false, '1px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        //
        $str .= $this->reporter->endtable();
        //


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        // GRAND TOTAL


        //
        $str .= $this->reporter->begintable('1600');
        //
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND-TOTAL', '150', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gjanqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gfebqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gmarqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gaprqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gmayqty, 2), '80', null, false, '2px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gjuneqty, 2), '80', null, false, '2px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gjulqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gaugqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gseptqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($goctqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gnovqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(number_format($gdecqty, 2), '80', null, false, '2px solid ', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        //
        $str .= $this->reporter->endtable();
        //
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    } //end fn
}//end class