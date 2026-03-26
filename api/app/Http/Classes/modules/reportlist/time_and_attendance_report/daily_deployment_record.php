<?php

namespace App\Http\Classes\modules\reportlist\time_and_attendance_report;

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

class daily_deployment_record
{
    public $modulename = 'Daily Deployment Record';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => 1200];

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
        $fields = ['radioprint', 'start', 'end', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');

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
                    '' as client, '' as clientname, '' as dclientname");
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        return $this->reportDefaultLayout($config);
    }

    public function reportDefault($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $filter   = "";

        if (isset($config['params']['dataparams']['client'])) {
            if ($config['params']['dataparams']['client'] != "") {
                $filter  .= " and e.client='" . $config['params']['dataparams']['client'] . "'";
            }
        }

        $emplvl = $this->othersClass->checksecuritylevel($config);

        $query = "select empd.line,empd.dateno,empd.empid,e.clientname as employee,date(empd.dateid) as dateid,empd.tothrs,empd.othrs, empd.rem,
                        empd.compcode, empd.pjroxascode1, empd.subpjroxascode, empd.blotroxascode,
                        empd.amenityroxascode, empd.subamenityroxascode, empd.departmentroxascode,empd.achrs,empd.rate
                from empprojdetail as empd
                left join projectroxas as comp on comp.compcode=empd.compcode and comp.code=empd.pjroxascode1
                left join subprojectroxas as subproj on subproj.code=empd.subpjroxascode and subproj.compcode=empd.compcode
                left join blocklotroxas as blocklot on blocklot.code=empd.blotroxascode and blocklot.compcode=empd.compcode
                left join amenityroxas as amnt on amnt.code=empd.amenityroxascode and amnt.compcode=empd.compcode
                left join subamenityroxas as subamnt on subamnt.code=empd.subamenityroxascode and subamnt.compcode=empd.compcode
                left join departmentroxas as dept on dept.code=empd.departmentroxascode and dept.compcode=empd.compcode
                left join client as e on e.clientid=empd.empid
                left join employee as emp on emp.empid=empd.empid
                where dateid between '" . $start . "' and '" . $end . "' 
                      and emp.level in $emplvl $filter  
                order by empd.dateid,empd.compcode, empd.pjroxascode1, empd.subpjroxascode,
                         empd.blotroxascode, empd.amenityroxascode, empd.subamenityroxascode,
                         empd.departmentroxascode, e.clientname";

        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


        $str = '';
        $layoutsize = '900';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "13";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Daily Deployment Record', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $companyid = $config['params']['companyid'];

        $count = 38;
        $page = 40;

        $str = '';
        $layoutsize = '900';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "13";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        $dateid = "";
        $i = 0;
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($dateid != "" && $dateid != $data->dateid) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '1480', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                } //end if

                if ($dateid == "" || $dateid != $data->dateid) {
                    $dateid = $data->dateid;
                    $total = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Work Date: ' . $data->dateid, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->col('Project: ' . $data->pjroxascode1, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->col('Amenity: ' . $data->amenityroxascode, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Company: ' . $data->compcode, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->col('Sub-Project: ' . $data->subpjroxascode, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->col('Sub-Amenity: ' . $data->subamenityroxascode, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Department: ' . $data->departmentroxascode, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->col('Block Lot: ' . $data->blotroxascode, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '2px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('EMPLOYEE NAME', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('WORK HOURS', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('OT HOURS', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('REMARKS', '480', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->employee, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->tothrs, '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->othrs, '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rem, '480', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                if ($dateid == $data->dateid) {
                    $total += $data->tothrs;
                }
                $str .= $this->reporter->endtable();

                if ($i == (count((array)$result) - 1)) {

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '1480', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }
}
