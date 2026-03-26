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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class truck_list
{
    public $modulename = 'Truck List';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
        $company = $config['params']['companyid'];

        $fields = ['radioprint', 'truck', 'radioreporttype'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'truck.name', 'clientname');
        data_set($col1, 'truck.lookupclass', 'lookupmitruck');
        data_set($col1, 'truck.required', false);
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Default', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Show Details', 'value' => '1', 'color' => 'teal']
            ]
        );

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
                'default' as print,
                '' as truck,
                '' as clientname,
                '' as client,
                '' as truckid,
                '0' as reporttype
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case 0:
                return $this->reportDefaultLayout($config);
                break;
            case 1:
                return $this->report_showdetails_Layout($config);
                break;
        }
    }

    public function reportDefault($config)
    {
        // QUERY

        $truckid     = $config['params']['dataparams']['truckid'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $filter   = "";

        if ($truckid != "") {
            $filter .= " and clientid = '$truckid'";
        }

        switch ($reporttype) {
            case 0:
                $query = "select clientid,client,clientname,
                    type as model,plateno,classification as brand
                            from client
                            where istrucking=1 $filter
                            order by clientname";
                break;

            case 1:
                $query = "select c.clientid,c.client,c.clientname,
                                whdoc.docno,date(whdoc.expiry) as expiry,whdoc.rem
                        from client as c
                        left join whdoc on whdoc.whid=c.clientid
                        where c.istrucking=1 $filter
                        order by clientname";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
        $font_size = '10';
        $padding = '';
        $margin = '';

        $truckname     = $config['params']['dataparams']['clientname'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        if ($truckname == '') {
            $truckname = 'ALL';
        } else {
            $truckname = $truckname;
        }

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TRUCK LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        if ($truckname == '') {
            $str .= $this->reporter->col('TRUCK : ALL TRUCK', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        } else {
            $str .= $this->reporter->col('TRUCK : ' . strtoupper($truckname), NULL, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    private function default_table_cols($layoutsize, $border, $font, $fontsize)
    {
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TRUCK CODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TRUCK NAME', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PLATE NUMBER', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRAND', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MODEL', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
        $font_size = '10';
        $fontsize11 = 11;
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $this->reporter->linecounter = 0;
        $layoutsize = '1000';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayHeader($config);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);


        foreach ($result as $key => $data) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->client, '100', null, false, $border, $border_line, 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->clientname, '250', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->plateno, '100', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->brand, '100', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->model, '100', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                if (!$allowfirstpage) {
                    $str .= $this->displayHeader($config);
                }
                $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);
                $page = $page + $count;
            }
        } //end foreach


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();
        return $str;
    }



    private function displayshowdetailsHeader($config)
    {

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
        $font_size = '10';
        $padding = '';
        $margin = '';

        $truckname     = $config['params']['dataparams']['clientname'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        if ($truckname == '') {
            $truckname = 'ALL';
        } else {
            $truckname = $truckname;
        }

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TRUCK LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        if ($truckname == '') {
            $str .= $this->reporter->col('TRUCK : ALL TRUCK', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        } else {
            $str .= $this->reporter->col('TRUCK : ' . strtoupper($truckname), NULL, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    private function showdetails_table_cols($layoutsize, $border, $font, $fontsize)
    {
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TRUCK CODE', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TRUCK NAME', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REFERENCE', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('EXPIRY', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NOTES', '230', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }

    public function report_showdetails_Layout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
        $font_size = '10';
        $fontsize = 11;
        $padding = '';
        $margin = '';

        $count = 55;
        $page = 55;
        $this->reporter->linecounter = 0;
        $layoutsize = '800';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayshowdetailsHeader($config);
        $str .= $this->showdetails_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize);

        $truck = '';
        foreach ($result as $key => $data) {


            if ($truck == "" || $truck != $data->client) {
                $truck = $data->client;
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->client, '120', null, false, $border, $border_line, 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->clientname, '250', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->expiry, '100', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->rem, '230', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            } else {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '120', null, false, $border, $border_line, 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '250', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->expiry, '100', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->rem, '230', null, false, $border, $border_line, 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }


            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
                if (!$allowfirstpage) {
                    $str .= $this->displayshowdetailsHeader($config);
                }
                $str .= $this->showdetails_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize);
                $page = $page + $count;
            }
        } //end foreach


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class