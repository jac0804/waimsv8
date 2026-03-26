<?php

namespace App\Http\Classes\modules\warehousing;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;

class replenishpallet
{
    public $modulename = 'REPLENISH PER PALLET';
    public $gridname = 'inventory';

    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $stock = 'lastock';
    public $detail = 'ladetail';

    public $hhead = 'glhead';
    public $hstock = 'glstock';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $prefix = '';

    private $fields = [];

    private $btnClass;
    private $fieldClass;
    private $tabClass;

    private $companysetup;
    private $coreFunctions;
    private $othersClass;

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    public $issearchshow = false;
    public $style = 'width:100%;max-width:100%;';

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Validated', 'color' => 'primary']
    ];

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 2542,
            'view' => 2543,
            'new' => 2544,
            'save' => 2544
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'location', 'pallet', 'listdocument', 'listdate', 'validate'];
        $stockbuttons = ['validate'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[1]['style'] = 'width:60px;whiteSpace: normal;min-width:60px; max-width:60px;';;
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $itemfilter = $config['params']['itemfilter'];
        $adminid = $config['params']['adminid'];
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));

        $condition = '';
        $searchfilter = $config['params']['search'];
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['h.docno', 'pallet.name', 'location.loc'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 25';
        }

        switch ($itemfilter) {
            case 'draft':
                $condition .= ' and r.validate is null ';
                break;
            case 'posted':
                $condition .= ' and r.validate is not null ';
                break;
        }

        $qry = "select cntnum.trno, cntnum.docno, h.docno, h.dateid, r.palletid, r.locid, ifnull(pallet.name,'') as pallet,
        ifnull(location.loc,'') as location, r.validate
        from replenishstock as r left join cntnum on cntnum.trno=r.trno left join glhead as h on h.trno=cntnum.trno
        left join pallet on pallet.line=r.palletid left join location on location.line=r.locid
        where userid=? and r.itemid=0 and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? $filtersearch " . $condition;
        $data = $this->coreFunctions->opentable($qry, [$adminid, $date1, $date2]);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'cancel',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton


    public function createHeadField($config)
    {
        $fields = ['lblsource', 'wh', 'whname', 'pallet', 'location', 'scanpallet'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'pallet.required', true);
        data_set($col1, 'pallet.class', 'sbccsreadonly');
        data_set($col1, 'location.required', true);
        data_set($col1, 'location.class', 'sbccsreadonly');
        data_set($col1, 'scanpallet.label', 'Scan Source Pallet');

        $fields = ['lbldestination', 'client', 'clientname', 'location2'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'client.label', 'Warehouse');
        data_set($col2, 'client.lookupclass', 'replenishdestination');
        data_set($col2, 'clientname.class', 'sbccsreadonly');
        data_set($col2, 'clientname.label', 'Name');
        data_set($col2, 'location2.required', true);
        data_set($col2, 'location2.class', 'sbccsreadonly');
        data_set($col2, 'location2.addedparams', ['whid', 'clientname']);

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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

    public function selectqry($config, $pallet, $location)
    {
        $whcode = $this->companysetup->getwh($config['params']);
        $whname = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$whcode]);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$whcode]);

        return "
        select 0 as clientid, '" . $whcode . "' as client, " . $whid . " as whid, '" . $whcode . "' as wh, '" . $whname . "' as whname,
        '" . $whcode . "' as client, '" . $whname . "' as clientname, '" . $pallet . "' as pallet, '" . $location . "' as location,
        0 as palletid2, '' as pallet2, 0 as locid2, '' as location2";
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient'], $config);
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Transaction'];
    }

    public function getlastclient($pref)
    {
        return '';
    }

    public function loadheaddata($config)
    {
        $trno = 0;
        $qry = $this->selectqry($config, '', '');
        $head = $this->coreFunctions->opentable($qry);
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $trno];
        } else {
            $head = $this->resetdata('', $config);
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    private function resetdata($client = '', $config)
    {
        $whcode = $this->companysetup->getwh($config['params']);
        $whname = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$whcode]);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$whcode]);

        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $whcode;
        $data[0]['clientname'] = $whname;
        $data[0]['whid'] = $whid; //for destination
        $data[0]['wh'] = $whcode;
        $data[0]['whname'] = $whname;
        $data[0]['pallet'] = '';
        $data[0]['location'] = '';
        $data[0]['pallet2'] = '';
        $data[0]['location2'] = '';
        $data[0]['palletid2'] = 0;
        $data[0]['locid2'] = 0;
        return $data;
    }

    public function stockstatusposted($config)
    {
        $pallet = '';
        $location = '';
        $head = [];
        $action = $config['params']['action'];
        if (isset($config['params']['action2'])) {
            $action = $config['params']['action2'];
        }

        switch ($action) {
            case 'validate':
                $row = $config['params']['row'];

                if (count($config['params']['arrparams']) != 0) {
                    if ($row['pallet'] != $config['params']['barcode']) {
                        return ['status' => false, 'msg' => 'Scan pallet doesn`t match'];
                    } else {
                        $location = $config['params']['arrparams'][0];
                        $pallet = $config['params']['barcode'];

                        if ($row['validate'] == null) {
                            $this->coreFunctions->sbcupdate("replenishstock", ['validate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $row['trno']]);

                            $qry = $this->selectqry($config, $pallet, $location);
                            $head = $this->coreFunctions->opentable($qry);

                            return ['status' => true, 'msg' => 'Successfully validated.', 'action' => 'reloadlisting'];
                        } else {
                            return ['status' => false, 'msg' => 'Already validated.'];
                        }
                    }
                } else {
                    if ($row['location'] != $config['params']['barcode']) {
                        return ['status' => false, 'msg' => 'Scan location does not match.'];
                    } else {
                        return ['status' => true, 'msg' => 'The location was successfully scanned.', 'action' => 'rescan', 'title' => 'Scan Pallet'];
                    }
                }


                break;

            case 'scanpallet':
                if (count($config['params']['arrparams']) != 0) {
                    if (count($config['params']['arrparams']) > 0) {
                        $pallet = $config['params']['arrparams'][0];
                        $location = $config['params']['barcode'];

                        $palletid = $this->coreFunctions->getfieldvalue("pallet", "line", "name=?", [$pallet]);
                        if (!$palletid) {
                            return ['status' => false, 'msg' => 'The scanned pallet does not exist.'];
                        }

                        $locid = $this->coreFunctions->getfieldvalue("location", "line", "loc=?", [$location]);
                        if (!$locid) {
                            return ['status' => false, 'msg' => 'The scanned location does not exist.'];
                        }

                        $qry = $this->selectqry($config, $pallet, $location);
                        $head = $this->coreFunctions->opentable($qry);

                        return ['status' => true, 'msg' => 'The location was successfully scanned. ', 'action' => 'reloadhead', 'head' => $head];
                    }
                }
                return ['status' => true, 'msg' => 'The pallet was successfully scanned.', 'action' => 'rescan', 'title' => 'Scan Location'];
                break;
        }
    }

    public function updatehead($config)
    {
        $user = $config['params']['user'];
        $adminid = $config['params']['adminid'];
        $head = $config['params']['head'];
        $clientid  = $head['clientid'];
        $data = [];
        $msg = '';

        $sourceid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$head['wh']]);
        if (!$sourceid) {
            return ['status' => false, 'msg' => 'Source warehouse does not exists.', 'clientid' => $clientid];
        }

        $destinationid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$head['client']]);
        if (!$destinationid) {
            return ['status' => false, 'msg' => 'Destination warehouse does not exist.', 'clientid' => $clientid];
        }

        $palletid = $this->coreFunctions->getfieldvalue("pallet", "line", "name=?", [$head['pallet']]);
        if (!$palletid) {
            return ['status' => false, 'msg' => 'The scanned pallet does not exist.', 'clientid' => $clientid];
        }

        $locid = $this->coreFunctions->getfieldvalue("location", "line", "loc=?", [$head['location']]);
        if (!$locid) {
            return ['status' => false, 'msg' => 'The scanned location does not exist.', 'clientid' => $clientid];
        }

        $locid2 = $this->coreFunctions->getfieldvalue("location", "line", "loc=?", [$head['location2']]);
        if (!$locid2) {
            return ['status' => false, 'msg' => 'Destination location does not exist.', 'clientid' => $clientid];
        }

        if ($head['location'] == $head['location2']) {
            return ['status' => false, 'msg' => 'Destination location must not be the same as the source location.', 'clientid' => $clientid];
        }

        $qry = "select itemid, uom, ifnull(sum(bal),0) as bal from rrstatus where whid=? and palletid=? and locid=? and bal<>0 group by itemid, uom, whid, palletid, locid";
        $rrstatus = $this->coreFunctions->opentable($qry, [$sourceid, $palletid, $locid]);

        if (empty($rrstatus)) {
            return ['status' => false, 'msg' => 'No available stocks exist on the selected pallet and location.', 'clientid' => $clientid];
        } else {
            $doc = 'TS';
            $trno = $this->othersClass->generatecntnum($config, "cntnum", $doc, "TP");
            if ($trno != -1) {

                $docno =  $this->coreFunctions->getfieldvalue("cntnum", 'docno', "trno=?", [$trno]);
                $data = [
                    'trno' => $trno,
                    'doc' => $doc,
                    'docno' => $docno,
                    'client' => $head['client'],
                    'clientname' => $head['clientname'],
                    'dateid' => $this->othersClass->getCurrentDate(),
                    'due' => $this->othersClass->getCurrentDate(),
                    'wh' => $head['wh'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'createby' => $user,
                    'rem' => 'AUTO-GENERATED REPLENISH PER PALLET'
                ];

                if ($this->coreFunctions->sbcinsert("lahead", $data)) {

                    $line = 1;
                    foreach ($rrstatus as $key => $value) {
                        $itemid = $value->itemid;
                        $uom = $value->uom;
                        $amt = 0;
                        $qty = $value->bal;

                        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
                        $factor = 1;
                        if (!empty($item)) {
                            $item[0]->factor = $this->othersClass->val($item[0]->factor);
                            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                        }

                        $latestcost = $this->othersClass->getlatestcostTS($config, $item[0]->barcode, '', $config['params']['center'], $trno);
                        if ($latestcost['status']) {
                            $amt = $latestcost['data'][0]->amt;
                        } else {
                            $amt = 0;
                        }

                        $computedata = $this->othersClass->computestock($amt, '', $qty, $factor, 0);

                        $stock = [
                            'trno' => $trno,
                            'line' => $line,
                            'itemid' => $itemid,
                            'uom' => $uom,
                            'whid' => $sourceid,
                            'locid' => $locid,
                            'locid2' => $locid2,
                            'palletid' => $palletid,
                            'palletid2' => $palletid,
                            'isamt' => $amt,
                            'amt' => $computedata['amt'],
                            'isqty' => $qty,
                            'iss' => $computedata['qty'],
                            'ext' => $computedata['ext'],
                            'encodedby' => $user,
                            'encodeddate' => $this->othersClass->getCurrentTimeStamp()
                        ];

                        foreach ($stock as $key => $v) {
                            $stock[$key] = $this->othersClass->sanitizekeyfield($key, $stock[$key]);
                        }

                        if ($this->coreFunctions->sbcinsert("lastock", $stock)) {

                            $cost = $this->othersClass->computecostingpallet($stock['itemid'], $stock['whid'], $stock['locid'], $stock['palletid'], $trno, $line, $stock['iss'], $doc, $config['params']);
                            if ($cost != -1) {
                                $this->coreFunctions->sbcupdate("lastock", ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
                            } else {
                                $this->deleteall($trno);
                                return ['status' => false, 'msg' => 'OUT-OF-STOCK for item ' . $item[0]->barcode, 'clientid' => $clientid];
                                break;
                            }
                        } else {
                            $this->deleteall($trno);
                            return ['status' => false, 'msg' => 'Failed to insert stock details', 'clientid' => $clientid];
                            break;
                        }

                        $line += 1;
                    }

                    $result = $this->posttrans($config, $trno);
                    if (!$result['status']) {
                        return ['status' => false, 'msg' => $result['msg'], 'clientid' => $clientid];
                    } else {
                        $replenish = [
                            'trno' => $trno,
                            'locid' => $locid2,
                            'palletid' => $palletid,
                            'userid' => $adminid
                        ];
                        $this->coreFunctions->sbcinsert("replenishstock", $replenish);
                        return ['status' => true, 'msg' => 'Successfully created ' . $docno, 'clientid' => $clientid];
                    }
                } else {
                    $this->deleteall($trno);
                    return ['status' => false, 'msg' => 'Failed to insert head details', 'clientid' => $clientid];
                }
            }
        }

        $this->success = true;

        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    }


    private function deleteall($trno)
    {
        $this->coreFunctions->execqry("delete from cntnum where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from lahead where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from lastock where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    }

    public function posttrans($config, $clientid)
    {
        $config['params']['clientid'] = $clientid;
        $trno = $config['params']['clientid'];
        $stock = app('App\Http\Classes\modules\inventory\ts')->openstock($trno, $config);
        $checkcosting = $this->othersClass->checkcosting($stock);
        if ($checkcosting != '') {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
        }

        return $this->othersClass->posttranstock($config);
    } //end function

}
