<?php

namespace App\Http\Classes\modules\reportlist\sales_agent;

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
use DateTime;


class agent_commission_report
{
    public $modulename = 'Agent Commission Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
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
        $fields = ['radioprint','prepared','approved', 'start', 'end', 'radioreporttype','dagentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1,'radioreporttype.label','Transactions');
        data_set($col1,'radioreporttype.options',
        [
            ['label' => 'Detail Format', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Summary Format', 'value' => '1', 'color' => 'teal']
        ]);
        data_set($col1,'dagentname.label','Salesman');
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("
        select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as 'prepared',
        '' as 'approved',
        0 as agentid,
        '' as dagentname,
        '' as agent,
        '' as agentname,
        '0' as reporttype");
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
        $result = $this->report_Layout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        
        if ($reporttype == 0){ //Detail -- query test
            $query = "
                select ifnull(agent.clientname,'') as clientname,
                year(head.dateid) as yr,
                month(head.dateid) as month,
                sum(stock.ext) as amount
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join client as agent on agent.clientid=head.agentid
                where head.doc in ('SD','SE','SF','SJ','MJ') and year(head.dateid) = '2026'
                and stock.ext <> 0
                group by agent.clientname, year(head.dateid), month(head.dateid)
                order by agent.clientname
            ";
            
        }else if ($reporttype == 1){ //Summary -- query test
            $query = "
                select ifnull(agent.clientname,'') as clientname,
                year(head.dateid) as yr,
                month(head.dateid) as month,
                sum(stock.ext) as amount
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join client as agent on agent.clientid=head.agentid
                where head.doc in ('SD','SE','SF','SJ','MJ') and year(head.dateid) = '2026'
                and stock.ext <> 0
                group by agent.clientname, year(head.dateid), month(head.dateid)
                order by agent.clientname
            ";
        }
        return $this->coreFunctions->opentable($query);
    }

    public function header_layout($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $agentid  = $config['params']['dataparams']['agentid'];
        $agent     = $config['params']['dataparams']['agent'];
        $printDate = date("m/d/Y  g:i:s A");

        $str = '';
        $layoutsize = '1000';
        $fontsize = "10";
        $border = "1px solid ";
        $border2 = "1px dotted ";
        $font = $this->companysetup->getrptfont($config['params']);

        $reporttype = $config['params']['dataparams']['reporttype'];

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->letterhead($center, $username, $config);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SALESMAN COMMISSION REPORT', '1000', null, false, $border, '', 'L', $font, '12', 'B', 'Red', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FROM: ' . date('F d,Y', strtotime($start)) . ' To ' . date('F d,Y', strtotime($end)), '1000', null, false, $border, '', 'L', $font, '12', 'B', 'Red', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Salesman : '.$agent, '1000', null, false, $border, '', 'L', $font, '12', 'B', 'Bue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($printDate, '1000', null, false, $border, '', 'L', $font, $fontsize, 'I', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br>';
        $line = 0;
        if($reporttype == 0){$line = 1600;}else{$line = 1300;};
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', $line, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');  //1380 - 1680
        $str .= $this->reporter->pagenumber('Page', 80, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        // $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

         //columns
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Agent Sales Report', 480, 30, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Collection withing Credit Terms', 300, 30, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Collection Beyond Credit Terms', 300, 30, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', 600, 30, false, $border, '', 'L', $font, $fontsize, 'I', '', '');
            $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            //
            $str .= $this->reporter->col('DATE', 80, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('TYPE', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('A', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('B', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('TOTAL', 100, 30, false, $border2, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('A', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('B', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('TOTAL', 100, 30, false, $border2, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('A', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('B', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('TOTAL', 100, 30, false, $border2, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('Paid Days', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('Allow Days', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('Terms', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        if($reporttype == 0){ //Detailed
            $str .= $this->reporter->col('Status', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('A/R Balance', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('Partial Payment', 100, 30, false, $border2, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }else if ($reporttype == 1){//Summary
            $str .= $this->reporter->col('', 40, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }  
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', $line+80, null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= '<br></br>';
        return $str;
    }

    private function subTotal($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border2 = "1px solid ";
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '0px;margin-top:0px;margin-left:0px');

        $line = 0;
        $border = '';
        if($reporttype == 0){$line = 1680; $border = '1px solid';
        }else{$line = 1380;$border = '1px solid';};
       
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            //
            $str .= $this->reporter->col('DATE'.'Sub Total:', 180, 30, false, $border2, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        if($reporttype == 0){ //Detailed
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }else if ($reporttype == 1){//Summary
            $str .= $this->reporter->col('', 40, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', $line, null, false, $border2, 'T', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    private function granTotal($config)//grand total with commission rate
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border2 = "1px solid ";
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '0px;margin-top:0px;margin-left:0px');

        if($reporttype == 0){$line = 1680; $border = '1px solid';
        }else{$line = 1380;$border = '1px solid';};
       
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            //
            $str .= $this->reporter->col('Sales - Grand Total:', 180, 30, false, $border2, '', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        if($reporttype == 0){ //For Detailed
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }else if ($reporttype == 1){//For Summary
            $str .= $this->reporter->col('', 40, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            //-------------------------------------//
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            //
            $str .= $this->reporter->col('Return - Grand Total:', 180, 30, false, $border2, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        if($reporttype == 0){ //For Detailed
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }else if ($reporttype == 1){//For Summary
            $str .= $this->reporter->col('', 40, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

        //-------------------------------------GRAND TOTAL--------------------------------------------------------------------------------
         $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            //
            $str .= $this->reporter->col('GRAND TOTAL:', 180, 30, false, $border2, '', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        if($reporttype == 0){ //For Detailed
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }else if ($reporttype == 1){//For Summary
            $str .= $this->reporter->col('', 40, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            //-------------------------------------//
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            //
            $str .= $this->reporter->col('', 180, 30, false, $border2, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        if($reporttype == 0){ //For Detailed
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', 30, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }else if ($reporttype == 1){//For Summary
            $str .= $this->reporter->col('', 40, 30, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

        //-----------------------------------COMMISSION RATE---------------------------------

        $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            //
            $str .= $this->reporter->col('COMMISSION RATE', 180, 40, false, $border2, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        if($reporttype == 0){ //For Detailed
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 40, false, $border2, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', 30, 40, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }else if ($reporttype == 1){//For Summary
            $str .= $this->reporter->col('', 40, 40, false, $border2, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


        

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', $line, null, false, $border2, 'T', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .="<br><br>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By:', 150, null, false, '', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '', null, false, '', '', 'L', $font, '12', '', '', '');//var
        $str .= $this->reporter->col('Approved By:', 150, null, false, '', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '', null, false, '', '', 'L', $font, '12', '', '', '');//var
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }

    public function report_Layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $page = 35;
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:30px');
        $str .= $this->header_layout($config);
        $count = 0;
        $datasample = 12;// temp

        $query = $this->reportDefault($config);

        foreach ($query as $data) {

            //month  
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('MONTH', '', null, false, $border2, 'T', 'L', $font, '12', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //column
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', 80, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('Sales Total:', 100, 30, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(number_format($data->amount,2), 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            if($reporttype == 0){ //For Detailed
                $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col('-', 100, 30, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            //--------------------------------------//
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', 80, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('Return Total:', 100, 30, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'BR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            //
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            if($reporttype == 0){ //For Detailed
                $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col('-', 100, 30, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            }
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $count++;

            //the sub total
            $str .= $this->subTotal($config);

            if ($count >= 4) {
                $str .= $this->reporter->page_break();
                $count = 0;
                $str .= $this->header_layout($config);
            }
        }

        //the grand total
        if($count < 4){
        $str .= $this->granTotal($config);
        }else{
        $str .= $this->reporter->page_break();
        $str .= $this->header_layout($config);//could at least remove the column names
        $str .= $this->granTotal($config);
        }
        
        $str .= $this->reporter->endreport();
        return $str;
    }
}