<?php

namespace App\Http\Classes\modules\reportlist\queuing_reports;

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

class queuing_summary_per_counter
    {
    public $modulename = 'Queuing Summary Per Counter';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];
    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)// Essentially the input fields from the web 
    {
        $fields = ['start','end','counter']; // 'name' =>

            $col1 = $this->fieldClass->create($fields);
            data_set($col1,'start.type','date');
            data_set($col1,'end.type','date');
            data_set($col1, 'counter.type', 'lookup');
            data_set($col1, 'counter.lookupclass', 'lookupcounter');  
            data_set($col1, 'counter.action', 'lookupcounter');
            data_set($col1, 'counter.label', 'Queuing Counter');
            

            $fields = ['print'];
            $col2 = $this->fieldClass->create($fields);

        return array('col1'=>$col1, 'col2'=> $col2);
    }
    public function paramsdata($config)//data parameters; the default values of the input fields
    { // 'names' or 'alias'
        return $this->coreFunctions->opentable( "select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '' as counter

        ");
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status'=>true, 'msg'=>'Msg works', 'report'=>$str,'params'=>$this->reportParams];
    }

    public function reportplotting($config)// Type of Report (radio option) case connection
    {
        $data=$this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)  // Query for Detailed Report
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $counter= $config['params']['dataparams']['counter'];
        $filter = "";

        if ($counter!= ''){
        $filter .= " and req.code = '$counter' ";
        }


        $query = '';
        $query = "select `date`, `counter`, count(`Customer`) as `customer`, code,
            sum(case when `Priority` = 0 then `Served` else 0 end) as `rserved`,
            sum(case when `Priority` = 0 then `Cancel` else 0 end) as `rcancel`,
            sum(case when `Priority` = 1 then `Served` else 0 end) as `pserved`,
            sum(case when `Priority` = 1 then `Cancel` else 0 end) as `pcancel`
            from (

            select req.code as `counter`, ser.ctr as `Customer`, ser.isdone as `Served`, ser.iscancel as `Cancel`, ser.ispwd as `Priority`,ser.serviceline, code.code,date(ser.dateid) as `date`
            from reqcategory as req
            left join currentservice as ser on ser.counterline = req.line
            left join reqcategory as code on code.line = ser.serviceline
            where req.iscounter = 1 and counterline  <> 0 and date(ser.dateid) between '$start' and '$end' $filter


            ) as ctr
            group by `date`, `Counter`, code
            order by `date`;";
            // var_dump($query);
        return $this->coreFunctions->openTable($query);  
    }

    public function DefaultHeader($config,$result)
    {
        
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $counter= $config['params']['dataparams']['counter'];
        $str = ''; // required
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $gtotalc  = 0;
        $gtotalrs = 0;
        $gtotalrc = 0;
        $gtotalps = 0;
        $gtotalpc = 0;

        foreach ($result as $row) {
            $gtotalc  += $row->customer;
            $gtotalrs += $row->rserved;
            $gtotalrc += $row->rcancel;
            $gtotalps += $row->pserved;
            $gtotalpc += $row->pcancel;
        }

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('QUEING SUMMARY PER COUNTER ','500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From'. $start . ' to ' . $end,'500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TICKET SUMMARY','500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Served: '. ($gtotalps + $gtotalrs),'500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total Regular: '. ($gtotalrs + $gtotalrc),'500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Cancelled: '. ($gtotalrc + $gtotalpc),'500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total Priority: ' . ($gtotalps + $gtotalpc) ,'500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From:', '50', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($start . ' to ' . $end, '200', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '250');
        $str .= $this->reporter->col('User :', '50', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($counter == '' ? 'ALL USER' : strtoupper($counter), '200', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->pagenumber('Page', '230', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //Columns
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '140', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '140', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Regular', '140', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Priority', '140', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();    
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();     
        $str .= $this->reporter->col('COUNTER', '140', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('# of Customers', '140', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Served', '70', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Cancel', '70', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Served', '70', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Cancel', '70', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config, $result)
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $counter = '';

        $prevCounter = '';
        $date = '';

        $totalc  = 0;
        $totalrs = 0;
        $totalrc = 0;
        $totalps = 0;
        $totalpc = 0;

        $gtotalc = 0;
        $gtotalrs = 0;
        $gtotalrc = 0;
        $gtotalps = 0;
        $gtotalpc = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        // Group per counter using your actual query fields
        $summary_per_counter = [];
        foreach ($result as $row) {
            if (!isset($summary_per_counter[$row->counter])) {
                $summary_per_counter[$row->counter] = [
                    'serve'  => 0,
                    'cancel' => 0,
                ];
            }
            $summary_per_counter[$row->counter]['serve']  += $row->rserved + $row->pserved;
            $summary_per_counter[$row->counter]['cancel'] += $row->rcancel + $row->pcancel;
        }

        $count = 50;
        $page = 50;

        $str = ''; // required
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->DefaultHeader($config, $result);

        foreach ($result as $data)
        {
            $prevCounter = $counter;

            if ($date !== $data->date) {
                $date = $data->date;
                $str .= '<br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(date('m/d/Y', strtotime($data->date)), $layoutsize, null, null, false, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            if ($counter !== $data->counter) {
                $counter = $data->counter;
                if ($prevCounter !== '') {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '140', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                    $str .= $this->reporter->col($totalc, '140', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
                    $str .= $this->reporter->col($totalrs, '70', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
                    $str .= $this->reporter->col($totalrc, '70', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
                    $str .= $this->reporter->col($totalps, '70', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
                    $str .= $this->reporter->col($totalpc, '70', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $totalc = $totalrs = $totalrc = $totalps = $totalpc = 0;
                }
                // Counter label
                $str .= '<br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->counter, $layoutsize, null, null, false, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            // Data row
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->code,     '140', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->customer, '140', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->rserved,  '70',  null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->rcancel,  '70',  null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->pserved,  '70',  null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->pcancel,  '70',  null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalc  += $data->customer;
            $totalrs += $data->rserved;
            $totalrc += $data->rcancel;
            $totalps += $data->pserved;
            $totalpc += $data->pcancel;

            $gtotalc  += $data->customer;
            $gtotalrs += $data->rserved;
            $gtotalrc += $data->rcancel;
            $gtotalps += $data->pserved;
            $gtotalpc += $data->pcancel;
        }

        // Last counter subtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '140', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($totalc,  '140', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($totalrs, '70',  null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($totalrc, '70',  null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($totalps, '70',  null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($totalpc, '70',  null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // SUMMARY PER COUNTER Section
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARY PER COUNTER', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Serve',         '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Cancel',        '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('',                    '500', null, false, $border, '',   'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        foreach ($summary_per_counter as $counterName => $totals) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($counterName,       '150', null, false, $border, '', 'L', $font, $fontsize);
            $str .= $this->reporter->col($totals['serve'],   '150', null, false, $border, '', 'C', $font, $fontsize);
            $str .= $this->reporter->col($totals['cancel'],  '150', null, false, $border, '', 'C', $font, $fontsize);
            $str .= $this->reporter->col('',                 '500', null, false, $border, '', 'L', $font, $fontsize);
            $str .= $this->reporter->endrow();
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

}