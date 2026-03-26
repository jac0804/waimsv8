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

class coe
{
    public $modulename = 'COE';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $fields = [];

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
        $fields = ['radioprint', 'dclientname', 'end', 'bclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'end.label', 'Date');
        data_set($col1, 'bclientname.label', 'General Manager');
        data_set($col1, 'bclientname.readonly', false);
        data_set($col1, 'dclientname.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            left(now(),10) as `end`,
            '0' as clientid,
              '' as client,
              '' as clientname,
              '' as dclientname,'' as bclientname");
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportLayout($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function reportDefault($config)
    {
        $filter   = "";
        $empid     = $config['params']['dataparams']['clientid'];
        $date     = $config['params']['dataparams']['end'];

        if ($empid != '') {
            $filter = " and emp.empid = $empid";
        }

        $query = "select emp.status,emp.gender,emp.emplast,concat(emp.empfirst, ' ',left(emp.empmiddle,1),'. ',emp.emplast) as employee,CASE WHEN gender = 'Male' THEN 'Mr' WHEN gender = 'Female' AND status = 'Married' THEN 'Mrs'
            WHEN gender = 'Female' THEN 'Ms' END as salutation,job.jobtitle,
            DATE_FORMAT(date(emp.hired), '%M %e, %Y') as hired,
            DATE_FORMAT(date(emp.resigned), '%M %e, %Y') as resigned,
            d.divname as company,d.address as companyaddr,
            CONCAT(DAY('" . $date . "'),
            CASE
                WHEN DAY('" . $date . "') % 100 IN (11, 12, 13) THEN 'th'
                WHEN DAY('" . $date . "') % 10 = 1 THEN 'st'
                WHEN DAY('" . $date . "') % 10 = 2 THEN 'nd'
                WHEN DAY('" . $date . "') % 10 = 3 THEN 'rd'
                ELSE 'th'
            END) as days,DATE_FORMAT('" . $date . "', '%M ') as coemonth,year('" . $date . "') as coeyear
            from employee as emp
            left join jobthead as job on job.line=emp.jobid
            left join division as d on d.divid=emp.divid
            where resigned is not null $filter";

        return $this->coreFunctions->opentable($query);
    }

    public function reportLayout($config)
    {
        $genmanager     = $config['params']['dataparams']['bclientname'];
        $result = $this->reportDefault($config);
        $border = '1px solid  #C0C0C0 !important';
        $font = 'Calibri';
        $font_size = 16;
        $count = 55;
        $page = 55;
        $str = '';

        $layoutsize = 1000;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= '<br/><br/><br/><br/><br/><br/><br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('This is to certify that', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($result[0]->salutation . '. ' . $result[0]->employee), '1000', null, '', $border, '', 'CT', $font, 25, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('was an employee of ' . $result[0]->company . ' as', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($result[0]->jobtitle), '1000', null, '', $border, '', 'CT', $font, 17, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('from ' . $result[0]->hired . ' up to ' . $result[0]->resigned, '1000', null, '', $border, '', 'CT', $font, $font_size, 'I', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $label1 = '';
        $label2 = '';

        switch ($result[0]->gender) {
            case 'Male':
                $label1 = 'his';
                $label2 = 'He';
                break;

            default:
                $label1 = 'her';
                $label2 = 'She';
                break;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Furthermore, ' . $result[0]->salutation . '. ' . $result[0]->emplast . ' is cleared all of ' . $label1 . ' accountabilities, propriety and monetary, in', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('connection with ' . $label1 . ' employment. ' . $label2 . ' is known to be efficient and effective in performing ' . $label1 . '', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('duties and responsibilities and has a good moral character.', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('This certification is being issued upon request by aforementioned name for whatever lawful', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('purposes it may serve.', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Given ths ' . $result[0]->days . ' day of ' . $result[0]->coemonth . ' ' . $result[0]->coeyear . ' at ' . $result[0]->companyaddr . '.', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/><br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($genmanager), '1000', null, '', $border, '', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('General Manager', '1000', null, '', $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class