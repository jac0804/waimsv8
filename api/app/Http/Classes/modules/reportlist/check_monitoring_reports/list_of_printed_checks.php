<?php

namespace App\Http\Classes\modules\reportlist\check_monitoring_reports;

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

class list_of_printed_checks
{
    public $modulename = 'List of Printed Checks';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];

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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set(
            $col1,
            'radioprint.options',
            [
                ['label' => 'Default', 'value' => 'default', 'color' => 'red']
            ]
        );
        $fields = ['acnoname5'];
        $col2 = $this->fieldClass->create($fields);
        $fields = [];
        $col3 = $this->fieldClass->create($fields);
        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $companyid = $config['params']['companyid'];
        $params = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as acnoid,
        '' as acnoname,
        '' as acno
        ";
        return $this->coreFunctions->opentable($params);
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
        return $this->list_of_printed_checks_report($config);
    }
    public function query_printed_checks($config)
    {
        $query = " 
                select coa.acnoname,head.docno,detail.checkno,head.rem as remark,date(detail.postdate) as checkdate,sum(detail.cr) as amount,head.clientname FROM glhead as head
                left join gldetail as detail on detail.trno = head.trno
                left join coa on coa.acnoid = detail.acnoid
				where head.doc = 'cv' and left(coa.alias,2) = 'CB'
				group by head.docno,detail.checkno,head.rem ,detail.postdate,coa.acnoname,head.clientname";
        return $this->coreFunctions->opentable($query);
    }
    public function header_printed_checks($config, $layoutsize)
    {
        $border = '1px solid';

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $center1 = $config['params']['center'];
        $username = $config['params']['user'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("# COMPANY", '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("# BANK",  '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("PO #",  '120', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("MRR #",  '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("RFP #",  '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("DOCUMENT #",  '120', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("CANCELLED", '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("CHECK DATE",  '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("CHECK #",  '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("REPLACE CHECK " . '#',  '100', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("NAME",  '130', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("REMARKS",  '200', null, false, $border, 'BT', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("AMOUNT",  '130', null, false, $border, 'BT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        return $str;
    }
    public function list_of_printed_checks_report($config)
    {
        $border = '1px solid';

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $center1 = $config['params']['center'];
        $username = $config['params']['user'];
        $str = '';
        $layoutsize = 1500;
        $data = $this->query_printed_checks($config);
        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        // $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '25px;', 'margin-top:5px;');
        $str .= $this->header_printed_checks($config, $layoutsize);


        foreach ($data as $key => $value) {

            $bank = '';
            if ($value->acnoname != '') {
                $bank = trim(explode('-', $value->acnoname)[0]);
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("", '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($bank,  '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("",  '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("",  '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("",  '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($value->docno,  '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($value->checkdate,  '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($value->checkno,  '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("",  '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($value->clientname,  '130', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($value->remark,  '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($value->amount, 2),  '130', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class