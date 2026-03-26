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

class service_served_per_user_report
{
    public $modulename = 'Service Served Per User Report';
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
        $fields = ['radioprint', 'start', 'end', 'username'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
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
      '" . $this->othersClass->getCurrentDate() . "' as end,
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
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
        $filter = "";

        if (!empty($user)) {
            $filter .= " and users = '$user' ";
        }


        $query = "select cs.users,rc.code as description, count(cs.isdone) as total  from currentservice as cs
        left join reqcategory as rc on rc.line = cs.serviceline
        where cs.isdone = 1 and date(cs.dateid) between '$start' and '$end'  $filter
        group by cs.users, rc.code

        union all

        select cs.users,rc.code as description, count(cs.isdone) as total  from hcurrentservice as cs
        left join reqcategory as rc on rc.line = cs.serviceline
        where cs.isdone = 1 and date(cs.dateid) between '$start' and '$end'  $filter
        group by cs.users, rc.code
        order by users, description
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
        $end = date('m/d/Y', strtotime($config['params']['dataparams']['end']));
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
        $str .= $this->reporter->col('Services Served Per User', null, null, false, null, null, 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('From: ' . $start . ' to ' . $end, null, null, false, '3px solid', '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $str = '';
        $layoutsize = 1000;
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }


        $userData = [];
        $descriptions = [];

        foreach ($result as $row) {
            $userData[$row->users][$row->description] = $row->total;

            if (!isset($descriptions[$row->description])) {
                $descriptions[$row->description] = 0;
            }
        }

        // width calculation
        $userWidth        = 150;
        $totalServedWidth = 100;
        $remainingWidth   = $layoutsize - $userWidth - $totalServedWidth;
        $descWidth        = count($descriptions) > 0 ? floor($remainingWidth / count($descriptions)) : $remainingWidth;

        // header
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('User', $userWidth, null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        foreach ($descriptions as $desc => $val) {
            $str .= $this->reporter->col($desc, $descWidth, null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        }
        $str .= $this->reporter->col('Total Served', $totalServedWidth, null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();


        //data row
        $grandTotal = 0;

        foreach ($userData as $user => $data) {
            $rowTotal = array_sum($data);
            $grandTotal += $rowTotal;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($user, $userWidth, '20', false, null, '', 'L', $font, $fontsize);

            foreach ($descriptions as $desc => $val) {
                $cellVal = isset($data[$desc]) ? $data[$desc] : 0;
                $descriptions[$desc] += $cellVal;
                $str .= $this->reporter->col(number_format($cellVal), $descWidth, '20', false, null, '', 'C', $font, $fontsize);
            }

            $str .= $this->reporter->col(number_format($rowTotal), $totalServedWidth, '20', false, null, '', 'R', $font, $fontsize);
            $str .= $this->reporter->endrow();
        }

        //total row
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', $userWidth, null, false, $border, 'TB', 'L', $font, $fontsize, 'B');
        foreach ($descriptions as $desc => $colTotal) {
            $str .= $this->reporter->col(number_format($colTotal), $descWidth, null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        }
        $str .= $this->reporter->col(number_format($grandTotal), $totalServedWidth, null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class
