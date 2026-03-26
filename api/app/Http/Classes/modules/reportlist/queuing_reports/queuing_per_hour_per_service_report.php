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

class queuing_per_hour_per_service_report
{
    public $modulename = 'Queuing per Hour per Service Report';
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
        $this->companysetup  = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass   = new othersClass;
        $this->fieldClass    = new txtfieldClass;
        $this->reporter      = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $fields = ['radioprint', 'start', 'servicedep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'servicedep.label', 'Queuing Service');
        data_set($col1, 'servicedep.type', 'lookup');
        data_set($col1, 'servicedep.lookupclass', 'lookupqservice');
        data_set($col1, 'servicedep.action', 'lookupqservice');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center    = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter   = $this->coreFunctions->opentable(
            "select name,code,concat(code,'~',name) as dcentername from center where code = ?",
            [$center]
        );

        $paramstr = "select 
            'default' as print,
            date(now()) as start,
            '' as client,
            '' as clientname,
            '0' as clientid,
            '' as servicedep,
            0 as serviceline,
            '' as area,
            '' as dclientname,
            '" . $center . "' as center,
            '" . $dcenter[0]->dcentername . "' as dcentername,
            '" . $dcenter[0]->name . "' as centername,
            '' as prefix";

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

        return [
            'status' => true,
            'msg'    => 'Generating report successfully.',
            'report' => $str,
            'params' => $this->reportParams
        ];
    }

    public function reportplotting($config)
    {
        return $this->reportDefault_Layout($config);
    }

    public function reportDefault_query($config)
    {
        $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $servicedep  = $config['params']['dataparams']['servicedep'];
        $serviceline = $config['params']['dataparams']['serviceline'];
        $filter      = "";

        if ($servicedep != '') {
            $filter .= " and hc.serviceline = '" . $serviceline . "'";
        }

        $query = "select hc.serviceline, rc.code, rc.description,HOUR(hc.enddate) AS timedate,DATE(hc.enddate) AS dateid,
                  COUNT(hc.isdone) AS num

                  FROM hcurrentservice AS hc
                  LEFT JOIN reqcategory AS rc ON hc.serviceline = rc.line
                  WHERE hc.isdone = 1 AND DATE(hc.enddate) = '$start' $filter
                  GROUP BY hc.serviceline, HOUR(hc.enddate), rc.code, rc.description, DATE(hc.enddate)

                  UNION ALL

                  select hc.serviceline, rc.code, rc.description,HOUR(hc.enddate) AS timedate,DATE(hc.enddate) AS dateid,
                  COUNT(hc.isdone) AS num

                  FROM currentservice AS hc
                  LEFT JOIN reqcategory AS rc ON hc.serviceline = rc.line
                  WHERE hc.isdone = 1 AND DATE(hc.enddate) = '$start' $filter
                  GROUP BY hc.serviceline, HOUR(hc.enddate), rc.code, rc.description, DATE(hc.enddate)
                  ORDER BY code, dateid";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $font       = 'Century Gothic';
        $start      = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
        $layoutsize = 1000;
        $str        = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Queuing Per Hour Per Service', null, null, false, null, null, 'L', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE: ' . $start, null, null, false, '3px solid', '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $font       = 'century gothic';
        $fontsize   = '12';
        $layoutsize = '1000';

        $str = '';
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        
        $services   = [];
        $hoursFound = [];

        foreach ($result as $data) {
            $code = $data->code;
            $hour = (int) $data->timedate;
            $num  = (int) $data->num;

            if (!isset($services[$code])) {
                $services[$code] = [
                    'description' => $data->description,
                    'hours'       => [],
                ];
            }

            if (!isset($services[$code]['hours'][$hour])) {
                $services[$code]['hours'][$hour] = 0;
            }

            $services[$code]['hours'][$hour] += $num;
            $hoursFound[$hour] = true;
        }

        ksort($hoursFound);
        $hours = array_keys($hoursFound);

        
        $labelWidth  = 152;
        $remaining   = 1000 - $labelWidth;
        $colWidth    = count($hours) > 0 ? floor($remaining / count($hours)) : 94;

        
        $hourLabel = function ($h) {
            if ($h == 0)  return '12AM';
            if ($h < 12)  return $h . 'AM';
            if ($h == 12) return '12PM';
            return ($h - 12) . 'PM';
        };

        
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Service', $labelWidth, null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');

        foreach ($hours as $h) {
            $str .= $this->reporter->col($hourLabel($h), $colWidth, null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        
        $grandTotal      = 0;
        $grandTotalPerHr = array_fill_keys($hours, 0);

        foreach ($services as $code => $info) {
            $rowTotal = 0;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($code, $labelWidth, null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');

            foreach ($hours as $h) {
                $val                 = isset($info['hours'][$h]) ? $info['hours'][$h] : 0;
                $rowTotal            += $val;
                $grandTotalPerHr[$h] += $val;
                $display             = $val > 0 ? $val : '-';

                $str .= $this->reporter->col($display, $colWidth, null, false, '1px solid', '', 'C', $font, $fontsize, '', '', '');
            }

            $grandTotal += $rowTotal;
            $str .= $this->reporter->endrow();
        }

        
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', $labelWidth, null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');

        foreach ($hours as $h) {
            $str .= $this->reporter->col($grandTotalPerHr[$h], $colWidth, null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endreport();
        return $str;
    }
}