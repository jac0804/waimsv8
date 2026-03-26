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

class current_student_receivables
{
    public $modulename = 'Current Student Receivables';
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
        $companyid = $config['params']['companyid'];

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

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 'default' as print, '' as clientname,'' as ehstudentlookup, '0' as clientid,
                    '" . $defaultcenter[0]['center'] . "' as center,
                    '" . $defaultcenter[0]['centername'] . "' as centername,
                    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,'0' as reporttype,
                   '' as contra,'0' as posttype";

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
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $reporttype   = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case '0': // SUMMARIZED
                $result = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result); // POSTED
                break;
            case '1': // DETAILED
                $result = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result); // POSTED
                break;
        }

        return $result;
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
                $query = $this->reportDefault_QUERY_ALL($config); // POSTED
                break;
        }


        $result = $this->coreFunctions->opentable($query);
        return $this->reportplotting($config, $result);
    }

    public function reportDefault_QUERY_POSTED($config)
    {
        $filtercenter = $config['params']['dataparams']['center'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $reporttype   = $config['params']['dataparams']['reporttype'];
        $contra       = $config['params']['dataparams']['contra'];


        $filter = "";
        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }

        if ($filtercenter != "") {
            $filter .= " and cntnum.center='$filtercenter'";
        }

        if ($contra != '') {
            $acnoid       = $config['params']['dataparams']['acnoid'];
            $filter .= " and coa.acnoid='$acnoid'";
        }

        switch ($reporttype) {
            case '1': // DETAILED
                $query = "select 'p' as tr, client.clientname, 
                                date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                                (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance 
                        from (arledger as detail 
                        left join client on client.clientid=detail.clientid)
                        left join cntnum on cntnum.trno=detail.trno
                        left join glhead as head on head.trno=detail.trno
                        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                        left join coa on coa.acnoid=gdetail.acnoid
                        where detail.bal<>0 and left(coa.alias,2)='AR' $filter
                        order by client.clientname, detail.dateid, detail.docno";

                break;
            case '0': // SUMMARIZED
                $query = "select clientname,sum(balance) as balance 
                            from (select 'p' as tr, client.clientname, 
                            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
                            from (arledger as detail left join client on client.clientid=detail.clientid)
                            left join cntnum on cntnum.trno=detail.trno
                            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                            left join coa on coa.acnoid=gdetail.acnoid
                            where detail.bal<>0 and left(coa.alias,2)='AR'  
                            $filter) as x
                            group by clientname order by clientname";

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

        $filter = "";
        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }

        if ($filtercenter != "") {
            $filter .= " and cntnum.center='$filtercenter'";
        }

        if ($contra != '') {
            $acnoid       = $config['params']['dataparams']['acnoid'];
            $filter .= " and coa.acnoid='$acnoid'";
        }


        switch ($reporttype) {
            case '1': // DETAILED
                $query = "select 'u' as tr, client.clientname, 
                                date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                                detail.db as balance
                        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                        left join client on client.client=head.client)
                        left join coa on coa.acnoid=detail.acnoid)
                        left join cntnum on cntnum.trno=head.trno
                        where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
                        union all
                        select 'u' as tr, client.clientname, 
                                date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                                sum(stock.ext) as balance
                        from (((lahead as head left join lastock as stock on stock.trno=head.trno
                        left join ladetail as detail on detail.trno=head.trno)
                        left join client on client.client=head.client)
                        left join coa on coa.acnoid=detail.acnoid)
                        left join cntnum on cntnum.trno=head.trno
                        where head.doc in ('sj','mj')  $filter 
                        group by clientname, dateid, docno, elapse
                        order by clientname, dateid, docno";
                break;

            case '0': // SUMMARIZED
                $query = "select clientname, sum(balance) as balance 
                          from ( select 'u' as tr, client.clientname,detail.db as balance
                                from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                                left join client on client.client=head.client)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' 
                                    and detail.refx = 0  $filter
                                union all
                                select 'u' as tr, client.clientname,sum(stock.ext) as balance
                                from (((lahead as head left join lastock as stock on stock.trno=head.trno
                                left join ladetail as detail on detail.trno=head.trno)
                                left join client on client.client=head.client)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where head.doc in ('sj','mj','cm') $filter 
                                group by clientname) as x
                        group by clientname
                        order by clientname";
                break;
        } //end swicth


        return $query;
    }

    public function reportDefault_QUERY_ALL($config)
    {
        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $reporttype   = $config['params']['dataparams']['reporttype'];
        $contra       = $config['params']['dataparams']['contra'];

        $filter = "";
        $filter1 = "";
        $filter2 = "";
        $filter3 = "";
        if ($client != "") {
            $filter = " and client.clientid='$clientid'";
        }

        if ($filtercenter != "") {
            $filter .= " and cntnum.center='$filtercenter'";
        }

        if ($contra != '') {
            $acnoid       = $config['params']['dataparams']['acnoid'];
            $filter .= " and coa.acnoid='$acnoid'";
        }

        $addfield = "";
        $addfield2 = '';
        $addleftjoin = "";
        $addleftjoin2 = "";
        $addgrp3m = '';

        switch ($reporttype) {
            case '1': // DETAILED
                $query = "select 'p' as tr, client.clientname, 
                                date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
                                (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance 
                        from (arledger as detail 
                        left join client on client.clientid=detail.clientid)
                        left join cntnum on cntnum.trno=detail.trno
                        left join glhead as head on head.trno=detail.trno
                        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                        left join coa on coa.acnoid=gdetail.acnoid
                        where detail.bal<>0 and left(coa.alias,2)='AR' $filter
                        union all
                        select 'u' as tr, client.clientname, 
                                date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                                detail.db as balance
                        from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                        left join client on client.client=head.client)
                        left join coa on coa.acnoid=detail.acnoid)
                        left join cntnum on cntnum.trno=head.trno
                        where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
                        union all
                        select 'u' as tr, client.clientname, 
                                date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
                                sum(stock.ext) as balance
                        from (((lahead as head left join lastock as stock on stock.trno=head.trno
                        left join ladetail as detail on detail.trno=head.trno)
                        left join client on client.client=head.client)
                        left join coa on coa.acnoid=detail.acnoid)
                        left join cntnum on cntnum.trno=head.trno
                        where head.doc in ('sj','mj')  $filter 
                        group by clientname, dateid, docno, elapse
                        order by clientname, dateid, docno";
                break;


            case '0': // SUMMARIZED
                $query = "select clientname,sum(balance) as balance 
                          from (select 'p' as tr, client.clientname, 
                                        (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
                                from (arledger as detail 
                                left join client on client.clientid=detail.clientid)
                                left join cntnum on cntnum.trno=detail.trno
                                left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
                                left join coa on coa.acnoid=gdetail.acnoid
                                where detail.bal<>0 and left(coa.alias,2)='AR' $filter
                                union all
                                select 'u' as tr, client.clientname,detail.db as balance
                                from (((lahead as head left join ladetail as detail on detail.trno=head.trno)
                                left join client on client.client=head.client)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where head.doc in ('ar','gj','cr') and left(coa.alias,2)='AR' and detail.refx = 0  $filter
                                union all
                                select 'u' as tr, client.clientname,sum(stock.ext) as balance
                                from (((lahead as head left join lastock as stock on stock.trno=head.trno
                                left join ladetail as detail on detail.trno=head.trno)
                                left join client on client.client=head.client)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where head.doc in ('sj','mj','cm') $filter 
                                group by clientname) as x
                          group by clientname
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
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $contra = '';
        $contra   = $config['params']['dataparams']['contra'];

        $posttype     = $config['params']['dataparams']['posttype'];
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
        $layoutsize = '1000';
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
        $str .= $this->reporter->col('DETAILED CURRENT STUDENT RECEIVABLES', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

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

        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STUDENT NAME', '270', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No. of days', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout_LAYOUT_DETAILED($config, $result)
    {
        $count = 40;
        $page = 40;
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $this->reporter->linecounter = 0;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_DETAILED($config);

        $itemname = "";
        $date = "";
        $docno = "";
        $yourref = "";
        $totalext = 0;
        $totalqty = 0;
        $totaltons = 0;
        $subtotalqty = 0;
        $subtotalext = 0;
        $subtotalpv = 0;
        $subtotaltons = 0;
        $gsubtotalqty = 0;
        $gsubtotalext = 0;
        $gsubtotalpv = 0;
        $member = "";
        $grandtotalpv = 0;
        $grandtotalqty = 0;
        $gsubtotaltons = 0;

        $iitem = "";

        foreach ($result as $key => $data) {
            $display = $data->clientname;
            $docno = $data->docno;
            $date = $data->dateid;
            $order = $data->elapse;
            $served = $data->balance;

            if ($itemname == "") {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->clientname, '270', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
                $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
            }

            if (strtoupper($itemname) == strtoupper($data->clientname)) {
                $itemname = "";

                if (strtoupper($docno) == strtoupper($data->clientname)) {
                    $docno = "";
                } else {
                    if ($docno != '') {
                        $subtotalqty = 0;
                        $subtotalext = 0;
                    }
                    $itemname = strtoupper($data->clientname);
                }
            } else {
                if ($docno != '') {
                }

                if ($itemname != '') {

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();

                    $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('SUB TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');

                    $str .= $this->reporter->endrow();


                    $str .= $this->reporter->addline();
                    if ($this->reporter->linecounter == $page) {
                        $str .= $this->reporter->endtable();
                        $str .= $this->reporter->page_break();
                        $str .= $this->displayHeader_DETAILED($config);
                        $page = $page + $count;
                    }
                }

                if ($itemname != '') {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->clientname, '270', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '5px');
                    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, '', '', '');


                    $str .= $this->reporter->addline();
                    if ($this->reporter->linecounter == $page) {
                        $str .= $this->reporter->endtable();
                        $str .= $this->reporter->page_break();
                        $str .= $this->displayHeader_DETAILED($config);
                        $page = $page + $count;
                    }
                }

                $subtotalext = 0;
                $gsubtotalext = 0;
                $docno = $data->clientname;
                if (strtoupper($docno) == strtoupper($data->clientname)) {
                    $docno = "";
                } else {
                    $docno = strtoupper($data->clientname);
                }
            }

            if ($iitem == $data->clientname) {
                $iitem = "";
            } else {
                $iitem = $data->clientname;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('', '270', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col($date, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col($data->docno, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col(number_format($order, 0), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
            $str .= $this->reporter->col(number_format($served, 2), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');


            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $subtotalext = $subtotalext + $data->balance;
            $gsubtotalext = $gsubtotalext + $data->balance;
            $totalext = $totalext + $data->balance;
            $itemname = strtoupper($data->clientname);
            $docno = $data->clientname;
            $iitem = $data->clientname;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader_DETAILED($config);
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUB TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL :', '50', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->endreport();

        return $str;
    }

    private function displayHeader_SUMMARIZED($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];

        $reporttype   = $config['params']['dataparams']['reporttype'];
        $contra = '';
        $dept = "";
        $contra   = $config['params']['dataparams']['contra'];

        $posttype     = $config['params']['dataparams']['posttype'];

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
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('CURRENT STUDENT RECEIVABLES - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        if ($client == '') {
            $str .= $this->reporter->col('Student : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Student : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }
        if ($contra == '') {
            $str .= $this->reporter->col('Account: ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Account: ' . $contra, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
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
        $str .= $this->reporter->col('STUDENT NAME', '110px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110px', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result)
    {

        $companyid = $config['params']['companyid'];
        $count = 60;
        $page = 60;
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $this->reporter->linecounter = 0;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_SUMMARIZED($config);
        $amt = null;
        foreach ($result as $key => $data) {
            $bal = number_format($data->balance, 2);
            if ($bal == 0) {
                $bal = '-';
            }

            $display = $data->clientname;
            $served = $data->balance;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($display, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($bal, '110px', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->endrow();

            $amt = $amt + $data->balance;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader_SUMMARIZED($config);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL : ', '110px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($amt, 2), '110px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class