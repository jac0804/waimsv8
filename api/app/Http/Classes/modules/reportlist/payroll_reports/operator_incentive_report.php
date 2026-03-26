<?php

namespace App\Http\Classes\modules\reportlist\payroll_reports;

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

class operator_incentive_report
{
    public $modulename = 'Operator Incentive Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1500'];

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
        $fields = ['radioprint', 'batchrep', 'start', 'end', 'radioreporttype', 'radioarrangeby2'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'batchrep.lookupclass', 'lookupbatchrep');


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'default', 'color' => 'red'],
        ]);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];

        return $this->coreFunctions->opentable("select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                0 as arrangeby2,
                '' as batchrep,
                '' as batch,
                '' as line,
                '0' as reporttype");
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
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case 0: // summarized
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;

            case 1: // detailed
                $result = $this->reportDefaultLayout($config);
                break;
        }
        return $result;
    }

    public function reportDefault($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case 0: // summarized
                $query = $this->summarized_QUERY($config);
                break;

            case 1: // detailed
                $query = $this->default_QUERY($config);
                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $batchid      = $config['params']['dataparams']['line'];
        $order = '';
        $filter = "";

        if ($batchid != 0) {
            $filter .= " and hhead.batchid = " . $batchid . " ";
        }

        if ($config['params']['dataparams']['arrangeby2'] == 0) {
            $order = 'date';
        } else {
            $order = 'empname';
        }

        $query = " select date(hhead.dateid) as date, hhead.docno,
                        emp.client as empcode, emp.clientname as empname,
                        i.barcode as code, i.itemname as truckasset, cat.category as activity,
                      
                        time_format(heq.starttime, '%H:%i ') as start,
                        time_format(heq.endtime, '%H:%i ') as end,
                        
                        heq.duration,heq.odostart,heq.odoend,heq.distance,
                        hhead.opincentive as incentive

                        from oihead as head
                        left join heqhead as  hhead on hhead.oitrno=head.trno
                        left join heqstock as heq on heq.trno=hhead.trno
                        left join employee as c on c.empid=hhead.empid
                        left join client as emp on emp.clientid=c.empid
                        left join jobthead as jh on jh.line=c.jobid
                        left join batch on batch.line=hhead.batchid
                        left join item as i on i.itemid=hhead.itemid
                        left join reqcategory as cat on cat.line = heq.activityid
                        where date(hhead.dateid) between '$start' and '$end'  and heq.line =1 $filter 
                        union all

                        select date(hhead.dateid) as date, hhead.docno,
                        emp.client as empcode, emp.clientname as empname,
                        i.barcode as code, i.itemname as truckasset, cat.category as activity,

                        time_format(heq.starttime, '%H:%i ') as start,
                        time_format(heq.endtime, '%H:%i') as end,
                    
                        heq.duration,heq.odostart,heq.odoend,heq.distance,
                        hhead.opincentive as incentive
                        from hoihead as head
                        left join heqhead as  hhead on hhead.oitrno=head.trno
                        left join heqstock as heq on heq.trno=hhead.trno
                        left join employee as c on c.empid=hhead.empid
                        left join client as emp on emp.clientid=c.empid
                        left join jobthead as jh on jh.line=c.jobid
                        left join item as i on i.itemid=hhead.itemid
                        left join reqcategory as cat on cat.line = heq.activityid
                        where  date(hhead.dateid) between '$start' and '$end'  and heq.line =1 $filter
                        order by   $order  ";
        return $query;
    }


    public function summarized_QUERY($config)
    {
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $batchid      = $config['params']['dataparams']['line'];

        $filter = "";
        $order = "";

        if ($batchid != 0) {
            $filter .= " and hhead.batchid = " . $batchid . " ";
        }

        if ($config['params']['dataparams']['arrangeby2'] == 0 || $config['params']['dataparams']['arrangeby2'] == 1) {
            $order = 'empname';
        }
        $query = " select  emp.client as empcode, emp.clientname as empname,
                      ifnull(jh.jobtitle,'') as jobtitle,sum(hhead.opincentive) as amt
                      from oihead as head
                      left join heqhead as  hhead on hhead.oitrno=head.trno
                      left join employee as c on c.empid=hhead.empid
                      left join client as emp on emp.clientid=c.empid
                      left join jobthead as jh on jh.line=c.jobid
                      where date(hhead.dateid) between '$start' and '$end'  $filter
                      group by emp.client, emp.clientname,jobtitle
                      union all
                      select  emp.client as empcode, emp.clientname as empname,
                      ifnull(jh.jobtitle,'') as jobtitle,sum(hhead.opincentive) as amt
                  
                      from hoihead as head
                      left join heqhead as  hhead on hhead.oitrno=head.trno
                      left join employee as c on c.empid=hhead.empid
                      left join client as emp on emp.clientid=c.empid
                      left join jobthead as jh on jh.line=c.jobid
                      where date(hhead.dateid) between '$start' and '$end'  $filter
                      group by emp.client, emp.clientname,jobtitle
                      order by   $order";
        return $query;
    }

    public function reportDefaultLayout($config)
    {

        $result = $this->reportDefault($config);

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $layoutsize = '1800';
        $totalduration = 0;
        $totaldistance = 0;
        $totalincentive = 0;


        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->date, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empcode, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->code, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->truckasset, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->activity, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->start, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->end, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->duration, '117', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->odostart, '117', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->odoend, '117', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->distance, '117', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->incentive, '130', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();

                $totalduration +=  $data->duration;
                $totaldistance +=  $data->distance;
                $totalincentive += $data->incentive;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '1202', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');
        //total duration

        $str .= $this->reporter->col(number_format($totalduration, $decimal), '117', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('', '234', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');

        //total distance
        $str .= $this->reporter->col(number_format($totaldistance, $decimal), '117', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');


        //total incentive
        $str .= $this->reporter->col(number_format($totalincentive, $decimal), '130', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $batchname     = $config['params']['dataparams']['batch'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str = '';
        if ($reporttype == 0) { //summarized
            $layoutsize = '800';
            $size = '800';
            $title = 'Operator Incentive Summarized';
        } else { //detailed
            $layoutsize = '1800';
            $size = '1800';
            $title = 'Operator Incentive Detailed';
        }

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($title, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        if ($batchname == '') {
            $str .= $this->reporter->col('Batch: ALL', $size, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Batch: ' . $batchname, $size, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Trnx Date : ' . $start . ' to ' . $end, $size, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $reporttype = $config['params']['dataparams']['reporttype'];


        $str .= $this->reporter->begintable($layoutsize);
        if ($reporttype == 1) { //detailed
            $layoutsize = '1800';
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Date', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Docno', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Code', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', ''); //emp code
            $str .= $this->reporter->col('Emp Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Code', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', ''); // code
            $str .= $this->reporter->col('Truck/Asset', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Activity', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Start', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('End', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Duration', '117', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('ODO start', '117', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('ODO end', '117', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Distance', '117', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Incentive', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        } else { //summarized
            $layoutsize = '800';
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Code', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Emp Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Job Title ', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Amount', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $layoutsize = '800';
        $totalamt = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col($data->empcode, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->jobtitle, '200', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amt, $decimal), '200', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
                $totalamt = $totalamt + $data->amt;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '600', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, $decimal), '200', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class