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

class list_of_pending_task_per_client
{
    public $modulename = 'List of Pending Task Per Client Report';
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
        $fields = ['radioprint', 'start', 'end','empname','dclientname', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        data_set($col1, 'empname.type', 'lookup');
        data_set($col1, 'empname.lookupclass', 'projectheadlookup');
        data_set($col1, 'empname.action', 'lookupclient');
        data_set($col1, 'empname.label', 'Project Head');
    
        data_set($col1, 'radioreporttype.label', 'Format');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Detailed', 'value' => '0', 'color' => 'red'],
            ['label' => 'Summary', 'value' => '1', 'color' => 'red']
        ]);


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
        '" . $this->othersClass->getCurrentDate() . "' as start,
        '" . $this->othersClass->getCurrentDate() . "' as end, 
        '' as empname,
        0 as empcode,
        '' as dclientname,
        '' as clientid,
        '' as client,
        '' as clientname,
        '0' as reporttype
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
        return $this->report_layout($config);
    }

    public function reportDefault($config)
    {
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end  = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $phead = $config['params']['dataparams']['empcode'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $filter = '';

        if ($client != '') {
            $filter .= " and c.client = '$client' ";
        }

        if ($phead != '') {
        $filter .= " and e.client = '$phead' ";
        }

        switch ($reporttype) {
            case 0: //Detailed
                $query = "
                select h.trno, date(h.dateid) as dateid, c.clientname as customer, e.clientname as phead, h.rem
                from tmhead as h
                left join client as c on c.clientid = h.clientid
                left join client as e on e.clientid = h.requestby
                where h.dateid between '$start' and '$end'  $filter
                ";
                break;
            case 1: //Summary
                $query = "
                select h.trno, date(h.dateid) as dateid, c.clientname as customer, e.clientname as phead, h.rem,
                (select count(enddate) as enddate from tmdetail as tm where tm.trno = h.trno and isassigntype = 0) as completed,
                (select count(line) as line from tmdetail as tm where tm.trno = h.trno  and isassigntype = 0) as overall
                from tmhead as h
                left join client as c on c.clientid = h.clientid
                left join client as e on e.clientid = h.requestby
                where h.dateid between '$start' and '$end'  $filter
                group by h.trno, h.dateid, customer, phead, rem
                ";
                break;
        }
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    public function taskListQuery($config)
    {
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end  = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $head = $config['params']['dataparams']['empname'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $query = "
        select d.trno, d.line, d.title, ifnull(u.username,cl.clientname) as userassigned,date(d.startdate) as `start`,date(d.enddate) as `end`
        from tmdetail as d
        left join useraccess as u on u.userid = d.userid
        left join client as cl on cl.clientid = d.userid
        where d.enddate is null and d.isassigntype = 0
        ;
        ";
         
        return $this->coreFunctions->opentable($query);
    }

    public function header_layout($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];

        $str = '';
        $layoutsize = '1000';
        $fontsize = "10";
        $border = "1px solid ";
        $font = $this->companysetup->getrptfont($config['params']);

        $reporttype = $config['params']['dataparams']['reporttype'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LIST OF PENDING TASK PER CLIENT', '1000', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1000', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
         //columns
        if($reporttype == 0){ //Detailed
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DATE', 120, 30, false, $border, 'TB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('CLIENT NAME', 350, 30, false, $border, 'TB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('PROJECT HEAD', 250, 30, false, $border, 'TB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('REMARKS', 250, 30, false, $border, 'TB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }else if ($reporttype == 1){//Summary
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DATE', 100, null, false, $border, 'tB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('CLIENT NAME', 320, null, false, $border, 'tB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('PROJECT HEAD', 230, null, false, $border, 'tB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('REMARKS', 220, null, false, $border, 'tB', 'LM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('PENDING TASK', 100, null, false, $border, 'tB', 'CM', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= '<br></br>';
        return $str;
    }

    private function innerheader_layout($config)//column names of TaskLists
    {
        $str = '';
        $layoutsize = '1000';
        $fontsize = "10";
        $border = "1px dotted";
        $font = $this->companysetup->getrptfont($config['params']);

         //columns
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 95, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Task', 270, null, false, $border, 'LTB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Assigned to', 200, null, false, $border, 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Start Date', 80, null, false, $border, 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('End Date', 80, null, false, $border, 'RTB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', 25, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    private function lastLine($config){ //closing line of the sub rows
        $str = '';
        $layoutsize = '1000';
        $fontsize = "10";
        $border = "1px dotted";
        $font = $this->companysetup->getrptfont($config['params']);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 95, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', 630, null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', 25, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function report_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px dotted ";
        $page = 35;
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str .= $this->reporter->beginreport();
        $str .= $this->header_layout($config);
        $this->reporter->linecounter = 0;
        $mains = $this->reportDefault($config);
        $subs= $this->taskListQuery($config);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '990', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subsByTrno = [];

        if ($reporttype == 0){ //Detailed
            foreach ($subs as $sub) {
                $subsByTrno[$sub->trno][] = $sub;
            }
            foreach ($mains as $main) {
                $trno = isset($main->trno) ? $main->trno : '';

                $lines = $this->reporter->estimateRowLines([
                    [$main->dateid, 120, '6px'],
                    [$main->customer, 350, '6px'],
                    [$main->phead, 250, '6px'],
                    [$main->rem, 250, '6px'],
                ], $fontsize  * 0.5);
                for ($l = 0; $l < $lines; $l++) {
                    $this->reporter->addline();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();//main
                $str .= $this->reporter->col($main->dateid, 120, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col($main->customer, 350, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col($main->phead, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col($main->rem, 250, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $subrows = isset($subsByTrno[$trno]) ? $subsByTrno[$trno] : [];

                if ($this->reporter->linecounter >= $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_layout($config);
                    $this->reporter->linecounter = 0;
                }

                if (!empty($subrows)) {
                    $str .= $this->innerheader_layout($config);
                    $this->reporter->addline();
                }

                //Task List
                foreach ($subrows as $sub) {
                    $sublines = $this->reporter->estimateRowLines([
                        [$sub->title, 270, '6px'],
                        [$sub->userassigned, 200, '6px'],
                        [$sub->start, 80, '6px'],
                        [$sub->end, 80, '6px'],
                    ], $fontsize * 0.5);
                    for ($l = 0; $l < $sublines; $l++) {
                        $this->reporter->addline();
                    }
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();//sub
                    $str .= $this->reporter->col('', 95, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                    $str .= $this->reporter->col($sub->title, 270, null, false, $border, 'L', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                    $str .= $this->reporter->col($sub->userassigned, 200, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                    $str .= $this->reporter->col($sub->start, 80, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                    $str .= $this->reporter->col($sub->end, 80, null, false, $border, 'R', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                    $str .= $this->reporter->col('', 25, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    

                    if ($this->reporter->linecounter >= $page) {
                        $str .= $this->lastLine($config);
                        $str .= $this->reporter->page_break();
                        $str .= $this->header_layout($config);
                        // if (!empty($subrows)) {
                        //     $str .= $this->innerheader_layout($config);
                        //     $this->reporter->addline();
                        // }
                        $this->reporter->linecounter = 0;
                    }
                }
                if (!empty($subrows)) {
                $str .= $this->lastLine($config);
                }
                $this->reporter->addline();
                $str .= '<br>';
                
            }
        }else if ($reporttype == 1){ //Summary
            foreach ($mains as $main) {

                $lines = $this->reporter->estimateRowLines([
                    [$main->dateid, 100, '6px'],
                    [$main->customer, 320, '6px'],
                    [$main->phead, 230, '6px'],
                    [$main->rem, 220, '6px'],
                ], $fontsize);
                for ($l = 0; $l < $lines; $l++) {
                    $this->reporter->addline();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($main->dateid, 100, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col($main->customer, 320, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col($main->phead, 230, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col($main->rem, 220, null, false, $border, '', 'LT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->col($main->completed. '/'.$main->overall, 100, null, false, $border, '', 'CT', $font, $fontsize, '', '', '6px', '', 0, '', 0, 0, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter >= $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_layout($config);
                    $this->reporter->linecounter = 0;
                }
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }


}