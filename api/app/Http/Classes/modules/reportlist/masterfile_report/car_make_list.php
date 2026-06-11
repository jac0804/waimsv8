<?php

namespace App\Http\Classes\modules\reportlist\masterfile_report;

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

class car_make_list
{
    public $modulename = 'Car Make List';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
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
        $fields = ['radioprint', 'client'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'lookupcarmake');
        data_set($col1, 'client.action', 'lookupcarmake');
        data_set($col1, 'client.required', false);
        data_set($col1, 'client.label', 'Car Make');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
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
        '' as client,
        0 as clientid
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
        $data = $this->default_qry($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function default_qry($config)
    {
        $carmake = ($config['params']['dataparams']['client']);
        $carid = ($config['params']['dataparams']['clientid']);

        $filter = "";

        if ($carmake != "") {
            $filter .= " and cm.carid = ' " . $carid . "'";
        }

        $query = "select cm.carid, c.carname, cm.`year`, cm.model, cm.`type`, cm.sub_model, cm.other_info
                from cmake as c
                left join cmodel as cm on cm.carid = c.id
                where 1 = 1 $filter";

        return $this->coreFunctions->opentable($query);
    }

    public function defaultHeader($config)
    {
        $center = $config['params']['center'];
        $username   = $config['params']['user'];
        $printDate = date('m/d/Y g:i a');
        $str = ''; // required
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "12";
        $border = "1px solid ";
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $carmake = $config['params']['dataparams']['client'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->endrow();

        $str .= '<br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CAR MAKE LIST', null, null, false, $border, '', 'l', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        if ($carmake != "") {
            $str .= $this->reporter->col('CAR MAKE : ' . '<b> ' . strtoupper($carmake) . '</b>', null, null, false, $border, '', 'L', $font, '11', '', '', '');
        }else {
            $str .= $this->reporter->col('CAR MAKE :' . '<b>' . ' ALL' . '</b>', null, null, false, $border, '', 'L', $font, '11', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CAR MAKE', '200', null, false, $border, 'TB', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('MODEL', '200', null, false, $border, 'TB', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('TYPE', '200', null, false, $border, 'TB', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('SUB MODEL', '200', null, false, $border, 'TB', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('YEAR', '200', null, false, $border, 'TB', 'C', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, null, false, '', 'C', 'Arial', '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config, $result)
    {
        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid ";
        $dashed = "1px dashed";
        $count = 52;
        $page = $count;
        $linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->defaultHeader($config);


        foreach ($result as $row) {
            // if ($linecounter == $count) {
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->reporter->begintable($layoutsize);
            //     $str .= $this->reporter->startrow();
            //     $str .= $this->reporter->col('', null, 20, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            //     $str .= $this->reporter->endrow();
            //     $str .= $this->reporter->endtable();
            //     $page += $count;
            //     $linecounter = 0;
            // }
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->carname, '200', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($row->model, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->type, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->sub_model, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->year, '200', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $linecounter++;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class