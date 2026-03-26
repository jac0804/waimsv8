<?php

namespace App\Http\Classes\modules\reportlist\payroll_reports;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class employee_demography_report
{
    public $modulename = 'Employee Demography - Age Bracket by Gender';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
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
        $fields = ['radioprint', 'company', 'deptrep', 'sectrep', 'emptype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        // for company lookup
        data_set($col1, 'company.type', 'lookup');
        data_set($col1, 'company.lookupclass', 'lookupcompany');
        data_set($col1, 'company.action', 'lookupcompany');

        // for department lookup
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');

        // for emp type
        data_set($col1, 'emptype.type', 'lookup');
        data_set($col1, 'emptype.lookupclass', 'lookupatype');
        data_set($col1, 'emptype.action', 'lookupatype');
        data_set($col1, 'emptype.name', 'type');

        $fields = ['prepared', 'checked', 'approved'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        date(now()) as end, 
        0 clientid, '' client, '' as clientname, '' as dclientname,
        0 as divid, '' as company,
        0 as deptid, '' as deptname,
        0 as sectid, '' as sectname,
        '' as type, '' as prepared, '' as checked, '' as approved
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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        return $this->employee_demography_layout($config);
        break;
    }

    // QUERY
    public function default_qry($config)
    {
        // $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $center = ($config['params']['center']);
        $company = ($config['params']['dataparams']['company']);
        $divid = ($config['params']['dataparams']['divid']);
        $deptid = ($config['params']['dataparams']['deptid']);
        $deptname = ($config['params']['dataparams']['deptname']);
        $sectid = ($config['params']['dataparams']['sectid']);
        $sectname = ($config['params']['dataparams']['sectname']);
        $type = ($config['params']['dataparams']['type']);

        $filter = '';

        if ($company != '') {
            $filter .= " and e.divid = $divid";
        }

        if ($deptname != '') {
            $filter .= " and e.deptid = $deptid";
        }

        if ($sectname != '') {
            $filter .= " and e.sectid = $sectid";
        }

        if ($type != '') {
            $filter .= " and e.emptype = '$type'";
        }

        $query = "select
                    age_group,
                    sum(case when x.gender = 'MALE' then 1 else 0 end) as male,
                    sum(case when x.gender = 'FEMALE' then 1 else 0 end) as female,
                    sum(case when x.gender = 'LGBT' then 1 else 0 end) as lgbt
                from (
                    select 
                        e.gender, e.emptype,
                        case
                            when timestampdiff(year, e.bday, curdate()) between 0 and 18 then '0 to 18'
                            when timestampdiff(year, e.bday, curdate()) between 19 and 22 then '19 to 22'
                            when timestampdiff(year, e.bday, curdate()) between 23 and 30 then '23 to 30'
                            when timestampdiff(year, e.bday, curdate()) between 31 and 40 then '31 to 40'
                            when timestampdiff(year, e.bday, curdate()) between 41 and 100 then '41 to 100'
                            else 'undefined'
                        end as age_group
                    from employee as e
                    left join app on app.empid = e.empid
                    where 1 = 1
                    $filter
                ) as x
                group by age_group
                order by field(age_group, '0 to 18', '19 to 22', '23 to 30', '31 to 40', '41 to 100', 'undefined');
                ";
        // var_dump($query);
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
    }

    public function employee_demography_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $type = ($config['params']['dataparams']['type']);
        $company = ($config['params']['dataparams']['company']);
        $deptname = ($config['params']['dataparams']['deptname']);
        $sectname = ($config['params']['dataparams']['sectname']);
        $type = ($config['params']['dataparams']['type']);

        $str = '';
        $layoutsize = '1000';
        $font = "Times New Roman";
        $fontsize = "10";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $reportdate = date('l, F j, Y');
        $reporttime = date('h:i:s A');

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<i>' . strtoupper($headerdata[0]->name) . '</i>', null, null, false, '', '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($headerdata[0]->tel, null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        // $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('<i>Employee Demography - Age Bracket by Gender</i>', null, null, false, '3px solid', '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 15, false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>Date Period: </b>' . $reportdate, '400', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($reporttime, '600', null, false, '2px solid', 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($type != '') {
            $str .= $this->reporter->col('<b>Empoyment Type: &nbsp&nbsp&nbsp</b>' . $type, 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Empoyment Type: &nbsp&nbsp&nbsp</b>' . 'All Type', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        if ($company != '') {
            $str .= $this->reporter->col('<b>Company: &nbsp&nbsp&nbsp</b>' . $company, 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Company: &nbsp&nbsp&nbsp</b>' . 'All Company', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        if ($deptname != '') {
            $str .= $this->reporter->col('<b>Department: &nbsp&nbsp&nbsp</b>' . $deptname, 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Department: &nbsp&nbsp&nbsp</b>' . 'All Department', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        if ($sectname != '') {
            $str .= $this->reporter->col('<b>Section: &nbsp&nbsp&nbsp</b>' . $sectname, 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('<b>Section: &nbsp&nbsp&nbsp</b>' . 'All Section', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 15, false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Age Bracket', '250', null, false, '2px solid', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Male', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Female', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('LGBT', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '100', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '800', null, false, '2px solid', 'B', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '2px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function employee_demography_layout($config)
    {

        $result = $this->default_qry($config);

        $str = '';
        $layoutsize = '1000';
        $font = 'Times New Roman';
        // $font = $this->companysetup->getrptfont($config['params']);
        // $font='Courier New';
        $fontsize = "10";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $count = count($result);

        // if (empty($result)) {
        //     return $this->othersClass->emptydata($config);
        // }

        $ageGroups = [
            '0 to 18'   => ['MALE' => 0, 'FEMALE' => 0, 'LGBT' => 0, 'total' => 0],
            '19 to 22'  => ['MALE' => 0, 'FEMALE' => 0, 'LGBT' => 0, 'total' => 0],
            '23 to 30'  => ['MALE' => 0, 'FEMALE' => 0, 'LGBT' => 0, 'total' => 0],
            '31 to 40'  => ['MALE' => 0, 'FEMALE' => 0, 'LGBT' => 0, 'total' => 0],
            '41 to 100' => ['MALE' => 0, 'FEMALE' => 0, 'LGBT' => 0, 'total' => 0],
        ];
        $undefined = ['male' => 0, 'female' => 0, 'total' => 0];

        // Fill in actual data from query
        foreach ($result as $row) {
            if (isset($ageGroups[$row->age_group])) {
                $ageGroups[$row->age_group] = [
                    'MALE'   => (int)$row->male,
                    'FEMALE' => (int)$row->female,
                    'LGBT' => (int)$row->lgbt,
                    'total'  => (int)$row->male + (int)$row->female + (int)$row->lgbt,
                ];
            } else {
                $undefined = [
                    'MALE'   => (int)$row->male,
                    'FEMALE' => (int)$row->female,
                    'LGBT' => (int)$row->lgbt,
                    'total'  => (int)$row->male + (int)$row->female + (int)$row->lgbt,
                ];
            }
        }

        // Grand totals
        $grandMale   = 0;
        $grandFemale = 0;
        $grandLgbt = 0;
        $grandTotal  = 0;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->employee_demography_header($config);

        foreach ($ageGroups as $label => $counts) {
            $male   = $counts['MALE']   > 0 ? $counts['MALE']   : '-';
            $female = $counts['FEMALE'] > 0 ? $counts['FEMALE'] : '-';
            $lgbt = $counts['LGBT'] > 0 ? $counts['LGBT'] : '-';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($label, '250', null, false, '', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($male, '150', null, false, '', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($female, '150', null, false, '', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($lgbt, '150', null, false, '', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($counts['total'], '100', null, false, '', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandMale   += $counts['MALE'];
            $grandFemale += $counts['FEMALE'];
            $grandFemale += $counts['LGBT'];
            $grandTotal  += $counts['total'];
        }

        // Undefined Employee Gender Row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Undefined Employee Gender', '250', null, false, '2px solid', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($undefined['total'], '100', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $grandTotal += $undefined['total'];

        // Grand Total Row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Total',      '250', null, false, 'T', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandMale,   '150', null, false, 'T', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandFemale, '150', null, false, 'T', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandLgbt, '150', null, false, 'T', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandTotal,  '100', null, false, 'T', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class