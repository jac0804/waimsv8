<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\pc;
use App\Http\Classes\sqlquery;
use Exception;

use Datetime;
use Carbon\Carbon;

class loadinventorywithbal
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;
    private $sqlquery;

    public $modulename = 'LOAD INVENTORY WITH BALANCE';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'transnum';
    private $head = 'pchead';
    private $hhead = 'hpchead';
    private $stock = 'pcstock';
    private $hstock = 'hpcstock';
    private $infotable = 'headinfotrans';
    private $hinfotable = 'hheadinfotrans';
    public $dqty = 'rrqty';
    public $damt = 'rrcost';

    public $tablelogs = 'transnum_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];

        switch ($companyid) {
            case 14: //majesty
                switch ($doc) {
                    case 'PC':
                        $fields = ['sizeid', 'partname', 'stockgrp', 'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'sizeid.label', 'Bin');
                        data_set($col1, 'sizeid.class', 'sbccsreadonly');
                        data_set($col1, 'partname.label', 'Principal');
                        data_set($col1, 'stockgrp.label', 'Divsion');
                        data_set($col1, 'stockgrp.lookupclass', 'lookupgroup_loadinventorywithbalance');

                        data_set($col1, 'refresh.label', 'LOAD INVENTORY');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                }
                break;
            case 17: //unihome
            case 27: //nte
            case 36: //rozlab
            case 39: //CBBSI
                switch ($doc) {
                    case 'PC':
                        $fields = ['class', 'brandname', 'divsion', 'categoryname', 'subcatname', 'refresh'];
                        $col1 = $this->fieldClass->create($fields);
                        data_set($col1, 'class.lookupclass', 'lookupclass_loadinventorywithbalance');
                        data_set($col1, 'brandname.lookupclass', 'lookupbrand_loadinventorywithbalance');
                        data_set($col1, 'divsion.lookupclass', 'lookupgroup_loadinventorywithbalance');
                        data_set($col1, 'categoryname.lookupclass', 'lookupcategory_loadinventorywithbalance');
                        data_set($col1, 'subcatname.lookupclass', 'lookupsubcat_loadinventorywithbalance');

                        data_set($col1, 'refresh.label', 'LOAD INVENTORY');

                        $fields = [];
                        $col2 = $this->fieldClass->create($fields);
                        break;
                }
                break;
        }



        return array('col1' => $col1, 'col2' =>  $col2);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];
        switch ($companyid) {
            case 14: //majesty
            case 17: //unihome
            case 27: //nte
            case 36: //rozlab
            case 39: //CBBSI
                switch ($doc) {
                    case 'PC':
                        $select = "
                        select 
                        $trno as trno,
                        '' as classid,
                        '' as classic,
                        '' as class,
                        
                        '' as brandid,
                        '' as brandname,
                        '' as brand,
                        
                        '' as sizeid,

                        '' as groupid,
                        '' as stockgrp,

                        '' as partid,
                        '' as partname,
                        
                        '' as categoryid,
                        '' as categoryname,
                        '' as category,
                        
                        '' as subcatname,
                        '' as subcat";
                        $data = $this->coreFunctions->opentable($select, [$trno]);
                        break;
                }
                break;
        }
        return $data;
    }


    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function loaddata($config)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', -1);

        $elapsedInsert = 0;
        $start = Carbon::parse($this->othersClass->getCurrentTimeStamp());

        $trno = $config['params']['dataparams']['trno'];
        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];

        $isexpiry = $this->companysetup->getisexpiry($config['params']);

        $class = isset($config['params']['dataparams']['classid']) ? $config['params']['dataparams']['classid'] : '';
        $brand = isset($config['params']['dataparams']['brandid']) ? $config['params']['dataparams']['brandid'] : '';
        $groupid = isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
        $category = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
        $subcat = isset($config['params']['dataparams']['subcat']) ? $config['params']['dataparams']['subcat'] : '';

        $sizeid = isset($config['params']['dataparams']['sizeid']) ? $config['params']['dataparams']['sizeid'] : '';
        $partid = isset($config['params']['dataparams']['partid']) ? $config['params']['dataparams']['partid'] : '';

        $filter = '';

        if ($class != "") {
            $filter = $filter . " and item.class='$class'";
        }

        if ($brand != "") {
            $filter = $filter . " and item.brand='$brand'";
        }

        if ($groupid != "") {
            $filter = $filter . " and item.groupid='$groupid'";
        }

        if ($category != "") {
            $filter = $filter . " and item.category='$category'";
        }

        if ($subcat != "") {
            $filter = $filter . " and item.subcat='$subcat'";
        }

        if ($sizeid != "") {
            $filter = $filter . " and item.sizeid='$sizeid'";
        }

        if ($partid != "") {
            $filter = $filter . " and item.part='$partid'";
        }

        $msg = 'Successfully loaded.';

        $blnInsert = false;

        $stock = [];
        switch ($companyid) {
            case 14: //majesty
            case 17: //unihome
            case 27: //nte
            case 36: //rozlab    
            case 39: //CBBSI
                switch ($doc) {
                    case 'PC':

                        $path = 'App\Http\Classes\modules\inventory\pc';

                        $header = $this->coreFunctions->opentable("select wh, date(dateid) as dateid from " . $this->head . " where trno=?", [$trno]);
                        $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$header[0]->wh]);
                        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Load Inventory');

                        if ($isexpiry) {
                            $item = $this->coreFunctions->opentable("select item.barcode, item.itemid, item.uom, item.itemname, ifnull(uom.factor,0) as factor 
                                    from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.isinactive=0 and item.barcode<>'' $filter order by item.itemname");
                        } else {
                            $item = $this->coreFunctions->opentable($this->getbalbydateqry('', $header[0]->wh, '', '', $header[0]->dateid, $filter));
                        }

                        $itemcount = 0;
                        if (!empty($item)) {
                            foreach ($item as $key => $value) {

                                if ($isexpiry) {
                                    $arrloc = $this->sqlquery->getbalbydateloc($value->barcode, $header[0]->wh, $header[0]->dateid);
                                    foreach ($arrloc as $key2 => $value2) {
                                        if ($value2->bal != 0) {
                                            $itemcount += 1;
                                            $config['params']['data']['uom'] = $value->uom;
                                            $config['params']['data']['itemid'] = $value->itemid;
                                            $config['params']['trno'] = $trno;
                                            $config['params']['data']['disc'] = '';
                                            $config['params']['data']['wh'] = $header[0]->wh;
                                            $config['params']['data']['loc'] = $value2->loc;

                                            $tempexpiry = $value2->expiry;

                                            $dateformatcount = 0;

                                            ConfirmBalHere:
                                            $confirmbal = $this->coreFunctions->opentable("select ifnull(sum(rs.bal),0) as value, rs.expiry from rrstatus as rs left join client on client.clientid=rs.whid
                                                where rs.itemid=" . $value->itemid . " and client.client='" . $header[0]->wh . "' and rs.loc='" . $value2->loc . "' and rs.expiry='" . $tempexpiry . "' group by rs.expiry having ifnull(sum(rs.bal),0)>0");

                                            $this->othersClass->logConsole(count($confirmbal));

                                            if (count($confirmbal) == 0) {
                                                if ($dateformatcount <= 2) {
                                                    if ($this->othersClass->validateDate($tempexpiry, "Y-m-d")) {
                                                        $expiry1 = date_format(date_create($tempexpiry), "Y-m-d");
                                                        $tempexpiry = $expiry1;
                                                        $dateformatcount += 1;
                                                        goto ConfirmBalHere;
                                                    } else {
                                                        if ($this->othersClass->validateDate($tempexpiry, "Y/m/d")) {
                                                            $expiry1 = date_format(date_create($tempexpiry), "Y-m-d");
                                                            $tempexpiry = $expiry1;
                                                            $dateformatcount += 2;
                                                            goto ConfirmBalHere;
                                                        }
                                                    }
                                                }
                                            }

                                            $config['params']['data']['expiry'] = $tempexpiry;

                                            $this->coreFunctions->LogConsole('expiry: ' . $value2->bal);

                                            $balbase = 0;
                                            if ($value->factor == 0) {
                                                $value->factor = 1;
                                            }
                                            $balbase = $value2->bal / $value->factor;

                                            $this->coreFunctions->LogConsole('expiry bal: ' . $balbase);

                                            $config['params']['data']['oqty'] = $balbase;
                                            $config['params']['data']['amt'] = '0';
                                            $config['params']['data']['qty'] = '0';

                                            $config['params']['barcode'] = $value->barcode;
                                            $config['params']['wh'] = $header[0]->wh;
                                            $config['params']['client'] = '';
                                            $config['params']['loc'] = $config['params']['data']['loc'];
                                            $cost = app($path)->getlatestprice($config);
                                            if (!empty($cost['data'])) {
                                                $config['params']['data']['amt'] = $cost['data'][0]->amt * $value->factor;
                                            }

                                            $result = app($path)->additem("insert", $config);
                                            $blnInsert = true;
                                        }
                                    }
                                } else {

                                    $bal = $value->bal;
                                    if ($bal > 0) {
                                        $itemcount += 1;
                                        // $config['params']['data']['uom'] = $value->uom;
                                        // $config['params']['data']['itemid'] = $value->itemid;
                                        // $config['params']['trno'] = $trno;
                                        // $config['params']['data']['disc'] = '';
                                        // $config['params']['data']['wh'] = $header[0]->wh;
                                        // $config['params']['data']['loc'] = '';

                                        // $this->coreFunctions->LogConsole('no expiry: ' . $bal);

                                        // $balbase = $bal;
                                        // if ($value->factor == 0) {
                                        //     $value->factor = 1;
                                        // }
                                        // $balbase = $bal / $value->factor;

                                        // $this->coreFunctions->LogConsole('no expiry bal: ' . $balbase);

                                        // $config['params']['data']['oqty'] = $balbase;
                                        // $config['params']['data']['amt'] = '0';
                                        // $config['params']['data']['qty'] = '0';

                                        // $config['params']['barcode'] = $value->barcode;
                                        // $config['params']['wh'] = $header[0]->wh;
                                        // $config['params']['client'] = '';



                                        // $cost = app($path)->getlatestprice($config);
                                        // if (!empty($cost['data'])) {
                                        //     $config['params']['data']['amt'] = $cost['data'][0]->amt * $value->factor;
                                        // }

                                        // $result = app($path)->additem("insert", $config);

                                        $this->coreFunctions->LogConsole($value->barcode . ': ' . $bal);

                                        $balbase = $bal;
                                        if ($value->factor == 0) {
                                            $value->factor = 1;
                                        }
                                        $balbase = $bal / $value->factor;

                                        $factor = 1;
                                        if (!empty($item)) {
                                            $value->factor = $this->othersClass->val($value->factor);
                                            if ($value->factor !== 0) $factor = $value->factor;
                                        }

                                        $config['params']['barcode'] = $value->barcode;
                                        $config['params']['wh'] = $header[0]->wh;
                                        $config['params']['client'] = '';
                                        $config['params']['trno'] = $trno;

                                        $rrcost = 0;
                                        $cost = $this->getlatestprice($config['params']['center'], $value->itemid);
                                        if (!empty($cost['data'])) {
                                            $rrcost = $cost['data'][0]->amt * $factor;
                                        }

                                        $this->coreFunctions->LogConsole($value->barcode . ': cost ' . $rrcost);

                                        $qty = round($bal, $this->companysetup->getdecimal('qty', $config['params']));
                                        $computedata = $this->othersClass->computestock($rrcost, '', $qty, $factor);

                                        $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
                                        $line = $this->coreFunctions->datareader($qry, [$trno]);
                                        if ($line == '') {
                                            $line = 0;
                                        }
                                        $line = $line + 1;
                                        $stock = [
                                            'trno' => $trno,
                                            'line' => $line,
                                            'itemid' => $value->itemid,
                                            'uom' => $value->uom,
                                            'whid' => $whid,
                                            'rrcost' => $rrcost,
                                            'cost' => number_format($computedata['amt'], $this->companysetup->getdecimal('price', $config['params']), '.', ''),
                                            'oqty' => $balbase
                                        ];

                                        $this->coreFunctions->sbcinsert(app($path)->stock, $stock);

                                        $blnInsert = true;
                                    }
                                }
                            }

                            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Total items:' . $itemcount);

                            $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
                            $elapsedInsert = $start->diffInSeconds($end);

                            $stock = app($path)->openstock($trno, $config);
                        }
                        break;
                }
                break;
        }

        $status = true;
        if (!$blnInsert) {
            $status = false;
            $msg = 'Nothing to insert, all items with balance already added.';
        }

        $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
        $elapsed = $start->diffInSeconds($end);

        return ['status' => $status, 'msg' => $msg, 'data' => [], 'reloadgriddata' => ['inventory' => $stock], 'execTime' => $elapsed . 's', 'execTimeInsert' => $elapsedInsert . 's'];
    }


    public function getbalbydateqry($barcode, $wh, $loc, $expiry, $date, $itemfilter)
    {

        // $item = $this->coreFunctions->opentable("select item.barcode, item.itemid, item.uom, item.itemname, ifnull(uom.factor,0) as factor 
        //                 from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.isinactive=0 and item.barcode<>'' $filter order by item.itemname");

        $filter = '';
        if ($expiry != null || $expiry != '') {
            // $filter = " and rrstatus.expiry = '" . $expiry . "' ";

            // $this->coreFunctions->sbclogger($expiry);

            if ($this->othersClass->validateDate($expiry) || $this->othersClass->validateDate($expiry, 'Y/m/d')) {
                $expiry1 = date_format(date_create($expiry), "Y-m-d");
                $expiry2 = date_format(date_create($expiry), "Y/m/d");
                $filter = " and (stock.expiry = '" . $expiry1 . "' or stock.expiry = '" . $expiry2 . "')";
            } else {
                $filter = " and stock.expiry = '" . $expiry . "' ";
            }
        }

        $sql = "select item.barcode, item.itemid, item.uom, item.itemname, ifnull(uom.factor,0) as factor, ifnull(sum(t.qty-t.iss),0) as bal from (
                select item.itemid,0 as qty,sum(stock.iss) as iss from lahead as head left join lastock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid
                where item.isinactive=0 and wh.client='" . $wh . "' and stock.loc='" . $loc . "' and head.dateid <= '" . $date . "'" . $filter . $itemfilter . "
                group by item.itemid
                union all 
                select item.itemid,sum(stock.qty) as qty,sum(stock.iss) as iss from glhead as head left join glstock as stock on stock.trno=head.trno 
                left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid 
                where item.isinactive=0 and wh.client='" . $wh . "' and stock.loc='" . $loc . "' and head.dateid <='" . $date . "'" . $filter . $itemfilter . "
                group by item.itemid) as t left join item on item.itemid=t.itemid left join uom on uom.itemid=item.itemid and uom.uom=item.uom
                group by item.barcode, item.itemid, item.uom, item.itemname, ifnull(uom.factor,0)
                having ifnull(sum(t.qty-t.iss),0)>0
                order by item.itemname";
        // $this->coreFunctions->LogConsole($sql);
        return $sql;
    } //end function

    public function getlatestprice($center, $itemid)
    {
        $qry = "select amt, disc from (
            select head.dateid, stock.cost as amt, stock.disc
            from lahead as head left join lastock as stock on stock.trno = head.trno left join cntnum on cntnum.trno=head.trno 
            where cntnum.center = ? and stock.itemid = ? and stock.rrcost <> 0 
            UNION ALL
            select stock.dateid,stock.cost as amt, stock.disc 
            from rrstatus as stock left join cntnum on cntnum.trno=stock.trno
            where cntnum.center = ? and stock.itemid = ? and stock.cost <> 0
            ) as c order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $itemid, $center, $itemid]);

        return ['data' => $data];
    }
}
