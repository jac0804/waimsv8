<?php

namespace App\Http\Classes\modules\reportlist\sales_agent;

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


class analyze_agent_sales_per_area_report
{
    public $modulename = 'Analyze Agent Sales Per Area Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1200'];

    public function __construct($params = null)
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass($params);
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $fields = ['radioprint', 'year', 'pref', 'categoryname', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.label', 'Transactions');
        data_set($col1, 'categoryname.label', 'Item Category');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("
        select 
        'default' as print,
        left(now(),4) as year,
        '' as pref, 
        '' as prefix,
        '' as category,
        '' as categoryname,
        '0' as reporttype");
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
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $year    = $config['params']['dataparams']['year'];
        $prefix = $config['params']['dataparams']['prefix'];
        $category  = $config['params']['dataparams']['category'];
        $categoryname  = $config['params']['dataparams']['categoryname'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $filter = '';

        if ($prefix != '') {
            $filter .= " and head.doc  = '$prefix'";
        }

        if ($categoryname != "") {
            $filter .= " and item.category='$category'";
        }

        switch ($reporttype) {
            case '0': // posted
                $query = "select area, yr,
                    sum(mojan) as mojan,
                    sum(mofeb) as mofeb,
                    sum(momar) as momar,
                    sum(moapr) as moapr,
                    sum(momay) as momay,
                    sum(mojun) as mojun,
                    sum(mojul) as mojul,
                    sum(moaug) as moaug,
                    sum(mosep) as mosep,
                    sum(mooct) as mooct,
                    sum(monov) as monov,
                    sum(modec) as modec
                    from (
                    select ifnull(cl.area,'') as area,
                    upper(ifnull(agent.clientname,'')) as agent,
                    year(head.dateid) as yr,
                    sum(case when month(head.dateid)=1  then stock.ext else 0 end) as mojan,
                    sum(case when month(head.dateid)=2  then stock.ext else 0 end) as mofeb,
                    sum(case when month(head.dateid)=3  then stock.ext else 0 end) as momar,
                    sum(case when month(head.dateid)=4  then stock.ext else 0 end) as moapr,
                    sum(case when month(head.dateid)=5  then stock.ext else 0 end) as momay,
                    sum(case when month(head.dateid)=6  then stock.ext else 0 end) as mojun,
                    sum(case when month(head.dateid)=7  then stock.ext else 0 end) as mojul,
                    sum(case when month(head.dateid)=8  then stock.ext else 0 end) as moaug,
                    sum(case when month(head.dateid)=9  then stock.ext else 0 end) as mosep,
                    sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
                    sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
                    sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join client as cl on cl.clientid=head.clientid
                    left join client as agent on agent.clientid=head.agentid
                    left join cntnum on cntnum.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    where head.doc in ('SJ','CM') and year(head.dateid)= '$year' $filter
                    group by 1, 2, 3
                    ) as x
                    group by area,yr
                    order by area, yr";
                break;
            case '1': // unposted
                $query = "select area, yr,
                    sum(mojan) as mojan,
                    sum(mofeb) as mofeb,
                    sum(momar) as momar,
                    sum(moapr) as moapr,
                    sum(momay) as momay,
                    sum(mojun) as mojun,
                    sum(mojul) as mojul,
                    sum(moaug) as moaug,
                    sum(mosep) as mosep,
                    sum(mooct) as mooct,
                    sum(monov) as monov,
                    sum(modec) as modec
                    from (
                    select ifnull(cl.area,'') as area,
                    upper(ifnull(agent.clientname,'')) as agent,
                    year(head.dateid) as yr,
                    sum(case when month(head.dateid)=1  then stock.ext else 0 end) as mojan,
                    sum(case when month(head.dateid)=2  then stock.ext else 0 end) as mofeb,
                    sum(case when month(head.dateid)=3  then stock.ext else 0 end) as momar,
                    sum(case when month(head.dateid)=4  then stock.ext else 0 end) as moapr,
                    sum(case when month(head.dateid)=5  then stock.ext else 0 end) as momay,
                    sum(case when month(head.dateid)=6  then stock.ext else 0 end) as mojun,
                    sum(case when month(head.dateid)=7  then stock.ext else 0 end) as mojul,
                    sum(case when month(head.dateid)=8  then stock.ext else 0 end) as moaug,
                    sum(case when month(head.dateid)=9  then stock.ext else 0 end) as mosep,
                    sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
                    sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
                    sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join client as cl on cl.client=head.client
                    left join client as agent on agent.client=head.agent
                    left join cntnum on cntnum.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    where head.doc in ('SJ','CM') and year(head.dateid)= '$year' $filter
                    group by 1, 2, 3
                    ) as x
                    group by area,yr
                    order by area, yr";
                break;
            default: // all
                $query = "select area, yr,
                    sum(mojan) as mojan,
                    sum(mofeb) as mofeb,
                    sum(momar) as momar,
                    sum(moapr) as moapr,
                    sum(momay) as momay,
                    sum(mojun) as mojun,
                    sum(mojul) as mojul,
                    sum(moaug) as moaug,
                    sum(mosep) as mosep,
                    sum(mooct) as mooct,
                    sum(monov) as monov,
                    sum(modec) as modec
                    from (
                    select ifnull(cl.area,'') as area,
                    upper(ifnull(agent.clientname,'')) as agent,
                    year(head.dateid) as yr,
                    sum(case when month(head.dateid)=1  then stock.ext else 0 end) as mojan,
                    sum(case when month(head.dateid)=2  then stock.ext else 0 end) as mofeb,
                    sum(case when month(head.dateid)=3  then stock.ext else 0 end) as momar,
                    sum(case when month(head.dateid)=4  then stock.ext else 0 end) as moapr,
                    sum(case when month(head.dateid)=5  then stock.ext else 0 end) as momay,
                    sum(case when month(head.dateid)=6  then stock.ext else 0 end) as mojun,
                    sum(case when month(head.dateid)=7  then stock.ext else 0 end) as mojul,
                    sum(case when month(head.dateid)=8  then stock.ext else 0 end) as moaug,
                    sum(case when month(head.dateid)=9  then stock.ext else 0 end) as mosep,
                    sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
                    sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
                    sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join client as cl on cl.client=head.client
                    left join client as agent on agent.client=head.agent
                    left join cntnum on cntnum.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    where head.doc in ('SJ','CM') and year(head.dateid)= '$year'  $filter
                    group by 1, 2, 3
                    union all
                    select ifnull(cl.area,'') as area,
                    upper(ifnull(agent.clientname,'')) as agent,
                    year(head.dateid) as yr,
                    sum(case when month(head.dateid)=1  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=2  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=3  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=4  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=5  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=6  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=7  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=8  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=9  then stock.ext else 0 end),
                    sum(case when month(head.dateid)=10 then stock.ext else 0 end),
                    sum(case when month(head.dateid)=11 then stock.ext else 0 end),
                    sum(case when month(head.dateid)=12 then stock.ext else 0 end)
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join client as cl on cl.clientid=head.clientid
                    left join client as agent on agent.clientid=head.agentid
                    left join cntnum on cntnum.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    where head.doc in ('SJ','CM') and  year(head.dateid)= '$year'  $filter
                    group by 1, 2, 3
                    ) as x
                    group by area,yr
                    order by area, yr";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function default_header($config, $layoutsize, $font, $fontsize, $year, $transtype, $itemclass)
    {
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $prefix = $config['params']['dataparams']['prefix'];
        $category = $config['params']['dataparams']['categoryname'];

        $center = $config['params']['center'];
        $headerdata = $this->coreFunctions->opentable("select code, name, address from center where code='$center'");

        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ANALYZE SALES MONTHLY PER AREA - ' . $year, '800', null, false, '', '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->col('Print Date : ' . date('m/d/Y g:i:sA'), '400', null, false, '', '', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TRANSACTION : ', '80', null, false, '', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(' ' . $transtype, '120', null, false, '', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PREFIX :', '50', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col($prefix == '' ? 'ALL PREFIX' : strtoupper($prefix), '180', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('ITEM CATEGORY :', '80', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col($category == '' ? 'ALL CATEGORY' : strtoupper($category), '150', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->pagenumber('Page', 200, null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('S A L E S', $layoutsize, null, false, '', '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $headers = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('AREA', '130', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
        foreach ($headers as $h) {
            $str .= $this->reporter->col($h, '88', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('TOTAL', '125', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config, $data)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $year       = $config['params']['dataparams']['year'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $category   = $config['params']['dataparams']['categoryname'];

        $layoutsize = '1300';
        $font       = 'Century Gothic';
        $fontsize   = '9';
        $border     = '1px solid';

        switch ($reporttype) {
            case '0':
                $transtype = 'POSTED';
                break;
            case '1':
                $transtype = 'UNPOSTED';
                break;
            default:
                $transtype = 'ALL TRANSACTION';
                break;
        }

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $itemclass = $category == '' ? 'All Item Class' : $category;

        $months = ['mojan', 'mofeb', 'momar', 'moapr', 'momay', 'mojun', 'mojul', 'moaug', 'mosep', 'mooct', 'monov', 'modec'];

        $str = '';
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '0px;margin-top:20px;margin-left:20px');

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->default_header($config, $layoutsize, $font, $fontsize, $year, $transtype, $itemclass);

        $grand = array_fill_keys($months, 0);
        $grandtotal = 0;

        foreach ($data as $d) {
            $rowtotal = 0;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($d->area == '' ? 'NO DEFAULT AREA' : strtoupper($d->area), '130', null, false, '', '', 'L', $font, $fontsize, '', '', '');

            foreach ($months as $m) {
                $val = $d->$m;
                $rowtotal += $val;
                $grand[$m] += $val;
                $str .= $this->reporter->col($val == 0 ? '-' : number_format($val, 2), '78', null, false, '', '', 'R', $font, $fontsize, '', '', '');
            }

            $grandtotal += $rowtotal;
            $str .= $this->reporter->col($rowtotal == 0 ? '-' : number_format($rowtotal, 2), '134', null, false, '', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL :', '130', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        foreach ($months as $m) {
            $str .= $this->reporter->col($grand[$m] == 0 ? '-' : number_format($grand[$m], 2), '78', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col(number_format($grandtotal, 2), '134', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class