<?php

namespace App\Http\Classes\modules\reportlist\student;

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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class student_sales_report
{
    public $modulename = 'Student Sales Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'start', 'end', 'ehstudentlookup', 'dcentername', 'categoryname', 'subcatname', 'radiotypeofreportsales'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
        data_set($col1, 'groupid.action', 'lookupclientgroupledger');
        data_set($col1, 'groupid.class', 'csgroup sbccsreadonly');
        data_set($col1, 'groupid.readonly', true);
        data_set($col1, 'dcentername.required', false);
        data_set($col1, 'ehstudentlookup.label', 'Student');
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
        $fields = ['radioposttype', 'radiosortby'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
            ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]);
        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as end,
                            '' as clientname,'' as ehstudentlookup, '0' as clientid,
                            'report' as typeofreport, '0' as posttype,'docno' as sortby,
                         '' as category,'' as categoryname,'' as subcat,
                         '" . $defaultcenter[0]['center'] . "' as center,
                         '" . $defaultcenter[0]['centername'] . "' as centername,
                         '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                         '' as subcatname, '' as subcatid";


        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }
    // 
    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }
    // GET THE FINISH LAYOUT OF REPORT
    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $typeofreport = $config['params']['dataparams']['typeofreport'];
        // ADD SWITCH IF EVER MORE LAYOUT OR PER COMPANY

        switch ($typeofreport) {
            case 'report':
                $result = $this->reportDefaultLayout_REPORT($config);
                break;
            case 'lessreturn':
                $result = $this->reportDefaultLayout_LESSRETURN($config);
                break;
            case 'return':
                $result = $this->reportDefaultLayout_RETURN($config);
                break;
        }

        return $result;
    }

    public function reportDefault($config)
    {
        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $category  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
        $subcat =  $config['params']['dataparams']['subcat'];
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $center       = $config['params']['dataparams']['center'];
        $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
        $typeofreport = $config['params']['dataparams']['typeofreport'];
        $sortby       = $config['params']['dataparams']['sortby'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter = "";

        if ($subcatname != "") {
            $filter = $filter . " and item.subcat='$subcat'";
        }
        if ($client != "") {
            $filter .= " and client.clientid='$clientid'";
        }

        if ($custcategory != "") {
            $filter .= " and item.category='$category'";
        }

        $center       = $config['params']['dataparams']['center'];
        if ($center != "") {
            $filter .= " and cntnum.center='$center'";
        }

        switch ($posttype) {
            case '0': // POSTED
                switch ($typeofreport) {
                    case 'report':
                        $query = "select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname, sum(stock.ext) as amount
                                from glhead as head 
                                left join glstock as stock on stock.trno=head.trno
                                left join client on client.clientid=head.clientid
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
                                        $filter and item.isofficesupplies = 0
                                group by head.dateid, head.docno, client.client,client.clientname
                                order by $sortby";
                        break;
                    case 'lessreturn':
                        $query = "select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname,
                                        sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount
                                from glhead as head 
                                left join glstock as stock on stock.trno=head.trno
                                left join client on client.clientid=head.clientid
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf','cm')
                                        and head.dateid between '$start' and '$end' $filter  and item.isofficesupplies=0
                                group by head.dateid, head.docno, client.client, 
                                        client.clientname,head.doc
                                order by $sortby";
                        break;
                    case 'return':
                        $query = "select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname,sum(stock.ext) as amount
                                from glhead as head 
                                left join glstock as stock on stock.trno=head.trno
                                left join client on client.clientid=head.clientid
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc='CM' and head.dateid between '$start' and '$end' 
                                        $filter  and item.isofficesupplies =0
                                group by head.dateid, head.docno, client.client, client.clientname
                                order by $sortby";
                        break;
                }
                //
                break;
            case  '1': // UNPOSTED
                switch ($typeofreport) {
                    case 'report':
                        $query = "select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname, sum(stock.ext) as amount
                                from lahead as head 
                                left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf') and head.dateid between '$start' and '$end' 
                                        $filter and item.isofficesupplies =0
                                group by head.dateid, head.docno, client.client, client.clientname
                                order by $sortby";
                        break;

                    case 'lessreturn':
                        $query = "select head.doc,'sales less return' as type, 'u' as tr,date(head.dateid) as dateid, 
                                        head.docno,client.client, client.clientname,
                                        sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount
                                from lahead as head 
                                left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf','cm') 
                                        and head.dateid between '$start' and '$end' $filter and item.isofficesupplies =0
                                group by head.dateid, head.docno, client.client,client.clientname, head.doc
                                order by $sortby";
                        break;

                    case 'return':
                        $query = "select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname, sum(stock.ext) as amount
                                from lahead as head 
                                left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc='cm' and head.dateid between '$start' and '$end' $filter 
                                        and item.isofficesupplies=0
                                group by head.dateid, head.docno, client.client, client.clientname
                                order by $sortby";
                        break;
                }
                break;
            default:
                switch ($typeofreport) {
                    case 'report':
                        $query = "select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname, sum(stock.ext) as amount
                                from glhead as head 
                                left join glstock as stock on stock.trno=head.trno
                                left join client on client.clientid=head.clientid
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf')
                                and date(head.dateid) between '$start' and '$end' 
                                    $filter and item.isofficesupplies = 0
                                group by head.dateid, head.docno, client.client,client.clientname
                                union all
                                select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                                client.client, client.clientname, sum(stock.ext) as amount
                                from lahead as head 
                                left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf') and head.dateid between '$start' and '$end' 
                                $filter and item.isofficesupplies =0
                                group by head.dateid, head.docno, client.client, client.clientname
                                order by $sortby";
                        break;

                    case 'lessreturn':
                        $query = "select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, 
                                        head.docno,client.client, client.clientname, 
                                        sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount 
                                from glhead as head left join glstock as stock on stock.trno=head.trno
                                left join client on client.clientid=head.clientid
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf','cm')
                                        and head.dateid between '$start' and '$end' $filter and item.isofficesupplies=0
                                group by head.dateid, head.docno, client.client,client.clientname, head.doc
                                union all
                                select head.doc,'sales less return' as type, 'u' as tr, 
                                        date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname, 
                                        sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount
                                from lahead as head left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc in ('sj','mj','sd','se','sf','cm') 
                                        and head.dateid between '$start' and '$end' $filter 
                                        and item.isofficesupplies =0
                                group by head.dateid, head.docno, client.client,client.clientname,head.doc
                                order by $sortby";
                        break;

                    case 'return':
                        $query = "select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname,sum(stock.ext) as amount
                                from glhead as head 
                                left join glstock as stock on stock.trno=head.trno
                                left join client on client.clientid=head.clientid
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc='CM' and head.dateid between '$start' and '$end' 
                                     $filter and item.isofficesupplies =0
                                group by head.dateid, head.docno, client.client, client.clientname
                                union all
                                select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                                        client.client, client.clientname,sum(stock.ext) as amount
                                from lahead as head 
                                left join lastock as stock on stock.trno=head.trno
                                left join client on client.client=head.client
                                left join cntnum on cntnum.trno=head.trno
                                left join item on item.itemid=stock.itemid
                                left join itemcategory as cat on cat.line = item.category
                                left join itemsubcategory as subcat on subcat.line = item.subcat
                                where head.doc='cm' and head.dateid between '$start' and '$end' $filter 
                                        and item.isofficesupplies=0
                                group by head.dateid, head.docno, client.client, client.clientname
                                order by $sortby";
                        break;
                }
                break;
        }

        return $this->coreFunctions->opentable($query);
    }


    //default

    private function default_displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];


        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientname   = $config['params']['dataparams']['clientname'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $subcatname =  $config['params']['dataparams']['subcatname'];
        $typeofreport = $config['params']['dataparams']['typeofreport'];
        $sortby       = $config['params']['dataparams']['sortby'];
        $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

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

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STUDENT SALES ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        if ($posttype == '0') {
            $posttype = 'Posted';
        } else if ($posttype == '1') {
            $posttype = 'Unposted';
        } else {
            $posttype = 'ALL';
        }

        $filtercenter = $config['params']['dataparams']['center'];
        if ($filtercenter == "") {
            $filtercenter = 'ALL';
        }

        $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        if ($sortby == 'docno') {
            $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();

        if ($custcategory == '') {
            $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Category : ' . strtoupper($custcategory),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }

        if ($subcatname == '') {
            $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $name = 'STUDENT NAME';


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STUDENT CODE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($name, '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout_REPORT($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientname   = $config['params']['dataparams']['clientname'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $typeofreport = $config['params']['dataparams']['typeofreport'];
        $sortby       = $config['params']['dataparams']['sortby'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $count = 34;
        $page = 36;

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        $Tot = 0;
        $amt = 0;

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();


            $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format(
                $data->amount,
                2
            ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->endrow();
            $Tot = $Tot + $data->amount;

            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function reportDefaultLayout_LESSRETURN($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientname   = $config['params']['dataparams']['clientname'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $typeofreport = $config['params']['dataparams']['typeofreport'];
        $sortby       = $config['params']['dataparams']['sortby'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $companyid = $config['params']['companyid'];


        $count = 34;
        $page = 36;

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";


        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        $Tot = 0;
        $amt = 0;

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format(
                $data->amount,
                2
            ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->endrow();
            $Tot = $Tot + $data->amount;

            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
        }


        $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportDefaultLayout_RETURN($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $client       = $config['params']['dataparams']['ehstudentlookup'];
        $clientname   = $config['params']['dataparams']['clientname'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $typeofreport = $config['params']['dataparams']['typeofreport'];
        $sortby       = $config['params']['dataparams']['sortby'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $companyid = $config['params']['companyid'];

        $count = 34;
        $page = 36;

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";


        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        $Tot = 0;
        $amt = 0;

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

            $str .= $this->reporter->endrow();
            $Tot = $Tot + $data->amount;

            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $page = $page + $count;
            }
        }


        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();


        return $str;
    }
}//end class