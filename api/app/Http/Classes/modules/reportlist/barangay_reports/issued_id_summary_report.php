<?php

namespace App\Http\Classes\modules\reportlist\barangay_reports;

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

class issued_id_summary_report
{
    public $modulename = 'Issued ID Summary Report';
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
        return $this->issued_id_summary_layout($config);
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

        $query = "select cnum.doc, date(head.dateid) as dateid, head.clientname, c.addr, head.address,  c.client as brgyid, head.amount,
                (select gh.docno
                from glhead gh
                left join cntnum cn on cn.trno = gh.trno 
                where cn.doc = 'CR' and gh.clientid = head.clientid
                limit 1) as ref
                from glhead as head
                left join client as c on c.clientid = head.clientid
                left join cntnum as cnum on cnum.trno = head.trno
                where cnum.doc = 'BK' and date(head.dateid) between '$start' and '$end' $filter
                union all
                select cnum.doc, date(head.dateid) as dateid, head.clientname, c.addr,  head.address, c.client as brgyid,  head.amount,
                (select lh.docno
                from lahead lh
                left join cntnum cn on cn.trno = lh.trno
                where cn.doc = 'CR' and lh.client = head.client 
                limit 1) as ref
                from lahead as head
                left join client as c on c.client = head.client
                left join cntnum as cnum on cnum.trno = head.trno
                where cnum.doc = 'BK' and date(head.dateid) between '$start' and '$end' $filter
                ";
        // var_dump($query);
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
    }

    public function issued_id_summary_header($config)
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

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BARANGAY', '500', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Print Date : ' . $printDate, '500', '20', false, '2px solid', '', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ISSUED ID SUMMARY REPORT', null, null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
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
        $str .= $this->reporter->col('DATE', '120', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BRGY. ID', '170', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FULL NAME', '330', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REF #', '170', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '170', null, false, '2px solid', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function issued_id_summary_layout($config)
    {
        $result = $this->default_qry($config);

        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $count = count($result);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->issued_id_summary_header($config);

        $prevDate = '';
        $dateTotal = 0;
        $dateCount = 0;

        $grandTotal = 0;
        $grandCount = 0;

        foreach ($result as $key => $data) {

            if ($prevDate != '' && $prevDate != $data->dateid) {

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, '10', false, '1px dashed', '', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', null, false, '', 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('TOTAL ID:', '170', null, false, '', 'B', 'LT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($dateCount, '150', null, false, '1px solid', 'TBRL', 'CT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '290', null, false, '', 'B', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('TOTAL AMOUNT : ', '210', null, false, '', 'B', 'RT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($dateTotal, 2) ?: '-', '130', null, false, '1px solid', 'TBRL', 'RT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, '10', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $dateTotal = 0;
                $dateCount = 0;
            }

            if ($prevDate != $data->dateid) {
                $str .= $this->reporter->begintable($layoutsize);
            }

            $dateTotal += $data->amount;
            $dateCount++;

            $grandTotal += $data->amount;
            $grandCount++;

            $str .= $this->reporter->startrow();

            // Only show date if it's different from previous date
            $displayDate = ($prevDate != $data->dateid) ? $data->dateid : '';

            $str .= $this->reporter->col($displayDate, '120', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->brgyid, '170', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '330', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ref, '170', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->amount != 0 ? number_format($data->amount, 2) : '-', '170', null, false, '2px solid', '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $prevDate = $data->dateid;
        }

        if ($prevDate != '') {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', null, '10', false, '1px dashed', '', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '', 'B', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('TOTAL ID:', '170', null, false, '', 'B', 'LT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($dateCount, '150', null, false, '1px solid', 'TBRL', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '290', null, false, '', 'B', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('TOTAL AMOUNT : ', '210', null, false, '', 'B', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($dateTotal, 2) ?: '-', '130', null, false, '1px solid', 'TBRL', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', null, '10', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', '10', false, '2px solid', '', 'C', $font, $font, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '50', null, false, '', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '170', null, false, '', 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '290', null, false, '', 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('GRAND TOTAL : ', '210', null, false, '', 'B', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandTotal, 2) ?: '-', '130', null, false, '1px solid', 'TBRL', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class