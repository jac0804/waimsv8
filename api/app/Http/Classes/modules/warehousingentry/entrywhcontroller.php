<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use Exception;

class entrywhcontroller
{

    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'ITEM DETAILS';
    public $gridname = 'inventory';
    private $fields = ['barcode', 'itemname'];
    private $table = 'lastock';

    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $stock = 'lastock';
    public $detail = 'ladetail';

    public $hhead = 'glhead';
    public $hstock = 'glstock';
    public $hdetail = 'gldetail';

    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';

    public $style = 'width:100%;';
    public $showclosebtn = true;

    public function __construct()
    {
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->warehousinglookup = new warehousinglookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 2022, 'edit' => 2024);
        return $attrib;
    }

    public function createTab($config)
    {
        $barcode = 0;
        $itemdesc = 1;
        $isqty = 2;
        $picker = 3;
        $ispicked = 4;
        $location = 5;
        $void = 6;
        $returndate = 7;

        $column = ['barcode', 'itemdesc', 'isqty', 'picker', 'ispicked', 'location',  'void', 'returndate'];
        $sortcolumn = ['barcode', 'itemdesc', 'isqty', 'picker',  'ispicked', 'location', 'void', 'returndate'];

        $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $sortcolumn]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$location]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$location]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$returndate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$isqty]['checkfield'] = 'void';
        $obj[0][$this->gridname]['columns'][$picker]['checkfield'] = 'ispicked';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];
        $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);
        if ($posted) {
            $tbuttons = [];
        } else {
            $tbuttons = ['saveallentry'];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    private function selectqry($config)
    {
        $qry = "stock.line,stock.trno,stock.itemid,item.barcode,item.itemname as itemdesc,
        round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        round(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss, stock.uom,
        stock.isamt, stock.disc,
        (case when stock.void = 1 then 'true' else 'false' end) as void, stock.whid,
        stock.pickerid, ifnull(client.clientname,'') as picker, stock.locid, loc.loc as location, 
        stock.palletid, pallet.name as pallet, head.doc, stock.refx, stock.linex,
        (case when stock.pickerstart is null then 'false' else 'true' end) as ispicked";
        return $qry;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];
        $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);

        $stocktable = $this->table;
        $voidtable = 'voidstock';
        if ($posted) {
            $stocktable = 'glstock';
            $voidtable = 'hvoidstock';
        }

        $select = $this->selectqry($config);
        $select = $select . ",'' as bgcolor ";

        $qry = "
        select " . $select . ", null as returndate 
        from " . $stocktable . "  as stock
        left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        left join lahead as head on head.trno=stock.trno
        where stock.trno=? 
        union all
        select " . $select . ", stock.returndate 
        from " . $voidtable . "  as stock
        left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        left join lahead as head on head.trno=stock.trno
        where stock.trno=? 
        order by line";
        return $this->coreFunctions->opentable($qry, [$trno, $trno]);
    }

    public function lookupsetup($config)
    {
        $lookupclass = $config['params']['lookupclass2'];
        switch ($lookupclass) {
            case 'pickerstock':
                return $this->warehousinglookup->lookuppicker($config);
                break;
        }
    }

    public function saveallentry($config)
    {

        $data = $config['params']['data'];
        $trno = $config['params']['tableid'];
        $msg = '';
        $status = true;

        try {
            foreach ($data as $key => $value) {
                $data = [];
                if ($value['bgcolor'] != '') {

                    $line = $value['line'];

                    $isvoided = $this->coreFunctions->datareader("select void as value from voidstock where trno=? and line=? and void=1", [$trno, $line]);
                    if ($isvoided) {
                        $msg .= $value['itemdesc'] . ' was already voided, ';
                        continue;
                    }

                    $void = ($value['void'] == "true" ? 1 : 0);

                    $isdispatch = $this->coreFunctions->getfieldvalue("cntnuminfo", "status", "trno=? and status='IN-TRANSIT'", [$trno]);
                    if ($isdispatch != '') {
                        $data['void'] = $void;
                        if ($data['void']) {
                            goto updatequeryhere;
                        }
                    }

                    // assigning of picker was moved on WH PICKER MODULE - 2021.06.21
                    $pick = $this->coreFunctions->opentable("select pickerstart, pickerid from lastock where trno=? and line=?", [$trno, $line]);
                    if (!empty($pick)) {
                        if ($value['pickerid'] != $pick[0]->pickerid) {
                            $msg .= " Item " . $value['itemdesc'] . " was already picked, unable to change picker. ";
                        }
                    } else {
                        $data['pickerid'] = $value['pickerid'];
                    }

                    if ($void) {
                        $boxqty = $this->coreFunctions->datareader("select ifnull(sum(qty),0) as value from boxinginfo where trno=? and itemid=?", [$trno, $value['itemid']]);
                        if ($boxqty >= $value['isqty']) {
                            $msg .= " Item " . $value['itemdesc'] . " was already boxed, unable to void. ";
                        } else {
                            $data['void'] = $void;
                        }
                    } else {
                    }

                    if (!$data) {
                        goto updateqtyhere;
                    }

                    updatequeryhere:
                    $result = $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $trno, 'line' => $line]);
                    if ($result) {

                        if ($void) {
                            $result = $this->coreFunctions->execqry("insert into voidstock (trno, line, refx, linex, uom, disc, rem, rrcost, cost, rrqty, qty, isamt, amt, isqty, iss, ext, qa, ref, void, encodeddate, encodedby, editdate, editby, loc, loc2, sku, tstrno, tsline, comm, icomm, expiry, isqty2, iscomponent, outputid, iss2, agent, agent2, isextract, outputline, tsako, msako, itemcomm, itemhandling, kgs, isfromjo, original_qty, jotrno, joline, fcost, itemid, whid, rebate, stageid, palletid, locid, palletid2, locid2, pickerid, pickerstart, pickerend, forkliftid, isforklift, whmanid, whmandate, voidby, voidddate) 
                                select trno, line, refx, linex, uom, disc, rem, rrcost, cost, rrqty, qty, isamt, amt, isqty, iss, ext, qa, ref, void, encodeddate, encodedby, editdate, editby, loc, loc2, sku, tstrno, tsline, comm, icomm, expiry, isqty2, iscomponent, outputid, iss2, agent, agent2, isextract, outputline, tsako, msako, itemcomm, itemhandling, kgs, isfromjo, original_qty, jotrno, joline, fcost, itemid, whid, rebate, stageid, palletid, locid, palletid2, locid2, pickerid, pickerstart, pickerend, forkliftid, isforklift, whmanid, whmandate,'" . $config['params']['user'] . "',now() from lastock where trno=" . $trno . " and line=" . $line);
                            if ($result) {
                                $this->coreFunctions->execqry('delete from ' . $this->table . ' where trno=? and line=?', 'delete', [$trno, $line]);
                                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);

                                $this->setserveditems($value['refx'], $value['linex'], $value['doc']);

                                $this->logger->sbcwritelog($trno, $config, 'VOID' . ($isdispatch != "" ? " DISPATCH" : ""), $value['itemdesc']);

                                $pending = $this->coreFunctions->datareader("select trno as value from lastock where pickerend is null and trno=?", [$trno]);
                                if (!$pending) {
                                    if ($isdispatch != '') {
                                        goto checkifvoiditemshere;
                                    }
                                    $ischecker = $this->coreFunctions->datareader("select checkerid as value from cntnuminfo where trno=?", [$trno]);
                                    if (!$ischecker) {
                                        checkifvoiditemshere:
                                        $cntnum_status = 'PICKED';
                                        $pending = $this->coreFunctions->datareader("select trno as value from lastock where void=0 and trno=?", [$trno]);
                                        if (!$pending) {
                                            $cntnum_status = 'VOID';
                                        }
                                        $this->coreFunctions->execqry("update cntnum set status='" . $cntnum_status . "' where trno=" . $trno);
                                        $this->coreFunctions->execqry("update cntnuminfo set status='" . $cntnum_status . "' where trno=" . $trno);

                                        if ($cntnum_status == 'VOID') {
                                            if ($isdispatch != '') {
                                                goto posttranshere;
                                            }
                                            $already_picked = $this->coreFunctions->datareader("select trno as value from voidstock where trno=? and pickerstart is not null limit 1", [$trno]);
                                            if (!$already_picked) {
                                                posttranshere:
                                                $post =  $this->othersClass->posttranstock($config);
                                                if ($post) {
                                                    $current_time = $this->othersClass->getCurrentTimeStamp();
                                                    $this->coreFunctions->execqry("update hcntnuminfo set status='CONTROLLER VOID', logisticdate='" . $current_time . "', logisticby='" . $config['params']['user'] . "' where trno=?", 'update', [$trno]);
                                                    $this->coreFunctions->sbcupdate("cntnum", ['status' => 'VOID'], ['trno' => $trno]);
                                                    return ['status' => true, 'msg' => 'Successfully post void.', 'action' => 'reloadlisting'];
                                                } else {
                                                    return ['status' => false, 'msg' => 'Posting void failed.'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            updateqtyhere:
                            $data = [];

                            $origqty = $this->coreFunctions->getfieldvalue("lastock", "isqty", "trno=? and line=?", [$trno, $line]);

                            $itemid = $value['itemid'];
                            $amt = $this->othersClass->sanitizekeyfield('amt', $value['isamt']);
                            $qty = $this->othersClass->sanitizekeyfield('qty', $value['isqty']);
                            $uom = $value['uom'];
                            $disc = $value['disc'];

                            $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                            $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
                            $factor = 1;

                            if (!empty($item)) {
                                $item[0]->factor = $this->othersClass->val($item[0]->factor);
                                if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                            }

                            if ($origqty > $qty) {

                                $cur = $this->coreFunctions->getfieldvalue("lahead", 'cur', 'trno=?', [$trno]);
                                $curtopeso = $this->coreFunctions->getfieldvalue("lahead", 'forex', 'trno=?', [$trno]);
                                $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);

                                $data['isqty'] = $qty;
                                $data['iss'] = $computedata['qty'];
                                $data['amt'] = round($computedata['amt'] * $curtopeso, 2);
                                $data['ext'] = $computedata['ext'];

                                $result = $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $trno, 'line' => $line]);
                                if ($result) {
                                    $cost = $this->othersClass->computecostingpallet($value['itemid'], $value['whid'], $value['locid'], $value['palletid'], $trno, $line, $data['isqty'], $value['doc'], $config['params']);

                                    if ($cost != -1) {
                                        $this->coreFunctions->sbcupdate($this->table, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
                                    } else {
                                        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                                        $qty = $origqty;
                                        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);

                                        $data['isqty'] = $qty;
                                        $data['iss'] = $computedata['qty'];
                                        $data['amt'] = round($computedata['amt'] * $curtopeso, 2);
                                        $data['ext'] = $computedata['ext'];
                                        $data['editby'] = 'OUT_STOCK';
                                        $data['editdate'] = $current_timestamp;

                                        $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $trno, 'line' => $line]);
                                        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                                        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' Barcode:' . $value['barcode'] . ' Qty' . $value['isqty']);
                                    }

                                    if ($this->setserveditems($value['refx'], $value['linex'], $value['doc']) == 0) {
                                        $data2 = ['isqty' => 0, 'iss' => 0, 'ext' => 0];
                                        $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $trno, 'line' => $line]);
                                        $this->setserveditems($value['refx'], $value['linex'], $value['doc']);
                                        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                                        $msg .= "(" . $item[0]->barcode . ") Qty Received is Greater than RR Qty.";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $msg .= $e;
            $status = false;
        }

        $returndata = $this->loaddata($config);

        if ($msg == '') {
            $msg  = 'All saved successfully.';
        }
        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    }

    public function setserveditems($refx, $linex, $doc)
    {
        if ($refx == 0) {
            return 1;
        }
        $qry1 = "select stock.iss from lahead as head left join lastock as 
          stock on stock.trno=head.trno where head.doc='" . $doc . "' and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select glstock.iss from glhead left join glstock on glstock.trno=
          glhead.trno where glhead.doc='" . $doc . "' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

        $qry2 = "select ifnull(sum(iss),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty == '') {
            $qty = 0;
        }
        $sotable = '';
        switch ($doc) {
            case 'SD':
                $sotable = 'hsastock';
                break;
            case 'SE':
                $sotable = 'hsbstock';
                break;
            case 'SF':
                $sotable = 'hscstock';
                break;
            case 'SH':
                $sotable = 'hsgstock';
                break;
        }
        return $this->coreFunctions->execqry("update " . $sotable . " set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }
}
