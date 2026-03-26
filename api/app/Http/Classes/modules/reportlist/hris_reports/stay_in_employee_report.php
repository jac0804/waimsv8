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

class stay_in_employee_report
{
    public $modulename = 'Stay In Employee Report';
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
        $fields = ['radioprint', 'atype', 'floor'];
        $col1 = $this->fieldClass->create($fields);
        data_set(
            $col1,
            'radioprint.options',
            [['label' => 'Default', 'value' => 'default', 'color' => 'red']]
        );
        data_set($col1, 'atype.readonly', true);
        data_set($col1, 'floor.label', 'Floor No.');
        data_set($col1, 'floor.type', 'lookup');
        data_set($col1, 'floor.action', 'lookupfloor');
        data_set($col1, 'floor.lookupclass', 'lookupfloor');
        data_set($col1, 'floor.required', false);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
                'default' as print,
                '' as type,
                '' as floor
                
    ");
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
        return $this->one_sky_layout($config);
    }
    public function one_sky_query($config)
    {
        $floor = $config['params']['dataparams']['floor'];
        $type = $config['params']['dataparams']['type'];
        $filter = "";
        if ($floor != "") {
            $filter .= " and client.floor = '" . $floor . "'";
        }
        if ($type != "") {
            $filter .= " and app.type = '" . $type . "'";
        }


        $query = "select client.floor from client 
        left join app on app.idno = client.client
        where client.floor <> '' $filter group by client.floor
        order by client.floor";

        return $this->coreFunctions->opentable($query);
    }
    public function onesky_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $floor = $config['params']['dataparams']['floor'];
        $type = $config['params']['dataparams']['type'];
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = 'Century Gothic';
        $font_size = '9';
        if ($floor != "") {
        }
        if ($type != "") {
        }
        if ($floor == "") {
            $floor = 'ALL';
        }
        if ($type == "") {
            $type = 'ALL';
        }

        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#000000'; //black
        $layoutsize = 1000;
        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, $border, $border, '', 'C', $font, '18', 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable(750);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('Floor No: ', 70, null, false, $border, '', 'L', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col($floor, 430, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('Type: ', 70, null, false, $border, '', 'L', $font, '10', 'B', '', '', '');
        $str .= $this->reporter->col($type, 430, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('Count of Name Area', 200, null, $bgcolors, $border, 'BLT', 'L', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('Employment Type', 150, null, $bgcolors, $border, 'BLT', 'C', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('Total', 150, null, $bgcolors, $border, 'BLTR', 'C', $font, '11', 'B', $fontcolor, '5px', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '11', 'B', '5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function one_sky_layout($config)
    {
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = 'Century Gothic';
        $font_size = '12';
        $layoutsize = 1000;
        $fontcolor = '#FFFFFF'; //white
        $bgcolors = '#FFFF00'; //black #000000
        $bgcolor_gtotal = '#D3D3D3'; // light gray
        $str = '';
        $data = $this->one_sky_query($config);

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->onesky_header($config);

        $gtotal = 0;
        $subtotal = 0;

        // var_dump($data);
        $str .= $this->reporter->begintable($layoutsize);
        foreach ($data as $key => $value) {

            $data2 = $this->getcounttype($config, $value->floor);
            $i = 0;
            foreach ($data2 as $key2 => $value2) {

                if ($i == 0) {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col($value->floor, 200, null, false, $border, 'BL', 'L', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col($value2->t, 150, null, false, $border, 'BL', 'C', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col($value2->typecount, 150, null, false, $border, 'BLR', 'C', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
                    $str .= $this->reporter->endrow();
                } else {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col('', 200, null, false, $border, 'BL', 'L', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col($value2->t, 150, null, false, $border, 'BL', 'C', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col($value2->typecount, 150, null, false, $border, 'BLR', 'C', $font, '12', '', '', '', '');
                    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
                    $str .= $this->reporter->endrow();
                }
                $i++;
                $subtotal += $value2->typecount;
            }
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
            $str .= $this->reporter->col($value->floor . ' Total: ', 200, null, $bgcolors, $border, 'BL', 'L', $font, '12', 'B', '', '', '');
            $str .= $this->reporter->col('', 150, null, $bgcolors, $border, 'B', 'C', $font, '12', '', '', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 0), 150, null, $bgcolors, $border, 'BR', 'C', $font, '12', 'B', '', '', '');
            $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
            $str .= $this->reporter->endrow();
            $gtotal += $subtotal;
            $subtotal = 0;
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col('GRAND TOTAL: ', 200, null, $bgcolor_gtotal, $border, 'BL', 'L', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', 150, null, $bgcolor_gtotal, $border, 'BL', 'C', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($gtotal, 0), 150, null, $bgcolor_gtotal, $border, 'BLR', 'C', $font, '12', 'B', '', '', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
    public function getcounttype($config, $floor)
    {
        $type = $config['params']['dataparams']['type'];
        $filter = "";
        if ($floor != "") {
            $filter .= " and client.floor = '" . $floor . "'";
        }
        if ($type != "") {
            $filter .= " and app.type = '" . $type . "'";
        }
        $query = "select count(app.type) as typecount,ifnull(app.type,'') as t from client 
        left join app on app.idno = client.client
        where client.`floor` <> '' $filter
		group by client.floor,app.type having count(app.type) > 0
        order by client.floor ";
        return $this->coreFunctions->opentable($query);
    }
}//end class