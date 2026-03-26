<?php

namespace App\Http\Classes\modules\reportlist\queuing_reports;

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
use Illuminate\Support\Facades\URL;

class queuing_served_per_hour_report
{
    public $modulename = 'Queuing Served Per Hour Report';
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
        $fields = ['radioprint', 'start', 'username'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'username.lookupclass', 'lookupusers2');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
      'default' as print,
      '" . $this->othersClass->getCurrentDate() . "' as start,
      '' as dclientname,
      '' as username,
      '" . $center . "' as center,
      '" . $dcenter[0]->dcentername . "' as dcentername,
      '" . $dcenter[0]->name . "' as centername,
      '' as prefix
      ";
        return $this->coreFunctions->opentable($paramstr);
    }


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

        return $this->reportDefault_Layout($config);
    }


    public function reportDefault_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }


        $query = "select dateid, users,
        sum(case when timedate = 8 then num else 0 end) as eight,
        sum(case when timedate = 9 then num else 0 end) as nine,
        sum(case when timedate = 10 then num else 0 end) as ten,
        sum(case when timedate = 11 then num else 0 end) as eleven,
        sum(case when timedate = 12 then num else 0 end) as twelve,
        sum(case when timedate = 13 then num else 0 end) as onepm,
        sum(case when timedate = 14 then num else 0 end) as twopm,
        sum(case when timedate = 15 then num else 0 end) as threepm,
        sum(case when timedate = 16 then num else 0 end) as fourpm,
        sum(case when timedate = 17 then num else 0 end) as fivepm,
        sum(case when timedate = 18 then num else 0 end) as sixpm
        from (select hc.users, hour(hc.enddate) as timedate, date(hc.dateid) as dateid, count(hc.isdone) as num
        from hcurrentservice as hc
        where hc.isdone = 1 and date(hc.dateid) = '$start' $filter
        group by hc.users, hour(hc.enddate), hc.dateid
        union all
        select hc.users, hour(hc.enddate) as timedate, date(hc.dateid) as dateid, count(hc.isdone) as num
        from currentservice as hc
        where hc.isdone = 1 and date(hc.dateid) = '$start' $filter
        group by hc.users, hour(hc.enddate), hc.dateid) as a
        group by users, dateid
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $result = $this->reportDefault_query($config);
        $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);


        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Queuing Served per Hour', null, null, false, null, null, 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('DATE: ' . $start, null, null, false, '3px solid', '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';


        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $str = '';
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $filter = "";
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        // define all possible hours with their display labels
        $allHours = array(
            'sevenam'  => '7am',
            'eight'    => '8am',
            'nine'     => '9am',
            'ten'      => '10am',
            'eleven'   => '11am',
            'twelve'   => '12pm',
            'onepm'    => '1pm',
            'twopm'    => '2pm',
            'threepm'  => '3pm',
            'fourpm'   => '4pm',
            'fivepm'   => '5pm',
            'sixpm'    => '6pm',
        );

        // get only hours that have transactions
        $activeHours = array();
        foreach ($allHours as $key => $label) {
            foreach ($result as $row) {
                if (isset($row->$key) && $row->$key > 0) {
                    $activeHours[$key] = $label;
                    break;
                }
            }
        }

        // calculate dynamic column width
        $userColWidth = 152;
        $remaining = 1000 - $userColWidth;
        $hourColWidth = count($activeHours) > 0 ? floor($remaining / count($activeHours)) : 94;

        // header row - only show active hours
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('User', $userColWidth, null, false, '1px solid', '', 'L', $font, '12', 'B', '', '');
        foreach ($activeHours as $key => $label) {
            $str .= $this->reporter->col($label, $hourColWidth, null, false, '1px solid', 'TB', 'C', $font, '12', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // initialize totals dynamically
        $totals = array();
        foreach ($activeHours as $key => $label) {
            $totals[$key] = 0;
        }

        // data rows
        foreach ($result as $row) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->users, $userColWidth, null, false, '', '', 'L', $font, $fontsize, '', '', '');
            foreach ($activeHours as $key => $label) {
                $value = isset($row->$key) ? $row->$key : 0;
                $str .= $this->reporter->col($value ? $value : '', $hourColWidth, null, false, '', '', 'C', $font, $fontsize, '', '', '');
                $totals[$key] += $value;
            }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // separator row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', $userColWidth, '30', false, '3px solid', '', 'L', $font, '12', 'B', '', '');
        foreach ($activeHours as $key => $label) {
            $str .= $this->reporter->col('', $hourColWidth, '30', false, '3px solid', '', 'C', $font, '12', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // total row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', $userColWidth, null, false, '1px solid', '', 'L', $font, '12', 'B', '', '');
        foreach ($activeHours as $key => $label) {
            $total = isset($totals[$key]) ? $totals[$key] : 0;
            $str .= $this->reporter->col($total ? $total : '', $hourColWidth, null, false, '1px solid', 'BT', 'C', $font, '12', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class
