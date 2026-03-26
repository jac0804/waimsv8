<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class collection_summary
{
    public $modulename = 'Collection Summary';
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
        $fields = ['radioprint', 'dateid', 'enddate', 'dclientname', 'dcentername'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        data_set($col1, 'dateid.label', 'StartDate');
        data_set($col1, 'dateid.readonly', false);

        data_set($col1, 'enddate.readonly', false);

        $fields = ['radioposttype'];
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
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


        $paramstr = "select 
            'default' as print,
            adddate(left(now(),10),-360) as dateid,
            left(now(),10) as enddate,
            '' as client,
            '0' as posttype,
            '' as dclientname,
            '' as dagentname,
            '' as agent,
            '' as agentname,
            '' as agentid,
            '" . $defaultcenter[0]['center'] . "' as center,
            '" . $defaultcenter[0]['centername'] . "' as centername,
            '" . $defaultcenter[0]['dcentername'] . "' as dcentername ";

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
        $result = $this->reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result); // POSTED
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $posttype = $config['params']['dataparams']['posttype'];

        switch ($posttype) {
            case '0': // POSTED
                $query = $this->QUERY_POSTED($config); // POSTED                
                break;
            // case '1': // UNPOSTED
            //     $query = $this->QUERY_UNPOSTED($config); // POSTED
            //     break;

            // case '2': // ALL
            //     $query = $this->QUERY_ALL($config); // POSTED
            //     break;
        }

        $result = $this->coreFunctions->opentable($query);

        return $this->reportplotting($config, $result);
    }

    public function QUERY_POSTED($config)
    {
        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['client'];
        // $posttype     = $config['params']['dataparams']['posttype'];
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));

        $filter = "";
        $filter1 = "";
        if ($client != "") {
            $filter = " and client.client='$client'";
        }

        if ($filtercenter != "") {
            $filter1 = " and cntnum.center='$filtercenter'";
        }

        $addfield = '';
        $addfield2 = '';
        // if ($companyid == 32) { //3m
        //     $addfield = ',brgy, area';
        //     $addfield2 = ',client.brgy, client.area';
        // }

        $query = "select 
            #head.trno,head.aftrno,num.dptrno,le.trno,
            head.docno,date(head.dateid) as receiptdate,
            #ea.depodate,
            le.docno,r.reqtype as typeofloan,'' as receiptno,'' as orrec,le.clientname as custname,
            sum(dinfo.principal) as principal,sum(dinfo.interest) as interest,sum(dinfo.pfnf) as pfnf,sum(dinfo.nf) as nf,sum(dinfo.penalty) as penalty,
            '' as dst,'' as total
            #dinfo.principal,dinfo.interest,dinfo.pfnf,dinfo.nf,dinfo.penalty
            #SOA DATE	AMOUNT IN SOA	TOTAL	DATE DEPOSITED	STATUS	BANK/ BRANCH	CHECK NO.	AMOUNT	CHECK DATE	Customer Name

            from glhead as head 
            left join cntnum as num on num.trno=head.aftrno
            left join heahead as le on le.trno=num.dptrno
            left join reqcategory as r on r.line=le.planid
            left join htempdetailinfo as dinfo on dinfo.trno=le.trno
            where head.doc = 'CR'
            group by 
            #head.trno,head.aftrno,num.dptrno,le.trno,
            head.docno,head.dateid,
            #ea.depodate,
            le.docno,r.reqtype,le.clientname";

        return $query;
    }

    // public function QUERY_UNPOSTED($config)
    // {
    //     $filtercenter = $config['params']['dataparams']['center'];
    //     $client       = $config['params']['dataparams']['client'];
    //     $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    //     $end = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));

    //     $filter = "";
    //     $filter1 = "";
    //     if ($client != "") {
    //         $filter = " and client.client='$client'";
    //     }

    //     if ($filtercenter != "") {
    //         $filter1 = " and cntnum.center='$filtercenter'";
    //     }

    //     $query = "select clientname, name, crlimit, sum(db) as db, sum(cr) as cr, sum(db) as balance,
    //             (select sum(checks) from (
    //                 select sum(a.db - cr) as checks, a.clientid
    //                 from crledger as a
    //                 left join cntnum on cntnum.trno = a.trno
    //                 where a.depodate is null and date(a.checkdate) <= '$end' $filter1
    //                 group by a.clientid
                    
    //                 UNION ALL

    //                 select sum(a.db - cr) as checks, client.clientid
    //                 from ladetail as a
    //                 left join lahead as h on h.trno = a.trno
    //                 left join cntnum on cntnum.trno = a.trno
    //                 left join coa on coa.acnoid = a.acnoid
    //                 left join client on client.client = h.client
    //                 where date(h.dateid) <= '$end' and left(coa.alias, 2) = 'CR' $filter1
    //                 group by client.clientid
    //                 ) as a where a.clientid = x.clientid) as pdc
    //                 from (
    //                 select 'u' as tr, detail.trno,client.clientid,detail.client,client.clientname, ifnull(client.clientname,'no name') as name,date(head.dateid) as dateid,
    //                     datediff(now(), head.dateid) as elapse,client.crlimit,detail.db,detail.cr
    //                 from (((lahead as head
    //                 left join ladetail as detail on detail.trno=head.trno)
    //                 left join client on client.client=head.client)
    //                 left join coa on coa.acnoid=detail.acnoid)
    //                 left join cntnum on cntnum.trno=head.trno
    //             where left(coa.alias,2)='AR' and detail.refx = 0 and date(head.dateid) between '$start' and '$end' $filter $filter1) as x
    //             group by clientid,client,clientname, name,crlimit
    //             order by clientname";

    //     return $query;
    // }

    // public function QUERY_ALL($config)
    // {
    //     $filtercenter = $config['params']['dataparams']['center'];
    //     $client       = $config['params']['dataparams']['client'];
    //     // $posttype     = $config['params']['dataparams']['posttype'];
    //     $companyid = $config['params']['companyid'];
    //     $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    //     $end = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));

    //     $filter = "";
    //     $filter1 = "";
    //     if ($client != "") {
    //         $filter = " and client.client='$client'";
    //     }

    //     if ($filtercenter != "") {
    //         $filter1 = " and cntnum.center='$filtercenter'";
    //     }

    //     $addfield = '';
    //     $addfield2 = '';
    //     if ($companyid == 32) { //3m
    //         $addfield = ',brgy, area';
    //         $addfield2 = ',client.brgy, client.area';
    //     }

    //     $query = " select clientname, name,crlimit,sum(db) as db,sum(cr) as cr, sum(balance) as balance, pdc
    //             from (
    //             select clientname, name,crlimit,sum(db) as db,sum(cr) as cr,
    //             sum((select (case when ar.db > 0 then ar.bal else (ar.bal * -1) end) as balance
    //                  from arledger as ar
    //                  where ar.trno = x.trno and ar.clientid = x.clientid and date(ar.dateid) <= '$end' limit 1)) as balance,
    //                 (select sum(checks) as checks from (
    //                 select sum(a.db - cr) as checks, a.clientid
    //                 from crledger as a
    //                 left join cntnum on cntnum.trno = a.trno
    //                 where a.depodate is null and date(a.checkdate) <= '$end' $filter1
    //                 group by clientid
                    
    //                 UNION ALL

    //                 select sum(a.db - cr) as checks, client.clientid
    //                 from ladetail as a
    //                 left join lahead as h on h.trno = a.trno
    //                 left join cntnum on cntnum.trno = a.trno
    //                 left join coa on coa.acnoid = a.acnoid
    //                 left join client on client.client = h.client
    //                 where date(h.dateid) <= '$end' and left(coa.alias, 2) = 'CR' $filter1
    //                 group by clientid
    //                 ) as a where a.clientid = x.clientid) as pdc " . $addfield . "
    //            from (
    //                 select 'p' as tr,detail.trno, detail.clientid,client.client,client.clientname,
    //                 ifnull(client.clientname,'no name' ) as name,detail.dateid, 
    //                 datediff(now(), detail.dateid) as elapse,client.crlimit,detail.db,detail.cr " . $addfield2 . "
    //                  from (arledger as detail
    //                  left join client on client.clientid=detail.clientid)
    //                  left join cntnum on cntnum.trno=detail.trno
    //                  left join glhead as head on head.trno=detail.trno
    //                  where detail.bal<>0 and date(detail.dateid) between '$start' and '$end' $filter $filter1) as x
    //                  group by clientid,client,clientname, name,crlimit  " . $addfield . " 
               
    //                  UNION ALL

    //             select clientname, name, crlimit, sum(db) as db, sum(cr) as cr, sum(db) as balance,
    //                     (select sum(checks) as checks
    //                     from (
    //                     select sum(a.db - cr) as checks, a.clientid
    //                     from crledger as a
    //                     left join cntnum on cntnum.trno = a.trno
    //                     where a.depodate is null and date(a.checkdate) <= '$end' $filter1
    //                     group by clientid
                        
    //                     UNION ALL

    //                     select sum(a.db - cr) as checks, client.clientid
    //                     from ladetail as a
    //                     left join lahead as h on h.trno = a.trno
    //                     left join cntnum on cntnum.trno = a.trno
    //                     left join coa on coa.acnoid = a.acnoid
    //                     left join client on client.client = h.client
    //                     where date(h.dateid) <= '$end' and left(coa.alias, 2) = 'CR'
    //                     group by clientid
    //                     ) as a where a.clientid = x.clientid) as pdc
    //                     from (
    //                     select 'u' as tr, detail.trno,client.clientid,detail.client,client.clientname,
    //                     ifnull(client.clientname,'no name') as name,date(head.dateid) as dateid, datediff(now(), head.dateid) as elapse,
    //                     client.crlimit,detail.db,detail.cr
    //                     from (((lahead as head
    //                     left join ladetail as detail on detail.trno=head.trno)
    //                     left join client on client.client=head.client)
    //                     left join coa on coa.acnoid=detail.acnoid)
    //                     left join cntnum on cntnum.trno=head.trno
    //                     where left(coa.alias,2)='AR' and detail.refx = 0 and date(head.dateid) between '$start' and '$end' $filter $filter1) as x
    //                     group by clientid,client,clientname, name,crlimit
    //             ) as a
    //             group by clientname,name,crlimit,pdc
    //             order by clientname";


    //     return $query;
    // }

    private function displayHeader_SUMMARIZED($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $filtercenter = $config['params']['dataparams']['center'];
        $client       = $config['params']['dataparams']['client'];
        $posttype     = $config['params']['dataparams']['posttype'];

        $start = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));

        $contra = "";
        $dept = "";


        $str = '';
        $layoutsize = $companyid == 32 ? '1200' : '1000'; //3m
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

        $str .= $this->reporter->col('SCHEDULE OF AR', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        switch ($posttype) {
            case 0: //posted
                $reporttype = 'Posted';
                break;
            case 1: //unposted
                $reporttype = 'Unposted';
                break;
            case 2: //ALL
                $reporttype = 'ALL';
                break;
        }

        if ($filtercenter == '') {
            $filtercenter = 'ALL';
        }


        $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        if ($client == '') {
            $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->col('Transaction : ' . $reporttype, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Date from ' . $start . ' to ' . $end, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

        $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER NAME', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        if ($companyid == 32) { //3m
            $str .= $this->reporter->col('BARANGAY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('AREA', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('CREDIT LINE', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEBIT', '115', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '115', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UNCLEARED CHECKS', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AVAILABLE CREDIT', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout_LAYOUT_SUMMARIZED($config, $result)
    {
        $companyid = $config['params']['companyid'];
        $count = 28;
        $page = 30;
        $layoutsize = $companyid == 32 ? '1200' : '1000'; //3m
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_SUMMARIZED($config);
        $amt = null;
        foreach ($result as $key => $data) {
            $bal =$data->balance;         

            $uncleared = $data->pdc;
            $crlimit = $data->crlimit;
            $availablecr = $crlimit - $bal - $uncleared;
            $bal =  number_format($data->balance, 2);
            if ($bal == 0) {
                $bal = '-';
            }            

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            if ($companyid == 32) { //3m
                $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            }
            $str .= $this->reporter->col($data->crlimit != 0 ? number_format($data->crlimit, 2) : '-', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->db != 0 ? number_format($data->db, 2) : '-', '115', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->cr != 0 ? number_format($data->cr, 2) : '-', '115', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($bal, '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($uncleared != 0 ? number_format($uncleared, 2) : '-', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($availablecr != 0 ? number_format($availablecr, 2) : '-', '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();

            $amt = $amt + $data->balance;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader_SUMMARIZED($config);
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class