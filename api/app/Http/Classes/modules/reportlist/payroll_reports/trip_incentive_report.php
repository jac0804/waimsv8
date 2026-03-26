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

class trip_incentive_report
{
    public $modulename = 'Trip Incentive Report';
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
            $filter .= " and htd.batchid = " . $batchid . " ";
        }

        if ($config['params']['dataparams']['arrangeby2'] == 0) {
            $order = 'date';
        } else {
            $order = 'empname';
        }

        $query = "
                   
                        select date(gl.dateid) as date, gl.docno,item.barcode as code,
                        emp.client as empcode, emp.clientname as empname,
                        wh.clientname as whnamefr,gl.clientname as clto, htd.activity,htd.rate,
                        item.itemname as description,date(gl.sdate1) as date1,date(gl.sdate2) as date2,batch.batch,
                        ifnull(jh.jobtitle,'') as jobtitle
                
                        from tihead as head
                        left join htripdetail as htd on htd.titrno=head.trno
                        left join glhead as  gl on gl.trno=htd.trno
                        left join client as emp on emp.clientid=htd.clientid
                        left join employee as c on c.empid=emp.clientid
                        left join jobthead as jh on jh.line=c.jobid
                        left join client as wh on wh.clientid=gl.whid
                        left join item on item.itemid=htd.itemid
                        left join batch on batch.line=htd.batchid
                        where date(head.start) >= '$start' and date(head.enddate)<='$end'  $filter

                        union all

                        select date(gl.dateid) as date, gl.docno,item.barcode as code,
                        emp.client as empcode, emp.clientname as empname,
                        wh.clientname as whnamefr,gl.clientname as clto, htd.activity,htd.rate,
                        item.itemname as description,date(gl.sdate1) as date1,date(gl.sdate2) as date2,batch.batch,
                        ifnull(jh.jobtitle,'') as jobtitle
                
                        from htihead as head
                        left join htripdetail as htd on htd.titrno=head.trno
                        left join glhead as  gl on gl.trno=htd.trno
                        left join client as emp on emp.clientid=htd.clientid
                        left join employee as c on c.empid=emp.clientid
                        left join jobthead as jh on jh.line=c.jobid
                        left join client as wh on wh.clientid=gl.whid
                        left join item on item.itemid=htd.itemid
                        left join batch on batch.line=htd.batchid
                        where date(head.start) >= '$start' and date(head.enddate)<='$end'  $filter
                        order by  $order
                        ";

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
            $filter .= " and htd.batchid = " . $batchid . " ";
        }

        if ($config['params']['dataparams']['arrangeby2'] == 0 || $config['params']['dataparams']['arrangeby2'] == 1) {
            $order = 'empname';
        }
        $query = "
                        select  emp.client as empcode, emp.clientname as empname,
                        ifnull(jh.jobtitle,'') as jobtitle,
                        sum(htd.rate) as rate
                        from tihead as head
                        left join htripdetail as htd on htd.titrno=head.trno
                        left join glhead as  gl on gl.trno=htd.trno
                        left join client as emp on emp.clientid=htd.clientid
                        left join employee as c on c.empid=emp.clientid
                        left join jobthead as jh on jh.line=c.jobid
                        where date(head.start) >= '$start' and date(head.enddate)<='$end'  $filter
                        group by emp.client, emp.clientname,jobtitle

                        union all
                        select  emp.client as empcode, emp.clientname as empname,
                        ifnull(jh.jobtitle,'') as jobtitle,
                        sum(htd.rate) as rate
                        from htihead as head
                        left join htripdetail as htd on htd.titrno=head.trno
                        left join glhead as  gl on gl.trno=htd.trno
                        left join client as emp on emp.clientid=htd.clientid
                        left join employee as c on c.empid=emp.clientid
                        left join jobthead as jh on jh.line=c.jobid
                        where date(head.start) >= '$start' and date(head.enddate)<='$end'  $filter
                        group by emp.client, emp.clientname,jobtitle
                        order by  $order ";
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

        $layoutsize = '1500';
        $totalrate = 0;

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
                $str .= $this->reporter->col($data->date, '116', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empcode, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->whnamefr, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clto, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->code, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->description, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->activity, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->rate, $decimal), '116', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
                $totalrate = $totalrate + $data->rate;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Rate: ', '1384', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalrate, $decimal), '116', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

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
            $title = 'Trip Incentive Summarized';
        } else { //detailed
            $layoutsize = '1500';
            $size = '1500';
            $title = 'Trip Incentive Detailed';
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
        $str .= $this->reporter->col('Trip Date : ' . $start . ' to ' . $end, $size, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
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
            $layoutsize = '1500';
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DATE', '116', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Docno', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Code', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Emp Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('From', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('To', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Truck Code', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Desc', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Activity', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Rate', '116', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        } else { //summarized
            $layoutsize = '800';
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Code', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Emp Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Job Title ', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Amount', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
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
        $totalrate = 0;

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
                $str .= $this->reporter->col($data->jobtitle, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->rate, $decimal), '200', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
                $totalrate = $totalrate + $data->rate;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Rate: ', '600', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalrate, $decimal), '200', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class