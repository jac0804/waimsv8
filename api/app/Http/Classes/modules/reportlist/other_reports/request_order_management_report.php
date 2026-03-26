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

class request_order_management_report
{
    public $modulename = 'Request Order Management Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'start', 'end', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,   left(now(),10) as end,
        '0' as reporttype");
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
            case '0': // summary
                $str = $this->summ_reportDefaultLayout($config);
                break;
            case '1': //detailed
                $str = $this->detailed_reportDefaultLayout($config);
                break;
        }

        return $str;
    }

    public function reportDefault($config)
    {
        // QUERY

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $reporttype    = $config['params']['dataparams']['reporttype'];

        $filter   = '';


        //    ( select count(*) from (
        //     select distinct la.docno, hrs.trno, hs.trno as hstrno,ls.itemid
        //     from lahead la
        //     join lastock ls  on ls.trno = la.trno
        //     join hsostock hs on hs.trno = ls.refx and hs.line = ls.linex
        //     join rostock hrs on hrs.refx = hs.trno and hrs.linex = hs.line
        //     where la.doc = 'sj'

        //     union all

        //     select distinct la.docno, hrs.trno, hs.trno as hstrno,ls.itemid
        //     from glhead la
        //     join glstock ls  on ls.trno = la.trno
        //     join hsostock hs on hs.trno = ls.refx and hs.line = ls.linex
        //     join hrostock hrs on hrs.refx = hs.trno and hrs.linex = hs.line
        //     where la.doc = 'sj') u where u.trno = head.trno and u.hstrno=so.trno and u.itemid=i.itemid) as drcount,

        //   ( select count(*) from (
        //     select distinct la.docno, hrs.trno, hs.trno as hstrno,ls.itemid
        //     from lahead la
        //     join lastock ls  on ls.trno = la.trno
        //     join hsostock hs on hs.trno = ls.refx and hs.line = ls.linex
        //     join hrostock hrs on hrs.refx = hs.trno and hrs.linex = hs.line
        //     where la.doc = 'sj'

        //     union all

        //     select distinct la.docno, hrs.trno, hs.trno as hstrno,ls.itemid
        //     from glhead la
        //     join glstock ls  on ls.trno = la.trno
        //     join hsostock hs on hs.trno = ls.refx and hs.line = ls.linex
        //     join hrostock hrs on hrs.refx = hs.trno and hrs.linex = hs.line
        //     where la.doc = 'sj') u where u.trno = head.trno and u.hstrno=so.trno and u.itemid=i.itemid) as drcount,


        switch ($reporttype) {
            // case '1': //detailed // ito yung walang count
            // $query = "select datenow,docno,barcode,itemname,isqty,uom,customer,addr,area,agentt,
            //     sum(totalton) as totalton, sum(totaldiesel) as totaldiesel,sum(amount) as amount,sotrno,soline,itemid
            //     from (
            // select date(head.dateid) as datenow, head.docno, i.barcode, i.itemname,stock.isqty,
            //     stock.uom,
            //     cl.clientname as customer, cl.addr,cl.area, agent.clientname as agentt,
            //     sum(st.weight * st.isqty) as totalton,
            //     sum(roso.diesel) as totaldiesel, so.trno as sotrno,st.line as soline, i.itemid,

            //     (select sum(rs.isamt * rs.isqty) as amt from hrostock as rs where rs.trno=head.trno and rs.refx=so.trno and rs.itemid=i.itemid ) as amount
            //         from rohead as head
            // left join rostock as stock on stock.trno=head.trno
            // left join item  as i on i.itemid=stock.itemid
            // left join hsostock as st on st.trno=stock.refx and st.line=stock.linex
            // left join hsohead as so on so.trno=st.trno
            // left join roso on roso.trno=head.trno and roso.sotrno=so.trno
            // left join client as cl on cl.client=so.client
            // left join client as agent on agent.client=so.agent where date(head.dateid) between '$start' and '$end' $filter and stock.isqty is not null
            // group by head.dateid, head.docno, i.barcode, i.itemname,stock.isqty,
            //     stock.uom,cl.clientname, cl.addr,cl.area, agent.clientname,head.trno,so.trno,i.itemid,st.line
            // union all
            // select date(head.dateid) as datenow, head.docno, i.barcode, i.itemname,stock.isqty,
            //     stock.uom,
            //     cl.clientname as customer, cl.addr,cl.area, agent.clientname as agentt,
            //     sum(st.weight * st.isqty) as totalton,
            //     sum(roso.diesel) as totaldiesel, so.trno as sotrno,st.line as soline, i.itemid,
            //     (select sum(rs.isamt * rs.isqty) as amt from hrostock as rs where rs.trno=head.trno and rs.refx=so.trno and rs.itemid=i.itemid ) as amount

            //         from hrohead as head
            // left join hrostock as stock on stock.trno=head.trno
            // left join item  as i on i.itemid=stock.itemid
            // left join hsostock as st on st.trno=stock.refx and st.line=stock.linex
            // left join hsohead as so on so.trno=st.trno
            // left join roso on roso.trno=head.trno and roso.sotrno=so.trno
            // left join client as cl on cl.client=so.client
            // left join client as agent on agent.client=so.agent  where date(head.dateid) between '$start' and '$end'  $filter and stock.isqty is not null
            // group by head.dateid, head.docno, i.barcode, i.itemname,stock.isqty,
            //     stock.uom,cl.clientname, cl.addr,cl.area, agent.clientname,head.trno,so.trno,i.itemid,st.line) as s 
            // group by datenow,docno,barcode,itemname,isqty,uom,customer,addr,area,agentt,sotrno,soline,itemid order by docno ";
            // break;
            case '1': //detailed
                $query = "select datenow,docno,barcode,itemname,isqty,uom,customer,addr,area,agentt,
                    sum(totalton) as totalton, sum(totaldiesel) as totaldiesel,sum(amount) as amount,drcount,terms,loaddate,sum(distance) as distance
                    from (
                select date(head.dateid) as datenow, head.docno, i.barcode, i.itemname,stock.isqty,
                    stock.uom,
                    cl.clientname as customer, cl.addr,cl.area, agent.clientname as agentt,
                    sum(st.weight * st.isqty) as totalton,
                    sum(roso.diesel) as totaldiesel,
                       
                    (select sum(rs.isamt * rs.isqty) as amt from rostock as rs where rs.trno=head.trno and rs.refx=so.trno and rs.itemid=i.itemid ) as amount,
                       
                    0 as drcount,so.terms,date(info.loaddate) as loaddate,sum(roso.distance) as distance

                        from rohead as head
                left join rostock as stock on stock.trno=head.trno
                left join item  as i on i.itemid=stock.itemid
                left join hsostock as st on st.trno=stock.refx and st.line=stock.linex
                left join hsohead as so on so.trno=st.trno
                left join roso on roso.trno=head.trno and roso.sotrno=so.trno
                left join client as cl on cl.client=so.client
                left join headinfotrans as info on info.trno=head.trno  
                left join client as agent on agent.client=so.agent where date(head.dateid) between '$start' and '$end' $filter and stock.isqty is not null
                group by head.dateid, head.docno, i.barcode, i.itemname,stock.isqty,
                    stock.uom,cl.clientname, cl.addr,cl.area, agent.clientname,head.trno,so.trno,i.itemid,so.terms,info.loaddate
                union all
                select date(head.dateid) as datenow, head.docno, i.barcode, i.itemname,stock.isqty,
                    stock.uom,
                    cl.clientname as customer, cl.addr,cl.area, agent.clientname as agentt,
                    sum(st.weight * st.isqty) as totalton,
                    sum(roso.diesel) as totaldiesel,
                    (select sum(rs.isamt * rs.isqty) as amt from hrostock as rs where rs.trno=head.trno and rs.refx=so.trno and rs.itemid=i.itemid ) as amount,
                        0 as drcount,so.terms,date(info.loaddate) as loaddate,sum(roso.distance) as distance
                     

                        from hrohead as head
                left join hrostock as stock on stock.trno=head.trno
                left join item  as i on i.itemid=stock.itemid
                left join hsostock as st on st.trno=stock.refx and st.line=stock.linex
                left join hsohead as so on so.trno=st.trno
                left join roso on roso.trno=head.trno and roso.sotrno=so.trno
                left join client as cl on cl.client=so.client
                left join hheadinfotrans as info on info.trno=head.trno  
                left join client as agent on agent.client=so.agent  where date(head.dateid) between '$start' and '$end'  $filter and stock.isqty is not null
                group by head.dateid, head.docno, i.barcode, i.itemname,stock.isqty,
                    stock.uom,cl.clientname, cl.addr,cl.area, agent.clientname,head.trno,so.trno,i.itemid,so.terms,info.loaddate) as s 
                group by datenow,docno,barcode,itemname,isqty,uom,customer,addr,area,agentt,drcount,terms,loaddate order by docno,customer,terms ";

                break;
            case 0: // summarized
                $query = "select datenow, docno,area,sum(tonnage) as tonnage, sum(diesel) as diesel,trno,drcount,sum(amount) as amount,loaddate,sum(distance) as distance from (
                        select date(head.dateid) as datenow, head.docno,head.trno,

                        (select sum(sostock.weight * sostock.isqty) from rostock as ro
                        left join hsostock as sostock on sostock.trno=ro.refx and sostock.line=ro.linex
                        where sostock.trno=so.trno  and ro.trno=head.trno group by sostock.trno) as tonnage,cl.area, sum(roso.diesel) as diesel,
                                0 as drcount ,
                                     (select sum(rs.isamt * rs.isqty) as amt from rostock as rs where rs.trno=head.trno and rs.refx=so.trno ) as amount,
                                      date(info.loaddate) as loaddate,sum(roso.distance) as distance                                              
                                        from rohead as head
                                        left join roso on roso.trno=head.trno
                                        left join hsohead as so on so.trno=roso.sotrno
                                        left join client as cl on cl.client=so.client
                                        left join headinfotrans as info on info.trno=head.trno  
                                        where date(head.dateid) between  '$start' and '$end'  $filter
                                        group by head.dateid, head.docno,so.trno,cl.area,head.trno,info.loaddate
                        union all
                        select date(head.dateid) as datenow, head.docno,head.trno,
                        (select sum(sostock.weight * sostock.isqty) from hrostock as ro
                        left join hsostock as sostock on sostock.trno=ro.refx and sostock.line=ro.linex
                        where sostock.trno=so.trno and ro.trno=head.trno  group by sostock.trno) as tonnage,cl.area, sum(roso.diesel) as diesel,
                            0 as drcount,
                                     (select sum(rs.isamt * rs.isqty) as amt from hrostock as rs where rs.trno=head.trno and rs.refx=so.trno ) as amount,
                                       date(info.loaddate) as loaddate,sum(roso.distance) as distance  
                                                from hrohead as head
                                        left join roso on roso.trno=head.trno
                                        left join hsohead as so on so.trno=roso.sotrno
                                        left join client as cl on cl.client=so.client
                                        left join hheadinfotrans as info on info.trno=head.trno  
                                        where date(head.dateid) between  '$start' and '$end'  $filter
                                        group by head.dateid, head.docno,so.trno,cl.area,head.trno,info.loaddate) as x group by datenow, docno,area,trno,drcount,loaddate  order by datenow,docno";
                break;
        }
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    private function detailed_displayHeader($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '9';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1200';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Detailed Request Order Management Report', null, null, false, '10px solid ', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Date: ', '50', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($start . ' - ' . $end, '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', ''); //150
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '90', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NUMBER', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TRIP NUMBER', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('LOAD DATE', '90', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('BARCODE', '70', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('UOM', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '80', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ADDRESS', '150', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AREA', '70', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AGENT', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL DISTANCE', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL TONNAGE / WEIGHT', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', ''); //80
        $str .= $this->reporter->col('TOTAL DR NUMBER', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('TOTAL DIESEL LOADED', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', ''); //80
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }


    public function detailed_reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '9';
        $count = 5;
        $page = 5;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1200';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->detailed_displayHeader($config);
        $totalton = 0;
        $totaldiesl = 0;
        $totaldistance = 0;

        $current_date = null;
        $seq = 0;
        $current_docno = '';
        $totalamt = 0;

        $printedKeys = [];
        $seenPairs = [];
        $termCounts = [];

        foreach ($result as $key => $data) {
            if ($current_date != $data->datenow) {
                $current_date  = $data->datenow;
                $current_docno = '';
                $seq = 0;
                $printedKeys = []; // reset per date
                $seenPairs = [];
                $termCounts = [];
            }

            $tripToPrint   = '';
            $dieselToPrint = '';
            $distanceToPrint = '';

            if ($current_docno != $data->docno) {
                $current_docno = $data->docno;
                $seq++;
                $tripToPrint = $seq;
            }

            // unique key para isang beses lang iprint
            $currentKey = $data->docno . '|' . $data->customer . '|' . $data->agentt . '|' . $data->addr . '|' . $data->area;

            if (!in_array($currentKey, $printedKeys)) {
                $dieselToPrint = number_format($data->totaldiesel, 2);
                $distanceToPrint = number_format($data->distance, 2);
                $printedKeys[] = $currentKey; // mark as printed
            }

            $pairKey = $data->terms . '|' . $data->customer;
            // check kung nakita na ba natin ito
            if (!isset($seenPairs[$pairKey])) {
                // counter ng terms pag wala pa 0 magsisimula
                if (!isset($termCounts[$data->terms])) {
                    $termCounts[$data->terms] = 0;
                }
                // increment
                $termCounts[$data->terms]++;
                //mark tru pag nakita na 
                $seenPairs[$pairKey] = true;
                // ilabas ang bilang (1, 2, 3...)
                $clcountToPrint = $termCounts[$data->terms];
            } else {
                // kung may same
                //  gawin blank lang
                $clcountToPrint = '';
            }

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->datenow, '90', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->docno, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($tripToPrint, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '', ''); //tripToPrint
            $str .= $this->reporter->col($data->loaddate, '90', '', false, $border, '', 'CT', $font, $font_size, '', '', '', ''); //tripToPrint
            $str .= $this->reporter->col($data->barcode, '70', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->itemname, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->isqty, 2), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->uom, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->customer, '80', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->addr, '150', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->area, '70', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->agentt, '50', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($distanceToPrint, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->totalton, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '', '');

            $str .= $this->reporter->col($clcountToPrint, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '', '');

            $str .= $this->reporter->col($dieselToPrint, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '', ''); //dieselToPrint
            $totalton += $data->totalton;
            $totaldiesl += $dieselToPrint;
            $totalamt += $data->amount;
            $totaldistance += $distanceToPrint;
            $str .= $this->reporter->endrow();
            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->detailed_displayHeader($config, $layoutsize);
            //     $page += $count;
            // }
        } //end foreach
        // $str .= $this->reporter->endtable();
        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL: ', '90', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaldistance, 2), '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalton, 2), '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, 2), '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaldiesl, 2), '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }



    private function summ_displayHeader($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Summarized Request Order Management Report', null, null, false, '10px solid ', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Date: ', '50', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($start . ' - ' . $end, '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', ''); //150
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '80', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT NUMBER', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TRIP NUMBER', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('LOAD DATE', '80', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AREA', '210', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DISTANCE', '80', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL TONNAGE / WEIGHT', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL NUMBER OF DR', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DIESEL LOADED', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    public function summ_reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $count = 55;
        $page = 55;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->summ_displayHeader($config);
        $totalton = 0;
        $totaldiesl = 0;
        $totalamount = 0;
        $totaldistance = 0;

        // variables para sa sequence
        $current_date = null;
        $seq = 0;
        $lastDocno = '';
        $drnoCache = [];

        foreach ($result as $key => $data) {

            if ($current_date != $data->datenow) {
                $current_date = $data->datenow;
                $seq = 1; // reset sa 1
            } else {
                $seq++; // dagdag kung same date
            }

            $drnoToPrint = '';
            if ($lastDocno != $data->docno) {
                $lastDocno = $data->docno;

                // kung wala pa sa cache, i-query
                if (!isset($drnoCache[$data->docno])) {
                    $drnoSql = "select count(distinct concat(cl.client, '-', so.terms)) as drno
                        from rohead as head
                        left join roso on roso.trno=head.trno
                        left join hsohead as so on so.trno=roso.sotrno
                        left join client as cl on cl.client=so.client 
                        where head.trno= $data->trno
                        union all
                        select count(distinct concat(cl.client, '-', so.terms)) as drno
                        from hrohead as head
                        left join roso on roso.trno=head.trno
                        left join hsohead as so on so.trno=roso.sotrno
                        left join client as cl on cl.client=so.client 
                        where head.trno= $data->trno
                        order by drno desc";

                    $drnoRows = $this->coreFunctions->opentable($drnoSql);
                    $count = 0;
                    foreach ($drnoRows as $row) {
                        $count += $row->drno;  // total count
                    }

                    $drnoCache[$data->docno] = $count;  // i-cache
                }
                $drnoToPrint = $drnoCache[$data->docno];  // ipakita lamng sa unang row
            }

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col($data->datenow, '80', '', false, $border, '', 'C', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->docno, '100', '', false, $border, '', 'L', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($seq, '50', '', false, $border, '', 'C', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->loaddate, '80', '', false, $border, '', 'C', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->area, '210', '', false, $border, '', 'L', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->distance, '80', '', false, $border, '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->tonnage, 2), '100', '', false, $border, '', 'R', $font, $font_size, '', '', '', '');

            $str .= $this->reporter->col($drnoToPrint, '100', '', false, $border, '', 'C', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '100', '', false, $border, '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->diesel, 2), '100', '', false, $border, '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->endrow();
            $totalton += $data->tonnage;
            $totaldiesl += $data->diesel;
            $totalamount += $data->amount;
            $totaldistance += $data->distance;

            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->summ_displayHeader($config, $layoutsize);
            //     $page += $count;
            // }
        } //end foreach
        // $str .= $this->reporter->endtable();
        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL: ', '80', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '50', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '210', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaldistance, 2), '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalton, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' ', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamount, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaldiesl, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class