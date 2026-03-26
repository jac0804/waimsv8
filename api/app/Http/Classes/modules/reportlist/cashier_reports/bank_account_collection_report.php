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

class bank_account_collection_report
{
    public $modulename = 'Bank Account Collection Report';
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
        $fields = ['radioprint', 'start', 'acnoname5'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'start.label', 'Date');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
        'default' as print,
         date(now()) as start,
          '' as contra5, '' as acnoname5, '0' as acnoid5 ");
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




    public function banknames($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $acnoid = $config['params']['dataparams']['acnoid5']; // line at acnoid
        $acnoname = $config['params']['dataparams']['acnoname5']; //acnoname at category
        $contra = $config['params']['dataparams']['contra5']; // acno paymode
        $contra = str_replace('\\', '\\\\', $contra);

        $filter = "";
        $filter2 = "";
        $filter3 = "";
        $filter4 = "";

        if ($acnoname != "") {
            $filter .= " and coa.acnoid= $acnoid ";
            $filter3 .= " and paymode.line= $acnoid ";
            $filter2 .= " and coa.acno= '" . $contra . "' ";
            $filter4 .= " and paymode.category= '" . $acnoname . "' ";
        }

        $query = "  select sum(amount) as amount,datenow,name,bankname from (
                       select sum(amount) as amount,acnoname as bankname,datenow,name from (
                        select head.amount,coa.acnoname,date(dx.dateid) as datenow,c.name  from dxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        left join center as c on c.code=num.center
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter $filter2
                        group by head.amount,coa.acnoname,date(dx.dateid),c.name

                        union all
                        select head.amount,coa.acnoname as bankname,date(dx.dateid) as datenow,c.name  from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.dstrno = dx.trno
                        LEFT JOIN hcehead AS head ON head.trno = num.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                         left join center as c on c.code=num.center
                        where  paymode.category IN ('Check')
                        and date(dx.dateid)='$start' and date(head.dateid) = '$start'  $filter $filter2
                        group by head.amount,coa.acnoname,date(dx.dateid),c.name

                        union all

                        select dx.amount,coa.acnoname as bankname,date(dx.dateid) as datenow,c.name    from dxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                        left join center as c on c.code=num.center
                        where  paymode.category IN ('Cash')
                        and date(dx.dateid) = '$start'  $filter $filter2
                        group by dx.amount,coa.acnoname,date(dx.dateid),c.name

                        union all
                        select dx.amount,coa.acnoname as bankname,date(dx.dateid) as datenow,c.name  from hdxhead as dx
                        LEFT JOIN transnum AS num ON num.trno = dx.trno
                        LEFT JOIN coa ON coa.acnoid = dx.bank
                        LEFT JOIN reqcategory AS paymode ON paymode.line = dx.mpid and paymode.ispaymode =1
                         left join center as c on c.code=num.center
                        where  paymode.category IN ('Cash')
                        and date(dx.dateid) = '$start'  $filter $filter2
                        group by dx.amount,coa.acnoname,date(dx.dateid),c.name

                        union all
                        select head.amount,(case paymode.category when 'BANK TRANSFER' then head.acnoname else paymode.category end) as bankname,
                         date(head.dateid) as datenow,c.name
                         from hcehead as head
                        LEFT JOIN transnum AS num ON num.trno = head.trno
                        LEFT JOIN reqcategory AS paymode ON paymode.line = head.mpid and paymode.ispaymode =1
                        LEFT JOIN reqcategory AS ttype ON ttype.line = head.trnxtid and ttype.isttype =1
                        LEFT JOIN coa ON coa.acno = head.contra
                         left join center as c on c.code=num.center
                        where paymode.category not IN ('Cash','Check')  and ttype.category not in ('REFUND','SUBSIDY')
                        and date(head.dateid)='$start' and ((paymode.category != 'BANK TRANSFER'  $filter3 $filter4) or
                        (paymode.category = 'BANK TRANSFER'  $filter $filter2))   
                        group by head.amount,paymode.category,head.acnoname,date(head.dateid),c.name
                        ) as a group by bankname,datenow,name

                        union all

                        select sum(ce.amount) as amount,ce.bank as bankname,date(ce.dateid) as datenow,c.name   from tcoll as ce
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        left join center as c on c.code=ce.center
                        where ce.doc <>'DX' and ce.dstrno<>0 and paymode.category in ('Check')
                        and date(ce.depodate)='$start' and date(ce.dateid)='$start' $filter3 $filter4
                        group by ce.bank,date(ce.dateid),c.name

                        union all

                        select sum(ce.amount) as amount,paymode.category as bankname,date(ce.dateid) as datenow,c.name  from tcoll as ce
                        LEFT JOIN transnum AS num ON num.trno = ce.trno
                        left join reqcategory as paymode on paymode.line = ce.mpid and paymode.ispaymode =1
                        left join center as c on c.code=ce.center
                        where ce.doc <>'DX' and  paymode.category not in ('Check')
                        and date(ce.dateid) = '$start'  $filter3  $filter4
                        group by paymode.category,date(ce.dateid),c.name ) as xm
                        group by datenow,name,bankname
                        order by bankname, name, datenow";
        return $this->coreFunctions->opentable($query);
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->banknames($config);
        $count = 25;
        $page = 25;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:95px');
        $str .= $this->displayHeader($config);
        $totalamt = 0;
        $currentBank = '';
        $bankTotal = 0;
        $currentDate = '';
        foreach ($result as $key => $data) {

            if ($currentBank != $data->bankname) {
                if ($currentBank != '') {
                    // print subtotal row ng previous bank
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(number_format($bankTotal, 2), '1000', '', false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $bankTotal = 0;
                }

                // print header ng bagong bank
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->bankname, '1000', '', false, '', '', 'L', $font, $fontsize, 'B');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $currentBank = $data->bankname;
                $currentDate = '';
            }
            $dateCol = ($currentDate == $data->datenow) ? '' : $data->datenow;
            $currentDate = $data->datenow;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($dateCol, '200', '', false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->name, '600', '', false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '200', '', false, $border, '', 'R', $font, $fontsize, '', '', '');
            $bankTotal += $data->amount;
            $totalamt += $data->amount;
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } //end foreach

        if ($currentBank != '') {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(number_format($bankTotal, 2), '1000', '', false, '1px dotted', 'T', 'R', $font, $fontsize, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endtable();


        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL: ', '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, 2), '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }


    private function displayHeader($config)
    {

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = '10';

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
        $str .= $this->reporter->col('Bank Account Collection Report', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRANCH', '600', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();


        return $str;
    }
}//end class