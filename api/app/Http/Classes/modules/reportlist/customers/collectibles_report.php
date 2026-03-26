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

class collectibles_report
{
    public $modulename = 'Collectibles Report';
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
        $companyid = $config['params']['companyid'];


        $fields = ['radioprint', 'start', 'dclientname', 'categoryname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'customer_borrower');
        data_set($col1, 'dclientname.label', 'Borrower');
        data_set($col1, 'categoryname.action', 'lookupreqcategory');
        data_set($col1, 'categoryname.lookupclass', 'lookuploan');
        data_set($col1, 'categoryname.label', 'Loan Type');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $companyid = $config['params']['companyid'];
        $user = $config['params']['user'];
        return $this->coreFunctions->opentable("select 
        'default' as print,
        date(now()) as start,
        '0' as clientid,
        '' as client,
        '' as clientname,
        '' as dclientname,
        '' as  categoryname,
        '0' as planid");
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
        // $center = $config['params']['center'];
        // $username = $config['params']['user'];

        return $this->reportDefaultLayout($config);
    }
    public function reportDefault($config)
    {

        $client     = $config['params']['dataparams']['client'];
        $categoryname     = $config['params']['dataparams']['categoryname'];
        $planid     = $config['params']['dataparams']['planid'];
        $asof = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $filter = "";

        if ($client != "") {
            $filter .= " and leh.client='$client'";
        }

        if ($categoryname != "") {
            $filter .= " and c.line='$planid'";
        }

        $query = "
            select  date(leh.dateid) as datehere,leh.clientname,ifnull(sb.category,'')  as sbu,ifnull(sb.reqtype,'')  as company,
            '' as status,i.amount as principal,
            sum(dinfo.interest) as interest,
            i.pf as prfee, i.nf as notarialfee,i.rpt , ''as dst,leh.terms,i.amortization,
            num.trno as cvtrno,cl.clientid,i.docstamp  as dst

            from heahead as leh
            left join heainfo as i on i.trno = leh.trno
            left join reqcategory as c on c.line = leh.planid
            left join reqcategory as sb on sb.line=i.sbuid
            left join htempdetailinfo as dinfo on dinfo.trno=leh.trno
            left join client as cl on cl.client=leh.client
            left join cntnum as num on num.dptrno=leh.trno
            left join glhead as cvhead on cvhead.trno=num.trno
            where  date(leh.dateid)  <='$asof' and num.trno is not null and cvhead.doc='cv'  $filter 
            group by leh.dateid,leh.clientname,sb.category,
            leh.terms,num.trno,cl.clientid,i.amount,i.pf, i.nf,i.amortization,i.rpt,i.docstamp,sb.reqtype";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    public function balanceqry($config)
    {

        $result = $this->reportDefault($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $cvs = [];
        foreach ($result as $key => $datahere) {
            if ($datahere->cvtrno != 0) {
                $cvs[] = $datahere->cvtrno;
            }
        }
        // var_dump($cvs);

        $cvs_str = implode(',', $cvs);
        // Kung walang laman, huwag ituloy
        if (empty($cvs_str)) {
            return $this->othersClass->emptydata($config);
        }

        $query = "select ar.trno, DATE_FORMAT(ar.dateid, '%Y-%m') as dateid,ar.clientid,sum(ar.bal) as bal,
                sum(if(c.alias in ('AR1','AR5','AR6'), ar.db, 0)) as principal,
                sum(if(c.alias='AR2', ar.db, 0)) as interest,
                sum(if(left(c.alias, 2) = 'AR' and c.alias not in ('AR1','AR5','AR6','AR2'),ar.db, 0)) as otherfee
                from arledger as ar
                left join coa as c on c.acnoid=ar.acnoid
                where  ar.trno in ($cvs_str) and ar.bal<>0 
                group by ar.trno,DATE_FORMAT(ar.dateid, '%Y-%m'),ar.clientid";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    private function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $client     = $config['params']['dataparams']['clientname'];
        $categoryname     = $config['params']['dataparams']['categoryname'];
        $start     = $config['params']['dataparams']['start'];

        $str = '';
        $month_withbal = $this->balanceqry($config);

        if (empty($month_withbal)) {
            return $this->othersClass->emptydata($config);
        }

        $month_withbal = json_decode(json_encode($month_withbal), true);

        $dateids = array_column($month_withbal, 'dateid');
        $dateids = array_filter($dateids);         // remove empty
        $dateids = array_unique($dateids);         // remove duplicates
        sort($dateids);                            // sort ascending (by date)

        // Convert to readable "Month Year" format
        $uniqueMonths = [];
        foreach ($dateids as $d) {
            $uniqueMonths[] = date('F Y', strtotime($d));
        }

        // header layout setup
        $layoutsize = 1360 + (count($uniqueMonths) * 420);
        // var_dump($layoutsize);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $borrower = '';
        if ($client != '') {
            $borrower = $client;
        } else {
            $borrower = 'ALL';
        }
        $loantype = '';
        if ($categoryname != '') {
            $loantype = $categoryname;
        } else {
            $loantype = 'ALL';
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('COLLECTIBLES REPORT', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As Of: ' . date('m/d/Y', strtotime($start)), null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Borrower: ' . $borrower, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->col('Loan Type: ' . $loantype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '230', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '170', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('RPT/', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');


        foreach ($uniqueMonths as $month) {
            // var_dump($month);
            $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employees Name', '230', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Company', '170', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SBU', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Principal', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Interest', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Processing', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notarial', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TITLING/', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Loan Terms', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Monthly', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');

        //monthly
        foreach ($uniqueMonths as $month) {
            $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($month, '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Fee', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Fee', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HOA/MISC', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DST', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Receivables', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('(in month)', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Due Date', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amortization', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        //monthly
        foreach ($uniqueMonths as $month) {
            $str .= $this->reporter->col('Principal', '100', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Interest', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Other Fees', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Total', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();


        return $str;
    }

    public function reportDefaultLayout_orig($config)
    {
        $result = $this->reportDefault($config);
        $month_withbal = $this->balanceqry($config);
        $count = 10;
        $page = 10;
        $layoutsize = '1280';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $month_withbal = json_decode(json_encode($month_withbal), true);
        $dateids = array_column($month_withbal, 'dateid');
        $dateids = array_filter($dateids);         // remove empty
        $dateids = array_unique($dateids);         // remove duplicates
        sort($dateids);                            // sort ascending (by date)
        // layoutsize
        $layoutsize = 1280 + (count($dateids) * 420);

        // month lookup structure -sample
        // $monthlookup['trno_clientid']['dateid'] = ['principal' => x, 'interest' => y, 'others' => z];

        $monthlookup = [];
        foreach ($month_withbal as $array) {
            $lookupKey = $array['trno'] . '_' . $array['clientid'];
            $dateid = $array['dateid'];

            if (!isset($monthlookup[$lookupKey])) {
                $monthlookup[$lookupKey] = [];
            }

            $monthlookup[$lookupKey][$dateid] = [
                'principal' => isset($array['principal']) ? $array['principal'] : 0,
                'interest'  => isset($array['interest']) ? $array['interest'] : 0,
                'others'    => isset($array['otherfee']) ? $array['otherfee'] : 0,
            ];
        }


        // var_dump($monthlookup; //trno_clientid

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $terms_in_month = intval($data->terms); // sample data 6 MOS
            // $effectivity = $data->datehere; // mula 2025-05-15 ay bibilang ng 6 months =duedate
            $due_date = date('Y-m-d', strtotime('+' . $terms_in_month . ' months', strtotime($data->datehere)));

            $tlreceivable = $data->principal + $data->interest + $data->prfee + $data->notarialfee;
            $lookupKey = $data->cvtrno . '_' . $data->clientid;

            $str .= $this->reporter->col($data->datehere, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->company, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->sbu, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->status, '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->principal != 0 ? number_format($data->principal, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->interest != 0 ? number_format($data->interest, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->prfee != 0 ? number_format($data->prfee, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->notarialfee != 0 ?  number_format($data->notarialfee, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->notarialfee != 0 ? number_format($data->rpt, 2) : '', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dst != 0 ? number_format($data->dst, 2) : '', '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->tlreceivable != 0 ? number_format($tlreceivable, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($terms_in_month, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($due_date, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->amortization != 0 ? number_format($data->amortization, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

            foreach ($dateids as $dateid) {
                if (isset($monthlookup[$lookupKey][$dateid])) {
                    $monthData = $monthlookup[$lookupKey][$dateid];
                    $principal = $monthData['principal'];
                    $interest  = $monthData['interest'];
                    $others    = $monthData['others'];
                } else {
                    $principal = $interest = $others = 0;
                }

                $str .= $this->reporter->col($principal != 0 ? number_format($principal, 2) : '', '100', null, false, $border, 'L', 'RT', $font, $fontsize);
                $str .= $this->reporter->col($interest != 0 ? number_format($interest, 2) : '', '120', null, false, $border, 'L', 'RT', $font, $fontsize);
                $str .= $this->reporter->col($others != 0 ?  number_format($others, 2) : '', '100', null, false, $border, 'L', 'RT', $font, $fontsize);
                $str .= $this->reporter->col('', '100', null, false, $border, 'LR', 'RT', $font, $fontsize);
            }

            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $month_withbal = $this->balanceqry($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // Halimbawa ng structure ng month lookup
        // $monthlookup['trno_clientid']['dateid'] = ['principal' => x, 'interest' => y, 'others' => z];

        // convertion ng month_withbal sa associative array
        $month_withbal = json_decode(json_encode($month_withbal), true);

        // Kumuha ng listahan ng unique date IDs mula sa data
        $dateids = array_unique(array_filter(array_column($month_withbal, 'dateid')));

        // I-sort ang mga date asc-pataas
        sort($dateids);

        // computation ng laki ng layout base sa nakuhang bialang ng mga mga months 
        $layoutsize = 1360 + (count($dateids) * 420);

        // I-initialize ang lookup array para sa monthly balances
        $monthlookup = [];

        // Loop sa bawat record ng monthly balance ,
        foreach ($month_withbal as $array) {
            // Gumawa ng key base sa clientid at dateid
            $lookupKey = $array['clientid'] . '_' . $array['dateid'];

            // Kung wala pa ang key, i-initialize ito
            if (!isset($monthlookup[$lookupKey])) {
                $monthlookup[$lookupKey] = array('principal' => 0, 'interest' => 0, 'others' => 0);
            }

            // Idagdag ang mga amount sa principal, interest, at others
            $monthlookup[$lookupKey]['principal'] += isset($array['principal']) ? $array['principal'] : 0;
            $monthlookup[$lookupKey]['interest']  += isset($array['interest']) ? $array['interest'] : 0;
            $monthlookup[$lookupKey]['others']    += isset($array['otherfee']) ? $array['otherfee'] : 0;
        }

        $gtprincipal = [];
        $gtinterest  = [];
        $gtothers    = [];
        $gtmonth     = [];



        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->displayHeader($config);

        // Main loop
        for ($i = 0; $i < count($result); $i++) {
            $data = $result[$i];

            // Gumawa ng unique key para makilala ang grupo ng magkakaparehong row
            $currKey = $data->datehere . '_' . $data->clientname . '_' . $data->clientid . '_' . $data->company . '_' . $data->status . '_' . $data->principal . '_' . $data->interest . '_' . $data->prfee . '_' . $data->notarialfee . '_' . $data->terms . '_' . $data->rpt . '_' . $data->dst . '_' . $data->amortization . '_' . $data->sbu;

            // Loop para pagsamahin ang mga row na may parehong key (grouping)
            for ($j = $i + 1; $j < count($result); $j++) {
                $next = $result[$j];

                // Gumawa ng key para sa next na record
                $nextKey = $next->datehere . '_' . $next->clientname . '_' . $next->clientid . '_' . $next->company . '_' . $next->status . '_' . $next->principal . '_' . $next->interest . '_' . $next->prfee . '_' . $next->notarialfee . '_' . $next->terms . '_' . $next->rpt . '_' . $next->dst . '_' . $next->amortization . '_' . $data->sbu;

                // Kung pareho ang key, pagsamahin ang mga ampunt
                if ($currKey == $nextKey) {
                    $data->principal   += $next->principal;
                    $data->interest    += $next->interest;
                    $data->prfee       += $next->prfee;
                    $data->notarialfee += $next->notarialfee;
                    $data->amortization += $next->amortization;

                    //laktawan ang pinagsamang row
                    $i = $j;
                } else {
                    break;
                }
            }

            // computation ng due date base sa terms buwan iyan
            $terms_in_month = intval($data->terms);
            $due_date = date('Y-m-d', strtotime('+' . $terms_in_month . ' months', strtotime($data->datehere)));

            //total receivable
            $tlreceivable = $data->principal + $data->interest + $data->prfee + $data->notarialfee;

            // Prefix para sa lookup key ng buwan
            $lookupPrefix = $data->clientid . '_';

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            // main column dito
            $str .= $this->reporter->col($data->datehere, '80', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col($data->clientname, '230', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col($data->company, '170', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col($data->sbu, '80', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'LT', $font, $fontsize);
            $str .= $this->reporter->col($data->principal != 0 ? number_format($data->principal, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->interest != 0 ? number_format($data->interest, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->prfee != 0 ? number_format($data->prfee, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->notarialfee != 0 ?  number_format($data->notarialfee, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->rpt != 0 ? number_format($data->rpt, 2) : '', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dst != 0 ? number_format($data->dst, 2) : '', '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($tlreceivable != 0 ? number_format($tlreceivable, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($terms_in_month, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($due_date, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->amortization != 0 ? number_format($data->amortization, 2) : '', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

            // Loop para sa bawat buwan
            foreach ($dateids as $dateid) {
                // Buong key para sa lookup
                $lookupKey = $lookupPrefix . $dateid;
                // Kuhanin ang data ng buwan kung mayroon
                $m = isset($monthlookup[$lookupKey]) ? $monthlookup[$lookupKey] : array('principal' => 0, 'interest' => 0, 'others' => 0);
                $tlmonth = $m['principal'] + $m['interest'] + $m['others'];
                $str .= $this->reporter->col($m['principal'] ? number_format($m['principal'], 2) : '', '100', null, false, $border, 'L', 'RT', $font, $fontsize);
                $str .= $this->reporter->col($m['interest'] ? number_format($m['interest'], 2) : '', '120', null, false, $border, 'L', 'RT', $font, $fontsize);
                $str .= $this->reporter->col($m['others'] ? number_format($m['others'], 2) : '', '100', null, false, $border, 'L', 'RT', $font, $fontsize);
                $str .= $this->reporter->col($tlmonth != 0 ? number_format($tlmonth, 2) : '', '100', null, false, $border, 'LR', 'RT', $font, $fontsize);

                if (!isset($gtprincipal[$dateid])) {
                    $gtprincipal[$dateid] = 0;
                    $gtinterest[$dateid] = 0;
                    $gtothers[$dateid] = 0;
                    $gtmonth[$dateid] = 0;
                }
                //kukunin para sa grandtotal
                $gtprincipal[$dateid] += $m['principal'];
                $gtinterest[$dateid]  += $m['interest'];
                $gtothers[$dateid]    += $m['others'];
                $gtmonth[$dateid]     += $tlmonth;
            }
            $str .= $this->reporter->endrow();
        }

        //grandtotal per month
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '230', null, false, $border, 'T', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '170', null, false, $border, 'T', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'RT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'RT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'RT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'RT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'LT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'RT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'CT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'CT', $font, $fontsize);
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'RT', $font, $fontsize);

        foreach ($dateids as $dateid) {
            // $str .= $this->reporter->col(isset($gtprincipal[$dateid]) ? number_format($gtprincipal[$dateid], 2) : '', '100', null, false, $border, 'T', 'RT', $font, $fontsize);
            // $str .= $this->reporter->col(isset($gtinterest[$dateid])  ? number_format($gtinterest[$dateid], 2)  : '', '120', null, false, $border, 'T', 'RT', $font, $fontsize);
            // $str .= $this->reporter->col(isset($gtothers[$dateid])    ? number_format($gtothers[$dateid], 2)    : '', '100', null, false, $border, 'T', 'RT', $font, $fontsize);
            // $str .= $this->reporter->col(isset($gtmonth[$dateid])     ? number_format($gtmonth[$dateid], 2)     : '', '100', null, false, $border, 'T', 'RT', $font, $fontsize);
            $str .= $this->reporter->col($gtprincipal[$dateid] != 0  ? number_format($gtprincipal[$dateid], 2) : '', '100', null, false, $border, 'T', 'RT', $font, $fontsize);
            $str .= $this->reporter->col($gtinterest[$dateid] != 0 ? number_format($gtinterest[$dateid], 2)  : '', '120', null, false, $border, 'T', 'RT', $font, $fontsize);
            $str .= $this->reporter->col($gtothers[$dateid] != 0 ? number_format($gtothers[$dateid], 2) : '', '100', null, false, $border, 'T', 'RT', $font, $fontsize);
            $str .= $this->reporter->col($gtmonth[$dateid] != 0 ? number_format($gtmonth[$dateid], 2) : '', '100', null, false, $border, 'T', 'RT', $font, $fontsize);
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class