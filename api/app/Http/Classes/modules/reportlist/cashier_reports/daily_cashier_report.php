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


class daily_cashier_report
{
    public $modulename = 'Daily Cashier Report';
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
        $str .= $this->reporter->col('Daily Cashier Report', null, null, false, $border, '', 'C', $font, '18', 'B', 'blue', '') . '<br />';
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



    public function reportDefaultLayout_daily_cashier($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $center = $config['params']['center'];
        $user = $config['params']['user'];

        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = 800;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);

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
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and rslip <>'' and ce.createby='$user' ) as v");

        $crno = $this->coreFunctions->datareader("select group_concat(crno order by crno separator ', ') as value from (
        select concat(min(yourref),'-',max(yourref)) as crno from hcehead as ce left join transnum as num on num.trno = ce.trno 
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and yourref <>'' and ce.createby='$user'
        union all
        select concat(min(yourref),'-',max(yourref)) as crno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and yourref <>''
        and createby='$user') as v");

        $drno = $this->coreFunctions->datareader("select group_concat(drno order by drno separator ', ') as value from (
        select concat(min(drno),'-',max(drno)) as drno from hcehead as ce left join transnum as num on num.trno = ce.trno 
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and drno <>'' and ce.createby='$user'
        union all
        select concat(min(drno),'-',max(drno)) as drno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and drno <>''
        and createby='$user') as v");


        $sicsino = $this->coreFunctions->datareader("select group_concat(sicsino order by sicsino separator ', ') as value from (
        select concat(min(sicsino),'-',max(sicsino)) as sicsino from hcehead as ce left join transnum as num on num.trno = ce.trno 
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and ce.rem2='' and ce.createby='$user'
        union all
        select concat(min(sicsino),'-',max(sicsino)) as sicsino from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' 
        and createby='$user') as v");//and trnxtype = 'MC UNIT'

        $orno = $this->coreFunctions->datareader("select group_concat(orno order by orno separator ', ') as value from (
        select concat(min(ourref),'-',max(ourref)) as orno from hcehead as ce left join transnum as num on num.trno = ce.trno 
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and ourref <>'' and ce.createby='$user'
        union all
        select concat(min(ourref),'-',max(ourref)) as orno from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and ourref <>''
        and createby='$user') as v");

        $sicsinomc = $this->coreFunctions->datareader("select group_concat(sicsino order by sicsino separator ', ') as value from (
        select concat(min(sicsino),'-',max(sicsino)) as sicsino from hcehead as ce left join transnum as num on num.trno = ce.trno 
        where num.center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and ce.rem2<>'' and ce.createby='$user'
        union all
        select concat(min(sicsino),'-',max(sicsino)) as sicsino from tcoll where center ='" . $center . "' and date(dateid) ='" . $start . "' and sicsino <>'' and trnxtype = 'MC UNIT'
        and createby='$user') as v");


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
        $data4 = $this->qrypermode($config, $user);
        $str .= $this->renderSection4($config, $data4);


        $str .= '<br/>';
        // /////petty cash
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
        $data5 = $this->qrypermode($config, $user);
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
        $data6 = $this->qrypermode($config, $user);
        $str .= $this->renderSection6($config, $data6);

        //remaining cash
        $this->nogroup = 7;
        $data7 = $this->qrypermode($config, $user);
        $str .= $this->renderSection7($config, $data7);

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PREPARED BY: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($user, '700', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= str_repeat('-', 200);


        $str .= $this->reporter->endreport();

        return $str;
    }



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

    public function qrypermode($config, $user = null)
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

        if ($user) {
            $filter .= " and userr.username = '$user' ";
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
                        where date(ce.dateid)  = '$start' $filter2
                        group by userr.name,userr.username) as xm 
                        group by name,username
                        order by name,username";
        } elseif ($this->nogroup == 2) { //less deposits
            $query = " select sum(amount) as amount,bankname,name,username from (
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
                        select head.amount,(case paymode.category when 'BANK TRANSFER' then concat(paymode.category,'-',head.acnoname) else paymode.category end) as bankname,
                        userr.name,userr.username from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        left join useraccess as userr on userr.username=head.createby
                        where paymode.category not IN ('Cash','Check')  and ttype.category not in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,paymode.category,head.acnoname,userr.name,userr.username
                        union all
                        select head.amount,ttype.category as bankname,userr.name,userr.username from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        left join useraccess as userr on userr.username=head.createby
                        where ttype.category  in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' $filter
                        group by head.amount,ttype.category,userr.name,userr.username
                        
                        ) as a group by bankname,name,username

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
