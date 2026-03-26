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

class payment_released
{
    public $modulename = 'Payment Released';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];



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
        $fields = ['radioprint', 'effectfromdate', 'effecttodate', 'schedin', 'schedout', 'reportusers', 'categoryname', 'potype', 'dept', 'repsortby', 'radioreporttype'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'schedin.label', 'Start Time (HH:mm)');
        data_set($col1, 'schedout.label', 'End Time (HH:mm)');

        data_set($col1, 'schedin.type', 'input');
        data_set($col1, 'schedout.type', 'input');

        data_set($col1, 'schedout.readonly', false);
        data_set($col1, 'schedin.readonly', false);
        data_set($col1, 'potype.required', false);
        data_set($col1, 'repsortby.label', 'Sort by Header');
        data_set($col1, 'categoryname.action', 'lookupreqcategory');
        data_set($col1, 'categoryname.lookupclass', 'lookupreqcategory');
        data_set($col1, 'reportusers.lookupclass', 'lookupusers2');
        data_set($col1, 'dept.label', 'Department');
        data_set($col1, 'dept.lookupclass', 'lookupddeptname');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Final Checking', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Payment Released', 'value' => '1', 'color' => 'orange']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        $datetime = "select  date_format(now(),'%H:%i') as start,  date_format(now(),'%H:%i') as end";
        $datetime = $this->coreFunctions->opentable($datetime);

        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];
        return $this->coreFunctions->opentable("select 
                'default' as print,
                left(now(),10) as effectfromdate,
                left(now(),10) as effecttodate,
             '" . $datetime[0]->start . "' as schedin,
           '" . $datetime[0]->end . "'  as schedout,
                '' as repsortby,
                '' as name,
                '' as categoryname,
                '' as potype,
                '' as dept,
                '' as clientname,
                '' as ourref,
                '' as username,
                  '' as userid,
                '0' as reporttype
                ");
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
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }
    public function default_QUERY($config)
    {
        $schedin        = $config['params']['dataparams']['schedin'];
        $schedout      = $config['params']['dataparams']['schedout'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['effectfromdate']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['effecttodate']));
        $detpname  = $config['params']['dataparams']['clientname'];

        $reporttype  = $config['params']['dataparams']['reporttype'];
        $sortby = $config['params']['dataparams']['name'];
        $potype = $config['params']['dataparams']['potype'];
        $catname = $config['params']['dataparams']['categoryname'];
        $filteruser  = $config['params']['dataparams']['username'];
        $filter = "";
        $date = "";
        $time = "";
        $order = "order by docno";
        if (!empty($filteruser)) {
            $filter .= " and head.createby = '$filteruser' ";
        }
        if ($reporttype == 0) {
            $filter .= "and info.releasedate is null and num.statid = 42 ";
            $date = " and date(head.dateid) between  '$start' and '$end'";
        } else {
            $filter .= "and info.releasedate is not null ";
            $date = " and DATE_FORMAT(info.releasedate, '%Y-%m-%d %H:%i') between  '" . $start . " " . $schedin . "' and '" . $end . " " . $schedout . "'";
        }
        if ($detpname != '') {
            $deptid  = $config['params']['dataparams']['deptid'];
            $filter .= "and dept.clientid =  " . $deptid;
        }
        if ($potype != '') {
            $filter .= " and pr.potype = '$potype' ";
        }
        if ($catname != '') {
            $filter .= " and cat.category = '$catname' ";
        }
        if ($sortby != '') {
            $order = "order by $sortby";
        }

        $query = "select head.docno,info.releasedate as datereleased,head.ourref,head.yourref,dinfo.si2 as sino ,dept.clientname as deptname,
format(detail.db,2) as amount,po.yourref as pono,pr.potype,cat.category
from lahead as head
left join ladetail as detail on detail.trno = head.trno
left join cntnum as num on num.trno = head.trno
left join coa on coa.acnoid = detail.acnoid
left join cntnuminfo as info on info.trno = head.trno
left join detailinfo as dinfo on dinfo.trno=detail.trno and dinfo.line=detail.line
left join cvitems as cv on cv.trno = detail.trno
left join hpohead as po on po.trno=cv.refx
left join hpostock as pos on pos.trno=po.trno
left join hprhead as pr on pr.trno=pos.reqtrno
left join client as dept on dept.clientid = pr.deptid
 left join reqcategory as cat on cat.line=pr.ourref
where head.doc = 'CV' and coa.alias in ('AP98') and head.salestype in('COD Cash') $date $filter 
group by head.docno,info.releasedate,head.ourref,head.yourref,dinfo.si2,
detail.db,po.yourref,dept.clientname,head.dateid,pr.potype,cat.category
union all
select head.docno,info.releasedate as datereleased,head.ourref,head.yourref,dinfo.si2 as sino ,dept.clientname as deptname,
format(detail.db,2) as amount,po.yourref as pono,pr.potype,cat.category
from glhead as head
left join gldetail as detail on detail.trno = head.trno
left join cntnum as num on num.trno = head.trno
left join coa on coa.acnoid = detail.acnoid
left join cntnuminfo as info on info.trno = head.trno
left join detailinfo as dinfo on dinfo.trno=detail.trno and dinfo.line=detail.line
left join hcvitems as cv on cv.trno = detail.trno
left join hpohead as po on po.trno=cv.refx
left join hpostock as pos on pos.trno=po.trno
left join hprhead as pr on pr.trno=pos.reqtrno
left join client as dept on dept.clientid = pr.deptid
 left join reqcategory as cat on cat.line=pr.ourref
where head.doc = 'CV' and coa.alias in ('AP98') and head.salestype in('COD Cash') $date $filter
group by head.docno,info.releasedate,head.ourref,head.yourref,dinfo.si2,
detail.db,po.yourref,dept.clientname,head.dateid,pr.potype,cat.category  $order";

        return $query;
    }
    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
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
                $str .= $this->reporter->col($data->deptname, '230', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->amount, '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->datereleased, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pono, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sino, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
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

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['schedin']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['schedout']));
        $filterusername  = $config['params']['user'];

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
        $str .= $this->reporter->col('Payment Released', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date and Time Range : ' . $start . ' to ' . $end, '700', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User : ' . $filterusername, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
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

        $str .= $this->reporter->col('CV#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPARTMENT', '230', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE CASH RELEASED', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PO#', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SI#', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OURREF', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}
