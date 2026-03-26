<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class working_clearance_list_report
{
    public $modulename = 'Working Clearance List Report';
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

        $fields = ['start', 'end', 'street'];
        $col1 = $this->fieldClass->create($fields);
        // street lookup
        data_set($col1, 'street.type', 'lookup');
        data_set($col1, 'street.lookupclass', 'lookupstreet');
        data_set($col1, 'street.action', 'lookupstreet');

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
        0 clientid, '' client, '' as clientname, '' as dclientname,
        '' as area, '' as street
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        return $this->working_clearance_list_layout($config);
    }

    // QUERY
    public function default_qry($config)
    {
        // $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $area = ($config['params']['dataparams']['area']);
        $street = ($config['params']['dataparams']['street']);

        $filter = '';
        $leftjoin = '';
        $leftjoin2 = '';
        $leftjoin3 = '';

        if ($area != '') {
            $filter .= " and c.area = '$area'";
        }

        $query = "select cnum.doc, head.clientname, c.addr, head.address, c.client as brgyid
                from glhead as head
                left join client as c on c.clientid = head.clientid
                left join cntnum as cnum on cnum.trno = head.trno
                where cnum.doc = 'BD' and date(head.dateid) between '$start' and '$end' $filter 

                union all

                select cnum.doc, head.clientname, c.addr, head.address, c.client as brgyid
                from lahead  as head
                left join client as c on c.client = head.client
                left join cntnum as cnum on cnum.trno = head.trno
                where cnum.doc = 'BD' and date(head.dateid) between '$start' and '$end' $filter;
                ";
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
    }

    public function working_clearance_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('F-j-Y', strtotime($config['params']['dataparams']['start']));
        $end   = date('F-j-Y', strtotime($config['params']['dataparams']['end']));
        $area = ($config['params']['dataparams']['area']);

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $printDate = date('m/d/Y g:i a');

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->letterhead($center, $username, $config);
        // $str .= $this->reporter->endtable();

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '500', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Print Date : ' . $printDate, '500', '20', false, '2px solid', '', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WORKING CLEARANCE LIST', null, null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE FROM: ' . $start . ' TO ' . $end, null, null, false, '3px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($area != '') {
            $str .= $this->reporter->col('STREET: &nbsp&nbsp' . strtoupper($area), 250, null, false, '2px solid', '', 'LT', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('STREET: &nbsp&nbspALL STREET', 250, null, false, '2px solid', '', 'LT', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '3px solid', 'B', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', 30, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FULL NAME', '350', 30, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', 30, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRGY. ID', '200', 30, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', 30, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ADDRESS', '350', 30, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', 30, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function working_clearance_list_layout($config)
    {

        $result = $this->default_qry($config);

        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        // $font = $this->companysetup->getrptfont($config['params']);
        // $font='Courier New';
        $fontsize = "10";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $count = count($result);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }


        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->working_clearance_header($config);

        $lastKey = count($result) - 1;

        foreach ($result as $key => $data) {
            $isLast = ($key == $lastKey);
            // Don't print bottom border if last record
            $rowBorder = $isLast ? '' : '1px dashed';
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', 30, true,  $rowBorder, 'B', 'C', $font, $fontsize, 'B', '', '', '', '',  '', '', '', '#757575');
            $str .= $this->reporter->col($data->clientname, '350', 30, true,  $rowBorder, 'B', 'LT', $font, $fontsize, '', '', '', '', '',  '', '', '', '#757575');
            $str .= $this->reporter->col('', '10', 30, true,  $rowBorder, '', 'C', $font, $fontsize, 'B', '', '', '', '',  '', '', '', '#757575');
            $str .= $this->reporter->col($data->brgyid, '200', 30, true,  $rowBorder, 'B', 'CT', $font, $fontsize, '', '', '', '', '',  '', '', '', '#757575');
            $str .= $this->reporter->col('', '10', 30, true,  $rowBorder, '', 'C', $font, $fontsize, 'B', '', '', '', '',  '', '', '', '#757575');
            $str .= $this->reporter->col($data->address, '350', 30, true,  $rowBorder, 'B', 'LT', $font, $fontsize, '', '', '', '', '',  '', '', '', '#757575');
            $str .= $this->reporter->col('', '50', 30, true,  $rowBorder, 'B', 'C', $font, $fontsize, 'B', '', '', '', '',  '', '', '', '#757575');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '3px solid', 'B', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class