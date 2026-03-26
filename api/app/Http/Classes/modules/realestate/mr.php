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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class mr
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'MATERIAL REQUEST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showstage' => true];
    public $tablenum = 'transnum';
    public $head = 'mrhead';
    public $hhead = 'hmrhead';
    public $stock = 'mrstock';
    public $hstock = 'hmrstock';
    public $detail = '';
    public $hdetail = '';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    public $dqty = 'isqty';
    public $hqty = 'iss';
    public $damt = 'isamt';
    public $hamt = 'amt';
    private $stockselect;
    private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'wh', 'projectid', 'phaseid', 'modelid', 'blklotid', 'cotrno'];
    private $except = ['trno', 'dateid'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 2289,
            'edit' => 2290,
            'new' => 2291,
            'save' => 2292,
            // 'change' => 2293, remove change doc
            'delete' => 2294,
            'print' => 2295,
            'lock' => 2296,
            'unlock' => 2297,
            'post' => 2298,
            'unpost' => 2299,
            'additem' => 2300,
            'edititem' => 2301,
            'deleteitem' => 2302
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $isproject = $this->companysetup->getisproject($config['params']);
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $projectfilter = '';
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        if ($isproject) {
            $viewall = $this->othersClass->checkAccess($config['params']['user'], 2234);
            $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
            $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
            if ($viewall == '0') {
                $projectfilter = " and head.projectid = " . $projectid . " ";
            }
        }

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }
        $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
     order by dateid desc, docno desc";

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
            'others'
        );
        $buttons = $this->btnClass->create($btns);
        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
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

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'jo', 'title' => 'Job Order Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        return $buttons;
    }

    public function createTab($access, $config)
    {
        $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
        $isexpiry = $this->companysetup->getisexpiry($config['params']);
        $ispallet = $this->companysetup->getispallet($config['params']);
        $column = [
            'action',
            'isqty',
            'uom',
            'project',
            'phasename',
            'housemodel',
            'blk',
            'lot',
            'amenityname',
            'subamenityname',
            'wh'
        ];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        //  'viewref'
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
                'headgridbtns' => ['viewdiagram']
            ],
        ];

        $stockbuttons = ['save', 'delete', 'showbalance'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$blk]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$lot]['readonly'] = true;

        return $obj;
    }

    public function createTab2($access, $config)
    {

        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'generatematerialreq']];
        $mrs = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        $return['Request to Purchase'] = ['icon' => 'fas fa-th-list', 'tab' => $mrs];
        return $return;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['pendigco', 'saveitem', 'deleteallitem'];

        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid =  $config['params']['companyid'];
        $fields = ['docno', 'client', 'clientname', 'dwhname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.readonly', true);
        data_set($col1, 'dwhname.required', true);

        $fields = ['dateid', 'dprojectname', 'phase', 'housemodel', ['blklot', 'lot']];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dprojectname.type', 'lookup');
        data_set($col2, 'dprojectname.action', 'lookupproject');
        data_set($col2, 'dprojectname.lookupclass', 'project');
        data_set($col2, 'dprojectname.addedparams', ['client']);
        data_set($col2, 'phase.addedparams', ['projectid']);
        data_set($col2, 'housemodel.addedparams', ['projectid']);
        data_set($col2, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
        data_set($col2, 'lot.class', 'cslot sbccsreadonly');

        $fields = [['yourref', 'ourref'], 'codocno', 'productionorder', 'rem'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['projectid'] = '0';
        $data[0]['projectname'] = '';
        $data[0]['projectcode'] = '';
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['dwhname'] = '';
        $data[0]['dprojectname'] = '';
        $data[0]['phase'] = '';
        $data[0]['phaseid'] = 0;
        $data[0]['housemodel'] = '';
        $data[0]['modelid'] = 0;
        $data[0]['blklot'] = '';
        $data[0]['lot'] = '';
        $data[0]['blklotid'] = 0;
        $data[0]['codocno'] = '';
        $data[0]['cotrno'] = 0;
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
        $data[0]['whname'] = $name;
        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $isproject = $this->companysetup->getisproject($config['params']);
        $projectfilter = "";

        if ($trno == 0) {
            $trno = $this->othersClass->readprofile('TRNO', $config);
            if ($trno == '') {
                $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
            }
            $config['params']['trno'] = $trno;
        } else {
            $this->othersClass->checkprofile('TRNO', $trno, $config);
        }
        $center = $config['params']['center'];
        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $tablenum = $this->tablenum;

        if ($isproject) {
            $viewall = $this->othersClass->checkAccess($config['params']['user'], 2234);
            $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
            $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
            if ($viewall == '0') {
                $projectfilter = " and head.projectid = " . $projectid . " ";
            }
        }

        $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         head.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         ifnull(agent.client,'') as agent, 
         ifnull(agent.clientname,'') as agentname,'' as dagentname,
         warehouse.client as wh,
         warehouse.clientname as whname, 
         '' as dwhname,
         left(head.due,10) as due, 
          head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         client.groupid,ifnull(project.code,'') as projectcode,
         head.phaseid, ph.code as phase,
         head.modelid, hm.model as housemodel, 
         head.blklotid, bl.blk as blklot, bl.lot,
         cohead.trno as cotrno, cohead.docno as codocno";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid 
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join hcohead as cohead on cohead.trno = head.cotrno
        where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid 
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join hcohead as cohead on cohead.trno = head.cotrno
        where head.trno = ? and num.doc=? and num.center=? " . $projectfilter . " ";

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
            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }

    public function updatehead($config, $isupdate)
    {
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
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if    
            }
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
            $cotrno = $this->coreFunctions->getfieldvalue("mrhead", "cotrno", "trno=?", [$head['trno']]);
            $line = $this->coreFunctions->getfieldvalue("mrstock", "line", "refx=? and trno=?", [$cotrno, $head['trno']]);
            if (!empty($line)) {
                if ($cotrno != $head['cotrno']) {
                    return ['status' => false, 'msg' => "Can`t update, delete first all items that are connected to Previous Construction Instruction"];
                }
            } else {
                $this->autogenerate_constructionorder($config);
            }
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->recomputestock($head, $config);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->autogenerate_constructionorder($config);
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
    } // end function
    public function autogenerate_constructionorder($config)
    {

        $trno = $config['params']['trno'];
        $cotrno = $config['params']['head']['cotrno'];
        $msg = '';
        $qry = "
        select
        stock.trno, stock.line,stock.uom,
        stock.rem,stock.rrqty,stock.qty,stock.qa,stock.refx,stock.linex,
        stock.ref,stock.itemid,stock.whid,stock.amenity,stock.subamenity,
        stock.projectid,stock.phaseid,stock.modelid,stock.blklotid,stock.void,
        head.projectid as projectid2,head.phaseid as phaseid2,head.modelid as modelid2,head.blklotid as blklotid2
        from hcostock as stock
        left join hcohead as head on head.trno = stock.trno
        where stock.trno = ? and stock.qty>stock.qa ";
        $data = $this->coreFunctions->opentable($qry, [$cotrno]);
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $config['params']['data']['uom'] = $data[$key]->uom;
                $config['params']['data']['rem'] = $data[$key]->rem;
                $config['params']['data']['rrqty'] = $data[$key]->rrqty;
                $config['params']['data']['qty'] = $data[$key]->qty;
                $config['params']['data']['prqty'] = $data[$key]->qty;
                $config['params']['data']['ref'] = $data[$key]->ref;
                $config['params']['data']['qa'] = $data[$key]->qa;
                $config['params']['data']['refx'] = $data[$key]->trno;
                $config['params']['data']['linex'] = $data[$key]->line;
                $config['params']['data']['itemid'] = $data[$key]->itemid;
                $config['params']['data']['whid'] = $data[$key]->whid;
                $config['params']['data']['amenity'] = $data[$key]->amenity;
                $config['params']['data']['subamenity'] = $data[$key]->subamenity;
                $config['params']['data']['projectid'] = $data[$key]->projectid;
                $config['params']['data']['blklotid'] = $data[$key]->blklotid;
                $config['params']['data']['modelid'] = $data[$key]->modelid;
                $config['params']['data']['phaseid'] = $data[$key]->phaseid;
                $config['params']['data']['void'] = $data[$key]->void;
                $return =  $this->additem('insert', $config);
                if ($return['status']) {
                    $this->setserveditems($config, $data[$key]->trno, $data[$key]->line, $this->hqty);
                } else {
                    $msg .= $return['msg'];
                }
            }
            $head = [
                'cotrno' => $data[0]->trno,
                'projectid' => $data[0]->projectid2,
                'phaseid' => $data[0]->phaseid2,
                'modelid' => $data[0]->modelid2,
                'blklotid' => $data[0]->blklotid2
            ];
            if ($msg == '') {
                $this->coreFunctions->sbcupdate('mrhead', $head, ['trno' => $trno]);
            }
        };
    }
    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
        $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $table . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

        $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
        $crlimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);

        if (!empty($isitemzeroqty)) {
            return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for head
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
        terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,projectid,phaseid,modelid,blklotid,cotrno)
        SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
        head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
        head.due,head.cur,head.projectid,head.phaseid,head.modelid,head.blklotid,head.cotrno
        FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            // for stock
            $qry = "insert into " . $this->hstock . "(trno, line, uom,disc,rem,iss,isamt,amt, isqty,ext,qa,void,
          encodeddate, encodedby,editdate,editby,loc,expiry,kgs,itemid,whid,projectid,phaseid,modelid,blklotid,refx,linex,amenity,subamenity,prqty,prqa)
          SELECT trno, line, uom,disc,rem,iss,isamt,amt, isqty,ext,qa,void,
          encodeddate, encodedby,editdate,editby,loc,expiry,kgs,itemid,whid,projectid,phaseid,modelid,blklotid,refx,linex,amenity,subamenity,prqty,prqa FROM " . $this->stock . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
                $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
            }
            //if($posthead){      
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($data)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,agent, projectid,phaseid,modelid,blklotid,cotrno)
    select head.trno, head.doc, head.docno, head.client, head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.agent,head.projectid,
    head.phaseid,head.modelid,head.blklotid,head.cotrno
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
    where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $qry = "insert into " . $this->stock . "(
        trno, line, uom,disc,rem,iss,isamt,amt, isqty,ext,qa,void,
        encodeddate, encodedby,editdate,editby,loc,expiry,kgs,itemid,whid,projectid,phaseid,modelid,blklotid,refx,linex,amenity,subamenity,prqty,prqa)
        select trno, line, uom,disc,rem,iss,isamt,amt, isqty,ext,qa,void,
        encodeddate, encodedby,editdate,editby,loc,expiry,kgs,itemid,whid,projectid,phaseid,modelid,blklotid,refx,linex,amenity,subamenity,prqty,prqa
        from " . $this->hstock . " where trno=?";
            //stock
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
            }
        }
    } //end function

    private function updateprojmngmt($config, $stage)
    {
        $trno = $config['params']['trno'];
        $data = $this->openstock($trno, $config);
        $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
        $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

        $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='MI' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

        $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='MI' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

        $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }

        $this->coreFunctions->execqry("update stages set mi=" . $qty . " where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
        return $this->othersClass->updateprojcompletion($config, $proj, $sub, $stage, $trno);
    }

    private function getstockselect($config)
    {
        $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    item.barcode, 
    item.itemname,
    stock.uom, 
    stock.qa, 
    stock." . $this->hamt . ", 
    stock." . $this->hqty . " as iss,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
    FORMAT(stock.prqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as prqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    stock.void,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,
    item.brand,
    stock.rem,
    stock.refx,stock.linex,
    ifnull(uom.factor,1) as uomfactor,
    ifnull(project.name,'') as project,
    ifnull(project.line,0) as projectid,
    stock.phaseid, ph.code as phasename,
    stock.modelid, hm.model as housemodel, 
    stock.blklotid, bl.blk as blk, bl.lot,
    stock.amenity,stock.subamenity,
    am.description as amenityname,subam.description as subamenityname,
    '' as bgcolor,
    '' as errcolor ";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as project on project.line=stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenity
    left join subamenities as subam on subam.line=stock.subamenity and subam.amenityid=stock.amenity
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join $this->hhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom  
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as project on project.line=stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenity
    left join subamenities as subam on subam.line=stock.subamenity and subam.amenityid=stock.amenity
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
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as project on project.line=stock.projectid 
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenity
    left join subamenities as subam on subam.line=stock.subamenity and subam.amenityid=stock.amenity
    where stock.trno = ? and stock.line = ? ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return  $this->additem('insert', $config);
                break;
            case 'addallitem':
                return $this->addallitem($config);
                break;
            case 'quickadd':
                return $this->quickadd($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
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
            case 'getpendigco':
                return $this->getpendingco($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function diagram($config)
    {

        $data = [];
        $nodes = [];
        $links = [];
        $data['width'] = 1500;
        $startx = 100;

        $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so 
     left join hsostock as s on s.trno = so.trno
     left join glstock as sstock on sstock.refx = s.trno
     where sstock.trno = ? 
     group by so.trno,so.docno,so.dateid";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        if (!empty($t)) {
            $startx = 550;
            $a = 0;
            foreach ($t as $key => $value) {
                //SO            
                data_set(
                    $nodes,
                    $t[$key]->docno,
                    [
                        'align' => 'right',
                        'x' => 200,
                        'y' => 50 + $a,
                        'w' => 250,
                        'h' => 80,
                        'type' => $t[$key]->docno,
                        'label' => $t[$key]->rem,
                        'color' => 'blue',
                        'details' => [$t[$key]->dateid]
                    ]
                );
                array_push($links, ['from' => $t[$key]->docno, 'to' => 'sj']);
                $a = $a + 100;
            }
        }

        //SJ
        $qry = "
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where head.trno=?
    group by head.docno, head.dateid, head.trno, ar.bal";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        if (!empty($t)) {
            data_set(
                $nodes,
                'sj',
                [
                    'align' => 'left',
                    'x' => $startx,
                    'y' => 100,
                    'w' => 250,
                    'h' => 80,
                    'type' => $t[0]->docno,
                    'label' => $t[0]->rem,
                    'color' => 'green',
                    'details' => [$t[0]->dateid]
                ]
            );

            foreach ($t as $key => $value) {
                //CR
                $sjtrno = $t[$key]->trno;
                $crqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ?
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ?";
                $crdata = $this->coreFunctions->opentable($crqry, [$sjtrno, $sjtrno]);
                if (!empty($crdata)) {
                    foreach ($crdata as $key2 => $value2) {
                        data_set(
                            $nodes,
                            'cr',
                            [
                                'align' => 'left',
                                'x' => $startx + 400,
                                'y' => 100,
                                'w' => 250,
                                'h' => 80,
                                'type' => $crdata[$key2]->docno,
                                'label' => $crdata[$key2]->rem,
                                'color' => 'red',
                                'details' => [$crdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => 'sj', 'to' => 'cr']);
                        $a = $a + 100;
                    }
                }

                //CM
                $cmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid
        where stock.refx=?
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where stock.refx=?
        group by head.docno, head.dateid";
                $cmdata = $this->coreFunctions->opentable($cmqry, [$sjtrno, $sjtrno]);
                if (!empty($cmdata)) {
                    foreach ($cmdata as $key2 => $value2) {
                        data_set(
                            $nodes,
                            $cmdata[$key2]->docno,
                            [
                                'align' => 'left',
                                'x' => $startx + 400,
                                'y' => 200,
                                'w' => 250,
                                'h' => 80,
                                'type' => $cmdata[$key2]->docno,
                                'label' => $cmdata[$key2]->rem,
                                'color' => 'red',
                                'details' => [$cmdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => 'sj', 'to' => $cmdata[$key2]->docno]);
                        $a = $a + 100;
                    }
                }
            }
        }

        $data['nodes'] = $nodes;
        $data['links'] = $links;

        return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'diagram':
                return $this->diagram($config);
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function getpendingco($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $data) {
            $config['params']['data']['uom'] = $data['uom'];
            $config['params']['data']['rem'] = $data['rem'];
            $config['params']['data']['rrqty'] = $data['rrqty'];
            $config['params']['data']['prqty'] = $data['qty'];
            $config['params']['data']['qty'] = $data['qty'];
            $config['params']['data']['ref'] = $data['ref'];
            $config['params']['data']['qa'] = $data['qa'];
            $config['params']['data']['refx'] = $data['trno'];
            $config['params']['data']['linex'] = $data['line'];
            $config['params']['data']['itemid'] = $data['itemid'];
            $config['params']['data']['whid'] = $data['whid'];
            $config['params']['data']['amenity'] = $data['amenity'];
            $config['params']['data']['subamenity'] = $data['subamenity'];
            $config['params']['data']['projectid'] = $data['projectid'];
            $config['params']['data']['blklotid'] = $data['blklotid'];
            $config['params']['data']['modelid'] = $data['modelid'];
            $config['params']['data']['phaseid'] = $data['phaseid'];
            $return =  $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
            } else {
                $msg .= $return['msg'];
            }
        }

        return [
            'row' => $rows,
            'status' => true,
            'msg' => $msg
        ];
    }
    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);
        $data = $this->openstockline($config);
        $msg = '';
        if ($config['params']['row'][$this->dqty] == 0) {
            $msg = 'Item request quantity have zero quantity';
            return ['row' => $data, 'status' => false, 'msg' => $msg];
        }
        if (!$isupdate) {
            $msg = 'Item request quantity received is greater Construction Order';
            return ['row' => $data, 'status' => false, 'msg' => $msg];
        } else {
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }

    public function updateitem($config)
    {
        $msg = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $update = $this->additem('update', $config);
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;
        $msg1 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
            }
        }

        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty'];
        }
    } //end function

    public function addallitem($config)
    {
        $msg = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $row = $this->additem('insert', $config);
            if ($msg != '') {
                $msg = $msg . ' ' . $row['msg'];
            } else {
                $msg = $row['msg'];
            }
        }

        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $status = true;

        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $status = false;
            }
        }

        return ['inventory' => $data, 'status' => $status, 'msg' => $msg];
    } //end function

    public function quickadd($config)
    {
        $barcodelength = $this->companysetup->getbarcodelength($config['params']);
        $config['params']['barcode'] = trim($config['params']['barcode']);
        if ($barcodelength == 0) {
            $barcode = $config['params']['barcode'];
        } else {
            $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
        }

        $wh = $config['params']['wh'];
        $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom, '' as expiry from item where barcode=?", [$barcode]);
        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            $data = $this->getlatestprice($config);

            if (!empty($data['data'])) {
                $item[0]->amt = $data['data'][0]->amt;
                $item[0]->disc = $data['data'][0]->disc;
                $item[0]->uom = $data['data'][0]->uom;
            }
            $config['params']['data'] = json_decode(json_encode($item[0]), true);
            return $this->additem('insert', $config);
        } else {
            return ['status' => false, 'msg' => 'Barcode not found.', ''];
        }
    }

    // insert and update item
    public function additem($action, $config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];
        $expiry = '';
        $uom = isset($config['params']['data']['uom']) ? $config['params']['data']['uom'] : '';
        $rem = isset($config['params']['data']['rem']) ? $config['params']['data']['rem'] : '';
        $qa = isset($config['params']['data']['qa']) ? $config['params']['data']['qa']  : 0;
        $itemid = isset($config['params']['data']['itemid']) ? $config['params']['data']['itemid'] : 0;
        $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';

        $locid = isset($config['params']['data']['locid']) ? $config['params']['data']['locid'] : 0;
        $palletid = isset($config['params']['data']['palletid']) ? $config['params']['data']['palletid'] : 0;

        $refx =   isset($config['params']['data']['refx']) ? $config['params']['data']['refx'] : 0;
        $linex = isset($config['params']['data']['linex']) ? $config['params']['data']['linex'] : 0;
        $ref = isset($config['params']['data']['ref']) ? $config['params']['data']['ref'] : 0;

        if (isset($config['params']['data']['projectid'])) {
            $projectid = $config['params']['data']['projectid'];
        } else {
            $projectid = $this->coreFunctions->getfieldvalue("mrhead", "projectid", "trno=?", [$trno]);
        }

        if (isset($config['params']['data']['phaseid'])) {
            $phaseid = $config['params']['data']['phaseid'];
        } else {
            $phaseid = $this->coreFunctions->getfieldvalue("mrhead", "phaseid", "trno=?", [$trno]);
        }

        if (isset($config['params']['data']['modelid'])) {
            $modelid = $config['params']['data']['modelid'];
        } else {
            $modelid = $this->coreFunctions->getfieldvalue("mrhead", "modelid", "trno=?", [$trno]);
        }

        if (isset($config['params']['data']['blklotid'])) {
            $blklotid = $config['params']['data']['blklotid'];
        } else {
            $blklotid =  $this->coreFunctions->getfieldvalue("mrhead", "blklotid", "trno=?", [$trno]);
        }

        $amenity = isset($config['params']['data']['amenity']) ? $amenity = $config['params']['data']['amenity'] : 0;
        $subamenity = isset($config['params']['data']['subamenity']) ? $config['params']['data']['subamenity'] : 0;
        $void =  isset($config['params']['data']['void']) ? $config['params']['data']['void'] : 0;

        $disc = '';
        $factor = 1;
        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
            $amt = isset($config['params']['data']['amt']) ? $config['params']['data']['amt'] : 0;
            $qty = $config['params']['data']['qty'];
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;
            $amt = $config['params']['data'][$this->damt];
            $qty = $config['params']['data'][$this->dqty];
        }
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
        $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);
        if (floatval($forex) == 0) {
            $forex = 1;
        }
        $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
        $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
        $wh = isset($config['params']['data']['wh']) ? $config['params']['data']['wh'] : '';
        if ($wh == '') {
            $whid = isset($config['params']['data']['whid']) ? $config['params']['data']['whid'] : 0;
        } else {
            $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
        }
        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'isamt' => $amt,
            $this->hamt => round($computedata['amt'] * $forex, 2),
            $this->hqty => $computedata['qty'],
            $this->dqty => $qty,
            'prqty' => $qty,
            'ext' => $computedata['ext'],
            'qa' => $qa,
            'whid' => $whid,
            'uom' => $uom,
            'rem' => $rem,
            'refx' => $refx,
            'linex' => $linex,
            'expiry' => $expiry,
            'projectid' => $projectid,
            'phaseid' => $phaseid,
            'modelid' => $modelid,
            'blklotid' => $blklotid,
            'amenity' => $amenity,
            'subamenity' => $subamenity,
            'void' => $void

        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];

        //insert item
        if ($action == 'insert') {
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];

            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode);
                $msg = 'Item was successfully added.';
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $coqty = $this->coreFunctions->getfieldvalue('hcostock', 'qty', 'trno=? and line=?', [$refx, $linex]);
            if ($qty > $coqty) {
                $return = false;
            } else {
                if ($qty != 0) {
                    $msg = 'Item was successfully updated.';
                    $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
                    $this->setserveditems($config, $refx, $linex, $this->hqty);
                }
            }
            return $return;
        }
    } // end function

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $data2 = $this->coreFunctions->opentable('select trno,line,refx,linex from ' . $this->stock . ' where trno=?', [$trno]);
        if ($this->companysetup->getserial($config['params'])) {
            foreach ($data2 as $key => $value) {
                $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
            }
        }

        foreach ($data2 as $key => $value) {
            $this->coreFunctions->sbcupdate('hcostock', ['qa' => 0], ['refx' => $data2[$key]->refx, 'linex' => $data2[$key]->linex]);
        }
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];

        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        if ($this->companysetup->getserial($config['params'])) {
            $this->othersClass->deleteserialout($trno, $line);
        }
        $row = $config['params']['row'];
        $this->coreFunctions->sbcupdate('hcostock', ['qa' => 0], ['trno' => $row['refx'], 'line' => $row['linex']]);
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);

        if ($this->companysetup->getisproject($config['params'])) {
            $this->updateprojmngmt($config, $data[0]->stageid);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function

    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $client = $config['params']['client'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(
        select head.docno,head.dateid,
          stock.cost/uom.factor as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid
          where head.doc in ('RR','IS','CM','AJ','TS') and cntnum.center = ?
          and item.barcode = ? 
          and stock.rrcost <> 0 
          UNION ALL
          select head.docno,head.dateid,stock.cost/uom.factor as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          left join uom on uom.itemid = item.itemid
          where head.doc in ('RR','IS','CM','AJ','TS') and cntnum.center = ?
          and item.barcode = ? 
          and stock.rrcost <> 0 
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
        if (!empty($data)) {
            return ['status' => true, 'msg' => 'Found the latest cost...', 'data' => $data];
        } else {
            return ['status' => false, 'msg' => 'No Latest cost found...'];
        }
    } // end function


    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';

        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        $dataparams = $config['params']['dataparams'];
        if ($companyid == 39) { //cbbsi
            if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
            if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
            if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        }
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function recomputestock($head, $config)
    {
        $data = $this->openstock($head['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $exec = true;
        foreach ($data2 as $key => $value) {
            $computedata = $this->othersClass->computestock($data2[$key][$this->damt] * $head['forex'], $data[$key]->disc, $data2[$key][$this->dqty], $data[$key]->uomfactor, 0);
            $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        }
        return $exec;
    }
    public function setserveditems($config, $refx, $linex, $hqty)
    {
        $trno = $config['params']['trno'];
        $filter = "";
        $qry1 = "select stock.iss from mrhead as head left join mrstock as
    stock on stock.trno=head.trno where stock.trno = " . $trno . " and stock.void = 0 and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select stock.iss from hmrhead left join hmrstock as stock on stock.trno=
    hmrhead.trno where stock.trno = " . $trno . " and  stock.void = 0 and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry2 = "select ifnull(sum(" . $hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hcostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }
} //end class
