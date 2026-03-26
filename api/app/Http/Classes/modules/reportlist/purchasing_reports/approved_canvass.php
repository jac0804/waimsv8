<?php

namespace App\Http\Classes\modules\reportlist\purchasing_reports;

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

class approved_canvass
{
    public $modulename = 'Approved Canvass';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1300'];

    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField()
    {
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

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
                '' as userid,
              '' as statid
                ");
    }
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
    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $company   = $config['params']['companyid'];

        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

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
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(date("Y-m-d", strtotime($data->dateid)), '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->supplier, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '155', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '145', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->qty, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->uom, '50', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->cost, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->total, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approveddate, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');


                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function reportDefault($config)
    {
        $query = $this->default_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }
    public function default_QUERY($config)
    {

        $start      = $config['params']['dataparams']['start'];
        $end        = $config['params']['dataparams']['end'];
        $query = "select head.trno,head.docno,head.client as supplier,head.dateid,item.itemname,ifnull(info.itemdesc,'') as itemdesc,stock.qty,stock.uom,stock.cost,stock.disc,(stock.cost * stock.qty) as total,stock.approveddate,stock.status
from hcdhead as head
left join hcdstock as stock on stock.trno = head.trno
left join item on item.itemid = stock.itemid
left join hstockinfotrans as info on info.trno = stock.reqtrno and info.line=stock.reqline
where date(head.dateid) between '$start' and '$end' and stock.status = 1 order  by item.itemname,head.client";

        return $query;
    }
    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

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
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '920', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

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
        $company   = $config['params']['companyid'];
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '70', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUPPLIER', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME (Stockcard)', '145', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME (Requestor)', '145', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QUANTITY', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DISCOUNT', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APPROVED DATE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}
