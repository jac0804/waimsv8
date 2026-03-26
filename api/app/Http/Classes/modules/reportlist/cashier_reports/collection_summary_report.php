<?php

namespace App\Http\Classes\modules\reportlist\cashier_reports;

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

class collection_summary_report
{
    public $modulename = 'Collection Summary Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1400'];


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
        $fields = ['radioprint', 'start', 'dcentername']; //dclientname //, 'start', 'dcentername'
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'start.required', true);
        data_set($col1, 'start.label', 'Date');
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        return $this->coreFunctions->opentable("select 
        'default' as print,
        date(now()) as start, date_add(date(now()),interval 1 month) as end,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername");
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

        return $this->reportDefaultLayout($config);
    }



    public function reportDefault($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }

        $query = "select sum(amount) as amount, datenow, center,name as branch
                        from (
                        select sum(hce.amount) as amount, date(hce.dateid) as datenow,num.center,c.name
                        from hcehead as hce
                        left join reqcategory as r on r.line = hce.trnxtid and r.isttype =1
                        left join transnum as num on num.trno=hce.trno
                        left join center as c on c.code=num.center
                        where r.category not in ('REFUND','SUBSIDY') and date(hce.dateid)   = '$start'  $filter
                        group by date(hce.dateid),num.center,c.name
                        union all
                        select sum(ce.amount) as amount, date(ce.dateid) as datenow,num.center,c.name
                        from cehead as ce
                        left join reqcategory as r on r.line = ce.trnxtid and r.isttype =1
                        left join transnum as num on num.trno=ce.trno
                        left join center as c on c.code=num.center
                        where r.category not in ('REFUND','SUBSIDY') and date(ce.dateid)   = '$start'  $filter
                        group by date(ce.dateid),num.center,c.name
                        union all
                        select sum(ce.amount) as amount, date(ce.dateid) as datenow,ce.center,c.name
                        from tcoll as ce
                        left join center as c on c.code=ce.center
                        where date(ce.dateid)  = '$start' $filter2
                        group by date(ce.dateid),ce.center,c.name) as xm
                        group by datenow,name,center
                        order by datenow desc";

        return $this->coreFunctions->opentable($query);
    }


    public function banknames($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }

        $query = "  select sum(amount) as amount,bankname,datenow,center,docno from (
                       select sum(amount) as amount,acnoname as bankname,datenow,center,docno from (
                        select head.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as acnoname,date(dx.dateid) as datenow,num.center,num.docno  from dxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                        group by head.amount,coa.acnoname,date(dx.dateid),num.center,num.docno

                        union all
                        select head.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as  acnoname,date(dx.dateid) as datenow,num.center,num.docno  from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                        group by head.amount,coa.acnoname,date(dx.dateid),num.center,num.docno

                        union all

                        select dx.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as  acnoname,date(dx.dateid) as datenow,num.center,num.docno   from dxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Cash')
                        and date(dx.dateid) = '$start'  $filter
                        group by dx.amount,coa.acnoname,date(dx.dateid),num.center,num.docno

                        union all
                        select dx.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as  acnoname,date(dx.dateid) as datenow,num.center,num.docno from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Cash')
                        and date(dx.dateid) = '$start'  $filter
                        group by dx.amount,coa.acnoname,date(dx.dateid),num.center,num.docno

                        union all
                        select head.amount,(case paymode.category when 'BANK TRANSFER' then head.acnoname else paymode.category end) as acnoname,
                         date(head.dateid) as datenow,num.center,'' as docno
                         from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        where paymode.category not IN ('Cash','Check')  and ttype.category not in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start'  $filter
                        group by head.amount,paymode.category,head.acnoname,date(head.dateid),num.center
                        ) as a 
                        group by bankname,datenow,center,docno

                        union all

                        select sum(ce.amount) as amount,ce.bank as bankname,date(ce.dateid) as datenow,ce.center,'' as docno  from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                        and date(ce.depodate)='$start' and date(ce.dateid)='$start'  $filter2
                        group by ce.bank,date(ce.dateid),ce.center

                        union all

                        select sum(ce.amount) as amount,paymode.category as bankname,date(ce.dateid) as datenow,ce.center,'' as docno from tcoll as ce
                        LEFT JOIN transnum AS num ON num.trno = ce.trno
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        where ce.doc <>'DX' and  paymode.category not in ('Check')
                        and date(ce.dateid) = '$start'  $filter2
                        group by paymode.category,date(ce.dateid),ce.center ) as xm
                        group by datenow,bankname,center,docno
                        order by datenow desc";
        return $this->coreFunctions->opentable($query);
    }



    public function count_all_bankname($config, $data)
    {
        $count = 0;
        foreach ($data as $i => $value) {
            $count++;
        }
        return $count;
    }


    private function displayHeader($layoutsize, $border, $font, $fontsize, $config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $bankname = $this->banknames($config);
        $bankname_count = $this->count_all_bankname($config, $bankname);
        $layoutsize = 480 + ($bankname_count * 100);

        $bankLookup = [];
        foreach ($bankname as $array_index => $array) {
            $lookupKey = $array->center . '_' . $array->datenow;
            $bankLookup[$lookupKey][$array->bankname] = $array->bankname;
        }

        $str = '';
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
        $str .= $this->reporter->col('COLLECTION REPORT', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATED', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRANCHES', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL COLLECTIONS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        foreach ($bankLookup as $key => $banks) {
            foreach ($banks as $bankname) {
                $bankname = trim($bankname);
                if (strtoupper($bankname) != 'CASH IN BANK' && strtoupper($bankname) != 'CASH') {
                    $bankname = preg_replace('/^CASH IN BANK[\s-]*/i', '', $bankname);
                }
                $str .= $this->reporter->col(strtoupper($bankname), '100', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
            }
        }

        $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();


        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $count = 10;
        $page = 10;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $bankname = $this->banknames($config);
        $bankname_count = $this->count_all_bankname($config, $bankname);
        $layoutsize = 480 + ($bankname_count * 100);

        $bankLookup = [];
        foreach ($bankname as $array_index => $array) {
            // Combine center and datenow as a unique key
            $lookupKey = $array->center . '_' . $array->datenow;
            $bankLookup[$lookupKey][$array->bankname] = isset($array->amount) ? number_format($array->amount, 2) : '0.00';
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->displayHeader($this->reportParams['layoutSize'], $border, $font, $fontsize, $config);
        $grandTotals = [];
        $totalbal = 0;
        foreach ($result as $key => $data) {

            $dateid = $data->datenow;
            $branch = $data->branch;
            $center = $data->center;
            $amount = $data->amount;
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '20', 0);
            $arr_branch = $this->reporter->fixcolumn([$branch], '24', 0);
            $arr_amount = $this->reporter->fixcolumn([$amount], '20', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_branch]);
            for ($r = 0; $r < $maxrow; $r++) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col(' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '80', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_branch[$r]) ? $arr_branch[$r] : ''), '100', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
                $amountValue = isset($arr_amount[$r]) ? $arr_amount[$r] : '';
                $amountValue = (float) $amountValue;
                // Round the value to 2 decimal places
                $amountValue = round($amountValue, 2);
                $amt = number_format($amountValue, 2);

                if ($r == 0) {
                    $str .= $this->reporter->col($amt, '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
                } else {
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
                }
                $lookupKey = $center . '_' . $dateid;
                $totalBankAmount = 0;

                if ($r == 0) {
                    if (isset($bankLookup[$lookupKey])) {
                        foreach ($bankLookup[$lookupKey] as $bankname => $bankTotal) {
                            $bankTotal = str_replace(',', '', $bankTotal);
                            $bankTotal = (float) $bankTotal;
                            $totalBankAmount += $bankTotal;

                            if (!isset($grandTotals[$bankname])) {
                                $grandTotals[$bankname] = 0;
                            }
                            $grandTotals[$bankname] += $bankTotal;
                            // var_dump($grandTotals);

                            $str .= $this->reporter->col(number_format($bankTotal, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
                        }
                        $balance = $amount - $totalBankAmount;
                        $totalbal += $balance;
                        $str .= $this->reporter->col(number_format($balance, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
                    } else {
                        $str .= $this->reporter->col('-', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
                    }
                } else {
                    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
                }
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('TOTAL: ', '100', null, false, '1px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
        foreach ($grandTotals as $bankname => $total) {
            $str .= $this->reporter->col(number_format($total, 2), '100', null, false, '1px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class