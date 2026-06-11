<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class soalog_report
{
    public $modulename = 'SOA Log Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['year', 'dclientname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'year.type', 'lookup');
        data_set($col2, 'year.class', 'sbccsreadonly');
        data_set($col2, 'year.lookupclass', 'lookupyear');
        data_set($col2, 'year.action', 'lookupyear');

        data_set($col2, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col2, 'dclientname.label', 'Customer');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {

        return $this->coreFunctions->opentable("
        select 'default' as print,
        left(now(),4) as year,
        '' as month,
        '' as bmonth,
        '' as dclientname,
        '' as client,
        '' as clientid,
        '' as clientname
        ");
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

    public function default_query($config)
    {
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $year = ($config['params']['dataparams']['year']);

        $filter = '';

        if ($clientname != '') {
            $filter .= " and client.clientid = '$clientid'";
        }

        if ($year != '') {
            $filter .= " and sl.year = '$year'";
        }

        $query = "select client.clientname, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, `dec` 
        from soalog as sl
        left join client on client.clientid = sl.clientid 
        where 1=1 $filter 
        order by clientname
        ;";
        // var_dump($query);
        $data = $this->coreFunctions->opentable($query);
        return $data;
    }

    public function reportplotting($config)
    {

        return $this->reportDefault_Layout($config);
    }




    public function displayHeader($config)
    {
        $result = $this->default_query($config);
        $year = $config['params']['dataparams']['year'];
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1600';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SOA Logs For Year' . ' ' . $year, null, null, false, '10px solid ', '', 'L', $font, '12', 'B', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer Name', '160', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Jan', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Feb', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Mar', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Apr', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('May', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('June', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('July', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Aug', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Sep', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Oct', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Nov', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Dec', '120', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefault_Layout($config)
    {

        $str = '';

        $filter = "";
        $layoutsize = '1000';
        $font = 'Tahoma';
        // $font = $this->companysetup->getrptfont($config['params']);
        // $font='Courier New';
        $fontsize = "11";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->default_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);


        $totalRows = count($result);
        $currentRow = 0;
        foreach ($result as $row) {

            // ITEM ROW 
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->clientname, '160', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->jan, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->feb, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->mar, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->apr, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->may, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->jun, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->jul, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->aug, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->sep, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->oct, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->nov, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->dec, '120', '40', false, '', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1600', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class