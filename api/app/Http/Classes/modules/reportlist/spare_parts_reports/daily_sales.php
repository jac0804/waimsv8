<?php

namespace App\Http\Classes\modules\reportlist\spare_parts_reports;

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

class daily_sales
{
    public $modulename = 'Daily Sales';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1000px;max-width:1000px;';
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
        $fields = ['radioprint', 'start', 'end', 'dwhname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1,  'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'red']]);

        $fields = ['radioposttype'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => 'posted', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => 'unposted', 'color' => 'teal'],
            ['label' => 'All', 'value' => 'all', 'color' => 'teal']
        ]);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return ['col1' => $col1,'col2' => $col2,'col3' => $col3];
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable(
            "select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                0 as whid,
                '' as wh,
                '' as whname,
                '' as dwhname,
                'all' as posttype"
        );
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $wh     = $config['params']['dataparams']['wh'];
        $posttype  = $config['params']['dataparams']['posttype'];

        $filter = '';
        if ($wh != "") {
            $whid = $config['params']['dataparams']['whid'];
            $filter = $filter . " and stock.whid=" . $whid;
        }

        switch ($posttype) {
            case 'posted': // posted
                $query = " select dateid,sum(total) as total
                from (
                    
                    select head.dateid,head.docno,cat.name as category,  case head.doc when 'cm' then stock.ext*-1 else stock.ext end as total from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','CM')  $filter 
                ) as a
                group by dateid";
            break;
            case 'unposted': // unposted
                $query = " select dateid,sum(total) as total
                from (
                    select head.dateid,head.docno,cat.name as category,   case head.doc when 'cm' then stock.ext*-1 else stock.ext end as total from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','CM')  $filter
                     
                ) as a
                group by dateid";
            break;
            case 'all': // all
                $query = " select dateid,sum(total) as total
                from (
                    select head.dateid,head.docno,cat.name as category,   case head.doc when 'cm' then stock.ext*-1 else stock.ext end as total from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','CM')  $filter
                    union all
                    select head.dateid,head.docno,cat.name as category,  case head.doc when 'cm' then stock.ext*-1 else stock.ext end as total from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','CM')  $filter 
                ) as a
                group by dateid";
            break;
        }

        
        return $query;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $this->reporter->linecounter = 0;
        $str = '';
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $wh         = $config['params']['dataparams']['wh'];

        $filter = '';
        if ($wh != "") {
            $filter = $filter . " and wh.client='$wh'";
        }

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '800';
        $totalsrp = 0;

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);
        $difference = $this->diff($start, $end);

        $dateTotals = [];
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $dateTotals[$data->dateid] = $data->total;
            }
        }

        $dates = [];
        $cnt = 0;
        for ($i = 0; $i <= $difference; $i++) {
            $day = $this->getDay($data->dateid);
            $cnt = $cnt + 1;
            $interval = $this->coreFunctions->datareader("select date_add('$start', interval $i day) as value");
            $dates[$cnt] = date("Y-m-d", strtotime($interval));
            $date1 = implode(" ", $dates);
            $date2 = explode(" ", $date1);
            $day = $this->getDay($date2[$i]);
            $str .= $this->reporter->col(date('Y F d,', strtotime($date2[$i])) . ' ' . $day, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            if (isset($dateTotals[$date2[$i]])) {
                $total = $dateTotals[$date2[$i]];
            } else {
                $total = 0;
            }

            $str .= $this->reporter->col(number_format($total, 2), '400', null, false, $border, '', 'R', $font, $fontsize, '', '', ''); // Display total amount
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endrow();
            $totalsrp = $totalsrp + $total;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total : ', '400', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalsrp, $decimal), '400', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
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
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $whname     = $config['params']['dataparams']['whname'];
        $posttype  = $config['params']['dataparams']['posttype'];
        
        switch ($posttype) {
            case 'posted':
                $posttype = "POSTED";
                break;
            case 'unposted':
                $posttype = "UNPOSTED";
                break;
            default:
                $posttype = "ALL";
                break;
        }

        $str = '';
        $layoutsize = '800';
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
        $str .= $this->reporter->col('Daily Sales', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('WH: ' . ($whname == '' ? 'ALL' : $whname), '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '800';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY SALES', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '400', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function getDay($date)
    {
        $dowMap = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $dow_numeric = date('w', strtotime($date));
        return $dowMap[$dow_numeric];
    }

    public function diff($start, $end)
    {
        $date1 = new DateTime($start);
        $date2 = new DateTime($end);
        $diff = $date1->diff($date2);
        return $diff->days;
    }
}//end class