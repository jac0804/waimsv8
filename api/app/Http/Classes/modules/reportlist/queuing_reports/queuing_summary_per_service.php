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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class queuing_summary_per_service
{
    public $modulename = 'Queuing Summary Per Service';
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

        $fields = ['start', 'end', 'servicedep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'servicedep.label', 'Queuing Service');
        data_set($col1, 'servicedep.type', 'lookup');
        data_set($col1, 'servicedep.lookupclass', 'lookupqservice');
        data_set($col1, 'servicedep.action', 'lookupqservice');

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
        adddate(left(now(),10),-30) as start,
        date(now()) as end, 
        0 clientid, '' client, '' as clientname, '' as dclientname,
        '0' as reporttype,
        0 as serviceline, '' as servicedep
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
        return $this->summary_per_service_layout($config);
    }

    // QUERY
    public function default_qry($config)
    {
        // $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $servicedep = ($config['params']['dataparams']['servicedep']);
        $serviceline = ($config['params']['dataparams']['serviceline']);

        $filter = '';

        if ($servicedep != '') {
            $filter .= " and cs.serviceline = '" . $serviceline . "'";
        }

        $query = "select date, service,
                  sum(ctrcount) as ctrcount,
                  sum(rserved) as rserved,
                  sum(rcancel) as rcancel,
                  sum(pserved) as pserved,
                  sum(pcancel) as pcancel
                from (
                  select date(cs.dateid) as date, r.code as service, count(*) as ctrcount,
                    sum(case when ispwd = 0 and isdone = 1 then isdone else 0 end) as rserved,
                    sum(case when ispwd = 0 and iscancel = 1 then iscancel else 0 end) as rcancel,
                    sum(case when ispwd = 1 and isdone = 1 then isdone else 0 end) as pserved,
                    sum(case when ispwd = 1 and iscancel = 1 then iscancel else 0 end) as pcancel
                  from currentservice as cs
                  left join reqcategory as r on r.line = cs.serviceline
                  where counterline <> 0 and date(dateid) between '$start' and '$end' $filter
                  group by r.code, cs.dateid
                
                  union all
                
                  select date(cs.dateid) as date, r.code as service, count(*) as ctrcount,
                    sum(case when ispwd = 0 and isdone = 1 then isdone else 0 end) as rserved,
                    sum(case when ispwd = 0 and iscancel = 1 then iscancel else 0 end) as rcancel,
                    sum(case when ispwd = 1 and isdone = 1 then isdone else 0 end) as pserved,
                    sum(case when ispwd = 1 and iscancel = 1 then iscancel else 0 end) as pcancel
                  from hcurrentservice as cs
                  left join reqcategory as r on r.line = cs.serviceline
                  where counterline <> 0 and date(dateid) between '$start' and '$end' $filter
                  group by r.code, cs.dateid
                ) as x
                
                group by date, service
                order by date
                ";
        // var_dump($query);
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
    }

    public function service_summary_qry($config)
    {
        // $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid = ($config['params']['dataparams']['clientid']);
        $clientname = ($config['params']['dataparams']['clientname']);
        $servicedep = ($config['params']['dataparams']['servicedep']);
        $serviceline = ($config['params']['dataparams']['serviceline']);

        $filter = '';

        if ($servicedep != '') {
            $filter .= " and cs.serviceline = '" . $serviceline . "'";
        }


        $query = "select
              case when ispwd = 0 then 'Regular' else 'Priority' end as types,
              sum(case when isdone = 1 then 1 else 0 end) as served,
              sum(case when iscancel = 1 then 1 else 0 end) as cancel,
              service,sum(waittime) as waittime
            from (
              select cs.ispwd,
                case when isdone = 1 then 1 else 0 end as isdone,
                case when iscancel = 1 then 1 else 0 end as iscancel,
                r.code as service,cs.dateid,cs.startdate,ifnull(timestampdiff(minute,cs.dateid,cs.startdate),0) as waittime
              from currentservice as cs
              left join reqcategory as r on r.line = cs.serviceline
              where counterline <> 0 and date(dateid) between '$start' and '$end' $filter
            
              union all
            
              select cs.ispwd,
                case when isdone = 1 then 1 else 0 end as isdone,
                case when iscancel = 1 then 1 else 0 end as iscancel,
                r.code as service,cs.dateid,cs.startdate,ifnull(timestampdiff(minute,cs.dateid,cs.startdate),0) as waittime
              from hcurrentservice as cs
              left join reqcategory as r on r.line = cs.serviceline
              where counterline <> 0 and date(dateid) between '$start' and '$end' $filter
            ) as x
            
            group by ispwd, service
            order by service, ispwd
                ";
        // var_dump($query);
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
    }

    public function summary_per_service_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
        $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));
        $servicedep = ($config['params']['dataparams']['servicedep']);

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $printDate = date('m/d/Y');

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->letterhead($center, $username, $config);
        // $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Queuing Summary Per Service', '500', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>From: ' . $start . '</b> to <b>' . $end . '</b>', null, null, false, '3px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $service = '';
        if ($servicedep != '') {
            $service = strToUpper($servicedep);
        } else {
            $service = 'ALL SERVICE';
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Service: ' . '<b>' . $service . '</b>', null, null, false, '3px solid', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function summary_per_service_layout($config)
    {
        // Run both queries independently
        $result1 = $this->default_qry($config);
        $result2 = $this->service_summary_qry($config);

        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "10";
        $this->reporter->linecounter = 0;

        $grandTotal = ['ctrcount' => 0, 'rserved' => 0, 'rcancel' => 0, 'pserved' => 0, 'pcancel' => 0];
        foreach ($result1 as $data) {
            $grandTotal['ctrcount'] += $data->ctrcount;
            $grandTotal['rserved']  += $data->rserved;
            $grandTotal['rcancel']  += $data->rcancel;
            $grandTotal['pserved']  += $data->pserved;
            $grandTotal['pcancel']  += $data->pcancel;
        }

        $totalServed    = $grandTotal['rserved'] + $grandTotal['pserved'];
        $totalCancelled = $grandTotal['rcancel'] + $grandTotal['pcancel'];
        $totalRegular   = $grandTotal['rserved'] + $grandTotal['rcancel'];
        $totalPriority  = $grandTotal['pserved'] + $grandTotal['pcancel'];

        // SECTION 1: TICKET SUMMARY
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->summary_per_service_header($config);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TICKET SUMMARY', null, null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Served:', '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($totalServed, '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Total Regular', '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($totalRegular, '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Cancelled:', '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($totalCancelled, '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Total Priority:', '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($totalPriority, '250', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '250', 30, false, '2px solid', 'BTL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '250', 30, false, '2px solid', 'BT', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REGULAR', '250', 30, false, '2px solid', 'BTL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PRIORITY', '250', 30, false, '2px solid', 'BTLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Service', '250', 30, false, '2px solid', 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('# of Customers', '250', 30, false, '2px solid', 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Served', '125', 30, false, '2px solid', 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cancel', '125', 30, false, '2px solid', 'TBR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Served', '125', 30, false, '2px solid', 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cancel', '125', 30, false, '2px solid', 'TBR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // Data rows grouped by date
        $currentDate  = null;
        $dateSubtotal = ['ctrcount' => 0, 'rserved' => 0, 'rcancel' => 0, 'pserved' => 0, 'pcancel' => 0];
        $grandTotal   = ['ctrcount' => 0, 'rserved' => 0, 'rcancel' => 0, 'pserved' => 0, 'pcancel' => 0];

        foreach ($result1 as $data) {

            // Print subtotal when date changes
            if ($currentDate !== null && $currentDate !== $data->date) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '250', null, false, '2px solid', '', 'L', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '750', null, false, '2px solid', 'B', 'L', $font, '12', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '250', 30, false, '', '', 'LT', $font, $fontsize, '', '');
                $str .= $this->reporter->col($dateSubtotal['ctrcount'] . '&nbsp&nbsp', '250', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
                $str .= $this->reporter->col($dateSubtotal['rserved'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
                $str .= $this->reporter->col($dateSubtotal['rcancel'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
                $str .= $this->reporter->col($dateSubtotal['pserved'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
                $str .= $this->reporter->col($dateSubtotal['pcancel'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '250', null, false, '2px solid', '', 'L', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '750', null, false, '2px solid', 'T', 'L', $font, '12', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $dateSubtotal = ['ctrcount' => 0, 'rserved' => 0, 'rcancel' => 0, 'pserved' => 0, 'pcancel' => 0];
            }

            // Print date header when date changes
            if ($currentDate !== $data->date) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(date('m/d/Y', strtotime($data->date)), '250', 30, false, '', '', 'LT', $font, $fontsize, 'B', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $currentDate = $data->date;
            }

            // Data row
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '', '', 'LT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->service, '200', null, false, '', '', 'LT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->ctrcount . '&nbsp&nbsp', '250', null, false, '', '', 'CT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->rserved . '&nbsp&nbsp', '125', null, false, '', '', 'CT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->rcancel . '&nbsp&nbsp', '125', null, false, '', '', 'CT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->pserved . '&nbsp&nbsp', '125', null, false, '', '', 'CT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->pcancel . '&nbsp&nbsp', '125', null, false, '', '', 'CT', $font, $fontsize, '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $dateSubtotal['ctrcount'] += $data->ctrcount;
            $dateSubtotal['rserved']  += $data->rserved;
            $dateSubtotal['rcancel']  += $data->rcancel;
            $dateSubtotal['pserved']  += $data->pserved;
            $dateSubtotal['pcancel']  += $data->pcancel;

            $grandTotal['ctrcount'] += $data->ctrcount;
            $grandTotal['rserved']  += $data->rserved;
            $grandTotal['rcancel']  += $data->rcancel;
            $grandTotal['pserved']  += $data->pserved;
            $grandTotal['pcancel']  += $data->pcancel;
        }

        // Last date subtotal
        if ($currentDate !== null) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '250', null, false, '2px solid', '', 'L', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '750', null, false, '2px solid', 'B', 'L', $font, '12', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '250', 30, false, '', '', 'LT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($dateSubtotal['ctrcount'] . '&nbsp&nbsp', '250', 30, false, '3px solid', '', 'CT', $font, $fontsize, 'B', '');
            $str .= $this->reporter->col($dateSubtotal['rserved'] . '&nbsp&nbsp', '125', 30, false, '3px solid', '', 'CT', $font, $fontsize, 'B', '');
            $str .= $this->reporter->col($dateSubtotal['rcancel'] . '&nbsp&nbsp', '125', 30, false, '3px solid', '', 'CT', $font, $fontsize, 'B', '');
            $str .= $this->reporter->col($dateSubtotal['pserved'] . '&nbsp&nbsp', '125', 30, false, '3px solid', '', 'CT', $font, $fontsize, 'B', '');
            $str .= $this->reporter->col($dateSubtotal['pcancel'] . '&nbsp&nbsp', '125', 30, false, '3px solid', '', 'CT', $font, $fontsize, 'B', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '250', null, false, '2px solid', 'T', 'L', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '750', null, false, '2px solid', 'T', 'L', $font, '12', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // Grand Total row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total', '250', 30, false, '', '', 'LT', $font, $fontsize, 'B', '');
        $str .= $this->reporter->col($grandTotal['ctrcount'] . '&nbsp&nbsp', '250', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
        $str .= $this->reporter->col($grandTotal['rserved'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
        $str .= $this->reporter->col($grandTotal['rcancel'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
        $str .= $this->reporter->col($grandTotal['pserved'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
        $str .= $this->reporter->col($grandTotal['pcancel'] . '&nbsp&nbsp', '125', 30, false, '2px solid', '', 'CT', $font, $fontsize, 'B', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '250', null, false, '2px solid', '', 'L', $font, '12', '', '', '');
        // $str .= $this->reporter->col('', '750', null, false, '2px solid', 'B', 'L', $font, '12', '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // SECTION 2: SUMMARY PER SERVICE
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Summary Per Service', null, 30, false, '2px solid', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Service', '250', 30, false, '2px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Served', '125', 30, false, '2px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cancel', '125', 30, false, '2px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Avg Wait (mins)', '250', 30, false, '2px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '125', 30, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '125', 30, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // Data rows grouped by service
        $currentService = null;

        foreach ($result2 as $data) {
            if ($currentService !== $data->service) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->service, '250', 30, false, '', '', 'LT', $font, $fontsize, 'B', '');
                $str .= $this->reporter->col('', '125', 30, false, '', '', 'RT', $font, $fontsize, '', '');
                $str .= $this->reporter->col('', '125', 30, false, '', '', 'RT', $font, $fontsize, '', '');
                $str .= $this->reporter->col('', '250', 30, false, '', '', 'RT', $font, $fontsize, '', '');
                $str .= $this->reporter->col('', '125', 30, false, '', '', 'C', $font, $fontsize, '', '');
                $str .= $this->reporter->col('', '125', 30, false, '', '', 'C', $font, $fontsize, '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $currentService = $data->service;
            }

            $wait = 0;
            if($data->waittime !=0){
                $wait = round($data->waittime/$data->served,0);
            }
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '', '', 'LT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->types, '200', null, false, '', '', 'LT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->served . '&nbsp&nbsp', '125', null, false, '', '', 'CT', $font, $fontsize, '', '');
            $str .= $this->reporter->col($data->cancel . '&nbsp&nbsp', '125', null, false, '2px solid', '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($wait, '250', null, false, '', '', 'LT', $font, $fontsize, '', '');
            $str .= $this->reporter->col('', '125', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '125', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class