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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class customer_registration_report
{
    public $modulename = 'Customer Registration Report';
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
        $fields = ['radioprint', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        // data_set($col1, 'start.required', true);
        // data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        // data_set($col1, 'dcentername.readonly', false);
        // data_set($col1, 'prefix.readonly', false);

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
      '" . $center . "' as center,
      '" . $dcenter[0]->dcentername . "' as dcentername,
      '" . $dcenter[0]->name . "' as centername,
      '' as prefix,
      '0' as reporttype,
      0 clientid, '' client, '' as clientname, '' as dclientname
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
        $reporttype = $config['params']['dataparams']['reporttype'];
        // customer sales
        return $this->customer_reg_layout($config);
    }

    // QUERY
    public function customer_reg_qry($config)
    {
        // $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $center = ($config['params']['center']);

        $filter = '';

        if ($clientname != '') {
            $filter .= " and c.clientid = $clientid";
        }

        $query = "select center.name as center, c.client as code, c.clientname, head.docno, p.station, p.remarks, p.serial, p.others
                from glhead as head
                left join client as c on c.clientid = head.clientid
                left join center on center.code = c.center
                left join hparticulars as p on p.trno = head.trno
                where head.doc = 'SJ' and p.trno <> 0 and c.center = '$center' $filter
                union all
                select center.name as center, c.client as code, c.clientname, head.docno, p.station, p.remarks, p.serial, p.others
                from lahead as head
                left join client as c on c.clientid = head.client
                left join center on center.code = c.center
                left join hparticulars as p on p.trno = head.trno
                where head.doc = 'SJ' and p.trno <> 0 and c.center = '$center' $filter
                order by clientname;
                ";
        // var_dump($query);
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
    }

    // customer registration
    public function customer_reg_layout_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $clientname = ($config['params']['dataparams']['clientname']);
        $str = '';
        $layoutsize = '1000';
        $font = "Roboto";
        $fontsize = "10";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->tel, null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '', '', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('CUSTOMER REGISTRATION', null, null, false, '3px solid', '', 'L', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br> ', null, null, true, '2px solid', 'T', 'L', $font, '12', '', '', '', '', '', '', '', '', '#eeeeee');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($clientname == "") {
            $clientname = "ALL";
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CLIENT: ' . $clientname, null, null, false, '2px dotted', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->pagenumber('Page', null, null, false, '', '', 'R');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<br> ', null, null, true, '2px solid', 'B', 'L', $font, '12', '', '', '', '', '', '', '', '', '#eeeeee');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('D O C N O', '200', null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('S T A T I O N', '200', null, false, '2px solid', 'B', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('S E R I A L&nbspNO.', '150', null, false, '2px solid', 'B', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('R E M', '250', null, false, '2px solid', 'B', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('O T H E R S', '200', null, false, '2px solid', 'B', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function customer_reg_layout($config) // customer registration layout
    {

        $str = '';
        $layoutsize = '1000';
        $font = 'Roboto';
        // $font = $this->companysetup->getrptfont($config['params']);
        // $font='Courier New';
        $fontsize = "12";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->customer_reg_qry($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->customer_reg_layout_header($config);

        $count = count($result);

        $currentCustomer = '';
        $isFirstCustomer = true;

        for ($i = 0; $i < $count; $i++) {

            $data = $result[$i];

            // if ($currentCustomer != $data->clientname) 
            if ($currentCustomer != (trim($data->clientname) != '' ? $data->clientname : 'WALK-IN')) {

                $currentCustomer = trim($data->clientname) != '' ? $data->clientname : 'WALK-IN';
                // $currentCustomer = $data->clientname;

                $borderType = $isFirstCustomer ? 'B' : 'TB';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->center . ' - ' . $data->code . ' - ' . $currentCustomer, '1000', null, false, '2px dotted', $borderType, 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $isFirstCustomer = false;
            }
            // ITEM ROW
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->docno, '200', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->station, '200', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->serial, '150', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->remarks, '250', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->others, '200', null, false, '2px solid', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class
