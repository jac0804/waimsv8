<?php

namespace App\Http\Classes\modules\reportlist\items;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class item_history_per_supplier
{
    public $modulename = 'Item History Per Supplier Report ';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'start', 'end', 'dwhname', 'luom', 'dclientname', 'ditemname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dwhname.lookupclass', 'lookupwhfilter');
        data_set($col1, 'dwhname.action', 'lookupwhfilter');
        data_set($col1, 'dwhname.required', true);
        data_set($col1, 'luom.action', 'replookupuom');
        data_set($col1, 'luom.required', true);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $user = $config['params']['user'];
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$user]);

        $data[0]['wh'] = $this->companysetup->getwh($config['params']);
        $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data[0]['wh']]);
        $whname= $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$whid]);

        $paramstr = "select
    'default' as print,
    '" . $this->othersClass->getCurrentDate() . "' as start,
    '" . $this->othersClass->getCurrentDate() . "' as end,
    '" . $data[0]['wh'] . "' as wh,
    '" . $whid . "' as whid,
    '" . $whname . "'  as whname,
    '" . $data[0]['wh'] . '~' . $whname  . "' as dwhname,
    '' as loc,
    '' as dclientname,
    '' as clientname,
    0 as clientid,
    0 as supplierid,
    '' as itemname,
    '' as ditemname,
    '' as client,
    '' as uom,
    '' as barcode,
    '' as approved,
    '' as received";
        return $this->coreFunctions->opentable($paramstr);
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        return $this->reportDefault_Layout($config);
    }

    public function reportDefault_query($config)
    {
        ini_set('memory_limit', '-1');

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $whby       = $config['params']['dataparams']['wh'];
        $whid       = $config['params']['dataparams']['whid'];
        $uom        = $config['params']['dataparams']['uom'];
        $location   = $config['params']['dataparams']['loc'];
        $supplier   = $config['params']['dataparams']['clientname'];
        $supplierid = $config['params']['dataparams']['clientid'];
        $itemname   = $config['params']['dataparams']['itemname'];

        $filter = "";

        if ($supplier != "") {
            $filter .= " and client.clientid = '$supplierid'";
        }

        if ($whby != "") {
            $filter .= " and stock.whid = '$whid'";
        }

        if ($location != "") {
            $filter .= " and stock.loc = '$location'";
        }

        if ($itemname != "") {
            $filter .= " and item.itemname = '$itemname'";
        }

        $query = "
        select itemname, barcode, 0 as trno, 'beginning bal.' as docno, null as dateid,
               0 as qty, 0 as iss, 0 as isqty2, '' as rem, '' as clientname,
               sum(qty - iss) as bal
        from (
            select item.itemid, item.itemname, item.barcode,
                   round(ifnull(stock.qty * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as qty,
                   round(ifnull(stock.iss * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as iss
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom='$uom'
            left join client on client.clientid=item.supplier
            where head.dateid < '$start' $filter

            union all

            select item.itemid, item.itemname, item.barcode,
                   round(ifnull(stock.qty * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as qty,
                   round(ifnull(stock.iss * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as iss
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom='$uom'
            left join client on client.clientid=item.supplier
            where head.dateid < '$start' $filter
        ) as ledger
        group by ledger.itemid, ledger.barcode, ledger.itemname

        union all

        select item.itemname, item.barcode, head.trno, head.docno, left(head.dateid,10) as dateid,
               round(ifnull(stock.qty * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as qty,
               round(ifnull(stock.iss * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as iss,
               stock.isqty2, stock.rem, client.clientname, 0 as bal
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom='$uom'
        left join client on client.clientid=item.supplier
        where head.dateid between '$start' and '$end' $filter

        union all

        select item.itemname, item.barcode, head.trno, head.docno, left(head.dateid,10) as dateid,
               round(ifnull(stock.qty * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as qty,
               round(ifnull(stock.iss * if(ifnull(uom.factor,0)=0, 1, if(uom.factor<=1, uom.factor, 1/uom.factor)),0),4) as iss,
               stock.isqty2, stock.rem, client.clientname, 0 as bal
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom='$uom'
        left join client on client.clientid=item.supplier
        where head.dateid between '$start' and '$end' $filter

        order by itemname, dateid, trno
    ";

        logger($query); // uncomment to debug
        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $p = $config['params']['dataparams'];
        $start = date("F-j-Y", strtotime($p['start']));
        $end = date("F-j-Y", strtotime($p['end']));
        $uom = $p['uom'];
        $wh = $p['wh'];
        $whname = isset($p['whname']) ? $p['whname'] : '';
        $supplier = $p['dclientname'] != '' ? $p['dclientname'] : 'All';

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);

        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, null, null, 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, null, null, 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->tel, null, null, false, null, null, 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM HISTORY PER SUPPLIER', null, null, false, '10px solid ', '', 'L', $font, '15', 'B', '#800000');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE RANGE: ' . $start . ' TO ' . $end, '500', null, false, '', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('UOM: ' . $uom, '500', null, false, '', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WAREHOUSE: ' . $whname . '~' . $wh, '500', null, false, '', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('SUPPLIER: ' . $supplier, '500', null, false, '', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '80', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT#', '180', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '300', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('QTY IN', '80', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('QTY OUT', '80', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('OOS QTY', '80', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '90', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('PARTICULAR', '110', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    // negative numbers are shown in parentheses, e.g. (3.00)
    private function fmt($n, $dec = 2)
    {
        return $n < 0 ? '(' . number_format(abs($n), $dec) . ')' : number_format($n, $dec);
    }

    public function reportDefault_Layout($config)
    {
        $str = '';
        $companyid = $config['params']['companyid'];
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "11";
        $this->reporter->linecounter = 0;

        $dec = 2;

        $result = $this->reportDefault_query($config);

        $withTrans = [];
        foreach ($result as $r) {
            if ($r->docno != 'beginning bal.') {
                $withTrans[$r->itemname] = true;
            }
        }
        $result = array_filter($result, function ($r) use ($withTrans) {
            return isset($withTrans[$r->itemname]);
        });

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $limitPerPage = 45;   // rows per page, adjust after testing
        $rowCount = 0;

        $resultArray = array_values($result);
        $totalRows = count($resultArray);

        $currentItem = '';
        $bal = 0;
        $totalin = 0;
        $totalout = 0;
        $totaloos = 0;
        $grandin = 0;
        $grandout = 0;
        $grandoos = 0;
        $grandbal = 0;

        for ($i = 0; $i < $totalRows; $i++) {

            $row = $resultArray[$i];
            $next = ($i < $totalRows - 1) ? $resultArray[$i + 1] : null;
            $isBeg = ($row->docno == 'beginning bal.');

            // skip beginning balance of items that have no transaction in the date range
            if ($isBeg && ($next == null || $next->itemname != $row->itemname || $next->docno == 'beginning bal.')) {
                continue;
            }

            if ($rowCount > 0 && $rowCount + 4 > $limitPerPage) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $str .= $this->reporter->begintable($layoutsize);
                $rowCount = 0;
            }

            if ($row->itemname != $currentItem) {
                $currentItem = $row->itemname;
                $bal = 0;
                $totalin = 0;
                $totalout = 0;
                $totaloos = 0;

                $title = strtoupper($row->itemname) . ($row->barcode != '' ? '  [' . $row->barcode . ']' : '');
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($title, '1000', '25', false, '', '', 'LB', $font, '12', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $rowCount++;

                if (!$isBeg) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '80', null, false, '', '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('beginning bal.', '180', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '300', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, '', '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, '', '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, '', '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col(number_format(0, $dec), '90', null, false, '', '', 'RT', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('', '110', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $rowCount++;
                }
            }

            if ($isBeg) {
                $bal = $row->bal;
                $dispDate = '';
                $dispClient = '';
                $dispQty = '';
                $dispIss = '';
                $dispOos = '';
                $dispRem = '';
            } else {
                $bal = $bal - $row->iss - $row->isqty2 + $row->qty;
                $totalin += $row->qty;
                $totalout += $row->iss;
                $totaloos += $row->isqty2;
                $dispDate = $row->dateid;
                $dispClient = $row->clientname;
                $dispQty = number_format($row->qty, $dec);
                $dispIss = number_format($row->iss, $dec);
                $dispOos = number_format($row->isqty2, $dec);
                $dispRem = $row->rem;
            }

            $rowStyle = $isBeg ? 'B' : '';

            // ITEM ROW
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($dispDate, '80', null, false, '', '', 'CT', $font, $fontsize, $rowStyle, '', '');
            $str .= $this->reporter->col($row->docno, '180', null, false, '', '', 'LT', $font, $fontsize, $rowStyle, '', '');
            $str .= $this->reporter->col($dispClient, '300', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dispQty, '80', null, false, '', '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dispIss, '80', null, false, '', '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dispOos, '80', null, false, '', '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($this->fmt($bal, $dec), '90', null, false, '', '', 'RT', $font, $fontsize, $rowStyle, '', '');
            $str .= $this->reporter->col($dispRem, '110', null, false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $rowCount++;

            // item total row when the item group ends
            $isLastOfGroup = ($next == null) || ($next->itemname != $currentItem);

            if ($isLastOfGroup) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '80', null, false, '', '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '180', null, false, '', '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('ITEM TOTAL : ', '300', null, false, '1px solid', 'T', 'RT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalin, $dec), '80', null, false, '1px solid', 'T', 'RT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalout, $dec), '80', null, false, '1px solid', 'T', 'RT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($totaloos, $dec), '80', null, false, '1px solid', 'T', 'RT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($this->fmt($bal, $dec), '90', null, false, '1px solid', 'T', 'RT', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '110', null, false, '', '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $rowCount++;

                $grandin += $totalin;
                $grandout += $totalout;
                $grandoos += $totaloos;
                $grandbal += $bal;
            }
        }

        // GRAND TOTAL
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', '30', false, '', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '180', '30', false, '', '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL : ', '300', '30', false, '3px solid', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandin, $dec), '80', '30', false, '3px solid', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandout, $dec), '80', '30', false, '3px solid', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandoos, $dec), '80', '30', false, '3px solid', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($this->fmt($grandbal, $dec), '90', '30', false, '3px solid', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '110', '30', false, '', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class