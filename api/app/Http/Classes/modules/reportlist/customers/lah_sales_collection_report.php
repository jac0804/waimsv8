<?php

namespace App\Http\Classes\modules\reportlist\customers;

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
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class lah_sales_collection_report
{
    public $modulename = 'LAH Sales Collection Report';
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
        $fields = ['radioprint', 'start', 'end', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
        data_set($col1, 'dclientname.label', 'Customer');

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
      adddate(left(now(),10),-360) as start,
      date(now()) as end, 
    '' as client,
    '' as clientname,
    '0' as clientid,
      '' as dclientname,
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
        $client       = $config['params']['dataparams']['client'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $filter = "";

        if ($client != "") {
            $filter = " and cl.clientid='$clientid'";
        }

        $query = "   select d.refx, cl.clientname, head.docno, head.dateid, d.ref, head.rem, sum(d.cr) as amount, c.acnoName
                from glhead as head
                left join gldetail as d on d.trno=head.trno
                left join client as cl on cl.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                left join coa as c on c.acnoid=d.acnoid
                left join ( select trno,agentid from glhead where doc = 'SJ') as sj on sj.trno = d.refx

                where head.doc ='CR' and sj.agentid= '3867'   and date(head.dateid) between '$start' and '$end' and d.refx <>'0' $filter
                group by d.refx, cl.clientname, head.docno, head.dateid, d.ref, head.rem, c.acnoName
                order by head.dateid
                ";
        return $this->coreFunctions->opentable($query);
    }


    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $result = $this->reportDefault_query($config);
        $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1500';
        $font = "Roboto Mono";
        $fontsize = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);


        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1500', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $logopath = URL::to($this->companysetup->getlogopath($config['params']) . 'sbclogo1.jpg');
        $path = $this->companysetup->getlogopath($config['params']);
        $path = str_replace('public/', '', $path);
        $logopath = URL::to($path . 'sbclogo1.jpg');

        $str .= "<div style='margin-bottom:20px; text-align:left;margin-left:-110px;margin-top:-20px;'>"; //margin-top:-30px;
        $str .= "<img src='{$logopath}' width='1750' height='250'>";
        $str .= "</div>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LAH SALES COLLECTION REPORT', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('Date Range: ' . $start . ' - ' . $end, null, null, false, '3px solid', '', 'L', $font, '13', 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SALES NO', '250', null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('COLLECT NO', '250', null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('DATE', '150', null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '375', null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('NOTES', '375', null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '2px solid', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefault_Layout($config)
    {

        $str = '';
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter = "";
        $layoutsize = '1500';
        $font = 'Roboto';
        // $font = $this->companysetup->getrptfont($config['params']);
        // $font='Courier New';
        $fontsize = "12";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $count = count($result);
        $currentMonth = '';
        $subtotal = 0;
        $grandtotal = 0;

        for ($i = 0; $i < $count; $i++) {

            $data = $result[$i];
            $month = date("Y-m", strtotime($data->dateid));

            // print subtotal if month changes
            if ($month != $currentMonth) {
                if ($currentMonth != '') {
                    // print subtotal of previous month
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Subtotal: ' . $currentMonth, '1400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $subtotal = 0;
                }

                $currentMonth = $month;

                // print month header
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(date("F Y", strtotime($data->dateid)), '1500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            // ITEM ROW
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->ref, '250', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->docno, '250', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(date("Y-m-d", strtotime($data->dateid)), '150', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '375', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->rem, '375', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, '', '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $subtotal += $data->amount;
            $grandtotal += $data->amount;
        }

        // print last month subtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Subtotal: ' . $currentMonth, '1400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class
