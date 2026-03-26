<?php

namespace App\Http\Classes\modules\realestate;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;


class pn
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PROJECT COMPLETION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    private $stockselect;
    public $dqty = 'rrqty';
    public $hqty = 'qty';
    public $damt = 'rrcost';
    public $hamt = 'cost';
    public $defaultContra = 'CIP';
    private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname',  'yourref', 'ourref', 'rem', 'wh', 'projectid',  'phaseid', 'modelid', 'blklotid', 'contra', 'cotrno'];

    private $except = ['trno', 'dateid'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

    private $acctg = [];
    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
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
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4708,
            'edit' => 4709,
            'new' => 4710,
            'save' => 4711,
            'delete' => 4712,
            'print' => 4713,
            'lock' => 4714,
            'unlock' => 4715,
            'changeamt' => 4716,
            'post' => 4717,
            'unpost' => 4718,
            'additem' => 4719,
            'edititem' => 4720,
            'deleteitem' => 4721,
            'viewamt' => 4722
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];

        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $yourref = 4;
        $ourref = 5;

        $postdate = 6;
        $listpostedby = 7;
        $listcreateby = 8;
        $listeditby = 9;
        $listviewby = 10;

        $getcols = ['action', 'lblstatus', 'listdocument', 'listdate',  'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view', 'diagram'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $cols[$yourref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';

        $cols[$postdate]['label'] = 'Post Date';

        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
    }


    public function loaddoclisting($config)
    {

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $limit = "limit 150";

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref', 'item.itemname'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        $companyid = $config['params']['companyid'];
        $dateid = "left(head.dateid,10) as dateid";
        $ustatus = "DRAFT";
        $status = "stat.status";
        $leftjoin = "";
        $leftjoin_posted = "";
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.lockdate is null ';
                break;
            case 'locked':
                $condition = ' and head.lockdate is not null and num.postdate is null ';
                $status = "'LOCKED'";
                $ustatus = "LOCKED";
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }
        $qry = "select head.trno, head.docno, $dateid, case ifnull(head.lockdate,'') when '' then '" . $ustatus . "' else 'Locked' end as stat, head.createby, head.editby, head.viewby, num.postedby,
      date(num.postdate) as postdate, head.yourref, head.ourref, item.itemname
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join lastock as stock on stock.trno=head.trno
     left join trxstatus as stat on stat.line=num.statid 
     left join item on item.itemid=stock.itemid
  
     " . $leftjoin . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     union all
     select head.trno, head.docno,  $dateid," . $status . " as stat, head.createby, head.editby, head.viewby, num.postedby,
     date(num.postdate) as postdate, head.yourref, head.ourref, item.itemname
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join glstock as stock on stock.trno=head.trno
     left join trxstatus as stat on stat.line=num.statid 
     left join item on item.itemid=stock.itemid
     " . $leftjoin_posted . "
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     group by head.trno, head.docno,  head.dateid, stat.status, head.createby, head.editby, head.viewby, num.postedby, num.postdate, head.yourref, head.ourref, item.itemname 
     order by dateid desc,docno desc " . $limit;

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }


    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'post',
            'unpost',
            'lock',
            'unlock',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown',
            'help',

        );


        $buttons = $this->btnClass->create($btns);
        $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
        $step6 = $this->helpClass->getFields(['btndelete']);

        $buttons['help']['items'] = [
            'create' => ['label' => 'How to create New Document', 'action' => $step1],
            'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
            'additem' => ['label' => 'How to add item/s', 'action' => $step3],
            'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
            'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
            'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
        ];

        return $buttons;
    } // createHeadbutton


    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];
        $action = 0;
        $rrqty = 1;
        $uom = 2;
        $rrcost = 3;
        $cost = 4;
        $project = 5;
        $phasename = 6;
        $housemodel = 7;
        $blk = 8;
        $lot = 9;
        $amenityname = 10;
        $subamenityname = 11;
        $wh = 12;

        $column = [
            'action',  'rrqty', 'uom', 'rrcost', 'cost', 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname', 'wh'
        ];

        $sortcolumn = [
            'action', 'rrqty', 'uom', 'rrcost',  'cost', 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname', 'wh'
        ];

        $headgridbtns = ['viewdistribution'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
                'headgridbtns' => $headgridbtns
            ]
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['columns'][$blk]['readonly'] = true;
        $obj[0]['inventory']['columns'][$lot]['readonly'] = true;
        $obj[0]['inventory']['columns'][$project]['type'] = 'input';
        $obj[0]['inventory']['columns'][$project]['readonly'] = true;
        $obj[0]['inventory']['columns'][$phasename]['type'] = 'input';
        $obj[0]['inventory']['columns'][$phasename]['readonly'] = true;
        $obj[0]['inventory']['columns'][$housemodel]['type'] = 'input';
        $obj[0]['inventory']['columns'][$housemodel]['readonly'] = true;
        $obj[0]['inventory']['columns'][$rrcost]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$cost]['label'] = 'Total Amount';
        $obj[0]['inventory']['columns'][$cost]['type'] = 'input';
        $obj[0]['inventory']['columns'][$cost]['readonly'] = true;
        $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
        $obj[0]['inventory']['columns'][$rrqty]['readonly'] = true;

        $obj[0]['inventory']['columns'][$cost]['style'] = 'text-align: right';
        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $companyid = $config['params']['companyid'];
        $pendingcor = 0;
        $saveitem = 1;
        $deleteallitem = 2;
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'codocno', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Customer Code');
        data_set($col1, 'clientname.label', 'Customer Name');
        data_set($col1, 'client.lookupclass', 'customercor');
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'whname.required', true);
        data_set($col1, 'whname.type', 'lookup');
        data_set($col1, 'whname.action', 'lookupclient');
        data_set($col1, 'whname.lookupclass', 'wh');

        $fields = ['dateid', 'dacnoname', 'dprojectname', 'phase'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'projectname.lookupclass', 'fproject');
        data_set($col2, 'phase.addedparams', ['projectid']);
        data_set($col2, 'dacnoname.label', 'Account');


        $fields = ['housemodel', ['blklot', 'lot'], ['yourref', 'ourref']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'housemodel.addedparams', ['projectid']);
        data_set($col3, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);

        $fields = ['rem'];
        $col4 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['due'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['yourref'] = '';

        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['projectcode'] = '';
        $data[0]['projectname'] = '';
        $data[0]['projectid'] = 0;
        $data[0]['dwhname'] = '';
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;

        $data[0]['phase'] = '';
        $data[0]['phaseid'] = 0;
        $data[0]['housemodel'] = '';
        $data[0]['modelid'] = 0;
        $data[0]['blklot'] = '';
        $data[0]['blklotid'] = 0;
        $data[0]['blk'] = '';
        $data[0]['lot'] = '';
        $data[0]['cotrno'] = 0;
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);

        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        $tablenum = $this->tablenum;

        if ($trno == 0) {
            $trno = $this->othersClass->readprofile('TRNO', $config);
            if ($trno == '') {
                $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
            }
            $config['params']['trno'] = $trno;
        } else {
            $this->othersClass->checkprofile('TRNO', $trno, $config);
        }

        if ($this->companysetup->getistodo($config['params'])) {
            $this->othersClass->checkseendate($config, $tablenum);
        }

        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;

        $qryselect = "select num.center,head.trno,head.docno,client.client,head.yourref,head.ourref,
                            left(head.dateid,10) as dateid,head.clientname,head.address,
                            date_format(head.createdate,'%Y-%m-%d') as createdate,head.rem,
                             
                            
                            warehouse.client as wh,
                            warehouse.clientname as whname,'' as dwhname,left(head.due,10) as due,client.groupid,
                            head.projectid,ifnull(project.code,'') as projectcode,
                            ifnull(project.name,'') as projectname,'' as dprojectname,
                            phase.code as phase,head.phaseid,model.model as housemodel,head.modelid,bl.blk as blklot,head.blklotid,bl.lot,
                            head.contra, coa.acnoname, '' as dacnoname,co.docno as codocno";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join projectmasterfile as project on project.line=head.projectid
        left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
        left join housemodel as model on model.line=head.modelid and model.projectid=head.projectid
        left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
        left join coa on coa.acno=head.contra
        left join hcohead as co on co.trno = head.cotrno
        
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
         left join client as warehouse on warehouse.clientid = head.whid
        left join projectmasterfile as project on project.line=head.projectid
        left join phase on phase.line=head.phaseid and phase.projectid=head.projectid
        left join housemodel as model on model.line=head.modelid and model.projectid=head.projectid
        left join blklot as bl on bl.line=head.blklotid and bl.phaseid=head.phaseid and bl.projectid=head.projectid
        left join coa on coa.acno=head.contra
        left join hcohead as co on co.trno = head.cotrno
        where head.trno = ? and num.doc=? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hidetabbtn = ['btndeleteallitem' => false];
            $clickobj = ['button.btnadditem'];

            $hideobj = [];
            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }
            return  [
                'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,
                'clickobj' => $clickobj, 'hidetabbtn' => $hidetabbtn, 'hideobj' => $hideobj
            ];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $companyid = $config['params']['companyid'];
        $head = $config['params']['head'];
        $data = [];
        if ($isupdate) {
            unset($this->fields[1]);
            unset($head['docno']);
        }
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                } //end if
            }

        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->head, $data);
            $this->autocreatestock($config);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
        }
    } // end function


    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno=? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $companyid = $config['params']['companyid'];
        $periodic = $this->companysetup->getisperiodic($config['params']);
        $serial = $this->companysetup->getserial($config['params']);

        if ($serial) {
            if (!$this->othersClass->checkserialin($config)) {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
            }
        }


        if ($this->companysetup->isinvonly($config['params'])) {
            return $this->othersClass->posttranstock($config);
        } else {

            $checkacct = $this->othersClass->checkcoaacct(['CIP', 'IN1', 'TX1']);

            if ($checkacct != '') {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
            }

            $checkcontra = $this->coreFunctions->getfieldvalue("lahead", "contra", "trno=?", [$trno]);
            if ($checkcontra == '') {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Missing Contra Account'];
            }

            if (!$this->createdistribution($config)) {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
            } else {
                $return = $this->othersClass->posttranstock($config);
                return $return;
            }
        }
    } //end function

    public function unposttrans($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $isfa = $this->companysetup->getisfixasset($config['params']);
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");

        $return = $this->othersClass->unposttranstock($config);
        return $return;
    } //end function


    public function createdistribution($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        $systype = $this->companysetup->getsystemtype($config['params']);
        $status = true;
        $isvatexpurch = $this->companysetup->getvatexpurch($config['params']);
        $isglc = $this->companysetup->isglc($config['params']);
        $periodic = $this->companysetup->getisperiodic($config['params']);
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
        stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid,stock.freight,head.ewtrate,head.ewt,
        stock.projectid as sprojectid,stock.phaseid, stock.modelid, stock.blklotid,stock.amenityid,
         stock.subamenityid
        from ' . $this->head . ' as head 
        left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid where head.trno=?';

        $stock = $this->coreFunctions->opentable($qry, [$trno]);
        $tax = 0;
        $ewt = 0;
        $totalap = 0;
        $delcharge = 0;
        $cost = 0;
        $lcost = 0;

        if (!empty($stock)) {
            $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);

            $vat = $stock[0]->tax;
            $tax1 = 0;
            $tax2 = 0;
            if ($vat != 0) {
                $tax1 = 1 + ($vat / 100);
                $tax2 = $vat / 100;
            }


            foreach ($stock as $key => $value) {
                $params = [];
                $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));

                if ($vat != 0) {
                    if ($isvatexpurch) {
                        $tax = ($stock[$key]->ext * $tax2);
                    } else {

                        $tax = ($stock[$key]->ext / $tax1);
                        $tax = $stock[$key]->ext - $tax;
                    }
                }

                $cost = $stock[$key]->cost * $stock[$key]->qty;


                $params = [
                    'client' => $stock[$key]->client,
                    'acno' => $stock[$key]->contra,
                    'ext' => $stock[$key]->ext,
                    'wh' => $stock[$key]->wh,
                    'date' => $stock[$key]->dateid,
                    'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
                    'tax' =>  $tax,
                    'discamt' => $disc * $stock[$key]->rrqty,
                    'cur' => $stock[$key]->cur,
                    'forex' => $stock[$key]->forex,
                    'cost' =>  $cost,
                    'projectid' => $stock[$key]->projectid,
                    'subproject' => $stock[$key]->subproject,
                    'stageid' => $stock[$key]->stageid,
                    'freight' => $stock[$key]->freight,
                    'lcost' => $lcost
                ];

                if ($systype == 'REALESTATE') {
                    $params['projectid'] = $stock[$key]->sprojectid;
                    $params['phaseid'] = $stock[$key]->phaseid;
                    $params['modelid'] = $stock[$key]->modelid;
                    $params['blklotid'] = $stock[$key]->blklotid;
                    $params['amenityid'] = $stock[$key]->amenityid;
                    $params['subamenityid'] = $stock[$key]->subamenityid;
                }


                if ($isvatexpurch) {
                    $this->distributionvatex($params, $config);
                } else {
                    $this->distribution($params, $config);
                }
            }
        }

        if (!empty($this->acctg)) {
            $tdb = 0;
            $tcr = 0;
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            if ($isglc) {
                //loop to get totals
                foreach ($this->acctg as $key => $value) {
                    $tdb = $tdb +  round($this->acctg[$key]['db'], 2);
                    $tcr = $tcr +  round($this->acctg[$key]['cr'], 2);
                }

                $diff = $tdb - $tcr;
                $this->coreFunctions->LogConsole(round($diff, 2));
                $alias = 'GLC';

                if ($diff != 0) {
                    $qry = "select client,forex,dateid,cur,branch,deptid,contra,projectid,wh from " . $this->head . " where trno = ?";
                    $d = $this->coreFunctions->opentable($qry, [$trno]);

                    if (abs(round($diff, 2)) != 0) {
                        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$alias]);

                        if ($diff < 0) {
                            $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => abs(round($diff, 2)), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];
                            if ($systype == 'REALESTATE') {
                                $entry['projectid'] = $params['projectid'];
                                $entry['phaseid'] = $params['phaseid'];
                                $entry['modelid'] = $params['modelid'];
                                $entry['blklotid'] = $params['blklotid'];
                                $entry['amenityid'] = $params['amenityid'];
                                $entry['subamenityid'] = $params['subamenityid'];
                            }
                            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                        } else {
                            $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => 0, 'cr' => abs(round($diff, 2)), 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];
                            if ($systype == 'REALESTATE') {
                                $entry['projectid'] = $params['projectid'];
                                $entry['phaseid'] = $params['phaseid'];
                                $entry['modelid'] = $params['modelid'];
                                $entry['blklotid'] = $params['blklotid'];
                                $entry['amenityid'] = $params['amenityid'];
                                $entry['subamenityid'] = $params['subamenityid'];
                            }
                            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                        }
                    }
                }
            }

            foreach ($this->acctg as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
                }
                $this->acctg[$key]['editdate'] = $current_timestamp;
                $this->acctg[$key]['editby'] = $config['params']['user'];
                $this->acctg[$key]['encodeddate'] = $current_timestamp;
                $this->acctg[$key]['encodedby'] = $config['params']['user'];
                $this->acctg[$key]['trno'] = $config['params']['trno'];
                $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
                $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
                $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
                $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
            }

            if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
                $status =  true;
            } else {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
                $status = false;
            }
        }

        return $status;
    } //end function

    public function distribution($params, $config)
    {

        $companyid = $config['params']['companyid'];
        $systype = $this->companysetup->getsystemtype($config['params']);
        $entry = [];
        $forex = $params['forex'];
        if ($forex == 0) {
            $forex = 1;
        }

        $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);
        $periodic = $this->companysetup->getisperiodic($config['params']);

        if (!$this->companysetup->getispurchasedisc($config['params'])) {
            $params['discamt'] = 0;
        }

        $cur = $params['cur'];
        $invamt = $params['cost'];


        $ewt = isset($params['ewt']) ? $params['ewt'] : 0;
        $ext = $params['ext'];



        //AP
        if (!$suppinvoice) {

            if (floatval($ext) != 0) {

                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
                $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($ext * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ext, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

                if ($systype == 'REALESTATE') {
                    $entry['projectid'] = $params['projectid'];
                    $entry['phaseid'] = $params['phaseid'];
                    $entry['modelid'] = $params['modelid'];
                    $entry['blklotid'] = $params['blklotid'];
                    $entry['amenityid'] = $params['amenityid'];
                    $entry['subamenityid'] = $params['subamenityid'];
                }


                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }


            //disc
            if ($periodic) {

                if (floatval($params['discamt']) != 0) {

                    $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
                    $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

                    if ($systype == 'REALESTATE') {
                        $entry['projectid'] = $params['projectid'];
                        $entry['phaseid'] = $params['phaseid'];
                        $entry['modelid'] = $params['modelid'];
                        $entry['blklotid'] = $params['blklotid'];
                        $entry['amenityid'] = $params['amenityid'];
                        $entry['subamenityid'] = $params['subamenityid'];
                    }

                    $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }
            }


            if (floatval($params['tax']) != 0) {
                // input tax
                $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
                $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

                if ($systype == 'REALESTATE') {
                    $entry['projectid'] = $params['projectid'];
                    $entry['phaseid'] = $params['phaseid'];
                    $entry['modelid'] = $params['modelid'];
                    $entry['blklotid'] = $params['blklotid'];
                    $entry['amenityid'] = $params['amenityid'];
                    $entry['subamenityid'] = $params['subamenityid'];
                }


                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
        }

        //INV
        if (floatval($invamt) != 0) {


            $freight = $params['freight'];
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
            $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $invamt + $freight, 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
            $this->coreFunctions->LogConsole('INV ' . $invamt + $freight);

            if ($systype == 'REALESTATE') {
                $entry['projectid'] = $params['projectid'];
                $entry['phaseid'] = $params['phaseid'];
                $entry['modelid'] = $params['modelid'];
                $entry['blklotid'] = $params['blklotid'];
                $entry['amenityid'] = $params['amenityid'];
                $entry['subamenityid'] = $params['subamenityid'];
            }


            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            if ($suppinvoice) {

                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
                $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => $invamt, 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];


                if ($systype == 'REALESTATE') {
                    $entry['projectid'] = $params['projectid'];
                    $entry['phaseid'] = $params['phaseid'];
                    $entry['modelid'] = $params['modelid'];
                    $entry['blklotid'] = $params['blklotid'];
                    $entry['amenityid'] = $params['amenityid'];
                    $entry['subamenityid'] = $params['subamenityid'];
                }


                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
        }
    } //end function

    public function distributionvatex($params, $config)
    {
        $companyid = $config['params']['companyid'];
        $systype = $this->companysetup->getsystemtype($config['params']);
        $entry = [];
        $forex = $params['forex'];
        if ($forex == 0) {
            $forex = 1;
        }
        $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);

        $cur = $params['cur'];
        $invamt = $params['ext'];
        $ewt = isset($params['ewt']) ? $params['ewt'] : 0;
        $ext = $params['ext'];

        //AP
        if (!$suppinvoice) {
            if (floatval($ext) != 0) {
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
                $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => (($ext + $params['tax']) * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ext, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

                if ($systype == 'REALESTATE') {
                    $entry['projectid'] = $params['projectid'];
                    $entry['phaseid'] = $params['phaseid'];
                    $entry['modelid'] = $params['modelid'];
                    $entry['blklotid'] = $params['blklotid'];
                    $entry['amenityid'] = $params['amenityid'];
                    $entry['subamenityid'] = $params['subamenityid'];
                }

                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }


            //disc
            if (floatval($params['discamt']) != 0) {
                $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
                $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

                if ($systype == 'REALESTATE') {
                    $entry['projectid'] = $params['projectid'];
                    $entry['phaseid'] = $params['phaseid'];
                    $entry['modelid'] = $params['modelid'];
                    $entry['blklotid'] = $params['blklotid'];
                    $entry['amenityid'] = $params['amenityid'];
                    $entry['subamenityid'] = $params['subamenityid'];
                }

                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }

            if (floatval($params['tax']) != 0) {
                // input tax
                $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
                $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

                if ($systype == 'REALESTATE') {
                    $entry['projectid'] = $params['projectid'];
                    $entry['phaseid'] = $params['phaseid'];
                    $entry['modelid'] = $params['modelid'];
                    $entry['blklotid'] = $params['blklotid'];
                    $entry['amenityid'] = $params['amenityid'];
                    $entry['subamenityid'] = $params['subamenityid'];
                }


                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
        }

        //INV
        if (floatval($invamt) != 0) {

            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
            $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['cost'] / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
            if ($systype == 'REALESTATE') {
                $entry['projectid'] = $params['projectid'];
                $entry['phaseid'] = $params['phaseid'];
                $entry['modelid'] = $params['modelid'];
                $entry['blklotid'] = $params['blklotid'];
                $entry['amenityid'] = $params['amenityid'];
                $entry['subamenityid'] = $params['subamenityid'];
            }
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            if ($suppinvoice) {
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
                $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => $params['cost'], 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['cost'] / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];

                if ($systype == 'REALESTATE') {
                    $entry['projectid'] = $params['projectid'];
                    $entry['phaseid'] = $params['phaseid'];
                    $entry['modelid'] = $params['modelid'];
                    $entry['blklotid'] = $params['blklotid'];
                    $entry['amenityid'] = $params['amenityid'];
                    $entry['subamenityid'] = $params['subamenityid'];
                }



                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
        }
    } //end function

    private function getstockselect($config)
    {
        $sqlselect = "select stock.trno,stock.line,item.barcode,item.itemname,stock.uom,stock.rem,stock." . $this->hqty . " as qty,
                            FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
                            round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                            case when stock.void=0 then 'false' else 'true' end as void,stock.refx,stock.linex,
                            item.itemid,
                            stock.encodeddate,stock.encodedby,
                            stock.editby,stock.editdate,stock.projectid,
                            FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
                            FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
                           stock.phaseid, ps.code as phasename,  stock.modelid, hm.model as housemodel,stock.blklotid, bl.blk, bl.lot,
                            proj.code as project,
                             amen.description as amenityname, 
                             subamen.description as subamenityname,
                             subamen.line as subamenity,amen.line as amenity,

                            stock.whid,warehouse.client as wh,
                            warehouse.clientname as whname,

                             '' as bgcolor,case when stock.void=0 then '' else 'bg-red-2' end as errcolor";

        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . " 
            from $this->stock as stock
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
             left join client as warehouse on warehouse.clientid=stock.whid
            left join projectmasterfile as proj on proj.line=stock.projectid
           
            left join phase as ps on ps.line = stock.phaseid
            left join housemodel as hm on hm.line = stock.modelid
            left join blklot as bl on bl.line = stock.blklotid
            left join amenities as amen on amen.line= stock.amenityid
            left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

            where stock.trno =? 
            UNION ALL  
            " . $sqlselect . "  
            from $this->hstock as stock
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
           left join client as warehouse on warehouse.clientid=stock.whid
            left join projectmasterfile as proj on proj.line=stock.projectid
           
             left join phase as ps on ps.line = stock.phaseid
                left join housemodel as hm on hm.line = stock.modelid
                left join blklot as bl on bl.line = stock.blklotid
                left join amenities as amen on amen.line= stock.amenityid
                left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

            where stock.trno =? order by line";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    } //end function

    public function openstockline($config)
    {
        $sqlselect = $this->getstockselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = $sqlselect . "  
                from $this->stock as stock
                left join item on item.itemid=stock.itemid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                 left join client as warehouse on warehouse.clientid=stock.whid
                left join projectmasterfile as proj on proj.line=stock.projectid
             
                left join phase as ps on ps.line = stock.phaseid
                left join housemodel as hm on hm.line = stock.modelid
                left join blklot as bl on bl.line = stock.blklotid
                left join amenities as amen on amen.line= stock.amenityid
                left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

                where stock.trno = ? and stock.line = ? ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);
        $data = $this->openstockline($config);
        $data2 = json_decode(json_encode($data), true);

        $msg1 = '';
        $msg2 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
                $msg1 = ' Out of stock ';
            }
        }

        if (!$isupdate) {
            return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
        } else {
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }


    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $this->additem('update', $config);
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;
        $msg1 = '';
        $msg2 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
                //if($data[$key]->refx==0){
                $msg1 = ' Out of stock ';
                //}
            }
        }
        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ')'];
        }
    } //end function
    public function addallitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $msg = 'Successfully saved.';
            $config['params']['data'] = $value;
            $return = $this->additem('insert', $config);
            if ($return['status'] == false) {
                $msg = $return['msg'];
                break;
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
    } //end function

    public function additem($action, $config)
    {

        $trno = $config['params']['trno'];
        $itemid = $config['params']['data']['itemid'];
        $uom = $config['params']['data']['uom'];
        $wh = $config['params']['data']['wh'];
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

        $refx = 0;
        $linex = 0;
        $projectid = 0;
        $phaseid = 0;
        $modelid = 0;
        $blklotid = 0;
        $amenity = 0;
        $subamenity = 0;
        $void = 'false';
        $rrcost = 0;
        $cost = 0;

        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }


        if (isset($config['params']['data']['projectid'])) {
            $projectid = $config['params']['data']['projectid'];
        }


        if (isset($config['params']['data']['phaseid'])) {
            $phaseid = $config['params']['data']['phaseid'];
        }


        if (isset($config['params']['data']['modelid'])) {
            $modelid = $config['params']['data']['modelid'];
        }

        if (isset($config['params']['data']['blklotid'])) {
            $blklotid = $config['params']['data']['blklotid'];
        }

        if (isset($config['params']['data']['amenity'])) {
            $amenity = $config['params']['data']['amenity'];
        }
        if (isset($config['params']['data']['subamenity'])) {
            $subamenity = $config['params']['data']['subamenity'];
        }


        if (isset($config['params']['data']['void'])) {
            $void = $config['params']['data']['void'];
        }

        if (isset($config['params']['data']['rrcost'])) {
            $rrcost = $config['params']['data']['rrcost'];
        }

        if (isset($config['params']['data']['cost'])) {
            $cost = $config['params']['data']['cost'];
        }

        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
            $qty = $config['params']['data']['qty'];
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $qty = $config['params']['data'][$this->dqty];
            $config['params']['line'] = $line;
        }
        $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0 ) $factor = $item[0]->factor;
        }

        $computedata = $this->othersClass->computestock($rrcost, '', $qty, $factor);

        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'uom' => $uom,
            $this->dqty => $qty,
            $this->hqty => $computedata['qty'],

            'refx' => $refx,
            'linex' => $linex,
            'rrcost' => $rrcost,
            'cost' => $computedata['amt'],
            'ext' => $computedata['ext'],
            'projectid' => $projectid,
            'phaseid' => $phaseid,
            'modelid' => $modelid,
            'blklotid' => $blklotid,
            'amenityid' => $amenity,
            'subamenityid' => $subamenity,
            'void' => $void,
            'whid' => $whid
        ];


        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];

        if ($action == 'insert') {
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode);
                //getcostfrom MI
                $cotrno = $this->coreFunctions->getfieldvalue($this->head, "cotrno", "trno=?", [$trno]);
                $cost = $this->coreFunctions->getfieldvalue("glstock", "sum(ext)", "refx=?", [$cotrno]);
                $this->coreFunctions->LogConsole('MI Cost:' . $cost);
                $cost2 = $cost / $factor;
                $computedata = $this->othersClass->computestock($cost2, '', $qty, $factor);
                $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'rrcost' => $cost2, 'ext' => $computedata['ext']], ['trno' => $trno, 'line' => $line]);

                $row = $this->openstockline($config);
                $msg = 'Item was successfully added.';

                return ['row' => $row, 'status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            return $return;
        }
    } // end function




    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return $this->additem('insert', $config);
                break;
            case 'addallitem': // save all item selected from lookup
                return $this->addallitem($config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            case 'saveitem': //save all item edited
                return $this->updateitem($config);
                break;
            case 'saveperitem':
                return $this->updateperitem($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            case 'getpendigco':
                return $this->getpendingco($config);
                break;

            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getpendingco($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $data) {
            $cotrno = $data['trno']; //trno ng construction order            
            $config['params']['data']['uom'] = $data['uom'];
            $config['params']['data']['qty'] = $data['qty'];
            $config['params']['data']['wh'] = $data['wh'];
            $config['params']['data']['itemid'] = $data['itemid'];
            $config['params']['data']['projectid'] = $data['projectid'];
            $config['params']['data']['phaseid'] = $data['phaseid'];
            $config['params']['data']['modelid'] = $data['modelid'];
            $config['params']['data']['blklotid'] = $data['blklotid'];
            $return =  $this->additem('insert', $config);
            if ($return['status']) {
                $this->coreFunctions->sbcupdate('transnum', ['pctrno' => $trno], ['trno' => $cotrno]);
                array_push($rows, $return['row'][0]);
            }
        }

        return [
            'row' => $rows, 'status' => true, 'msg' => 'Item was successfully added' . $msg
        ];
    }

    public function autocreatestock($config)
    {
        $trno = $config['params']['trno'];
        $cotrno = $this->coreFunctions->getfieldvalue($this->head, "cotrno", "trno=?", [$trno]);

        $qry = "select co.docno, ci.itemid, i.itemname, i.barcode, ci.qty,ci.uom, co.projectid,co.phaseid,
        co.modelid, co.blklotid,tr.pctrno,co.trno,co.wh
        from hcohead as co
        left join hcihead as ci on ci.trno=co.citrno
          left join transnum as tr on tr.trno=co.trno
        left join item as i on i.itemid=ci.itemid where tr.pctrno = 0 and co.trno = " . $cotrno;
        $data = $this->coreFunctions->opentable($qry);
        $rows = [];
        $msg = '';
        foreach ($data as $key => $v) { //trno ng construction order            
            $config['params']['data']['uom'] = $data[$key]->uom;
            $config['params']['data']['qty'] = $data[$key]->qty;
            $config['params']['data']['wh'] = $data[$key]->wh;
            $config['params']['data']['itemid'] = $data[$key]->itemid;
            $config['params']['data']['projectid'] = $data[$key]->projectid;
            $config['params']['data']['phaseid'] = $data[$key]->phaseid;
            $config['params']['data']['modelid'] = $data[$key]->modelid;
            $config['params']['data']['blklotid'] = $data[$key]->blklotid;
            $return =  $this->additem('insert', $config);
            if ($return['status']) {
                $this->coreFunctions->sbcupdate('transnum', ['pctrno' => $trno], ['trno' => $cotrno]);
            }
        }
    }



    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->sbcupdate('transnum', ['pctrno' => 0], ['pctrno' => $trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }


    public function deleteitem($config)
    {
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->coreFunctions->sbcupdate('transnum', ['pctrno' => 0], ['pctrno' => $trno]);
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function


    // start
    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);
        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'showemailbtn' => false];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];
        $this->logger->sbcviewreportlog($config);
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
    // end

} //end class
