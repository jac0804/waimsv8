<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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
use Illuminate\Support\Facades\URL;
use DateTime;
use DateInterval;
use DatePeriod;

use Illuminate\Support\Facades\Storage;
use TCPDF_FONTS;
// not yet  
class homeworks_sales_report
{
    public $modulename = 'Homeworks Sales Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'station_rep', 'ddeptname', 'dclientname', 'channel', 'ditemname'];
        //, 'radioreportitemstatus', 'station_rep'
        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'dbranchname.required', true);
        data_set($col1, 'station_rep.addedparams', ['branch']);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'channel.lookupclass', 'repchannellookup');
        data_set($col1, 'dclientname.lookupclass', 'wasupplier');

        data_set($col1, 'radioprint.options', [
            // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
        ]);

        $fields = ['radioreportitemstatus', 'radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioreporttype.label', 'Options');
        data_set(
            $col2,
            'radioreporttype.options',
            [
                ['label' => 'Detail Per Item', 'value' => '0', 'color' => 'orange'],
                ['label' => 'Summary Per Day', 'value' => '1', 'color' => 'orange'],
                ['label' => 'Vendor Summary Report', 'value' => '2', 'color' => 'orange'],
                ['label' => 'Detail Per Doc#', 'value' => '3', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Concession', 'value' => '4', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Consignment', 'value' => '5', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Outright', 'value' => '6', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Concess/Day', 'value' => '7', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Consign/Day', 'value' => '8', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Outright/Day', 'value' => '9', 'color' => 'orange']
            ]
        );

        data_set(
            $col2,
            'radioreportitemstatus.options',
            [
                ['label' => 'Active Items', 'value' => '(0)', 'color' => 'orange'],
                ['label' => 'Inactive Items', 'value' => '(1)', 'color' => 'orange'],
                ['label' => 'All Items', 'value' => '(0,1)', 'color' => 'orange']
            ]
        );

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,   
        left(now(),10) as end ,
        '(0,1)' as itemstatus,
         '0' as reporttype,
         '' as station_rep,
         '' as stationline,
         '0' as stationid,
        '' as stationname,
            0 as deptid,
            '' as ddeptname, 
            '' as dept,
            '' as deptname,
             '' as client,
             '0' as clientid,
        '' as clientname,
        '' as dclientname,
        '' as channel,
        '' as barcode,
        '' as itemname,
        '0' as itemid,
          '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername
        
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
        switch ($config['params']['dataparams']['reporttype']) {
            case '0': // Detail Per Item
                $str = $this->Detail_per_item($config);
                break;
            case '1': // Summary Per Day
                $str = $this->Summary_per_day($config);
                break;
            case '2': // Vendor Summary Report
                $str = $this->vendor_summary_report($config);
                break;
            case '3': // Detail Per Doc#
                $str = $this->detail_per_doc($config);
                break;
            case '4': // Vendor w/ Item Concession
            case '5': // Vendor w/ Item Consignment
            case '6': // Vendor w/ Item Outright
                $str = $this->vendor_with_item_concession($config);
                break;
            case '7': // Vendor w/ Item Concess/Day
            case '8': // Vendor w/ Item Consign/Day
            case '9': // Vendor w/ Item Outright/Day
                $str = $this->vendor_with_item_concess_day($config);
                break;
        }

        return $str;
    }


    public function reportDefault($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        // $branchid = $config['params']['dataparams']['branchid'];
        // $branchname = $config['params']['dataparams']['branchname'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $itemid   = $config['params']['dataparams']['itemid'];
        $barcode   = $config['params']['dataparams']['barcode'];

        $reptype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];
        $printtype = $config['params']['dataparams']['print'];

        $filter = "";
        $orderby = 'order by itemdesc,suppliername';

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }
        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }

        if ($client != "") {
            $filter .= " and supp.clientid = '$clientid'";
        }


        if ($barcode != "") {
            $filter .= " and item.itemid = '$itemid'";
        }

        switch ($printtype) {
            // case 'default':
            //     $query = "select head.dateid, head.docno, supp.client as supcode, supp.clientname as suppliername, item.barcode,
            //     item.itemname as itemdesc, (stock.iss - stock.qty) as totalsold,
            //     if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)) as srp,
            //     (if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as disc,
            //     if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)) as ext,
            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as totalcost,
            //     if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1) as comm1,

            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as grosspayable,
            //     info.terminalid, if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge) as cardcharge, info.comm2,
            //     (if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as grossap2,
            //     left(supp.client,3) as supp, info.bankrate,info.banktype,
            //     if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //     (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))  as percentage
            //     from glhead as head 
            //     left join glstock as stock on stock.trno = head.trno 
            //     left join item on item.itemid = stock.itemid
            //     left join cntnum as num on num.trno = head.trno
            //     left join client as supp on supp.clientid = item.supplier
            //     left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //     where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
            //     // var_dump($query);
            //     break;
            //  round((if(left(num. bref,3) = 'sjs', ((info.discamt) * -1), info.discamt )),2) as disc,
            //  round(if(left(num.bref,3)='sjs',info.discamt*-1,if(info.discamt<0,info.discamt*-1,info.discamt)),2) as disc,
            case 'default':
                $query = "select head.dateid, head.docno, supp.client as supcode, supp.clientname as suppliername, item.barcode,
                item.itemname as itemdesc, round(stock.iss - stock.qty, 2) as totalsold,
                round(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)),2) as srp,
               
                round(((info.discamt) * -1),2) as disc,

                round(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)),2) as ext,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as totalcost,
                round(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1),2) as comm1,

                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as grosspayable,
                info.terminalid, round(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge),2) as cardcharge, round(info.comm2,2) as comm2,
                round((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as grossap2,

                left(supp.client,3) as supp, info.bankrate,info.banktype,
                round(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)),2)  as percentage
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno 
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
                // var_dump($query);
                break;
            // case 'CSV':
            //     $query = "select head.dateid as `DATE`, supp.client as `SUPPLIER CODE`, supp.clientname as `SUPPLIER NAME`, item.barcode as `ITEM CODE`,
            //     item.itemname as `ITEM DESCRIPTION`, (stock.iss - stock.qty) as `TOTAL_SOLD`,
            //     if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)) as `SRP`,
            //     (if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as `DISC`,
            //     if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)) as `TTL_SRP`,
            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as `TTL_COST`,
            //     if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1) as `COM_1`,
            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as `GROSS_PAYABLE`,
            //      if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //     (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))  as `PERCENTAGE`,

            //     0 as 'QTY_3', 0 as 'MOS3' , 0 as 'QTY_6', 0 as 'MOS6',
            //     0 as 'QTY_9', 0 as 'MOS9_MOS12', 0 as 'QTY_F' , 0 as 'FINANCE_0', 0 as 'QTY_FR', 0 as 'FINANCE_REGULAR', 
            //     0 as 'QTY_D', 0 as 'DEBIT_STRAIGHT',

            //     (if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as `COM_2`, 0 as 'NET_PAYABLE',
            //     info.terminalid, if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge) as cardcharge,
            //     info.bankrate,info.banktype
            //     from glhead as head 
            //     left join glstock as stock on stock.trno = head.trno 
            //     left join item on item.itemid = stock.itemid
            //     left join cntnum as num on num.trno = head.trno
            //     left join client as supp on supp.clientid = item.supplier
            //     left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //     where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
            //     break;
            //round((if(left(num.bref,3) = 'sjs', ((info.discamt) *-1), info.discamt )),2) as `DISC`,

            case 'CSV':
                $query = "select head.dateid as `DATE`, supp.client as `SUPPLIER CODE`, supp.clientname as `SUPPLIER NAME`, item.barcode as `ITEM CODE`,
                item.itemname as `ITEM DESCRIPTION`, round(stock.iss - stock.qty,2) as `TOTAL_SOLD`,
                round(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)),2) as `SRP`,

                round(((info.discamt) * -1),2) as `DISC`,

                round(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)),2) as `TTL_SRP`,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as `TTL_COST`,
                round(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1),2) as `COM_1`,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as `GROSS_PAYABLE`,
                 round(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)),2)  as `PERCENTAGE`,
               
                round(0) as 'QTY_3', round(0) as 'MOS3' , round(0) as 'QTY_6', round(0) as 'MOS6',
                round(0) as 'QTY_9', round(0) as 'MOS9_MOS12', round(0) as 'QTY_F' , round(0) as 'FINANCE_0', 
                round(0) as 'QTY_FR', round(0) as 'FINANCE_REGULAR', 
                round(0) as 'QTY_D', round(0) as 'DEBIT_STRAIGHT',

                round((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as `COM_2`, round(0) as 'NET_PAYABLE',
                info.terminalid, round(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge),2) as cardcharge,
                info.bankrate,info.banktype
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno 
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    public function reportdatacsv($config)
    {
        $reptype = $config['params']['dataparams']['reporttype'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        switch ($reptype) {
            case '0':
                $data = $this->reportDefault($config);
                break;
            case '1':
                $data = $this->summaryperday($config);
                break;
            case '2':
                $data = $this->vendor_summaryqry($config);
                break;
            case '3':
                $data = $this->detailperdoc($config);
                break;
            case '4':
            case '5':
            case '6':
                $data = $this->concession($config);
                break;
            case '7':
            case '8':
            case '9':
                $data = $this->concessday($config);
                break;
        }
        switch ($reptype) {
            case '0':
                foreach ($data as $row => $value) {
                    $raw = $value->terminalid;
                    $parts = explode('~', $raw);
                    $firstpart = $parts[0];
                    switch ($firstpart) {
                        case 'CCASHALOALDI':
                        case 'CHOMECREDIT':
                        case 'CHOMECREDIT0':
                        case 'CCASHALO':
                        case 'DCASHALOALDI':
                        case 'DHOMECREDIT':
                        case 'DHOMECREDIT0':
                        case 'DCASHALO':
                        case 'CASHALOALDI':
                        case 'HOMECREDIT':
                        case 'HOMECREDIT0':
                        case 'CASHALO':
                            if ($value->bankrate != 0) {
                                $value->QTY_FR = $value->TOTAL_SOLD;
                                $value->FINANCE_REGULAR = $value->cardcharge;
                            } else {
                                // $zero = 'FINANCE_0%';
                                $value->QTY_F  = $value->TOTAL_SOLD;
                                $value->FINANCE_0 = $value->cardcharge;
                            }
                            break;
                        default:
                            if (!empty($value->banktype) && $value->cardcharge != 0) {
                                switch (strtoupper(str_replace(' ', '', $value->banktype))) {
                                    case '3MONS':
                                    case '3MOS':
                                        $value->QTY_3 = $value->TOTAL_SOLD;
                                        $value->MOS3 = $value->cardcharge;
                                        break;
                                     case '6MONS':
                                     case '6MOS':
                                        $value->QTY_6 =  $value->TOTAL_SOLD;
                                        $value->MOS6 = $value->cardcharge;
                                        break;
                                    case '9MONS':
                                    case '12MONS':
                                    case '9MOS':
                                    case '12MOS':
                                        $value->QTY_9 =  $value->TOTAL_SOLD;
                                        $value->MOS9_MOS12 =  $value->cardcharge;
                                        break;
                                    case 'STRAIGHT':
                                    case 'DEBIT':
                                    case 'AUBGCASH':
                                    case 'DEBIT/BANCNET':
                                        $value->QTY_D =  $value->TOTAL_SOLD;
                                        $value->DEBIT_STRAIGHT = $value->cardcharge;
                                        break;
                                }
                            }
                            break;
                    }
                    $grosspayable = $value->TTL_COST; //grosspayablr
                    $grossap2s = $value->COM_2; //com2
                    $charges = $grossap2s +  $value->MOS3 + $value->MOS6 +  $value->MOS9_MOS12 +  $value->DEBIT_STRAIGHT +  $value->FINANCE_REGULAR + $value->FINANCE_0;
                    $value->NET_PAYABLE =  $grosspayable  + $charges;

                    //number format
                    $value->TOTAL_SOLD = (float)$value->TOTAL_SOLD;
                    $value->SRP = (float)$value->SRP;
                    $value->DISC = (float)$value->DISC;
                    $value->TTL_SRP = (float)$value->TTL_SRP;
                    $value->TTL_COST = (float)$value->TTL_COST;
                    $value->COM_1 = (float)$value->COM_1;
                    $value->GROSS_PAYABLE = (float)$value->GROSS_PAYABLE;
                    $value->PERCENTAGE = (float)$value->PERCENTAGE;

                    $value->MOS3 = (float)$value->MOS3;
                    $value->QTY_3 = (float)$value->QTY_3;
                    $value->MOS6 = (float)$value->MOS6;
                    $value->QTY_6 = (float)$value->QTY_6;
                    $value->MOS9_MOS12 = (float)$value->MOS9_MOS12;
                    $value->QTY_9 = (float)$value->QTY_9;
                    $value->DEBIT_STRAIGHT = (float)$value->DEBIT_STRAIGHT;
                    $value->QTY_D = (float)$value->QTY_D;
                    $value->FINANCE_REGULAR = (float)$value->FINANCE_REGULAR;
                    $value->QTY_FR = (float)$value->QTY_FR;
                    $value->FINANCE_0 = (float)$value->FINANCE_0;
                    $value->QTY_F = (float)$value->QTY_F;
                    $value->NET_PAYABLE = (float)$value->NET_PAYABLE;
                    $value->COM_2 = (float)$value->COM_2;
                    unset($value->terminalid);
                    unset($value->cardcharge);
                    unset($value->bankrate);
                    unset($value->banktype);
                }
                break;
            case '1': //SUMMARY PER DAY
                $difference = $this->diff($start, $end);
                $dates = [];
                for ($i = 0; $i <= $difference; $i++) {
                    $dates[] = date("Y-m-d", strtotime("+$i days", strtotime($start)));
                } //kinukuha lahat ng date sa start at end na date range 

                $groupedData = [];
                foreach ($data as $v) {
                    $groupedData[$v->DATE][] = $v;
                }

                $allData = [];
                foreach ($dates as $datehere) {
                    if (empty($groupedData[$datehere])) {
                        continue; // Skip dates with no data
                    }

                    $totalSold = $totaldisc = $totalsrp = $totalcost = $totalcom1 = $totalgros = $totalpercent = $totalcom2 = 0;
                    $tlqty_fr = $tlqty_z = $tlqty3 = $tlqty6 = $tlqty9 = $tlqtystdb = 0;
                    $tlfr = $tlz = $tlmos3 = $tlmos6 = $tlmos9 = $tlstdb = 0;
                    foreach ($groupedData[$datehere] as $transaction) {
                        $totalSold += $transaction->TTL_SOLD;
                        $totaldisc += $transaction->DISC;
                        $totalsrp += $transaction->TTL_SRP;
                        $totalcost += $transaction->TTL_COST;
                        $totalcom1 += $transaction->COM_1;
                        $totalgros += $transaction->GROSS_PAYABLE;
                        $totalpercent += $transaction->PERCENTAGE;
                        $totalcom2 += $transaction->COM_2;

                        //  CHARGES 
                        $raw = $transaction->terminalid;
                        $parts = explode('~', $raw);
                        $firstpart = $parts[0];
                        $banktype = $transaction->banktype;
                        $bankrate = $transaction->bankrate;
                        $cardcharge = $transaction->cardcharge;

                        switch ($firstpart) {
                            case 'CCASHALOALDI':
                            case 'CHOMECREDIT':
                            case 'CHOMECREDIT0':
                            case 'CCASHALO':
                            case 'DCASHALOALDI':
                            case 'DHOMECREDIT':
                            case 'DHOMECREDIT0':
                            case 'DCASHALO':
                            case 'CASHALOALDI':
                            case 'HOMECREDIT':
                            case 'HOMECREDIT0':
                            case 'CASHALO':

                                if ($bankrate != 0) {
                                    $tlqty_fr += $transaction->TTL_SOLD;
                                    $tlfr += $transaction->cardcharge;
                                } else {
                                    $tlqty_z  += $transaction->TTL_SOLD;
                                    $tlz += $transaction->cardcharge;
                                }
                                break;
                            default:
                                if (!empty($banktype) && $cardcharge != 0) {
                                    switch (strtoupper(str_replace(' ', '', $transaction->banktype))) {
                                         case '3MONS':
                                         case '3MOS':
                                            $tlqty3 += $transaction->TTL_SOLD;
                                            $tlmos3 += $transaction->cardcharge;
                                            break;
                                        case '6MONS':
                                        case '6MOS':  
                                            $tlqty6 +=  $transaction->TTL_SOLD;
                                            $tlmos6 += $transaction->cardcharge;
                                            break;
                                        case '9MONS':
                                        case '12MONS':
                                        case '9MOS':
                                        case '12MOS':
                                            $tlqty9 +=  $transaction->TTL_SOLD;
                                            $tlmos9 +=  $transaction->cardcharge;
                                            break;
                                        case 'STRAIGHT':
                                        case 'DEBIT':
                                        case 'AUBGCASH':
                                        case 'DEBIT/BANCNET':
                                            $tlqtystdb +=  $transaction->TTL_SOLD;
                                            $tlstdb += $transaction->cardcharge;
                                            break;
                                    }
                                }
                                break;
                        }
                    }


                    $charges = $totalcom2 + $tlmos3 + $tlmos6 + $tlmos9 + $tlstdb + $tlfr + $tlz;

                    $netpayable =  $totalgros + $charges;

                    $allData[] = [
                        'DATE' => $datehere,
                        'TTL_SOLD' =>  (float)$totalSold,
                        'DISC' =>  (float)$totaldisc,
                        'TTL_SRP' =>  (float)$totalsrp,
                        'TTL_COST' =>  (float)$totalcost,
                        'COM_1' =>  (float)$totalcom1,
                        'GROSS_PAYABLE' =>  (float)$totalgros,
                        'PERCENTAGE' =>  (float)$totalpercent,

                        'QTY_3' =>  (float)$tlqty3,
                        'MOS3' =>  (float)$tlmos3,
                        'QTY_6' =>  (float)$tlqty6,
                        'MOS6' =>  (float)$tlmos6,
                        'QTY_9' =>  (float)$tlqty9,
                        'MOS9_MOS12' =>  (float)$tlmos9,

                        'QTY_F' =>  (float)$tlqty_z,
                        'FINANCE_0' =>  (float)$tlz,
                        'QTY_FR' =>  (float)$tlqty_fr,
                        'FINANCE_REGULAR' =>  (float)$tlfr,
                        'QTY_D' =>  (float)$tlqtystdb,
                        'DEBIT_STRAIGHT' =>  (float)$tlstdb,

                        'COM_2' =>  (float)$totalcom2,
                        'NET_PAYABLE' =>  (float)$netpayable

                    ];
                }

                $data = array_map(function ($value) {
                    return [
                        'DATE' => $value['DATE'],
                        'TTL_SOLD' => $value['TTL_SOLD'],
                        'DISC' => $value['DISC'],
                        'TTL_SRP' => $value['TTL_SRP'],
                        'TTL_COST' => $value['TTL_COST'],
                        'COM_1' => $value['COM_1'],
                        'GROSS_PAYABLE' => $value['GROSS_PAYABLE'],
                        'PERCENTAGE' => $value['PERCENTAGE'],

                        'QTY_3' => $value['QTY_3'],
                        'MOS3' => $value['MOS3'],
                        'QTY_6' => $value['QTY_6'],
                        'MOS6' =>  $value['MOS6'],
                        'QTY_9' =>  $value['QTY_9'],
                        'MOS9_MOS12' => $value['MOS9_MOS12'],

                        'QTY_F' => $value['QTY_F'],
                        'FINANCE_0' =>  $value['FINANCE_0'],
                        'QTY_FR' => $value['QTY_FR'],
                        'FINANCE_REGULAR' => $value['FINANCE_REGULAR'],
                        'QTY_D' => $value['QTY_D'],
                        'DEBIT_STRAIGHT' => $value['DEBIT_STRAIGHT'],
                        'COM_2' =>  $value['COM_2'],
                        'NET_PAYABLE' =>  $value['NET_PAYABLE']
                    ];
                }, $allData);

                break;
            case '2': //vendor summary
                $suppl = $this->supp($config);
                $supplier = [];
                foreach ($suppl as $supply) {
                    $supplier[] = $supply->suppcode;
                }

                $groupedData = [];
                foreach ($data as $f) {
                    $groupedData[$f->CHANNEL][$f->filter][] = $f;
                }

                foreach ($groupedData as $channel => $Group) {
                    foreach ($supplier as $suppliers) {
                        if (empty($Group[$suppliers])) continue;

                        $totalsold = $totaldisc = $totalsrp  = $totalcom1 = $totalgros = $totalpercent = $totalcom2 = 0;
                        $tlqty_fr = $tlqty_z = $tlqty3 = $tlqty6 = $tlqty9 = $tlqtystdb = 0;
                        $tlfr = $tlz = $tlmos3 = $tlmos6 = $tlmos9 = $tlstdb = 0;
                        $srp = 0;

                        foreach ($Group[$suppliers] as $transaction) {
                            $totalsold += $transaction->TTL_SOLD;
                            $srp += $transaction->SRP;
                            $totaldisc += $transaction->DISC;
                            $totalsrp += $transaction->TTL_SRP;
                            $totalcom2 += $transaction->COM_2;
                            $totalgros += $transaction->GROSS_PAYABLE;
                            $totalcom1 += $transaction->COM_1;
                            // CHARGES 
                            $raw = $transaction->terminalid;
                            $parts = explode('~', $raw);
                            $firstpart = $parts[0];
                            $banktype = $transaction->banktype;
                            $bankrate = $transaction->bankrate;
                            $cardcharge = $transaction->cardcharge;
                            $branch = $transaction->BRANCH;
                            $supcode = $transaction->SUPPLIER_CODE;
                            $supname = $transaction->SUPPLIER_NAME;

                            switch ($firstpart) {
                                case 'CCASHALOALDI':
                                case 'CHOMECREDIT':
                                case 'CHOMECREDIT0':
                                case 'CCASHALO':
                                case 'DCASHALOALDI':
                                case 'DHOMECREDIT':
                                case 'DHOMECREDIT0':
                                case 'DCASHALO':
                                case 'CASHALOALDI':
                                case 'HOMECREDIT':
                                case 'HOMECREDIT0':
                                case 'CASHALO':
                                    // case 'CAUGBGCASH':
                                    if ($bankrate != 0) {
                                        $tlqty_fr += $transaction->TTL_SOLD;
                                        $tlfr += $transaction->cardcharge;
                                    } else {
                                        $tlqty_z  += $transaction->TTL_SOLD;
                                        $tlz += $transaction->cardcharge;
                                    }
                                    break;
                                default:
                                    if (!empty($banktype) && $cardcharge != 0) {
                                        switch (strtoupper(str_replace(' ', '', $transaction->banktype))) {
                                            case '3MONS':
                                            case '3MOS':
                                                $tlqty3 += $transaction->TTL_SOLD;
                                                $tlmos3 += $transaction->cardcharge;
                                                break;
                                            case '6MONS':
                                            case '6MOS':  
                                                $tlqty6 +=  $transaction->TTL_SOLD;
                                                $tlmos6 += $transaction->cardcharge;
                                                break;
                                            case '9MONS':
                                            case '12MONS':
                                            case '9MOS':
                                            case '12MOS':
                                                $tlqty9 +=  $transaction->TTL_SOLD;
                                                $tlmos9 +=  $transaction->cardcharge;
                                                break;
                                            case 'STRAIGHT':
                                            case 'DEBIT':
                                            case 'AUBGCASH':
                                            case 'DEBIT/BANCNET':
                                                $tlqtystdb +=  $transaction->TTL_SOLD;
                                                $tlstdb += $transaction->cardcharge;
                                                break;
                                        }
                                    }
                                    break;
                            }
                        }
                        $charges = $totalcom2 + $tlmos3 + $tlmos6 + $tlmos9 + $tlstdb + $tlfr + $tlz;

                        $netpayable =  $totalgros + $charges;
                        $allData[] = [
                            'CHANNEL' => $channel,
                            'BRANCH' => $branch,
                            'SUPPLIER_CODE' => $supcode,
                            'SUPPLIER_NAME' => $supname,
                            'TTL_SOLD' => (float)$totalsold,
                            'SRP' => (float)$srp,
                            'DISC' => (float)$totaldisc,
                            'TTL_SRP' => (float)$totalsrp,
                            'COM_1' => (float)$totalcom1,
                            'GROSS_PAYABLE' => (float)$totalgros,

                            'QTY_3' => (float)$tlqty3,
                            'MOS3' => (float)$tlmos3,
                            'QTY_6' => (float)$tlqty6,
                            'MOS6' => (float)$tlmos6,
                            'QTY_9' => (float)$tlqty9,
                            'MOS9_MOS12' => (float)$tlmos9,

                            'QTY_F' => (float)$tlqty_z,
                            'FINANCE_0' => (float)$tlz,
                            'QTY_FR' => (float)$tlqty_fr,
                            'FINANCE_REGULAR' => (float)$tlfr,
                            'QTY_D' => (float)$tlqtystdb,
                            'DEBIT_STRAIGHT' => (float)$tlstdb,

                            'COM_2' => (float)$totalcom2,
                            'NET_PAYABLE' => (float)$netpayable

                        ];
                    }
                }
                $data = array_map(function ($value) {
                    return [
                        // 'DATE' => $value['DATE'],
                        'CHANNEL' => $value['CHANNEL'],
                        'BRANCH' => $value['BRANCH'],
                        'SUPPLIER_CODE' => $value['SUPPLIER_CODE'],
                        'SUPPLIER_NAME' =>  $value['SUPPLIER_NAME'],
                        'TTL_SOLD' => $value['TTL_SOLD'],
                        'SRP' => $value['SRP'],
                        'DISC' => $value['DISC'],
                        'TTL_SRP' => $value['TTL_SRP'],
                        'COM_1' => $value['COM_1'],
                        'GROSS_PAYABLE' => $value['GROSS_PAYABLE'],

                        'QTY_3' => $value['QTY_3'],
                        'MOS3' => $value['MOS3'],
                        'QTY_6' => $value['QTY_6'],
                        'MOS6' =>  $value['MOS6'],
                        'QTY_9' =>  $value['QTY_9'],
                        'MOS9_MOS12' => $value['MOS9_MOS12'],

                        'QTY_F' => $value['QTY_F'],
                        'FINANCE_0' =>  $value['FINANCE_0'],
                        'QTY_FR' => $value['QTY_FR'],
                        'FINANCE_REGULAR' => $value['FINANCE_REGULAR'],
                        'QTY_D' => $value['QTY_D'],
                        'DEBIT_STRAIGHT' => $value['DEBIT_STRAIGHT'],
                        'COM_2' =>  $value['COM_2'],
                        'NET_PAYABLE' =>  $value['NET_PAYABLE']
                    ];
                }, $allData);

                break;
            case '3': //detail per doc
                foreach ($data as $row => $value) {
                    $raw = $value->terminalid;
                    $parts = explode('~', $raw);
                    $firstpart = $parts[0];
                    switch ($firstpart) {
                        case 'CCASHALOALDI':
                        case 'CHOMECREDIT':
                        case 'CHOMECREDIT0':
                        case 'CCASHALO':
                        case 'DCASHALOALDI':
                        case 'DHOMECREDIT':
                        case 'DHOMECREDIT0':
                        case 'DCASHALO':
                        case 'CASHALOALDI':
                        case 'HOMECREDIT':
                        case 'HOMECREDIT0':
                        case 'CASHALO':
                            // case 'CAUGBGCASH':
                            if ($value->bankrate != 0) {
                                $value->QTY_FR = $value->TTL_SOLD;
                                $value->FINANCE_REGULAR = $value->cardcharge;
                            } else {
                                // $zero = 'FINANCE_0%';
                                $value->QTY_F  = $value->TTL_SOLD;
                                $value->FINANCE_0 = $value->cardcharge;
                            }
                            break;
                        default:
                            if (!empty($value->banktype) && $value->cardcharge != 0) {
                                switch (strtoupper(str_replace(' ', '', $value->banktype))) {
                                    case '3MONS':
                                    case '3MOS':
                                        $value->QTY_3 = $value->TTL_SOLD;
                                        $value->MOS3 = $value->cardcharge;
                                        break;
                                  case '6MONS':
                                  case '6MOS':
                                        $value->QTY_6 =  $value->TTL_SOLD;
                                        $value->MOS6 = $value->cardcharge;
                                        break;
                                    case '9MONS':
                                    case '12MONS':
                                    case '9MOS':
                                    case '12MOS':
                                        $value->QTY_9 =  $value->TTL_SOLD;
                                        $value->MOS9_MOS12 =  $value->cardcharge;
                                        break;
                                    case 'STRAIGHT':
                                    case 'DEBIT':
                                    case 'AUBGCASH':
                                    case 'DEBIT/BANCNET':
                                        $value->QTY_D =  $value->TTL_SOLD;
                                        $value->DEBIT_STRAIGHT = $value->cardcharge;
                                        break;
                                }
                            }
                            break;
                    }
                    $grosspayable = $value->TTL_COST; //grosspayablr
                    $grossap2s = $value->COM_2; //com2
                    $charges = $grossap2s +  $value->MOS3 + $value->MOS6 +  $value->MOS9_MOS12 +  $value->DEBIT_STRAIGHT +  $value->FINANCE_REGULAR + $value->FINANCE_0;
                    $value->NET_PAYABLE =  $grosspayable  + $charges;

                    //number format
                    $value->TTL_SOLD = (float)$value->TTL_SOLD;
                    $value->SRP = (float)$value->SRP;
                    $value->DISC = (float)$value->DISC;
                    $value->TTL_SRP = (float)$value->TTL_SRP;
                    $value->TTL_COST = (float)$value->TTL_COST;
                    $value->COM_1 = (float)$value->COM_1;
                    $value->GROSS_PAYABLE = (float)$value->GROSS_PAYABLE;
                    $value->PERCENTAGE = (float)$value->PERCENTAGE;

                    $value->MOS3 = (float)$value->MOS3;
                    $value->QTY_3 = (float)$value->QTY_3;
                    $value->MOS6 = (float)$value->MOS6;
                    $value->QTY_6 = (float)$value->QTY_6;
                    $value->MOS9_MOS12 = (float)$value->MOS9_MOS12;
                    $value->QTY_9 = (float)$value->QTY_9;
                    $value->DEBIT_STRAIGHT = (float)$value->DEBIT_STRAIGHT;
                    $value->QTY_D = (float)$value->QTY_D;
                    $value->FINANCE_REGULAR = (float)$value->FINANCE_REGULAR;
                    $value->QTY_FR = (float)$value->QTY_FR;
                    $value->FINANCE_0 = (float)$value->FINANCE_0;
                    $value->QTY_F = (float)$value->QTY_F;
                    $value->NET_PAYABLE = (float)$value->NET_PAYABLE;
                    $value->COM_2 = (float)$value->COM_2;
                    unset($value->terminalid);
                    unset($value->cardcharge);
                    unset($value->bankrate);
                    unset($value->banktype);
                }
                break;
            case '4': //vendor with item concession
                foreach ($data as $row => $value) {
                    $raw = $value->terminalid;
                    $parts = explode('~', $raw);
                    $firstpart = $parts[0];
                    switch ($firstpart) {
                        case 'CCASHALOALDI':
                        case 'CHOMECREDIT':
                        case 'CHOMECREDIT0':
                        case 'CCASHALO':
                        case 'DCASHALOALDI':
                        case 'DHOMECREDIT':
                        case 'DHOMECREDIT0':
                        case 'DCASHALO':
                        case 'CASHALOALDI':
                        case 'HOMECREDIT':
                        case 'HOMECREDIT0':
                        case 'CASHALO':
                            // case 'CAUGBGCASH':
                            if ($value->bankrate != 0) {
                                $value->QTY_FR = $value->TTL_SOLD;
                                $value->FINANCE_REGULAR = $value->cardcharge;
                            } else {
                                // $zero = 'FINANCE_0%';
                                $value->QTY_F  = $value->TTL_SOLD;
                                $value->FINANCE_0 = $value->cardcharge;
                            }
                            break;
                        default:
                            if (!empty($value->banktype) && $value->cardcharge != 0) {
                                switch (strtoupper(str_replace(' ', '', $value->banktype))) {
                                    case '3MONS':
                                    case '3MOS':
                                        $value->QTY_3 = $value->TTL_SOLD;
                                        $value->MOS3 = $value->cardcharge;
                                        break;
                                    case '6MONS':
                                    case '6MOS': 
                                        $value->QTY_6 =  $value->TTL_SOLD;
                                        $value->MOS6 = $value->cardcharge;
                                        break;
                                    case '9MONS':
                                    case '12MONS':
                                    case '9MOS':
                                    case '12MOS':
                                        $value->QTY_9 =  $value->TTL_SOLD;
                                        $value->MOS9_MOS12 =  $value->cardcharge;
                                        break;
                                    case 'STRAIGHT':
                                    case 'DEBIT':
                                    case 'AUBGCASH':
                                    case 'DEBIT/BANCNET':
                                        $value->QTY_D =  $value->TTL_SOLD;
                                        $value->DEBIT_STRAIGHT = $value->cardcharge;
                                        break;
                                }
                            }
                            break;
                    }
                    $grosspayable = $value->GROSS_PAYABLE; //grosspayablr
                    $grossap2s = $value->COM_2; //com2
                    $charges = $grossap2s +  $value->MOS3 + $value->MOS6 +  $value->MOS9_MOS12 +  $value->DEBIT_STRAIGHT +  $value->FINANCE_REGULAR + $value->FINANCE_0;
                    $value->NET_PAYABLE =  $grosspayable  + $charges;

                    //number format
                    $value->TTL_SOLD = (float)$value->TTL_SOLD;
                    $value->SRP = (float)$value->SRP;
                    $value->DISC = (float)$value->DISC;
                    $value->TTL_SRP = (float)$value->TTL_SRP;
                    $value->COM_1 = (float)$value->COM_1;
                    $value->GROSS_PAYABLE = (float)$value->GROSS_PAYABLE;

                    $value->MOS3 = (float)$value->MOS3;
                    $value->QTY_3 = (float)$value->QTY_3;
                    $value->MOS6 = (float)$value->MOS6;
                    $value->QTY_6 = (float)$value->QTY_6;
                    $value->MOS9_MOS12 = (float)$value->MOS9_MOS12;
                    $value->QTY_9 = (float)$value->QTY_9;
                    $value->DEBIT_STRAIGHT = (float)$value->DEBIT_STRAIGHT;
                    $value->QTY_D = (float)$value->QTY_D;
                    $value->FINANCE_REGULAR = (float)$value->FINANCE_REGULAR;
                    $value->QTY_FR = (float)$value->QTY_FR;
                    $value->FINANCE_0 = (float)$value->FINANCE_0;
                    $value->QTY_F = (float)$value->QTY_F;
                    $value->NET_PAYABLE = (float)$value->NET_PAYABLE;
                    $value->COM_2 = (float)$value->COM_2;
                    unset($value->terminalid);
                    unset($value->cardcharge);
                    unset($value->bankrate);
                    unset($value->banktype);
                    unset($value->COST);
                    unset($value->filter);
                }
                break;
            case '5': //item consignment
            case '6': //outright
                foreach ($data as $row => $value) {
                    $raw = $value->terminalid;
                    $parts = explode('~', $raw);
                    $firstpart = $parts[0];
                    switch ($firstpart) {
                        case 'CCASHALOALDI':
                        case 'CHOMECREDIT':
                        case 'CHOMECREDIT0':
                        case 'CCASHALO':
                        case 'DCASHALOALDI':
                        case 'DHOMECREDIT':
                        case 'DHOMECREDIT0':
                        case 'DCASHALO':
                        case 'CASHALOALDI':
                        case 'HOMECREDIT':
                        case 'HOMECREDIT0':
                        case 'CASHALO':
                            // case 'CAUGBGCASH':
                            if ($value->bankrate != 0) {
                                $value->QTY_FR = $value->TTL_SOLD;
                                $value->FINANCE_REGULAR = $value->cardcharge;
                            } else {
                                // $zero = 'FINANCE_0%';
                                $value->QTY_F  = $value->TTL_SOLD;
                                $value->FINANCE_0 = $value->cardcharge;
                            }
                            break;
                        default:
                            if (!empty($value->banktype) && $value->cardcharge != 0) {
                                switch (strtoupper(str_replace(' ', '', $value->banktype))) {
                                    case '3MONS':
                                    case '3MOS':
                                        $value->QTY_3 = $value->TTL_SOLD;
                                        $value->MOS3 = $value->cardcharge;
                                        break;
                                    case '6MONS':
                                    case '6MOS':    
                                        $value->QTY_6 =  $value->TTL_SOLD;
                                        $value->MOS6 = $value->cardcharge;
                                        break;
                                    case '9MONS':
                                    case '12MONS':
                                    case '9MOS':
                                    case '12MOS':
                                        $value->QTY_9 =  $value->TTL_SOLD;
                                        $value->MOS9_MOS12 =  $value->cardcharge;
                                        break;
                                    case 'STRAIGHT':
                                    case 'DEBIT':
                                    case 'AUBGCASH':
                                    case 'DEBIT/BANCNET':
                                        $value->QTY_D =  $value->TTL_SOLD;
                                        $value->DEBIT_STRAIGHT = $value->cardcharge;
                                        break;
                                }
                            }
                            break;
                    }
                    $grosspayable = $value->GROSS_PAYABLE; //grosspayablr
                    $grossap2s = $value->COM_2; //com2
                    $charges = $grossap2s +  $value->MOS3 + $value->MOS6 +  $value->MOS9_MOS12 +  $value->DEBIT_STRAIGHT +  $value->FINANCE_REGULAR + $value->FINANCE_0;
                    $value->NET_PAYABLE =  $grosspayable  + $charges;

                    //number format
                    $value->TTL_SOLD = (float)$value->TTL_SOLD;
                    $value->SRP = (float)$value->SRP;
                    $value->DISC = (float)$value->DISC;
                    $value->TTL_SRP = (float)$value->TTL_SRP;
                    $value->COST = (float)$value->COST;
                    $value->GROSS_PAYABLE = (float)$value->GROSS_PAYABLE;

                    $value->MOS3 = (float)$value->MOS3;
                    $value->QTY_3 = (float)$value->QTY_3;
                    $value->MOS6 = (float)$value->MOS6;
                    $value->QTY_6 = (float)$value->QTY_6;
                    $value->MOS9_MOS12 = (float)$value->MOS9_MOS12;
                    $value->QTY_9 = (float)$value->QTY_9;
                    $value->DEBIT_STRAIGHT = (float)$value->DEBIT_STRAIGHT;
                    $value->QTY_D = (float)$value->QTY_D;
                    $value->FINANCE_REGULAR = (float)$value->FINANCE_REGULAR;
                    $value->QTY_FR = (float)$value->QTY_FR;
                    $value->FINANCE_0 = (float)$value->FINANCE_0;
                    $value->QTY_F = (float)$value->QTY_F;
                    $value->NET_PAYABLE = (float)$value->NET_PAYABLE;
                    $value->COM_2 = (float)$value->COM_2;
                    unset($value->terminalid);
                    unset($value->cardcharge);
                    unset($value->bankrate);
                    unset($value->banktype);
                    unset($value->COM_1);
                    unset($value->filter);
                }
                break;
            case '7': //concess day
                $difference = $this->diff($start, $end);

                $dates = [];
                for ($i = 0; $i <= $difference; $i++) {
                    $dates[] = date("Y-m-d", strtotime("+$i days", strtotime($start)));
                }
                $groupedData = [];
                foreach ($data as $cs) {
                    $groupedData[$cs->SUPPLIER_CODE][$cs->DATE][] = $cs;
                }

                $allData = [];

                foreach ($groupedData as $supplier => $dateGroup) {
                    foreach ($dates as $date) {
                        if (empty($dateGroup[$date])) continue;


                        $totalSold = $totaldisc = $totalsrp  = $totalcom1 = $totalgros = $totalpercent = $totalcom2 = 0;
                        $tlqty_fr = $tlqty_z = $tlqty3 = $tlqty6 = $tlqty9 = $tlqtystdb = 0;
                        $tlfr = $tlz = $tlmos3 = $tlmos6 = $tlmos9 = $tlstdb = 0;
                        foreach ($dateGroup[$date] as $transaction) {
                            $totalSold += $transaction->TTL_SOLD;
                            $totaldisc += $transaction->DISC;
                            $totalsrp += $transaction->TTL_SRP;
                            $totalcom1 += $transaction->COM_1;
                            $totalgros += $transaction->GROSS_PAYABLE;
                            $totalcom2 += $transaction->COM_2;

                            //  CHARGES 
                            $raw = $transaction->terminalid;
                            $parts = explode('~', $raw);
                            $firstpart = $parts[0];
                            $banktype = $transaction->banktype;
                            $bankrate = $transaction->bankrate;
                            $cardcharge = $transaction->cardcharge;
                            $supcode = $transaction->SUPPLIER_CODE;
                            $supname = $transaction->SUPPLIER_NAME;

                            switch ($firstpart) {
                                case 'CCASHALOALDI':
                                case 'CHOMECREDIT':
                                case 'CHOMECREDIT0':
                                case 'CCASHALO':
                                case 'DCASHALOALDI':
                                case 'DHOMECREDIT':
                                case 'DHOMECREDIT0':
                                case 'DCASHALO':
                                case 'CASHALOALDI':
                                case 'HOMECREDIT':
                                case 'HOMECREDIT0':
                                case 'CASHALO':
                                    // case 'CAUGBGCASH':
                                    if ($bankrate != 0) {
                                        $tlqty_fr += $transaction->TTL_SOLD;
                                        $tlfr += $transaction->cardcharge;
                                    } else {
                                        $tlqty_z  += $transaction->TTL_SOLD;
                                        $tlz += $transaction->cardcharge;
                                    }
                                    break;
                                default:
                                    if (!empty($banktype) && $cardcharge != 0) {
                                        switch (strtoupper(str_replace(' ', '', $transaction->banktype))) {
                                            case '3MONS':
                                            case '3MOS':
                                                $tlqty3 += $transaction->TTL_SOLD;
                                                $tlmos3 += $transaction->cardcharge;
                                                break;
                                            case '6MONS':
                                            case '6MOS':
                                                $tlqty6 +=  $transaction->TTL_SOLD;
                                                $tlmos6 += $transaction->cardcharge;
                                                break;
                                            case '9MONS':
                                            case '12MONS':
                                            case '9MOS':
                                            case '12MOS':
                                                $tlqty9 +=  $transaction->TTL_SOLD;
                                                $tlmos9 +=  $transaction->cardcharge;
                                                break;
                                            case 'STRAIGHT':
                                            case 'DEBIT':
                                            case 'AUBGCASH':
                                            case 'DEBIT/BANCNET':    
                                                $tlqtystdb +=  $transaction->TTL_SOLD;
                                                $tlstdb += $transaction->cardcharge;
                                                break;
                                        }
                                    }
                                    break;
                            }
                        }


                        $charges = $totalcom2 + $tlmos3 + $tlmos6 + $tlmos9 + $tlstdb + $tlfr + $tlz;

                        $netpayable =  $totalgros + $charges;

                        $allData[] = [
                            'SUPPLIER_CODE' => $supcode,
                            'SUPPLIER_NAME' => $supname,
                            'DATE' => $date,
                            'TTL_SOLD' => (float)$totalSold,
                            'DISC' => (float)$totaldisc,
                            'TTL_SRP' => (float)$totalsrp,
                            'COM_1' => (float)$totalcom1,
                            'GROSS_PAYABLE' => (float)$totalgros,

                            'QTY_3' => (float)$tlqty3,
                            'MOS3' => (float)$tlmos3,
                            'QTY_6' => (float)$tlqty6,
                            'MOS6' => (float)$tlmos6,
                            'QTY_9' => (float)$tlqty9,
                            'MOS9_MOS12' => (float)$tlmos9,

                            'QTY_F' => (float)$tlqty_z,
                            'FINANCE_0' => (float)$tlz,
                            'QTY_FR' => (float)$tlqty_fr,
                            'FINANCE_REGULAR' => (float)$tlfr,
                            'QTY_D' => (float)$tlqtystdb,
                            'DEBIT_STRAIGHT' => (float)$tlstdb,
                            'COM_2' => (float)$totalcom2,
                            'NET_PAYABLE' => (float)$netpayable

                        ];
                    }
                }

                $data = array_map(function ($value) {
                    return [
                        'SUPPLIER_CODE' => $value['SUPPLIER_CODE'],
                        'SUPPLIER_NAME' => $value['SUPPLIER_NAME'],
                        'DATE' => $value['DATE'],
                        'TTL_SOLD' => $value['TTL_SOLD'],
                        'DISC' => $value['DISC'],
                        'TTL_SRP' => $value['TTL_SRP'],
                        'COM_1' => $value['COM_1'],
                        'GROSS_PAYABLE' => $value['GROSS_PAYABLE'],

                        'QTY_3' => $value['QTY_3'],
                        'MOS3' => $value['MOS3'],
                        'QTY_6' => $value['QTY_6'],
                        'MOS6' =>  $value['MOS6'],
                        'QTY_9' =>  $value['QTY_9'],
                        'MOS9_MOS12' => $value['MOS9_MOS12'],

                        'QTY_F' => $value['QTY_F'],
                        'FINANCE_0' =>  $value['FINANCE_0'],
                        'QTY_FR' => $value['QTY_FR'],
                        'FINANCE_REGULAR' => $value['FINANCE_REGULAR'],
                        'QTY_D' => $value['QTY_D'],
                        'DEBIT_STRAIGHT' => $value['DEBIT_STRAIGHT'],
                        'COM_2' =>  $value['COM_2'],
                        'NET_PAYABLE' =>  $value['NET_PAYABLE']
                    ];
                }, $allData);

                break;
            case '8': //consign day
            case '9': //outright day
                $difference = $this->diff($start, $end);

                $dates = [];
                for ($i = 0; $i <= $difference; $i++) {
                    $dates[] = date("Y-m-d", strtotime("+$i days", strtotime($start)));
                }
                $groupedData = [];
                foreach ($data as $cs) {
                    $groupedData[$cs->SUPPLIER_CODE][$cs->DATE][] = $cs;
                }

                $allData = [];

                foreach ($groupedData as $supplier => $dateGroup) {
                    foreach ($dates as $date) {
                        if (empty($dateGroup[$date])) continue;


                        $totalSold = $totaldisc = $totalsrp  = $cost = $totalgros = $totalpercent = $totalcom2 = 0;
                        $tlqty_fr = $tlqty_z = $tlqty3 = $tlqty6 = $tlqty9 = $tlqtystdb = 0;
                        $tlfr = $tlz = $tlmos3 = $tlmos6 = $tlmos9 = $tlstdb = 0;
                        foreach ($dateGroup[$date] as $transaction) {
                            $totalSold += $transaction->TTL_SOLD;
                            $cost += $transaction->COST;
                            $totaldisc += $transaction->DISC;
                            $totalsrp += $transaction->TTL_SRP;

                            $totalgros += $transaction->GROSS_PAYABLE;
                            $totalcom2 += $transaction->COM_2;

                            //  CHARGES 
                            $raw = $transaction->terminalid;
                            $parts = explode('~', $raw);
                            $firstpart = $parts[0];
                            $banktype = $transaction->banktype;
                            $bankrate = $transaction->bankrate;
                            $cardcharge = $transaction->cardcharge;
                            $supcode = $transaction->SUPPLIER_CODE;
                            $supname = $transaction->SUPPLIER_NAME;

                            switch ($firstpart) {
                                case 'CCASHALOALDI':
                                case 'CHOMECREDIT':
                                case 'CHOMECREDIT0':
                                case 'CCASHALO':
                                case 'DCASHALOALDI':
                                case 'DHOMECREDIT':
                                case 'DHOMECREDIT0':
                                case 'DCASHALO':
                                case 'CASHALOALDI':
                                case 'HOMECREDIT':
                                case 'HOMECREDIT0':
                                case 'CASHALO':
                                    // case 'CAUGBGCASH':
                                    if ($bankrate != 0) {
                                        $tlqty_fr += $transaction->TTL_SOLD;
                                        $tlfr += $transaction->cardcharge;
                                    } else {
                                        $tlqty_z  += $transaction->TTL_SOLD;
                                        $tlz += $transaction->cardcharge;
                                    }
                                    break;
                                default:
                                    if (!empty($banktype) && $cardcharge != 0) {
                                        switch (strtoupper(str_replace(' ', '', $transaction->banktype))) {
                                            case '3MONS':
                                            case '3MOS':     
                                                $tlqty3 += $transaction->TTL_SOLD;
                                                $tlmos3 += $transaction->cardcharge;
                                                break;
                                            case '6MONS':
                                            case '6MOS':       
                                                $tlqty6 +=  $transaction->TTL_SOLD;
                                                $tlmos6 += $transaction->cardcharge;
                                                break;
                                            case '9MONS':
                                            case '12MONS':
                                            case '9MOS':
                                            case '12MOS':
                                                $tlqty9 +=  $transaction->TTL_SOLD;
                                                $tlmos9 +=  $transaction->cardcharge;
                                                break;
                                            case 'STRAIGHT':
                                            case 'DEBIT':
                                            case 'AUBGCASH':
                                            case 'DEBIT/BANCNET':    
                                                $tlqtystdb +=  $transaction->TTL_SOLD;
                                                $tlstdb += $transaction->cardcharge;
                                                break;
                                        }
                                    }
                                    break;
                            }
                        }


                        $charges = $totalcom2 + $tlmos3 + $tlmos6 + $tlmos9 + $tlstdb + $tlfr + $tlz;

                        $netpayable =  $totalgros + $charges;

                        $allData[] = [
                            'SUPPLIER_CODE' => $supcode,
                            'SUPPLIER_NAME' => $supname,
                            'DATE' => $date,
                            'TTL_SOLD' => (float)$totalSold,
                            'COST' => (float)$cost,
                            'DISC' => (float)$totaldisc,
                            'TTL_SRP' => (float)$totalsrp,
                            'GROSS_PAYABLE' => (float)$totalgros,

                            'QTY_3' => (float)$tlqty3,
                            'MOS3' => (float)$tlmos3,
                            'QTY_6' => (float)$tlqty6,
                            'MOS6' => (float)$tlmos6,
                            'QTY_9' => (float)$tlqty9,
                            'MOS9_MOS12' => (float)$tlmos9,

                            'QTY_F' => (float)$tlqty_z,
                            'FINANCE_0' => (float)$tlz,
                            'QTY_FR' => (float)$tlqty_fr,
                            'FINANCE_REGULAR' => (float)$tlfr,
                            'QTY_D' => (float)$tlqtystdb,
                            'DEBIT_STRAIGHT' => (float)$tlstdb,
                            'COM_2' => (float)$totalcom2,
                            'NET_PAYABLE' => (float)$netpayable

                        ];
                    }
                }

                $data = array_map(function ($value) {
                    return [
                        'SUPPLIER_CODE' => $value['SUPPLIER_CODE'],
                        'SUPPLIER_NAME' => $value['SUPPLIER_NAME'],
                        'DATE' => $value['DATE'],
                        'TTL_SOLD' => $value['TTL_SOLD'],
                        'COST' => $value['COST'],
                        'DISC' => $value['DISC'],
                        'TTL_SRP' => $value['TTL_SRP'],
                        'GROSS_PAYABLE' => $value['GROSS_PAYABLE'],

                        'QTY_3' => $value['QTY_3'],
                        'MOS3' => $value['MOS3'],
                        'QTY_6' => $value['QTY_6'],
                        'MOS6' =>  $value['MOS6'],
                        'QTY_9' =>  $value['QTY_9'],
                        'MOS9_MOS12' => $value['MOS9_MOS12'],

                        'QTY_F' => $value['QTY_F'],
                        'FINANCE_0' =>  $value['FINANCE_0'],
                        'QTY_FR' => $value['QTY_FR'],
                        'FINANCE_REGULAR' => $value['FINANCE_REGULAR'],
                        'QTY_D' => $value['QTY_D'],
                        'DEBIT_STRAIGHT' => $value['DEBIT_STRAIGHT'],
                        'COM_2' =>  $value['COM_2'],
                        'NET_PAYABLE' =>  $value['NET_PAYABLE']
                    ];
                }, $allData);

                break;
        }

        $status =  true;
        $msg = 'Generating CSV successfully';
        if (empty($data)) {
            $status =  false;
            $msg = 'No data Found';
        }
        return ['status' => $status, 'msg' => $msg, 'data' => $data, 'params' => $this->reportParams, 'name' => 'Homework Sales Report'];
    }


    public function summaryperday($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        // $branchid = $config['params']['dataparams']['branchid'];
        // $branchname = $config['params']['dataparams']['branchname'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $itemid   = $config['params']['dataparams']['itemid'];
        $barcode   = $config['params']['dataparams']['barcode'];

        $reptype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];
        $printtype = $config['params']['dataparams']['print'];


        $filter = "";

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }
        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }

        if ($client != "") {
            $filter .= " and supp.clientid = '$clientid'";
        }


        if ($barcode != "") {
            $filter .= " and item.itemid = '$itemid'";
        }
        switch ($printtype) {
            // case 'default':
            //     $query = "select head.dateid, sum(stock.iss - stock.qty) as totalsold,
            //         sum(if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as disc,
            //         sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as ext,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as totalcost,
            //         sum(if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as comm1s,
            //         sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as comm1,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as grosspayable,
            //         info.terminalid, 
            //          sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as grossap2,
            //         left(supp.client,3) as supp,info.bankrate,info.banktype,
            //        sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //        (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)))  as percentage,
            //         SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) AS tldisc

            //         from glhead as head 
            //         left join glstock as stock on stock.trno = head.trno 
            //         left join item on item.itemid = stock.itemid
            //         left join cntnum as num on num.trno = head.trno 
            //         left join client as supp on supp.clientid = item.supplier
            //         left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //          where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
            //         group by head.dateid, info.terminalid,supp.client,info.bankrate,info.banktype,num.bref";
            //     break;
            // round(sum(if(left(num.bref,3)='sjs',((info.discamt) * -1),info.discamt)),2) as disc,
            //  round(sum(if(left(num.bref,3)='sjs',((info.discamt) * -1),info.discamt)),2) as tldisc
            case 'default':
                $query = "select head.dateid,round(sum(stock.iss - stock.qty), 2) as totalsold,
                
                    round(sum(info.discamt)*-1,2) as disc,
                    round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as ext,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as totalcost,
                    round(sum(if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as comm1s,
                    round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as comm1,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as grosspayable,
                    info.terminalid, 
                    round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as grossap2,
                    left(supp.client,3) as supp,info.bankrate,info.banktype,
                    round(sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                    (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))),2)  as percentage,
                   
                  
                     round(sum(info.discamt)*-1,2) as tldisc
                    from glhead as head 
                    left join glstock as stock on stock.trno = head.trno 
                    left join item on item.itemid = stock.itemid
                    left join cntnum as num on num.trno = head.trno 
                    left join client as supp on supp.clientid = item.supplier
                    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                     where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                    group by head.dateid, info.terminalid,supp.client,info.bankrate,info.banktype,num.bref";
                break;
            case 'CSV':
                // $query = "select head.dateid  as `DATE`, sum(stock.iss - stock.qty) as `TTL_SOLD`,
                //       sum(if(left(num.bref,3) = 'sjs', info.discamt,  info.discamt * -1) * -1) AS `DISC`,
                //     sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as `TTL_SRP`,
                //     sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as `TTL_COST`,
                //     sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as  `COM_1`,
                //     sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as  `GROSS_PAYABLE`,
                //     sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                //    (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)))  as  `PERCENTAGE`,

                //     0 as 'QTY_3', 0 as 'MOS3' , 0 as 'QTY_6', 0 as 'MOS6',
                //     0 as 'QTY_9', 0 as 'MOS9_MOS12', 0 as 'QTY_F' , 0 as 'FINANCE_0', 0 as 'QTY_FR', 0 as 'FINANCE_REGULAR', 
                //     0 as 'QTY_D', 0 as 'DEBIT_STRAIGHT',
                //      sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as  `COM_2`,
                //      0 as `NET_PAYABLE`,

                //      info.terminalid, 
                //      sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
                //      info.bankrate,info.banktype

                //     from glhead as head 
                //     left join glstock as stock on stock.trno = head.trno 
                //     left join item on item.itemid = stock.itemid
                //     left join cntnum as num on num.trno = head.trno 
                //     left join client as supp on supp.clientid = item.supplier
                //     left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                //      where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                //     group by head.dateid, info.terminalid,supp.client,info.bankrate,info.banktype,num.bref";
                // break;

                $query = "select head.dateid  as `DATE`, round(sum(stock.iss - stock.qty),2) as `TTL_SOLD`,
                        round(sum(info.discamt)*-1,2) as `DISC`,

                    round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as `TTL_SRP`,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as `TTL_COST`,
                    round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as  `COM_1`,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as  `GROSS_PAYABLE`,
                    round(sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                    (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))),2)  as  `PERCENTAGE`,

                    round(0) as 'QTY_3', round(0) as 'MOS3' , round(0) as 'QTY_6', round(0) as 'MOS6',
                    round(0) as 'QTY_9', round(0) as 'MOS9_MOS12', round(0) as 'QTY_F' , round(0) as 'FINANCE_0',
                    round(0) as 'QTY_FR', round(0) as 'FINANCE_REGULAR', 
                    round(0) as 'QTY_D', round(0) as 'DEBIT_STRAIGHT',
                     round(sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as  `COM_2`,
                     round(0) as `NET_PAYABLE`,

                     info.terminalid, 
                     round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                     info.bankrate,info.banktype
                  
                    from glhead as head 
                    left join glstock as stock on stock.trno = head.trno 
                    left join item on item.itemid = stock.itemid
                    left join cntnum as num on num.trno = head.trno 
                    left join client as supp on supp.clientid = item.supplier
                    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                     where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                    group by head.dateid, info.terminalid,supp.client,info.bankrate,info.banktype,num.bref";
                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    public function vendor_summaryqry($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        // $branchid = $config['params']['dataparams']['branchid'];
        // $branchname = $config['params']['dataparams']['branchname'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $itemid   = $config['params']['dataparams']['itemid'];
        $barcode   = $config['params']['dataparams']['barcode'];

        $reptype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];
        $printtype = $config['params']['dataparams']['print'];


        $filter = "";

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }
        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }

        if ($client != "") {
            $filter .= " and supp.clientid = '$clientid'";
        }


        if ($barcode != "") {
            $filter .= " and item.itemid = '$itemid'";
        }
        switch ($printtype) {
            // case 'default':
            //     $query = "select br.clientname as branchname, item.channel, supp.client as suppcode, supp.clientname as suppliername,
            //         sum(stock.iss - stock.qty) as totalsold,
            //          sum(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1))) as srp,
            //         sum(if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as disc,
            //         sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as ext,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as totalcost,
            //          sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as comm1,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as grosspayable,
            //         info.terminalid,
            //          sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as grossap2,
            //         left(supp.client,3) as supp,info.bankrate,info.banktype,
            //          sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //         (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)))  as percentage,
            //          SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) AS tldisc,
            //          concat(supp.clientname, ' - ', supp.client) as filter
            //         from glhead as head
            //         left join glstock as stock on stock.trno = head.trno
            //         left join item on item.itemid = stock.itemid
            //         left join cntnum as num on num.trno = head.trno
            //         left join client as supp on supp.clientid = item.supplier
            //         left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //         left join client as br on br.clientid=head.branch
            //          where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
            //         group by br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref 
            //         order by item.channel,filter";
            //     break;
            //round(SUM(IF(LEFT(num.bref,3) = 'sjs',  ((info.discamt )*-1),  info.discamt)),2) AS tldisc,
            //round(sum(if(left(num.bref,3) = 'sjs', ((info.discamt) * -1), info.discamt )),2) as disc,
            // round(SUM(IF(LEFT(num.bref,3) = 'sjs',  ((info.discamt ) * -1 ),  info.discamt)),2) AS tldisc,
            //    round(sum(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1))),2) as srp,
            case 'default':
                $query = "select br.clientname as branchname, item.channel, supp.client as suppcode, supp.clientname as suppliername,
                    round(sum(stock.iss - stock.qty),2) as totalsold,
                 
                    round(sum(if(left(num.bref,3) = 'sjs', stock.isamt * stock.iss , ((stock.isamt * stock.qty) * -1))),2) as srp,
                    

                     round(sum(info.discamt)*-1,2) as disc,

                    round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as ext,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as totalcost,
                     round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as comm1,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as grosspayable,
                    info.terminalid,
                     round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as grossap2,
                    left(supp.client,3) as supp,info.bankrate,info.banktype,
                     round(sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                    (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))),2)  as percentage,
                     round(sum(info.discamt)*-1,2) as tldisc,
                     concat(trim(supp.clientname), ' - ',trim(supp.client)) as filter
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join cntnum as num on num.trno = head.trno
                    left join client as supp on supp.clientid = item.supplier
                    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                    left join client as br on br.clientid=head.branch
                     where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                    group by br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref 
                    order by item.channel,filter asc";
                // var_dump($query);
                break;
            // case 'CSV':
            //     //sum(if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as `DISC`,
            //     $query = "select item.channel as `CHANNEL`, br.clientname  as `BRANCH`,  supp.client as `SUPPLIER_CODE`,
            //          supp.clientname as `SUPPLIER_NAME`,
            //         sum(stock.iss - stock.qty) as `TTL_SOLD`,
            //         sum(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1))) as `SRP`,
            //         SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) as `DISC`,
            //         sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as `TTL_SRP`,
            //         sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as `COM_1`,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as  `GROSS_PAYABLE`,


            //         0 as 'QTY_3', 0 as 'MOS3' , 0 as 'QTY_6', 0 as 'MOS6',
            //         0 as 'QTY_9', 0 as 'MOS9_MOS12', 0 as 'QTY_F' , 0 as 'FINANCE_0', 0 as 'QTY_FR', 0 as 'FINANCE_REGULAR', 
            //         0 as 'QTY_D', 0 as 'DEBIT_STRAIGHT',

            //          sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as `COM_2`,
            //          0 AS `NET_PAYABLE`,

            //         info.terminalid,
            //         sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,

            //         left(supp.client,3) as supp,info.bankrate,info.banktype,
            //         concat(supp.clientname, ' - ', supp.client) as filter

            //         from glhead as head
            //         left join glstock as stock on stock.trno = head.trno
            //         left join item on item.itemid = stock.itemid
            //         left join cntnum as num on num.trno = head.trno
            //         left join client as supp on supp.clientid = item.supplier
            //         left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //         left join client as br on br.clientid=head.branch
            //          where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
            //         group by br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref 
            //         order by item.channel,filter";
            //     break;
            case 'CSV':
                //sum(if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as `DISC`,
                // round(SUM(IF(LEFT(num.bref,3) = 'sjs',  ((info.discamt ) * -1 ),  info.discamt)),2) AS  `DISC`,
                // round(sum(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1))),2) as `SRP`,
                $query = "select item.channel as `CHANNEL`, br.clientname  as `BRANCH`,  supp.client as `SUPPLIER_CODE`,
                     supp.clientname as `SUPPLIER_NAME`,
                    round(sum(stock.iss - stock.qty),2) as `TTL_SOLD`,

                      round(sum(if(left(num.bref,3) = 'sjs', stock.isamt * stock.iss , ((stock.isamt * stock.qty) * -1))),2) as `SRP`,
                    round(sum(info.discamt)*-1,2) as  `DISC`,


                    round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as `TTL_SRP`,
                    round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as `COM_1`,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as  `GROSS_PAYABLE`,
                   

                    round(0) as 'QTY_3', round(0) as 'MOS3' , round(0) as 'QTY_6', round(0) as 'MOS6',
                    round(0) as 'QTY_9', round(0) as 'MOS9_MOS12', round(0) as 'QTY_F' , round(0) as 'FINANCE_0', 
                    round(0) as 'QTY_FR', round(0) as 'FINANCE_REGULAR', 
                    round(0) as 'QTY_D', round(0) as 'DEBIT_STRAIGHT',

                     round(sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as `COM_2`,
                     round(0) AS `NET_PAYABLE`,
                
                    info.terminalid,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                   
                    left(supp.client,3) as supp,info.bankrate,info.banktype,
                    concat(trim(supp.clientname), ' - ',trim(supp.client)) as filter

                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join cntnum as num on num.trno = head.trno
                    left join client as supp on supp.clientid = item.supplier
                    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                    left join client as br on br.clientid=head.branch
                     where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                    group by br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref 
                    order by item.channel,filter asc";
                break;
        }
        return $this->coreFunctions->opentable($query);
    }

    public function detailperdoc($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        // $branchid = $config['params']['dataparams']['branchid'];
        // $branchname = $config['params']['dataparams']['branchname'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $itemid   = $config['params']['dataparams']['itemid'];
        $barcode   = $config['params']['dataparams']['barcode'];

        $reptype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];
        $printtype = $config['params']['dataparams']['print'];


        $filter = "";
        $orderby = 'order by itemdesc,suppliername';

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }
        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }

        if ($client != "") {
            $filter .= " and supp.clientid = '$clientid'";
        }


        if ($barcode != "") {
            $filter .= " and item.itemid = '$itemid'";
        }
        switch ($printtype) {
            // case 'default':
            //     $query = "select date(head.dateid) as dateid, supp.client as supcode, concat(left(stock.ref,2),right(stock.ref,6)) as docno, supp.clientname as suppliername, item.barcode,
            //     item.itemname as itemdesc,(stock.iss - stock.qty) as totalsold,
            //     if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)) as srp,
            //     (if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as tldisc,
            //     if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)) as ext,
            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as totalcost, 
            //     if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1) as comm1,
            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as grosspayable,
            //     info.terminalid, if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge) as cardcharge,
            //      info.comm2,
            //     (if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as grossap2,left(supp.client,3) as supp,
            //     info.bankrate,info.banktype,
            //     if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //     (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))  as percentage
            //     from glhead as head 
            //     left join glstock as stock on stock.trno = head.trno
            //     left join item on item.itemid = stock.itemid
            //     left join cntnum as num on num.trno = head.trno
            //     left join client as supp on supp.clientid = item.supplier
            //     left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //       where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
            //     break;
            //      round((if(left(num.bref,3) = 'sjs', ((info.discamt)*-1), info.discamt )),2) as tldisc,
            case 'default':
                $query = "select date(head.dateid) as dateid, supp.client as supcode, concat(left(stock.ref,2),right(stock.ref,6)) as docno, supp.clientname as suppliername, item.barcode,
                item.itemname as itemdesc,round((stock.iss - stock.qty),2) as totalsold,
                round(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)),2) as srp,

           
                 round(((info.discamt) * -1),2) as tldisc,
               

                round(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)),2) as ext,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as totalcost, 
                round(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1),2) as comm1,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as grosspayable,
                info.terminalid, round(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge),2) as cardcharge,
                 info.comm2,
                round((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as grossap2,
                left(supp.client,3) as supp,
                info.bankrate,info.banktype,
                round(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)),2)  as percentage
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                  where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
                break;
            // case 'CSV':
            //     $query = "select date(head.dateid)  as `DATE`, concat(left(stock.ref,2),right(stock.ref,6))  as `INVOICE #`,
            //     supp.client as `SUPPLIER CODE`, supp.clientname as `SUPPLIER NAME`, item.barcode as `ITEM CODE`,
            //     item.itemname as `ITEM DESCRIPTION`,(stock.iss - stock.qty) as  `TTL_SOLD`,
            //     if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)) as `SRP`,
            //     (if(left(num.bref,3) = 'sjs', info.discamt, (info.discamt * -1)) * -1) as  `DISC`,
            //     if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)) as  `TTL_SRP`,
            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as `TTL_COST`, 
            //     if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1) as `COM_1`, 
            //     if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)) as  `GROSS_PAYABLE`, 
            //      if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //     (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))  as  `PERCENTAGE`, 

            //     0 as 'QTY_3', 0 as 'MOS3' , 0 as 'QTY_6', 0 as 'MOS6',
            //     0 as 'QTY_9', 0 as 'MOS9_MOS12', 0 as 'QTY_F' , 0 as 'FINANCE_0', 0 as 'QTY_FR', 0 as 'FINANCE_REGULAR', 
            //     0 as 'QTY_D', 0 as 'DEBIT_STRAIGHT',
            //     (if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as 'COM_2', 0 as 'NET_PAYABLE', 

            //     info.terminalid, if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge) as cardcharge,
            //     info.bankrate,info.banktype

            //     from glhead as head 
            //     left join glstock as stock on stock.trno = head.trno
            //     left join item on item.itemid = stock.itemid
            //     left join cntnum as num on num.trno = head.trno
            //     left join client as supp on supp.clientid = item.supplier
            //     left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //       where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
            //     break;
            //  round((if(left(num.bref,3) = 'sjs', ((info.discamt)*-1), info.discamt)),2) as  `DISC`,
            case 'CSV':
                $query = "select date(head.dateid)  as `DATE`, concat(left(stock.ref,2),right(stock.ref,6))  as `INVOICE #`,
                supp.client as `SUPPLIER CODE`, supp.clientname as `SUPPLIER NAME`, item.barcode as `ITEM CODE`,
                item.itemname as `ITEM DESCRIPTION`,round((stock.iss - stock.qty),2) as  `TTL_SOLD`,
                round(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)),2) as `SRP`,
              
                round(((info.discamt) * -1),2) as  `DISC`,

                round(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)),2) as  `TTL_SRP`,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2)  as `TTL_COST`, 
                round(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1),2) as `COM_1`, 
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as  `GROSS_PAYABLE`, 
                 round(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)),2)  as  `PERCENTAGE`, 

                round(0) as 'QTY_3', round(0) as 'MOS3' , round(0) as 'QTY_6', round(0) as 'MOS6',
                round(0) as 'QTY_9', round(0) as 'MOS9_MOS12', round(0) as 'QTY_F' , round(0) as 'FINANCE_0', 
                round(0) as 'QTY_FR', round(0) as 'FINANCE_REGULAR', 
                round(0) as 'QTY_D', round(0) as 'DEBIT_STRAIGHT',
                round((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as 'COM_2', round(0) as 'NET_PAYABLE', 

                info.terminalid, round(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge),2) as cardcharge,
                info.bankrate,info.banktype
               
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                  where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";
                break;
        }
        return $this->coreFunctions->opentable($query);
    }



    public function concession($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        // $branchid = $config['params']['dataparams']['branchid'];
        // $branchname = $config['params']['dataparams']['branchname'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $itemid   = $config['params']['dataparams']['itemid'];
        $barcode   = $config['params']['dataparams']['barcode'];

        $reptype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];
        $printtype = $config['params']['dataparams']['print'];

        $filter = "";

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }
        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }

        if ($client != "") {
            $filter .= " and supp.clientid = '$clientid'";
        }


        if ($barcode != "") {
            $filter .= " and item.itemid = '$itemid'";
        }

        $totalcost = " , round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as totalcost ";
        $csvtlcost = " ,  round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as `GROSS_PAYABLE`, ";
        $cost = ",round(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1)),2) as cost,";
        $costcsv = ",round(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1)),2) as  `COST`,";
        switch ($reptype) {
            case '4': // concess day
                $filter .= " and  left(supp.client,3)='163'"; //concession
                break;
            case '5': // consign day
                $filter .= " and  left(supp.client,3)='162'"; //consignment 162
                break;
            case '6': //outright day
                $filter .= " and  left(supp.client,3)='161'"; //OUTRIGHT
                $cost = ",round(if(left(num.bref,3) = 'sjs', stock.cost, ((sum(info.comap)/sum(stock.qty)) * -1)),2) as cost,";
                $costcsv = ",round(if(left(num.bref,3) = 'sjs', stock.cost, ((sum(info.comap)/sum(stock.qty))* -1)),2) as `COST`,";
                break;
        }
        if ($center == '') {
            $orderby = "order by center.name, filter asc";
        } else {
            $orderby = "order by filter asc ";
        }
        //     round(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1)),2) as cost,
        switch ($printtype) {
            // case 'default':
            //     $query = "select  supp.client as supcode, supp.clientname as suppliername, item.barcode,
            //     item.itemname as itemdesc,sum(stock.iss - stock.qty) as totalsold,
            //     if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)) as srp,
            //     if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1)) as cost,
            //     sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as ext,
            //     sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as totalcost, 
            //     sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as comm1,
            //     sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as grosspayable,
            //     info.terminalid, 
            //     sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
            //     sum(info.comm2) as comm2,
            //     sum((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1)) as grossap2,left(supp.client,3) as supp,
            //     info.bankrate,info.banktype,
            //     sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //     (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)))  as percentage,
            //     SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) AS disc

            //     from glhead as head 
            //     left join glstock as stock on stock.trno = head.trno 
            //     left join item on item.itemid = stock.itemid
            //     left join cntnum as num on num.trno = head.trno 
            //     left join client as supp on supp.clientid = item.supplier
            //     left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //     where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
            //     group by supp.client, supp.clientname, item.barcode,item.itemname,info.terminalid,info.bankrate,info.banktype,num.bref,stock.isamt,stock.cost
            //     order by supp.client ";
            //     break;
            // round(SUM(IF(LEFT(num.bref,3) = 'sjs',  ((info.discamt) *-1),  info.discamt)),2) AS disc
            case 'default':
                $query = "select center.name as center, supp.client as supcode, supp.clientname as suppliername, item.barcode,
                item.itemname as itemdesc,round(sum(stock.iss - stock.qty),2) as totalsold,
                round(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)),2) as srp
                $cost
                round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as ext,
                round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as comm1,
                round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as grosspayable,
                info.terminalid, 
                round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                round(sum(info.comm2),2) as comm2,
                round(sum((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1)),2) as grossap2,
                left(supp.client,3) as supp,
                info.bankrate,info.banktype,
                round(sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))),2)  as percentage,
                 round(sum(info.discamt)*-1,2) as disc $totalcost , concat(trim(supp.clientname), ' - ',trim(supp.client)) as filter
            
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno 
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno 
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                left join center on center.code=num.center
                where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                group by center.name,supp.client, supp.clientname, item.barcode,item.itemname,info.terminalid,info.bankrate,info.banktype,num.bref,stock.isamt,stock.cost
                $orderby ";
             
              // $this->coreFunctions->LogConsole($query);
                // logger($query);
                break;
            // case 'CSV':
            //     $query = "select  supp.client as `SUPPLIER CODE`, supp.clientname as `SUPPLIERNAME`, item.barcode as `ITEM CODE`,
            //     item.itemname as itemdesc,sum(stock.iss - stock.qty) as `TTL_SOLD`,
            //      if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1)) as  `COST`,
            //     if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)) as `SRP`,
            //     SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) as `DISC`,
            //     sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as `TTL_SRP`,
            //     sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as `COM_1`,
            //     sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as `GROSS_PAYABLE`,
            //     0 as 'QTY_3', 0 as 'MOS3' , 0 as 'QTY_6', 0 as 'MOS6',
            //     0 as 'QTY_9', 0 as 'MOS9_MOS12', 0 as 'QTY_F' , 0 as 'FINANCE_0', 0 as 'QTY_FR', 0 as 'FINANCE_REGULAR', 
            //     0 as 'QTY_D', 0 as 'DEBIT_STRAIGHT',
            //     sum((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1))  as `COM_2`,
            //     info.terminalid, 
            //     sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
            //     info.bankrate,info.banktype
            //     from glhead as head 
            //     left join glstock as stock on stock.trno = head.trno 
            //     left join item on item.itemid = stock.itemid
            //     left join cntnum as num on num.trno = head.trno 
            //     left join client as supp on supp.clientid = item.supplier
            //     left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //     where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
            //     group by supp.client, supp.clientname, item.barcode,item.itemname,info.terminalid,info.bankrate,info.banktype,num.bref,stock.isamt,stock.cost
            //     order by supp.client ";
            //     break;
            // round(SUM(IF(LEFT(num.bref,3) = 'sjs',  ((info.discamt) *-1),  info.discamt )),2) as `DISC`,
            // round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as `GROSS_PAYABLE`,
            case 'CSV':
                $query = "select  center.name as `BRANCH`, supp.client as `SUPPLIER CODE`, supp.clientname as `SUPPLIERNAME`, item.barcode as `ITEM CODE`,
                item.itemname as itemdesc,round(sum(stock.iss - stock.qty),2) as `TTL_SOLD`
                $costcsv
                round(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)),2) as `SRP`,
                round(sum(info.discamt)*-1,2) as `DISC`,
                round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as `TTL_SRP`,
                round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as `COM_1`
                $csvtlcost
                round(0) as 'QTY_3', round(0) as 'MOS3' , round(0) as 'QTY_6', round(0) as 'MOS6',
                round(0) as 'QTY_9', round(0) as 'MOS9_MOS12', round(0) as 'QTY_F' , round(0) as 'FINANCE_0', 
                round(0) as 'QTY_FR', round(0) as 'FINANCE_REGULAR', 
                round(0) as 'QTY_D', round(0) as 'DEBIT_STRAIGHT',
                round(sum((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1)),2)  as `COM_2`,
                round(0) as 'NET_PAYABLE', 
                info.terminalid, 
                round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                info.bankrate,info.banktype,concat(trim(supp.clientname), ' - ',trim(supp.client)) as filter
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno 
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno 
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                 left join center on center.code=num.center
                where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                group by center.name,supp.client, supp.clientname, item.barcode,item.itemname,info.terminalid,info.bankrate,info.banktype,num.bref,stock.isamt,stock.cost
                $orderby ";
                break;
        }
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    public function concessday($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        // $branchid = $config['params']['dataparams']['branchid'];
        // $branchname = $config['params']['dataparams']['branchname'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $itemid   = $config['params']['dataparams']['itemid'];
        $barcode   = $config['params']['dataparams']['barcode'];

        $reptype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];
        $printtype = $config['params']['dataparams']['print'];
        $filter = "";

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }
        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }

        if ($client != "") {
            $filter .= " and supp.clientid = '$clientid'";
        }


        if ($barcode != "") {
            $filter .= " and item.itemid = '$itemid'";
        }

        $totalcost = " , round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as totalcost";
        $csvtlcost = ",round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as  `GROSS_PAYABLE`,";
        $cost = ",round(sum(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1))),2) as cost,";
        $costcsv = ", round(sum(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1))),2) as `COST`,";
        switch ($reptype) {
            case '7': // concess day
                $filter .= " and  left(supp.client,3)='163'"; //concession
                break;
            case '8': // consign day
                $filter .= " and  left(supp.client,3)='162'"; //consignment 162
                break;
            case '9': //outright day
                $filter .= " and  left(supp.client,3)='161'"; //OUTRIGHT
                $cost =  ",round(sum(if(left(num.bref,3) = 'sjs', stock.cost, ((info.comap/stock.qty) * -1))),2) as cost,";
                $costcsv = ", round(sum(if(left(num.bref,3) = 'sjs', stock.cost, ((info.comap/stock.qty) * -1))),2) as `COST`,";
                break;
        }

        switch ($printtype) {
            // case 'default':
            //     $query = "select date(head.dateid) as dateid,br.clientname as branchname, item.channel, supp.client as suppcode, supp.clientname as suppliername,
            //         sum(stock.iss - stock.qty) as totalsold,
            //           sum(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1))) as cost,
            //          sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as ext,
            //          sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as totalcost,
            //         sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as comm1,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as grosspayable,
            //         info.terminalid, 
            //         sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as grossap2,
            //         left(supp.client,3) as supp,info.bankrate,info.banktype,
            //          sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
            //          (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1)))  as percentage,
            //          SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) AS disc
            //         from glhead as head
            //         left join glstock as stock on stock.trno = head.trno
            //         left join item on item.itemid = stock.itemid
            //         left join cntnum as num on num.trno = head.trno
            //         left join client as supp on supp.clientid = item.supplier
            //         left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //         left join client as br on br.clientid=head.branch
            //          where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
            //         group by date(head.dateid),br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref  ";
            //     break;
            //round(SUM(IF(LEFT(num.bref,3) = 'sjs',  ((info.discamt) *-1),  info.discamt )),2) AS disc
            // round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap/stock.qty) * -1))),2) as totalcost,
            //round(sum(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1))),2) as cost,
            case 'default':
                $query = "select date(head.dateid) as dateid,br.clientname as branchname, item.channel, supp.client as suppcode, supp.clientname as suppliername,
                    round(sum(stock.iss - stock.qty),2) as totalsold
                     $cost
                     round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as ext,
                    round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as comm1,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as grosspayable,
                    info.terminalid, 
                    round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as grossap2,
                    left(supp.client,3) as supp,info.bankrate,info.banktype,
                     round(sum(if(left(supp.client,3) = '163', ((if(left(num.bref,3) = 'sjs', (stock.ext), ((stock.ext) * -1)) * (info.comm1 / 100)) * -1) / stock.ext,
                     (((if(left(num.bref,3) = 'sjs', (info.comap), (info.comap) * -1)) / stock.ext) - 1))),2)  as percentage,
                     round(sum(info.discamt)*-1,2) as disc $totalcost, concat(trim(supp.clientname), ' - ',trim(supp.client)) as filter
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join cntnum as num on num.trno = head.trno
                    left join client as supp on supp.clientid = item.supplier
                    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                    left join client as br on br.clientid=head.branch
                     where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                    group by date(head.dateid),br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref order by filter asc ";
                    
                    break;
            // case 'CSV':
            //     $query = "select  supp.client as  `SUPPLIER_CODE`, supp.clientname as  `SUPPLIER_NAME`, date(head.dateid) as `DATE`,
            //         sum(stock.iss - stock.qty) as `TTL_SOLD`,
            //         sum(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1))) as `COST`,
            //         SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) AS `DISC`,
            //         sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as  `TTL_SRP`,
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as  `GROSS_PAYABLE`,
            //         sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as `COM_1`,

            //         0 as 'QTY_3', 0 as 'MOS3' , 0 as 'QTY_6', 0 as 'MOS6',
            //         0 as 'QTY_9', 0 as 'MOS9_MOS12', 0 as 'QTY_F' , 0 as 'FINANCE_0', 0 as 'QTY_FR', 0 as 'FINANCE_REGULAR', 
            //         0 as 'QTY_D', 0 as 'DEBIT_STRAIGHT',
            //         sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1) as `COM_2`,
            //         0 as `NET_PAYABLE`,

            //         info.terminalid, 
            //         sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
            //         left(supp.client,3) as supp,info.bankrate,info.banktype

            //         from glhead as head
            //         left join glstock as stock on stock.trno = head.trno
            //         left join item on item.itemid = stock.itemid
            //         left join cntnum as num on num.trno = head.trno
            //         left join client as supp on supp.clientid = item.supplier
            //         left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
            //         left join client as br on br.clientid=head.branch
            //          where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
            //         group by date(head.dateid),br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref  ";
            //     break;
            // /  round(SUM(IF(LEFT(num.bref,3) = 'sjs',  ((info.discamt) *-1),  info.discamt )),2) AS `DISC`,
            //round(sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))),2) as  `GROSS_PAYABLE`,
            case 'CSV':
                $query = "select  supp.client as  `SUPPLIER_CODE`, supp.clientname as  `SUPPLIER_NAME`, date(head.dateid) as `DATE`,
                    round(sum(stock.iss - stock.qty),2) as `TTL_SOLD`
                   $costcsv
                     round(sum(info.discamt)*-1,2) AS `DISC`,

                    round(sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))),2) as  `TTL_SRP`
                    $csvtlcost
                    round(sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)),2) as `COM_1`,
                   
                    round(0) as 'QTY_3', round(0) as 'MOS3' , round(0) as 'QTY_6', round(0) as 'MOS6',
                    round(0) as 'QTY_9', round(0) as 'MOS9_MOS12', round(0) as 'QTY_F' , round(0) as 'FINANCE_0',
                     round(0) as 'QTY_FR', round(0) as 'FINANCE_REGULAR', 
                    round(0) as 'QTY_D', round(0) as 'DEBIT_STRAIGHT',
                    round(sum(if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as `COM_2`,
                    round(0) as `NET_PAYABLE`,

                    info.terminalid, 
                    round(sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)),2) as cardcharge,
                    left(supp.client,3) as supp,info.bankrate,info.banktype, concat(trim(supp.clientname), ' - ',trim(supp.client)) as filter

                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join cntnum as num on num.trno = head.trno
                    left join client as supp on supp.clientid = item.supplier
                    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                    left join client as br on br.clientid=head.branch
                     where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                    group by date(head.dateid),br.clientname, item.channel, supp.client, supp.clientname, info.terminalid, supp.client,info.bankrate,info.banktype,num.bref order by filter asc ";
                break;
        }
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }



    public function calculate_chars_per_line($width, $avg1)
    {
        return floor($width / $avg1);
    }

    public function calculate_chars_per_lines($width, $avg2)
    {
        return floor($width / $avg2);
    }


    public function calculate_lines($text, $chars_per_line)
    {
        $lines = 0;
        $text_length = strlen($text);

        while ($text_length > 0) {
            $lines++;
            $text = substr($text, $chars_per_line);  // Remove the processed part
            $text_length = strlen($text); // Update the remaining text length
        }
        // return ceil($lines);
        return $lines;
    }


    public function calculate_lines_needed($data, $config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case '0':
                //font calibri - size 7
                // Calculate how many characters fit per line for item description and supplier name
                $width1 = 90;  // Width for item description (in pixels)
                $width2 = 87; // Width for supplier name (in pixels)

                $avg1 = ceil($width1 / 19); //19 -> character per line item
                $avg2 = ceil($width2 / 18); //supplier
                $variable1 = $data->itemdesc;
                $variable2 = $data->suppliername;
                break;
            case '2':
                // Calculate how many characters fit per line for item description and supplier name
                $width1 = 60;  // Width for item description (in pixels)
                $width2 = 87; // Width for supplier name (in pixels)

                $avg1 = ceil($width1 / 19); //19 -> character per line branch
                $avg2 = ceil($width2 / 18); //supplier
                $variable1 = $data->branchname;
                $variable2 = $data->suppliername;
                break;

            // case '3':
            //     $width1 = 76;  // suppliername
            //     $width2 = 75; // itemdesc

            //     $avg1 = ceil($width1 / 17);
            //     $avg2 = ceil($width2 / 19);
            //     $variable1 = $data->suppliername;
            //     $variable2 = $data->itemdesc;
            //     break;

            case '4': //Vendor w/ Item Concession
            case '5': //Vendor w/ Item Consignment
            case '6': //Vendor w/ Item Outright
                $width1 = 68;  // barcode
                $width2 = 100; // itemdesc

                $avg1 = ceil($width1 / 14);
                $avg2 = ceil($width2 / 25);
                $variable1 = $data->barcode;
                $variable2 = $data->itemdesc;
                break;
        }


        // Get characters per line
        $chars_per_line1 = $this->calculate_chars_per_line($width1, $avg1);
        $chars_per_line2 = $this->calculate_chars_per_lines($width2, $avg2);


        $lines_needed1 = $this->calculate_lines($variable1, $chars_per_line1);
        $lines_needed2 = $this->calculate_lines($variable2, $chars_per_line2);

        return max($lines_needed1, $lines_needed2);
    }


    public function Detail_per_item($config)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '7';

        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1200';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];
        $str .= '<div style="position: relative;margin:0 0 38px 0;">';
        $str .= $this->detail_per_item_displayHeader($config);

        $totalsld = 0;
        $totaldiscount = 0;
        $tlsrp = 0;
        $tllsrp = 0;
        $tlssrp = 0;
        $tlcost = 0;
        $comm1here = 0;
        $gross = 0;
        $percenthere = 0;
        $commm2 = 0;
        $netp = 0;

        $tldqty3 = 0;
        $tldmos3 = 0;
        $tldqty6 = 0;
        $tldmos6 = 0;

        $tldqty9 = 0;
        $tldmos9 = 0;


        $tldqtyfzero = 0;
        $tldfzero = 0;

        $tldqtyfreg = 0;
        $tldfreg = 0;

        $tldqtyst = 0;
        $tldmos = 0;
        $page = 14;
        $count = 14;
        $counterkwek = 0;

        foreach ($result as $key => $data) {

            $qty3 = $qty6 = $qty9 = $qtyst = 0;
            $mos3 = $mos6 = $mos9 = $most = 0;

            $itemlines = $this->calculate_lines_needed($data, $config);
            // echo "Calculated Item Lines: " . $itemlines . "\n";

            // if ($counterkwek + $itemlines >= $page) {
            // echo "Page Break Triggered!\n";
            if ($this->reporter->linecounter  >= $page) {

                $str .= $this->reporter->endtable();
                $str .= '</div>';
                $str .= $this->reporter->page_break();
                // $this->reporter->linecounter = 0;
                $counterkwek = 0;
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                $str .= $this->detail_per_item_displayHeader($config);
                $page = $page + $count;
            }

            // $this->reporter->linecounter += $itemlines;
            $counterkwek += $itemlines;
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            $financeregularqty = $financeregular = 0;
            $financezeroqty = $financezero = 0;
            $raw = $data->terminalid;
            $parts = explode('~', $raw);
            $firstpart = $parts[0];
            switch ($firstpart) {
                case 'CCASHALOALDI':
                case 'CHOMECREDIT':
                case 'CHOMECREDIT0':
                case 'CCASHALO':
                case 'DCASHALOALDI':
                case 'DHOMECREDIT':
                case 'DHOMECREDIT0':
                case 'DCASHALO':
                case 'CASHALOALDI':
                case 'HOMECREDIT':
                case 'HOMECREDIT0':
                case 'CASHALO':
                    // case 'CAUGBGCASH':
                    if ($data->bankrate != 0) {
                        $financeregularqty = $data->totalsold;
                        $financeregular = $data->cardcharge;
                    } else {
                        $financezeroqty = $data->totalsold;
                        $financezero = $data->cardcharge;
                    }
                    break;
                default:
                    if (!empty($data->banktype) && $data->cardcharge != 0) {
                        switch (strtoupper(str_replace(' ', '', $data->banktype))) {
                            case '3MONS':
                            case '3MOS':
                                $qty3 = $data->totalsold;
                                $mos3 = $data->cardcharge;
                                break;
                            case '6MONS':
                            case '6MOS':    
                                $qty6 =  $data->totalsold;
                                $mos6 =  $data->cardcharge;
                                break;
                            case '9MONS':
                            case '12MONS':
                            case '9MOS':
                            case '12MOS':
                                $qty9 =  $data->totalsold;
                                $mos9 =  $data->cardcharge;
                                break;
                            case 'STRAIGHT':
                            case 'DEBIT':
                            case 'AUBGCASH': 
                            case 'DEBIT/BANCNET':      
                                $qtyst =  $data->totalsold;
                                $most =  $data->cardcharge;
                                break;
                        }
                    }
                    break;
            }


            $disp_financeregularqty = ($financeregularqty != 0) ? $financeregularqty : 0;
            $disp_financeregular    = ($financeregular != 0)    ? $financeregular : 0;

            $disp_financezeroqty    = ($financezeroqty != 0)    ? $financezeroqty   : 0;
            $disp_financezero       = ($financezero != 0)       ? $financezero     : 0;


            $display_mos3 = ($mos3 != 0) ? $mos3 : 0;
            $display_mos6 = ($mos6 != 0) ? $mos6 : 0;
            $display_mos9 = ($mos9 != 0) ? $mos9 : 0;
            $display_mos = ($most != 0) ? $most : 0;

            $disp_qty3  = ($qty3 != 0) ? $qty3 : 0;
            $disp_qty6  = ($qty6 != 0) ? $qty6 : 0;
            $disp_qty9  = ($qty9 != 0) ? $qty9 : 0;
            $disp_qtyst = ($qtyst != 0) ? $qtyst : 0;


            $grosspayable = $data->totalcost; //grosspayablr
            $grossap2s = $data->grossap2; //com2


            $charges = $grossap2s + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;
            $netpayable =  $grosspayable  + $charges;
            $disp_netpayable = $netpayable;


            $discount = $data->disc;
            $discount = ($discount != 0) ? $discount : 0;


            $totalsold  = ($data->totalsold != 0) ? floatval($data->totalsold) : 0;
            $srp        = ($data->srp != 0) ? floatval($data->srp) : 0;
            $tlsrp      = ($data->ext != 0) ? floatval($data->ext) : 0;
            // $comm1 = ($data->ext * ($data->comm1 / 100) * -1);
            $comm1 = $data->comm1;

            $percentage = $data->percentage;


            $comap      = ($data->totalcost != 0) ? floatval($data->totalcost) : 0;
            $grossap2   = ($grossap2s != 0) ? floatval($grossap2s) : 0;

            if ($totalsold < 0) { //ito yung negative
                $totalsoldd = '(' . number_format(abs($totalsold), 2) . ')';
            } else {
                $totalsoldd = $totalsold == 0 ? '-' : number_format($totalsold, 2);
            }

            if ($srp < 0) { //ito yung negative
                $srpd = '(' . number_format(abs($srp), 2) . ')';
            } else {
                $srpd = $srp == 0 ? '-' : number_format($srp, 2);
            }

            if ($discount < 0) { //ito yung negative
                $discountd = '(' . number_format(abs($discount), 2) . ')';
            } else {
                $discountd = $discount == 0 ? '-' : number_format($discount, 2);
            }

            if ($tlsrp < 0) { //ito yung negative
                $tlsrpd = '(' . number_format(abs($tlsrp), 2) . ')';
            } else {
                $tlsrpd = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
            }

            if ($comap < 0) { //ito yung negative
                $comapd = '(' . number_format(abs($comap), 2) . ')';
            } else {
                $comapd = $comap == 0 ? '-' : number_format($comap, 2);
            }

            if ($comm1 < 0) { //ito yung negative
                $comm1d = '(' . number_format(abs($comm1), 2) . ')';
            } else {
                $comm1d = $comm1 == 0 ? '-' : number_format($comm1, 2);
            }

            if ($percentage < 0) { //ito yung negative
                $percentaged = '(' . number_format(abs($percentage), 2) . ')';
            } else {
                $percentaged = $percentage == 0 ? '-' : number_format($percentage, 2);
            }

            if ($disp_qty3 < 0) { //ito yung negative
                $disp_qty3 = '(' . number_format(abs($disp_qty3), 2) . ')';
            } else {
                $disp_qty3 = $disp_qty3 == 0 ? '-' : number_format($disp_qty3, 2);
            }

            if ($display_mos3 < 0) { //ito yung negative
                $display_mos3 = '(' . number_format(abs($display_mos3), 2) . ')';
            } else {
                $display_mos3 = $display_mos3 == 0 ? '-' : number_format($display_mos3, 2);
            }

            if ($disp_qty6 < 0) { //ito yung negative
                $disp_qty6 = '(' . number_format(abs($disp_qty6), 2) . ')';
            } else {
                $disp_qty6 = $disp_qty6 == 0 ? '-' : number_format($disp_qty6, 2);
            }

            if ($display_mos6 < 0) { //ito yung negative
                $display_mos6 = '(' . number_format(abs($display_mos6), 2) . ')';
            } else {
                $display_mos6 = $display_mos6 == 0 ? '-' : number_format($display_mos6, 2);
            }

            if ($disp_qty9 < 0) { //ito yung negative
                $disp_qty9 = '(' . number_format(abs($disp_qty9), 2) . ')';
            } else {
                $disp_qty9 = $disp_qty9 == 0 ? '-' : number_format($disp_qty9, 2);
            }

            if ($display_mos9 < 0) { //ito yung negative
                $display_mos9 = '(' . number_format(abs($display_mos9), 2) . ')';
            } else {
                $display_mos9 = $display_mos9 == 0 ? '-' : number_format($display_mos9, 2);
            }


            if ($disp_financezeroqty < 0) { //ito yung negative
                $disp_financezeroqty = '(' . number_format(abs($disp_financezeroqty), 2) . ')';
            } else {
                $disp_financezeroqty = $disp_financezeroqty == 0 ? '-' : number_format($disp_financezeroqty, 2);
            }
            if ($disp_financezero < 0) { //ito yung negative
                $disp_financezero = '(' . number_format(abs($disp_financezero), 2) . ')';
            } else {
                $disp_financezero = $disp_financezero == 0 ? '-' : number_format($disp_financezero, 2);
            }
            if ($disp_financeregularqty < 0) { //ito yung negative
                $disp_financeregularqty = '(' . number_format(abs($disp_financeregularqty), 2) . ')';
            } else {
                $disp_financeregularqty = $disp_financeregularqty == 0 ? '-' : number_format($disp_financeregularqty, 2);
            }
            if ($disp_financeregular < 0) { //ito yung negative
                $disp_financeregular = '(' . number_format(abs($disp_financeregular), 2) . ')';
            } else {
                $disp_financeregular = $disp_financeregular == 0 ? '-' : number_format($disp_financeregular, 2);
            }

            if ($disp_qtyst < 0) { //ito yung negative
                $disp_qtyst = '(' . number_format(abs($disp_qtyst), 2) . ')';
            } else {
                $disp_qtyst = $disp_qtyst == 0 ? '-' : number_format($disp_qtyst, 2);
            }

            if ($display_mos < 0) { //ito yung negative
                $display_mos = '(' . number_format(abs($display_mos), 2) . ')';
            } else {
                $display_mos = $display_mos == 0 ? '-' : number_format($display_mos, 2);
            }

            if ($grossap2 < 0) { //ito yung negative
                $grossap2d = '(' . number_format(abs($grossap2), 2) . ')';
            } else {
                $grossap2d = $grossap2 == 0 ? '-' : number_format($grossap2, 2);
            }

            if ($disp_netpayable < 0) { //ito yung negative
                $disp_netpayable = '(' . number_format(abs($disp_netpayable), 2) . ')';
            } else {
                $disp_netpayable = $disp_netpayable == 0 ? '-' : number_format($disp_netpayable, 2);
            }


            $str .= $this->reporter->col($data->dateid, '50', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->supcode, '50', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->suppliername, '87', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->barcode, '60', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '3', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemdesc, '90', '', false, $border, '', 'LT', $font, $font_size, '', '', '', '', '', 'min-width:73px;max-width:73px;word-wrap:break-word;');
            $str .= $this->reporter->col($totalsoldd, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($srpd, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($discountd, '35', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($tlsrpd, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($comapd, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col(($comm1 == 0) ? '-' : number_format($comm1, 2).')', '35', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($comm1d, '35', '', false, $border, '', 'RT', $font, $font_size, '', '', '');

            $str .= $this->reporter->col($comapd, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($percentaged, '35', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty3, '35', '', false, $border, '', 'CT', $font, $font_size, '', '', '');  //QTY
            $str .= $this->reporter->col($display_mos3, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', ''); //3 Mos
            $str .= $this->reporter->col($disp_qty6, '35', '', false, $border, '', 'CT', $font, $font_size, '', '', '');  //QTY
            $str .= $this->reporter->col($display_mos6, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', ''); //6 Mos
            $str .= $this->reporter->col($disp_qty9, '35', '', false, $border, '', 'RT', $font, $font_size, '', '', ''); //QTY
            $str .= $this->reporter->col($display_mos9, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', ''); //9 Mos/12 Mos
            $str .= $this->reporter->col($disp_financezeroqty, '35', '', false, $border, '', 'CT', $font, $font_size, '', '', ''); //QTY
            $str .= $this->reporter->col($disp_financezero, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', ''); //finance 0%
            $str .= $this->reporter->col($disp_financeregularqty, '35', '', false, $border, '', 'CT', $font, $font_size, '', '', ''); //QTY
            $str .= $this->reporter->col($disp_financeregular, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', ''); //finance RTegular
            $str .= $this->reporter->col($disp_qtyst, '30', '', false, $border, '', 'CT', $font, $font_size, '', '', ''); //QTY
            $str .= $this->reporter->col($display_mos, '40', '', false, $border, '', 'RT', $font, $font_size, '', '', ''); //debit/straight
            $str .= $this->reporter->col($grossap2d, '35', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_netpayable, '70', '', false, $border, '', 'RT', $font, $font_size, '', '', '');


            $totalsld += $totalsold;
            $totaldiscount += $discount;
            $tlssrp += $srp;
            $tllsrp += $tlsrp;
            $tlcost += $comap;
            $comm1here += $comm1;
            $gross += $comap;
            $percenthere += $percentage;
            $commm2 += $grossap2;
            $netp += $netpayable;

            $tldqty3 += $qty3;
            $tldmos3 += $mos3;
            $tldqty6 += $qty6;
            $tldmos6 += $mos6;

            $tldqty9 += $qty9;
            $tldmos9 += $mos9;

            $tldqtyfzero += $financezeroqty;
            $tldfzero += $financezero;

            $tldqtyfreg += $financeregularqty;
            $tldfreg += $financeregular;

            $tldqtyst += $qtyst;
            $tldmos += $most;

            // $counterkwek += $itemlines;

            // $this->reporter->linecounter += $itemlines;
        } //end foreach
        // var_dump($tllsrp);

        if ($totalsld < 0) { //ito yung negative
            $totalsld = '(' . number_format(abs($totalsld), 2) . ')';
        } else {
            $totalsld = $totalsld == 0 ? '-' : number_format($totalsld, 2);
        }

        if ($totaldiscount < 0) { //ito yung negative
            $totaldiscount = '(' . number_format(abs($totaldiscount), 2) . ')';
        } else {
            $totaldiscount = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
        }
        if ($tlcost < 0) { //ito yung negative
            $tlcost = '(' . number_format(abs($tlcost), 2) . ')';
        } else {
            $tlcost = $tlcost == 0 ? '-' : number_format($tlcost, 2);
        }

        if ($gross < 0) { //ito yung negative
            $gross = '(' . number_format(abs($gross), 2) . ')';
        } else {
            $gross = $gross == 0 ? '-' : number_format($gross, 2);
        }

        if ($tldqty3 < 0) { //ito yung negative
            $tldqty3 = '(' . number_format(abs($tldqty3), 2) . ')';
        } else {
            $tldqty3 = $tldqty3 == 0 ? '-' : number_format($tldqty3, 2);
        }


        if ($tldqty6 < 0) { //ito yung negative
            $tldqty6 = '(' . number_format(abs($tldqty6), 2) . ')';
        } else {
            $tldqty6 = $tldqty6 == 0 ? '-' : number_format($tldqty6, 2);
        }

        if ($tldqty9 < 0) { //ito yung negative
            $tldqty9 = '(' . number_format(abs($tldqty9), 2) . ')';
        } else {
            $tldqty9 = $tldqty9 == 0 ? '-' : number_format($tldqty9, 2);
        }

        if ($tldqtyfzero < 0) { //ito yung negative
            $tldqtyfzero = '(' . number_format(abs($tldqtyfzero), 2) . ')';
        } else {
            $tldqtyfzero = $tldqtyfzero == 0 ? '-' : number_format($tldqtyfzero, 2);
        }

        if ($tldqtyfreg < 0) { //ito yung negative
            $tldqtyfreg = '(' . number_format(abs($tldqtyfreg), 2) . ')';
        } else {
            $tldqtyfreg = $tldqtyfreg == 0 ? '-' : number_format($tldqtyfreg, 2);
        }

        if ($tldqtyst < 0) { //ito yung negative
            $tldqtyst = '(' . number_format(abs($tldqtyst), 2) . ')';
        } else {
            $tldqtyst = $tldqtyst == 0 ? '-' : number_format($tldqtyst, 2);
        }

        if ($commm2 < 0) { //ito yung negative
            $commm2 = '(' . number_format(abs($commm2), 2) . ')';
        } else {
            $commm2 = $commm2 == 0 ? '-' : number_format($commm2, 2);
        }


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRANDTOTAL', '50', '', false, $border, 'TB', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '87', '', false, $border, 'TB', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '3', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '90', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalsld, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totaldiscount, '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tlcost, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gross, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldqty3, '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', '');  //QTY
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //3 Mos
        $str .= $this->reporter->col($tldqty6, '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', '');  //QTY
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //6 Mos
        $str .= $this->reporter->col($tldqty9, '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //9 Mos/12 Mos
        $str .= $this->reporter->col($tldqtyfzero, '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //finance 0%
        $str .= $this->reporter->col($tldqtyfreg, '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //finance RTegular
        $str .= $this->reporter->col($tldqtyst, '30', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //debit/straight
        $str .= $this->reporter->col($commm2, '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '', '', '', 'min-width:70px;max-width:70px;word-wrap:break-word;');
        $str .= $this->reporter->endrow();



        if ($tlssrp < 0) { //ito yung negative
            $tlssrp = '(' . number_format(abs($tlssrp), 2) . ')';
        } else {
            $tlssrp = $tlssrp == 0 ? '-' : number_format($tlssrp, 2);
        }

        if ($tllsrp < 0) { //ito yung negative
            $tllsrp = '(' . number_format(abs($tllsrp), 2) . ')';
        } else {
            $tllsrp = $tllsrp == 0 ? '-' : number_format($tllsrp, 2);
        }

        if ($comm1here < 0) { //ito yung negative
            $comm1here = '(' . number_format(abs($comm1here), 2) . ')';
        } else {
            $comm1here = $comm1here == 0 ? '-' : number_format($comm1here, 2);
        }
        if ($percenthere < 0) { //ito yung negative
            $percenthere = '(' . number_format(abs($percenthere), 2) . ')';
        } else {
            $percenthere = $percenthere == 0 ? '-' : number_format($percenthere, 2);
        }

        if ($tldmos3 < 0) { //ito yung negative
            $tldmos3 = '(' . number_format(abs($tldmos3), 2) . ')';
        } else {
            $tldmos3 = $tldmos3 == 0 ? '-' : number_format($tldmos3, 2);
        }

        if ($tldmos6 < 0) { //ito yung negative
            $tldmos6 = '(' . number_format(abs($tldmos6), 2) . ')';
        } else {
            $tldmos6 = $tldmos6 == 0 ? '-' : number_format($tldmos6, 2);
        }

        if ($tldmos9 < 0) { //ito yung negative
            $tldmos9 = '(' . number_format(abs($tldmos9), 2) . ')';
        } else {
            $tldmos9 = $tldmos9 == 0 ? '-' : number_format($tldmos9, 2);
        }

        if ($tldfzero < 0) { //ito yung negative
            $tldfzero = '(' . number_format(abs($tldfzero), 2) . ')';
        } else {
            $tldfzero = $tldfzero == 0 ? '-' : number_format($tldfzero, 2);
        }

        if ($tldfreg < 0) { //ito yung negative
            $tldfreg = '(' . number_format(abs($tldfreg), 2) . ')';
        } else {
            $tldfreg = $tldfreg == 0 ? '-' : number_format($tldfreg, 2);
        }

        if ($tldmos < 0) { //ito yung negative
            $tldmos = '(' . number_format(abs($tldmos), 2) . ')';
        } else {
            $tldmos = $tldmos == 0 ? '-' : number_format($tldmos, 2);
        }

        if ($netp < 0) { //ito yung negative
            $netp = '(' . number_format(abs($netp), 2) . ')';
        } else {
            $netp = $netp == 0 ? '-' : number_format($netp, 2);
        }



        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '87', '', false, $border, 'TB', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '3', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '90', '', false, $border, 'TB', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tllsrp, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($comm1here, '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($percenthere, '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', '');  //QTY
        $str .= $this->reporter->col($tldmos3, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //3 Mos
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', '');  //QTY
        $str .= $this->reporter->col($tldmos6, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //6 Mos
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col($tldmos9, '50', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //9 Mos/12 Mos
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col($tldfzero, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //finance 0%
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col($tldfreg, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //finance RTegular
        $str .= $this->reporter->col('', '30', '', false, $border, 'TB', 'CT', $font, $font_size, '', '', ''); //QTY
        $str .= $this->reporter->col($tldmos, '40', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', ''); //debit/straight
        $str .= $this->reporter->col('', '35', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($netp, '70', '', false, $border, 'TB', 'RT', $font, $font_size, '', '', '', '', '', 'min-width:70px;max-width:70px;word-wrap:break-word;');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // if ($countitem  <= $page) {
        //     $test = $page - $countitem;
        //     $space = $test;
        //     for ($i = 0; $i < $space; $i++) {
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //     }
        // }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        $str .= '</div>';
        return $str;
    }

    private function detail_per_item_displayHeader($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        // $letter = $this->reporter->letterhead($center, $username, $config);
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        // $font = $this->companysetup->getrptfont($config['params']);
        $font = 'calibri';

        $font_size = '8';
        $itemstatus = $config['params']['dataparams']['itemstatus'];

        $client = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];


        if ($client != "") {
            $clientname = $client . '~' . $config['params']['dataparams']['clientname'];
        } else {
            $clientname = 'ALL SUPPLIER';
        }

        if ($itemstatus == '(0)') {
            $itemstatus = 'ACTIVE ITEMS';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'ACTIVE ITEMS';
        } else {
            $itemstatus = 'ALL ITEMS';
        }


        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1200';
        // $str = '<br>';


        $str .= $this->reporter->begintable($layoutsize);
        // $letter= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name . ' ' . strtoupper($headerdata[0]->address), '1000', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($itemstatus . ' - SALES REPORT DETAIL', '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $clientname, '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM: ' . $start, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO: ' . $end, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '680', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('CHARGES', '420', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '21', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Date', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Supplier Code', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Supplier Name ', '87', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Item Code', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '3', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Item Description', '90', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL Sold', '40', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Srp', '40', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Disc', '35', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL Srp', '40', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL Cost', '40', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Com 1', '35', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Gross Payable', '40', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Percentage', '35', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('3 Mos', '40', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('6 Mos', '40', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('9 Mos/12 Mos', '50', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Finance 0%', '40', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Finance Regular', '40', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '30', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Debit/ Straight', '40', '', false, $border, 'BT', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Com 2', '35', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Net Payable', '70', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('', '42', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    private function Summary_per_day_header($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        // $letter = $this->reporter->letterhead($center, $username, $config);
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '7';
        $itemstatus = $config['params']['dataparams']['itemstatus'];

        if ($itemstatus == '(0)') {
            $itemstatus = 'ACTIVE ITEMS';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'ACTIVE ITEMS';
        } else {
            $itemstatus = 'ALL ITEMS';
        }


        $client = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];


        if ($client != "") {
            $clientname = $client . '~' . $config['params']['dataparams']['clientname'];
        } else {
            $clientname = 'ALL SUPPLIER';
        }

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1200';

        $str .= $this->reporter->begintable($layoutsize);
        // $letter= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name . ' ' . strtoupper($headerdata[0]->address), '1000', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($itemstatus . ' - SALES REPORT SUMMARY PER DAY', '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $clientname, '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM:' . $start, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO:' . $end, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '656', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('CHARGES', '984', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '164', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL SOLD', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DISC', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL SRP', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL COST', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('COM 1', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('GROSS PAYABLE', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('PERCENTAGE', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('3 MOS', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('6 MOS', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('9 MOS/12 MOS', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FINANCE 0%', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FINANCE REGULAR', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DEBIT/STRAIGHT', '55', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('COM 2', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('NET PAYABLE', '55', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    public function Summary_per_day($config)
    {
        $result = $this->summaryperday($config);

        $border = '1px solid';
        $font = 'calibri';
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $font_size = '7';
        // $count = 40;
        // $page = 40;

        $count = 31;
        $page = 31;


        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1200';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= '<div style="position: relative;margin:0 0 38px 0;">';
        $str .= $this->Summary_per_day_header($config);
        $difference = $this->diff($start, $end);

        $dates = [];
        for ($i = 0; $i <= $difference; $i++) {
            $dates[] = date("Y-m-d", strtotime("+$i days", strtotime($start)));
        }

        // Group data per dateid
        $groupedData = [];
        foreach ($result as $data) {
            $groupedData[$data->dateid][] = $data;
        }

        // Grand totals
        $totalsld = $totaldiscount = $tlsrp = $tlcost = $comm1here = $gross = $percenthere = $commm2 = $tlcomap = $netp = 0;
        $tldqty3 = $tldmos3 = $tldqty6 = $tldmos6 = $tldqty9 = $tldmos9 = 0;
        $tldqtyfzero = $tldfzero = $tldqtyfreg = $tldfreg = $tldqtyst = $tldmos = 0;



        $gtotalsld = $gtotaldiscount = $gtlsrp = $gtlcost = $gcomm1here = 0;
        $ggross =   $gpercenthere = $gcommm2 =  $gnetp = 0;

        $gtldqty3 =  $gtldmos3 = $gtldqty6 = $gtldmos6 =  $gtldqty9 = 0;
        $gtldmos9 = $gtldqtyfzero = $gtldfzero = $gtldqtyfreg =  $gtldfreg = 0;
        $gtldqtyst =   $gtldmos =   $gtlcomap = 0;

        $counts = 0;
        $netpayable = 0;
        foreach ($dates as $date) {

            if (empty($groupedData[$date])) {
                continue; // Skip dates with no data
            }
            // // Daily totals
            $totalsold = $totaldisc = $totalsrp = $totalcost = $totalcom1 = 0;
            $grosspayable = $grossap2 = $netap = $dext = $comap = 0;
            $qty3 = $qty6 = $qty9 = $qtyst = 0;
            $mos3 = $mos6 = $mos9 = $most = 0;
            $financeregularqty = $financeregular = 0;
            $financezeroqty = $financezero = 0;
            $percentage = 0;
            $com1here = 0;
            $discount = 0;

            $percentage_total_per_day = 0;
            if (!empty($groupedData[$date])) {
                foreach ($groupedData[$date] as $data) {
                    $totalsold += $data->totalsold;
                    $totaldisc += $data->tldisc;
                    $totalsrp += $data->ext;
                    $totalcost += $data->totalcost;
                    // $totalcom1 += $data->comm1;
                    $grossap2 += $data->grossap2;
                    // $netap += $data->netap;
                    $dext += $data->ext;
                    $grosspayable += $data->grosspayable;
                    $comap += $data->grosspayable;


                    // CHARGES 
                    $raw = $data->terminalid;
                    $parts = explode('~', $raw);
                    $firstpart = $parts[0];
                    $banktype = $data->banktype;
                    $bankrate = $data->bankrate;
                    $cardcharge = $data->cardcharge;

                    $comm1 = $data->comm1;

                    $totalcom1 += $comm1;


                    switch ($firstpart) {
                        case 'CCASHALOALDI':
                        case 'CHOMECREDIT':
                        case 'CHOMECREDIT0':
                        case 'CCASHALO':
                        case 'DCASHALOALDI':
                        case 'DHOMECREDIT':
                        case 'DHOMECREDIT0':
                        case 'DCASHALO':
                        case 'CASHALOALDI':
                        case 'HOMECREDIT':
                        case 'HOMECREDIT0':
                        case 'CASHALO':
                            // case 'CAUGBGCASH':
                            if ($bankrate != 0) {
                                $financeregularqty += $data->totalsold;
                                $financeregular += $cardcharge;
                            } else {
                                $financezeroqty += $data->totalsold;
                                $financezero += $cardcharge;
                            }
                            break;
                        default:
                            if (!empty($banktype) && $cardcharge != 0) {
                                switch (strtoupper(str_replace(' ', '', $data->banktype))) {
                                    case '3MONS':
                                    case '3MOS':
                                        $qty3 += $data->totalsold;
                                        $mos3 += $cardcharge;
                                        break;
                                    case '6MONS':
                                    case '6MOS':     
                                        $qty6 += $data->totalsold;
                                        $mos6 += $cardcharge;
                                        break;
                                    case '9MONS':
                                    case '12MONS':
                                    case '9MOS':
                                    case '12MOS':
                                        $qty9 += $data->totalsold;
                                        $mos9 += $cardcharge;
                                        break;
                                    case 'STRAIGHT':
                                    case 'DEBIT':
                                    case 'AUBGCASH':
                                    case 'DEBIT/BANCNET':    
                                        $qtyst += $data->totalsold;
                                        $most += $cardcharge;
                                        break;
                                }
                            }
                            break;
                    }

                    $percentage_total_per_day += $data->percentage;

                    $display_mos3 = ($mos3 != 0) ? $mos3 : 0;
                    $display_mos6 = ($mos6 != 0) ? $mos6 : 0;
                    $display_mos9 = ($mos9 != 0) ? $mos9 : 0;
                    $display_mos = ($most != 0) ? $most : 0;

                    $disp_qty3  = ($qty3 != 0) ? $qty3 : 0;
                    $disp_qty6  = ($qty6 != 0) ? $qty6 : 0;
                    $disp_qty9  = ($qty9 != 0) ? $qty9 : 0;
                    $disp_qtyst = ($qtyst != 0) ? $qtyst : 0;


                    // $descount = $this->othersClass->Discount($totalsrp, $totaldisc);
                    // $discount = $data->disc;
                    // $display_discount = $discount != 0 ? $discount : 0;

                    // $percentage = $percentage_total_per_day;
                    // $com1here = $totalcom1;

                    // $grosspayable = $data->grosspayable;
                    // $charges = $grossap2 + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;

                    // $netpayable =  $grosspayable - $charges;



                    // $descount = $this->othersClass->Discount($totalsrp, $totaldisc);
                    // $discount = $data->disc;
                    // $display_discount = $discount != 0 ? abs($discount) : 0;

                    // $percentage = $percentage_total_per_day;
                    // $com1here = $totalcom1;

                    // $grosspayable = $data->grosspayable;
                    // $charges = $grossap2 + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;

                    // $netpayable =  $grosspayable - $charges;

                    $discount += $data->tldisc;
                    $display_discount = $discount != 0 ? $discount : 0;

                    $percentage = $percentage_total_per_day;
                    $com1here = $totalcom1;
                }
            }


            // $discount = $data->disc;
            // $display_discount = $discount != 0 ? $discount : 0;

            // $percentage = $percentage_total_per_day;
            // $com1here = $totalcom1;

            // $grosspayable = $data->grosspayable;
            // $charges = abs($grossap2) + abs($mos3) + abs($mos6) + abs($mos9) + abs($most) + abs($financeregular) + abs($financezero);
            $charges = $grossap2 + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;

            $netpayable =  $comap + $charges;



            if ($totalsold < 0) { //ito yung negative
                $totalsoldd = '(' . number_format(abs($totalsold), 2) . ')';
            } else {
                $totalsoldd = $totalsold == 0 ? '-' : number_format($totalsold, 2);
            }
            if ($display_discount < 0) { //ito yung negative
                $display_discount = '(' . number_format(abs($display_discount), 2) . ')';
            } else {
                $display_discount = $display_discount == 0 ? '-' : number_format($display_discount, 2);
            }

            if ($totalsrp < 0) { //ito yung negative
                $totalsrpd = '(' . number_format(abs($totalsrp), 2) . ')';
            } else {
                $totalsrpd = $totalsrp == 0 ? '-' : number_format($totalsrp, 2);
            }


            if ($grosspayable < 0) { //ito yung negative
                $grosspayabled = '(' . number_format(abs($grosspayable), 2) . ')';
            } else {
                $grosspayabled = $grosspayable == 0 ? '-' : number_format($grosspayable, 2);
            }

            if ($com1here < 0) { //ito yung negative
                $com1hered = '(' . number_format(abs($com1here), 2) . ')';
            } else {
                $com1hered = $com1here == 0 ? '-' : number_format($com1here, 2);
            }

            if ($percentage < 0) { //ito yung negative
                $percentaged = '(' . number_format(abs($percentage), 2) . ')';
            } else {
                $percentaged = $percentage == 0 ? '-' : number_format($percentage, 2);
            }

            if ($disp_qty3 < 0) { //ito yung negative
                $disp_qty3 = '(' . number_format(abs($disp_qty3), 2) . ')';
            } else {
                $disp_qty3 = $disp_qty3 == 0 ? '-' : number_format($disp_qty3, 2);
            }

            if ($display_mos3 < 0) { //ito yung negative
                $display_mos3 = '(' . number_format(abs($display_mos3), 2) . ')';
            } else {
                $display_mos3 = $display_mos3 == 0 ? '-' : number_format($display_mos3, 2);
            }
            if ($disp_qty6 < 0) { //ito yung negative
                $disp_qty6 = '(' . number_format(abs($disp_qty6), 2) . ')';
            } else {
                $disp_qty6 = $disp_qty6 == 0 ? '-' : number_format($disp_qty6, 2);
            }
            if ($display_mos6 < 0) { //ito yung negative
                $display_mos6 = '(' . number_format(abs($display_mos6), 2) . ')';
            } else {
                $display_mos6 = $display_mos6 == 0 ? '-' : number_format($display_mos6, 2);
            }
            if ($disp_qty9 < 0) { //ito yung negative
                $disp_qty9 = '(' . number_format(abs($disp_qty9), 2) . ')';
            } else {
                $disp_qty9 = $disp_qty9 == 0 ? '-' : number_format($disp_qty9, 2);
            }
            if ($display_mos9 < 0) { //ito yung negative
                $display_mos9 = '(' . number_format(abs($display_mos9), 2) . ')';
            } else {
                $display_mos9 = $display_mos9 == 0 ? '-' : number_format($display_mos9, 2);
            }
            if ($financezeroqty < 0) { //ito yung negative
                $financezeroqtyd = '(' . number_format(abs($financezeroqty), 2) . ')';
            } else {
                $financezeroqtyd = $financezeroqty == 0 ? '-' : number_format($financezeroqty, 2);
            }
            if ($financezero < 0) { //ito yung negative
                $financezerod = '(' . number_format(abs($financezero), 2) . ')';
            } else {
                $financezerod = $financezero == 0 ? '-' : number_format($financezero, 2);
            }
            if ($financeregularqty < 0) { //ito yung negative
                $financeregularqtyd = '(' . number_format(abs($financeregularqty), 2) . ')';
            } else {
                $financeregularqtyd = $financeregularqty == 0 ? '-' : number_format($financeregularqty, 2);
            }
            if ($financeregular < 0) { //ito yung negative
                $financeregulard = '(' . number_format(abs($financeregular), 2) . ')';
            } else {
                $financeregulard = $financeregular == 0 ? '-' : number_format($financeregular, 2);
            }
            if ($disp_qtyst < 0) { //ito yung negative
                $disp_qtyst = '(' . number_format(abs($disp_qtyst), 2) . ')';
            } else {
                $disp_qtyst = $disp_qtyst == 0 ? '-' : number_format($disp_qtyst, 2);
            }
            if ($display_mos < 0) { //ito yung negative
                $display_mos = '(' . number_format(abs($display_mos), 2) . ')';
            } else {
                $display_mos = $display_mos == 0 ? '-' : number_format($display_mos, 2);
            }
            if ($grossap2 < 0) { //ito yung negative
                $grossap2d = '(' . number_format(abs($grossap2), 2) . ')';
            } else {
                $grossap2d = $grossap2 == 0 ? '-' : number_format($grossap2, 2);
            }
            if ($netpayable < 0) { //ito yung negative
                $netpayabled = '(' . number_format(abs($netpayable), 2) . ')';
            } else {
                $netpayabled = $netpayable == 0 ? '-' : number_format($netpayable, 2);
            }

            // Display
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($date, '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($totalsoldd, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_discount, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($totalsrpd, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grosspayabled, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($com1hered, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grosspayabled, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($percentaged, '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty3, '55', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos3, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty6, '55', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos6, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty9, '55', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos9, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($financezeroqtyd, '55', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($financezerod, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($financeregularqtyd, '55', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($financeregulard, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qtyst, '55', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grossap2d, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($netpayabled, '55', '', false, $border, '', 'R', $font, $font_size, '', '', '');

            // Accumulate for grand total
            $totalsld += $totalsold;
            $totaldiscount += $discount;
            $tlsrp += $totalsrp;
            $tlcost += $totalcost;
            $comm1here += $com1here;
            $gross += $grosspayable;
            $percenthere += $percentage;
            $commm2 += $grossap2;
            $netp += $netpayable;

            $tldqty3 += $qty3;
            $tldmos3 += $mos3;
            $tldqty6 += $qty6;
            $tldmos6 += $mos6;
            $tldqty9 += $qty9;
            $tldmos9 += $mos9;
            $tldqtyfzero += $financezeroqty;
            $tldfzero += $financezero;
            $tldqtyfreg += $financeregularqty;
            $tldfreg += $financeregular;
            $tldqtyst += $qtyst;
            $tldmos += $most;

            $tlcomap += $comap;



            $gtotalsld += $totalsold;
            $gtotaldiscount += $discount;
            $gtlsrp += $totalsrp;
            $gtlcost += $totalcost;
            $gcomm1here += $com1here;
            $ggross += $grosspayable;
            $gpercenthere += $percentage;
            $gcommm2 += $grossap2;
            $gnetp += $netpayable;

            $gtldqty3 += $qty3;
            $gtldmos3 += $mos3;
            $gtldqty6 += $qty6;
            $gtldmos6 += $mos6;
            $gtldqty9 += $qty9;
            $gtldmos9 += $mos9;
            $gtldqtyfzero += $financezeroqty;
            $gtldfzero += $financezero;
            $gtldqtyfreg += $financeregularqty;
            $gtldfreg += $financeregular;
            $gtldqtyst += $qtyst;
            $gtldmos += $most;

            $gtlcomap += $comap;


            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                if ($totalsld < 0) { //ito yung negative
                    $totalsld = '(' . number_format(abs($totalsld), 2) . ')';
                } else {
                    $totalsld = $totalsld == 0 ? '-' : number_format($totalsld, 2);
                }
                if ($tlsrp < 0) { //ito yung negative
                    $tlsrp = '(' . number_format(abs($tlsrp), 2) . ')';
                } else {
                    $tlsrp = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
                }
                if ($comm1here < 0) { //ito yung negative
                    $comm1here = '(' . number_format(abs($comm1here), 2) . ')';
                } else {
                    $comm1here = $comm1here == 0 ? '-' : number_format($comm1here, 2);
                }
                if ($percenthere < 0) { //ito yung negative
                    $percenthere = '(' . number_format(abs($percenthere), 2) . ')';
                } else {
                    $percenthere = $percenthere == 0 ? '-' : number_format($percenthere, 2);
                }
                if ($tldmos3 < 0) { //ito yung negative
                    $tldmos3 = '(' . number_format(abs($tldmos3), 2) . ')';
                } else {
                    $tldmos3 = $tldmos3 == 0 ? '-' : number_format($tldmos3, 2);
                }
                if ($tldmos6 < 0) { //ito yung negative
                    $tldmos6 = '(' . number_format(abs($tldmos6), 2) . ')';
                } else {
                    $tldmos6 = $tldmos6 == 0 ? '-' : number_format($tldmos6, 2);
                }
                if ($tldmos9 < 0) { //ito yung negative
                    $tldmos9 = '(' . number_format(abs($tldmos9), 2) . ')';
                } else {
                    $tldmos9 = $tldmos9 == 0 ? '-' : number_format($tldmos9, 2);
                }

                if ($tldfzero < 0) { //ito yung negative
                    $tldfzero = '(' . number_format(abs($tldfzero), 2) . ')';
                } else {
                    $tldfzero = $tldfzero == 0 ? '-' : number_format($tldfzero, 2);
                }

                if ($tldfreg < 0) { //ito yung negative
                    $tldfreg = '(' . number_format(abs($tldfreg), 2) . ')';
                } else {
                    $tldfreg = $tldfreg == 0 ? '-' : number_format($tldfreg, 2);
                }

                if ($tldmos < 0) { //ito yung negative
                    $tldmos = '(' . number_format(abs($tldmos), 2) . ')';
                } else {
                    $tldmos = $tldmos == 0 ? '-' : number_format($tldmos, 2);
                }
                if ($netp < 0) { //ito yung negative
                    $netp = '(' . number_format(abs($netp), 2) . ')';
                } else {
                    $netp = $netp == 0 ? '-' : number_format($netp, 2);
                }
                // $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('TOTAL: ', '50', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col($totalsld, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tlsrp, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($comm1here, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($percenthere, '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos3, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos6, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos9, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfzero, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfreg, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($netp, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();


                if ($totaldiscount < 0) { //ito yung negative
                    $totaldiscount = '(' . number_format(abs($totaldiscount), 2) . ')';
                } else {
                    $totaldiscount = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
                }
                if ($tlcomap < 0) { //ito yung negative
                    $tlcomap = '(' . number_format(abs($tlcomap), 2) . ')';
                } else {
                    $tlcomap = $tlcomap == 0 ? '-' : number_format($tlcomap, 2);
                }
                if ($tldqty3 < 0) { //ito yung negative
                    $tldqty3 = '(' . number_format(abs($tldqty3), 2) . ')';
                } else {
                    $tldqty3 = $tldqty3 == 0 ? '-' : number_format($tldqty3, 2);
                }
                if ($tldqty6 < 0) { //ito yung negative
                    $tldqty6 = '(' . number_format(abs($tldqty6), 2) . ')';
                } else {
                    $tldqty6 = $tldqty6 == 0 ? '-' : number_format($tldqty6, 2);
                }

                if ($tldqty9 < 0) { //ito yung negative
                    $tldqty9 = '(' . number_format(abs($tldqty9), 2) . ')';
                } else {
                    $tldqty9 = $tldqty9 == 0 ? '-' : number_format($tldqty9, 2);
                }

                if ($tldqtyfzero < 0) { //ito yung negative
                    $tldqtyfzero = '(' . number_format(abs($tldqtyfzero), 2) . ')';
                } else {
                    $tldqtyfzero = $tldqtyfzero == 0 ? '-' : number_format($tldqtyfzero, 2);
                }


                if ($tldqtyfreg < 0) { //ito yung negative
                    $tldqtyfreg = '(' . number_format(abs($tldqtyfreg), 2) . ')';
                } else {
                    $tldqtyfreg = $tldqtyfreg == 0 ? '-' : number_format($tldqtyfreg, 2);
                }

                if ($tldqtyst < 0) { //ito yung negative
                    $tldqtyst = '(' . number_format(abs($tldqtyst), 2) . ')';
                } else {
                    $tldqtyst = $tldqtyst == 0 ? '-' : number_format($tldqtyst, 2);
                }

                if ($commm2 < 0) { //ito yung negative
                    $commm2 = '(' . number_format(abs($commm2), 2) . ')';
                } else {
                    $commm2 = $commm2 == 0 ? '-' : number_format($commm2, 2);
                }

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($totaldiscount, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tlcomap, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tlcomap, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty3, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty6, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty9, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfzero, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfreg, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyst, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($commm2, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $totalsld = $totaldiscount = $tlsrp = $tlcost = $comm1here = $gross = $percenthere = $commm2 = $tlcomap = $netp = 0;
                $tldqty3 = $tldmos3 = $tldqty6 = $tldmos6 = $tldqty9 = $tldmos9 = 0;
                $tldqtyfzero = $tldfzero = $tldqtyfreg = $tldfreg = $tldqtyst = $tldmos = 0;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
                $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= '</div>';
                $str .= $this->reporter->page_break();

                // $str .= '<br/>';
                // $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                $str .= '<br/>';
                $str .= $this->Summary_per_day_header($config, $layoutsize);
                $page += $count;
            }
            $counts++;
        }

        if ($totalsld < 0) { //ito yung negative
            $totalsld = '(' . number_format(abs($totalsld), 2) . ')';
        } else {
            $totalsld = $totalsld == 0 ? '-' : number_format($totalsld, 2);
        }
        if ($tlsrp < 0) { //ito yung negative
            $tlsrp = '(' . number_format(abs($tlsrp), 2) . ')';
        } else {
            $tlsrp = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
        }
        if ($comm1here < 0) { //ito yung negative
            $comm1here = '(' . number_format(abs($comm1here), 2) . ')';
        } else {
            $comm1here = $comm1here == 0 ? '-' : number_format($comm1here, 2);
        }
        if ($percenthere < 0) { //ito yung negative
            $percenthere = '(' . number_format(abs($percenthere), 2) . ')';
        } else {
            $percenthere = $percenthere == 0 ? '-' : number_format($percenthere, 2);
        }
        if ($tldmos3 < 0) { //ito yung negative
            $tldmos3 = '(' . number_format(abs($tldmos3), 2) . ')';
        } else {
            $tldmos3 = $tldmos3 == 0 ? '-' : number_format($tldmos3, 2);
        }
        if ($tldmos6 < 0) { //ito yung negative
            $tldmos6 = '(' . number_format(abs($tldmos6), 2) . ')';
        } else {
            $tldmos6 = $tldmos6 == 0 ? '-' : number_format($tldmos6, 2);
        }
        if ($tldmos9 < 0) { //ito yung negative
            $tldmos9 = '(' . number_format(abs($tldmos9), 2) . ')';
        } else {
            $tldmos9 = $tldmos9 == 0 ? '-' : number_format($tldmos9, 2);
        }

        if ($tldfzero < 0) { //ito yung negative
            $tldfzero = '(' . number_format(abs($tldfzero), 2) . ')';
        } else {
            $tldfzero = $tldfzero == 0 ? '-' : number_format($tldfzero, 2);
        }

        if ($tldfreg < 0) { //ito yung negative
            $tldfreg = '(' . number_format(abs($tldfreg), 2) . ')';
        } else {
            $tldfreg = $tldfreg == 0 ? '-' : number_format($tldfreg, 2);
        }

        if ($tldmos < 0) { //ito yung negative
            $tldmos = '(' . number_format(abs($tldmos), 2) . ')';
        } else {
            $tldmos = $tldmos == 0 ? '-' : number_format($tldmos, 2);
        }
        if ($netp < 0) { //ito yung negative
            $netp = '(' . number_format(abs($netp), 2) . ')';
        } else {
            $netp = $netp == 0 ? '-' : number_format($netp, 2);
        }
        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL: ', '50', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($totalsld, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tlsrp, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($comm1here, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($percenthere, '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldmos3, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldmos6, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldmos9, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldfzero, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldfreg, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldmos, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($netp, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();


        if ($totaldiscount < 0) { //ito yung negative
            $totaldiscount = '(' . number_format(abs($totaldiscount), 2) . ')';
        } else {
            $totaldiscount = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
        }
        if ($tlcomap < 0) { //ito yung negative
            $tlcomap = '(' . number_format(abs($tlcomap), 2) . ')';
        } else {
            $tlcomap = $tlcomap == 0 ? '-' : number_format($tlcomap, 2);
        }
        if ($tldqty3 < 0) { //ito yung negative
            $tldqty3 = '(' . number_format(abs($tldqty3), 2) . ')';
        } else {
            $tldqty3 = $tldqty3 == 0 ? '-' : number_format($tldqty3, 2);
        }
        if ($tldqty6 < 0) { //ito yung negative
            $tldqty6 = '(' . number_format(abs($tldqty6), 2) . ')';
        } else {
            $tldqty6 = $tldqty6 == 0 ? '-' : number_format($tldqty6, 2);
        }

        if ($tldqty9 < 0) { //ito yung negative
            $tldqty9 = '(' . number_format(abs($tldqty9), 2) . ')';
        } else {
            $tldqty9 = $tldqty9 == 0 ? '-' : number_format($tldqty9, 2);
        }

        if ($tldqtyfzero < 0) { //ito yung negative
            $tldqtyfzero = '(' . number_format(abs($tldqtyfzero), 2) . ')';
        } else {
            $tldqtyfzero = $tldqtyfzero == 0 ? '-' : number_format($tldqtyfzero, 2);
        }


        if ($tldqtyfreg < 0) { //ito yung negative
            $tldqtyfreg = '(' . number_format(abs($tldqtyfreg), 2) . ')';
        } else {
            $tldqtyfreg = $tldqtyfreg == 0 ? '-' : number_format($tldqtyfreg, 2);
        }

        if ($tldqtyst < 0) { //ito yung negative
            $tldqtyst = '(' . number_format(abs($tldqtyst), 2) . ')';
        } else {
            $tldqtyst = $tldqtyst == 0 ? '-' : number_format($tldqtyst, 2);
        }

        if ($commm2 < 0) { //ito yung negative
            $commm2 = '(' . number_format(abs($commm2), 2) . ')';
        } else {
            $commm2 = $commm2 == 0 ? '-' : number_format($commm2, 2);
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totaldiscount, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tlcomap, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tlcomap, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldqty3, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldqty6, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldqty9, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldqtyfzero, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldqtyfreg, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tldqtyst, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($commm2, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();







        // if ($gtotalsld < 0) { //ito yung negative
        //     $gtotalsld = '(' . number_format(abs($gtotalsld), 2) . ')';
        // } else {
        //     $gtotalsld = $gtotalsld == 0 ? '-' : number_format($gtotalsld, 2);
        // }
        // if ($gtlsrp < 0) { //ito yung negative
        //     $gtlsrp = '(' . number_format(abs($gtlsrp), 2) . ')';
        // } else {
        //     $gtlsrp = $gtlsrp == 0 ? '-' : number_format($gtlsrp, 2);
        // }
        // if ($gcomm1here < 0) { //ito yung negative
        //     $gcomm1here = '(' . number_format(abs($gcomm1here), 2) . ')';
        // } else {
        //     $gcomm1here = $gcomm1here == 0 ? '-' : number_format($gcomm1here, 2);
        // }
        // if ($gpercenthere < 0) { //ito yung negative
        //     $gpercenthere = '(' . number_format(abs($gpercenthere), 2) . ')';
        // } else {
        //     $gpercenthere = $gpercenthere == 0 ? '-' : number_format($gpercenthere, 2);
        // }
        // if ($gtldmos3 < 0) { //ito yung negative
        //     $gtldmos3 = '(' . number_format(abs($gtldmos3), 2) . ')';
        // } else {
        //     $gtldmos3 = $gtldmos3 == 0 ? '-' : number_format($gtldmos3, 2);
        // }
        // if ($gtldmos6 < 0) { //ito yung negative
        //     $gtldmos6 = '(' . number_format(abs($gtldmos6), 2) . ')';
        // } else {
        //     $gtldmos6 = $gtldmos6 == 0 ? '-' : number_format($gtldmos6, 2);
        // }
        // if ($gtldmos9 < 0) { //ito yung negative
        //     $gtldmos9 = '(' . number_format(abs($gtldmos9), 2) . ')';
        // } else {
        //     $gtldmos9 = $gtldmos9 == 0 ? '-' : number_format($gtldmos9, 2);
        // }

        // if ($gtldfzero < 0) { //ito yung negative
        //     $gtldfzero = '(' . number_format(abs($gtldfzero), 2) . ')';
        // } else {
        //     $gtldfzero = $gtldfzero == 0 ? '-' : number_format($gtldfzero, 2);
        // }

        // if ($gtldfreg < 0) { //ito yung negative
        //     $gtldfreg = '(' . number_format(abs($gtldfreg), 2) . ')';
        // } else {
        //     $gtldfreg = $gtldfreg == 0 ? '-' : number_format($gtldfreg, 2);
        // }

        // if ($gtldmos < 0) { //ito yung negative
        //     $gtldmos = '(' . number_format(abs($gtldmos), 2) . ')';
        // } else {
        //     $gtldmos = $gtldmos == 0 ? '-' : number_format($gtldmos, 2);
        // }
        // if ($gnetp < 0) { //ito yung negative
        //     $gnetp = '(' . number_format(abs($gnetp), 2) . ')';
        // } else {
        //     $gnetp = $gnetp == 0 ? '-' : number_format($gnetp, 2);
        // }
        // // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('GRANDTOTAL: ', '50', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col($gtotalsld, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtlsrp, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gcomm1here, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gpercenthere, '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldmos3, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldmos6, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldmos9, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldfzero, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldfreg, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldmos, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gnetp, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->endrow();


        // if ($gtotaldiscount < 0) { //ito yung negative
        //     $gtotaldiscount = '(' . number_format(abs($gtotaldiscount), 2) . ')';
        // } else {
        //     $gtotaldiscount = $gtotaldiscount == 0 ? '-' : number_format($gtotaldiscount, 2);
        // }
        // if ($gtlcomap < 0) { //ito yung negative
        //     $gtlcomap = '(' . number_format(abs($gtlcomap), 2) . ')';
        // } else {
        //     $gtlcomap = $gtlcomap == 0 ? '-' : number_format($gtlcomap, 2);
        // }
        // if ($gtldqty3 < 0) { //ito yung negative
        //     $gtldqty3 = '(' . number_format(abs($gtldqty3), 2) . ')';
        // } else {
        //     $gtldqty3 = $gtldqty3 == 0 ? '-' : number_format($gtldqty3, 2);
        // }
        // if ($gtldqty6 < 0) { //ito yung negative
        //     $gtldqty6 = '(' . number_format(abs($gtldqty6), 2) . ')';
        // } else {
        //     $gtldqty6 = $gtldqty6 == 0 ? '-' : number_format($gtldqty6, 2);
        // }

        // if ($gtldqty9 < 0) { //ito yung negative
        //     $gtldqty9 = '(' . number_format(abs($gtldqty9), 2) . ')';
        // } else {
        //     $gtldqty9 = $gtldqty9 == 0 ? '-' : number_format($gtldqty9, 2);
        // }

        // if ($gtldqtyfzero < 0) { //ito yung negative
        //     $gtldqtyfzero = '(' . number_format(abs($gtldqtyfzero), 2) . ')';
        // } else {
        //     $gtldqtyfzero = $gtldqtyfzero == 0 ? '-' : number_format($gtldqtyfzero, 2);
        // }


        // if ($gtldqtyfreg < 0) { //ito yung negative
        //     $gtldqtyfreg = '(' . number_format(abs($gtldqtyfreg), 2) . ')';
        // } else {
        //     $gtldqtyfreg = $gtldqtyfreg == 0 ? '-' : number_format($gtldqtyfreg, 2);
        // }

        // if ($gtldqtyst < 0) { //ito yung negative
        //     $gtldqtyst = '(' . number_format(abs($gtldqtyst), 2) . ')';
        // } else {
        //     $gtldqtyst = $gtldqtyst == 0 ? '-' : number_format($gtldqtyst, 2);
        // }

        // if ($gcommm2 < 0) { //ito yung negative
        //     $gcommm2 = '(' . number_format(abs($gcommm2), 2) . ')';
        // } else {
        //     $gcommm2 = $gcommm2 == 0 ? '-' : number_format($gcommm2, 2);
        // }

        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtotaldiscount, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtlcomap, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtlcomap, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldqty3, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldqty6, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldqty9, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldqtyfzero, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldqtyfreg, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gtldqtyst, '55', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col($gcommm2, '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '55', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();




        // if ($counts < $page) { //kapag mas mababa yung page 
        //     $test = $page - $counts;
        //     $tryy = $test / 2;
        //     $full = $test;
        //     // $full = $test +  $tryy;
        //     for ($i = 0; $i < $full; $i++) {
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //     }
        // } else {
        // $test = $page - $counts;
        // $full = $test - 5; // dahil 40 pabbaba
        // for ($i = 0; $i < $full; $i++) {
        //     $str .= $this->reporter->begintable($layoutsize);
        //     $str .= $this->reporter->startrow();
        //     $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        //     $str .= $this->reporter->endrow();
        //     $str .= $this->reporter->endtable();
        // }
        // }
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
        // $str .= $this->reporter->col("<div style='padding-bottom:50px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $str .= '</div>';
        // $str .= '<div style="position: relative;margin:80px 0 10px 0;">';
        $str .= '</br> </br> </br>';
        //  $str .= '</br>';
        $str .= "<div style='position:absolute; bottom:60px'>";
        $sign = URL::to('/images/homeworks/checked.png');
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="70px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '5', 'B', '', '1px');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        // $str .= $this->lastfooter($config, $layoutsize, $font, $font_size, $border);
        $str .= $this->reporter->endreport();
        // $str .= '</div>';
        $str .= '</div>';
        return $str;
    }


    private function vendor_summary_report_header($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        // $letter = $this->reporter->letterhead($center, $username, $config);
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '8';
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        $channel = $config['params']['dataparams']['channel'];

        if ($itemstatus == '(0)') {
            $itemstatus = 'ACTIVE ITEMS';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'ACTIVE ITEMS';
        } else {
            $itemstatus = 'ALL ITEMS';
        }





        $client = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];

        if ($client != "") {
            $clientname = $client . '~' . $config['params']['dataparams']['clientname'];
        } else {
            $clientname = 'ALL SUPPLIER';
        }



        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1500';

        // $str = '<br>';

        $str .= $this->reporter->begintable($layoutsize);
        // $letter= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name . ' ' . strtoupper($headerdata[0]->address), '600', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '1239', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');//939
        $str .= $this->reporter->col($itemstatus . ' - VENDOR SUMMARY REPORT', '361', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $clientname, '1250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');//950
        $str .= $this->reporter->col('FROM:' . $start, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO:' . $end, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '980', '', false, $border, '', 'C', $font, $font_size, '', '', ''); //680
        $str .= $this->reporter->col('CHARGES', '420', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '160', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(); //23
        $str .= $this->reporter->col('Branch', '210', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', ''); //60
        $str .= $this->reporter->col('Supplier Code', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Supplier Name', '237', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');//87
        $str .= $this->reporter->col('TTL Sold', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Srp', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Disc', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL Srp', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Com 1', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Gross Payable', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('3 Mos', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('6 Mos', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('9 Mos/12 Mos', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Finance 0%', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Finance Regular', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Qty', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Debit/ Straight', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Com 2', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Net Payable', '70', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    public function vendor_summary_report($config)
    {
        $result = $this->vendor_summaryqry($config);


        $border = '1px solid';
        $font = 'calibri';
        $font_size = '7';
        $page = 43;
        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1500';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:40px');
        $str .= '<div style="position: relative;margin:0 0 38px 0;">'; //TOP RIGHT BOTTOM LEFT
        $str .= $this->vendor_summary_report_header($config);
        $suppl = $this->supp($config);


        $supplier = [];
        foreach ($suppl as $supply) {
            $supplier[] = $supply->suppcode;
        }
        
        $groupedData = [];
        foreach ($result as $data) {
            $groupedData[$data->channel][$data->filter][] = $data;
        }
        // Grand totals
        $gtotalsld = $gstllsrp = $gtotaldiscount = $gtlsrp = $gtlcost = $gcomm1here = $ggross = $gcommm2 = $gtotalcomap = $gnetp = 0;
        $gtldqty3 = $gtldmos3 = $gtldqty6 = $gtldmos6 = $gtldqty9 = $gtldmos9 = 0;
        $gtldqtyfzero = $gtldfzero = $gtldqtyfreg = $gtldfreg = $gtldqtyst = $gtldmos = 0;


        $sname = '';
        $count = 0;


        foreach ($groupedData as $channel => $Group) {
            $stotalsold = $stllsrp = $stotaldisc = $stotalsrp = $stotalcost = $stotalcom1 = 0;
            $sgrosspayable = $sgrossap2 = $snetap = $sdext = $scomap = 0;
            $sqty3 = $sqty6 = $sqty9 = $sqtyst = 0;
            $smos3 = $smos6 = $smos9 = $smost = 0;
            $sfinanceregularqty = $sfinanceregular = 0;
            $sfinancezeroqty = $sfinancezero = 0;

            if ($count > $page || ($sname != '' && $sname != $channel)) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
                $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= '</div>';
                $str .= $this->reporter->page_break();
                // $str .= '<br/>';
                $str .= '<div style="position: relative;margin:0 0 40px 0;">';
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= $this->vendor_summary_report_header($config); // ulit header para sa new page
                $count = 0;
            }


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($channel, '50', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B');
            $str .= $this->reporter->col('', '1450', null, false, '1px solid', '', 'L', $font, $font_size, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            foreach ($supplier as $suppliers) {
                if (empty($Group[$suppliers])) continue;

                $totalsold  = $totaldisc = $totalsrp = $totalcost = $totalcom1 = 0;
                $grosspayable = $grossap2  = $dext = $comap = $srp = 0;
                $qty3 = $qty6 = $qty9 = $qtyst = 0;
                $mos3 = $mos6 = $mos9 = $most = 0;
                $financeregularqty = $financeregular = 0;
                $financezeroqty = $financezero = 0;

                foreach ($Group[$suppliers] as $data) {

                    $totalsold += $data->totalsold;
                    $totaldisc += $data->tldisc;
                    $totalsrp += $data->ext;
                    $totalcost += $data->totalcost;
                    $grossap2 += $data->grossap2;
                    $dext += $data->ext;
                    $comap += $data->grosspayable;
                    $srp += $data->srp;

                    // CHARGES 
                    $raw = $data->terminalid;
                    $parts = explode('~', $raw);
                    $firstpart = $parts[0];
                    $banktype = $data->banktype;
                    $bankrate = $data->bankrate;
                    $cardcharge = $data->cardcharge;

                    switch ($firstpart) {
                        case 'CCASHALOALDI':
                        case 'CHOMECREDIT':
                        case 'CHOMECREDIT0':
                        case 'CCASHALO':
                        case 'DCASHALOALDI':
                        case 'DHOMECREDIT':
                        case 'DHOMECREDIT0':
                        case 'DCASHALO':
                        case 'CASHALOALDI':
                        case 'HOMECREDIT':
                        case 'HOMECREDIT0':
                        case 'CASHALO':
                            // case 'CAUGBGCASH':
                            if ($bankrate != 0) {
                                $financeregularqty += $data->totalsold;
                                $financeregular += $cardcharge;
                            } else {
                                $financezeroqty += $data->totalsold;
                                $financezero += $cardcharge;
                            }
                            break;
                        default:
                            if (!empty($banktype) && $cardcharge != 0) {
                                switch (strtoupper(str_replace(' ', '', $data->banktype))) {
                                    case '3MONS':
                                    case '3MOS':    
                                        $qty3 += $data->totalsold;
                                        $mos3 += $cardcharge;
                                        break;
                                    case '6MONS':
                                    case '6MOS':     
                                        $qty6 += $data->totalsold;
                                        $mos6 += $cardcharge;
                                        break;
                                    case '9MONS':
                                    case '12MONS':
                                    case '9MOS':
                                    case '12MOS':
                                        $qty9 += $data->totalsold;
                                        $mos9 += $cardcharge;
                                        break;
                                    case 'STRAIGHT':
                                    case 'DEBIT':
                                    case 'AUBGCASH':
                                    case 'DEBIT/BANCNET':     
                                        $qtyst += $data->totalsold;
                                        $most += $cardcharge;
                                        break;
                                }
                            }
                            break;
                    }

                    $comm1 = $data->comm1;
                    $totalcom1 += $comm1;
                }

                if ($count >= $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
                    $str .= $this->reporter->col("<div style='padding-bottom:50px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '</div>';
                    $str .= $this->reporter->page_break();
                    $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                    $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                    $str .= $this->vendor_summary_report_header($config); // ulit header para sa new page

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($channel, '50', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B');
                    $str .= $this->reporter->col('', '1450', null, false, '1px solid', '', 'L', $font, $font_size, 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $count = 0;
                }


                $display_mos3 = ($mos3 != 0) ? $mos3 : 0;
                $display_mos6 = ($mos6 != 0) ? $mos6 : 0;
                $display_mos9 = ($mos9 != 0) ? $mos9 : 0;
                $display_mos = ($most != 0) ? $most : 0;

                $disp_qty3  = ($qty3 != 0) ? $qty3 : 0;
                $disp_qty6  = ($qty6 != 0) ? $qty6 : 0;
                $disp_qty9  = ($qty9 != 0) ? $qty9 : 0;
                $disp_qtyst = ($qtyst != 0) ? $qtyst : 0;

                $disp_financeregularqty = ($financeregularqty != 0) ? $financeregularqty : 0;
                $disp_financeregular    = ($financeregular != 0)    ? $financeregular : 0;

                $disp_financezeroqty    = ($financezeroqty != 0)    ? $financezeroqty   : 0;
                $disp_financezero       = ($financezero != 0)       ? $financezero     : 0;


                $discount = $totaldisc;
                $discount = ($discount != 0) ? $discount : 0;


                $charges = $grossap2 + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;
                $netpayable = $comap + $charges;
                $disp_netpayable = $netpayable;
                $com1here = $totalcom1;
                $tlsrp      = ($dext != 0) ? floatval($dext) : 0;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();

                if ($totalsold < 0) { //ito yung negative
                    $totalsoldd = '(' . number_format(abs($totalsold), 2) . ')';
                } else {
                    $totalsoldd = $totalsold == 0 ? '-' : number_format($totalsold, 2);
                }
                if ($srp < 0) { //ito yung negative
                    $srpd = '(' . number_format(abs($srp), 2) . ')';
                } else {
                    $srpd = $srp == 0 ? '-' : number_format($srp, 2);
                }

                if ($discount < 0) { //ito yung negative
                    $discountd = '(' . number_format(abs($discount), 2) . ')';
                } else {
                    $discountd = $discount == 0 ? '-' : number_format($discount, 2);
                }
                if ($tlsrp < 0) { //ito yung negative
                    $tlsrpd = '(' . number_format(abs($tlsrp), 2) . ')';
                } else {
                    $tlsrpd = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
                }
                if ($com1here < 0) { //ito yung negative
                    $com1hered = '(' . number_format(abs($com1here), 2) . ')';
                } else {
                    $com1hered = $com1here == 0 ? '-' : number_format($com1here, 2);
                }
                if ($comap < 0) { //ito yung negative
                    $comapd = '(' . number_format(abs($comap), 2) . ')';
                } else {
                    $comapd = $comap == 0 ? '-' : number_format($comap, 2);
                }
                if ($disp_qty3 < 0) { //ito yung negative
                    $disp_qty3 = '(' . number_format(abs($disp_qty3), 2) . ')';
                } else {
                    $disp_qty3 = $disp_qty3 == 0 ? '-' : number_format($disp_qty3, 2);
                }
                if ($display_mos3 < 0) { //ito yung negative
                    $display_mos3 = '(' . number_format(abs($display_mos3), 2) . ')';
                } else {
                    $display_mos3 = $display_mos3 == 0 ? '-' : number_format($display_mos3, 2);
                }
                if ($disp_qty6 < 0) { //ito yung negative
                    $disp_qty6 = '(' . number_format(abs($disp_qty6), 2) . ')';
                } else {
                    $disp_qty6 = $disp_qty6 == 0 ? '-' : number_format($disp_qty6, 2);
                }
                if ($display_mos6 < 0) { //ito yung negative
                    $display_mos6 = '(' . number_format(abs($display_mos6), 2) . ')';
                } else {
                    $display_mos6 = $display_mos6 == 0 ? '-' : number_format($display_mos6, 2);
                }
                if ($disp_qty9 < 0) { //ito yung negative
                    $disp_qty9 = '(' . number_format(abs($disp_qty9), 2) . ')';
                } else {
                    $disp_qty9 = $disp_qty9 == 0 ? '-' : number_format($disp_qty9, 2);
                }
                if ($display_mos9 < 0) { //ito yung negative
                    $display_mos9 = '(' . number_format(abs($display_mos9), 2) . ')';
                } else {
                    $display_mos9 = $display_mos9 == 0 ? '-' : number_format($display_mos9, 2);
                }
                if ($disp_financezeroqty < 0) { //ito yung negative
                    $disp_financezeroqty = '(' . number_format(abs($disp_financezeroqty), 2) . ')';
                } else {
                    $disp_financezeroqty = $disp_financezeroqty == 0 ? '-' : number_format($disp_financezeroqty, 2);
                }
                if ($disp_financezero < 0) { //ito yung negative
                    $disp_financezero = '(' . number_format(abs($disp_financezero), 2) . ')';
                } else {
                    $disp_financezero = $disp_financezero == 0 ? '-' : number_format($disp_financezero, 2);
                }
                if ($disp_financeregularqty < 0) { //ito yung negative
                    $disp_financeregularqty = '(' . number_format(abs($disp_financeregularqty), 2) . ')';
                } else {
                    $disp_financeregularqty = $disp_financeregularqty == 0 ? '-' : number_format($disp_financeregularqty, 2);
                }
                if ($disp_financeregular < 0) { //ito yung negative
                    $disp_financeregular = '(' . number_format(abs($disp_financeregular), 2) . ')';
                } else {
                    $disp_financeregular = $disp_financeregular == 0 ? '-' : number_format($disp_financeregular, 2);
                }

                if ($disp_qtyst < 0) { //ito yung negative
                    $disp_qtyst = '(' . number_format(abs($disp_qtyst), 2) . ')';
                } else {
                    $disp_qtyst = $disp_qtyst == 0 ? '-' : number_format($disp_qtyst, 2);
                }
                if ($display_mos < 0) { //ito yung negative
                    $display_mos = '(' . number_format(abs($display_mos), 2) . ')';
                } else {
                    $display_mos = $display_mos == 0 ? '-' : number_format($display_mos, 2);
                }

                if ($grossap2 < 0) { //ito yung negative
                    $grossap2d = '(' . number_format(abs($grossap2), 2) . ')';
                } else {
                    $grossap2d = $grossap2 == 0 ? '-' : number_format($grossap2, 2);
                }

                if ($disp_netpayable < 0) { //ito yung negative
                    $disp_netpayable = '(' . number_format(abs($disp_netpayable), 2) . ')';
                } else {
                    $disp_netpayable = $disp_netpayable == 0 ? '-' : number_format($disp_netpayable, 2);
                }

                $str .= $this->reporter->col($data->branchname, '210', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->suppcode, '50', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data->suppliername, '237', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($totalsoldd, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($srpd, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($discountd, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tlsrpd, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($com1hered, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($comapd, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');

                $str .= $this->reporter->col($disp_qty3, '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($display_mos3, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_qty6, '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($display_mos6, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_qty9, '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($display_mos9, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_financezeroqty, '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_financezero, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_financeregularqty, '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_financeregular, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_qtyst, '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($display_mos, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($grossap2d, '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($disp_netpayable, '70', '', false, $border, '', 'RT', $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                //  sub total

                $stotalsold += $totalsold;
                $stotaldisc += $discount;
                $stotalsrp += $tlsrp;
                $stotalcost += $totalcost;
                $stotalcom1 += $com1here;
                $sgrosspayable += $grosspayable;
                $sgrossap2 += $grossap2;
                $snetap += $netpayable;

                $sqty3 += $qty3;
                $smos3 += $mos3;
                $sqty6 += $qty6;
                $smos6 += $mos6;
                $sqty9 += $qty9;
                $smos9 += $mos9;
                $sfinancezeroqty += $financezeroqty;
                $sfinancezero += $financezero;
                $sfinanceregularqty += $financeregularqty;
                $sfinanceregular += $financeregular;
                $sqtyst += $qtyst;
                $smost += $most;

                $scomap += $comap;
                $stllsrp += $srp;
                $count++;
            }


            if ($stotalsold < 0) { //ito yung negative
                $stotalsolds = '(' . number_format(abs($stotalsold), 2) . ')';
            } else {
                $stotalsolds = $stotalsold == 0 ? '-' : number_format($stotalsold, 2);
            }
            if ($stllsrp < 0) { //ito yung negative
                $stllsrps = '(' . number_format(abs($stllsrp), 2) . ')';
            } else {
                $stllsrps = $stllsrp == 0 ? '-' : number_format($stllsrp, 2);
            }
            if ($stotaldisc < 0) { //ito yung negative
                $stotaldiscs = '(' . number_format(abs($stotaldisc), 2) . ')';
            } else {
                $stotaldiscs = $stotaldisc == 0 ? '-' : number_format($stotaldisc, 2);
            }
            if ($stotalsrp < 0) { //ito yung negative
                $stotalsrps = '(' . number_format(abs($stotalsrp), 2) . ')';
            } else {
                $stotalsrps = $stotalsrp == 0 ? '-' : number_format($stotalsrp, 2);
            }
            if ($stotalcom1 < 0) { //ito yung negative
                $stotalcom1s = '(' . number_format(abs($stotalcom1), 2) . ')';
            } else {
                $stotalcom1s = $stotalcom1 == 0 ? '-' : number_format($stotalcom1, 2);
            }
            if ($scomap < 0) { //ito yung negative
                $scomaps = '(' . number_format(abs($scomap), 2) . ')';
            } else {
                $scomaps = $scomap == 0 ? '-' : number_format($scomap, 2);
            }
            if ($sqty3 < 0) { //ito yung negative
                $sqty3s = '(' . number_format(abs($sqty3), 2) . ')';
            } else {
                $sqty3s = $sqty3 == 0 ? '-' : number_format($sqty3, 2);
            }

            if ($smos3 < 0) { //ito yung negative
                $smos3s = '(' . number_format(abs($smos3), 2) . ')';
            } else {
                $smos3s = $smos3 == 0 ? '-' : number_format($smos3, 2);
            }

            if ($sqty6 < 0) { //ito yung negative
                $sqty6s = '(' . number_format(abs($sqty6), 2) . ')';
            } else {
                $sqty6s = $sqty6 == 0 ? '-' : number_format($sqty6, 2);
            }
            if ($smos6 < 0) { //ito yung negative
                $smos6s = '(' . number_format(abs($smos6), 2) . ')';
            } else {
                $smos6s = $smos6 == 0 ? '-' : number_format($smos6, 2);
            }
            if ($sqty9 < 0) { //ito yung negative
                $sqty9s = '(' . number_format(abs($sqty9), 2) . ')';
            } else {
                $sqty9s = $sqty9 == 0 ? '-' : number_format($sqty9, 2);
            }
            if ($smos9 < 0) { //ito yung negative
                $smos9s = '(' . number_format(abs($smos9), 2) . ')';
            } else {
                $smos9s = $smos9 == 0 ? '-' : number_format($smos9, 2);
            }
            if ($sfinancezeroqty < 0) { //ito yung negative
                $sfinancezeroqtys = '(' . number_format(abs($sfinancezeroqty), 2) . ')';
            } else {
                $sfinancezeroqtys = $sfinancezeroqty == 0 ? '-' : number_format($sfinancezeroqty, 2);
            }
            if ($sfinancezero < 0) { //ito yung negative
                $sfinancezeros = '(' . number_format(abs($sfinancezero), 2) . ')';
            } else {
                $sfinancezeros = $sfinancezero == 0 ? '-' : number_format($sfinancezero, 2);
            }
            if ($sfinanceregularqty < 0) { //ito yung negative
                $sfinanceregularqtys = '(' . number_format(abs($sfinanceregularqty), 2) . ')';
            } else {
                $sfinanceregularqtys = $sfinanceregularqty == 0 ? '-' : number_format($sfinanceregularqty, 2);
            }
            if ($sfinanceregular < 0) { //ito yung negative
                $sfinanceregulars = '(' . number_format(abs($sfinanceregular), 2) . ')';
            } else {
                $sfinanceregulars = $sfinanceregular == 0 ? '-' : number_format($sfinanceregular, 2);
            }
            if ($sqtyst < 0) { //ito yung negative
                $sqtysts = '(' . number_format(abs($sqtyst), 2) . ')';
            } else {
                $sqtysts = $sqtyst == 0 ? '-' : number_format($sqtyst, 2);
            }
            if ($smost < 0) { //ito yung negative
                $smosts = '(' . number_format(abs($smost), 2) . ')';
            } else {
                $smosts = $smost == 0 ? '-' : number_format($smost, 2);
            }
            if ($sgrossap2 < 0) { //ito yung negative
                $sgrossap2s = '(' . number_format(abs($sgrossap2), 2) . ')';
            } else {
                $sgrossap2s = $sgrossap2 == 0 ? '-' : number_format($sgrossap2, 2);
            }
            if ($snetap < 0) { //ito yung negative
                $snetaps = '(' . number_format(abs($snetap), 2) . ')';
            } else {
                $snetaps = $snetap == 0 ? '-' : number_format($snetap, 2);
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL', '210', '', false, '1px dotted', 'TB', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'TB', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '237', '', false, '1px dotted', 'TB', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($stotalsolds, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($stotaldiscs, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($stotalsrps, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col(($tlcost == 0) ? '-' : number_format($tlcost, 2), '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($stotalcom1s, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($scomaps, '50', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col(($percenthere == 0) ? '-' : number_format($percenthere, 2), '50', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sqty3s, '49', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($smos3s, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sqty6s, '49', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($smos6s, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sqty9s, '49', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($smos9s, '50', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sfinancezeroqtys, '49', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sfinancezeros, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sfinanceregularqtys, '49', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sfinanceregulars, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sqtysts, '49', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($smosts, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sgrossap2s, '49', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($snetaps, '70', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // grand total
            $gtotalsld += $stotalsold;
            $gtotaldiscount += $stotaldisc;
            $gtlsrp += $stotalsrp;
            $gtlcost += $stotalcost;
            $gcomm1here += $stotalcom1;
            $ggross += $sgrosspayable;
            $gcommm2 += $sgrossap2;
            $gtotalcomap += $scomap;
            $gnetp += $snetap;
            $gtldqty3 += $sqty3;
            $gtldmos3 += $smos3;
            $gtldqty6 += $sqty6;
            $gtldmos6 += $smos6;
            $gtldqty9 += $sqty9;
            $gtldmos9 += $smos9;
            $gtldqtyfzero += $sfinancezeroqty;
            $gtldfzero += $sfinancezero;
            $gtldqtyfreg += $sfinanceregularqty;
            $gtldfreg += $sfinanceregular;
            $gtldqtyst += $sqtyst;
            $gtldmos += $smost;
            $gstllsrp += $stllsrp;

            $sname = $supplier;
        }

        if ($gtotalsld < 0) { //ito yung negative
            $gtotalsld = '(' . number_format(abs($gtotalsld), 2) . ')';
        } else {
            $gtotalsld = $gtotalsld == 0 ? '-' : number_format($gtotalsld, 2);
        }
        if ($gstllsrp < 0) { //ito yung negative
            $gstllsrp = '(' . number_format(abs($gstllsrp), 2) . ')';
        } else {
            $gstllsrp = $gstllsrp == 0 ? '-' : number_format($gstllsrp, 2);
        }
        if ($gtotaldiscount < 0) { //ito yung negative
            $gtotaldiscount = '(' . number_format(abs($gtotaldiscount), 2) . ')';
        } else {
            $gtotaldiscount = $gtotaldiscount == 0 ? '-' : number_format($gtotaldiscount, 2);
        }
        if ($gtlsrp < 0) { //ito yung negative
            $gtlsrp = '(' . number_format(abs($gtlsrp), 2) . ')';
        } else {
            $gtlsrp = $gtlsrp == 0 ? '-' : number_format($gtlsrp, 2);
        }
        if ($gcomm1here < 0) { //ito yung negative
            $gcomm1here = '(' . number_format(abs($gcomm1here), 2) . ')';
        } else {
            $gcomm1here = $gcomm1here == 0 ? '-' : number_format($gcomm1here, 2);
        }
        if ($gtotalcomap < 0) { //ito yung negative
            $gtotalcomap = '(' . number_format(abs($gtotalcomap), 2) . ')';
        } else {
            $gtotalcomap = $gtotalcomap == 0 ? '-' : number_format($gtotalcomap, 2);
        }
        if ($gtldqty3 < 0) { //ito yung negative
            $gtldqty3 = '(' . number_format(abs($gtldqty3), 2) . ')';
        } else {
            $gtldqty3 = $gtldqty3 == 0 ? '-' : number_format($gtldqty3, 2);
        }
        if ($gtldmos3 < 0) { //ito yung negative
            $gtldmos3 = '(' . number_format(abs($gtldmos3), 2) . ')';
        } else {
            $gtldmos3 = $gtldmos3 == 0 ? '-' : number_format($gtldmos3, 2);
        }
        if ($gtldqty6 < 0) { //ito yung negative
            $gtldqty6 = '(' . number_format(abs($gtldqty6), 2) . ')';
        } else {
            $gtldqty6 = $gtldqty6 == 0 ? '-' : number_format($gtldqty6, 2);
        }
        if ($gtldmos6 < 0) { //ito yung negative
            $gtldmos6 = '(' . number_format(abs($gtldmos6), 2) . ')';
        } else {
            $gtldmos6 = $gtldmos6 == 0 ? '-' : number_format($gtldmos6, 2);
        }
        if ($gtldqty9 < 0) { //ito yung negative
            $gtldqty9 = '(' . number_format(abs($gtldqty9), 2) . ')';
        } else {
            $gtldqty9 = $gtldqty9 == 0 ? '-' : number_format($gtldqty9, 2);
        }
        if ($gtldmos9 < 0) { //ito yung negative
            $gtldmos9 = '(' . number_format(abs($gtldmos9), 2) . ')';
        } else {
            $gtldmos9 = $gtldmos9 == 0 ? '-' : number_format($gtldmos9, 2);
        }
        if ($gtldqtyfzero < 0) { //ito yung negative
            $gtldqtyfzero = '(' . number_format(abs($gtldqtyfzero), 2) . ')';
        } else {
            $gtldqtyfzero = $gtldqtyfzero == 0 ? '-' : number_format($gtldqtyfzero, 2);
        }
        if ($gtldfzero < 0) { //ito yung negative
            $gtldfzero = '(' . number_format(abs($gtldfzero), 2) . ')';
        } else {
            $gtldfzero = $gtldfzero == 0 ? '-' : number_format($gtldfzero, 2);
        }
        if ($gtldqtyfreg < 0) { //ito yung negative
            $gtldqtyfreg = '(' . number_format(abs($gtldqtyfreg), 2) . ')';
        } else {
            $gtldqtyfreg = $gtldqtyfreg == 0 ? '-' : number_format($gtldqtyfreg, 2);
        }
        if ($gtldfreg < 0) { //ito yung negative
            $gtldfreg = '(' . number_format(abs($gtldfreg), 2) . ')';
        } else {
            $gtldfreg = $gtldfreg == 0 ? '-' : number_format($gtldfreg, 2);
        }
        if ($gtldqtyst < 0) { //ito yung negative
            $gtldqtyst = '(' . number_format(abs($gtldqtyst), 2) . ')';
        } else {
            $gtldqtyst = $gtldqtyst == 0 ? '-' : number_format($gtldqtyst, 2);
        }
        if ($gtldmos < 0) { //ito yung negative
            $gtldmos = '(' . number_format(abs($gtldmos), 2) . ')';
        } else {
            $gtldmos = $gtldmos == 0 ? '-' : number_format($gtldmos, 2);
        }
        if ($gcommm2 < 0) { //ito yung negative
            $gcommm2 = '(' . number_format(abs($gcommm2), 2) . ')';
        } else {
            $gcommm2 = $gcommm2 == 0 ? '-' : number_format($gcommm2, 2);
        }
        if ($gnetp < 0) { //ito yung negative
            $gnetp = '(' . number_format(abs($gnetp), 2) . ')';
        } else {
            $gnetp = $gnetp == 0 ? '-' : number_format($gnetp, 2);
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '210', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('GRANDTOTAL', '237', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($gtotalsld, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtotaldiscount, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col(($tlcost == 0) ? '-' : number_format($tlcost, 2), '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gcomm1here, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col(($percenthere == 0) ? '-' : number_format($percenthere, 2), '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldqty3, '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldqty6, '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldqty9, '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldqtyfzero, '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldqtyfreg, '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldqtyst, '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gcommm2, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '210', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'TB', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '237', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtlsrp, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col(($tlcost == 0) ? '-' : number_format($tlcost, 2), '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtotalcomap, '50', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col(($percenthere == 0) ? '-' : number_format($percenthere, 2), '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldmos3, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldmos6, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldmos9, '50', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldfzero, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldfreg, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gtldmos, '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '49', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($gnetp, '70', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        // if ($count  <= $page) {
        //     $test = $page - $count; //yung hindi pa naoccupy
        //     $space = $test - 5;
        //     for ($i = 0; $i < $space; $i++) {
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //     }
        // }
        // $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
        $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</div>';
        $str .= $this->reporter->page_break();
        $str .= '<div style="position: relative;margin:-100px 0 10px 0;">';
        $str .= "<div style='position:absolute; bottom:50px'>";
        $sign = URL::to('/images/homeworks/checked.png');
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="90px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        $str .= '</div>';
        $str .= '</div>';
        return $str;
    }



    public function footer($config, $layoutsize, $font, $font_size, $border)
    {

        $str = '';
        $reptype = $config['params']['dataparams']['reporttype'];

        switch ($reptype) {
            case '1':
            case '7':
            case '8':
            case '9':
                $str .= '<div style="position: relative;margin:-100px 0 10px 0;">';
                $str .= "<div style='position:absolute; bottom:50px'>";
                $sign = URL::to('/images/homeworks/checked.png');
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="70px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '5', 'B', '', '1px');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '</div>';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                break;

            case '2':
                $str .= '<div style="position: relative;margin:-95px 0 10px 0;">';
                $str .= "<div style='position:absolute; bottom:35px'>";
                $sign = URL::to('/images/homeworks/checked.png');
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '550', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="90px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '550', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '</div>';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '500', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '500', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '500', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '500', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
            case '4':
            case '5':
            case '6':
                $str .= '<div style="position: relative;margin:-100px 0 10px 0;">';
                $str .= "<div style='position:absolute; bottom:50px'>";
                $sign = URL::to('/images/homeworks/checked.png');
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="90px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '</div>';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                break;
            default:
                $str .= '<div style="position: relative;margin:-85px 0 20px 0;">';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '10px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
        }

        if($reptype !=2 ){//not vendor summary report
            
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';

        }else{//vendor summary report 

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1500', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '650', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        $str .= $this->reporter->pagenumber('Page', '100', null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '650', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '650', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '650', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '650', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '650', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';

        }
        // $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        // $dt = new DateTime($current_timestamp);
        // $date = $dt->format('d-M-y');
        // $username = $config['params']['user'];

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        // $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');

        // $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        // $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        // $str .= '</div>';
        return $str;
    }


    public function lastfooter($config, $layoutsize, $font, $font_size, $border)
    {
        $str = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= '</div>';
        return $str;
    }

    private function calculate_lines_needed_details_perdoc($data)
    {

        // ilan ang avg_char_width kapag calibri ang font at 6 ang fontsize?
        $supname = $data->suppliername;
        $itemdesc = $data->itemdesc;

        $supname_width = 76;  // Width of the branchname column
        $itemdisc_width = 75;  // Width of the suppliername column

        $avg_char_width =  4.5;  // Average character width for Calibri 7px

        $chars_per_line_sup = floor($supname_width / $avg_char_width); //13.33

        $chars_per_line_item = floor($itemdisc_width / $avg_char_width); //19.33

        $lines_needed_sup = ceil(strlen($supname) / $chars_per_line_sup);

        $lines_needed_item = ceil(strlen($itemdesc) / $chars_per_line_item);

        $lines_needed = max($lines_needed_sup, $lines_needed_item);

        return $lines_needed;
    }


    private function detail_per_doc_header($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        // $letter = $this->reporter->letterhead($center, $username, $config);
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '6';
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        $client = $config['params']['dataparams']['client'];
        $clientname = $config['params']['dataparams']['clientname'];


        if ($client != "") {
            $clientname = $client . '~' . $config['params']['dataparams']['clientname'];
        } else {
            $clientname = 'ALL SUPPLIER';
        }

        if ($itemstatus == '(0)') {
            $itemstatus = 'ACTIVE ITEMS';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'ACTIVE ITEMS';
        } else {
            $itemstatus = 'ALL ITEMS';
        }

        $center = $config['params']['center'];

        $str = '';
        $layoutsize = '1200';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name . ' ' . strtoupper($headerdata[0]->address), '1000', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($itemstatus . ' - SALES REPORT DETAIL PER DOC#', '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $clientname, '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM:' . $start, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO:' . $end, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '665', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('CHARGES', '450', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '85', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        //26
        $str .= $this->reporter->begintable($layoutsize); // $font,  $font_size, 'B', '', '', '', '', 'min-width:38px;max-width:38px;word-wrap:break-word;');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Invoice #', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Supplier Code', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Supplier Name ', '76', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Item Code', '70', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Item Description', '75', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('TTL Sold', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Srp', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Disc', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('TTL Srp', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('TTL Cost', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Com 1', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Gross Payable', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Percentage', '38', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');

        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('3 Mos', '36', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('6 Mos', '36', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('9 Mos/12 Mos', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Finance 0%', '36', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Finance Regular', '36', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Qty', '35', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Debit/ Straight', '36', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '', '', '');

        $str .= $this->reporter->col('Com 2', '35', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->col('Net Payable', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }

    public function detail_per_doc($config)
    {
        $result = $this->detailperdoc($config);

        $border = '1px solid';
        $font = 'calibri';
        $font_size = '6';

        $tlsrp = 0;


        $grandtotalsold = 0;
        $grandtotalsrp = 0;
        $grandtotaldisc = 0;
        $grandtotalpercent = 0;
        $grandtotaltlsrp  = 0;
        $grandtotalcost  = 0;
        $grandtotalcom1  = 0;
        $grandtotalgpayable  = 0;
        $grandtotalcom2  = 0;
        $grandtotalnpayable  = 0;

        $grandtotaltldqty3 = 0;
        $grandtotaltldmos3 = 0;
        $grandtotaltldqty6 = 0;
        $grandtotaltldmos6 = 0;

        $grandtotaltldqty9 = 0;
        $grandtotaltldmos9 = 0;

        $grandtotaltldqtyfzero = 0;
        $grandtotaltldfzero = 0;
        $grandtotaltldqtyfreg = 0;
        $grandtotaltldfreg = 0;

        $grandtotaltldqtyst = 0;
        $grandtotaltldmos = 0;


        $tldqty3 = 0;
        $tldmos3 = 0;
        $tldqty6 = 0;
        $tldmos6 = 0;

        $tldqty9 = 0;
        $tldmos9 = 0;


        $tldqtyfzero = 0;
        $tldfzero = 0;

        $tldqtyfreg = 0;
        $tldfreg = 0;

        $tldqtyst = 0;
        $tldmos = 0;

        $page = 51;
        $countitem = 0;

        $page_count = 1;
        $count = 0;



        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1200';

        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= '<div style="position: relative;margin:0 0 38px 0;">';
        $str .= $this->detail_per_doc_header($config);



        foreach ($result as $key => $data) {
            $qty3 = $qty6 = $qty9 = $qtyst = 0;
            $mos3 = $mos6 = $mos9 = $most = 0;

            $financeregularqty = $financeregular = 0;
            $financezeroqty = $financezero = 0;
            $raw = $data->terminalid;
            $parts = explode('~', $raw);
            $firstpart = $parts[0];
            switch ($firstpart) {
                case 'CCASHALOALDI':
                case 'CHOMECREDIT':
                case 'CHOMECREDIT0':
                case 'CCASHALO':
                case 'DCASHALOALDI':
                case 'DHOMECREDIT':
                case 'DHOMECREDIT0':
                case 'DCASHALO':
                case 'CASHALOALDI':
                case 'HOMECREDIT':
                case 'HOMECREDIT0':
                case 'CASHALO':
                    // case 'CAUGBGCASH':
                    if ($data->bankrate != 0) {
                        $financeregularqty = $data->totalsold;
                        $financeregular = $data->cardcharge;
                    } else {
                        $financezeroqty = $data->totalsold;
                        $financezero = $data->cardcharge;
                    }
                    break;
                default:
                    if (!empty($data->banktype) && $data->cardcharge != 0) {
                        switch (strtoupper(str_replace(' ', '', $data->banktype))) {
                            case '3MONS':
                            case '3MOS':     
                                $qty3 = $data->totalsold;
                                $mos3 = $data->cardcharge;
                                break;
                            case '6MONS':
                            case '6MOS':        
                                $qty6 =  $data->totalsold;
                                $mos6 =  $data->cardcharge;
                                break;
                            case '9MONS':
                            case '12MONS':
                            case '9MOS':
                            case '12MOS':
                                $qty9 =  $data->totalsold;
                                $mos9 =  $data->cardcharge;
                                break;
                            case 'STRAIGHT':
                            case 'DEBIT':
                            case 'AUBGCASH':
                            case 'DEBIT/BANCNET':     
                                $qtyst =  $data->totalsold;
                                $most =  $data->cardcharge;

                                break;
                        }
                    }
                    break;
            }


            $disp_financeregularqty = ($financeregularqty != 0) ? $financeregularqty : 0;
            $disp_financeregular    = ($financeregular != 0)    ? $financeregular : 0;

            $disp_financezeroqty    = ($financezeroqty != 0)    ? $financezeroqty   : 0;
            $disp_financezero       = ($financezero != 0)       ? $financezero     : 0;


            $display_mos3 = ($mos3 != 0) ? $mos3 : 0;
            $display_mos6 = ($mos6 != 0) ? $mos6 : 0;
            $display_mos9 = ($mos9 != 0) ? $mos9 : 0;
            $display_mos = ($most != 0) ? $most : 0;

            $disp_qty3  = ($qty3 != 0) ? $qty3 : 0;
            $disp_qty6  = ($qty6 != 0) ? $qty6 : 0;
            $disp_qty9  = ($qty9 != 0) ? $qty9 : 0;
            $disp_qtyst = ($qtyst != 0) ? $qtyst : 0;


            // $netap =  $data->netap;
            $grosspayable = $data->totalcost;
            $grossap2s = $data->grossap2;

            $charges = $grossap2s + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;

            $netpayable =  $grosspayable  + $charges;
            $disp_netpayable = $netpayable;



            $discount = $data->tldisc;
            $discount = $discount != 0 ?  $discount : 0;



            $totalsold  = ($data->totalsold != 0) ? floatval($data->totalsold) : 0;
            $srp        = ($data->srp != 0) ? floatval($data->srp) : 0;
            $tlsrp      = ($data->ext != 0) ? floatval($data->ext) : 0;
            $comm1 = $data->comm1;
            $comap      = ($data->totalcost != 0) ? floatval($data->totalcost) : 0;
            $grossap2   = ($data->grossap2 != 0) ? floatval($data->grossap2) : 0;

            $percentage = $data->percentage;


            $lines_needed = $this->calculate_lines_needed_details_perdoc($data);
            $countitem += $lines_needed;

            if ($countitem + $lines_needed >= $page) {
                $str .= $this->reporter->endtable();
                $str .= '</div>';
                $str .= $this->reporter->page_break();
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                $str .= $this->detail_per_doc_header($config);
                $page_count++;
                $countitem = 0;
            }

            if ($totalsold < 0) { //ito yung negative
                $totalsoldd = '(' . number_format(abs($totalsold), 2) . ')';
            } else {
                $totalsoldd = $totalsold == 0 ? '-' : number_format($totalsold, 2);
            }
            if ($srp < 0) { //ito yung negative
                $srpd = '(' . number_format(abs($srp), 2) . ')';
            } else {
                $srpd = $srp == 0 ? '-' : number_format($srp, 2);
            }
            if ($discount < 0) { //ito yung negative
                $discountd = '(' . number_format(abs($discount), 2) . ')';
            } else {
                $discountd = $discount == 0 ? '-' : number_format($discount, 2);
            }
            if ($tlsrp < 0) { //ito yung negative
                $tlsrpd = '(' . number_format(abs($tlsrp), 2) . ')';
            } else {
                $tlsrpd = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
            }
            if ($comap < 0) { //ito yung negative
                $comapd = '(' . number_format(abs($comap), 2) . ')';
            } else {
                $comapd = $comap == 0 ? '-' : number_format($comap, 2);
            }

            if ($comm1 < 0) { //ito yung negative
                $comm1d = '(' . number_format(abs($comm1), 2) . ')';
            } else {
                $comm1d = $comm1 == 0 ? '-' : number_format($comm1, 2);
            }
            if ($percentage < 0) { //ito yung negative
                $percentaged = '(' . number_format(abs($percentage), 2) . ')';
            } else {
                $percentaged = $percentage == 0 ? '-' : number_format($percentage, 2);
            }
            if ($disp_qty3 < 0) { //ito yung negative
                $disp_qty3 = '(' . number_format(abs($disp_qty3), 2) . ')';
            } else {
                $disp_qty3 = $disp_qty3 == 0 ? '-' : number_format($disp_qty3, 2);
            }
            if ($display_mos3 < 0) { //ito yung negative
                $display_mos3 = '(' . number_format(abs($display_mos3), 2) . ')';
            } else {
                $display_mos3 = $display_mos3 == 0 ? '-' : number_format($display_mos3, 2);
            }
            if ($disp_qty6 < 0) { //ito yung negative
                $disp_qty6 = '(' . number_format(abs($disp_qty6), 2) . ')';
            } else {
                $disp_qty6 = $disp_qty6 == 0 ? '-' : number_format($disp_qty6, 2);
            }

            if ($display_mos6 < 0) { //ito yung negative
                $display_mos6 = '(' . number_format(abs($display_mos6), 2) . ')';
            } else {
                $display_mos6 = $display_mos6 == 0 ? '-' : number_format($display_mos6, 2);
            }
            if ($disp_qty9 < 0) { //ito yung negative
                $disp_qty9 = '(' . number_format(abs($disp_qty9), 2) . ')';
            } else {
                $disp_qty9 = $disp_qty9 == 0 ? '-' : number_format($disp_qty9, 2);
            }
            if ($display_mos9 < 0) { //ito yung negative
                $display_mos9 = '(' . number_format(abs($display_mos9), 2) . ')';
            } else {
                $display_mos9 = $display_mos9 == 0 ? '-' : number_format($display_mos9, 2);
            }
            if ($disp_financezeroqty < 0) { //ito yung negative
                $disp_financezeroqty = '(' . number_format(abs($disp_financezeroqty), 2) . ')';
            } else {
                $disp_financezeroqty = $disp_financezeroqty == 0 ? '-' : number_format($disp_financezeroqty, 2);
            }
            if ($disp_financezero < 0) { //ito yung negative
                $disp_financezero = '(' . number_format(abs($disp_financezero), 2) . ')';
            } else {
                $disp_financezero = $disp_financezero == 0 ? '-' : number_format($disp_financezero, 2);
            }
            if ($disp_financeregularqty < 0) { //ito yung negative
                $disp_financeregularqty = '(' . number_format(abs($disp_financeregularqty), 2) . ')';
            } else {
                $disp_financeregularqty = $disp_financeregularqty == 0 ? '-' : number_format($disp_financeregularqty, 2);
            }
            if ($disp_financeregular < 0) { //ito yung negative
                $disp_financeregular = '(' . number_format(abs($disp_financeregular), 2) . ')';
            } else {
                $disp_financeregular = $disp_financeregular == 0 ? '-' : number_format($disp_financeregular, 2);
            }
            if ($disp_qtyst < 0) { //ito yung negative
                $disp_qtyst = '(' . number_format(abs($disp_qtyst), 2) . ')';
            } else {
                $disp_qtyst = $disp_qtyst == 0 ? '-' : number_format($disp_qtyst, 2);
            }
            if ($display_mos < 0) { //ito yung negative
                $display_mos = '(' . number_format(abs($display_mos), 2) . ')';
            } else {
                $display_mos = $display_mos == 0 ? '-' : number_format($display_mos, 2);
            }
            if ($grossap2 < 0) { //ito yung negative
                $grossap2d = '(' . number_format(abs($grossap2), 2) . ')';
            } else {
                $grossap2d = $grossap2 == 0 ? '-' : number_format($grossap2, 2);
            }
            if ($disp_netpayable < 0) { //ito yung negative
                $disp_netpayable = '(' . number_format(abs($disp_netpayable), 2) . ')';
            } else {
                $disp_netpayable = $disp_netpayable == 0 ? '-' : number_format($disp_netpayable, 2);
            }


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateid, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->docno, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->supcode, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->suppliername, '76', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->barcode, '70', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemdesc, '75', '', false, $border, '', 'LT', $font, $font_size, '', '', ''); //, $font_size, '', '', '', '', '', 'min-width:73px;max-width:73px;word-wrap:break-word;');

            $str .= $this->reporter->col($totalsoldd, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($srpd, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($discountd, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($tlsrpd, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');

            $str .= $this->reporter->col($comapd, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($comm1d, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($comapd, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($percentaged, '38', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');

            $str .= $this->reporter->col($disp_qty3, '35', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($display_mos3, '36', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_qty6, '35', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($display_mos6, '36', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_qty9, '35', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($display_mos9, '50', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_financezeroqty, '35', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_financezero, '36', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_financeregularqty, '35', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_financeregular, '36', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_qtyst,  '35', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($display_mos, '36', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($grossap2d, '35', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->col($disp_netpayable, '50', '', false, $border, '', 'RT', $font,  $font_size, '', '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //gtotal
            $grandtotalsold += $totalsold;
            $grandtotalsrp += $srp;
            $grandtotaldisc += $discount;

            $grandtotalpercent += $percentage;
            $grandtotaltlsrp  += $tlsrp;

            $grandtotalcost  += $comap;
            $grandtotalcom1  += $comm1;
            $grandtotalgpayable  += $comap;
            $grandtotalcom2  += $grossap2;
            $grandtotalnpayable  += $netpayable;

            $tldqty3 += $qty3;
            $tldmos3 += $mos3;
            $tldqty6 += $qty6;
            $tldmos6 += $mos6;

            $tldqty9 += $qty9;
            $tldmos9 += $mos9;

            $tldqtyfzero += $financezeroqty;
            $tldfzero += $financezero;

            $tldqtyfreg += $financeregularqty;
            $tldfreg += $financeregular;

            $tldqtyst += $qtyst;
            $tldmos += $most;

            $grandtotaltldqty3 += $qty3;
            $grandtotaltldmos3 += $mos3;
            $grandtotaltldqty6 += $qty6;
            $grandtotaltldmos6 += $mos6;

            $grandtotaltldqty9 += $qty9;
            $grandtotaltldmos9 += $mos9;

            $grandtotaltldqtyfzero += $financezeroqty;
            $grandtotaltldfzero += $financezero;

            $grandtotaltldqtyfreg += $financeregularqty;
            $grandtotaltldfreg += $financeregular;

            $grandtotaltldqtyst += $qtyst;
            $grandtotaltldmos += $most;

            // $countitem += $lines_needed;



            // $document = $data->docno;

            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->detail_per_doc_header($config);
            //     $page = $page + $count;
            // }

            // if ($i == (count((array)$result) - 1)) {
            //     goto SubtotalHere;
            // }
            // $i++;
        }

        if ($grandtotalsold < 0) { //ito yung negative
            $grandtotalsold = '(' . number_format(abs($grandtotalsold), 2) . ')';
        } else {
            $grandtotalsold = $grandtotalsold == 0 ? '-' : number_format($grandtotalsold, 2);
        }
        if ($grandtotaldisc < 0) { //ito yung negative
            $grandtotaldisc = '(' . number_format(abs($grandtotaldisc), 2) . ')';
        } else {
            $grandtotaldisc = $grandtotaldisc == 0 ? '-' : number_format($grandtotaldisc, 2);
        }
        if ($grandtotalcost < 0) { //ito yung negative
            $grandtotalcost = '(' . number_format(abs($grandtotalcost), 2) . ')';
        } else {
            $grandtotalcost = $grandtotalcost == 0 ? '-' : number_format($grandtotalcost, 2);
        }
        if ($grandtotalgpayable < 0) { //ito yung negative
            $grandtotalgpayable = '(' . number_format(abs($grandtotalgpayable), 2) . ')';
        } else {
            $grandtotalgpayable = $grandtotalgpayable == 0 ? '-' : number_format($grandtotalgpayable, 2);
        }
        if ($grandtotaltldqty3 < 0) { //ito yung negative
            $grandtotaltldqty3 = '(' . number_format(abs($grandtotaltldqty3), 2) . ')';
        } else {
            $grandtotaltldqty3 = $grandtotaltldqty3 == 0 ? '-' : number_format($grandtotaltldqty3, 2);
        }
        if ($grandtotaltldqty6 < 0) { //ito yung negative
            $grandtotaltldqty6 = '(' . number_format(abs($grandtotaltldqty6), 2) . ')';
        } else {
            $grandtotaltldqty6 = $grandtotaltldqty6 == 0 ? '-' : number_format($grandtotaltldqty6, 2);
        }
        if ($grandtotaltldqty9 < 0) { //ito yung negative
            $grandtotaltldqty9 = '(' . number_format(abs($grandtotaltldqty9), 2) . ')';
        } else {
            $grandtotaltldqty9 = $grandtotaltldqty9 == 0 ? '-' : number_format($grandtotaltldqty9, 2);
        }
        if ($grandtotaltldqtyfzero < 0) { //ito yung negative
            $grandtotaltldqtyfzero = '(' . number_format(abs($grandtotaltldqtyfzero), 2) . ')';
        } else {
            $grandtotaltldqtyfzero = $grandtotaltldqtyfzero == 0 ? '-' : number_format($grandtotaltldqtyfzero, 2);
        }
        if ($grandtotaltldqtyfreg < 0) { //ito yung negative
            $grandtotaltldqtyfreg = '(' . number_format(abs($grandtotaltldqtyfreg), 2) . ')';
        } else {
            $grandtotaltldqtyfreg = $grandtotaltldqtyfreg == 0 ? '-' : number_format($grandtotaltldqtyfreg, 2);
        }
        if ($grandtotaltldqtyst < 0) { //ito yung negative
            $grandtotaltldqtyst = '(' . number_format(abs($grandtotaltldqtyst), 2) . ')';
        } else {
            $grandtotaltldqtyst = $grandtotaltldqtyst == 0 ? '-' : number_format($grandtotaltldqtyst, 2);
        }
        if ($grandtotalcom2 < 0) { //ito yung negative
            $grandtotalcom2 = '(' . number_format(abs($grandtotalcom2), 2) . ')';
        } else {
            $grandtotalcom2 = $grandtotalcom2 == 0 ? '-' : number_format($grandtotalcom2, 2);
        }



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '76', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Grandtotal: ', '75', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($grandtotalsold, '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaldisc, '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotalcost, '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotalgpayable, '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldqty3,  '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldqty6, '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldqty9, '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldqtyfzero, '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldqtyfreg, '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('',  '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldqtyst, '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('',  '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col($grandtotalcom2, '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();


        if ($grandtotalsrp < 0) { //ito yung negative
            $grandtotalsrp = '(' . number_format(abs($grandtotalsrp), 2) . ')';
        } else {
            $grandtotalsrp = $grandtotalsrp == 0 ? '-' : number_format($grandtotalsrp, 2);
        }
        if ($grandtotaltlsrp < 0) { //ito yung negative
            $grandtotaltlsrp = '(' . number_format(abs($grandtotaltlsrp), 2) . ')';
        } else {
            $grandtotaltlsrp = $grandtotaltlsrp == 0 ? '-' : number_format($grandtotaltlsrp, 2);
        }
        if ($grandtotalcom1 < 0) { //ito yung negative
            $grandtotalcom1 = '(' . number_format(abs($grandtotalcom1), 2) . ')';
        } else {
            $grandtotalcom1 = $grandtotalcom1 == 0 ? '-' : number_format($grandtotalcom1, 2);
        }
        if ($grandtotalpercent < 0) { //ito yung negative
            $grandtotalpercent = '(' . number_format(abs($grandtotalpercent), 2) . ')';
        } else {
            $grandtotalpercent = $grandtotalpercent == 0 ? '-' : number_format($grandtotalpercent, 2);
        }
        if ($grandtotaltldmos3 < 0) { //ito yung negative
            $grandtotaltldmos3 = '(' . number_format(abs($grandtotaltldmos3), 2) . ')';
        } else {
            $grandtotaltldmos3 = $grandtotaltldmos3 == 0 ? '-' : number_format($grandtotaltldmos3, 2);
        }
        if ($grandtotaltldmos6 < 0) { //ito yung negative
            $grandtotaltldmos6 = '(' . number_format(abs($grandtotaltldmos6), 2) . ')';
        } else {
            $grandtotaltldmos6 = $grandtotaltldmos6 == 0 ? '-' : number_format($grandtotaltldmos6, 2);
        }
        if ($grandtotaltldmos9 < 0) { //ito yung negative
            $grandtotaltldmos9 = '(' . number_format(abs($grandtotaltldmos9), 2) . ')';
        } else {
            $grandtotaltldmos9 = $grandtotaltldmos9 == 0 ? '-' : number_format($grandtotaltldmos9, 2);
        }
        if ($grandtotaltldfzero < 0) { //ito yung negative
            $grandtotaltldfzero = '(' . number_format(abs($grandtotaltldfzero), 2) . ')';
        } else {
            $grandtotaltldfzero = $grandtotaltldfzero == 0 ? '-' : number_format($grandtotaltldfzero, 2);
        }
        if ($grandtotaltldfreg < 0) { //ito yung negative
            $grandtotaltldfreg = '(' . number_format(abs($grandtotaltldfreg), 2) . ')';
        } else {
            $grandtotaltldfreg = $grandtotaltldfreg == 0 ? '-' : number_format($grandtotaltldfreg, 2);
        }
        if ($grandtotaltldmos < 0) { //ito yung negative
            $grandtotaltldmos = '(' . number_format(abs($grandtotaltldmos), 2) . ')';
        } else {
            $grandtotaltldmos = $grandtotaltldmos == 0 ? '-' : number_format($grandtotaltldmos, 2);
        }
        if ($grandtotalnpayable < 0) { //ito yung negative
            $grandtotalnpayable = '(' . number_format(abs($grandtotalnpayable), 2) . ')';
        } else {
            $grandtotalnpayable = $grandtotalnpayable == 0 ? '-' : number_format($grandtotalnpayable, 2);
        }


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '76', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '75', '', false, '1px dotted', 'T', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltlsrp, '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotalcom1, '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '38', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col($grandtotalpercent, '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('',  '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldmos3, '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldmos6, '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldmos9, '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldfzero, '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldfreg,  '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotaltldmos,  '36', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '35', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($grandtotalnpayable, '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $font_style = 'I';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited By :', '201', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted By :', '201', '', false, $border, 'T', 'C', $font, $font_size,  $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved By :', '201', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Received By :', '201', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        // if ($countitem <= $page) {
        //     $test = $page - $countitem;
        //     $test2 = $test - 5;
        //     for ($i = 0; $i < $test2; $i++) {
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //     }
        // }
        $str .= $this->lastfooter($config, $layoutsize, $font, $font_size, $border);
        $str .= $this->reporter->endreport();

        return $str;
    }


    private function vendor_with_item_concession_header($config, $supcode, $supplier, $centerr)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid   = $config['params']['dataparams']['clientid'];
        $clientname = $config['params']['dataparams']['dclientname'];
        $center = $config['params']['center'];

        $centerhere = $config['params']['dataparams']['center'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '7';
        $itemstatus = $config['params']['dataparams']['itemstatus'];

        if ($itemstatus == '(0)') {
            $itemstatus = 'ACTIVE ITEMS';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'ACTIVE ITEMS';
        } else {
            $itemstatus = 'ALL ITEMS';
        }
        $center = $config['params']['center'];
        $reptype = $config['params']['dataparams']['reporttype'];
        switch ($reptype) {
            case '4':
                $label = "CONCESSION";
                break;
            case '5':
                $label = "CONSIGNMENT";
                break;
            case '6':
                $label = "OUTRIGHT";
                break;
        }

        $supp = "";
        if ($clientid != 0) {
            $supp = $clientname;
        } else {
            $supp = $supcode . '~' . $supplier;
        }


        $str = '';
        $layoutsize = '1200';

        // var_dump($centerr);
        // $this->coreFunctions->LogConsole("Center: " . $centerr);
        $cent = $this->coreFunctions->getfieldvalue("center", "name", "code = '" . $centerhere . "'");

        if ($centerhere == '') {
            $center = $centerr;
        } else {
            // $center = $headerdata[0]->name . ' ' . strtoupper($headerdata[0]->address);
            $center = $cent;
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($center, '1000', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($itemstatus . ' - ITEM PER VENDOR SUMMARY REPORT-' . $label, '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . ' ' . $supp, '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM:' . $start, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '950', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('TO:' . $end, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '780', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('CHARGES', '960', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '160', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        switch ($reptype) {

            case '4': //concession

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col('Item Code', '68', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Item Description', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Sold', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Srp', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Disc', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Srp', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Com 1', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Gross Payable', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('3 Mos', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('6 Mos', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('9 Mos/12 Mos', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Fincnce 0%', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Finance Regular', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Debit/ Straight', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Com 2', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Net Payable', '70', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                break;
            case '5': //consignment
            case '6': //outright
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Item Code', '68', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Item Description', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Sold', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Cost', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Srp', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Disc', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Srp', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                // $str .= $this->reporter->col('Com 1', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Gross Payable', '51', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('3 Mos', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('6 Mos', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('9 Mos/12 Mos', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Fincnce 0%', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Finance Regular', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Debit/ Straight', '51', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Com 2', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Net Payable', '70', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                break;
        }
        // $str .= $this->reporter->endtable();
        return $str;
    }



    public function vendor_with_item_concessions($config)
    {
        $result = $this->concession($config);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '7';
        $reptype = $config['params']['dataparams']['reporttype'];
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }


        $str = '';
        $layoutsize = '1200';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];
        $centerhere = $config['params']['dataparams']['center'];
        // var_dump($centerhere);

        $totalsld = 0;
        $totaldiscount = 0;
        $tlsrp = 0;
        $tllsrp = 0;
        $tlssrp = 0;
        $tlcost = 0;
        $comm1here = 0;
        $gross = 0;
        $percenthere = 0;
        $commm2 = 0;
        $netp = 0;

        $tldqty3 = 0;
        $tldmos3 = 0;
        $tldqty6 = 0;
        $tldmos6 = 0;

        $tldqty9 = 0;
        $tldmos9 = 0;


        $tldqtyfzero = 0;
        $tldfzero = 0;

        $tldqtyfreg = 0;
        $tldfreg = 0;

        $tldqtyst = 0;
        $tldmos = 0;
        // $page = 45;
        $counterkwek = 0;
        $tl2srp = 0;
        $costlang = 0;

        $suppcode = '';
        $supname = '';
        $centerr = '';
        $i = 0;
        $count = 48;
        $page = 32; //32

        foreach ($result as $key => $data) {

            $qty3 = $qty6 = $qty9 = $qtyst = 0;
            $mos3 = $mos6 = $mos9 = $most = 0;

            $itemlines = $this->calculate_lines_needed($data, $config);
            // echo "Calculated Item Lines: " . $itemlines . "\n";

            if ($counterkwek + $itemlines > $page) {
                // if ($this->reporter->linecounter  > $page) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:80px;');
                $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= '</div>';
                $str .= $this->reporter->page_break();
                // $str .= $this->reporter->page_break();
                // $str .= '</div>';
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                $str .= $this->vendor_with_item_concession_header($config,  $suppcode, $supname, $centerr);
                $counterkwek = 0;
                // $page = $page + $count;
            }


            if ($suppcode != '' && $suppcode != $data->supcode) {
                // echo "Page Break Triggered!\n";
                // displaytotal:
                $str .= $this->reporter->endtable();

                if ($totalsld < 0) { //ito yung negative
                    $totalslds = '(' . number_format(abs($totalsld), 2) . ')';
                } else {
                    $totalslds = $totalsld == 0 ? '-' : number_format($totalsld, 2);
                }
                if ($totaldiscount < 0) { //ito yung negative
                    $totaldiscounts = '(' . number_format(abs($totaldiscount), 2) . ')';
                } else {
                    $totaldiscounts = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
                }
                if ($comm1here < 0) { //ito yung negative
                    $comm1heres = '(' . number_format(abs($comm1here), 2) . ')';
                } else {
                    $comm1heres = $comm1here == 0 ? '-' : number_format($comm1here, 2);
                }
                if ($tldqty3 < 0) { //ito yung negative
                    $tldqty3s = '(' . number_format(abs($tldqty3), 2) . ')';
                } else {
                    $tldqty3s = $tldqty3 == 0 ? '-' : number_format($tldqty3, 2);
                }
                if ($tldqty6 < 0) { //ito yung negative
                    $tldqty6s = '(' . number_format(abs($tldqty6), 2) . ')';
                } else {
                    $tldqty6s = $tldqty6 == 0 ? '-' : number_format($tldqty6, 2);
                }
                if ($tldqty9 < 0) { //ito yung negative
                    $tldqty9s = '(' . number_format(abs($tldqty9), 2) . ')';
                } else {
                    $tldqty9s = $tldqty9 == 0 ? '-' : number_format($tldqty9, 2);
                }
                if ($tldqtyfzero < 0) { //ito yung negative
                    $tldqtyfzeros = '(' . number_format(abs($tldqtyfzero), 2) . ')';
                } else {
                    $tldqtyfzeros = $tldqtyfzero == 0 ? '-' : number_format($tldqtyfzero, 2);
                }
                if ($tldqtyfreg < 0) { //ito yung negative
                    $tldqtyfregs = '(' . number_format(abs($tldqtyfreg), 2) . ')';
                } else {
                    $tldqtyfregs = $tldqtyfreg == 0 ? '-' : number_format($tldqtyfreg, 2);
                }
                if ($tldqtyst < 0) { //ito yung negative
                    $tldqtysts = '(' . number_format(abs($tldqtyst), 2) . ')';
                } else {
                    $tldqtysts = $tldqtyst == 0 ? '-' : number_format($tldqtyst, 2);
                }
                if ($commm2 < 0) { //ito yung negative
                    $commm2s = '(' . number_format(abs($commm2), 2) . ')';
                } else {
                    $commm2s = $commm2 == 0 ? '-' : number_format($commm2, 2);
                }
                if ($tlssrp < 0) { //ito yung negative
                    $tlssrps = '(' . number_format(abs($tlssrp), 2) . ')';
                } else {
                    $tlssrps = $tlssrp == 0 ? '-' : number_format($tlssrp, 2);
                }
                if ($tl2srp < 0) { //ito yung negative
                    $tl2srpz = '(' . number_format(abs($tl2srp), 2) . ')';
                } else {
                    $tl2srpz = $tl2srp == 0 ? '-' : number_format($tl2srp, 2);
                }
                if ($gross < 0) { //ito yung negative
                    $grosss = '(' . number_format(abs($gross), 2) . ')';
                } else {
                    $grosss = $gross == 0 ? '-' : number_format($gross, 2);
                }
                if ($tldmos3 < 0) { //ito yung negative
                    $tldmos3s = '(' . number_format(abs($tldmos3), 2) . ')';
                } else {
                    $tldmos3s = $tldmos3 == 0 ? '-' : number_format($tldmos3, 2);
                }
                if ($tldmos6 < 0) { //ito yung negative
                    $tldmos6s = '(' . number_format(abs($tldmos6), 2) . ')';
                } else {
                    $tldmos6s = $tldmos6 == 0 ? '-' : number_format($tldmos6, 2);
                }
                if ($tldmos9 < 0) { //ito yung negative
                    $tldmos9s = '(' . number_format(abs($tldmos9), 2) . ')';
                } else {
                    $tldmos9s = $tldmos9 == 0 ? '-' : number_format($tldmos9, 2);
                }
                if ($tldfzero < 0) { //ito yung negative
                    $tldfzeros = '(' . number_format(abs($tldfzero), 2) . ')';
                } else {
                    $tldfzeros = $tldfzero == 0 ? '-' : number_format($tldfzero, 2);
                }
                if ($tldfreg < 0) { //ito yung negative
                    $tldfregs = '(' . number_format(abs($tldfreg), 2) . ')';
                } else {
                    $tldfregs = $tldfreg == 0 ? '-' : number_format($tldfreg, 2);
                }
                if ($tldmos < 0) { //ito yung negative
                    $tldmoss = '(' . number_format(abs($tldmos), 2) . ')';
                } else {
                    $tldmoss = $tldmos == 0 ? '-' : number_format($tldmos, 2);
                }
                if ($netp < 0) { //ito yung negative
                    $netps = '(' . number_format(abs($netp), 2) . ')';
                } else {
                    $netps = $netp == 0 ? '-' : number_format($netp, 2);
                }

                if ($costlang < 0) { //ito yung negative
                    $costlangs = '(' . number_format(abs($costlang), 2) . ')';
                } else {
                    $costlangs = $costlang == 0 ? '-' : number_format($costlang, 2);
                }
                switch ($reptype) {
                    case '4': //concession

                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                        $str .= $this->reporter->col($totalslds, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($totaldiscounts, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($comm1heres, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty3s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty6s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty9s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfzeros, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfregs, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtysts, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($commm2s, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();


                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($grosss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos3s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos6s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos9s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfzeros, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfregs, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmoss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($netps, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        break;
                    case '5': //consignment
                    case '6': //outright
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                        $str .= $this->reporter->col($totalslds, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($costlangs, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($totaldiscounts, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        // $str .= $this->reporter->col($comm1heres, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty3s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty6s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty9s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfzeros, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfregs, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtysts, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($commm2s, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();


                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        // $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($grosss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos3s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos6s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos9s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfzeros, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfregs, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmoss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($netps, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        break;
                }


                $totalsld = $totaldiscount =    $tlssrp = 0;
                $tl2srp =  $comm1here =  $gross =   $percenthere =   $commm2 = $netp = $tldqty3 = 0;
                $tldmos3 =  $tldqty6 =   $tldmos6 =   $tldqty9 =  $tldmos9 =   $tldqtyfzero = 0;
                $tldfzero = $tldqtyfreg =  $tldfreg =   $tldqtyst = $tldmos = $costlang = 0;


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
                $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '</div>';
                $str .= $this->reporter->page_break();

                $counterkwek = 0;
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                // $str .= '<br/>';


                $str .= $this->vendor_with_item_concession_header($config, $data->supcode, $data->suppliername, $data->center);
                $suppcode = $data->supcode;
                $supname = $data->suppliername;
                $centerr = $data->center;
            }


            $counterkwek += $itemlines;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            $financeregularqty = $financeregular = 0;
            $financezeroqty = $financezero = 0;
            $raw = $data->terminalid;
            $parts = explode('~', $raw);
            $firstpart = $parts[0];
            switch ($firstpart) {
                case 'CCASHALOALDI':
                case 'CHOMECREDIT':
                case 'CHOMECREDIT0':
                case 'CCASHALO':
                case 'DCASHALOALDI':
                case 'DHOMECREDIT':
                case 'DHOMECREDIT0':
                case 'DCASHALO':
                case 'CASHALOALDI':
                case 'HOMECREDIT':
                case 'HOMECREDIT0':
                case 'CASHALO':
                    // case 'CAUGBGCASH':
                    if ($data->bankrate != 0) {
                        $financeregularqty = $data->totalsold;
                        $financeregular = $data->cardcharge;
                    } else {
                        $financezeroqty = $data->totalsold;
                        $financezero = $data->cardcharge;
                    }
                    break;
                default:
                    if (!empty($data->banktype) && $data->cardcharge != 0) {
                        switch (strtoupper(str_replace(' ', '', $data->banktype))) {
                            case '3MONS':
                            case '3MOS':    
                                $qty3 = $data->totalsold;
                                $mos3 = $data->cardcharge;
                                break;
                            case '6MONS':
                            case '6MOS':       
                                $qty6 =  $data->totalsold;
                                $mos6 =  $data->cardcharge;
                                break;
                            case '9MONS':
                            case '12MONS':
                            case '9MOS':
                            case '12MOS':
                                $qty9 =  $data->totalsold;
                                $mos9 =  $data->cardcharge;
                                break;
                            case 'STRAIGHT':
                            case 'DEBIT':
                            case 'AUBGCASH':
                            case 'DEBIT/BANCNET':     
                                $qtyst =  $data->totalsold;
                                $most =  $data->cardcharge;

                                break;
                        }
                    }
                    break;
            }


            $disp_financeregularqty = ($financeregularqty != 0) ? $financeregularqty : 0;
            $disp_financeregular    = ($financeregular != 0)    ? $financeregular : 0;

            $disp_financezeroqty    = ($financezeroqty != 0)    ? $financezeroqty   : 0;
            $disp_financezero       = ($financezero != 0)       ? $financezero     : 0;


            $display_mos3 = ($mos3 != 0) ? $mos3 : 0;
            $display_mos6 = ($mos6 != 0) ? $mos6 : 0;
            $display_mos9 = ($mos9 != 0) ? $mos9 : 0;
            $display_mos = ($most != 0) ? $most : 0;

            $disp_qty3  = ($qty3 != 0) ? $qty3 : 0;
            $disp_qty6  = ($qty6 != 0) ? $qty6 : 0;
            $disp_qty9  = ($qty9 != 0) ? $qty9 : 0;
            $disp_qtyst = ($qtyst != 0) ? $qtyst : 0;


            $grosspayable = $data->totalcost; //grosspayablr
            $grossap2s = $data->grossap2; //com2

            $charges = $grossap2s + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;

            $netpayable =  $grosspayable  + $charges;
            $disp_netpayable = $netpayable;


            $discount = $data->disc;
            $discount = ($discount != 0) ? $discount : 0;



            $totalsold  = ($data->totalsold != 0) ? floatval($data->totalsold) : 0;
            $srp        = ($data->srp != 0) ? floatval($data->srp) : 0;
            $tlsrp      = ($data->ext != 0) ? floatval($data->ext) : 0;
            // $comm1 = ($data->ext * ($data->comm1 / 100) * -1);
            $comm1 = $data->comm1;

            $percentage = $data->percentage;

            $cost = $data->cost;
            $cost = ($cost != 0) ? $cost : 0;



            $comap      = ($data->totalcost != 0) ? floatval($data->totalcost) : 0;
            $grossap2   = ($grossap2s != 0) ? floatval($grossap2s) : 0;


            if ($totalsold < 0) { //ito yung negative
                $totalsolds = '(' . number_format(abs($totalsold), 2) . ')';
            } else {
                $totalsolds = $totalsold == 0 ? '-' : number_format($totalsold, 2);
            }
            if ($srp < 0) { //ito yung negative
                $srps = '(' . number_format(abs($srp), 2) . ')';
            } else {
                $srps = $srp == 0 ? '-' : number_format($srp, 2);
            }
            if ($discount < 0) { //ito yung negative
                $discounts = '(' . number_format(abs($discount), 2) . ')';
            } else {
                $discounts = $discount == 0 ? '-' : number_format($discount, 2);
            }
            if ($tlsrp < 0) { //ito yung negative
                $tlsrps = '(' . number_format(abs($tlsrp), 2) . ')';
            } else {
                $tlsrps = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
            }
            if ($comm1 < 0) { //ito yung negative
                $comm1s = '(' . number_format(abs($comm1), 2) . ')';
            } else {
                $comm1s = $comm1 == 0 ? '-' : number_format($comm1, 2);
            }
            if ($comap < 0) { //ito yung negative
                $comaps = '(' . number_format(abs($comap), 2) . ')';
            } else {
                $comaps = $comap == 0 ? '-' : number_format($comap, 2);
            }
            if ($disp_qty3 < 0) { //ito yung negative
                $disp_qty3 = '(' . number_format(abs($disp_qty3), 2) . ')';
            } else {
                $disp_qty3 = $disp_qty3 == 0 ? '-' : number_format($disp_qty3, 2);
            }
            if ($display_mos3 < 0) { //ito yung negative
                $display_mos3 = '(' . number_format(abs($display_mos3), 2) . ')';
            } else {
                $display_mos3 = $display_mos3 == 0 ? '-' : number_format($display_mos3, 2);
            }
            if ($disp_qty6 < 0) { //ito yung negative
                $disp_qty6 = '(' . number_format(abs($disp_qty6), 2) . ')';
            } else {
                $disp_qty6 = $disp_qty6 == 0 ? '-' : number_format($disp_qty6, 2);
            }
            if ($display_mos6 < 0) { //ito yung negative
                $display_mos6 = '(' . number_format(abs($display_mos6), 2) . ')';
            } else {
                $display_mos6 = $display_mos6 == 0 ? '-' : number_format($display_mos6, 2);
            }

            if ($disp_qty9 < 0) { //ito yung negative
                $disp_qty9 = '(' . number_format(abs($disp_qty9), 2) . ')';
            } else {
                $disp_qty9 = $disp_qty9 == 0 ? '-' : number_format($disp_qty9, 2);
            }
            if ($display_mos9 < 0) { //ito yung negative
                $display_mos9 = '(' . number_format(abs($display_mos9), 2) . ')';
            } else {
                $display_mos9 = $display_mos9 == 0 ? '-' : number_format($display_mos9, 2);
            }
            if ($disp_financezeroqty < 0) { //ito yung negative
                $disp_financezeroqty = '(' . number_format(abs($disp_financezeroqty), 2) . ')';
            } else {
                $disp_financezeroqty = $disp_financezeroqty == 0 ? '-' : number_format($disp_financezeroqty, 2);
            }
            if ($disp_financezero < 0) { //ito yung negative
                $disp_financezero = '(' . number_format(abs($disp_financezero), 2) . ')';
            } else {
                $disp_financezero = $disp_financezero == 0 ? '-' : number_format($disp_financezero, 2);
            }
            if ($disp_financeregularqty < 0) { //ito yung negative
                $disp_financeregularqty = '(' . number_format(abs($disp_financeregularqty), 2) . ')';
            } else {
                $disp_financeregularqty = $disp_financeregularqty == 0 ? '-' : number_format($disp_financeregularqty, 2);
            }
            if ($disp_financeregular < 0) { //ito yung negative
                $disp_financeregular = '(' . number_format(abs($disp_financeregular), 2) . ')';
            } else {
                $disp_financeregular = $disp_financeregular == 0 ? '-' : number_format($disp_financeregular, 2);
            }
            if ($disp_qtyst < 0) { //ito yung negative
                $disp_qtyst = '(' . number_format(abs($disp_qtyst), 2) . ')';
            } else {
                $disp_qtyst = $disp_qtyst == 0 ? '-' : number_format($disp_qtyst, 2);
            }
            if ($display_mos < 0) { //ito yung negative
                $display_mos = '(' . number_format(abs($display_mos), 2) . ')';
            } else {
                $display_mos = $display_mos == 0 ? '-' : number_format($display_mos, 2);
            }
            if ($grossap2 < 0) { //ito yung negative
                $grossap2d = '(' . number_format(abs($grossap2), 2) . ')';
            } else {
                $grossap2d = $grossap2 == 0 ? '-' : number_format($grossap2, 2);
            }
            if ($disp_netpayable < 0) { //ito yung negative
                $disp_netpayable = '(' . number_format(abs($disp_netpayable), 2) . ')';
            } else {
                $disp_netpayable = $disp_netpayable == 0 ? '-' : number_format($disp_netpayable, 2);
            }

            if ($cost < 0) { //ito yung negative
                $dispcost = '(' . number_format(abs($cost), 2) . ')';
            } else {
                $dispcost = $cost == 0 ? '-' : number_format($cost, 2);
            }

            switch ($reptype) {

                case '4': //concession
                    $str .= $this->reporter->col($data->barcode, '68', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($data->itemdesc, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($totalsolds, '51', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($srps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($discounts, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //totalsrp
                    $str .= $this->reporter->col($tlsrps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //com1
                    $str .= $this->reporter->col($comm1s, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($comaps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty3, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos3, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty6, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos6,  '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty9, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos9, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezeroqty,  '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezero, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregularqty, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregular, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qtyst, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($grossap2d, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_netpayable, '70', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    break;
                case '5': //consignment
                case '6': //outright
                    $str .= $this->reporter->col($data->barcode, '68', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($data->itemdesc, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($totalsolds, '51', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($dispcost, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($srps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($discounts, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //totalsrp
                    $str .= $this->reporter->col($tlsrps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //com1
                    // $str .= $this->reporter->col($comm1s, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($comaps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty3, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos3, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty6, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos6,  '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty9, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos9, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezeroqty,  '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezero, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregularqty, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregular, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qtyst, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($grossap2d, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_netpayable, '70', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    break;
            }

            $totalsld += $totalsold;
            $totaldiscount += $discount;
            $tlssrp += $srp;
            $tl2srp += $tlsrp;
            $tlcost += $comap;
            $comm1here += $comm1;
            $gross += $comap;
            $percenthere += $percentage;
            $commm2 += $grossap2;
            $netp += $netpayable;

            $tldqty3 += $qty3;
            $tldmos3 += $mos3;
            $tldqty6 += $qty6;
            $tldmos6 += $mos6;

            $tldqty9 += $qty9;
            $tldmos9 += $mos9;

            $tldqtyfzero += $financezeroqty;
            $tldfzero += $financezero;

            $tldqtyfreg += $financeregularqty;
            $tldfreg += $financeregular;

            $tldqtyst += $qtyst;
            $tldmos += $most;
            $costlang += $cost;
        } //end foreach

        if ($totalsld < 0) { //ito yung negative
            $totalsldz = '(' . number_format(abs($totalsld), 2) . ')';
        } else {
            $totalsldz = $totalsld == 0 ? '-' : number_format($totalsld, 2);
        }
        if ($totaldiscount < 0) { //ito yung negative
            $totaldiscountz = '(' . number_format(abs($totaldiscount), 2) . ')';
        } else {
            $totaldiscountz = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
        }
        if ($comm1here < 0) { //ito yung negative
            $comm1herez = '(' . number_format(abs($comm1here), 2) . ')';
        } else {
            $comm1herez = $comm1here == 0 ? '-' : number_format($comm1here, 2);
        }
        if ($tldqty3 < 0) { //ito yung negative
            $tldqty3z = '(' . number_format(abs($tldqty3), 2) . ')';
        } else {
            $tldqty3z = $tldqty3 == 0 ? '-' : number_format($tldqty3, 2);
        }
        if ($tldqty6 < 0) { //ito yung negative
            $tldqty6z = '(' . number_format(abs($tldqty6), 2) . ')';
        } else {
            $tldqty6z = $tldqty6 == 0 ? '-' : number_format($tldqty6, 2);
        }
        if ($tldqty9 < 0) { //ito yung negative
            $tldqty9z = '(' . number_format(abs($tldqty9), 2) . ')';
        } else {
            $tldqty9z = $tldqty9 == 0 ? '-' : number_format($tldqty9, 2);
        }
        if ($tldqtyfzero < 0) { //ito yung negative
            $tldqtyfzeroz = '(' . number_format(abs($tldqtyfzero), 2) . ')';
        } else {
            $tldqtyfzeroz = $tldqtyfzero == 0 ? '-' : number_format($tldqtyfzero, 2);
        }
        if ($tldqtyfreg < 0) { //ito yung negative
            $tldqtyfregz = '(' . number_format(abs($tldqtyfreg), 2) . ')';
        } else {
            $tldqtyfregz = $tldqtyfreg == 0 ? '-' : number_format($tldqtyfreg, 2);
        }
        if ($tldqtyst < 0) { //ito yung negative
            $tldqtystz = '(' . number_format(abs($tldqtyst), 2) . ')';
        } else {
            $tldqtystz = $tldqtyst == 0 ? '-' : number_format($tldqtyst, 2);
        }
        if ($commm2 < 0) { //ito yung negative
            $commm2z = '(' . number_format(abs($commm2), 2) . ')';
        } else {
            $commm2z = $commm2 == 0 ? '-' : number_format($commm2, 2);
        }

        if ($tlssrp < 0) { //ito yung negative
            $tlssrpz = '(' . number_format(abs($tlssrp), 2) . ')';
        } else {
            $tlssrpz = $tlssrp == 0 ? '-' : number_format($tlssrp, 2);
        }
        if ($tl2srp < 0) { //ito yung negative
            $tl2srpz = '(' . number_format(abs($tl2srp), 2) . ')';
        } else {
            $tl2srpz = $tl2srp == 0 ? '-' : number_format($tl2srp, 2);
        }
        if ($gross < 0) { //ito yung negative
            $grossz = '(' . number_format(abs($gross), 2) . ')';
        } else {
            $grossz = $gross == 0 ? '-' : number_format($gross, 2);
        }
        if ($tldmos3 < 0) { //ito yung negative
            $tldmos3z = '(' . number_format(abs($tldmos3), 2) . ')';
        } else {
            $tldmos3z = $tldmos3 == 0 ? '-' : number_format($tldmos3, 2);
        }
        if ($tldmos6 < 0) { //ito yung negative
            $tldmos6z = '(' . number_format(abs($tldmos6), 2) . ')';
        } else {
            $tldmos6z = $tldmos6 == 0 ? '-' : number_format($tldmos6, 2);
        }
        if ($tldmos9 < 0) { //ito yung negative
            $tldmos9z = '(' . number_format(abs($tldmos9), 2) . ')';
        } else {
            $tldmos9z = $tldmos9 == 0 ? '-' : number_format($tldmos9, 2);
        }
        if ($tldfzero < 0) { //ito yung negative
            $tldfzeroz = '(' . number_format(abs($tldfzero), 2) . ')';
        } else {
            $tldfzeroz = $tldfzero == 0 ? '-' : number_format($tldfzero, 2);
        }
        if ($tldfreg < 0) { //ito yung negative
            $tldfregz = '(' . number_format(abs($tldfreg), 2) . ')';
        } else {
            $tldfregz = $tldfreg == 0 ? '-' : number_format($tldfreg, 2);
        }
        if ($tldmos < 0) { //ito yung negative
            $tldmosz = '(' . number_format(abs($tldmos), 2) . ')';
        } else {
            $tldmosz = $tldmos == 0 ? '-' : number_format($tldmos, 2);
        }
        if ($netp < 0) { //ito yung negative
            $netpz = '(' . number_format(abs($netp), 2) . ')';
        } else {
            $netpz = $netp == 0 ? '-' : number_format($netp, 2);
        }

        if ($costlang < 0) { //ito yung negative
            $costlangs = '(' . number_format(abs($costlang), 2) . ')';
        } else {
            $costlangs = $costlang == 0 ? '-' : number_format($costlang, 2);
        }


        switch ($reptype) {
            case '4': //concession
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col($totalsldz, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($totaldiscountz, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($comm1herez, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty3z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty6z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty9z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfzeroz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfregz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtystz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($commm2z, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($grossz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos3z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos6z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos9z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfzeroz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfregz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmosz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($netpz, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
            case '5': //consignment
            case '6': //outright
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col($totalsldz, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($costlangs, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($totaldiscountz, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col($comm1herez, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty3z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty6z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty9z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfzeroz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfregz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtystz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($commm2z, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($grossz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos3z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos6z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos9z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfzeroz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfregz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmosz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($netpz, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
        }




        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
        $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</div>';
        $str .= '<div style="position: relative;margin:-100px 0 10px 0;">';

        $str .= "<div style='position:absolute; bottom:50px'>";
        $sign = URL::to('/images/homeworks/checked.png');
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('<img src ="' . $sign . '" alt="test" width="60px" height ="50px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';

        $str .= $this->reporter->endreport();
        $str .= '</div>';
        return $str;
    }

    public function vendor_with_item_concession($config)
    {
        $result = $this->concession($config);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '7';
        $reptype = $config['params']['dataparams']['reporttype'];
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }


        $str = '';
        $layoutsize = '1200';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];
        $centerhere = $config['params']['dataparams']['center'];
        $clienthere = $config['params']['dataparams']['dclientname'];
        // var_dump($centerhere);

        $totalsld = 0;
        $totaldiscount = 0;
        $tlsrp = 0;
        $tllsrp = 0;
        $tlssrp = 0;
        $tlcost = 0;
        $comm1here = 0;
        $gross = 0;
        $percenthere = 0;
        $commm2 = 0;
        $netp = 0;

        $tldqty3 = 0;
        $tldmos3 = 0;
        $tldqty6 = 0;
        $tldmos6 = 0;

        $tldqty9 = 0;
        $tldmos9 = 0;


        $tldqtyfzero = 0;
        $tldfzero = 0;

        $tldqtyfreg = 0;
        $tldfreg = 0;

        $tldqtyst = 0;
        $tldmos = 0;
        // $page = 45;
        $counterkwek = 0;
        $tl2srp = 0;
        $costlang = 0;

        
                // $gtotalsld = $gtotaldiscount =    $gtlssrp = 0;
                // $gtl2srp =  $gcomm1here =  $ggross =   $gpercenthere =   $gcommm2 = $gnetp = $gtldqty3 = 0;
                // $gtldmos3 =  $gtldqty6 =   $gtldmos6 =   $gtldqty9 =  $gtldmos9 =   $gtldqtyfzero = 0;
                // $gtldfzero = $gtldqtyfreg =  $gtldfreg =   $gtldqtyst = $gtldmos = $gcostlang = 0;

        $suppcode = '';
        $supname = '';
        $centerr = '';
        $i = 0;
        $count = 48;
        $page = 32; //32

        $current_supplier = '';
        $current_center = '';
        $is_first_item = true;

        foreach ($result as $key => $data) {

            $qty3 = $qty6 = $qty9 = $qtyst = 0;
            $mos3 = $mos6 = $mos9 = $most = 0;

            $itemlines = $this->calculate_lines_needed($data, $config);
            // echo "Calculated Item Lines: " . $itemlines . "\n";

            if ($counterkwek + $itemlines > $page) {
                // if ($this->reporter->linecounter  > $page) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:80px;');
                $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= '</div>';
                $str .= $this->reporter->page_break();
                // $str .= $this->reporter->page_break();
                // $str .= '</div>';
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                $str .= $this->vendor_with_item_concession_header($config,  $suppcode, $supname, $centerr);
                $counterkwek = 0;
                // $page = $page + $count;
            }


            $shoultotal = false;

            if ($centerr == '' && $suppcode == '') {
                $centerr =  $data->center;
                $suppcode = $data->supcode;
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                $str .= $this->vendor_with_item_concession_header($config, $data->supcode, $data->suppliername, $data->center);
                $supname = $data->suppliername;
                // goto fav;
            }

            if ($centerr != $data->center) {
                $shoultotal = true; //gumana to kapag walang filter ang branch at may filter ang supplier
            } else {
                if ($suppcode != $data->supcode) {
                    $shoultotal = true;
                }
            }

            if ($shoultotal) {

                $str .= $this->reporter->endtable();

                if ($totalsld < 0) { //ito yung negative
                    $totalslds = '(' . number_format(abs($totalsld), 2) . ')';
                } else {
                    $totalslds = $totalsld == 0 ? '-' : number_format($totalsld, 2);
                }
                if ($totaldiscount < 0) { //ito yung negative
                    $totaldiscounts = '(' . number_format(abs($totaldiscount), 2) . ')';
                } else {
                    $totaldiscounts = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
                }
                if ($comm1here < 0) { //ito yung negative
                    $comm1heres = '(' . number_format(abs($comm1here), 2) . ')';
                } else {
                    $comm1heres = $comm1here == 0 ? '-' : number_format($comm1here, 2);
                }
                if ($tldqty3 < 0) { //ito yung negative
                    $tldqty3s = '(' . number_format(abs($tldqty3), 2) . ')';
                } else {
                    $tldqty3s = $tldqty3 == 0 ? '-' : number_format($tldqty3, 2);
                }
                if ($tldqty6 < 0) { //ito yung negative
                    $tldqty6s = '(' . number_format(abs($tldqty6), 2) . ')';
                } else {
                    $tldqty6s = $tldqty6 == 0 ? '-' : number_format($tldqty6, 2);
                }
                if ($tldqty9 < 0) { //ito yung negative
                    $tldqty9s = '(' . number_format(abs($tldqty9), 2) . ')';
                } else {
                    $tldqty9s = $tldqty9 == 0 ? '-' : number_format($tldqty9, 2);
                }
                if ($tldqtyfzero < 0) { //ito yung negative
                    $tldqtyfzeros = '(' . number_format(abs($tldqtyfzero), 2) . ')';
                } else {
                    $tldqtyfzeros = $tldqtyfzero == 0 ? '-' : number_format($tldqtyfzero, 2);
                }
                if ($tldqtyfreg < 0) { //ito yung negative
                    $tldqtyfregs = '(' . number_format(abs($tldqtyfreg), 2) . ')';
                } else {
                    $tldqtyfregs = $tldqtyfreg == 0 ? '-' : number_format($tldqtyfreg, 2);
                }
                if ($tldqtyst < 0) { //ito yung negative
                    $tldqtysts = '(' . number_format(abs($tldqtyst), 2) . ')';
                } else {
                    $tldqtysts = $tldqtyst == 0 ? '-' : number_format($tldqtyst, 2);
                }
                if ($commm2 < 0) { //ito yung negative
                    $commm2s = '(' . number_format(abs($commm2), 2) . ')';
                } else {
                    $commm2s = $commm2 == 0 ? '-' : number_format($commm2, 2);
                }
                if ($tlssrp < 0) { //ito yung negative
                    $tlssrps = '(' . number_format(abs($tlssrp), 2) . ')';
                } else {
                    $tlssrps = $tlssrp == 0 ? '-' : number_format($tlssrp, 2);
                }
                if ($tl2srp < 0) { //ito yung negative
                    $tl2srpz = '(' . number_format(abs($tl2srp), 2) . ')';
                } else {
                    $tl2srpz = $tl2srp == 0 ? '-' : number_format($tl2srp, 2);
                }
                if ($gross < 0) { //ito yung negative
                    $grosss = '(' . number_format(abs($gross), 2) . ')';
                } else {
                    $grosss = $gross == 0 ? '-' : number_format($gross, 2);
                }
                if ($tldmos3 < 0) { //ito yung negative
                    $tldmos3s = '(' . number_format(abs($tldmos3), 2) . ')';
                } else {
                    $tldmos3s = $tldmos3 == 0 ? '-' : number_format($tldmos3, 2);
                }
                if ($tldmos6 < 0) { //ito yung negative
                    $tldmos6s = '(' . number_format(abs($tldmos6), 2) . ')';
                } else {
                    $tldmos6s = $tldmos6 == 0 ? '-' : number_format($tldmos6, 2);
                }
                if ($tldmos9 < 0) { //ito yung negative
                    $tldmos9s = '(' . number_format(abs($tldmos9), 2) . ')';
                } else {
                    $tldmos9s = $tldmos9 == 0 ? '-' : number_format($tldmos9, 2);
                }
                if ($tldfzero < 0) { //ito yung negative
                    $tldfzeros = '(' . number_format(abs($tldfzero), 2) . ')';
                } else {
                    $tldfzeros = $tldfzero == 0 ? '-' : number_format($tldfzero, 2);
                }
                if ($tldfreg < 0) { //ito yung negative
                    $tldfregs = '(' . number_format(abs($tldfreg), 2) . ')';
                } else {
                    $tldfregs = $tldfreg == 0 ? '-' : number_format($tldfreg, 2);
                }
                if ($tldmos < 0) { //ito yung negative
                    $tldmoss = '(' . number_format(abs($tldmos), 2) . ')';
                } else {
                    $tldmoss = $tldmos == 0 ? '-' : number_format($tldmos, 2);
                }
                if ($netp < 0) { //ito yung negative
                    $netps = '(' . number_format(abs($netp), 2) . ')';
                } else {
                    $netps = $netp == 0 ? '-' : number_format($netp, 2);
                }

                if ($costlang < 0) { //ito yung negative
                    $costlangs = '(' . number_format(abs($costlang), 2) . ')';
                } else {
                    $costlangs = $costlang == 0 ? '-' : number_format($costlang, 2);
                }
                switch ($reptype) {
                    case '4': //concession

                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                        $str .= $this->reporter->col($totalslds, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($totaldiscounts, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($comm1heres, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty3s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty6s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty9s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfzeros, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfregs, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtysts, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($commm2s, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();


                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($grosss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos3s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos6s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos9s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfzeros, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfregs, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmoss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($netps, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        break;
                    case '5': //consignment
                    case '6': //outright
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                        $str .= $this->reporter->col($totalslds, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($costlangs, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($totaldiscounts, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        // $str .= $this->reporter->col($comm1heres, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty3s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty6s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqty9s, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfzeros, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtyfregs, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldqtysts, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($commm2s, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();


                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        // $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($grosss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos3s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos6s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmos9s, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfzeros, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldfregs, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tldmoss, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($netps, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        break;

                 //test
                // $gtotalsld += $totalsld;
                // $gtotaldiscount +=$totaldiscount; 
                // $gtlssrp += $tlssrp;
                // $gtl2srp += $tl2srp; 
                // $gcomm1here +=$comm1here;
                // $ggross +=$gross;
                // $gpercenthere +=$percenthere; 
                // $gcommm2 += $commm2;
                // $gnetp +=$netp; 
                // $gtldqty3 += $tldqty3 ;
                // $gtldmos3 +=$tldmos3;
                // $gtldqty6 +=$tldqty6;  
                // $gtldmos6 += $tldmos6;  
                // $gtldqty9 +=$tldqty9;
                // $gtldmos9 +=$tldmos9;
                // $gtldqtyfzero +=$tldqtyfzero;
                // $gtldfzero +=$tldfzero;
                // $gtldqtyfreg +=$tldqtyfreg ;
                // $gtldfreg +=$tldfreg;
                // $gtldqtyst +=$tldqtyst;
                // $gtldmos += $tldmos;
                // $gcostlang +=$costlang;

                }


                $totalsld = $totaldiscount =    $tlssrp = 0;
                $tl2srp =  $comm1here =  $gross =   $percenthere =   $commm2 = $netp = $tldqty3 = 0;
                $tldmos3 =  $tldqty6 =   $tldmos6 =   $tldqty9 =  $tldmos9 =   $tldqtyfzero = 0;
                $tldfzero = $tldqtyfreg =  $tldfreg =   $tldqtyst = $tldmos = $costlang = 0;


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
                $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '</div>';
                $str .= $this->reporter->page_break();

                $counterkwek = 0;
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                // $str .= '<br/>';


                $str .= $this->vendor_with_item_concession_header($config, $data->supcode, $data->suppliername, $data->center);
            }
            // fav:
            $suppcode = $data->supcode;
            $supname = $data->suppliername;
            $centerr = $data->center;
            $counterkwek += $itemlines;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();

            $financeregularqty = $financeregular = 0;
            $financezeroqty = $financezero = 0;
            $raw = $data->terminalid;
            $parts = explode('~', $raw);
            $firstpart = $parts[0];
            switch ($firstpart) {
                case 'CCASHALOALDI':
                case 'CHOMECREDIT':
                case 'CHOMECREDIT0':
                case 'CCASHALO':
                case 'DCASHALOALDI':
                case 'DHOMECREDIT':
                case 'DHOMECREDIT0':
                case 'DCASHALO':
                case 'CASHALOALDI':
                case 'HOMECREDIT':
                case 'HOMECREDIT0':
                case 'CASHALO':
                    // case 'CAUGBGCASH':
                    if ($data->bankrate != 0) {
                        $financeregularqty = $data->totalsold;
                        $financeregular = $data->cardcharge;
                    } else {
                        $financezeroqty = $data->totalsold;
                        $financezero = $data->cardcharge;
                    }
                    break;
                default:
                    if (!empty($data->banktype) && $data->cardcharge != 0) {
                        switch (strtoupper(str_replace(' ', '', $data->banktype))) {
                            case '3MONS':
                            case '3MOS':    
                                $qty3 = $data->totalsold;
                                $mos3 = $data->cardcharge;
                                break;
                            case '6MONS':
                            case '6MOS':    
                                $qty6 =  $data->totalsold;
                                $mos6 =  $data->cardcharge;
                                break;
                            case '9MONS':
                            case '12MONS':
                            case '9MOS':
                            case '12MOS':
                                $qty9 =  $data->totalsold;
                                $mos9 =  $data->cardcharge;
                                break;
                            case 'STRAIGHT':
                            case 'DEBIT':
                            case 'AUBGCASH':
                            case 'DEBIT/BANCNET':      
                                $qtyst =  $data->totalsold;
                                $most =  $data->cardcharge;

                                break;
                        }
                    }
                    break;
            }


            $disp_financeregularqty = ($financeregularqty != 0) ? $financeregularqty : 0;
            $disp_financeregular    = ($financeregular != 0)    ? $financeregular : 0;

            $disp_financezeroqty    = ($financezeroqty != 0)    ? $financezeroqty   : 0;
            $disp_financezero       = ($financezero != 0)       ? $financezero     : 0;


            $display_mos3 = ($mos3 != 0) ? $mos3 : 0;
            $display_mos6 = ($mos6 != 0) ? $mos6 : 0;
            $display_mos9 = ($mos9 != 0) ? $mos9 : 0;
            $display_mos = ($most != 0) ? $most : 0;

            $disp_qty3  = ($qty3 != 0) ? $qty3 : 0;
            $disp_qty6  = ($qty6 != 0) ? $qty6 : 0;
            $disp_qty9  = ($qty9 != 0) ? $qty9 : 0;
            $disp_qtyst = ($qtyst != 0) ? $qtyst : 0;


            $grosspayable = $data->totalcost; //grosspayablr
            $grossap2s = $data->grossap2; //com2

            $charges = $grossap2s + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;

            $netpayable =  $grosspayable  + $charges;
            $disp_netpayable = $netpayable;


            $discount = $data->disc;
            $discount = ($discount != 0) ? $discount : 0;



            $totalsold  = ($data->totalsold != 0) ? floatval($data->totalsold) : 0;
            $srp        = ($data->srp != 0) ? floatval($data->srp) : 0;
            $tlsrp      = ($data->ext != 0) ? floatval($data->ext) : 0;
            // $comm1 = ($data->ext * ($data->comm1 / 100) * -1);
            $comm1 = $data->comm1;

            $percentage = $data->percentage;

            $cost = $data->cost;
            $cost = ($cost != 0) ? $cost : 0;



            $comap      = ($data->totalcost != 0) ? floatval($data->totalcost) : 0;
            $grossap2   = ($grossap2s != 0) ? floatval($grossap2s) : 0;



            if ($totalsold < 0) { //ito yung negative
                $totalsolds = '(' . number_format(abs($totalsold), 2) . ')';
            } else {
                $totalsolds = $totalsold == 0 ? '-' : number_format($totalsold, 2);
            }
            if ($srp < 0) { //ito yung negative
                $srps = '(' . number_format(abs($srp), 2) . ')';
            } else {
                $srps = $srp == 0 ? '-' : number_format($srp, 2);
            }
            if ($discount < 0) { //ito yung negative
                $discounts = '(' . number_format(abs($discount), 2) . ')';
            } else {
                $discounts = $discount == 0 ? '-' : number_format($discount, 2);
            }
            if ($tlsrp < 0) { //ito yung negative
                $tlsrps = '(' . number_format(abs($tlsrp), 2) . ')';
            } else {
                $tlsrps = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
            }
            if ($comm1 < 0) { //ito yung negative
                $comm1s = '(' . number_format(abs($comm1), 2) . ')';
            } else {
                $comm1s = $comm1 == 0 ? '-' : number_format($comm1, 2);
            }
            if ($comap < 0) { //ito yung negative
                $comaps = '(' . number_format(abs($comap), 2) . ')';
            } else {
                $comaps = $comap == 0 ? '-' : number_format($comap, 2);
            }
            if ($disp_qty3 < 0) { //ito yung negative
                $disp_qty3 = '(' . number_format(abs($disp_qty3), 2) . ')';
            } else {
                $disp_qty3 = $disp_qty3 == 0 ? '-' : number_format($disp_qty3, 2);
            }
            if ($display_mos3 < 0) { //ito yung negative
                $display_mos3 = '(' . number_format(abs($display_mos3), 2) . ')';
            } else {
                $display_mos3 = $display_mos3 == 0 ? '-' : number_format($display_mos3, 2);
            }
            if ($disp_qty6 < 0) { //ito yung negative
                $disp_qty6 = '(' . number_format(abs($disp_qty6), 2) . ')';
            } else {
                $disp_qty6 = $disp_qty6 == 0 ? '-' : number_format($disp_qty6, 2);
            }
            if ($display_mos6 < 0) { //ito yung negative
                $display_mos6 = '(' . number_format(abs($display_mos6), 2) . ')';
            } else {
                $display_mos6 = $display_mos6 == 0 ? '-' : number_format($display_mos6, 2);
            }

            if ($disp_qty9 < 0) { //ito yung negative
                $disp_qty9 = '(' . number_format(abs($disp_qty9), 2) . ')';
            } else {
                $disp_qty9 = $disp_qty9 == 0 ? '-' : number_format($disp_qty9, 2);
            }
            if ($display_mos9 < 0) { //ito yung negative
                $display_mos9 = '(' . number_format(abs($display_mos9), 2) . ')';
            } else {
                $display_mos9 = $display_mos9 == 0 ? '-' : number_format($display_mos9, 2);
            }
            if ($disp_financezeroqty < 0) { //ito yung negative
                $disp_financezeroqty = '(' . number_format(abs($disp_financezeroqty), 2) . ')';
            } else {
                $disp_financezeroqty = $disp_financezeroqty == 0 ? '-' : number_format($disp_financezeroqty, 2);
            }
            if ($disp_financezero < 0) { //ito yung negative
                $disp_financezero = '(' . number_format(abs($disp_financezero), 2) . ')';
            } else {
                $disp_financezero = $disp_financezero == 0 ? '-' : number_format($disp_financezero, 2);
            }
            if ($disp_financeregularqty < 0) { //ito yung negative
                $disp_financeregularqty = '(' . number_format(abs($disp_financeregularqty), 2) . ')';
            } else {
                $disp_financeregularqty = $disp_financeregularqty == 0 ? '-' : number_format($disp_financeregularqty, 2);
            }
            if ($disp_financeregular < 0) { //ito yung negative
                $disp_financeregular = '(' . number_format(abs($disp_financeregular), 2) . ')';
            } else {
                $disp_financeregular = $disp_financeregular == 0 ? '-' : number_format($disp_financeregular, 2);
            }
            if ($disp_qtyst < 0) { //ito yung negative
                $disp_qtyst = '(' . number_format(abs($disp_qtyst), 2) . ')';
            } else {
                $disp_qtyst = $disp_qtyst == 0 ? '-' : number_format($disp_qtyst, 2);
            }
            if ($display_mos < 0) { //ito yung negative
                $display_mos = '(' . number_format(abs($display_mos), 2) . ')';
            } else {
                $display_mos = $display_mos == 0 ? '-' : number_format($display_mos, 2);
            }
            if ($grossap2 < 0) { //ito yung negative
                $grossap2d = '(' . number_format(abs($grossap2), 2) . ')';
            } else {
                $grossap2d = $grossap2 == 0 ? '-' : number_format($grossap2, 2);
            }
            if ($disp_netpayable < 0) { //ito yung negative
                $disp_netpayable = '(' . number_format(abs($disp_netpayable), 2) . ')';
            } else {
                $disp_netpayable = $disp_netpayable == 0 ? '-' : number_format($disp_netpayable, 2);
            }

            if ($cost < 0) { //ito yung negative
                $dispcost = '(' . number_format(abs($cost), 2) . ')';
            } else {
                $dispcost = $cost == 0 ? '-' : number_format($cost, 2);
            }

            switch ($reptype) {

                case '4': //concession
                    $str .= $this->reporter->col($data->barcode, '68', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($data->itemdesc, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($totalsolds, '51', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($srps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($discounts, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //totalsrp
                    $str .= $this->reporter->col($tlsrps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //com1
                    $str .= $this->reporter->col($comm1s, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($comaps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty3, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos3, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty6, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos6,  '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty9, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos9, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezeroqty,  '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezero, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregularqty, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregular, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qtyst, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($grossap2d, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_netpayable, '70', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    break;
                case '5': //consignment
                case '6': //outright
                    $str .= $this->reporter->col($data->barcode, '68', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($data->itemdesc, '100', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($totalsolds, '51', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($dispcost, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($srps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($discounts, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //totalsrp
                    $str .= $this->reporter->col($tlsrps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    //com1
                    // $str .= $this->reporter->col($comm1s, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($comaps, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty3, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos3, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty6, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos6,  '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qty9, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos9, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezeroqty,  '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financezero, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregularqty, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_financeregular, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_qtyst, '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($display_mos, '51', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($grossap2d, '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($disp_netpayable, '70', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    break;
            }

            $totalsld += $totalsold;
            $totaldiscount += $discount;
            $tlssrp += $srp;
            $tl2srp += $tlsrp;
            $tlcost += $comap;
            $comm1here += $comm1;
            $gross += $comap;
            $percenthere += $percentage;
            $commm2 += $grossap2;
            $netp += $netpayable;

            $tldqty3 += $qty3;
            $tldmos3 += $mos3;
            $tldqty6 += $qty6;
            $tldmos6 += $mos6;

            $tldqty9 += $qty9;
            $tldmos9 += $mos9;

            $tldqtyfzero += $financezeroqty;
            $tldfzero += $financezero;

            $tldqtyfreg += $financeregularqty;
            $tldfreg += $financeregular;

            $tldqtyst += $qtyst;
            $tldmos += $most;
            $costlang += $cost;


                // $gtotalsld += $totalsold;
                // $gtotaldiscount +=$discount; 
                // $gtlssrp += $srp;
                // $gtl2srp += $tlsrp; 
                // $gcomm1here +=$comm1;
                // $ggross +=$comap;
                // $gpercenthere +=$percentage; 
                // $gcommm2 += $grossap2;
                // $gnetp +=$netpayable; 

                // $gtldqty3 += $qty3 ;
                // $gtldmos3 +=$mos3;
                // $gtldqty6 +=$qty6;  
                // $gtldmos6 += $mos6;  
                // $gtldqty9 +=$qty9;
                // $gtldmos9 +=$mos9;
                // $gtldqtyfzero +=$financezeroqty;
                // $gtldfzero +=$financezero;
                // $gtldqtyfreg +=$financeregularqty ;
                // $gtldfreg +=$financeregular;
                // $gtldqtyst +=$qtyst;
                // $gtldmos += $most;
                // $gcostlang +=$cost;
        } //end foreach

        if ($totalsld < 0) { //ito yung negative
            $totalsldz = '(' . number_format(abs($totalsld), 2) . ')';
        } else {
            $totalsldz = $totalsld == 0 ? '-' : number_format($totalsld, 2);
        }
        if ($totaldiscount < 0) { //ito yung negative
            $totaldiscountz = '(' . number_format(abs($totaldiscount), 2) . ')';
        } else {
            $totaldiscountz = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
        }
        if ($comm1here < 0) { //ito yung negative
            $comm1herez = '(' . number_format(abs($comm1here), 2) . ')';
        } else {
            $comm1herez = $comm1here == 0 ? '-' : number_format($comm1here, 2);
        }
        if ($tldqty3 < 0) { //ito yung negative
            $tldqty3z = '(' . number_format(abs($tldqty3), 2) . ')';
        } else {
            $tldqty3z = $tldqty3 == 0 ? '-' : number_format($tldqty3, 2);
        }
        if ($tldqty6 < 0) { //ito yung negative
            $tldqty6z = '(' . number_format(abs($tldqty6), 2) . ')';
        } else {
            $tldqty6z = $tldqty6 == 0 ? '-' : number_format($tldqty6, 2);
        }
        if ($tldqty9 < 0) { //ito yung negative
            $tldqty9z = '(' . number_format(abs($tldqty9), 2) . ')';
        } else {
            $tldqty9z = $tldqty9 == 0 ? '-' : number_format($tldqty9, 2);
        }
        if ($tldqtyfzero < 0) { //ito yung negative
            $tldqtyfzeroz = '(' . number_format(abs($tldqtyfzero), 2) . ')';
        } else {
            $tldqtyfzeroz = $tldqtyfzero == 0 ? '-' : number_format($tldqtyfzero, 2);
        }
        if ($tldqtyfreg < 0) { //ito yung negative
            $tldqtyfregz = '(' . number_format(abs($tldqtyfreg), 2) . ')';
        } else {
            $tldqtyfregz = $tldqtyfreg == 0 ? '-' : number_format($tldqtyfreg, 2);
        }
        if ($tldqtyst < 0) { //ito yung negative
            $tldqtystz = '(' . number_format(abs($tldqtyst), 2) . ')';
        } else {
            $tldqtystz = $tldqtyst == 0 ? '-' : number_format($tldqtyst, 2);
        }
        if ($commm2 < 0) { //ito yung negative
            $commm2z = '(' . number_format(abs($commm2), 2) . ')';
        } else {
            $commm2z = $commm2 == 0 ? '-' : number_format($commm2, 2);
        }

        if ($tlssrp < 0) { //ito yung negative
            $tlssrpz = '(' . number_format(abs($tlssrp), 2) . ')';
        } else {
            $tlssrpz = $tlssrp == 0 ? '-' : number_format($tlssrp, 2);
        }
        if ($tl2srp < 0) { //ito yung negative
            $tl2srpz = '(' . number_format(abs($tl2srp), 2) . ')';
        } else {
            $tl2srpz = $tl2srp == 0 ? '-' : number_format($tl2srp, 2);
        }
        if ($gross < 0) { //ito yung negative
            $grossz = '(' . number_format(abs($gross), 2) . ')';
        } else {
            $grossz = $gross == 0 ? '-' : number_format($gross, 2);
        }
        if ($tldmos3 < 0) { //ito yung negative
            $tldmos3z = '(' . number_format(abs($tldmos3), 2) . ')';
        } else {
            $tldmos3z = $tldmos3 == 0 ? '-' : number_format($tldmos3, 2);
        }
        if ($tldmos6 < 0) { //ito yung negative
            $tldmos6z = '(' . number_format(abs($tldmos6), 2) . ')';
        } else {
            $tldmos6z = $tldmos6 == 0 ? '-' : number_format($tldmos6, 2);
        }
        if ($tldmos9 < 0) { //ito yung negative
            $tldmos9z = '(' . number_format(abs($tldmos9), 2) . ')';
        } else {
            $tldmos9z = $tldmos9 == 0 ? '-' : number_format($tldmos9, 2);
        }
        if ($tldfzero < 0) { //ito yung negative
            $tldfzeroz = '(' . number_format(abs($tldfzero), 2) . ')';
        } else {
            $tldfzeroz = $tldfzero == 0 ? '-' : number_format($tldfzero, 2);
        }
        if ($tldfreg < 0) { //ito yung negative
            $tldfregz = '(' . number_format(abs($tldfreg), 2) . ')';
        } else {
            $tldfregz = $tldfreg == 0 ? '-' : number_format($tldfreg, 2);
        }
        if ($tldmos < 0) { //ito yung negative
            $tldmosz = '(' . number_format(abs($tldmos), 2) . ')';
        } else {
            $tldmosz = $tldmos == 0 ? '-' : number_format($tldmos, 2);
        }
        if ($netp < 0) { //ito yung negative
            $netpz = '(' . number_format(abs($netp), 2) . ')';
        } else {
            $netpz = $netp == 0 ? '-' : number_format($netp, 2);
        }

        if ($costlang < 0) { //ito yung negative
            $costlangs = '(' . number_format(abs($costlang), 2) . ')';
        } else {
            $costlangs = $costlang == 0 ? '-' : number_format($costlang, 2);
        }


        switch ($reptype) {
            case '4': //concession
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col($totalsldz, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($totaldiscountz, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($comm1herez, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty3z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty6z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty9z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfzeroz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfregz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtystz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($commm2z, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($grossz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos3z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos6z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos9z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfzeroz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfregz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmosz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($netpz, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
            case '5': //consignment
            case '6': //outright
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col($totalsldz, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($costlangs, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($totaldiscountz, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col($comm1herez, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty3z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty6z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqty9z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfzeroz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtyfregz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldqtystz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($commm2z, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($grossz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos3z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos6z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmos9z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfzeroz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldfregz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($tldmosz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($netpz, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                break;
        }


        //////grandtotal


        // if ($gtotalsld < 0) { //ito yung negative
        //     $gtotalsldz = '(' . number_format(abs($gtotalsld), 2) . ')';
        // } else {
        //     $gtotalsldz = $gtotalsld == 0 ? '-' : number_format($gtotalsld, 2);
        // }
        // if ($gtotaldiscount < 0) { //ito yung negative
        //     $gtotaldiscountz = '(' . number_format(abs($totaldiscount), 2) . ')';
        // } else {
        //     $gtotaldiscountz = $totaldiscount == 0 ? '-' : number_format($totaldiscount, 2);
        // }
        // if ($gcomm1here < 0) { //ito yung negative
        //     $gcomm1herez = '(' . number_format(abs($comm1here), 2) . ')';
        // } else {
        //     $gcomm1herez = $gcomm1here == 0 ? '-' : number_format($gcomm1here, 2);
        // }
        // if ($gtldqty3 < 0) { //ito yung negative
        //     $gtldqty3z = '(' . number_format(abs($gtldqty3), 2) . ')';
        // } else {
        //     $gtldqty3z = $gtldqty3 == 0 ? '-' : number_format($gtldqty3, 2);
        // }
        // if ($gtldqty6 < 0) { //ito yung negative
        //     $gtldqty6z = '(' . number_format(abs($gtldqty6), 2) . ')';
        // } else {
        //     $gtldqty6z = $gtldqty6 == 0 ? '-' : number_format($gtldqty6, 2);
        // }
        // if ($gtldqty9 < 0) { //ito yung negative
        //     $gtldqty9z = '(' . number_format(abs($gtldqty9), 2) . ')';
        // } else {
        //     $gtldqty9z = $gtldqty9 == 0 ? '-' : number_format($gtldqty9, 2);
        // }
        // if ($gtldqtyfzero < 0) { //ito yung negative
        //     $gtldqtyfzeroz = '(' . number_format(abs($gtldqtyfzero), 2) . ')';
        // } else {
        //     $gtldqtyfzeroz = $gtldqtyfzero == 0 ? '-' : number_format($gtldqtyfzero, 2);
        // }
        // if ($gtldqtyfreg < 0) { //ito yung negative
        //     $gtldqtyfregz = '(' . number_format(abs($gtldqtyfreg), 2) . ')';
        // } else {
        //     $gtldqtyfregz = $gtldqtyfreg == 0 ? '-' : number_format($gtldqtyfreg, 2);
        // }
        // if ($gtldqtyst < 0) { //ito yung negative
        //     $gtldqtystz = '(' . number_format(abs($gtldqtyst), 2) . ')';
        // } else {
        //     $gtldqtystz = $gtldqtyst == 0 ? '-' : number_format($gtldqtyst, 2);
        // }
        // if ($gcommm2 < 0) { //ito yung negative
        //     $gcommm2z = '(' . number_format(abs($gcommm2), 2) . ')';
        // } else {
        //     $gcommm2z = $gcommm2 == 0 ? '-' : number_format($gcommm2, 2);
        // }

        // if ($gtlssrp < 0) { //ito yung negative
        //     $gtlssrpz = '(' . number_format(abs($gtlssrp), 2) . ')';
        // } else {
        //     $gtlssrpz = $tlssrp == 0 ? '-' : number_format($gtlssrp, 2);
        // }
        // if ($gtl2srp < 0) { //ito yung negative
        //     $gtl2srpz = '(' . number_format(abs($gtl2srp), 2) . ')';
        // } else {
        //     $gtl2srpz = $gtl2srp == 0 ? '-' : number_format($gtl2srp, 2);
        // }
        // if ($ggross < 0) { //ito yung negative
        //     $ggrossz = '(' . number_format(abs($ggross), 2) . ')';
        // } else {
        //     $ggrossz = $ggross == 0 ? '-' : number_format($ggross, 2);
        // }
        // if ($gtldmos3 < 0) { //ito yung negative
        //     $gtldmos3z = '(' . number_format(abs($gtldmos3), 2) . ')';
        // } else {
        //     $gtldmos3z = $gtldmos3 == 0 ? '-' : number_format($gtldmos3, 2);
        // }
        // if ($gtldmos6 < 0) { //ito yung negative
        //     $gtldmos6z = '(' . number_format(abs($gtldmos6), 2) . ')';
        // } else {
        //     $gtldmos6z = $gtldmos6 == 0 ? '-' : number_format($gtldmos6, 2);
        // }
        // if ($gtldmos9 < 0) { //ito yung negative
        //     $gtldmos9z = '(' . number_format(abs($gtldmos9), 2) . ')';
        // } else {
        //     $gtldmos9z = $gtldmos9 == 0 ? '-' : number_format($gtldmos9, 2);
        // }
        // if ($gtldfzero < 0) { //ito yung negative
        //     $gtldfzeroz = '(' . number_format(abs($gtldfzero), 2) . ')';
        // } else {
        //     $gtldfzeroz = $gtldfzero == 0 ? '-' : number_format($gtldfzero, 2);
        // }
        // if ($gtldfreg < 0) { //ito yung negative
        //     $gtldfregz = '(' . number_format(abs($gtldfreg), 2) . ')';
        // } else {
        //     $gtldfregz = $gtldfreg == 0 ? '-' : number_format($gtldfreg, 2);
        // }
        // if ($gtldmos < 0) { //ito yung negative
        //     $gtldmosz = '(' . number_format(abs($gtldmos), 2) . ')';
        // } else {
        //     $gtldmosz = $gtldmos == 0 ? '-' : number_format($gtldmos, 2);
        // }
        // if ($gnetp < 0) { //ito yung negative
        //     $gnetpz = '(' . number_format(abs($gnetp), 2) . ')';
        // } else {
        //     $gnetpz = $gnetp == 0 ? '-' : number_format($gnetp, 2);
        // }

        // if ($gcostlang < 0) { //ito yung negative
        //     $gcostlangs = '(' . number_format(abs($gcostlang), 2) . ')';
        // } else {
        //     $gcostlangs = $gcostlang == 0 ? '-' : number_format($gcostlang, 2);
        // }


        //     switch ($reptype) {
        //     case '4': //concession
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('GTOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
        //         $str .= $this->reporter->col($gtotalsldz, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtotaldiscountz, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcomm1herez, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty3z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty6z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty9z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfzeroz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfregz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtystz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcommm2z, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->endrow();

        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($ggrossz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos3z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos6z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos9z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfzeroz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfregz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmosz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gnetpz, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //         break;
        //     case '5': //consignment
        //     case '6': //outright
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '68', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('GTOTAL', '100', '', false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
        //         $str .= $this->reporter->col($gtotalsldz, '51', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcostlangs, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtotaldiscountz, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col($comm1herez, '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty3z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty6z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty9z, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfzeroz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfregz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtystz, '50', '', false, $border, 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('',  '51', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcommm2z, '50', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '70', '', false, $border, 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->endrow();

        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '68', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtl2srpz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col('', '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($ggrossz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos3z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos6z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos9z, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfzeroz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfregz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmosz, '51', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col('', '50', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gnetpz, '70', '', false, '1px dotted', 'T', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //         break;
        // }




        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
        $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</div>';
        $str .= '<div style="position: relative;margin:-100px 0 10px 0;">';

        $str .= "<div style='position:absolute; bottom:50px'>";
        $sign = URL::to('/images/homeworks/checked.png');
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('<img src ="' . $sign . '" alt="test" width="60px" height ="50px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("Page $page_count of  " . $final, '100', '', false, '', 'R', '', $font, $font_size);
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';

        $str .= $this->reporter->endreport();
        $str .= '</div>';
        return $str;
    }


    private function vendor_with_item_concess_day_header($config, $supcode, $supplier)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $clientid   = $config['params']['dataparams']['clientid'];
        $clientname = $config['params']['dataparams']['dclientname'];
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        // $letter = $this->reporter->letterhead($center, $username, $config);
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '7';
        $itemstatus = $config['params']['dataparams']['itemstatus'];

        if ($itemstatus == '(0)') {
            $itemstatus = 'ACTIVE ITEMS';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'ACTIVE ITEMS';
        } else {
            $itemstatus = 'ALL ITEMS';
        }


        // $client = $config['params']['dataparams']['client'];
        // $clientname = $config['params']['dataparams']['clientname'];


        // if ($client != "") {
        //     $clientname = $config['params']['dataparams']['clientname'];
        // } else {
        //     $clientname = 'ALL SUPPLIER';
        // }

        $supp = "";
        if ($clientid != 0) {
            $supp = $clientname;
        } else {
            $supp = $supcode . '~' . $supplier;
        }

        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $reptype = $config['params']['dataparams']['reporttype'];
        $label = "";
        switch ($reptype) {
            case '7':
                $label = "Concess/day";
                break;
            case '8':
                $label = "Consign/day";
                break;
            case '':
                $label = "Outright/day";
                break;
        }

        $str = '';
        $layoutsize = '1200';



        $str .= $this->reporter->begintable($layoutsize);
        // $letter= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name . ' ' . strtoupper($headerdata[0]->address), '1000', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($itemstatus . ' - ITEM PER VENDOR SUMMARY REPORT PER DAY-' . $label, '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $supp, '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM:' . $start, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '950', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO:' . $end, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '360', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('CHARGES', '720', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '120', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        switch ($reptype) {
            case '7': //concession
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Date', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Sold', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                // $str .= $this->reporter->col('Cost', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Disc', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Srp', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                // $str .= $this->reporter->col('TTL COST', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Com 1', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Gross Payable', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                // $str .= $this->reporter->col('PERCENTAGE', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('3 Mos', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('6 Mos', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('9 Mos/12 Mos', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Finance 0%', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Finance Regular', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Debit/Straight', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Com 2', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Net Payable', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                break;
            case '8':
            case '9':
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Date', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Sold', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Cost', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Disc', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('TTL Srp', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                // $str .= $this->reporter->col('TTL COST', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                // $str .= $this->reporter->col('Com 1', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Gross Payable', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                // $str .= $this->reporter->col('PERCENTAGE', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('3 Mos', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('6 Mos', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('9 Mos/12 Mos', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Finance 0%', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Finance Regular', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Qty', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Debit/Straight', '60', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Com 2', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Net Payable', '60', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                break;
        }
        // $str .= $this->reporter->endtable();
        return $str;
    }


    public function vendor_with_item_concess_day($config)
    {
        $result = $this->concessday($config);

        $border = '1px solid';
        $font = 'calibri';
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reptype = $config['params']['dataparams']['reporttype'];
        $font_size = '7';
        $page = 31;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1200';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $difference = $this->diff($start, $end);

        $dates = [];
        for ($i = 0; $i <= $difference; $i++) {
            $dates[] = date("Y-m-d", strtotime("+$i days", strtotime($start)));
        }


        $groupedData = [];
        foreach ($result as $data) {
            $groupedData[$data->suppcode][$data->dateid][] = $data;
        }

        // Grand totals
        // $gtotalsld = $gtotaldiscount = $gtlsrp = $gtlcost = $gcomm1here = $ggross = $gcommm2 = $gtotalcomap = $gnetp = 0;
        // $gtldqty3 = $gtldmos3 = $gtldqty6 = $gtldmos6 = $gtldqty9 = $gtldmos9 = 0;
        // $gtldqtyfzero = $gtldfzero = $gtldqtyfreg = $gtldfreg = $gtldqtyst = $gtldmos = $gcost = 0;

        $sname = '';
        $count = 0;

        foreach ($groupedData as $supplier => $dateGroup) {
            $stotalsold = $stotaldisc = $stotalsrp = $stotalcost = $stotalcom1 = 0;
            $sgrosspayable = $sgrossap2 = $snetap = $sdext = $scomap = 0;
            $sqty3 = $sqty6 = $sqty9 = $sqtyst = 0;
            $smos3 = $smos6 = $smos9 = $smost = $scost = 0;
            $sfinanceregularqty = $sfinanceregular = 0;
            $sfinancezeroqty = $sfinancezero = 0;

            // if ($count > $page || ($sname != '' && $sname != $supplier)) {
            //     $str .= $this->reporter->endtable();
            //     $str .= '</div>';
            //     $str .= $this->reporter->page_break();
            //     $str .= '<div style="position: relative;margin:0 0 38px 0;">';
            //     $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
            //     // $str .= $this->vendor_with_item_concess_day_header($config, $data->suppcode, $data->suppliername);
            //     $sname = '';
            //     $count = 0;
            // }

            if ($sname != '' && $sname != $supplier) {
                // End the current table and div
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
                $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '</div>';
                // New page
                $str .= $this->reporter->page_break();
                // $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                // Add footer
                $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                // Reset to trigger new header on next row
                $sname = '';
                $count = 0;
            }



            foreach ($dates as $date) {
                if (empty($dateGroup[$date])) continue;

                if ($count >= $page) {
                    // End the current table and div
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    // $str .= $this->reporter->col('', '1200', '',  false,  '', '', '',  $font,  $font_size,   '', '',  '', '',  0,  'style="padding-bottom:1000px;');
                    $str .= $this->reporter->col("<div style='padding-bottom:100px;'>" . "</div>", null, null, false, $border, '', 'L', $font, $font_size, 'R', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= '</div>';
                    // New page
                    $str .= $this->reporter->page_break();
                    // $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                    // Add footer
                    $str .= $this->footer($config, $layoutsize, $font, $font_size, $border);
                    // Reset $sname to trigger header printing for the same supplier
                    $sname = '';
                    $count = 0;
                }

                $totalsold = $totaldisc = $totalsrp = $totalcost = $totalcom1 = 0;
                $grosspayable = $grossap2 = $netap = $dext = $comap = $cost = 0;
                $qty3 = $qty6 = $qty9 = $qtyst = 0;
                $mos3 = $mos6 = $mos9 = $most = 0;
                $financeregularqty = $financeregular = 0;
                $financezeroqty = $financezero = 0;


                foreach ($dateGroup[$date] as $data) {
                    $totalsold += $data->totalsold;
                    $totaldisc += $data->disc;
                    $totalsrp += $data->ext;
                    $totalcost += $data->totalcost;
                    $grossap2 += $data->grossap2;
                    $dext += $data->ext;
                    $comap += $data->totalcost;
                    $cost += $data->cost;

                    // CHARGES 
                    $raw = $data->terminalid;
                    $parts = explode('~', $raw);
                    $firstpart = $parts[0];
                    $banktype = $data->banktype;
                    $bankrate = $data->bankrate;
                    $cardcharge = $data->cardcharge;

                    switch ($firstpart) {
                        case 'CCASHALOALDI':
                        case 'CHOMECREDIT':
                        case 'CHOMECREDIT0':
                        case 'CCASHALO':
                        case 'DCASHALOALDI':
                        case 'DHOMECREDIT':
                        case 'DHOMECREDIT0':
                        case 'DCASHALO':
                        case 'CASHALOALDI':
                        case 'HOMECREDIT':
                        case 'HOMECREDIT0':
                        case 'CASHALO':
                            // case 'CAUGBGCASH':
                            if ($bankrate != 0) {
                                $financeregularqty += $data->totalsold;
                                $financeregular += $cardcharge;
                            } else {
                                $financezeroqty += $data->totalsold;
                                $financezero += $cardcharge;
                            }
                            break;
                        default:
                            if (!empty($banktype) && $cardcharge != 0) {
                                switch (strtoupper(str_replace(' ', '', $data->banktype))) {
                                    case '3MONS':
                                    case '3MOS':    
                                        $qty3 += $data->totalsold;
                                        $mos3 += $cardcharge;
                                        break;
                                    case '6MONS':
                                    case '6MOS':    
                                        $qty6 += $data->totalsold;
                                        $mos6 += $cardcharge;
                                        break;
                                    case '9MONS':
                                    case '12MONS':
                                    case '9MOS':
                                    case '12MOS':
                                        $qty9 += $data->totalsold;
                                        $mos9 += $cardcharge;
                                        break;
                                    case 'STRAIGHT':
                                    case 'DEBIT':
                                    case 'AUBGCASH':
                                    case 'DEBIT/BANCNET':     
                                        $qtyst += $data->totalsold;
                                        $most += $cardcharge;
                                        break;
                                }
                            }
                            break;
                    }

                    $comm1 = $data->comm1;
                    $totalcom1 += $comm1;
                }

                $display_mos3 = ($mos3 != 0) ? $mos3 : 0;
                $display_mos6 = ($mos6 != 0) ? $mos6 : 0;
                $display_mos9 = ($mos9 != 0) ? $mos9 : 0;
                $display_mos = ($most != 0) ? $most : 0;

                $disp_qty3  = ($qty3 != 0) ? $qty3 : 0;
                $disp_qty6  = ($qty6 != 0) ? $qty6 : 0;
                $disp_qty9  = ($qty9 != 0) ? $qty9 : 0;
                $disp_qtyst = ($qtyst != 0) ? $qtyst : 0;



                $discount = $totaldisc;
                $display_discount = $discount != 0 ? $discount : 0;
                $costs = $cost;
                // $dcosts = $costs != 0 ? $costs : 0;

                // var_dump($costs);

                $charges = $grossap2 + $mos3 + $mos6 + $mos9 + $most + $financeregular + $financezero;
                $netpayable = $comap + $charges;
                $com1here = $totalcom1;
                $tlsrp      = ($dext != 0) ? floatval($dext) : 0;


                if ($sname != $supplier || $sname == '') {
                    $str .= '<div style="position: relative;margin:0 0 38px 0;">';
                    $str .= $this->vendor_with_item_concess_day_header($config, $data->suppcode, $data->suppliername);
                    $sname = $supplier;
                }

                if ($totalsold < 0) { //ito yung negative
                    $totalsoldc = '(' . number_format(abs($totalsold), 2) . ')';
                } else {
                    $totalsoldc = $totalsold == 0 ? '-' : number_format($totalsold, 2);
                }

                if ($display_discount < 0) { //ito yung negative
                    $display_discount = '(' . number_format(abs($display_discount), 2) . ')';
                } else {
                    $display_discount = $display_discount == 0 ? '-' : number_format($display_discount, 2);
                }
                if ($tlsrp < 0) { //ito yung negative
                    $tlsrpc = '(' . number_format(abs($tlsrp), 2) . ')';
                } else {
                    $tlsrpc = $tlsrp == 0 ? '-' : number_format($tlsrp, 2);
                }
                if ($com1here < 0) { //ito yung negative
                    $com1herec = '(' . number_format(abs($com1here), 2) . ')';
                } else {
                    $com1herec = $com1here == 0 ? '-' : number_format($com1here, 2);
                }
                if ($comap < 0) { //ito yung negative
                    $comapc = '(' . number_format(abs($comap), 2) . ')';
                } else {
                    $comapc = $comap == 0 ? '-' : number_format($comap, 2);
                }
                if ($disp_qty3 < 0) { //ito yung negative
                    $disp_qty3 = '(' . number_format(abs($disp_qty3), 2) . ')';
                } else {
                    $disp_qty3 = $disp_qty3 == 0 ? '-' : number_format($disp_qty3, 2);
                }
                if ($display_mos3 < 0) { //ito yung negative
                    $display_mos3 = '(' . number_format(abs($display_mos3), 2) . ')';
                } else {
                    $display_mos3 = $display_mos3 == 0 ? '-' : number_format($display_mos3, 2);
                }
                if ($disp_qty6 < 0) { //ito yung negative
                    $disp_qty6 = '(' . number_format(abs($disp_qty6), 2) . ')';
                } else {
                    $disp_qty6 = $disp_qty6 == 0 ? '-' : number_format($disp_qty6, 2);
                }
                if ($display_mos6 < 0) { //ito yung negative
                    $display_mos6 = '(' . number_format(abs($display_mos6), 2) . ')';
                } else {
                    $display_mos6 = $display_mos6 == 0 ? '-' : number_format($display_mos6, 2);
                }
                if ($disp_qty9 < 0) { //ito yung negative
                    $disp_qty9 = '(' . number_format(abs($disp_qty9), 2) . ')';
                } else {
                    $disp_qty9 = $disp_qty9 == 0 ? '-' : number_format($disp_qty9, 2);
                }
                if ($display_mos9 < 0) { //ito yung negative
                    $display_mos9 = '(' . number_format(abs($display_mos9), 2) . ')';
                } else {
                    $display_mos9 = $display_mos9 == 0 ? '-' : number_format($display_mos9, 2);
                }
                if ($financezeroqty < 0) { //ito yung negative
                    $financezeroqtyc = '(' . number_format(abs($financezeroqty), 2) . ')';
                } else {
                    $financezeroqtyc = $financezeroqty == 0 ? '-' : number_format($financezeroqty, 2);
                }
                if ($financezero < 0) { //ito yung negative
                    $financezeroc = '(' . number_format(abs($financezero), 2) . ')';
                } else {
                    $financezeroc = $financezero == 0 ? '-' : number_format($financezero, 2);
                }
                if ($financeregularqty < 0) { //ito yung negative
                    $financeregularqtyc = '(' . number_format(abs($financeregularqty), 2) . ')';
                } else {
                    $financeregularqtyc = $financeregularqty == 0 ? '-' : number_format($financeregularqty, 2);
                }
                if ($financeregular < 0) { //ito yung negative
                    $financeregularc = '(' . number_format(abs($financeregular), 2) . ')';
                } else {
                    $financeregularc = $financeregular == 0 ? '-' : number_format($financeregular, 2);
                }
                if ($disp_qtyst < 0) { //ito yung negative
                    $disp_qtyst = '(' . number_format(abs($disp_qtyst), 2) . ')';
                } else {
                    $disp_qtyst = $disp_qtyst == 0 ? '-' : number_format($disp_qtyst, 2);
                }
                if ($display_mos < 0) { //ito yung negative
                    $display_mos = '(' . number_format(abs($display_mos), 2) . ')';
                } else {
                    $display_mos = $display_mos == 0 ? '-' : number_format($display_mos, 2);
                }
                if ($grossap2 < 0) { //ito yung negative
                    $grossap2d = '(' . number_format(abs($grossap2), 2) . ')';
                } else {
                    $grossap2d = $grossap2 == 0 ? '-' : number_format($grossap2, 2);
                }
                if ($netpayable < 0) { //ito yung negative
                    $netpayabled = '(' . number_format(abs($netpayable), 2) . ')';
                } else {
                    $netpayabled = $netpayable == 0 ? '-' : number_format($netpayable, 2);
                }



                switch ($reptype) {

                    case '7': //concession 

                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->addline();
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col($date, '60', '', false, $border, '', 'C', $font, $font_size, '', '', ''); //$date
                        $str .= $this->reporter->col($totalsoldc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_discount, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tlsrpc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');

                        $str .= $this->reporter->col($com1herec, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($comapc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');

                        $str .= $this->reporter->col($disp_qty3, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos3, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($disp_qty6, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos6, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($disp_qty9, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos9, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financezeroqtyc, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financezeroc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financeregularqtyc, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financeregularc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($disp_qtyst, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($grossap2d, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($netpayabled, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');

                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        break;

                    case '8':
                    case '9':
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->addline();
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col($date, '60', '', false, $border, '', 'C', $font, $font_size, '', '', ''); //$date
                        $str .= $this->reporter->col($totalsoldc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');

                        $str .= $this->reporter->col($cost < 0 ?  '(' . number_format(abs($costs), 2) . ')' : ($cost != 0 ? number_format($costs, 2) : '-'), '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_discount, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($tlsrpc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');

                        // $str .= $this->reporter->col($com1herec, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($comapc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');

                        $str .= $this->reporter->col($disp_qty3, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos3, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($disp_qty6, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos6, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($disp_qty9, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos9, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financezeroqtyc, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financezeroc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financeregularqtyc, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($financeregularc, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($disp_qtyst, '60', '', false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($display_mos, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($grossap2d, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($netpayabled, '60', '', false, $border, '', 'R', $font, $font_size, '', '', '');

                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        break;
                }


                //  sub total

                $stotalsold += $totalsold;
                $stotaldisc += $discount;
                $stotalsrp += $tlsrp;
                $stotalcost += $totalcost;
                $stotalcom1 += $com1here;
                $sgrosspayable += $grosspayable;
                // $percenthere += $percentage;
                $sgrossap2 += $grossap2;
                $snetap += $netpayable;

                $sqty3 += $qty3;
                $smos3 += $mos3;
                $sqty6 += $qty6;
                $smos6 += $mos6;
                $sqty9 += $qty9;
                $smos9 += $mos9;
                $sfinancezeroqty += $financezeroqty;
                $sfinancezero += $financezero;
                $sfinanceregularqty += $financeregularqty;
                $sfinanceregular += $financeregular;
                $sqtyst += $qtyst;
                $smost += $most;
                $scost += $cost;

                $scomap += $comap;
                $count++;
            }

            if ($stotalsold < 0) { //ito yung negative
                $stotalsoldd = '(' . number_format(abs($stotalsold), 2) . ')';
            } else {
                $stotalsoldd = $stotalsold == 0 ? '-' : number_format($stotalsold, 2);
            }
            if ($stotaldisc < 0) { //ito yung negative
                $stotaldiscd = '(' . number_format(abs($stotaldisc), 2) . ')';
            } else {
                $stotaldiscd = $stotaldisc == 0 ? '-' : number_format($stotaldisc, 2);
            }
            if ($stotalsrp < 0) { //ito yung negative
                $stotalsrpd = '(' . number_format(abs($stotalsrp), 2) . ')';
            } else {
                $stotalsrpd = $stotalsrp == 0 ? '-' : number_format($stotalsrp, 2);
            }
            if ($stotalcom1 < 0) { //ito yung negative
                $stotalcom1d = '(' . number_format(abs($stotalcom1), 2) . ')';
            } else {
                $stotalcom1d = $stotalcom1 == 0 ? '-' : number_format($stotalcom1, 2);
            }
            if ($scomap < 0) { //ito yung negative
                $scomapd = '(' . number_format(abs($scomap), 2) . ')';
            } else {
                $scomapd = $scomap == 0 ? '-' : number_format($scomap, 2);
            }
            if ($sqty3 < 0) { //ito yung negative
                $sqty3d = '(' . number_format(abs($sqty3), 2) . ')';
            } else {
                $sqty3d = $sqty3 == 0 ? '-' : number_format($sqty3, 2);
            }
            if ($smos3 < 0) { //ito yung negative
                $smos3d = '(' . number_format(abs($smos3), 2) . ')';
            } else {
                $smos3d = $smos3 == 0 ? '-' : number_format($smos3, 2);
            }
            if ($sqty6 < 0) { //ito yung negative
                $sqty6d = '(' . number_format(abs($sqty6), 2) . ')';
            } else {
                $sqty6d = $sqty6 == 0 ? '-' : number_format($sqty6, 2);
            }
            if ($smos6 < 0) { //ito yung negative
                $smos6d = '(' . number_format(abs($smos6), 2) . ')';
            } else {
                $smos6d = $smos6 == 0 ? '-' : number_format($smos6, 2);
            }
            if ($sqty9 < 0) { //ito yung negative
                $sqty9d = '(' . number_format(abs($sqty9), 2) . ')';
            } else {
                $sqty9d = $sqty9 == 0 ? '-' : number_format($sqty9, 2);
            }
            if ($smos9 < 0) { //ito yung negative
                $smos9d = '(' . number_format(abs($smos9), 2) . ')';
            } else {
                $smos9d = $smos9 == 0 ? '-' : number_format($smos9, 2);
            }
            if ($sfinancezeroqty < 0) { //ito yung negative
                $sfinancezeroqtyd = '(' . number_format(abs($sfinancezeroqty), 2) . ')';
            } else {
                $sfinancezeroqtyd = $sfinancezeroqty == 0 ? '-' : number_format($sfinancezeroqty, 2);
            }
            if ($sfinancezero < 0) { //ito yung negative
                $sfinancezerod = '(' . number_format(abs($sfinancezero), 2) . ')';
            } else {
                $sfinancezerod = $sfinancezero == 0 ? '-' : number_format($sfinancezero, 2);
            }
            if ($sfinanceregularqty < 0) { //ito yung negative
                $sfinanceregularqtyd = '(' . number_format(abs($sfinanceregularqty), 2) . ')';
            } else {
                $sfinanceregularqtyd = $sfinanceregularqty == 0 ? '-' : number_format($sfinanceregularqty, 2);
            }
            if ($sfinanceregular < 0) { //ito yung negative
                $sfinanceregulard = '(' . number_format(abs($sfinanceregular), 2) . ')';
            } else {
                $sfinanceregulard = $sfinanceregular == 0 ? '-' : number_format($sfinanceregular, 2);
            }
            if ($sqtyst < 0) { //ito yung negative
                $sqtystd = '(' . number_format(abs($sqtyst), 2) . ')';
            } else {
                $sqtystd = $sqtyst == 0 ? '-' : number_format($sqtyst, 2);
            }
            if ($smost < 0) { //ito yung negative
                $smostd = '(' . number_format(abs($smost), 2) . ')';
            } else {
                $smostd = $smost == 0 ? '-' : number_format($smost, 2);
            }
            if ($sgrossap2 < 0) { //ito yung negative
                $sgrossap2d = '(' . number_format(abs($sgrossap2), 2) . ')';
            } else {
                $sgrossap2d = $sgrossap2 == 0 ? '-' : number_format($sgrossap2, 2);
            }
            if ($snetap < 0) { //ito yung negative
                $snetapd = '(' . number_format(abs($snetap), 2) . ')';
            } else {
                $snetapd = $snetap == 0 ? '-' : number_format($snetap, 2);
            }

            $scosted = $scost;

            switch ($reptype) {
                case '7': //concesion

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('TOTAL', '60', '', false, '1px dotted', 'TB', 'L', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col($stotalsoldd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    // $str .= $this->reporter->col($scost < 0 ?  '(' . number_format(abs($scosted), 2) . ')' : ($cost != 0 ? number_format($scosted, 2) : '-'), '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($stotaldiscd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($stotalsrpd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    // $str .= $this->reporter->col(($tlcost == 0) ? '-' : number_format($tlcost, 2), '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($stotalcom1d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($scomapd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    // $str .= $this->reporter->col(($percenthere == 0) ? '-' : number_format($percenthere, 2), '50', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqty3d, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smos3d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqty6d, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smos6d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqty9d, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smos9d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinancezeroqtyd, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinancezerod, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinanceregularqtyd, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinanceregulard, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqtystd, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smostd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sgrossap2d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($snetapd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    break;
                case '8':
                case '9':
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('TOTAL', '60', '', false, '1px dotted', 'TB', 'L', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col($stotalsoldd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($scost < 0 ?  '(' . number_format(abs($scosted), 2) . ')' : ($cost != 0 ? number_format($scosted, 2) : '-'), '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($stotaldiscd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($stotalsrpd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    // $str .= $this->reporter->col(($tlcost == 0) ? '-' : number_format($tlcost, 2), '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    // $str .= $this->reporter->col($stotalcom1d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($scomapd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    // $str .= $this->reporter->col(($percenthere == 0) ? '-' : number_format($percenthere, 2), '50', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqty3d, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smos3d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqty6d, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smos6d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqty9d, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smos9d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinancezeroqtyd, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinancezerod, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinanceregularqtyd, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sfinanceregulard, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sqtystd, '60', '', false, '1px dotted', 'TB', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($smostd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($sgrossap2d, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($snetapd, '60', '', false, '1px dotted', 'TB', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    break;
            }


            // grand total
            // $gtotalsld += $stotalsold;
            // $gtotaldiscount += $stotaldisc;
            // $gtlsrp += $stotalsrp;
            // $gtlcost += $stotalcost;
            // $gcomm1here += $stotalcom1;
            // $ggross += $sgrosspayable;
            // $gcommm2 += $sgrossap2;
            // $gtotalcomap += $scomap;
            // $gnetp += $snetap;
            // $gtldqty3 += $sqty3;
            // $gtldmos3 += $smos3;
            // $gtldqty6 += $sqty6;
            // $gtldmos6 += $smos6;
            // $gtldqty9 += $sqty9;
            // $gtldmos9 += $smos9;
            // $gtldqtyfzero += $sfinancezeroqty;
            // $gtldfzero += $sfinancezero;
            // $gtldqtyfreg += $sfinanceregularqty;
            // $gtldfreg += $sfinanceregular;
            // $gtldqtyst += $sqtyst;
            // $gtldmos += $smost;
            // $gcost += $scost;
        }

        // if ($gtotalsld < 0) { //ito yung negative
        //     $gtotalsld = '(' . number_format(abs($gtotalsld), 2) . ')';
        // } else {
        //     $gtotalsld = $gtotalsld == 0 ? '-' : number_format($gtotalsld, 2);
        // }
        // if ($gtotaldiscount < 0) { //ito yung negative
        //     $gtotaldiscount = '(' . number_format(abs($gtotaldiscount), 2) . ')';
        // } else {
        //     $gtotaldiscount = $gtotaldiscount == 0 ? '-' : number_format($gtotaldiscount, 2);
        // }
        // if ($gtlsrp < 0) { //ito yung negative
        //     $gtlsrp = '(' . number_format(abs($gtlsrp), 2) . ')';
        // } else {
        //     $gtlsrp = $gtlsrp == 0 ? '-' : number_format($gtlsrp, 2);
        // }
        // if ($gcomm1here < 0) { //ito yung negative
        //     $gcomm1here = '(' . number_format(abs($gcomm1here), 2) . ')';
        // } else {
        //     $gcomm1here = $gcomm1here == 0 ? '-' : number_format($gcomm1here, 2);
        // }
        // if ($gtotalcomap < 0) { //ito yung negative
        //     $gtotalcomap = '(' . number_format(abs($gtotalcomap), 2) . ')';
        // } else {
        //     $gtotalcomap = $gtotalcomap == 0 ? '-' : number_format($gtotalcomap, 2);
        // }
        // if ($gtldqty3 < 0) { //ito yung negative
        //     $gtldqty3 = '(' . number_format(abs($gtldqty3), 2) . ')';
        // } else {
        //     $gtldqty3 = $gtldqty3 == 0 ? '-' : number_format($gtldqty3, 2);
        // }
        // if ($gtldmos3 < 0) { //ito yung negative
        //     $gtldmos3 = '(' . number_format(abs($gtldmos3), 2) . ')';
        // } else {
        //     $gtldmos3 = $gtldmos3 == 0 ? '-' : number_format($gtldmos3, 2);
        // }
        // if ($gtldqty6 < 0) { //ito yung negative
        //     $gtldqty6 = '(' . number_format(abs($gtldqty6), 2) . ')';
        // } else {
        //     $gtldqty6 = $gtldqty6 == 0 ? '-' : number_format($gtldqty6, 2);
        // }
        // if ($gtldmos6 < 0) { //ito yung negative
        //     $gtldmos6 = '(' . number_format(abs($gtldmos6), 2) . ')';
        // } else {
        //     $gtldmos6 = $gtldmos6 == 0 ? '-' : number_format($gtldmos6, 2);
        // }
        // if ($gtldqty9 < 0) { //ito yung negative
        //     $gtldqty9 = '(' . number_format(abs($gtldqty9), 2) . ')';
        // } else {
        //     $gtldqty9 = $gtldqty9 == 0 ? '-' : number_format($gtldqty9, 2);
        // }
        // if ($gtldmos9 < 0) { //ito yung negative
        //     $gtldmos9 = '(' . number_format(abs($gtldmos9), 2) . ')';
        // } else {
        //     $gtldmos9 = $gtldmos9 == 0 ? '-' : number_format($gtldmos9, 2);
        // }
        // if ($gtldqtyfzero < 0) { //ito yung negative
        //     $gtldqtyfzero = '(' . number_format(abs($gtldqtyfzero), 2) . ')';
        // } else {
        //     $gtldqtyfzero = $gtldqtyfzero == 0 ? '-' : number_format($gtldqtyfzero, 2);
        // }
        // if ($gtldfzero < 0) { //ito yung negative
        //     $gtldfzero = '(' . number_format(abs($gtldfzero), 2) . ')';
        // } else {
        //     $gtldfzero = $gtldfzero == 0 ? '-' : number_format($gtldfzero, 2);
        // }
        // if ($gtldqtyfreg < 0) { //ito yung negative
        //     $gtldqtyfreg = '(' . number_format(abs($gtldqtyfreg), 2) . ')';
        // } else {
        //     $gtldqtyfreg = $gtldqtyfreg == 0 ? '-' : number_format($gtldqtyfreg, 2);
        // }
        // if ($gtldfreg < 0) { //ito yung negative
        //     $gtldfreg = '(' . number_format(abs($gtldfreg), 2) . ')';
        // } else {
        //     $gtldfreg = $gtldfreg == 0 ? '-' : number_format($gtldfreg, 2);
        // }
        // if ($gtldqtyst < 0) { //ito yung negative
        //     $gtldqtyst = '(' . number_format(abs($gtldqtyst), 2) . ')';
        // } else {
        //     $gtldqtyst = $gtldqtyst == 0 ? '-' : number_format($gtldqtyst, 2);
        // }
        // if ($gtldmos < 0) { //ito yung negative
        //     $gtldmos = '(' . number_format(abs($gtldmos), 2) . ')';
        // } else {
        //     $gtldmos = $gtldmos == 0 ? '-' : number_format($gtldmos, 2);
        // }
        // if ($gcommm2 < 0) { //ito yung negative
        //     $gcommm2 = '(' . number_format(abs($gcommm2), 2) . ')';
        // } else {
        //     $gcommm2 = $gcommm2 == 0 ? '-' : number_format($gcommm2, 2);
        // }
        // if ($gnetp < 0) { //ito yung negative
        //     $gnetp = '(' . number_format(abs($gnetp), 2) . ')';
        // } else {
        //     $gnetp = $gnetp == 0 ? '-' : number_format($gnetp, 2);
        // }

        // $gcosted = $gcost;

        // switch ($reptype) {
        //     case '7': //concesion

        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('GRANDTOTAL', '60', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        //         $str .= $this->reporter->col($gtotalsld, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col($gcost < 0 ?  '(' . number_format(abs($gcosted), 2) . ')' : ($cost != 0 ? number_format($gcosted, 2) : '-'), '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtotaldiscount, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtlsrp, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col(($tlcost == 0) ? '-' : number_format($tlcost, 2), '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcomm1here, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtotalcomap, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col(($percenthere == 0) ? '-' : number_format($percenthere, 2), '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty3, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos3, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty6, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos6, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty9, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos9, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfzero, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfzero, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfreg, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfreg, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyst, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcommm2, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gnetp, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //         break;
        //     case '8':
        //     case '9':
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('GRANDTOTAL', '60', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        //         $str .= $this->reporter->col($gtotalsld, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcost < 0 ?  '(' . number_format(abs($gcosted), 2) . ')' : ($cost != 0 ? number_format($gcosted, 2) : '-'), '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtotaldiscount, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtlsrp, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col(($tlcost == 0) ? '-' : number_format($tlcost, 2), '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col($gcomm1here, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtotalcomap, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         // $str .= $this->reporter->col(($percenthere == 0) ? '-' : number_format($percenthere, 2), '50', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty3, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos3, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty6, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos6, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqty9, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos9, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfzero, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfzero, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyfreg, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldfreg, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldqtyst, '60', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gtldmos, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gcommm2, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->col($gnetp, '60', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //         break;
        // }

        // $str .= '</div>';
        $str .= '</br> </br> </br>';
        // $str .= '<div style="position: relative;margin:50px 0 10px 0;">';
        $str .= "<div style='position:absolute; bottom:60px'>";
        $sign = URL::to('/images/homeworks/checked.png');
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="70px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '5', 'B', '', '1px');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Catherine Dela Cruz', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Marieta Jose', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Jonathan Go', '100', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved by', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '350', '', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dashed', 'B', '', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Please examine your Monthly Sales Report immediately. If no discrepancy is ', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Sales report not valid without official Homeworks', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' reported within 30 days from this bill\'s cut off date, the contents of this', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Dry Seal and valid signature.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($username, '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('statement will be considered correct. Thank you.', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // if ($count  <= $page) {
        //     $test = $page - $count; //yung hindi pa naoccupy
        //     $space = $test - 5;
        //     for ($i = 0; $i < $space; $i++) {
        //         $str .= $this->reporter->begintable($layoutsize);
        //         $str .= $this->reporter->startrow();
        //         $str .= $this->reporter->col('', '1200', '', false, '', '', '', $font, $font_size, '', '', '5px');
        //         $str .= $this->reporter->endrow();
        //         $str .= $this->reporter->endtable();
        //     }
        // }
        // $str .= $this->lastfooter($config, $layoutsize, $font, $font_size, $border);
        $str .= $this->reporter->endreport();
        $str .= '</div>';
        return $str;
    }


    public function diff($start, $end)
    {
        $date1 = new DateTime($start);
        $date2 = new DateTime($end);
        $diff = $date1->diff($date2);
        return $diff->days;
    }

    public function supp($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        // $branchid = $config['params']['dataparams']['branchid'];
        // $branchname = $config['params']['dataparams']['branchname'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];

        $itemid   = $config['params']['dataparams']['itemid'];
        $barcode   = $config['params']['dataparams']['barcode'];

        $reptype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];

        $filter = "";

        if ($center != "") {
            $filter .= "and num.center = '" . $center . "'  ";
        }
        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }

        if ($client != "") {
            $filter .= " and supp.clientid = '$clientid'";
        }


        if ($barcode != "") {
            $filter .= " and item.itemid = '$itemid'";
        }

        $qry = " select concat(trim(supp.clientname), ' - ',trim(supp.client)) as suppcode
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join cntnum as num on num.trno = head.trno
                    left join client as supp on supp.clientid = item.supplier
                     where  date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                    group by suppcode asc";
        return $this->coreFunctions->opentable($qry);
    }
}//end class