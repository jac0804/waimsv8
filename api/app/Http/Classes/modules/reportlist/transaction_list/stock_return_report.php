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

class stock_return_report
{
    public $modulename = 'Stock Return Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1100'];


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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
        $col2 = $this->fieldClass->create($fields);

        data_set(
            $col2,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start, left(now(),10) as end,
                        '0' as posttype,'0' as reporttype, 'ASC' as sorting,'' as dwhname, 
                        '' as wh, '' as whname,'' as dclientname, '' as client, '' as clientname";

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
            case 0:
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;

            case 1:
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
        }

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
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $clientname     = $config['params']['dataparams']['clientname'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $companyid  = $config['params']['companyid'];
        $wh = $config['params']['dataparams']['wh'];
        $sorting    = $config['params']['dataparams']['sorting'];


        $whfilter = "";
        $filter1 = "";
        $filter2 = "";
        $addfield = "";
        $addjoin = "";
        $groupby = "";
        if ($companyid == 16) { //ati
            $addfield = " , ifnull(sa.sano,'') as sano, ifnull(info.sono,'') as sono ";
            $addjoin = " left join clientsano as sa on sa.line=head.sano
                left join cntnuminfo as info on info.trno=head.trno";
        }

        if ($wh != "") {
            $whfilter .= " and warehouse.client='$wh'";
        }

        if ($client != "") {
            $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);
            switch ($posttype) {
                case 0: //posted
                    $filter1 = "";
                    $filter2 = " and head.clientid = '$clientid' ";
                    break;
                case 1: //unposted
                    $filter1 = "and head.client = '$client'";
                    $filter2 = "";
                    break;
                case 2: //all
                    $filter1 = "and head.client = '$client'";
                    $filter2 = " and head.clientid = '$clientid'  ";
                    break;
            }
        }

        if ($companyid == 16) { //ati
            $groupby = "group by head.docno, warehouse.clientname,head.dateid,
                                head.yourref,head.ourref,head.rem,head.clientname,dept.clientname,
                                 sa.sano,info.sono";
        } else {
            $groupby = "group by head.docno, warehouse.clientname,head.dateid,
                                head.yourref,head.ourref,head.rem,head.clientname,dept.clientname";
        }


        switch ($reporttype) {
            case 1: //detailed
                switch ($posttype) {
                    case 0: //posted
                        $query = "select head.trno,head.docno,date(head.dateid) as dateid,head.clientname,head.address,
                                    warehouse.client as wh,
                                    warehouse.clientname as whname,   '' as dwhname, head.yourref, head.ourref,head.rem as note1,
                                    item.barcode,   item.itemname,  stock.uom, stock.qty,stock.amt,stock.disc,stock.ext,
                                    stock.ref, stock.whid,stock.rem as note2,  swh.client as wh2,
                                    swh.clientname as whname2 $addfield

                                    from glhead as head
                                    left join glstock as stock on stock.trno=head.trno
                                    left join client as warehouse on warehouse.clientid = head.whid
                                    left join item on item.itemid=stock.itemid
                                    left join client as swh on swh.clientid=stock.whid $addjoin
                                    where doc='sp' and date(head.dateid) between '$start' and '$end' $whfilter $filter1 $filter2 
                                     order by docno $sorting ";
                        break;
                    case 1: //unposted
                        $query = "select head.trno,head.docno,date(head.dateid) as dateid,head.clientname,head.address,
                                    warehouse.client as wh,
                                    warehouse.clientname as whname,   '' as dwhname, head.yourref, head.ourref,head.rem as note1,
                                    item.barcode,   item.itemname,  stock.uom, stock.qty,stock.amt,stock.disc,stock.ext,
                                    stock.ref, stock.whid,stock.rem as note2,  swh.client as wh2,
                                    swh.clientname as whname2 $addfield

                                    from lahead as head
                                    left join lastock as stock on stock.trno=head.trno
                                    left join client as warehouse on warehouse.client = head.wh
                                    left join item on item.itemid=stock.itemid
                                    left join client as swh on swh.clientid=stock.whid $addjoin
                                    where doc='sp' and date(head.dateid) between '$start' and '$end' $whfilter $filter1 $filter2  
                                     order by docno $sorting";
                        break;
                    case 2: //all
                        $query = "select head.trno,head.docno,date(head.dateid) as dateid,head.clientname,head.address,
                                    warehouse.client as wh,
                                    warehouse.clientname as whname,   '' as dwhname, head.yourref, head.ourref,head.rem as note1,
                                    item.barcode,   item.itemname,  stock.uom, stock.qty,stock.amt,stock.disc,stock.ext,
                                    stock.ref, stock.whid,stock.rem as note2,  swh.client as wh2,
                                    swh.clientname as whname2 $addfield

                                    from lahead as head
                                    left join lastock as stock on stock.trno=head.trno
                                    left join client as warehouse on warehouse.client = head.wh
                                    left join item on item.itemid=stock.itemid
                                    left join client as swh on swh.clientid=stock.whid  $addjoin
                                    where doc='sp' and date(head.dateid) between '$start' and '$end' $whfilter $filter1 
                                

                                    union all

                                    select head.trno,head.docno,date(head.dateid) as dateid,head.clientname,head.address,
                                    warehouse.client as wh,
                                    warehouse.clientname as whname,   '' as dwhname, head.yourref, head.ourref,head.rem as note1,
                                    item.barcode,   item.itemname,  stock.uom, stock.qty,stock.amt,stock.disc,stock.ext,
                                    stock.ref, stock.whid,stock.rem as note2,  swh.client as wh2,
                                    swh.clientname as whname2 $addfield


                                    from glhead as head
                                    left join glstock as stock on stock.trno=head.trno
                                    left join client as warehouse on warehouse.clientid = head.whid
                                    left join item on item.itemid=stock.itemid
                                    left join client as swh on swh.clientid=stock.whid  $addjoin  
                                    where doc='sp' and date(head.dateid) between '$start' and '$end' $whfilter  $filter2 
                                     order by docno $sorting ";
                        break;
                }

                break;
            case 0: //summarized
                switch ($posttype) {
                    case 0: //posted
                        $query = "
                                select head.docno, warehouse.clientname as whname,date(head.dateid) as dateid,
                                head.yourref,head.ourref,head.rem,head.clientname,ifnull(dept.clientname,'') as deptname,
                                sum(stock.ext) as ext
                                $addfield
                                from glhead as head
                                 left join glstock as stock on stock.trno=head.trno
                                left join client as warehouse on warehouse.clientid = head.whid
                                 left join client as dept on dept.clientid = head.deptid
                                $addjoin
                                where doc='sp'  and date(head.dateid) between '$start' and '$end' $whfilter $filter1 $filter2 
                                $groupby
                                order by docno $sorting";

                        break;
                    case 1: //unposted
                        $query = "select head.docno,  warehouse.clientname as whname, date(head.dateid) as dateid,
                                   head.yourref,head.ourref,head.rem,head.clientname,ifnull(dept.clientname,'') as deptname,
                                    sum(stock.ext) as ext
                                    $addfield
                                    from lahead as head
                                    left join lastock as stock on stock.trno=head.trno
                                    left join client as warehouse on warehouse.client = head.wh
                                     left join client as dept on dept.clientid = head.deptid
                                    $addjoin
                                    where doc='sp'  and date(head.dateid) between '$start' and '$end' $whfilter $filter1 $filter2 
                                    $groupby
                                    order by docno $sorting";
                        break;
                    case 2: //all
                        $query = "select head.docno,  warehouse.clientname as whname, date(head.dateid) as dateid,
                                    head.yourref,head.ourref,head.rem,head.clientname,ifnull(dept.clientname,'') as deptname,
                                    sum(stock.ext) as ext
                                    $addfield
                                    from lahead as head
                                     left join lastock as stock on stock.trno=head.trno
                                    left join client as warehouse on warehouse.client = head.wh
                                     left join client as dept on dept.clientid = head.deptid
                                    $addjoin
                                    where doc='sp' and date(head.dateid) between '$start' and '$end' $whfilter $filter1 
                                     $groupby  
                                   

                                    union all

                                    select head.docno, warehouse.clientname as whname,date(head.dateid) as dateid,
                                    head.yourref,head.ourref,head.rem,head.clientname,ifnull(dept.clientname,'') as deptname,
                                    sum(stock.ext) as ext
                                    $addfield
                                    from glhead as head
                                     left join glstock as stock on stock.trno=head.trno
                                    left join client as warehouse on warehouse.clientid = head.whid
                                     left join client as dept on dept.clientid = head.deptid
                                    $addjoin
                                    where doc='sp' and date(head.dateid) between '$start' and '$end' $whfilter $filter2  $groupby
                                    order by docno $sorting";

                        break;
                }
                break;
        }



        return $query;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];

        if ($sorting == 'ASC') {
            $sorting = 'Ascending';
        } else {
            $sorting = 'Descending';
        }


        switch ($reporttype) {
            case 0: //summarized
                $reporttype = "Summarized";
                break;
            case 1: //detailed
                $reporttype = "Detailed";
                break;
        }

        switch ($posttype) {
            case 0:
                $posttype = 'Posted';
                break;

            case 1:
                $posttype = 'Unposted';
                break;

            default:
                $posttype = 'All';
                break;
        }

        if ($companyid == 16) { //ati
            $layoutsize = '1200';
        } else {
            $layoutsize = '1100';
        }

        $str = '';


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
        $str .= $this->reporter->col('Stock Return Report (' . $reporttype . ')', '550', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        if ($reporttype == 0) { //summarized
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '183', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Transaction Type: ' . $posttype, '184', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Sort by: ' . $sorting, '183', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Sort by: ' . $sorting, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
        }


        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $companyid  = $config['params']['companyid'];

        $count = 41;
        $page = 40;
        $this->reporter->linecounter = 0;

        $str = '';

        $layoutsize = '1100';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);

        $str .= $this->reporter->printline();
        $docno = "";
        $i = 0;
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {

                if ($docno != "" && $docno != $data->docno) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '600', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                } //end if

                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $total = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Doc#: ' . $data->docno, '367', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

                    if ($companyid == 16) { //ati
                        $str .= $this->reporter->col('SO No: ' . $data->sono, '367', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    } else {
                        $str .= $this->reporter->col('', '367', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    }
                    $str .= $this->reporter->col('Clientname: ' . $data->clientname, '366', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Warehouse: ' . $data->whname, '367', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    if ($companyid == 16) { //ati
                        $str .= $this->reporter->col('SA No: '  . $data->sano, '367', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    } else {
                        $str .= $this->reporter->col('', '367', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    }
                    $str .= $this->reporter->col('DATE: ' . $data->dateid, '366', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Itemname', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Quantity', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Amount', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Disc', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Warehouse', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Notes', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Ourref', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->qty, 2), '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amt, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->disc, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->whname2, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->note2, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                if ($docno == $data->docno) {
                    $total += $data->ext;
                }
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
                    $str .= $this->tableheader($layoutsize, $config);
                    $page = $page + $count;
                } //end if


                if ($i == (count((array)$result) - 1)) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    if ($companyid == 16) { //ati
                        $str .= $this->reporter->col('Total: ' . number_format($total, 2), '1200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->endrow();

                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '1200', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
                    } else {
                        $str .= $this->reporter->col('Total: ' . number_format($total, 2), '1100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->endrow();

                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '1100', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
                    }

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $count = 41;
        $page = 40;
        $this->reporter->linecounter = 0;

        $str = '';

        if ($companyid == 16) { //ati
            $layoutsize = '1200';
        } else {
            $layoutsize = '1100';
        }
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        $total = 0;
        $i = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->deptname, '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->whname, '200', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                if ($companyid == 16) { //ati
                    $str .= $this->reporter->col($data->sano, '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->sono, '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                }
                $str .= $this->reporter->col($data->yourref, '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ourref, '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();
                $total = $total + $data->ext;
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
                    $str .= $this->tableheader($layoutsize, $config);
                    $page = $page + $count;
                } //end if

                if ($i == (count((array)$result) - 1)) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();

                    if ($companyid = 16) { //ati
                        $str .= $this->reporter->col('', '900', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('Grand Total', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($total, 2), '300', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                    } else {
                        $str .= $this->reporter->col('', '700', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('Grand Total', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($total, 2), '300', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                    }

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }


    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Document No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Clientname', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Department', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse', '200', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        if ($companyid == 16) { //ati
            $str .= $this->reporter->col('SA No.', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('SO No.', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->col('Yourref', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Ourref', '50', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class