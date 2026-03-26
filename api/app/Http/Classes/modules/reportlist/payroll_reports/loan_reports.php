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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class loan_reports
{
    public $modulename = 'Loan Reports';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'P', 'format' => 'letter', 'layoutSize' => '1200'];

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
    $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'tpaygroup', 'dloantype', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.action', 'lookupdepartments');
    data_set($col1, 'deptrep.lookupclass', 'lookupdepartments');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'deptrep.name', 'department');
    data_set($col1, 'sectrep.action', 'lookupempsection');
    data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
    data_set($col1, 'tpaygroup.lookupclass', 'tpaygrouplookup');
    data_set($col1, 'tpaygroup.action', 'paygrouplookup');
    data_set($col1, 'dloantype.lookupclass', 'lookuploantype');
    array_set($col1, 'start.type', 'date');
    array_set($col1, 'end.type', 'date');


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
        '' as divid,
        '' as divname,
        '' as deptid,
        '' as department,
        '' as orgsection,
        '' as sectname,
        '' as sectrep,
        '' as sectid,
        '' as paygroup,
        '' as tpaygroup,
        '' as paygroupid,
        '' as code,
        '' as codename,
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
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $deptid = $config['params']['dataparams']['deptid'];
        $divid = $config['params']['dataparams']['divid'];
        $sectid = $config['params']['dataparams']['sectid'];
        $sectname = $config['params']['dataparams']['sectname'];
        $code = $config['params']['dataparams']['code'];
        $codename = $config['params']['dataparams']['codename'];
        $tpaygroup = $config['params']['dataparams']['tpaygroup'];

        $filter = '';
        $query = '';
        
        if ($deptid != '') {
            $filter .= " and emp.deptid = $deptid";
        }

        if ($divid != '') {
            $filter .= " and emp.divid = $divid";
        }

        if ($sectname != '') {
        if ($sectid != 0) {
            $filter .= " and emp.sectid = $sectid";
        }}
        
        if ($codename != '') {
            $filter .= " and pa.codename = '$codename'";
        }

        if ($tpaygroup != '') {
            $filter .= " and paygroup.paygroup = '$tpaygroup'";
        }

        $query = "select e.clientname, empstat.empstatus, CONCAT(LEFT(e.client,2), RIGHT(e.client,5)) as employee,
            sum(st.cr) as payment
            from standardsetup as ss
            left join standardtrans as st on st.empid = ss.empid
            left join paccount as pa on pa.line=ss.acnoid
            left join employee AS emp ON emp.empid=ss.empid
            left join client as e on e.clientid = emp.empid
            left join division as d on d.divid = emp.divid
            left join client as dept on dept.clientid = emp.deptid
            left join section as s on s.sectid = emp.sectid
            left join paygroup on paygroup.line = emp.paygroup
            left join empstatentry as empstat on empstat.line = emp.empstatus
            where st.cr<>0 and date(ss.dateid) between '$start' and '$end' and (pa.alias = 'LOAN' or pa.codename LIKE '%LOAN%')
            $filter
            group by e.clientname,empstat.empstatus,e.client
            order by e.clientname";

        return $this->coreFunctions->opentable($query);
    }

     public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $deptname   = $config['params']['dataparams']['department'];
        $divname    = $config['params']['dataparams']['divname'];
        $sectname   = $config['params']['dataparams']['sectname'];
        $codename   = $config['params']['dataparams']['codename'];
        $groupname  = $config['params']['dataparams']['tpaygroup'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $printDate  = date("l, F j, Y");  
        $printTime  = date("g:i:s A");
        $startFormatted = date("F j", strtotime($start));
        $endFormatted = date("F j", strtotime($end));
      
        $str = '';
        $layoutsize = '1000';
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
        $str .= $this->reporter->col('Loan Reports', '800', null, false, '', '', 'C', $font, '14', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date From :  ' . date("d-M-Y", strtotime($start)) . ' to ' . date("d-M-Y", strtotime($end)),'800',null,false,'','','C',$font,'11','');
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');   
        $division = ($divname == '') ? 'ALL COMPANY' : strtoupper($divname);
        $department = ($deptname == '') ? 'ALL DEPARTMENTS' : strtoupper($deptname);
        $section = ($sectname == '') ? 'ALL SECTIONS' : strtoupper($sectname);
        $str .= $this->reporter->col('Division: <b>' . $division .'</b>   Department: <b>' . $department . 
        '</b>   Section: <b>' . $section . '</b>','1000',null,false,'','','C', $font,'11','');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');   
        $loanname = ($codename == '') ? 'ALL LOAN TYPE' : strtoupper($codename);
        $groupname = ($groupname == '') ? 'ALL PAY GROUP' : strtoupper($groupname);
        $str .= $this->reporter->col('Pay Group: <b>' . $groupname .'</b>   Loan Type: <b>' . $loanname . '</b>','1000',null,false,'','','C', $font,'11','');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '700', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');   
        $str .= $this->reporter->col('ID NO.', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('EMPLOYEE NAME', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('PAYMENT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');   
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;

    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $limitPerPage = 45;
        $rowCount = 0;
        $grandTotal = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($result));

        foreach ($result as $data) {

            $grandTotal += $data->payment;

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {

                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // Repeat header every time na nag next page
                $str .= $this->displayHeader($config, count($result));
                $str .= $this->reporter->begintable($layoutsize);
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');   
            $str .= $this->reporter->col($data->employee, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data-> empstatus . ',' . $data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->payment, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');    
            $str .= $this->reporter->endrow();

            $rowCount++;
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $fontsize);
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize);
        $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($grandTotal,2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        
        $str .= $this->reporter->endreport();
        return $str;
    }
  
}//end class