<?php

namespace App\Http\Classes\modules\reportlist\pos_reports;

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

class hourly_sales_summary_report
{
    public $modulename = 'POS Report';
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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        $fields = ['start', 'end'];
        // for date filter
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'start.required', true);
        data_set($col2, 'end.required', true);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
      '0' as reporttype
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

        return $this->hourly_sales_summary($config);
    }

    public function hourly_sales_summary_qry($config)
    {
        $center = $config['params']['dataparams']['center'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "select
            h.hrs,
            ifnull(s.transcount,0) as transcount,
            ifnull(s.qtysold,0) as qtysold,
            ifnull(s.avgcount,0) as avgcount,
            ifnull(s.tdine,0) as tdine,
            ifnull(s.ttake,0) as ttake,
            ifnull(s.tdel,0) as tdel,
            ifnull(s.total,0) as total

            from (
                select 6 as hrs union all
                select 7 union all
                select 8 union all
                select 9 union all
                select 10 union all
                select 11 union all
                select 12 union all
                select 13 union all
                select 14 union all
                select 15 union all
                select 16 union all
                select 17 union all
                select 18 union all
                select 19 union all
                select 20 union all
                select 21 union all
                select 22 union all
                select 23 union all
                select 0 union all
                select 1 union all
                select 2 union all
                select 3 union all
                select 4 union all
                select 5
                ) h
                left join (select s.hrs, sum(transcount) as transcount, sum(qtysold) as qtysold,
                case when sum(transcount) = 0 then 0 else sum(amt - vatamt) / sum(transcount)end as avgcount,
                sum(tdine) as tdine, sum(ttake) as ttake, sum(tdel) as tdel,
                sum(tdine + ttake + tdel) as total
                from (
                    select 
                        HOUR(cnum.postdate) as hrs,
                        1 AS transcount,
                        ifnull(sum(stock.iss - stock.qty),0) as qtysold,
                        ifnull(sum(stock.ext-si.sramt-si.pwdamt-si.lessvat),0) as amt,
                        ifnull(sum(si.vatamt),0) as vatamt,
                        case when h.ordertype not in (1, 2) then sum(stock.ext-si.sramt-si.pwdamt-si.lessvat) else 0 end as tdine,
                        case when h.ordertype = 1 then sum(stock.ext-si.sramt-si.pwdamt-si.lessvat) else 0 end as ttake,
                        case when h.ordertype = 2 then sum(stock.ext-si.sramt-si.pwdamt-si.lessvat) else 0 end as tdel,
                        ifnull(sum(stock.ext-si.sramt-si.pwdamt-si.lessvat),0) AS total
                
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join head as h on h.webtrno = head.trno and h.docno = stock.ref
                    left join cntnum as cnum on cnum.trno = head.trno
                    left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
                
                    where cnum.doc in ('SJ', 'CM')
                      and date(head.dateid) between '$start' and '$end'
                      and cnum.center = '$center'
                
                    group by stock.line, head.dateid, cnum.postdate, h.ordertype
                ) s
                group by s.hrs) s on s.hrs = h.hrs
                order by FIELD(h.hrs, 6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,0,1,2,3,4,5);";
        // var_dump($query);
        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $data;
    }

    public function hourly_sales_summary_header($config)
    {
        $center     = $config['params']['center'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        // $data = $this->summarized_salesreport_query($config);

        $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
        $qry2 = "select bn.comptel from branchstation as bn left join client on client.clientid = bn.clientid
         where bn.comptel <> 0 and client.center = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $headerdata2 = $this->coreFunctions->opentable($qry2);

        $gnrtdate = date('m-d-Y H:i:s A');
        // $system = '';
        $srno = '';
        $machineid = '';
        $postrmnl = '';
        // $username = '';

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '3px solid', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '3px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->tel, null, null, false, '3px solid', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('HOURLY SALES REPORT', null, null, false, $border, '', 'C', 'Times New Roman', $fontsize, 'B', 'blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<b>From : ' . $start . ' To ' . $end . '</b>', '500', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time', '180', '40', false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Quantity Sold', '110', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Transaction Count', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Average Count', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Dine In', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Take Out', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Deliveries', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Total Amount', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Running Total', '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function hourly_sales_summary($config)
    {
        $str = '';
        $layoutsize = '1000';
        // $font = $this->companysetup->getrptfont($config['params']);
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->hourly_sales_summary_header($config);
        $data = $this->hourly_sales_summary_qry($config);

        // if (empty($data)) {
        //     return $this->othersClass->emptydata($config);
        // }

        $runningTotal = 0;

        $ttlQtySold = 0;
        $ttlTransCount = 0;
        $ttlAvgCount = 0;
        $ttlTdine = 0;
        $ttlTtake = 0;
        $ttlTdel = 0;
        $grandTotal = 0;

        for ($i = 0; $i < count($data); $i++) {

            $hour = (int) $data[$i]['hrs'];

            $hour12 = $hour % 12;
            if ($hour12 == 0) {
                $hour12 = 12;
            }

            $ampm = ($hour < 12) ? 'AM' : 'PM';
            $displayTime = str_pad($hour12, 2, '0', STR_PAD_LEFT) . ':00' . $ampm . ' - ' . str_pad($hour12, 2, '0', STR_PAD_LEFT) . ':59' . $ampm;

            $currentTotal = (float) $data[$i]['total'];

            $runningTotal += $currentTotal;

            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($displayTime, '180', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['qtysold'] != 0 ? number_format($data[$i]['qtysold'], 2) : '-', '110', null, false, $border, 'BR', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['transcount'] != 0 ? number_format($data[$i]['transcount'], 2) : '-', '100', null, false, $border, 'BR', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['avgcount'] != 0 ? number_format($data[$i]['avgcount'], 2) : '-', '100', null, false, $border, 'BR', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['tdine'] != 0 ? number_format($data[$i]['tdine'], 2) : '-', '100', null, false, $border, 'BR', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['ttake'] != 0 ? number_format($data[$i]['ttake'], 2) : '-', '100', null, false, $border, 'BR', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['tdel'] != 0 ? number_format($data[$i]['tdel'], 2) : '-', '100', null, false, $border, 'BR', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['total'] != 0 ? number_format($data[$i]['total'], 2) : '-', '100', null, false, $border, 'BR', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($runningTotal != 0 ? number_format($runningTotal, 2) : '-', '100', null, false, $border, 'BR', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $ttlQtySold += $data[$i]['qtysold'];
            $ttlTransCount += $data[$i]['transcount'];
            $ttlAvgCount += $data[$i]['avgcount'];
            $ttlTdine += $data[$i]['tdine'];
            $ttlTtake += $data[$i]['ttake'];
            $ttlTdel += $data[$i]['tdel'];
            $grandTotal += $data[$i]['total'];
        }

        // grand totals
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total :', '180', null, false, '3px solid', '', 'R', $font, '11', 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlQtySold != 0 ? number_format($ttlQtySold, 2) : '-', '100', null, false, '3px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlTransCount != 0 ? number_format($ttlTransCount, 2) : '-', '90', null, false, '3px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlAvgCount != 0 ? number_format($ttlAvgCount, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlTdine != 0 ? number_format($ttlTdine, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlTtake != 0 ? number_format($ttlTtake, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($ttlTdel != 0 ? number_format($ttlTdel, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($grandTotal != 0 ? number_format($grandTotal, 2) : '-', '90', null, false, '3px solid', 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '100', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class