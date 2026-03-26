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

class category_sales
{
    public $modulename = 'Category Sales';
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
            "select 'default' as print,
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
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $wh   = $config['params']['dataparams']['wh'];
        $posttype = $config['params']['dataparams']['posttype'];

        $filter = '';
        if ($wh != "") {
            $whid = $config['params']['dataparams']['whid'];
            $filter = $filter . " and stock.whid=" . $whid;
        }
        
        switch ($posttype) {
            case 'posted': // posted
                $query = "select category,sum(refund) as refund, sum(total) as total
                from (
                    select head.docno,cat.name as category,case head.doc when 'CM' then stock.ext else 0 end as refund ,
                    case head.doc when 'CI' then stock.ext else 0 end as total  
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','cm') and i.category <> 0 $filter
                ) as a
                group by category";
            break;
            case 'unposted':
                $query = "select category,sum(refund) as refund, sum(total) as total
                from (
                    select head.docno,cat.name as category, case head.doc when 'CM' then stock.ext else 0 end as refund ,
                    case head.doc when 'CI' then stock.ext else 0 end as total 
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','cm') and i.category <> 0 $filter
                ) as a
                group by category";
            break;
            case 'all':
                $query = "select category,sum(refund) as refund, sum(total) as total
                from (
                    select head.docno,cat.name as category, case head.doc when 'CM' then stock.ext else 0 end as refund ,
                    case head.doc when 'CI' then stock.ext else 0 end as total 
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','cm') and i.category <> 0 $filter
                    union all
                    select head.docno,cat.name as category,case head.doc when 'CM' then stock.ext else 0 end as refund ,
                    case head.doc when 'CI' then stock.ext else 0 end as total  
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    where head.dateid between '$start' and '$end' and  head.doc in ('ci','cm') and i.category <> 0 $filter
                ) as a
                group by category";
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
        // $count = 45;
        // $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '800';
        $totalsrp = 0;
        $totalref =0;

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
                $str .= $this->reporter->col($data->category, '400', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->refund, 2), '200', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->total, 2), '200', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
                $totalsrp = $totalsrp + $data->total;
                $totalref = $totalref + $data->refund;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Amount', '400', null, false, $border, 'TB', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalref, $decimal), '200', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalsrp, $decimal), '200', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
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
        $str .= $this->reporter->col('Category Sales', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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
        $str .= $this->reporter->col('CATEGORY', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REFUND/CHANGE/RETURN', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class