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
use DateTime;

class so_and_ro_monitoring_daily_update
{
    public $modulename = 'SO and RO Monitoring Daily Update';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1200'];

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
        adddate(left(now(),10),-360) as start,   left(now(),10) as end,'0' as reporttype");
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
            case '0': //summarized 
                return $this->reportDefaultLayout_summarized($config);
                break;
            case '1':
                return $this->reportDefaultLayout($config);
                break;
        }
    }

    public function reportDefault($config)
    {
        // QUERY

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': //summarized
                // $query = "
                //     select date(head.dateid) as datenow,head.docno as sodocno,
                //     (select group_concat(distinct t.docno separator ', ')
                //     from (select  ro.docno,hso.trno
                //     from hrohead as ro
                //     left join hrostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex

                //     union

                //     select ro.docno,hso.trno
                //     from rohead as ro
                //     left join rostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex ) as t where t.trno=head.trno) as rodocno,


                //     (select group_concat(distinct t.loaddate separator ', ')
                //    from (select  date(info.loaddate) as loaddate,hso.trno
                //     from hheadinfotrans as info
                //     left join hrohead as ro on ro.trno=info.trno
                //     left join hrostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex

                //     union

                //     select date(info.loaddate) as loaddate,hso.trno
                //     from headinfotrans as info
                //     left join rohead as ro on ro.trno=info.trno
                //     left join rostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex ) as t where t.trno=head.trno) as loaddate,
                //     head.rem as remarks,head.lockdate,cl.clientname as customername,ifnull(num.postdate,'') as postdate,
                //     if((select max(s.void) from hsostock as s where s.trno = head.trno) = 1,'Yes', '') as void

                //     from hsohead as head
                //     left join client as cl on cl.client=head.client
                //     left join transnum as num on num.trno=head.trno
                //     where date(head.dateid) between '$start' and '$end'";
                // break;
                //if(max(hso.void) = 1,'Yes', '') as void
                $query = "select date(hsoh.dateid) as datenow,hsoh.docno as sodocno, ro.docno as rodocno,date(info.loaddate) as loaddate,
                        hsoh.rem as remarks,hsoh.lockdate,cl.clientname as customername,num.postdate,
                        if((select max(s.void) from hsostock as s where s.trno = hsoh.trno) = 1,'Yes', '') as void

                        from rohead as ro
                        left join rostock rs on rs.trno = ro.trno
                        left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex
                        left join headinfotrans as info on info.trno=ro.trno
                        left join hsohead as hsoh on hsoh.trno=hso.trno
                        left join client as cl on cl.client=hsoh.client
                        left join transnum as num on num.trno=hsoh.trno
                        where hsoh.dateid is not null and date(hsoh.dateid) between '$start' and '$end'
                        group by hsoh.dateid,hsoh.docno, ro.docno,info.loaddate,
                        hsoh.rem,hsoh.lockdate,cl.clientname,num.postdate,hsoh.trno

                        union all

                        select date(hsoh.dateid) as datenow,hsoh.docno as sodocno, ro.docno as rodocno,date(info.loaddate) as loaddate,
                        hsoh.rem as remarks,hsoh.lockdate,cl.clientname as customername,num.postdate,
                        if((select max(s.void) from hsostock as s where s.trno = hsoh.trno) = 1,'Yes', '') as void

                        from hrohead as ro
                        left join hrostock rs on rs.trno = ro.trno
                        left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex
                        left join hheadinfotrans as info on info.trno=ro.trno
                        left join hsohead as hsoh on hsoh.trno=hso.trno 
                        left join client as cl on cl.client=hsoh.client
                        left join transnum as num on num.trno=hsoh.trno
                        where hsoh.dateid is not null  and date(hsoh.dateid) between '$start' and '$end'
                        group by hsoh.dateid,hsoh.docno, ro.docno,info.loaddate,
                        hsoh.rem,hsoh.lockdate,cl.clientname,num.postdate,hsoh.trno
                        order by sodocno,datenow";
                break;
            case '1': //detail
                // $query = "
                //     select date(head.dateid) as datenow,head.docno as sodocno,
                //     (select group_concat(distinct t.docno separator ', ')
                //     from (select  ro.docno,hso.trno
                //     from hrohead as ro
                //     left join hrostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex

                //     union

                //     select ro.docno,hso.trno
                //     from rohead as ro
                //     left join rostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex ) as t where t.trno=head.trno) as rodocno,


                //     (select group_concat(distinct t.loaddate separator ', ')
                //    from (select  date(info.loaddate) as loaddate,hso.trno
                //     from hheadinfotrans as info
                //     left join hrohead as ro on ro.trno=info.trno
                //     left join hrostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex

                //     union

                //     select date(info.loaddate) as loaddate,hso.trno
                //     from headinfotrans as info
                //     left join rohead as ro on ro.trno=info.trno
                //     left join rostock rs on rs.trno = ro.trno
                //     left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex ) as t where t.trno=head.trno) as loaddate,
                //     head.rem as remarks,head.lockdate,cl.clientname as customername,cl.addr,agent.clientname as agentname,
                //     ifnull(num.postdate,'') as postdate,
                //     if((select max(s.void) from hsostock as s where s.trno = head.trno) = 1,'Yes', '') as void

                //     from hsohead as head
                //     left join client as cl on cl.client=head.client
                //     left join transnum as num on num.trno=head.trno
                //     left join client as agent on agent.client=head.agent
                //     where date(head.dateid) between '$start' and '$end'";
                $query = "select date(hsoh.dateid) as datenow,hsoh.docno as sodocno, ro.docno as rodocno,info.loaddate as loaddate,
                        hsoh.rem as remarks,hsoh.lockdate,cl.clientname as customername,num.postdate,
                        if((select max(s.void) from hsostock as s where s.trno = hsoh.trno) = 1,'Yes', '') as void,
                        cl.addr,agent.clientname as agentname

                        from rohead as ro
                        left join rostock rs on rs.trno = ro.trno
                        left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex
                        left join headinfotrans as info on info.trno=ro.trno
                        left join hsohead as hsoh on hsoh.trno=hso.trno 
                        left join client as cl on cl.client=hsoh.client
                        left join transnum as num on num.trno=hsoh.trno
                        left join client as agent on agent.client=hsoh.agent
                        where hsoh.dateid is not null and date(hsoh.dateid) between '$start' and '$end'
                        group by hsoh.dateid,hsoh.docno, ro.docno,info.loaddate,
                        hsoh.rem,hsoh.lockdate,cl.clientname,num.postdate,hsoh.trno,cl.addr,agent.clientname

                        union all

                        select date(hsoh.dateid) as datenow,hsoh.docno as sodocno, ro.docno as rodocno,info.loaddate as loaddate,
                        hsoh.rem as remarks,hsoh.lockdate,cl.clientname as customername,num.postdate,
                        if((select max(s.void) from hsostock as s where s.trno = hsoh.trno) = 1,'Yes', '') as void,
                        cl.addr,agent.clientname as agentname

                        from hrohead as ro
                        left join hrostock rs on rs.trno = ro.trno
                        left join hsostock hso on hso.trno = rs.refx and hso.line = rs.linex
                        left join hheadinfotrans as info on info.trno=ro.trno
                        left join hsohead as hsoh on hsoh.trno=hso.trno
                        left join client as cl on cl.client=hsoh.client
                        left join transnum as num on num.trno=hsoh.trno
                        left join client as agent on agent.client=hsoh.agent
                        where hsoh.dateid is not null  and date(hsoh.dateid) between '$start' and '$end'
                        group by hsoh.dateid,hsoh.docno, ro.docno,info.loaddate,
                        hsoh.rem,hsoh.lockdate,cl.clientname,num.postdate,hsoh.trno,cl.addr,agent.clientname
                        order by sodocno,datenow";
                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1050';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        // $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SO and RO Monitoring Daily Update', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Date: ', '50', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($start . ' - ' . $end, '1150', null, false, $border, '', 'L', $font, $font_size, 'B', '', ''); //150
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('DATE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('SO NUMBER', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('DATE OF APPROVAL', '150', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('TIME OF APPROVAL', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('RO NUMBER', '200', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('LOADING DATE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('REMARKS', '300', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '90', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('SO NUMBER', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ADDRESS', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AGENT NAME', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('DATE OF APPROVAL', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TIME OF APPROVAL', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('RO NUMBER', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ACTUAL LOADING DATE', '90', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DIFFERENCE', '50', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('VOID', '20', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = 'calibri';
        $font_size = '9';
        $count = 25;
        $page = 25;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1050';
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->displayHeader($config);
        // $str .= $this->reporter->begintable($layoutsize);

        foreach ($result as $key => $data) {
            $app = new DateTime($data->lockdate);
            // $approvedate = $app->format('F j, Y'); //January 1, 2025
            $approvedate = $app->format('Y-m-d'); //January 1, 2025
            $hours = $app->format('g:i:s a'); //  8:31:21 pm

            if ($data->loaddate != null) {
                // $appr = date_create($data->postdate);
                // $load = date_create($data->loaddate);
                $appr = date_create($data->postdate)->setTime(0, 0, 0);
                $load = date_create($data->loaddate);

                $interval = date_diff($appr, $load);
                if ($interval->days == 1) {
                    $days = $interval->days . ' day';
                } elseif ($interval->days > 1) {
                    $days = $interval->days . ' days';
                } else {
                    $days = $interval->days;
                }

                $loadhere = new DateTime($data->loaddate);
                $loaddates = $loadhere->format('Y-m-d');
            } else {
                $days = 0;
                $loaddates = null;
            }


            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            // $str .= $this->reporter->col($data->datenow, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            // $str .= $this->reporter->col($data->sodocno, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            // $str .= $this->reporter->col($approvedate, '150', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            // $str .= $this->reporter->col($hours, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            // $str .= $this->reporter->col($data->rodocno, '200', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            // $str .= $this->reporter->col($data->loaddate, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            // $str .= $this->reporter->col($data->remarks, '300', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            // $str .= $this->reporter->endrow();
            $str .= $this->reporter->col($data->datenow, '90', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->sodocno, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col('&nbsp' . $data->customername, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->addr, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->agentname, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($approvedate, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($hours, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->rodocno, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($loaddates, '90', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($days, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->remarks, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->void, '20', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');


            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->displayHeader($config, $layoutsize);
            //     $page += $count;
            // }
        } //end foreach

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }


    private function displayHeader_summ($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1050';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        // $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SO and RO Monitoring Daily Update', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Date: ', '50', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($start . ' - ' . $end, '1150', null, false, $border, '', 'L', $font, $font_size, 'B', '', ''); //150
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('SO NUMBER', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DATE OF APPROVAL', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TIME OF APPROVAL', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('RO NUMBER', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ACTUAL LOADING DATE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('STANDARD LOADING DATE DIFFERENCE', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', '230', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('VOID', '20', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }


    public function reportDefaultLayout_summarized($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = 'calibri';
        $font_size = '10';
        $count = 25;
        $page = 25;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1050';
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->displayHeader_summ($config);

        foreach ($result as $key => $data) {
            // $app = new DateTime($data->lockdate);
            $app = new DateTime($data->postdate); //approve date
            // $approvedate = $app->format('F j, Y'); //January 1, 2025
            $approvedate = $app->format('Y-m-d'); //January 1, 2025
            $hours = $app->format('g:i:s a'); //  8:31:21 pm


            if ($data->loaddate != null) {
                $appr = date_create($data->postdate)->setTime(0, 0, 0);
                $load = date_create($data->loaddate);

                $interval = date_diff($appr, $load);
                if ($interval->days == 1) {
                    $days = $interval->days . ' day';
                } elseif ($interval->days > 1) {
                    $days = $interval->days . ' days';
                } else {
                    $days = $interval->days;
                }

                $loadhere = new DateTime($data->loaddate);
                $loaddates = $loadhere->format('Y-m-d');
            } else {
                $days = 0;
                $loaddates = null;
            }

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->datenow, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->sodocno, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col('&nbsp' . $data->customername, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($approvedate, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($hours, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->rodocno, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($loaddates, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($days, '100', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->remarks, '230', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->void, '20', '', false, $border, '', 'CT', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->endrow();
            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->displayHeader($config, $layoutsize);
            //     $page += $count;
            // }
        } //end foreach

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class