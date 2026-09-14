<?php

namespace App\Http\Classes\modules\payrollentry;

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

class contracthistory
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Contract History';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'hdivinfo';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['dateid', 'num',   'year', 'numdays', 'hours', 'months','days',  'othrs', 'workloc',  'basicrate', 'drate',  'othrsextra', 'cola',
            'apothrs',  'payment', 'comm1', 'comrate',  'leadfrom', 'ssstotal', 'phicee', 'hdmfee',   'eccer',    'agent','tax01','contractn' ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace:normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = "Date";

        $obj[0][$this->gridname]['columns'][$num]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$num]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$num]['label'] = "Guards#";

        $obj[0][$this->gridname]['columns'][$year]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$year]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$year]['label'] = "Days in a Year";

        $obj[0][$this->gridname]['columns'][$numdays]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$numdays]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$numdays]['label'] = "Days in a Week";

        $obj[0][$this->gridname]['columns'][$hours]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$hours]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$hours]['label'] = "Duty Hours";

        $obj[0][$this->gridname]['columns'][$months]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$months]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$months]['label'] = "No. of Months";

        $obj[0][$this->gridname]['columns'][$days]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$days]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$days]['label'] = "No. of Days";

        $obj[0][$this->gridname]['columns'][$othrs]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$othrs]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$othrs]['label'] = "No. of Hours";

        $obj[0][$this->gridname]['columns'][$workloc]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$workloc]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$workloc]['label'] = "Excess Duty";

        $obj[0][$this->gridname]['columns'][$basicrate]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$basicrate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$basicrate]['label'] = "Daily Wage";

        $obj[0][$this->gridname]['columns'][$drate]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$drate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$drate]['label'] = "Basic Salary";

        $obj[0][$this->gridname]['columns'][$othrsextra]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$othrsextra]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$othrsextra]['label'] = "Excess Hours Duty";

        $obj[0][$this->gridname]['columns'][$cola]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$cola]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$cola]['label'] = "Night Pay / Cola";

        $obj[0][$this->gridname]['columns'][$apothrs]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$apothrs]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$apothrs]['label'] = "Overtime Pay";

        $obj[0][$this->gridname]['columns'][$payment]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$payment]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$payment]['label'] = "13th Month Pay";

        $obj[0][$this->gridname]['columns'][$comm1]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$comm1]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$comm1]['label'] = "5 Days Incentive";

        $obj[0][$this->gridname]['columns'][$comrate]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$comrate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$comrate]['label'] = "Uniform Allowance";

        $obj[0][$this->gridname]['columns'][$leadfrom]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$leadfrom]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$leadfrom]['label'] = "Retirement";

        $obj[0][$this->gridname]['columns'][$ssstotal]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$ssstotal]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$ssstotal]['label'] = "SSS Cont.";

        $obj[0][$this->gridname]['columns'][$phicee]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$phicee]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$phicee]['label'] = "PHIC Cont.";

        $obj[0][$this->gridname]['columns'][$hdmfee]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$hdmfee]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$hdmfee]['label'] = "HDMF Cont.";

        $obj[0][$this->gridname]['columns'][$eccer]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$eccer]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$eccer]['label'] = "ECC Cont.";

        $obj[0][$this->gridname]['columns'][$agent]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$agent]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$agent]['label'] = "AGENCY FEE";

        $obj[0][$this->gridname]['columns'][$tax01]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center;";
        $obj[0][$this->gridname]['columns'][$tax01]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$tax01]['label'] = "12% VAT";

        $obj[0][$this->gridname]['columns'][$contractn]['style'] = "width:110px;whiteSpace:normal;min-width:110px;";
        $obj[0][$this->gridname]['columns'][$contractn]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$contractn]['label'] = "TOTAL CONTRACT";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];

        $qry = "select date(dv.editdate) as dateid, format(dv.noguards,2) as num,format(dv.yrdays,2) as year,
        format(dv.wkdays,2) as numdays, format(dv.dutyhrs,2) as hours, format(dv.mons,2) as months,
        format(dv.days,2) as days, format(dv.hrs,2) as othrs,
        format(dv.excesshrs,2) as workloc,format(dv.dailywage,2) as basicrate, format(dv.salary,2) as drate,format(dv.excessduty,2) as othrsextra,
        format(dv.cola,2) as cola,format(dv.otamt,2) as apothrs,  format(dv.amt13th,2) as payment, format(dv.incentive,2) as comm1,
        format(dv.uniformamt,2) as comrate,format(dv.retireamt,2) as leadfrom,format(dv.sssamt,2) as ssstotal,
        format(dv.phicamt,2) as phicee, format(dv.hdmfamt,2) as hdmfee,  format(dv.eccamt,2) as eccer, format(dv.agencyfee,2) as agent, format(dv.agencyvat,2) as tax01,
        format(dv.salary + dv.excessduty +  dv.cola + dv.otamt +  dv.amt13th +  dv.incentive +  dv.uniformamt + dv.retireamt + dv.sssamt + dv.phicamt + dv.hdmfamt + dv.eccamt + dv.agencyfee +  dv.agencyvat, 2) as contractn 
        from hdivinfo as dv  where dv.divid=$tableid order by dv.line desc";

        $data = $this->coreFunctions->opentable($qry);

        return $data;
    }
} //end class