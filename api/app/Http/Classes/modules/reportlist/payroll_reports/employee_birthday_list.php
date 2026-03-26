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
use App\Http\Classes\modules\warehousing\forklift;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class employee_birthday_list
{
  public $modulename = 'Employee Birthday List';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $batch;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'atype','month', 'month2', 'prepared', 'checked', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.action', 'lookupdepartments');
        data_set($col1, 'deptrep.lookupclass', 'lookupdepartments');
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'deptrep.name', 'department');
        data_set($col1, 'sectrep.action', 'lookupempsection');
        data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
        data_set($col1, 'atype.lookupclass', 'lookupatype');
        // data_set($col1, 'empstattype.lookupclass', 'empstatlookup');
        data_set($col1, 'atype.label', 'Emptype');
        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        data_set($col1, 'month2.type', 'lookup');
        data_set($col1, 'month2.readonly', true);
        data_set($col1, 'month2.action', 'lookuprandom');
        data_set($col1, 'month2.lookupclass', 'lookup_month2');
        // array_set($col1, 'start.type', 'date');
        // array_set($col1, 'end.type', 'date');


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as bmonth,'' as month,  '' as bmonth2,'' as month2, 
        '' as divid,
        '' as divname,
        '' as deptid,
        '' as department,
        '' as orgsection,
        '' as sectname,
        '' as sectrep,
        '' as sectid,
        '' as type,
        '' as prepared,
        '' as checked, 
        '' as approved

     ");
    }
    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function getloaddata($config)
    {
        return [];
    }
    
    public function reportplotting($config)
    {
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $deptid = $config['params']['dataparams']['deptid'];
        $month1 = $config['params']['dataparams']['bmonth'];
        $month2 = $config['params']['dataparams']['bmonth2'];
        $divid = $config['params']['dataparams']['divid'];
        $sectid = $config['params']['dataparams']['sectid'];
        $sectname = $config['params']['dataparams']['sectname'];
        $emptype = $config['params']['dataparams']['type'];

        $filter = '';
        $query = '';
        
        if ($deptid != '') {
            $filter .= " and e.deptid = $deptid";
        }

        if ($divid != '') {
            $filter .= " and e.divid = $divid";
        }

        if ($sectname != '') {
        if ($sectid != 0) {
            $filter .= " and e.sectid = $sectid";
        }}

        if ($emptype != '') {
            $filter .= " and e.emptype  = '$emptype'";
        }

        $query = "select concat(e.emplast, ' ', e.empfirst,' ' , e.empmiddle)as name ,DATE_FORMAT(e.bday,'%M %d, %Y') as bday, e.dept as dept, s.sectname as section, TIMESTAMPDIFF(year, e.bday, curdate()) as age
            from client as c
            left join employee as e on e.empid = c.clientid 
            left join section as s on s.sectid = e.sectid
            left join client as dept on dept.clientid = e.deptid
            left join division as d on d.divid = e.divid
            where c.isemployee = 1 and month(e.bday) between $month1 and $month2 $filter
            order by month(e.bday), day(e.bday), name";
        // var_dump($query);

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $month1 = $config['params']['dataparams']['bmonth'];
        $month2 = $config['params']['dataparams']['bmonth2'];
        $department = $config['params']['dataparams']['department'];
        $divname = $config['params']['dataparams']['divname'];
        $sectname = $config['params']['dataparams']['sectname'];
        $emptype = $config['params']['dataparams']['type'];
        $printDate = date("l, F j, Y");  
        $printTime = date("g:i:s A");
        $startFormatted = date("F", mktime(0,0,0,$month1,1));
        $endFormatted = date("F", mktime(0,0,0,$month2,1));
      
        $str = '';
        $layoutsize = '800';
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

      
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Sub total per Sales', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('Employee Birthday List ', '700', null, false, '10px solid ', '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Printed :', '120', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($printDate . '  ' . $printTime, '270', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '40');
        $str .= $this->reporter->col('Covered Month :', '130', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($startFormatted . ' to ' . $endFormatted, '170', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '80');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Company :', '120', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($divname == '' ? 'ALL COMPANY' : strtoupper($divname), '270', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '40');
        $str .= $this->reporter->col('Emp. Type :', '130', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($emptype == '' ? 'ALL TYPE' : strtoupper($emptype), '170', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '80');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

       
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Department :', '120', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($department == '' ? 'ALL DEPARTMENT' : strtoupper($department), '270', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '40');
        $str .= $this->reporter->col('Section :', '130', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($sectname == '' ? 'ALL SECTION' : strtoupper($sectname), '170', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '80');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '780', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NAME', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BIRTHDAY', '140', null, false, $border, 'TB', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('AGE', '50', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('DEPARTMENT/SECTION', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;

    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '800';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $limitPerPage = 45;
        $rowCount = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($result));

        foreach ($result as $data) {

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {

                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // Repeat header every time na nag next page
                $str .= $this->displayHeader($config, count($result));
                $str .= $this->reporter->begintable($layoutsize);
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->name, '250', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->bday, '140', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->age, '50', null, false, $border, '', 'R', $font, '10', '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, '10', '', '', '');
            
            $dept = trim($data->dept);
            $section = trim($data->section);
            if ($dept != '' && $section != '') {
                $deptSection = $dept . '/' . $section;
            } elseif ($dept != '') {
                $deptSection = $dept;
            } elseif ($section != '') {
                $deptSection = $section;
            } else {
                $deptSection = '';
            }
            $str .= $this->reporter->col($deptSection, '250', null, false, $border, '', 'L', $font, $fontsize);

            $str .= $this->reporter->endrow();

            $rowCount++;
        }
        $str .= $this->reporter->endtable();

        $totalClient = count($result);

        // $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EMPLOYEE(S): ', '200', null, false, $border, 'TB', 'L', $font, '11', 'B');
        $str .= $this->reporter->col($totalClient, '100', null, false, $border, 'TB', 'L', $font, '11', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // For footer
        $str .= '<br/><br/>';
        $config['params']['doc'] = $this->modulename;
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'posted', $dataparams['approved']);
        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared by: ', '300', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Checked by: ', '300', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Posted By: ', '300', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . $config['params']['dataparams']['prepared'], '300', 30, false, $border, 'B', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '100', 30, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('' . $config['params']['dataparams']['checked'], '300', 30, false, $border, 'B', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '100', 30, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('' . $config['params']['dataparams']['approved'], '300', 30, false, $border, 'B', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }


}//end class