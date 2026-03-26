<?php

namespace App\Http\Classes\modules\reportlist\hris_reports;

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
use DateTime;

class wage_and_career_history_report
{
    public $modulename = 'Wage and Career History';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

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
        $fields = ['radioprint',  'divrep', 'deptrep', 'dsectionname', 'atype', 'prepared', 'checked', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'divrep.label', 'Company Name');
        data_set($col1, 'atype.readonly', true);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable("select 
                'default' as print,
                '' as deptrep, 
                '0' as deptid, 
                '' as deptname,
                '' as dept,
                '' as type,
                '' as atype,
                '0' as divid,'' as divcode,
                '' as divname,'' as divrep,
                '' as division,
                '0' as sectid,
                '' as orgsection,
                '' as sectname,
                '' as dsectionname,
                '' as prepared,
                '' as checked,
                '' as approved ");
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
        $divid     = $config['params']['dataparams']['divid'];
        $divrep    = $config['params']['dataparams']['divrep'];
        $department = $config['params']['dataparams']['deptrep'];
        $deptid = $config['params']['dataparams']['deptid'];
        $sectid     = $config['params']['dataparams']['sectid'];
        $type     = $config['params']['dataparams']['type'];


        $filter   = "";
        $filter2 = "";

        if ($divrep != '') {
            $filter .= " and emp.divid = $divid";
        }
        if ($department != "") {
            $filter .= " and emp.deptid=" . $deptid;
        }

        if ($sectid != 0) {
            $filter .= " and emp.sectid = $sectid";
        }

        if ($type != '') {
            $filter2 = " where emptype = '" . $type . "'";
        }
        //and client.resigned is null


        $query = "select right(empcode,4) as empcode,effectivedate,salary,job,emptype,remarks,deptname,sectionname,empname from (
                        select
                            es.docno,client.client as empcode,date(es.effdate) as effectivedate,
                            if(es.tbasicrate !='',es.tbasicrate,es.fbasicrate)  as salary,
                            if(es.tjobcode != '', jt2.jobtitle, jtfrom.jobtitle) as job,
                            if(es.ttype != '', es.ttype, es.ftype) as emptype,
                            es.remarks,dept.clientname as deptname,sect.sectname as sectionname,client.clientname as empname
                        from client
                        left join heschange as es  on es.empid = client.clientid
                        left join jobthead as jtfrom on jtfrom.docno = es.jobcode
                        left join jobthead as jt2  on jt2.docno = es.tjobcode
                        left join employee as emp on emp.empid = client.clientid
                        left join client as dept on dept.clientid = emp.deptid
                        left join section as sect on sect.sectid=emp.sectid 
                        where   es.docno is not null 

                        union all
                        select
                            es.docno,client.client as empcode,date(es.effdate) as effectivedate,
                            if(es.tbasicrate !='',es.tbasicrate,es.fbasicrate)  as salary,
                            if(es.tjobcode != '', jt2.jobtitle,jtfrom.jobtitle) as job,
                            if(es.ttype != '', es.ttype, es.ftype) as emptype,
                            es.remarks,dept.clientname as deptname,sect.sectname as sectionname,client.clientname as empname
                        from client
                        left join eschange as es  on es.empid = client.clientid
                        left join jobthead as jtfrom on jtfrom.docno = es.jobcode
                        left join jobthead as jt2  on jt2.docno = es.tjobcode
                        left join employee as emp on emp.empid = client.clientid
                        left join client as dept on dept.clientid = emp.deptid
                        left join section as sect on sect.sectid=emp.sectid
                        where   es.docno is not null 

                        union all

                        select 'nodoc' as docno, client.client as empcode, date(rate.dateeffect) as effectivedate,
                            rate.basicrate as salary,
                            jt.jobtitle as job, emp.emptype as emptype, emp.remarks,
                            dept.clientname as deptname,sect.sectname as sectionname,client.clientname as empname
                        from client
                        left join employee as emp on emp.empid = client.clientid
                        left join jobthead as jt on jt.line = emp.jobid
                        left join client as dept on dept.clientid = emp.deptid
                        left join section as sect on sect.sectid=emp.sectid
                        left join (select rate.empid,date(rate.dateeffect) as dateeffect, rate.basicrate,rate.hstrno
                                from ratesetup as rate where rate.hstrno=0) as rate on rate.empid=emp.empid
                        where client.isemployee=1 and emp.isactive=1 and rate.dateeffect is not null
                        and client.clientid not in (select empid from heschange  union select empid from eschange)
                        ) as a  $filter2 
                         group by empcode,effectivedate,salary,job,emptype,remarks,deptname,sectionname,empname
                         order by empcode,effectivedate ";

        return $this->coreFunctions->opentable($query);
    }

    //gagawin kong same nung nasa picture naka group sa empcode at empname
    private function displayHeader($config)
    {
        $divid     = $config['params']['dataparams']['divid'];
        $divname     = $config['params']['dataparams']['divname'];
        $deptid     = $config['params']['dataparams']['deptid'];
        $deptname   = $config['params']['dataparams']['deptname'];
        $sectid     = $config['params']['dataparams']['sectid'];
        $sectname    = $config['params']['dataparams']['sectname'];
        $type     = $config['params']['dataparams']['type'];
        $border = '1px solid';
        $font = 'Century Gothic';
        $printDate = date("l, F j, Y");
        $printTime = date("g:i:s A");

        $font_size = 10;

        $fontcolor = '#000000'; //white
        // $bgcolors = '#000000'; //black
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];


        $str = '';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Wage and Career History', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->col('EMPLOYEE: ' . $employee, NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('Company Name :', '160', null,  false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($divid == 0 ? 'All' : strtoupper($divname), '210', null,  false, $border, '', 'L', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Department : ', '100', null,  false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($deptid == 0 ? 'All Departments' : strtoupper($deptname), '150', null,  false, $border, '', 'L', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Section : ', '80', null,  false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sectid == 0 ? 'All sections' : strtoupper($sectname), '150', null,  false, $border, '', 'L', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('Type : ', '50', null,  false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($type == '' ? 'All type' : strtoupper($type), '100', null,  false, $border, '', 'L', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Printed: ', '120', null,  false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($printDate, '190', null,  false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($printTime, '690', null,  false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Emp#', '50', null,  false, '2px solid', 'T', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('Effectivity', '100', null,  false, '2px solid', 'T', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col("Job Title", '220', null,  false, '2px solid', 'T', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('Salary', '80', null,  false, '2px solid', 'T', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('Department/Section', '250', null,  false, '2px solid', 'T', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('Emp.Type', '80', null,  false, '2px solid', 'T', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->col('Remarks', '220', null,  false, '2px solid', 'T', 'C', $font, $font_size, 'B', $fontcolor, '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid'; //#C0C0C0 !important
        $font = 'Century Gothic';
        $font_size = 10;
        $this->reporter->linecounter = 0;
        $count = 21;
        $page = 21;
        $str = '';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayHeader($config);
        $empcode = '';
        foreach ($result as $key => $data) {

            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            if ($empcode == '' || $empcode != $data->empcode) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->empcode, '50', null, false, '1px dotted', 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col($data->empname, '950', null, false, '1px dotted', 'T', 'L', $font, $font_size, 'B', '', '');
                $empcode = $data->empcode;
                $str .= $this->reporter->endrow();
            }
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $dt = new DateTime($data->effectivedate);
            $effectivedate = $dt->format('d/m/Y');
            $str .= $this->reporter->col('', '50', null, false, $border,  '', 'C', $font, $font_size,  '',   '', '5px');
            $str .= $this->reporter->col($effectivedate, '100',  null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
            $str .= $this->reporter->col($data->job, '220',  null, false, $border, '', 'L', $font, $font_size,     '',  '', '5px');
            $str .= $this->reporter->col(number_format($data->salary, 2), '80', null, false,  $border, '', 'C', $font, $font_size, '',  '',  '5px');
            $str .= $this->reporter->col($data->deptname . '/' . $data->sectionname, '250', null, false, $border,  '', 'L', $font, $font_size, '', '', '5px');
            $str .= $this->reporter->col($data->emptype,  '80',  null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
            $str .= $this->reporter->col($data->remarks, '220', null, false, $border, '', 'L',  $font, $font_size, '', '', '5px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();



            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $page = $page + $count;
                // $this->reporter->linecounter = 0; // reset line count
                // $page = $count;
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class