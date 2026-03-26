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

class replenishment_summary
{
    public $modulename = 'Replenishment Summary';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:3000px;max-width:3000px;';
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
        $fields = ['radioprint', 'start', 'end', 'dcentername'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dcentername.required', true);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];

        $paramstr = "select 
       'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
            '' as center,
            '' as dcentername";
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
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->DEFAULT_QUERY_now($config);
        return $this->coreFunctions->opentable($query);
    }


    public function header_DEFAULT($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start      = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
        $end        = date("m/d/Y", strtotime($config['params']['dataparams']['end']));
        $center    = $config['params']['dataparams']['center'];
        $str = '';
        // $layoutsize= '1000';
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
        $str .= $this->reporter->col('Replenishment Summary', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        if ($center == '') {
            $str .= $this->reporter->col('Branch: ALL ', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Branch: ' . $center, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function tableheader($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $acnoname = $this->get_all_acnoname($config);
        $acnoname_count = $this->count_all_acnoname($config, $acnoname);
        $layoutsize = 630 + ($acnoname_count * 80);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PCV#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('EMPLOYEE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PARTICULAR', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        foreach ($acnoname as $array_index => $array) {
            $str .= $this->reporter->col($array->acnoname, '80', null, false, '1px solid ', 'TB', 'C', $font, $fontsize, 'B', '', '');
        }
        return $str;
    }

    public function get_all_acnoname($config)
    {
        $qry2 = "select coa.acnoname,coa.acnoid from tcdetail as d left join coa on coa.acnoid=d.acnoid where coa.acnoid is not null";
        return $this->coreFunctions->opentable($qry2);
    }


    public function count_all_acnoname($config, $data)
    {
        $count = 0;
        foreach ($data as $i => $value) {
            $count++;
        }
        return $count;
    }


    public function DEFAULT_QUERY_now($config)
    {
        $inner_fields = '';
        $group_fields = 'test.dateid, test.pcv, test.empname, test.rem, test.amount , test.trno, test.line';
        $all_acno = $this->get_all_acnoname($config);
        foreach ($all_acno as $array_index => $array) {
            $acnoid = (int)$array->acnoid;
            $inner_fields .= ", if(d.acnoid = $acnoid, d.deduction, 0) as `$acnoid`";
            $group_fields .= ", `$acnoid`";
        }
        $center    = $config['params']['dataparams']['center'];

        $filter = "";

        if ($center != "") {
            $filter .= " and num.center = '$center'";
        }

        $query = "select test.* from (
            select  date(head.dateid) AS dateid, d.ref AS pcv,
                d.empname, d.rem,FORMAT(d.deduction, 2) AS amount, head.trno, d.line $inner_fields
            FROM tcdetail AS d
            left join tchead AS head ON head.trno = d.trno
            left join transnum AS num ON num.trno = head.trno
            left join coa ON coa.acnoid = d.acnoid
            WHERE head.doc = 'TC' and d.isreplenish = 0  and d.acnoid <>0 $filter
            
            union all

             select  date(head.dateid) AS dateid, d.ref AS pcv,
                d.empname, d.rem,FORMAT(d.deduction, 2) AS amount, head.trno, d.line $inner_fields
            FROM htcdetail AS d
            left join htchead AS head ON head.trno = d.trno
            left join transnum AS num ON num.trno = head.trno
            left join coa ON coa.acnoid = d.acnoid
            WHERE head.doc = 'TC' and d.isreplenish = 0  and d.acnoid <>0 $filter) as test
        group by $group_fields
        order by test.dateid, test.pcv";

        // var_dump($query);

        return $query;
    }



    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $fontsize11 = 11;

        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $acno = $this->get_all_acnoname($config);
        $all_acno = $this->count_all_acnoname($config, $acno);
        $layoutsize = 630 + ($all_acno * 80);

        $acno_here = [];
        foreach ($acno as $array_index => $array) {
            array_push($acno_here, $array->acnoid);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->header_DEFAULT($config, $layoutsize);
        $str .= $this->tableheader($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);

        $grandtotal = 0;
        $totalbalqty = 0;
        foreach ($result as $key => $data) {
            $dateid = $data->dateid;
            $empname = $data->empname;
            $pcv = $data->pcv;
            $rem = $data->rem;
            $amount = $data->amount;
            $arr_empname = $this->reporter->fixcolumn([$empname], '80', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '20', 0);
            $arr_pcv = $this->reporter->fixcolumn([$pcv], '40', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '90', 0);
            $arr_amount = $this->reporter->fixcolumn([$amount], '20', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_empname, $arr_dateid, $arr_pcv, $arr_rem, $arr_amount]);

            $totalbalqty = 0;

            for ($r = 0; $r < $maxrow; $r++) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();
                $str .= $this->reporter->col(' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_pcv[$r]) ? $arr_pcv[$r] : ''), '100', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_empname[$r]) ? $arr_empname[$r] : ''), '150', null, false, '1px solid ', '', 'L', $font, $font_size, '',  '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '200', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(' ' . (isset($arr_amount[$r]) ? $arr_amount[$r] : ''), '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');

                if ($r == 0) {
                    foreach ($data as $i => $value) {
                        if (array_search($i, $acno_here) !== false) {
                            if ($value == 0) {
                                $str .= $this->reporter->col('-', '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                            } else {
                                $str .= $this->reporter->col(number_format($value, 2), '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                            }
                            $totalbalqty += $value;
                        }
                    }
                } else {
                    foreach ($data as $i => $value) {
                        if (array_search($i, $acno_here) !== false) {
                            $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
                        }
                    }
                    $str .= $this->reporter->col('', '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', "", "");
                }
            }
            $grandtotal += $totalbalqty;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRANDTOTAL: ', '100', null, false, '1px solid ', 'T', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'T', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px solid ', 'T', 'L', $font, $font_size, '', "", "");

        for ($i = 0; $i < count($acno_here); $i++) {
            $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'L', $font, $font_size, '', "", "");
        }

        $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, '1px solid ', 'T', 'R', $font, $font_size, '', "", "");


        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class