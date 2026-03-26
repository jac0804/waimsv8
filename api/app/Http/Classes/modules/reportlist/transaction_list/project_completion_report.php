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

class project_completion_report
{
    public $modulename = 'Project Completion Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'reportusers.lookupclass', 'user');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dcentername.required', true);

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
        return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '0' as clientid,
    '' as clientname,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as posttype,
    '0' as reporttype, 
    'ASC' as sorting,
    '' as center,'' as dcentername,
    '' as dclientname,'' as reportusers
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
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
        $clientid     = $config['params']['dataparams']['clientid'];
        $client   = $config['params']['dataparams']['client'];
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $fcenter    = $config['params']['dataparams']['center'];

        $filter = "";
        if ($filterusername != "") {
            $filter .= " and head.createby = '$filterusername' ";
        }
        if ($prefix != "") {
            $filter .= " and cnt.bref = '$prefix' ";
        }
        if ($client != "") {
            $filter .= " and client.clientid = '$clientid' ";
        }
        if ($fcenter != "") {
            $filter .= " and cnt.center = '$fcenter'";
        }

        switch ($reporttype) {
            case 0: // summarized
                switch ($posttype) {
                    case 0: // posted
                        $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.cost) as amt,
          wh.clientname, head.createby, left(head.dateid,10) as dateid, ifnull(project.name,'') as projectname, ph.code as phase, 
          hm.model as housemodel,bl.blk as blklot, bl.lot
          from glhead as head 
          left join glstock as stock on stock.trno = head.trno 
          left join item as item on item.itemid = stock.itemid 
          left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
          left join client as client on client.clientid = head.clientid 
          left join client as wh on wh.clientid = stock.whid 
          left join transnum as cnt on cnt.trno = head.trno
        
          left join projectmasterfile as project on project.line=head.projectid
          left join phase as ph on ph.line = head.phaseid
          left join housemodel as hm on hm.line = head.modelid
          left join blklot as bl on bl.line = head.blklotid
          where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,project.name,ph.code,hm.model,bl.blk,bl.lot
          order by head.docno $sorting";
                        break;

                    case 1: // unposted
                        $query = "
          select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.cost) as amt,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,ifnull(project.name,'') as projectname, ph.code as phase, 
          hm.model as housemodel,bl.blk as blklot, bl.lot
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join client as client on client.client = head.client
          left join item as item on item.itemid = stock.itemid
          left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
          left join client as wh on wh.clientid = stock.whid
          left join cntnum as cnt on cnt.trno = head.trno
    
          left join projectmasterfile as project on project.line=head.projectid
          left join phase as ph on ph.line = head.phaseid
          left join housemodel as hm on hm.line = head.modelid
          left join blklot as bl on bl.line = head.blklotid
          where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,project.name,ph.code,hm.model,bl.blk,bl.lot
          order by head.docno $sorting";


                        break;

                    default: // all
                        $query = "
          select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.cost) as amt,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,ifnull(project.name,'') as projectname, ph.code as phase, 
          hm.model as housemodel,bl.blk as blklot, bl.lot
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join client as client on client.client = head.client
          left join item as item on item.itemid = stock.itemid
          left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
          left join client as wh on wh.clientid = stock.whid
          left join cntnum as cnt on cnt.trno = head.trno
          left join projectmasterfile as project on project.line=head.projectid
          left join phase as ph on ph.line = head.phaseid
          left join housemodel as hm on hm.line = head.modelid
          left join blklot as bl on bl.line = head.blklotid
          where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter
          
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,project.name,ph.code,hm.model,bl.blk,bl.lot
         
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.cost) as amt,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,ifnull(project.name,'') as projectname, ph.code as phase, 
          hm.model as housemodel,bl.blk as blklot, bl.lot
          from glhead as head 
          left join glstock as stock on stock.trno = head.trno 
          left join item as item on item.itemid = stock.itemid 
          left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
          left join client as client on client.clientid = head.clientid
          left join client as wh on wh.clientid = stock.whid 
          left join cntnum as cnt on cnt.trno = head.trno
          
          left join projectmasterfile as project on project.line=head.projectid
          left join phase as ph on ph.line = head.phaseid
          left join housemodel as hm on hm.line = head.modelid
          left join blklot as bl on bl.line = head.blklotid
          where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,project.name,ph.code,hm.model,bl.blk,bl.lot
          
          order by docno $sorting";
                        break;
                } // end switch posttype
                break;

            case 1: // detailed
                switch ($posttype) {
                    case 0: // posted
                        $query = "
          select
          head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref, 
          head.yourref, 
          item.barcode, item.itemname, uom.uom, wh.clientname as wh, 
          stock.qty,
          ifnull(project.name,'') as projectname, ph.code as phase, 
          hm.model as housemodel,bl.blk as blklot, bl.lot, stock.rrcost as unitamt, stock.cost as amt
          from glhead as head 
          left join glstock as stock on stock.trno = head.trno 
          left join item as item on item.itemid = stock.itemid 
          left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
          left join client as client on client.clientid = head.clientid  
          left join client as wh on wh.clientid = stock.whid 
          left join cntnum as cnt on cnt.trno = head.trno
          left join projectmasterfile as project on project.line=head.projectid
         left join phase as ph on ph.line = head.phaseid
         left join housemodel as hm on hm.line = head.modelid
         left join blklot as bl on bl.line = head.blklotid
          where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter 
          order by head.docno $sorting";
                        break;

                    case 1: // unposted
                        $query = "
          select
          head.trno, head.docno, client.clientname,  date(head.dateid) as dateid, head.ourref,
          head.yourref,
          item.barcode,  item.itemname, uom.uom, wh.clientname as wh,
          stock.qty,
          ifnull(project.name,'') as projectname, ph.code as phase, 
          hm.model as housemodel,bl.blk as blklot, bl.lot, stock.rrcost as unitamt, stock.cost as amt
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join client as client on client.client = head.client
          left join item as item on item.itemid = stock.itemid
          left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
          left join client as wh on wh.clientid = stock.whid
          left join cntnum as cnt on cnt.trno = head.trno
          left join projectmasterfile as project on project.line=head.projectid
          left join phase as ph on ph.line = head.phaseid
          left join housemodel as hm on hm.line = head.modelid
          left join blklot as bl on bl.line = head.blklotid
          where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter 
          order by head.docno $sorting";
                        break;

                    default: // sana all
                        $query = "
            select
            head.trno, head.docno, client.clientname,  date(head.dateid) as dateid, head.ourref, 
            head.yourref, 
            item.barcode, item.itemname, uom.uom, wh.clientname as wh, 
            stock.qty,
            ifnull(project.name,'') as projectname, ph.code as phase, 
            hm.model as housemodel,bl.blk as blklot, bl.lot, stock.rrcost as unitamt, stock.cost as amt
            from glhead as head 
            left join glstock as stock on stock.trno = head.trno 
            left join item as item on item.itemid = stock.itemid 
            left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
            left join client as client on client.clientid = head.clientid
            left join client as wh on wh.clientid = stock.whid 
            left join cntnum as cnt on cnt.trno = head.trno
            left join projectmasterfile as project on project.line=head.projectid
            left join phase as ph on ph.line = head.phaseid
            left join housemodel as hm on hm.line = head.modelid
            left join blklot as bl on bl.line = head.blklotid
            where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select
            head.trno, head.docno, client.clientname,  date(head.dateid) as dateid, head.ourref,
            head.yourref, 
            item.barcode,item.itemname,uom.uom, wh.clientname as wh,
            stock.qty, 
            ifnull(project.name,'') as projectname, ph.code as phase, 
            hm.model as housemodel,bl.blk as blklot, bl.lot, stock.rrcost as unitamt, stock.cost as amt
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join client as client on client.client = head.client
            left join item as item on item.itemid = stock.itemid
            left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
            left join client as wh on wh.clientid = stock.whid
            left join cntnum as cnt on cnt.trno = head.trno
            left join projectmasterfile as project on project.line=head.projectid
            left join phase as ph on ph.line = head.phaseid
            left join housemodel as hm on hm.line = head.modelid
            left join blklot as bl on bl.line = head.blklotid
            where head.doc = 'PN' and date(head.dateid) between '$start' and '$end' $filter 
            order by docno $sorting
          ";
                        break;
                }
                break;
        }

        return $query;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $str = '';

        $layoutsize = '1300';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $docno = "";
        $i = 0;
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno != "" && $docno != $data->docno) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '30px', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $total = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();


                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Customer: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();

                    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Item Description', '240', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

                    $str .= $this->reporter->col('Project Name', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Phase', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('House Model', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Block & Lot', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->itemname, '240', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col(number_format($data->qty, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col(number_format($data->unitamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

                $str .= $this->reporter->col($data->projectname, '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->phase, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->housemodel, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->blklot . ' - ' . $data->lot, '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                if ($docno == $data->docno) {
                    $total += $data->amt;
                }
                $str .= $this->reporter->endtable();

                if ($i == (count((array)$result) - 1)) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '240', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '60', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');

                    $str .= $this->reporter->col(' ', '140', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(' ', '130', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');

                    $str .= $this->reporter->col('Total: ' . number_format($total, 2), '140', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
            }
        }
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
        $client     = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];
        $posttype   = $config['params']['dataparams']['posttype'];

        if ($sorting == 'ASC') {
            $sorting = 'Ascending';
        } else {
            $sorting = 'Descending';
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

        if ($reporttype == 0) {
            $reporttype = 'Summarized';
        } else {
            $reporttype = 'Detailed';
        }

        $str = '';


        if ($reporttype == 'Summarized') { //summarized
            $layoutsize = '1200';
        } else {
            $layoutsize = '1300'; //detailed
        }


        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if ($companyid == 3) { //conti
            $qry = "select name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username, $config);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        if ($filterusername != "") {
            $user = $filterusername;
        } else {
            $user = "ALL USERS";
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Project Completion Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, $border,  '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);
        $count = 38;
        $page = 40;
        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        $totalext = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->supplier, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->projectname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->phase, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->housemodel, '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->blklot . ' - ' . $data->lot, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->createby, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


                $totalext = $totalext + $data->amt;
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_DEFAULT($config);
                    $str .= $this->tableheader($layoutsize, $config);
                    $page = $page + $count;
                } //end if
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '140', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('TOTAL :', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function tableheader($layoutsize, $config)
    {

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";


        $layoutsize = '1200';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PROJECT NAME', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PHASE', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HOUSE MODEL', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BLOCK & LOT', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREATE BY', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class