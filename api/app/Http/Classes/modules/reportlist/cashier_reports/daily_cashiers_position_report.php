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
use Illuminate\Support\Facades\URL;
use App\Http\Classes\reportheader;


class daily_cashiers_position_report
{
    public $modulename = 'Daily Cashier\'s  Position Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

    private $nogroup = 1;

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
        $fields = ['radioprint', 'start', 'dcentername', 'amount', 'lblgrossprofit', 'tenthirty', 'thirtyfifty', 'fiftyhundred', 'hundredtwofifty', 'twofiftyfivehundred', 'fivehundredup', 'unit'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'start.label', 'Date');
        data_set($col1, 'amount.label', 'Change Fund');

        data_set($col1, 'lblgrossprofit.label', ' Denomination:');
        data_set($col1, 'lblgrossprofit.style', 'font-weight:bold; font-size:12px;');

        data_set($col1, 'tenthirty.type', 'input');
        data_set($col1, 'tenthirty.label', '1000');
        data_set($col1, 'tenthirty.readonly', false);
        data_set($col1, 'tenthirty.required', true);

        data_set($col1, 'thirtyfifty.type', 'input');
        data_set($col1, 'thirtyfifty.label', '500');
        data_set($col1, 'thirtyfifty.readonly', false);
        data_set($col1, 'thirtyfifty.required', true);

        data_set($col1, 'fiftyhundred.type', 'input');
        data_set($col1, 'fiftyhundred.label', '200');
        data_set($col1, 'fiftyhundred.readonly', false);
        data_set($col1, 'fiftyhundred.required', true);

        data_set($col1, 'hundredtwofifty.type', 'input');
        data_set($col1, 'hundredtwofifty.label', '100');
        data_set($col1, 'hundredtwofifty.readonly', false);
        data_set($col1, 'hundredtwofifty.required', true);


        data_set($col1, 'twofiftyfivehundred.type', 'input');
        data_set($col1, 'twofiftyfivehundred.label', '50');
        data_set($col1, 'twofiftyfivehundred.readonly', false);
        data_set($col1, 'twofiftyfivehundred.required', true);

        data_set($col1, 'fivehundredup.type', 'input');
        data_set($col1, 'fivehundredup.label', '20');
        data_set($col1, 'fivehundredup.readonly', false);
        data_set($col1, 'fivehundredup.required', true);

        data_set($col1, 'unit.type', 'input');
        data_set($col1, 'unit.label', 'Coins');
        data_set($col1, 'unit.readonly', false);
        data_set($col1, 'unit.required', true);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 
       'default' as print,
        adddate(date(now()),-360) as start,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,'0' as amount,
        '0' as tenthirty, '0' as thirtyfifty,'0' as fiftyhundred,'0' as hundredtwofifty,'0' as twofiftyfivehundred,'0' as fivehundredup, '0' as unit ";
        return $this->coreFunctions->opentable($paramstr);
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
        $result = $this->reportDefaultLayout_daily_cashier($config);

        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }
        $query = " select sum(amount) as amount, category
                        from (
                        select sum(hce.amount) as amount, pmode.category,hce.doc
                        from hcehead as hce
                        left join reqcategory as pmode on pmode.line = hce.mpid
                        left join transnum as num on num.trno=hce.trno
                        where date(hce.dateid)  = '$start' $filter
                        group by pmode.category,hce.doc

                        union all

                        select sum(ce.amount) as amount, pmode.category,ce.doc
                        from cehead as ce
                        left join reqcategory as pmode on pmode.line = ce.mpid
                         left join transnum as num on num.trno=ce.trno
                        where date(ce.dateid)  = '$start' $filter
                        group by pmode.category,ce.doc
                        union all

                        select sum(ce.amount) as amount, pmode.category,ce.doc
                        from tcoll as ce
                        left join reqcategory as pmode on pmode.line = ce.mpid
                        where date(ce.dateid)  = '$start' $filter2
                        group by pmode.category,ce.doc) as xm
                        group by category";
        // var_dump($query);
        // $query =" select sum(amount) as amount,bankname from (
        //                select sum(amount) as amount,acnoname as bankname from (
        //                 select dx.amount,coa.acnoname from dxhead as dx
        //                 LEFT JOIN transnum AS num ON num.dstrno = dx.trno
        //                 LEFT JOIN hcehead AS head ON head.trno = num.trno
        //                 LEFT JOIN coa ON coa.acnoid = dx.bank
        //                 LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
        //                 where num.dstrno <> 0 and paymode.category IN ('Cash','Check') 
        //                 and date(dx.dateid)='$start'  $filter
        //                 group by dx.amount,coa.acnoname

        //                 union all
        //                 select dx.amount,coa.acnoname from hdxhead as dx
        //                 LEFT JOIN transnum AS num ON num.dstrno = dx.trno
        //                 LEFT JOIN hcehead AS head ON head.trno = num.trno
        //                 LEFT JOIN coa ON coa.acnoid = dx.bank
        //                 LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
        //                 where num.dstrno <> 0 and paymode.category IN ('Cash','Check')
        //                 and date(dx.dateid)='$start' $filter
        //                 group by dx.amount,coa.acnoname
        //                 union all

        //                 select dx.amount,paymode.category as acnoname from dxhead as dx
        //                 LEFT JOIN transnum AS num ON num.dstrno = dx.trno
        //                 LEFT JOIN hcehead AS head ON head.trno = num.trno
        //                 LEFT JOIN coa ON coa.acnoid = dx.bank
        //                 LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
        //                 where num.dstrno <> 0 and paymode.category not IN ('Cash','Check') 
        //                 and date(dx.dateid)='$start'  $filter
        //                 group by dx.amount,paymode.category

        //                 union all
        //                 select dx.amount,paymode.category as acnoname from hdxhead as dx
        //                 LEFT JOIN transnum AS num ON num.dstrno = dx.trno
        //                 LEFT JOIN hcehead AS head ON head.trno = num.trno
        //                 LEFT JOIN coa ON coa.acnoid = dx.bank
        //                 LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
        //                 where num.dstrno <> 0 and paymode.category not IN ('Cash','Check')
        //                 and date(dx.dateid)='$start'  $filter
        //                 group by dx.amount,paymode.category

        //                 ) as a group by bankname

        //                union all


        //                 select sum(ce.amount) as amount,ce.bank as bankname from tcoll as ce
        //                 left join reqcategory as paymode on paymode.line = ce.mpid
        //                 where ce.dstrno<>0 and paymode.category in ('Cash','Check')
        //                 and date(ce.depodate)='$start' $filter2
        //                 group by ce.bank

        //               union all

        //                 select sum(ce.amount) as amount,paymode.category as bankname from tcoll as ce
        //                 left join reqcategory as paymode on paymode.line = ce.mpid
        //                 where ce.dstrno<>0 and paymode.category not in ('Cash','Check')
        //                 and date(ce.depodate)='$start' $filter2
        //                 group by paymode.category) as xm
        //                 group by bankname
        //                 order by bankname";

        return $this->coreFunctions->opentable($query);
    }


    public function qrypermode_og($config)
    {
        //dstrno
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }



        if ($this->nogroup == 1) { //total daily cash/checks
            $query = " select sum(amount) as amount
                        from (
                        select sum(hce.amount) as amount
                        from hcehead as hce
                        left join reqcategory as r on r.line = hce.trnxtid and r.isttype =1
                        left join transnum as num on num.trno=hce.trno
                        where r.category not in ('REFUND','SUBSIDY') and date(hce.dateid)  = '$start' $filter 
                        union all
                        select sum(ce.amount) as amount
                        from cehead as ce
                        left join reqcategory as r on r.line = ce.trnxtid and r.isttype =1
                        left join transnum as num on num.trno=ce.trno
                        where r.category not in ('REFUND','SUBSIDY') and date(ce.dateid)  = '$start' $filter
                        union all
                        select sum(ce.amount) as amount
                        from tcoll as ce
                        where date(ce.dateid)  = '$start' $filter2 ) as xm";
        } elseif ($this->nogroup == 2) { //less deposits
            $query = " select sum(amount) as amount,bankname from (
                       select sum(amount) as amount,acnoname as bankname from (
                        select head.amount,coa.acnoname from dxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                        group by head.amount,coa.acnoname

                        union all
                        select head.amount,coa.acnoname from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Check')  
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start' $filter
                        group by head.amount,coa.acnoname

                        union all

                        select dx.amount,coa.acnoname from dxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname

                        union all
                        select dx.amount,coa.acnoname from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname
                        
                        union all
                        select head.amount,(case paymode.category when 'BANK TRANSFER' then concat(paymode.category,'-',head.acnoname) else paymode.category end) as bankname from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        where paymode.category not IN ('Cash','Check')  and ttype.category not in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,paymode.category,head.acnoname
                        union all
                        select head.amount,ttype.category as bankname from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        where ttype.category  in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,ttype.category
                        
                        ) as a group by bankname

                        union all

                        select sum(ce.amount) as amount,ce.bank as bankname from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                        and date(ce.depodate)='$start' and date(ce.dateid) = '$start' $filter2
                        group by ce.bank

                        union all

                        select sum(ce.amount) as amount,paymode.category as bankname from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        where ce.doc <>'DX' and  paymode.category not in ('Check')
                        and date(ce.dateid) = '$start' $filter2
                        group by paymode.category) as xm
                        group by bankname
                        order by bankname";
        } elseif ($this->nogroup == 3) { //used series
            $query = "select
                    ifnull(group_concat(yourref order by yourref separator ', '), '') AS crno,
                    ifnull(group_concat(ourref order by ourref separator ', '), '') AS orno,
                    ifnull(group_concat(sicsino order by sicsino separator ', '), '') AS sicsi,
                    ifnull(group_concat(drno order by drno separator ', '), '') AS drno,
                    ifnull(group_concat(drno order by rslip separator ', '), '') AS rslip
                from (
                select head.yourref,head.ourref,head.sicsino,head.drno,head.rslip from cehead as head
                left join transnum as num on num.trno=head.trno
                where date(head.dateid)  = '$start'  $filter
                union all
                select head.yourref,head.ourref,head.sicsino,head.drno,head.rslip from hcehead as head
                left join transnum as num on num.trno=head.trno
                where date(head.dateid)  = '$start'  $filter ) as a";
                
        } else { //pdc
            $query = "
            select sum(amount) as amount,bank, checkdate,checkinfo from (
                select sum(detail.amount) as amount,detail.bank,date(detail.checkdate) as checkdate,detail.checkno as checkinfo
                    from hrchead as head
                    left join transnum as num on num.trno=head.trno
                    left join hrcdetail as detail on detail.trno = head.trno
                     where date(head.dateid) = '$start' $filter
                    group by detail.bank,detail.checkdate,detail.checkno
                    union all
                    select sum(detail.amount) as amount,detail.bank,date(detail.checkdate) as checkdate,detail.checkno as checkinfo
                    from rchead as head
                    left join transnum as num on num.trno=head.trno
                    left join rcdetail as detail on detail.trno = head.trno
                     where date(head.dateid) = '$start' $filter
                    group by detail.bank,detail.checkdate,detail.checkno) as a
                    group by bank,checkdate ,checkinfo
                    order by checkdate";
        }
        return $this->coreFunctions->opentable($query);
    }


    public function header_DEFAULT($config)
    {
        $center = $config['params']['dataparams']['center'];
        $centername = $config['params']['dataparams']['centername'];
        $username   = $config['params']['user'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $str = '';
        $layoutsize = '800';
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
        $str .= $this->reporter->col('Daily Cashier Position Report', null, null, false, $border, '', 'C', $font, '18', 'B', 'blue', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date: ' . $start, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');

        // $str .= $this->reporter->col('Branch Name: ' . $centername, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        if ($center == '') {
            $str .= $this->reporter->col('Branch Name :' . 'ALL', null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        } else {
            $str .= $this->reporter->col('Branch Name :' . $center . ' - ' . $centername, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_daily_cashier_og($config)
    {
        $result = $this->default_QUERY($config);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['center'];
        $inputamt = $config['params']['dataparams']['amount'];
        // var_dump($inputamt);
        $onekiaw = $config['params']['dataparams']['tenthirty'];
        $fivehundred = $config['params']['dataparams']['thirtyfifty'];
        $twohundred = $config['params']['dataparams']['fiftyhundred'];
        $onhundred = $config['params']['dataparams']['hundredtwofifty'];
        $fifty = $config['params']['dataparams']['twofiftyfivehundred'];
        $twenty = $config['params']['dataparams']['fivehundredup'];
        $unit = $config['params']['dataparams']['unit'];
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = 800;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        // if (empty($result)) {
        //     return $this->othersClass->emptydata($config);
        // }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Funds balance beginning:', '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $total = 0;
        $totalamount = 0;
        //BEG BALANCE       
        $cash = $this->coreFunctions->getfieldvalue("eod", "cash", "date(dateid)<?", [$start], "dateid desc", true);
        $checks = $this->coreFunctions->getfieldvalue("eod", "checks", "date(dateid)<?", [$start], "dateid desc", true);
        $total = $cash + $checks;

        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cash', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($cash, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Checks', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($checks, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($total, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Less Deposit :', '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $qry = "select sum(amount) as value from (
                        select head.amount from dxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
                        where paymode.category IN ('Cash','Check') 
                        and date(dx.dateid)='$start' and date(head.dateid) < '$start' and num.center ='$center'
                        union all
                        select head.amount from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
                        where paymode.category IN ('Cash','Check')
                        and date(dx.dateid)='$start' and date(head.dateid) < '$start' and num.center ='$center'  
                        union all
                        select dx.amount from tcoll as dx
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid
                        where paymode.category IN ('Cash','Check')
                        and date(dx.depodate)='$start' and date(dx.dateid) < '$start' and dx.center ='$center'                     
                        ) as a  ";

        $totalamount = $this->coreFunctions->datareader($qry, [], '', true); //cash/cheks

        $str .= $this->reporter->col('Cash/Checks :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalamount, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $totalcash_on_hand = $total - $totalamount;
        $str .= $this->reporter->col('Total cash on hand :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'red', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalcash_on_hand, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= str_repeat('*', 133);

        $totaldaily_cash = 0;
        $datar = $this->qrypermode($config);
        if ($this->nogroup && !empty($datar)) {
            foreach ($datar as $key => $datascr) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Total Daily Cash and Checks :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($datascr->amount, 2), '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totaldaily_cash += $datascr->amount;
            }
            $this->nogroup = 2;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Other Income', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $totalfundsavail = $totaldaily_cash + $totalcash_on_hand;
        $str .= $this->reporter->col('TOTAL FUNDS AVAILABLE :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalfundsavail, 2), '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LESS', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'red', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cash deposits ( choices: Palawan/ Finance Office/ Bank )', '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $totaltransac = 0;
        $data3 = $this->qrypermode($config);
        if ($this->nogroup && !empty($data3)) {
            foreach ($data3 as $key => $datahere) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($datahere->bankname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($datahere->amount, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totaltransac += $datahere->amount;
            }
            $this->nogroup = 3;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL TRANSACTIONS  CASH /CHECK/SUBSIDY/ONLINE', '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totaltransac, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();



        $funds = $totalfundsavail - $totaltransac;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FUNDS BALANCE END', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($funds, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('USED RECEIPTS:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $rslip = $this->coreFunctions->datareader("select group_concat(rslip order by rslip separator ', ') as value from (
            select concat(min(rslip),'-',max(rslip)) as rslip from hcehead as ce left join transnum as num on num.trno = ce.trno 
            where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and rslip <>'') as v");

        $crno = $this->coreFunctions->datareader("select group_concat(crno order by crno separator ', ') as value from (
        select concat(min(yourref),'-',max(yourref)) as crno from hcehead as ce left join transnum as num on num.trno = ce.trno 
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and yourref <>''
        union all
        select concat(min(yourref),'-',max(yourref)) as crno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and yourref <>'') as v");

        $drno = $this->coreFunctions->datareader("select group_concat(drno order by drno separator ', ') as value from (
        select concat(min(drno),'-',max(drno)) as drno from hcehead as ce left join transnum as num on num.trno = ce.trno 
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and drno <>''
        union all
        select concat(min(drno),'-',max(drno)) as drno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and drno <>'') as v");

        $sicsino = $this->coreFunctions->datareader("select group_concat(sicsino order by sicsino separator ', ') as value from (
            select concat(min(sicsino),'-',max(sicsino)) as sicsino from hcehead as ce left join transnum as num on num.trno = ce.trno 
            where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and ce.rem2=''
            union all
            select concat(min(sicsino),'-',max(sicsino)) as sicsino from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and trnxtype = 'MC UNIT') as v");

        $orno = $this->coreFunctions->datareader("select group_concat(orno order by orno separator ', ') as value from (
            select concat(min(ourref),'-',max(ourref)) as orno from hcehead as ce left join transnum as num on num.trno = ce.trno 
            where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and ourref <>''
            union all
            select concat(min(ourref),'-',max(ourref)) as orno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and ourref <>'') as v");

        $sicsinomc = $this->coreFunctions->datareader("select group_concat(sicsino order by sicsino separator ', ') as value from (
            select concat(min(sicsino),'-',max(sicsino)) as sicsino from hcehead as ce left join transnum as num on num.trno = ce.trno 
            where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and ce.rem2<>''
            union all
            select concat(min(sicsino),'-',max(sicsino)) as sicsino from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and trnxtype = 'MC UNIT') as v");

        $data4 = $this->qrypermode($config);
        if ($this->nogroup && !empty($data4)) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('COLLECTION RECEIPT', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($crno, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DR', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($drno, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('CSI PARTS', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($sicsino, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('CSI MC UNITS', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($sicsinomc, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('OFFICIAL RECEIPTS', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($orno, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('REFUND SLIP', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($rslip, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $this->nogroup = 4;
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL POST DATED CHECKS RECEIVED :', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'red', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE OF CHECK', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '300', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Name/Bank/Check No.', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $totalhere = 0;
        $data5 = $this->qrypermode($config);


        if ($this->nogroup && !empty($data5)) {
            foreach ($data5 as $key => $data5here) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data5here->checkdate, '200', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data5here->amount, 2), '300', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data5here->bank . ' ' . $data5here->checkinfo, '300', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totalhere += $data5here->amount;
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'LTB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalhere, 2), '300', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, 'TBR', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $center = $config['params']['center'];
        $petty = $this->coreFunctions->getfieldvalue("center", "petty", "code=?", [$center]);

        // $unrep = "select sum(amount) as value from ( select sum(amount) as amount from tcdetail where isreplenish <>1 
        //                                                             union all
        //                                                             select sum(amount) as amount from htcdetail where isreplenish <>1 ) as a";

        $unrep = "select sum(amount) as value from ( select sum(d.deduction) as amount
                    from tcdetail as d
                    left join tchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    where isreplenish <>1 and  num.center ='" . $center . "' and date(head.dateid) >='" . $start . "'
              union all
                    select sum(d.deduction) as amount from htcdetail as d
                    left join htchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    where isreplenish <>1 and  num.center ='" . $center . "' and date(head.dateid) >='" . $start . "') as a";

        //pag kahit isreplenish =1
        // $totalexp = "select sum(amount) as value from ( select sum(amount) as amount from tcdetail
        //                                                             union all
        //                                                 select sum(amount) as amount from htcdetail ) as a";


        // var_dump($unrep);

        $exp = $this->coreFunctions->opentable($unrep);


        $expenses = "select sum(amount) as value from ( select sum(d.deduction) as amount
                    from tcdetail as d
                    left join tchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    where   num.center ='" . $center . "' and date(head.dateid) ='" . $start . "'
              union all
                    select sum(d.deduction) as amount from htcdetail as d
                    left join htchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    where   num.center ='" . $center . "' and date(head.dateid) ='" . $start . "') as a";

        $totalexp = $this->coreFunctions->opentable($expenses);



        $remainingcash = "select ifnull(head.endingbal,0) as endingbal from tchead as head
                            left join transnum as num on num.trno=head.trno
                            where  num.center ='" . $center . "' and date(head.dateid) ='" . $start . "'
                            union all
                            select ifnull(head.endingbal,0) as endingbal from htchead as head
                            left join transnum as num on num.trno=head.trno
                            where  num.center ='" . $center . "' and date(head.dateid) ='" . $start . "' order by endingbal desc limit 1";
        // var_dump($remainingcash);
        $rem = $this->coreFunctions->opentable($remainingcash);

        $endingbal = 0;
        if (!empty($rem)) {
            $endingbal = $rem[0]->endingbal;
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PETTY CASH FUND: AMOUNT P', '190', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($petty, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '510', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '190', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '510', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';


        ////bilang ng unreplenish kada empname
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('UNREPLENISHED:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($exp[0]->value, 2), '280', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CHANGE FUND: AMOUNT P', '174', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($inputamt, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '126', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '174', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '126', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EXPENSES:', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalexp[0]->value, 2), '675', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REMAINING CASH:', '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($endingbal, 2), '670', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/><br/>';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL: 0', '400', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= '<br/>';

        // $onekiaw = $config['params']['dataparams']['tenthirty'];
        // $fivehundred = $config['params']['dataparams']['thirtyfifty'];
        // $twohundred = $config['params']['dataparams']['fiftyhundred'];
        // $onhundred = $config['params']['dataparams']['hundredtwofifty'];
        // $fifty = $config['params']['dataparams']['twofiftyfivehundred'];
        // $twenty = $config['params']['dataparams']['fivehundredup'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SALES DENOMINATION:', '500', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();




        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('1000', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($onekiaw, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $onekiawtotal = 1000 * $onekiaw;
        $str .= $this->reporter->col(number_format($onekiawtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('500', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($fivehundred, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $fivehtotal = 500 * $fivehundred;
        $str .= $this->reporter->col(number_format($fivehtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('200', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($twohundred, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $twohtotal = 200 * $twohundred;
        $str .= $this->reporter->col(number_format($twohtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('100', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($onhundred, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $onhtotal = 100 * $onhundred;
        $str .= $this->reporter->col(number_format($onhtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('50', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($fifty, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $fiftytotal = 50 * $fifty;
        $str .= $this->reporter->col(number_format($fiftytotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('20', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($twenty, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $twentytotal = 20 * $twenty;
        $str .= $this->reporter->col(number_format($twentytotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //coins
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('COINS', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($unit, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($unit, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //TOTAL CASH
        // $tlsales = 0;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL CASH', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalcash = $onekiawtotal + $fivehtotal + $twohtotal + $onhtotal +  $fiftytotal +  $twentytotal + $unit;
        $str .= $this->reporter->col(number_format($totalcash, 2), '190', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('ADDITIONAL PARTIAL DEPOSITS', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $additional = $this->partialqry($config);
        // $partial = 0;
        // if (!empty($additional)) {
        //     foreach ($additional as $key => $adddata) {
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col($adddata->bankname, '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        //         $str .= $this->reporter->col(number_format($adddata->amount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        //         $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //         $partial += $adddata->amount;
        //     }
        // }


        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('BANKS', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $banks = $this->banks($config);
        // $tlbanks = 0;
        // if (!empty($banks)) {
        //     foreach ($banks as $key => $banksdata) {
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col($banksdata->bankname, '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        //         $str .= $this->reporter->col(number_format($banksdata->amount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        //         $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //         $tlbanks += $banksdata->amount;
        //     }
        // }
        // // + $partial
        // $tlsales = $tlbanks + $totalcash + $partial;
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('TOTAL SALES', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col(number_format($tlsales, 2), '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Docno', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Borrower', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Loan Type', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Terms', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    public function partialqry_og($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }
        //galing sa ce
        $query = "select sum(amount) as amount, bankname from (
                select sum(head.amount) as amount,paymode.category as bankname from hcehead as head
                LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                left join transnum as num on num.trno=head.trno
                where paymode.category not in ('Cash','Check','BANK TRANSFER') and  date(head.dateid)='$start'  $filter
                group by bankname

                union all

                select sum(ce.amount) as amount,ce.bank as bankname from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                                        left join transnum as num on num.trno=ce.trno
                                        where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                                        and date(ce.depodate)='$start' and date(ce.dateid) = '$start'  $filter
                                        group by ce.bank

                union all
                select sum(ce.amount) as amount,paymode.category as bankname from tcoll as ce
                                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                                        left join transnum as num on num.trno=ce.trno
                                        where ce.doc <>'DX' and  paymode.category not in ('Check')
                                        and date(ce.dateid) ='$start' and date(ce.dateid) = '$start'  $filter
                                        group by paymode.category) as zxy group by bankname";
        return $this->coreFunctions->opentable($query);
    }

    public function banks_og($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }

        $query = "select sum(amount) as amount,bankname from (
                       select sum(amount) as amount,acnoname as bankname from (
                        select head.amount,coa.acnoname from dxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                        group by head.amount,coa.acnoname

                        union all
                        select head.amount,coa.acnoname from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Check')  
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start' $filter
                        group by head.amount,coa.acnoname

                        union all
                        select dx.amount,coa.acnoname from dxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname

                        union all
                        select dx.amount,coa.acnoname from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname
                        
                        union all
                        select head.amount, head.acnoname  from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        where paymode.category = 'BANK TRANSFER'  and ttype.category not in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,head.acnoname ) as a group by bankname

                        union all

                        select sum(ce.amount) as amount,ce.bank as bankname from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                        and date(ce.depodate)='$start' and date(ce.dateid) = '$start' $filter2
                        group by ce.bank

                        union all

                        select sum(ce.amount) as amount,paymode.category as bankname from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        where ce.doc <>'DX' and  paymode.category not in ('Check')
                        and date(ce.dateid) = '$start' $filter2
                        group by paymode.category) as xm
                        group by bankname
                        order by bankname";


        return $this->coreFunctions->opentable($query);
    }


    public function reportDefaultLayout_daily_cashier($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['center'];
        $total = 0;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = 800;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $this->nogroup = 1;
        $users = $this->qrypermode($config);

        if (!empty($users)) {
            $firstPage = true;
            foreach ($users as $user) {
                if (!$firstPage) {                    
                    $str .= $this->reporter->page_break(); // bagong page for next user
                    
                }
                $firstPage = false;

                $str .= $this->reporter->beginreport($layoutsize);                
                $str .= $this->header_DEFAULT($config);
                // $str .= $this->renderfirst($config, $user, $total);
                list($htmlFirst, $totalcash_on_hand) = $this->renderfirst($config, $user, $total);
                $str .= $htmlFirst;

                $this->nogroup = 1;
                $data1 = $this->qrypermode($config, $user->username);
                // $str .= $this->renderSection1($config, $data1, $user, $totalcash_on_hand);
                list($htmlFirst2, $totalfundsavail) = $this->renderSection1($config, $data1, $totalcash_on_hand);
                $str .= $htmlFirst2;

                $str .= '<br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('LESS', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'red', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Cash deposits ( choices: Palawan/ Finance Office/ Bank )', '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                /////qry nogroup2

                $this->nogroup = 2;
                $data2 = $this->qrypermode($config, $user->username);
                // $str .= $this->renderSection1($config, $data1, $user);
                $str .= $this->renderSection2($config, $data2, $totalfundsavail);


                $str .= '<br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('USED RECEIPTS:', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $rslip = $this->coreFunctions->datareader("select group_concat(rslip order by rslip separator ', ') as value from (
                select concat(min(rslip),'-',max(rslip)) as rslip from hcehead as ce  left join transnum as num on num.trno = ce.trno            
                where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and rslip <>'' and ce.createby='$user->username' ) as v");

                $crno = $this->coreFunctions->datareader("select group_concat(crno order by crno separator ', ') as value from (
                select concat(min(yourref),'-',max(yourref)) as crno from hcehead as ce left join transnum as num on num.trno = ce.trno 
                where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and yourref <>'' and ce.createby='$user->username'
                union all
                select concat(min(yourref),'-',max(yourref)) as crno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and yourref <>''
                and createby='$user->username') as v");

                $drno = $this->coreFunctions->datareader("select group_concat(drno order by drno separator ', ') as value from (
                select concat(min(drno),'-',max(drno)) as drno from hcehead as ce left join transnum as num on num.trno = ce.trno 
                where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and drno <>'' and ce.createby='$user->username'
                union all
                select concat(min(drno),'-',max(drno)) as drno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and drno <>''
                and createby='$user->username') as v");


                $sicsino = $this->coreFunctions->datareader("select group_concat(sicsino order by sicsino separator ', ') as value from (
                select concat(min(sicsino),'-',max(sicsino)) as sicsino from hcehead as ce left join transnum as num on num.trno = ce.trno 
                where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and ce.rem2='' and ce.createby='$user->username'
                union all
                select concat(min(sicsino),'-',max(sicsino)) as sicsino from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' 
                and createby='$user->username') as v");//and trnxtype = 'MC UNIT'

                $orno = $this->coreFunctions->datareader("select group_concat(orno order by orno separator ', ') as value from (
                select concat(min(ourref),'-',max(ourref)) as orno from hcehead as ce left join transnum as num on num.trno = ce.trno 
                where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and ourref <>'' and ce.createby='$user->username'
                union all
                select concat(min(ourref),'-',max(ourref)) as orno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and ourref <>''
                and createby='$user->username') as v");

                $sicsinomc = $this->coreFunctions->datareader("select group_concat(sicsino order by sicsino separator ', ') as value from (
                select concat(min(sicsino),'-',max(sicsino)) as sicsino from hcehead as ce left join transnum as num on num.trno = ce.trno 
                where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and ce.rem2<>'' and ce.createby='$user->username'
                union all
                select concat(min(sicsino),'-',max(sicsino)) as sicsino from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and trnxtype = 'MC UNIT'
                and createby='$user->username') as v");


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('COLLECTION RECEIPT', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($crno, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('DR', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($drno, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CSI PARTS', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($sicsino, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CSI MC UNITS', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($sicsinomc, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('OFFICIAL RECEIPTS', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($orno, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('REFUND SLIP', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($rslip, '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

               ////////////


                $str .= '<br/><br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('TOTAL POST DATED CHECKS RECEIVED :', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'red', '');
                $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('DATE OF CHECK', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('AMOUNT', '300', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Name/Bank/Check No.', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $this->nogroup = 4;
                $data4 = $this->qrypermode($config, $user->username);
                $str .= $this->renderSection4($config, $data4);


                $str .= '<br/>';
                /////petty cash
                $center = $config['params']['center'];
                $petty = $this->coreFunctions->getfieldvalue("center", "petty", "code=?", [$center]);

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('PETTY CASH FUND: AMOUNT P', '190', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($petty, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '510', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '190', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '510', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= '<br/>';
                //////bilang ng unreplenish kada empname at change fund
                $this->nogroup = 5;
                $data5 = $this->qrypermode($config, $user->username);
                $str .= $this->renderSection5($config, $data5);


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '174', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '126', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                //total expenses
                $this->nogroup = 6;
                $data6 = $this->qrypermode($config, $user->username);
                $str .= $this->renderSection6($config, $data6);

                //remaining cash
                $this->nogroup = 7;
                $data7 = $this->qrypermode($config, $user->username);
                $str .= $this->renderSection7($config, $data7);


                $str .= '<br/><br/><br/>';


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('TOTAL: 0', '400', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= '<br/>';

                //denom
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('SALES DENOMINATION:', '500', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // $str .= $this->renderdenom($config);
                list($htmlFirst3, $totalcash) = $this->renderdenom($config);
                $str .= $htmlFirst3;


                ///////Partial

                // $additional = $this->partialqry($config);
                // $additional = $this->partialqry($config, $user->username);
                // // $str .= $this->renderpartialdepo($config, $additional);
                // list($htmlFirst4, $partial) = $this->renderpartialdepo($config, $additional);
                // $str .= $htmlFirst4;

                // //////Banksss
                // $banks = $this->banks($config, $user->username);
                // $str .= $this->renderingbanks($config, $banks, $totalcash, $partial);

                $str .= '<br/><br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('PREPARED BY: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($user->name, '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '<br/><br/>';
                $str .= str_repeat('-', 200);

                $str .= $this->reporter->endreport();
            }
        }

        return $str;
    }


    private function renderfirst($config, $user, $total)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center     = $config['params']['center'];
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";

        $str = '';

        // // header section
        // $str .= $this->reporter->beginreport($layoutsize);
        // $str .= $this->header_DEFAULT($config);

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Funds balance beginning:', '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // BEG BALANCE
        //$cash   = $this->coreFunctions->getfieldvalue("eod", "begbal", "date(dateid)<? and closeby = ?", [$start,$user->username], "dateid desc", true);
        $cash   = $this->coreFunctions->getfieldvalue("eod", "cash", "date(dateid)<? and closeby = ?", [$start,$user->username], "dateid desc", true);
        $checks = $this->coreFunctions->getfieldvalue("eod", "checks", "date(dateid)<? and closeby = ?", [$start,$user->username], "dateid desc", true);
        $begbal = $this->coreFunctions->getfieldvalue("eod", "begbal", "date(dateid)=? and closeby = ?", [$start,$user->username], "dateid desc", true);
        $total  = $cash + $checks;

        // if($total == 0){
        //     $total = $begbal;
        // }

        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cash', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($cash, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Checks', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($checks, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // TOTAL row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($total, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Less Deposit :', '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $qry = "select sum(amount) as totalamt,name from (
                select head.amount,userr.name from dxhead as dx
                LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                LEFT JOIN hcehead AS head ON head.trno = num.trno
                LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
                left join useraccess as userr on userr.username=dx.createby
                where paymode.category IN ('Cash','Check') 
                and date(dx.dateid)='$start' and date(head.dateid) < '$start' and num.center ='$center'
                and userr.username = '$user->username'
                union all
                select head.amount,userr.name from hdxhead as dx
                LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                LEFT JOIN hcehead AS head ON head.trno = num.trno
                LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid
                left join useraccess as userr on userr.username=dx.createby
                where paymode.category IN ('Cash','Check')
                and date(dx.dateid)='$start' and date(head.dateid) < '$start' and num.center ='$center' 
                 and userr.username = '$user->username'
                union all
                select dx.amount,userr.name from tcoll as dx
                LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid
                 left join useraccess as userr on userr.username=dx.createby
                where paymode.category IN ('Cash','Check')
                and date(dx.depodate)='$start' and date(dx.dateid) < '$start' and dx.center ='$center'  
                 and userr.username = '$user->username'                 
                ) as a group by name 
                order by name ";
        // ('3SVALENCIA','demo') 
        $ress = $this->coreFunctions->opentable($qry);
        $cashChecks = 0;

        if (!empty($ress)) {
            foreach ($ress as $totalperuser) {
                $cashChecks += $totalperuser->totalamt ? $totalperuser->totalamt : 0;
            }
        }

        $str .= $this->reporter->col('Cash/Checks :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($cashChecks, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $totalcash_on_hand = $total - $cashChecks;
        $str .= $this->reporter->col('Total cash on hand :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'red', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalcash_on_hand, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= str_repeat('*', 133);




        // return [$str, $total];
        return [$str, $totalcash_on_hand];
        // return $str;
    }

    private function renderSection1($config, $data1, $totalcash_on_hand)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        $totaldaily_cash = 0;
        if (!empty($data1)) {
            foreach ($data1 as $row) {
                $amount = $row->amount ? $row->amount : 0;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Total Daily Cash and Checks :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($amount, 2), '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $totaldaily_cash += $amount;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Total Other Income', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalfundsavail = $totaldaily_cash + $totalcash_on_hand;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL FUNDS AVAILABLE :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($totalfundsavail, 2), '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            //kung walang data
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Total Daily Cash and Checks :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('0.00', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Total Other Income', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalfundsavail = 0;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL FUNDS AVAILABLE :', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($totalfundsavail, 2), '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // return $str;
        return [$str, $totalfundsavail];
    } //end class


    private function renderSection2($config, $data2, $totalfundsavail)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        $totaltransac = 0;
        if (!empty($data2)) {
            foreach ($data2 as $row) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($row->bankname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($row->amount, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totaltransac += $row->amount;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL TRANSACTIONS  CASH /CHECK/SUBSIDY/ONLINE', '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($totaltransac, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $funds = $totalfundsavail - $totaltransac;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('FUNDS BALANCE END', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($funds, 2), '300', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        return $str;
    } //end class


    private function renderSection4($config, $data4)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        $totalhere = 0;
        if (!empty($data4)) {
            foreach ($data4 as $row) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($row->checkdate, '200', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($row->amount, 2), '300', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($row->bank . ' ' . $row->checkinfo, '300', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totalhere += $row->amount;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'LTB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totalhere, 2), '300', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TBR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            // $str .= $this->reporter->col('', '200', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('<span style="color:#FFFFFF">.</span>', '200', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TL', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Total', '200', null, false, $border, 'LTB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totalhere, 2), '300', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TBR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        return $str;
    } //end class

    private function renderSection5($config, $data5)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $inputamt = $config['params']['dataparams']['amount'];
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        if (!empty($data5)) {
            foreach ($data5 as $row) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('UNREPLENISHED:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($row->unrep, 2), '280', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('CHANGE FUND: AMOUNT P', '174', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($inputamt, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '126', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        } else {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $unrep = 0;
            $str .= $this->reporter->col('UNREPLENISHED:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($unrep, 2), '280', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('CHANGE FUND: AMOUNT P', '174', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($inputamt, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '126', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        return $str;
    } //end class


    private function renderSection6($config, $data6)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        if (!empty($data6)) {
            foreach ($data6 as $row) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('TOTAL EXPENSES:', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($row->expenses, 2), '675', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        } else {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $espenses = 0;
            $str .= $this->reporter->col('TOTAL EXPENSES:', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($espenses, 2), '675', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        return $str;
    } //end class

    private function renderSection7($config, $data7)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        if (!empty($data7)) {
            foreach ($data7 as $row) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('REMAINING CASH:', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($row->endingbal, 2), '675', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        } else {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $endingbal = 0;
            $str .= $this->reporter->col('REMAINING CASH:', '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($endingbal, 2), '675', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        return $str;
    } //end class


    private function renderdenom($config)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $onekiaw = $config['params']['dataparams']['tenthirty'];
        $fivehundred = $config['params']['dataparams']['thirtyfifty'];
        $twohundred = $config['params']['dataparams']['fiftyhundred'];
        $onhundred = $config['params']['dataparams']['hundredtwofifty'];
        $fifty = $config['params']['dataparams']['twofiftyfivehundred'];
        $twenty = $config['params']['dataparams']['fivehundredup'];
        $unit = $config['params']['dataparams']['unit'];
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('1000', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($onekiaw, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $onekiawtotal = 1000 * $onekiaw;
        $str .= $this->reporter->col(number_format($onekiawtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('500', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($fivehundred, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $fivehtotal = 500 * $fivehundred;
        $str .= $this->reporter->col(number_format($fivehtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('200', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($twohundred, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $twohtotal = 200 * $twohundred;
        $str .= $this->reporter->col(number_format($twohtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('100', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($onhundred, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $onhtotal = 100 * $onhundred;
        $str .= $this->reporter->col(number_format($onhtotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('50', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($fifty, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $fiftytotal = 50 * $fifty;
        $str .= $this->reporter->col(number_format($fiftytotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('20', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('X', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($twenty, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $twentytotal = 20 * $twenty;
        $str .= $this->reporter->col(number_format($twentytotal, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //coins
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('COINS', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($unit, 0), '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($unit, 2), '190', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //TOTAL CASH
        // $tlsales = 0;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL CASH', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalcash = $onekiawtotal + $fivehtotal + $twohtotal + $onhtotal +  $fiftytotal +  $twentytotal + $unit;
        $str .= $this->reporter->col(number_format($totalcash, 2), '190', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // return $str;
        return [$str, $totalcash];
    } //end class


    private function renderpartialdepo($config, $additional)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        $partial = 0;
        if (!empty($additional)) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('ADDITIONAL PARTIAL DEPOSITS', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            foreach ($additional as $row) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($row->bankname, '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($row->amount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $partial += $row->amount;
            }
        }
        // return $str;
        return [$str, $partial];
    } //end class

    private function renderingbanks($config, $banks, $totalcash, $partial)
    {
        $layoutsize = 800;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $str = '';
        $tlbanks = 0;
        if (!empty($banks)) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('BANKS', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '600', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            foreach ($banks as $row) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($row->bankname, '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($row->amount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $tlbanks += $row->amount;
            }
        }

        $tlsales = $tlbanks + $totalcash + $partial;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL SALES', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($tlsales, 2), '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    } //end class

    public function qrypermode($config, $username = null)
    {
        //dstrno
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }

        if ($username) {
            $filter .= " and userr.username = '$username' ";
        }

        if ($this->nogroup == 1) { //total daily cash/checks
            $query = " select sum(amount) as amount,name,username
                        from (
                        select sum(hce.amount) as amount,userr.name,userr.username
                        from hcehead as hce
                        left join reqcategory as r on r.line = hce.trnxtid and r.isttype =1
                        left join transnum as num on num.trno=hce.trno
                        left join useraccess as userr on userr.username=hce.createby
                        where r.category not in ('REFUND','SUBSIDY') and date(hce.dateid)  = '$start' $filter 
                        group by userr.name,userr.username
                        union all
                        select sum(ce.amount) as amount,userr.name,userr.username
                        from cehead as ce
                        left join reqcategory as r on r.line = ce.trnxtid and r.isttype =1
                        left join transnum as num on num.trno=ce.trno
                        left join useraccess as userr on userr.username=ce.createby
                        where r.category not in ('REFUND','SUBSIDY') and date(ce.dateid)  = '$start' $filter
                        group by userr.name,userr.username
                        union all
                        select sum(ce.amount) as amount,userr.name,userr.username
                        from tcoll as ce
                        left join useraccess as userr on userr.username=ce.createby
                        where ce.doc='MC' and date(ce.dateid)  = '$start' $filter2
                        group by userr.name,userr.username) as xm 
                        group by name,username
                        order by name,username";
        } elseif ($this->nogroup == 2) { //less deposits
            $query = " select sum(amount) as amount,bankname,name,username,docno from (
                        select sum(amount) as amount,acnoname as bankname,name,username,docno from (
                        select head.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as acnoname,userr.name,userr.username,dx.docno from dxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                        group by head.amount,coa.acnoname,userr.name,userr.username,dx.docno,num.bref,num.seq

                        union all
                        select head.amount,concat(coa.acnoname,'(',num.bref,num.seq,')')  as acnoname,userr.name,userr.username,dx.docno from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Check')  
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start' $filter
                        group by head.amount,coa.acnoname,userr.name,userr.username,dx.docno,num.bref,num.seq

                        union all

                        select dx.amount,concat(coa.acnoname,'(',num.bref,num.seq,')')  as acnoname,userr.name,userr.username,dx.docno from dxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname,userr.name,userr.username,dx.docno,num.bref,num.seq

                        union all
                        select dx.amount,concat(coa.acnoname,'(',num.bref,num.seq,')') as acnoname,userr.name,userr.username,dx.docno from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                         left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname,userr.name,userr.username,dx.docno,num.bref,num.seq
                        
                        union all
                        select head.amount,(case paymode.category when 'BANK TRANSFER' then concat(paymode.category,'-',head.acnoname) else paymode.category end) as bankname,
                        userr.name,userr.username,'' as docno from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        left join useraccess as userr on userr.username=head.createby
                        where paymode.category not IN ('Cash','Check')  and ttype.category not in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,paymode.category,head.acnoname,userr.name,userr.username
                        union all
                        select head.amount,ttype.category as bankname,userr.name,userr.username,'' as docno from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        left join useraccess as userr on userr.username=head.createby
                        where ttype.category  in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,ttype.category,userr.name,userr.username
                        
                        ) as a group by bankname,name,username,docno

                        union all

                        select sum(ce.amount) as amount,ce.bank as bankname,userr.name,userr.username,'' as docno from tcoll as ce
                        left join useraccess as userr on userr.username=ce.createby
                        where ce.doc ='DX' and date(ce.dateid) = '$start' $filter2
                        group by ce.bank,userr.name,userr.username

                        union all

                        select sum(ce.amount) as amount,paymode.category as bankname,userr.name,userr.username,'' as docno from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                         left join useraccess as userr on userr.username=ce.createby
                        where ce.doc <>'DX' and  paymode.category not in ('Cash','Check')
                        and date(ce.dateid) = '$start' $filter2
                        group by paymode.category,userr.name,userr.username) as xm
                        group by bankname,name,username,docno
                        order by bankname,docno,name,username";
        } elseif ($this->nogroup == 3) { //used series
            $query = "select
                    ifnull(group_concat(yourref order by yourref separator ', '), '') AS crno,
                    ifnull(group_concat(ourref order by ourref separator ', '), '') AS orno,
                    ifnull(group_concat(sicsino order by sicsino separator ', '), '') AS sicsi,
                    ifnull(group_concat(drno order by drno separator ', '), '') AS drno,
                    ifnull(group_concat(rslip order by rslip separator ', '), '') AS rslip
                from (
                select head.yourref,head.ourref,head.sicsino,head.drno,head.rslip from cehead as head
                left join transnum as num on num.trno=head.trno
                left join useraccess as userr on userr.username=head.createby
                where date(head.dateid)  = '$start'  $filter
                union all
                select head.yourref,head.ourref,head.sicsino,head.drno,head.rslip from hcehead as head
                left join transnum as num on num.trno=head.trno
                left join useraccess as userr on userr.username=head.createby
                where date(head.dateid)  = '$start'  $filter
                union all
                select ce.yourref,ce.ourref,ce.sicsino,ce.drno,'' as rslip from tcoll as ce
                left join useraccess as userr on userr.username=ce.createby
                where date(ce.dateid)  = '$start'  $filter2 ) as a";
        } elseif ($this->nogroup == 4) { //pdc
            $query = "
            select sum(amount) as amount,bank, checkdate,checkinfo,name,username from (
                select sum(detail.amount) as amount,detail.bank,date(detail.checkdate) as checkdate,detail.checkno as checkinfo,
                   userr.name,userr.username
                    from hrchead as head
                    left join transnum as num on num.trno=head.trno
                    left join hrcdetail as detail on detail.trno = head.trno
                    left join useraccess as userr on userr.username=head.createby
                     where date(head.dateid) = '$start' $filter
                    group by detail.bank,detail.checkdate,detail.checkno,userr.name,userr.username
                    union all
                    select sum(detail.amount) as amount,detail.bank,date(detail.checkdate) as checkdate,detail.checkno as checkinfo,
                    userr.name,userr.username
                    from rchead as head
                    left join transnum as num on num.trno=head.trno
                    left join rcdetail as detail on detail.trno = head.trno
                    left join useraccess as userr on userr.username=head.createby
                     where date(head.dateid) = '$start' $filter
                    group by detail.bank,detail.checkdate,detail.checkno,userr.name,userr.username) as a
                    group by bank,checkdate ,checkinfo,name,username
                    order by checkdate";
        } elseif ($this->nogroup == 5) { // UNREPLENISHED
            $query = "select sum(amount) as unrep,name,username 
                     from ( 
                    select sum(d.deduction) as amount,userr.name,userr.username
                    from tcdetail as d
                    left join tchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    left join useraccess as userr on userr.username=head.createby
                    where isreplenish <>1 $filter and date(head.dateid) >='" . $start . "'
                    group by userr.name,userr.username
                    union all
                    select sum(d.deduction) as amount,userr.name,userr.username
                    from htcdetail as d
                    left join htchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    left join useraccess as userr on userr.username=head.createby
                    where isreplenish <>1 $filter and date(head.dateid) >='" . $start . "'
                    group by userr.name,userr.username) as a
                    group by name,username
                    order by name,username";
        } elseif ($this->nogroup == 6) { //total expenses
            $query = "select sum(amount) as expenses,name,username 
                    from ( 
                    select sum(d.deduction) as amount,userr.name,userr.username
                    from tcdetail as d
                    left join tchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    left join useraccess as userr on userr.username=head.createby
                    where  date(head.dateid) ='" . $start . "' $filter
                    group by userr.name,userr.username
              union all
                    select sum(d.deduction) as amount,userr.name,userr.username
                    from htcdetail as d
                    left join htchead as head on head.trno=d.trno
                    left join transnum as num on num.trno=head.trno
                    left join useraccess as userr on userr.username=head.createby
                    where  date(head.dateid) ='" . $start . "' $filter
                    group by userr.name,userr.username) as a
                    group by name,username
                    order by name,username";
        } else { //remaining cash
            $query = "select ifnull(head.endingbal,0) as endingbal,userr.name,userr.username from tchead as head
                            left join transnum as num on num.trno=head.trno
                            left join useraccess as userr on userr.username=head.createby
                            where  date(head.dateid) ='" . $start . "' $filter 
                            union all
                            select ifnull(head.endingbal,0) as endingbal,userr.name,userr.username from htchead as head
                            left join transnum as num on num.trno=head.trno
                            left join useraccess as userr on userr.username=head.createby
                            where  date(head.dateid) ='" . $start . "' $filter order by endingbal desc limit 1";
            // var_dump($query);
        }

        return $this->coreFunctions->opentable($query);
    }

    public function partialqry($config, $username = null)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }
        if ($username) {
            $filter .= " and userr.username = '$username' ";
        }
        //galing sa ce
        $query = "select sum(amount) as amount, bankname,name,username from (
                select sum(head.amount) as amount,paymode.category as bankname,userr.name,userr.username from hcehead as head
                LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                left join transnum as num on num.trno=head.trno
                left join useraccess as userr on userr.username=head.createby
                where paymode.category not in ('Cash','Check','BANK TRANSFER') and  date(head.dateid)='$start'  $filter
                group by bankname,userr.name,userr.username

                union all

                select sum(ce.amount) as amount,ce.bank as bankname,userr.name,userr.username from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                                        left join transnum as num on num.trno=ce.trno
                                        left join useraccess as userr on userr.username=ce.createby
                                        where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                                        and date(ce.depodate)='$start' and date(ce.dateid) = '$start'  $filter
                                        group by ce.bank,userr.name,userr.username

                union all
                select sum(ce.amount) as amount,paymode.category as bankname,userr.name,userr.username from tcoll as ce
                                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                                        left join transnum as num on num.trno=ce.trno
                                        left join useraccess as userr on userr.username=ce.createby
                                        where ce.doc <>'DX' and  paymode.category not in ('Check')
                                        and date(ce.dateid) ='$start' and date(ce.dateid) = '$start'  $filter
                                        group by paymode.category,userr.name,userr.username) as zxy 
                                        group by bankname,name,username
                                        order by bankname,name,username";
        return $this->coreFunctions->opentable($query);
    }

    public function banks($config, $username = null)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['dataparams']['center'];
        $filter = "";
        $filter2 = "";

        if ($center != "") {
            $filter .= " and num.center= '" . $center . "' ";
            $filter2 .= " and ce.center= '" . $center . "' ";
        }

        if ($username) {
            $filter .= " and userr.username = '$username' ";
        }

        $query = "select sum(amount) as amount,bankname,name,username from (
                       select sum(amount) as amount,acnoname as bankname,name,username from (
                        select head.amount,coa.acnoname,userr.name,userr.username from dxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter
                        group by head.amount,coa.acnoname,userr.name,userr.username

                        union all
                        select head.amount,coa.acnoname,userr.name,userr.username from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Check')  
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start' $filter
                        group by head.amount,coa.acnoname,userr.name,userr.username

                        union all
                        select dx.amount,coa.acnoname,userr.name,userr.username from dxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname,userr.name,userr.username

                        union all
                        select dx.amount,coa.acnoname,userr.name,userr.username from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=dx.createby
                        where  paymode.category IN ('Cash') 
                        and date(dx.dateid)='$start'  $filter
                        group by dx.amount,coa.acnoname,userr.name,userr.username
                        
                        union all
                        select head.amount, head.acnoname,userr.name,userr.username  from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        left join useraccess as userr on userr.username=head.createby
                        where paymode.category = 'BANK TRANSFER'  and ttype.category not in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,head.acnoname,userr.name,userr.username ) as a group by bankname,name,username

                        union all

                        select sum(ce.amount) as amount,ce.bank as bankname,userr.name,userr.username from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=ce.createby
                        where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                        and date(ce.depodate)='$start' and date(ce.dateid) = '$start' $filter2
                        group by ce.bank,userr.name,userr.username

                        union all

                        select sum(ce.amount) as amount,paymode.category as bankname,userr.name,userr.username from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        left join useraccess as userr on userr.username=ce.createby
                        where ce.doc <>'DX' and  paymode.category not in ('Check')
                        and date(ce.dateid) = '$start' $filter2
                        group by paymode.category,userr.name,userr.username) as xm
                        group by bankname,name,username
                        order by bankname,name,username";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }



    
}
