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

class warehousecontroller
{


    public $modulename = 'INVENTORY CONTROLLER';
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
    public $tablelogs_del = 'del_table_log';

    private $fields = ['checkerid', 'checkerlocid'];

    public $transdoc = "'RP','WB','SD', 'SE', 'SF', 'SH'";

    private $btnClass;
    private $fieldClass;
    private $tabClass;

    private $companysetup;
    private $coreFunctions;
    private $othersClass;

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;
    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
        ['val' => 'void', 'label' => 'Draft Void', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Completed', 'color' => 'primary']
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
            'load' => 2022,
            'view' => 2023,
            'edit' => 2024,
            'save' => 2025
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $lblstatus = 1;
        $statname = 2;
        $listdocument = 3;
        $listdate = 4;
        $listclientname = 5;
        $agentname = 6;
        $checker = 7;
        $checkerloc = 8;
        $checkerdate = 9;
        $transtype = 10;
        $lockdate = 11;
        $lockuser = 12;
        $postdate = 13;
        $listpostedby = 14;
        $picker = 15;
        $pickerend = 16;

        $getcols = ['action', 'lblstatus', 'statname', 'listdocument', 'listdate', 'listclientname', 'agentname', 'checker', 'checkerloc', 'checkerdate', 'transtype', 'lockdate', 'lockuser', 'postdate', 'listpostedby', 'picker', 'pickerend'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$lblstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$lblstatus]['align'] = 'text-left';
        $cols[$listclientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$agentname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$checker]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$picker]['type'] = 'label';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $center = $config['params']['center'];
        $itemfilter = $config['params']['itemfilter'];

        $status = '';
        if ($itemfilter == 'void') {
            $status = " and num.status='VOID'";
        } else {
            $status = " and num.status<>'VOID'";
        }

        $headtable = $this->head;
        $stocktable = $this->stock;
        $headcninfo = 'cntnuminfo';
        $agentleftjoin = 'ag.client = head.agent';
        if ($itemfilter == 'posted') {
            $headtable = 'glhead';
            $stocktable = 'glstock';
            $headcninfo = 'hcntnuminfo';
            $agentleftjoin = 'ag.clientid = head.agentid';
        }

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = "select head.trno, head.doc, '' as transtype, head.trno as clientid, head.docno,head.clientname,left(head.dateid,10) as dateid, num.status as stat,
        head.createby,head.editby,head.viewby,num.postedby, ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, ifnull(stat.status,'') as statname, stat.psort,
        ifnull(ag.clientname, '') as agentname, num.postdate, num.postedby, head.lockdate, head.lockuser, ci.checkerdate,
        ifnull((select group_concat(distinct ifnull(client.clientname,'')) from " . $stocktable . " as s left join client on client.clientid=s.pickerid where s.trno=num.trno),'') as picker,
        ifnull((select date_format(s.pickerend,'%m/%d/%Y %H:%i') from " . $stocktable . " as s where s.trno=head.trno order by pickerend desc limit 1),'') as pickerend
        from " . $headtable . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join " . $headcninfo . " as ci on ci.trno=head.trno left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        left join trxstatus as stat on stat.line=head.statid
        left join client as ag on " . $agentleftjoin . "
        where head.lockdate is not null and head.doc in (" . $this->transdoc . ") 
        and num.center = ? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $status . "  " . $filtersearch . "
        order by ifnull(stat.psort,99), head.dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$center, $date1, $date2]);
        $data = $this->othersClass->updatetranstype($data);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }


    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'edit',
            'save',
            'cancel',
            'post',
            'unpost',
            'logs',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createHeadField($config)
    {
        $fields = ['client', 'clientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.type', 'input');
        data_set($col1, 'client.class', 'docno sbccsreadonly');
        data_set($col1, 'client.label', 'Document No.');

        $fields = ['checker', 'checkerloc'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['transtype'];
        $col3 = $this->fieldClass->create($fields);

        $fields = [['unlockwhclr', 'postwhclr']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'postwhclr.action', 'postvoid');
        data_set($col4, 'postwhclr.label', 'POST VOID');
        data_set($col4, 'postwhclr.confirmlabel', 'Post void transaction?');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }


    public function createTab($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'warehousingentry', 'lookupclass' => 'entrywhcontroller', 'label' => 'Items Details']];

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

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['clientname'] = '';
        $data[0]['checker'] = '';
        $data[0]['checkerid'] = 0;
        $data[0]['checkerloc'] = '';
        $data[0]['checkerlocid'] = 0;
        $data[0]['transtype'] = '';

        return $data;
    }

    private function selectqry($config)
    {
        $table = $this->head;
        if (isset($config['params']['row']['postdate'])) {
            $table = 'glhead';
        }

        $qry = "select h.trno as clientid, h.docno as client, h.clientname,
        ci.checkerid, ifnull(client.clientname,'') as checker, ci.checkerlocid, ifnull(cl.name,'') as checkerloc, h.doc,
        (case
        when h.doc='RP' then 'Packing List Receiving'
        when h.doc='SD' then 'Sales Journal Dealer'
        when h.doc='SE' then 'Sales Journal Branch'
        when h.doc='SF' then 'Sales Journal Online'
        when h.doc='SH' then 'Special Parts Issuance'
        else '' end) as transtype
        from " . $table . " as h left join cntnuminfo as ci on ci.trno=h.trno
        left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        where h.trno=?";
        return $qry;
    }

    public function loadheaddata($config)
    {
        $trno = $config['params']['clientid'];
        $head = $this->coreFunctions->opentable($this->selectqry($config), [$trno]);
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            $posted = false;
            if (isset($config['params']['row']['postdate'])) {
                $hideobj = ['unlockwhclr' => true, 'postwhclr' => true];
                $posted = true;
            } else {
                $hideobj = ['unlockwhclr' => false, 'postwhclr' => false];
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => $posted, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $udpate)
    {
        $head = $config['params']['head'];
        $trno  = $head['clientid'];
        $data = [];

        $result = $this->checkIfAllowedEdit($trno);
        if (!$result['status']) {
            return ['status' => false, 'msg' => $result['msg'], 'clientid' => $trno];
        }

        switch ($head['doc']) {
            case 'RP':
            case 'WB':
                break;

            default:
                $post = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerdate", "trno=?", [$trno]);
                if ($post) {
                    return ['status' => false, 'msg' => "Unable to change checker and checker location. This document is for DISPATCHING.", 'clientid' => $trno];
                }

                foreach ($this->fields as $key) {
                    if (isset($head[$key])) {
                        $data[$key] = $this->othersClass->sanitizekeyfield($key, $head[$key]);
                    }
                }

                $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);
                break;
        }
        $msg = '';

        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $trno];
    } // end function


    public function stockstatusposted($config)
    {
        $clientid = $config['params']['clientid'];
        $transdoc = $this->coreFunctions->datareader("select doc as value from cntnum where trno=?", [$clientid]);
        switch ($transdoc) {
            case 'RP':
                return ['status' => false, 'msg' => 'Tagging of FOR PICKING is for Sales Journal transctions only'];
                break;
        }

        switch ($config['params']['action']) {
            case 'post':

                $result = $this->checkIfAllowedEdit($clientid);
                if (!$result['status']) {
                    return $result;
                }

                $checkerid = $this->coreFunctions->datareader("select checkerid as value from cntnuminfo where trno=?", [$clientid]);
                if ($checkerid == 0) {
                    return ['status' => false, 'msg' => 'Cannot proceed for PICKING. Please specifty valid checker'];
                }
                $checkerlocid = $this->coreFunctions->datareader("select checkerlocid as value from cntnuminfo where trno=?", [$clientid]);
                if ($checkerlocid == 0) {
                    return ['status' => false, 'msg' => 'Cannot proceed for PICKING. Please specifty valid checker location'];
                }
                $pickerid = $this->coreFunctions->datareader("select ifnull(count(pickerid),0) as value from lastock where trno=? and pickerid=0", [$clientid]);
                if ($pickerid != 0) {
                    return ['status' => false, 'msg' => 'Cannot proceed for PICKING. Please fill-in picker for all items.'];
                }
                $this->coreFunctions->execqry("update cntnuminfo as ci left join cntnum as c on c.trno=ci.trno set c.status = 'FOR PICKING', ci.status='FOR PICKING', c.crtldate=now(), c.crtlby='" . $config['params']['user'] . "' where ci.trno=?", 'update', [$clientid]);
                return ['status' => true, 'msg' => 'Successfully updated.'];
                break;

            case 'postvoid':
                $status = $this->coreFunctions->datareader("select status as value from cntnum where trno=?", [$clientid]);
                if ($status == 'VOID') {
                    goto postvoidhere;
                } else {
                    $stock = $this->coreFunctions->datareader("select count(trno) as value from voidstock where trno=? and returndate is null", [$clientid]);
                    if ($stock) {
                        return ['status' => false, 'msg' => 'Allows to post if all items have already been returned.'];
                    }
                }

                $stock = $this->coreFunctions->datareader("select count(trno) as value from lastock where trno=?", [$clientid]);
                if ($stock) {
                    return ['status' => false, 'msg' => 'Posting is permitted if all items are void.'];
                }

                postvoidhere:
                $post =  $this->othersClass->posttranstock($config);
                if ($post) {
                    $current_time = $this->othersClass->getCurrentTimeStamp();
                    $this->coreFunctions->execqry("update hcntnuminfo set status='CONTROLLER VOID', logisticdate='" . $current_time . "', logisticby='" . $config['params']['user'] . "' where trno=?", 'update', [$clientid]);
                    return ['status' => true, 'msg' => 'Successfully post void.', 'action' => 'reloadlisting'];
                } else {
                    return ['status' => false, 'msg' => 'Posting void failed.'];
                }

                return ['status' => true, 'msg' => 'Successfully updated.'];
                break;

            case 'unlock':
                $checkerid = $this->coreFunctions->datareader("select checkerdone as value from cntnuminfo where trno=?", [$clientid]);
                if ($checkerid) {
                    return ['status' => false, 'msg' => 'Cannot unlock; DR has already been printed.'];
                }

                $status = '';
                $pending = $this->coreFunctions->datareader("select trno as value from lastock where trno=? and pickerstart is null", [$clientid]);
                if ($pending) {
                    $status = 'FOR PICKING';
                } else {
                    $status = 'PICKED';
                }

                $this->coreFunctions->execqry("update cntnuminfo set status='" . $status . "', checkerrcvdate=null, checkerid=0 where trno=?", 'update', [$clientid]);
                $this->coreFunctions->execqry("update cntnum set status='" . $status . "' where trno=?", 'update', [$clientid]);
                $this->coreFunctions->execqry("update lahead set lockdate=null where trno=?", 'update', [$clientid]);

                $this->logger->sbcwritelog($clientid, $config, 'UNLOCK', 'INVENTORY CONTROLLER');

                return ['status' => true, 'msg' => 'Successfully unlock!'];
                break;

            default:
                return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    private function checkIfAllowedEdit($clientid)
    {
        $dispatch = $this->coreFunctions->getfieldvalue("cntnuminfo", "dispatchdate", "trno=?", [$clientid]);
        if ($dispatch) {
            return ['status' => false, 'msg' => 'Dispatched already.'];
        }

        $dispatch = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerdate", "trno=?", [$clientid]);
        if ($dispatch) {
            return ['status' => false, 'msg' => 'Already checked by the checker.'];
        }

        return ['status' => true, 'msg' => ''];
    }
}
