<?php

namespace App\Http\Classes\modules\reportlist\student;

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

class current_student_receivables_aging
{
    public $modulename = 'Current Student Receivables Aging';
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
        $fields = ['radioprint'];
        array_push($fields, 'ehstudentlookup', 'dcentername', 'contra');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'contra.lookupclass', 'AR');
        data_set($col1, 'ehstudentlookup.label', 'Student');
        $fields = ['radioposttype', 'radioreporttype'];

        $col2 = $this->fieldClass->create($fields);
        data_set(
            $col2,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr = "select 
                    'default' as print,
                     '' as clientname,'' as ehstudentlookup, '0' as clientid,
                    '0' as posttype,
                    '0' as reporttype,
                    '' as contra,'' as acnoname,'0' as acnoid,
                    '" . $defaultcenter[0]['center'] . "' as center,
                    '" . $defaultcenter[0]['centername'] . "' as centername,
                    '" . $defaultcenter[0]['dcentername'] . "' as dcentername
                    ";

        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportDefault($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config, $result)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $reporttype   = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': // SUMMARIZED
                $data = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result);
                break;
            case '1': // DETAILED
                $data = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result);
                break;
        }

        return $data;
    }

    public function reportDefault($config)
    {
        // QUERY
        $posttype = $config['params']['dataparams']['posttype'];
        switch ($posttype) {
            case '0': // POSTED
                $query = $this->reportDefault_QUERY_POSTED($config); // POSTED
                break;
            case '1': // UNPOSTED
                $query = $this->reportDefault_QUERY_UNPOSTED($config); // POSTED
                break;
            case '2': // ALL
                $query = $this->reportDefault_QUERY_all($config); //ALL
                break;
        }
        $result = $this->coreFunctions->opentable($query);
        return $this->reportplotting($config, $result);
    }

    public function reportDefault_QUERY_POSTED($config)
    {
        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $contra       = $config['params']['dataparams']['contra'];
        $acnoname       = $config['params']['dataparams']['acnoname'];
        $acnoid       = $config['params']['dataparams']['acnoid'];

        $filter = "";
        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }

        if ($filtercenter != "") {
            $filter .= " and cntnum.center='$filtercenter'";
        }

        if ($contra != '') {
            $filter .= " and coa.acnoid='$acnoid'";
        }

        $elapsedate = 'detail.dateid';

        switch ($reporttype) {
            case '1': // DETAILED
                $query = "select tr,trno,doc,ref,clientname,dateid,docno,yourref, name, sum(balance) as balance,elapse,deldate
                            from (select 'p' as tr,head.trno,head.doc,detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
                            date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
                            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,head.yourref,date(head.deldate) as deldate
                            from (arledger as detail 
                            left join client on client.clientid=detail.clientid)
                            left join cntnum on cntnum.trno=detail.trno
                            left join glhead as head on head.trno=detail.trno
                            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                            left join coa on coa.acnoid=gdetail.acnoid
                            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter ) as x
                            group by tr, clientname, dateid,docno,yourref,name,elapse,trno,doc,ref,deldate
                            order by tr, clientname";

                break;
            case '0': // SUMMARIZED
                $query = "select tr, (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, 
                                     sum(balance) as balance,elapse 
                        from (select 'p' as tr, client.clientname, 
                                    date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
                                    (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
                            from (arledger as detail left join client on client.clientid=detail.clientid)
                            left join cntnum on cntnum.trno=detail.trno
                            left join glhead as head on head.trno=detail.trno
                            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                            left join coa on coa.acnoid=gdetail.acnoid
                            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter
                            union all
                            select 'z' as tr, '' as clientname, '' as dateid, '' as docno, 
                                   '' as elapse, '' as balance ) as x
                        group by tr, clientname, elapse 
                        order by tr, clientname";

                break;
        } //end switch
        return $query;
    }

    public function reportDefault_QUERY_UNPOSTED($config)
    {
        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $contra       = $config['params']['dataparams']['contra'];
        $acnoname       = $config['params']['dataparams']['acnoname'];
        $acnoid       = $config['params']['dataparams']['acnoid'];

        $filter = "";

        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }

        if ($filtercenter != "") {
            $filter .= " and cntnum.center='$filtercenter'";
        }

        if ($contra != '') {
            $filter .= " and coa.acnoid='$acnoid'";
        }


        $elapsedate = 'head.dateid';

        switch ($reporttype) {
            case '1': // DETAILED
                $ref1 = "detail.ref";
                $ref2 = " '' as ref";
                $query = "select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
                            date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
                            detail.db as balance,head.yourref,date(head.deldate) as deldate
                            from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                            left join client on client.client=head.client)
                            left join coa on coa.acnoid=detail.acnoid)
                            left join cntnum on cntnum.trno=head.trno
                            where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter 
                            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate 
                            union all
                            select cntnum.center, 'u' as tr,head.trno,head.doc, $ref2,client.clientname, ifnull(client.clientname,'no name') as name,
                            date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
                            sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate
                            from lahead as head left join lastock as stock on stock.trno=head.trno
                            left join ladetail as detail on detail.trno=head.trno
                            left join client on client.client=head.client
                            left join coa on coa.acnoid=detail.acnoid
                            left join cntnum on cntnum.trno=head.trno
                            where cntnum.doc IN ('SJ','MJ','CM')  $filter
                            group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate 
                            order by clientname, dateid, docno";
                break;

            case '0': // SUMMARIZED
                $query = "select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, 
                                sum(balance) as balance,elapse 
                        from (select 'u' as tr, client.clientname, 
                                    date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                                    detail.db as balance 
                            from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                            left join client on client.client=head.client)
                            left join coa on coa.acnoid=detail.acnoid)
                            left join cntnum on cntnum.trno=head.trno
                            where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter 
                            group by client.clientname, head.dateid, head.docno, detail.db 
                            ) as t
                            group by clientname, elapse 
                            union all
                            select (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, 
                                    sum(balance) as balance,elapse 
                            from (select 'u' as tr, client.clientname,date(head.dateid) as dateid, 
                                        head.docno, datediff(now(), head.dateid) as elapse,
                                        sum(stock.ext) as balance
                                from lahead as head left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                where cntnum.doc IN ('SJ','MJ','CM')  $filter
                                group by cntnum.center, client.clientname, head.dateid, head.docno) as x
                            group by clientname, elapse
                            union all
                            select 'z' as clientname,'' as balance, '' as elapse 
                            order by clientname";
                break;
        } //end swicth

        return $query;
    }

    public function reportDefault_QUERY_all($config)
    {
        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $contra       = $config['params']['dataparams']['contra'];
        $acnoname       = $config['params']['dataparams']['acnoname'];
        $acnoid       = $config['params']['dataparams']['acnoid'];

        $filter = "";
        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }

        if ($filtercenter != "") {
            $filter .= " and cntnum.center='$filtercenter'";
        }

        if ($contra != '') {
            $filter .= " and coa.acnoid='$acnoid'";
        }

        $elapsedate = 'head.dateid';

        switch ($reporttype) {
            case '1': // DETAILED
                $ref1 = "detail.ref";
                $ref2 = " '' as ref";
                $query = "select ref,clientname,dateid,docno, name, sum(balance) as balance,elapse
                            from (
                            select cntnum.center, 'p' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
                            date(detail.dateid) as dateid, detail.docno,  datediff(now(), $elapsedate) as elapse,
                            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
                            head.yourref,date(head.deldate) as deldate
                            from (arledger as detail 
                            left join client on client.clientid=detail.clientid)
                            left join cntnum on cntnum.trno=detail.trno
                            left join glhead as head on head.trno=detail.trno
                            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                            left join coa on coa.acnoid=gdetail.acnoid
                            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now() $filter
                        union all
                        select cntnum.center, 'u' as tr,head.trno,head.doc,$ref1, client.clientname, ifnull(client.clientname,'no name') as name,
                                date(head.dateid) as dateid, head.docno, datediff(now(), $elapsedate) as elapse,
                                detail.db as balance,head.yourref,date(head.deldate) as deldate
                                from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                                left join client on client.client=head.client)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter 
                                group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,detail.db,head.trno,head.doc,detail.ref,head.deldate 
                                
                                union all
                                
                                select cntnum.center, 'u' as tr,head.trno,head.doc,$ref2, client.clientname, ifnull(client.clientname,'no name') as name,
                                date(head.dateid) as dateid, head.docno,  datediff(now(), $elapsedate) as elapse,
                                sum(stock.ext) as balance, head.yourref,date(head.deldate) as deldate
                                from lahead as head left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                where cntnum.doc IN ('SJ','MJ','CM')  $filter
                                group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc,head.deldate
                                order by clientname, dateid, docno) as x
                                        group by clientname, dateid,docno,name,ref,elapse
                                        order by  clientname";


                break;

            case '0': // SUMMARIZED
                $query = "select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, 
                                sum(balance) as balance,elapse 
                        from (select 'p' as tr, client.clientname, 
                                    date(detail.dateid) as dateid, detail.docno, datediff(now(), $elapsedate) as elapse,
                                    (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
                            from (arledger as detail left join client on client.clientid=detail.clientid)
                            left join cntnum on cntnum.trno=detail.trno
                            left join glhead as head on head.trno=detail.trno
                            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                            left join coa on coa.acnoid=gdetail.acnoid
                            where detail.bal<>0 and left(coa.alias,2)='AR' and detail.dateid<=now()  $filter 
                            union all
                            select  'z' as tr, '' as clientname, '' as dateid, '' as docno, '' as elapse, 
                                    0 as balance ) as x
                            group by  clientname,elapse
                            union all
                            select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, 
                                    sum(balance) as balance,elapse 
                            from (select 'u' as tr, client.clientname,
                                        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                                        detail.db as balance
                                from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                                left join client on client.client=head.client)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where cntnum.doc IN ('GJ','AR','CR') and left(coa.alias,2)='AR' and detail.refx = 0  $filter 
                                group by client.clientname, head.dateid, head.docno, detail.db) as t
                                group by clientname, elapse 
                                union all
                                select  (case when ifnull(clientname,'')='' then 'no name' else clientname end) as clientname, 
                                        sum(balance) as balance,elapse 
                                from (select 'u' as tr, client.clientname, date(head.dateid) as dateid, 
                                            head.docno, datediff(now(), head.dateid) as elapse,sum(stock.ext) as balance
                                    from lahead as head left join lastock as stock on stock.trno=head.trno
                                    left join ladetail as detail on detail.trno=head.trno
                                    left join client on client.client=head.client
                                    left join cntnum on cntnum.trno=head.trno
                                    left join coa on coa.acnoid=detail.acnoid
                                    where cntnum.doc IN ('SJ','MJ','CM')  $filter 
                                    group by client.clientname, head.dateid, head.docno, head.yourref, head.deldate) as x
                                group by clientname, elapse
                                union all
                                select   'z' as clientname, 0 as balance, '' as elapse 
                                order by clientname";
                break;
        } //end swicth
        return $query;
    }

    private function displayHeader_DETAILED($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $contra       = $config['params']['dataparams']['contra'];

        $dept = "";


        switch ($posttype) {
            case 0: //posted
                $reporttype = 'Posted';
                break;
            case 1: //unposted
                $reporttype = 'Unposted';
                break;
            case 2: //all
                $reporttype = 'ALL';
                break;
        }

        $str = '';
        $layoutsize = '1050';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br><br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('DETAILED CURRENT STUDENT RECEIVABLES AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= '</br>';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        if ($client == '') {
            $str .= $this->reporter->col('Student : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Student : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

        if ($contra == '') {
            $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Account: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');



        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STUDENT NAME', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Current', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('120+ days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout_LAYOUT_DETAILED($config, $result)
    {

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $this->reporter->linecounter = 0;
        $count = 52;
        $page = 55;

        $str = '';
        $layoutsize = '1050';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_DETAILED($config);

        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;

        $tota = 0;
        $totb = 0;
        $totc = 0;
        $totd = 0;
        $tote = 0;
        $gt = 0;

        $customer = "";
        $docno = "";
        $subtotal = 0;

        $suba = 0;
        $subb = 0;
        $subc = 0;
        $subd = 0;
        $sube = 0;

        foreach ($result as $key => $data) {
            if ($customer != $data->clientname) {
                if ($customer != '') {
                    $this->reporter->addline();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');


                    $str .= $this->reporter->endrow();

                    $str .= '<br/>';
                    $subtotal = 0;
                    $suba = 0;
                    $subb = 0;
                    $subc = 0;
                    $subd = 0;
                    $sube = 0;

                    if ($this->reporter->linecounter == $page) {
                        $str .= $this->reporter->endtable();
                        $str .= $this->reporter->page_break();
                        $str .= $this->displayHeader_DETAILED($config);
                        $page = $page + $count;
                    } //end if

                }

                $this->reporter->addline();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->clientname, $layoutsize, null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');

                $str .= $this->reporter->endrow();


                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader_DETAILED($config);
                    $page = $page + $count;
                }
            }

            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;

            if ($data->elapse >= 0 && $data->elapse <= 30) {
                $a = $data->balance;
            }
            if ($data->elapse >= 31 && $data->elapse <= 60) {
                $b = $data->balance;
            }
            if ($data->elapse >= 61 && $data->elapse <= 90) {
                $c = $data->balance;
            }
            if ($data->elapse >= 91 && $data->elapse <= 120) {
                $d = $data->balance;
            }
            if ($data->elapse >= 121) {
                $e = $data->balance;
            }

            $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

            $str .= $this->reporter->col(($a > 0 ? number_format($a, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($b > 0 ? number_format($b, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($c > 0 ? number_format($c, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($d > 0 ? number_format($d, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($e > 0 ? number_format($e, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $tota += $a;
            $totb += $b;
            $totc += $c;
            $totd += $d;
            $tote += $e;
            $subtotal += $data->balance;
            $gt += $data->balance;
            $customer = $data->clientname;

            $suba += $a;
            $subb += $b;
            $subc += $c;
            $subd += $d;
            $sube += $e;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader_DETAILED($config);
                $page = $page + $count;
            } //end if
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }

    private function displayHeader_SUMMARIZED($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $contra       = $config['params']['dataparams']['contra'];
        $dept = "";

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .=  '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('CURRENT STUDENT RECEIVABLES AGING - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .=  '<br/>';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        if ($client == '') {
            $str .= $this->reporter->col('Student : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Student : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }
        if ($contra == '') {
            $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Acount: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');



        switch ($posttype) {
            case 0: //posted
                $reporttype = 'Posted';
                break;
            case 1: //unposted
                $reporttype = 'Unposted';
                break;
            case 2: //all
                $reporttype = 'ALL';
                break;
        }

        if ($filtercenter == "") {
            $filtercenter = "ALL";
        }

        $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

        $str .= $this->reporter->pagenumber('Page', $font);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STUDENT NAME', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Current', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31-60 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('61-90 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('91-120 days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('120+ days', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result)
    {

        $this->reporter->linecounter = 0;
        $count = 60;
        $page = 64;
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_SUMMARIZED($config);

        $clientname = "";
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;
        $tota = 0;
        $totb = 0;
        $totc = 0;
        $totd = 0;
        $tote = 0;
        $gt = 0;


        $subtota = 0;
        $subtotb = 0;
        $subtotc = 0;
        $subtotd = 0;
        $subtote = 0;
        $subgt = 0;
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;

        foreach ($result as $key => $data) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            if ($clientname == '') {

                $clientname = $data->clientname;

                if ($data->elapse >= 0 && $data->elapse <= 30) {
                    $a = 0;
                    $b = 0;
                    $c = 0;
                    $d = 0;
                    $e = 0;
                    $a = $data->balance;
                    $subtota = $subtota + $a;
                    $subgt = $subgt + $data->balance;
                }
                if ($data->elapse >= 31 && $data->elapse <= 60) {
                    $a = 0;
                    $b = 0;
                    $c = 0;
                    $d = 0;
                    $e = 0;
                    $b = $data->balance;
                    $subtotb = $subtotb + $b;
                    $subgt = $subgt + $data->balance;
                }
                if ($data->elapse >= 61 && $data->elapse <= 90) {
                    $a = 0;
                    $b = 0;
                    $c = 0;
                    $d = 0;
                    $e = 0;
                    $c = $data->balance;
                    $subtotc = $subtotc + $c;
                    $subgt = $subgt + $data->balance;
                }
                if ($data->elapse >= 91 && $data->elapse <= 120) {
                    $a = 0;
                    $b = 0;
                    $c = 0;
                    $d = 0;
                    $e = 0;
                    $d = $data->balance;
                    $subtotd = $subtotd + $d;
                    $subgt = $subgt + $data->balance;
                }
                if ($data->elapse > 120) {
                    $a = 0;
                    $b = 0;
                    $c = 0;
                    $d = 0;
                    $e = 0;
                    $e = $data->balance;
                    $subtote = $subtote + $e;
                    $subgt = $subgt + $data->balance;
                }
            } else {

                if ($clientname != $data->clientname) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();

                    if ($subgt != 0) {
                        $str .= $this->reporter->col($clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col(($subtota > 0 ? number_format($subtota, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col(($subtotb > 0 ? number_format($subtotb, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col(($subtotc > 0 ? number_format($subtotc, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col(($subtotd > 0 ? number_format($subtotd, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col(($subtote > 0 ? number_format($subtote, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col(number_format($subgt, 2), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
                    }
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $subtota = 0;
                    $subtotb = 0;
                    $subtotc = 0;
                    $subtotd = 0;
                    $subtote = 0;
                    $subgt = 0;

                    $clientname = $data->clientname;

                    if ($data->elapse >= 0 && $data->elapse <= 30) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $a = $data->balance;
                        $subtota = $subtota + $a;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 31 && $data->elapse <= 60) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $b = $data->balance;
                        $subtotb = $subtotb + $b;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 61 && $data->elapse <= 90) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $c = $data->balance;
                        $subtotc = $subtotc + $c;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 91 && $data->elapse <= 120) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $d = $data->balance;
                        $subtotd = $subtotd + $d;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 121) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $e = $data->balance;
                        $subtote = $subtote + $e;
                        $subgt = $subgt + $data->balance;
                    }
                } else {

                    if ($data->elapse >= 0 && $data->elapse <= 30) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $a = $data->balance;
                        $subtota = $subtota + $a;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 31 && $data->elapse <= 60) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $b = $data->balance;
                        $subtotb = $subtotb + $b;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 61 && $data->elapse <= 90) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $c = $data->balance;
                        $subtotc = $subtotc + $c;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 91 && $data->elapse <= 120) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $d = $data->balance;
                        $subtotd = $subtotd + $d;
                        $subgt = $subgt + $data->balance;
                    }
                    if ($data->elapse >= 121) {
                        $a = 0;
                        $b = 0;
                        $c = 0;
                        $d = 0;
                        $e = 0;
                        $e = $data->balance;
                        $subtote = $subtote + $e;
                        $subgt = $subgt + $data->balance;
                    }
                }
            }
            $tota += $a;
            $totb += $b;
            $totc += $c;
            $totd += $d;
            $tote += $e;
            $gt = $gt + $data->balance;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader_SUMMARIZED($config);
                $page = $page + $count;
            }
        } // end foreach

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('TOTAL : ', '110', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tota, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totb, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totc, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($totd, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tote, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($tota + $totb + $totc + $totd + $tote, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '5px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class