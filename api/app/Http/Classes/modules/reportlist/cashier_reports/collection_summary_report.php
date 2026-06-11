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
        $fields = ['radioprint', 'start', 'dcentername', 'radioreporttype']; //dclientname //, 'start', 'dcentername'
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'start.required', true);
        data_set($col1, 'start.label', 'Date');
        data_set($col1, 'radioreporttype.label', 'Options');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'Per user', 'value' => 'user', 'color' => 'red']
        ]);
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
        'default' as reporttype,
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
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {

            case 'default':
                return $this->reportDefaultLayout($config);
                break;
            case 'user':
                return $this->per_userLayout($config);
                break;
        }
    }



    public function reportDefault($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $filter = "";
        $filter2 = "";
        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }

        $isUser = $reporttype == 'user';

        $selectUsers = $groupByUsers = $isUser ? ", users"  : "";
        $orderby = $isUser ? "order by users, datenow desc" : "order by datenow desc";

        $query = "select sum(amount) as amount, datenow, center, name as branch $selectUsers
            from (
                select sum(hce.amount) as amount, date(hce.dateid) as datenow, num.center, c.name, hce.createby as users
                from hcehead as hce
                left join reqcategory as r on r.line = hce.trnxtid and r.isttype = 1
                left join transnum as num on num.trno = hce.trno
                left join center as c on c.code = num.center
                where r.category not in ('REFUND','SUBSIDY') and date(hce.dateid) = '$start' $filter
                group by date(hce.dateid), num.center, c.name, hce.createby
                union all
                select sum(ce.amount) as amount, date(ce.dateid) as datenow, num.center, c.name, ce.createby as users
                from cehead as ce
                left join reqcategory as r on r.line = ce.trnxtid and r.isttype = 1
                left join transnum as num on num.trno = ce.trno
                left join center as c on c.code = num.center
                where r.category not in ('REFUND','SUBSIDY') and date(ce.dateid) = '$start' $filter
                group by date(ce.dateid), num.center, c.name, ce.createby
                union all
                select sum(ce.amount) as amount, date(ce.dateid) as datenow, ce.center, c.name, ce.createby as users
                from tcoll as ce
                left join center as c on c.code = ce.center
                where date(ce.dateid) = '$start' $filter2
                group by date(ce.dateid), ce.center, c.name, ce.createby
            ) as xm
            group by datenow, name, center $groupByUsers
            $orderby";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    public function banknames($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center     = $config['params']['dataparams']['center'];
        $reporttype = isset($config['params']['dataparams']['reporttype']) ? $config['params']['dataparams']['reporttype'] : '';

        $filter  = "";
        $filter2 = "";

        if ($center != "") {
            $filter  .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }

        $isUser = $reporttype == 'user';

        $selectDx    = $isUser ? ", dx.createby"   : "";
        $selectHead  = $isUser ? ", head.createby" : "";
        $selectCe    = $isUser ? ", ce.createby"   : "";
        $selectOuter = $isUser ? ", createby"      : "";
        $groupDx     = $isUser ? ", dx.createby"   : "";
        $groupHead   = $isUser ? ", head.createby" : "";
        $groupCe     = $isUser ? ", ce.createby"   : "";
        $groupOuter  = $isUser ? ", createby"      : "";
        $orderby     = $isUser ? "order by createby, datenow desc" : "order by datenow desc";

        $query = "  select sum(amount) as amount,bankname,datenow,center,docno $selectOuter from (
                   select sum(amount) as amount,acnoname as bankname,datenow,center,docno $selectOuter from (
                    select head.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as acnoname,date(dx.dateid) as datenow,num.center,num.docno $selectDx from dxhead as dx
                    LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                    LEFT JOIN hcehead AS head ON head.trno = num.trno
                    LEFT JOIN coa ON coa.acnoid = dx.bank
                    LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                    where  paymode.category IN ('Check')
                    and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                    group by head.amount,coa.acnoname,date(dx.dateid),num.center,num.docno, num.bref, num.seq $groupDx

                    union all
                    select head.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as acnoname,date(dx.dateid) as datenow,num.center,num.docno $selectDx from hdxhead as dx
                    LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                    LEFT JOIN hcehead AS head ON head.trno = num.trno
                    LEFT JOIN coa ON coa.acnoid = dx.bank
                    LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                    where  paymode.category IN ('Check')
                    and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                    group by head.amount,coa.acnoname,date(dx.dateid),num.center,num.docno, num.bref, num.seq $groupDx

                    union all

                    select dx.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as acnoname,date(dx.dateid) as datenow,num.center,num.docno $selectDx from dxhead as dx
                    LEFT JOIN transnum AS num ON num.trno = dx.trno
                    LEFT JOIN coa ON coa.acnoid = dx.bank
                    LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                    where  paymode.category IN ('Cash')
                    and date(dx.dateid) = '$start'  $filter
                    group by dx.amount,coa.acnoname,date(dx.dateid),num.center,num.docno, num.bref, num.seq $groupDx

                    union all
                    select dx.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as acnoname,date(dx.dateid) as datenow,num.center,num.docno $selectDx from hdxhead as dx
                    LEFT JOIN transnum AS num ON num.trno = dx.trno
                    LEFT JOIN coa ON coa.acnoid = dx.bank
                    LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                    where  paymode.category IN ('Cash')
                    and date(dx.dateid) = '$start'  $filter
                    group by dx.amount,coa.acnoname,date(dx.dateid),num.center,num.docno, num.bref, num.seq $groupDx

                    union all
                    select head.amount,(case paymode.category when 'BANK TRANSFER' then head.acnoname else paymode.category end) as acnoname,
                     date(head.dateid) as datenow,num.center,'' as docno $selectHead
                     from hcehead as head
                    LEFT JOIN transnum AS num ON num.trno = head.trno
                    LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                    LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                    where paymode.category not IN ('Cash','Check')  and ttype.category not in ('REFUND','SUBSIDY')
                    and date(head.dateid)='$start'  $filter
                    group by head.amount,paymode.category,head.acnoname,date(head.dateid),num.center $groupHead
                    ) as a 
                    group by bankname,datenow,center,docno $groupOuter

                    union all

                    select sum(ce.amount) as amount,ce.bank as bankname,date(ce.dateid) as datenow,ce.center,'' as docno $selectCe from tcoll as ce
                    left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                    where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                    and date(ce.depodate)='$start' and date(ce.dateid)='$start'  $filter2
                    group by ce.bank,date(ce.dateid),ce.center $groupCe

                    union all

                    select sum(ce.amount) as amount,paymode.category as bankname,date(ce.dateid) as datenow,ce.center,'' as docno $selectCe from tcoll as ce
                    LEFT JOIN transnum AS num ON num.trno = ce.trno
                    left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                    where ce.doc <>'DX' and  paymode.category not in ('Check')
                    and date(ce.dateid) = '$start'  $filter2
                    group by paymode.category,date(ce.dateid),ce.center $groupCe ) as xm
                    group by datenow,bankname,center,docno $groupOuter
                    $orderby";

        // var_dump($query);
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
        $reporttype = $config['params']['dataparams']['reporttype'];
        $bankname = $this->banknames($config);
        $bankname_count = $this->count_all_bankname($config, $bankname);
        $layoutsize = 480 + ($bankname_count * 100);

        $bankLookup = [];
        foreach ($bankname as $array_index => $array) {
            $lookupKey = $array->center . '_' . $array->datenow;
            $bankLookup[$lookupKey][$array->bankname] = $array->bankname;
        }

        // var_dump($bankLookup);

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
        if ($reporttype == 'user') {
            $str .= $this->reporter->col('Cashier', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();


        return $str;
    }


    public function per_userLayout($config)
    {
        $result = $this->reportDefault($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $bankname = $this->banknames($config);
        $bankname_count = $this->count_all_bankname($config, $bankname);
        $layoutsize = 480 + ($bankname_count * 100);

        $reporttype = isset($config['params']['dataparams']['reporttype']) ? $config['params']['dataparams']['reporttype'] : '';
        $isUserReport = $reporttype == 'user';

        $bankLookup = [];
        foreach ($bankname as $array) {
            $lookupKey = $isUserReport
                ? $array->center . '_' . $array->datenow . '_' . $array->createby
                : $array->center . '_' . $array->datenow;
            $bankLookup[$lookupKey][$array->bankname] = isset($array->amount) ? number_format($array->amount, 2) : '0.00';
        }

        $uniqueBanks = [];
        foreach ($bankname as $array) {
            $bname = trim($array->bankname);
            if (!in_array($bname, $uniqueBanks)) {
                $uniqueBanks[] = $bname;
            }
        }


        $groupedByUser = [];
        foreach ($result as $data) {
            $user = $isUserReport ? $data->users : 'ALL';
            $lookupKey = $isUserReport
                ? $data->center . '_' . $data->datenow . '_' . $data->users
                : $data->center . '_' . $data->datenow;
            if (!isset($groupedByUser[$user])) {
                $groupedByUser[$user] = [];
            }
            $groupedByUser[$user][$lookupKey] = $data;
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($this->reportParams['layoutSize'], $border, $font, $fontsize, $config);

        $grandTotalAmount = 0;
        $grandTotals = [];
        $grandTotalBal = 0;

        foreach ($groupedByUser as $username => $branches) {


            if ($isUserReport) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('USER: ' . strtoupper($username), $layoutsize, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $str .= $this->reporter->begintable($layoutsize);

            $userTotalAmount = 0;
            $userBankTotals = [];
            $userTotalBal = 0;

            foreach ($branches as $lookupKey => $data) {
                $dateid   = $data->datenow;
                $branch   = $data->branch;
                $center   = $data->center;
                $amount   = (float) $data->amount;
                $cashiers = isset($data->users) ? $data->users : '';
                $userTotalAmount += $amount;

                $arr_dateid = $this->reporter->fixcolumn([$dateid], '20', 0);
                $arr_branch = $this->reporter->fixcolumn([$branch], '24', 0);
                $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_branch]);

                for ($r = 0; $r < $maxrow; $r++) {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->col(' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col(' ' . (isset($arr_branch[$r]) ? $arr_branch[$r] : ''), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($r == 0 ? number_format($amount, 2) : '', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

                    if ($r == 0) {
                        $rowBankAmount = 0;
                        foreach ($uniqueBanks as $bname) {
                            $bankTotal = isset($bankLookup[$lookupKey][$bname]) ? (float) str_replace(',', '', $bankLookup[$lookupKey][$bname]) : 0;
                            $rowBankAmount += $bankTotal;

                            if (!isset($userBankTotals[$bname])) {
                                $userBankTotals[$bname] = 0;
                            }
                            $userBankTotals[$bname] += $bankTotal;

                            if (!isset($grandTotals[$bname])) {
                                $grandTotals[$bname] = 0;
                            }
                            $grandTotals[$bname] += $bankTotal;

                            $str .= $this->reporter->col($bankTotal > 0 ? number_format($bankTotal, 2) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                        }
                        $balance = $amount - $rowBankAmount;
                        $userTotalBal += $balance;
                        $grandTotalBal += $balance;
                        $str .= $this->reporter->col(number_format($balance, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');


                        if ($isUserReport) {
                            $str .= $this->reporter->col($cashiers, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                        }
                    } else {
                        foreach ($uniqueBanks as $bname) {
                            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                        }
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');


                        if ($isUserReport) {
                            $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                        }
                    }
                    $str .= $this->reporter->endrow();
                }
            }

            $str .= $this->reporter->endtable();

            // subtotal row
            $grandTotalAmount += $userTotalAmount;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('SUBTOTAL:', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($userTotalAmount, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            foreach ($uniqueBanks as $bname) {
                $ubt = isset($userBankTotals[$bname]) ? $userBankTotals[$bname] : 0;
                $str .= $this->reporter->col($ubt > 0 ? number_format($ubt, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            }
            $str .= $this->reporter->col(number_format($userTotalBal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            if ($isUserReport) {
                $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
            }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= '<br/>';
        }

        // grand total row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('TOTAL:', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandTotalAmount, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        foreach ($uniqueBanks as $bname) {
            $gt = isset($grandTotals[$bname]) ? $grandTotals[$bname] : 0;
            $str .= $this->reporter->col($gt > 0 ? number_format($gt, 2) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col(number_format($grandTotalBal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        if ($isUserReport) {
            $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }


    private function default_displayHeader($layoutsize, $border, $font, $fontsize, $config, $bankColumns = [])
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $layoutsize = 480 + (count($bankColumns) * 100);

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
        $str .= $this->reporter->col('DATED', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRANCHES', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL COLLECTIONS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        foreach ($bankColumns as $bankname) {
            $str .= $this->reporter->col(strtoupper($bankname), '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        if ($reporttype == 'user') {
            $str .= $this->reporter->col('Cashier', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $bankname = $this->banknames($config);

        $bankColumns = [];
        $bankLookup = [];

        foreach ($bankname as $array) {
            $cleanBank = trim($array->bankname);

            if (strtoupper($cleanBank) != 'CASH IN BANK' && strtoupper($cleanBank) != 'CASH') {
                $cleanBank = preg_replace('/^CASH IN BANK[\s-]*/i', '', $cleanBank);
            }

            $bankColumns[$cleanBank] = $cleanBank;

            $lookupKey = $array->center . '_' . $array->datenow;

            if (!isset($bankLookup[$lookupKey])) {
                $bankLookup[$lookupKey] = [];
            }

            if (!isset($bankLookup[$lookupKey][$cleanBank])) {
                $bankLookup[$lookupKey][$cleanBank] = 0;
            }

            $bankLookup[$lookupKey][$cleanBank] += isset($array->amount) ? (float) str_replace(',', '', $array->amount) : 0;
        }

        $bankColumns = array_values($bankColumns);
        $layoutsize = 480 + (count($bankColumns) * 100);

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($layoutsize, $border, $font, $fontsize, $config, $bankColumns);

        $str .= $this->reporter->begintable($layoutsize);

        $grandTotals = [];
        $totalbal = 0;

        foreach ($result as $key => $data) {
            $dateid = $data->datenow;
            $branch = $data->branch;
            $center = $data->center;
            $amount = isset($data->amount) ? (float) str_replace(',', '', $data->amount) : 0;

            $arr_dateid = $this->reporter->fixcolumn([$dateid], '20', 0);
            $arr_branch = $this->reporter->fixcolumn([$branch], '24', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_branch]);

            for ($r = 0; $r < $maxrow; $r++) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col(' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_branch[$r]) ? $arr_branch[$r] : ''), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

                if ($r == 0) {
                    $str .= $this->reporter->col(number_format($amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

                    $lookupKey2 = $center . '_' . $dateid;
                    $totalBankAmount = 0;

                    foreach ($bankColumns as $bankname) {
                        $bankTotal = isset($bankLookup[$lookupKey2][$bankname]) ? (float) $bankLookup[$lookupKey2][$bankname] : 0;
                        $totalBankAmount += $bankTotal;

                        if (!isset($grandTotals[$bankname])) {
                            $grandTotals[$bankname] = 0;
                        }

                        $grandTotals[$bankname] += $bankTotal;

                        $str .= $this->reporter->col($bankTotal == 0 ? '' : number_format($bankTotal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                    }

                    $balance = $amount - $totalBankAmount;
                    $totalbal += $balance;

                    $str .= $this->reporter->col(number_format($balance, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                } else {
                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

                    foreach ($bankColumns as $bankname) {
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                    }

                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                }

                $str .= $this->reporter->endrow();
            }
        }

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('TOTAL: ', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

        foreach ($bankColumns as $bankname) {
            $total = isset($grandTotals[$bankname]) ? $grandTotals[$bankname] : 0;
            $str .= $this->reporter->col($total == 0 ? '' : number_format($total, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class