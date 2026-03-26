<?php

namespace App\Http\Classes\modules\reportlist\motorcycle_reports;

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

class average_sales_per_mc_unit
{
    public $modulename = 'Average Sales Per MC Unit';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1340'];

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
        $fields = ['radioprint', 'start', 'end', 'brandname', 'brandid', 'dcentername', 'monthsno'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'effectfromdate.required', true);
        data_set($col1, 'effecttodate.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'dcentername.required', false);
        data_set($col1, 'monthsno.label', 'No. of Months');
        data_set($col1, 'monthsno.required', true);
        data_set($col1,  'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'red']]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable(
            "select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                  '' as brand,
                  '' as brandid,  
                  '' as brandname,
                        '' as monthsno,
                   '" . $defaultcenter[0]['center'] . "' as center,
                   '" . $defaultcenter[0]['centername'] . "' as centername,
                   '" . $defaultcenter[0]['dcentername'] . "' as dcentername"
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
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
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $brandid    = $config['params']['dataparams']['brandid'];
        $brandname  = $config['params']['dataparams']['brandname'];
        $brand  = $config['params']['dataparams']['brand'];


        $filter = '';
        $fcenter    = $config['params']['dataparams']['center'];
        if ($fcenter != "") {
            $filter .= " and cntnum.center = '$fcenter'";
        }

        if ($brandname != "") {
            $filter = $filter . " and i.brand='$brand'";
        }

        $query = "      select i.barcode,i.itemname,count(ss.color) as totalsales,ss.color
                        from lahead as head
                        left join lastock as stock on stock.trno=head.trno
                        left join item as i on i.itemid=stock.itemid
                        left join itemcategory as cat on cat.line = i.category
                        left join cntnum on cntnum.trno=head.trno
                        left join serialout as ss on ss.trno=stock.trno and ss.line=stock.line
                        where head.dateid between '$start' and '$end' and head.doc in ('sj','mj') and cat.name ='MC UNIT' $filter
                        group by i.barcode,i.itemname,ss.color
                        union all
                        select i.barcode,i.itemname,count(ss.color) as totalsales,ss.color
                        from glhead as head
                        left join glstock as stock on stock.trno=head.trno
                        left join item as i on i.itemid=stock.itemid
                        left join itemcategory as cat on cat.line = i.category
                        left join cntnum on cntnum.trno=head.trno
                        left join serialout as ss on ss.trno=stock.trno and ss.line=stock.line
                        where head.dateid between '$start' and '$end' and head.doc in ('sj','mj') and cat.name ='MC UNIT' $filter
                        group by i.barcode,i.itemname,ss.color";
        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $monthsno         = $config['params']['dataparams']['monthsno'];
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1000';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $av = $data->totalsales / $monthsno;
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->color, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(round($data->totalsales, 0), '250', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($av, $decimal), '250', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
            }
        }
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
        $fcenter    = $config['params']['dataparams']['center'];
        $fcentername    = $config['params']['dataparams']['centername'];
        $brandname     = $config['params']['dataparams']['brandname'];
        $monthsno         = $config['params']['dataparams']['monthsno'];
        $str = '';
        $layoutsize = '1000';

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
        $str .= $this->reporter->col('Average Sales Per MC Unit', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        if ($brandname == '') {
            $str .= $this->reporter->col('Brand: ALL', '234', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Brand: ' . $brandname, '234', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        if ($fcenter == '') {
            $str .= $this->reporter->col('Branch: ALL ', '233', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Branch: ' . $fcentername, '233', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        if ($monthsno == '') {
            $str .= $this->reporter->col('No. of Months: 0', '233', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('No. of Months: ' . $monthsno, '233', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
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
        $layoutsize = '1000';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MC UNIT', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COLOR', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL SALES', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AVERAGE SALES', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class