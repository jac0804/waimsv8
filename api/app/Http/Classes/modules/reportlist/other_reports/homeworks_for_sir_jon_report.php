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
use DateTime;
use DateInterval;
use DatePeriod;


// not yet done
class homeworks_for_sir_jon_report
{
    public $modulename = 'Homeworks For Sir Jon';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'station_rep', 'ddeptname', 'dclientname', 'channel'];
        $col1 = $this->fieldClass->create($fields);
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
                ['label' => 'Vendor w/ Item Concession', 'value' => '1', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Consignment', 'value' => '2', 'color' => 'orange'],
                ['label' => 'Vendor w/ Item Outright', 'value' => '3', 'color' => 'orange'],
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
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);

        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,   
        left(now(),10) as end ,
        '(0,1)' as itemstatus,
        '1' as reporttype,
      '" . $center . "' as center,
      '" . $dcenter[0]->name . "' as centername,
      '" . $dcenter[0]->dcentername . "' as dcentername,
        '' as station_rep,
        '' as stationline,
        '0' as stationid,
        '' as stationname,
        '0' as deptid,
        '' as ddeptname, 
        '' as dept,
        '' as deptname,
        '' as client,
        '0' as clientid,
        '' as clientname,
        '' as dclientname,
        '' as channel,
        '' as barcode,
        '0' as itemid
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

            case '1': // Vendor w/ Item Concession
                $str = $this->vendor_with_item_concession($config);
                break;

            case '2': // Vendor w/ Item Consignment
                $str = $this->vendor_with_item_consignment($config);
                break;

            case '3': // Vendor w/ Item Outright
                $str = $this->vendor_with_item_outright($config);
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
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $filter = "";
        $filtercl = "";
        $filtercl_ = "";

        if ($stationname != '') {
            $filter .= "and num.station=$station";
        }

        if ($deptname != '') {
            $filter .= "  and head.deptid = '$dept'";
        }

        if ($chan != '') {
            $filter .= "  and item.channel = '$chan'";
        }
        switch ($reporttype) {
            case '1': # Item Concession item.supplier
                $filtercl_ = " and left(supplier.client,3) = '163'";
                break;
            case '2': # Item Consignment
                $filtercl_ = " and left(supplier.client,3) = '162'";
                break;
            case '3': # Item Outright
                $filtercl_ = " and left(supplier.client,3) = '161'";
                break;
        }

        if ($client != "") {
            $filter .= " and supplier.clientid = '$clientid'";
        }
        //case cntnum.doc when 'CM' then sum(stock.qty)-1 else sum(stock.iss) end as iss
        $query = "select station,
                supcode, suppliername,barcode,itemdesc,
                sum(totalsold) as totalsold,
                sum(srp) as srp,
                sum(tlsrp) as tlsrp,
                sum(totalcost) as totalcost, sum(com1) as comm1, sum(grosspayable) as grosspayable, sum(com2) as comm2,sum(ext) as ext,pricetype,sum(comap) as comap,
                sum(disc) as disc,terminalid,sum(netap) as netap,sum(grossap2) as grossap2,banktype,bankrate,sum(cardcharge) as cardcharge
                from(
                select num.station,
               
                supplier.client as supcode, supplier.clientname as suppliername,
                item.barcode , item.itemname as itemdesc,
                case num.doc when 'CM' then sum(stock.qty)-1 else sum(stock.iss) end as totalsold,sum(stock.amt) as srp,
                sum(stock.disc) as disc,
                (SUM(stock.amt) * case num.doc when 'CM' then sum(stock.qty)-1 else sum(stock.iss) end) as tlsrp,
                case num.doc when 'CM' then SUM(stock.qty)-1 else SUM(stock.iss) * SUM(stock.cost) end as totalcost,sum(info.comm1) as com1,
                case num.doc when 'CM' then SUM(stock.qty)-1 else SUM(stock.iss) * SUM(stock.cost) end as grosspayable,sum(info.comm2) as com2,sum(stock.ext) as ext,info.pricetype,sum(info.comap)  as comap,
                sum(info.cardcharge) as cardcharge,sum(info.netap) as netap,sum(info.comap2) as grossap2,info.terminalid,info.banktype,info.bankrate
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join client as supplier on supplier.clientid=item.supplier
                left join cntnum as num on num.trno=head.trno
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                where date(head.dateid) between '$start' and '$end' and left(num.bref,3) = 'SJS' and item.isinactive in $itemstatus $filter $filtercl_
                group by date(head.dateid),supplier.client, supplier.clientname,
                item.barcode,item.itemname,num.doc,num.station,info.pricetype,stock.disc,info.terminalid,info.banktype,info.bankrate
               
                union all
           
                select num.station,
               
                supplier.client as supcode, supplier.clientname as suppliername,
                item.barcode, item.itemname as itemdesc,
                case num.doc when 'CM' then sum(stock.qty)-1 else sum(stock.iss) end as totalsold,sum(stock.amt) as srp,
                sum(stock.disc) as disc,
                (SUM(stock.amt) * SUM(stock.iss)) AS tlsrp,
                case num.doc when 'CM' then SUM(stock.qty)-1 else SUM(stock.iss) * SUM(stock.cost) end as totalcost,sum(info.comm1) as com1,
                case num.doc when 'CM' then SUM(stock.qty)-1 else SUM(stock.iss) * SUM(stock.cost) end as grosspayable,sum(info.comm2) as com2,sum(stock.ext) as ext,info.pricetype,sum(info.comap)  as comap,
                sum(info.cardcharge) as cardcharge,sum(info.netap) as netap,sum(info.comap2) as grossap2,info.terminalid,info.banktype,info.bankrate
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join client as supplier on supplier.clientid=item.supplier
                left join cntnum as num on num.trno=head.trno
                left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
                 where date(head.dateid) between '$start' and '$end' and left(num.bref,3) in ('SJS','SRS') and item.isinactive in $itemstatus $filter $filtercl_
                group by supplier.client, supplier.clientname,
                item.barcode,item.itemname,num.doc,num.station,info.pricetype,stock.disc,info.terminalid,info.banktype,info.bankrate) as rwn 
                group by supcode, suppliername,barcode,itemdesc,station,pricetype,disc,terminalid,banktype,bankrate
                order by supcode,barcode";

        return $this->coreFunctions->opentable($query);
    }
    public function default_query_test($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $itemid   = $config['params']['dataparams']['itemid'];
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

        if ($itemid != 0) {
            $filter .= " and item.itemid = '$itemid'";
        }

        switch ($reporttype) {
            case '1': # Item Concession item.supplier
                $filter .= " and left(supp.client,3) = '163'";
                break;
            case '2': # Item Consignment
                $filter .= " and left(supp.client,3) = '162'";
                break;
            case '3': # Item Outright
                $filter .= " and left(supp.client,3) = '161'";
                break;
        }


        $query = "select  supp.client as supcode, supp.clientname as suppliername, item.barcode,
                item.itemname as itemdesc,sum(stock.iss - stock.qty) as totalsold,
                if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)) as srp,
                if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1)) as cost,
                sum(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1))) as ext,
                sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as totalcost, 
                sum(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1)) as comm1,
                sum(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1))) as grosspayable,
                info.terminalid, 
                sum(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge)) as cardcharge,
                sum(info.comm2) as comm2,
                sum((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1)) as grossap2,
                info.bankrate,info.banktype,
                SUM(IF(LEFT(num.bref,3) = 'sjs',  info.discamt,  info.discamt * -1) * -1) AS disc,info.pricetype
            
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno 
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno 
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter
                group by supp.client, supp.clientname, item.barcode,item.itemname,info.terminalid,info.bankrate,info.banktype,num.bref,stock.isamt,stock.cost,info.pricetype
                order by supp.client";

        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }
    public function reportdatacsv($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $deptname     = $config['params']['dataparams']['ddeptname'];
        $dept     = $config['params']['dataparams']['deptid'];

        $chan     = $config['params']['dataparams']['channel'];
        $client   = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $itemid   = $config['params']['dataparams']['itemid'];
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

        if ($itemid != 0) {
            $filter .= " and item.itemid = '$itemid'";
        }

        switch ($reporttype) {
            case '1': # Item Concession item.supplier
                $filter .= " and left(supp.client,3) = '163'";
                break;
            case '2': # Item Consignment
                $filter .= " and left(supp.client,3) = '162'";
                break;
            case '3': # Item Outright
                $filter .= " and left(supp.client,3) = '161'";
                break;
        }


        $query = "select supp.client as `SUPPLIER CODE`, supp.clientname as `SUPPLIER NAME`, item.barcode as `ITEM CODE`,
                item.itemname as `ITEM DESCRIPTION`, round(stock.iss - stock.qty,2) as `TTL_SOLD`,
                round(if(left(num.bref,3) = 'sjs', stock.isamt, (stock.isamt * -1)),2) as `SRP`,
                round(((info.discamt) * -1),2) as `DISC`,
                round(if(left(num.bref,3) = 'sjs', stock.ext, (stock.ext * -1)),2) as `TTL_SRP`,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as `TTL_COST`,
                round(if(left(num.bref,3) = 'sjs', stock.cost, (stock.cost * -1)),2) as COST,
                round(if(left(num.bref, 3) = 'sjs', 1.0, -1.0) * if(info.comm1 = 0, 0.00, (stock.ext * (info.comm1/100)) *-1),2) as `COM_1`,
                round(if(left(num.bref,3) = 'sjs', (info.comap), ((info.comap) * -1)),2) as `GROSS_PAYABLE`,
                info.terminalid, 
                round(0) as 'QTY_3', round(0) as 'MOS3' , round(0) as 'QTY_6', round(0) as 'MOS6',
                round(0) as 'QTY_9', round(0) as 'MOS9_MOS12', round(0) as 'QTY_F' , round(0) as 'FINANCE_0', 
                round(0) as 'QTY_FR', round(0) as 'FINANCE_REGULAR', 
                round(0) as 'QTY_D', round(0) as 'DEBIT_STRAIGHT',

                round((if(left(num.bref,3) = 'sjs', (info.comap2), ((info.comap2) * -1)) * -1),2) as `COM_2`, round(0) as 'NET_PAYABLE',
                round(if(left(num.bref,3) = 'sjs', (info.cardcharge * -1), info.cardcharge),2) as cardcharge,
                info.bankrate,info.banktype
                from glhead as head 
                left join glstock as stock on stock.trno = head.trno 
                left join item on item.itemid = stock.itemid
                left join cntnum as num on num.trno = head.trno
                left join client as supp on supp.clientid = item.supplier
                left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
                where date(head.dateid) between '$start' and '$end'  and left(num.bref,3) in ('SJS','SRS') and  item.isinactive in $itemstatus $filter";

        $data = $this->coreFunctions->opentable($query);
        switch ($reporttype) {
            case '1': //vendor with item concession
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
                }
                break;
            case '2': //item consignment
            case '3': //outright
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
                }
                break;
        }


        return ['status' => true, 'msg' => 'Generating CSV successfully', 'data' => $data, 'params' => $this->reportParams, 'name' => 'ItemList'];
    }
    private function vendor_with_item_header($config, $supcode, $supplier)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $itemstatus = $config['params']['dataparams']['itemstatus'];
        $client = $config['params']['dataparams']['client'];
        $clientid   = $config['params']['dataparams']['clientid'];
        $clientname = $config['params']['dataparams']['dclientname'];
        $retporttype = $config['params']['dataparams']['reporttype'];

        if ($itemstatus == '(0)') {
            $itemstatus = 'ACTIVE ITEMS';
        } elseif ($itemstatus == '(1)') {
            $itemstatus = 'ACTIVE ITEMS';
        } else {
            $itemstatus = 'ALL ITEMS';
        }
        $reptype = '';
        $lastcol = 'TYPE';
        if ($retporttype == '1') {
            $reptype = 'CONCESSION';
            $lastcol = 'GM';
        } else if ($retporttype == '2') {
            $reptype = 'CONSIGNMENT';
        } else if ($retporttype == '3') {
            $reptype = 'OUTRIGHT';
        }
        $supp = "";
        if ($clientid != 0) {
            $supp = $clientname;
        } else {
            $supp = $supcode . '~' . $supplier;
        }


        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1200';
        if ($retporttype != '1') {
            $layoutsize = '1250';
        }

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name) . ' ' . strtoupper($headerdata[0]->address), '883', null, false, $border, '', 'L', $font, 14, 'B', '', '');
        $str .= $this->reporter->col($itemstatus . ' - ITEM PER VENDOR SUMMARY REPORT-' . $reptype, '317', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier: ' . $supp, '600', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '880', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM:&nbsp' . $start, '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('CHARGE', '385', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO:&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $end, '315', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '80', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '86', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL SOLD', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        if ($retporttype != '1') {
            $str .= $this->reporter->col('COST', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        }
        $str .= $this->reporter->col('SRP', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DISCOUNT', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TTL SRP', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('COM 1', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('GROSS PAYABLE', '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('3 MOS', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('6 MOS', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('9 MOS/12 MOS', '50', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FINANCE 0%', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FINANCE REGULAR', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('QTY', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DEBIT / STRAIGHT', '49', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('COM 2', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('NET PAYABLE', '49', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($lastcol, '50', '', false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function vendor_with_item_concession($config)
    {
        // $result = $this->reportDefault($config);
        $result = $this->default_query_test($config);


        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $count = 26;
        $page = 26;
        $p = 40;
        $totalsld = 0;
        $totaldiscount = 0;
        $tlsrp = 0;
        $tlcost = 0;
        $comm1here = 0;
        $comm1here2 = 0;
        $gross = 0;
        $commm2 = 0;
        $netp = 0;
        $totalcomapp = 0;
        $gmtotal = 0;

        $tsrp = 0;

        $tqty3 = 0;
        $tqty6 = 0;
        $tqty9 = 0;

        $tmos3 = 0;
        $tmos6 = 0;
        $tmos9 = 0;

        $tmost = 0;
        $tqtyst = 0;

        $tfinance_rqty =  0;
        $tfinance_r = 0;
        $tfinance_zqty = 0;
        $tfinance_z = 0;

        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1200';
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50;margin-top:10px;margin-left:65px;');

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $counter = 0;
        $gm = 0;
        $supcode = "";
        $supname = "";

        $subsold = 0;
        $subcost = 0;
        $subsrp = 0;
        $subdiscount = 0;
        $subamt = 0;
        $subcomm1 = 0;
        $subgrosspayable = 0;
        $subdisplay_mos3 = 0;
        $subdisp_qty3 = 0;

        $subdisplay_mos6 = 0;
        $subdisp_qty6 = 0;
        $subdisplay_mos9 = 0;
        $subdisp_qty9 = 0;
        $subdisp_financezeroqty = 0;
        $subdisp_financezero = 0;
        $subdisp_financeregularqty = 0;
        $subdisp_financeregular = 0;
        $subdisplay_mos = 0;
        $subdisp_qtyst = 0;
        $subcomm2 = 0;
        $subnetpayable = 0;
        $subgm = 0;
        $showfooter = false;
        $i = 0;
        foreach ($result as $key => $data) {
            $i++;

            if ($supcode == "") {
                $str .= "<div style='position:relative; margin:0 0 80px 0'>";
                $str .= $this->vendor_with_item_header($config, $data->supcode, $data->suppliername);
            }
            if ($counter >= 37) {
                goto pbreak;
            }

            if ($supcode != "" && $supcode == $data->supcode) {
                $supcode = "";
                $supname = "";
            } else {
                if ($supcode != "" && $supcode != $data->supcode) {
                    pbreak:
                    $counter = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1200', '', false, '1px dotted', 'T', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('SUBTOTAL:', '80', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col('', '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($subsold, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($subsrp, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdiscount != 0 ? number_format(abs($subdiscount), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($subamt, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subcomm1 < 0 ? '(' . number_format(abs($subcomm1), 2) . ')' : ($subcomm1 != 0 ? number_format($subcomm1, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subgrosspayable < 0 ? '(' . number_format($subgrosspayable, 2) . ')' : ($subgrosspayable != 0 ? number_format($subgrosspayable, 2) : '-'), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty3 < 0 ? '(' . number_format(abs($subdisp_qty3), 2) . ')' : ($subdisp_qty3 != 0 ? number_format($subdisp_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos3 < 0 ? '(' . number_format(abs($subdisplay_mos3), 2) . ')' : ($subdisplay_mos3 != 0 ? number_format($subdisplay_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty6 < 0 ? '(' . number_format(abs($subdisp_qty6), 2) . ')' : ($subdisp_qty6 != 0 ? number_format($subdisp_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos6 < 0 ? '(' . number_format(abs($subdisplay_mos6), 2) . ')' : ($subdisplay_mos6 != 0 ? number_format($subdisplay_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty9 < 0 ? '(' . number_format(abs($subdisp_qty9), 2) . ')' : ($subdisp_qty9 != 0 ? number_format($subdisp_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos9 < 0 ? '(' . number_format(abs($subdisplay_mos9), 2) . ')' : ($subdisplay_mos9 != 0 ? number_format($subdisplay_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financezeroqty < 0 ? '(' . number_format(abs($subdisp_financezeroqty), 2) . ')' : ($subdisp_financezeroqty != 0 ? number_format($subdisp_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financezero < 0 ? '(' . number_format(abs($subdisp_financezero), 2) . ')' : ($subdisp_financezero != 0 ? number_format($subdisp_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financeregularqty < 0 ? '(' . number_format(abs($subdisp_financeregularqty), 2) . ')' : ($subdisp_financeregularqty != 0 ? number_format($subdisp_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financeregular < 0 ? '(' . number_format(abs($subdisp_financeregular), 2) . ')' : ($subdisp_financeregular != 0 ? number_format($subdisp_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qtyst < 0 ? '(' . number_format(abs($subdisp_qtyst), 2) . ')' : ($subdisp_qtyst != 0 ? number_format($subdisp_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos < 0 ? '(' . number_format(abs($subdisplay_mos), 2) . ')' : ($subdisplay_mos != 0 ? number_format($subdisplay_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subcomm2 < 0 ? '(' . number_format(abs($subcomm2), 2) . ')' : ($subcomm2 != 0 ? number_format($subcomm2, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subnetpayable < 0 ? '(' . number_format(abs($subnetpayable), 2) . ')' : ($subnetpayable != 0 ? number_format($subnetpayable, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subgm < 0 ? '(' . number_format(abs($subgm), 2) . ')' : ($subgm != 0 ? $subgm : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $subsold = 0;
                    $subsrp = 0;
                    $subdiscount = 0;
                    $subamt = 0;
                    $subcomm1 = 0;
                    $subgrosspayable = 0;
                    $subdisplay_mos3 = 0;
                    $subdisp_qty3 = 0;

                    $subdisplay_mos6 = 0;
                    $subdisp_qty6 = 0;
                    $subdisplay_mos9 = 0;
                    $subdisp_qty9 = 0;
                    $subdisp_financezeroqty = 0;
                    $subdisp_financezero = 0;
                    $subdisp_financeregularqty = 0;
                    $subdisp_financeregular = 0;
                    $subdisplay_mos = 0;
                    $subdisp_qtyst = 0;
                    $subcomm2 = 0;
                    $subnetpayable = 0;
                    $subgm = 0;
                    $str .= "</div>";

                    $str .= $this->reporter->page_break();
                    $str .= $this->footer_homeworks($config, $layoutsize, $date, $username, $last = false);
                    $str .= "<div style='position:relative; margin:0 0 80px 0'>";
                    // $str .= $this->footer_homeworks($config, $layoutsize, $date, $username, $last = false);
                    $str .= $this->vendor_with_item_header($config, $data->supcode, $data->suppliername);
                    // $showfooter = true;
                }
                $supcode = $data->supcode;
                $supname = $data->suppliername;
            }




            $len = strlen($data->itemdesc);
            $maxlen = ceil($len / 12);
            $counter += $maxlen;
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

            $comm1 = $data->comm1;
            $cost = $data->cost;
            $cost = ($cost != 0) ? $cost : 0;


            // $comap      = ($data->totalcost != 0) ? floatval($data->totalcost) : 0;
            // $grossap2   = ($grossap2s != 0) ? floatval($grossap2s) : 0;


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($supname, '1200', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();



            $sold = $totalsold;
            $srp = $data->srp;
            $dis =  $discount;
            $ammt = $tlsrp;
            $comm1dis = $comm1;
            $grosspay = $grosspayable;
            $dis_mos3 = $display_mos3;
            $dis_qty3 = $disp_qty3;
            $dis_mos6 = $display_mos6;
            $dis_qty6 = $disp_qty6;
            $dis_mos9 = $display_mos9;
            $dis_qty9 = $disp_qty9;
            $dis_financezeroqty = $disp_financezeroqty;
            $dis_financezero = $disp_financezero;
            $dis_financeregularqty = $disp_financeregularqty;
            $dis_financeregular = $disp_financeregular;
            $dis_mos = $display_mos;
            $dis_qtyst = $disp_qtyst;
            $com2 = $grossap2s;


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->barcode, '80', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemdesc, '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($sold, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($srp, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($discount != 0 ? number_format(abs($dis), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($ammt, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($comm1 < 0 ? '(' . number_format(abs($comm1dis), 2) . ')' : ($comm1 != 0 ? number_format($comm1dis, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grosspayable != 0 ? number_format($grosspay, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty3 < 0 ? '(' . number_format(abs($dis_qty3), 2) . ')' : ($disp_qty3 != 0 ? number_format($dis_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos3 < 0 ? '(' . number_format(abs($dis_mos3), 2) . ')' : ($display_mos3 != 0 ? number_format($dis_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty6 < 0 ? '(' . number_format(abs($dis_qty6), 2) . ')' : ($disp_qty6 != 0 ? number_format($dis_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos6  < 0 ? '(' . number_format(abs($dis_mos6), 2) . ')' : ($display_mos6 != 0 ? number_format($dis_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty9 < 0 ? '(' . number_format(abs($dis_qty9), 2) . ')' : ($disp_qty9 != 0 ? number_format($dis_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos9 < 0 ? '(' . number_format(abs($dis_mos9), 2) . ')' : ($display_mos9 != 0 ? number_format($dis_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financezeroqty < 0 ? '(' . number_format(abs($dis_financezeroqty), 2) . ')' : ($disp_financezeroqty != 0 ? number_format($dis_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financezero < 0 ? '(' . number_format(abs($dis_financezero), 2) . ')' : ($disp_financezero != 0 ? number_format($dis_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financeregularqty  < 0 ? '(' . number_format(abs($dis_financeregularqty), 2) . ')' : ($disp_financeregularqty != 0 ? number_format($dis_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financeregular < 0 ? '(' . number_format(abs($dis_financeregular), 2) . ')' : ($disp_financeregular != 0 ? number_format($dis_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qtyst < 0 ? '(' . number_format(abs($dis_qtyst), 2) . ')' : ($disp_qtyst != 0 ? number_format($dis_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos < 0 ? '(' . number_format(abs($dis_mos), 2) . ')' : ($display_mos != 0 ?  number_format($dis_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grossap2s < 0 ? '(' . number_format(abs($com2), 2) . ')' : ($grossap2s != 0 ? number_format($grossap2s, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($disp_netpayable, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');


            if ($grosspayable > 0 && $tlsrp > 0) {
                $gm = ($grosspayable / $tlsrp) - 1;
                $grandgm = $gm;
            }
            $str .= $this->reporter->col($gm < 0 ? '(' . number_format(abs($gm), 2) . ')' : ($gm != 0 ? number_format($gm, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            // subtotal
            $subsold += $sold;
            $subsrp += $srp;
            $subdiscount += $discount;
            $subamt += $tlsrp;
            $subcomm1 += $comm1;
            $subgrosspayable += $grosspayable;
            $subdisplay_mos3 += $display_mos3;
            $subdisp_qty3 += $disp_qty3;

            $subdisplay_mos6 += $display_mos6;
            $subdisp_qty6 += $disp_qty6;
            $subdisplay_mos9 += $display_mos9;
            $subdisp_qty9 += $disp_qty9;
            $subdisp_financezeroqty += $disp_financezeroqty;
            $subdisp_financezero += $disp_financezero;
            $subdisp_financeregularqty += $disp_financeregularqty;
            $subdisp_financeregular += $disp_financeregular;
            $subdisplay_mos += $display_mos;
            $subdisp_qtyst += $disp_qtyst;
            $subcomm2 += $grossap2s;
            $subnetpayable += $netpayable;
            $subgm += $gm;

            // grandtotal
            $totalsld = $totalsld + $data->totalsold;
            $totaldiscount = $totaldiscount + $data->disc;
            $tlsrp = $tlsrp + $data->ext;
            $tsrp = $tsrp + $data->srp;
            $tlcost = $tlcost + $data->totalcost;
            $comm1here = $comm1here + $comm1;
            $comm1here2 = $comm1here2 + $grossap2s;
            $gross = $gross + $data->grosspayable;

            $tqty3 = $tqty3 + $qty3;
            $tmos3 = $tmos3 + $mos3;

            $tqty6 = $tqty6 + $qty6;
            $tmos6 = $tmos6 + $mos6;

            $tqty9 = $tqty9 + $qty9;
            $tmos9 = $tmos9 + $mos9;

            $tmost = $tmost + $most;
            $tqtyst = $tqtyst + $qtyst;

            $tfinance_rqty =  $tfinance_rqty + $financeregularqty;
            $tfinance_r = $tfinance_r + $financeregular;
            $tfinance_zqty = $tfinance_zqty  + $financezeroqty;
            $tfinance_z = $tfinance_z + $financezero;

            $netp = $netp + $netpayable;
            $gmtotal += $grandgm;
            $supcode = $data->supcode;
            $supname = $data->suppliername;
        } //end foreach

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dotted', 'T', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUBTOTAL:', '80', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($subsold, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($subsrp, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdiscount != 0 ? number_format(abs($subdiscount), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($subamt, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subcomm1 < 0 ? '(' . number_format(abs($subcomm1), 2) . ')' : ($subcomm1 != 0 ? number_format($subcomm1, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subgrosspayable < 0 ? '(' . number_format($subgrosspayable, 2) . ')' : ($subgrosspayable != 0 ? number_format($subgrosspayable, 2) : '-'), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty3 < 0 ? '(' . number_format(abs($subdisp_qty3), 2) . ')' : ($subdisp_qty3 != 0 ? number_format($subdisp_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos3 < 0 ? '(' . number_format(abs($subdisplay_mos3), 2) . ')' : ($subdisplay_mos3 != 0 ? number_format($subdisplay_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty6 < 0 ? '(' . number_format(abs($subdisp_qty6), 2) . ')' : ($subdisp_qty6 != 0 ? number_format($subdisp_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos6 < 0 ? '(' . number_format(abs($subdisplay_mos6), 2) . ')' : ($subdisplay_mos6 != 0 ? number_format($subdisplay_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty9 < 0 ? '(' . number_format(abs($subdisp_qty9), 2) . ')' : ($subdisp_qty9 != 0 ? number_format($subdisp_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos9 < 0 ? '(' . number_format(abs($subdisplay_mos9), 2) . ')' : ($subdisplay_mos9 != 0 ? number_format($subdisplay_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financezeroqty < 0 ? '(' . number_format(abs($subdisp_financezeroqty), 2) . ')' : ($subdisp_financezeroqty != 0 ? number_format($subdisp_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financezero < 0 ? '(' . number_format(abs($subdisp_financezero), 2) . ')' : ($subdisp_financezero != 0 ? number_format($subdisp_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financeregularqty < 0 ? '(' . number_format(abs($subdisp_financeregularqty), 2) . ')' : ($subdisp_financeregularqty != 0 ? number_format($subdisp_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financeregular < 0 ? '(' . number_format(abs($subdisp_financeregular), 2) . ')' : ($subdisp_financeregular != 0 ? number_format($subdisp_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qtyst < 0 ? '(' . number_format(abs($subdisp_qtyst), 2) . ')' : ($subdisp_qtyst != 0 ? number_format($subdisp_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos < 0 ? '(' . number_format(abs($subdisplay_mos), 2) . ')' : ($subdisplay_mos != 0 ? number_format($subdisplay_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subcomm2 < 0 ? '(' . number_format(abs($subcomm2), 2) . ')' : ($subcomm2 != 0 ? number_format($subcomm2, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subnetpayable < 0 ? '(' . number_format(abs($subnetpayable), 2) . ')' : ($subnetpayable != 0 ? number_format($subnetpayable, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subgm < 0 ? '(' . number_format(abs($subgm), 2) . ')' : ($subgm != 0 ? number_format($subgm, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $discc = $totaldiscount != 0 ? $totaldiscount : ' - ';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRANDTOTAL: ', '80', '', false, $border, 'T', 'LT', $font, 7, 'B', '', '');
        $str .= $this->reporter->col('', '86', '', false, $border, 'T', 'L', $font, 5, '', '', '');
        $str .= $this->reporter->col($totalsld < 0 ? '(' . number_format(abs($totalsld), 2) . ')' : ($totalsld != 0 ? number_format($totalsld, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tsrp < 0 ? '(' . number_format(abs($tsrp), 2) . ')' : ($tsrp != 0 ? number_format($tsrp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($discc < 0 ? '(' . number_format(abs($discc), 2) . ')' : ($discc != 0 ? number_format($discc, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tlsrp < 0 ? '(' . number_format(abs($tlsrp), 2) . ')' : ($tlsrp != 0 ? number_format($tlsrp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($comm1here < 0 ? '(' . number_format(abs($comm1here), 2) . ')' : ($comm1here != 0 ? number_format($comm1here, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($gross < 0 ? '(' . number_format(abs($gross), 2) . ')' : ($gross != 0 ? number_format($gross, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty3 < 0 ? '(' . number_format(abs($tqty3), 2) . ')' : ($tqty3 != 0 ? number_format($tqty3, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos3 < 0 ? '(' . number_format(abs($tmos3), 2) . ')' : ($tmos3 != 0 ? number_format($tmos3, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty6 < 0 ? '(' . number_format(abs($tqty6), 2) . ')' : ($tqty6 != 0 ? number_format($tqty6, 2) : ''), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos6 < 0 ? '(' . number_format(abs($tmos6), 2) . ')' : ($tmos6 != 0 ? number_format($tmos6, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty9 < 0 ? '(' . number_format(abs($tqty9), 2) . ')' : ($tqty9 != 0 ? number_format($tqty9, 2) : '-'), '50', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos9 < 0 ? '(' . number_format(abs($tmos9), 2) . ')' : ($tmos9 != 0 ? number_format($tmos9, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_rqty < 0 ? '(' . number_format(abs($tfinance_rqty), 2) . ')' : ($tfinance_rqty != 0 ? number_format($tfinance_rqty, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_r < 0 ? '(' . number_format(abs($tfinance_r), 2) . ')' : ($tfinance_r != 0 ? number_format($tfinance_r, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_zqty < 0 ? '(' . number_format(abs($tfinance_zqty), 2) . ')' : ($tfinance_zqty != 0 ? number_format($tfinance_zqty, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_z < 0 ? '(' . number_format(abs($tfinance_z), 2) . ')' : ($tfinance_z != 0 ? number_format($tfinance_z, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqtyst < 0 ? '(' . number_format(abs($tqtyst), 2) . ')' : ($tqtyst != 0 ? number_format($tqtyst, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmost < 0 ? '(' . number_format($tmost, 2) . ')' : ($tmost != 0 ? number_format($tmost, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($comm1here2 < 0 ? '(' . number_format(abs($comm1here2), 2) . ')' : ($comm1here2 != 0 ? number_format($comm1here2, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($netp < 0 ? '(' . number_format(abs($netp), 2) . ')' : ($netp != 0 ? number_format($netp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($gmtotal < 0 ? '(' . number_format(abs($gmtotal), 2) . ')' : ($gmtotal != 0 ? number_format($gmtotal, 2) : '-'), '50', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $font_style = 'I';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted By :', '225', '', false, $border, 'T', 'C', $font, $font_size,  $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Received By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // $str .= "<div style='position:relative; margin:145px 0 0 0'>";
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
        // $str .= "</div>";


        $str .= $this->reporter->endreport();
        $str .= '</div>';
        return $str;
    }


    public function vendor_with_item_consignment($config)
    {
        $result = $this->default_query_test($config);


        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $count = 26;
        $page = 26;
        $p = 40;
        $totalsld = 0;
        $gtotalcost = 0;
        $totaldiscount = 0;
        $tlsrp = 0;
        $tlcost = 0;
        $comm1here = 0;
        $comm1here2 = 0;
        $gross = 0;
        $commm2 = 0;
        $netp = 0;
        $totalcomapp = 0;
        $gmtotal = 0;

        $tsrp = 0;

        $tqty3 = 0;
        $tqty6 = 0;
        $tqty9 = 0;

        $tmos3 = 0;
        $tmos6 = 0;
        $tmos9 = 0;

        $tmost = 0;
        $tqtyst = 0;

        $tfinance_rqty =  0;
        $tfinance_r = 0;
        $tfinance_zqty = 0;
        $tfinance_z = 0;

        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1250';
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50;margin-top:10px;margin-left:65px;');

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $counter = 0;
        $gm = 0;
        $supcode = "";
        $supname = "";

        $subsold = 0;
        $subsrp = 0;
        $subcost = 0;
        $subdiscount = 0;
        $subamt = 0;
        $subcomm1 = 0;
        $subgrosspayable = 0;
        $subdisplay_mos3 = 0;
        $subdisp_qty3 = 0;

        $subdisplay_mos6 = 0;
        $subdisp_qty6 = 0;
        $subdisplay_mos9 = 0;
        $subdisp_qty9 = 0;
        $subdisp_financezeroqty = 0;
        $subdisp_financezero = 0;
        $subdisp_financeregularqty = 0;
        $subdisp_financeregular = 0;
        $subdisplay_mos = 0;
        $subdisp_qtyst = 0;
        $subcomm2 = 0;
        $subnetpayable = 0;
        $subgm = 0;
        $showfooter = false;
        $i = 0;
        foreach ($result as $key => $data) {
            $i++;

            if ($supcode == "") {
                $str .= "<div style='position:relative; margin:0 0 80px 0'>";
                $str .= $this->vendor_with_item_header($config, $data->supcode, $data->suppliername);
            }
            if ($counter >= 37) {
                goto pbreak;
            }

            if ($supcode != "" && $supcode == $data->supcode) {
                $supcode = "";
                $supname = "";
            } else {
                if ($supcode != "" && $supcode != $data->supcode) {
                    pbreak:
                    $counter = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1200', '', false, '1px dotted', 'T', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('SUBTOTAL:', '80', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col('', '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($subsold, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($subcost, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($subsrp, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdiscount != 0 ? number_format(abs($subdiscount), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($subamt, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subcomm1 < 0 ? '(' . number_format(abs($subcomm1), 2) . ')' : ($subcomm1 != 0 ? number_format($subcomm1, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subgrosspayable < 0 ? '(' . number_format($subgrosspayable, 2) . ')' : ($subgrosspayable != 0 ? number_format($subgrosspayable, 2) : '-'), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty3 < 0 ? '(' . number_format(abs($subdisp_qty3), 2) . ')' : ($subdisp_qty3 != 0 ? number_format($subdisp_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos3 < 0 ? '(' . number_format(abs($subdisplay_mos3), 2) . ')' : ($subdisplay_mos3 != 0 ? number_format($subdisplay_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty6 < 0 ? '(' . number_format(abs($subdisp_qty6), 2) . ')' : ($subdisp_qty6 != 0 ? number_format($subdisp_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos6 < 0 ? '(' . number_format(abs($subdisplay_mos6), 2) . ')' : ($subdisplay_mos6 != 0 ? number_format($subdisplay_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty9 < 0 ? '(' . number_format(abs($subdisp_qty9), 2) . ')' : ($subdisp_qty9 != 0 ? number_format($subdisp_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos9 < 0 ? '(' . number_format(abs($subdisplay_mos9), 2) . ')' : ($subdisplay_mos9 != 0 ? number_format($subdisplay_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financezeroqty < 0 ? '(' . number_format(abs($subdisp_financezeroqty), 2) . ')' : ($subdisp_financezeroqty != 0 ? number_format($subdisp_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financezero < 0 ? '(' . number_format(abs($subdisp_financezero), 2) . ')' : ($subdisp_financezero != 0 ? number_format($subdisp_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financeregularqty < 0 ? '(' . number_format(abs($subdisp_financeregularqty), 2) . ')' : ($subdisp_financeregularqty != 0 ? number_format($subdisp_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financeregular < 0 ? '(' . number_format(abs($subdisp_financeregular), 2) . ')' : ($subdisp_financeregular != 0 ? number_format($subdisp_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qtyst < 0 ? '(' . number_format(abs($subdisp_qtyst), 2) . ')' : ($subdisp_qtyst != 0 ? number_format($subdisp_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos < 0 ? '(' . number_format(abs($subdisplay_mos), 2) . ')' : ($subdisplay_mos != 0 ? number_format($subdisplay_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subcomm2 < 0 ? '(' . number_format(abs($subcomm2), 2) . ')' : ($subcomm2 != 0 ? number_format($subcomm2, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subnetpayable < 0 ? '(' . number_format(abs($subnetpayable), 2) . ')' : ($subnetpayable != 0 ? number_format($subnetpayable, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subgm < 0 ? number_format(abs($subgm), 2) : ($subgm != 0 ? number_format($subgm) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $subsold = 0;
                    $subsrp = 0;
                    $subdiscount = 0;
                    $subamt = 0;
                    $subcomm1 = 0;
                    $subgrosspayable = 0;
                    $subdisplay_mos3 = 0;
                    $subdisp_qty3 = 0;

                    $subdisplay_mos6 = 0;
                    $subdisp_qty6 = 0;
                    $subdisplay_mos9 = 0;
                    $subdisp_qty9 = 0;
                    $subdisp_financezeroqty = 0;
                    $subdisp_financezero = 0;
                    $subdisp_financeregularqty = 0;
                    $subdisp_financeregular = 0;
                    $subdisplay_mos = 0;
                    $subdisp_qtyst = 0;
                    $subcomm2 = 0;
                    $subnetpayable = 0;
                    $subgm = 0;
                    $str .= "</div>";

                    $str .= $this->reporter->page_break();
                    $str .= $this->footer_homeworks($config, $layoutsize, $date, $username, $last = false);
                    $str .= "<div style='position:relative; margin:0 0 80px 0'>";
                    // $str .= $this->footer_homeworks($config, $layoutsize, $date, $username, $last = false);
                    $str .= $this->vendor_with_item_header($config, $data->supcode, $data->suppliername);
                    // $showfooter = true;
                }
                $supcode = $data->supcode;
                $supname = $data->suppliername;
            }




            $len = strlen($data->itemdesc);
            $maxlen = ceil($len / 12);
            $counter += $maxlen;
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

            $comm1 = $data->comm1;
            $cost = $data->cost;
            $cost = ($cost != 0) ? $cost : 0;


            // $comap      = ($data->totalcost != 0) ? floatval($data->totalcost) : 0;
            // $grossap2   = ($grossap2s != 0) ? floatval($grossap2s) : 0;


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($supname, '1200', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();



            $sold = $totalsold;
            $discost = $cost;
            $srp = $data->srp;
            $dis =  $discount;
            $ammt = $tlsrp;
            $comm1dis = $comm1;
            $grosspay = $grosspayable;
            $dis_mos3 = $display_mos3;
            $dis_qty3 = $disp_qty3;
            $dis_mos6 = $display_mos6;
            $dis_qty6 = $disp_qty6;
            $dis_mos9 = $display_mos9;
            $dis_qty9 = $disp_qty9;
            $dis_financezeroqty = $disp_financezeroqty;
            $dis_financezero = $disp_financezero;
            $dis_financeregularqty = $disp_financeregularqty;
            $dis_financeregular = $disp_financeregular;
            $dis_mos = $display_mos;
            $dis_qtyst = $disp_qtyst;
            $com2 = $grossap2s;


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->barcode, '80', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemdesc, '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($totalsold != 0 ? number_format($sold, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($cost != 0 ? number_format($discost, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->srp != 0 ? number_format($srp, 2) : '', '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($discount != 0 ? number_format(abs($dis), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($ammt, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($comm1 < 0 ? '(' . number_format(abs($comm1dis), 2) . ')' : ($comm1 != 0 ? number_format($comm1dis, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grosspayable != 0 ? number_format($grosspay, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty3 < 0 ? '(' . number_format(abs($dis_qty3), 2) . ')' : ($disp_qty3 != 0 ? number_format($dis_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos3 < 0 ? '(' . number_format(abs($dis_mos3), 2) . ')' : ($display_mos3 != 0 ? number_format($dis_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty6 < 0 ? '(' . number_format(abs($dis_qty6), 2) . ')' : ($disp_qty6 != 0 ? number_format($dis_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos6  < 0 ? '(' . number_format(abs($dis_mos6), 2) . ')' : ($display_mos6 != 0 ? number_format($dis_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty9 < 0 ? '(' . number_format(abs($dis_qty9), 2) . ')' : ($disp_qty9 != 0 ? number_format($dis_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos9 < 0 ? '(' . number_format(abs($dis_mos9), 2) . ')' : ($display_mos9 != 0 ? number_format($dis_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financezeroqty < 0 ? '(' . number_format(abs($dis_financezeroqty), 2) . ')' : ($disp_financezeroqty != 0 ? number_format($dis_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financezero < 0 ? '(' . number_format(abs($dis_financezero), 2) . ')' : ($disp_financezero != 0 ? number_format($dis_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financeregularqty  < 0 ? '(' . number_format(abs($dis_financeregularqty), 2) . ')' : ($disp_financeregularqty != 0 ? number_format($dis_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financeregular < 0 ? '(' . number_format(abs($dis_financeregular), 2) . ')' : ($disp_financeregular != 0 ? number_format($dis_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qtyst < 0 ? '(' . number_format(abs($dis_qtyst), 2) . ')' : ($disp_qtyst != 0 ? number_format($dis_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos < 0 ? '(' . number_format(abs($dis_mos), 2) . ')' : ($display_mos != 0 ?  number_format($dis_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grossap2s < 0 ? '(' . number_format(abs($com2), 2) . ')' : ($grossap2s != 0 ? number_format($grossap2s, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(number_format($disp_netpayable, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');


            if ($grosspayable > 0 && $tlsrp > 0) {
                $gm = ($grosspayable / $tlsrp) - 1;
            }
            $str .= $this->reporter->col($data->pricetype != '' ? $data->pricetype : '-', '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            // subtotal
            $subsold += $sold;
            $subsrp += $srp;
            $subcost += $cost;
            $subdiscount += $discount;
            $subamt += $tlsrp;
            $subcomm1 += $comm1;
            $subgrosspayable += $grosspayable;
            $subdisplay_mos3 += $display_mos3;
            $subdisp_qty3 += $disp_qty3;

            $subdisplay_mos6 += $display_mos6;
            $subdisp_qty6 += $disp_qty6;
            $subdisplay_mos9 += $display_mos9;
            $subdisp_qty9 += $disp_qty9;
            $subdisp_financezeroqty += $disp_financezeroqty;
            $subdisp_financezero += $disp_financezero;
            $subdisp_financeregularqty += $disp_financeregularqty;
            $subdisp_financeregular += $disp_financeregular;
            $subdisplay_mos += $display_mos;
            $subdisp_qtyst += $disp_qtyst;
            $subcomm2 += $grossap2s;
            $subnetpayable += $netpayable;
            $subgm += $gm;

            // grandtotal
            $totalsld = $totalsld + $data->totalsold;

            $gtotalcost += $data->cost;
            $totaldiscount = $totaldiscount + $data->disc;
            $tlsrp = $tlsrp + $data->ext;
            $tsrp = $tsrp + $data->srp;
            $tlcost = $tlcost + $data->totalcost;
            $comm1here = $comm1here + $comm1;
            $comm1here2 = $comm1here2 + $grossap2s;
            $gross = $gross + $data->grosspayable;

            $tqty3 = $tqty3 + $qty3;
            $tmos3 = $tmos3 + $mos3;

            $tqty6 = $tqty6 + $qty6;
            $tmos6 = $tmos6 + $mos6;

            $tqty9 = $tqty9 + $qty9;
            $tmos9 = $tmos9 + $mos9;

            $tmost = $tmost + $most;
            $tqtyst = $tqtyst + $qtyst;

            $tfinance_rqty =  $tfinance_rqty + $financeregularqty;
            $tfinance_r = $tfinance_r + $financeregular;
            $tfinance_zqty = $tfinance_zqty  + $financezeroqty;
            $tfinance_z = $tfinance_z + $financezero;

            $netp = $netp + $netpayable;
            $gmtotal += $gm;
            $supcode = $data->supcode;
            $supname = $data->suppliername;
        } //end foreach

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dotted', 'T', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUBTOTAL:', '80', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($subsold, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($subcost, 2), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($subsrp, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdiscount != 0 ? number_format(abs($subdiscount), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($subamt, 2), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subcomm1 < 0 ? '(' . number_format(abs($subcomm1), 2) . ')' : ($subcomm1 != 0 ? number_format($subcomm1, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subgrosspayable < 0 ? '(' . number_format($subgrosspayable, 2) . ')' : ($subgrosspayable != 0 ? number_format($subgrosspayable, 2) : '-'), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty3 < 0 ? '(' . number_format(abs($subdisp_qty3), 2) . ')' : ($subdisp_qty3 != 0 ? number_format($subdisp_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos3 < 0 ? '(' . number_format(abs($subdisplay_mos3), 2) . ')' : ($subdisplay_mos3 != 0 ? number_format($subdisplay_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty6 < 0 ? '(' . number_format(abs($subdisp_qty6), 2) . ')' : ($subdisp_qty6 != 0 ? number_format($subdisp_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos6 < 0 ? '(' . number_format(abs($subdisplay_mos6), 2) . ')' : ($subdisplay_mos6 != 0 ? number_format($subdisplay_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty9 < 0 ? '(' . number_format(abs($subdisp_qty9), 2) . ')' : ($subdisp_qty9 != 0 ? number_format($subdisp_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos9 < 0 ? '(' . number_format(abs($subdisplay_mos9), 2) . ')' : ($subdisplay_mos9 != 0 ? number_format($subdisplay_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financezeroqty < 0 ? '(' . number_format(abs($subdisp_financezeroqty), 2) . ')' : ($subdisp_financezeroqty != 0 ? number_format($subdisp_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financezero < 0 ? '(' . number_format(abs($subdisp_financezero), 2) . ')' : ($subdisp_financezero != 0 ? number_format($subdisp_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financeregularqty < 0 ? '(' . number_format(abs($subdisp_financeregularqty), 2) . ')' : ($subdisp_financeregularqty != 0 ? number_format($subdisp_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financeregular < 0 ? '(' . number_format(abs($subdisp_financeregular), 2) . ')' : ($subdisp_financeregular != 0 ? number_format($subdisp_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qtyst < 0 ? '(' . number_format(abs($subdisp_qtyst), 2) . ')' : ($subdisp_qtyst != 0 ? number_format($subdisp_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos < 0 ? '(' . number_format(abs($subdisplay_mos), 2) . ')' : ($subdisplay_mos != 0 ? number_format($subdisplay_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subcomm2 < 0 ? '(' . number_format(abs($subcomm2), 2) . ')' : ($subcomm2 != 0 ? number_format($subcomm2, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subnetpayable < 0 ? '(' . number_format(abs($subnetpayable), 2) . ')' : ($subnetpayable != 0 ? number_format($subnetpayable, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subgm < 0 ? '(' . number_format(abs($subgm), 2) . ')' : ($subgm != 0 ? number_format($subgm, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $discc = $totaldiscount != 0 ? $totaldiscount : ' - ';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRANDTOTAL: ', '80', '', false, $border, 'T', 'LT', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '86', '', false, $border, 'T', 'L', $font, 5, '', '', '');
        $str .= $this->reporter->col($totalsld < 0 ? '(' . number_format(abs($totalsld), 2) . ')' : ($totalsld != 0 ? number_format($totalsld, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($gtotalcost < 0 ? '(' . number_format(abs($gtotalcost), 2) . ')' : ($gtotalcost != 0 ? number_format($gtotalcost, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');

        $str .= $this->reporter->col($tsrp < 0 ? '(' . number_format(abs($tsrp), 2) . ')' : ($tsrp != 0 ? number_format($tsrp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($discc < 0 ? '(' . number_format(abs($discc), 2) . ')' : ($discc != 0 ? number_format($discc, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tlsrp < 0 ? '(' . number_format(abs($tlsrp), 2) . ')' : ($tlsrp != 0 ? number_format($tlsrp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($comm1here < 0 ? '(' . number_format(abs($comm1here), 2) . ')' : ($comm1here != 0 ? number_format($comm1here, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($gross < 0 ? '(' . number_format(abs($gross), 2) . ')' : ($gross != 0 ? number_format($gross, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty3 < 0 ? '(' . number_format(abs($tqty3), 2) . ')' : ($tqty3 != 0 ? number_format($tqty3, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos3 < 0 ? '(' . number_format(abs($tmos3), 2) . ')' : ($tmos3 != 0 ? number_format($tmos3, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty6 < 0 ? '(' . number_format(abs($tqty6), 2) . ')' : ($tqty6 != 0 ? number_format($tqty6, 2) : ''), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos6 < 0 ? '(' . number_format(abs($tmos6), 2) . ')' : ($tmos6 != 0 ? number_format($tmos6, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty9 < 0 ? '(' . number_format(abs($tqty9), 2) . ')' : ($tqty9 != 0 ? number_format($tqty9, 2) : '-'), '50', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos9 < 0 ? '(' . number_format(abs($tmos9), 2) . ')' : ($tmos9 != 0 ? number_format($tmos9, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_rqty < 0 ? '(' . number_format(abs($tfinance_rqty), 2) . ')' : ($tfinance_rqty != 0 ? number_format($tfinance_rqty, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_r < 0 ? '(' . number_format(abs($tfinance_r), 2) . ')' : ($tfinance_r != 0 ? number_format($tfinance_r, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_zqty < 0 ? '(' . number_format(abs($tfinance_zqty), 2) . ')' : ($tfinance_zqty != 0 ? number_format($tfinance_zqty, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_z < 0 ? '(' . number_format(abs($tfinance_z), 2) . ')' : ($tfinance_z != 0 ? number_format($tfinance_z, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqtyst < 0 ? '(' . number_format(abs($tqtyst), 2) . ')' : ($tqtyst != 0 ? number_format($tqtyst, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmost < 0 ? '(' . number_format($tmost, 2) . ')' : ($tmost != 0 ? number_format($tmost, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($comm1here2 < 0 ? '(' . number_format(abs($comm1here2), 2) . ')' : ($comm1here2 != 0 ? number_format($comm1here2, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($netp < 0 ? '(' . number_format(abs($netp), 2) . ')' : ($netp != 0 ? number_format($netp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($gmtotal < 0 ? '(' . number_format(abs($gmtotal), 2) . ')' : ($gmtotal != 0 ? number_format($gmtotal, 2) : '-'), '50', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $font_style = 'I';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted By :', '225', '', false, $border, 'T', 'C', $font, $font_size,  $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Received By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // $str .= "<div style='position:relative; margin:145px 0 0 0'>";
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
        // $str .= "</div>";


        $str .= $this->reporter->endreport();
        $str .= '</div>';
        return $str;
    }



    public function vendor_with_item_outright($config)
    {
        $result = $this->default_query_test($config);


        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $count = 26;
        $page = 26;
        $p = 40;
        $totalsld = 0;
        $gtotalcost = 0;
        $totaldiscount = 0;
        $tlsrp = 0;
        $tlcost = 0;
        $comm1here = 0;
        $comm1here2 = 0;
        $gross = 0;
        $commm2 = 0;
        $netp = 0;
        $totalcomapp = 0;
        $gmtotal = 0;

        $tsrp = 0;

        $tqty3 = 0;
        $tqty6 = 0;
        $tqty9 = 0;

        $tmos3 = 0;
        $tmos6 = 0;
        $tmos9 = 0;

        $tmost = 0;
        $tqtyst = 0;

        $tfinance_rqty =  0;
        $tfinance_r = 0;
        $tfinance_zqty = 0;
        $tfinance_z = 0;

        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1250';
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50;margin-top:10px;margin-left:65px;');

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $dt = new DateTime($current_timestamp);
        $date = $dt->format('d-M-y');
        $username = $config['params']['user'];

        $counter = 0;
        $gm = 0;
        $supcode = "";
        $supname = "";

        $subsold = 0;
        $subcost = 0;
        $subsrp = 0;
        $subdiscount = 0;
        $subamt = 0;
        $subcomm1 = 0;
        $subgrosspayable = 0;
        $subdisplay_mos3 = 0;
        $subdisp_qty3 = 0;

        $subdisplay_mos6 = 0;
        $subdisp_qty6 = 0;
        $subdisplay_mos9 = 0;
        $subdisp_qty9 = 0;
        $subdisp_financezeroqty = 0;
        $subdisp_financezero = 0;
        $subdisp_financeregularqty = 0;
        $subdisp_financeregular = 0;
        $subdisplay_mos = 0;
        $subdisp_qtyst = 0;
        $subcomm2 = 0;
        $subnetpayable = 0;
        $subgm = 0;
        $showfooter = false;
        $i = 0;
        foreach ($result as $key => $data) {
            $i++;

            if ($supcode == "") {
                $str .= "<div style='position:relative; margin:0 0 80px 0'>";
                $str .= $this->vendor_with_item_header($config, $data->supcode, $data->suppliername);
            }
            if ($counter >= 37) {
                goto pbreak;
            }

            if ($supcode != "" && $supcode == $data->supcode) {
                $supcode = "";
                $supname = "";
            } else {
                if ($supcode != "" && $supcode != $data->supcode) {
                    pbreak:
                    $counter = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1200', '', false, '1px dotted', 'T', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('SUBTOTAL:', '80', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col('', '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subsold != 0 ? number_format($subsold, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subcost != 0 ? number_format($subcost, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subsrp != 0 ? number_format($subsrp, 2) : '-', '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdiscount != 0 ? number_format(abs($subdiscount), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subamt != 0 ? number_format($subamt, 2) : '-', '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subcomm1 < 0 ? '(' . number_format(abs($subcomm1), 2) . ')' : ($subcomm1 != 0 ? number_format($subcomm1, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subgrosspayable < 0 ? '(' . number_format($subgrosspayable, 2) . ')' : ($subgrosspayable != 0 ? number_format($subgrosspayable, 2) : '-'), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty3 < 0 ? '(' . number_format(abs($subdisp_qty3), 2) . ')' : ($subdisp_qty3 != 0 ? number_format($subdisp_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos3 < 0 ? '(' . number_format(abs($subdisplay_mos3), 2) . ')' : ($subdisplay_mos3 != 0 ? number_format($subdisplay_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty6 < 0 ? '(' . number_format(abs($subdisp_qty6), 2) . ')' : ($subdisp_qty6 != 0 ? number_format($subdisp_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos6 < 0 ? '(' . number_format(abs($subdisplay_mos6), 2) . ')' : ($subdisplay_mos6 != 0 ? number_format($subdisplay_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qty9 < 0 ? '(' . number_format(abs($subdisp_qty9), 2) . ')' : ($subdisp_qty9 != 0 ? number_format($subdisp_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos9 < 0 ? '(' . number_format(abs($subdisplay_mos9), 2) . ')' : ($subdisplay_mos9 != 0 ? number_format($subdisplay_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financezeroqty < 0 ? '(' . number_format(abs($subdisp_financezeroqty), 2) . ')' : ($subdisp_financezeroqty != 0 ? number_format($subdisp_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financezero < 0 ? '(' . number_format(abs($subdisp_financezero), 2) . ')' : ($subdisp_financezero != 0 ? number_format($subdisp_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financeregularqty < 0 ? '(' . number_format(abs($subdisp_financeregularqty), 2) . ')' : ($subdisp_financeregularqty != 0 ? number_format($subdisp_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_financeregular < 0 ? '(' . number_format(abs($subdisp_financeregular), 2) . ')' : ($subdisp_financeregular != 0 ? number_format($subdisp_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisp_qtyst < 0 ? '(' . number_format(abs($subdisp_qtyst), 2) . ')' : ($subdisp_qtyst != 0 ? number_format($subdisp_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subdisplay_mos < 0 ? '(' . number_format(abs($subdisplay_mos), 2) . ')' : ($subdisplay_mos != 0 ? number_format($subdisplay_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subcomm2 < 0 ? '(' . number_format(abs($subcomm2), 2) . ')' : ($subcomm2 != 0 ? number_format($subcomm2, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($subnetpayable < 0 ? '(' . number_format(abs($subnetpayable), 2) . ')' : ($subnetpayable != 0 ? number_format($subnetpayable, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $subsold = 0;
                    $subcost = 0;
                    $subsrp = 0;
                    $subdiscount = 0;
                    $subamt = 0;
                    $subcomm1 = 0;
                    $subgrosspayable = 0;
                    $subdisplay_mos3 = 0;
                    $subdisp_qty3 = 0;

                    $subdisplay_mos6 = 0;
                    $subdisp_qty6 = 0;
                    $subdisplay_mos9 = 0;
                    $subdisp_qty9 = 0;
                    $subdisp_financezeroqty = 0;
                    $subdisp_financezero = 0;
                    $subdisp_financeregularqty = 0;
                    $subdisp_financeregular = 0;
                    $subdisplay_mos = 0;
                    $subdisp_qtyst = 0;
                    $subcomm2 = 0;
                    $subnetpayable = 0;
                    $subgm = 0;
                    $str .= "</div>";

                    $str .= $this->reporter->page_break();
                    $str .= $this->footer_homeworks($config, $layoutsize, $date, $username, $last = false);
                    $str .= "<div style='position:relative; margin:0 0 80px 0'>";
                    // $str .= $this->footer_homeworks($config, $layoutsize, $date, $username, $last = false);
                    $str .= $this->vendor_with_item_header($config, $data->supcode, $data->suppliername);
                    // $showfooter = true;
                }
                $supcode = $data->supcode;
                $supname = $data->suppliername;
            }




            $len = strlen($data->itemdesc);
            $maxlen = ceil($len / 12);
            $counter += $maxlen;
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

            $comm1 = $data->comm1;
            $cost = $data->cost;
            $cost = ($cost != 0) ? $cost : 0;


            // $comap      = ($data->totalcost != 0) ? floatval($data->totalcost) : 0;
            // $grossap2   = ($grossap2s != 0) ? floatval($grossap2s) : 0;


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($supname, '1200', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();



            $sold = $totalsold;
            $discost = $cost;
            $srp = $data->srp;
            $dis =  $discount;
            $ammt = $tlsrp;
            $comm1dis = $comm1;
            $grosspay = $grosspayable;
            $dis_mos3 = $display_mos3;
            $dis_qty3 = $disp_qty3;
            $dis_mos6 = $display_mos6;
            $dis_qty6 = $disp_qty6;
            $dis_mos9 = $display_mos9;
            $dis_qty9 = $disp_qty9;
            $dis_financezeroqty = $disp_financezeroqty;
            $dis_financezero = $disp_financezero;
            $dis_financeregularqty = $disp_financeregularqty;
            $dis_financeregular = $disp_financeregular;
            $dis_mos = $display_mos;
            $dis_qtyst = $disp_qtyst;
            $com2 = $grossap2s;


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->barcode, '80', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->itemdesc, '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($sold != 0 ? number_format($sold, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($cost != 0 ? number_format($discost, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($srp != 0 ? number_format($srp, 2) : '-', '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($discount != 0 ? number_format(abs($dis), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($tlsrp != 0 ? number_format($ammt, 2) : '-', '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($comm1 < 0 ? '(' . number_format(abs($comm1dis), 2) . ')' : ($comm1 != 0 ? number_format($comm1dis, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grosspayable != 0 ? number_format($grosspay, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty3 < 0 ? '(' . number_format(abs($dis_qty3), 2) . ')' : ($disp_qty3 != 0 ? number_format($dis_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos3 < 0 ? '(' . number_format(abs($dis_mos3), 2) . ')' : ($display_mos3 != 0 ? number_format($dis_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty6 < 0 ? '(' . number_format(abs($dis_qty6), 2) . ')' : ($disp_qty6 != 0 ? number_format($dis_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos6  < 0 ? '(' . number_format(abs($dis_mos6), 2) . ')' : ($display_mos6 != 0 ? number_format($dis_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qty9 < 0 ? '(' . number_format(abs($dis_qty9), 2) . ')' : ($disp_qty9 != 0 ? number_format($dis_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos9 < 0 ? '(' . number_format(abs($dis_mos9), 2) . ')' : ($display_mos9 != 0 ? number_format($dis_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financezeroqty < 0 ? '(' . number_format(abs($dis_financezeroqty), 2) . ')' : ($disp_financezeroqty != 0 ? number_format($dis_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financezero < 0 ? '(' . number_format(abs($dis_financezero), 2) . ')' : ($disp_financezero != 0 ? number_format($dis_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financeregularqty  < 0 ? '(' . number_format(abs($dis_financeregularqty), 2) . ')' : ($disp_financeregularqty != 0 ? number_format($dis_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_financeregular < 0 ? '(' . number_format(abs($dis_financeregular), 2) . ')' : ($disp_financeregular != 0 ? number_format($dis_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_qtyst < 0 ? '(' . number_format(abs($dis_qtyst), 2) . ')' : ($disp_qtyst != 0 ? number_format($dis_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($display_mos < 0 ? '(' . number_format(abs($dis_mos), 2) . ')' : ($display_mos != 0 ?  number_format($dis_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($grossap2s < 0 ? '(' . number_format(abs($com2), 2) . ')' : ($grossap2s != 0 ? number_format($grossap2s, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($disp_netpayable < 0 ? '(' . number_format($disp_netpayable, 2) . ')' : ($disp_netpayable != 0 ? number_format($disp_netpayable, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');


            $str .= $this->reporter->col($data->pricetype != '' ? $data->pricetype : '-', '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            // subtotal
            $subsold += $sold;
            $subcost += $cost;
            $subsrp += $srp;
            $subdiscount += $discount;
            $subamt += $tlsrp;
            $subcomm1 += $comm1;
            $subgrosspayable += $grosspayable;
            $subdisplay_mos3 += $display_mos3;
            $subdisp_qty3 += $disp_qty3;

            $subdisplay_mos6 += $display_mos6;
            $subdisp_qty6 += $disp_qty6;
            $subdisplay_mos9 += $display_mos9;
            $subdisp_qty9 += $disp_qty9;
            $subdisp_financezeroqty += $disp_financezeroqty;
            $subdisp_financezero += $disp_financezero;
            $subdisp_financeregularqty += $disp_financeregularqty;
            $subdisp_financeregular += $disp_financeregular;
            $subdisplay_mos += $display_mos;
            $subdisp_qtyst += $disp_qtyst;
            $subcomm2 += $grossap2s;
            $subnetpayable += $netpayable;
            $subgm += $gm;

            // grandtotal
            $totalsld = $totalsld + $data->totalsold;
            $gtotalcost += $data->cost;
            $totaldiscount = $totaldiscount + $data->disc;
            $tlsrp = $tlsrp + $data->ext;
            $tsrp = $tsrp + $data->srp;
            $tlcost = $tlcost + $data->totalcost;
            $comm1here = $comm1here + $comm1;
            $comm1here2 = $comm1here2 + $grossap2s;
            $gross = $gross + $data->grosspayable;

            $tqty3 = $tqty3 + $qty3;
            $tmos3 = $tmos3 + $mos3;

            $tqty6 = $tqty6 + $qty6;
            $tmos6 = $tmos6 + $mos6;

            $tqty9 = $tqty9 + $qty9;
            $tmos9 = $tmos9 + $mos9;

            $tmost = $tmost + $most;
            $tqtyst = $tqtyst + $qtyst;

            $tfinance_rqty =  $tfinance_rqty + $financeregularqty;
            $tfinance_r = $tfinance_r + $financeregular;
            $tfinance_zqty = $tfinance_zqty  + $financezeroqty;
            $tfinance_z = $tfinance_z + $financezero;

            $netp = $netp + $netpayable;
            $gmtotal += $gm;
            $supcode = $data->supcode;
            $supname = $data->suppliername;
        } //end foreach

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1200', '', false, '1px dotted', 'T', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $gtotalcost
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUBTOTAL:', '80', '', false, $border, '', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '86', '', false, $border, '', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subsold != 0 ? number_format($subsold, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subcost != 0 ? number_format($subcost, 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subsrp != 0 ? number_format($subsrp, 2) : '-', '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdiscount != 0 ? number_format(abs($subdiscount), 2) : '-', '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subamt != 0 ? number_format($subamt, 2) : '-', '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subcomm1 < 0 ? '(' . number_format(abs($subcomm1), 2) . ')' : ($subcomm1 != 0 ? number_format($subcomm1, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subgrosspayable < 0 ? '(' . number_format($subgrosspayable, 2) . ')' : ($subgrosspayable != 0 ? number_format($subgrosspayable, 2) : '-'), '50', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty3 < 0 ? '(' . number_format(abs($subdisp_qty3), 2) . ')' : ($subdisp_qty3 != 0 ? number_format($subdisp_qty3, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos3 < 0 ? '(' . number_format(abs($subdisplay_mos3), 2) . ')' : ($subdisplay_mos3 != 0 ? number_format($subdisplay_mos3, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty6 < 0 ? '(' . number_format(abs($subdisp_qty6), 2) . ')' : ($subdisp_qty6 != 0 ? number_format($subdisp_qty6, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos6 < 0 ? '(' . number_format(abs($subdisplay_mos6), 2) . ')' : ($subdisplay_mos6 != 0 ? number_format($subdisplay_mos6, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qty9 < 0 ? '(' . number_format(abs($subdisp_qty9), 2) . ')' : ($subdisp_qty9 != 0 ? number_format($subdisp_qty9, 2) : '-'), '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos9 < 0 ? '(' . number_format(abs($subdisplay_mos9), 2) . ')' : ($subdisplay_mos9 != 0 ? number_format($subdisplay_mos9, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financezeroqty < 0 ? '(' . number_format(abs($subdisp_financezeroqty), 2) . ')' : ($subdisp_financezeroqty != 0 ? number_format($subdisp_financezeroqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financezero < 0 ? '(' . number_format(abs($subdisp_financezero), 2) . ')' : ($subdisp_financezero != 0 ? number_format($subdisp_financezero, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financeregularqty < 0 ? '(' . number_format(abs($subdisp_financeregularqty), 2) . ')' : ($subdisp_financeregularqty != 0 ? number_format($subdisp_financeregularqty, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_financeregular < 0 ? '(' . number_format(abs($subdisp_financeregular), 2) . ')' : ($subdisp_financeregular != 0 ? number_format($subdisp_financeregular, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisp_qtyst < 0 ? '(' . number_format(abs($subdisp_qtyst), 2) . ')' : ($subdisp_qtyst != 0 ? number_format($subdisp_qtyst, 2) : '-'), '49', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subdisplay_mos < 0 ? '(' . number_format(abs($subdisplay_mos), 2) . ')' : ($subdisplay_mos != 0 ? number_format($subdisplay_mos, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subcomm2 < 0 ? '(' . number_format(abs($subcomm2), 2) . ')' : ($subcomm2 != 0 ? number_format($subcomm2, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($subnetpayable < 0 ? '(' . number_format(abs($subnetpayable), 2) . ')' : ($subnetpayable != 0 ? number_format($subnetpayable, 2) : '-'), '49', '', false, $border, '', 'RT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, '', 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $discc = $totaldiscount != 0 ? $totaldiscount : ' - ';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRANDTOTAL: ', '80', '', false, $border, 'T', 'LT', $font, 7, 'B', '', '');
        $str .= $this->reporter->col('', '86', '', false, $border, 'T', 'L', $font, 5, '', '', '');
        $str .= $this->reporter->col($totalsld < 0 ? '(' . number_format(abs($totalsld), 2) . ')' : ($totalsld != 0 ? number_format($totalsld, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($gtotalcost < 0 ? '(' . number_format(abs($gtotalcost), 2) . ')' : ($gtotalcost != 0 ? number_format($gtotalcost, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tsrp < 0 ? '(' . number_format(abs($tsrp), 2) . ')' : ($tsrp != 0 ? number_format($tsrp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($discc < 0 ? '(' . number_format(abs($discc), 2) . ')' : ($discc != 0 ? number_format($discc, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tlsrp < 0 ? '(' . number_format(abs($tlsrp), 2) . ')' : ($tlsrp != 0 ? number_format($tlsrp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($comm1here < 0 ? '(' . number_format(abs($comm1here), 2) . ')' : ($comm1here != 0 ? number_format($comm1here, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($gross < 0 ? '(' . number_format(abs($gross), 2) . ')' : ($gross != 0 ? number_format($gross, 2) : '-'), '50', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty3 < 0 ? '(' . number_format(abs($tqty3), 2) . ')' : ($tqty3 != 0 ? number_format($tqty3, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos3 < 0 ? '(' . number_format(abs($tmos3), 2) . ')' : ($tmos3 != 0 ? number_format($tmos3, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty6 < 0 ? '(' . number_format(abs($tqty6), 2) . ')' : ($tqty6 != 0 ? number_format($tqty6, 2) : ''), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos6 < 0 ? '(' . number_format(abs($tmos6), 2) . ')' : ($tmos6 != 0 ? number_format($tmos6, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqty9 < 0 ? '(' . number_format(abs($tqty9), 2) . ')' : ($tqty9 != 0 ? number_format($tqty9, 2) : '-'), '50', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmos9 < 0 ? '(' . number_format(abs($tmos9), 2) . ')' : ($tmos9 != 0 ? number_format($tmos9, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_zqty < 0 ? '(' . number_format(abs($tfinance_zqty), 2) . ')' : ($tfinance_zqty != 0 ? number_format($tfinance_zqty, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_z < 0 ? '(' . number_format(abs($tfinance_z), 2) . ')' : ($tfinance_z != 0 ? number_format($tfinance_z, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_rqty < 0 ? '(' . number_format(abs($tfinance_rqty), 2) . ')' : ($tfinance_rqty != 0 ? number_format($tfinance_rqty, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tfinance_r < 0 ? '(' . number_format(abs($tfinance_r), 2) . ')' : ($tfinance_r != 0 ? number_format($tfinance_r, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($tqtyst < 0 ? '(' . number_format(abs($tqtyst), 2) . ')' : ($tqtyst != 0 ? number_format($tqtyst, 2) : '-'), '49', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->col($tmost < 0 ? '(' . number_format($tmost, 2) . ')' : ($tmost != 0 ? number_format($tmost, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($comm1here2 < 0 ? '(' . number_format(abs($comm1here2), 2) . ')' : ($comm1here2 != 0 ? number_format($comm1here2, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col($netp < 0 ? '(' . number_format(abs($netp), 2) . ')' : ($netp != 0 ? number_format($netp, 2) : '-'), '49', '', false, $border, 'T', 'R', $font, 7, '', '', '');
        $str .= $this->reporter->col('', '50', '', false, $border, 'T', 'C', $font, 7, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $font_style = 'I';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Audited By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Noted By :', '225', '', false, $border, 'T', 'C', $font, $font_size,  $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Approved By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Received By :', '225', '', false, $border, 'T', 'C', $font, $font_size, $font_style, '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // $str .= "<div style='position:relative; margin:145px 0 0 0'>";
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
        // $str .= "</div>";


        $str .= $this->reporter->endreport();
        $str .= '</div>';
        return $str;
    }


    public function footer_homeworks($config, $layoutsize, $date, $username, $last)
    {

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '7';
        $str = '';


        $str .= "<div style='position:relative; margin:-90px 0 0 0'>";



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
        $str .= "</div>";
        $str .= '<br/>';
        return $str;
    }
}//end class