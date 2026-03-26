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

class mc_availability
{
    public $modulename = 'MC Availability';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'start', 'brandname', 'brandid', 'dcentername', 'dwhname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'effectfromdate.required', true);
        data_set($col1, 'effecttodate.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'dcentername.required', false);

        data_set($col1, 'start.label', 'As of Date');
        data_set($col1, 'dcentername.required', false);
        data_set($col1,  'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'red']]);

        $fields = ['radioreporttype', 'print'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2,  'radioreporttype.options', [
            ['label' => 'Default', 'value' => 'standard', 'color' => 'red'],
            ['label' => 'Per Engine', 'value' => 'engine', 'color' => 'red']
        ]);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select c.code as center,c.name as centername,concat(c.code,'~',c.name) as dcentername,concat(c.warehouse,'~',w.clientname) as dwhname,w.clientname as whname,c.warehouse as wh 
        from center as c left join client as w on w.client = c.warehouse where c.code='$center'")), true);
        $params_qry = "select 
            'default' as print,
            left(now(),10) as start,
            '0' as posttype,
            '' as brand,
            '' as brandid,  
            '' as brandname,
            '" . $defaultcenter[0]['wh'] . "' as wh,
            '" . $defaultcenter[0]['whname'] . "' as whname,
            '" . $defaultcenter[0]['dwhname'] . "' as dwhname,
            '' as center,
            '' as centername,
            '' as dcentername, 
            
            'standard' as reporttype";
        return $this->coreFunctions->opentable($params_qry);
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
        if ($reporttype == 'standard') {
            $result = $this->reportDefaultLayout($config);
        } else {
            $result = $this->reportEngineLayout($config);
        }

        return $result;
    }

    public function reportDefault($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        // QUERY
        if ($reporttype == 'standard') {
            $query = $this->default_QUERY($config);
        } else {
            $query = $this->engine_QUERY($config);
        }

        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $brandname  = $config['params']['dataparams']['brandname'];
        $brand      = $config['params']['dataparams']['brand'];
        $wh         = $config['params']['dataparams']['wh'];
        $fcenter    = $config['params']['dataparams']['center'];

        $filter = '';

        if ($fcenter != "") $filter .= " and cntnum.center = '$fcenter'";
        if ($brandname != "") $filter .= " and i.brand='$brand'";
        if ($wh != "") $filter .= " and wh.client='$wh'";

        $query = "      select wh.clientname as whname, i.itemname,count(ss.color) as tlcolor,ss.color
                        from lahead as head
                        left join lastock as stock on stock.trno=head.trno
                        left join item as i on i.itemid=stock.itemid
                        left join itemcategory as cat on cat.line = i.category
                        left join cntnum on cntnum.trno=head.trno
                        left join serialin as ss on ss.trno=stock.trno and ss.line=stock.line
                        left join client as wh on wh.clientid=stock.whid
                        where  head.dateid<='$asof' and ss.outline=0 and cat.name ='MC UNIT' $filter  
                        group by  wh.clientname,i.itemname,ss.color
                        union all
                        select wh.clientname as whname,i.itemname,count(ss.color) as tlcolor,ss.color
                        from glhead as head
                        left join glstock as stock on stock.trno=head.trno
                        left join item as i on i.itemid=stock.itemid
                        left join itemcategory as cat on cat.line = i.category
                        left join cntnum on cntnum.trno=head.trno
                        left join rrstatus as rr on rr.trno=stock.trno and rr.line=stock.line
                        left join serialin as ss on ss.trno=rr.trno and ss.line=rr.line
                        left join client as wh on wh.clientid=stock.whid
                         where head.dateid<='$asof'  and ss.outline=0 and cat.name ='MC UNIT' $filter  
                        group by  wh.clientname,i.itemname,ss.color ";

        return $query;
    }


    public function engine_QUERY($config)
    {
        $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $brandname  = $config['params']['dataparams']['brandname'];
        $brand      = $config['params']['dataparams']['brand'];
        $wh         = $config['params']['dataparams']['wh'];
        $fcenter    = $config['params']['dataparams']['center'];

        $filter = '';

        if ($fcenter != "") $filter .= " and cntnum.center = '$fcenter'";
        if ($brandname != "") $filter .= " and i.brand='$brand'";
        if ($wh != "") $filter .= " and wh.client='$wh'";

        $query = "      
                    select wh.clientname as whname,i.itemname,ss.serial as mc_engine,ss.chassis,count(ss.serial) as enginecount,ss.color
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    left join cntnum on cntnum.trno=head.trno
                    left join rrstatus as rr on rr.trno=stock.trno and rr.line=stock.line
                    left join serialin as ss on ss.trno=rr.trno and ss.line=rr.line
                    left join client as wh on wh.clientid=stock.whid
                    where  head.dateid<='$asof' and ss.outline=0 and cat.name ='MC UNIT' $filter  
                    group by wh.clientname,i.itemname,ss.serial,ss.chassis,ss.color
                    union all
                    select wh.clientname as whname,i.itemname,ss.serial as mc_engine,ss.chassis,count(ss.serial) as enginecount,ss.color
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = i.category
                    left join cntnum on cntnum.trno=head.trno
                    left join rrstatus as rr on rr.trno=stock.trno and rr.line=stock.line
                    left join serialin as ss on ss.trno=rr.trno and ss.line=rr.line
                    left join client as wh on wh.clientid=stock.whid
                    where head.dateid<='$asof'  and ss.outline=0 and cat.name ='MC UNIT' $filter  
                    group by wh.clientname,i.itemname,ss.serial,ss.chassis,ss.color ";

        return $query;
    }


    public function reportEngineLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

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
        $str .= $this->header_engine($config);
        $str .= $this->tableheader2($layoutsize, $config);

        if (!empty($result)) {

            $totalunit = 0;
            $itemname = '';
            $overalltotal = 0;

            foreach ($result as $key => $data) {
                if ($itemname != $data->itemname) {
                    if ($itemname != '') {
                        $str .= $this->reporter->addline();
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '230', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->endrow();

                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '320', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('Total ' . $itemname . ':', '230', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'RT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'RT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col($totalunit, '100', null, false, $border, 'T', 'CT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->endrow();
                    }

                    $overalltotal += $totalunit;
                    $itemname = $data->itemname;
                    $totalunit = 0;
                }
                $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->whname, '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->color, '230', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->mc_engine, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->chassis, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->enginecount, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $totalunit += $data->enginecount;
            }

            $overalltotal += $totalunit;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '230', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('Total ' . $itemname . ':', '230', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($totalunit, '100', null, false, $border, 'T', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= "<br/>";
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '230', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('OVERALL TOTAL: ', '230', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($overalltotal, '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_engine($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $fcenter    = $config['params']['dataparams']['center'];
        $fcentername    = $config['params']['dataparams']['centername'];
        $brandname     = $config['params']['dataparams']['brandname'];
        $whname     = $config['params']['dataparams']['whname'];
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
        $str .= $this->reporter->col('MC Availability', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As of : ' . $asof, '320', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        if ($brandname == '') {
            $str .= $this->reporter->col('Brand: ALL', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Brand: ' . $brandname, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        if ($fcenter == '') {
            $str .= $this->reporter->col('Branch: ALL ', '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Branch: ' . $fcentername, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }

        if ($whname == '') {
            $str .= $this->reporter->col('WH: ALL ', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('WH: ' . $whname, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('', '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader2($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1000';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOCATION', '320', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MC UNIT', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COLOR', '230', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ENGINE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CHASSIS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NO OF UNITS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

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

            $totalunit = 0;
            $itemname = '';
            $overalltotal = 0;

            foreach ($result as $key => $data) {
                if ($itemname != $data->itemname) {
                    if ($itemname != '') {
                        $str .= "<br/>";
                        $str .= $this->reporter->addline();
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '250', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '130', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->endrow();

                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '320', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('Total ' . $itemname . ':', '250', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'RT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col($totalunit, '130', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }

                    $overalltotal += $totalunit;
                    $itemname = $data->itemname;
                    $totalunit = 0;
                }

                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->whname, '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->color, '250', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->tlcolor, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '130', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $totalunit += $data->tlcolor;
            }

            $overalltotal += $totalunit;

            // $str .= "<br/>";
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '130', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, 'T', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('Total ' . $itemname . ':', '250', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($totalunit, '130', null, false, $border, 'T', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // $str .= "<br/>";
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '130', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('OVERALL TOTAL: ', '250', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($overalltotal, '130', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $fcenter    = $config['params']['dataparams']['center'];
        $fcentername    = $config['params']['dataparams']['centername'];
        $brandname     = $config['params']['dataparams']['brandname'];
        $whname     = $config['params']['dataparams']['whname'];
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
        $str .= $this->reporter->col('MC Availability', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As of : ' . $asof, '320', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        if ($brandname == '') {
            $str .= $this->reporter->col('Brand: ALL', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Brand: ' . $brandname, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        if ($fcenter == '') {
            $str .= $this->reporter->col('Branch: ALL ', '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Branch: ' . $fcentername, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }

        if ($whname == '') {
            $str .= $this->reporter->col('WH: ALL ', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('WH: ' . $whname, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('', '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

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
        $layoutsize = '1000';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOCATION', '320', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MC UNIT', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COLOR', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL PER COLOR', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL AVAILABLE UNITS', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class