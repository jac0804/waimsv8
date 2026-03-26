<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class operator_history
{
    public $modulename = 'Operator Incentive';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3000px;';
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
       $fields = ['radioprint', 'start', 'end', 'empcode'];

        $col1 = $this->fieldClass->create($fields);
        data_set(
            $col1,
            'radioprint.options',
            [
                ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ]
        );
        data_set($col1, 'empcode.label', 'Employee');
        data_set($col1, 'empcode.required', false);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        $fields = ['radioposttype', 'radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set(
            $col2,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal']
            ]
        );
        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }
    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS 
        $paramstr = "select 'default' as print, 
        adddate(left(now(),10),-360) as start,
        left(now(),10) as `end`,
        '' as client,
        '' as clientname,
        '0' as reporttype,
        '0' as posttype,
        '' as dclientname,
        '0' as empid,
        '' as empcode";
        return $this->coreFunctions->opentable($paramstr);
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
            case '0': // SUMMARIZED
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
            case '1': // DETAILED
                $result = $this->reportDefaultLayout_DETAILED($config);
        }

        return $result;
    }
    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_MAIN_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }
    public function default_MAIN_QUERY($config)
    {
        $posttype = $config['params']['dataparams']['posttype'];
        $empcode = isset($config['params']['dataparams']['empcode']) ? $config['params']['dataparams']['empcode'] : '';
        $reporttype = $config['params']['dataparams']['reporttype'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $empid = $config['params']['dataparams']['empid'];
        $filter = "";
        if (!empty($empcode)) {
            $filter .= " and cl.clientid = '$empid' ";
        }
        switch ($reporttype) {
            case '1':
                switch ($posttype) {
                    case '1':
                        $query = "select head.docno,date(head.dateid) as dateid,cl.clientname as empname,cl.client as empcode,head.opincentive,
                                item.itemname as truckasset,item.barcode as code, cat.category as activity,time_format(time(stock.starttime), '%H:%i') as starttime,
                                time_format(time(stock.endtime), '%H:%i') as endtime,stock.duration,stock.odostart,
                                stock.odoend,stock.distance,stock.fuelconsumption
                                from eqhead as head
                                left join eqstock as stock on stock.trno = head.trno
                                left join item on item.itemid = head.itemid
                                left join employee as emp on emp.empid = head.empid
                                left join client as cl on cl.clientid = emp.empid
                                left join reqcategory as cat on cat.line = stock.activityid
                                where date(head.dateid) between '$start' and '$end'  $filter  order by head.dateid,cl.clientname ";

                        return $query;
                        break;
                    default:
                        $query = "select head.docno,date(head.dateid) as dateid,cl.clientname as empname,cl.client as empcode,head.opincentive,
                                item.itemname as truckasset,item.barcode as code, cat.category as activity,time_format(time(stock.starttime), '%H:%i') as starttime,
                                time_format(time(stock.endtime), '%H:%i') as endtime,stock.duration,stock.odostart,
                                stock.odoend,stock.distance,stock.fuelconsumption
                                from heqhead as head
                                left join heqstock as stock on stock.trno = head.trno
                                left join item on item.itemid = head.itemid
                                left join employee as emp on emp.empid = head.empid
                                left join client as cl on cl.clientid = emp.empid
                                left join reqcategory as cat on cat.line = stock.activityid
                                where  date(head.dateid) between '$start' and '$end'  $filter   order by head.dateid,cl.clientname ";

                        return $query;
                        break;
                }
                break;

            default:
                switch ($posttype) {
                    case '1':
                        $query = "select cl.clientname as empname,cl.client as empcode,sum(head.opincentive) as opincentive
                                    from eqhead as head
                                    left join employee as emp on emp.empid = head.empid
                                    left join client as cl on cl.clientid = emp.empid
                                    where date(head.dateid) between '$start' and '$end'  $filter group by cl.client,cl.clientname order by head.dateid ";
                        return $query;
                        break;
                    default:
                        $query = "select cl.clientname as empname,cl.client as empcode,sum(head.opincentive) as opincentive
                                    from heqhead as head
                                    left join employee as emp on emp.empid = head.empid
                                    left join client as cl on cl.clientid = emp.empid
                                    where  date(head.dateid) between '$start' and '$end'  $filter group by cl.client,cl.clientname order by head.dateid ";

                        return $query;
                        break;
                }
                break;
        }
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
            $layoutsize = '1917';
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Date', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Docno', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Code', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', ''); //emp code
            $str .= $this->reporter->col('Emp Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Code', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', ''); // code
            $str .= $this->reporter->col('Truck/Asset', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Activity', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Start', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('End', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Duration', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('ODO start', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('ODO end', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Distance', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Fuel', '117', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Incentive', '130', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        } else { //summarized
            $layoutsize = '600';
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Code', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Emp Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Incentive', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype     = $config['params']['dataparams']['posttype'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $str = '';
        if ($reporttype == 0) { //summarized
            $layoutsize = '600';
            $size = '600';
            $title = 'Operator History Summarized';
        } else { //detailed
            $layoutsize = '1917';
            $size = '1917';
            $title = 'Operator History Detailed';
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

        $str .= $this->reporter->col('Transaction Type: ' . $posttype, $size, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, $size, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }
    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $posttype = $config['params']['dataparams']['posttype'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $layoutsize = '1917';
        $totalduration = 0;
        $totaldistance = 0;
        $totafuel = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);
        $docno = "";
        if (!empty($result)) {
            foreach ($result as $key => $data) {

                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empcode, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->code, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->truckasset, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->activity, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->starttime, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->endtime, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->duration, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->odostart, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->odoend, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->distance, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->fuelconsumption, '117', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                if ($docno == "") {
                    $str .= $this->reporter->col($data->opincentive, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $docno = $data->docno;
                } else {
                    if ($docno == $data->docno) {
                        $docno = $data->docno;
                        $str .= $this->reporter->col('0.00', '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    } else {
                        $docno = $data->docno;
                        $str .= $this->reporter->col($data->opincentive, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    }
                }
                $str .= $this->reporter->endtable();
                $totalduration += $data->duration;
                $totaldistance += $data->distance;
                $totafuel += $data->fuelconsumption;
            }
        }

        if ($posttype == '1') {
            $qry  = "select sum(opincentive) as opincentive from eqhead where date(eqhead.dateid) between '$start' and '$end'";
        } else {
            $qry  = "select sum(opincentive) as opincentive from heqhead  where date(heqhead.dateid) between '$start' and '$end'";
        }
        $opincentive =  $this->coreFunctions->opentable($qry);


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('', '1202', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');
        //total duration

        $str .= $this->reporter->col(number_format($totalduration, $decimal), '117', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');



        $str .= $this->reporter->col('', '234', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');


        //total distance
        $str .= $this->reporter->col(number_format($totaldistance, $decimal), '117', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->col(number_format($totafuel, 2), '117', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');
        //total incentive
        foreach ($opincentive as $key => $value) {

            $str .= $this->reporter->col($value->opincentive, '130', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');
        }


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();

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

        $layoutsize = '600';
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
                $str .= $this->reporter->col($data->opincentive, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endtable();
                $totalamt += $data->opincentive;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '400', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, $decimal), '200', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}
